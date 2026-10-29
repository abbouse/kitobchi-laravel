<?php

namespace App\Console\Commands;

use App\Services\FinanceBot\FinanceBotService;
use Illuminate\Console\Command;

class FinanceBotMonthlyReportCommand extends Command
{
    protected $signature = 'finance-bot:monthly-report {--month= : Qaysi oy (Y-m)} {--chat= : Maxsus chat ID}';

    protected $description = "Oylik moliyaviy hisobotni Telegram guruhiga yuboradi";

    public function handle(FinanceBotService $service): int
    {
        $month = $this->option('month');
        $chat = $this->option('chat');

        $this->info("Oylik hisobot yuborilmoqda...");
        $ok = $service->sendMonthlyReport($chat, $month);

        if ($ok) {
            $this->info("Oylik hisobot muvaffaqiyatli yuborildi.");
            return self::SUCCESS;
        }

        $this->warn("Oylik hisobot yuborilmadi.");
        return self::FAILURE;
    }
}

