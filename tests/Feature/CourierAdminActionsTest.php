<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Support\CourierDeliveryAttempts;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Boshqaruv amallari (bo'shatish, biriktirish, qotganlar) va kitobga xos
 * "yetkazib bo'lmadi → qayta urinish" oqimi.
 */
class CourierAdminActionsTest extends TestCase
{
    use CatalogFixtures, CourierFlowFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        CourierLimits::flush();
        CourierDeliveryAttempts::flush();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
        $this->mock(\App\Services\OrderStatusPushService::class)->shouldIgnoreMissing();
        $this->mock(\App\Services\CourierBroadcaster::class)->shouldIgnoreMissing();
    }

    private function admin(): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'root'.uniqid().'@test.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
    }

    private function accept(Couriers $courier, int $soldId)
    {
        Sanctum::actingAs($courier, ['*'], 'courier');

        return $this->postJson("/api/v1/courier/orders/confirm/{$soldId}");
    }

    public function test_admin_releases_and_reassigns_courier(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: true, amount: 30000);
        $first = $this->makeCourier(100000);
        $this->accept($first, $sold->id)->assertOk();
        $courierOrder = CourierOrder::where('order_id', $sold->id)->firstOrFail();

        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/courier-orders')
            ->post("/boshqaruv/courier-orders/{$courierOrder->id}/release", ['reason' => 'courier_unresponsive'])
            ->assertSessionHas('success');

        $this->assertNull($courierOrder->fresh()->courier_id);
        $this->assertSame('pending', $courierOrder->fresh()->status_code);
        $this->assertSame(0, (int) $first->fresh()->cod_reserved_amount);

        $second = $this->makeCourier(100000);
        $this->post("/boshqaruv/courier-orders/{$courierOrder->id}/assign", ['courier_id' => $second->id])
            ->assertSessionHas('success');

        $this->assertSame((int) $second->id, (int) $courierOrder->fresh()->courier_id);
        $this->assertSame('in_delivery', $courierOrder->fresh()->status_code);
        $this->assertSame(30000, (int) $second->fresh()->cod_reserved_amount);
    }

    public function test_admin_release_after_pickup_needs_force(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();
        CourierTask::where('order_id', $sold->id)->update(['status_code' => 'picked_up']);
        $courierOrder = CourierOrder::where('order_id', $sold->id)->firstOrFail();

        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/courier-orders')
            ->post("/boshqaruv/courier-orders/{$courierOrder->id}/release", ['reason' => 'other'])
            ->assertSessionHas('error');
        $this->assertSame((int) $courier->id, (int) $courierOrder->fresh()->courier_id);

        $this->post("/boshqaruv/courier-orders/{$courierOrder->id}/release", ['reason' => 'other', 'allow_picked_up' => 1])
            ->assertSessionHas('success');
        $this->assertNull($courierOrder->fresh()->courier_id);
    }

    public function test_blocked_courier_cannot_be_assigned(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $courier->forceFill(['status' => 'blocked'])->save();
        $courierOrder = CourierOrder::where('order_id', $sold->id)->firstOrFail();

        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/courier-orders')
            ->post("/boshqaruv/courier-orders/{$courierOrder->id}/assign", ['courier_id' => $courier->id])
            ->assertSessionHas('error');
        $this->assertNull($courierOrder->fresh()->courier_id);
    }

    public function test_failed_attempt_keeps_book_and_frees_today_limit(): void
    {
        DB::table('project_settings')->update(['courier_max_active_orders' => 1]);
        CourierLimits::flush();

        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();

        // Kitob hali olinmagan — urinish qayd etilmaydi
        $this->postJson("/api/v1/courier/orders/{$sold->id}/attempt-failed", ['reason' => 'customer_absent'])
            ->assertStatus(422);

        CourierTask::where('order_id', $sold->id)->update(['status_code' => 'picked_up']);
        $this->getJson("/api/v1/courier/orders/view/{$sold->id}")
            ->assertOk()
            ->assertJsonPath('data.can_report_failed_attempt', true)
            ->assertJsonPath('data.can_release', false);

        $this->postJson("/api/v1/courier/orders/{$sold->id}/attempt-failed", ['reason' => 'customer_unreachable'])
            ->assertOk()
            ->assertJsonPath('return_required', false)
            ->assertJsonPath('attempt_no', 1);

        $courierOrder = CourierOrder::where('order_id', $sold->id)->firstOrFail();
        $this->assertSame('in_delivery', $courierOrder->status_code, 'Kitob buyurtmasi bekor qilinmaydi');
        $this->assertTrue($courierOrder->next_attempt_at->isFuture());
        $this->assertSame(0, CourierLimits::activeOrdersCount((int) $courier->id), 'Ertangi buyurtma bugungi limitni band qilmaydi');

        // Ikkinchi buyurtmani olishi mumkin (limit=1 bo'lsa ham)
        ['sold' => $other] = $this->makeCourierOrder(cod: false);
        $this->accept($courier, $other->id)->assertOk();
    }

    public function test_refusal_marks_return_required_and_shows_in_stuck(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $this->accept($courier, $sold->id)->assertOk();
        CourierTask::where('order_id', $sold->id)->update(['status_code' => 'picked_up']);

        $this->postJson("/api/v1/courier/orders/{$sold->id}/attempt-failed", ['reason' => 'customer_refused'])
            ->assertOk()
            ->assertJsonPath('return_required', true);

        $courierOrder = CourierOrder::where('order_id', $sold->id)->firstOrFail();
        $this->assertNotNull($courierOrder->return_required_at);
        $this->assertSame(1, CourierLimits::applyStuckScope(CourierOrder::query())->count());

        // Admin yana bir urinish beradi
        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/courier-orders')
            ->post("/boshqaruv/courier-orders/{$courierOrder->id}/retry")
            ->assertSessionHas('success');
        $this->assertNull($courierOrder->fresh()->return_required_at);
        $this->assertSame(0, CourierLimits::applyStuckScope(CourierOrder::query())->count());

        // Admin "qaytgan" desa topshiriq yopiladi
        $this->patch("/boshqaruv/courier-orders/{$courierOrder->id}/status", ['status' => 'returned']);
        $this->assertSame('cancelled', CourierTask::where('order_id', $sold->id)->value('status_code'));
    }

    public function test_courier_orders_page_has_stuck_count(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        CourierOrder::where('order_id', $sold->id)->update(['created_at' => now()->subHours(5)]);
        $this->withoutVite();

        $this->actingAs($this->admin(), 'panel')
            ->get('/boshqaruv/courier-orders?courier_orders_tab=stuck')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('courierOrderCounts.stuck', 1)
                ->has('courierOrders', 1)
                ->where('courierOrders.0.isStuck', true)
                ->where('courierOrders.0.canAssign', true));
    }
}
