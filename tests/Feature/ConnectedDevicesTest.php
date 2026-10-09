<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** "Ulangan qurilmalar": o'lik sessiyalar ro'yxatda qolmaydi, logout qatorni o'chiradi. */
class ConnectedDevicesTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    private function makeSession(User $user, string $deviceId): string
    {
        $plain = $user->createToken('user_token')->plainTextToken;
        DB::table('connected_devices')->insert([
            'user_id' => $user->id, 'user_type' => 'user', 'device_id' => $deviceId,
            'device_name' => 'iPhone 15', 'platform' => 'ios',
            'token' => hash('sha256', explode('|', $plain, 2)[1]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $plain;
    }

    public function test_dead_sessions_are_hidden_and_logout_removes_device(): void
    {
        $user = User::query()->forceCreate([
            'name' => 'Ali', 'lastname' => 'V', 'phone_number' => '998901112299', 'password' => bcrypt('x'),
        ]);
        $old = $this->makeSession($user, 'old-install');
        $cur = $this->makeSession($user, 'new-install');

        // Eski sessiya tokeni o'chib ketgan (masalan ilova qayta o'rnatilgan)
        $user->tokens()->where('token', hash('sha256', explode('|', $old, 2)[1]))->delete();

        $this->withToken($cur)->getJson('/api/v1/kitobchi/user/devices')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_id', 'new-install');

        $this->withToken($cur)->getJson('/api/v1/kitobchi/user/logout')->assertOk();
        $this->assertSame(0, DB::table('connected_devices')->where('user_id', $user->id)->count());
    }
}
