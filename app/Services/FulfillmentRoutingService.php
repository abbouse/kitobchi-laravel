<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Models\DeliveryService;
use App\Models\OrderFulfillment;
use App\Models\Sold;

class FulfillmentRoutingService
{
    public function __construct(
        private readonly HubAssignmentService $hubAssignmentService,
    ) {}

    public function decide(
        object $buyerLocation,
        DeliveryService $deliveryService,
        array $selectedDeliveryOffer,
        int $sellerCount,
        bool $withPackaging,
        bool $containsBooks,
        bool $isCashOnDelivery,
        int $cashCollectAmount,
    ): array {
        $deliveryType = Sold::normalizeDeliveryTypeValue($deliveryService->type ?: $deliveryService->name);
        $mode = $this->decideMode($deliveryType, $sellerCount, $withPackaging, $containsBooks);
        $hub = $this->hubAssignmentService->assignForOrder($buyerLocation, $mode);

        $firstMileMode = match ($mode) {
            FulfillmentMode::DIRECT_COURIER => 'none',
            default => 'courier_pickup',
        };

        $lastMileMode = match ($mode) {
            FulfillmentMode::DIRECT_COURIER => 'direct_delivery',
            FulfillmentMode::POSTAL_ONLY_VIA_HUB => 'postal_dispatch',
            default => $deliveryType === 'postal' ? 'postal_dispatch' : 'courier_delivery',
        };

        return [
            'mode' => $mode,
            'hub' => $hub,
            'status_code' => FulfillmentStatusCode::AWAITING_SELLER_PREP->value,
            'first_mile_mode' => $firstMileMode,
            'last_mile_mode' => $lastMileMode,
            'routing_version' => 'v1',
            'is_cod' => $isCashOnDelivery && in_array($lastMileMode, ['direct_delivery', 'courier_delivery'], true),
            'cash_collect_amount' => $isCashOnDelivery ? max(0, $cashCollectAmount) : 0,
            'routing_snapshot' => [
                'delivery_type' => $deliveryType,
                'seller_count' => $sellerCount,
                'with_packaging' => $withPackaging,
                'contains_books' => $containsBooks,
                'zone_rule_id' => (int) ($selectedDeliveryOffer['zone_rule_id'] ?? 0) ?: null,
                'zone_name' => $selectedDeliveryOffer['zone_name'] ?? null,
                'zone_scope' => $selectedDeliveryOffer['zone_scope'] ?? null,
                'country_code' => $selectedDeliveryOffer['country_code'] ?? ($buyerLocation->country_code ?? null),
                'cod_allowed' => (bool) ($selectedDeliveryOffer['cod_allowed'] ?? true),
                'calculated_price' => (int) ($selectedDeliveryOffer['calculated_price'] ?? 0),
                'hub_id' => $hub?->id,
                'hub_code' => $hub?->code,
                'hub_name' => $hub?->name,
            ],
        ];
    }

    public function createForOrder(
        Sold $order,
        array $routingDecision,
        DeliveryService $deliveryService,
        ?int $deliveryZoneRuleId,
    ): OrderFulfillment {
        return OrderFulfillment::create([
            'order_id' => $order->id,
            'hub_id' => $routingDecision['hub']?->id,
            'fulfillment_mode' => $routingDecision['mode']->value,
            'status_code' => $routingDecision['status_code'],
            'first_mile_mode' => $routingDecision['first_mile_mode'],
            'last_mile_mode' => $routingDecision['last_mile_mode'],
            'delivery_service_id' => $deliveryService->id,
            'delivery_zone_rule_id' => $deliveryZoneRuleId,
            'routing_version' => $routingDecision['routing_version'],
            'is_cod' => (bool) $routingDecision['is_cod'],
            'cash_collect_amount' => (int) $routingDecision['cash_collect_amount'],
            'routing_snapshot' => $routingDecision['routing_snapshot'],
            'meta' => [
                'created_from_order' => true,
            ],
        ]);
    }

    private function decideMode(
        string $deliveryType,
        int $sellerCount,
        bool $withPackaging,
        bool $containsBooks,
    ): FulfillmentMode
    {
        if ($deliveryType === 'pickup') {
            return FulfillmentMode::PICKUP_ONLY;
        }

        if ($deliveryType === 'postal') {
            return FulfillmentMode::POSTAL_ONLY_VIA_HUB;
        }

        if ($sellerCount > 1 || $withPackaging || $containsBooks) {
            return FulfillmentMode::HUB_BASED;
        }

        return FulfillmentMode::DIRECT_COURIER;
    }
}
