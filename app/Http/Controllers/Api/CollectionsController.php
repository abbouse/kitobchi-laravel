<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Collection;
use App\Models\FavouriteProducts;
use App\Models\Stationery;
use App\Support\ProductPayloadFormatter;
use App\Support\ProductVisibilityScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Dasturiy SEO "kolleksiya" (mavzuiy to'plam) sahifalari uchun ochiq API —
 * autentifikatsiyasiz ishlaydi (Nuxt SSR shu yerdan o'qiydi, xuddi
 * ShareController::product kabi). Ko'rinish qoidasi (bloklangan sotuvchi/
 * tasdiqlanmagan mahsulot yashirilishi) ProductVisibilityScope orqali —
 * saytning boshqa hamma joyidagi bilan BIR XIL manba.
 */
class CollectionsController extends Controller
{
    // GET /api/v1/kitobchi/collections — hub sahifa uchun ro'yxat
    public function index(): JsonResponse
    {
        $collections = Cache::remember('collections_index_v1', 300, function () {
            return Collection::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get(['id', 'slug', 'title', 'meta_description', 'type']);
        });

        return response()->json([
            'status' => 'success',
            'data' => $collections->map(fn (Collection $c) => [
                'slug' => $c->slug,
                'title' => $c->title,
                'description' => $c->meta_description,
                'type' => $c->type,
            ])->values(),
        ]);
    }

    // GET /api/v1/kitobchi/collections/{slug} — kolleksiya sahifasi
    public function show(string $slug): JsonResponse
    {
        $collection = Collection::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('items')
            ->first();

        if (! $collection) {
            return response()->json(['status' => 'error', 'message' => 'Kolleksiya topilmadi'], 404);
        }

        $userId = Auth::guard('user')->id();

        $bookIds = $collection->items->where('product_type', 'book')->pluck('product_id')->all();
        $stationeryIds = $collection->items->where('product_type', 'stationery')->pluck('product_id')->all();

        // MUHIM: bloklangan sotuvchi/tasdiqlanmagan mahsulot bu yerda ham
        // ko'rinmasligi kerak — shuning uchun ProductVisibilityScope orqali
        // filtrlanadi (agar mahsulot keyinchalik yashirilgan bo'lsa,
        // kolleksiyadan ham avtomatik chiqib ketadi, item o'chirilmasa ham).
        $books = $bookIds
            ? ProductVisibilityScope::applyBooks(Books::query())->whereIn('id', $bookIds)->with(['category', 'seller'])->get()->keyBy('id')
            : collect();
        $stationeries = $stationeryIds
            ? ProductVisibilityScope::applyStationeries(Stationery::query())->whereIn('id', $stationeryIds)->with(['category', 'seller'])->get()->keyBy('id')
            : collect();

        $products = $collection->items
            ->map(function ($item) use ($books, $stationeries, $userId) {
                $product = $item->product_type === 'book'
                    ? ($books[$item->product_id] ?? null)
                    : ($stationeries[$item->product_id] ?? null);

                if (! $product) {
                    return null;
                }

                $isFavourite = false;
                if ($userId) {
                    $isFavourite = FavouriteProducts::where('user_id', $userId)
                        ->where('product_type', $item->product_type)
                        ->where('product_id', $product->id)
                        ->exists();
                }

                return ProductPayloadFormatter::format($product, [
                    'type' => $item->product_type,
                    'favourite' => $isFavourite,
                ]);
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'slug' => $collection->slug,
                'title' => $collection->title,
                'intro' => $collection->intro,
                'description' => $collection->meta_description,
                'type' => $collection->type,
                'products' => $products,
            ],
        ]);
    }
}
