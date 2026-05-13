<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Models\Hub;

class HubAssignmentService
{
    public function assignForOrder(object $buyerLocation, FulfillmentMode $mode): ?Hub
    {
        if ($mode === FulfillmentMode::DIRECT_COURIER || $mode === FulfillmentMode::PICKUP_ONLY) {
            return null;
        }

        $query = Hub::query()
            ->where('is_active', true)
            ->where('country_code', strtoupper((string) ($buyerLocation->country_code ?? 'UZ')));

        if ($mode === FulfillmentMode::POSTAL_ONLY_VIA_HUB) {
            $query->where('supports_first_mile', true)
                ->where('supports_postal_dispatch', true);
        } else {
            $query->where('supports_first_mile', true)
                ->where(function ($builder) {
                    $builder->where('supports_last_mile', true)
                        ->orWhere('supports_postal_dispatch', true);
                });
        }

        $city = trim((string) ($buyerLocation->city_name ?? ''));
        $region = trim((string) ($buyerLocation->region_name ?? ''));

        return $query
            ->orderByRaw(
                'CASE
                    WHEN city_name IS NOT NULL AND city_name <> "" AND LOWER(city_name) = ? THEN 0
                    WHEN region_name IS NOT NULL AND region_name <> "" AND LOWER(region_name) = ? THEN 1
                    WHEN is_primary = 1 THEN 2
                    ELSE 3
                END',
                [mb_strtolower($city), mb_strtolower($region)]
            )
            ->orderBy('priority')
            ->orderBy('name')
            ->first();
    }
}
