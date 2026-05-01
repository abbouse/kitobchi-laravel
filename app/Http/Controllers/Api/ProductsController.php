<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\ProductViewLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    private function formatInStoreIsbnCandidate($book, $user = null): array
    {
        return $this->formatProduct($book, $user, 'book');
    }

    private function normalizeScannedProductCode(string $raw): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', trim($raw)) ?? '';
        if ($clean === '') return null;
        return strlen($clean) >= 8 && strlen($clean) <= 14 ? $clean : null;
    }

    private function stationeryHasAvailableStock(Stationery $stationery): bool
    {
        if ($stationery->relationLoaded('variants') && $stationery->variants->isNotEmpty()) {
            return $stationery->variants->contains(fn($variant) => (int) ($variant->stock ?? 0) > 0);
        }

        return (int) ($stationery->stock ?? 0) > 0;
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

    private function isRecommendationActiveForProduct($product): bool
    {
        if (!($product->recommended ?? false)) {
            return false;
        }

        if (empty($product->recommendedExpiresAt)) {
            return true;
        }

        return Carbon::parse($product->recommendedExpiresAt)->isFuture();
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

    private function productKey(int|string|null $id, string $type): string
    {
        return $type . '_' . $id;
    }

    private function appendProducts($result, $products)
    {
        return $result
            ->merge($products)
            ->unique(fn($p) => $this->productKey($p['id'] ?? null, $p['type'] ?? 'book'))
            ->values();
    }

    private function resultIdsByType($result, string $type): array
    {
        return $result
            ->filter(fn($item) => ($item['type'] ?? null) === $type)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    private function premiumSellerScope($query)
    {
        return $query
            ->where('isPremiumShop', true)
            ->where(fn($q) => $q
                ->whereNull('isPremiumExpiresAt')
                ->orWhere('isPremiumExpiresAt', '>', now())
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
        $limit = max(12, min((int) $request->query('limit', 18), 24));

        $cartBookCategoryIds = [];
        $cartStationeryCategoryIds = [];
        $cartBookIds = [];
        $cartStationeryIds = [];

        if ($user) {
            $cartItems = MyCart::where('user_id', $user->id)
                ->with(['product', 'variant'])
                ->get();

            $cartBookCategoryIds = $cartItems
                ->where('product_type', 'book')
                ->map(fn($item) => $item->product?->category_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $cartStationeryCategoryIds = $cartItems
                ->where('product_type', 'stationery')
                ->map(fn($item) => $item->product?->category_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $cartBookIds = $cartItems
                ->where('product_type', 'book')
                ->pluck('product_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            $cartStationeryIds = $cartItems
                ->where('product_type', 'stationery')
                ->pluck('product_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();
        }

        $result = collect();

        $pushBooks = function ($query, int $take) use (&$result, $user, $limit, $cartBookIds) {
            if ($result->count() >= $limit || $take <= 0) {
                return;
            }

            $products = $query
                ->whereNotIn('id', array_merge($cartBookIds, $this->resultIdsByType($result, 'book')))
                ->with(['seller', 'category', 'tags'])
                ->limit($take)
                ->get()
                ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

            $result = $this->appendProducts($result, $products);
        };

        $pushStationery = function ($query, int $take) use (&$result, $user, $limit, $cartStationeryIds) {
            if ($result->count() >= $limit || $take <= 0) {
                return;
            }

            $products = $query
                ->whereNotIn('id', array_merge($cartStationeryIds, $this->resultIdsByType($result, 'stationery')))
                ->with(['seller', 'category', 'tags', 'variants'])
                ->limit($take)
                ->get()
                ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

            $result = $this->appendProducts($result, $products);
        };

        // 1. Recommended mahsulotlar
        $pushBooks(
            $this->isRecommended($this->bookScope())
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales'),
            $limit
        );
        $pushStationery(
            $this->isRecommended($this->stationeryScope())
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales'),
            max(0, $limit - $result->count())
        );

        // 2. Premium do'kon mahsulotlari
        if ($result->count() < $limit) {
            $need = $limit - $result->count();
            $pushBooks(
                $this->bookScope()
                    ->whereHas('seller', fn($q) => $this->premiumSellerScope($q))
                    ->orderByDesc('recommended')
                    ->orderByDesc('totalSalesWeek')
                    ->orderByDesc('totalSales'),
                $need
            );
            $pushStationery(
                $this->stationeryScope()
                    ->whereHas('seller', fn($q) => $this->premiumSellerScope($q))
                    ->orderByDesc('recommended')
                    ->orderByDesc('totalSalesWeek')
                    ->orderByDesc('totalSales'),
                max(0, $limit - $result->count())
            );
        }

        // 3. Cart itemlarga o'xshash mahsulotlar
        if ($result->count() < $limit && (!empty($cartBookCategoryIds) || !empty($cartStationeryCategoryIds))) {
            if (!empty($cartBookCategoryIds)) {
                $pushBooks(
                    $this->bookScope()
                        ->whereIn('category_id', $cartBookCategoryIds)
                        ->orderByDesc('recommended')
                        ->orderByDesc('totalSalesWeek')
                        ->orderByDesc('totalSales'),
                    $limit - $result->count()
                );
            }

            if (!empty($cartStationeryCategoryIds)) {
                $pushStationery(
                    $this->stationeryScope()
                        ->whereIn('category_id', $cartStationeryCategoryIds)
                        ->orderByDesc('recommended')
                        ->orderByDesc('totalSalesWeek')
                        ->orderByDesc('totalSales'),
                    max(0, $limit - $result->count())
                );
            }
        }

        // 4. Statistikasi yuqori trenddagi mahsulotlar
        if ($result->count() < $limit) {
            $pushBooks(
                $this->bookScope()
                    ->orderByDesc('totalSalesWeek')
                    ->orderByDesc('totalSales')
                    ->orderByDesc('views'),
                $limit - $result->count()
            );
            $pushStationery(
                $this->stationeryScope()
                    ->orderByDesc('totalSalesWeek')
                    ->orderByDesc('totalSales')
                    ->orderByDesc('views'),
                max(0, $limit - $result->count())
            );
        }

        // 5. Yetmasa random fallback
        if ($result->count() < $limit) {
            $pushBooks(
                $this->bookScope()
                    ->inRandomOrder(),
                $limit - $result->count()
            );
            $pushStationery(
                $this->stationeryScope()
                    ->inRandomOrder(),
                max(0, $limit - $result->count())
            );
        }

        return response()->json([
            'status' => 'success',
            'data' => $result->take($limit)->values(),
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
    //  5c. SELLER MAHSULOTI ISBN BO'YICHA — mijoz "do'kon ichida" skaneri uchun
    //  GET /products/sellers/{sellerId}/by-isbn/{isbn}
    //
    //  Faqat shu sotuvchining is_approved + count > 0 kitoblari ichidan
    //  ISBN bo'yicha mosini topadi. Topilmasa, shu seller'da yo'q deb ham
    //  alohida xabar berish uchun: 404 ichida `cause: not_in_shop` yoki
    //  `cause: not_in_database` kelishi mumkin.
    // =========================================================================
    public function sellerProductByIsbn(Request $request, $sellerId, string $isbn)
    {
        return $this->sellerProductByCode($request, $sellerId, $isbn);
    }

    public function sellerProductByCode(Request $request, $sellerId, string $code)
    {
        $normalizedCode = $this->normalizeScannedProductCode($code);
        if ($normalizedCode === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Shtrix-kod formati noto\'g\'ri.',
            ], 422);
        }

        $seller = Seller::where('id', $sellerId)
            ->where('is_hidden', 0)
            ->where('parent_id', 0)
            ->where('status', 'approved')
            ->first(['id']);

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Do\'kon topilmadi.',
            ], 404);
        }

        $booksInSeller = Books::query()
            ->whereIsbn($normalizedCode)
            ->where('seller_id', $sellerId)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with(['category', 'tags', 'seller'])
            ->orderByDesc('updated_at')
            ->get();

        $bookMatches = $booksInSeller->where('count', '>', 0)->values();

        $stationeryInSeller = Stationery::query()
            ->where('barcode', $normalizedCode)
            ->where('seller_id', $sellerId)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with(['category', 'tags', 'seller', 'variants'])
            ->orderByDesc('updated_at')
            ->get();

        $stationeryMatches = $stationeryInSeller
            ->filter(fn($item) => $this->stationeryHasAvailableStock($item))
            ->values();

        $matches = collect()
            ->merge($bookMatches->map(fn($book) => ['type' => 'book', 'model' => $book]))
            ->merge($stationeryMatches->map(fn($item) => ['type' => 'stationery', 'model' => $item]))
            ->values();

        if ($matches->count() === 1) {
            $match = $matches->first();
            return response()->json([
                'status' => 'success',
                'data'   => $this->formatProduct($match['model'], Auth::guard('user')->user(), $match['type']),
            ]);
        }

        if ($matches->count() > 1) {
            $user = Auth::guard('user')->user();

            return response()->json([
                'status'           => 'success',
                'multiple_matches' => true,
                'message'          => "Bu shtrix-kod bo'yicha bir nechta mahsulot topildi. Kerakli variantni tanlang.",
                'candidates'       => $matches
                    ->map(fn($match) => $this->formatProduct($match['model'], $user, $match['type']))
                    ->values(),
            ]);
        }

        if ($booksInSeller->isNotEmpty() || $stationeryInSeller->isNotEmpty()) {
            return response()->json([
                'status'  => 'error',
                'cause'   => 'sold_out',
                'message' => "Bunday mahsulotdan qolmagan.",
            ], 404);
        }

        $existsAnywhere = Books::query()
            ->whereIsbn($normalizedCode)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->exists()
            || Stationery::query()
                ->where('barcode', $normalizedCode)
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->exists();

        return response()->json([
            'status'  => 'error',
            'cause'   => $existsAnywhere ? 'not_in_shop' : 'not_in_database',
            'message' => $existsAnywhere
                ? "Bu mahsulot bu do'konda sotilmaydi."
                : "Bu mahsulot Kitobchi bazasida yo'q. Sotuvchidan ushbu mahsulotni qo'shishini so'rang.",
        ], 404);
    }

    // =========================================================================
    //  5b. QR TOKEN ORQALI SELLER QIDIRISH — "Do'kon ichida" rejimi
    //  GET /products/sellers/by-qr/{token}
    //
    //  Mijoz do'konga osib qo'yilgan QR'ni skaner qilganida, app shu endpoint'ga
    //  murojaat qiladi. Backend tokenga mos sellerni topadi va id + ko'rsatish
    //  uchun zarur maydonlarni qaytaradi. Keyin Flutter mavjud
    //  /products/sellers/profile/{id}/{page} ni chaqirib to'liq katalogni
    //  oladi (in-store mode bayrog'i bilan).
    // =========================================================================
    public function sellerByQr(Request $request, string $token)
    {
        // Token formatini soft-validate qilamiz: 40 belgi, alfanumerik.
        // Str::random alfa+digit ishlatadi. Boshqa belgilar kelsa darrov 404.
        if (!preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'QR formati noto\'g\'ri.',
            ], 422);
        }

        $location = SellerLocation::query()
            ->where('qr_token', $token)
            ->where('is_deleted', false)
            ->with(['seller' => function ($query) {
                $query->where('is_hidden', 0)
                    ->where('parent_id', 0)
                    ->where('status', 'approved');
            }])
            ->first();

        if (!$location || !$location->seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Filial topilmadi yoki QR yangilangan. Iltimos, do\'kondan yangi QR\'ni so\'rang.',
            ], 404);
        }

        $seller = $location->seller;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'seller' => array_merge(
                    $this->formatSellerInfo($seller),
                    [
                        'location' => [
                            'id'      => $location->id,
                            'address' => $location->fullAddress,
                            'lat'     => $location->lat,
                            'lon'     => $location->lon,
                            'description' => $location->description,
                            'is_main' => (bool) $location->is_main,
                        ],
                    ]
                ),
                'in_store_mode' => true,
            ],
        ]);
    }

    public function trackView(Request $request, string $type, int $id)
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahsulot turi noto‘g‘ri.',
            ], 422);
        }

        $product = $type === 'book'
            ? Books::query()
                ->where('id', $id)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->first()
            : Stationery::query()
                ->where('id', $id)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->first();

        if (!$product || !(int) ($product->seller_id ?? 0)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahsulot topilmadi.',
            ], 404);
        }

        $recommendationActive = $this->isRecommendationActiveForProduct($product);

        ProductViewLog::query()->create([
            'seller_id' => (int) $product->seller_id,
            'product_id' => (int) $product->id,
            'product_type' => $type,
            'user_id' => optional(Auth::guard('user')->user())->id,
            'recommendation_active' => $recommendationActive,
            'device_id' => $request->header('X-Device-Id'),
            'session_id' => $request->header('X-Session-Id'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $product->increment('views');

        return response()->json([
            'status' => 'success',
            'data' => [
                'views' => (int) ($product->views + 1),
                'recommendation_active' => $recommendationActive,
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
