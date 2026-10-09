<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\BotTicket;
use App\Services\SessionService;
use App\Services\Staff\StaffModules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Rolga mos dashboard, navbatdagi ishlar, xodimlar KPI. */
class StaffWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role, array $permissions = [], array $extra = []): Admin
    {
        return Admin::query()->forceCreate(array_merge([
            'name' => ucfirst($role) . ' Xodim', 'email' => $role . uniqid() . '@t.uz', 'password' => bcrypt('x'),
            'role' => $role, 'permissions' => $permissions, 'is_active' => 1,
        ], $extra));
    }

    private function props($response): array
    {
        return $response->viewData('page')['props'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_support_agent_sees_personal_workspace_without_business_analytics(): void
    {
        $agent = $this->admin('support', ['support']);
        $ticket = SessionService::createTicket(userId: 7001, firstMessage: 'Salom');
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: 'Salom', telegramActorId: 7001);
        BotTicket::create(['user_id' => 7002, 'source_type' => 'bot', 'status' => 'active', 'first_msg' => 'Yordam', 'admin_id' => $agent->id]);

        AdminAuditLog::query()->create([
            'admin_id' => $agent->id, 'admin_name' => $agent->name, 'method' => 'POST',
            'route_name' => 'boshqaruv.support.inbox.reply', 'path' => '/boshqaruv/support/inbox/api/reply',
            'action' => 'Support Inbox Reply', 'status_code' => 200,
        ]);

        $props = $this->props($this->actingAs($agent, 'panel')->get('/boshqaruv')->assertOk());

        $this->assertNull($props['dashboard']);
        $this->assertFalse($props['workspace']['access']['business']);
        $keys = array_column($props['workspace']['queues'], 'key');
        $this->assertContains('support.unassigned', $keys);
        $this->assertContains('support.mine', $keys);
        $this->assertNotContains('orders.new', $keys);
        $this->assertNotContains('finance.payouts', $keys);

        $unassigned = collect($props['workspace']['queues'])->firstWhere('key', 'support.unassigned');
        $this->assertSame(1, $unassigned['count']);
        $mine = collect($props['workspace']['queues'])->firstWhere('key', 'support.mine');
        $this->assertSame(1, $mine['count']);

        $me = $props['workspace']['me'];
        $this->assertSame(1, $me['activity']['total']);
        $this->assertSame('Support', $me['activity']['modules'][0]['label']);
        $this->assertStringContainsString('Murojaatni javob berdi', $me['recent'][0]['text']);
        $this->assertSame('Jami amallar', $me['highlights'][0]['label']);

        // Sidebar badge va qo'ng'iroq
        $this->assertSame(1, $props['workQueue']['nav']['/boshqaruv/support/inbox']);
        $this->assertGreaterThanOrEqual(1, $props['workQueue']['total']);

        // Moliyaviy eksport yopiq, jamoa sahifasi yopiq
        $this->actingAs($agent, 'panel')->get('/boshqaruv/dashboard/export')->assertForbidden();
        $this->actingAs($agent, 'panel')->get('/boshqaruv/team')->assertForbidden();
    }

    public function test_superadmin_gets_business_overview_and_lazy_tabs(): void
    {
        $root = $this->admin('superadmin');

        $props = $this->props($this->actingAs($root, 'panel')->get('/boshqaruv')->assertOk());
        $this->assertNotNull($props['dashboard']);
        $this->assertArrayHasKey('status', $props['dashboard']);
        $this->assertTrue($props['workspace']['access']['finance']);
        $this->assertArrayNotHasKey('economics', $props); // faqat tab ochilganda yuklanadi

        $version = $this->actingAs($root, 'panel')->get('/boshqaruv')->viewData('page')['version'] ?? '';
        $partial = $this->actingAs($root, 'panel')->get('/boshqaruv', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Partial-Component' => 'Dashboard',
            'X-Inertia-Partial-Data' => 'growth',
        ])->assertOk()->json('props');
        $this->assertArrayHasKey('funnel', $partial['growth']);
        $this->assertArrayHasKey('retention', $partial['growth']);
    }

    public function test_operations_manager_sees_orders_without_money(): void
    {
        $ops = $this->admin('operations', ['orders', 'users', 'sellers', 'couriers']);
        $props = $this->props($this->actingAs($ops, 'panel')->get('/boshqaruv')->assertOk());

        $this->assertNotNull($props['dashboard']);
        $this->assertFalse($props['workspace']['access']['finance']);
        $this->assertTrue($props['dashboard']['financialRestricted']);
        $this->assertArrayNotHasKey('exportUrl', $props['dashboard']);
        $keys = array_column($props['workspace']['queues'], 'key');
        $this->assertContains('orders.new', $keys);
        $this->assertContains('sellers.pending', $keys);
        $this->assertNotContains('support.unassigned', $keys);
    }

    public function test_team_page_shows_staff_activity(): void
    {
        $root = $this->admin('superadmin');
        $catalog = $this->admin('catalog', ['catalog']);
        foreach (['boshqaruv.books.moderate', 'boshqaruv.books.moderate', 'boshqaruv.catalog.update'] as $route) {
            AdminAuditLog::query()->create([
                'admin_id' => $catalog->id, 'admin_name' => $catalog->name, 'method' => 'PATCH',
                'route_name' => $route, 'path' => '/x', 'action' => 'x', 'target_id' => '15', 'status_code' => 302,
            ]);
        }

        $props = $this->props($this->actingAs($root, 'panel')->get('/boshqaruv/team?range=7d')->assertOk());
        $row = collect($props['team']['rows'])->firstWhere('id', $catalog->id);
        $this->assertSame(3, $row['actions']);
        $this->assertSame('Katalog', $row['modules'][0]['label']);
        $this->assertSame(2, $row['domain']['productModeration']);
        $this->assertSame(1, $props['team']['totals']['activeAdmins']);

        $detail = $this->actingAs($root, 'panel')->getJson('/boshqaruv/team/' . $catalog->id . '/data?range=7d')->assertOk()->json();
        $this->assertSame(3, $detail['activity']['total']);
        $this->assertSame('Kitobni moderatsiya qildi · #15', $detail['recent'][1]['text']);

        // Auditor (faqat ko'rish) ham jamoani ko'radi
        $auditor = $this->admin('auditor', array_keys(Admin::MODULES), ['is_read_only' => 1]);
        $this->actingAs($auditor, 'panel')->get('/boshqaruv/team')->assertOk();
    }

    public function test_module_mapping_and_descriptions(): void
    {
        $this->assertSame('split', StaffModules::moduleOf('boshqaruv.users.split.block'));
        $this->assertSame('users', StaffModules::moduleOf('boshqaruv.users.block'));
        $this->assertSame('catalog', StaffModules::moduleOf('boshqaruv.catalog-slots.approve'));
        $this->assertSame('finance', StaffModules::moduleOf('boshqaruv.courier-transactions.approve'));
        $this->assertSame('Do‘konni tasdiqladi · #9', StaffModules::describe('boshqaruv.sellers.approve', 'PATCH', '9'));
        $this->assertSame('Buyurtmani holatini o‘zgartirdi', StaffModules::describe('boshqaruv.orders.status', 'PATCH'));
    }
}
