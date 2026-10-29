<?php

namespace App\Console\Commands;

use App\Services\FinanceBot\FinanceBotService;
use Illuminate\Console\Command;

class FinanceBotDailyReportCommand extends Command
{
    protected $signature = 'finance-bot:daily-report {--date= : Hisobot sanasi (Y-m-d)} {--chat= : Maxsus chat ID}';

    protected $description = "Kunlik moliyaviy hisobotni Telegram guruhiga yuboradi";

    public function handle(FinanceBotService $service): int
    {
        $date = $this->option('date');
        $chat = $this->option('chat');

        $this->info("Kunlik hisobot yuborilmoqda...");
        $ok = $service->sendDailyReport($chat, $date);

        if ($ok) {
            $this->info("Kunlik hisobot muvaffaqiyatli yuborildi.");
            return self::SUCCESS;
        }

        $this->warn("Hisobot yuborilmadi (chat ID mavjud emas yoki Telegram xatosi).");
        return self::FAILURE;
    }
}

