<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** IDOR va bloklash regressiyalari: mijoz faqat o'z ma'lumotiga tegadi. */
class SecurityRegressionTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    private function user(): User
    {
        return User::query()->forceCreate(['name' => 'U', 'lastname' => 'T', 'phone_number' => '99890'.random_int(1000000, 9999999), 'password' => bcrypt('x')]);
    }

    private function location(User $user): int
    {
        return DB::table('locations')->insertGetId([
            'user_id' => $user->id, 'lat' => '41.3', 'lon' => '69.2', 'fullAddress' => 'Manzil '.$user->id,
            'isDeleted' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_customer_cannot_select_or_delete_another_customers_address(): void
    {
        $victim = $this->user();
        $attacker = $this->user();
        $victimLocation = $this->location($victim);
        $own = $this->location($attacker);

        Sanctum::actingAs($attacker, ['*'], 'user');
        $this->getJson("/api/v1/kitobchi/locations/select/{$victimLocation}")->assertStatus(404);
        $this->assertNull($attacker->fresh()->mainAddressID);
        $this->getJson("/api/v1/kitobchi/locations/select/{$own}")->assertStatus(201);
        $this->assertSame($own, (int) $attacker->fresh()->mainAddressID);

        $this->getJson("/api/v1/kitobchi/locations/delete/{$victimLocation}");
        $this->assertSame(0, (int) DB::table('locations')->where('id', $victimLocation)->value('isDeleted'));
    }

    public function test_blocked_customer_with_leftover_token_gets_423(): void
    {
        $user = $this->user();
        $token = $user->createToken('t')->plainTextToken;
        $user->forceFill(['status' => 'blocked', 'blocked_until' => now()->addDay(), 'block_reason' => 'Firibgarlik'])->save();

        $this->withToken($token)->getJson('/api/v1/kitobchi/user/devices')
            ->assertStatus(423)
            ->assertJsonPath('error_code', 'user_account_blocked');
    }
}
