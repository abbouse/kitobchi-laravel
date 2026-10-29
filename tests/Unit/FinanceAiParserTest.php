<?php

namespace Tests\Unit;

use App\Services\FinanceBot\FinanceAiParserService;
use App\Services\OpenAIService;
use Mockery;
use Tests\TestCase;

class FinanceAiParserTest extends TestCase
{
    public function test_could_be_financial_filter(): void
    {
        /** @var OpenAIService $mockOpenAi */
        $mockOpenAi = Mockery::mock(OpenAIService::class);
        $service = new FinanceAiParserService($mockOpenAi);

        // Son bo'lgan holatlar
        $this->assertTrue($service->couldBeFinancial('-50000'));
        $this->assertTrue($service->couldBeFinancial('taksi -20k'));
        $this->assertTrue($service->couldBeFinancial('arenda 150000'));
        $this->assertTrue($service->couldBeFinancial('kuryer 50 ming'));
        $this->assertTrue($service->couldBeFinancial('1.5 mln tushum'));

        // Moliyaviy kalit so'zlar
        $this->assertTrue($service->couldBeFinancial('kuryer arenda to\'ladi'));
        $this->assertTrue($service->couldBeFinancial('ofisga qadoq oldik'));

        // Moliyaga aloqasi yo'q oddiy gaplar
        $this->assertFalse($service->couldBeFinancial('Salom hammaga'));
        $this->assertFalse($service->couldBeFinancial('Ertaga majlis bormi?'));
        $this->assertFalse($service->couldBeFinancial('Rahmat kattakon'));
        $this->assertFalse($service->couldBeFinancial(''));
    }

    public function test_has_negative_sign(): void
    {
        /** @var OpenAIService $mockOpenAi */
        $mockOpenAi = Mockery::mock(OpenAIService::class);
        $service = new FinanceAiParserService($mockOpenAi);

        // Minus bilan kelgan holatlar (Chiqim)
        $this->assertTrue($service->hasNegativeSign('-50000'));
        $this->assertTrue($service->hasNegativeSign('taksi -20 000'));
        $this->assertTrue($service->hasNegativeSign('obed uchun -35k'));
        $this->assertTrue($service->hasNegativeSign('- 100 ming'));

        // Minussiz kelgan holatlar (Kirim / Foyda)
        $this->assertFalse($service->hasNegativeSign('50000'));
        $this->assertFalse($service->hasNegativeSign('+50000'));
        $this->assertFalse($service->hasNegativeSign('kuryer arenda 150000'));
        $this->assertFalse($service->hasNegativeSign('buyurtma puli 200 ming'));
    }

    public function test_parse_with_openai_mock(): void
    {
        $mockOpenAi = Mockery::mock(OpenAIService::class);
        $mockOpenAi->shouldReceive('askJsonWithMessages')
            ->once()
            ->andReturn([
                'is_financial' => true,
                'type' => 'expense',
                'amount' => 50000,
                'category' => 'office',
                'title' => 'Ofis kantselyariya',
                'note' => '-50000 ofisga qog\'oz',
                'is_complete' => true,
                'missing_field' => null,
                'clarification_question' => null,
            ]);

        $service = new FinanceAiParserService($mockOpenAi);
        $res = $service->parse('-50000 ofisga qog\'oz');

        $this->assertTrue($res['is_financial']);
        $this->assertEquals('expense', $res['type']);
        $this->assertEquals(50000, $res['amount']);
        $this->assertEquals('office', $res['category']);
        $this->assertEquals('Ofis kantselyariya', $res['title']);
        $this->assertTrue($res['is_complete']);
    }

    public function test_parse_with_missing_title_asks_clarification(): void
    {
        $mockOpenAi = Mockery::mock(OpenAIService::class);
        $mockOpenAi->shouldReceive('askJsonWithMessages')
            ->once()
            ->andReturn([
                'is_financial' => true,
                'type' => 'expense',
                'amount' => 50000,
                'category' => 'other',
                'title' => null,
                'note' => '-50000',
                'is_complete' => false,
                'missing_field' => 'title',
                'clarification_question' => 'Summani tushundim (-50 000 so\'m). Iltimos, bu nimaga sarflandi?',
            ]);

        $service = new FinanceAiParserService($mockOpenAi);
        $res = $service->parse('-50000');

        $this->assertTrue($res['is_financial']);
        $this->assertEquals('expense', $res['type']);
        $this->assertEquals(50000, $res['amount']);
        $this->assertFalse($res['is_complete']);
        $this->assertEquals('title', $res['missing_field']);
        $this->assertNotNull($res['clarification_question']);
    }
}

