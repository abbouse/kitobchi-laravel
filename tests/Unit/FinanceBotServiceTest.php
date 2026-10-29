<?php

namespace Tests\Unit;

use App\Models\PlatformExpense;
use App\Models\PlatformIncome;
use App\Services\FinanceBot\FinanceAiParserService;
use App\Services\FinanceBot\FinanceBotClient;
use App\Services\FinanceBot\FinanceBotService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FinanceBotServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ignores_non_financial_messages(): void
    {
        /** @var FinanceBotClient $tg */
        $tg = Mockery::mock(FinanceBotClient::class);
        $tg->shouldNotReceive('sendMessage');

        /** @var FinanceAiParserService $ai */
        $ai = Mockery::mock(FinanceAiParserService::class);
        $ai->shouldReceive('couldBeFinancial')->with('Salom yaxshimisizlar')->andReturn(false);

        $service = new FinanceBotService($tg, $ai);

        $service->handleMessage([
            'chat' => ['id' => -1001234567, 'type' => 'supergroup'],
            'from' => ['id' => 99999, 'is_bot' => false, 'username' => 'testuser'],
            'text' => 'Salom yaxshimisizlar',
            'message_id' => 101,
        ]);

        $this->assertTrue(true);
    }

    public function test_asks_question_when_info_missing(): void
    {
        /** @var FinanceBotClient $tg */
        $tg = Mockery::mock(FinanceBotClient::class);
        $tg->shouldReceive('sendMessage')
            ->once()
            ->with(-1001234567, '❓ Summani tushundim. Bu nimaga sarflandi?', [
                'reply_parameters' => ['message_id' => 102],
            ])
            ->andReturn(['message_id' => 202]);

        /** @var FinanceAiParserService $ai */
        $ai = Mockery::mock(FinanceAiParserService::class);
        $ai->shouldReceive('couldBeFinancial')->with('-50000')->andReturn(true);
        $ai->shouldReceive('parse')->andReturn([
            'is_financial' => true,
            'type' => 'expense',
            'amount' => 50000,
            'category' => 'other',
            'title' => null,
            'note' => '-50000',
            'is_complete' => false,
            'missing_field' => 'title',
            'clarification_question' => 'Summani tushundim. Bu nimaga sarflandi?',
        ]);

        $service = new FinanceBotService($tg, $ai);

        $service->handleMessage([
            'chat' => ['id' => -1001234567, 'type' => 'supergroup'],
            'from' => ['id' => 99999, 'is_bot' => false, 'username' => 'testuser'],
            'text' => '-50000',
            'message_id' => 102,
        ]);

        $this->assertTrue(Cache::has('finance_bot:pending_msg:202'));
        $this->assertTrue(Cache::has('finance_bot:pending_user:99999'));
    }
}

