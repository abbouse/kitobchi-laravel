<?php

namespace Tests\Feature;

use App\Jobs\ProcessGuliAutoClose;
use App\Jobs\ProcessGuliFollowUp;
use App\Jobs\ProcessGuliGreeting;
use App\Jobs\ProcessGuliSupportReply;
use App\Models\BotTicket;
use App\Models\BotTicketMessage;
use App\Models\Sold;
use App\Models\User;
use App\Services\SessionService;
use App\Services\Support\GuliAgentService;
use App\Services\Support\SupportInboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Guli (AI support agenti): mijozni taniydi, tool'lar, yopish qoidalari, xatoda operatorga uzatish. */
class GuliAgentTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const CHAT = 7700123;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        config(['nutgram.token' => 'TEST:TOKEN', 'services.openai.key' => 'sk-test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 55]])]);
        Bus::fake([ProcessGuliFollowUp::class, ProcessGuliAutoClose::class, ProcessGuliGreeting::class, ProcessGuliSupportReply::class]);
    }

    private function ai(array $responses): ClientFake
    {
        $fake = new ClientFake($responses);
        $this->app->instance('guli.ai_client', $fake);

        return $fake;
    }

    private static function text(string $content): CreateResponse
    {
        return CreateResponse::fake(['choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => $content, 'tool_calls' => []], 'finish_reason' => 'stop']]]);
    }

    private static function tool(string $name, array $args = []): CreateResponse
    {
        return CreateResponse::fake(['choices' => [['index' => 0, 'message' => [
            'role' => 'assistant', 'content' => null,
            'tool_calls' => [['id' => 'call_'.$name, 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode((object) $args)]]],
        ], 'finish_reason' => 'tool_calls']]]);
    }

    private function customer(): User
    {
        return User::query()->forceCreate([
            'name' => 'Aziza', 'lastname' => 'K', 'phone_number' => '998901112200', 'password' => bcrypt('x'),
            'telegram_id' => self::CHAT, 'cashback' => 12000,
        ]);
    }

    private function ticketWith(string $text): BotTicket
    {
        $ticket = SessionService::createTicket(userId: self::CHAT, firstMessage: $text, name: 'Aziza');
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: $text, telegramActorId: self::CHAT);

        return BotTicket::query()->findOrFail($ticket->id);
    }

    private function say(BotTicket $ticket, string $text): ?string
    {
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: $text, telegramActorId: self::CHAT);

        return app(GuliAgentService::class)->handleIncomingMessage($ticket->fresh(), $text);
    }

    public function test_recognises_telegram_customer_and_answers_with_order_tracking(): void
    {
        $user = $this->customer();
        $order = Sold::query()->forceCreate([
            'user_id' => $user->id, 'amount' => 85000, 'deliveryType' => 'courier',
            'status' => 'B', 'status_code' => 'in_delivery', 'paymentStatus' => 0, 'payment_status_code' => 'cash_pending',
            'qr' => 'QR'.random_int(100000, 999999), 'address' => [['fullAddress' => 'Chilonzor 5']],
            'items' => [['name' => 'O‘tkan kunlar', 'count_item' => 1, 'price' => 85000, 'type' => 'book']],
        ]);

        $fake = $this->ai([
            self::tool('get_order_details', ['order_id' => $order->id]),
            self::text("Buyurtmangiz yo'lda, bugun yetib boradi 😊\n\nEzgu tilaklar bilan Guli 🌸"),
        ]);
        $ticket = $this->ticketWith('Buyurtmam qayerda?');

        $reply = app(GuliAgentService::class)->handleIncomingMessage($ticket, 'Buyurtmam qayerda?');

        $this->assertSame("Buyurtmangiz yo'lda, bugun yetib boradi 😊\nEzgu tilaklar bilan Guli 🌸", $reply, 'Imzo oldidagi bo‘sh qator olib tashlanadi');
        // Mijoz kontekstda tanilgan, tool natijasi odamga tushunarli
        $fake->assertSent(\OpenAI\Resources\Chat::class, function (string $method, array $params) use ($order) {
            $system = $params['messages'][0]['content'];
            $tool = collect($params['messages'])->firstWhere('role', 'tool');

            return str_contains($system, 'Aziza K') && str_contains($system, "#{$order->id}")
                && $tool !== null
                && str_contains($tool['content'], 'Yetkazilmoqda')
                && str_contains($tool['content'], 'O‘tkan kunlar')
                && str_contains($tool['content'], 'Naqd');
        });
        Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage') && str_contains((string) $r['text'], "Buyurtmangiz yo'lda"));
        Bus::assertDispatched(ProcessGuliFollowUp::class, fn ($job) => $job->kind === 'anything_else');
    }

    public function test_complaints_with_no_or_done_words_are_not_treated_as_closing(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Salom');
        foreach (["Kuryer kelgani yo'q", "buyurtmam nima bo'ldi", 'kitob hali yoq', 'деньги нет'] as $complaint) {
            $this->ai([self::text('Tekshirib ko‘raman')]);
            $this->assertNotNull($this->say($ticket, $complaint), "«{$complaint}» suhbatni yopmasligi kerak");
            $this->assertNotSame('closed', $ticket->fresh()->status);
        }
    }

    public function test_no_to_a_clarifying_question_keeps_conversation_open(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Buyurtma haqida');
        $this->ai([self::text("Buyurtma raqamingiz bormi?\nEzgu tilaklar bilan Guli 🌸")]);
        $this->say($ticket, 'Buyurtma haqida');
        Bus::assertDispatched(ProcessGuliFollowUp::class, fn ($job) => $job->kind === 'awaiting_answer');

        $this->ai([self::tool('get_my_orders'), self::text('Mana buyurtmalaringiz')]);
        $this->assertSame("Mana buyurtmalaringiz\nEzgu tilaklar bilan Guli 🌸", $this->say($ticket, "yo'q"));
        $this->assertNotSame('closed', $ticket->fresh()->status);

        // Endi "boshqa savol bormi?" so'ralgandan keyin "yo'q" — yopiladi
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'operator', message: "Boshqa savollaringiz yo'qmi?\nEzgu tilaklar bilan Guli 🌸");
        $this->ai([]);
        $this->assertNull($this->say($ticket, "yo'q"));
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_thanks_closes_and_requests_feedback(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Salom');
        $this->ai([]);

        $reply = $this->say($ticket, 'Katta rahmat!');

        $this->assertStringStartsWith('Arzimaydi!', (string) $reply);
        $fresh = $ticket->fresh();
        $this->assertSame('closed', $fresh->status);
        $this->assertNotNull($fresh->feedback_requested_at);
        Http::assertSent(fn ($r) => str_contains((string) ($r['reply_markup'] ?? ''), 'fb:'.$ticket->id.':good'));
    }

    public function test_ai_failure_hands_over_to_operator_instead_of_silence(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Pulim qaytmadi');
        $this->ai([new \RuntimeException('OpenAI 500')]);

        $reply = app(GuliAgentService::class)->handleIncomingMessage($ticket, 'Pulim qaytmadi');

        $this->assertStringContainsString('mutaxassis', (string) $reply);
        $fresh = $ticket->fresh();
        $this->assertFalse((bool) $fresh->ai_enabled);
        $this->assertSame('queue', $fresh->status);
        $this->assertTrue(BotTicketMessage::query()->where('ticket_id', $ticket->id)->where('message_type', 'note')->exists(), 'Operator uchun ichki eslatma');
    }

    public function test_photo_is_handed_over_and_voice_asks_for_text(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Salom');
        $fake = $this->ai([]);

        $this->assertStringContainsString('tinglay olmayman', (string) $this->say($ticket, '[Ovozli xabar]'));
        $this->assertTrue((bool) $ticket->fresh()->ai_enabled);

        $this->assertStringContainsString('mutaxassis', (string) $this->say($ticket, '[Rasm]'));
        $this->assertFalse((bool) $ticket->fresh()->ai_enabled);
        $fake->assertNothingSent();
    }

    public function test_burst_of_messages_gets_single_reply(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Salom');
        $first = SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: 'Kitob', telegramActorId: self::CHAT);
        SessionService::saveMessage(ticketId: (int) $ticket->id, sentBy: 'user', message: 'Abdulla Qodiriy kitobi bormi?', telegramActorId: self::CHAT);
        $fake = $this->ai([self::text('Bor')]);

        (new ProcessGuliSupportReply((int) $ticket->id, 'Kitob', (int) $first->id))->handle(app(GuliAgentService::class));

        $fake->assertNothingSent();
    }

    public function test_guli_kpi(): void
    {
        $this->customer();
        $ticket = $this->ticketWith('Salom');
        $this->ai([self::text('Salom! Qanday yordam beray?')]);
        $this->say($ticket, 'Salom');
        $this->ai([]);
        $this->say($ticket, 'rahmat');
        app(SupportInboxService::class)->recordCustomerFeedback($ticket->fresh(), 'good');

        $guli = app(SupportInboxService::class)->guliKpi(now()->subDay(), now()->addMinute());

        $this->assertSame(1, $guli['handled']);
        $this->assertSame(1, $guli['resolved']);
        $this->assertSame(100, $guli['resolution_rate']);
        $this->assertSame(1, $guli['good']);
    }
}
