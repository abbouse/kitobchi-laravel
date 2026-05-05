<?php

namespace App\Http\Controllers\Api\Courier;

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
use App\Services\OrderRealtimeService;
use App\Services\QrTokenService;
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
                $query->where('status', 'pending')
                    // Ba'zi eski yoki callbackdan keyin sync bo'lmay qolgan
                    // buyurtmalar `pay_process`da qolib ketgan bo'lishi mumkin.
                    // Agar underlying Sold allaqachon to'langan bo'lsa
                    // (`paymentStatus = 2`), kuryerga uni available sifatida
                    // ko'rsatamiz.
                    ->orWhere(function ($q) {
                        $q->where('status', 'pay_process')
                            ->whereHas('order', fn ($order) => $order->where('paymentStatus', 2));
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
            ->map(function ($order) {
                if ($order->status === 'pay_process' && (int) ($order->paymentStatus?->paymentStatus ?? 0) === 2) {
                    $order->status = 'pending';
                }
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
                // Phase 3: SLA penaltyni hisoblab final_bonus ni yozamiz.
                // computeFinalBonus() bonusni courierBonus va final_bonus ustunlariga
                // yozadi va sla_deadline ni mijoz pause bilan to'g'rilaydi.
                $finalBonus = $this->bonusService->computeFinalBonus($order);

                $order->status = 'delivered';
                $order->save();

                $orderCustomer->status = 'C';
                $orderCustomer->save();

                // courier balansiga: asosiy yetkazib berish narxi + yakuniy bonus.
                $courier->balance += ((int) $order->courierPrice + $finalBonus);
                $courier->save();
            });

            $order->refresh();
            $this->orderRealtimeService->broadcastCourierOrderUpdated($order, 'courier_order.delivered');
            $this->orderService->processCashbackAfterOrderMutation($orderCustomer, $orderCustomer->user()->first());

            return response()->json([
                'success'      => true,
                'message'      => __('courier_api.order_delivered'),
                'order_id'     => $order->order_id,
                'final_bonus'  => $finalBonus,
                'courierPrice' => (int) $order->courierPrice,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Xatolik: ' . $th->getMessage()], 500);
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

                $sold->courier_id   = $courier->id;
                $sold->courierName  = $courier->first_name . ' ' . $courier->last_name;
                $sold->status       = 'P';
                $sold->save();

                $order->courier_id = $courier->id;
                $order->status     = 'in_delivery';
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
            return response()->json(['success' => false, 'message' => 'Xatolik: ' . $th->getMessage()], 500);
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
            ->latest()
            ->get()
            ->map(function ($order) {
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

        foreach ($items as $item) {
            if ($item->seller_id) {
                $item->setRelation(
                    'orderStatus',
                    $sellerStatuses->get((string) $item->seller_id)
                );
            }
            if ($item->product && $item->product->seller && $item->sellerLocation) {
                $item->product->seller->location = $item->sellerLocation;
            }
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
            return Sold::where('status', 'B')
                ->where('id', $signed['sold_id'])
                ->where('user_id', $signed['user_id'])
                ->where('courier_id', $courierId)
                ->first();
        }

        return Sold::where('status', 'B')
            ->where('qr', $qr)
            ->where('courier_id', $courierId)
            ->first();
    }
}
