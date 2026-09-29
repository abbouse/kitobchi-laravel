<?php

namespace Tests\Feature;

use App\Models\Couriers;
use App\Models\ProjectSetting;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Kuryerning bir vaqtdagi faol buyurtmalar limiti boshqaruvdan sozlanadi.
 */
class CourierActiveOrdersLimitTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        CourierLimits::flush();
    }

    protected function tearDown(): void
    {
        CourierLimits::flush();
        parent::tearDown();
    }

    private function courier(): Couriers
    {
        return Couriers::query()->forceCreate([
            'first_name' => 'Ali', 'last_name' => 'K', 'status' => 'approved', 'is_online' => true,
            'region' => 'Toshkent', 'phone_number' => '+998901234567', 'password' => bcrypt('x'),
        ]);
    }

    private function activeOrder(int $courierId): void
    {
        DB::table('courier_orders')->insert([
            'courier_id' => $courierId, 'order_id' => random_int(1000, 999999),
            'status' => 'in_delivery', 'status_code' => 'in_delivery',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_limit_comes_from_settings_and_is_clamped(): void
    {
        $this->assertSame(3, CourierLimits::maxActiveOrders());

        ProjectSetting::query()->forceCreate(['courier_max_active_orders' => 5]);
        CourierLimits::flush();
        $this->assertSame(5, CourierLimits::maxActiveOrders());

        ProjectSetting::query()->update(['courier_max_active_orders' => 99]);
        CourierLimits::flush();
        $this->assertSame(CourierLimits::MAX_ACTIVE_ORDERS, CourierLimits::maxActiveOrders());
    }

    public function test_accept_is_blocked_at_configured_limit(): void
    {
        ProjectSetting::query()->forceCreate(['courier_max_active_orders' => 1]);
        $courier = $this->courier();
        $this->activeOrder((int) $courier->id);
        Sanctum::actingAs($courier, ['*'], 'courier');

        $this->getJson('/api/v1/courier/orders/available')
            ->assertOk()
            ->assertJsonPath('active_orders_count', 1)
            ->assertJsonPath('max_active_orders', 1);

        $this->postJson('/api/v1/courier/orders/confirm/12345')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'active_orders_limit')
            ->assertJsonPath('max_active_orders', 1);
    }
}
