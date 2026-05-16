<?php

namespace App\Http\Controllers\Api\Courier;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\User;
use App\Models\Seller;
use App\Models\Couriers;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\SellerOrder;
use App\Services\CourierBonusService;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Services\OrderRealtimeService;
use App\Services\QrTokenService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\CourierCashOnDeliveryCapacityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierOrderController extends Controller
{
    public function __construct(
        private readonly CourierBonusService $bonusService,
        private readonly OrderService $orderService,
        private readonly OrderRealtimeService $orderRealtimeService,
        private readonly QrTokenService $qrTokenService,
        private readonly OrderStatusPushService $orderStatusPushService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly CourierCashOnDeliveryCapacityService $courierCashOnDeliveryCapacityService,
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
                $taskSummary = $this->buildTaskSummary($order->order()->first(), null);
                $order->task_leg = $taskSummary['task_leg'];
                $order->fulfillment_mode = $taskSummary['fulfillment_mode'];
                $order->cash_collect_amount = $taskSummary['cash_collect_amount'];
                $order->is_cod = $taskSummary['is_cod'];
                $order->task_pickup_address = $taskSummary['pickup_address'];
                $order->task_dropoff_address = $taskSummary['dropoff_address'];
                $order->hub = $taskSummary['hub'];
                $order->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
                $this->bonusService->normalizeBonusState($order);
                return $this->hydrateCourierOrderItems($order, Auth::guard('courier')->id());
            });

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
        $taskSummary = $this->buildTaskSummary($show->order()->first(), $courier->id);
        $show->task_leg = $taskSummary['task_leg'];
        $show->fulfillment_mode = $taskSummary['fulfillment_mode'];
        $show->cash_collect_amount = $taskSummary['cash_collect_amount'];
        $show->is_cod = $taskSummary['is_cod'];
        $show->task_pickup_address = $taskSummary['pickup_address'];
        $show->task_dropoff_address = $taskSummary['dropoff_address'];
        $show->hub = $taskSummary['hub'];
        $show->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
        $this->bonusService->normalizeBonusState($show);

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
            $finalBonus = 0;
            DB::transaction(function () use ($order, $orderCustomer, $courier, &$finalBonus) {
                $previousStatus = (string) $orderCustomer->status;
                // Phase 3: SLA penaltyni hisoblab final_bonus ni yozamiz.
                // computeFinalBonus() bonusni courierBonus va final_bonus ustunlariga
                // yozadi va sla_deadline ni mijoz pause bilan to'g'rilaydi.
                $finalBonus = $this->bonusService->computeFinalBonus($order);

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
                'final_bonus'  => $finalBonus,
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
                    ->whereNull('courier_id')
                    ->lockForUpdate()
                    ->first();

                if (!$order || !$sold) {
                    return response()->json([
                        'success' => false,
                        'message' => __('courier_api.order_already_taken'),
                    ], 404);
                }

                $this->courierTaskOrchestratorService->acceptAvailableTasksForCourier($sold, $courier);

                $sold->courier_id   = $courier->id;
                $sold->courierName  = $courier->first_name . ' ' . $courier->last_name;
                $sold->status       = OrderStatusCode::PACKING->legacy();
                $sold->status_code  = OrderStatusCode::PACKING->value;
                $sold->save();

                $order->courier_id = $courier->id;
                $order->status     = CourierOrderStatusCode::IN_DELIVERY->legacy();
                $order->status_code = CourierOrderStatusCode::IN_DELIVERY->value;
                $order->save();

                // Phase 3: pickup_bonus ni qulflash + SLA boshlash.
                // Bu yerda asosan locked_bonus, picked_up_at, sla_deadline o'rnatadi.
                $this->bonusService->lockBonusOnAccept($order);

                return response()->json([
                    'success'      => true,
                    'message'      => __('courier_api.order_confirmed'),
                    'order_id'     => $order->order_id,
                    'locked_bonus' => (int) $order->locked_bonus,
                    'sla_deadline' => optional($order->sla_deadline)->toIso8601String(),
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
                    WHEN status = 'delivered' THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($order) use ($courier) {
                $taskSummary = $this->buildTaskSummary($order->order()->first(), $courier->id);
                $order->task_leg = $taskSummary['task_leg'];
                $order->fulfillment_mode = $taskSummary['fulfillment_mode'];
                $order->cash_collect_amount = $taskSummary['cash_collect_amount'];
                $order->is_cod = $taskSummary['is_cod'];
                $order->task_pickup_address = $taskSummary['pickup_address'];
                $order->task_dropoff_address = $taskSummary['dropoff_address'];
                $order->hub = $taskSummary['hub'];
                $order->available_collateral = $this->courierCashOnDeliveryCapacityService->availableCollateral($courier);
                $this->bonusService->normalizeBonusState($order);
                return $this->hydrateCourierOrderItems($order, $order->courier_id);
            });
        return response()->json([
            'success' => true,
            'data'    => $orders,
        ], 200);
    }

    /**
     * Phase 3 — "Mijoz javob bermayapti" tugmasi.
     *
     * Kuryer in_delivery statusdagi buyurtma uchun bu endpointni chaqirib SLA
     * timerini pauza qilishi/davom ettirishi mumkin. Pauza paytidagi vaqt
     * sla_deadline ni oldinga suradi, shuning uchun kuryer kechikgan deb
     * hisoblanmaydi. Tugma toggle ishlaydi: birinchi bosishda pauza, ikkinchi
     * bosishda davom ettirish.
     */
    public function customerDelay(Request $request, $id)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.unauthorized'),
            ], 401);
        }

        $order = CourierOrder::where('order_id', $id)
            ->where('courier_id', $courier->id)
            ->where('status', 'in_delivery')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('courier_api.customer_delay_invalid'),
            ], 404);
        }

        $result = $this->bonusService->toggleCustomerDelay($order);

        if (($result['success'] ?? true) === false) {
            return response()->json([
                'success' => false,
                'paused' => false,
                'message' => $result['message'] ?? __('courier_api.customer_delay_limit_reached'),
                'total_delay_seconds' => $result['total_delay_seconds'] ?? (int) $order->total_delay_seconds,
                'customer_delay_count' => $result['customer_delay_count'] ?? (int) $order->customer_delay_count,
                'remaining_delay_seconds' => $result['remaining_delay_seconds'] ?? 0,
                'sla_deadline' => $result['sla_deadline'] ?? optional($order->sla_deadline)->toIso8601String(),
            ], 422);
        }

        $order->refresh();
        $this->orderRealtimeService->broadcastCourierOrderUpdated(
            $order,
            $result['paused'] ? 'courier_order.customer_delay_started' : 'courier_order.customer_delay_resumed'
        );

        return response()->json([
            'success'             => true,
            'paused'              => $result['paused'],
            'message'             => $result['paused']
                ? __('courier_api.customer_delay_marked')
                : __('courier_api.customer_delay_resumed'),
            'total_delay_seconds' => $result['total_delay_seconds'],
            'customer_delay_count'=> $result['customer_delay_count'] ?? (int) $order->customer_delay_count,
            'remaining_delay_seconds' => $result['remaining_delay_seconds'] ?? 0,
            'sla_deadline'        => $result['sla_deadline'],
        ], 200);
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
            if ($item->product && $seller) {
                $item->product->setRelation('seller', $seller);
            }
            $item->setRelation('seller', $seller);
            $item->pickup_qr = $this->buildPickupQrForItem($item, $courierId, $order->order_id);
        }

        return $order;
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
            'hub' => $order->fulfillment?->hub?->only(['id', 'name', 'code', 'address', 'lat', 'lon']),
        ];
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
