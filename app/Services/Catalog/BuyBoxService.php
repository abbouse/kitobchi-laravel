<?php

namespace App\Services\Catalog;

use App\Models\BookEdition;
use App\Models\Books;
use App\Models\BranchStock;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Buy box": kitob kartasining mijoz ro'yxatlarida chiqadigan YAGONA taklifini
 * tanlaydi (books.catalog_featured) va karta statistikasini yangilaydi.
 *
 * Tanlov tartibi (Amazon featured offer mantig'iga o'xshash, lekin sodda va
 * deterministik):
 *   1. faqat ko'rinadigan takliflar (status, moderatsiya, yashirilmagan,
 *      arxivlanmagan, do'kon faol);
 *   2. sotuvda bor (qoldiq > 0) takliflar avval;
 *   3. amaldagi narx (chegirma bilan) arzoni;
 *   4. tasdiqlangan do'kon, reputatsiya, reyting, sotuvlar;
 *   5. oxirida id — natija har doim bir xil.
 *
 * Hech bir taklif sotuvda bo'lmasa ham karta ro'yxatda qoladi ("sotuvda yo'q"
 * holatida) — avvalgi xulq-atvor saqlanadi.
 */
class BuyBoxService
{
    /** Ommaviy ishlar (backfill) uchun: har taklifda qayta hisoblamaslik. */
    private bool $paused = false;

    public function pause(): void
    {
        $this->paused = true;
    }

    public function resume(): void
    {
        $this->paused = false;
    }

    public function touch(?int $editionId): void
    {
        if (! $editionId || $this->paused) {
            return;
        }

        // Tranzaksiya ichida bo'lsak — commit'dan keyin (qoldiq/narx haqiqiy bo'lganda)
        DB::afterCommit(function () use ($editionId) {
            try {
                $this->recompute($editionId);
            } catch (\Throwable $e) {
                // Buy box hisoblash xatosi asosiy amalni (buyurtma, stock) buzmasligi kerak;
                // `catalog:buybox` rejalashtirilgan buyrug'i keyin to'g'rilaydi.
                Log::warning('BuyBox recompute failed', ['edition_id' => $editionId, 'error' => $e->getMessage()]);
            }
        });
    }

    public function touchForBook(?int $bookId): void
    {
        if (! $bookId) {
            return;
        }

        $this->touch((int) DB::table('books')->where('id', $bookId)->value('edition_id'));
    }

    /** @return array{featured:?int, offers:int, in_stock:int, changed:int} */
    public function recompute(int $editionId): array
    {
        $offers = Books::query()
            ->where('edition_id', $editionId)
            ->select(['id', 'seller_id', 'price', 'discountPrice', 'discountExpiresAt', 'status', 'is_approved', 'is_hidden', 'archived_at', 'totalSales', 'catalog_featured'])
            ->withAvailableTotal()
            ->get();

        $sellers = Seller::query()
            ->whereIn('id', $offers->pluck('seller_id')->unique()->all())
            ->get(['id', 'status', 'is_hidden', 'parent_id', 'isVerified', 'reputation_score', 'rating'])
            ->keyBy('id');

        $eligible = $offers->filter(fn (Books $b) => $this->isEligible($b, $sellers->get($b->seller_id)))->values();

        $ranked = $eligible->sort(function (Books $a, Books $b) use ($sellers) {
            $sa = $sellers->get($a->seller_id);
            $sb = $sellers->get($b->seller_id);

            return [
                $b->totalAvailableStock() > 0 ? 1 : 0,
                self::effectivePrice($a),
                (int) ($sb->isVerified ?? 0),
                (float) ($sb->reputation_score ?? 0),
                (float) ($sb->rating ?? 0),
                (int) ($b->totalSales ?? 0),
                (int) $a->id,
            ] <=> [
                $a->totalAvailableStock() > 0 ? 1 : 0,
                self::effectivePrice($b),
                (int) ($sa->isVerified ?? 0),
                (float) ($sa->reputation_score ?? 0),
                (float) ($sa->rating ?? 0),
                (int) ($a->totalSales ?? 0),
                (int) $b->id,
            ];
        })->values();

        // Ko'rinadigan taklif bo'lmasa ham bitta "nomzod" belgilanadi (ro'yxatlar baribir
        // ko'rinish shartini tekshiradi) — hodisasiz tasdiqlangan yagona taklif darhol chiqadi.
        $featuredId = $ranked->first()?->id
            ?? $offers->sortBy(fn (Books $b) => [(int) $b->is_approved === 1 ? 0 : 1, $b->archived_at ? 1 : 0, -(int) $b->id])->first()?->id;
        $inStock = $eligible->filter(fn (Books $b) => $b->totalAvailableStock() > 0);
        $priced = $inStock->isNotEmpty() ? $inStock : $eligible;

        $changed = [];
        foreach ($offers as $offer) {
            $should = $featuredId !== null && (int) $offer->id === (int) $featuredId;
            if ((bool) $offer->catalog_featured !== $should) {
                $changed[] = (int) $offer->id;
            }
        }

        if (! empty($changed)) {
            // MUHIM: butun karta bitta so'rovda yoziladi — bir vaqtda ketgan ikki
            // hisob (masalan, buyurtma + narx tahriri) ikkita "tanlangan" taklif
            // qoldirmasligi uchun (oxirgi yozuv g'olib).
            Books::query()->where('edition_id', $editionId)->toBase()->update([
                'catalog_featured' => DB::raw('CASE WHEN id = ' . (int) ($featuredId ?? 0) . ' THEN 1 ELSE 0 END'),
            ]);
            $this->reindex($changed);
        }

        BookEdition::withTrashed()->whereKey($editionId)->toBase()->update([
            'offers_count' => $eligible->count(),
            'in_stock_offers_count' => $inStock->count(),
            'min_price' => $priced->isNotEmpty() ? $priced->map(fn ($b) => self::effectivePrice($b))->min() : null,
            'featured_book_id' => $featuredId,
        ]);

        return [
            'featured' => $featuredId,
            'offers' => $eligible->count(),
            'in_stock' => $inStock->count(),
            'changed' => count($changed),
        ];
    }

