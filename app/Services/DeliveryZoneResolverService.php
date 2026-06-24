<?php

namespace App\Services;

use App\Models\DeliveryZoneRule;
use Illuminate\Support\Collection;

class DeliveryZoneResolverService
{
    public function isCountrySupported(?string $countryCode): bool
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return false;
        }

        return DeliveryZoneRule::active()
            ->where('country_code', $countryCode)
            ->exists();
    }

    public function resolveOffers(object|array $location, int $sellerCount, float|int $cartTotal): Collection
    {
        $countryCode = $this->detectCountryCode($location);

        if ($countryCode === null) {
            return collect();
        }

        $activeCountryRules = DeliveryZoneRule::query()
            ->with('deliveryService')
            ->active()
            ->where('country_code', $countryCode)
            ->get();

        if ($activeCountryRules->isEmpty()) {
            return collect();
        }

        $rules = $activeCountryRules
            ->filter(function (DeliveryZoneRule $rule) use ($location) {
                $service = $rule->deliveryService;

                if (! $service || ! $service->status) {
                    return false;
                }

                return $this->ruleMatchesLocation($rule, $location);
            })
            ->sort(function (DeliveryZoneRule $a, DeliveryZoneRule $b) {
                return [$b->priority, $this->specificityScore($b), $b->id]
                    <=> [$a->priority, $this->specificityScore($a), $a->id];
            });

        return $rules
            ->groupBy('delivery_service_id')
            ->map(function (Collection $group) use ($sellerCount, $cartTotal) {
                /** @var DeliveryZoneRule $rule */
                $rule = $group->first();
                $service = $rule->deliveryService;
                $codAllowed = $service->type === 'courier_service' && (bool) $rule->cod_allowed;
                $basePrice = (int) ($rule->base_price ?? $service->priceKg ?? 0);
                $additionalPercent = (float) ($rule->additional_seller_percent ?? 50);
                $freePriceFrom = (int) ($rule->free_price_from ?? $service->freePriceFrom ?? 0);
                $priceComponents = $this->calculatePriceComponents(
                    basePrice: $basePrice,
                    sellerCount: $sellerCount,
                    additionalPercent: $additionalPercent,
                    freePriceFrom: $freePriceFrom,
                    cartTotal: $cartTotal,
                );

                return [
                    'id' => (int) $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'muddat' => (int) ($rule->eta_days ?? $service->muddat ?? 0),
                    'priceKg' => (int) ($service->priceKg ?? 0),
                    'freePriceFrom' => $freePriceFrom,
                    'is_free' => $priceComponents['total'] === 0,
                    'calculated_price' => $priceComponents['total'],
                    'base_delivery_price' => $priceComponents['base_delivery_price'],
                    'additional_seller_price' => $priceComponents['additional_seller_price'],
                    'additional_seller_percent' => $additionalPercent,
                    'seller_count' => $sellerCount,
                    'capital' => (bool) ($service->capital ?? false),
                    'cod_allowed' => $codAllowed,
                    'zone_rule_id' => (int) $rule->id,
                    'zone_name' => $rule->zone_name,
                    'zone_scope' => $rule->scope,
                    'country_code' => $rule->country_code,
                    'priority' => (int) $rule->priority,
                ];
            })
            ->values();
    }

    public function resolveSelectedOffer(object|array $location, int $sellerCount, float|int $cartTotal, int $deliveryServiceId): ?array
    {
        return $this->resolveOffers($location, $sellerCount, $cartTotal)
            ->firstWhere('id', $deliveryServiceId);
    }

    public function calculatePriceComponents(
        int $basePrice,
        int $sellerCount,
        float $additionalPercent,
        int $freePriceFrom,
        float|int $cartTotal,
    ): array {
        $sellerMultiplier = max(0, $sellerCount - 1);
        $additionalSellerPrice = (int) round($basePrice * ($additionalPercent / 100) * $sellerMultiplier);
        $baseDeliveryPrice = $freePriceFrom > 0 && $cartTotal >= $freePriceFrom
            ? 0
            : max(0, $basePrice);

        return [
            'base_delivery_price' => $baseDeliveryPrice,
            'additional_seller_price' => $additionalSellerPrice,
            'total' => $baseDeliveryPrice + $additionalSellerPrice,
        ];
    }

    private function detectCountryCode(object|array $location): ?string
    {
        $countryCode = strtoupper(trim((string) $this->value($location, 'country_code')));
        if ($countryCode !== '') {
            return $countryCode;
        }

        $lat = $this->floatValue($location, 'lat');
        $lon = $this->floatValue($location, 'lon');

        if ($lat !== null && $lon !== null && $lat >= 37.0 && $lat <= 45.7 && $lon >= 55.9 && $lon <= 73.3) {
            return 'UZ';
        }

        $address = mb_strtolower(trim((string) $this->value($location, 'fullAddress')));
        if ($address !== '' && (
            str_contains($address, 'uzbekiston')
            || str_contains($address, 'uzbekistan')
            || str_contains($address, 'узбекистан')
        )) {
            return 'UZ';
        }

        return null;
    }

    private function ruleMatchesLocation(DeliveryZoneRule $rule, object|array $location): bool
    {
        if ($rule->scope === 'country') {
            return true;
        }

        $lat = $this->floatValue($location, 'lat');
        $lon = $this->floatValue($location, 'lon');
        if ($lat === null || $lon === null || $rule->center_lat === null || $rule->center_lon === null || $rule->radius_km === null) {
            return false;
        }

        return $this->distanceKm($lat, $lon, (float) $rule->center_lat, (float) $rule->center_lon) <= (float) $rule->radius_km;
    }

    private function specificityScore(DeliveryZoneRule $rule): int
    {
        if ($rule->scope === 'country') {
            return 10;
        }

        return 100
            + ($rule->city_name ? 15 : 0)
            + ($rule->district_name ? 10 : 0)
            + ($rule->region_name ? 5 : 0);
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));

        return $earthRadiusKm * $c;
    }

    private function value(object|array $payload, string $key): mixed
    {
        if (is_array($payload)) {
            return $payload[$key] ?? null;
        }

        return $payload->{$key} ?? null;
    }

    private function floatValue(object|array $payload, string $key): ?float
    {
        $value = $this->value($payload, $key);

        return is_numeric($value) ? (float) $value : null;
    }
}
