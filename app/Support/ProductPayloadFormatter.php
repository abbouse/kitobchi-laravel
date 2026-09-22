<?php

namespace App\Support;

use App\Models\Books;
use App\Models\FavouriteProducts;

class ProductPayloadFormatter
{
    public static function format($product, array $options = []): ?array
    {
        if (!$product) {
            return null;
        }

        $type = $options['type']
            ?? ($product instanceof Books ? 'book' : 'stationery');
        $isBook = $type === 'book';
        $user = $options['user'] ?? null;
        $favourite = array_key_exists('favourite', $options)
            ? (bool) $options['favourite']
            : self::resolveFavourite($product, $user, $type);

        $normalizedImages = self::normalizeImages($product->images ?? []);
        $imageUrls = ProductImageUrls::build($normalizedImages);
        $mode = $options['mode'] ?? 'card';
        $isDetail = $mode === 'detail';

        $payload = [
            'id' => $product->id,
            'artikul' => $product->artikul ?? null,
            'type' => $type,
            'product_type' => $type,
            'name' => $product->name,
            'author' => $isBook ? ($product->author ?? null) : null,
            'material' => $isBook ? null : ($product->material ?? null),
            'category_id' => $product->category_id ?? null,
            'images' => $normalizedImages,
            'image' => $imageUrls['medium'][0] ?? $imageUrls['original'][0] ?? $imageUrls['thumb'][0] ?? null,
            'image_urls' => $imageUrls['original'],
            'medium_images' => $imageUrls['medium'],
            'thumb_images' => $imageUrls['thumb'],
            'price' => $product->price ?? 0,
            'discountPrice' => $isBook
                ? ($product->discountPrice ?? $product->price ?? 0)
                : ($product->discount_price ?? $product->price ?? 0),
            'count' => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
            'stock' => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
            // Ko'rinish uchun yashiringan qoldiq: haqiqiy son yuqori chegaradan
            // katta bo'lsa "10+" kabi ko'rsatiladi. `count`/`stock` maydonlari
            // yuqorida haqiqiy qiymat bilan qoladi — ular savat/checkout
            // limitlash (stepper max) uchun ishlatiladi va bu yerda TEGILMAYDI.
            'stock_display' => self::stockDisplayLabel($isBook ? ($product->count ?? 0) : ($product->stock ?? 0)),
            'sales' => $product->totalSales ?? 0,
            'weekly_sales' => $product->totalSalesWeek ?? 0,
            'ugc_aggregate_score' => (float) ($product->ugc_aggregate_score ?? 0),
            'ugc_reviews_count' => (int) ($product->ugc_reviews_count ?? 0),
            'ugc_last_scored_at' => optional($product->ugc_last_scored_at)?->toIso8601String(),
            'favourite' => $favourite,
            'category' => self::formatCategory($product, $options['category_format'] ?? 'title'),
            'tags' => self::formatTags($product),
            'seller' => self::formatSeller($product),
        ];

        // GLOBAL KATALOG (qo'shimcha maydonlar — eski ilovalar e'tiborsiz qoldiradi)
        if ($isBook) {
            $payload['edition_id'] = $product->edition_id ? (int) $product->edition_id : null;
            $payload['condition'] = $product->condition ?? 'new';
            if ($product->edition_id && $product->relationLoaded('edition') && $product->edition) {
                $edition = $product->edition;
                $payload['offers_count'] = (int) ($edition->in_stock_offers_count ?: $edition->offers_count);
                $payload['min_price'] = $edition->min_price !== null ? (int) $edition->min_price : null;
            } else {
                $payload['offers_count'] = 1;
                $payload['min_price'] = null;
            }
        }

        if ($isDetail) {
            $payload['description'] = $product->description ?? null;
            $payload['isbn'] = $isBook ? ($product->isbn ?? null) : null;
            $payload['pages'] = $isBook ? ($product->pages ?? null) : null;
            $payload['publisher'] = $isBook ? ($product->publisher?->name ?? null) : null;
            $payload['barcode'] = $isBook ? null : ($product->barcode ?? null);
            $payload['lang'] = $isBook ? ($product->lang ?? "O'zbek") : null;
            $payload['langType'] = $isBook ? ($product->langType ?? '') : null;
            $payload['coverType'] = $isBook ? ($product->coverType ?? 'Yumshoq') : null;
            $payload['year'] = $isBook ? ($product->year ?? now()->year) : null;

            if ($isBook) {
                $payload['offers'] = $product->edition_id
                    ? CatalogOffers::forEdition((int) $product->edition_id, (int) $product->id)
                    : [];
            }
        }

        if (($options['include_variants'] ?? $isDetail) && !$isBook) {
            $payload['variants'] = self::formatVariants($product);
        }

        if (!empty($options['extra']) && is_array($options['extra'])) {
            $payload = array_merge($payload, $options['extra']);
        }

        if (!empty($options['seller_extra']) && is_array($options['seller_extra'])) {
            $payload['seller'] = array_merge($payload['seller'] ?? [], $options['seller_extra']);
        }

        return $payload;
    }

