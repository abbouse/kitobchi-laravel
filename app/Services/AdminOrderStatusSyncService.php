<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\CourierOrder;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;

class AdminOrderStatusSyncService
{
    public const SELLER_STATUSES = [
        'payment_pending' => ['label' => "To'lov jarayonida", 'badge' => 'badge-muted'],
        'new' => ['label' => 'Yangi buyurtma', 'badge' => 'badge-info'],
        'accepted' => ['label' => "Do'kon qabul qildi", 'badge' => 'badge-warning'],
        'handed_to_courier' => ['label' => "Kuryerga berildi", 'badge' => 'badge-success'],
        'cancelled' => ['label' => 'Bekor qilindi', 'badge' => 'badge-danger'],
    ];

    public const COURIER_STATUSES = [
        'payment_pending' => ['label' => "To'lov jarayonida", 'badge' => 'badge-muted'],
        'pending' => ['label' => 'Kutilmoqda', 'badge' => 'badge-info'],
        'in_delivery' => ['label' => "Yo'lda", 'badge' => 'badge-warning'],
        'delivered' => ['label' => 'Yetib bordi', 'badge' => 'badge-success'],
        'customer_received' => ['label' => 'Mijoz qabul qildi', 'badge' => 'badge-success'],
        'cancelled' => ['label' => 'Bekor qilindi', 'badge' => 'badge-danger'],
        'returned' => ['label' => 'Qaytgan', 'badge' => 'badge-danger'],
    ];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderStatusPushService $orderStatusPushService,
    ) {}

    public function updateMainOrder(Sold $order, string $status): void
    {
        DB::transaction(function () use ($order, $status) {
            $previousStatus = (string) $order->status;

            if ($status === 'F') {
                $this->orderService->cancelOrder($order, strict: false);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));
                return;
            }

            $statusCode = OrderStatusCode::fromLegacy($status);

            if (in_array($statusCode, [OrderStatusCode::DELIVERED, OrderStatusCode::CUSTOMER_RECEIVED], true)
                && $order->payment_status_code !== PaymentStatusCode::PAID->value) {
                $order->paymentStatus = PaymentStatusCode::PAID->legacy();
                $order->payment_status_code = PaymentStatusCode::PAID->value;
            }

            $order->status = $statusCode->legacy();
            $order->status_code = $statusCode->value;
            $this->syncCompletionState($order);
            $order->save();
            $this->syncFulfillmentFromMainStatus($order, $statusCode->value);

            $sellerStatus = $this->mapMainToSeller($statusCode->value, $order->payment_status_code);
            SellerOrder::where('order_id', $order->id)->update([
                'status' => $sellerStatus->legacy(),
                'status_code' => $sellerStatus->value,
                'updated_at' => now(),
            ]);

            $courierStatus = $this->resolveCourierStatusForMainOrder($order, $statusCode->value);
            CourierOrder::where('order_id', $order->id)->update([
                'status' => $courierStatus->legacy(),
                'status_code' => $courierStatus->value,
                'updated_at' => now(),
            ]);

            if ($order->payment_status_code === PaymentStatusCode::PAID->value) {
                $this->orderService->processCashbackAfterOrderMutation($order, $order->user()->first());
            }

            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, $statusCode->legacy()));
        });
    }

    public function updateSellerOrder(SellerOrder $sellerOrder, int|string $status): void
    {
        DB::transaction(function () use ($sellerOrder, $status) {
            $statusCode = SellerOrderStatusCode::fromLegacy($status);
            $sellerOrder->update([
                'status' => $statusCode->legacy(),
                'status_code' => $statusCode->value,
            ]);

            $order = $sellerOrder->order()->first();
            if (!$order) {
                return;
            }

            if ($statusCode === SellerOrderStatusCode::CANCELLED) {
                $previousStatus = (string) $order->status;
                $this->orderService->cancelOrder($order, strict: false);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));
                return;
            }

            $previousStatus = (string) $order->status;
            $order->status_code = match ($statusCode) {
                SellerOrderStatusCode::HANDED_TO_COURIER => OrderStatusCode::IN_DELIVERY->value,
                SellerOrderStatusCode::ACCEPTED => OrderStatusCode::PACKING->value,
                default => OrderStatusCode::PENDING->value,
            };
            $order->status = OrderStatusCode::from($order->status_code)->legacy();
            $this->syncCompletionState($order);
            $order->save();
            $this->syncFulfillmentFromSellerStatus($order, $statusCode);

            $courierStatus = $this->resolveCourierStatusForSellerUpdate($order, $statusCode);
            CourierOrder::where('order_id', $order->id)->update([
                'status' => $courierStatus->legacy(),
                'status_code' => $courierStatus->value,
                'updated_at' => now(),
            ]);

            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, (string) $order->status));
        });
    }

    public function updateCourierOrder(CourierOrder $courierOrder, string $status): void
    {
        DB::transaction(function () use ($courierOrder, $status) {
            $statusCode = CourierOrderStatusCode::fromLegacy($status);
            $courierOrder->update([
                'status' => $statusCode->legacy(),
                'status_code' => $statusCode->value,
            ]);

            $order = $courierOrder->order()->first();
            if (!$order) {
                return;
            }

            $previousStatus = (string) $order->status;

            if ($statusCode === CourierOrderStatusCode::CANCELLED) {
                $this->orderService->cancelOrder($order, strict: false);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));
                return;
            }

            $order->status_code = match ($statusCode) {
                CourierOrderStatusCode::DELIVERED => OrderStatusCode::DELIVERED->value,
                CourierOrderStatusCode::CUSTOMER_RECEIVED => OrderStatusCode::CUSTOMER_RECEIVED->value,
                CourierOrderStatusCode::IN_DELIVERY => OrderStatusCode::IN_DELIVERY->value,
                CourierOrderStatusCode::RETURNED => OrderStatusCode::RETURNED->value,
                default => OrderStatusCode::PENDING->value,
            };
            $order->status = OrderStatusCode::from($order->status_code)->legacy();

            if (in_array($statusCode, [CourierOrderStatusCode::DELIVERED, CourierOrderStatusCode::CUSTOMER_RECEIVED], true)
                && $order->payment_status_code !== PaymentStatusCode::PAID->value) {
                $order->paymentStatus = PaymentStatusCode::PAID->legacy();
                $order->payment_status_code = PaymentStatusCode::PAID->value;
            }

            $this->syncCompletionState($order);
            $order->save();
            $this->syncFulfillmentFromCourierStatus($order, $statusCode);

            SellerOrder::where('order_id', $order->id)->update([
                'status' => $this->mapCourierToSeller($statusCode->value)->legacy(),
                'status_code' => $this->mapCourierToSeller($statusCode->value)->value,
                'updated_at' => now(),
            ]);

            if ($order->payment_status_code === PaymentStatusCode::PAID->value) {
                $this->orderService->processCashbackAfterOrderMutation($order, $order->user()->first());
            }

            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, (string) $order->status));
        });
    }

    public function mapMainToSeller(string $status, int|string|null $paymentStatus = null): SellerOrderStatusCode
    {
        $statusCode = OrderStatusCode::fromLegacy($status)->value;
        $paymentCode = PaymentStatusCode::fromLegacy($paymentStatus)->value;

        if ($paymentCode === PaymentStatusCode::CARD_PENDING->value
            && in_array($statusCode, [OrderStatusCode::PENDING->value, OrderStatusCode::PACKING->value], true)) {
            return SellerOrderStatusCode::PAYMENT_PENDING;
        }

        return match ($statusCode) {
            'packing' => SellerOrderStatusCode::ACCEPTED,
            'in_delivery', 'delivered', 'customer_received', 'returned' => SellerOrderStatusCode::HANDED_TO_COURIER,
            'cancelled' => SellerOrderStatusCode::CANCELLED,
            default => SellerOrderStatusCode::NEW,
        };
    }

    public function mapMainToCourier(string $status, int|string|null $paymentStatus = null): CourierOrderStatusCode
    {
        $statusCode = OrderStatusCode::fromLegacy($status)->value;
        $paymentCode = PaymentStatusCode::fromLegacy($paymentStatus)->value;

        if ($paymentCode === PaymentStatusCode::CARD_PENDING->value
            && in_array($statusCode, [OrderStatusCode::PENDING->value, OrderStatusCode::PACKING->value], true)) {
            return CourierOrderStatusCode::PAYMENT_PENDING;
        }

        return match ($statusCode) {
            'in_delivery' => CourierOrderStatusCode::IN_DELIVERY,
            'delivered' => CourierOrderStatusCode::DELIVERED,
            'customer_received' => CourierOrderStatusCode::CUSTOMER_RECEIVED,
            'returned' => CourierOrderStatusCode::RETURNED,
            'cancelled' => CourierOrderStatusCode::CANCELLED,
            default => CourierOrderStatusCode::PENDING,
        };
    }

    public function mapSellerToCourier(int|string $status, int|string|null $paymentStatus = null): CourierOrderStatusCode
    {
        $statusCode = SellerOrderStatusCode::fromLegacy($status);
        $paymentCode = PaymentStatusCode::fromLegacy($paymentStatus)->value;

        return match ($statusCode) {
            SellerOrderStatusCode::HANDED_TO_COURIER => CourierOrderStatusCode::IN_DELIVERY,
            SellerOrderStatusCode::CANCELLED => CourierOrderStatusCode::CANCELLED,
            default => ($paymentCode === PaymentStatusCode::CARD_PENDING->value
                ? CourierOrderStatusCode::PAYMENT_PENDING
                : CourierOrderStatusCode::PENDING),
        };
    }

    private function resolveCourierStatusForMainOrder(Sold $order, string $status): CourierOrderStatusCode
    {
        $statusCode = OrderStatusCode::fromLegacy($status)->value;
        $paymentCode = PaymentStatusCode::fromLegacy($order->payment_status_code)->value;
        $hasCourier = (int) ($order->courier_id ?? 0) > 0;

        if ($statusCode === OrderStatusCode::PENDING->value) {
            return $paymentCode === PaymentStatusCode::CARD_PENDING->value
                ? CourierOrderStatusCode::PAYMENT_PENDING
                : CourierOrderStatusCode::PENDING;
        }

        if (!$hasCourier && in_array($statusCode, [
            OrderStatusCode::PACKING->value,
            OrderStatusCode::IN_DELIVERY->value,
        ], true)) {
            return CourierOrderStatusCode::CANCELLED;
        }

        return match ($statusCode) {
            OrderStatusCode::PACKING->value,
            OrderStatusCode::IN_DELIVERY->value => CourierOrderStatusCode::IN_DELIVERY,
            OrderStatusCode::DELIVERED->value => CourierOrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED->value => CourierOrderStatusCode::CUSTOMER_RECEIVED,
            OrderStatusCode::RETURNED->value => CourierOrderStatusCode::RETURNED,
            OrderStatusCode::CANCELLED->value => CourierOrderStatusCode::CANCELLED,
            default => CourierOrderStatusCode::PENDING,
        };
    }

    private function resolveCourierStatusForSellerUpdate(Sold $order, SellerOrderStatusCode $statusCode): CourierOrderStatusCode
    {
        $paymentCode = PaymentStatusCode::fromLegacy($order->payment_status_code)->value;
        $hasCourier = (int) ($order->courier_id ?? 0) > 0;

        return match ($statusCode) {
            SellerOrderStatusCode::HANDED_TO_COURIER => $hasCourier
                ? CourierOrderStatusCode::IN_DELIVERY
                : CourierOrderStatusCode::CANCELLED,
            SellerOrderStatusCode::ACCEPTED => $hasCourier
                ? CourierOrderStatusCode::IN_DELIVERY
                : CourierOrderStatusCode::CANCELLED,
            SellerOrderStatusCode::CANCELLED => CourierOrderStatusCode::CANCELLED,
            default => ($paymentCode === PaymentStatusCode::CARD_PENDING->value
                ? CourierOrderStatusCode::PAYMENT_PENDING
                : CourierOrderStatusCode::PENDING),
        };
    }

    public function mapCourierToSeller(string $status): SellerOrderStatusCode
    {
        return match (CourierOrderStatusCode::fromLegacy($status)) {
            CourierOrderStatusCode::DELIVERED,
            CourierOrderStatusCode::CUSTOMER_RECEIVED,
            CourierOrderStatusCode::IN_DELIVERY,
            CourierOrderStatusCode::RETURNED => SellerOrderStatusCode::HANDED_TO_COURIER,
            CourierOrderStatusCode::CANCELLED => SellerOrderStatusCode::CANCELLED,
            default => SellerOrderStatusCode::ACCEPTED,
        };
    }

    private function syncCompletionState(Sold $order): void
    {
        $isCompletedPaid = $order->isCompletedAndPaid();

        if ($isCompletedPaid) {
            $order->completed_at ??= now();
            return;
        }

        $order->completed_at = null;
    }

    private function syncFulfillmentFromMainStatus(Sold $order, string $status): void
    {
        $fulfillment = $order->fulfillment()->first();
        if (!$fulfillment) {
            return;
        }

        $fulfillment->status_code = match ($status) {
            OrderStatusCode::DELIVERED->value => FulfillmentStatusCode::DELIVERED->value,
            OrderStatusCode::CUSTOMER_RECEIVED->value => FulfillmentStatusCode::DELIVERED->value,
            OrderStatusCode::RETURNED->value => FulfillmentStatusCode::RETURNED->value,
            OrderStatusCode::CANCELLED->value => FulfillmentStatusCode::CANCELLED->value,
            OrderStatusCode::IN_DELIVERY->value => $fulfillment->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value
                ? FulfillmentStatusCode::OUT_FOR_DELIVERY->value
                : FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
            default => $fulfillment->status_code,
        };
        $fulfillment->save();
    }

    private function syncFulfillmentFromSellerStatus(Sold $order, SellerOrderStatusCode $statusCode): void
    {
        $fulfillment = $order->fulfillment()->first();
        if (!$fulfillment) {
            return;
        }

        $fulfillment->status_code = match ($statusCode) {
            SellerOrderStatusCode::ACCEPTED => FulfillmentStatusCode::AWAITING_SELLER_PREP->value,
            SellerOrderStatusCode::HANDED_TO_COURIER => $fulfillment->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value
                ? FulfillmentStatusCode::OUT_FOR_DELIVERY->value
                : FulfillmentStatusCode::PICKED_FROM_SELLER->value,
            SellerOrderStatusCode::CANCELLED => FulfillmentStatusCode::CANCELLED->value,
            default => $fulfillment->status_code,
        };
        $fulfillment->save();
    }

    private function syncFulfillmentFromCourierStatus(Sold $order, CourierOrderStatusCode $statusCode): void
    {
        $fulfillment = $order->fulfillment()->first();
        if (!$fulfillment) {
            return;
        }

        $fulfillment->status_code = match ($statusCode) {
            CourierOrderStatusCode::IN_DELIVERY => FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            CourierOrderStatusCode::DELIVERED => FulfillmentStatusCode::DELIVERED->value,
            CourierOrderStatusCode::CUSTOMER_RECEIVED => FulfillmentStatusCode::DELIVERED->value,
            CourierOrderStatusCode::RETURNED => FulfillmentStatusCode::RETURNED->value,
            CourierOrderStatusCode::CANCELLED => FulfillmentStatusCode::CANCELLED->value,
            default => $fulfillment->status_code,
        };
        $fulfillment->save();
    }
}
