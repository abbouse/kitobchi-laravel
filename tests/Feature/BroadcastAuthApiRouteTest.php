<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastAuthApiRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_broadcasting_auth_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->getByName('api.broadcasting.auth');

        $this->assertNotNull($route);
        $this->assertSame('api/broadcasting/auth', $route->uri());
        $this->assertContains('auth:user,seller,courier', $route->gatherMiddleware());
    }

    public function test_guest_is_rejected_not_404(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'presence-global-online',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_reaches_broadcast_auth(): void
    {
        $user = User::query()->forceCreate([
            'name' => 'Socket', 'lastname' => 'Test', 'phone_number' => '998901112244',
            'password' => bcrypt('secret'),
        ]);
        Sanctum::actingAs($user, ['*'], 'user');

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'presence-global-online',
        ]);

        $this->assertNotContains($response->status(), [401, 404, 405]);
    }
}
