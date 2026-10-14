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

class ProcessGuliSupportReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout 30 soniya
     */
    public int $timeout = 30;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 2;

    public function __construct(
        public int $ticketId,
        public string $userText,
    ) {
    }

    public function handle(GuliAgentService $guli): void
    {
        $ticket = BotTicket::query()->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        // Agar oradan vaqt o'tib operator allaqachon qo'shilgan bo'lsa — AI to'xtaydi
        if (! $guli->shouldHandle($ticket)) {
            Log::info("[ProcessGuliSupportReply] Ticket #{$ticket->id} inson operatoriga o'tgan yoki yopilgan, Guli aralashmaydi.");
            return;
        }

        $guli->handleIncomingMessage($ticket, $this->userText);
    }
}
