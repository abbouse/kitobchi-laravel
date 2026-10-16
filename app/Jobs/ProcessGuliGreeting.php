<?php

namespace App\Jobs;

use App\Models\BotTicket;
use App\Services\Support\GuliAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Yangi ticket ochilganda Guli proaktif salom beradi.
 */
class ProcessGuliGreeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 2;

    public function __construct(
        public int $ticketId,
    ) {
    }

    public function handle(GuliAgentService $guli): void
    {
        $ticket = BotTicket::query()->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        if (! $guli->shouldHandle($ticket)) {
            Log::info("[GuliGreeting] Ticket #{$ticket->id} AI uchun emas, salom bekor.");
            return;
        }

        $guli->sendGreeting($ticket);
    }
}
