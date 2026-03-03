<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerAd;
use App\Models\SellerAdSetting;
use App\Models\Seller;
use App\Models\SellerStaffLog;
use App\Models\Books;
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
        // Contests/Targeting faqat Admin va Owner uchun mavjud (role 0: owner, role 1: admin)
        return !$seller->parent_id || $seller->role <= 1;
    }

    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }
    
    private function checkAndGetProductData($productId, $storeSellerId)
    {
        if (empty($productId)) {
            return [
                'product' => null,
                'product_type' => 'none', 
            ];
        }
        $product = Books::where('id', $productId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$product) {
            return null;
        }
        $productType = $product->type ?: 'book';

        return [
            'product' => $product,
            'product_type' => $productType,
        ];
    }

    /**
     * user hohlagan ad type uchun priceni olib keladi flutter uida ishlatish uchun
     */
    public function getInitialTargetData(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Targeting available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        
        // 1. Narx sozlamalarini olish
        $prices = SellerAdSetting::select('type', 'price')->get();
        $priceData = [];
        foreach ($prices as $item) {
            $priceData[$item->type] = $item->price;
        }

        // 2. So'nggi yaratilgan reklamalarni olish (getLastTargets funksiyasidan olingan mantiq)
        $latestTargets = SellerAd::where('seller_id', $storeSellerId)
            ->with('product:id,name') // Agar product_id mavjud bo'lsa, kitob nomini yuklash
            ->latest('created_at')
            ->take(15)
            ->get();

        return response()->json([
            'success' => true, 
            'data' => [
                'prices' => $priceData, // 'type' => 'price' formatida map
                'latest_targets' => $latestTargets,
            ]
        ], 200);
    }

    /**
     * target yoqish paytida sellerga o'zining barcha maxsulotlarini qaytarish kerak bo'lishi mumkin u tanlashi uchun
     */
    public function getProductList()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Targeting available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $data = Books::where('seller_id', $storeSellerId)
             ->where('is_hidden', false)
             ->latest('updated_at')
             ->get(['id', 'name', 'images']); 

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * Home page uchun top va center qismiga banner reklamasi yaratish metodi
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeHomePageBannerAd(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Targeting available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        // 1. VALIDATSIYA
        $request->validate([
            'type' => ['required', 'string', Rule::in(['top_banner', 'center_banner'])],
            'description' => 'nullable|string|max:1000',
            'action' => ['required', 'string', Rule::in(['to_book', 'to_shop'])],
            'product_id' => 'nullable|integer',
            'expire_days' => 'required|integer|min:1|max:30',
            'images' => 'sometimes|array|min:1|max:1', // Eng kamida bitta rasm
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2. NARXNI O'LCHASH
        $settings = SellerAdSetting::where('type', $request->type)->first();
        if (!$settings) {
            return response()->json(['success' => false, 'message' => 'Reklama narxi topilmadi. Adminstratsiya bilan bogʻlaning.'], 404);
        }
        $expireDays = (int) $request->expire_days;
        $amount = ($settings->price / 30) * $expireDays;
        $productId = $request->product_id;
        $productType = 'none';

        if ($request->action === 'to_book' && !empty($productId)) {
            $productData = $this->checkAndGetProductData($productId, $storeSellerId);

            if (is_null($productData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanlangan mahsulot (product) topilmadi yoki sizning doʻkoningizga tegishli emas.'
                ], 404);
            }
            $productType = $productData['product_type']; 
        }
        if ($request->action === 'to_book' && empty($productId)) {
             return response()->json([
                'success' => false,
                'message' => 'Action "to_book" boʻlganda, product_id kiritilishi shart.'
            ], 422);
        }
        // 3. RASMNI YUKLASH
        $imageFile = $request->file('images');
        if (is_array($imageFile)) {
            $bannerPath = array_shift($imageFile)->store('target', 'public');
        } else {
            $bannerPath = $imageFile->store('target', 'public');
        }
        
        // 4. BAZAGA YOZISH
        try {
            DB::beginTransaction();

            $ad = SellerAd::create([
                'seller_id' => $storeSellerId,
                'type' => $request->type,
                'description' => $request->description,
                'action' => $request->action,
                'product_id' => $productId,
                'product_type' => $productType,
                'banner_img' => $bannerPath,
                'days' => $expireDays,
                'expire_at' => Carbon::now()->addDays($expireDays),
                'moderation' => 'pending',
                'paymentStatus' => 'pending',
                'amount' => $amount,
            ]);

            $this->writeLog($seller, 'Yangi Banner Reklama yaratdi', "Turi: {$request->type} | Summa: {$amount} | ID: {$ad->id}");
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Banner reklamangiz muvaffaqiyatli yaratildi va to\'lovni kutmoqda.',
                'ad_id' => $ad->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk('public')->delete($bannerPath); 
            return response()->json([
                'success' => false,
                'message' => 'Reklamani saqlashda xato yuz berdi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * forum_post ad type uchun store metodi
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeForumPostAd(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Targeting available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        // 1. VALIDATSIYA
        $request->validate([
            'description' => 'required|string|max:1000',
            'action' => ['required', 'string', Rule::in(['to_book', 'to_shop'])],
            'product_id' => 'nullable|integer',
            'expire_days' => 'required|integer|min:1|max:30',
        ]);

        // 2. NARXNI O'LCHASH
        $productId = $request->product_id;
        $productType = 'none';

        if ($request->action === 'to_book' && !empty($productId)) {
            $productData = $this->checkAndGetProductData($productId, $storeSellerId);

            if (is_null($productData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanlangan mahsulot (product) topilmadi yoki sizning doʻkoningizga tegishli emas.'
                ], 404);
            }
            $productType = $productData['product_type']; 
        }
        $settings = SellerAdSetting::where('type', 'forum_post')->first();
        if (!$settings) {
            return response()->json(['success' => false, 'message' => 'Reklama narxi topilmadi. Adminstratsiya bilan bogʻlaning.'], 404);
        }
        $expireDays = (int) $request->expire_days;
        $amount = ($settings->price / 30) * $expireDays;

        // 3. BAZAGA YOZISH (Forum Post Ad banner_img talab qilmaydi)
        try {
            $ad = SellerAd::create([
                'seller_id' => $storeSellerId,
                'type' => 'forum_post',
                'description' => $request->description,
                'action' => $request->action,
                'product_id' => $productId,
                'product_type' => $productType,
                'days' => $expireDays,
                'expire_at' => Carbon::now()->addDays($expireDays),
                'moderation' => 'pending',
                'paymentStatus' => 'pending',
                'amount' => $amount,
            ]);
            $this->writeLog($seller, 'Yangi Forum Post Reklama yaratdi', "Summa: {$amount} | ID: {$ad->id}");
            return response()->json([
                'success' => true,
                'message' => 'Forum post reklamangiz muvaffaqiyatli yaratildi va to\'lovni kutmoqda.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reklamani saqlashda xato yuz berdi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * tafsiyalar bo'limidagi ad uchun store metodi
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeRecommendationAd(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Targeting available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        // 1. VALIDATSIYA
        $request->validate([
            'product_id' => 'required|integer', 
            'expire_days' => 'required|integer|min:1|max:365',
        ]);
        
        // Tanlangan product sellerga tegishli ekanligini tekshirish
        $product = Books::where('id', $request->product_id)->where('seller_id', $storeSellerId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Tanlangan mahsulot sizning doʻkoningizga tegishli emas yoki topilmadi.'], 404);
        }

        // 2. NARXNI O'LCHASH
        $productId = $request->product_id;
        $productType = 'none';

        if ($request->action === 'to_book' && !empty($productId)) {
            $productData = $this->checkAndGetProductData($productId, $storeSellerId);

            if (is_null($productData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanlangan mahsulot (product) topilmadi yoki sizning doʻkoningizga tegishli emas.'
                ], 404);
            }
            $productType = $productData['product_type']; 
        }
        $settings = SellerAdSetting::where('type', 'recommendation')->first();
        if (!$settings) {
            return response()->json(['success' => false, 'message' => 'Reklama narxi topilmadi. Adminstratsiya bilan bogʻlaning.'], 404);
        }
        $expireDays = (int) $request->expire_days;
        $amount = ($settings->price / 30) * $expireDays;
        
        // 3. BAZAGA YOZISH
        try {
            $ad = SellerAd::create([
                'seller_id' => $storeSellerId,
                'type' => 'recommendation',
                'description' => "Recommendation: " . $product->name,
                'action' => 'to_book', 
                'product_id' => $productId,
                'product_type' => $productType,
                'days' => $expireDays,
                'expire_at' => Carbon::now()->addDays($expireDays),
                'moderation' => 'pending',
                'paymentStatus' => 'pending',
                'amount' => $amount,
            ]);

            $this->writeLog($seller, 'Yangi Tavsiya Reklama yaratdi', "Mahsulot: {$product->name} | Summa: {$amount} | ID: {$ad->id}");

            return response()->json([
                'success' => true,
                'message' => 'Tavsiya reklamangiz muvaffaqiyatli yaratildi va to\'lovni kutmoqda.',
                'ad_id' => $ad->id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reklamani saqlashda xato yuz berdi: ' . $e->getMessage()
            ], 500);
        }
    }
}