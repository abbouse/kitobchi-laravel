<?php

namespace App\Support;

use App\Models\Books;
use App\Models\FavouriteProducts;
use App\Models\Stationery;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mahsulot/sotuvchi ko'rinish qoidasining YAGONA manbasi.
 *
 * Avval bu qoida faqat app/Traits/HasProductVisibility.php ichida yashagan
 * va faqat controller instansiyasi ichidan ($this->visibleBooks() kabi)
 * chaqirilishi mumkin edi. Bu esa Blade view'lardagi to'g'ridan-to'g'ri
 * so'rovlar (masalan welcome.blade.php'dagi @php bloklar) va controller
 * instansiyasiga ega bo'lmagan joylar (masalan AppServiceProvider'dagi
 * view composer) uchun qoidani QO'LDA TAKRORLASHga majbur qilardi —
 * natijada ba'zi joylarda seller-active tekshiruvi butunlay tushib
 * qolgan edi (welcome.blade.php'dagi 3 ta so'rov, sevimlilar ro'yxati
 * va uning badge-hisoblagichlari).
 *
 * Endi HasProductVisibility trait ham, Blade view'lar ham shu YAGONA
 * klassni chaqiradi — qoida ikkinchi marta yozilmaydi, shuning uchun
 * biri to'g'irlanib biri eskirib qolish xavfi yo'q.
 *
 * Ko'rinish sharti (stock/count TEKSHIRILMAYDI — ataylab, izoh uchun
 * HasProductVisibility trait'ning boshidagi izohga qarang):
 *   Mahsulot: status=true, is_approved=1, is_hidden=false
 *   Sotuvchi: status='approved', is_hidden=false, parent_id null yoki 0
 */
class ProductVisibilityScope
{
    public static function activeSeller(): \Closure
    {
        return fn ($s) => $s
            ->where('status', 'approved')
            ->where('is_hidden', false)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            });
    }

    public static function applyBooks(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', self::activeSeller());
    }

    public static function applyStationeries(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', self::activeSeller());
    }

    /**
     * Berilgan kitob ID'lari orasidan hozir haqiqatan ko'rinadiganlarini
     * qaytaradi (masalan sevimlilar ro'yxatini filtrlash uchun).
     *
     * @param array<int> $ids
     * @return array<int>
     */
    public static function visibleBookIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return self::applyBooks(Books::query())->whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    public static function visibleStationeryIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return self::applyStationeries(Stationery::query())->whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * Foydalanuvchining sevimlilaridan faqat HOZIR ko'rinadigan
     * (yashirilmagan/bloklanmagan do'konga tegishli bo'lmagan)
     * mahsulotlar sonini sanaydi — header badge va toggle javobidagi
     * hisoblagich shu bilan hisoblanishi kerak, aks holda ko'rsatilgan
     * son foydalanuvchi ko'ra olmaydigan mahsulotlarni ham qo'shib
     * yuboradi.
     */
    public static function visibleFavouriteCount(int $userId): int
    {
        $favs = FavouriteProducts::where('user_id', $userId)
            ->select('id', 'product_id', 'product_type')
            ->get();

        if ($favs->isEmpty()) {
            return 0;
        }

        $bookIds = $favs->where('product_type', 'book')->pluck('product_id')->all();
        $stationeryIds = $favs->where('product_type', 'stationery')->pluck('product_id')->all();

        return count(self::visibleBookIds($bookIds)) + count(self::visibleStationeryIds($stationeryIds));
    }
}
