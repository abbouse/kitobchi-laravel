<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\FavouriteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
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
            $product = Books::with(['seller', 'category', 'tags'])
                ->find($id);
        } else {
            $product = Stationery::with(['seller', 'category', 'tags', 'variants'])
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
        $isBook = $type === 'book';

        $base = [
            'id'            => $product->id,
            'type'          => $type,
            'name'          => $product->name,
            'description'   => $product->description ?? null,
            'images'        => $product->images ?? [],
            'price'         => $product->price,
            'discountPrice' => $isBook
                ? ($product->discountPrice ?? $product->price)
                : ($product->discount_price ?? $product->price),
            'count'         => $isBook ? $product->count : $product->stock,
            'sales'         => $product->totalSales ?? 0,
            'weekly_sales'  => $product->totalSalesWeek ?? 0,
            'favourite'     => $isFavourite,
            'category_id'   => $product->category_id,
            'category'      => $product->category?->title ?? null,
            'seller'        => [
                'seller_id' => $product->seller?->id,
                'shop_name' => $product->seller?->shop_name,
                'photo'     => $product->seller?->photo,
                'is_verified' => $product->seller?->is_verified ?? false,
            ],
            'tags' => $product->tags->map(fn($t) => [
                'uz' => $t->tag_name_uz ?? $t->name_uz ?? null,
                'ru' => $t->tag_name_ru ?? $t->name_ru ?? null,
                'en' => $t->tag_name_en ?? $t->name_en ?? null,
            ])->filter()->values(),
        ];

        if ($isBook) {
            $base += [
                'author'    => $product->author ?? null,
                'lang'      => $product->lang ?? null,
                'langType'  => $product->langType ?? null,
                'coverType' => $product->coverType ?? null,
                'year'      => $product->year ?? null,
            ];
        } else {
            $base += [
                'material' => $product->material ?? null,
                'variants' => $product->variants->map(fn($v) => [
                    'id'         => $v->id,
                    'color_name' => $v->color_name,
                    'image'      => $v->image_path ?? null,
                    'stock'      => $v->stock,
                ])->values(),
            ];
        }

        return $base;
    }
}