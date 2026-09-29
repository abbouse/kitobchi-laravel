<?php

namespace Tests\Feature;

use App\Support\CourierLimits;
use App\Support\DeliverySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Mijoz tanlagan yetkazish kuni va vaqti.
 */
class DeliveryScheduleTest extends TestCase
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

    public function test_days_start_day_after_tomorrow_for_seven_days(): void
    {
        $days = DeliverySchedule::days();
        $this->assertCount(7, $days);
        $this->assertSame(today()->addDays(2)->toDateString(), $days[0]['date']);
        $this->assertSame('Ertadan keyin', $days[0]['label_uz']);
        $this->assertSame(today()->addDays(8)->toDateString(), $days[6]['date']);

        $this->assertTrue(DeliverySchedule::isValid(today()->addDays(3)->toDateString(), '14-18'));
        $this->assertFalse(DeliverySchedule::isValid(today()->addDay()->toDateString(), '14-18'));
        $this->assertFalse(DeliverySchedule::isValid(today()->addDays(9)->toDateString(), '14-18'));
        $this->assertFalse(DeliverySchedule::isValid(today()->addDays(3)->toDateString(), '08-10'));

        // Predzakaz: kunlar jo'natish kunidan boshlanadi
        $late = DeliverySchedule::days(today()->addDays(12));
        $this->assertSame(today()->addDays(12)->toDateString(), $late[0]['date']);
    }

    public function test_scheduled_order_reaches_couriers_on_delivery_day(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $sold->forceFill(['delivery_date' => today()->addDays(2)->toDateString(), 'delivery_slot' => '14-18'])->save();

        $courier = $this->makeCourier();
        Sanctum::actingAs($courier, ['*'], 'courier');

        $this->getJson('/api/v1/courier/orders/available')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")
            ->assertStatus(422)->assertJsonPath('error_code', 'delivery_day_not_ready');

        $sold->forceFill(['delivery_date' => today()->toDateString()])->save();
        $this->getJson('/api/v1/courier/orders/available')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.delivery_window', today()->format('d.m').' · 14:00–18:00');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")->assertOk();
    }
}
