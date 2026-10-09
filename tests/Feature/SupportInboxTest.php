<?php

namespace Tests\Feature;

use App\Events\SellerSupportTicketUpdated;
use App\Events\SupportInboxUpdated;
use App\Jobs\SendSellerSupportPush;
use App\Models\Admin;
use App\Models\BotTicket;
use App\Models\Conversation;
use App\Models\SellerSupportTicket;
use App\Models\SupportTemplate;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Boshqaruv support inbox: mijoz (Telegram/ilova) va do'kon murojaatlari bitta joyda. */
class SupportInboxTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private Admin $agent;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        config(['nutgram.token' => 'TEST:TOKEN']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]]),
        ]);
        $this->agent = Admin::query()->forceCreate([
            'name' => 'Dilnoza', 'email' => 'd' . uniqid() . '@t.uz', 'password' => bcrypt('x'),
            'role' => 'support', 'permissions' => ['support'], 'is_active' => 1,
        ]);
    }

    private function api(): \Illuminate\Testing\TestResponse|static
    {
        return $this->actingAs($this->agent, 'panel');
    }

    public function test_telegram_customer_message_appears_and_agent_replies_from_panel(): void
    {
        Event::fake([SupportInboxUpdated::class]);

        $ticket = SessionService::createTicket(userId: 5550001, firstMessage: 'Buyurtmam qayerda?', username: 'ali', name: 'Ali');
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: 'Buyurtmam qayerda?', telegramActorId: 5550001);

        Event::assertDispatched(SupportInboxUpdated::class, fn ($e) => $e->key === 'c-bot-5550001' && $e->kind === 'message');

        $list = $this->api()->getJson('/boshqaruv/support/inbox/api/threads?segment=customer&filter=open')->assertOk()->json();
        $this->assertSame('c-bot-5550001', $list['items'][0]['key']);
        $this->assertSame(1, $list['items'][0]['unread']);
        $this->assertTrue($list['items'][0]['waiting']);
        $this->assertSame(1, $list['counts']['unassigned']);

        $this->api()->postJson('/boshqaruv/support/inbox/api/reply', ['key' => 'c-bot-5550001', 'message' => 'Hozir tekshiraman'])
            ->assertOk()
            ->assertJsonPath('message.from', 'agent')
            ->assertJsonPath('message.delivered', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage') && $request['text'] === 'Hozir tekshiraman');

        $fresh = BotTicket::query()->find($ticket->id);
        $this->assertSame('active', $fresh->status);
        $this->assertSame($this->agent->id, (int) $fresh->admin_id);
        $this->assertNotNull($fresh->first_response_at);

        // Ichki eslatma mijozga yuborilmaydi
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        $this->api()->postJson('/boshqaruv/support/inbox/api/reply', ['key' => 'c-bot-5550001', 'message' => 'VIP mijoz', 'note' => true])
            ->assertOk()->assertJsonPath('message.from', 'note');
        Http::assertNothingSent();

        $thread = $this->api()->getJson('/boshqaruv/support/inbox/api/thread?key=c-bot-5550001')->assertOk()->json();
        $this->assertSame(['customer', 'agent', 'note'], array_column($thread['messages'], 'from'));
        $this->assertSame('Telegram bot', $thread['context']['profile']['channel']);

        $this->api()->postJson('/boshqaruv/support/inbox/api/read', ['key' => 'c-bot-5550001'])->assertOk();
        $this->assertSame(0, (int) BotTicket::query()->find($ticket->id)->admin_unread_count);

        // Yopish → Telegramga "yaxshi/yomon" so'rovi
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]])]);
        $this->api()->postJson('/boshqaruv/support/inbox/api/close', ['key' => 'c-bot-5550001'])
            ->assertOk()->assertJsonPath('thread.status', 'closed');
        Http::assertSent(fn ($request) => str_contains((string) ($request['reply_markup'] ?? ''), 'fb:' . $ticket->id . ':good'));

        app(\App\Services\Support\SupportInboxService::class)->recordCustomerFeedback(BotTicket::query()->find($ticket->id), 'bad');
        $this->assertSame('bad', BotTicket::query()->find($ticket->id)->feedback);

        $kpi = $this->api()->getJson('/boshqaruv/support/inbox/api/kpi?range=today')->assertOk()->json();
        $this->assertSame(1, $kpi['totals']['bad']);
        $row = collect($kpi['agents'])->firstWhere('id', $this->agent->id);
        $this->assertSame(1, $row['handled']);
        $this->assertSame(1, $row['closed']);
        $this->assertSame(1, $row['replies']);
        $this->assertSame(0, $row['satisfaction']);
        $this->assertSame('c-bot-5550001', $kpi['bad_feedback'][0]['key']);
    }

    public function test_reply_to_closed_customer_thread_opens_new_ticket(): void
    {
        $ticket = SessionService::createTicket(userId: 5550002, firstMessage: 'Salom');
        SessionService::closeTicket((int) $ticket->id, 'test');

        $this->api()->postJson('/boshqaruv/support/inbox/api/reply', ['key' => 'c-bot-5550002', 'message' => 'Yana bir savol bor edimi?'])->assertOk();

        $this->assertSame(2, BotTicket::query()->where('user_id', 5550002)->count());
        $latest = BotTicket::query()->where('user_id', 5550002)->latest('id')->first();
        $this->assertSame('active', $latest->status);
        $this->assertSame($this->agent->id, (int) $latest->admin_id);
    }

    public function test_app_customer_feedback_after_close(): void
    {
        $support = $this->makeSeller(['shop_name' => 'Kitobchi']);
        $user = User::query()->forceCreate(['name' => 'Vali', 'lastname' => 'G', 'phone_number' => '998901234567', 'password' => bcrypt('x')]);
        $conversation = Conversation::query()->create(['type' => 'shop', 'user_id' => $user->id, 'shop_id' => $support->id]);

        $ticket = BotTicket::create([
            'user_id' => $user->id, 'source_type' => 'shop_chat', 'source_conversation_id' => $conversation->id,
            'status' => 'active', 'first_msg' => 'Savol', 'admin_id' => $this->agent->id,
        ]);
        SessionService::closeTicket((int) $ticket->id, 'panel_closed');

        Sanctum::actingAs($user, ['*'], 'user');
        $this->getJson('/api/v1/kitobchi/support/feedback?conversation_id=' . $conversation->id)
            ->assertOk()
            ->assertJsonPath('data.can_rate', true);

        $this->postJson('/api/v1/kitobchi/support/feedback', ['ticket_id' => $ticket->id, 'value' => 'good'])->assertOk();
        $this->postJson('/api/v1/kitobchi/support/feedback', ['ticket_id' => $ticket->id, 'value' => 'bad'])->assertStatus(422);
        $this->assertSame('good', BotTicket::query()->find($ticket->id)->feedback);
    }

    public function test_shop_ticket_flow_with_realtime_push_and_feedback(): void
    {
        Event::fake([SupportInboxUpdated::class, SellerSupportTicketUpdated::class]);
        Bus::fake([SendSellerSupportPush::class]);

        $seller = $this->makeSeller(['shop_name' => 'Nur kitoblar']);
        Sanctum::actingAs($seller, ['*'], 'seller');

        $ticketId = $this->postJson('/api/v1/seller/support-tickets', ['subject' => 'Pul yechish', 'message' => 'Pulim qachon tushadi?'])
            ->assertStatus(201)->json('data.id');
        Event::assertDispatched(SupportInboxUpdated::class, fn ($e) => $e->key === 's-' . $seller->id && $e->segment === 'shop');

        $list = $this->api()->getJson('/boshqaruv/support/inbox/api/threads?segment=shop&filter=open')->assertOk()->json();
        $this->assertSame('s-' . $seller->id, $list['items'][0]['key']);
        $this->assertSame('Nur kitoblar', $list['items'][0]['name']);
        $this->assertSame(1, $list['segments']['shop']['unread']);

        $this->api()->postJson('/boshqaruv/support/inbox/api/reply', ['key' => 's-' . $seller->id, 'message' => '1–3 ish kunida tushadi'])->assertOk();
        $this->api()->postJson('/boshqaruv/support/inbox/api/reply', ['key' => 's-' . $seller->id, 'message' => 'Ichki: moliya bilan gaplashdim', 'note' => true])->assertOk();

        $ticket = SellerSupportTicket::query()->find($ticketId);
        $this->assertSame('answered', $ticket->status);
        $this->assertSame(1, (int) $ticket->seller_unread_count);
        $this->assertNotNull($ticket->first_response_at);
        Event::assertDispatched(SellerSupportTicketUpdated::class, fn ($e) => $e->sellerId === $seller->id && $e->message['message'] === '1–3 ish kunida tushadi');
        Bus::assertDispatched(SendSellerSupportPush::class, fn ($job) => $job->ticketId === $ticket->id && $job->kind === 'reply');

        // Do'kon ilovasida ichki eslatma ko'rinmaydi
        Sanctum::actingAs($seller, ['*'], 'seller');
        $messages = $this->getJson('/api/v1/seller/support-tickets/' . $ticketId)->assertOk()->json('data.messages');
        $this->assertSame(['Pulim qachon tushadi?', '1–3 ish kunida tushadi'], array_column($messages, 'message'));

        $this->api()->postJson('/boshqaruv/support/inbox/api/close', ['key' => 's-' . $seller->id])->assertOk();
        Bus::assertDispatched(SendSellerSupportPush::class, fn ($job) => $job->kind === 'closed');

        Sanctum::actingAs($seller, ['*'], 'seller');
        $this->postJson('/api/v1/seller/support-tickets/' . $ticketId . '/feedback', ['value' => 'good'])
            ->assertOk()->assertJsonPath('data.feedback', 'good');
        $this->postJson('/api/v1/seller/support-tickets/' . $ticketId . '/feedback', ['value' => 'bad'])->assertStatus(422);
    }

    public function test_templates_crud_and_page_renders(): void
    {
        $this->withoutVite();
        $this->assertGreaterThan(5, SupportTemplate::query()->count());

        $templates = $this->api()->postJson('/boshqaruv/support/inbox/api/templates', [
            'title' => 'Qaytarish', 'body' => 'Kitobni 14 kun ichida qaytarishingiz mumkin, {name}.', 'audience' => 'customer', 'shortcut' => 'qaytar',
        ])->assertOk()->json('templates');
        $created = collect($templates)->firstWhere('shortcut', 'qaytar');
        $this->assertNotNull($created);

        $this->api()->deleteJson('/boshqaruv/support/inbox/api/templates/' . $created['id'])->assertOk();
        $this->assertFalse((bool) SupportTemplate::query()->find($created['id'])->is_active);

        $this->api()->get('/boshqaruv/support/inbox')->assertOk();
        $this->api()->get('/boshqaruv/support/kpi?range=30d')->assertOk();
    }

    public function test_agent_without_support_permission_is_blocked(): void
    {
        $other = Admin::query()->forceCreate([
            'name' => 'Marketing', 'email' => 'm' . uniqid() . '@t.uz', 'password' => bcrypt('x'),
            'role' => 'marketing', 'permissions' => ['marketing'], 'is_active' => 1,
        ]);

        $this->actingAs($other, 'panel')->getJson('/boshqaruv/support/inbox/api/threads')->assertForbidden();
    }
}