    /** Barcha kartalarni qayta hisoblash (rejalashtirilgan xavfsizlik to'ri). */
    public function recomputeAll(?callable $progress = null): int
    {
        $count = 0;
        BookEdition::withTrashed()->select('id')->orderBy('id')->chunkById(500, function ($chunk) use (&$count, $progress) {
            foreach ($chunk as $edition) {
                $this->recompute((int) $edition->id);
                $count++;
            }
            if ($progress) {
                $progress($count);
            }
        });

        // Katalogdan ajratilgan takliflar har doim ro'yxatda ko'rinadi
        Books::query()->whereNull('edition_id')->where('catalog_featured', false)->toBase()->update(['catalog_featured' => true]);

        return $count;
    }

    public static function effectivePrice(Books $book): int
    {
        $price = (int) ($book->price ?? 0);
        $discount = (int) ($book->discountPrice ?? 0);
        $expires = $book->discountExpiresAt ? strtotime((string) $book->discountExpiresAt) : null;

        if ($discount > 0 && $discount < $price && ($expires === null || $expires > time())) {
            return $discount;
        }

        return $price;
    }

    private function isEligible(Books $book, ?Seller $seller): bool
    {
        return $seller
            && (bool) $book->status
            && (int) $book->is_approved === 1
            && ! (bool) $book->is_hidden
            && $book->archived_at === null
            && $seller->status === 'approved'
            && ! (bool) $seller->is_hidden
            && empty($seller->parent_id);
    }

    /** Qidiruv indeksini yangilash (Scout yoqilgan bo'lsa, navbat orqali). */
    public function reindex(array $bookIds): void
    {
        $driver = config('scout.driver');
        if (empty($bookIds) || ! $driver || $driver === 'null') {
            return;
        }

        try {
            Books::query()->whereIn('id', $bookIds)->searchable();
        } catch (\Throwable $e) {
            Log::debug('Catalog reindex skipped: ' . $e->getMessage());
        }
    }

    /**
     * Moderatsiya qarori model hodisalarisiz (updateQuietly) yozilgan joylardan
     * chaqiriladi: buy box qayta hisoblanadi, eski/avto karta faollashadi.
     */
    public function afterModeration($product): void
    {
        if (! $product instanceof Books || ! $product->edition_id) {
            return;
        }

        if ((int) $product->is_approved === 1) {
            BookEdition::query()
                ->whereKey($product->edition_id)
                ->where('status', BookEdition::STATUS_PENDING)
                ->whereIn('source', ['legacy', 'backfill'])
                ->update(['status' => BookEdition::STATUS_ACTIVE]);
        }

        $this->touch((int) $product->edition_id);
    }

    /** Qoldiq o'zgarganda (BranchStock hodisasi) chaqiriladi. */
    public function touchForStockRow(BranchStock $row): void
    {
        if ($row->product_type === BranchStock::TYPE_BOOK) {
            $this->touchForBook((int) $row->product_id);
        }
    }
}
