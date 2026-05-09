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

        $imageUrls = ProductImageUrls::build($product->images ?? []);

        $payload = [
            'id' => $product->id,
            'type' => $type,
            'product_type' => $type,
            'name' => $product->name,
            'author' => $isBook ? ($product->author ?? null) : null,
            'material' => $isBook ? null : ($product->material ?? null),
            'category_id' => $product->category_id ?? null,
            'images' => self::normalizeImages($product->images ?? []),
            'image_urls' => $imageUrls['original'],
            'medium_images' => $imageUrls['medium'],
            'thumb_images' => $imageUrls['thumb'],
            'description' => $product->description ?? null,
            'price' => $product->price ?? 0,
            'discountPrice' => $isBook
                ? ($product->discountPrice ?? $product->price ?? 0)
                : ($product->discount_price ?? $product->price ?? 0),
            'count' => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
            'stock' => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
            'sales' => $product->totalSales ?? 0,
            'weekly_sales' => $product->totalSalesWeek ?? 0,
            'lang' => $isBook ? ($product->lang ?? "O'zbek") : null,
            'langType' => $isBook ? ($product->langType ?? '') : null,
            'coverType' => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year' => $isBook ? ($product->year ?? now()->year) : null,
            'ugc_aggregate_score' => (float) ($product->ugc_aggregate_score ?? 0),
            'ugc_reviews_count' => (int) ($product->ugc_reviews_count ?? 0),
            'ugc_last_scored_at' => optional($product->ugc_last_scored_at)?->toIso8601String(),
            'favourite' => $favourite,
            'category' => self::formatCategory($product, $options['category_format'] ?? 'title'),
            'tags' => self::formatTags($product),
            'seller' => self::formatSeller($product),
        ];

        if (($options['include_variants'] ?? true) && !$isBook) {
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

    private static function resolveFavourite($product, $user, string $type): bool
    {
        if (!$user || !$product?->id) {
            return false;
        }

        return FavouriteProducts::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('product_type', $type)
            ->exists();
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
        ])->values()->toArray();
    }
}
