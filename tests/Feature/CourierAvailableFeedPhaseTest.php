<?php

namespace Tests\Feature;

use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Kuryerga faqat yangi, egasi yo'q buyurtmalar chiqadi (hub bosqichidagilar emas). */
class CourierAvailableFeedPhaseTest extends TestCase
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

    private function feedIds(): array
    {
        return collect($this->getJson('/api/v1/courier/orders/available')->assertOk()->json('data'))
            ->pluck('order_id')->map(fn ($id) => (int) $id)->all();
    }

    public function test_only_open_phases_are_visible(): void
    {
        $courier = $this->makeCourier();
        Sanctum::actingAs($courier, ['*'], 'courier');

        $direct = $this->makeCourierOrder('direct_courier', cod: false);
        $hubNew = $this->makeCourierOrder('hub_based', cod: false);
        $hubPacking = $this->makeCourierOrder('hub_based', cod: false, fulfillmentStatus: 'packed');
        $hubAtHub = $this->makeCourierOrder('hub_based', cod: false, fulfillmentStatus: 'arrived_at_hub');
        $directGone = $this->makeCourierOrder('direct_courier', cod: false, fulfillmentStatus: 'picked_from_seller');

        $ids = $this->feedIds();
        $this->assertContains($direct['sold']->id, $ids);
        $this->assertContains($hubNew['sold']->id, $ids);
        $this->assertNotContains($hubPacking['sold']->id, $ids, 'Hubda qadoqlanayotgan buyurtma chiqmasin');
        $this->assertNotContains($hubAtHub['sold']->id, $ids);
        $this->assertNotContains($directGone['sold']->id, $ids);

        // Bekor qilingan buyurtma ham chiqmaydi
        $direct['sold']->forceFill(['status' => 'F', 'status_code' => 'cancelled'])->save();
        $this->assertNotContains($direct['sold']->id, $this->feedIds());
    }
}
