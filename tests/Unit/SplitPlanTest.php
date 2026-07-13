<?php

namespace Tests\Unit;

use App\Models\SplitPlan;
use PHPUnit\Framework\TestCase;

class SplitPlanTest extends TestCase
{
    public function test_zero_minimum_order_disables_the_minimum_threshold(): void
    {
        $plan = new SplitPlan(['min_order_sum' => 0]);

        $this->assertSame(0, $plan->minimumOrderSum());
    }

    public function test_empty_minimum_order_also_has_no_hidden_minimum(): void
    {
        $plan = new SplitPlan(['min_order_sum' => null]);

        $this->assertSame(0, $plan->minimumOrderSum());
    }

    public function test_empty_maximum_order_is_unlimited(): void
    {
        $plan = new SplitPlan(['max_order_sum' => null]);

        $this->assertSame(PHP_INT_MAX, $plan->maximumOrderSum());
    }
}
