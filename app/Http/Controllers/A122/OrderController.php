<?php

namespace App\Http\Controllers\A122;

use App\Enums\FulfillmentMode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Gifts;
use App\Models\Hub;
use App\Models\OrderFulfillment;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Services\AdminOrderStatusSyncService;
use App\Services\AdminPaidOrderRefundService;
use App\Services\FulfillmentAdminOverrideService;
use App\Services\HubPrintViewService;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Services\PostalResendService;
use App\Services\SellerCancellationReasonCatalog;
use App\Services\SellerOrderCancellationService;
use App\Services\SellerOrderSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly AdminOrderStatusSyncService $statusSync,
        private readonly OrderStatusPushService $orderStatusPushService,
        private readonly PostalResendService $postalResendService,
        private readonly FulfillmentAdminOverrideService $fulfillmentAdminOverrideService,
        private readonly HubPrintViewService $hubPrintViewService,
        private readonly AdminPaidOrderRefundService $adminPaidOrderRefundService,
        private readonly SellerOrderCancellationService $sellerOrderCancellationService,
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
            'paid' => $applyOrderStatus($query, OrderStatusCode::DELIVERED, OrderStatusCode::CUSTOMER_RECEIVED),
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
                $query->whereIn('status_code', [OrderStatusCode::DELIVERED->value, OrderStatusCode::CUSTOMER_RECEIVED->value])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', [OrderStatusCode::DELIVERED->legacy(), OrderStatusCode::CUSTOMER_RECEIVED->legacy()]);
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
                'C', 'D', 'delivered', 'customer_received' => 'paid',
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
                    PaymentStatusCode::PAID => 'To‘langan',
                    PaymentStatusCode::HELD => 'Hold qilingan',
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
        $order->load(['user', 'certificate', 'fulfillment.hub']);
        $activeHubs = Hub::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'city_name', 'country_code', 'priority']);
        $fulfillmentModes = collect(FulfillmentMode::cases())
            ->map(fn (FulfillmentMode $mode) => [
                'value' => $mode->value,
                'label' => match ($mode) {
                    FulfillmentMode::HUB_BASED => 'Hub orqali kuryer yetkazuvi',
                    FulfillmentMode::DIRECT_COURIER => 'Direct courier (seller → mijoz)',
                    FulfillmentMode::POSTAL_ONLY_VIA_HUB => 'Hub → pochta',
                    FulfillmentMode::PICKUP_ONLY => 'Pickup only',
                },
            ]);
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
            if (! $seller && $product?->relationLoaded('seller')) {
                $seller = $product->seller;
            }
            if (! $seller && method_exists($product, 'seller')) {
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
            'discount' => (float) (($order->discountAmount ?? 0) + ($order->collectionDiscountAmount ?? 0)),
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

        $paymentTransaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->latest('id')
            ->first();

        $paymentCard = null;
        if ($paymentTransaction && filled($paymentTransaction->provider_card_id)) {
            $paymentCard = UserCard::query()
                ->where('provider_card_id', $paymentTransaction->provider_card_id)
                ->first();
        }

        $paymentCardSnapshot = data_get($paymentTransaction?->provider_response, 'card_snapshot', []);
        $paymentCardView = [
            'provider' => $paymentTransaction?->provider,
            'provider_card_id' => $paymentTransaction?->provider_card_id,
            'masked_number' => $paymentCard?->card_number ?: ($paymentCardSnapshot['masked_number'] ?? null),
            'vendor' => $paymentCard?->vendor ?: ($paymentCardSnapshot['vendor'] ?? null),
            'card_name' => $paymentCard?->card_name ?: ($paymentCardSnapshot['card_name'] ?? null),
            'phone_number' => $paymentCard?->phone_number ?: ($paymentCardSnapshot['phone_number'] ?? null),
        ];

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
                $label = 'Sellerga tushgan';
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
            $settlementOverview['label'] = 'Sellerga tushgan';
        } elseif ($settlementOverview['sale_count'] > 0 && $settlementOverview['current_net'] <= 0) {
            $settlementOverview['status'] = 'reversed';
            $settlementOverview['label'] = 'Hisob-kitob qaytarilgan';
        }

        $courierOrder = CourierOrder::with(['courier:id,first_name,last_name,phone_number,photo,region', 'user:id,name,lastname,phone_number'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        $assignedCourier = $courierOrder?->courier;
        if (! $assignedCourier && ! empty($order->courier_id)) {
            $assignedCourier = Couriers::select('id', 'first_name', 'last_name', 'phone_number', 'photo', 'region')
                ->find($order->courier_id);
        }

        $panelAdmin = Auth::guard('panel')->user();
        $canRefundPayment = $panelAdmin?->isSuperAdmin()
            && ($paymentTransaction?->provider === 'paylov')
            && in_array(PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus), [PaymentStatusCode::HELD, PaymentStatusCode::PAID], true)
            && ! in_array((string) ($order->status_code ?? $order->status), [
                OrderStatusCode::CANCELLED->value,
                OrderStatusCode::CANCELLED->legacy(),
            ], true);

        $refundConfirmationPhrase = null;
        if ($canRefundPayment) {
            $refundConfirmationPhrase = $this->makeRefundConfirmationPhrase();
            session()->put("admin.order_refund_phrase.{$order->id}", $refundConfirmationPhrase);
        }

        return view('a122.orders.show', compact(
            'order',
            'activeHubs',
            'fulfillmentModes',
            'items',
            'summary',
            'address',
            'primaryAddress',
            'sellerOrders',
            'sellerSettlements',
            'settlementOverview',
            'courierOrder',
            'assignedCourier',
            'paymentTransaction',
            'paymentCardView',
            'canRefundPayment',
            'refundConfirmationPhrase',
        ));
    }

    public function printLabel(Sold $order)
    {
        $order->loadMissing(['user', 'fulfillment.hub']);
        /** @var OrderFulfillment|null $fulfillment */
        $fulfillment = $order->fulfillment;
        abort_if(! $fulfillment, 404, 'Fulfillment topilmadi.');

        return view('boshqaruv.orders.print.label', [
            'order' => $order,
            'fulfillment' => $fulfillment,
            'label' => $this->hubPrintViewService->labelData($fulfillment),
        ]);
    }

    public function printReceipt(Sold $order)
    {
        $order->loadMissing(['user', 'fulfillment.hub']);
        /** @var OrderFulfillment|null $fulfillment */
        $fulfillment = $order->fulfillment;
        abort_if(! $fulfillment, 404, 'Fulfillment topilmadi.');

        return view('boshqaruv.orders.print.receipt', [
            'order' => $order,
            'fulfillment' => $fulfillment,
            'receipt' => $this->hubPrintViewService->receiptData($fulfillment),
        ]);
    }

    private function resolveItemImage($product): ?string
    {
        if (! $product) {
            return null;
        }

        if (property_exists($product, 'first_image') || isset($product->first_image)) {
            $firstImage = $product->first_image;
            if (is_string($firstImage) && $firstImage !== '') {
                return $firstImage;
            }
        }

        $images = $product->images ?? null;
        if (is_array($images) && ! empty($images[0]) && is_string($images[0])) {
            return $images[0];
        }

        return null;
    }

    public function updateStatus(Request $request, Sold $order)
    {
        $request->validate([
            'status' => 'required|in:A,P,B,C,D,F,R,pending,packing,in_delivery,delivered,customer_received,cancelled,returned',
        ]);
        try {
            $this->statusSync->updateMainOrder($order, (string) $request->input('status'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Buyurtma holati yangilandi.');
    }

    public function switchFulfillmentMode(Request $request, Sold $order)
    {
        $request->validate([
            'target_mode' => 'required|string|in:'.implode(',', array_map(
                static fn (FulfillmentMode $mode) => $mode->value,
                FulfillmentMode::cases(),
            )),
            'hub_id' => 'nullable|integer|exists:hubs,id',
            'override_note' => 'nullable|string|max:1000',
        ]);

        try {
            $targetMode = FulfillmentMode::from((string) $request->input('target_mode'));
            $targetHub = $request->filled('hub_id') ? Hub::findOrFail((int) $request->input('hub_id')) : null;

            $this->fulfillmentAdminOverrideService->switchMode(
                $order,
                $targetMode,
                $targetHub,
                $request->input('override_note'),
            );

            return back()->with('success', 'Fulfillment mode yangilandi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage() ?: "Fulfillment mode’ni almashtirib bo'lmadi.");
        }
    }

    public function rerouteHub(Request $request, Sold $order)
    {
        $request->validate([
            'hub_id' => 'required|integer|exists:hubs,id',
            'reroute_note' => 'nullable|string|max:1000',
        ]);

        try {
            $targetHub = Hub::findOrFail((int) $request->input('hub_id'));
            $this->fulfillmentAdminOverrideService->rerouteHub(
                $order,
                $targetHub,
                $request->input('reroute_note'),
            );

            return back()->with('success', 'Mas’ul hub yangilandi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage() ?: "Hub reroute qilib bo'lmadi.");
        }
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

            return back()->with('success', 'Buyurtma pochta qaytimi sifatida belgilandi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function adminCancel(Request $request, Sold $order)
    {
        $statusCode = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if (in_array($statusCode, [
            OrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED,
            OrderStatusCode::CANCELLED,
            OrderStatusCode::RETURNED,
        ], true)) {
            return back()->with('error', "Yakunlangan, qaytgan yoki bekor qilingan buyurtmani bekor qilib bo'lmaydi.");
        }
        // Faqat yetkazishga chiqmagan (yangi) buyurtmani bekor qilib, pulni qaytarish mumkin.
        // Buyurtma kuryerga topshirilib yetkazilayotgan bo'lsa — nasiya shartnomasi allaqachon
        // faollashgan (hold charge qilingan), shu bois bu bosqichda avtomatik refund bilan bekor qilinmaydi.
        if ($statusCode === OrderStatusCode::IN_DELIVERY) {
            return back()->with('error', "Buyurtma yetkazilmoqda. Yetkazilayotgan buyurtmani bu yerdan bekor qilib bo'lmaydi.");
        }
        $previousStatus = (string) $order->status;
        $result = $this->orderService->cancelOrder($order, strict: false);
        if (($result['ok'] ?? false) === true) {
            $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F');
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Buyurtma bekor qilindi.' : $result['message']);
    }

    public function refundAndCancel(Request $request, Sold $order)
    {
        $admin = Auth::guard('panel')->user();
        if (! $admin || ! $admin->isSuperAdmin()) {
            return back()->with('error', 'Bu amal faqat superadmin uchun ruxsat etilgan.');
        }

        $request->validate([
            'confirmation_phrase' => 'required|string|max:64',
            'reason' => 'nullable|string|max:255',
        ]);

        $expectedPhrase = (string) session()->get("admin.order_refund_phrase.{$order->id}", '');
        session()->forget("admin.order_refund_phrase.{$order->id}");

        if ($expectedPhrase === '' || ! hash_equals($expectedPhrase, trim((string) $request->input('confirmation_phrase')))) {
            return back()->with('error', 'Tasdiqlash matni noto‘g‘ri kiritildi.');
        }

        try {
            $this->adminPaidOrderRefundService->refundAndCancelOrder(
                $order,
                $admin,
                $request->filled('reason') ? (string) $request->input('reason') : null,
            );

            return back()->with('success', 'Pul qaytarildi va buyurtma bekor qilindi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refundSellerOrder(Request $request, SellerOrder $sellerOrder)
    {
        $admin = Auth::guard('panel')->user();
        if (! $admin || ! $admin->isAdmin()) {
            return back()->with('error', 'Bu amal faqat admin uchun ruxsat etilgan.');
        }

        $order = Sold::query()->find($sellerOrder->order_id);
        if (! $order || ! $this->canProcessOperationalAdjustment($order)) {
            return back()->with('error', 'Bu buyurtma statusida mahsulot/seller qismini bekor qilib bo‘lmaydi.');
        }

        $request->validate([
            'reason_code' => ['required', 'string', 'max:64', Rule::in(SellerCancellationReasonCatalog::orderSelectableCodes())],
            'custom_note' => 'nullable|string|max:500',
        ]);

        if ($request->input('reason_code') === 'custom' && blank($request->input('custom_note'))) {
            return back()->with('error', 'Custom sabab uchun izoh yozilishi shart.');
        }

        try {
            $this->sellerOrderCancellationService->cancelSellerOrderByAdmin(
                $admin,
                $sellerOrder,
                (string) $request->input('reason_code'),
                $request->filled('custom_note') ? (string) $request->input('custom_note') : null,
            );

            return back()->with('success', 'Seller order bo‘yicha refund va bekor qilish bajarildi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refundSellerOrderItem(Request $request, \App\Models\SellerOrderItem $sellerOrderItem)
    {
        $admin = Auth::guard('panel')->user();
        if (! $admin || ! $admin->isAdmin()) {
            return back()->with('error', 'Bu amal faqat admin uchun ruxsat etilgan.');
        }

        $sellerOrder = SellerOrder::query()->find($sellerOrderItem->order_id);
        $order = $sellerOrder ? Sold::query()->find($sellerOrder->order_id) : null;
        if (! $order || ! $this->canProcessOperationalAdjustment($order)) {
            return back()->with('error', 'Bu buyurtma statusida mahsulot/seller qismini bekor qilib bo‘lmaydi.');
        }

        $request->validate([
            'reason_code' => ['required', 'string', 'max:64', Rule::in(SellerCancellationReasonCatalog::itemSelectableCodes())],
            'custom_note' => 'nullable|string|max:500',
        ]);

        if ($request->input('reason_code') === 'custom' && blank($request->input('custom_note'))) {
            return back()->with('error', 'Custom sabab uchun izoh yozilishi shart.');
        }

        try {
            $this->sellerOrderCancellationService->cancelItemByAdmin(
                $admin,
                $sellerOrderItem,
                (string) $request->input('reason_code'),
                $request->filled('custom_note') ? (string) $request->input('custom_note') : null,
            );

            return back()->with('success', 'Mahsulot bo‘yicha refund va bekor qilish bajarildi.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function makeRefundConfirmationPhrase(): string
    {
        return 'QAYTAR-'.Str::upper(Str::random(3)).'-'.random_int(10, 99);
    }

    private function canProcessOperationalAdjustment(Sold $order): bool
    {
        $orderStatus = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if (in_array($orderStatus, [
            OrderStatusCode::IN_DELIVERY,
            OrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED,
            OrderStatusCode::RETURNED,
            OrderStatusCode::CANCELLED,
        ], true)) {
            return false;
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if (in_array($paymentStatus, [
            PaymentStatusCode::CASH_PENDING,
            PaymentStatusCode::CARD_PENDING,
            PaymentStatusCode::HELD,
        ], true)) {
            return true;
        }

        if ($paymentStatus !== PaymentStatusCode::PAID) {
            return false;
        }

        if ((int) ($order->amount ?? 0) <= 0) {
            return (int) ($order->cashbackAmount ?? 0) > 0
                || (int) ($order->giftCertAmount ?? 0) > 0;
        }

        return Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->where(function ($query) {
                $query->where('state', 2)
                    ->orWhereNotNull('perform_time');
            })
            ->exists();
    }

    public function export(Request $request)
    {
        return Excel::download(new OrdersExport($request->all()), 'orders_'.now()->format('Y-m-d').'.xlsx');
    }

    public function sendUnreachablePush(Sold $order)
    {
        $res = $this->orderStatusPushService->sendCashOrderUnreachableNotice($order);

        if (! $res['success']) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $res['message']], 422);
            }
            return back()->with('error', $res['message']);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $res['message']]);
        }

        return back()->with('success', $res['message']);
    }
}
