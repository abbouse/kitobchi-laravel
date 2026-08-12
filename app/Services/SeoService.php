<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Sanitizatsiyalangan va toza meta description matnini avtomatik generatsiya qilish.
     */
    public function cleanDescription(?string $text, string $fallbackName = 'Kitobchi'): string
    {
        $raw = strip_tags($text ?? '');
        $clean = trim(preg_replace('/\s+/u', ' ', $raw));

        if ($clean === '') {
            return "{$fallbackName} — Kitobchi marketpleysida eng qulay narxda xarid qiling. O'zbekiston bo'ylab yetkazib berish.";
        }

        return Str::limit($clean, 155, '…');
    }

    /**
     * Kitob uchun 100% avtomatlashtirilgan JSON-LD schemasini qurish.
     * (Product + BreadcrumbList)
     */
    public function buildBookSchemas(Books $book, string $canonicalUrl): array
    {
        $isAvailable = $book->count > 0;
        $price = $book->discountPrice && $book->discountPrice < $book->price
            ? (float) $book->discountPrice
            : (float) $book->price;

        $imgUrl = $book->first_image
            ? asset('storage/' . $book->first_image)
            : url('/images/logo/logo_blue.png');

        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $canonicalUrl . '#product',
            'name' => $book->name,
            'image' => [$imgUrl],
            'description' => $this->cleanDescription($book->description, $book->name),
            'sku' => $book->artikul ?: "BOOK-{$book->id}",
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'UZS',
                'price' => $price,
                'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
                'availability' => $isAvailable ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $canonicalUrl,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $book->seller?->shop_name ?: 'Kitobchi Marketplace',
                ],
            ],
        ];

        if ($book->isbn) {
            $productSchema['isbn'] = $book->isbn;
        }

        if ($book->author) {
            $productSchema['author'] = [
                '@type' => 'Person',
                'name' => $book->author,
            ];
        }

        if ($book->publisher?->name) {
            $productSchema['brand'] = [
                '@type' => 'Brand',
                'name' => $book->publisher->name,
            ];
        }

        if ($book->ugc_reviews_count && $book->ugc_aggregate_score) {
            $productSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $book->ugc_aggregate_score, 1),
                'reviewCount' => (int) $book->ugc_reviews_count,
            ];
        }

        // Google Search BreadcrumbList Schema
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Bosh sahifa',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Katalog',
                    'item' => route('web.catalog'),
                ],
            ],
        ];

        if ($book->category) {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $book->category->name ?? 'Kitoblar',
                'item' => route('web.catalog', ['category' => $book->category_id]),
            ];
        }

        $breadcrumbSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbSchema['itemListElement']) + 1,
            'name' => $book->name,
            'item' => $canonicalUrl,
        ];

        return [$productSchema, $breadcrumbSchema];
    }

    /**
     * Kanselyariya uchun 100% avtomatlashtirilgan JSON-LD schemasini qurish.
     */
    public function buildStationerySchemas(Stationery $item, string $canonicalUrl): array
    {
        $isAvailable = $item->stock > 0;
        $price = $item->discount_price && $item->discount_price < $item->price
            ? (float) $item->discount_price
            : (float) $item->price;

        $images = is_array($item->images) ? $item->images : [];
        $firstImg = $images[0] ?? null;
        $imgUrl = $firstImg ? asset('storage/' . $firstImg) : url('/images/logo/logo_blue.png');

        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $canonicalUrl . '#product',
            'name' => $item->name,
            'image' => [$imgUrl],
            'description' => $this->cleanDescription($item->description, $item->name),
            'sku' => $item->artikul ?: "STAT-{$item->id}",
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'UZS',
                'price' => $price,
                'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
                'availability' => $isAvailable ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $canonicalUrl,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $item->seller?->shop_name ?: 'Kitobchi Marketplace',
                ],
            ],
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Bosh sahifa',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Katalog',
                    'item' => route('web.catalog'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $item->name,
                    'item' => $canonicalUrl,
                ],
            ],
        ];

        return [$productSchema, $breadcrumbSchema];
    }

    /**
     * Google Sitelinks Searchbox Schema (WebSite SearchAction)
     */
    public function buildWebSiteSearchSchema(): array
    {
        $baseUrl = url('/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $baseUrl . '/#website',
            'url' => $baseUrl . '/',
            'name' => 'Kitobchi',
            'description' => "Kitob va kanselyariya marketpleysi — O'zbekiston bo'ylab tezkor yetkazib berish.",
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('web.catalog') . '?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
