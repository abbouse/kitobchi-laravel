<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasProductVisibility;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\StationeryVariant;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\ProductViewLog;
use App\Services\ProductPersonalizationService;
use App\Services\ProductStockAlertService;
use App\Support\ProductImageUrls;
use App\Support\ProductPayloadFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsController extends Controller
{
    use HasProductVisibility;

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

        return ProductPayloadFormatter::format($product, [
            'user' => $user,
            'type' => $isBook ? 'book' : 'stationery',
            'mode' => 'card',
            'category_format' => 'title',
            'extra' => [
                'discountExpiresAt' => $discountExpiresAt,
                'recommended' => (bool) ($product->recommended ?? false),
            ],
            'seller_extra' => [
                'isPremium' => $this->sellerIsPremium($product->seller),
                'hasSale' => $this->sellerHasManyDiscounts($product->seller?->id),
            ],
        ]);
    }

    private function formatProductDetail($product, $user = null, string $type = 'book'): array
    {
        $isBook = $type === 'book';

        $discountExpiresAt = null;
        if ($isBook) {
            $dp = $product->discountPrice ?? 0;
            if ($dp > 0) {
                $discountExpiresAt = $product->discountExpiresAt
                    ? Carbon::parse($product->discountExpiresAt)->toISOString()
                    : null;
            }
        } else {
            $dp = $product->discount_price ?? 0;
            if ($dp > 0) {
                $discountExpiresAt = $product->discountExpiresAt
                    ? Carbon::parse($product->discountExpiresAt)->toISOString()
                    : null;
            }
        }

        return ProductPayloadFormatter::format($product, [
            'user' => $user,
            'type' => $isBook ? 'book' : 'stationery',
            'mode' => 'detail',
            'category_format' => 'title',
            'extra' => [
                'discountExpiresAt' => $discountExpiresAt,
                'recommended' => (bool) ($product->recommended ?? false),
            ],
            'seller_extra' => [
                'isPremium' => $this->sellerIsPremium($product->seller),
                'hasSale' => $this->sellerHasManyDiscounts($product->seller?->id),
            ],
        ]);
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
            ->where('is_approved', 1)->where('is_hidden', 0)->inStock()
            ->where('discountPrice', '>', 0)
            ->where(fn($q) => $q->whereNull('discountExpiresAt')
                ->orWhere('discountExpiresAt', '>', now()))
            ->count();

        if ($bookCount >= $threshold) {
            $this->_sellerDiscountCache[$sellerId] = true;
            return true;
        }

        $statCount = Stationery::where('seller_id', $sellerId)
            ->where('is_approved', 1)->where('is_hidden', 0)->inStock()
            ->where('discount_price', '>', 0)
            ->where(fn($q) => $q->whereNull('discountExpiresAt')
                ->orWhere('discountExpiresAt', '>', now()))
            ->count();

        $result = ($bookCount + $statCount) >= $threshold;
        $this->_sellerDiscountCache[$sellerId] = $result;
        return $result;
    }

    // ── Seller info formatlash (seller() va sellersWithLatest uchun) ─
    // MUHIM: bu — BUYERGA (xaridorga) ko'rinadigan ma'lumot. Sotuvchining
    // ichki ishlash ko'rsatkichlari (reputation_score, successful_orders,
    // response_time_hours va h.k.) ATAYLAB shu yerdan chiqarib
    // tashlangan — xaridorga faqat reyting (rating + necha ta xariddan
    // ekanligi) ko'rinishi kerak, xolos. Ichki ko'rsatkichlar sotuvchining
    // o'z panelida (seller dashboard) ko'rinaveradi, bu yerga aloqasi yo'q.
    private function formatSellerInfo($seller): array
    {
        return [
            'id'              => $seller->id,
            'shop_name'       => $seller->shop_name,
            'photo'           => $seller->photo,
            'firstname'       => $seller->firstname,
            'lastname'        => $seller->lastname,
            'region'          => $seller->region,
            'rating'          => (float) ($seller->rating ?? 0),
            'rating_reviews_count' => (int) ($seller->rating_reviews_count ?? 0),
            'activity_types'  => $seller->activity_types,
            'isVerified'      => $seller->isVerified,
            'isPremium'       => $this->sellerIsPremium($seller),
            'hasSale'         => $this->sellerHasManyDiscounts($seller->id),
        ];
    }

    private function sellerCategoryPayload($category, $products, int $page, int $perPage, int $total): array
    {
        return [
            'category_id' => $category?->id ?? 0,
            'name_uz'     => $category?->name_uz ?? '',
            'name_ru'     => $category?->name_ru ?? '',
            'name_en'     => $category?->name_en ?? '',
            'name_ja'     => $category?->name_ja ?? '',
            'products'    => $products,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'has_more'    => ($page * $perPage) < $total,
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
        return $this->visibleBooks();
    }

    // ── Stationery uchun base scope ───────────────────────────────
    private function stationeryScope()
    {
        return $this->visibleStationeries();
    }

    private function publicBookDetailScope()
    {
        return $this->visibleBooks();
    }

    private function publicStationeryDetailScope()
    {
        return $this->visibleStationeries();
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
        $user  = $request->user('user') ?? auth('sanctum')->user() ?? Auth::guard('user')->user();
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
        $user  = $request->user('user') ?? auth('sanctum')->user() ?? Auth::guard('user')->user();
        $limit = (int)$col;
        $personalized = $this->personalizedRecommendations($request, $user, $limit);
        $result = collect($personalized);

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

        $items = $result
            ->unique(fn($p) => $p['type'].'_'.$p['id'])
            ->when($personalized->isEmpty(), fn($collection) => $collection->shuffle())
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 'success',
            'data'   => $items,
            'based_on' => $personalized->isEmpty() ? 'default' : 'product_views',
        ]);
    }

    private function personalizedRecommendations(Request $request, $user, int $limit)
    {
        $keys = app(ProductPersonalizationService::class)->recommendationKeys($request, $limit, 'all');

        if ($keys->isEmpty()) {
            return collect();
        }

        $bookIds = $keys->where('type', 'book')->pluck('id')->all();
        $stationeryIds = $keys->where('type', 'stationery')->pluck('id')->all();

        $books = $bookIds === []
            ? collect()
            : $this->bookScope()
                ->with(['category', 'tags', 'seller'])
                ->whereIn('id', $bookIds)
                ->get()
                ->keyBy('id');

        $stationeries = $stationeryIds === []
            ? collect()
            : $this->stationeryScope()
                ->with(['category', 'tags', 'variants', 'seller'])
                ->whereIn('id', $stationeryIds)
                ->get()
                ->keyBy('id');

        return $keys
            ->map(function (array $key) use ($books, $stationeries, $user) {
                if ($key['type'] === 'book') {
                    $book = $books->get($key['id']);
                    return $book ? $this->formatProduct($book, $user, 'book') : null;
                }

                $stationery = $stationeries->get($key['id']);
                return $stationery ? $this->formatProduct($stationery, $user, 'stationery') : null;
            })
            ->filter()
            ->values();
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

    public function similarProducts(Request $request, string $type, int $id)
    {
        $type = strtolower($type);
        if (! in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid product type',
            ], 422);
        }

        $user = $request->user('user') ?? auth('sanctum')->user() ?? Auth::guard('user')->user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(3, min(40, (int) $request->input('per_page', 20)));

        $base = $type === 'book'
            ? $this->bookScope()->with(['category', 'tags', 'seller'])->find($id)
            : $this->stationeryScope()->with(['category', 'tags', 'variants', 'seller'])->find($id);

        if (! $base) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }

        $scored = $this->rankSimilarProducts($base, $type, $page, $perPage);
        $total = $scored->count();
        $items = $scored
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map(fn ($product) => $this->formatProduct($product, $user, $type));

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    private function rankSimilarProducts($base, string $type, int $page, int $perPage)
    {
        $baseTagIds = $this->tagIds($base);
        $tokens = $this->similarityTokens(implode(' ', array_filter([
            $base->name ?? '',
            $base->author ?? '',
            $base->material ?? '',
            $base->description ?? '',
        ])));

        $candidateLimit = min(700, max(160, ($page * $perPage) + 160));
        $query = $type === 'book'
            ? $this->bookScope()->with(['category', 'tags', 'seller'])
            : $this->stationeryScope()->with(['category', 'tags', 'variants', 'seller']);

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
                    $q->orWhere('name', 'like', '%' . $token . '%');
                }
            });
        }

        $candidates = $query
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit($candidateLimit)
            ->get();

        if ($candidates->count() < ($page * $perPage)) {
            $existingIds = $candidates->pluck('id')->push($base->id)->all();
            $fallback = ($type === 'book'
                ? $this->bookScope()->with(['category', 'tags', 'seller'])
                : $this->stationeryScope()->with(['category', 'tags', 'variants', 'seller']))
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales')
                ->limit($candidateLimit - $candidates->count())
                ->get();
            $candidates = $candidates->merge($fallback);
        }

        return $candidates
            ->map(function ($product) use ($base, $type, $baseTagIds, $tokens) {
                $product->similarity_score = $this->similarityScore($base, $product, $type, $baseTagIds, $tokens);
                return $product;
            })
            ->sortByDesc(fn ($product) => $product->similarity_score)
            ->values();
    }

    private function similarityScore($base, $product, string $type, array $baseTagIds, array $baseTokens): float
    {
        $score = 0.0;

        $vectorScore = $this->vectorSimilarity($base->vectorData ?? null, $product->vectorData ?? null);
        if ($vectorScore !== null) {
            $score += $vectorScore * 80;
        }

        if (! empty($base->category_id) && (string) $base->category_id === (string) ($product->category_id ?? '')) {
            $score += 45;
        }

        $tagOverlap = count(array_intersect($baseTagIds, $this->tagIds($product)));
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

    private function tagIds($product): array
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

    private function vectorSimilarity($left, $right): ?float
    {
        $a = $this->normalizeVector($left);
        $b = $this->normalizeVector($right);

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

    private function normalizeVector($value): array
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
        $requestedType = (string) $request->query('type', 'new');
        $type = in_array($requestedType, ['new', 'recommended'], true)
            ? $requestedType
            : 'new';
        $page = max(1, (int) $request->query('page', 1));
        $categoryLimit = max(3, min(12, (int) $request->query('category_limit', 6)));
        $perCategory = max(4, min(12, (int) $request->query('per_category', 10)));

        $cacheKey = "api_books_by_cat_{$type}_{$page}_{$categoryLimit}_{$perCategory}";
        if (!$user) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }
        }

        try {
            $allCategoryIds = $this->bookScope()
                ->whereNotNull('category_id')
                ->distinct()
                ->orderBy('category_id')
                ->pluck('category_id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->values()
                ->all();

            $totalCategories = count($allCategoryIds);
            $categoryIds = array_slice($allCategoryIds, ($page - 1) * $categoryLimit, $categoryLimit);

            if (empty($categoryIds)) {
                return response()->json([
                    'status' => 'success',
                    'type' => $type,
                    'data' => [],
                    'meta' => [
                        'page' => $page,
                        'category_limit' => $categoryLimit,
                        'per_category' => $perCategory,
                        'total_categories' => $totalCategories,
                        'has_more' => false,
                    ],
                ]);
            }

            // MUHIM — nega bitta (yoki bir nechta FIQAT) so'rov: ilgari har
            // bir kategoriya uchun alohida foreach ichida 1-3 ta so'rov
            // yuborilardi (N kategoriya => 2N-3N alohida DB round-trip, har
            // biri seller/stock uchun correlated subquery bilan). Kategoriya
            // soni ko'paygani sayin "hammasini ko'rish" sahifasi sekinlashib,
            // oxir-oqibat timeout bera boshladi. Endi har bosqich uchun
            // BARCHA kategoriyalar bitta `whereIn` so'rovda olinadi va
            // natija PHP tomonida category_id bo'yicha guruhlanadi — DB
            // so'rovlari soni kategoriya soniga bog'liq bo'lmay qoladi.
            // Frontend endi kategoriya-kategoriya pagination qiladi. Shu
            // sabab bu endpoint "barcha kategoriyalarni bir urinishda" emas,
            // faqat ko'rinadigan navbatdagi bo'lakni formatlaydi. Ilgari
            // 20+ kategoriya * 10 mahsulot + seller/tag/favourite formatlash
            // birinchi ochilishda sezilarli sekinlik berardi.
            $safetyCap = max(80, count($categoryIds) * $perCategory * 4);

            $grouped = [];

            if ($type === 'recommended') {
                // 1. Recommended bo'lganlarni olishga urinib ko'ramiz
                //    (bu flag admin tomonidan qo'lda belgilanadi — odatda
                //    kam sonli, shuning uchun limitsiz olinadi)
                $recommended = $this->isRecommended(
                    $this->bookScope()
                        ->whereIn('category_id', $categoryIds)
                        ->with(['seller', 'category', 'tags'])
                )
                    ->orderBy('category_id')
                    ->orderByDesc('totalSalesWeek')
                    ->orderByDesc('totalSales')
                    ->get()
                    ->groupBy('category_id');

                foreach ($recommended as $catId => $books) {
                    $grouped[$catId] = $books->take($perCategory)->values();
                }

                // 2. Kam bo'lgan (< 3) kategoriyalarni haftalik sotuvga
                //    qarab bitta qo'shimcha so'rovda to'ldiramiz
                $needFill = [];
                foreach ($categoryIds as $catId) {
                    $have = $grouped[$catId] ?? collect();
                    if ($have->count() < 3) {
                        $needFill[$catId] = $have;
                    }
                }

                if (!empty($needFill)) {
                    $existIds = collect($needFill)->flatMap(fn($c) => $c->pluck('id'))->all();

                    $fill = $this->bookScope()
                        ->whereIn('category_id', array_keys($needFill))
                        ->whereNotIn('id', $existIds ?: [0])
                        ->with(['seller', 'category', 'tags'])
                        ->orderBy('category_id')
                        ->orderByDesc('totalSalesWeek')
                        ->orderByDesc('totalSales')
                        ->limit($safetyCap)
                        ->get()
                        ->groupBy('category_id');

                    foreach ($needFill as $catId => $have) {
                        $extra = ($fill[$catId] ?? collect())->take($perCategory - $have->count());
                        $grouped[$catId] = $have->concat($extra)->values();
                    }
                }

                // 3. Hali ham bo'sh qolgan kategoriyalar — yangi kitoblar
                $stillEmpty = array_values(array_filter(
                    $categoryIds,
                    fn($catId) => empty($grouped[$catId] ?? null)
                ));

                if (!empty($stillEmpty)) {
                    $newFill = $this->bookScope()
                        ->whereIn('category_id', $stillEmpty)
                        ->with(['seller', 'category', 'tags'])
                        ->orderBy('category_id')
                        ->orderByDesc('created_at')
                        ->limit($safetyCap)
                        ->get()
                        ->groupBy('category_id');

                    foreach ($stillEmpty as $catId) {
                        $grouped[$catId] = ($newFill[$catId] ?? collect())->take($perCategory)->values();
                    }
                }
            } else {
                // type=new — yangi kitoblar, barcha kategoriyalar uchun
                // bitta so'rovda
                $newBooks = $this->bookScope()
                    ->whereIn('category_id', $categoryIds)
                    ->with(['seller', 'category', 'tags'])
                    ->orderBy('category_id')
                    ->orderByDesc('created_at')
                    ->limit($safetyCap)
                    ->get()
                    ->groupBy('category_id');

                foreach ($newBooks as $catId => $books) {
                    $grouped[$catId] = $books->take($perCategory)->values();
                }
            }

            $result = [];
            foreach ($grouped as $catId => $books) {
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

            $payload = [
                'status' => 'success',
                'type'   => $type,
                'data'   => $result,
                'meta' => [
                    'page' => $page,
                    'category_limit' => $categoryLimit,
                    'per_category' => $perCategory,
                    'total_categories' => $totalCategories,
                    'has_more' => ($page * $categoryLimit) < $totalCategories,
                ],
            ];

            if (!$user) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $payload, 60);
            }

            return response()->json($payload);
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
        $perPage = 15;

        $seller = Seller::where('id', $id)
            ->where('is_hidden', 0)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->where('status', 'approved')
            ->firstOrFail();

        $baseBookQ = fn() => Books::where('seller_id', $id)
            ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
            ->with(['category', 'tags', 'seller']);

        $baseStatQ = fn() => Stationery::where('seller_id', $id)
            ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
            ->with(['category', 'tags', 'variants', 'seller']);

        // Chegirmali kitoblar (muddati o'tmagan)
        $discountedBooks = $this->hasActiveDiscount($baseBookQ(), true)
            ->orderByRaw('(price - discountPrice) DESC')
            ->limit($perPage)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Trend kitoblar
        $trendingBooks = $baseBookQ()
            ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales')
            ->limit($perPage)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Recommended kitoblar (muddati o'tmagan)
        $recommendedBooks = $this->isRecommended($baseBookQ())
            ->orderByDesc('totalSalesWeek')
            ->limit($perPage)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Kategoriya bo'yicha kitoblar
        $bookCategoryIds = Books::where('seller_id', $id)
            ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
            ->whereNotNull('category_id')->distinct()->pluck('category_id');

        $booksByCategory = [];
        foreach ($bookCategoryIds as $catId) {
            $catQuery = $baseBookQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales');
            $catTotal = (clone $catQuery)->count();
            $catBooks = $catQuery->limit($perPage)->get();
            if ($catBooks->isEmpty()) continue;
            $category = $catBooks->first()->category;
            if (!$category) continue;
            $booksByCategory[] = $this->sellerCategoryPayload(
                $category,
                $catBooks->map(fn($b) => $this->formatProduct($b, $user, 'book'))->values()->toArray(),
                1,
                $perPage,
                $catTotal,
            );
        }

        // Chegirmali stationery (muddati o'tmagan)
        $discountedStats = $this->hasActiveDiscount($baseStatQ(), false)
            ->orderByRaw('(price - discount_price) DESC')
            ->limit($perPage)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Trend stationery
        $trendingStats = $baseStatQ()
            ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales')
            ->limit($perPage)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Recommended stationery
        $recommendedStats = $this->isRecommended($baseStatQ())
            ->orderByDesc('totalSalesWeek')
            ->limit($perPage)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Kategoriya bo'yicha stationery
        $statCategoryIds = Stationery::where('seller_id', $id)
            ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
            ->whereNotNull('category_id')->distinct()->pluck('category_id');

        $stationeriesByCategory = [];
        foreach ($statCategoryIds as $catId) {
            $catQuery = $baseStatQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->orderByDesc('totalSales');
            $catTotal = (clone $catQuery)->count();
            $catStats = $catQuery->limit($perPage)->get();
            if ($catStats->isEmpty()) continue;
            $category = $catStats->first()->category;
            if (!$category) continue;
            $stationeriesByCategory[] = $this->sellerCategoryPayload(
                $category,
                $catStats->map(fn($s) => $this->formatProduct($s, $user, 'stationery'))->values()->toArray(),
                1,
                $perPage,
                $catTotal,
            );
        }

        // MUHIM: `summary` (kitoblar/kanselyariya soni) ATAYLAB olib
        // tashlandi — bu ham xaridorga kerak bo'lmagan "statistika" turi
        // (foydalanuvchi so'rovi: profilida faqat reyting ko'rinsa bas).

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

    public function sellerCategoryProducts(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        $type = strtolower((string) $request->query('type', 'book'));
        $categoryId = (int) $request->query('category_id');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(15, (int) $request->query('per_page', 15)));

        if (!in_array($type, ['book', 'stationery'], true) || $categoryId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Noto\'g\'ri parametrlar.',
            ], 422);
        }

        $seller = Seller::where('id', $id)
            ->where('is_hidden', 0)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->where('status', 'approved')
            ->firstOrFail();

        if ($type === 'book') {
            $query = Books::where('seller_id', $seller->id)
                ->where('category_id', $categoryId)
                ->where('status', true)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->with(['category', 'tags', 'seller'])
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales');
        } else {
            $query = Stationery::where('seller_id', $seller->id)
                ->where('category_id', $categoryId)
                ->where('status', true)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->with(['category', 'tags', 'variants', 'seller'])
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales');
        }

        $total = (clone $query)->count();
        $items = $query->forPage($page, $perPage)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => [],
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'has_more' => false,
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'products' => $items
                    ->map(fn($item) => $this->formatProduct($item, $user, $type))
                    ->values()
                    ->toArray(),
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($page * $perPage) < $total,
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
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->where('status', 'approved')
            ->first(['id']);

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Do\'kon topilmadi.',
            ], 404);
        }

        $booksInSeller = Books::query()
            ->where(function ($query) use ($normalizedCode) {
                $query->whereIsbn($normalizedCode)
                    ->orWhere('artikul', $normalizedCode);
            })
            ->where('seller_id', $sellerId)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with(['category', 'tags', 'seller'])
            ->orderByDesc('updated_at')
            ->get();

        $bookMatches = $booksInSeller->where('count', '>', 0)->values();

        $stationeryInSeller = Stationery::query()
            ->where(function ($query) use ($normalizedCode) {
                $query->where('barcode', $normalizedCode)
                    ->orWhere('artikul', $normalizedCode);
            })
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
                'data'   => $this->formatProductDetail($match['model'], Auth::guard('user')->user(), $match['type']),
            ]);
        }

        if ($matches->count() > 1) {
            $user = Auth::guard('user')->user();

            return response()->json([
                'status'           => 'success',
                'multiple_matches' => true,
                'message'          => "Bu shtrix-kod bo'yicha bir nechta mahsulot topildi. Kerakli variantni tanlang.",
                'candidates'       => $matches
                    ->map(fn($match) => $this->formatProductDetail($match['model'], $user, $match['type']))
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
            ->where(function ($query) use ($normalizedCode) {
                $query->whereIsbn($normalizedCode)
                    ->orWhere('artikul', $normalizedCode);
            })
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->exists()
            || Stationery::query()
                ->where(function ($query) use ($normalizedCode) {
                    $query->where('barcode', $normalizedCode)
                        ->orWhere('artikul', $normalizedCode);
                })
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
                    ->where(function ($q) {
                        $q->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                    })
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

    public function subscribeStockAlert(
        Request $request,
        string $type,
        int $id,
        ProductStockAlertService $stockAlertService
    ) {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Avval tizimga kiring.',
            ], 401);
        }

        if (!in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahsulot turi noto‘g‘ri.',
            ], 422);
        }

        $validated = $request->validate([
            'variant_id' => 'nullable|integer|exists:stationery_variants,id',
        ]);

        if ($type === 'book') {
            $product = $this->publicBookDetailScope()->find($id);
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mahsulot topilmadi.',
                ], 404);
            }

            if ((int) ($product->count ?? 0) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bu mahsulot hozir mavjud.',
                ], 422);
            }

            $result = $stockAlertService->subscribe($user, 'book', $product->id);

            return response()->json([
                'status' => 'success',
                'created' => (bool) $result['created'],
            ]);
        }

        $product = $this->publicStationeryDetailScope()
            ->with('variants')
            ->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahsulot topilmadi.',
            ], 404);
        }

        $variantId = isset($validated['variant_id']) ? (int) $validated['variant_id'] : null;
        if ($variantId) {
            $variant = StationeryVariant::query()
                ->where('product_id', $product->id)
                ->find($variantId);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant topilmadi.',
                ], 422);
            }

            if ((int) ($variant->stock ?? 0) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bu variant hozir mavjud.',
                ], 422);
            }

            $result = $stockAlertService->subscribe($user, 'stationery', $product->id, $variant->id);

            return response()->json([
                'status' => 'success',
                'created' => (bool) $result['created'],
            ]);
        }

        $hasAnyVariantInStock = $product->variants->contains(
            fn($variant) => (int) ($variant->stock ?? 0) > 0
        );

        if ((int) ($product->stock ?? 0) > 0 || $hasAnyVariantInStock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu mahsulot hozir mavjud.',
            ], 422);
        }

        $result = $stockAlertService->subscribe($user, 'stationery', $product->id);

        return response()->json([
            'status' => 'success',
            'created' => (bool) $result['created'],
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
            ? $this->publicBookDetailScope()->find($id)
            : $this->publicStationeryDetailScope()->find($id);

        if (!$product || !(int) ($product->seller_id ?? 0)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahsulot topilmadi.',
            ], 404);
        }

        $recommendationActive = $this->isRecommendationActiveForProduct($product);

        $user = $request->user('user') ?? auth('sanctum')->user() ?? Auth::guard('user')->user();
        $deviceId = trim((string) $request->header('X-Device-Id', ''));
        $sessionId = trim((string) $request->header('X-Session-Id', ''));

        $recentViewQuery = ProductViewLog::query()
            ->where('product_id', (int) $product->id)
            ->where('product_type', $type)
            ->where('created_at', '>=', now()->subMinutes(30));

        if ($user) {
            $recentViewQuery->where('user_id', $user->id);
        } elseif ($sessionId !== '') {
            $recentViewQuery->where('session_id', $sessionId);
        } elseif ($deviceId !== '' && $deviceId !== 'unknown_device') {
            $recentViewQuery->where('device_id', $deviceId);
        } else {
            $recentViewQuery->whereRaw('1 = 0');
        }

        $recentView = $recentViewQuery->latest()->first();

        if ($recentView) {
            $recentView->update([
                'recommendation_active' => $recommendationActive,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'updated_at' => now(),
            ]);
        } else {
            ProductViewLog::query()->create([
            'seller_id' => (int) $product->seller_id,
            'product_id' => (int) $product->id,
            'product_type' => $type,
            'user_id' => optional($user)->id,
            'recommendation_active' => $recommendationActive,
            'device_id' => $deviceId !== '' ? $deviceId : null,
            'session_id' => $sessionId !== '' ? $sessionId : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            $product->increment('views');
            $product->views = (int) ($product->views ?? 0) + 1;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'views' => (int) ($product->views ?? 0),
                'recommendation_active' => $recommendationActive,
                'deduped' => (bool) $recentView,
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
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->where('status', 'approved')
            ->where(fn($q) => $q
                ->whereHas('books', fn($b) => $b
                    ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1))
                ->orWhereHas('stationeries', fn($s) => $s
                    ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1))
            )
            ->with([
                'books' => fn($q) => $q
                    ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
                    ->latest('created_at')->take(20)
                    ->with(['category', 'tags', 'seller']),
                'stationeries' => fn($q) => $q
                    ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
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
