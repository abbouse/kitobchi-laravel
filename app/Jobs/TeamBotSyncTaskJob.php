<?php

namespace App\Jobs;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Vazifa xabarini jamoa guruhida yaratadi yoki yangilaydi. */
class TeamBotSyncTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 20;

    public function __construct(public int $taskId)
    {
    }

    public function handle(TeamBotService $bot): void
    {
        $bot->syncTask($this->taskId);
    }
}
