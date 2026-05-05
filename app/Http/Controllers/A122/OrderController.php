<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Gifts;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Models\Stationery;
use App\Services\AdminOrderStatusSyncService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly AdminOrderStatusSyncService $statusSync,
    ) {}

    public function index(Request $request)
    {
        $query = Sold::with('user:id,name,lastname');
        $tab = $request->input('tab', 'pending');

        match ($tab) {
            'shipped' => $query->where('status', 'B'),
            'paid' => $query->where('status', 'C'),
            'cancelled' => $query->where('status', 'F'),
            'all' => null,
            default => $query->whereIn('status', ['A', 'P']),
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('id', $search)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")));
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Sold::count(),
            'pending' => Sold::whereIn('status', ['A', 'P'])->count(),
            'shipped' => Sold::where('status', 'B')->count(),
            'paid' => Sold::where('status', 'C')->count(),
            'cancelled' => Sold::where('status', 'F')->count(),
        ];

        $rows = $orders->map(function (Sold $o) {
            $status = match ((string) $o->status) {
                'A', 'P' => 'pending',
                'B' => 'shipped',
                'C' => 'paid',
                'F' => 'cancelled',
                default => 'pending',
            };

            return [
                'id' => '#ORD-'.$o->id,
                'customer' => trim(($o->user?->name ?? 'Mehmon').' '.($o->user?->lastname ?? '')),
                'items' => (int) collect($o->items ?? [])->sum('count_item'),
                'total' => number_format((float) $o->amount, 0).' UZS',
                'status' => $status,
                'date' => optional($o->created_at)->format('Y-m-d'),
                'payment' => match ((int) $o->paymentStatus) {
                    2 => 'Paid',
                    1 => 'Card',
                    0 => 'Cash',
                    default => 'Other',
                },
                'raw_id' => $o->id,
            ];
        })->values();

        return view('a122.orders.index', compact('orders', 'rows', 'counts', 'tab'));
    }

    public function show(Sold $order)
    {
        $order->load(['user', 'certificate']);
        $items = collect($order->items ?? [])->map(function ($item) {
            $type = $item['type'] ?? 'book';
            $product = match ($type) {
                'stationery' => Stationery::find($item['item_id'] ?? 0),
                'gift' => Gifts::find($item['item_id'] ?? 0),
                default => Books::find($item['item_id'] ?? 0),
            };
            $seller = null;
            $sellerId = (int) ($item['seller_id'] ?? 0);
            if ($sellerId > 0) {
                $seller = Seller::select('id', 'shop_name', 'photo')->find($sellerId);
            }
            if (!$seller && $product?->relationLoaded('seller')) {
                $seller = $product->seller;
            }
            if (!$seller && method_exists($product, 'seller')) {
                $seller = $product->seller()->first(['id', 'shop_name', 'photo']);
            }

            $item['seller'] = $seller;
            $item['product'] = $product;
            $item['image'] = $this->resolveItemImage($product);
            $item['type_label'] = match ($type) {
                'stationery' => 'Kanselyariya',
                'gift' => 'Gift',
                default => 'Kitob',
            };
            return $item;
        });
        $summary = [
            'items_count' => (int) $items->sum(fn ($it) => (int) ($it['count_item'] ?? $it['count'] ?? 1)),
            'subtotal' => (float) $items->sum(fn ($it) => ((float) ($it['item_price'] ?? $it['price'] ?? 0)) * (int) ($it['count_item'] ?? $it['count'] ?? 1)),
            'delivery' => (float) ($order->deliveryPrice ?? 0),
            'discount' => (float) ($order->discountAmount ?? 0),
            'cashback' => (float) ($order->cashbackAmount ?? 0),
        ];
        $address = collect($order->address ?? [])->values();
        $primaryAddress = $address->first() ?? [];

        $sellerOrders = SellerOrder::with(['seller:id,shop_name', 'courier:id,first_name,last_name,phone_number'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->get();

        $courierOrder = CourierOrder::with(['courier:id,first_name,last_name,phone_number,photo,region', 'user:id,name,lastname,phone_number'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        $assignedCourier = $courierOrder?->courier;
        if (!$assignedCourier && !empty($order->courier_id)) {
            $assignedCourier = Couriers::select('id', 'first_name', 'last_name', 'phone_number', 'photo', 'region')
                ->find($order->courier_id);
        }

        return view('a122.orders.show', compact(
            'order',
            'items',
            'summary',
            'address',
            'primaryAddress',
            'sellerOrders',
            'courierOrder',
            'assignedCourier',
        ));
    }

    private function resolveItemImage($product): ?string
    {
        if (!$product) {
            return null;
        }

        if (property_exists($product, 'first_image') || isset($product->first_image)) {
            $firstImage = $product->first_image;
            if (is_string($firstImage) && $firstImage !== '') {
                return $firstImage;
            }
        }

        $images = $product->images ?? null;
        if (is_array($images) && !empty($images[0]) && is_string($images[0])) {
            return $images[0];
        }

        return null;
    }

    public function updateStatus(Request $request, Sold $order)
    {
        $request->validate(['status' => 'required|in:A,P,B,C,F']);
        $this->statusSync->updateMainOrder($order, (string) $request->input('status'));
        return back()->with('success', "Buyurtma holati yangilandi.");
    }

    public function adminCancel(Request $request, Sold $order)
    {
        if (in_array($order->status, ['C', 'F'])) {
            return back()->with('error', "Yetkazilgan yoki bekor qilingan buyurtmani bekor qilib bo'lmaydi.");
        }
        $result = $this->orderService->cancelOrder($order, strict: false);
        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? "Buyurtma bekor qilindi." : $result['message']);
    }

    public function export(Request $request)
    {
        return Excel::download(new OrdersExport($request->all()), 'orders_' . now()->format('Y-m-d') . '.xlsx');
    }
}
