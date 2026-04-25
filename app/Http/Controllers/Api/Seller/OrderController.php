<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Sold;
use App\Models\SellerOrder;
use App\Models\Books;
use App\Models\Couriers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ ORDER ACCESS: OWNER, ADMIN (1), PRODUCT MANAGER (2)
     */
    private function hasOrderAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 yoki 2 → ACCESS
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    private function formatOrderAddress($address): array
    {
        if (is_array($address)) {
            return array_values($address);
        }

        if (is_string($address)) {
            $decoded = json_decode($address, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    private function formatSellerOrderItem($item): array
    {
        return [
            'id' => (int) $item->id,
            'seller_id' => (int) $item->seller_id,
            'order_id' => (int) $item->order_id,
            'product_id' => (int) $item->product_id,
            'variant_id' => $item->variant_id ? (int) $item->variant_id : null,
            'type' => (string) $item->type,
            'quantity' => (int) $item->quantity,
            'price' => (int) $item->price,
            'created_at' => optional($item->created_at)?->toISOString(),
            'updated_at' => optional($item->updated_at)?->toISOString(),
            'book' => $item->book?->toArray(),
            'stationery' => $item->stationery?->toArray(),
            'gift' => $item->gift?->toArray(),
            'variant' => $item->variant ? [
                'id' => (int) $item->variant->id,
                'color_name' => $item->variant->color_name,
                'image_path' => $item->variant->image_path,
                'stock' => (int) ($item->variant->stock ?? 0),
            ] : null,
        ];
    }

    private function formatSellerOrder($order): array
    {
        $items = collect($order->items ?? [])
            ->map(fn($item) => $this->formatSellerOrderItem($item))
            ->values()
            ->all();

        return [
            'id' => (int) $order->id,
            'seller_id' => (int) $order->seller_id,
            'order_id' => $order->order_id ? (int) $order->order_id : null,
            'client_id' => $order->client_id ? (int) $order->client_id : null,
            'courier_id' => $order->courier_id ? (int) $order->courier_id : null,
            'courierName' => $order->courierName,
            'amount' => (int) $order->amount,
            'items_count' => (int) ($order->items_count ?? count($items)),
            'status' => (int) $order->status,
            'delivery_type' => $order->delivery_type,
            'address' => $this->formatOrderAddress($order->address),
            'items' => $items,
            'created_at' => optional($order->created_at)?->toISOString(),
            'updated_at' => optional($order->updated_at)?->toISOString(),
        ];
    }

    public function lastOrders(Request $request)
{
    $seller = Auth::guard('seller')->user();

    if (!$this->hasOrderAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);

    $orders = Seller::find($storeSellerId)->orders()
        ->where('status', '!=', 0)
        ->with([
            'items' => fn($q) => $q->where('seller_id', $storeSellerId)
                ->with(['book', 'stationery', 'gift']),
        ])
        ->withCount(['items' => fn($q) => $q->where('seller_id', $storeSellerId)])
        ->orderByDesc('created_at')
        ->limit(20)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $orders->map(fn($order) => $this->formatSellerOrder($order))->values(),
    ]);
}

    
    /**
     * QR skanerlanganda — buyurtma ma'lumotlarini qaytaradi (status o'zgarmaydi)
     * GET /orders/scan-qr/{qr}
     */
    public function scanQR(Request $request, $qr)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasOrderAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        // QR format: KC:$sellerId|$shopName|$orderId|$courierId
        $parts = explode('|', str_replace('KC:', '', $qr));
        if (count($parts) !== 4) {
            return response()->json(['success' => false, 'message' => "Noto'g'ri QR format"], 400);
        }
        [$sellerId, $shopName, $orderId, $courierId] = $parts;

        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller   = Seller::find($storeSellerId);

        if (!$storeSeller || $storeSeller->shop_name !== $shopName) {
            return response()->json(['success' => false, 'message' => "Do'kon nomi mos kelmadi"], 403);
        }

        $sellerOrder = SellerOrder::with([
            'items' => fn($q) => $q->where('seller_id', $storeSellerId)
                ->with(['book', 'stationery', 'gift', 'variant']),
        ])
            ->where('order_id', $orderId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$sellerOrder) {
            return response()->json(['success' => false, 'message' => 'Buyurtma topilmadi'], 404);
        }

        $courier = Couriers::find($courierId);
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Kuryer topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order'   => $this->formatSellerOrder($sellerOrder),
                'courier' => [
                    'id'   => $courier->id,
                    'name' => $courier->first_name . ' ' . $courier->last_name,
                ],
                'qr' => $qr,
            ],
        ]);
    }

