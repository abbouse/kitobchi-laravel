<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FavouriteProducts;
use App\Traits\HasProductVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Veb saytdagi "Sevimlilar" — ilova ishlatadigan XUDDI SHU
 * FavouriteProducts jadvaliga yozadi (book/stationery, user_id bo'yicha).
 * Bu yerda ilovaning API guardi ('user', Sanctum) emas, balki veb login
 * (WebAuthController::verifyCode() -> Auth::login()) ishlatadigan ODATIY
 * 'web' guard orqali ishlaydi — Auth::user() shu sababli to'g'ri ishlaydi.
 */
class WebFavoritesController extends Controller
{
    use HasProductVisibility;

    public function index()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('welcome');
        }

        $favourites = FavouriteProducts::where('user_id', $user->id)
            ->with(['product.category'])
            ->latest()
            ->get();

        $items = $favourites
            ->map(function ($fav) {
                $product = $fav->product;
                if (! $product) {
                    return null;
                }

                return (object) [
                    'favourite_id' => $fav->id,
                    'type' => $fav->product_type,
                    'product' => $product,
                ];
            })
            ->filter()
            ->values();

        return view('favorites.index', ['items' => $items]);
    }

    /**
     * Sevimlilarga qo'shish/olib tashlash (toggle).
     * POST /favorites/toggle { product_id, product_type }
     */
    public function toggle(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => "Iltimos, avval tizimga kiring.",
                'require_auth' => true,
            ], 401);
        }

        $productId = (int) $request->input('product_id');
        $type = $request->input('product_type') === 'stationery' ? 'stationery' : 'book';

        if ($productId <= 0) {
            return response()->json(['status' => 'error', 'message' => "Noto'g'ri mahsulot."], 422);
        }

        // Mijoz brauzerdan yuborgan ID'ga ishonmaymiz — mahsulot haqiqatan
        // sotuvda (ko'rinadigan) ekanligini serverda tekshiramiz.
        $exists = $type === 'book'
            ? $this->visibleBooks()->where('id', $productId)->exists()
            : $this->visibleStationeries()->where('id', $productId)->exists();

        if (! $exists) {
            return response()->json(['status' => 'error', 'message' => 'Mahsulot topilmadi.'], 404);
        }

        $existing = FavouriteProducts::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('product_type', $type)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'removed',
                'count' => FavouriteProducts::where('user_id', $user->id)->count(),
            ]);
        }

        FavouriteProducts::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_type' => $type,
        ]);

        return response()->json([
            'status' => 'success',
            'action' => 'added',
            'count' => FavouriteProducts::where('user_id', $user->id)->count(),
        ]);
    }
}
