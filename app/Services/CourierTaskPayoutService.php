<?php

namespace App\Services;

use App\Models\CourierTask;
use App\Models\ProjectSetting;

class CourierTaskPayoutService
{
    private const DEFAULT_BASE_FEE = 3000;
    private const DEFAULT_PRICE_PER_KM = 1500;
    private const DEFAULT_MIN_FEE = 5000;

    private ?ProjectSetting $settings = null;

    public function calculate(?array $pickupAddress, ?array $dropoffAddress): array
    {
        $settings = $this->settings();
        $distanceKm = $this->distanceKm($pickupAddress, $dropoffAddress);
        $baseFee = max(0, (int) ($settings?->courier_base_fee ?? self::DEFAULT_BASE_FEE));
        $pricePerKm = max(0, (int) ($settings?->courier_price_per_km ?? self::DEFAULT_PRICE_PER_KM));
        $minFee = max(0, (int) ($settings?->courier_min_fee ?? self::DEFAULT_MIN_FEE));
        $distanceFee = (int) ceil($distanceKm * $pricePerKm);
        $bonus = $this->bonusForDistance($distanceKm, $settings?->courier_bonus_rules ?? []);
        $total = max($minFee, $baseFee + $distanceFee + $bonus);

        return [
            'distance_km' => round($distanceKm, 2),
            'base_fee_amount' => $baseFee,
            'distance_fee_amount' => $distanceFee,
            'bonus_amount' => $bonus,
            'fee_amount' => $total,
            'payout_breakdown' => [
                'base_fee' => $baseFee,
                'price_per_km' => $pricePerKm,
                'distance_km' => round($distanceKm, 2),
                'distance_fee' => $distanceFee,
                'bonus' => $bonus,
                'min_fee' => $minFee,
                'total' => $total,
            ],
        ];
    }

    public function apply(CourierTask $task): CourierTask
    {
        $payload = $this->calculate($task->pickup_address, $task->dropoff_address);

        foreach ($payload as $key => $value) {
            $task->{$key} = $value;
        }

        return $task;
    }

    private function settings(): ?ProjectSetting
    {
        return $this->settings ??= ProjectSetting::query()->first();
    }

    private function bonusForDistance(float $distanceKm, array $rules): int
    {
        $bonus = 0;

        foreach ($rules as $rule) {
            $from = isset($rule['from_km']) ? (float) $rule['from_km'] : 0.0;
            $to = isset($rule['to_km']) && $rule['to_km'] !== null && $rule['to_km'] !== ''
                ? (float) $rule['to_km']
                : null;

            if ($distanceKm < $from) {
                continue;
            }

            if ($to !== null && $distanceKm > $to) {
                continue;
            }

            $bonus = max($bonus, max(0, (int) ($rule['bonus_amount'] ?? 0)));
        }

        return $bonus;
    }

    private function distanceKm(?array $from, ?array $to): float
    {
        $lat1 = (float) ($from['lat'] ?? 0);
        $lon1 = (float) ($from['lon'] ?? 0);
        $lat2 = (float) ($to['lat'] ?? 0);
        $lon2 = (float) ($to['lon'] ?? 0);

        if (($lat1 == 0.0 && $lon1 == 0.0) || ($lat2 == 0.0 && $lon2 == 0.0)) {
            return 0.0;
        }

        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