public function toCourier(Request $request, $qr)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    if (!$this->hasOrderAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.'
        ], 403);
    }
    // QR format: KC:$sellerId|$shopName|$orderId|$courierId
    $parts = explode('|', str_replace('KC:', '', $qr));
    if (count($parts) !== 4) {
        return response()->json(['success' => false, 'message' => 'Invalid QR format'], 400);
    }
    [$sellerId, $shopName, $orderId, $courierId] = $parts;

    $storeSellerId = $this->getStoreSellerId($seller);
    $storeSeller = Seller::find($storeSellerId);

    if (
        !$storeSeller ||
        $storeSeller->shop_name !== $shopName ||
        (string) $storeSellerId !== (string) $sellerId
    ) {
        return response()->json(['success' => false, 'message' => 'Shop name does not match'], 403);
    }

    try {
        return DB::transaction(function () use ($storeSeller, $orderId, $courierId, $storeSellerId) {
            $seller_order = SellerOrder::whereIn('status', [1, 2])
                ->where('order_id', $orderId)
                ->where('seller_id', $storeSellerId)
                ->lockForUpdate()
                ->first();

            if (!$seller_order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buyurtma topilmadi yoki allaqachon kuryerga berilgan'
                ], 404);
            }

            $sold = Sold::where('id', $orderId)->lockForUpdate()->first();
            if (!$sold) {
                return response()->json(['success' => false, 'message' => 'Order not found in solds table'], 404);
            }

            $courier = Couriers::find($courierId);
            if (!$courier) {
                return response()->json(['success' => false, 'message' => 'Courier not found'], 404);
            }

            $seller_order->courier_id = $courier->id;
            $seller_order->courierName = $courier->first_name . ' ' . $courier->last_name;
            $seller_order->status = 3;
            $seller_order->save();

            // Har bir seller o'z ulushi uchun alohida to'lov oladi —
            // ilgari bu kod `$allSellersDone` bloki ichida bo'lib, ko'p
            // sellerli buyurtmada faqat oxirgi seller (barchasi status=3
            // bo'lgandan keyin kuryerga bergan) to'lov olardi, qolganlari
            // to'lovsiz qolardi. Endi shu seller 'kuryerga berdi' bosganda
            // uning o'zining ulushi darhol balansga qo'shiladi va
            // successful_orders hisoblagichi oshiriladi.
            $storeSeller->balance += $seller_order->amount;
            $storeSeller->increment('successful_orders');
            $storeSeller->save();

            $allSellersDone = SellerOrder::where('order_id', $orderId)
                ->where('status', '!=', 3)
                ->doesntExist();

            if ($allSellersDone) {
                $sold->status = 'B';
                $sold->updated_at = now();
                $sold->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Products collected. ' . ($allSellersDone ? 'Order fully sent to courier.' : 'Waiting for other shops...')
            ], 200);
        });
    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => 'Xatolik: ' . $th->getMessage()
        ], 500);
    }
}

    public function acceptOrder(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $sellerOrder = SellerOrder::where('id', $id)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$sellerOrder) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if ((int) $sellerOrder->status === 4) {
            return response()->json(['success' => false, 'message' => 'Bekor qilingan buyurtmani qabul qilib bo‘lmaydi'], 422);
        }

        if ((int) $sellerOrder->status >= 2) {
            return response()->json([
                'success' => true,
                'message' => "Buyurtma allaqachon do'kon tomonidan qabul qilingan",
                'data' => ['status' => (int) $sellerOrder->status],
            ], 200);
        }

        DB::transaction(function () use ($sellerOrder, $storeSellerId) {
            $sellerOrder->status = 2;
            // Qabul qilingan vaqtni yozamiz — response_time hisoblash uchun.
            $sellerOrder->accepted_at = now();
            $sellerOrder->save();

            // Seller uchun o'rtacha javob vaqtini (soatda) yangilaymiz.
            // Faqat accepted_at bor buyurtmalar hisobga olinadi — eski
            // (null accepted_at) buyurtmalar statistikani buzmaydi.
            $avgHours = SellerOrder::where('seller_id', $storeSellerId)
                ->whereNotNull('accepted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, accepted_at) / 3600) as avg_h')
                ->value('avg_h');

            if ($avgHours !== null) {
                // decimal(5,2) — 999.99 soatgacha, shuning uchun cap qo'yamiz.
                $capped = min(round((float) $avgHours, 2), 999.99);
                Seller::where('id', $storeSellerId)
                    ->update(['response_time_hours' => $capped]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Buyurtma do'kon tomonidan qabul qilindi",
            'data' => ['status' => 2],
        ], 200);
    }

    public function viewOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        $seller = Auth::guard('seller')->user();
        
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID

        // ✅ OWNER DO'KONI ORDERI
        $view = Seller::find($storeSellerId)->orders()
            ->where('id', $orderId)
            ->where('status', '!=', 0)
            ->with([
                'items' => fn($q) => $q->where('seller_id', $storeSellerId)
                    ->with(['book', 'stationery', 'variant', 'gift']),
            ])
            ->withCount(['items' => fn($q) => $q->where('seller_id', $storeSellerId)])
            ->first();

        if (!$view) {
            return response()->json([
                'success' => false, 
                'message' => 'Order topilmadi'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSellerOrder($view),
        ], 200);
    }

    public function ordersCount(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => true,
                'orders' => 0, // Bo'sh count
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID

        // ✅ OWNER DO'KONI ORDER SONI
        $count = Seller::find($storeSellerId)->orders()
            ->where('status', '!=', 0)
            ->count();

        return response()->json([
            'success' => true,
            'orders' => $count,
        ], 200);
    }
}
