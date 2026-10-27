<?php

namespace App\Console\Commands;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Console\Command;

class TeamBotRemindCommand extends Command
{
    protected $signature = 'team-bot:remind {--minutes=120 : Necha daqiqadan keyin buyurtma "kutib qolgan" hisoblanadi}';

    protected $description = "Uzoq kutib qolgan buyurtmalar va muddati o'tgan vazifalar haqida eslatma";

    public function handle(TeamBotService $bot): int
    {
        $bot->remindStale((int) $this->option('minutes'));

        return self::SUCCESS;
    }
}
