<?php

namespace Tests\Feature;

use App\Models\Sold;
use App\Support\CourierLimits;
use App\Support\OrderPreorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Predzakazli buyurtma jo'natish kunigacha kuryerga chiqmaydi.
 */
class PreorderOrderFlowTest extends TestCase
{
    use CatalogFixtures, CourierFlowFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        CourierLimits::flush();
        $this->mock(\App\Services\OrderStatusPushService::class)->shouldIgnoreMissing();
        $this->mock(\App\Services\CourierBroadcaster::class)->shouldIgnoreMissing();
    }

    public function test_held_preorder_is_hidden_from_couriers_until_ship_date(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $sold->forceFill(['preorder_ships_at' => today()->addDays(5)->toDateString()])->save();
        $this->assertTrue(OrderPreorder::isHeld($sold->fresh()));

        $courier = $this->makeCourier();
        Sanctum::actingAs($courier, ['*'], 'courier');

        $this->getJson('/api/v1/courier/orders/available')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'preorder_not_ready');

        // Jo'natish kuni keldi
        $sold->forceFill(['preorder_ships_at' => today()->toDateString()])->save();
        $this->assertFalse(OrderPreorder::isHeld($sold->fresh()));
        $this->getJson('/api/v1/courier/orders/available')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")->assertOk();
    }

    public function test_ships_at_falls_back_to_item_dates(): void
    {
        $sold = new Sold(['items' => [
            ['item_id' => 1, 'preorder_release_date' => today()->addDays(3)->toDateString()],
            ['item_id' => 2, 'preorder_release_date' => today()->addDays(9)->toDateString()],
            ['item_id' => 3],
        ]]);

        $this->assertSame(today()->addDays(9)->toDateString(), OrderPreorder::shipsAt($sold)->toDateString());
    }

    public function test_release_command_runs(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $sold->forceFill(['preorder_ships_at' => today()->toDateString()])->save();

        $this->artisan('orders:release-preorders')->expectsOutput('Released preorders: 1')->assertSuccessful();
        $this->artisan('orders:release-preorders')->expectsOutput('Released preorders: 0')->assertSuccessful();
    }
}
