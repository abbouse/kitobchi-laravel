<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FavouriteProducts;
use App\Support\ProductVisibilityScope;
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

        // MUHIM: avval mehmon (login qilmagan) foydalanuvchi /favorites'ga
        // kirsa bosh sahifaga otlantirilar edi — piyolamarket.uz'da esa
        // mehmon ham shu sahifani ko'radi (bo'sh holat, "Kirish" taklif
        // qilinadi), faqat yurakni bosganda login so'raladi (toggle() da
        // pastda). Endi shu xatti-harakatga moslashtirildi.
        if (! $user) {
            return view('favorites.index', ['items' => collect()]);
        }

        $favourites = FavouriteProducts::where('user_id', $user->id)
            ->with(['product.category'])
            ->latest()
            ->get();

        // MUHIM: morphTo('product') hech qanday ko'rinish shartisiz xom
        // qatorni qaytaradi — bloklangan/yashiringan do'konning yoki
        // o'chirilgan/tasdiqlanmagan mahsulotning yozuvi ham shu yerga
        // kirib qolardi. Sevimlilar sahifasida ham saytning boshqa hamma
        // joyidagi bir xil qoida (ProductVisibilityScope) qo'llaniladi —
        // hozir ko'rinmaydigan mahsulot ro'yxatdan butunlay chiqarib
        // tashlanadi (o'chirilmaydi, faqat shu ro'yxatda ko'rsatilmaydi;
        // do'kon qayta faollashsa yana paydo bo'ladi).
        $visibleBookIds = array_flip(ProductVisibilityScope::visibleBookIds(
            $favourites->where('product_type', 'book')->pluck('product_id')->all()
        ));
        $visibleStationeryIds = array_flip(ProductVisibilityScope::visibleStationeryIds(
            $favourites->where('product_type', 'stationery')->pluck('product_id')->all()
        ));

        $items = $favourites
            ->map(function ($fav) use ($visibleBookIds, $visibleStationeryIds) {
                $product = $fav->product;
                if (! $product) {
                    return null;
                }

                $isVisible = $fav->product_type === 'stationery'
                    ? isset($visibleStationeryIds[$fav->product_id])
                    : isset($visibleBookIds[$fav->product_id]);

                if (! $isVisible) {
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
                // Faqat hozir ko'rinadigan mahsulotlar sanaladi — aks holda
                // header'dagi badge foydalanuvchi ko'ra olmaydigan
                // (bloklangan do'kon) mahsulotlarni ham qo'shib yuboradi.
                'count' => ProductVisibilityScope::visibleFavouriteCount($user->id),
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
            'count' => ProductVisibilityScope::visibleFavouriteCount($user->id),
        ]);
    }
}
