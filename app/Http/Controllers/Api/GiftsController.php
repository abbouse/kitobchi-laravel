<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gifts;
use App\Models\MyCart;
use App\Models\Books;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GiftsController extends Controller
{
    // =========================================================================
    //  YORDAMCHI — seller summalarini hisoblash
    // =========================================================================
    private function calcSellerSums(int $userId): array
    {
        $cartItems = MyCart::where('user_id', $userId)
            ->with(['product.seller'])
            ->get();

        $sellerSums = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product) continue;

            $price = $product instanceof Books
                ? ((is_numeric($product->discountPrice) && $product->discountPrice > 0)
                    ? $product->discountPrice : $product->price)
                : ((is_numeric($product->discount_price) && $product->discount_price > 0)
                    ? $product->discount_price : $product->price);

            $sid = $product->seller_id;
            $sellerSums[$sid] = ($sellerSums[$sid] ?? 0) + ($price * ($cartItem->count_item ?? 1));
        }

        return $sellerSums;
    }

    // =========================================================================
    //  1. BARCHA SELLERLAR + birinchi sahifa
    //
    //  GET /api/gifts
    //
    //  Birinchi yuklashda barcha sellerlarni va ularning
    //  birinchi N ta gifini qaytaradi.
    //
    //  Response:
    //  {
    //    "status": "success",
    //    "data": [
    //      {
    //        "seller_id": 1,
    //        "seller_shop_name": "Kitobchi",
    //        "seller_photo": null,
    //        "has_more": true,       ← scroll kerakmi
    //        "total": 12,
    //        "gifts": [ {...}, {...}, ... ]  ← birinchi 6 ta
    //      },
    //      {
    //        "seller_id": 55,
    //        "seller_shop_name": "Book.uz",
    //        "has_more": false,
    //        "total": 2,
    //        "gifts": [ {...}, {...} ]
    //      }
    //    ]
    //  }
    // =========================================================================
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $sellerSums = $this->calcSellerSums($user->id);
        if (empty($sellerSums)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $totalCartSum    = array_sum($sellerSums);
        $uniqueSellerIds = array_keys($sellerSums);

        // Har bir seller uchun alohida query
        // Birinchi sahifada per_seller_limit ta ko'rsatamiz
        $perSellerLimit = (int) $request->input('per_seller', 6);

        $result = [];

        // ── Platforma sovg'alari (seller_id = 1) ─────────────────
        $platformQuery = Gifts::where('status', true)
            ->where('is_approved', true)
            ->where('stock', '>', 0)
            ->where('seller_id', 1)
            ->where('priceFrom', '<=', $totalCartSum)
            ->where('priceTo',   '>=', $totalCartSum)
            ->with('seller:id,shop_name,photo')
            ->orderBy('id', 'DESC');

        $platformTotal = $platformQuery->count();
        $platformGifts = $platformQuery->limit($perSellerLimit)->get();

        if ($platformTotal > 0) {
            $result[] = [
                'seller_id'        => 1,
                'seller_shop_name' => 'Kitobchi',
                'seller_photo'     => null,
                'total'            => $platformTotal,
                'has_more'         => $platformTotal > $perSellerLimit,
                'gifts'            => $this->formatGifts($platformGifts),
            ];
        }

        // ── Har bir seller sovg'alari ─────────────────────────────
        foreach ($uniqueSellerIds as $sellerId) {
            if ($sellerId == 1) continue;

            $sum = $sellerSums[$sellerId];

            $sellerQuery = Gifts::where('status', true)
                ->where('is_approved', true)
                ->where('stock', '>', 0)
                ->where('seller_id', $sellerId)
                ->where('priceFrom', '<=', $sum)
                ->where('priceTo',   '>=', $sum)
                ->with('seller:id,shop_name,photo')
                ->orderBy('id', 'DESC');

            $sellerTotal = $sellerQuery->count();
            $sellerGifts = $sellerQuery->limit($perSellerLimit)->get();

            if ($sellerTotal > 0) {
                $first = $sellerGifts->first();
                $result[] = [
                    'seller_id'        => $sellerId,
                    'seller_shop_name' => $first?->seller?->shop_name ?? "Do'kon",
                    'seller_photo'     => $first?->seller?->photo ?? null,
                    'total'            => $sellerTotal,
                    'has_more'         => $sellerTotal > $perSellerLimit,
                    'gifts'            => $this->formatGifts($sellerGifts),
                ];
            }
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    // =========================================================================
    //  2. BITTA SELLER GIFLARINI PAGINATE QILISH
    //
    //  GET /api/gifts/seller/{sellerId}?page=2&per_page=6
    //
    //  User o'sha sellerning qatorini scroll qilganda chaqiriladi.
    //  Faqat o'sha seller giflarini paginate qilib qaytaradi.
    //
    //  Response:
    //  {
    //    "status": "success",
    //    "seller_id": 55,
    //    "data": [ {...}, {...} ],
    //    "meta": {
    //      "current_page": 2,
    //      "last_page": 3,
    //      "has_more": true
    //    }
    //  }
    // =========================================================================
    public function bySeller(Request $request, int $sellerId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $sellerSums   = $this->calcSellerSums($user->id);
        $totalCartSum = array_sum($sellerSums);

        if (empty($sellerSums)) {
            return response()->json(['status' => 'success', 'data' => [], 'meta' => ['has_more' => false]]);
        }

        $perPage = min((int) $request->input('per_page', 6), 50);
        $page    = max((int) $request->input('page', 1), 1);

        // Seller uchun filter
        if ($sellerId == 1) {
            // Platforma — umumiy summa bo'yicha
            $query = Gifts::where('status', true)
                ->where('is_approved', true)
                ->where('stock', '>', 0)
                ->where('seller_id', 1)
                ->where('priceFrom', '<=', $totalCartSum)
                ->where('priceTo',   '>=', $totalCartSum);
        } else {
            // Seller — o'sha sellerning summasi bo'yicha
            $sum = $sellerSums[$sellerId] ?? 0;
            if ($sum == 0) {
                return response()->json(['status' => 'success', 'data' => [], 'meta' => ['has_more' => false]]);
            }
            $query = Gifts::where('status', true)
                ->where('is_approved', true)
                ->where('stock', '>', 0)
                ->where('seller_id', $sellerId)
                ->where('priceFrom', '<=', $sum)
                ->where('priceTo',   '>=', $sum);
        }

        $paginated = $query
            ->with('seller:id,shop_name,photo')
            ->orderBy('id', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status'    => 'success',
            'seller_id' => $sellerId,
            'data'      => $this->formatGifts(collect($paginated->items())),
            'meta'      => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
            ],
        ]);
    }

    // =========================================================================
    //  3. GIFT TANLASH
    //  POST /api/gifts/select
    // =========================================================================
    public function select_gift(Request $request)
    {
        $request->validate(['gift_id' => 'required|integer|exists:gifts,id']);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $gift = Gifts::where('id', $request->gift_id)
            ->where('is_approved', true)
            ->where('stock', '>', 0)
            ->firstOrFail();

        $cartItems     = MyCart::where('user_id', $user->id)->with('product')->get();
        $cartSellerIds = $cartItems->pluck('product.seller_id')
            ->unique()->filter()->toArray();

        if ($gift->seller_id != 1 && !in_array($gift->seller_id, $cartSellerIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Bu sovg'a sizning savatingiz uchun mavjud emas!",
            ], 403);
        }

        return response()->json(['status' => 'success', 'message' => "Sovg'a tanlandi"]);
    }

    // ── Format helper ─────────────────────────────────────────────
    private function formatGifts($gifts): array
    {
        return $gifts->map(fn($g) => [
            'id'               => $g->id,
            'name'             => $g->name,
            'images'           => $g->images ?? [],
            'priceFrom'        => $g->priceFrom,
            'priceTo'          => $g->priceTo,
            'stock'            => $g->stock,
            'seller_id'        => $g->seller_id,
            'seller_shop_name' => $g->seller_id == 1
                ? 'Kitobchi'
                : ($g->seller?->shop_name ?? "Do'kon"),
            'seller_photo'     => $g->seller?->photo ?? null,
        ])->values()->toArray();
    }
}