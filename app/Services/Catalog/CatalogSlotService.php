<?php

namespace App\Services\Catalog;

use App\Models\Books;
use App\Models\CatalogSlotPurchase;
use App\Models\CatalogSlotSetting;
use App\Models\Seller;
use App\Models\SellerBalanceEntry;
use Illuminate\Support\Facades\DB;

/**
 * KATALOG JOYINI SOTISH.
 *
 * Bitta kitob kartasida bitta joy bo'ladi va u eksklyuziv: joy band bo'lsa
 * boshqa do'kon o'sha kartaga sotib ololmaydi. To'lov do'kon balansidan
 * darhol yechiladi (premium obuna kabi), admin rad etsa qaytariladi.
 *
 * Joy g'olibga `BuyBoxService` orqali ta'sir qiladi — `books.catalog_featured`
 * ga hech qachon to'g'ridan-to'g'ri yozilmaydi, aks holda keyingi qayta
 * hisoblash uni o'chirib yuborardi.
 */
class CatalogSlotService
{
    public function settings(): CatalogSlotSetting
    {
        return CatalogSlotSetting::current();
    }

    /** Kunlik proratsiya — reklama tizimidagi kabi (oylik narx / 30 * kun). */
    public function priceFor(int $days, ?CatalogSlotSetting $settings = null): int
    {
        $settings ??= $this->settings();

        return (int) round(((int) $settings->price_per_month / 30) * max(1, $days));
    }

    /**
     * Kartani band qilib turgan joy (tasdiq kutayotgan yoki faol). Bo'lmasa null.
     */
    public function occupiedFor(int $editionId): ?CatalogSlotPurchase
    {
        return CatalogSlotPurchase::query()->blocking()->where('edition_id', $editionId)->first();
    }

    /** Buy box uchun: kartada hozir kuchda bo'lgan pullik taklif id'si. */
    public static function runningBookId(int $editionId): ?int
    {
        $id = CatalogSlotPurchase::query()->running()->where('edition_id', $editionId)->value('book_id');

        return $id ? (int) $id : null;
    }

    /**
     * Do'kon joyni sotib oladi. Balans darhol yechiladi, joy moderatsiyaga
     * tushadi.
     *
     * @return array{ok: bool, code?: string, message?: string, purchase?: CatalogSlotPurchase}
     */
    public function purchase(Seller $seller, Books $book, int $days): array
    {
        $settings = $this->settings();

        if (! $settings->is_active || (int) $settings->price_per_month <= 0) {
            return ['ok' => false, 'code' => 'disabled', 'message' => 'Katalog joyi hozircha sotuvda emas.'];
        }

        if ($days < (int) $settings->min_days || $days > (int) $settings->max_days) {
            return [
                'ok' => false,
                'code' => 'bad_days',
                'message' => "Muddat {$settings->min_days}-{$settings->max_days} kun oralig'ida bo'lishi kerak.",
            ];
        }

        $editionId = (int) ($book->edition_id ?? 0);
        if ($editionId <= 0) {
            return ['ok' => false, 'code' => 'no_edition', 'message' => 'Bu kitob global katalogga ulanmagan.'];
        }

        if ((int) $book->seller_id !== (int) $seller->id) {
            return ['ok' => false, 'code' => 'not_yours', 'message' => "Bu kitob sizning do'koningizga tegishli emas."];
        }

        if ((bool) $book->is_hidden || $book->archived_at !== null || (int) $book->is_approved !== 1) {
            return ['ok' => false, 'code' => 'not_sellable', 'message' => "Kitob sotuvda emas: avval uni moderatsiyadan o'tkazing."];
        }

        $price = $this->priceFor($days, $settings);

        return DB::transaction(function () use ($seller, $book, $days, $price, $editionId) {
            // Band joyni ikki do'kon bir vaqtda olmasligi uchun qulflaymiz.
            $busy = CatalogSlotPurchase::query()
                ->where('edition_id', $editionId)
                ->whereIn('status', CatalogSlotPurchase::BLOCKING)
                ->lockForUpdate()
                ->get()
                ->first(fn (CatalogSlotPurchase $p) => $p->ends_at === null || $p->ends_at->greaterThan(now()));

            if ($busy) {
                return [
                    'ok' => false,
                    'code' => 'slot_taken',
                    'message' => $busy->ends_at
                        ? 'Bu kitobda joy band. ' . $busy->ends_at->format('d.m.Y') . " dan keyin bo'shaydi."
                        : 'Bu kitobda joy band.',
                ];
            }

            $fresh = Seller::query()->whereKey($seller->id)->lockForUpdate()->first();
            if (! $fresh || (int) $fresh->balance < $price) {
                return [
                    'ok' => false,
                    'code' => 'insufficient_balance',
                    'message' => 'Balans yetarli emas. Kerak: ' . number_format($price, 0, '.', ' ') . " so'm.",
                ];
            }

            $purchase = CatalogSlotPurchase::create([
                'edition_id' => $editionId,
                'seller_id' => $fresh->id,
                'book_id' => $book->id,
                'status' => CatalogSlotPurchase::STATUS_PENDING,
                'days' => $days,
                'price_uzs' => $price,
                'charged_at' => now(),
            ]);

            SellerBalanceEntry::record(
                $fresh, -$price, SellerBalanceEntry::TYPE_CATALOG_SLOT,
                'catalog_slot_purchase', (int) $purchase->id,
                'Katalog joyi - ' . $days . ' kun'
            );

            return ['ok' => true, 'purchase' => $purchase->fresh()];
        });
    }

