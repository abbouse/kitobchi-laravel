<?php

namespace App\Traits;

use App\Models\Books;
use App\Models\Stationery;
use App\Support\ProductVisibilityScope;

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
 *
 * MUHIM: shart matnining o'zi endi app/Support/ProductVisibilityScope.php
 * ichida — bitta joyda. Bu trait faqat controller'lar uchun qulay
 * $this->visibleBooks() kabi qisqa nom beradi, lekin qoidani QAYTA
 * YOZMAYDI. Controller instansiyasiga ega bo'lmagan joylar (Blade
 * view'lar, service provider'lar) ProductVisibilityScope'ni to'g'ridan-
 * to'g'ri chaqiradi — ikkalasi doim bir xil natija beradi.
 */
trait HasProductVisibility
{
    /**
     * Faol seller sharti
     */
    protected function activeSeller(): \Closure
    {
        return ProductVisibilityScope::activeSeller();
    }

    /**
     * Ko'rinadigan kitoblar query si
     */
    protected function visibleBooks(array $with = []): \Illuminate\Database\Eloquent\Builder
    {
        $q = Books::query();
        $with = array_values(array_unique(array_merge($with, ['authorProfile'])));

        $q->with($with);

        // FILIAL STOCK: `count` accessor uchun jami mavjud stockni bitta
        // subselect bilan yuklaydi (N+1 oldini oladi)
        return ProductVisibilityScope::applyBooks($q->withAvailableTotal());
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

        // FILIAL STOCK: `stock` accessor uchun subselect (N+1 oldini oladi)
        return ProductVisibilityScope::applyStationeries($q->withAvailableTotal());
    }

    /**
     * Mavjud query ga book visibility shartlarini qo'shish
     */
    protected function applyBookVisibility($query): \Illuminate\Database\Eloquent\Builder
    {
        return ProductVisibilityScope::applyBooks($query);
    }

    /**
     * Mavjud query ga stationery visibility shartlarini qo'shish
     */
    protected function applyStationeryVisibility($query): \Illuminate\Database\Eloquent\Builder
    {
        return ProductVisibilityScope::applyStationeries($query);
    }
}
