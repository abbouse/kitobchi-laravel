<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\SellerOrder;
use App\Models\Stationery;
use App\Services\AdminOrderStatusSyncService;
use Illuminate\Http\Request;

class SellerOrderController extends Controller
{
    public function __construct(private readonly AdminOrderStatusSyncService $statusSync) {}

    public function index(Request $request)
    {
        $q = SellerOrder::with(['seller:id,shop_name,firstname,lastname,phone_number,photo', 'client:id,name,lastname,phone_number,avatar', 'courier:id,first_name,last_name,phone_number', 'order:id,user_id,amount,status,paymentStatus,deliveryPrice,items,address,created_at']);

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all' && is_numeric($tab)) {
            $q->where('status', $tab);
        }

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('order_id', $s)
                ->orWhere('seller_id', $s)
                ->orWhereHas('seller', fn ($sellerQuery) => $sellerQuery->where('shop_name', 'like', "%$s%"))
                ->orWhereHas('client', fn ($clientQuery) => $clientQuery
                    ->where('name', 'like', "%$s%")
                    ->orWhere('lastname', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%"))
            );
        }

        if ($request->date_from) $q->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $q->whereDate('created_at', '<=', $request->date_to);

        $orders = $q->latest()->paginate(25)->withQueryString();

        $counts = ['all' => SellerOrder::count()];
        foreach ([1, 2, 3, 4] as $s) {
            $counts[$s] = SellerOrder::where('status', $s)->count();
        }

        $statuses = AdminOrderStatusSyncService::SELLER_STATUSES;

        return view('a122.seller-orders.index', compact('orders', 'counts', 'tab', 'statuses'));
    }

    public function show(SellerOrder $sellerOrder)
    {
        $sellerOrder->load([
            'seller',
            'client',
            'courier',
            'order:id,user_id,amount,status,paymentStatus,deliveryPrice,items,address,created_at',
        ]);

        $rawItems = collect($sellerOrder->order?->items ?? [])
            ->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $sellerOrder->seller_id)
            ->values();

        $items = $rawItems->map(function (array $item) {
            $product = match ($item['type'] ?? 'book') {
                'stationery' => Stationery::find($item['item_id'] ?? 0),
                'gift' => null,
                default => Books::find($item['item_id'] ?? 0),
            };

            return [
                'name' => $item['name'] ?? $product?->name ?? 'Mahsulot',
                'type' => $item['type'] ?? 'book',
                'cover' => $item['cover'] ?? null,
                'author' => $item['author'] ?? ($product->author ?? null),
                'quantity' => (int) ($item['count_item'] ?? $item['quantity'] ?? 1),
                'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
                'product' => $product,
            ];
        });

        $address = collect($sellerOrder->order?->address ?? [])->first() ?? collect($sellerOrder->address ?? [])->first() ?? null;
        $summary = [
            'items_count' => $items->sum('quantity'),
            'items_total' => (float) $items->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']),
            'delivery_type' => $sellerOrder->delivery_type ?: ($sellerOrder->order?->deliveryType ?? '—'),
            'delivery_price' => (float) ($sellerOrder->order?->deliveryPrice ?? 0),
        ];

        $statuses = AdminOrderStatusSyncService::SELLER_STATUSES;
        return view('a122.seller-orders.show', compact('sellerOrder', 'statuses', 'items', 'address', 'summary'));
    }

    public function updateStatus(Request $request, SellerOrder $sellerOrder)
    {
        $request->validate(['status' => 'required|in:1,2,3,4']);
        $this->statusSync->updateSellerOrder($sellerOrder, (int) $request->status);
        return back()->with('success', 'Holat yangilandi.');
    }
}
