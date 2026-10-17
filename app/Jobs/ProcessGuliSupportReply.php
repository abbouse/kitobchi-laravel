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
        public ?int $messageId = null,
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

        // Mijoz ketma-ket bir nechta xabar yozsa — faqat oxirgisiga bitta
        // javob beriladi (tarixda hammasi bor). Avval har xabarga alohida
        // javob ketib, Guli bir xil gapni 2–3 marta yozardi.
        if ($this->messageId !== null) {
            $newer = \App\Models\BotTicketMessage::query()
                ->where('ticket_id', $ticket->id)
                ->where('sent_by', 'user')
                ->where('id', '>', $this->messageId)
                ->exists();
            if ($newer) {
                return;
            }
        }

        $guli->handleIncomingMessage($ticket, $this->userText);
    }
}
