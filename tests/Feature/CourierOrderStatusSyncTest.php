<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Models\OrderFulfillment;
use App\Services\AdminOrderStatusSyncService;
use App\Services\OrderService;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Asosiy buyurtma holati kuryer (courier_orders + courier_tasks) va do'kon
 * tomoniga doim birga o'tadi — hech kim olmagan buyurtmalar ham.
 */
class CourierOrderStatusSyncTest extends TestCase
{
    use CatalogFixtures, CourierFlowFixtures, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        CourierLimits::flush();
        $this->mock(\App\Services\OrderStatusPushService::class)->shouldIgnoreMissing();
        $this->mock(\App\Services\CourierBroadcaster::class)->shouldIgnoreMissing();
        $this->admin = Admin::query()->forceCreate([
            'name' => 'Admin', 'email' => 'sync'.uniqid().'@t.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);
    }

    private function feedIds(Couriers $courier): array
    {
        Sanctum::actingAs($courier, ['*'], 'courier');

        return collect($this->getJson('/api/v1/courier/orders/available')->assertOk()->json('data'))
            ->pluck('order_id')->map(fn ($id) => (int) $id)->all();
    }

    private function adminSets(int $soldId, string $status): void
    {
        $this->actingAs($this->admin, 'panel')
            ->patch("/boshqaruv/orders/{$soldId}/status", ['status' => $status])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');
    }

    private function courierStatus(int $soldId): string
    {
        return (string) CourierOrder::where('order_id', $soldId)->value('status_code');
    }

    public function test_admin_terminal_status_closes_untaken_courier_order_and_tasks(): void
    {
        $courier = $this->makeCourier();
        foreach (['delivered', 'customer_received', 'returned'] as $status) {
            foreach (['direct_courier', 'hub_based'] as $mode) {
                ['sold' => $sold] = $this->makeCourierOrder($mode, cod: $mode === 'direct_courier');
                $this->assertContains($sold->id, $this->feedIds($courier), 'Avval kuryer ro‘yxatida bor');

                $this->adminSets($sold->id, $status);

                $this->assertSame($status, $this->courierStatus($sold->id), "{$mode} → {$status}: kuryer qatori ham yangilansin");
                $this->assertSame(0, CourierTask::where('order_id', $sold->id)->whereNotIn('status_code', ['completed', 'cancelled', 'failed'])->count());
                $this->assertSame('handed_to_courier', DB::table('seller_orders')->where('order_id', $sold->id)->value('status_code'));
                $this->assertNotContains($sold->id, $this->feedIds($courier));
            }
        }
    }

    public function test_admin_delivered_completes_courier_task_and_settles_cash(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: true, amount: 40000);
        $courier = $this->makeCourier(100000);
        Sanctum::actingAs($courier, ['*'], 'courier');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")->assertOk();
        $this->assertSame(40000, (int) $courier->fresh()->cod_reserved_amount);
        $this->assertSame(1, CourierLimits::activeOrdersCount((int) $courier->id));

        $this->adminSets($sold->id, 'delivered');

        $task = CourierTask::where('order_id', $sold->id)->firstOrFail();
        $this->assertSame('completed', $task->status_code);
        $this->assertNotNull($task->wallet_debited_at);
        $this->assertSame(0, (int) $courier->fresh()->cod_reserved_amount, 'Naqd bandlik bo‘shasin');
        $this->assertContains($this->courierStatus($sold->id), ['delivered', 'customer_received']);
        $this->assertSame(0, CourierLimits::activeOrdersCount((int) $courier->id), 'Kuryer band bo‘lib qolmasin');
        $this->assertSame('paid', $sold->fresh()->payment_status_code);
    }

