<?php

namespace App\Services;

use App\Exceptions\PaylovApiException;
use App\Models\Books;
use App\Models\Sold;
use App\Models\SplitContract;
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

        return $this->registerForTransaction($transaction, $order);
    }

    /**
     * Bitta real Paylov tranzaksiyasi uchun bitta fiskal chek yaratadi.
     * Split to'lovlarida order bitta, tranzaksiyalar esa bir nechta bo'ladi.
     */
    public function registerForTransaction(Transaction $transaction, ?Sold $order = null): bool
    {
        if (! $this->isEnabled()
            || (string) $transaction->provider !== 'paylov'
            || (int) $transaction->state !== 2
            || blank($transaction->provider_transaction_id)
        ) {
            return false;
        }

        $order ??= $transaction->order;
        if (! $order) {
            return false;
        }

        // Allaqachon fiskalizatsiya qilinganmi?
        $existing = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        if (filled($existing['qr_code_url'] ?? null)) {
            return true;
        }

        $expectedAmount = (string) $transaction->payment_type === 'split'
            ? max(0, (int) $transaction->amount)
            : null;
        $items = $this->buildFiscalItems($order, $expectedAmount);
        if ($items === []) {
            Log::warning('[Paylov OFD] Fiscal items build failed', ['order_id' => $order->id]);

            return false;
        }

        $paylov = PaylovService::make();
        $receiptMeta = $this->receiptMetaFor($transaction);
        $this->markAttempt($transaction, $receiptMeta);

        try {
            $response = $paylov->registerFiscalReceipt(
                (string) $transaction->provider_transaction_id,
                $items,
                $receiptMeta['receipt_type'],
                $receiptMeta['advance_contract_id'],
            );

            $ofd = data_get($response, 'result.ofd', []);

            return $this->storePerformData($transaction, is_array($ofd) ? $ofd : []);
        } catch (\Throwable $e) {
            // Chek allaqachon yaratilgan bo'lsa — statusdan olib saqlaymiz
            if (str_contains($e->getMessage(), 'ofd_check_already_generated')) {
                return $this->syncTransactionFromStatus($transaction);
            }

            $this->markFailure($transaction, $e);

            Log::error('[Paylov OFD] Register failed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->provider_transaction_id,
                'error' => $e->getMessage(),
            ]);

            if (! $e instanceof PaylovApiException || $e->isRetryable()) {
                throw $e; // Tarmoq va vaqtinchalik OFD xatolarida job retry qiladi.
            }

            return false;
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

        return $this->syncTransactionFromStatus($transaction);
    }

    public function syncTransactionFromStatus(Transaction $transaction): bool
    {
        if (! $this->isEnabled() || blank($transaction->provider_transaction_id)) {
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
                'order_id' => $transaction->order_id,
                'transaction_id' => $transaction->provider_transaction_id,
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
    public function buildFiscalItems(Sold $order, ?int $expectedAmount = null): array
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

        // OFD har bir itemda tin YOKI pinfl talab qiladi. Chek HAR DOIM
        // platforma (Kitobchi) STIRi bilan yaratiladi — sotuvchining shaxsiy
        // INN/PINFLi ishlatilmaydi. Sabab: to'lovni mijozdan sotuvchi emas,
        // platforma (Paylov merchant) qabul qiladi, shuning uchun fiskal chek
        // ham platforma nomidan bo'lishi kerak. Ilgari sotuvchining o'z INNi
        // bo'lsa o'shani ishlatishga urinilardi — bu sotuvchining Paylov
        // submerchant profili faol bo'lmaganda "commitent/subcommission not
        // active" OFD xatosini keltirib chiqarardi (yuqoridagi izohga qarang).
        $taxId = ['tin' => $tin !== '' ? $tin : null, 'pinfl' => null];

        $items = [];

        foreach ($orderItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            // Yakuniy bekor qilingan itemlar mijozdan olinadigan summaga
            // kirmaydi. Ularni OFD payloadida qoldirish Paylovda eski seller
            // STIRi bilan "commitent/subcommission not active" xatosini ham
            // chiqarishi mumkin.
            $isCancelled = filter_var($row['is_cancelled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isCancelled || filled($row['cancelled_at'] ?? null)) {
                continue;
            }

            $type = (string) ($row['type'] ?? 'book');
            $unitPrice = (int) round(((float) ($row['item_price'] ?? 0)) * $mult);
            $count = max(1, (int) ($row['count_item'] ?? 1));

            // OFD 0 so'mlik pozitsiyani qabul qilmaydi (validation_error).
            // Bepul sovg'alar summaga ta'sir qilmaydi — chekka kiritmaymiz.
            if ($unitPrice <= 0) {
                continue;
            }

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

            // OFD: har bir itemda tin YOKI pinfl bo'lishi SHART (platforma STIRi)
            if ($taxId['tin']) {
                $item['tin'] = $taxId['tin'];
            } elseif ($taxId['pinfl']) {
                $item['pinfl'] = $taxId['pinfl'];
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

            // Xizmatlar platforma nomidan — platforma STIRi
            if ($tin !== '') {
                $item['tin'] = $tin;
            }

            $items[] = $item;
        }

        // IKPU yoki STIR/PINFL yetishmagan item bo'lsa — yubormaymiz (OFD rad etadi)
        foreach ($items as $item) {
            if ($item['code'] === '' || $item['package_code'] === '') {
                Log::warning('[Paylov OFD] Item without IKPU/package code', [
                    'order_id' => $order->id,
                    'title' => $item['title'],
                ]);

                return [];
            }

            if (empty($item['tin']) && empty($item['pinfl'])) {
                Log::warning('[Paylov OFD] Item without tin/pinfl — set PAYLOV_OFD_TIN in .env', [
                    'order_id' => $order->id,
                    'title' => $item['title'],
                ]);

                return [];
            }
        }

        // ── Summani tranzaksiyaga tenglashtirish ───────────────────────
        // Promo/keshbek chegirmalari itemlar narxida aks etmagan — farqni
        // discount sifatida eng katta itemlardan boshlab taqsimlaymiz.
        $expected = (int) round(((float) ($expectedAmount ?? $order->amount)) * $mult);
        if ($expected <= 0) {
            return [];
        }
        $itemsTotal = array_sum(array_map(fn ($item) => $item['price'] * $item['count'], $items));

        $diff = $itemsTotal - $expected; // musbat = chegirma kerak

        if ($diff > 0) {
            [$items, $diff] = $this->allocateDiscount($items, $diff);
        }

        $resolvedTotal = array_sum(array_map(
            fn ($item) => ($item['price'] - $item['discount']) * $item['count'],
            $items,
        ));

        if ($diff !== 0 || $resolvedTotal !== $expected) {
            Log::warning('[Paylov OFD] Items total mismatch with order amount', [
                'order_id' => $order->id,
                'expected' => $expected,
                'items_total' => $itemsTotal,
                'resolved_total' => $resolvedTotal,
                'unresolved_diff' => $diff,
            ]);

            return [];
        }

        return $items;
    }

    /**
     * Paylov discountni dona narxiga qo'llaydi va u price'dan oshmasligi kerak.
     * Umumiy chegirma quantityga qoldiqsiz bo'linmasa, line ko'pi bilan ikki
     * guruhga ajratiladi: masalan 3 dona va 100 tiyin => 2×33 + 1×34.
     *
     * @return array{0: array, 1: int} [items, unresolved discount]
     */
    private function allocateDiscount(array $items, int $discount): array
    {
        usort($items, fn ($left, $right) => ($right['price'] * $right['count']) <=> ($left['price'] * $left['count']));
        $result = [];
        $remaining = max(0, $discount);

        foreach ($items as $item) {
            $count = max(1, (int) $item['count']);
            $price = max(1, (int) $item['price']);
            $lineDiscount = min($remaining, max(0, ($price - 1) * $count));
            $remaining -= $lineDiscount;

            if ($lineDiscount <= 0) {
                $item['discount'] = 0;
                $result[] = $item;

                continue;
            }

            $baseDiscount = intdiv($lineDiscount, $count);
            $remainder = $lineDiscount % $count;
            $baseCount = $count - $remainder;

            if ($baseCount > 0) {
                $baseItem = $item;
                $baseItem['count'] = $baseCount;
                $baseItem['discount'] = $baseDiscount;
                $result[] = $baseItem;
            }

            if ($remainder > 0) {
                $remainderItem = $item;
                $remainderItem['count'] = $remainder;
                $remainderItem['discount'] = $baseDiscount + 1;
                $result[] = $remainderItem;
            }
        }

        return [$result, $remaining];
    }

    // ─── Yordamchilar ────────────────────────────────────────────────────────

    private function paidTransactionFor(Sold $order): ?Transaction
    {
        return Transaction::query()
            ->where('payment_type', 'order')
            ->where('order_id', $order->id)
            ->where('provider', 'paylov')
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

        $existing = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        $transaction->forceFill([
            'perform_fiscal_data' => array_merge($existing, [
                'status' => 'registered',
                'qr_code_url' => $receiptUrl,
                'terminal_id' => $ofd['terminalId'] ?? null,
                'receipt_id' => $ofd['receiptId'] ?? null,
                'fiscal_sign' => $ofd['fiscalSign'] ?? null,
                'date' => now()->toDateTimeString(),
                'last_error' => null,
                'last_error_code' => null,
                'last_error_field' => null,
            ]),
        ])->save();

        Log::info('[Paylov OFD] Fiscal receipt stored', [
            'transaction_id' => $transaction->provider_transaction_id,
            'receipt_id' => $ofd['receiptId'] ?? null,
        ]);

        return true;
    }

    private function markAttempt(Transaction $transaction, array $receiptMeta): void
    {
        $data = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        $transaction->forceFill([
            'perform_fiscal_data' => array_merge($data, [
                'status' => 'pending',
                'flow' => $receiptMeta['flow'],
                'receipt_type' => $receiptMeta['receipt_type'],
                'attempts' => ((int) ($data['attempts'] ?? 0)) + 1,
                'last_attempt_at' => now()->toDateTimeString(),
            ]),
        ])->save();
    }

    private function markFailure(Transaction $transaction, \Throwable $error): void
    {
        $transaction->refresh();
        $data = is_array($transaction->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        $transaction->forceFill([
            'perform_fiscal_data' => array_merge($data, [
                'status' => 'failed',
                'last_error' => mb_substr($error->getMessage(), 0, 1000),
                'last_error_code' => $error instanceof PaylovApiException ? $error->apiCode : class_basename($error),
                'last_error_field' => $error instanceof PaylovApiException
                    ? ($error->errorData['field'] ?? null)
                    : null,
                'last_error_data' => $error instanceof PaylovApiException && $error->errorData !== []
                    ? $error->errorData
                    : null,
                'last_failed_at' => now()->toDateTimeString(),
            ]),
        ])->save();
    }

    /**
     * Oddiy order hech qachon global split/avans konfiguratsiyasini meros olmaydi.
     * Bu ajratish noto'g'ri "Bo'nak (Avans)" chek yaratilishining oldini oladi.
     *
     * @return array{flow: string, receipt_type: ?int, advance_contract_id: ?string}
     */
    private function receiptMetaFor(Transaction $transaction): array
    {
        if ((string) $transaction->payment_type !== 'split') {
            return [
                'flow' => 'standard',
                'receipt_type' => null,
                'advance_contract_id' => null,
            ];
        }

        $receiptType = (int) data_get(
            $transaction->provider_response,
            'fiscal_receipt_type',
            config('services.paylov.ofd.split_credit_receipt_type', 2),
        );

        return [
            'flow' => $receiptType === 1 ? 'split_advance' : 'split_credit',
            'receipt_type' => $receiptType,
            'advance_contract_id' => $this->splitContractReference($transaction),
        ];
    }

    private function splitContractReference(Transaction $transaction): ?string
    {
        foreach ([
            'fiscal_contract_id',
            'create.result.advanceContractId',
            'pay.result.advanceContractId',
            'transaction.advanceContractId',
            'advanceContractId',
        ] as $path) {
            $value = trim((string) data_get($transaction->provider_response, $path, ''));
            if ($value !== '') {
                return $value;
            }
        }

        $contractId = (int) data_get($transaction->provider_response, 'split_contract_id', 0);
        if ($contractId > 0) {
            $contract = SplitContract::query()->find($contractId);
            if ($contract) {
                $meta = is_array($contract->meta) ? $contract->meta : [];
                $reference = trim((string) ($meta['fiscal_contract_id'] ?? ''));
                if ($reference === '') {
                    $reference = 'N-'.str_pad((string) $contract->id, 8, '0', STR_PAD_LEFT);
                    $contract->forceFill([
                        'meta' => array_merge($meta, ['fiscal_contract_id' => $reference]),
                    ])->save();
                }

                return $reference;
            }
        }

        // Eski tranzaksiyalar uchun vaqtinchalik fallback. Yangi shartnomalar
        // har doim o'zining alohida fiscal_contract_id qiymatiga ega bo'ladi.
        $configured = trim((string) config('services.paylov.ofd.split_advance_contract_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        return null;
    }
}
