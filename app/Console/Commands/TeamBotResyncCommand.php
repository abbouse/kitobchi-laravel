<?php

namespace App\Console\Commands;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Console\Command;

class TeamBotResyncCommand extends Command
{
    protected $signature = 'team-bot:resync {--all : Oxirgi 7 kundagi barcha buyurtma xabarlarini qayta chizish}';

    protected $description = "Guruhdagi buyurtma xabarlarini boshqaruvdagi holat bilan tenglashtiradi";

    public function handle(TeamBotService $bot): int
    {
        $count = $bot->resyncRecent((bool) $this->option('all'));
        $this->info("Tekshirildi: {$count} ta xabar");

        return self::SUCCESS;
    }
}
