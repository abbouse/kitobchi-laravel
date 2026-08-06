<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Sotuvda kerakli miqdorni to'liq qopli olmagan holatda tashlanadi (masalan,
 * ikkita mijoz oxirgi donani deyarli bir vaqtda sotib olmoqchi bo'lganda —
 * biri checkout tekshiruvidan o'tib bo'lgach, ikkinchisi filial-stockni
 * lock bilan kamaytirishga uringanda yetarli qoldiq topmaydi).
 *
 * Bu istisno BranchStockService::decrementForSale() ichidagi DB::transaction
 * yopilishidan OLDIN tashlanadi — shu tufayli o'sha (qisman) kamaytirish
 * avtomatik ravishda bekor (rollback) bo'ladi, keyin tashqi controller
 * o'zining butun buyurtma yaratish tranzaksiyasini ham bekor qiladi.
 * Natijada: yetarli stock bo'lmasa, buyurtma UMUMAN yaratilmaydi —
 * avvalgidek "jim qisqartirib qo'yish" (clamp) emas.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly string $productType,
        public readonly int $productId,
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(sprintf(
            'Yetarli qoldiq yo\'q: %s #%d (variant #%d) — so\'ralgan %d, mavjud %d.',
            $productType,
            $productId,
            $variantId,
            $requested,
            $available,
        ));
    }
}
