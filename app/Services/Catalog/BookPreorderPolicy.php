<?php

namespace App\Services\Catalog;

use App\Models\BookEdition;
use App\Models\Books;
use Illuminate\Support\Carbon;

/**
 * OLDINDAN BUYURTMA QOIDALARI.
 *
 * Faqat hali chiqmagan, nashr etilayotgan kitob uchun:
 *  - jo'natish kuni ertadan boshlab eng ko'pi 27 kun ichida (karta hold 28 kun);
 *  - kitob yili o'tgan yillarga tegishli bo'lmasin (eski kitob "yangi" emas);
 *  - kitob boshqa do'konlarda allaqachon oddiy sotuvda bo'lmasin — u holda
 *    u chiqib bo'lgan, oldindan buyurtmaning ma'nosi yo'q.
 *
 * Narxni do'kon o'zi belgilaydi, qoldiq — qabul qilinadigan nusxalar soni
 * (sotilgani sayin kamayadi, oddiy qoldiq kabi).
 */
class BookPreorderPolicy
{
    /**
     * Karta hold Paylov'da eng ko'pi 28 kun turadi; jo'natish kuni pul
     * yechilishi uchun 1 kun zaxira qoldiriladi.
     */
    public const MAX_DAYS = 27;

    /**
     * @return array{0: ?Carbon, 1: ?string} [sana yoki null, xato matni yoki null]
     */
    public function resolve(mixed $rawDate, ?BookEdition $edition, int $storeSellerId): array
    {
        if ($rawDate === null || trim((string) $rawDate) === '') {
            return [null, null]; // oldindan buyurtma o'chirildi
        }

        try {
            $date = Carbon::parse((string) $rawDate)->startOfDay();
        } catch (\Throwable) {
            return [null, "Jo'natish sanasi noto'g'ri"];
        }

        if (! $date->isAfter(today())) {
            return [null, "Jo'natish sanasi ertadan keyingi kun bo'lishi kerak"];
        }

        if ($date->isAfter(today()->addDays(self::MAX_DAYS))) {
            return [null, 'Oldindan buyurtma eng ko\'pi '.self::MAX_DAYS.' kunga ochiladi'];
        }

        if ($edition) {
            $year = (int) ($edition->year ?? 0);
            if ($year > 0 && $year < (int) now()->year) {
                return [null, "Bu kitob {$year}-yilda chiqqan. Oldindan buyurtma faqat hali chiqmagan yangi kitoblar uchun"];
            }

            $releasedElsewhere = Books::query()
                ->where('edition_id', $edition->id)
                ->where('seller_id', '!=', $storeSellerId)
                ->where('is_hidden', false)
                ->whereNull('archived_at')
                ->where('status', true)
                ->where(fn ($q) => $q->whereNull('preorder_release_date')->orWhere('preorder_release_date', '<=', today()))
                ->whereStockAvailable('>', 0)
                ->exists();

            if ($releasedElsewhere) {
                return [null, "Bu kitob boshqa do'konlarda allaqachon sotuvda. Oldindan buyurtma faqat hali chiqmagan kitoblar uchun"];
            }
        }

        return [$date, null];
    }
}
