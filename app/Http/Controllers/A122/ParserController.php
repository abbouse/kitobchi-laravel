<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\CatalogParserItem;
use App\Models\Seller;
use App\Services\CatalogParsers\BookUzParserService;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function __construct(
        private readonly BookUzParserService $bookUzParserService,
    ) {}

    public function index()
    {
        $bookUzQuery = CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER);

        $providers = [
            [
                'key' => BookUzParserService::PROVIDER,
                'label' => 'book.uz',
                'subtitle' => 'Kitob katalogi, stock va rasm importi',
                'seller_id' => BookUzParserService::SELLER_ID,
                'total' => (clone $bookUzQuery)->count(),
                'in_stock' => (clone $bookUzQuery)->where('in_stock', true)->count(),
                'out_of_stock' => (clone $bookUzQuery)->where('in_stock', false)->count(),
                'imported' => (clone $bookUzQuery)->whereNotNull('imported_book_id')->count(),
                'route' => route('admin.parsers.book-uz'),
            ],
        ];

        return view('a122.parsers.index', compact('providers'));
    }

    public function bookUz(Request $request)
    {
        $query = CatalogParserItem::query()
            ->where('provider', BookUzParserService::PROVIDER)
            ->with([
                'importedBook:id,name,isbn',
                'matchedBook:id,name,author,isbn',
                'suggestedCategory:id,name_uz',
            ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('author', 'like', '%' . $search . '%')
                    ->orWhere('isbn', 'like', '%' . $search . '%')
                    ->orWhere('source_category', 'like', '%' . $search . '%');
            });
        }

        $stock = $request->input('stock');
        if ($stock === 'in') {
            $query->where('in_stock', true);
        } elseif ($stock === 'out') {
            $query->where('in_stock', false);
        }

        $imported = $request->input('imported');
        if ($imported === 'yes') {
            $query->whereNotNull('imported_book_id');
        } elseif ($imported === 'no') {
            $query->whereNull('imported_book_id');
        }

        $items = $query
            ->orderByRaw('CASE WHEN COALESCE(imported_book_id, matched_book_id) IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByRaw('CASE WHEN in_stock = 1 THEN 0 ELSE 1 END ASC')
            ->latest('last_synced_at')
            ->paginate(24)
            ->withQueryString();
        $categories = BookCategories::query()->orderBy('name_uz')->get(['id', 'name_uz']);
        $seller = Seller::query()->find(BookUzParserService::SELLER_ID, ['id', 'shop_name']);

        $stats = [
            'total' => CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER)->count(),
            'in_stock' => CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER)->where('in_stock', true)->count(),
            'out_of_stock' => CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER)->where('in_stock', false)->count(),
            'imported' => CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER)->whereNotNull('imported_book_id')->count(),
            'last_synced_at' => CatalogParserItem::query()->where('provider', BookUzParserService::PROVIDER)->max('last_synced_at'),
        ];

        return view('a122.parsers.book-uz', compact('items', 'categories', 'seller', 'stats'));
    }

    public function syncBookUz(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'source_url' => ['nullable', 'url'],
        ]);

        $result = $this->bookUzParserService->syncCatalog(
            $validated['limit'] ?? null,
            $validated['source_url'] ?? null,
        );

        $redirect = back()->with(
            'success',
            "book.uz katalogi yangilandi: {$result['synced']} ta muvaffaqiyatli, {$result['failed']} ta xato."
        );

        if (($result['requested'] ?? 0) === 0) {
            return $redirect->with('warning', 'Book.uz dan mahsulot linklari topilmadi. Crawl yoki sitemap qayta tekshirildi, lekin hech narsa olinmadi.');
        }

        if (($result['failed'] ?? 0) > 0) {
            $previewErrors = array_slice($result['errors'] ?? [], 0, 5);

            return $redirect
                ->with('warning', 'Ayrim mahsulotlar o‘qilmadi. Pastda birinchi xatolar ko‘rsatilgan.')
                ->with('parser_errors', $previewErrors);
        }

        return $redirect;
    }

    public function importBookUzItem(Request $request, CatalogParserItem $item)
    {
        abort_unless($item->provider === BookUzParserService::PROVIDER, 404);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:book_categories,id'],
        ]);

        $book = $this->bookUzParserService->importItem($item, (int) $validated['category_id']);

        return back()->with('success', "Mahsulot bazaga qo‘shildi: #{$book->id} {$book->name}");
    }

    public function importBookUzSelected(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:book_categories,id'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ]);

        $items = CatalogParserItem::query()
            ->where('provider', BookUzParserService::PROVIDER)
            ->whereIn('id', $validated['item_ids'])
            ->get();

        $result = $this->bookUzParserService->importMany($items, (int) $validated['category_id']);

        return back()->with(
            'success',
            "Tanlangan mahsulotlar import qilindi: {$result['imported']} ta muvaffaqiyatli, {$result['failed']} ta xato."
        );
    }
}
