<?php

namespace Tests\Feature;

use App\Models\Sold;
use App\Models\User;
use App\Support\FlashDelivery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Bepul yetkazish soati: oyna, mijoz limiti, promokod faqat aksiya vaqtida. */
class FlashDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $row = ['flash_delivery_enabled' => 1, 'flash_delivery_start_time' => '20:00', 'flash_delivery_duration' => 15, 'flash_delivery_promo_code' => 'BEPUL15', 'flash_delivery_limit' => 'daily'];
        DB::table('project_settings')->exists()
            ? DB::table('project_settings')->update($row)
            : DB::table('project_settings')->insert($row);
        Cache::forget('flash_delivery:setting');
        $this->user = User::query()->forceCreate(['name' => 'A', 'lastname' => 'B', 'phone_number' => '998901230000', 'password' => bcrypt('x')]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $time): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 ' . $time, 'Asia/Tashkent'));
    }

    public function test_status_before_during_and_after_window(): void
    {
        $this->at('19:50:00');
        $s = $this->getJson('/api/v1/kitobchi/flash-delivery/status')->assertOk()->json();
        $this->assertTrue($s['enabled']);
        $this->assertFalse($s['active']);
        $this->assertSame(600, $s['seconds_until_next']);
        $this->assertSame('20:15', $s['end_time']);
        $this->assertNull($s['eligible']);

        $this->at('20:05:00');
        Sanctum::actingAs($this->user, ['*'], 'user');
        $s = $this->getJson('/api/v1/kitobchi/flash-delivery/status')->assertOk()->json();
        $this->assertTrue($s['active']);
        $this->assertSame(600, $s['seconds_remaining']);
        $this->assertTrue($s['eligible']);

        $this->at('20:20:00');
        $s = FlashDelivery::status($this->user);
        $this->assertFalse($s['active']);
        $this->assertSame(86400 - 1200, $s['seconds_until_next']);
    }

    public function test_once_per_day_and_grace(): void
    {
        $this->at('20:16:00'); // 2 daqiqalik muhlat ichida
        $this->assertTrue(FlashDelivery::appliesTo($this->user));
        $this->at('20:18:00');
        $this->assertFalse(FlashDelivery::appliesTo($this->user));

        $this->at('20:03:00');
        Sold::query()->forceCreate([
            'user_id' => $this->user->id, 'amount' => 10000, 'deliveryType' => 'courier_service', 'status' => 'A',
            'status_code' => 'pending', 'paymentStatus' => 0, 'payment_status_code' => 'cash_pending', 'items' => [],
            'flash_free_delivery' => true, 'flash_delivery_saved' => 15000,
        ]);
        $this->assertFalse(FlashDelivery::appliesTo($this->user));
        $this->assertTrue(FlashDelivery::status($this->user)['used']);

        // Ertasiga yana mumkin
        Carbon::setTestNow(Carbon::parse('2026-10-18 20:01:00', 'Asia/Tashkent'));
        $this->assertTrue(FlashDelivery::appliesTo($this->user));

        // "Umrida bir marta" rejimi
        DB::table('project_settings')->update(['flash_delivery_limit' => 'once']);
        Cache::forget('flash_delivery:setting');
        $this->assertFalse(FlashDelivery::appliesTo($this->user));
    }

    public function test_min_order_amount(): void
    {
        DB::table('project_settings')->update(['flash_delivery_min_amount' => 50000]);
        Cache::forget('flash_delivery:setting');
        $this->at('20:05:00');

        $this->assertSame(50000, FlashDelivery::status($this->user)['min_amount']);
        $this->assertFalse(FlashDelivery::appliesTo($this->user, null, 10000));
        $this->assertTrue(FlashDelivery::appliesTo($this->user, null, 50000));
        // Summa berilmasa (holat so'rovi) — faqat vaqt va limit tekshiriladi
        $this->assertTrue(FlashDelivery::appliesTo($this->user));
    }

    public function test_flash_promo_code_only_works_inside_window(): void
    {
        DB::table('promocodes')->insert([
            'code' => 'BEPUL15', 'type' => 'free_delivery', 'amount' => 0, 'status' => 1, 'expires_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        Sanctum::actingAs($this->user, ['*'], 'user');

        $this->at('12:00:00');
        $ref = new \ReflectionMethod(\App\Http\Controllers\Api\PurchaseController::class, 'validatePromocode');
        $controller = app(\App\Http\Controllers\Api\PurchaseController::class);
        $res = $ref->invoke($controller, 'bepul15', $this->user->id, 50000.0, []);
        $this->assertStringContainsString('20:00–20:15', $res['error']);

        $this->at('20:10:00');
        $res = $ref->invoke($controller, 'BEPUL15', $this->user->id, 50000.0, []);
        $this->assertArrayNotHasKey('error', $res, (string) ($res['error'] ?? ''));
        $this->assertTrue($res['free_delivery']);
    }

    public function test_disabled(): void
    {
        DB::table('project_settings')->update(['flash_delivery_enabled' => 0]);
        Cache::forget('flash_delivery:setting');
        $this->at('20:05:00');
        $this->assertFalse(FlashDelivery::appliesTo($this->user));
        $this->assertFalse($this->getJson('/api/v1/kitobchi/flash-delivery/status')->json('enabled'));
    }
}
