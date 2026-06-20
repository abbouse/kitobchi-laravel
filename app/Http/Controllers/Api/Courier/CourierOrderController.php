<?php

namespace App\Http\Controllers\Api\Courier;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\CourierTask;
use App\Models\Sold;
use App\Models\User;
use App\Models\Seller;
use App\Models\Couriers;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\SellerLocation;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Services\OrderRealtimeService;
use App\Services\QrTokenService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\CourierCashOnDeliveryCapacityService;
use App\Services\SellerOrderCancellationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderRealtimeService $orderRealtimeService,
        private readonly QrTokenService $qrTokenService,
        private readonly OrderStatusPushService $orderStatusPushService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly CourierCashOnDeliveryCapacityService $courierCashOnDeliveryCapacityService,
        private readonly SellerOrderCancellationService $sellerOrderCancellationService,
    ) {
    }

    public function getAvailableOrders(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized')
            ], 401);
        }

        if (!$courier->is_online) {
            return response()->json([
                'success' => true,
                'data' => [],
                'is_online' => false,
            ], 200);
        }

        $orders = CourierOrder::query()
            ->whereNull('courier_id')
            ->where(function ($query) {
                $query->where('status_code', CourierOrderStatusCode::PENDING->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', CourierOrderStatusCode::PENDING->legacy());
                    })
                    // Ba'zi eski yoki callbackdan keyin sync bo'lmay qolgan
                    // buyurtmalar `pay_process`da qolib ketgan bo'lishi mumkin.
                    // Agar underlying Sold allaqachon to'langan bo'lsa
                    // (`paymentStatus = 2`), kuryerga uni available sifatida
                    // ko'rsatamiz.
                    ->orWhere(function ($q) {
                        $q->where(function ($pendingQuery) {
                                $pendingQuery->where('status_code', CourierOrderStatusCode::PAYMENT_PENDING->value)
                                    ->orWhere(function ($fallback) {
                                        $fallback->whereNull('status_code')
                                            ->where('status', CourierOrderStatusCode::PAYMENT_PENDING->legacy());
                                    });
                            })
                            ->whereHas('order', fn ($order) => $order->where(function ($paidQuery) {
                                $paidQuery->where('payment_status_code', PaymentStatusCode::PAID->value)
                                    ->orWhere(function ($fallback) {
                                        $fallback->whereNull('payment_status_code')
                                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                                    });
                            }));
                    });
            })
            ->with([
                'paymentStatus',
                'items.product.seller',
                'items.orderStatus',
                'items.sellerLocation.workdays',
                'customer.location'
            ])
            ->latest()
            ->get()
            ->filter(function ($order) use ($courier) {
                $orderModel = $order->order()->first();
                if (!$orderModel) {
                    return false;
                }

                $tasks = $this->courierTaskOrchestratorService->ensureTasksForOrder($orderModel)
                    ->filter(fn ($task) => $task->courier_id === null && $task->status_code === 'assigned');

                if ($tasks->isEmpty()) {
                    return false;
                }

                $codExposure = (int) $tasks
                    ->filter(fn ($task) => $task->is_cod)
                    ->sum(fn ($task) => (int) ($task->cash_collect_amount ?? 0));

                return $codExposure === 0
                    || $this->courierCashOnDeliveryCapacityService->canTakeCashOrder($courier, $codExposure);
            })
            ->map(function ($order) use ($courier) {
                if ($order->status_code === CourierOrderStatusCode::PAYMENT_PENDING->value
                    && ($order->paymentStatus?->payment_status_code ?? null) === PaymentStatusCode::PAID->value) {
                    $order->status = CourierOrderStatusCode::PENDING->legacy();
                    $order->status_code = CourierOrderStatusCode::PENDING->value;
                }
                $soldOrder = $order->order()->first();
                $taskSummary = $this->buildTaskSummary($soldOrder, null);
                $operationalAmount = $soldOrder
                    ? $this->sellerOrderCancellationService->operationalAmountForCourier($soldOrder)
                    : max(0, (int) $order->amount);
                $order->amount = $operationalAmount;
                $order->task_leg = $taskSummary['task_leg'];
                $order->fulfillment_mode = $taskSummary['fulfillment_mode'];
                $order->cash_collect_amount = $taskSummary['is_cod']
                    ? $operationalAmount
                    : $taskSummary['cash_collect_amount'];
                $order->is_cod = $taskSummary['is_cod'];
                $order->task_pickup_address = $taskSummary['pickup_address'];
                $order->task_dropoff_address = $taskSummary['dropoff_address'];
                $order->task_distance_km = $taskSummary['distance_km'];
                $order->task_fee_amount = $taskSummary['task_fee_amount'];
                $order->task_bonus_amount = $taskSummary['task_bonus_amount'];
                $order->payout_breakdown = $taskSummary['payout_breakdown'];
                if ($taskSummary['task_fee_amount'] > 0) {
                    $order->courierPrice = $this->taskBasePayout($taskSummary);
                    $order->courierBonus = $taskSummary['task_bonus_amount'];
                }
                $order->hub = $taskSummary['hub'];
                $order->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
                $this->normalizeCourierOrderStatusForPayload($order);
                return $this->hydrateCourierOrderItems($order, Auth::guard('courier')->id());
            })
            ->filter(fn (CourierOrder $order) => $order->items->isNotEmpty())
            ->filter(fn (CourierOrder $order) => !$this->hasClosedPickupLocation($order))
            ->values();

        Log::info('Courier available orders fetched', [
            'courier_id' => $courier->id,
            'count' => $orders->count(),
            'order_ids' => $orders->pluck('order_id')->values()->all(),
            'statuses' => $orders->pluck('status')->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ], 200);
    }

    public function showOrder(Request $request, $id)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized')
            ], 401);
        }
        $show = CourierOrder::where('order_id', $id)
            ->where('courier_id', $courier->id)
            ->with([
                'paymentStatus',
                'items.product.seller',
                'items.orderStatus',
                'items.sellerLocation.workdays',
                'customer.location'
            ])
            ->first();

        if (!$show) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.order_not_found')
            ], 404);
        }

        $show = $this->hydrateCourierOrderItems($show, $courier->id);
        if ($show->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.order_not_found')
            ], 404);
        }
        $soldOrder = $show->order()->first();
        $taskSummary = $this->buildTaskSummary($soldOrder, $courier->id);
        $operationalAmount = $soldOrder
            ? $this->sellerOrderCancellationService->operationalAmountForCourier($soldOrder)
            : max(0, (int) $show->amount);
        $show->amount = $operationalAmount;
        $show->task_leg = $taskSummary['task_leg'];
        $show->fulfillment_mode = $taskSummary['fulfillment_mode'];
        $show->cash_collect_amount = $taskSummary['is_cod']
            ? $operationalAmount
            : $taskSummary['cash_collect_amount'];
        $show->is_cod = $taskSummary['is_cod'];
        $show->task_pickup_address = $taskSummary['pickup_address'];
        $show->task_dropoff_address = $taskSummary['dropoff_address'];
        $show->task_distance_km = $taskSummary['distance_km'];
        $show->task_fee_amount = $taskSummary['task_fee_amount'];
        $show->task_bonus_amount = $taskSummary['task_bonus_amount'];
        $show->payout_breakdown = $taskSummary['payout_breakdown'];
        if ($taskSummary['task_fee_amount'] > 0) {
            $show->courierPrice = $this->taskBasePayout($taskSummary);
            $show->courierBonus = $taskSummary['task_bonus_amount'];
        }
        $show->hub = $taskSummary['hub'];
        $show->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
        $this->normalizeCourierOrderStatusForPayload($show);

        return response()->json([
            'success' => true,
            'data'    => $show
        ], 200);
    }

    public function toCustomer(Request $request, $qr)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized')
            ], 401);
        }
        $orderCustomer = $this->resolveCustomerOrderByQr($qr, $courier->id);
        if (!$orderCustomer) {
            return response()->json(['success' => false, 'message' => __('courier_api.order_invalid_qr')], 404);
        }
        $order = CourierOrder::where('order_id', $orderCustomer->id)
            ->where('status', 'in_delivery')
            ->where('courier_id', $courier->id)
            ->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => __('courier_api.order_not_found')], 404);
        }
        try {
            DB::transaction(function () use ($order, $orderCustomer, $courier) {
                $previousStatus = (string) $orderCustomer->status;

                $order->status = CourierOrderStatusCode::CUSTOMER_RECEIVED->legacy();
                $order->status_code = CourierOrderStatusCode::CUSTOMER_RECEIVED->value;
                $order->save();

                $orderCustomer->status = OrderStatusCode::CUSTOMER_RECEIVED->legacy();
                $orderCustomer->status_code = OrderStatusCode::CUSTOMER_RECEIVED->value;
                if ($orderCustomer->payment_status_code !== PaymentStatusCode::PAID->value) {
                    $orderCustomer->paymentStatus = PaymentStatusCode::PAID->legacy();
                    $orderCustomer->payment_status_code = PaymentStatusCode::PAID->value;
                }
                $orderCustomer->completed_at ??= now();
                $orderCustomer->save();
                $this->courierTaskOrchestratorService->markDeliveredToCustomer($orderCustomer, $courier->id);

                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($orderCustomer->fresh(), $previousStatus, 'D'));
            });

            $order->refresh();
            $this->orderRealtimeService->broadcastCourierOrderUpdated($order, 'courier_order.customer_received');
            $this->orderService->processCashbackAfterOrderMutation($orderCustomer, $orderCustomer->user()->first());

            return response()->json([
                'success'      => true,
                'message'      => __('courier_api.order_delivered'),
                'order_id'     => $order->order_id,
                'courier_bonus' => (int) $order->courierBonus,
                'courierPrice' => (int) $order->courierPrice,
            ], 200);
        } catch (\Throwable $th) {
            $code = $th instanceof \RuntimeException ? 422 : 500;
            return response()->json(['success' => false, 'message' => 'Xatolik: ' . $th->getMessage()], $code);
        }
    }

    public function confirmOrder(Request $request, $id)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => __('courier_api.unauthorized')], 401);
        }

        if (!$courier->is_online) {
            return response()->json([
                'success' => false,
                'error_code' => 'courier_offline',
                'message' => "Buyurtmani qabul qilish uchun onlayn holatga o'ting.",
            ], 409);
        }

        try {
            $response = DB::transaction(function () use ($courier, $id) {
                $activeOrdersCount = CourierOrder::query()
                    ->where('courier_id', $courier->id)
                    ->where('status', 'in_delivery')
                    ->lockForUpdate()
                    ->count();

                if ($activeOrdersCount >= 3) {
                    return response()->json([
                        'success' => false,
                        'message' => "Sizda faol buyurtmalar soni 3 taga yetgan. Avval ulardan birini yakunlang.",
                    ], 422);
                }

                // Lock rows to prevent race condition
                $order = CourierOrder::where('order_id', $id)
                    ->where('status', 'pending')
                    ->whereNull('courier_id')
                    ->lockForUpdate()
                    ->first();

                $sold = Sold::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$order || !$sold) {
                    return response()->json([
                        'success' => false,
                        'message' => __('courier_api.order_already_taken'),
                    ], 404);
                }

                $acceptedTasks = $this->courierTaskOrchestratorService->acceptAvailableTasksForCourier($sold, $courier);
                $taskBonus = (int) $acceptedTasks->sum('bonus_amount');
                $taskBasePayout = (int) $acceptedTasks->sum(
                    fn (CourierTask $task) => max(0, (int) $task->fee_amount - (int) $task->bonus_amount)
                );

                $sold->courier_id   = $courier->id;
                $sold->courierName  = $courier->first_name . ' ' . $courier->last_name;
                $sold->status       = OrderStatusCode::PACKING->legacy();
                $sold->status_code  = OrderStatusCode::PACKING->value;
                $sold->save();

                $order->courier_id = $courier->id;
                $order->status     = CourierOrderStatusCode::IN_DELIVERY->legacy();
                $order->status_code = CourierOrderStatusCode::IN_DELIVERY->value;
                $order->courierPrice = $taskBasePayout;
                $order->courierBonus = $taskBonus;
                $order->save();

                return response()->json([
                    'success'      => true,
                    'message'      => __('courier_api.order_confirmed'),
                    'order_id'     => $order->order_id,
                    'courier_bonus' => (int) $order->courierBonus,
                    'courier_price' => (int) $order->courierPrice,
                ], 200);
            });

            $freshOrder = CourierOrder::where('order_id', $id)
                ->where('courier_id', $courier->id)
                ->first();
            if ($freshOrder) {
                $this->orderRealtimeService->broadcastCourierOrderUpdated($freshOrder, 'courier_order.confirmed');
            }

            return $response;
        } catch (\Throwable $th) {
            $code = $th instanceof \RuntimeException ? 422 : 500;
            return response()->json(['success' => false, 'message' => 'Xatolik: ' . $th->getMessage()], $code);
        }
    }

    public function myOrders(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => __('courier_api.unauthorized')], 401);
        }
        $orders = CourierOrder::where('courier_id', $courier->id)
            ->with([
                'paymentStatus',
                'items.product.seller',
                'items.orderStatus',
                'items.sellerLocation.workdays',
                'customer.location'
            ])
            ->orderByRaw("
                CASE
                    WHEN status IN ('in_delivery', 'pending') THEN 0
                    WHEN status IN ('delivered', 'customer_received') THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($order) use ($courier) {
                $soldOrder = $order->order()->first();
                $taskSummary = $this->buildTaskSummary($soldOrder, $courier->id);
                $operationalAmount = $soldOrder
                    ? $this->sellerOrderCancellationService->operationalAmountForCourier($soldOrder)
                    : max(0, (int) $order->amount);
                $order->amount = $operationalAmount;
                $order->task_leg = $taskSummary['task_leg'];
                $order->fulfillment_mode = $taskSummary['fulfillment_mode'];
                $order->cash_collect_amount = $taskSummary['is_cod']
                    ? $operationalAmount
                    : $taskSummary['cash_collect_amount'];
                $order->is_cod = $taskSummary['is_cod'];
                $order->task_pickup_address = $taskSummary['pickup_address'];
                $order->task_dropoff_address = $taskSummary['dropoff_address'];
                $order->task_distance_km = $taskSummary['distance_km'];
                $order->task_fee_amount = $taskSummary['task_fee_amount'];
                $order->task_bonus_amount = $taskSummary['task_bonus_amount'];
                $order->payout_breakdown = $taskSummary['payout_breakdown'];
                if ($taskSummary['task_fee_amount'] > 0) {
                    $order->courierPrice = $this->taskBasePayout($taskSummary);
                    $order->courierBonus = $taskSummary['task_bonus_amount'];
                }
                $order->hub = $taskSummary['hub'];
                $order->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
                $this->normalizeCourierOrderStatusForPayload($order);
                return $this->hydrateCourierOrderItems($order, $order->courier_id);
            })
            ->filter(fn (CourierOrder $order) => $order->items->isNotEmpty())
            ->values();
        return response()->json([
            'success' => true,
            'data'    => $orders,
        ], 200);
    }

    private function normalizeCourierOrderStatusForPayload(CourierOrder $order): void
    {
        $statusCode = CourierOrderStatusCode::fromLegacy($order->status_code ?: $order->status);
        $order->status_code = $statusCode->value;
        $order->status = $statusCode->value;
    }

    public function customerDelay(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'paused' => false,
            'message' => 'Kutish rejimi o‘chirilgan. Kuryer to‘lovi km va bonus qoidalari asosida hisoblanadi.',
        ], 410);
    }

    private function hydrateCourierOrderItems(CourierOrder $order, ?int $courierId): CourierOrder
    {
        $items = $order->items;

        // Eski yozuvlarda courier_order_items.order_id noto'g'ri courier_orders.id
        // bilan saqlanib qolgan. Yangi yozuvlar Sold id bilan ishlaydi.
        // Shuning uchun agar asosiy relation bo'sh bo'lsa, legacy fallback qilamiz.
        if ($items->isEmpty()) {
            $items = CourierOrderItem::query()
                ->where('order_id', $order->id)
                ->with([
                    'product.seller',
                    'orderStatus',
                    'sellerLocation.workdays',
                ])
                ->get();

            $order->setRelation('items', $items);
        }

        $items = $this->filterCancelledSellerItems($order, $items);
        $order->setRelation('items', $items);

        if ($items->isEmpty()) {
            return $order;
        }

        $sellerStatuses = SellerOrder::query()
            ->where('order_id', $order->order_id)
            ->whereIn('seller_id', $items->pluck('seller_id')->filter()->unique()->values())
            ->select('id', 'status', 'seller_id', 'order_id', 'courier_id', 'courierName')
            ->get()
            ->keyBy(fn ($sellerOrder) => (string) $sellerOrder->seller_id);

        $sellers = Seller::query()
            ->whereIn('id', $items->pluck('seller_id')->filter()->unique()->values())
            ->select('id', 'shop_name', 'lastname', 'firstname', 'phone_number', 'photo', 'isVerified')
            ->get()
            ->keyBy(fn ($seller) => (string) $seller->id);

        foreach ($items as $item) {
            if ($item->seller_id) {
                $item->setRelation(
                    'orderStatus',
                    $sellerStatuses->get((string) $item->seller_id)
                );
            }
            $seller = $sellers->get((string) $item->seller_id);
            if ($seller && $item->sellerLocation) {
                $seller->location = $item->sellerLocation;
            }
            $schedule = $this->locationScheduleState($item->sellerLocation);
            $item->seller_location_is_open = $schedule['is_open'];
            $item->seller_location_today_open_time = $schedule['open_time'];
            $item->seller_location_today_close_time = $schedule['close_time'];
            $item->seller_location_today_work_time_label = $schedule['label'];
            if ($item->product && $seller) {
                $item->product->setRelation('seller', $seller);
            }
            $item->setRelation('seller', $seller);
            $item->pickup_qr = $this->buildPickupQrForItem($item, $courierId, $order->order_id);
        }

        return $order;
    }

    private function hasClosedPickupLocation(CourierOrder $order): bool
    {
        if (!in_array($order->task_leg, ['first_mile', 'direct_delivery'], true)) {
            return false;
        }

        return $order->items
            ->filter(fn ($item) => $item->sellerLocation !== null)
            ->contains(fn ($item) => $this->locationScheduleState($item->sellerLocation)['is_open'] === false);
    }

    private function locationScheduleState(?SellerLocation $location): array
    {
        if (!$location) {
            return [
                'is_open' => true,
                'open_time' => null,
                'close_time' => null,
                'label' => null,
            ];
        }

        $now = Carbon::now(config('app.timezone', 'Asia/Tashkent'));
        $dayOfWeek = strtolower($now->format('l'));
        $workday = $location->relationLoaded('workdays')
            ? $location->workdays->firstWhere('day_of_week', $dayOfWeek)
            : $location->workdays()->where('day_of_week', $dayOfWeek)->first();

        if (!$workday) {
            return [
                'is_open' => true,
                'open_time' => null,
                'close_time' => null,
                'label' => null,
            ];
        }

        $open = Carbon::parse($workday->open_time, config('app.timezone', 'Asia/Tashkent'))
            ->setDate($now->year, $now->month, $now->day);
        $close = Carbon::parse($workday->close_time, config('app.timezone', 'Asia/Tashkent'))
            ->setDate($now->year, $now->month, $now->day);
        if ($close->lessThanOrEqualTo($open)) {
            $close->addDay();
        }

        $openTime = $open->format('H:i');
        $closeTime = $close->format('H:i');

        return [
            'is_open' => $now->betweenIncluded($open, $close),
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'label' => "Bugun {$openTime} - {$closeTime} gacha ishlaydi",
        ];
    }

    private function filterCancelledSellerItems(CourierOrder $order, \Illuminate\Support\Collection $items): \Illuminate\Support\Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $activeSellerItems = SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_orders.order_id', $order->order_id)
            ->whereNull('seller_order_items.cancelled_at')
            ->where(function ($query) {
                $query->whereNull('seller_order_items.refund_status')
                    ->orWhere('seller_order_items.refund_status', '!=', 'cancel_pending');
            })
            ->get([
                'seller_orders.seller_id as seller_id',
                'seller_order_items.product_id',
                'seller_order_items.variant_id',
                'seller_order_items.type',
                'seller_order_items.quantity',
                'seller_order_items.price',
            ]);

        if ($activeSellerItems->isEmpty()) {
            return $items->take(0);
        }

        $remaining = [];
        foreach ($activeSellerItems as $sellerItem) {
            $key = $this->courierItemMatchKey(
                (int) $sellerItem->seller_id,
                (int) $sellerItem->product_id,
                $sellerItem->variant_id !== null ? (int) $sellerItem->variant_id : null,
                (string) $sellerItem->type,
                (int) $sellerItem->quantity,
                (int) $sellerItem->price,
            );
            $remaining[$key] = ($remaining[$key] ?? 0) + 1;
        }

        return $items->filter(function ($item) use (&$remaining) {
            $key = $this->courierItemMatchKey(
                (int) ($item->seller_id ?? 0),
                (int) ($item->product_id ?? 0),
                $item->variant_id !== null ? (int) $item->variant_id : null,
                (string) ($item->type ?? ''),
                (int) ($item->quantity ?? 0),
                (int) ($item->price ?? 0),
            );

            if (($remaining[$key] ?? 0) <= 0) {
                return false;
            }

            $remaining[$key]--;

            return true;
        })->values();
    }

    private function courierItemMatchKey(
        int $sellerId,
        int $productId,
        ?int $variantId,
        string $type,
        int $quantity,
        int $price
    ): string {
        return implode(':', [
            $sellerId,
            $productId,
            $variantId ?? 0,
            $type,
            $quantity,
            $price,
        ]);
    }

    private function buildPickupQrForItem(mixed $item, ?int $courierId, ?int $soldOrderId = null): ?string
    {
        $sellerId = (int) ($item->product?->seller_id ?? 0);
        $orderId = (int) ($soldOrderId ?? $item->order_id ?? 0);

        if ($sellerId <= 0 || $orderId <= 0 || !$courierId) {
            return null;
        }

        return $this->qrTokenService->makePickupToken($sellerId, $orderId, $courierId);
    }

    private function resolveCustomerOrderByQr(string $qr, int $courierId): ?Sold
    {
        $signed = $this->qrTokenService->parseDeliveryToken($qr);
        if ($signed) {
            return Sold::where(function ($query) {
                    $query->where('status_code', OrderStatusCode::IN_DELIVERY->value)
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('status_code')
                                ->where('status', OrderStatusCode::IN_DELIVERY->legacy());
                        });
                })
                ->where('id', $signed['sold_id'])
                ->where('user_id', $signed['user_id'])
                ->where('courier_id', $courierId)
                ->first();
        }

        return Sold::where(function ($query) {
                $query->where('status_code', OrderStatusCode::IN_DELIVERY->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::IN_DELIVERY->legacy());
                    });
            })
            ->where('qr', $qr)
            ->where('courier_id', $courierId)
            ->first();
    }

    private function buildTaskSummary(?Sold $order, ?int $courierId): array
    {
        if (!$order) {
            return [
                'task_leg' => null,
                'fulfillment_mode' => null,
                'cash_collect_amount' => 0,
                'is_cod' => false,
                'pickup_address' => null,
                'dropoff_address' => null,
                'distance_km' => 0.0,
                'task_fee_amount' => 0,
                'task_bonus_amount' => 0,
                'payout_breakdown' => null,
                'hub' => null,
            ];
        }

        $order->loadMissing('fulfillment.hub', 'courierTasks');
        $tasks = $order->courierTasks;
        if ($tasks->isEmpty()) {
            $tasks = $this->courierTaskOrchestratorService->ensureTasksForOrder($order);
        }
        if ($courierId) {
            $tasks = $tasks->where('courier_id', $courierId);
        }

        $activeTask = $this->pickPreferredTask($tasks, $courierId);

        return [
            'task_leg' => $activeTask?->leg,
            'fulfillment_mode' => $order->fulfillment?->fulfillment_mode,
            'cash_collect_amount' => (int) ($activeTask?->cash_collect_amount ?? $order->fulfillment?->cash_collect_amount ?? 0),
            'is_cod' => (bool) ($activeTask?->is_cod ?? $order->fulfillment?->is_cod ?? false),
            'pickup_address' => $activeTask?->pickup_address,
            'dropoff_address' => $activeTask?->dropoff_address,
            'distance_km' => (float) ($activeTask?->distance_km ?? 0),
            'task_fee_amount' => (int) ($activeTask?->fee_amount ?? 0),
            'task_bonus_amount' => (int) ($activeTask?->bonus_amount ?? 0),
            'payout_breakdown' => $activeTask?->payout_breakdown,
            'hub' => $order->fulfillment?->hub?->only(['id', 'name', 'code', 'address', 'lat', 'lon']),
        ];
    }

    private function taskBasePayout(array $taskSummary): int
    {
        return max(
            0,
            (int) ($taskSummary['task_fee_amount'] ?? 0) - (int) ($taskSummary['task_bonus_amount'] ?? 0)
        );
    }

    private function pickPreferredTask(\Illuminate\Support\Collection $tasks, ?int $courierId): ?CourierTask
    {
        if ($tasks->isEmpty()) {
            return null;
        }

        if ($courierId === null) {
            $availableTask = $tasks
                ->whereNull('courier_id')
                ->first(fn (CourierTask $task) => $task->status_code === CourierTaskStatusCode::ASSIGNED->value);

            if ($availableTask) {
                return $availableTask;
            }
        }

        $priority = [
            CourierTaskStatusCode::ASSIGNED->value => 10,
            CourierTaskStatusCode::ACCEPTED->value => 20,
            CourierTaskStatusCode::ARRIVED_AT_PICKUP->value => 30,
            CourierTaskStatusCode::PICKED_UP->value => 40,
            CourierTaskStatusCode::DROPPED_OFF->value => 50,
            CourierTaskStatusCode::COMPLETED->value => 60,
            CourierTaskStatusCode::FAILED->value => 70,
            CourierTaskStatusCode::CANCELLED->value => 80,
        ];

        return $tasks
            ->sort(function (CourierTask $a, CourierTask $b) use ($priority) {
                $aPriority = $priority[$a->status_code] ?? 999;
                $bPriority = $priority[$b->status_code] ?? 999;

                if ($aPriority === $bPriority) {
                    return (int) $b->id <=> (int) $a->id;
                }

                return $aPriority <=> $bPriority;
            })
            ->first();
    }
}
