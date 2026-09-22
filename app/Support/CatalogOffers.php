<?php

namespace App\Support;

use App\Models\Books;
use App\Services\Catalog\BuyBoxService;
use Illuminate\Support\Collection;

/**
 * Kitob sahifasidagi "Boshqa do'konlardagi takliflar" bloki: bitta katalog
 * kartasining barcha KO'RINADIGAN takliflari — sotuvda borlari avval, keyin
 * amaldagi narx bo'yicha. Har taklif `id` si — savatga qo'shiladigan id.
 */
final class CatalogOffers
{
    public static function forEdition(int $editionId, ?int $currentBookId = null, int $limit = 30): array
    {
        $offers = ProductVisibilityScope::applyBooks(Books::query())
            ->where('edition_id', $editionId)
            ->select(['id', 'edition_id', 'seller_id', 'price', 'discountPrice', 'discountExpiresAt', 'condition', 'catalog_featured', 'artikul'])
            ->withAvailableTotal()
            ->with('seller:id,shop_name,photo,region,rating,rating_reviews_count,reputation_score,isVerified,isPremiumShop')
            // Limitdan OLDIN tartib: sotuvda borlari va arzonlari kesilib qolmasin
            ->orderByRaw(\App\Models\BranchStock::availableSql('book', 'books.id') . ' > 0 DESC')
            ->orderByRaw('CASE WHEN discountPrice > 0 AND discountPrice < price THEN discountPrice ELSE price END ASC')
            ->limit($limit)
            ->get();

        return self::sort($offers)
            ->map(fn (Books $offer) => [
                'id' => (int) $offer->id,
                'artikul' => $offer->artikul,
                'price' => (int) $offer->price,
                'discountPrice' => (int) (($offer->discountPrice ?? 0) ?: $offer->price),
                'effective_price' => BuyBoxService::effectivePrice($offer),
                'count' => $offer->count,
                'stock' => $offer->count,
                'stock_display' => ProductPayloadFormatter::stockDisplayLabel((int) $offer->count),
                'in_stock' => $offer->count > 0,
                'condition' => $offer->condition ?? 'new',
                'is_current' => $currentBookId !== null && (int) $offer->id === $currentBookId,
                'is_featured' => (bool) $offer->catalog_featured,
                'seller' => [
                    'seller_id' => $offer->seller?->id,
                    'shop_name' => $offer->seller?->shop_name,
                    'photo' => $offer->seller?->photo,
                    'region' => $offer->seller?->region,
                    'rating' => (float) ($offer->seller?->rating ?? 0),
                    'rating_reviews_count' => (int) ($offer->seller?->rating_reviews_count ?? 0),
                    'isVerified' => (bool) ($offer->seller?->isVerified ?? false),
                    'is_verified' => (bool) ($offer->seller?->isVerified ?? false),
                    'isPremium' => (bool) ($offer->seller?->isPremiumShop ?? false),
                ],
            ])
            ->values()
            ->all();
    }

    /** @param Collection<int, Books> $offers */
    public static function sort(Collection $offers): Collection
    {
        return $offers->sort(fn (Books $a, Books $b) => [
            $b->count > 0 ? 1 : 0,
            BuyBoxService::effectivePrice($a),
            (int) $a->id,
        ] <=> [
            $a->count > 0 ? 1 : 0,
            BuyBoxService::effectivePrice($b),
            (int) $b->id,
        ])->values();
    }

    /** Bir kitob kartasining barcha taklif id'lari (sharh va reytingni birlashtirish uchun). */
    public static function siblingIds(?int $editionId, int $bookId): array
    {
        if (! $editionId) {
            return [$bookId];
        }

        $ids = Books::query()->where('edition_id', $editionId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return empty($ids) ? [$bookId] : $ids;
    }
}
