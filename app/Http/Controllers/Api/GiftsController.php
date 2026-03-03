<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gifts;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\MyCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GiftsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $col)
{
    $user = Auth::guard('user')->user(); 
    
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
    }

    $cartItems = MyCart::where('user_id', $user->id)
        ->with(['product.seller'])
        ->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['status' => 'success', 'data' => []], 200);
    }

    $sellerSums = [];
    foreach ($cartItems as $cartItem) {
        $product = $cartItem->product;
        if (!$product) continue;

        // DIQQAT: O'zgaruvchi nomlarini modelga moslab to'g'irladik
        if ($product instanceof \App\Models\Books) {
            $price = ($product->discountPrice && $product->discountPrice > 0) 
                     ? $product->discountPrice 
                     : $product->price;
        } else {
            $price = ($product->discount_price && $product->discount_price > 0) 
                     ? $product->discount_price 
                     : $product->price;
        }

        $sellerId = $product->seller_id;
        // stock_item emas, count_item deb o'zgartirildi
        $quantity = $cartItem->count_item ?? 1; 
        
        $sellerSums[$sellerId] = ($sellerSums[$sellerId] ?? 0) + ($price * $quantity);
    }

    $totalCartSum = array_sum($sellerSums); // Barcha do'konlar summasi yig'indisi
    $uniqueSellerIds = array_keys($sellerSums);

    $giftsQuery = Gifts::where('status', true)
        ->where('is_approved', true)
        ->where('stock', '>', 0)
        ->with('seller');

    $giftsQuery->where(function ($query) use ($sellerSums, $uniqueSellerIds, $totalCartSum) {
        // 1. Har bir do'konning o'z sovg'alari (o'sha do'konning summasiga qarab)
        foreach ($uniqueSellerIds as $sellerId) {
            if ($sellerId == 1) continue; // Platformani pastda alohida hisoblaymiz

            $sum = $sellerSums[$sellerId] ?? 0;
            $query->orWhere(function ($subQuery) use ($sellerId, $sum) {
                $subQuery->where('seller_id', $sellerId)
                         ->where('priceFrom', '<=', $sum)
                         ->where('priceTo', '>=', $sum);
            });
        }

        // 2. Platforma sovg'alari (seller_id = 1)
        // Bu savatda nima bo'lishidan qat'iy nazar umumiy summaga qarab chiqadi
        $query->orWhere(function ($subQuery) use ($totalCartSum) {
            $subQuery->where('seller_id', 1)
                     ->where('priceFrom', '<=', $totalCartSum)
                     ->where('priceTo', '>=', $totalCartSum);
        });
    });

    $gifts = $giftsQuery->orderBy('id', 'DESC')->limit((int)$col)->get();

    $formattedGifts = $gifts->map(function ($gift) {
        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'images' => $gift->images,
            'priceFrom' => $gift->priceFrom,
            'priceTo' => $gift->priceTo,
            'stock' => $gift->stock,
            'seller_id' => $gift->seller_id,
            'seller_shop_name' => $gift->seller?->shop_name ?? 'Platforma',
            'seller_photo' => $gift->seller?->photo ?? null,
        ];
    });

    return response()->json(['status' => 'success', 'data' => $formattedGifts], 200);
}

    /**
     * Gift tanlash (select_gift)
     */
    public function select_gift(Request $request)
    {
        $request->validate([
            'gift_id' => 'required|integer|exists:gifts,id',
        ]);

        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
        }

        // Giftni topish va is_approved + stock > 0 tekshiruvi
        $gift = Gifts::where('id', $request->gift_id)
            ->where('is_approved', true)
            ->where('stock', '>', 0)
            ->firstOrFail();

        // Cart sellerlarini olish (hakerga qarshi)
        $cartItems = MyCart::where('user_id', $user->id)
            ->with(['product'])
            ->get();

        $cartSellerIds = $cartItems->pluck('product.seller_id')->unique()->filter()->toArray();

        // Gift seller_id tekshiruvi
        if ($gift->seller_id != 1 && !in_array($gift->seller_id, $cartSellerIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bu gift sizning savatingiz uchun mavjud emas!'
            ], 403);
        }

        // seller_id != 1 bo'lsa → cartga 0 so'm gift qo'shish
        if ($gift->seller_id != 1) {
            MyCart::updateOrCreate(
                [
                    'user_id'      => $user->id,
                    'product_id'   => $gift->id,
                    'product_type' => 'gift',
                    'variant_id'   => null,
                ],
                [
                    'stock_item' => 1,
                    'price'      => 0,
                ]
            );
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Gift tanlandi'
        ], 200);
    }

}