<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Models\DeliveryZoneRule;
use App\Models\Sold;

class DeliveryEtaSyncService
{
    public function __construct(
        private readonly OrderStatusPushService $orderStatusPushService,
    ) {}

    public function syncZoneRuleEtaChange(
        DeliveryZoneRule $rule,
        ?int $previousEtaDays,
        ?int $newEtaDays,
    ): int {
        if ($previousEtaDays === null || $newEtaDays === null || $previousEtaDays === $newEtaDays) {
            return 0;
        }

        $affected = 0;

        Sold::query()
            ->where('delivery_zone_rule_id', $rule->id)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($previousEtaDays, $newEtaDays, &$affected) {
                foreach ($orders as $order) {
                    $status = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
                    if (in_array($status, [
                        OrderStatusCode::DELIVERED,
                        OrderStatusCode::CUSTOMER_RECEIVED,
                        OrderStatusCode::CANCELLED,
                        OrderStatusCode::RETURNED,
                    ], true)) {
                        continue;
                    }

                    $snapshot = is_array($order->delivery_rule_snapshot)
                        ? $order->delivery_rule_snapshot
                        : [];

                    $currentEtaDays = (int) data_get($snapshot, 'eta_days', -1);
                    if ($currentEtaDays !== $previousEtaDays) {
                        continue;
                    }

                    $previousEta = $order->estimatedDeliveryAt();

                    $snapshot['eta_days'] = $newEtaDays;
                    $snapshot['eta_updated_at'] = now()->toIso8601String();
                    $snapshot['eta_update_source'] = 'delivery_zone_rule_sync';
                    $snapshot['eta_previous_days'] = $previousEtaDays;

                    $order->delivery_rule_snapshot = $snapshot;
                    $order->save();

                    $freshOrder = $order->fresh();
                    if ($newEtaDays > $previousEtaDays) {
                        $this->orderStatusPushService->sendEtaChangedNotice(
                            $freshOrder,
                            $previousEta,
                            $freshOrder?->estimatedDeliveryAt(),
                        );
                    }

                    $affected++;
                }
            });

        return $affected;
    }
}
