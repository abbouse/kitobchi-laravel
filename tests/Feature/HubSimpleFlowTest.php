<?php

namespace Tests\Feature;

use App\Models\HubStaff;
use App\Models\OrderFulfillment;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Yangi hub ilovasi oqimi: kutilmoqda → qabul → tayyorlash → jo'natish. */
class HubSimpleFlowTest extends TestCase
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

    private function staffFor(int $fid): HubStaff
    {
        return HubStaff::query()->forceCreate([
            'hub_id' => (int) OrderFulfillment::findOrFail($fid)->hub_id, 'username' => 'm'.random_int(1000, 9999),
            'full_name' => 'Menejer', 'password' => bcrypt('x'), 'role' => 'manager', 'is_active' => 1,
        ]);
    }

    private function queueIds(string $queue): array
    {
        return collect($this->getJson("/api/v1/hub/queues/{$queue}")->assertOk()->json('data.data'))->pluck('id')->all();
    }

    public function test_simple_flow_to_courier_and_post(): void
    {
        ['sold' => $sold, 'fulfillmentId' => $fid] = $this->makeCourierOrder('hub_based', cod: false, fulfillmentStatus: 'awaiting_seller_prep');
        $staff = $this->staffFor($fid);
        Sanctum::actingAs($staff, ['*'], 'hub');

        $this->assertContains($fid, $this->queueIds('incoming'));
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/arrive")->assertOk();
        $this->assertContains($fid, $this->queueIds('prepare'));

        $this->postJson("/api/v1/hub/fulfillments/{$fid}/prepare")->assertOk()
            ->assertJsonPath('fulfillment.status_code', 'labeled');
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/prepare")->assertStatus(422);
        $this->assertContains($fid, $this->queueIds('send'));

        $this->getJson('/api/v1/hub/dashboard')->assertOk()->assertJsonPath('counts.send', 1);

        $this->postJson("/api/v1/hub/fulfillments/{$fid}/dispatch", ['dispatch_method' => 'courier'])->assertOk();
        $this->assertContains($fid, $this->queueIds('courier'));

        // Endi kuryerlarga ko'rinadi
        Sanctum::actingAs($this->makeCourier(), ['*'], 'courier');
        $ids = collect($this->getJson('/api/v1/courier/orders/available')->json('data'))->pluck('order_id')->map(fn ($i) => (int) $i)->all();
        $this->assertContains($sold->id, $ids);

        // Pochta yo'li
        ['fulfillmentId' => $pid] = $this->makeCourierOrder('postal_only_via_hub', cod: false, fulfillmentStatus: 'picked_from_seller');
        Sanctum::actingAs($this->staffFor($pid), ['*'], 'hub');
        $this->postJson("/api/v1/hub/fulfillments/{$pid}/arrive")->assertOk();
        $this->postJson("/api/v1/hub/fulfillments/{$pid}/prepare")->assertOk();
        $this->postJson("/api/v1/hub/fulfillments/{$pid}/dispatch", [
            'dispatch_method' => 'postal', 'postal_tracking_number' => 'EMS123', 'postal_provider' => 'uzpost',
        ])->assertOk()->assertJsonPath('fulfillment.status_code', 'dispatched_to_post')
            ->assertJsonPath('fulfillment.postal_tracking_number', 'EMS123');
    }
}
