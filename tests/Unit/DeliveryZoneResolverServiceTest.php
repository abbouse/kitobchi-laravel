<?php

namespace Tests\Unit;

use App\Models\DeliveryZoneRule;
use App\Services\DeliveryZoneResolverService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DeliveryZoneResolverServiceTest extends TestCase
{
    public function test_free_delivery_keeps_multi_seller_surcharge(): void
    {
        $components = (new DeliveryZoneResolverService)->calculatePriceComponents(
            basePrice: 25000,
            sellerCount: 3,
            additionalPercent: 50,
            freePriceFrom: 100000,
            cartTotal: 150000,
        );

        $this->assertSame(0, $components['base_delivery_price']);
        $this->assertSame(25000, $components['additional_seller_price']);
        $this->assertSame(25000, $components['total']);
    }

    public function test_delivery_price_includes_base_when_free_limit_is_not_reached(): void
    {
        $components = (new DeliveryZoneResolverService)->calculatePriceComponents(
            basePrice: 25000,
            sellerCount: 3,
            additionalPercent: 50,
            freePriceFrom: 100000,
            cartTotal: 90000,
        );

        $this->assertSame(25000, $components['base_delivery_price']);
        $this->assertSame(25000, $components['additional_seller_price']);
        $this->assertSame(50000, $components['total']);
    }

    public function test_named_zone_rule_must_match_location_names(): void
    {
        $service = new DeliveryZoneResolverService;
        $method = (new ReflectionClass($service))->getMethod('ruleMatchesLocation');
        $method->setAccessible(true);

        $rule = new DeliveryZoneRule([
            'scope' => 'country',
            'country_code' => 'UZ',
            'city_name' => 'Toshkent shahri',
        ]);

        $this->assertTrue($method->invoke($service, $rule, [
            'country_code' => 'UZ',
            'city_name' => 'Toshkent shahri',
        ]));

        $this->assertFalse($method->invoke($service, $rule, [
            'country_code' => 'UZ',
            'city_name' => 'Samarqand',
        ]));
    }
}
