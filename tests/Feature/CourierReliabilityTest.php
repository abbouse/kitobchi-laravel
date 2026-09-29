<?php

namespace Tests\Feature;

use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Services\AdminOrderStatusSyncService;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Kuryer oqimining ishonchliligi: bo'shatish, COD bandligi, admin
 * "yetkazildi", bloklangan kuryer, hub last-mile.
 */
class CourierReliabilityTest extends TestCase
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

    private function accept(Couriers $courier, int $soldId)
    {
        Sanctum::actingAs($courier, ['*'], 'courier');

        return $this->postJson("/api/v1/courier/orders/confirm/{$soldId}");
    }

    public function test_courier_releases_order_and_cod_reservation_is_freed(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: true, amount: 50000);
        $first = $this->makeCourier(200000);
        $this->accept($first, $sold->id)->assertOk();
        $this->assertSame(50000, (int) $first->fresh()->cod_reserved_amount);

        $this->postJson("/api/v1/courier/orders/{$sold->id}/release", ['reason' => 'vehicle_problem'])
            ->assertOk();

        $this->assertSame(0, (int) $first->fresh()->cod_reserved_amount);
        $task = CourierTask::where('order_id', $sold->id)->firstOrFail();
        $this->assertNull($task->courier_id);
        $this->assertSame('assigned', $task->status_code);
        $this->assertNull($sold->fresh()->courier_id);
        $this->assertSame('pending', CourierOrder::where('order_id', $sold->id)->value('status_code'));

        // Boshqa kuryer endi qabul qila oladi
        $second = $this->makeCourier(200000);
        $this->accept($second, $sold->id)->assertOk();
        $this->assertSame((int) $second->id, (int) $sold->fresh()->courier_id);
    }

    public function test_release_after_pickup_is_refused(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();
        CourierTask::where('order_id', $sold->id)->update(['status_code' => 'picked_up']);

        $this->postJson("/api/v1/courier/orders/{$sold->id}/release", ['reason' => 'personal'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'already_picked_up');
    }

    public function test_cancel_releases_cod_and_admin_delivered_settles_task(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: true, amount: 40000);
        $courier = $this->makeCourier(100000);
        $this->accept($courier, $sold->id)->assertOk();
        $this->assertSame(40000, (int) $courier->fresh()->cod_reserved_amount);

        app(AdminOrderStatusSyncService::class)->updateCourierOrder(
            CourierOrder::where('order_id', $sold->id)->firstOrFail(), 'delivered'
        );

        $task = CourierTask::where('order_id', $sold->id)->firstOrFail();
        $this->assertSame('completed', $task->status_code);
        $this->assertNotNull($task->wallet_debited_at);
        $fresh = $courier->fresh();
        $this->assertSame(0, (int) $fresh->cod_reserved_amount);
        $payout = (int) CourierOrder::where('order_id', $sold->id)->value('settled_amount');
        $this->assertSame(100000 - 40000 + $payout, (int) $fresh->balance, 'Naqd summa yechildi, yetkazish haqi qo\'shildi');

        // Bekor qilish: boshqa buyurtmada bandlik qaytadi
        ['sold' => $other] = $this->makeCourierOrder(cod: true, amount: 30000);
        $this->accept($courier, $other->id)->assertOk();
        $this->assertSame(30000, (int) $courier->fresh()->cod_reserved_amount);
        app(AdminOrderStatusSyncService::class)->updateCourierOrder(
            CourierOrder::where('order_id', $other->id)->firstOrFail(), 'cancelled'
        );
        $this->assertSame(0, (int) $courier->fresh()->cod_reserved_amount);
        $this->assertSame('cancelled', CourierTask::where('order_id', $other->id)->value('status_code'));
    }

    public function test_blocked_courier_is_logged_out(): void
    {
        $courier = $this->makeCourier();
        $token = $courier->createToken('app')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/courier/orders/available')->assertOk();

        $courier->update(['status' => 'blocked']);
        $this->assertSame(0, $courier->tokens()->count());
        $this->assertFalse((bool) $courier->fresh()->is_online);

        // Token o'chirilgan bo'lsa ham, eski sessiyali kuryer ham to'xtaydi
        Sanctum::actingAs($courier->fresh(), ['*'], 'courier');
        $this->getJson('/api/v1/courier/orders/available')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'courier_blocked');
    }

    public function test_hub_last_mile_courier_can_deliver(): void
    {
        ['sold' => $sold, 'courierOrder' => $co, 'fulfillmentId' => $fid] =
            $this->makeCourierOrder(mode: 'hub_based', cod: false, fulfillmentStatus: 'assigned_last_mile');
        // Hubdan yuborilgan: last-mile topshirig'i yaratiladi
        app(\App\Services\CourierTaskOrchestratorService::class)
            ->ensureLastMileTask(\App\Models\OrderFulfillment::findOrFail($fid));

        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();
        $this->assertSame('packing', $sold->fresh()->status_code, 'Last-mile qabulida holat pasaymaydi');

        $res = $this->postJson("/api/v1/courier/orders/toCustomer/{$sold->qr}");
        $this->assertSame(200, $res->status(), (string) $res->getContent());
        $this->assertSame('customer_received', $sold->fresh()->status_code);
        $this->assertSame('completed', CourierTask::where('order_id', $sold->id)->where('leg', 'last_mile')->value('status_code'));
    }

    public function test_hub_flow_without_courier_scan_reaches_customer(): void
    {
        ['sold' => $sold, 'fulfillmentId' => $fid] =
            $this->makeCourierOrder(mode: 'hub_based', cod: false, fulfillmentStatus: 'awaiting_seller_prep');
        $hubId = (int) \App\Models\OrderFulfillment::findOrFail($fid)->hub_id;
        $staff = \App\Models\HubStaff::query()->forceCreate([
            'hub_id' => $hubId, 'username' => 'mgr'.random_int(100, 999), 'full_name' => 'Menejer',
            'password' => bcrypt('x'), 'role' => 'manager', 'is_active' => 1,
        ]);
        Sanctum::actingAs($staff, ['*'], 'hub');

        // Do'kon o'zi olib keldi: kuryer ham, do'kon skaneri ham yo'q
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/arrive")->assertOk();
        $this->assertSame('handed_to_courier', \App\Models\SellerOrder::where('order_id', $sold->id)->value('status_code'));
        $this->assertSame(0, CourierOrder::where('order_id', $sold->id)->where('status_code', 'pending')->count(),
            'Keraksiz first-mile qatori kuryer ro\'yxatida qolmaydi');

        foreach (['qc', 'pack', 'label'] as $step) {
            $this->postJson("/api/v1/hub/fulfillments/{$fid}/{$step}")->assertOk();
        }
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/dispatch", ['dispatch_method' => 'courier'])->assertOk();
        $this->assertSame('in_delivery', $sold->fresh()->status_code);

        // Hali kuryer yo'q — topshirib bo'lmaydi
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/handover-last-mile")->assertStatus(422);

        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();
        $this->assertSame('in_delivery', $sold->fresh()->status_code);

        Sanctum::actingAs($staff, ['*'], 'hub');
        $this->postJson("/api/v1/hub/fulfillments/{$fid}/handover-last-mile")->assertOk();
        $this->assertSame('out_for_delivery', \App\Models\OrderFulfillment::findOrFail($fid)->status_code);
        $this->assertSame('picked_up', CourierTask::where('order_id', $sold->id)->where('leg', 'last_mile')->value('status_code'));

        Sanctum::actingAs($courier, ['*'], 'courier');
        $this->postJson("/api/v1/courier/orders/toCustomer/{$sold->qr}")->assertOk();
        $this->assertSame('customer_received', $sold->fresh()->status_code);
        $this->assertSame('delivered', \App\Models\OrderFulfillment::findOrFail($fid)->status_code);
    }
}