    public function test_admin_in_delivery_without_courier_leaves_feed_but_can_still_be_assigned(): void
    {
        ['sold' => $sold, 'courierOrder' => $courierOrder] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        $this->assertContains($sold->id, $this->feedIds($courier));

        $this->adminSets($sold->id, 'in_delivery');

        $this->assertSame('in_delivery', $this->courierStatus($sold->id), 'Kuryersiz yo‘lda — "kutilmoqda"da qolmasin');
        $this->assertNotContains($sold->id, $this->feedIds($courier));

        // Keyin boshqaruvdan kuryer biriktirish ishlaydi
        $this->actingAs($this->admin, 'panel')
            ->post("/boshqaruv/courier-orders/{$courierOrder->id}/assign", ['courier_id' => $courier->id])
            ->assertSessionMissing('error');
        $row = CourierOrder::where('order_id', $sold->id)->firstOrFail();
        $this->assertSame((int) $courier->id, (int) $row->courier_id);
        $this->assertSame('in_delivery', $row->status_code);
    }

    public function test_hub_marks_delivered_closes_courier_side(): void
    {
        ['sold' => $sold, 'fulfillmentId' => $fulfillmentId] = $this->makeCourierOrder('hub_based', cod: false, fulfillmentStatus: 'out_for_delivery');
        $sold->forceFill(['status' => 'B', 'status_code' => 'in_delivery'])->save();
        DB::table('seller_orders')->where('order_id', $sold->id)->update(['status' => 3, 'status_code' => 'handed_to_courier']);
        $this->feedIds($this->makeCourier()); // topshiriqlar yaratiladi

        app(AdminOrderStatusSyncService::class)->updateFulfillmentStatus(OrderFulfillment::findOrFail($fulfillmentId), 'delivered');

        $this->assertSame('delivered', $sold->fresh()->status_code);
        $this->assertSame('delivered', $this->courierStatus($sold->id));
        $this->assertSame(0, CourierTask::where('order_id', $sold->id)->whereNotIn('status_code', ['completed', 'cancelled', 'failed'])->count());
    }

    public function test_customer_cancel_releases_courier_cod_reservation(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: true, amount: 30000);
        $courier = $this->makeCourier(100000);
        Sanctum::actingAs($courier, ['*'], 'courier');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")->assertOk();
        $this->assertSame(30000, (int) $courier->fresh()->cod_reserved_amount);

        app(OrderService::class)->cancelOrder($sold->fresh(), strict: false);

        $this->assertSame('cancelled', $this->courierStatus($sold->id));
        $this->assertSame('cancelled', CourierTask::where('order_id', $sold->id)->value('status_code'));
        $this->assertSame(0, (int) $courier->fresh()->cod_reserved_amount);
    }

    public function test_reconcile_command_repairs_stale_rows(): void
    {
        $courier = $this->makeCourier();
        ['sold' => $delivered] = $this->makeCourierOrder(cod: false);
        ['sold' => $cancelled] = $this->makeCourierOrder(cod: false);
        ['sold' => $open] = $this->makeCourierOrder(cod: false);
        $this->feedIds($courier); // topshiriqlar yaratiladi

        // Eski xato: asosiy buyurtma yopilgan, kuryer tomoni ochiq qolgan
        $delivered->forceFill(['status' => 'C', 'status_code' => 'delivered'])->save();
        $cancelled->forceFill(['status' => 'F', 'status_code' => 'cancelled'])->save();

        $this->artisan('orders:reconcile-courier', ['--dry-run' => true])->expectsOutputToContain('Topildi: 2')->assertSuccessful();
        $this->assertSame('pending', $this->courierStatus($delivered->id));

        $this->artisan('orders:reconcile-courier')->expectsOutputToContain('Tuzatildi: 2')->assertSuccessful();
        $this->assertSame('delivered', $this->courierStatus($delivered->id));
        $this->assertSame('cancelled', $this->courierStatus($cancelled->id));
        $this->assertSame('pending', $this->courierStatus($open->id), 'Ochiq buyurtmaga tegilmaydi');
        $this->assertSame(0, CourierTask::whereIn('order_id', [$delivered->id, $cancelled->id])->whereNotIn('status_code', ['completed', 'cancelled'])->count());
    }
}
