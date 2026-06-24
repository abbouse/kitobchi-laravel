<?php

namespace Tests\Unit;

use App\Services\DeliveryZoneResolverService;
use PHPUnit\Framework\TestCase;

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
}
