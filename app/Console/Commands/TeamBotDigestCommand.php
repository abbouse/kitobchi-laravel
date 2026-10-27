<?php

namespace App\Console\Commands;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Console\Command;

class TeamBotDigestCommand extends Command
{
    protected $signature = 'team-bot:digest';

    protected $description = 'Jamoa guruhiga kechagi kun xulosasi';

    public function handle(TeamBotService $bot): int
    {
        $bot->sendDigest();

        return self::SUCCESS;
    }
}
