<?php

namespace App\Http\Controllers\A122;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\PostalReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Gifts;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\Stationery;
use App\Services\AdminOrderStatusSyncService;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Services\PostalResendService;
use App\Services\SellerOrderSettlementService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly AdminOrderStatusSyncService $statusSync,
        private readonly OrderStatusPushService $orderStatusPushService,
        private readonly PostalResendService $postalResendService,
    ) {}

    public function index(Request $request)
    {
        $query = Sold::with('user:id,name,lastname');
        $tab = $request->input('tab', 'pending');
        $applyOrderStatus = function ($builder, OrderStatusCode ...$statuses) {
            $values = array_map(fn (OrderStatusCode $status) => $status->value, $statuses);
            $legacyValues = array_map(fn (OrderStatusCode $status) => $status->legacy(), $statuses);

            $builder->where(function ($query) use ($values, $legacyValues) {
                $query->whereIn('status_code', $values)
                    ->orWhere(function ($fallback) use ($legacyValues) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', $legacyValues);
                    });
            });
        };

        match ($tab) {
            'shipped' => $applyOrderStatus($query, OrderStatusCode::IN_DELIVERY),
            'paid' => $applyOrderStatus($query, OrderStatusCode::DELIVERED),
            'cancelled' => $applyOrderStatus($query, OrderStatusCode::CANCELLED, OrderStatusCode::RETURNED),
            'all' => null,
            default => $applyOrderStatus($query, OrderStatusCode::PENDING, OrderStatusCode::PACKING),
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('id', $search)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")));
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Sold::count(),
            'pending' => Sold::where(function ($query) {
                $query->whereIn('status_code', [OrderStatusCode::PENDING->value, OrderStatusCode::PACKING->value])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', [OrderStatusCode::PENDING->legacy(), OrderStatusCode::PACKING->legacy()]);
                    });
            })->count(),
            'shipped' => Sold::where(function ($query) {
                $query->where('status_code', OrderStatusCode::IN_DELIVERY->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::IN_DELIVERY->legacy());
                    });
            })->count(),
            'paid' => Sold::where(function ($query) {
                $query->where('status_code', OrderStatusCode::DELIVERED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::DELIVERED->legacy());
                    });
            })->count(),
            'cancelled' => Sold::where(function ($query) {
                $query->whereIn('status_code', [OrderStatusCode::CANCELLED->value, OrderStatusCode::RETURNED->value])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::CANCELLED->legacy());
                    });
            })->count(),
        ];

        $rows = $orders->map(function (Sold $o) {
            $status = match ((string) ($o->status_code ?? $o->status)) {
                'A', 'P', 'pending', 'packing' => 'pending',
                'B', 'in_delivery' => 'shipped',
                'C', 'delivered' => 'paid',
                'F', 'cancelled', 'returned' => 'cancelled',
                default => 'pending',
            };
            $paymentStatus = PaymentStatusCode::fromLegacy($o->payment_status_code ?? $o->paymentStatus);

            return [
                'id' => '#ORD-'.$o->id,
                'customer' => trim(($o->user?->name ?? 'Mehmon').' '.($o->user?->lastname ?? '')),
                'items' => (int) collect($o->items ?? [])->sum('count_item'),
                'total' => number_format((float) $o->amount, 0).' UZS',
                'status' => $status,
                'date' => optional($o->created_at)->format('Y-m-d'),
                'payment' => match ($paymentStatus) {
                    PaymentStatusCode::PAID => "To‘langan",
                    PaymentStatusCode::CARD_PENDING => 'Karta kutilmoqda',
                    PaymentStatusCode::CASH_PENDING => 'Naqd kutilmoqda',
                    PaymentStatusCode::CANCELLED => 'To‘lov bekor qilingan',
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
                'gift' => 'Sovg‘a',
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
        $order->status_code = $order->status_code;
        $order->payment_status_code = $order->payment_status_code;
        $order->postal_return_status = $order->postal_return_status;
        $address = collect($order->address ?? [])->values();
        $primaryAddress = $address->first() ?? [];

        $sellerOrders = SellerOrder::with(['seller:id,shop_name', 'courier:id,first_name,last_name,phone_number'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->get();

        $sellerTransactions = SellerTransaction::query()
            ->where('order_id', $order->id)
            ->whereIn('category', [
                SellerOrderSettlementService::CATEGORY_ORDER_SALE,
                SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL,
            ])
            ->orderBy('id')
            ->get();

        $sellerSettlements = [];
        $settlementOverview = [
            'gross' => 0,
            'commission' => 0,
            'net' => 0,
            'reversed_net' => 0,
            'current_net' => 0,
            'sale_count' => 0,
            'reversal_count' => 0,
            'status' => 'pending',
            'label' => 'Hisob-kitob kutilmoqda',
        ];

        foreach ($sellerOrders as $sellerOrder) {
            $transactions = $sellerTransactions
                ->where('seller_order_id', $sellerOrder->id)
                ->values();

            $saleTransactions = $transactions
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
                ->where('status', SellerTransaction::STATUS_APPROVED)
                ->values();
            $reversalTransactions = $transactions
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL)
                ->where('status', SellerTransaction::STATUS_APPROVED)
                ->values();

            $gross = (int) $saleTransactions->sum('amount');
            $commission = (int) $saleTransactions->sum('commissionPrice');
            $net = (int) $saleTransactions->sum('netAmount');
            $reversedNet = (int) $reversalTransactions->sum('netAmount');
            $currentNet = $net - $reversedNet;
            $saleCount = $saleTransactions->count();
            $reversalCount = $reversalTransactions->count();

            $status = 'pending';
            $label = 'Hisob-kitob kutilmoqda';
            if ($saleCount > 0 && $currentNet > 0) {
                $status = 'settled';
                $label = "Sellerga tushgan";
            } elseif ($saleCount > 0 && $currentNet <= 0) {
                $status = 'reversed';
                $label = 'Hisob-kitob qaytarilgan';
            }

            $sellerSettlements[$sellerOrder->id] = [
                'gross' => $gross,
                'commission' => $commission,
                'net' => $net,
                'reversed_net' => $reversedNet,
                'current_net' => $currentNet,
                'sale_count' => $saleCount,
                'reversal_count' => $reversalCount,
                'status' => $status,
                'label' => $label,
                'latest_sale_at' => $saleTransactions->last()?->created_at,
                'latest_reversal_at' => $reversalTransactions->last()?->created_at,
            ];

            $settlementOverview['gross'] += $gross;
            $settlementOverview['commission'] += $commission;
            $settlementOverview['net'] += $net;
            $settlementOverview['reversed_net'] += $reversedNet;
            $settlementOverview['sale_count'] += $saleCount;
            $settlementOverview['reversal_count'] += $reversalCount;
        }

        $settlementOverview['current_net'] = $settlementOverview['net'] - $settlementOverview['reversed_net'];
        if ($settlementOverview['sale_count'] > 0 && $settlementOverview['current_net'] > 0) {
            $settlementOverview['status'] = 'settled';
            $settlementOverview['label'] = "Sellerga tushgan";
        } elseif ($settlementOverview['sale_count'] > 0 && $settlementOverview['current_net'] <= 0) {
            $settlementOverview['status'] = 'reversed';
            $settlementOverview['label'] = 'Hisob-kitob qaytarilgan';
        }

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
            'sellerSettlements',
            'settlementOverview',
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

    public function markPostalReturned(Request $request, Sold $order)
    {
        $request->validate([
            'postal_return_fee' => 'required|integer|min:0|max:1000000',
            'postal_return_note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->postalResendService->markReturnedToSender(
                $order,
                (int) $request->input('postal_return_fee'),
                $request->input('postal_return_note'),
            );

            return back()->with('success', "Buyurtma pochta qaytimi sifatida belgilandi.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function adminCancel(Request $request, Sold $order)
    {
        if (in_array($order->status, ['C', 'F'])) {
            return back()->with('error', "Yetkazilgan yoki bekor qilingan buyurtmani bekor qilib bo'lmaydi.");
        }
        $previousStatus = (string) $order->status;
        $result = $this->orderService->cancelOrder($order, strict: false);
        if (($result['ok'] ?? false) === true) {
            $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F');
        }
        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? "Buyurtma bekor qilindi." : $result['message']);
    }

    public function export(Request $request)
    {
        return Excel::download(new OrdersExport($request->all()), 'orders_' . now()->format('Y-m-d') . '.xlsx');
    }
}
