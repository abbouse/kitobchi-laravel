<?php

namespace App\Services;

use App\Models\CourierOrder;

/**
 * Legacy facade for removed surge/SLA pause flow.
 *
 * Courier payouts now live on courier_tasks:
 * courierPrice = base + km fee, courierBonus = distance bonus.
 */
class CourierBonusService
{
    public function settings(): array
    {
        return [
            'surge_step' => 0,
            'surge_max' => 0,
            'surge_threshold' => 0,
            'sla_minutes' => 0,
            'penalty_step' => 0,
        ];
    }

    public function normalizeBonusState(CourierOrder $order, bool $persist = true): CourierOrder
    {
        return $order;
    }

    public function tickPickupBonus(): array
    {
        return [
            'ticked' => 0,
            'threshold_crossed' => [],
        ];
    }

    public function lockBonusOnAccept(CourierOrder $order): void
    {
    }

    public function startSlaOnPickupReady(CourierOrder $order): void
    {
        if (!$order->picked_up_at) {
            $order->picked_up_at = now();
            $order->save();
        }
    }

    public function computeFinalBonus(CourierOrder $order): int
    {
        return max(0, (int) ($order->courierBonus ?? 0));
    }

    public function toggleCustomerDelay(CourierOrder $order): array
    {
        return [
            'success' => false,
            'paused' => false,
            'total_delay_seconds' => 0,
            'customer_delay_count' => 0,
            'remaining_delay_seconds' => 0,
            'message' => 'Kutish rejimi o‘chirilgan.',
            'sla_deadline' => null,
        ];
    }

    public function findSlaWarningCandidates(): array
    {
        return [];
    }

    public function markSlaWarningNotified(CourierOrder $order): void
    {
    }
}
