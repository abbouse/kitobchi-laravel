<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin kirgandan keyin sayt bosh sahifasiga emas, boshqaruvga yo'naltiriladi. */
class PanelLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'root@t.uz', 'password' => 'secret123', 'role' => 'superadmin', 'is_active' => 1,
        ]);
    }

    public function test_login_ignores_site_intended_url(): void
    {
        $this->admin();

        $this->withSession(['url.intended' => url('/cart')])
            ->post('/boshqaruv/login', ['email' => 'root@t.uz', 'password' => 'secret123'])
            ->assertRedirect(route('boshqaruv.dashboard'));
    }

    public function test_login_returns_to_requested_panel_page(): void
    {
        $this->admin();

        $this->get('/boshqaruv/orders')->assertRedirect(route('boshqaruv.login'));
        $this->post('/boshqaruv/login', ['email' => 'root@t.uz', 'password' => 'secret123'])
            ->assertRedirect(url('/boshqaruv/orders'));
    }

    public function test_logged_in_admin_opening_login_goes_to_panel(): void
    {
        $this->actingAs($this->admin(), 'panel')
            ->get('/boshqaruv/login')
            ->assertRedirect(route('boshqaruv.dashboard'));
    }
}
