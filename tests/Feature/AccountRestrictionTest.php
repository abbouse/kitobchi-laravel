<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Hub;
use App\Models\HubStaff;
use App\Models\Seller;
use App\Models\SellerBalanceEntry;
use App\Support\CourierLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Do'kon, kuryer va hub xodimini cheklash: sessiyalar yopiladi, pul yechish muzlaydi, balans faqat daftar orqali. */
class AccountRestrictionTest extends TestCase
{
    use CatalogFixtures, CourierFlowFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        CourierLimits::flush();
        $this->mock(\App\Services\OrderStatusPushService::class)->shouldIgnoreMissing();
        $this->mock(\App\Services\CourierBroadcaster::class)->shouldIgnoreMissing();
    }

    private function admin(string $role = 'superadmin', array $permissions = []): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Admin '.$role, 'email' => $role.uniqid().'@t.uz', 'password' => bcrypt('x'),
            'role' => $role, 'permissions' => $permissions, 'is_active' => 1,
        ]);
    }

    public function test_blocked_seller_and_staff_are_logged_out_and_cannot_withdraw(): void
    {
        $store = $this->makeSeller(['balance' => 500000]);
        $staff = $this->makeSeller(['parent_id' => $store->id, 'staff_status' => 'active']);
        $storeToken = $store->createToken('t')->plainTextToken;
        $staffToken = $staff->createToken('t')->plainTextToken;

        $this->withToken($storeToken)->getJson('/api/v1/seller/session/config')->assertOk();

        $this->actingAs($this->admin(), 'panel')
            ->post("/boshqaruv/sellers/{$store->id}/block", ['reason' => 'Soxta mahsulot shikoyatlari', 'duration' => '7_days'])
            ->assertSessionHas('success');

        $store->refresh();
        $this->assertSame('blocked', $store->status, 'enum endi blocked qiymatini saqlaydi');
        $this->assertNotNull($store->blocked_until);
        $this->assertSame(0, DB::table('personal_access_tokens')->whereIn('tokenable_id', [$store->id, $staff->id])->where('tokenable_type', $store->getMorphClass())->count());

        // Eski token bilan ham, yangi login bilan ham kira olmaydi
        $this->app['auth']->forgetGuards();
        $this->withToken($staffToken)->getJson('/api/v1/seller/session/config')->assertStatus(401);
        Sanctum::actingAs($staff, ['*'], 'seller');
        $this->getJson('/api/v1/seller/session/config')->assertStatus(401)->assertJsonPath('error_code', 'seller_blocked');
        Sanctum::actingAs($store, ['*'], 'seller');
        $this->postJson('/api/v1/seller/transactions/withdrawal', ['amount' => 100000])->assertStatus(401);

        $login = $this->postJson('/api/v1/seller/login', [
            'phone_number' => $staff->phone_number, 'password' => 'x', 'device_id' => 'd', 'device_name' => 'n', 'platform' => 'android',
        ])->json();
        $this->assertSame('seller_blocked', $login['error_code'] ?? null);
        $this->assertArrayNotHasKey('token', $login);

        // Muddat tugagach avtomatik ochiladi
        $store->forceFill(['blocked_until' => now()->subMinute()])->save();
        $this->artisan('accounts:expire-blocks')->assertSuccessful();
        $this->assertSame('approved', $store->fresh()->status);
    }

    public function test_payout_freeze_and_ledger_balance_adjustment(): void
    {
        $store = $this->makeSeller(['balance' => 300000]);
        $ops = $this->admin('operations', ['sellers']);
        $finance = $this->admin('finance', ['finance', 'sellers']);

        // Oddiy operator pulga tegolmaydi
        $this->actingAs($ops, 'panel')->post("/boshqaruv/sellers/{$store->id}/payout-freeze", ['frozen' => 1, 'reason' => 'Tekshiruv'])->assertSessionHas('error');
        $this->actingAs($ops, 'panel')->post("/boshqaruv/sellers/{$store->id}/balance-adjust", ['amount' => 1000, 'reason' => 'Sinov uchun'])->assertSessionHas('error');

        $this->actingAs($finance, 'panel')->post("/boshqaruv/sellers/{$store->id}/payout-freeze", ['frozen' => 1, 'reason' => 'Qaytarishlar tekshiruvi'])->assertSessionHas('success');
        Sanctum::actingAs($store, ['*'], 'seller');
        $this->postJson('/api/v1/seller/transactions/withdrawal', ['amount' => 100000])->assertStatus(423)->assertJsonPath('error_code', 'payouts_frozen');

        $this->actingAs($finance, 'panel')->post("/boshqaruv/sellers/{$store->id}/balance-adjust", ['amount' => -50000, 'reason' => 'Brak kitob uchun jarima'])->assertSessionHas('success');
        $this->assertSame(250000, (int) $store->fresh()->balance);
        $entry = SellerBalanceEntry::query()->where('seller_id', $store->id)->latest('id')->firstOrFail();
        $this->assertSame(-50000, $entry->amount);
        $this->assertSame('admin_adjustment', $entry->type);
        $this->actingAs($finance, 'panel')->post("/boshqaruv/sellers/{$store->id}/balance-adjust", ['amount' => -900000, 'reason' => 'Ortiqcha ayirish'])->assertSessionHas('error');
    }

    public function test_seller_edit_form_cannot_overwrite_balance_or_silently_block(): void
    {
        $store = $this->makeSeller(['balance' => 120000, 'region' => 'Toshkent']);
        $payload = ['shop_name' => $store->shop_name, 'phone_number' => $store->phone_number, 'region' => 'Toshkent', 'status' => 'approved', 'balance' => 9999999];

        $this->actingAs($this->admin(), 'panel')->put("/boshqaruv/sellers/{$store->id}", $payload);
        $this->assertSame(120000, (int) $store->fresh()->balance, 'Forma balansni qayta yozmaydi');

        $this->actingAs($this->admin(), 'panel')->put("/boshqaruv/sellers/{$store->id}", ['status' => 'blocked'] + $payload)->assertSessionHasErrors('status');
        $this->assertSame('approved', $store->fresh()->status);
    }

    public function test_blocked_courier_is_logged_out_and_untaken_orders_return_to_pool(): void
    {
        ['sold' => $sold] = $this->makeCourierOrder(cod: false);
        $courier = $this->makeCourier();
        Sanctum::actingAs($courier, ['*'], 'courier');
        $this->postJson("/api/v1/courier/orders/confirm/{$sold->id}")->assertOk();
        $token = $courier->createToken('t')->plainTextToken;

        $this->actingAs($this->admin(), 'panel')
            ->post("/boshqaruv/couriers/{$courier->id}/block", ['reason' => 'Mijozga qo‘pollik', 'duration' => 'forever'])
            ->assertSessionHas('success');

        $this->assertSame('blocked', $courier->fresh()->status);
        $this->assertNull($courier->fresh()->blocked_until);
        $row = CourierOrder::query()->where('order_id', $sold->id)->firstOrFail();
        $this->assertNull($row->courier_id, 'Olib ketilmagan buyurtma boshqa kuryerlarga qaytdi');
        $this->assertSame('pending', $row->status_code);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/courier/orders/my')->assertStatus(401);
    }

    public function test_store_courier_stops_when_store_is_blocked(): void
    {
        $store = $this->makeSeller();
        $courier = $this->makeCourier();
        $courier->forceFill(['seller_id' => $store->id])->save();
        $this->assertTrue($courier->fresh()->canWork());

        $store->forceFill(['status' => 'blocked'])->save();

        $this->assertFalse($courier->fresh()->canWork());
        Sanctum::actingAs($courier->fresh(), ['*'], 'courier');
        $this->getJson('/api/v1/courier/orders/my')->assertStatus(401)->assertJsonPath('error_code', 'courier_blocked');
    }

    public function test_disabled_hub_staff_and_closed_hub_lose_access(): void
    {
        $hub = Hub::query()->forceCreate(['name' => 'Hub', 'code' => 'H'.random_int(100, 999), 'country_code' => 'UZ', 'is_active' => 1]);
        $staff = HubStaff::query()->forceCreate(['hub_id' => $hub->id, 'username' => 'op'.uniqid(), 'full_name' => 'Operator', 'password' => bcrypt('x'), 'role' => 'manager', 'is_active' => 1]);
        $token = $staff->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/hub/me')->assertOk();

        $hub->update(['is_active' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/hub/me')->assertStatus(401);

        Sanctum::actingAs($staff->fresh(), ['*'], 'hub');
        $this->getJson('/api/v1/hub/me')->assertStatus(401)->assertJsonPath('error_code', 'hub_inactive');
    }
}
