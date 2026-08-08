<?php

namespace Tests\Unit;

use App\Models\Seller;
use App\Models\SellerCommissionPromotion;
use App\Services\SellerCommissionService;
use PHPUnit\Framework\TestCase;

class SellerCommissionServiceTest extends TestCase
{
    public function test_zero_permanent_rate_is_normalized_to_global_mode(): void
    {
        $seller = new Seller;
        $seller->commission_percent = 0;

        $this->assertNull($seller->getAttributes()['commission_percent']);

        $seller->commission_percent = 7;
        $this->assertSame(7, $seller->getAttributes()['commission_percent']);
    }

    public function test_free_promotion_temporarily_reduces_commission_to_zero(): void
    {
        $this->assertSame(
            0,
            SellerCommissionService::effectivePercent(12, SellerCommissionPromotion::TYPE_FREE, 0),
        );
    }

    public function test_fixed_promotion_uses_lower_rate_without_raising_base_rate(): void
    {
        $this->assertSame(
            5,
            SellerCommissionService::effectivePercent(12, SellerCommissionPromotion::TYPE_FIXED_RATE, 5),
        );
        $this->assertSame(
            12,
            SellerCommissionService::effectivePercent(12, SellerCommissionPromotion::TYPE_FIXED_RATE, 20),
        );
    }

    public function test_missing_or_unknown_promotion_keeps_base_commission(): void
    {
        $this->assertSame(12, SellerCommissionService::effectivePercent(12, null, null));
        $this->assertSame(12, SellerCommissionService::effectivePercent(12, 'unknown', 0));
    }
}
