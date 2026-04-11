<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\Seller;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductsController extends Controller
{
    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function formatProduct($product, $user = null, string $type = 'book'): array
    {
        $isBook = $type === 'book';

        // ── Chegirma amal qilish vaqti ────────────────────────────
        // null = abadiy, date = muddati bor
        $discountExpiresAt = null;
        if ($isBook) {
            $dp = $product->discountPrice ?? 0;
            if ($dp > 0) {
                $discountExpiresAt = $product->discountExpiresAt
                    ? Carbon::parse($product->discountExpiresAt)->toISOString()
                    : null; // null = abadiy
            }
        } else {
            $dp = $product->discount_price ?? 0;
            if ($dp > 0) {
                $discountExpiresAt = $product->discountExpiresAt
                    ? Carbon::parse($product->discountExpiresAt)->toISOString()
                    : null; // null = abadiy
            }
        }

        return [
            'id'                 => $product->id,
            'type'               => $isBook ? 'book' : 'stationery',
            'name'               => $product->name,
            'author'             => $isBook ? ($product->author ?? null) : null,
            'material'           => $isBook ? null : ($product->material ?? null),
            'category_id'        => $product->category_id,
            'images'             => $product->images ?? [],
            'description'        => $product->description ?? null,
            'price'              => $product->price,
            'discountPrice'      => $isBook
                ? ($product->discountPrice ?? $product->price)
                : ($product->discount_price ?? $product->price),
            'discountExpiresAt'  => $discountExpiresAt, // null = abadiy, ISO = muddatli
            'count'              => $isBook ? $product->count : $product->stock,
            'sales'              => $product->totalSales ?? 0,
            'weekly_sales'       => $product->totalSalesWeek ?? 0,
            'recommended'        => (bool)($product->recommended ?? false),
            'lang'               => $isBook ? ($product->lang ?? "O'zbek") : null,
            'langType'           => $isBook ? ($product->langType ?? '') : null,
            'coverType'          => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year'               => $isBook ? ($product->year ?? now()->year) : null,
            'favourite'          => $user
                ? FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->where('product_type', $isBook ? 'book' : 'stationery')
                    ->exists()
                : false,
            'category'           => $product->category?->title ?? null,
            'tags'               => $product->relationLoaded('tags')
                ? $product->tags->map(fn($tag) => [
                    'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
                    'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
                    'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
                  ])->filter()->values()
                : [],
            'seller'             => [
                'seller_id'   => $product->seller?->id,
                'shop_name'   => $product->seller?->shop_name,
                'photo'       => $product->seller?->photo,
                'isVerified'  => $product->seller?->isVerified,
                'isPremium'   => $this->sellerIsPremium($product->seller),
                'hasSale'     => $this->sellerHasManyDiscounts($product->seller?->id),
            ],
            'variants'           => !$isBook && $product->relationLoaded('variants')
                ? $product->variants->map(fn($v) => [
                    'id'         => $v->id,
                    'color_name' => $v->color_name,
                    'image'      => $v->image_path ?? null,
                    'stock'      => $v->stock,
                  ])
                : null,
        ];
    }

    // ── Do'kon premium ekanligini tekshirish ─────────────────────
    // isPremiumShop = true VA (isPremiumExpiresAt null YOKI kelajakda)
    private function sellerIsPremium($seller): bool
    {
        if (!$seller || !$seller->isPremiumShop) return false;
        if (!$seller->isPremiumExpiresAt) return true; // muddatsiz
        return Carbon::parse($seller->isPremiumExpiresAt)->isFuture();
    }

    // ── Do'konda 10+ faol chegirma borligini tekshirish ──────────
    // Cache: bir so'rovda bir seller uchun bir marta hisoblaydi
    private array $_sellerDiscountCache = [];

    private function sellerHasManyDiscounts(?int $sellerId, int $threshold = 10): bool
    {
        if (!$sellerId) return false;
        if (isset($this->_sellerDiscountCache[$sellerId])) {
            return $this->_sellerDiscountCache[$sellerId];
        }

        $bookCount = Books::where('seller_id', $sellerId)
            ->where('is_approved', 1)->where('is_hidden', 0)->where('count', '>', 0)
            ->where('discountPrice', '>', 0)
            ->where(fn($q) => $q->whereNull('discountExpiresAt')
                ->orWhere('discountExpiresAt', '>', now()))
            ->count();

        if ($bookCount >= $threshold) {
            $this->_sellerDiscountCache[$sellerId] = true;
            return true;
        }

        $statCount = Stationery::where('seller_id', $sellerId)
            ->where('is_approved', 1)->where('is_hidden', 0)->where('stock', '>', 0)
            ->where('discount_price', '>', 0)
            ->where(fn($q) => $q->whereNull('discountExpiresAt')
                ->orWhere('discountExpiresAt', '>', now()))
            ->count();

        $result = ($bookCount + $statCount) >= $threshold;
        $this->_sellerDiscountCache[$sellerId] = $result;
        return $result;
    }

    // ── Seller info formatlash (seller() va sellersWithLatest uchun) ─
    private function formatSellerInfo($seller): array
    {
        return [
            'id'              => $seller->id,
            'shop_name'       => $seller->shop_name,
            'photo'           => $seller->photo,
            'firstname'       => $seller->firstname,
            'lastname'        => $seller->lastname,
            'region'          => $seller->region,
            'activity_types'  => $seller->activity_types,
            'isVerified'      => $seller->isVerified,
            'isPremium'       => $this->sellerIsPremium($seller),
            'hasSale'         => $this->sellerHasManyDiscounts($seller->id),
        ];
    }


    private function bookScope()
    {
        return Books::where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0));
    }

    // ── Stationery uchun base scope ───────────────────────────────
    private function stationeryScope()
    {
        return Stationery::where('stock', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0));
    }

    // ── Recommended scope (hozir amal qilayotgan) ─────────────────
    // recommended = true VA (recommendedExpiresAt null YOKI kelajakda)
    private function isRecommended($query)
    {
        return $query->where('recommended', true)
            ->where(fn($q) => $q
                ->whereNull('recommendedExpiresAt')
                ->orWhere('recommendedExpiresAt', '>', now())
            );
    }

    // ── Faol chegirma scope ───────────────────────────────────────
    // discountPrice > 0 VA (discountExpiresAt null YOKI kelajakda)
    private function hasActiveDiscount($query, bool $isBook = true)
    {
        $priceCol = $isBook ? 'discountPrice' : 'discount_price';
        return $query->where($priceCol, '>', 0)
            ->where(fn($q) => $q
                ->whereNull('discountExpiresAt')
                ->orWhere('discountExpiresAt', '>', now())
            );
    }

    // =========================================================================
    //  1. YANGI MAHSULOTLAR
    //  GET /products/{col}
    // =========================================================================
    public function index(Request $request, string $col)
    {
        $user  = Auth::guard('user')->user();
        $books = $this->bookScope()
            ->with(['seller', 'category', 'tags'])
            ->orderBy('created_at', 'DESC')
            ->limit((int)$col)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $books->map(fn($b) => $this->formatProduct($b, $user, 'book')),
        ]);
    }

    // =========================================================================
    //  2. TAVSIYALAR — bosh sahifa uchun
    //  GET /products/recommendation/{col}
    //
    //  Ustuvorlik tartibi:
    //    1. recommended=true va muddati o'tmagan (eng yuqori priority)
    //    2. Faol chegirmadagi mahsulotlar
    //    3. Haftalik sotuvga qarab (eng ko'p sotilgan)
    //    4. Yangi mahsulotlar (fallback)
    //  Kitob va stationery aralash, deduplicate
    // =========================================================================
    public function recommendation(Request $request, string $col)
    {
        $user  = Auth::guard('user')->user();
        $limit = (int)$col;
        $result = collect();

        // ── 1. Recommended (true + muddati o'tmagan) ─────────────
        $recBooks = $this->isRecommended($this->bookScope())
            ->with(['category', 'tags', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->limit($limit)
            ->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        $recStats = $this->isRecommended($this->stationeryScope())
            ->with(['category', 'tags', 'variants', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->limit($limit)
            ->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        $result = $result->merge($recBooks)->merge($recStats);

        // ── 2. Faol chegirmadagi (yetarli bo'lmasa) ───────────────
        if ($result->count() < $limit) {
            $existIds = $result->pluck('id')->toArray();

            $discBooks = $this->hasActiveDiscount($this->bookScope(), true)
                ->whereNotIn('id', $existIds)
                ->with(['category', 'tags', 'seller'])
                ->orderByDesc('totalSalesWeek')
                ->limit($limit - $result->count())
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $result->merge($discBooks);
        }

        // ── 3. Haftalik sotuvga qarab (hali ham yetmasa) ──────────
        if ($result->count() < $limit) {
            $existIds = $result->pluck('id')->toArray();
            $need     = $limit - $result->count();

            $trendBooks = $this->bookScope()
                ->whereNotIn('id', $existIds)
                ->with(['category', 'tags', 'seller'])
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales')
                ->limit($need)
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $trendStats = $this->stationeryScope()
                ->whereNotIn('id', $existIds)
                ->with(['category', 'tags', 'variants', 'seller'])
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales')
                ->limit($need)
                ->get()
                ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

            $result = $result->merge($trendBooks)->merge($trendStats);
        }

        // ── 4. Yangi mahsulotlar (oxirgi fallback) ────────────────
        if ($result->count() < $limit) {
            $existIds = $result->pluck('id')->toArray();

            $newBooks = $this->bookScope()
                ->whereNotIn('id', $existIds)
                ->with(['category', 'tags', 'seller'])
                ->orderByDesc('created_at')
                ->limit($limit - $result->count())
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $result->merge($newBooks);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result
                ->unique(fn($p) => $p['type'].'_'.$p['id'])
                ->shuffle()
                ->take($limit)
                ->values(),
        ]);
    }

    // =========================================================================
    //  3. SAVAT SAHIFASI UCHUN TAVSIYALAR
    //  GET /products/cart-recommendation
    //
    //  Ustuvorlik tartibi:
    //    A. Cartdagi kategoriyalarga o'xshash + recommended=true
    //    B. Cartdagi kategoriyalarga o'xshash (recommended bo'lmasa)
    //    C. Faol chegirmadagi mahsulotlar
    //    D. Haftalik eng ko'p sotilgan
    // =========================================================================
    public function cartRecommendation(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $cartItems = MyCart::where('user_id', $user->id)->with('product')->get();

        $cartCategoryIds = $cartItems
            ->map(fn($c) => $c->product?->category_id)
            ->filter()->unique()->toArray();

        $cartProductIds = $cartItems
            ->map(fn($c) => $c->product_id)
            ->filter()->toArray();

        $result = collect();

        // ── A. Cartdagi kategoriyalarda recommended ───────────────
        if (!empty($cartCategoryIds)) {
            $simRecBooks = $this->isRecommended($this->bookScope())
                ->whereIn('category_id', $cartCategoryIds)
                ->whereNotIn('id', $cartProductIds)
                ->with(['seller', 'category', 'tags'])
                ->orderByDesc('totalSalesWeek')
                ->limit(8)
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $result->merge($simRecBooks);
        }

        // ── B. Cartdagi kategoriyalar (recommended bo'lmasa ham) ──
        if (!empty($cartCategoryIds)) {
            $existIds = $result->pluck('id')->toArray();

            $similarBooks = $this->bookScope()
                ->whereIn('category_id', $cartCategoryIds)
                ->whereNotIn('id', array_merge($cartProductIds, $existIds))
                ->with(['seller', 'category', 'tags'])
                ->orderByDesc('totalSalesWeek')
                ->limit(10)
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $result->merge($similarBooks);
        }

        // ── C. Faol chegirmadagi mahsulotlar ──────────────────────
        $existIds = $result->pluck('id')->toArray();

        $discountBooks = $this->hasActiveDiscount($this->bookScope(), true)
            ->whereNotIn('id', array_merge($cartProductIds, $existIds))
            ->with(['seller', 'category', 'tags'])
            ->orderByDesc('totalSalesWeek')
            ->limit(8)
            ->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        $discountStats = $this->hasActiveDiscount($this->stationeryScope(), false)
            ->whereNotIn('id', $existIds)
            ->with(['seller', 'category', 'tags', 'variants'])
            ->orderByDesc('totalSalesWeek')
            ->limit(6)
            ->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        $result = $result->merge($discountBooks)->merge($discountStats);

        // ── D. Haftalik eng ko'p sotilgan (yetmasa) ───────────────
        if ($result->count() < 20) {
            $existIds = $result->pluck('id')->toArray();

            $trendBooks = $this->bookScope()
                ->whereNotIn('id', array_merge($cartProductIds, $existIds))
                ->with(['seller', 'category', 'tags'])
                ->orderByDesc('totalSalesWeek')
                ->limit(20 - $result->count())
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $result->merge($trendBooks);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result
                ->unique(fn($p) => $p['type'].'_'.$p['id'])
                ->shuffle()
                ->take(20)
                ->values(),
        ]);
    }

    // =========================================================================
    //  4. KATEGORIYA BO'YICHA KITOBLAR
    //  GET /products/books-by-category?type=new|recommended
    //
    //  type=recommended ustuvorlik tartibi:
    //    1. recommended=true va muddati o'tmagan
    //    2. Yetarli emas (< 3) → haftalik sotuvga qarab fallback
    //    3. Hech narsa yo'q → yangi kitoblar
    // =========================================================================
    public function booksByCategory(Request $request)
    {
        $user = Auth::guard('user')->user();
        $type = $request->query('type', 'new');

        try {
            $categoryIds = $this->bookScope()
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $result = [];

            foreach ($categoryIds as $catId) {
                $baseQuery = $this->bookScope()
                    ->where('category_id', $catId)
                    ->with(['seller', 'category', 'tags']);

                if ($type === 'recommended') {
                    // 1. Recommended bo'lganlarni olishga urinib ko'ramiz
                    $books = $this->isRecommended(clone $baseQuery)
                        ->orderByDesc('totalSalesWeek')
                        ->orderByDesc('totalSales')
                        ->limit(10)
                        ->get();

                    // 2. Kam bo'lsa (< 3) — haftalik sotuvga qarab to'ldiramiz
                    if ($books->count() < 3) {
                        $existIds = $books->pluck('id')->toArray();
                        $fill = (clone $baseQuery)
                            ->whereNotIn('id', $existIds)
                            ->orderByDesc('totalSalesWeek')
                            ->orderByDesc('totalSales')
                            ->limit(10 - $books->count())
                            ->get();
                        $books = $books->merge($fill);
                    }

                    // 3. Hali ham bo'sh — yangi kitoblar
                    if ($books->isEmpty()) {
                        $books = (clone $baseQuery)
                            ->orderByDesc('created_at')
                            ->limit(10)
                            ->get();
                    }
                } else {
                    // type=new — yangi kitoblar
                    $books = (clone $baseQuery)
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get();
                }

                if ($books->isEmpty()) continue;

                $category = $books->first()->category;
                if (!$category) continue;

                $result[] = [
                    'category_id' => $catId,
                    'name_uz'     => $category->name_uz ?? '',
                    'name_ru'     => $category->name_ru ?? '',
                    'name_en'     => $category->name_en ?? '',
                    'name_ja'     => $category->name_ja ?? '',
                    'books'       => $books
                        ->map(fn($b) => $this->formatProduct($b, $user, 'book'))
                        ->values()
                        ->toArray(),
                ];
            }

            usort($result, fn($a, $b) => count($b['books']) - count($a['books']));

            return response()->json([
                'status' => 'success',
                'type'   => $type,
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('booksByCategory error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Server xatosi'], 500);
        }
    }

    // =========================================================================
    //  5. SELLER SAHIFASI
    //  GET /products/seller/{id}
    // =========================================================================
    public function seller(Request $request, $id)
    {
        $user = Auth::guard('user')->user();

        $seller = Seller::where('id', $id)
            ->where('is_hidden', 0)
            ->where('parent_id', 0)
            ->where('status', 'approved')
            ->firstOrFail();

        $baseBookQ = fn() => Books::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)->where('count', '>', 0)
            ->with(['category', 'tags', 'seller']);

        $baseStatQ = fn() => Stationery::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)->where('stock', '>', 0)
            ->with(['category', 'tags', 'variants', 'seller']);

        // Chegirmali kitoblar (muddati o'tmagan)
        $discountedBooks = $this->hasActiveDiscount($baseBookQ(), true)
            ->orderByRaw('(price - discountPrice) DESC')
            ->limit(15)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Trend kitoblar
        $trendingBooks = $baseBookQ()
            ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales')
            ->limit(15)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Recommended kitoblar (muddati o'tmagan)
        $recommendedBooks = $this->isRecommended($baseBookQ())
            ->orderByDesc('totalSalesWeek')
            ->limit(15)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Kategoriya bo'yicha kitoblar
        $bookCategoryIds = Books::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)->where('count', '>', 0)
            ->whereNotNull('category_id')->distinct()->pluck('category_id');

        $booksByCategory = [];
        foreach ($bookCategoryIds as $catId) {
            $catBooks = $baseBookQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->limit(15)->get();
            if ($catBooks->isEmpty()) continue;
            $category = $catBooks->first()->category;
            if (!$category) continue;
            $booksByCategory[] = [
                'category_id' => $catId,
                'name_uz'     => $category->name_uz ?? '',
                'name_ru'     => $category->name_ru ?? '',
                'name_en'     => $category->name_en ?? '',
                'name_ja'     => $category->name_ja ?? '',
                'products'    => $catBooks->map(fn($b) => $this->formatProduct($b, $user, 'book'))->values(),
            ];
        }

        // Chegirmali stationery (muddati o'tmagan)
        $discountedStats = $this->hasActiveDiscount($baseStatQ(), false)
            ->orderByRaw('(price - discount_price) DESC')
            ->limit(15)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Trend stationery
        $trendingStats = $baseStatQ()
            ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales')
            ->limit(15)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Recommended stationery
        $recommendedStats = $this->isRecommended($baseStatQ())
            ->orderByDesc('totalSalesWeek')
            ->limit(15)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Kategoriya bo'yicha stationery
        $statCategoryIds = Stationery::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)->where('stock', '>', 0)
            ->whereNotNull('category_id')->distinct()->pluck('category_id');

        $stationeriesByCategory = [];
        foreach ($statCategoryIds as $catId) {
            $catStats = $baseStatQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->limit(15)->get();
            if ($catStats->isEmpty()) continue;
            $category = $catStats->first()->category;
            if (!$category) continue;
            $stationeriesByCategory[] = [
                'category_id' => $catId,
                'name_uz'     => $category->name_uz ?? '',
                'name_ru'     => $category->name_ru ?? '',
                'name_en'     => $category->name_en ?? '',
                'name_ja'     => $category->name_ja ?? '',
                'products'    => $catStats->map(fn($s) => $this->formatProduct($s, $user, 'stationery'))->values(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'seller' => $this->formatSellerInfo($seller),
                'books' => [
                    'discounted'   => $discountedBooks,
                    'trending'     => $trendingBooks,
                    'recommended'  => $recommendedBooks,
                    'by_category'  => $booksByCategory,
                ],
                'stationeries' => [
                    'discounted'   => $discountedStats,
                    'trending'     => $trendingStats,
                    'recommended'  => $recommendedStats,
                    'by_category'  => $stationeriesByCategory,
                ],
            ],
        ]);
    }

    // =========================================================================
    //  6. SELLERLAR + OXIRGI MAHSULOTLAR
    //  GET /products/sellers-with-latest
    // =========================================================================
    public function sellersWithLatestProducts(Request $request)
    {
        $user = Auth::guard('user')->user();

        $sellers = Seller::where('is_hidden', 0)
            ->where('parent_id', 0)
            ->where('status', 'approved')
            ->where(fn($q) => $q
                ->whereHas('books', fn($b) => $b
                    ->where('count', '>', 0)->where('is_hidden', 0)->where('is_approved', 1))
                ->orWhereHas('stationeries', fn($s) => $s
                    ->where('stock', '>', 0)->where('is_hidden', 0)->where('is_approved', 1))
            )
            ->with([
                'books' => fn($q) => $q
                    ->where('count', '>', 0)->where('is_hidden', 0)->where('is_approved', 1)
                    ->latest('created_at')->take(20)
                    ->with(['category', 'tags', 'seller']),
                'stationeries' => fn($q) => $q
                    ->where('stock', '>', 0)->where('is_hidden', 0)->where('is_approved', 1)
                    ->latest('created_at')->take(20)
                    ->with(['category', 'tags', 'variants', 'seller']),
            ])
            ->inRandomOrder()
            ->take(50)
            ->get();

        $result = $sellers->map(fn($seller) => [
            ...$this->formatSellerInfo($seller),
            'status'       => $seller->status,
            'books'        => $seller->books->map(fn($b) => $this->formatProduct($b, $user, 'book')),
            'stationeries' => $seller->stationeries->map(fn($s) => $this->formatProduct($s, $user, 'stationery')),
        ]);

        return response()->json(['status' => 'success', 'data' => $result]);
    }
}