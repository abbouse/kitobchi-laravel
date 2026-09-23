<?php

namespace App\Support;

use App\Models\Books;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
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
            ->select(['id', 'edition_id', 'seller_id', 'price', 'discountPrice', 'discountExpiresAt', 'catalog_featured', 'artikul'])
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

    /**
     * "Boshqa nashrlar": bir xil ISBN'ga ega, lekin boshqa nashr bo'lgan
     * kartalar (qattiq/yumshoq muqova, boshqa til yoki yozuv). ISBN standarti
     * bo'yicha ular alohida ISBN olishi kerak, amalda esa nashriyotlar ISBN'ni
     * qayta ishlatadi — shuning uchun ular bitta kartaga qo'shilmaydi, balki
     * mijozga "boshqa nashri ham bor" deb ko'rsatiladi (Amazon'dagi format
     * almashtirgichga o'xshash).
     *
     * @return array<int, array>
     */
    public static function otherPrintings(?int $editionId, int $limit = 6): array
    {
        if (! $editionId) {
            return [];
        }

        // MUHIM: kartani yuklangan relationdan OLMAYMIZ — ro'yxatlarda u qisman
        // (`edition:id,offers_count,…`) yuklanadi va `isbn13` bo'lmaydi.
        $current = \App\Models\BookEdition::query()->whereKey($editionId)->first(['id', 'isbn13', 'title']);
        if (! $current || blank($current->isbn13)) {
            return [];
        }

        return \App\Models\BookEdition::query()
            ->where('status', \App\Models\BookEdition::STATUS_ACTIVE)
            ->where('isbn13', $current->isbn13)
            ->whereKeyNot($editionId)
            ->whereNotNull('featured_book_id')
            ->where('offers_count', '>', 0)
            ->orderByDesc('in_stock_offers_count')
            ->orderByDesc('offers_count')
            ->limit($limit + 4)
            ->get()
            // MUHIM: bir xil ISBN har doim ham bir xil kitob emas — nashriyotlar
            // ISBN'ni butunlay boshqa kitobga ham qayta ishlatadi (backfill buni
            // `isbn_conflicts` deb sanaydi). Mijozga "boshqa nashri" deb begona
            // kitob ko'rsatilmasligi uchun nom ham o'xshash bo'lishi shart.
            ->filter(fn (\App\Models\BookEdition $e) => \App\Services\Catalog\CatalogService::titlesSimilar($current->title, $e->title))
            ->take($limit)
            ->map(fn (\App\Models\BookEdition $e) => [
                'edition_id' => (int) $e->id,
                // Mijoz shu id bo'yicha kitob sahifasini ochadi
                'product_id' => (int) $e->featured_book_id,
                'title' => $e->title,
                // Nom farq qilsa ilova uni ham ko'rsatadi (faqat variant emas)
                'title_differs' => CatalogService::normalizeText($current->title) !== CatalogService::normalizeText($e->title),
                'variant' => self::variantLabel($e),
                'coverType' => \App\Services\Catalog\CatalogService::canonCover($e->coverType),
                'language' => \App\Services\Catalog\CatalogService::canonLang($e->lang),
                'languageWrite' => \App\Services\Catalog\CatalogService::canonScript($e->langType),
                'image' => ProductImageUrls::originalUrl($e->coverPath()),
                'min_price' => $e->min_price !== null ? (int) $e->min_price : null,
                'offers_count' => (int) $e->offers_count,
                'in_stock' => (int) $e->in_stock_offers_count > 0,
            ])
            ->values()
            ->all();
    }

    /** "Qattiq muqova · Rus · Kirill" */
    public static function variantLabel(\App\Models\BookEdition $edition): string
    {
        $service = \App\Services\Catalog\CatalogService::class;
        $parts = array_filter([
            match ($service::canonCover($edition->coverType)) {
                'hard' => 'Qattiq muqova', 'soft' => 'Yumshoq muqova', default => null,
            },
            match ($service::canonLang($edition->lang)) {
                'uz' => "O'zbek", 'ru' => 'Rus', 'en' => 'Ingliz', 'qq' => 'Qoraqalpoq', default => null,
            },
            match ($service::canonScript($edition->langType)) {
                'latin' => 'Lotin', 'cyrillic' => 'Kirill', default => null,
            },
        ]);

        return implode(' · ', $parts);
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
