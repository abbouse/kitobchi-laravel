<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\OrderFulfillment;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminOrderStatusSyncService
{
    public const SELLER_STATUSES = [
        'payment_pending' => ['label' => "To'lov jarayonida", 'badge' => 'badge-muted'],
        'new' => ['label' => 'Yangi buyurtma', 'badge' => 'badge-info'],
        'accepted' => ['label' => "Do'kon qabul qildi", 'badge' => 'badge-warning'],
        'handed_to_courier' => ['label' => 'Kuryerga berildi', 'badge' => 'badge-success'],
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
        private readonly PaylovOrderPaymentService $paylovOrderPaymentService,
        private readonly SellerOrderCancellationService $sellerOrderCancellationService,
    ) {}

    public function updateMainOrder(Sold $order, string $status, array $options = []): void
    {
        DB::transaction(function () use ($order, $status, $options) {
            $order = Sold::query()->lockForUpdate()->findOrFail($order->id);
            $statusCode = OrderStatusCode::fromLegacy($status);
            $currentStatusCode = OrderStatusCode::fromLegacy(
                $order->status_code ?? $order->status,
            );

            if ($currentStatusCode === $statusCode) {
                return;
            }

            if (! empty($options['forward_only'])
                && (
                    in_array($currentStatusCode, [
                        OrderStatusCode::CANCELLED,
                        OrderStatusCode::RETURNED,
                        OrderStatusCode::CUSTOMER_RECEIVED,
                    ], true)
                    || $this->rank($this->mainOrderFlow(), $statusCode->value)
                        <= $this->rank($this->mainOrderFlow(), $currentStatusCode->value)
                )) {
                return;
            }

            $previousStatus = (string) $order->status;
            $previousCompletedPaid = $order->isCompletedAndPaid();
            $this->guardMainTransition($order, $statusCode, $options);

            if ($statusCode === OrderStatusCode::CANCELLED) {
                $this->orderService->cancelOrder($order, strict: false);
                $this->syncFulfillmentCancellation($order);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));

                return;
            }

            if ($statusCode === OrderStatusCode::IN_DELIVERY) {
                $this->markActiveSellerOrdersHandedToCourier($order);
            }

            $this->chargeHeldPaymentForOperationalStatus($order, $statusCode, 'admin_main_status_update');
            $order->refresh();

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
            $this->updateActiveSellerOrders($order, $sellerStatus);

            $courierStatus = $this->resolveCourierStatusForMainOrder($order, $statusCode->value);
            CourierOrder::where('order_id', $order->id)->update([
                'status' => $courierStatus->legacy(),
                'status_code' => $courierStatus->value,
                'updated_at' => now(),
            ]);

            $this->syncCompletedOrderSideEffects($order, $previousCompletedPaid, 'main_status_update');

            $courierOrderId = (int) $order->id;
            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, $statusCode->legacy()));
            DB::afterCommit(fn () => app(CourierBroadcaster::class)->notifyPendingForOrder($courierOrderId));
        });
    }

    public function updateSellerOrder(SellerOrder $sellerOrder, int|string $status, array $options = []): void
    {
        DB::transaction(function () use ($sellerOrder, $status, $options) {
            $sellerOrder = SellerOrder::query()->lockForUpdate()->findOrFail($sellerOrder->id);
            $statusCode = SellerOrderStatusCode::fromLegacy($status);
            $this->guardSellerTransition($sellerOrder, $statusCode, $options);
            $sellerOrder->update([
                'status' => $statusCode->legacy(),
                'status_code' => $statusCode->value,
            ]);

            $order = $sellerOrder->order()->first();
            if (! $order) {
                return;
            }
            $previousCompletedPaid = $order->isCompletedAndPaid();

            if ($statusCode === SellerOrderStatusCode::CANCELLED) {
                $previousStatus = (string) $order->status;
                $this->orderService->cancelOrder($order, strict: false);
                $this->syncFulfillmentCancellation($order);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));

                return;
            }

            $previousStatus = (string) $order->status;
            $allActiveSellerOrdersDone = $this->allActiveSellerOrdersHandedToCourier($sellerOrder);
            $order->loadMissing('fulfillment');
            $isDirectCourier = $order->fulfillment?->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value;
            $targetOrderStatus = match ($statusCode) {
                SellerOrderStatusCode::HANDED_TO_COURIER => $allActiveSellerOrdersDone && $isDirectCourier ? OrderStatusCode::IN_DELIVERY : OrderStatusCode::PACKING,
                SellerOrderStatusCode::ACCEPTED => OrderStatusCode::PACKING,
                default => OrderStatusCode::PENDING,
            };
            $this->chargeHeldPaymentForOperationalStatus($order, $targetOrderStatus, 'admin_seller_status_update');
            $order->refresh();
            $order->status_code = $targetOrderStatus->value;
            $order->status = $targetOrderStatus->legacy();
            $this->syncCompletionState($order);
            $order->save();
            $this->syncFulfillmentFromSellerStatus($order, $statusCode);
            $this->syncCompletedOrderSideEffects($order, $previousCompletedPaid, 'seller_status_update');

            $courierStatus = $this->resolveCourierStatusForSellerUpdate($order, $statusCode);
            CourierOrder::where('order_id', $order->id)->update([
                'status' => $courierStatus->legacy(),
                'status_code' => $courierStatus->value,
                'updated_at' => now(),
            ]);

            $courierOrderId = (int) $order->id;
            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, (string) $order->status));
            DB::afterCommit(fn () => app(CourierBroadcaster::class)->notifyPendingForOrder($courierOrderId));
        });
    }

    public function updateCourierOrder(CourierOrder $courierOrder, string $status, array $options = []): void
    {
        DB::transaction(function () use ($courierOrder, $status, $options) {
            $courierOrder = CourierOrder::query()->lockForUpdate()->findOrFail($courierOrder->id);
            $statusCode = CourierOrderStatusCode::fromLegacy($status);
            $this->guardCourierTransition($courierOrder, $statusCode, $options);
            $courierOrder->update([
                'status' => $statusCode->legacy(),
                'status_code' => $statusCode->value,
            ]);

            $order = $courierOrder->order()->first();
            if (! $order) {
                return;
            }

            $previousStatus = (string) $order->status;
            $previousCompletedPaid = $order->isCompletedAndPaid();

            if ($statusCode === CourierOrderStatusCode::CANCELLED) {
                $this->orderService->cancelOrder($order, strict: false);
                $this->syncFulfillmentCancellation($order);
                DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F'));

                return;
            }

            $targetOrderStatus = match ($statusCode) {
                CourierOrderStatusCode::DELIVERED => OrderStatusCode::DELIVERED,
                CourierOrderStatusCode::CUSTOMER_RECEIVED => OrderStatusCode::CUSTOMER_RECEIVED,
                CourierOrderStatusCode::IN_DELIVERY => OrderStatusCode::IN_DELIVERY,
                CourierOrderStatusCode::RETURNED => OrderStatusCode::RETURNED,
                default => OrderStatusCode::PENDING,
            };
            $this->chargeHeldPaymentForOperationalStatus($order, $targetOrderStatus, 'admin_courier_status_update');
            $order->refresh();

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

            // BUG TUZATILDI (2026-09): ilgari BARCHA seller orderlar
            // (hatto allaqachon 'cancelled' bo'lganlari ham) kuryer
            // statusiga qarab qayta yozilar edi. Masalan, ko'p-do'konli
            // buyurtmada bitta do'kon o'z bo'lagini bekor qilgan bo'lsa-yu,
            // keyin kuryer boshqa do'kon(lar) uchun "yetkazib berildi"
            // deb belgilasa, bekor qilingan seller order xato ravishda
            // qayta "faollashtirilar" edi (statusi HANDED_TO_COURIER/
            // ACCEPTED'ga qaytardi). Endi faqat hali bekor qilinmagan
            // (aktiv) seller orderlar yangilanadi — xuddi
            // updateActiveSellerOrders() da bo'lgani kabi.
            $this->activeSellerOrdersQuery($order)->update([
                'status' => $this->mapCourierToSeller($statusCode->value)->legacy(),
                'status_code' => $this->mapCourierToSeller($statusCode->value)->value,
                'updated_at' => now(),
            ]);

            $this->syncCompletedOrderSideEffects($order, $previousCompletedPaid, 'courier_status_update');

            DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, (string) $order->status));
        });
    }

    public function updateFulfillmentStatus(OrderFulfillment $fulfillment, FulfillmentStatusCode|string $status, array $options = []): OrderFulfillment
    {
        return DB::transaction(function () use ($fulfillment, $status, $options) {
            $target = $status instanceof FulfillmentStatusCode ? $status : FulfillmentStatusCode::from((string) $status);
            /** @var OrderFulfillment $fulfillment */
            $fulfillment = OrderFulfillment::query()->lockForUpdate()->findOrFail($fulfillment->id);
            $order = $fulfillment->order()->lockForUpdate()->first();

            $this->guardFulfillmentTransition($fulfillment, $target, $order, $options);

            if ($target === FulfillmentStatusCode::CANCELLED && $order) {
                $this->orderService->cancelOrder($order, strict: false);
                $this->syncFulfillmentCancellation($order);

                return $fulfillment->fresh();
            }

            $this->applyFulfillmentStatus($fulfillment, $target);
            $fulfillment->save();

            if ($order) {
                $this->syncOrderFromFulfillmentStatus($order, $fulfillment, $target, $options);
            }

            return $fulfillment->fresh();
        });
    }

    private function allActiveSellerOrdersHandedToCourier(SellerOrder $sellerOrder): bool
    {
        return SellerOrder::query()
            ->where('order_id', $sellerOrder->order_id)
            ->where(function ($query) {
                $query->where('status_code', '!=', SellerOrderStatusCode::CANCELLED->value)
                    ->orWhereNull('status_code');
            })
            ->where(function ($query) {
                $query->where('status', '!=', SellerOrderStatusCode::CANCELLED->legacy())
                    ->orWhereNull('status');
            })
            ->where(function ($query) {
                $query->where('status_code', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->legacy());
                    });
            })
            ->doesntExist();
    }

    private function chargeHeldPaymentForOperationalStatus(Sold $order, OrderStatusCode $targetStatus, string $reason): void
    {
        if (! in_array($targetStatus, [
            OrderStatusCode::IN_DELIVERY,
            OrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED,
        ], true)) {
            return;
        }

        if (PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus) !== PaymentStatusCode::HELD) {
            return;
        }

        if ($targetStatus === OrderStatusCode::IN_DELIVERY && ! $this->allActiveSellerOrdersHandedToCourierForOrder($order)) {
            return;
        }

        $this->paylovOrderPaymentService->chargeHeldOrder(
            $order,
            $this->sellerOrderCancellationService->operationalAmountForCourier($order),
            $reason,
        );
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

        if (! $hasCourier && in_array($statusCode, [
            OrderStatusCode::PACKING->value,
            OrderStatusCode::IN_DELIVERY->value,
        ], true)) {
            return CourierOrderStatusCode::PENDING;
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
                : CourierOrderStatusCode::PENDING,
            SellerOrderStatusCode::ACCEPTED => CourierOrderStatusCode::PENDING,
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

    private function syncCompletedOrderSideEffects(Sold $order, bool $previousCompletedPaid, string $reason): void
    {
        $order->refresh();

        if ($order->isCompletedAndPaid()) {
            $this->orderService->processCashbackAfterOrderMutation($order, $order->user()->first());

            return;
        }

        if ($previousCompletedPaid) {
            $this->orderService->reverseCompletedOrderSideEffects($order, "{$reason}: order={$order->id}");
        }
    }

    private function syncFulfillmentFromMainStatus(Sold $order, string $status): void
    {
        $fulfillment = $order->fulfillment()->first();
        if (! $fulfillment) {
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
        if (! $fulfillment) {
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
        if (! $fulfillment) {
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

    private function guardMainTransition(Sold $order, OrderStatusCode $target, array $options = []): void
    {
        $current = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if ($current === $target) {
            return;
        }

        if (! $this->isMainRollback($current, $target)) {
            return;
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if ($paymentStatus === PaymentStatusCode::PAID && empty($options['allow_paid_rollback'])) {
            throw new RuntimeException('To‘lovi yechilgan buyurtmani oddiy status bilan orqaga qaytarib bo‘lmaydi. Refund yoki maxsus rollback amali kerak.');
        }
    }

    private function guardSellerTransition(SellerOrder $sellerOrder, SellerOrderStatusCode $target, array $options = []): void
    {
        $current = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?? $sellerOrder->status);
        if ($current === $target || ! $this->isSellerRollback($current, $target)) {
            return;
        }

        $order = $sellerOrder->order()->first();
        $paymentStatus = $order ? PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus) : null;
        if ($paymentStatus === PaymentStatusCode::PAID && empty($options['allow_paid_rollback'])) {
            throw new RuntimeException('To‘lovi yechilgan seller orderni oddiy status bilan orqaga qaytarib bo‘lmaydi.');
        }
    }

    private function guardCourierTransition(CourierOrder $courierOrder, CourierOrderStatusCode $target, array $options = []): void
    {
        $current = CourierOrderStatusCode::fromLegacy($courierOrder->status_code ?? $courierOrder->status);
        if ($current === $target) {
            return;
        }

        $order = $courierOrder->order()->first();
        if ($target === CourierOrderStatusCode::IN_DELIVERY && $order && ! $this->allActiveSellerOrdersHandedToCourierForOrder($order)) {
            throw new RuntimeException('Kuryer orderni yo‘lga chiqarish uchun barcha aktiv seller orderlar kuryerga berilgan bo‘lishi kerak.');
        }

        if (! $this->isCourierRollback($current, $target)) {
            return;
        }

        $paymentStatus = $order ? PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus) : null;
        if ($paymentStatus === PaymentStatusCode::PAID && empty($options['allow_paid_rollback'])) {
            throw new RuntimeException('To‘lovi yechilgan kuryer orderni oddiy status bilan orqaga qaytarib bo‘lmaydi.');
        }
    }

    private function guardFulfillmentTransition(OrderFulfillment $fulfillment, FulfillmentStatusCode $target, ?Sold $order, array $options = []): void
    {
        $current = $fulfillment->status_code
            ? FulfillmentStatusCode::from((string) $fulfillment->status_code)
            : FulfillmentStatusCode::AWAITING_SELLER_PREP;

        if ($current === $target || ! $this->isFulfillmentRollback($current, $target)) {
            return;
        }

        $paymentStatus = $order ? PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus) : null;
        if ($paymentStatus === PaymentStatusCode::PAID && empty($options['allow_paid_rollback'])) {
            throw new RuntimeException('To‘lovi yechilgan fulfillmentni oddiy status bilan orqaga qaytarib bo‘lmaydi.');
        }
    }

    private function applyFulfillmentStatus(OrderFulfillment $fulfillment, FulfillmentStatusCode $target): void
    {
        $currentRank = $this->rank($this->fulfillmentFlow(), (string) $fulfillment->status_code);
        $targetRank = $this->rank($this->fulfillmentFlow(), $target->value);
        $fulfillment->status_code = $target->value;

        match ($target) {
            FulfillmentStatusCode::PICKED_FROM_SELLER => $fulfillment->picked_from_seller_at ??= now(),
            FulfillmentStatusCode::ARRIVED_AT_HUB => $fulfillment->arrived_at_hub_at ??= now(),
            FulfillmentStatusCode::QC_CHECKED => $fulfillment->qc_checked_at ??= now(),
            FulfillmentStatusCode::PACKED => $fulfillment->packed_at ??= now(),
            FulfillmentStatusCode::LABELED => $fulfillment->labeled_at ??= now(),
            FulfillmentStatusCode::DISPATCHED_TO_POST => $fulfillment->dispatched_to_post_at ??= now(),
            FulfillmentStatusCode::ASSIGNED_LAST_MILE => $fulfillment->assigned_last_mile_at ??= now(),
            FulfillmentStatusCode::OUT_FOR_DELIVERY => $fulfillment->out_for_delivery_at ??= now(),
            FulfillmentStatusCode::DELIVERED => $fulfillment->delivered_at ??= now(),
            default => null,
        };

        if ($targetRank < $currentRank) {
            $this->clearFutureFulfillmentTimestamps($fulfillment, $targetRank);
        }
    }

    private function syncOrderFromFulfillmentStatus(Sold $order, OrderFulfillment $fulfillment, FulfillmentStatusCode $target, array $options = []): void
    {
        $targetOrderStatus = match ($target) {
            FulfillmentStatusCode::DISPATCHED_TO_POST,
            FulfillmentStatusCode::ASSIGNED_LAST_MILE,
            FulfillmentStatusCode::OUT_FOR_DELIVERY => OrderStatusCode::IN_DELIVERY,
            FulfillmentStatusCode::DELIVERED => OrderStatusCode::DELIVERED,
            FulfillmentStatusCode::RETURNED => OrderStatusCode::RETURNED,
            default => OrderStatusCode::PACKING,
        };

        if ($targetOrderStatus === OrderStatusCode::IN_DELIVERY && ! $this->allActiveSellerOrdersHandedToCourierForOrder($order)) {
            return;
        }

        if (in_array($targetOrderStatus, [OrderStatusCode::IN_DELIVERY, OrderStatusCode::DELIVERED], true)) {
            $this->chargeHeldPaymentForOperationalStatus($order, $targetOrderStatus, 'fulfillment_status_update');
            $order->refresh();
        }

        $order->status = $targetOrderStatus->legacy();
        $order->status_code = $targetOrderStatus->value;
        $this->syncCompletionState($order);
        $order->save();

        if ($target === FulfillmentStatusCode::OUT_FOR_DELIVERY) {
            CourierOrder::query()
                ->where('order_id', $order->id)
                ->whereNotIn('status_code', [
                    CourierOrderStatusCode::CUSTOMER_RECEIVED->value,
                    CourierOrderStatusCode::CANCELLED->value,
                    CourierOrderStatusCode::RETURNED->value,
                ])
                ->update([
                    'status' => CourierOrderStatusCode::IN_DELIVERY->legacy(),
                    'status_code' => CourierOrderStatusCode::IN_DELIVERY->value,
                    'updated_at' => now(),
                ]);
        }
    }

    private function allActiveSellerOrdersHandedToCourierForOrder(Sold $order): bool
    {
        return SellerOrder::query()
            ->where('order_id', $order->id)
            ->where(function ($query) {
                $query->where('status_code', '!=', SellerOrderStatusCode::CANCELLED->value)
                    ->orWhereNull('status_code');
            })
            ->where(function ($query) {
                $query->where('status', '!=', SellerOrderStatusCode::CANCELLED->legacy())
                    ->orWhereNull('status');
            })
            ->where(function ($query) {
                $query->where('status_code', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->legacy());
                    });
            })
            ->doesntExist();
    }

    private function markActiveSellerOrdersHandedToCourier(Sold $order): void
    {
        $this->activeSellerOrdersQuery($order)
            ->update([
                'status' => SellerOrderStatusCode::HANDED_TO_COURIER->legacy(),
                'status_code' => SellerOrderStatusCode::HANDED_TO_COURIER->value,
                'updated_at' => now(),
            ]);
    }

    private function updateActiveSellerOrders(Sold $order, SellerOrderStatusCode $status): void
    {
        $this->activeSellerOrdersQuery($order)
            ->update([
                'status' => $status->legacy(),
                'status_code' => $status->value,
                'updated_at' => now(),
            ]);
    }

    private function activeSellerOrdersQuery(Sold $order): \Illuminate\Database\Eloquent\Builder
    {
        return SellerOrder::query()
            ->where('order_id', $order->id)
            ->where(function ($query) {
                $query->where('status_code', '!=', SellerOrderStatusCode::CANCELLED->value)
                    ->orWhereNull('status_code');
            })
            ->where(function ($query) {
                $query->where('status', '!=', SellerOrderStatusCode::CANCELLED->legacy())
                    ->orWhereNull('status');
            });
    }

    private function syncFulfillmentCancellation(Sold $order): void
    {
        $fulfillment = OrderFulfillment::query()->where('order_id', $order->id)->first();
        if ($fulfillment) {
            $fulfillment->status_code = FulfillmentStatusCode::CANCELLED->value;
            $fulfillment->save();
        }

        CourierTask::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status_code', [
                CourierTaskStatusCode::COMPLETED->value,
                CourierTaskStatusCode::CANCELLED->value,
                CourierTaskStatusCode::FAILED->value,
            ])
            ->update([
                'status_code' => CourierTaskStatusCode::CANCELLED->value,
                'updated_at' => now(),
            ]);
    }

    private function isMainRollback(OrderStatusCode $current, OrderStatusCode $target): bool
    {
        return $this->rank($this->mainOrderFlow(), $target->value) < $this->rank($this->mainOrderFlow(), $current->value);
    }

    private function isSellerRollback(SellerOrderStatusCode $current, SellerOrderStatusCode $target): bool
    {
        return $this->rank($this->sellerOrderFlow(), $target->value) < $this->rank($this->sellerOrderFlow(), $current->value);
    }

    private function isCourierRollback(CourierOrderStatusCode $current, CourierOrderStatusCode $target): bool
    {
        return $this->rank($this->courierOrderFlow(), $target->value) < $this->rank($this->courierOrderFlow(), $current->value);
    }

    private function isFulfillmentRollback(FulfillmentStatusCode $current, FulfillmentStatusCode $target): bool
    {
        return $this->rank($this->fulfillmentFlow(), $target->value) < $this->rank($this->fulfillmentFlow(), $current->value);
    }

    private function rank(array $flow, string $status): int
    {
        $rank = array_search($status, $flow, true);

        return $rank === false ? 0 : (int) $rank;
    }

    private function mainOrderFlow(): array
    {
        return [
            OrderStatusCode::PENDING->value,
            OrderStatusCode::PACKING->value,
            OrderStatusCode::IN_DELIVERY->value,
            OrderStatusCode::DELIVERED->value,
            OrderStatusCode::CUSTOMER_RECEIVED->value,
        ];
    }

    private function sellerOrderFlow(): array
    {
        return [
            SellerOrderStatusCode::PAYMENT_PENDING->value,
            SellerOrderStatusCode::NEW->value,
            SellerOrderStatusCode::ACCEPTED->value,
            SellerOrderStatusCode::HANDED_TO_COURIER->value,
        ];
    }

    private function courierOrderFlow(): array
    {
        return [
            CourierOrderStatusCode::PAYMENT_PENDING->value,
            CourierOrderStatusCode::PENDING->value,
            CourierOrderStatusCode::IN_DELIVERY->value,
            CourierOrderStatusCode::DELIVERED->value,
            CourierOrderStatusCode::CUSTOMER_RECEIVED->value,
        ];
    }

    private function fulfillmentFlow(): array
    {
        return [
            FulfillmentStatusCode::AWAITING_SELLER_PREP->value,
            FulfillmentStatusCode::READY_FOR_PICKUP->value,
            FulfillmentStatusCode::PICKED_FROM_SELLER->value,
            FulfillmentStatusCode::ARRIVED_AT_HUB->value,
            FulfillmentStatusCode::QC_CHECKED->value,
            FulfillmentStatusCode::PACKED->value,
            FulfillmentStatusCode::LABELED->value,
            FulfillmentStatusCode::DISPATCHED_TO_POST->value,
            FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
            FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            FulfillmentStatusCode::DELIVERED->value,
        ];
    }

    private function clearFutureFulfillmentTimestamps(OrderFulfillment $fulfillment, int $targetRank): void
    {
        $columns = [
            FulfillmentStatusCode::PICKED_FROM_SELLER->value => 'picked_from_seller_at',
            FulfillmentStatusCode::ARRIVED_AT_HUB->value => 'arrived_at_hub_at',
            FulfillmentStatusCode::QC_CHECKED->value => 'qc_checked_at',
            FulfillmentStatusCode::PACKED->value => 'packed_at',
            FulfillmentStatusCode::LABELED->value => 'labeled_at',
            FulfillmentStatusCode::DISPATCHED_TO_POST->value => 'dispatched_to_post_at',
            FulfillmentStatusCode::ASSIGNED_LAST_MILE->value => 'assigned_last_mile_at',
            FulfillmentStatusCode::OUT_FOR_DELIVERY->value => 'out_for_delivery_at',
            FulfillmentStatusCode::DELIVERED->value => 'delivered_at',
        ];

        foreach ($columns as $status => $column) {
            if ($this->rank($this->fulfillmentFlow(), $status) > $targetRank) {
                $fulfillment->{$column} = null;
            }
        }
    }
}
