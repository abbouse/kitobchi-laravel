<?php

namespace App\Traits;

use App\Models\Books;
use App\Models\Stationery;

/**
 * Mahsulot va do'kon ko'rinish shartlarini qo'llash uchun trait.
 *
 * Ko'rinish shartlari (stock/count TEKSHIRILMAYDI):
 *   status      = true   → false: do'kon tomonidan o'chirilgan
 *   is_approved = 1      → 0: moderatsiyada, 2: rejected
 *   is_hidden   = false  → true: o'chirilgan mahsulot
 *
 * Seller faolligi:
 *   status    = 'approved'
 *   is_hidden = false
 *
 * Nima uchun stock tekshirilmaydi:
 *   - Stationery da variant bo'lsa parent stock noto'g'ri bo'lishi mumkin
 *   - User mahsulotni ko'rishi kerak, Item sahifasida "tugagan" badge ko'rinadi
 *   - Qolmagan mahsulot ham tavsiya va search da foydali
 */
trait HasProductVisibility
{
    /**
     * Faol seller sharti
     */
    protected function activeSeller(): \Closure
    {
        return fn($s) => $s
            ->where('status', 'approved')
            ->where('is_hidden', false)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            });
    }

    /**
     * Ko'rinadigan kitoblar query si
     */
    protected function visibleBooks(array $with = []): \Illuminate\Database\Eloquent\Builder
    {
        $q = Books::query();
        $with = array_values(array_unique(array_merge($with, ['authorProfile'])));

        $q->with($with);

        return $q
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', $this->activeSeller());
    }

    /**
     * Ko'rinadigan kantselyariya mahsulotlari query si
     */
    protected function visibleStationeries(array $with = []): \Illuminate\Database\Eloquent\Builder
    {
        $q = Stationery::query();

        if (!empty($with)) {
            $q->with($with);
        }

        return $q
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', $this->activeSeller());
    }

    /**
     * Mavjud query ga book visibility shartlarini qo'shish
     */
    protected function applyBookVisibility($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', $this->activeSeller());
    }

    /**
     * Mavjud query ga stationery visibility shartlarini qo'shish
     */
    protected function applyStationeryVisibility($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', false)
            ->whereHas('seller', $this->activeSeller());
    }
}
