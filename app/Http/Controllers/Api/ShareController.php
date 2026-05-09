<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\FavouriteProducts;
use App\Support\ProductPayloadFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    private function publicBookScope()
    {
        return Books::query()
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0));
    }

    private function publicStationeryScope()
    {
        return Stationery::query()
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q
                ->where('is_hidden', 0)
                ->where('status', 'approved')
                ->where('parent_id', 0));
    }

    private function err(string $msg, int $code = 404)
    {
        return response()->json(['status' => 'error', 'message' => $msg], $code);
    }

    // ── GET /api/share/product/{id}?type=book ─────────────────────────
    // Autentifikatsiyasiz ham ishlaydi
    public function product(Request $request, int $id)
    {
        $type = $request->query('type', 'book');
        $user = Auth::guard('user')->user(); // null bo'lishi mumkin

        if ($type === 'book') {
            $product = $this->publicBookScope()
                ->with(['seller', 'category', 'tags'])
                ->find($id);
        } else {
            $product = $this->publicStationeryScope()
                ->with(['seller', 'category', 'tags', 'variants'])
                ->find($id);
        }

        if (!$product) {
            return $this->err('Mahsulot topilmadi!');
        }

        // Favourite holati (faqat login qilingan user uchun)
        $isFavourite = false;
        if ($user) {
            $isFavourite = FavouriteProducts::where('user_id', $user->id)
                ->where('product_id', $id)
                ->where('product_type', $type)
                ->exists();
        }

        // ProductModel formatiga moslashtirish
        $data = $this->formatProduct($product, $type, $isFavourite);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    private function formatProduct($product, string $type, bool $isFavourite): array
    {
        return ProductPayloadFormatter::format($product, [
            'type' => $type,
            'favourite' => $isFavourite,
            'category_format' => 'title',
        ]);
    }
}