    /** Admin tasdiqlaydi: muddat SHU PAYTDAN boshlanadi (moderatsiya kuni yo'qolmaydi). */
    public function approve(CatalogSlotPurchase $purchase, ?int $reviewerId): CatalogSlotPurchase
    {
        $purchase->forceFill([
            'status' => CatalogSlotPurchase::STATUS_ACTIVE,
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
            'reject_reason' => null,
            'starts_at' => now(),
            'ends_at' => now()->addDays((int) $purchase->days),
        ])->save();

        app(BuyBoxService::class)->recompute((int) $purchase->edition_id);

        return $purchase;
    }

    /** Admin rad etadi - pul to'liq qaytadi. */
    public function reject(CatalogSlotPurchase $purchase, ?int $reviewerId, ?string $reason = null): CatalogSlotPurchase
    {
        DB::transaction(function () use ($purchase, $reviewerId, $reason) {
            $this->refund($purchase, 'Katalog joyi rad etildi');

            $purchase->forceFill([
                'status' => CatalogSlotPurchase::STATUS_REJECTED,
                'reviewer_id' => $reviewerId,
                'reviewed_at' => now(),
                'reject_reason' => $reason,
            ])->save();
        });

        app(BuyBoxService::class)->recompute((int) $purchase->edition_id);

        return $purchase;
    }

    /** Do'kon o'zi bekor qiladi - faqat hali tasdiqlanmagan joyni. */
    public function cancel(CatalogSlotPurchase $purchase): array
    {
        if ($purchase->status !== CatalogSlotPurchase::STATUS_PENDING) {
            return ['ok' => false, 'code' => 'not_pending', 'message' => 'Faqat tasdiq kutayotgan joyni bekor qilish mumkin.'];
        }

        DB::transaction(function () use ($purchase) {
            $this->refund($purchase, 'Katalog joyi bekor qilindi');
            $purchase->forceFill(['status' => CatalogSlotPurchase::STATUS_CANCELLED])->save();
        });

        return ['ok' => true, 'purchase' => $purchase->fresh()];
    }

    /** Muddati tugaganlarni yopadi va kartalarni qayta hisoblaydi. */
    public function expireDue(): int
    {
        $due = CatalogSlotPurchase::query()
            ->where('status', CatalogSlotPurchase::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        $buyBox = app(BuyBoxService::class);
        foreach ($due as $purchase) {
            $purchase->forceFill(['status' => CatalogSlotPurchase::STATUS_EXPIRED])->save();
            $buyBox->recompute((int) $purchase->edition_id);
        }

        return $due->count();
    }

    /** Pulni bir marta qaytarish (ikki marta qaytmasligi kafolatlanadi). */
    private function refund(CatalogSlotPurchase $purchase, string $note): void
    {
        if ($purchase->charged_at === null || $purchase->refunded_at !== null) {
            return;
        }

        $seller = Seller::query()->whereKey($purchase->seller_id)->lockForUpdate()->first();
        if (! $seller) {
            return;
        }

        SellerBalanceEntry::record(
            $seller, (int) $purchase->price_uzs, SellerBalanceEntry::TYPE_CATALOG_SLOT_REFUND,
            'catalog_slot_purchase', (int) $purchase->id, $note
        );

        $purchase->forceFill(['refunded_at' => now()])->save();
    }
}
