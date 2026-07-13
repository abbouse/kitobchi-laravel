<?php

namespace App\Observers;

use App\Models\Books;
use App\Models\Stationery;
use App\Services\ProductModerationStateService;

class ProductModerationObserver
{
    private const BOOK_FIELDS = [
        'name', 'author', 'author_id', 'translator', 'isbn', 'category_id',
        'images', 'description', 'price', 'discountPrice', 'discountExpiresAt',
        'lang', 'langType', 'coverType', 'year', 'pages', 'publisher_id',
    ];

    private const STATIONERY_FIELDS = [
        'name', 'barcode', 'material', 'category_id', 'images', 'description',
        'price', 'discount_price', 'discountExpiresAt',
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
