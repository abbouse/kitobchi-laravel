<?php

namespace App\Observers;

use App\Models\BookEdition;
use App\Models\Books;
use App\Models\BranchStock;
use App\Models\Seller;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Taklif (books), qoldiq (branch_stocks) va do'kon (sellers) o'zgarishlarida
 * katalog holatini (kartaga ulanish, buy box) yangilab turadi.
 */
class CatalogOfferObserver
{
    /** Buy box natijasiga ta'sir qiladigan taklif ustunlari. */
    private const BUYBOX_FIELDS = [
        'price', 'discountPrice', 'discountExpiresAt', 'status', 'is_approved',
        'is_hidden', 'archived_at', 'seller_id', 'totalSales',
    ];

    public function __construct(
        private readonly BuyBoxService $buyBox,
        private readonly CatalogService $catalog,
    ) {}

    public function created(Books $book): void
    {
        if ($book->edition_id) {
            $this->buyBox->touch((int) $book->edition_id);

            return;
        }

        if (! config('catalog.auto_link', true)) {
            return;
        }

        // Har qanday kanal (eski ilova, admin, import) orqali yaratilgan taklif
        // ham katalogga tushadi. Xato asosiy yaratishni buzmasin.
        try {
            $this->catalog->linkOffer($book, 'legacy');
        } catch (\Throwable $e) {
            Log::warning('Catalog auto-link failed', ['book_id' => $book->id, 'error' => $e->getMessage()]);
        }
    }

    public function updated(Books $book): void
    {
        if ($book->wasChanged('edition_id')) {
            $this->buyBox->touch((int) $book->getOriginal('edition_id'));
            if (! $book->edition_id) {
                Books::query()->whereKey($book->id)->toBase()->update(['catalog_featured' => true]);
            }
        }

        if (! $book->edition_id) {
            return;
        }

        if ($book->wasChanged('edition_id') || $book->wasChanged(self::BUYBOX_FIELDS)) {
            $this->buyBox->touch((int) $book->edition_id);
        }

        // Eski ilova/avto-ulash orqali ochilgan karta manba taklif moderatsiyadan
        // o'tganda faollashadi (do'kon arizalari esa faqat admin tasdig'i bilan).
        if ($book->wasChanged('is_approved') && (int) $book->is_approved === 1) {
            BookEdition::query()
                ->whereKey($book->edition_id)
                ->where('status', BookEdition::STATUS_PENDING)
                ->whereIn('source', ['legacy', 'backfill'])
                ->update(['status' => BookEdition::STATUS_ACTIVE]);
        }
    }

    public function deleted(Books $book): void
    {
        $this->buyBox->touch((int) $book->edition_id);
    }

    public function stockSaved(BranchStock $row): void
    {
        if ($row->wasChanged(['quantity', 'reserved']) || $row->wasRecentlyCreated) {
            $this->buyBox->touchForStockRow($row);
        }
    }

    public function stockDeleted(BranchStock $row): void
    {
        $this->buyBox->touchForStockRow($row);
    }

    public function sellerUpdated(Seller $seller): void
    {
        if (! $seller->wasChanged(['status', 'is_hidden', 'parent_id', 'isVerified'])) {
            return;
        }

        $editionIds = DB::table('books')
            ->where('seller_id', $seller->id)
            ->whereNotNull('edition_id')
            ->distinct()
            ->pluck('edition_id');

        foreach ($editionIds as $editionId) {
            $this->buyBox->touch((int) $editionId);
        }
    }
}
