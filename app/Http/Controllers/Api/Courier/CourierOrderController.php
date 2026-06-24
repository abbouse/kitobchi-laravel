<?php

namespace App\Http\Controllers\Api\Courier;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Http\Controllers\Controller;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\CourierTask;
use App\Models\CourierTransaction;
use App\Models\OrderFulfillment;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use App\Services\CourierCashOnDeliveryCapacityService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\OrderRealtimeService;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Services\QrTokenService;
use App\Services\SellerOrderCancellationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    ) {}

    public function getAvailableOrders(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }

        if (! $courier->is_online) {
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
                'customer.location',
            ])
            ->latest()
            ->get()
            ->filter(function ($order) use ($courier) {
                $orderModel = $order->order()->first();
                if (! $orderModel) {
                    return false;
                }

                $orderModel->loadMissing('fulfillment');
                $targetLegs = $this->courierTaskOrchestratorService->targetLegsForCurrentPhase($orderModel->fulfillment);
                $tasks = $this->courierTaskOrchestratorService->ensureTasksForOrder($orderModel)
                    ->filter(fn ($task) => $task->courier_id === null && $task->status_code === 'assigned');
                if ($targetLegs !== []) {
                    $tasks = $tasks->whereIn('leg', $targetLegs);
                }
                if (! $this->allSellerPickupTasksReadyForCourier($tasks, $orderModel)) {
                    return false;
                }

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
                $soldOrder = $order->order()->first();
                if (
                    $order->status_code === CourierOrderStatusCode::PAYMENT_PENDING->value
                    && $soldOrder
                    && PaymentStatusCode::fromLegacy($soldOrder->payment_status_code ?? $soldOrder->paymentStatus)->value === PaymentStatusCode::PAID->value
                ) {
                    $order->status = CourierOrderStatusCode::PENDING->legacy();
                    $order->status_code = CourierOrderStatusCode::PENDING->value;
                    $order->save();
                }
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
            ->values();

        Log::info('Courier available orders fetched', [
            'courier_id' => $courier->id,
            'count' => $orders->count(),
            'order_ids' => $orders->pluck('order_id')->values()->all(),
            'statuses' => $orders->pluck('status')->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    public function showOrder(Request $request, $id)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }
        $show = CourierOrder::where('order_id', $id)
            ->where('courier_id', $courier->id)
            ->with([
                'paymentStatus',
                'items.product.seller',
                'items.orderStatus',
                'items.sellerLocation.workdays',
                'customer.location',
            ])
            ->first();

        if (! $show) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.order_not_found'),
            ], 404);
        }

        $show = $this->hydrateCourierOrderItems($show, $courier->id);
        if ($show->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.order_not_found'),
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
            'data' => $show,
        ], 200);
    }

    public function toCustomer(Request $request, $qr)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }
        $orderCustomer = $this->resolveCustomerOrderByQr($qr, $courier->id);
        if (! $orderCustomer) {
            return response()->json(['success' => false, 'message' => __('courier_api.order_invalid_qr')], 404);
        }
        $order = CourierOrder::where('order_id', $orderCustomer->id)
            ->where('status', 'in_delivery')
            ->where('courier_id', $courier->id)
            ->first();
        if (! $order) {
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
                'success' => true,
                'message' => __('courier_api.order_delivered'),
                'order_id' => $order->order_id,
                'courier_bonus' => (int) $order->courierBonus,
                'courierPrice' => (int) $order->courierPrice,
            ], 200);
        } catch (\Throwable $th) {
            $code = $th instanceof \RuntimeException ? 422 : 500;

            return response()->json(['success' => false, 'message' => 'Xatolik: '.$th->getMessage()], $code);
        }
    }

    public function toHub(Request $request, string $qr)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }

        $payload = $this->qrTokenService->parseHubHandoffToken($qr);
        if (! $payload || (int) $payload['courier_id'] !== (int) $courier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Hub QR kodi noto‘g‘ri, eskirgan yoki boshqa kuryerga tegishli.',
            ], 422);
        }

        try {
            $fulfillment = DB::transaction(function () use ($payload, $courier) {
                $locked = OrderFulfillment::query()
                    ->whereKey((int) $payload['fulfillment_id'])
                    ->where('hub_id', (int) $payload['hub_id'])
                    ->where('order_id', (int) $payload['order_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->status_code !== FulfillmentStatusCode::PICKED_FROM_SELLER->value) {
                    throw new \RuntimeException('Buyurtma hubga topshirish bosqichida emas.');
                }

                $hasTask = CourierTask::query()
                    ->where('fulfillment_id', $locked->id)
                    ->where('order_id', $locked->order_id)
                    ->where('courier_id', $courier->id)
                    ->where('leg', 'first_mile')
                    ->whereNotIn('status_code', [
                        CourierTaskStatusCode::COMPLETED->value,
                        CourierTaskStatusCode::FAILED->value,
                        CourierTaskStatusCode::CANCELLED->value,
                    ])
                    ->exists();

                if (! $hasTask) {
                    throw new \RuntimeException('Sizga tegishli faol hub missiyasi topilmadi.');
                }

                $meta = $locked->meta ?? [];
                $timeline = collect($meta['timeline'] ?? [])
                    ->filter(fn ($row) => is_array($row))
                    ->values()
                    ->all();
                $timeline[] = [
                    'code' => 'arrived_at_hub',
                    'title' => 'Kuryer QR orqali hubga topshirdi',
                    'at' => now()->toIso8601String(),
                    'actor' => [
                        'id' => $courier->id,
                        'name' => trim($courier->first_name.' '.$courier->last_name),
                        'role' => 'courier',
                    ],
                ];
                $meta['timeline'] = $timeline;

                $locked->status_code = FulfillmentStatusCode::ARRIVED_AT_HUB->value;
                $locked->arrived_at_hub_at ??= now();
                $locked->meta = $meta;
                $locked->save();

                return $locked->fresh();
            });

            $this->courierTaskOrchestratorService->markArrivedAtHub($fulfillment);
            $this->qrTokenService->forgetHubHandoffCode($qr);

            return response()->json([
                'success' => true,
                'message' => 'Buyurtma hubga muvaffaqiyatli topshirildi.',
                'order_id' => (int) $fulfillment->order_id,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception instanceof \RuntimeException ? 422 : 500);
        }
    }

    public function confirmOrder(Request $request, $id)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json(['success' => false, 'message' => __('courier_api.unauthorized')], 401);
        }

        if (! $courier->is_online) {
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
                        'message' => 'Sizda faol buyurtmalar soni 3 taga yetgan. Avval ulardan birini yakunlang.',
                    ], 422);
                }

                $sold = Sold::where('id', $id)
                    ->lockForUpdate()
                    ->first();

                // Lock rows to prevent race condition. Card-paid orders can
                // briefly remain in courier payment_pending if the payment
                // callback and courier feed race each other.
                $order = CourierOrder::where('order_id', $id)
                    ->whereNull('courier_id')
                    ->where(function ($query) use ($sold) {
                        $query->where('status_code', CourierOrderStatusCode::PENDING->value)
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status_code')
                                    ->where('status', CourierOrderStatusCode::PENDING->legacy());
                            });

                        if (
                            $sold
                            && PaymentStatusCode::fromLegacy($sold->payment_status_code ?? $sold->paymentStatus)->value === PaymentStatusCode::PAID->value
                        ) {
                            $query->orWhere('status_code', CourierOrderStatusCode::PAYMENT_PENDING->value)
                                ->orWhere(function ($fallback) {
                                    $fallback->whereNull('status_code')
                                        ->where('status', CourierOrderStatusCode::PAYMENT_PENDING->legacy());
                                });
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $order || ! $sold) {
                    return response()->json([
                        'success' => false,
                        'message' => __('courier_api.order_already_taken'),
                    ], 404);
                }

                if (
                    $order->status_code === CourierOrderStatusCode::PAYMENT_PENDING->value
                    && PaymentStatusCode::fromLegacy($sold->payment_status_code ?? $sold->paymentStatus)->value === PaymentStatusCode::PAID->value
                ) {
                    $order->status = CourierOrderStatusCode::PENDING->legacy();
                    $order->status_code = CourierOrderStatusCode::PENDING->value;
                }

                $targetLegs = $this->courierTaskOrchestratorService->targetLegsForCurrentPhase($sold->fulfillment);
                $availableTasks = $this->courierTaskOrchestratorService->ensureTasksForOrder($sold)
                    ->filter(fn (CourierTask $task) => $task->status_code === CourierTaskStatusCode::ASSIGNED->value && $task->courier_id === null)
                    ->when($targetLegs !== [], fn ($tasks) => $tasks->whereIn('leg', $targetLegs))
                    ->values();

                if (! $this->allSellerPickupTasksReadyForCourier($availableTasks, $sold)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Multi buyurtmadagi do‘konlarning hammasi hali tayyor emas. Hamma do‘kon qabul qilgandan yoki 30 daqiqa tugagandan keyin olinadi.',
                    ], 422);
                }

                $eligibleTaskIds = $availableTasks
                    ->pluck('id')
                    ->values()
                    ->all();

                if ($eligibleTaskIds === []) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Do‘kon buyurtmani hali qabul qilmagan. 30 daqiqadan keyin yoki do‘kon qabul qilgandan so‘ng olinadi.',
                    ], 422);
                }

                $acceptedTasks = $this->courierTaskOrchestratorService->acceptAvailableTasksForCourier($sold, $courier, $eligibleTaskIds);
                $taskBonus = (int) $acceptedTasks->sum('bonus_amount');
                $taskBasePayout = (int) $acceptedTasks->sum(
                    fn (CourierTask $task) => max(0, (int) $task->fee_amount - (int) $task->bonus_amount)
                );

                $sold->courier_id = $courier->id;
                $sold->courierName = $courier->first_name.' '.$courier->last_name;
                $sold->status = OrderStatusCode::PACKING->legacy();
                $sold->status_code = OrderStatusCode::PACKING->value;
                $sold->save();

                $order->courier_id = $courier->id;
                $order->status = CourierOrderStatusCode::IN_DELIVERY->legacy();
                $order->status_code = CourierOrderStatusCode::IN_DELIVERY->value;
                $order->courierPrice = $taskBasePayout;
                $order->courierBonus = $taskBonus;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => __('courier_api.order_confirmed'),
                    'order_id' => $order->order_id,
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

            return response()->json(['success' => false, 'message' => 'Xatolik: '.$th->getMessage()], $code);
        }
    }

    public function myOrders(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json(['success' => false, 'message' => __('courier_api.unauthorized')], 401);
        }
        $orders = CourierOrder::where('courier_id', $courier->id)
            ->with([
                'paymentStatus',
                'items.product.seller',
                'items.orderStatus',
                'items.sellerLocation.workdays',
                'customer.location',
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
            'data' => $orders,
        ], 200);
    }

    public function orderHistory(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }

        $perPage = min(30, max(10, (int) $request->integer('per_page', 20)));
        $terminalCodes = [
            CourierOrderStatusCode::DELIVERED->value,
            CourierOrderStatusCode::CUSTOMER_RECEIVED->value,
            CourierOrderStatusCode::CANCELLED->value,
            CourierOrderStatusCode::RETURNED->value,
        ];
        $terminalLegacy = [
            CourierOrderStatusCode::DELIVERED->legacy(),
            CourierOrderStatusCode::CUSTOMER_RECEIVED->legacy(),
            CourierOrderStatusCode::CANCELLED->legacy(),
            CourierOrderStatusCode::RETURNED->legacy(),
            'cancelled',
        ];

        $orders = CourierOrder::query()
            ->where('courier_id', $courier->id)
            ->where(function ($query) use ($terminalCodes, $terminalLegacy) {
                $query->whereIn('status_code', $terminalCodes)
                    ->orWhere(function ($legacy) use ($terminalLegacy) {
                        $legacy->whereNull('status_code')
                            ->whereIn('status', $terminalLegacy);
                    });
            })
            ->with(['order.fulfillment.hub', 'order.courierTasks'])
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        $orders->getCollection()->transform(function (CourierOrder $courierOrder) use ($courier) {
            $soldOrder = $courierOrder->order;
            $taskSummary = $this->buildTaskSummary($soldOrder, $courier->id);
            $status = CourierOrderStatusCode::fromLegacy(
                $courierOrder->status_code ?: $courierOrder->status
            )->value;
            $dropoff = $taskSummary['dropoff_address'];
            $address = is_array($dropoff)
                ? ($dropoff['fullAddress'] ?? $dropoff['address'] ?? null)
                : null;

            if (! $address && $soldOrder) {
                $snapshot = $soldOrder->address;
                if (is_array($snapshot)) {
                    $address = $snapshot['fullAddress']
                        ?? $snapshot['address']
                        ?? $snapshot['address_line']
                        ?? null;
                } elseif (is_string($snapshot)) {
                    $address = $snapshot;
                }
            }

            $calculatedPayout = (int) ($taskSummary['task_fee_amount'] ?? 0);
            if ($calculatedPayout <= 0) {
                $calculatedPayout = max(
                    0,
                    (int) ($courierOrder->courierPrice ?? 0)
                        + (int) ($courierOrder->courierBonus ?? 0)
                );
            }
            $payout = $courierOrder->settled_amount !== null
                ? max(0, (int) $courierOrder->settled_amount)
                : $calculatedPayout;

            return [
                'id' => (int) $courierOrder->id,
                'order_id' => (int) $courierOrder->order_id,
                'status' => $status,
                'delivered_address' => $address,
                'payout' => $payout,
                'distance_km' => round((float) ($taskSummary['distance_km'] ?? 0), 2),
                'payment_type' => ($taskSummary['is_cod'] ?? false) ? 'cash' : 'card',
                'completed_at' => optional(
                    $courierOrder->settled_at ?: $courierOrder->updated_at
                )->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'has_more' => $orders->hasMorePages(),
            ],
        ]);
    }

    public function reports(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (! $courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }

        $days = (int) $request->integer('days', 30);
        if (! in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $completedQuery = CourierTask::query()
            ->where('courier_id', $courier->id)
            ->where('status_code', CourierTaskStatusCode::COMPLETED->value)
            ->whereBetween('completed_at', [$start, $end]);

        $totals = (clone $completedQuery)
            ->selectRaw('COUNT(*) as deliveries')
            ->selectRaw('COUNT(DISTINCT order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as gross_earnings')
            ->selectRaw('COALESCE(SUM(bonus_amount), 0) as bonus_earnings')
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_cod = 1 THEN 1 ELSE 0 END), 0) as cash_deliveries')
            ->first();

        $failedDeliveries = CourierTask::query()
            ->where('courier_id', $courier->id)
            ->whereIn('status_code', [
                CourierTaskStatusCode::FAILED->value,
                CourierTaskStatusCode::CANCELLED->value,
            ])
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $penalties = (int) CourierTransaction::query()
            ->where('courier_id', $courier->id)
            ->where('category', 'penalty')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $dailyRows = (clone $completedQuery)
            ->selectRaw('DATE(completed_at) as report_date')
            ->selectRaw('COUNT(*) as deliveries')
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as earnings')
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('report_date')
            ->get()
            ->keyBy('report_date');

        $daily = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyRows) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();
            $row = $dailyRows->get($key);

            return [
                'date' => $key,
                'deliveries' => (int) ($row->deliveries ?? 0),
                'earnings' => (int) ($row->earnings ?? 0),
                'distance_km' => round((float) ($row->distance_km ?? 0), 2),
            ];
        })->values();

        $deliveries = (int) ($totals->deliveries ?? 0);
        $ordersCount = (int) ($totals->orders_count ?? 0);
        $grossEarnings = (int) ($totals->gross_earnings ?? 0);
        $bonusEarnings = (int) ($totals->bonus_earnings ?? 0);
        $baseEarnings = max(0, $grossEarnings - $bonusEarnings);
        $distanceKm = round((float) ($totals->distance_km ?? 0), 2);
        $cashDeliveries = (int) ($totals->cash_deliveries ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'balance' => (int) $courier->balance,
                'total_withdrawn' => (int) $courier->total_withdrawal,
                'deliveries' => $deliveries,
                'orders_count' => $ordersCount,
                'failed_deliveries' => $failedDeliveries,
                'gross_earnings' => $grossEarnings,
                'base_earnings' => $baseEarnings,
                'bonus_earnings' => $bonusEarnings,
                'penalties' => $penalties,
                'net_earnings' => $grossEarnings - $penalties,
                'distance_km' => $distanceKm,
                'average_earning' => $deliveries > 0
                    ? (int) round($grossEarnings / $deliveries)
                    : 0,
                'average_distance_km' => $deliveries > 0
                    ? round($distanceKm / $deliveries, 2)
                    : 0,
                'cash_deliveries' => $cashDeliveries,
                'card_deliveries' => max(0, $deliveries - $cashDeliveries),
                'daily' => $daily,
            ],
        ]);
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
            $item->seller_location_today_work_time_state = $schedule['state'];
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
        if (! in_array($order->task_leg, ['first_mile', 'direct_delivery'], true)) {
            return false;
        }

        return $order->items
            ->filter(fn ($item) => $item->sellerLocation !== null)
            ->contains(fn ($item) => $this->locationScheduleState($item->sellerLocation)['is_open'] === false);
    }

    private function sellerPickupTaskReadyForCourier(CourierTask $task, Sold $order): bool
    {
        if (! in_array($task->leg, [CourierTaskLeg::FIRST_MILE->value, CourierTaskLeg::DIRECT_DELIVERY->value], true)) {
            return true;
        }

        $sellerId = (int) ($task->seller_id ?? 0);
        if ($sellerId <= 0) {
            return true;
        }

        $sellerOrder = SellerOrder::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $sellerId)
            ->first(['id', 'status', 'status_code', 'created_at']);

        if (! $sellerOrder) {
            return false;
        }

        $status = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?: $sellerOrder->status);
        if ($status === SellerOrderStatusCode::CANCELLED || $status === SellerOrderStatusCode::PAYMENT_PENDING) {
            return false;
        }

        if (! $this->sellerOrderHasCourierVisibleItems($sellerOrder)) {
            return false;
        }

        if (in_array($status, [SellerOrderStatusCode::ACCEPTED, SellerOrderStatusCode::HANDED_TO_COURIER], true)) {
            return true;
        }

        return $sellerOrder->created_at
            ? $sellerOrder->created_at->lte(now()->subMinutes(30))
            : false;
    }

    private function allSellerPickupTasksReadyForCourier(\Illuminate\Support\Collection $tasks, Sold $order): bool
    {
        if ($tasks->isEmpty()) {
            return false;
        }

        foreach ($tasks as $task) {
            if (! $this->sellerPickupTaskReadyForCourier($task, $order)) {
                return false;
            }
        }

        return true;
    }

    private function sellerOrderHasCourierVisibleItems(SellerOrder $sellerOrder): bool
    {
        return SellerOrderItem::query()
            ->where('order_id', $sellerOrder->id)
            ->where('type', '!=', 'gift')
            ->whereNull('cancelled_at')
            ->where(function ($query) {
                $query->whereNull('refund_status')
                    ->orWhere('refund_status', '!=', 'cancel_pending');
            })
            ->exists();
    }

    private function locationScheduleState(?SellerLocation $location): array
    {
        if (! $location) {
            return [
                'is_open' => true,
                'open_time' => null,
                'close_time' => null,
                'label' => null,
                'state' => 'unknown',
            ];
        }

        $now = Carbon::now(config('app.timezone', 'Asia/Tashkent'));
        $dayOfWeek = strtolower($now->format('l'));
        $workdays = $location->relationLoaded('workdays')
            ? $location->workdays
            : $location->workdays()->get();

        if ($workdays->isEmpty()) {
            return [
                'is_open' => true,
                'open_time' => null,
                'close_time' => null,
                'label' => "Ish vaqti ko'rsatilmagan",
                'state' => 'not_configured',
            ];
        }

        $previousDay = strtolower($now->copy()->subDay()->format('l'));
        $previousWorkday = $workdays->firstWhere('day_of_week', $previousDay);
        if ($previousWorkday) {
            $previousInterval = $this->locationWorkdayInterval($previousWorkday, $now->copy()->subDay());
            if (
                $previousInterval['overnight'] &&
                $now->betweenIncluded($previousInterval['open'], $previousInterval['close'])
            ) {
                return [
                    'is_open' => true,
                    'open_time' => $previousInterval['open_time'],
                    'close_time' => $previousInterval['close_time'],
                    'label' => "Kecha {$previousInterval['open_time']} - bugun {$previousInterval['close_time']} gacha ishlaydi",
                    'state' => 'open_overnight',
                ];
            }
        }

        $workday = $workdays->firstWhere('day_of_week', $dayOfWeek);
        if (! $workday) {
            return [
                'is_open' => false,
                'open_time' => null,
                'close_time' => null,
                'label' => 'Bugun ishlamaydi',
                'state' => 'closed_today',
            ];
        }

        $interval = $this->locationWorkdayInterval($workday, $now);
        $isOpen = $now->betweenIncluded($interval['open'], $interval['close']);
        $state = $isOpen
            ? 'open'
            : ($now->lessThan($interval['open']) ? 'opens_later' : 'closed_after_hours');
        $label = $state === 'closed_after_hours'
            ? "Bugun {$interval['open_time']} - {$interval['close_time']} gacha ishlagan"
            : "Bugun {$interval['open_time']} - {$interval['close_time']} gacha ishlaydi";

        return [
            'is_open' => $isOpen,
            'open_time' => $interval['open_time'],
            'close_time' => $interval['close_time'],
            'label' => $label,
            'state' => $state,
        ];
    }

    private function locationWorkdayInterval(mixed $workday, Carbon $date): array
    {
        $timezone = config('app.timezone', 'Asia/Tashkent');
        $open = Carbon::parse($workday->open_time, $timezone)
            ->setDate($date->year, $date->month, $date->day);
        $close = Carbon::parse($workday->close_time, $timezone)
            ->setDate($date->year, $date->month, $date->day);
        $overnight = $close->lessThanOrEqualTo($open);
        if ($overnight) {
            $close->addDay();
        }

        return [
            'open' => $open,
            'close' => $close,
            'open_time' => $open->format('H:i'),
            'close_time' => $close->format('H:i'),
            'overnight' => $overnight,
        ];
    }

    private function filterCancelledSellerItems(CourierOrder $order, \Illuminate\Support\Collection $items): \Illuminate\Support\Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $cancelledSellerIds = SellerOrder::query()
            ->where('order_id', $order->order_id)
            ->where(function ($query) {
                $query->where('status_code', SellerOrderStatusCode::CANCELLED->value)
                    ->orWhere('status', SellerOrderStatusCode::CANCELLED->legacy());
            })
            ->pluck('seller_id')
            ->map(fn ($sellerId) => (int) $sellerId)
            ->unique()
            ->values()
            ->all();

        $sellerItemRows = SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_orders.order_id', $order->order_id)
            ->whereNull('seller_order_items.cancelled_at')
            ->get([
                'seller_orders.seller_id as seller_id',
                'seller_order_items.product_id',
                'seller_order_items.variant_id',
                'seller_order_items.type',
                'seller_order_items.quantity',
                'seller_order_items.price',
                'seller_order_items.refund_status',
            ]);

        $activeSellerItems = $sellerItemRows
            ->filter(fn ($row) => ($row->refund_status ?? null) !== 'cancel_pending')
            ->values();

        if ($sellerItemRows->isEmpty()) {
            return $items
                ->reject(fn ($item) => in_array((int) ($item->seller_id ?? 0), $cancelledSellerIds, true))
                ->values();
        }

        $remaining = [];
        foreach ($activeSellerItems as $sellerItem) {
            $key = $this->courierItemMatchKey(
                (int) $sellerItem->seller_id,
                (int) $sellerItem->product_id,
                $sellerItem->variant_id !== null ? (int) $sellerItem->variant_id : null,
                (string) $sellerItem->type,
            );
            $remaining[$key] = ($remaining[$key] ?? 0) + 1;
        }

        return $items->filter(function ($item) use (&$remaining, $cancelledSellerIds) {
            $sellerId = (int) ($item->seller_id ?? 0);
            if (in_array($sellerId, $cancelledSellerIds, true)) {
                return false;
            }

            $key = $this->courierItemMatchKey(
                $sellerId,
                (int) ($item->product_id ?? 0),
                $item->variant_id !== null ? (int) $item->variant_id : null,
                (string) ($item->type ?? ''),
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
        string $type
    ): string {
        return implode(':', [
            $sellerId,
            $productId,
            $variantId ?? 0,
            $type,
        ]);
    }

    private function buildPickupQrForItem(mixed $item, ?int $courierId, ?int $soldOrderId = null): ?string
    {
        $sellerId = (int) ($item->product?->seller_id ?? 0);
        $orderId = (int) ($soldOrderId ?? $item->order_id ?? 0);

        if ($sellerId <= 0 || $orderId <= 0 || ! $courierId) {
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
        if (! $order) {
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

        $order->loadMissing('fulfillment.hub');
        $tasks = $this->courierTaskOrchestratorService->ensureTasksForOrder($order);
        if ($courierId) {
            $tasks = $tasks->where('courier_id', $courierId);
        }

        $activeTask = $this->pickPreferredTask($tasks, $courierId, $order->fulfillment);

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

    private function pickPreferredTask(
        \Illuminate\Support\Collection $tasks,
        ?int $courierId,
        ?OrderFulfillment $fulfillment
    ): ?CourierTask {
        if ($tasks->isEmpty()) {
            return null;
        }

        $targetLegs = $this->courierTaskOrchestratorService->targetLegsForCurrentPhase($fulfillment);
        if ($targetLegs !== []) {
            $targetTasks = $tasks->whereIn('leg', $targetLegs);
            if ($targetTasks->isNotEmpty()) {
                $tasks = $targetTasks;
            }
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
            ->sort(function (CourierTask $a, CourierTask $b) use ($priority, $fulfillment) {
                $aPriority = $priority[$a->status_code] ?? 999;
                $bPriority = $priority[$b->status_code] ?? 999;

                if ($aPriority === $bPriority) {
                    $aLegPriority = $this->legPriority($a->leg, $fulfillment);
                    $bLegPriority = $this->legPriority($b->leg, $fulfillment);
                    if ($aLegPriority !== $bLegPriority) {
                        return $aLegPriority <=> $bLegPriority;
                    }

                    return (int) $b->id <=> (int) $a->id;
                }

                return $aPriority <=> $bPriority;
            })
            ->first();
    }

    private function legPriority(?string $leg, ?OrderFulfillment $fulfillment): int
    {
        if (! $fulfillment) {
            return 50;
        }

        return match ($fulfillment->fulfillment_mode) {
            FulfillmentMode::DIRECT_COURIER->value => $leg === CourierTaskLeg::DIRECT_DELIVERY->value ? 0 : 50,
            FulfillmentMode::POSTAL_ONLY_VIA_HUB->value => $leg === CourierTaskLeg::FIRST_MILE->value ? 0 : 50,
            FulfillmentMode::HUB_BASED->value => in_array($fulfillment->status_code, [
                FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
                FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            ], true)
                ? ($leg === CourierTaskLeg::LAST_MILE->value ? 0 : 50)
                : ($leg === CourierTaskLeg::FIRST_MILE->value ? 0 : 50),
            default => 50,
        };
    }
}
