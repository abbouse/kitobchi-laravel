<?php

namespace App\Services\PostalTracking;

use App\Enums\OrderStatusCode;
use App\Models\OrderFulfillment;
use App\Models\Sold;
use App\Services\AdminOrderStatusSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostalOrderStatusTransitionService
{
    public function __construct(
        private readonly PostalTrackingStatusCatalog $statusCatalog,
        private readonly AdminOrderStatusSyncService $statusSync,
    ) {}

    /**
     * UzPost bir xil holatni qayta-qayta qaytarsa ham order faqat oldinga
     * yuradi. Asosiy status servisi SMS, push, settlement va cashbackni
     * admin o'zgartirgandagi ayni oqim orqali bajaradi.
     *
     * @param  array<string, mixed>  $tracking
     */
    public function reconcile(OrderFulfillment $fulfillment, array $tracking): bool
    {
        $target = $this->statusCatalog->orderStatusTarget($tracking['status_code'] ?? null);
        if (! $target) {
            return false;
        }

        $order = $this->orderFor($fulfillment);
        if (! $order || $order->deliveryType !== 'postal') {
            return false;
        }

        $current = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if (! $this->canAdvance($current, $target)) {
            return false;
        }

        try {
            $this->statusSync->updateMainOrder($order, $target->value, [
                'forward_only' => true,
                'source' => 'postal_tracking',
            ]);

            $updated = $order->fresh() ?? $order;
            $changed = OrderStatusCode::fromLegacy(
                $updated->status_code ?? $updated->status,
            ) === $target;

            if ($changed) {
                Log::info('[Postal tracking] Order status advanced', [
                    'order_id' => $order->id,
                    'fulfillment_id' => $fulfillment->id,
                    'provider' => $tracking['provider_code'] ?? null,
                    'tracking_number' => $tracking['tracking_number'] ?? null,
                    'postal_status' => $tracking['status_code'] ?? null,
                    'from' => $current->value,
                    'to' => $target->value,
                ]);
            }

            return $changed;
        } catch (Throwable $error) {
            Log::error('[Postal tracking] Automatic order transition failed', [
                'order_id' => $order->id,
                'fulfillment_id' => $fulfillment->id,
                'postal_status' => $tracking['status_code'] ?? null,
                'target_status' => $target->value,
                'error' => $error->getMessage(),
            ]);

            return false;
        }
    }

    private function orderFor(OrderFulfillment $fulfillment): ?Sold
    {
        if ($fulfillment->relationLoaded('order')) {
            $order = $fulfillment->getRelation('order');

            return $order instanceof Sold ? $order : null;
        }

        return $fulfillment->order()->first();
    }

    private function canAdvance(OrderStatusCode $current, OrderStatusCode $target): bool
    {
        if (in_array($current, [
            OrderStatusCode::CANCELLED,
            OrderStatusCode::RETURNED,
            OrderStatusCode::CUSTOMER_RECEIVED,
        ], true)) {
            return false;
        }

        return $this->rank($target) > $this->rank($current);
    }

    private function rank(OrderStatusCode $status): int
    {
        return match ($status) {
            OrderStatusCode::PENDING => 1,
            OrderStatusCode::PACKING => 2,
            OrderStatusCode::IN_DELIVERY => 3,
            OrderStatusCode::DELIVERED => 4,
            OrderStatusCode::CUSTOMER_RECEIVED => 5,
            OrderStatusCode::CANCELLED,
            OrderStatusCode::RETURNED => 99,
        };
    }
}
