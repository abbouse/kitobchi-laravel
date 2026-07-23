<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SharedCart;
use App\Models\MyCart;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\Sold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SharedCartController extends Controller
{
    private function err(string $msg, int $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $msg
        ], $code);
    }

    /**
     * N+1 OLDINI OLISH: item ro'yxatidagi barcha mahsulot/variantlarni BITTA
     * so'rovda (tur bo'yicha) yuklaydi. Avval har item uchun Books::find /
     * Stationery::find chaqirilardi (N+1). Natija bir xil — faqat so'rov soni kamayadi.
     *
     * @return array{book: \Illuminate\Support\Collection, stationery: \Illuminate\Support\Collection, variant: \Illuminate\Support\Collection}
     */
    private function preloadItemProducts(iterable $items, string $idKey, string $typeKey, string $variantKey): array
    {
        $bookIds = [];
        $statIds = [];
        $variantIds = [];

        foreach ($items as $it) {
            $pid = $it[$idKey] ?? null;
            if (! $pid) {
                continue;
            }
            $type = $it[$typeKey] ?? 'book';
            if ($type === 'gift') {
                continue;
            }
            if ($type === 'book') {
                $bookIds[] = $pid;
            } else {
                $statIds[] = $pid;
            }
            if (! empty($it[$variantKey])) {
                $variantIds[] = $it[$variantKey];
            }
        }

        return [
            'book' => $bookIds
                ? Books::withAvailableTotal()->whereIn('id', array_unique($bookIds))->get()->keyBy('id')
                : collect(),
            'stationery' => $statIds
                ? Stationery::withAvailableTotal()->whereIn('id', array_unique($statIds))->get()->keyBy('id')
                : collect(),
            'variant' => $variantIds
                ? StationeryVariant::withAvailableTotal()->whereIn('id', array_unique($variantIds))->get()->keyBy('id')
                : collect(),
        ];
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $request->validate([
            'source' => 'required|in:cart,order',
            'selected_cart_ids' => 'required_if:source,cart|array',
            'selected_cart_ids.*' => 'integer',
            'order_id' => 'required_if:source,order|integer',
            'expires_hours' => 'nullable|integer|min:1|max:8760',
        ]);

        $items = [];
        $orderId = null;

        // ================= CART =================

        if ($request->source === 'cart') {

            $cartItems = MyCart::where('user_id', $user->id)
                ->with(['product.seller', 'variant'])
                ->when(
                    !empty($request->selected_cart_ids),
                    fn($q) => $q->whereIn('id', $request->selected_cart_ids)
                )
                ->get();

            if ($cartItems->isEmpty()) {
                return $this->err('Savatda mahsulot topilmadi!');
            }

            foreach ($cartItems as $cartItem) {

                $product = $cartItem->product;
                if (!$product) continue;

                $variant = $cartItem->variant;

                $price = $this->effectivePrice(
                    $product,
                    $cartItem->product_type
                );

                $image = $variant?->image_path
                    ?? ($product->images[0] ?? null);

                $items[] = [

                    'product_id' => $product->id,
                    'product_type' => $cartItem->product_type,
                    'variant_id' => $variant?->id,

                    'name' => $product->name,

                    'author' =>
                        $cartItem->product_type === 'book'
                        ? ($product->author ?? null)
                        : null,

                    'material' =>
                        $cartItem->product_type === 'stationery'
                        ? ($product->material ?? null)
                        : null,

                    'color_name' => $variant?->color_name,

                    'image' => $image,

                    'price' => $price,

                    'quantity' => $cartItem->count_item,

                    'seller_id' => $product->seller_id,

                    'seller_name' =>
                        $product->seller?->shop_name,
                ];
            }
        }

        // ================= ORDER =================

        if ($request->source === 'order') {

            $order = Sold::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return $this->err('Buyurtma topilmadi!', 404);
            }

            $orderId = $order->id;

            // Barcha mahsulot/variantlarni bitta so'rovda oldindan yuklaymiz (N+1 yo'q)
            $preloaded = $this->preloadItemProducts($order->items, 'item_id', 'type', 'variant_id');

            foreach ($order->items as $orderItem) {

                // ❗ GIFT DOIM KESILADI
                if (($orderItem['type'] ?? null) === 'gift') {
                    continue;
                }

                $productId = $orderItem['item_id'] ?? null;

                $productType =
                    $orderItem['type'] ?? 'book';

                $product = $productId
                    ? ($productType === 'book'
                        ? $preloaded['book']->get($productId)
                        : $preloaded['stationery']->get($productId))
                    : null;

                $variantId =
                    $orderItem['variant_id'] ?? null;

                $variant =
                    $variantId
                    ? $preloaded['variant']->get($variantId)
                    : null;

                $currentStock =
                    $variant
                    ? ($variant->stock ?? 0)
                    : ($product?->count ?? $product?->stock ?? 0);

                $items[] = [

                    'product_id' => $productId,
                    'product_type' => $productType,
                    'variant_id' => $variantId,

                    'name' => $orderItem['name'],

                    'author' =>
                        $orderItem['author'] ?? null,

                    'material' =>
                        $orderItem['material'] ?? null,

                    'color_name' =>
                        $orderItem['color_name'] ?? null,

                    'image' =>
                        $orderItem['cover'] ?? null,

                    'price' =>
                        $orderItem['item_price'],

                    'quantity' =>
                        $orderItem['count_item'],

                    'seller_id' =>
                        $orderItem['seller_id'],

                    'seller_name' =>
                        $orderItem['seller_name'] ?? null,

                    'current_stock' =>
                        $currentStock,
                ];
            }

            if (empty($items)) {
                return $this->err(
                    "Buyurtmada ulashish mumkin bo'lgan mahsulot yo'q!"
                );
            }
        }

        // ================= SAVE =================

        $slug = Str::random(12);

        $expiresAt = $request->filled('expires_hours')
            ? now()->addHours(
                (int)$request->expires_hours
            )
            : null;

        $shared = SharedCart::create([

            'user_id' => $user->id,

            'slug' => $slug,

            'items' => $items,

            'source' => $request->source,

            'order_id' => $orderId,

            'expires_at' => $expiresAt,
        ]);

        $shareUrl =
            config('app.url')
            . '/share/cart/'
            . $slug;

        return response()->json([

            'status' => 'success',

            'message' => 'Havola yaratildi',

            'data' => [

                'slug' => $slug,

                'share_url' => $shareUrl,

                'items_count' => count($items),

                'expires_at' =>
                    $expiresAt?->toISOString(),
            ],

        ], 201);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(string $slug)
    {
        $shared = SharedCart::where('slug', $slug)->first();

        if (!$shared) {
            return $this->err('Havola topilmadi!', 404);
        }

        if ($shared->is_expired) {
            return $this->err(
                'Havolaning muddati tugagan!',
                410
            );
        }

        $shared->increment('view_count');

        // Barcha mahsulot/variantlarni bitta so'rovda oldindan yuklaymiz (N+1 yo'q)
        $preloaded = $this->preloadItemProducts($shared->items, 'product_id', 'product_type', 'variant_id');

        $items = collect($shared->items)

            // ❗ GIFT FILTER
            ->filter(function ($item) {
                return ($item['product_type'] ?? '') !== 'gift';
            })

            ->map(function ($item) use ($preloaded) {

                $productId =
                    $item['product_id'] ?? null;

                $productType =
                    $item['product_type'] ?? 'book';

                $variantId =
                    $item['variant_id'] ?? null;

                if (!$productId) return $item;

                $product = $productType === 'book'
                    ? $preloaded['book']->get($productId)
                    : $preloaded['stationery']->get($productId);

                $variant =
                    $variantId
                    ? $preloaded['variant']->get($variantId)
                    : null;

                $currentStock =
                    $variant
                    ? ($variant->stock ?? 0)
                    : ($product?->count ?? $product?->stock ?? 0);

                $currentPrice =
                    $product
                    ? $this->effectivePrice(
                        $product,
                        $productType
                    )
                    : ($item['price'] ?? 0);

                return array_merge($item, [

                    'current_stock' =>
                        $currentStock,

                    'current_price' =>
                        $currentPrice,

                    'is_available' =>
                        $product !== null
                        && $currentStock > 0,
                ]);

            })
            ->values();

        return response()->json([

            'status' => 'success',

            'data' => [

                'slug' => $shared->slug,

                'source' => $shared->source,

                'shared_by' => [
                    'name' => $shared->user?->name,
                ],

                'items' => $items,

                'items_count' =>
                    $items->count(),

                'view_count' =>
                    $shared->view_count,

                'expires_at' =>
                    $shared->expires_at?->toISOString(),

                'created_at' =>
                    $shared->created_at->toISOString(),
            ],
        ]);
    }
    
    public function orderItems(int $orderId)
    {
        $shared = SharedCart::where('order_id', $orderId)
            ->where('source', 'order')
            ->latest()
            ->first();
 
        if (!$shared) {
            return $this->err('Bu buyurtma ulashilmagan yoki topilmadi!', 404);
        }
 
        if ($shared->is_expired) {
            return $this->err('Havolaning muddati tugagan!', 410);
        }
 
        $shared->increment('view_count');
 
        // Barcha mahsulot/variantlarni bitta so'rovda oldindan yuklaymiz (N+1 yo'q)
        $preloaded = $this->preloadItemProducts($shared->items, 'product_id', 'product_type', 'variant_id');

        // Har bir item uchun hozirgi stock va narxni yangilaymiz
        $items = collect($shared->items)->map(function ($item) use ($preloaded) {
            $productId   = $item['product_id'] ?? null;
            $productType = $item['product_type'] ?? 'book';
            $variantId   = $item['variant_id'] ?? null;
 
            if (!$productId) return $item;
 
            $product = $productType === 'book'
                ? $preloaded['book']->get($productId)
                : $preloaded['stationery']->get($productId);

            $variant = $variantId ? $preloaded['variant']->get($variantId) : null;
 
            $currentStock = $variant
                ? ($variant->stock ?? 0)
                : ($product?->count ?? $product?->stock ?? 0);
 
            $currentPrice = $product
                ? $this->effectivePrice($product, $productType)
                : ($item['price'] ?? 0);
 
            return array_merge($item, [
                'current_stock' => $currentStock,
                'current_price' => $currentPrice,
                'is_available'  => $product !== null && $currentStock > 0,
            ]);
        })->values();
 
        return response()->json([
            'status' => 'success',
            'data'   => [
                'order_id'    => $orderId,
                'shared_by'   => ['name' => $shared->user?->name],
                'items'       => $items,
                'items_count' => $items->count(),
                'expires_at'  => $shared->expires_at?->toISOString(),
            ],
        ]);
    }

    // =========================================================================
    // ADD TO CART
    // =========================================================================

    public function addToCart(Request $request, string $slug)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $shared = SharedCart::where('slug', $slug)->first();

        if (!$shared) {
            return $this->err('Havola topilmadi!', 404);
        }

        if ($shared->is_expired) {
            return $this->err(
                'Havolaning muddati tugagan!',
                410
            );
        }

        $selectedItems = collect($shared->items)

            // ❗ GIFT FILTER
            ->filter(function ($item) {
                return ($item['product_type'] ?? '') !== 'gift';
            })

            ->values();

        $added = 0;

        foreach ($selectedItems as $item) {

            $productId =
                $item['product_id'] ?? null;

            $productType =
                $item['product_type'];

            $variantId =
                $item['variant_id'] ?? null;

            $quantity =
                max(1, (int)($item['quantity'] ?? 1));

            if (!$productId) continue;

            MyCart::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'product_type' => $productType,
                    'variant_id' => $variantId,
                ],
                [
                    'count_item' => $quantity,
                ]
            );

            $added++;
        }

        return response()->json([

            'status' => 'success',

            'message' =>
                "$added ta mahsulot savatchaga qo'shildi",

            'data' => [
                'added' => $added,
            ],
        ]);
    }

    // =========================================================================

    private function effectivePrice($product, string $type): float
    {
        if ($type === 'book') {

            return
                (is_numeric($product->discountPrice)
                    && $product->discountPrice > 0)

                ? (float)$product->discountPrice
                : (float)$product->price;
        }

        return
            (is_numeric($product->discount_price)
                && $product->discount_price > 0)

            ? (float)$product->discount_price
            : (float)$product->price;
    }
}