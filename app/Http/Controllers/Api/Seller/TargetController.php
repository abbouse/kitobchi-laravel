<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerAd;
use App\Models\SellerAdSetting;
use App\Models\Seller;
use App\Models\SellerStaffLog;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\MyCart;
use App\Models\ProductViewLog;
use App\Models\FavouriteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TargetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    private function hasContestAccess($seller)
    {
        return !$seller->parent_id || $seller->role <= 1;
    }

    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id'       => $storeSellerId,
            'text'            => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}"
                               . ($details ? " | {$details}" : ''),
        ]);
    }

    private function checkAndGetProductData($productId, $storeSellerId)
    {
        if (empty($productId)) return ['product' => null, 'product_type' => 'none'];

        $product = Books::where('id', $productId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$product) return null;

        return ['product' => $product, 'product_type' => $product->type ?: 'book'];
    }

    // =========================================================================
    //  1. INITIAL DATA — narxlar + oxirgi reklamalar
    //  GET /api/seller/target/initial
    // =========================================================================
    public function getInitialTargetData(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        if (!$this->hasContestAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $prices = SellerAdSetting::whereIn('type', ['top_banner', 'center_banner'])
            ->select('type', 'price')
            ->get();
        $priceData = [];
        foreach ($prices as $item) {
            $priceData[$item->type] = $item->price;
        }

        $latestTargets = SellerAd::where('seller_id', $storeSellerId)
            ->whereIn('type', ['top_banner', 'center_banner'])
            ->with('product:id,name')
            ->latest('created_at')
            ->take(15)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'balance'        => (int) ($seller->balance ?? 0),
                'prices'         => $priceData,
                'latest_targets' => $latestTargets,
            ],
        ], 200);
    }

    // =========================================================================
    //  2. MAHSULOTLAR RO'YXATI — reklama uchun tanlash
    //  GET /api/seller/target/products
    // =========================================================================
    public function getProductList()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        if (!$this->hasContestAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $books = Books::where('seller_id', $storeSellerId)
            ->where('is_hidden', false)
            ->latest('updated_at')
            ->get(['id', 'name', 'images']);

        return response()->json(['success' => true, 'data' => $books], 200);
    }

    // =========================================================================
    //  3. HOME PAGE BANNER — top_banner | center_banner
    //  POST /api/seller/target/banner
    // =========================================================================
    public function storeHomePageBannerAd(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        if (!$this->hasContestAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $request->validate([
            'type'        => ['required', 'string', Rule::in(['top_banner', 'center_banner'])],
            'description' => 'nullable|string|max:1000',
            'action'      => ['required', 'string', Rule::in(['to_book', 'to_product', 'to_shop'])],
            'product_id'  => 'nullable|integer',
            'expire_days' => 'required|integer|min:1|max:30',
            'images'      => 'sometimes|array|min:1|max:1',
            'images.*'    => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $settings = SellerAdSetting::where('type', $request->type)->first();
        if (!$settings) {
            return response()->json(['success' => false, 'message' => 'Reklama narxi topilmadi.'], 404);
        }

        $expireDays  = (int) $request->expire_days;
        $amount      = ($settings->price / 30) * $expireDays;
        $productId   = $request->product_id;
        $productType = 'none';

        $normalizedAction = $request->action === 'to_book'
            ? 'to_product'
            : $request->action;

        if ($normalizedAction === 'to_product') {
            if (empty($productId)) {
                return response()->json(['success' => false, 'message' => 'action=to_product uchun product_id shart.'], 422);
            }
            $productData = $this->checkAndGetProductData($productId, $storeSellerId);
            if (is_null($productData)) {
                return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
            }
            $productType = $productData['product_type'];
        }

        $imageFile  = $request->file('images');
        $bannerPath = is_array($imageFile)
            ? array_shift($imageFile)->store('target', 'public')
            : $imageFile->store('target', 'public');

        try {
            DB::beginTransaction();

            $ad = SellerAd::create([
                'seller_id'     => $storeSellerId,
                'type'          => $request->type,
                'description'   => $request->description,
                'action'        => $normalizedAction,
                'product_id'    => $productId,
                'product_type'  => $productType,
                'banner_img'    => $bannerPath,
                'days'          => $expireDays,
                'expire_at'     => Carbon::now()->addDays($expireDays),
                'moderation'    => 'pending',
                'paymentStatus' => 'pending',
                'amount'        => $amount,
            ]);

            $this->writeLog($seller, 'Banner Reklama yaratdi',
                "Turi: {$request->type} | Summa: {$amount} | ID: {$ad->id}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Reklamangiz yaratildi va to'lovni kutmoqda.",
                'ad_id'   => $ad->id,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk('public')->delete($bannerPath);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  4. MAHSULOT STATISTIKASI — recommendation, cart, views, favourites
    //  GET /api/seller/target/product-stats/{id}?type=book|stationery
    //
    //  Response:
    //  {
    //    "success": true,
    //    "data": {
    //      "product_id": 123,
    //      "type": "book",
    //      "name": "Alchemist",
    //      "views": 1240,
    //      "in_carts": 34,
    //      "in_favourites": 87,
    //      "recommended": true,
    //      "recommended_expires_at": "2026-05-10T00:00:00.000000Z",  // null = abadiy
    //      "recommended_days_left": 30,   // null = abadiy, 0 = tugagan
    //    }
    //  }
    // =========================================================================
    public function getProductStats(Request $request, int $id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        if (!$this->hasContestAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $type          = $request->query('type', 'book');

        if (!in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Mahsulot turi noto‘g‘ri.',
            ], 422);
        }

        // ── Mahsulotni topish ──────────────────────────────────────
        if ($type === 'stationery') {
            $product = Stationery::where('id', $id)
                ->where('seller_id', $storeSellerId)
                ->where('is_hidden', false)
                ->first();
        } else {
            $product = Books::where('id', $id)
                ->where('seller_id', $storeSellerId)
                ->where('is_hidden', false)
                ->first();
        }

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
        }

        // ── Recommended holati ────────────────────────────────────
        $isRecommended = (bool) $product->recommended;
        $expiresAt     = $product->recommendedExpiresAt
            ? Carbon::parse($product->recommendedExpiresAt)
            : null;

        // Muddati o'tgan bo'lsa recommended=false deb hisoblaymiz
        if ($isRecommended && $expiresAt && $expiresAt->isPast()) {
            $isRecommended = false;
        }

        $daysLeft = null;
        if ($isRecommended && $expiresAt) {
            $daysLeft = max(0, (int) now()->diffInDays($expiresAt, false));
        }
        // $daysLeft = null → abadiy, 0 = bugun tugaydi, N = N kun qoldi

        // ── Cart da nechta ─────────────────────────────────────────
        $inCarts = MyCart::where('product_id', $id)
            ->where('product_type', $type)
            ->distinct()
            ->count('user_id');

        // ── Sevimlilar da nechta ───────────────────────────────────
        $inFavourites = FavouriteProducts::where('product_id', $id)
            ->where('product_type', $type)
            ->distinct()
            ->count('user_id');

        // ── Ko'rishlar ─────────────────────────────────────────────
        $viewLogs = ProductViewLog::query()
            ->where('seller_id', $storeSellerId)
            ->where('product_id', $product->id)
            ->where('product_type', $type);

        $views = (int) (clone $viewLogs)->count();
        $recommendedViews = (int) (clone $viewLogs)
            ->where('recommendation_active', true)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'product_id'              => $product->id,
                'type'                    => $type,
                'name'                    => $product->name,
                'views'                   => $views,
                'recommended_views'       => $recommendedViews,
                'in_carts'                => $inCarts,
                'in_favourites'           => $inFavourites,
                'recommended'             => $isRecommended,
                'recommended_expires_at'  => $isRecommended
                    ? ($expiresAt?->toISOString() ?? null)
                    : null,
                'recommended_days_left'   => $isRecommended ? $daysLeft : 0,
            ],
        ], 200);
    }
}
