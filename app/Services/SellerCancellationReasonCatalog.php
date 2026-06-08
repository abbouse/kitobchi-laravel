<?php

namespace App\Services;

class SellerCancellationReasonCatalog
{
    public const ITEM_REASONS = [
        'product_out_of_stock' => [
            'uz' => 'Mahsulot do‘konda tugab qolgan.',
            'ru' => 'Товар закончился в магазине.',
            'en' => 'The product is out of stock in the store.',
            'ja' => 'この商品は店舗で在庫切れになりました。',
        ],
        'product_discontinued' => [
            'uz' => 'Mahsulot endi sotuvda yo‘q.',
            'ru' => 'Товар больше не продаётся.',
            'en' => 'This product is no longer available for sale.',
            'ja' => 'この商品は現在販売終了です。',
        ],
        'wrong_listing' => [
            'uz' => 'Mahsulot kartasi xato joylangan.',
            'ru' => 'Карточка товара была опубликована с ошибкой.',
            'en' => 'The product listing was published incorrectly.',
            'ja' => '商品情報が誤って掲載されていました。',
        ],
        'product_damaged' => [
            'uz' => 'Mahsulot jo‘natishdan oldin shikastlangan.',
            'ru' => 'Товар был повреждён до отправки.',
            'en' => 'The product was damaged before shipment.',
            'ja' => '発送前に商品が破損していることが判明しました。',
        ],
        'seller_issue' => [
            'uz' => 'Do‘kon buyurtmani hozircha bajara olmaydi.',
            'ru' => 'Магазин сейчас не может выполнить заказ.',
            'en' => 'The store is currently unable to fulfill the order.',
            'ja' => '店舗側の事情により現在この注文を処理できません。',
        ],
    ];

    public const ORDER_REASONS = [
        'all_products_out_of_stock' => [
            'uz' => 'Buyurtmadagi mahsulotlar do‘konda tugab qolgan.',
            'ru' => 'Товары из заказа закончились в магазине.',
            'en' => 'The products in this order are out of stock in the store.',
            'ja' => 'この注文内の商品は店舗で在庫切れになりました。',
        ],
        'catalog_removed' => [
            'uz' => 'Buyurtmadagi mahsulotlar endi sotuvda yo‘q.',
            'ru' => 'Товары из заказа больше не продаются.',
            'en' => 'The products in this order are no longer available for sale.',
            'ja' => 'この注文内の商品は現在販売終了です。',
        ],
        'shop_inventory_unavailable' => [
            'uz' => 'Do‘kon zaxirasi bu buyurtma uchun mavjud emas.',
            'ru' => 'Склад магазина недоступен для этого заказа.',
            'en' => 'The store inventory is unavailable for this order.',
            'ja' => 'この注文に必要な店舗在庫を確保できませんでした。',
        ],
        'seller_issue' => [
            'uz' => 'Do‘kon buyurtmani hozircha bajara olmaydi.',
            'ru' => 'Магазин сейчас не может выполнить заказ.',
            'en' => 'The store is currently unable to fulfill the order.',
            'ja' => '店舗側の事情により現在この注文を処理できません。',
        ],
    ];

    public static function itemReasonPayload(string $reasonCode, ?string $customNote = null): array
    {
        return self::payload(self::ITEM_REASONS, $reasonCode, $customNote);
    }

    public static function orderReasonPayload(string $reasonCode, ?string $customNote = null): array
    {
        return self::payload(self::ORDER_REASONS, $reasonCode, $customNote);
    }

    public static function itemStockZeroReasons(): array
    {
        return ['product_out_of_stock', 'product_discontinued', 'wrong_listing'];
    }

    public static function itemSelectableCodes(): array
    {
        return array_merge(array_keys(self::ITEM_REASONS), ['custom']);
    }

    public static function itemOptions(): array
    {
        return self::mapOptions(self::ITEM_REASONS, self::itemStockZeroReasons());
    }

    public static function orderStockZeroReasons(): array
    {
        return ['all_products_out_of_stock', 'catalog_removed', 'shop_inventory_unavailable'];
    }

    public static function orderSelectableCodes(): array
    {
        return array_merge(array_keys(self::ORDER_REASONS), ['custom']);
    }

    public static function orderOptions(): array
    {
        return self::mapOptions(self::ORDER_REASONS, self::orderStockZeroReasons());
    }

    private static function payload(array $map, string $reasonCode, ?string $customNote): array
    {
        $reasonCode = trim($reasonCode);

        if ($reasonCode === 'custom') {
            $custom = trim((string) $customNote);

            return [
                'code' => 'custom',
                'notes' => [
                    'uz' => $custom,
                    'ru' => $custom,
                    'en' => $custom,
                    'ja' => $custom,
                ],
                'custom_note' => $custom,
            ];
        }

        $notes = $map[$reasonCode] ?? null;
        if (! is_array($notes)) {
            throw new \RuntimeException('Noto‘g‘ri bekor qilish sababi.');
        }

        return [
            'code' => $reasonCode,
            'notes' => $notes,
            'custom_note' => null,
        ];
    }

    private static function mapOptions(array $map, array $stockZeroCodes): array
    {
        $options = [];

        foreach ($map as $code => $notes) {
            $options[] = [
                'code' => $code,
                'notes' => $notes,
                'auto_zero_stock' => in_array($code, $stockZeroCodes, true),
            ];
        }

        $options[] = [
            'code' => 'custom',
            'notes' => [
                'uz' => 'Boshqa sababni o‘zim yozaman.',
                'ru' => 'Укажу другую причину вручную.',
                'en' => 'I will write another reason manually.',
                'ja' => 'その他の理由を手動で入力します。',
            ],
            'auto_zero_stock' => false,
        ];

        return $options;
    }
}