    /**
     * Request-scoped favourite kesh: "userId:type" => [product_id => true].
     * Bir foydalanuvchining sevimlilari bir marta yuklanadi, keyin xotiradan
     * tekshiriladi. Natija avvalgidek — faqat N ta so'rov 1 taga tushadi.
     * (Octane yo'q — static har request'da tozalanadi.)
     */
    private static array $favouriteCache = [];

    private static function resolveFavourite($product, $user, string $type): bool
    {
        if (!$user || !$product?->id) {
            return false;
        }

        $key = $user->id . ':' . $type;
        if (!array_key_exists($key, self::$favouriteCache)) {
            self::$favouriteCache[$key] = FavouriteProducts::where('user_id', $user->id)
                ->where('product_type', $type)
                ->pluck('product_id')
                ->flip()
                ->all();
        }

        return isset(self::$favouriteCache[$key][$product->id]);
    }

    private static function normalizeImages($images): array
    {
        if (is_array($images)) {
            return array_values(array_filter($images));
        }

        if (is_string($images) && $images !== '') {
            $decoded = json_decode($images, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }

            return [trim($images)];
        }

        return [];
    }

    private static function formatCategory($product, string $format)
    {
        $category = $product->category ?? null;

        if (!$category) {
            return $format === 'object' ? null : null;
        }

        if ($format === 'object') {
            return [
                'id' => $category->id,
                'title' => $category->title ?? null,
                'name' => $category->name ?? null,
                'name_uz' => $category->name_uz ?? null,
                'name_ru' => $category->name_ru ?? null,
                'name_en' => $category->name_en ?? null,
                'name_ja' => $category->name_ja ?? null,
                'slug' => $category->slug ?? null,
                'icon' => $category->icon ?? null,
            ];
        }

        return $category->title
            ?? $category->name
            ?? $category->name_uz
            ?? null;
    }

    private static function formatTags($product): array
    {
        if (!$product->relationLoaded('tags') || !$product->tags) {
            return [];
        }

        return $product->tags->map(fn($tag) => [
            'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
            'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
            'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
            'ja' => $tag->tag_name_ja ?? $tag->name_ja ?? null,
        ])->filter()->values()->toArray();
    }

    private static function formatSeller($product): array
    {
        $seller = $product->seller ?? null;

        return [
            'seller_id' => $seller?->id,
            'shop_name' => $seller?->shop_name,
            'photo' => $seller?->photo,
            'rating' => (float) ($seller?->rating ?? 0),
            'rating_reviews_count' => (int) ($seller?->rating_reviews_count ?? 0),
            'reputation_score' => (float) ($seller?->reputation_score ?? 0),
            'isVerified' => $seller?->isVerified ?? false,
            'is_verified' => $seller?->isVerified ?? false,
        ];
    }

    private static function formatVariants($product): ?array
    {
        if (!$product->relationLoaded('variants')) {
            return null;
        }

        return $product->variants->map(fn($variant) => [
            'id' => $variant->id,
            'color_name' => $variant->color_name,
            'image' => $variant->image_path ?? null,
            'image_url' => ProductImageUrls::originalUrl($variant->image_path ?? null),
            'image_medium_url' => ProductImageUrls::variantUrl($variant->image_path ?? null, 'medium'),
            'image_thumb_url' => ProductImageUrls::variantUrl($variant->image_path ?? null, 'thumb'),
            'stock' => $variant->stock,
            'stock_display' => self::stockDisplayLabel($variant->stock ?? 0),
        ])->values()->toArray();
    }

    /**
     * Mijozga ko'rsatiladigan qoldiq yorlig'i: chegaradan katta bo'lsa
     * "{cap}+", aks holda aniq son (string sifatida — front-end qo'shimcha
     * formatlashsiz to'g'ridan-to'g'ri matnga qo'ya oladi).
     */
    public static function stockDisplayLabel(int $realStock): string
    {
        $cap = (int) config('catalog.stock_display_cap', 10);

        if ($realStock > $cap) {
            return $cap.'+';
        }

        return (string) max(0, $realStock);
    }
}
