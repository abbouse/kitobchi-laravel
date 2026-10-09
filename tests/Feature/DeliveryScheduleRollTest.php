<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kechikkan yetkazishlar keyingi kunga ko'chadi; boshqaruvda 7 kunlik jadval. */
class DeliveryScheduleRollTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::query()->forceCreate(['name' => 'T', 'lastname' => 'U', 'phone_number' => '998907770000', 'password' => bcrypt('x')]);
    }

    private function order(string $code, ?string $date, string $slot = '14-18'): Sold
    {
        return Sold::query()->forceCreate([
            'user_id' => $this->user->id, 'amount' => 10000, 'deliveryType' => 'courier_service',
            'status' => 'P', 'status_code' => $code, 'paymentStatus' => 2, 'payment_status_code' => 'paid',
            'items' => [], 'delivery_date' => $date, 'delivery_slot' => $date ? $slot : null,
        ]);
    }

    public function test_overdue_undelivered_orders_move_to_today_and_keep_history(): void
    {
        $late = $this->order('packing', today()->subDays(2)->toDateString());
        $onWay = $this->order('in_delivery', today()->subDay()->toDateString(), '18-22');
        $done = $this->order('delivered', today()->subDay()->toDateString());
        $future = $this->order('pending', today()->addDays(3)->toDateString());

        $this->artisan('orders:roll-overdue-deliveries --no-push')->assertSuccessful();

        $late->refresh();
        $this->assertSame(today()->toDateString(), $late->delivery_date->toDateString());
        $this->assertSame(today()->subDays(2)->toDateString(), $late->delivery_original_date->toDateString());
        $this->assertSame(1, $late->delivery_rescheduled_count);
        $this->assertSame('14-18', $late->delivery_slot);

        $this->assertSame(today()->toDateString(), $onWay->fresh()->delivery_date->toDateString());
        $this->assertSame('18-22', $onWay->fresh()->delivery_slot);
        $this->assertSame(today()->subDay()->toDateString(), $done->fresh()->delivery_date->toDateString());
        $this->assertSame(0, (int) $done->fresh()->delivery_rescheduled_count);
        $this->assertSame(today()->addDays(3)->toDateString(), $future->fresh()->delivery_date->toDateString());

        // Ikkinchi marta ham kechiksa — asl kun saqlanadi
        $late->forceFill(['delivery_date' => today()->subDay()->toDateString()])->save();
        $this->artisan('orders:roll-overdue-deliveries --no-push')->assertSuccessful();
        $late->refresh();
        $this->assertSame(2, $late->delivery_rescheduled_count);
        $this->assertSame(today()->subDays(2)->toDateString(), $late->delivery_original_date->toDateString());
    }

    public function test_orders_page_shows_upcoming_schedule_and_filters_by_day(): void
    {
        $this->withoutVite();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r' . uniqid() . '@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);

        $tomorrow = today()->addDay()->toDateString();
        $a = $this->order('pending', $tomorrow, '10-14');
        $this->order('packing', $tomorrow, '18-22');
        $this->order('delivered', $tomorrow, '10-14');
        $this->order('pending', today()->addDays(8)->toDateString(), '14-18');
        $this->order('pending', today()->subDay()->toDateString());

        $page = $this->actingAs($admin, 'panel')->get('/boshqaruv/orders?orders_day=' . $tomorrow . '&orders_slot=10-14');
        $page->assertOk();
        $props = $page->viewData('page')['props'];

        $days = collect($props['deliverySchedule']['days']);
        $this->assertCount(9, $days);
        $this->assertSame('Ertaga', $days[1]['label']);
        $this->assertSame(2, $days[1]['count']);
        $this->assertSame(['10-14' => 1, '14-18' => 0, '18-22' => 1], $days[1]['slots']);
        $this->assertSame(1, $days[8]['count']);
        $this->assertSame(1, $props['deliverySchedule']['overdue']);

        $this->assertSame([$a->id], array_column($props['orders'], 'rawId'));
        $this->assertSame($tomorrow, $props['orderFilters']['day']);
    }
}
