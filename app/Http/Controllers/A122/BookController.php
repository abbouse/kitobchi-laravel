<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Publisher;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Services\AuthorDirectoryService;
use App\Services\ProductModerationStateService;
use App\Support\ProductImageUrls;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function __construct(
        protected AuthorDirectoryService $authorDirectory,
        protected ProductModerationStateService $productModerationState,
    ) {}

    private function parseImagesText(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $query = Books::with(['category', 'publisher', 'authorProfile', 'seller']);
        $tab = match ((string) $request->input('tab', 'pending')) {
            'active' => 'active',
            'rejected', 'reject' => 'rejected',
            'all' => 'all',
            default => 'pending',
        };

        match ($tab) {
            'active' => $query->where('is_approved', 1),
            'rejected' => $query->where('is_approved', 2),
            'all' => null,
            default => $query->where('is_approved', 0),
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('artikul', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name_uz', 'like', "%{$search}%"))
                ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('seller', fn ($sellerQuery) => $sellerQuery->where('shop_name', 'like', "%{$search}%"))
                ->orWhere('id', $search));
        }

        $books = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Books::count(),
            'pending' => Books::where('is_approved', 0)->count(),
            'active' => Books::where('is_approved', 1)->count(),
            'rejected' => Books::where('is_approved', 2)->count(),
        ];

        $rows = $books->map(function (Books $b) {
            $status = (int) ($b->is_approved ?? 0) === 1
                ? 'active'
                : ((int) ($b->is_approved ?? 0) === 2 ? 'rejected' : 'pending');

            return [
                'id' => $b->id,
                'title' => $b->name,
                'author' => $b->authorProfile?->name ?: ($b->author ?: '—'),
                'category' => $b->category?->name_uz ?: '—',
                'seller' => $b->seller?->shop_name ?: '—',
                'price' => number_format((float) $b->price, 0).' UZS',
                'stock' => (int) ($b->count ?? 0),
                'sold' => (int) ($b->totalSales ?? 0),
                'status' => $status,
                'status_label' => match ($status) {
                    'active' => 'Faol',
                    'rejected' => 'Rad etilgan',
                    default => 'Moderatsiyada',
                },
                'updated_at' => optional($b->updated_at)->format('d.m.Y H:i') ?: '—',
            ];
        })->values();

        return view('a122.books.index', compact('books', 'rows', 'counts', 'tab'));
    }

    public function create()
    {
        $categories = BookCategories::orderBy('name_uz')->get();
        $publishers = Publisher::query()->orderBy('name')->get(['id', 'name']);
        $sellers = Seller::query()
            ->select('id', 'shop_name')
            ->whereNotNull('shop_name')
            ->orderBy('shop_name')
            ->get();

        return view('a122.books.create', compact('categories', 'publishers', 'sellers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'translator' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'category_id' => 'required|exists:book_categories,id',
            'publisher_id' => 'nullable|exists:publishers,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discountPrice' => 'nullable|numeric|min:0',
            'discountExpiresAt' => 'nullable|date',
            'count' => 'required|integer|min:0',
            'lang' => 'nullable|string|max:10',
            'langType' => 'nullable|string|max:40',
            'coverType' => 'nullable|string|max:40',
            'year' => 'nullable|integer|min:0|max:2100',
            'pages' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
            'is_hidden' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            'recommendedExpiresAt' => 'nullable|date',
            'images_text' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $images = $this->parseImagesText($request->input('images_text'));
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if (! $image->isValid()) {
                    continue;
                }
                $filename = time()."_admin_book_{$index}.".$image->getClientOriginalExtension();
                $path = $image->storeAs('books', $filename, 'public');
                $images[] = $path;
                ProductImageVariantGenerator::generateForPath($path);
            }
        }

        $data['isbn'] = Books::normalizeIsbn($request->input('isbn'));
        $data['images'] = array_values(array_unique($images));
        $data['is_approved'] = 0;
        $data['status'] = $request->boolean('status', true);
        $data['is_hidden'] = $request->boolean('is_hidden', false);
        $data['recommended'] = $request->boolean('recommended', false);
        $author = $this->authorDirectory->resolveOrCreateByName($request->input('author'));
        $data['author_id'] = $author?->id;
        $data['author'] = $author?->name ?: trim((string) $request->input('author'));

        $book = Books::create($data);
        $this->productModerationState->markPending($book, 'admin_created');

        return redirect()->route('admin.books.show', $book)->with('success', 'Yangi kitob yaratildi.');
    }

    public function show(Books $book)
    {
        $book->load(['category', 'seller', 'publisher', 'authorProfile']);
        $images = collect($book->images ?? [])
            ->filter(fn ($image) => is_string($image) && trim($image) !== '')
            ->map(fn (string $image) => ProductImageUrls::originalUrl($image))
            ->filter()
            ->values();
        $sellerOrders = $book->seller_id
            ? SellerOrder::query()
                ->with(['client:id,name,lastname,phone_number'])
                ->where('seller_id', $book->seller_id)
                ->latest()
                ->take(6)
                ->get()
            : collect();
        $recentOrders = Sold::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(60)
            ->get()
            ->filter(function (Sold $order) use ($book) {
                return collect($order->items ?? [])->contains(fn ($item) => (int) ($item['item_id'] ?? 0) === (int) $book->id && ($item['type'] ?? 'book') === 'book');
            })
            ->take(6)
            ->values();

        return view('a122.books.show', compact('book', 'images', 'sellerOrders', 'recentOrders'));
    }

    public function edit(Books $book)
    {
        $categories = BookCategories::orderBy('name_uz')->get();
        $publishers = Publisher::query()->orderBy('name')->get(['id', 'name']);
        $sellers = Seller::query()
            ->select('id', 'shop_name')
            ->whereNotNull('shop_name')
            ->orderBy('shop_name')
            ->get();

        return view('a122.books.edit', compact('book', 'categories', 'publishers', 'sellers'));
    }

    public function update(Request $request, Books $book)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'translator' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'category_id' => 'required|exists:book_categories,id',
            'publisher_id' => 'nullable|exists:publishers,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discountPrice' => 'nullable|numeric|min:0',
            'discountExpiresAt' => 'nullable|date',
            'count' => 'required|integer|min:0',
            'lang' => 'nullable|string|max:10',
            'langType' => 'nullable|string|max:40',
            'coverType' => 'nullable|string|max:40',
            'year' => 'nullable|integer|min:0|max:2100',
            'pages' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
            'is_hidden' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            'recommendedExpiresAt' => 'nullable|date',
            'is_approved' => 'nullable|in:0,1,2',
            'images_text' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $existingImages = $this->parseImagesText($request->input('images_text'));
        $currentImages = is_array($book->images) ? $book->images : (json_decode((string) $book->images, true) ?: []);
        $deletedImages = array_diff($currentImages, $existingImages);
        foreach ($deletedImages as $image) {
            if (is_string($image) && ! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
                ProductImageVariantGenerator::deleteForPath($image);
            }
        }

        $images = $existingImages;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if (! $image->isValid()) {
                    continue;
                }
                $filename = time()."_admin_book_{$index}.".$image->getClientOriginalExtension();
                $path = $image->storeAs('books', $filename, 'public');
                $images[] = $path;
                ProductImageVariantGenerator::generateForPath($path);
            }
        }

        $data['isbn'] = Books::normalizeIsbn($request->input('isbn'));
        $data['images'] = array_values(array_unique($images));
        $data['status'] = $request->boolean('status', true);
        $data['is_hidden'] = $request->boolean('is_hidden', false);
        $data['recommended'] = $request->boolean('recommended', false);
        $author = $this->authorDirectory->resolveOrCreateByName($request->input('author'));
        $data['author_id'] = $author?->id;
        $data['author'] = $author?->name ?: trim((string) $request->input('author'));

        $book->update($data);
        $this->productModerationState->markPending($book, 'admin_edited');

        return redirect()->route('admin.books.show', $book)->with('success', 'Kitob yangilandi.');
    }

    public function moderate(Request $request, Books $book)
    {
        $request->validate(['is_approved' => 'required|in:0,1,2']);
        $approval = (int) $request->input('is_approved');
        $book->updateQuietly([
            'is_approved' => $approval,
            'ai_moderation_status' => match ($approval) {
                1 => 'manual_approved',
                2 => 'manual_rejected',
                default => 'pending',
            },
            'ai_moderation_checked_at' => now(),
            'ai_moderation_note' => 'Admin tomonidan qo‘lda moderatsiya qilindi.',
            'ai_moderation_next_retry_at' => null,
            'ai_moderation_meta' => ['source' => 'admin_manual_override'],
        ]);

        return back()->with('success', 'Moderatsiya yangilandi.');
    }
}
