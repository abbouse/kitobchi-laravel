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
        // GLOBAL KATALOG: tasdiqlangan kartaga ulangan taklif (do'kon faqat narx
        // va qoldiq kiritadi) — mazmuni allaqachon tekshirilgan, darhol sotuvda.
        if ($product instanceof Books && $product->edition_id) {
            $edition = \App\Models\BookEdition::find($product->edition_id);
            if ($edition && $edition->status === \App\Models\BookEdition::STATUS_ACTIVE && $edition->verified_at !== null) {
                $product->is_approved = 1;
                $product->ai_moderation_status = 'approved';
                $product->ai_moderation_checked_at = now();
                $product->ai_moderation_note = 'Katalog kartasi orqali qo\'shildi';
                $product->ai_moderation_meta = [
                    'source' => 'catalog_offer',
                    'edition_id' => (int) $edition->id,
                ];

                return;
            }
        }

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
