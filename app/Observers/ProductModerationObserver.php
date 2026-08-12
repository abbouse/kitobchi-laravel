<?php

namespace App\Observers;

use App\Models\Books;
use App\Models\Stationery;
use App\Services\ProductModerationStateService;

class ProductModerationObserver
{
    // MUHIM — faqat MAHSULOT MOHIYATINI (nima ekanligini, kim yozganini,
    // qanday ko'rinishini) belgilaydigan maydonlar AI moderatsiyani qayta
    // ishga tushiradi. Narx/chegirma, ISBN, sahifa soni, yil, muqova turi,
    // tarjimon, shtrix-kod kabi "ikkinchi darajali" maydonlar — bular
    // moderatorning "bu listing firibgarlik/mos emasmi" qaroriga deyarli
    // ta'sir qilmaydi — seller o'zgartirganda DARHOL ko'rinadi, qayta
    // tekshiruv kutmaydi. Ro'yxatni qisqartirish qarori: eski (keng)
    // ro'yxat har bir narx/chegirma tahririda ham butun listingni qaytadan
    // AI navbatiga yuborardi — bu ham keraksiz OpenAI xarajati, ham
    // sellerga foydasiz kutish edi.
    private const BOOK_FIELDS = [
        'name', 'author', 'author_id', 'publisher_id', 'category_id',
        'images', 'description', 'lang',
    ];

    // Qayta moderatsiya TALAB QILMAYDIGAN (darhol ko'rinadigan) maydonlar:
    // translator, isbn, price, discountPrice, discountExpiresAt, langType,
    // coverType, year, pages, barcode, discount_price.
    private const STATIONERY_FIELDS = [
        'name', 'material', 'category_id', 'images', 'description',
    ];

    public function __construct(
        private readonly ProductModerationStateService $state,
    ) {}

    public function creating(Books|Stationery $product): void
    {
        $this->state->applyPendingAttributes($product, 'product_created');
    }

    public function updating(Books|Stationery $product): void
    {
        $fields = $product instanceof Books ? self::BOOK_FIELDS : self::STATIONERY_FIELDS;
        if ($product->isDirty($fields)) {
            $this->state->applyPendingAttributes($product, 'product_content_changed');
        }
    }
}
