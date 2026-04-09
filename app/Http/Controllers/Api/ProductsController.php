<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\Seller;
use App\Models\FavouriteProducts;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductsController extends Controller
{
    /**
     * Umumiy mahsulotni formatlash (kitob yoki stationery)
     */
    private function formatProduct($product, $user = null, $type = 'book')
    {
        $isBook = $type === 'book';

        return [
            'id'           => $product->id,
            'type'         => $isBook ? 'book' : 'stationery',
            'name'         => $product->name,
            'author'       => $isBook ? ($product->author ?? null) : null,
            'material'     => $isBook ? null : ($product->material ?? null),
            'category_id'  => $product->category_id,
            'images'       => $product->images ?? [],
            'description'  => $product->description ?? null,
            'price'        => $product->price,
            'discountPrice'=> $isBook
                ? ($product->discountPrice ?? $product->price)
                : ($product->discount_price ?? $product->price),
            'count'        => $isBook ? $product->count : $product->stock,
            'sales'        => $product->totalSales ?? 0,
            'weekly_sales' => $product->totalSalesWeek ?? 0,
            'lang'         => $isBook ? ($product->lang ?? "O'zbek") : null,
            'langType'     => $isBook ? ($product->langType ?? '') : null,
            'coverType'    => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year'         => $isBook ? ($product->year ?? now()->year) : null,
            'favourite'    => $user
                ? FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists()
                : false,
            'category'     => $product->category?->title ?? null,
            'tags'         => $product->tags->map(function ($tag) {
                return [
                    'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
                    'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
                    'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
                ];
            })->filter()->values(),
            'seller'       => [
                'seller_id'  => $product->seller?->id,
                'shop_name'  => $product->seller?->shop_name,
                'photo'      => $product->seller?->photo,
                'isVerified' => $product->seller?->isVerified,
            ],
            'variants'     => !$isBook && $product->relationLoaded('variants')
                ? $product->variants->map(fn($v) => [
                    'id'         => $v->id,
                    'color_name' => $v->color_name,
                    'image'      => $v->image_path ?? null,
                    'stock'      => $v->stock,
                  ])
                : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // EXISTING ENDPOINTS (o'zgarishsiz)
    // ─────────────────────────────────────────────────────────────

    /**
     * Main page — faqat yangi kitoblar
     * GET /products/{col}
     */
    public function index(Request $request, string $col)
    {
        $user  = Auth::guard('user')->user();
        $books = Books::where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0))
            ->with(['seller', 'category', 'tags'])
            ->orderBy('created_at', 'DESC')
            ->limit((int)$col)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $books->map(fn($b) => $this->formatProduct($b, $user, 'book')),
        ]);
    }

    /**
     * Tavsiyalar
     * GET /products/recommendation/{col}
     */
    public function recommendation(Request $request, string $col)
    {
        $user    = Auth::guard('user')->user();
        $limit   = (int)$col;
        $perType = max(3, (int)floor($limit / 2));

        $books = Books::where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0))
            ->with(['category', 'tags', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('created_at')
            ->limit($perType)
            ->get();

        $stationeries = Stationery::where('stock', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0))
            ->with(['category', 'tags', 'variants', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('created_at')
            ->limit($limit - $books->count())
            ->get();

        $combined = collect()
            ->merge($books->map(fn($b) => $this->formatProduct($b, $user, 'book')))
            ->merge($stationeries->map(fn($s) => $this->formatProduct($s, $user, 'stationery')))
            ->shuffle()
            ->take($limit);

        return response()->json(['status' => 'success', 'data' => $combined]);
    }

    // ─────────────────────────────────────────────────────────────
    // NEW: BOOKS BY CATEGORY — horizontal list uchun
    // GET /products/books-by-category?type=new   (yangi kitoblar)
    // GET /products/books-by-category?type=recommended  (tavsiyalar)
    //
    // Response:
    // [
    //   {
    //     "category_id": 1,
    //     "category_name": "Badiiy adabiyot",
    //     "books": [ ...10 ta kitob... ]
    //   },
    //   ...
    // ]
    // ─────────────────────────────────────────────────────────────
    public function booksByCategory(Request $request)
    {
        $user = Auth::guard('user')->user();
        // type: 'new' | 'recommended'
        $type = $request->query('type', 'new');

        try {
            // Faol kategoriyalarni olamiz (kamida 1 ta kitob bo'lgan)
            $categoryIds = Books::where('count', '>', 0)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->whereHas('seller', fn($q) => $q
                    ->where('is_hidden', 0)
                    ->where('status', 'approved')
                    ->where('parent_id', 0))
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $result = [];

            foreach ($categoryIds as $catId) {
                // Har kategoriya uchun asosiy query
                $query = Books::where('count', '>', 0)
                    ->where('is_hidden', 0)
                    ->where('is_approved', 1)
                    ->where('category_id', $catId)
                    ->whereHas('seller', fn($q) => $q
                        ->where('is_hidden', 0)
                        ->where('status', 'approved')
                        ->where('parent_id', 0))
                    ->with(['seller', 'category', 'tags']);

                // Sort: type ga qarab
                if ($type === 'new') {
                    $query->orderByDesc('created_at');
                } else {
                    // recommended → haftalik sotuv bo'yicha
                    $query->orderByDesc('totalSalesWeek')
                          ->orderByDesc('totalSales');
                }

                $books = $query->limit(10)->get();

                if ($books->isEmpty()) continue;

                // Category nomini birinchi kitobdan olamiz
                $category = $books->first()->category;
                if (!$category) continue;

                $result[] = [
                    'category_id'   => $catId,
                    'name_uz' => $category->name_uz ?? '',
                'name_en' => $category->name_en ?? '',
                'name_ja' => $category->name_ja ?? '',
                'name_ru' => $category->name_ru ?? '',
                    'books' => $books
                        ->map(fn($b) => $this->formatProduct($b, $user, 'book'))
                        ->values()
                        ->toArray(),
                ];
            }

            // Har kategoriyada kitob soni bo'yicha tartiblash
            // (ko'proq kitob bo'lgan kategoriya yuqorida tursin)
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

    // ─────────────────────────────────────────────────────────────
    // SELLER endpoints (o'zgarishsiz)
    // ─────────────────────────────────────────────────────────────

    public function seller(Request $request, $id)
    {
        $user = Auth::guard('user')->user();

        $seller = Seller::where('id', $id)
            ->where('is_hidden', 0)
            ->where('parent_id', 0)
            ->where('status', 'approved')
            ->firstOrFail();

        $baseBookQ = fn() => Books::where('seller_id', $id)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->where('count', '>', 0)
            ->with(['category', 'tags', 'seller']);

        $baseStatQ = fn() => Stationery::where('seller_id', $id)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->where('stock', '>', 0)
            ->with(['category', 'tags', 'variants', 'seller']);

        // Chegirmali kitoblar
        $discountedBooks = $baseBookQ()
            ->whereNotNull('discountPrice')
            ->where('discountPrice', '>', 0)
            ->whereRaw('discountPrice < price')
            ->orderByRaw('(price - discountPrice) DESC')
            ->limit(15)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Trend kitoblar
        $trendingBooks = $baseBookQ()
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit(15)->get()
            ->map(fn($b) => $this->formatProduct($b, $user, 'book'));

        // Kategoriya bo'yicha kitoblar
        $bookCategoryIds = Books::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)
            ->where('count', '>', 0)->whereNotNull('category_id')
            ->distinct()->pluck('category_id');

        $booksByCategory = [];
        foreach ($bookCategoryIds as $catId) {
            $catBooks = $baseBookQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->limit(15)->get();
            if ($catBooks->isEmpty()) continue;
            $category = $catBooks->first()->category;
            if (!$category) continue;
            $booksByCategory[] = [
                'category_id'   => $catId,
                'name_uz' => $category->name_uz ?? '',
                'name_en' => $category->name_en ?? '',
                'name_ja' => $category->name_ja ?? '',
                'name_ru' => $category->name_ru ?? '',
                'products'      => $catBooks->map(fn($b) => $this->formatProduct($b, $user, 'book'))->values(),
            ];
        }

        // Chegirmali stationery
        $discountedStats = $baseStatQ()
            ->whereNotNull('discount_price')
            ->where('discount_price', '>', 0)
            ->whereRaw('discount_price < price')
            ->orderByRaw('(price - discount_price) DESC')
            ->limit(15)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Trend stationery
        $trendingStats = $baseStatQ()
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit(15)->get()
            ->map(fn($s) => $this->formatProduct($s, $user, 'stationery'));

        // Kategoriya bo'yicha stationery
        $statCategoryIds = Stationery::where('seller_id', $id)
            ->where('is_hidden', 0)->where('is_approved', 1)
            ->where('stock', '>', 0)->whereNotNull('category_id')
            ->distinct()->pluck('category_id');

        $stationeriesByCategory = [];
        foreach ($statCategoryIds as $catId) {
            $catStats = $baseStatQ()->where('category_id', $catId)
                ->orderByDesc('totalSalesWeek')->limit(15)->get();
            if ($catStats->isEmpty()) continue;
            $category = $catStats->first()->category;
            if (!$category) continue;
            $stationeriesByCategory[] = [
                'category_id'   => $catId,
                'name_uz' => $category->name_uz ?? '',
                'name_en' => $category->name_en ?? '',
                'name_ja' => $category->name_ja ?? '',
                'name_ru' => $category->name_ru ?? '',
                'products'      => $catStats->map(fn($s) => $this->formatProduct($s, $user, 'stationery'))->values(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'seller' => [
                    'id'             => $seller->id,
                    'shop_name'      => $seller->shop_name,
                    'photo'          => $seller->photo,
                    'firstname'      => $seller->firstname,
                    'lastname'       => $seller->lastname,
                    'region'         => $seller->region,
                    'activity_types' => $seller->activity_types,
                    'isVerified'     => $seller->isVerified,
                ],
                'books'       => [
                    'discounted'  => $discountedBooks,
                    'trending'    => $trendingBooks,
                    'by_category' => $booksByCategory,
                ],
                'stationeries' => [
                    'discounted'  => $discountedStats,
                    'trending'    => $trendingStats,
                    'by_category' => $stationeriesByCategory,
                ],
            ],
        ]);
    }

    public function sellersWithLatestProducts(Request $request)
    {
        $user = Auth::guard('user')->user();

        $sellers = Seller::where('is_hidden', 0)
            ->where('parent_id', 0)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereHas('books', fn($q) => $q
                        ->where('count', '>', 0)
                        ->where('is_hidden', 0)
                        ->where('is_approved', 1))
                      ->orWhereHas('stationeries', fn($q) => $q
                        ->where('stock', '>', 0)
                        ->where('is_hidden', 0)
                        ->where('is_approved', 1));
            })
            ->with([
                'books' => fn($q) => $q->where('count', '>', 0)
                    ->where('is_hidden', 0)->where('is_approved', 1)
                    ->latest('created_at')->take(20)
                    ->with(['category', 'tags', 'seller']),
                'stationeries' => fn($q) => $q->where('stock', '>', 0)
                    ->where('is_hidden', 0)->where('is_approved', 1)
                    ->latest('created_at')->take(20)
                    ->with(['category', 'tags', 'variants', 'seller']),
            ])
            ->inRandomOrder()
            ->take(50)
            ->get();

        $result = $sellers->map(fn($seller) => [
            'id'             => $seller->id,
            'firstname'      => $seller->firstname,
            'lastname'       => $seller->lastname,
            'shop_name'      => $seller->shop_name,
            'region'         => $seller->region,
            'activity_types' => $seller->activity_types,
            'photo'          => $seller->photo,
            'status'         => $seller->status,
            'isVerified'     => $seller->isVerified,
            'books'          => $seller->books->map(fn($b) => $this->formatProduct($b, $user, 'book')),
            'stationeries'   => $seller->stationeries->map(fn($s) => $this->formatProduct($s, $user, 'stationery')),
        ]);

        return response()->json(['status' => 'success', 'data' => $result]);
    }
}