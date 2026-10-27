<?php

namespace App\Jobs;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Jamoa guruhidagi buyurtma xabarini yuboradi ($create) yoki yangilaydi. */
class TeamBotSyncOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 20;

    public function __construct(public int $orderId, public bool $create = false)
    {
    }

    public function handle(TeamBotService $bot): void
    {
        $bot->syncOrder($this->orderId, $this->create);
    }
}
