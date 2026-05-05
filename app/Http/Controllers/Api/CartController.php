<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MyCart;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\User;
use App\Models\FavouriteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Support\ProductImageUrls;
use App\Support\ProductPayloadFormatter;

class CartController extends Controller
{
    /**
     * Umumiy mahsulotni formatlash
     */
    private function formatProduct($product, $user = null, $type = 'book')
    {
        return ProductPayloadFormatter::format($product, [
            'user' => $user,
            'type' => $type,
            'category_format' => 'title',
        ]);
    }

    /**
     * Savatdagi barcha mahsulotlar ro'yxati
     */
    public function index(Request $request)
{
    $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
        }
    $cartItems = MyCart::where('user_id', $user->id)
        ->with(['variant', 'product.seller']) // Endi morphTo ishlaydi!
        ->orderBy('created_at', 'DESC')
        ->get();

    $formattedItems = $cartItems->map(function ($item) use ($user) {
        $product = $item->product;

        if (!$product) {
            return null;
        }

        return [
            'cart_id' => $item->id,
            'quantity' => $item->count_item,
            'product' => $this->formatProduct($product, $user, $item->product_type, $item->variant),
            'variant' => $item->variant
      ? [
          'id' => $item->variant->id,
          'color_name' => $item->variant->color_name,
          'image' => $item->variant->image_path,
          'stock' => $item->variant->stock,
        ]
      : null,
        ];
    })->filter()->values();

    $totalQuantity = $formattedItems->sum('quantity');
    $totalPrice = $formattedItems->sum(fn($i) => $i['product']['discountPrice'] * $i['quantity']);

    return response()->json([
        'status' => 'success',
        'data' => [
            'items' => $formattedItems,
            'summary' => [
                'total_items' => $totalQuantity,
                'total_price' => $totalPrice,
            ]
        ]
    ]);
}

    /**
     * Savatga mahsulot qo'shish
     */
    public function plus(Request $request)
{
    $user = Auth::guard('user')->user();

    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
    }

    $request->validate([
        'product_id'     => 'required|integer',
        'product_type'   => 'required|in:book,stationery',
        'variant_id'     => 'nullable|integer|exists:stationery_variants,id',
        'plusType'       => 'required|in:productDetail,cartScreen',
        'new_count_item' => 'required_if:plusType,cartScreen|integer|min:1',
    ]);

    $plusType = $request->plusType;

    // Mahsulot va stock aniqlash
    if ($request->product_type === 'book') {
        $product = Books::findOrFail($request->product_id);
        $availableStock = $product->count ?? 0;
        $stockSource = 'book count';
    } else {
        $product = Stationery::findOrFail($request->product_id);
        if ($request->variant_id) {
            $variant = StationeryVariant::findOrFail($request->variant_id);
            $availableStock = $variant->stock ?? 0;
            $stockSource = 'variant stock';
        } else {
            $availableStock = $product->stock ?? 0;
            $stockSource = 'stationery stock';
        }
    }

    if ($availableStock <= 0) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Mahsulot tugagan'
        ], 400);
    }

    // Joriy cart itemni topish
    $cartItem = MyCart::where([
        'user_id'      => $user->id,
        'product_id'   => $request->product_id,
        'product_type' => $request->product_type,
        'variant_id'   => $request->variant_id,
    ])->first();

    $currentCount = $cartItem?->count_item ?? 0;

    // Yangi miqdorni hisoblash
    $newCount = $plusType === 'productDetail'
        ? $currentCount + 1                     // Mahsulot sahifasidan → +1
        : $request->new_count_item;             // Savat sahifasidan → to‘liq yangi son

    // Stock tekshiruvi — har ikkala holatda ham
    if ($newCount > $availableStock) {
        return response()->json([
            'status'  => 'error',
            'message' => "Yetarli zaxira mavjud emas. Mavjud: $availableStock ta"
        ], 400);
    }

    // Agar newCount 0 bo‘lsa — cart itemni o‘chirish (ixtiyoriy, agar xohlasangiz)
    if ($newCount <= 0) {
        if ($cartItem) {
            $cartItem->delete();
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Mahsulot savatdan o‘chirildi',
            'data'    => ['quantity' => 0]
        ]);
    }

    // updateOrCreate — to‘g‘ridan-to‘g‘ri yangi qiymatni yozamiz
    $updatedCartItem = MyCart::updateOrCreate(
        [
            'user_id'      => $user->id,
            'product_id'   => $request->product_id,
            'product_type' => $request->product_type,
            'variant_id'   => $request->variant_id,
        ],
        [
            'count_item' => $newCount
        ]
    );

    return response()->json([
        'status'  => 'success',
        'data'    => [
            'quantity' => $newCount,
            'cart_id'  => $updatedCartItem->id
        ]
    ]);
}

    /**
     * Savatdan sonini kamaytirish
     */
    public function minus(Request $request, $cartId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
        }

        $cartItem = MyCart::where('id', $cartId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($cartItem->count_item > 1) {
            $cartItem->decrement('count_item');
        } else {
            $cartItem->delete();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Savatdan butunlay o‘chirish
     */
    public function remove(Request $request, $cartId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
        }

        MyCart::where('id', $cartId)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Savatdan o‘chirildi']);
    }
    public function batchDelete(Request $request)
{
    $user = Auth::guard('user')->user();
    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }
 
    $request->validate([
        'cart_ids'   => 'required|array|min:1',
        'cart_ids.*' => 'required|integer',
    ]);
 
    $deleted = MyCart::whereIn('id', $request->cart_ids)
        ->where('user_id', $user->id)
        ->delete();
 
    if ($deleted === 0) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Hech narsa o\'chirilmadi'
        ], 404);
    }
 
    return response()->json([
        'status'  => 'success',
        'deleted' => $deleted,
    ]);
}
    /**
     * Muayyan mahsulot savatda borligini tekshirish (Item sahifasi uchun)
     */
    public function check(Request $request, $productId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'data' => ['in_cart' => false, 'quantity' => 0]
            ]);
        }

        $request->validate([
            'product_type' => 'required|in:book,stationery',
            'variant_id'   => 'nullable|integer',
        ]);

        $item = MyCart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('product_type', $request->product_type)
            ->where('variant_id', $request->variant_id ?? null)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'in_cart'   => $item !== null,
                'quantity'  => $item?->count_item ?? 0,
                'cart_id'   => $item?->id
            ]
        ]);
    }
}
