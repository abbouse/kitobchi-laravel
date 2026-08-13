<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Policy;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCatalogController extends Controller
{
    public function showBook(int $id, ?string $slug = null)
    {
        try {
            $book = Books::with(['category', 'publisher', 'authorProfile', 'seller', 'tags'])
                ->where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->findOrFail($id);
        } catch (\Throwable $e) {
            abort(404, 'Mahsulot topilmadi');
        }

        $expectedSlug = Str::slug($book->name);
        if ($slug !== $expectedSlug) {
            return redirect()->route('web.books.show', ['id' => $book->id, 'slug' => $expectedSlug], 301);
        }

        try {
            $similarProducts = Books::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->where('id', '!=', $book->id)
                ->where(function ($q) use ($book) {
                    if ($book->category_id) {
                        $q->where('category_id', $book->category_id);
                    }
                    if ($book->author_id) {
                        $q->orWhere('author_id', $book->author_id);
                    }
                })
                ->orderByDesc('totalSales')
                ->take(12)
                ->get();
        } catch (\Throwable $e) {
            $similarProducts = collect();
        }

        try {
            $ugcReviews = \App\Models\BookClub::with('user')
                ->where('product_type', 'book')
                ->where('product_id', $book->id)
                ->where('is_deleted', false)
                ->where(function ($q) {
                    $q->whereNull('is_hidden_by_ai')->orWhere('is_hidden_by_ai', false);
                })
                ->orderByDesc('created_at')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            $ugcReviews = collect();
        }

        $canonicalUrl = route('web.books.show', ['id' => $book->id, 'slug' => $expectedSlug]);
        $seoService = app(\App\Services\SeoService::class);
        $schemas = $seoService->buildBookSchemas($book, $canonicalUrl);

        $price = $book->discountPrice && $book->discountPrice < $book->price ? (float) $book->discountPrice : (float) $book->price;
        $appScheme = "kitobchi://share/product/{$book->id}";
        $artikulScheme = $book->artikul ? "kitobchi://art/{$book->artikul}" : null;

        return view('products.show', [
            'product' => $book,
            'productType' => 'book',
            'schemas' => $schemas,
            'similarProducts' => $similarProducts,
            'ugcReviews' => $ugcReviews,
            'appScheme' => $appScheme,
            'artikulScheme' => $artikulScheme,
            'canonicalUrl' => $canonicalUrl,
            'productPrice' => $price,
            'productAvailability' => $book->count > 0 ? 'in stock' : 'out of stock',
        ]);
    }

    public function showStationery(int $id, ?string $slug = null)
    {
        try {
            $item = Stationery::with(['category', 'seller'])
                ->where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->findOrFail($id);
        } catch (\Throwable $e) {
            abort(404, 'Mahsulot topilmadi');
        }

        $expectedSlug = Str::slug($item->name);
        if ($slug !== $expectedSlug) {
            return redirect()->route('web.stationery.show', ['id' => $item->id, 'slug' => $expectedSlug], 301);
        }

        try {
            $similarProducts = Stationery::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->where('id', '!=', $item->id)
                ->where('category_id', $item->category_id)
                ->orderByDesc('totalSales')
                ->take(12)
                ->get();
        } catch (\Throwable $e) {
            $similarProducts = collect();
        }

        try {
            $ugcReviews = \App\Models\BookClub::with('user')
                ->where('product_type', 'stationery')
                ->where('product_id', $item->id)
                ->where('is_deleted', false)
                ->where(function ($q) {
                    $q->whereNull('is_hidden_by_ai')->orWhere('is_hidden_by_ai', false);
                })
                ->orderByDesc('created_at')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            $ugcReviews = collect();
        }

        $canonicalUrl = route('web.stationery.show', ['id' => $item->id, 'slug' => $expectedSlug]);
        $seoService = app(\App\Services\SeoService::class);
        $schemas = $seoService->buildStationerySchemas($item, $canonicalUrl);

        $price = $item->discount_price && $item->discount_price < $item->price ? (float) $item->discount_price : (float) $item->price;
        $appScheme = "kitobchi://share/product/{$item->id}";
        $artikulScheme = $item->artikul ? "kitobchi://art/{$item->artikul}" : null;

        return view('products.show', [
            'product' => $item,
            'productType' => 'stationery',
            'schemas' => $schemas,
            'similarProducts' => $similarProducts,
            'ugcReviews' => $ugcReviews,
            'appScheme' => $appScheme,
            'artikulScheme' => $artikulScheme,
            'canonicalUrl' => $canonicalUrl,
            'productPrice' => $price,
            'productAvailability' => $item->stock > 0 ? 'in stock' : 'out of stock',
        ]);
    }

    public function byArtikul(string $artikul)
    {
        try {
            $book = Books::where('artikul', $artikul)
                ->where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->first();

            if ($book) {
                return redirect()->route('web.books.show', ['id' => $book->id, 'slug' => Str::slug($book->name)]);
            }

            $stationery = Stationery::where('artikul', $artikul)
                ->where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->first();

            if ($stationery) {
                return redirect()->route('web.stationery.show', ['id' => $stationery->id, 'slug' => Str::slug($stationery->name)]);
            }
        } catch (\Throwable $e) {
            // Silence DB missing error
        }

        abort(404, 'Mahsulot topilmadi');
    }

    public function catalog(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category');
        $type = $request->input('type', 'all');

        // Handle AJAX Live Instant Search Suggestions Popup
        // MUHIM: avval bu yerda faqat Books qidirilardi — "daftar", "ruchka"
        // kabi kanselyariya so'zlari hech qachon natija bermasdi. Endi
        // ikkalasi ham qidiriladi va natijalar aralashtirib beriladi.
        if ($request->ajax() || $request->input('ajax') == 1) {
            try {
                $items = collect();

                if ($search !== '') {
                    $items = $items->merge(
                        Books::where('status', true)->where('is_approved', 1)->where('is_hidden', 0)
                            ->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('author', 'like', "%{$search}%")
                                    ->orWhere('isbn', 'like', "%{$search}%")
                                    ->orWhere('artikul', 'like', "%{$search}%");
                            })
                            ->take(6)->get()->map(function ($book) {
                                $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                                $price = $isDiscounted ? $book->discountPrice : $book->price;

                                return [
                                    'id' => $book->id,
                                    'name' => $book->name,
                                    'author' => $book->author,
                                    'price' => $price,
                                    'image' => $book->first_image ? asset('storage/'.$book->first_image) : asset('images/logo/logo_blue.png'),
                                    'url' => route('web.books.show', ['id' => $book->id, 'slug' => Str::slug($book->name)]),
                                ];
                            })
                    );

                    $items = $items->merge(
                        Stationery::where('status', true)->where('is_approved', 1)->where('is_hidden', 0)
                            ->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('artikul', 'like', "%{$search}%");
                            })
                            ->take(6)->get()->map(function ($item) {
                                $isDiscounted = $item->discount_price > 0 && $item->discount_price < $item->price;
                                $price = $isDiscounted ? $item->discount_price : $item->price;

                                return [
                                    'id' => $item->id,
                                    'name' => $item->name,
                                    'author' => $item->material,
                                    'price' => $price,
                                    'image' => $item->first_image ? asset('storage/'.$item->first_image) : asset('images/logo/logo_blue.png'),
                                    'url' => route('web.stationery.show', ['id' => $item->id, 'slug' => Str::slug($item->name)]),
                                ];
                            })
                    );
                }

                return response()->json(['items' => $items->take(8)->values()]);
            } catch (\Throwable $e) {
                return response()->json(['items' => []]);
            }
        }

        try {
            $bookCategories = BookCategories::where('status', true)->orderBy('name')->get();
            $stationeryCategories = StationeryCategory::where('status', true)->orderBy('name')->get();

            // MUHIM: avval bu yerda faqat Books so'ralardi — $stationeryCategories
            // olib kelinardi-yu, hech qachon Stationery mahsuloti ko'rsatilmasdi
            // (type=stationery tanlansa ham bo'sh natija chiqardi). Endi $type
            // qaysi modelni so'rashni belgilaydi.
            if ($type === 'stationery') {
                $productsQuery = Stationery::with(['category'])
                    ->where('status', true)
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0);

                if ($search !== '') {
                    $productsQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('artikul', 'like', "%{$search}%");
                    });
                }

                if ($categoryId) {
                    $productsQuery->where('category_id', $categoryId);
                }

                $products = $productsQuery->orderByDesc('totalSales')->paginate(24, ['*'], 'page');
            } else {
                $productsQuery = Books::with(['category'])
                    ->where('status', true)
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0);

                if ($search !== '') {
                    $productsQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('author', 'like', "%{$search}%")
                            ->orWhere('isbn', 'like', "%{$search}%")
                            ->orWhere('artikul', 'like', "%{$search}%");
                    });
                }

                if ($categoryId) {
                    $productsQuery->where('category_id', $categoryId);
                }

                $products = $productsQuery->orderByDesc('totalSales')->paginate(24, ['*'], 'page');
            }
        } catch (\Throwable $e) {
            $bookCategories = collect();
            $stationeryCategories = collect();
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);
        }

        return view('products.catalog', [
            'products' => $products,
            'bookCategories' => $bookCategories,
            'stationeryCategories' => $stationeryCategories,
            'search' => $search,
            'selectedCategory' => $categoryId,
            'type' => $type === 'stationery' ? 'stationery' : 'book',
        ]);
    }

    public function sitemap()
    {
        try {
            $books = Books::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->select('id', 'name', 'updated_at')
                ->orderByDesc('updated_at')
                ->take(5000)
                ->get();

            $stationeries = Stationery::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->select('id', 'name', 'updated_at')
                ->orderByDesc('updated_at')
                ->take(2000)
                ->get();

            $policies = Policy::where('is_active', true)->get();
        } catch (\Throwable $e) {
            $books = collect();
            $stationeries = collect();
            $policies = collect();
        }

        return response()
            ->view('seo.sitemap', compact('books', 'stationeries', 'policies'))
            ->header('Content-Type', 'text/xml');
    }

    public function googleMerchantFeed()
    {
        try {
            $books = Books::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->with(['publisher', 'category'])
                ->take(5000)
                ->get();

            $stationeries = Stationery::where('status', true)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->with(['category'])
                ->take(2000)
                ->get();
        } catch (\Throwable $e) {
            $books = collect();
            $stationeries = collect();
        }

        return response()
            ->view('seo.google-merchant', compact('books', 'stationeries'))
            ->header('Content-Type', 'text/xml');
    }

    public function robots()
    {
        $sitemapUrl = url('/sitemap.xml');
        $content = "User-agent: *\nAllow: /\nDisallow: /a122/\nDisallow: /boshqaruv/\nDisallow: /api/\n\nSitemap: {$sitemapUrl}\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
