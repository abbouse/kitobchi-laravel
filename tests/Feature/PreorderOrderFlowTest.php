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

    public function test_card_order_cancel_window_is_ten_minutes(): void
    {
        $this->mock(\App\Services\PaylovOrderPaymentService::class)->shouldIgnoreMissing();
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $sold->forceFill([
            'status' => 'A', 'status_code' => 'pending',
            'paymentStatus' => 1, 'payment_status_code' => 'held',
        ])->save();
        \Illuminate\Support\Facades\DB::table('transactions')->insert([
            'owner_id' => $sold->user_id, 'order_id' => $sold->id, 'amount' => 50000,
            'payment_type' => 'order', 'provider' => 'paylov', 'state' => 1,
            'create_time' => now()->subMinutes(11)->format('Y-m-d H:i:s'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = \App\Models\User::find($sold->user_id);
        Sanctum::actingAs($user, ['*'], 'user');

        $this->getJson("/api/v1/kitobchi/purchase/details/{$sold->id}")
            ->assertOk()->assertJsonPath('data.0.card_cancel_until', null);
        $this->getJson("/api/v1/kitobchi/purchase/cancel/{$sold->id}")
            ->assertStatus(400)->assertJsonPath('message', 'cancel_order_error_expired');

        \Illuminate\Support\Facades\DB::table('transactions')->where('order_id', $sold->id)
            ->update(['create_time' => now()->subMinutes(3)->format('Y-m-d H:i:s')]);
        $this->getJson("/api/v1/kitobchi/purchase/details/{$sold->id}")
            ->assertOk()->assertJsonPath('data.0.card_cancel_until', fn ($v) => $v !== null);
        $this->getJson("/api/v1/kitobchi/purchase/cancel/{$sold->id}")->assertOk();
        $this->assertSame('cancelled', $sold->fresh()->status_code);
    }
}
