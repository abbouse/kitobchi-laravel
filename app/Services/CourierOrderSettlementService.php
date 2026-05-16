<?php

namespace App\Services;

use App\Enums\OrderKind;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierOrderSettlementService
{
    public function __construct(
        private readonly CourierBonusService $courierBonusService,
    ) {}

    public function settleCompletedOrder(Sold $order): void
    {
        if ($order->order_kind !== OrderKind::STANDARD->value) {
            return;
        }

        if (!$order->isCompletedAndPaid()
            || (int) ($order->courier_id ?? 0) <= 0) {
            return;
        }

        DB::transaction(function () use ($order) {
            $courierOrder = CourierOrder::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$courierOrder || $courierOrder->settled_at) {
                return;
            }

            $courier = Couriers::query()->lockForUpdate()->find((int) $order->courier_id);
            if (!$courier) {
                return;
            }

            if ($courierOrder->status_code !== \App\Enums\CourierOrderStatusCode::CUSTOMER_RECEIVED->value) {
                $courierOrder->status = \App\Enums\CourierOrderStatusCode::CUSTOMER_RECEIVED->legacy();
                $courierOrder->status_code = \App\Enums\CourierOrderStatusCode::CUSTOMER_RECEIVED->value;
            }

            $finalBonus = $courierOrder->final_bonus;
            if ($finalBonus === null) {
                $finalBonus = $this->courierBonusService->computeFinalBonus($courierOrder);
            } else {
                $finalBonus = max(0, (int) $finalBonus);
            }

            $settledAmount = max(0, (int) $courierOrder->courierPrice) + $finalBonus;
            if ($settledAmount <= 0) {
                $courierOrder->settled_amount = 0;
                $courierOrder->settled_at = now();
                $courierOrder->save();
                return;
            }

            $courier->balance = (int) $courier->balance + $settledAmount;
            $courier->save();

            $courierOrder->settled_amount = $settledAmount;
            $courierOrder->settled_at = now();
            $courierOrder->save();

            Log::info('Courier order settled', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'courier_order_id' => $courierOrder->id,
                'courier_price' => (int) $courierOrder->courierPrice,
                'final_bonus' => $finalBonus,
                'settled_amount' => $settledAmount,
            ]);
        });
    }

    public function reverseCompletedOrderSettlement(Sold $order, ?string $reason = null): void
    {
        if ((int) ($order->courier_id ?? 0) <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $reason) {
            $courierOrder = CourierOrder::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$courierOrder || !$courierOrder->settled_at) {
                return;
            }

            $courier = Couriers::query()->lockForUpdate()->find((int) $order->courier_id);
            if (!$courier) {
                return;
            }

            $settledAmount = max(0, (int) ($courierOrder->settled_amount ?? 0));
            if ($settledAmount > 0) {
                $courier->balance = (int) $courier->balance - $settledAmount;
                $courier->save();
            }

            $courierOrder->settled_amount = null;
            $courierOrder->settled_at = null;
            $courierOrder->save();

            Log::warning('Courier order settlement reversed', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'courier_order_id' => $courierOrder->id,
                'settled_amount' => $settledAmount,
                'reason' => $reason,
            ]);
        });
    }
}
