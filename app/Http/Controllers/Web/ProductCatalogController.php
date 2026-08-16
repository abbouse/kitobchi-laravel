<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Policy;
use App\Models\Publisher;
use App\Models\Seller;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Support\ProductVisibilityScope;
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
            // MUHIM: withCount(['likes','comments']) — piyolamarket'dagi kabi har
            // bir sharh (BookClub posti) ostida "N kishi foydali deb topdi" (like)
            // va "N ta izoh" (comment) sonini ko'rsatish uchun. Ilova (mobil)dagi
            // bilan bir xil mantiq: post = sharh, like = "foydali", comment = izoh.
            $ugcReviews = \App\Models\BookClub::with(['user', 'images'])
                ->withCount(['likes', 'comments'])
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

        try {
            $aiRecommendations = $this->aiRecommendedProducts($book, 'book', 8);
        } catch (\Throwable $e) {
            $aiRecommendations = collect();
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
            'aiRecommendations' => $aiRecommendations,
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

    /**
     * "AI tavsiya" — mobil ilova (item.dart)dagi kabi vektor-asosidagi
     * o'xshashlik hisoblovi. Kategoriya/muallif bo'yicha oddiy moslashdan
     * farqli o'laroq bu yerda mahsulotning AI embedding'i (vectorData —
     * OpenAI orqali oldindan hisoblab qo'yilgan) boshqa mahsulotlarnikiga
     * kosinus o'xshashligi bo'yicha solishtiriladi, so'ng kategoriya/tag/
     * muallif/nashriyot/matn tokenlari va sotuv statistikasi bilan
     * kuchaytiriladi. Xuddi shu mantiq Api/ProductsController::
     * similarProducts()'da mobil ilova uchun ishlatiladi — bu yerda web
     * uchun soddalashtirilgan nusxasi (natija saytda alohida, "🤖 AI
     * tavsiya" deb belgilangan bo'limda — oddiy "O'xshash mahsulotlar"
     * ro'yxatidan vizual jihatdan ajratib ko'rsatiladi).
     */
    private function aiRecommendedProducts($base, string $type, int $limit = 8)
    {
        $baseTagIds = $this->productTagIds($base);
        $tokens = $this->similarityTokens(implode(' ', array_filter([
            $base->name ?? '',
            $base->author ?? '',
            $base->material ?? '',
            $base->description ?? '',
        ])));

        $query = $type === 'book'
            ? $this->visibleBooks(['category', 'tags'])
            : $this->visibleStationeries(['category', 'tags']);

        $query->where('id', '!=', $base->id);

        $hasFocusedFilter = ! empty($base->category_id)
            || ($type === 'book' && ! empty($base->author))
            || ($type === 'stationery' && ! empty($base->material))
            || ! empty($baseTagIds)
            || ! empty($tokens);

        if ($hasFocusedFilter) {
            $query->where(function ($q) use ($base, $type, $baseTagIds, $tokens) {
                if (! empty($base->category_id)) {
                    $q->orWhere('category_id', $base->category_id);
                }
                if ($type === 'book' && ! empty($base->author)) {
                    $q->orWhereRaw('LOWER(author) = ?', [mb_strtolower($base->author)]);
                }
                if ($type === 'stationery' && ! empty($base->material)) {
                    $q->orWhereRaw('LOWER(material) = ?', [mb_strtolower($base->material)]);
                }
                if (! empty($baseTagIds)) {
                    $q->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('id', $baseTagIds));
                }
                foreach (array_slice($tokens, 0, 6) as $token) {
                    $q->orWhere('name', 'like', '%'.$token.'%');
                }
            });
        }

        $candidates = $query
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit(200)
            ->get();

        return $candidates
            ->map(function ($product) use ($base, $type, $baseTagIds, $tokens) {
                $product->ai_score = $this->aiSimilarityScore($base, $product, $type, $baseTagIds, $tokens);

                return $product;
            })
            ->sortByDesc(fn ($product) => $product->ai_score)
            ->take($limit)
            ->values();
    }

    private function aiSimilarityScore($base, $product, string $type, array $baseTagIds, array $baseTokens): float
    {
        $score = 0.0;

        $vectorScore = $this->aiVectorSimilarity($base->vectorData ?? null, $product->vectorData ?? null);
        if ($vectorScore !== null) {
            $score += $vectorScore * 80;
        }

        if (! empty($base->category_id) && (string) $base->category_id === (string) ($product->category_id ?? '')) {
            $score += 45;
        }

        $tagOverlap = count(array_intersect($baseTagIds, $this->productTagIds($product)));
        $score += min(36, $tagOverlap * 12);

        if ($type === 'book') {
            if (! empty($base->author) && mb_strtolower($base->author) === mb_strtolower((string) ($product->author ?? ''))) {
                $score += 35;
            }
            if (! empty($base->publisher_id) && (string) $base->publisher_id === (string) ($product->publisher_id ?? '')) {
                $score += 10;
            }
        } elseif (! empty($base->material) && mb_strtolower($base->material) === mb_strtolower((string) ($product->material ?? ''))) {
            $score += 18;
        }

        $productTokens = $this->similarityTokens(implode(' ', array_filter([
            $product->name ?? '',
            $product->author ?? '',
            $product->material ?? '',
            $product->description ?? '',
        ])));
        $tokenOverlap = count(array_intersect($baseTokens, $productTokens));
        $score += min(32, $tokenOverlap * 8);

        $score += min(8, ((int) ($product->totalSalesWeek ?? 0)) * 0.15);
        $score += min(6, ((int) ($product->totalSales ?? 0)) * 0.03);

        if ((bool) ($product->recommended ?? false)) {
            $score += 3;
        }

        return $score;
    }

    private function productTagIds($product): array
    {
        if (! $product->relationLoaded('tags') || ! $product->tags) {
            return [];
        }

        return $product->tags
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function similarityTokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/u', trim($text)) ?: [];

        return collect($parts)
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function aiVectorSimilarity($left, $right): ?float
    {
        $a = $this->aiNormalizeVector($left);
        $b = $this->aiNormalizeVector($right);

        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return null;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $other = $b[$i] ?? 0.0;
            $dot += $value * $other;
            $normA += $value * $value;
            $normB += $other * $other;
        }

        if ($normA <= 0 || $normB <= 0) {
            return null;
        }

        return max(0.0, min(1.0, $dot / (sqrt($normA) * sqrt($normB))));
    }

    private function aiNormalizeVector($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_numeric($item) ? (float) $item : null,
            $value
        ), fn ($item) => $item !== null));
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
                ->withCount(['likes', 'comments'])
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

        try {
            $aiRecommendations = $this->aiRecommendedProducts($item, 'stationery', 8);
        } catch (\Throwable $e) {
            $aiRecommendations = collect();
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
            'aiRecommendations' => $aiRecommendations,
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

        // Kategoriyaga xos atribut filtrlari (piyolamarket.uz'dagi kabi) —
        // bizda alohida "attribute" jadvali yo'q, shu sabab loyihada
        // haqiqatda mavjud bo'lgan real ustunlardan foydalanamiz:
        // kitoblar uchun muallif (author) va muqova turi (coverType, ozod
        // matn — shuning uchun "yumshoq/qattiq" ikkita guruhga normalize
        // qilinadi, xuddi Api/Seller/ProductController'dagi
        // normalizeCoverTypeOut bilan bir xil mantiqda), kanselyariya
        // uchun material.
        $authorNames = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            (array) $request->input('authors', [])
        ), fn ($v) => $v !== ''));
        $coverTypes = array_values(array_intersect(
            (array) $request->input('cover_types', []),
            ['soft', 'hard']
        ));
        $materialNames = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            (array) $request->input('materials', [])
        ), fn ($v) => $v !== ''));

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

            // MUHIM: avval bu yerda faqat status='approved' tekshirilardi —
            // ProductVisibilityScope::activeSeller() esa yana ikkita shartni
            // talab qiladi: is_hidden=false (admin tomonidan yashirilgan
            // do'kon chiqmasin) va parent_id bo'sh/0 (do'kon HODIMLARI ham
            // Seller jadvalida alohida qator sifatida saqlanadi — ular
            // filtrlar ro'yxatida ALOHIDA "do'kon" bo'lib ko'rinmasligi
            // kerak, faqat asosiy/egasi hisobi ko'rinadi). Bu yagona qoida
            // — xuddi katalogdagi mahsulot ko'rinish shartlari bilan bir xil
            // manbadan (ProductVisibilityScope) olinadi.
            $sellers = (ProductVisibilityScope::activeSeller())(Seller::query())
                ->orderBy('shop_name')
                ->get();

            // Nashriyotlar jadvalida alohida is_active/is_hidden ustuni yo'q —
            // "nofaol" nashriyot degani shu nashriyotning HOZIR ko'rinadigan
            // (visible) birorta ham kitobi yo'qligi (masalan yagona kitobi
            // moderatsiyada yoki do'koni bloklangan bo'lishi mumkin). Bunday
            // nashriyot filtr ro'yxatida ko'rsatilsa, tanlanganda 0 natija
            // qaytaradi — shuning uchun chiqarilmaydi.
            $publishers = Publisher::whereHas('books', function ($q) {
                ProductVisibilityScope::applyBooks($q);
            })->orderBy('name')->get();

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
                if (!empty($materialNames)) {
                    $productsQuery->whereIn('material', $materialNames);
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
                if (!empty($authorNames)) {
                    $productsQuery->whereIn('author', $authorNames);
                }
                if (!empty($coverTypes)) {
                    // "qattiq"/"hard" so'zini o'z ichiga olgan qiymatlar — Qattiq
                    // muqova guruhi; qolgan hamma (bo'sh bo'lmagan) qiymat —
                    // Yumshoq guruhi. Xuddi normalizeCoverTypeOut() bilan bir xil.
                    $productsQuery->where(function ($q) use ($coverTypes) {
                        if (in_array('hard', $coverTypes, true)) {
                            $q->orWhere(function ($qq) {
                                $qq->where('coverType', 'like', '%qat%')
                                    ->orWhere('coverType', 'like', '%hard%');
                            });
                        }
                        if (in_array('soft', $coverTypes, true)) {
                            $q->orWhere(function ($qq) {
                                $qq->whereNotNull('coverType')
                                    ->where('coverType', '!=', '')
                                    ->where('coverType', 'not like', '%qat%')
                                    ->where('coverType', 'not like', '%hard%');
                            });
                        }
                    });
                }

                $products = $this->applyCatalogSort($productsQuery, $sort)->paginate(24, ['*'], 'page');
            }

            // Filtr paneli variantlari — faqat JORIY turkum (kitob/kanselyariya)
            // va JORIY kategoriya ichida haqiqatda mavjud bo'lgan qiymatlar
            // ko'rsatiladi (piyolamarket'dagi kabi "kategoriyaga xos" filtrlar —
            // bo'sh yoki mos kelmaydigan variant hech qachon chiqmaydi).
            $authorOptions = collect();
            $coverTypeAvailable = false;
            $materialOptions = collect();

            if ($type === 'stationery') {
                $matQuery = $this->applyStationeryVisibility(Stationery::query());
                if ($categoryId) {
                    $matQuery->where('category_id', $categoryId);
                }
                $materialOptions = $matQuery->whereNotNull('material')
                    ->where('material', '!=', '')
                    ->distinct()
                    ->orderBy('material')
                    ->limit(40)
                    ->pluck('material');
            } else {
                $attrQuery = $this->applyBookVisibility(Books::query());
                if ($categoryId) {
                    $attrQuery->where('category_id', $categoryId);
                }
                $authorOptions = (clone $attrQuery)
                    ->whereNotNull('author')
                    ->where('author', '!=', '')
                    ->distinct()
                    ->orderBy('author')
                    ->limit(60)
                    ->pluck('author');
                $coverTypeAvailable = (clone $attrQuery)
                    ->whereNotNull('coverType')
                    ->where('coverType', '!=', '')
                    ->exists();
            }
        } catch (\Throwable $e) {
            $bookCategories = collect();
            $stationeryCategories = collect();
            $sellers = collect();
            $publishers = collect();
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);
            $authorOptions = collect();
            $coverTypeAvailable = false;
            $materialOptions = collect();
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

        $currentCategory = null;
        if ($categoryId) {
            $currentCategory = ($type === 'stationery')
                ? $stationeryCategories->firstWhere('id', $categoryId)
                : $bookCategories->firstWhere('id', $categoryId);
        }

        return view('products.catalog', [
            'products' => $products,
            'bookCategories' => $bookCategories,
            'stationeryCategories' => $stationeryCategories,
            'currentCategory' => $currentCategory,
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
            'authorOptions' => $authorOptions ?? collect(),
            'coverTypeAvailable' => $coverTypeAvailable ?? false,
            'materialOptions' => $materialOptions ?? collect(),
            'selectedAuthors' => $authorNames,
            'selectedCoverTypes' => $coverTypes,
            'selectedMaterials' => $materialNames,
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
