<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Policy;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Traits\HasProductVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCatalogController extends Controller
{
    // MUHIM: avval bu yerda "ko'rinish" har joyda qo'lda
    // status=true/is_approved=1/is_hidden=0 orqali tekshirilardi — lekin
    // SOTUVCHINING o'zi faolmi (Seller.status='approved') hech qachon
    // so'ralmasdi! Natijada bloklangan/faol bo'lmagan do'konning
    // mahsulotlari ham katalogda, qidiruvda va mahsulot sahifasida
    // ko'rinaverardi. Ilovaning haqiqiy API'si (ProductsController,
    // SearchController) foydalanadigan XUDDI SHU HasProductVisibility
    // trait endi bu yerda ham ishlatiladi — ikkalasi bir xil qoidaga
    // bo'ysunadi.
    use HasProductVisibility;

    public function showBook(int $id, ?string $slug = null)
    {
        try {
            $book = $this->visibleBooks(['category', 'publisher', 'seller', 'tags'])
                ->findOrFail($id);
        } catch (\Throwable $e) {
            abort(404, 'Mahsulot topilmadi');
        }

        $expectedSlug = Str::slug($book->name);
        if ($slug !== $expectedSlug) {
            return redirect()->route('web.books.show', ['id' => $book->id, 'slug' => $expectedSlug], 301);
        }

        try {
            $similarBooks = $this->visibleBooks()
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
                ->take(8)
                ->get()
                ->map(function($b) { $b->type_label = 'book'; return $b; });

            $similarStationery = $this->visibleStationeries()
                ->orderByDesc('totalSales')
                ->take(4)
                ->get()
                ->map(function($s) { $s->type_label = 'stationery'; return $s; });

            $similarProducts = $similarBooks->concat($similarStationery)->shuffle();
        } catch (\Throwable $e) {
            $similarProducts = collect();
        }

        try {
            $ugcReviews = \App\Models\BookClub::with(['user', 'images'])
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

        [$isFavorited, $favoritedSimilarIds] = $this->favoriteState('book', $book->id, $similarProducts);

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
            'isFavorited' => $isFavorited,
            'favoritedSimilarIds' => $favoritedSimilarIds,
        ]);
    }

    /**
     * Joriy foydalanuvchi bu mahsulotni (va shu sahifadagi "o'xshash"
     * ro'yxatdagilarni) sevimlilarga qo'shganmi — yurak ikonkasini
     * to'g'ri holatda ko'rsatish uchun. Login bo'lmasa — bo'sh qaytadi.
     *
     * @return array{0: bool, 1: array<int>}
     */
    private function favoriteState(string $mainType, int $mainId, \Illuminate\Support\Collection $similarProducts): array
    {
        if (! auth()->check()) {
            return [false, []];
        }

        try {
            $mainFav = \App\Models\FavouriteProducts::where('user_id', auth()->id())
                ->where('product_type', $mainType)
                ->where('product_id', $mainId)
                ->exists();

            $similarByType = $similarProducts->groupBy(fn ($p) => $p->type_label ?? $mainType);
            $favoritedSimilarIds = [];

            foreach ($similarByType as $simType => $items) {
                $ids = \App\Models\FavouriteProducts::where('user_id', auth()->id())
                    ->where('product_type', $simType)
                    ->whereIn('product_id', $items->pluck('id'))
                    ->pluck('product_id')
                    ->all();
                $favoritedSimilarIds = array_merge($favoritedSimilarIds, $ids);
            }

            return [$mainFav, $favoritedSimilarIds];
        } catch (\Throwable $e) {
            return [false, []];
        }
    }

    public function showStationery(int $id, ?string $slug = null)
    {
        try {
            $item = $this->visibleStationeries(['category', 'seller', 'variants', 'tags'])
                ->findOrFail($id);
        } catch (\Throwable $e) {
            abort(404, 'Mahsulot topilmadi');
        }

        $expectedSlug = Str::slug($item->name);
        if ($slug !== $expectedSlug) {
            return redirect()->route('web.stationery.show', ['id' => $item->id, 'slug' => $expectedSlug], 301);
        }

        try {
            $similarProducts = $this->visibleStationeries()
                ->where('id', '!=', $item->id)
                ->where('category_id', $item->category_id)
                ->orderByDesc('totalSales')
                ->take(12)
                ->get();
        } catch (\Throwable $e) {
            $similarProducts = collect();
        }

        try {
            $ugcReviews = \App\Models\BookClub::with(['user', 'images'])
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

        [$isFavorited, $favoritedSimilarIds] = $this->favoriteState('stationery', $item->id, $similarProducts);

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
            'isFavorited' => $isFavorited,
            'favoritedSimilarIds' => $favoritedSimilarIds,
        ]);
    }

    public function byArtikul(string $artikul)
    {
        try {
            $book = $this->visibleBooks()->where('artikul', $artikul)->first();

            if ($book) {
                return redirect()->route('web.books.show', ['id' => $book->id, 'slug' => Str::slug($book->name)]);
            }

            $stationery = $this->visibleStationeries()->where('artikul', $artikul)->first();

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

        // Filtr paneli — saralash va narx oralig'i (piyolamarket.uz'dagi
        // kabi katalog sahifasida majburiy filtr).
        $sort = in_array($request->input('sort'), ['popular', 'new', 'price_asc', 'price_desc'], true)
            ? $request->input('sort')
            : 'popular';
        $priceMin = is_numeric($request->input('price_min')) && (float) $request->input('price_min') > 0
            ? (float) $request->input('price_min')
            : null;
        $priceMax = is_numeric($request->input('price_max')) && (float) $request->input('price_max') > 0
            ? (float) $request->input('price_max')
            : null;

        $sellerIds = (array) $request->input('seller_ids', []);
        $publisherIds = (array) $request->input('publisher_ids', []);

        // Handle AJAX Live Instant Search Suggestions Popup
        // MUHIM: avval bu yerda faqat Books qidirilardi — "daftar", "ruchka"
        // kabi kanselyariya so'zlari hech qachon natija bermasdi. Endi
        // ikkalasi ham qidiriladi va natijalar aralashtirib beriladi.
        if ($request->ajax() || $request->input('ajax') == 1) {
            try {
                $items = collect();

                if ($search !== '') {
                    $items = $items->merge(
                        $this->visibleBooks()
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
                        $this->visibleStationeries()
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
            // MUHIM: bu jadvallarda `status`/`name` ustunlari yo'q (faqat
            // is_active va name_uz/ru/en/ja) — avval bu yerda noto'g'ri ustun
            // nomlari ishlatilgani sabab SQL xato berardi va try/catch uni
            // yutib yuborardi — kategoriyalar hech qachon ko'rinmasdi.
            $bookCategories = BookCategories::where('is_active', true)->orderBy('name_uz')->get();
            $stationeryCategories = StationeryCategory::where('is_active', true)->orderBy('name_uz')->get();
            $sellers = \App\Models\Seller::where('status', 'approved')->orderBy('shop_name')->get();
            $publishers = \App\Models\Publisher::orderBy('name')->get();

            // MUHIM: avval bu yerda faqat Books so'ralardi — $stationeryCategories
            // olib kelinardi-yu, hech qachon Stationery mahsuloti ko'rsatilmasdi
            // (type=stationery tanlansa ham bo'sh natija chiqardi). Endi $type
            // qaysi modelni so'rashni belgilaydi.
            if ($type === 'stationery') {
                $productsQuery = $this->visibleStationeries(['category']);

                if ($search !== '') {
                    $productsQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('artikul', 'like', "%{$search}%");
                    });
                }

                if ($categoryId) {
                    $productsQuery->where('category_id', $categoryId);
                }

                if ($priceMin !== null) {
                    $productsQuery->where('price', '>=', $priceMin);
                }
                if ($priceMax !== null) {
                    $productsQuery->where('price', '<=', $priceMax);
                }
                
                if (!empty($sellerIds)) {
                    $productsQuery->whereIn('seller_id', $sellerIds);
                }

                $products = $this->applyCatalogSort($productsQuery, $sort)->paginate(24, ['*'], 'page');
            } else {
                $productsQuery = $this->visibleBooks(['category']);

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

                if ($priceMin !== null) {
                    $productsQuery->where('price', '>=', $priceMin);
                }
                if ($priceMax !== null) {
                    $productsQuery->where('price', '<=', $priceMax);
                }
                
                if (!empty($sellerIds)) {
                    $productsQuery->whereIn('seller_id', $sellerIds);
                }
                if (!empty($publisherIds)) {
                    $productsQuery->whereIn('publisher_id', $publisherIds);
                }

                $products = $this->applyCatalogSort($productsQuery, $sort)->paginate(24, ['*'], 'page');
            }
        } catch (\Throwable $e) {
            $bookCategories = collect();
            $stationeryCategories = collect();
            $sellers = collect();
            $publishers = collect();
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);
        }

        // Joriy sahifadagi mahsulotlar orasida foydalanuvchi sevimlisi
        // bo'lganlarini belgilash — faqat shu 24 ta uchun so'raladi (butun
        // sevimlilar jadvalini emas), N+1'ga yo'l qo'ymaslik uchun bitta
        // whereIn so'rovi yetarli.
        try {
            $favoritedIds = auth()->check() && $products->count() > 0
                ? \App\Models\FavouriteProducts::where('user_id', auth()->id())
                    ->where('product_type', $type === 'stationery' ? 'stationery' : 'book')
                    ->whereIn('product_id', $products->pluck('id'))
                    ->pluck('product_id')
                    ->all()
                : [];
        } catch (\Throwable $e) {
            $favoritedIds = [];
        }

        return view('products.catalog', [
            'products' => $products,
            'bookCategories' => $bookCategories,
            'stationeryCategories' => $stationeryCategories,
            'search' => $search,
            'selectedCategory' => $categoryId,
            'type' => $type === 'stationery' ? 'stationery' : 'book',
            'sort' => $sort,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'sellers' => $sellers,
            'publishers' => $publishers,
            'selectedSellers' => $sellerIds,
            'selectedPublishers' => $publisherIds,
            'favoritedIds' => $favoritedIds,
        ]);
    }

    /** Katalog saralash — piyolamarket uslubidagi filtr panelidan keladi. */
    private function applyCatalogSort($query, string $sort)
    {
        return match ($sort) {
            'new' => $query->orderByDesc('created_at'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('totalSales'),
        };
    }

    public function sitemap()
    {
        try {
            $books = $this->visibleBooks()
                ->select('id', 'name', 'updated_at')
                ->orderByDesc('updated_at')
                ->take(5000)
                ->get();

            $stationeries = $this->visibleStationeries()
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
            $books = $this->visibleBooks(['publisher', 'category'])
                ->take(5000)
                ->get();

            $stationeries = $this->visibleStationeries(['category'])
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
