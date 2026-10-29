<?php

namespace App\Console\Commands;

use App\Services\FinanceBot\FinanceBotService;
use Illuminate\Console\Command;

class FinanceBotWeeklyReportCommand extends Command
{
    protected $signature = 'finance-bot:weekly-report {--chat= : Maxsus chat ID}';

    protected $description = "Haftalik moliyaviy hisobotni Telegram guruhiga yuboradi";

    public function handle(FinanceBotService $service): int
    {
        $chat = $this->option('chat');

        $this->info("Haftalik hisobot yuborilmoqda...");
        $ok = $service->sendWeeklyReport($chat);

        if ($ok) {
            $this->info("Haftalik hisobot muvaffaqiyatli yuborildi.");
            return self::SUCCESS;
        }

        $this->warn("Haftalik hisobot yuborilmadi.");
        return self::FAILURE;
    }
}

