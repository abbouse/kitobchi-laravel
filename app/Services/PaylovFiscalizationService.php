<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Paylov OFD fiskalizatsiyasi — buyurtma to'lovi uchun fiskal chek yaratish.
 *
 * Oqim (https://developer.paylov.uz/uz/subscribe/ofd/register):
 *   1. Karta to'lovi muvaffaqiyatli bo'lgach registerForOrder chaqiriladi
 *      (queued job orqali — to'lov oqimini sekinlashtirmaydi)
 *   2. Chek ma'lumotlari transactions.perform_fiscal_data ga saqlanadi —
 *      PurchaseController::appendFiscalReceiptMeta aynan shu kalitlarni o'qiydi
 *   3. Refund: card cancel (/payment/cancel/) fiskalizatsiyani avtomatik bekor
 *      qiladi; p2p refundlarda refundForOrder chaqiriladi
 *
 * MUHIM: sum(items) tranzaksiya summasiga TENG bo'lishi shart (invalid_amount),
 * shuning uchun promo/keshbek chegirmalari itemlarga taqsimlanadi va qoldiq
 * farq eng katta itemning discount'iga singdiriladi.
 */
class PaylovFiscalizationService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.paylov.ofd.enabled', false);
    }

    // ─── Ro'yxatdan o'tkazish ────────────────────────────────────────────────

    /**
     * Buyurtma uchun fiskal chek yaratadi.
     *
     * @return bool true — chek yaratildi yoki allaqachon mavjud
     */
    public function registerForOrder(Sold $order): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $transaction = $this->paidTransactionFor($order);
        if (! $transaction || blank($transaction->provider_transaction_id)) {
            return false;
        }

        // Allaqachon fiskalizatsiya qilinganmi?
        $existing = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        if (filled($existing['qr_code_url'] ?? null)) {
            return true;
        }

        $items = $this->buildFiscalItems($order);
        if ($items === []) {
            Log::warning('[Paylov OFD] Fiscal items build failed', ['order_id' => $order->id]);

            return false;
        }

        $paylov = PaylovService::make();

        try {
            $response = $paylov->registerFiscalReceipt(
                (string) $transaction->provider_transaction_id,
                $items,
            );

            $ofd = data_get($response, 'result.ofd', []);

            return $this->storePerformData($transaction, is_array($ofd) ? $ofd : []);
        } catch (\Throwable $e) {
            // Chek allaqachon yaratilgan bo'lsa — statusdan olib saqlaymiz
            if (str_contains($e->getMessage(), 'ofd_check_already_generated')) {
                return $this->syncFromStatus($order);
            }

            Log::error('[Paylov OFD] Register failed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->provider_transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Job retry qilishi uchun
        }
    }

    /**
     * Fiskal chek holatini Paylovdan olib, lokalga saqlaydi (backfill).
     */
    public function syncFromStatus(Sold $order): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $transaction = $this->paidTransactionFor($order);
        if (! $transaction || blank($transaction->provider_transaction_id)) {
            return false;
        }

        try {
            $response = PaylovService::make()->getFiscalReceipt(
                transactionId: (string) $transaction->provider_transaction_id,
            );

            $result = data_get($response, 'result', []);
            if (! is_array($result) || empty($result['receiptUrl'] ?? null)) {
                return false;
            }

            return $this->storePerformData($transaction, $result);
        } catch (\Throwable $e) {
            Log::warning('[Paylov OFD] Status sync failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * P2P refund holatida refund fiskal chek yaratadi.
     * (Card cancel uchun kerak emas — Paylov o'zi bekor qiladi.)
     */
    public function refundForOrder(Sold $order): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $transaction = $this->paidTransactionFor($order);
        if (! $transaction) {
            return false;
        }

        $perform = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        $receiptId = (int) ($perform['receipt_id'] ?? 0);

        if ($receiptId <= 0) {
            // Chek lokalda yo'q — statusdan olishga urinamiz
            if (! $this->syncFromStatus($order)) {
                return false;
            }

            $transaction->refresh();
            $perform = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
            $receiptId = (int) ($perform['receipt_id'] ?? 0);

            if ($receiptId <= 0) {
                return false;
            }
        }

        try {
            $response = PaylovService::make()->refundFiscalReceipt($receiptId);
            $refund = data_get($response, 'result.ofd.refund', []);

            if (! is_array($refund) || empty($refund['receiptUrl'] ?? null)) {
                return false;
            }

            $transaction->forceFill([
                'cancel_fiscal_data' => [
                    'qr_code_url' => (string) $refund['receiptUrl'],
                    'terminal_id' => $refund['terminalId'] ?? null,
                    'receipt_id' => $refund['receiptId'] ?? null,
                    'fiscal_sign' => $refund['fiscalSign'] ?? null,
                    'date' => now()->toDateTimeString(),
                ],
            ])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('[Paylov OFD] Refund receipt failed', [
                'order_id' => $order->id,
                'receipt_id' => $receiptId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ─── Items yig'ish ───────────────────────────────────────────────────────

    /**
     * Buyurtmadan OFD items ro'yxatini tuzadi.
     * sum(price*count - discount) == order.amount * multiplier bo'lishi shart.
     */
    public function buildFiscalItems(Sold $order): array
    {
        $cfg = (array) config('services.paylov.ofd', []);
        $mult = max(1, (int) ($cfg['amount_multiplier'] ?? 100));
        $vat = max(0, (int) ($cfg['vat_percent'] ?? 0));
        $tin = trim((string) ($cfg['tin'] ?? ''));

        $orderItems = collect(is_array($order->items) ? $order->items : []);
        if ($orderItems->isEmpty()) {
            return [];
        }

        // Mahsulot IKPU kodlarini bitta so'rovda olamiz.
        // Aniqlash zanjiri: mahsulot kodi → kategoriya kodi → config default.
        $bookIds = $orderItems->where('type', 'book')->pluck('item_id')->filter()->all();
        $statIds = $orderItems->whereIn('type', ['stationery', 'gift'])->pluck('item_id')->filter()->all();

        $bookCodes = empty($bookIds) ? collect() : Books::query()
            ->with('category:id,ofd_ikpu_code,ofd_package_code')
            ->whereIn('id', $bookIds)->get(['id', 'category_id', 'ofd_ikpu_code', 'ofd_package_code'])->keyBy('id');
        $statCodes = empty($statIds) ? collect() : Stationery::query()
            ->with('category:id,ofd_ikpu_code,ofd_package_code')
            ->whereIn('id', $statIds)->get(['id', 'category_id', 'ofd_ikpu_code', 'ofd_package_code'])->keyBy('id');

        $items = [];

        foreach ($orderItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = (string) ($row['type'] ?? 'book');
            $unitPrice = (int) round(((float) ($row['item_price'] ?? 0)) * $mult);
            $count = max(1, (int) ($row['count_item'] ?? 1));

            $product = $type === 'book'
                ? $bookCodes->get((int) ($row['item_id'] ?? 0))
                : $statCodes->get((int) ($row['item_id'] ?? 0));

            $defaultIkpu = $type === 'book'
                ? (string) ($cfg['book_ikpu'] ?? '')
                : (string) ($cfg['stationery_ikpu'] ?? '');
            $defaultPackage = $type === 'book'
                ? (string) ($cfg['book_package_code'] ?? '')
                : (string) ($cfg['stationery_package_code'] ?? '');

            // Zanjir: mahsulot → kategoriya → global default
            $categoryIkpu = (string) ($product?->category?->ofd_ikpu_code ?? '');
            $categoryPackage = (string) ($product?->category?->ofd_package_code ?? '');

            $item = [
                'title' => mb_substr((string) ($row['name'] ?? 'Mahsulot'), 0, 250),
                'price' => $unitPrice,
                'discount' => 0,
                'count' => $count,
                'code' => trim((string) ($product?->ofd_ikpu_code ?: ($categoryIkpu ?: $defaultIkpu))),
                'vat_percent' => $vat,
                'package_code' => trim((string) ($product?->ofd_package_code ?: ($categoryPackage ?: $defaultPackage))),
            ];

            if ($tin !== '') {
                $item['tin'] = $tin;
            }

            $items[] = $item;
        }

        // Yetkazish va qadoqlash — alohida xizmat itemlari
        foreach ([
            ['amount' => (int) ($order->deliveryPrice ?? 0), 'title' => 'Yetkazib berish xizmati'],
            ['amount' => (int) ($order->packaging_price ?? 0), 'title' => 'Qadoqlash xizmati'],
        ] as $service) {
            if ($service['amount'] <= 0) {
                continue;
            }

            $item = [
                'title' => $service['title'],
                'price' => $service['amount'] * $mult,
                'discount' => 0,
                'count' => 1,
                'code' => trim((string) ($cfg['service_ikpu'] ?? '')),
                'vat_percent' => $vat,
                'package_code' => trim((string) ($cfg['service_package_code'] ?? '')),
            ];

            if ($tin !== '') {
                $item['tin'] = $tin;
            }

            $items[] = $item;
        }

        // IKPU topilmagan item bo'lsa — yubormaymiz (OFD rad etadi)
        foreach ($items as $item) {
            if ($item['code'] === '' || $item['package_code'] === '') {
                Log::warning('[Paylov OFD] Item without IKPU/package code', [
                    'order_id' => $order->id,
                    'title' => $item['title'],
                ]);

                return [];
            }
        }

        // ── Summani tranzaksiyaga tenglashtirish ───────────────────────
        // Promo/keshbek chegirmalari itemlar narxida aks etmagan — farqni
        // discount sifatida eng katta itemlardan boshlab taqsimlaymiz.
        $expected = (int) round(((float) $order->amount) * $mult);
        $itemsTotal = array_sum(array_map(
            fn ($i) => $i['price'] * $i['count'] - $i['discount'],
            $items,
        ));

        $diff = $itemsTotal - $expected; // musbat = chegirma kerak

        if ($diff > 0) {
            // Eng katta line-totaldan boshlab chegirma singdiramiz
            $indexes = array_keys($items);
            usort($indexes, fn ($a, $b) => ($items[$b]['price'] * $items[$b]['count'])
                <=> ($items[$a]['price'] * $items[$a]['count']));

            foreach ($indexes as $idx) {
                if ($diff <= 0) {
                    break;
                }

                $lineMax = $items[$idx]['price'] * $items[$idx]['count'];
                $apply = min($diff, $lineMax);
                $items[$idx]['discount'] = $apply;
                $diff -= $apply;
            }
        }

        if ($diff !== 0) {
            Log::warning('[Paylov OFD] Items total mismatch with order amount', [
                'order_id' => $order->id,
                'expected' => $expected,
                'items_total' => $itemsTotal,
                'unresolved_diff' => $diff,
            ]);

            return [];
        }

        return $items;
    }

    // ─── Yordamchilar ────────────────────────────────────────────────────────

    private function paidTransactionFor(Sold $order): ?Transaction
    {
        return Transaction::query()
            ->where('payment_type', 'order')
            ->where('order_id', $order->id)
            ->where('state', 2)
            ->latest('id')
            ->first();
    }

    /**
     * OFD javobini appendFiscalReceiptMeta o'qiydigan kalitlar bilan saqlaydi.
     */
    private function storePerformData(Transaction $transaction, array $ofd): bool
    {
        $receiptUrl = (string) ($ofd['receiptUrl'] ?? '');
        if ($receiptUrl === '') {
            return false;
        }

        $transaction->forceFill([
            'perform_fiscal_data' => [
                'qr_code_url' => $receiptUrl,
                'terminal_id' => $ofd['terminalId'] ?? null,
                'receipt_id' => $ofd['receiptId'] ?? null,
                'fiscal_sign' => $ofd['fiscalSign'] ?? null,
                'date' => now()->toDateTimeString(),
            ],
        ])->save();

        Log::info('[Paylov OFD] Fiscal receipt stored', [
            'transaction_id' => $transaction->provider_transaction_id,
            'receipt_id' => $ofd['receiptId'] ?? null,
        ]);

        return true;
    }
}
