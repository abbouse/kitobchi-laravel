<?php

namespace App\Jobs;

use App\Models\BotTicket;
use App\Models\BotTicketMessage;
use App\Services\Support\GuliAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 2 daqiqa davomida mijoz javob bermasa — Guli suhbatni avtomatik yopadi.
 */
class ProcessGuliAutoClose implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function __construct(
        public int $ticketId,
        public int $followUpMessageId,
    ) {
    }

    public function handle(GuliAgentService $guli): void
    {
        $ticket = BotTicket::query()->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        // Ticket allaqachon yopilgan yoki operatorda bo'lsa — to'xtatamiz
        if (! $guli->shouldHandle($ticket)) {
            Log::info("[GuliAutoClose] Ticket #{$ticket->id} allaqachon yopilgan yoki operatorda.");
            return;
        }

        // Oxirgi xabarni tekshiramiz — follow-up dan keyin biror xabar bormi?
        $lastMsg = BotTicketMessage::query()
            ->where('ticket_id', $this->ticketId)
            ->whereIn('sent_by', ['user', 'operator', 'admin'])
            ->where('message_type', '<>', 'note')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastMsg) {
            return;
        }

        // Agar mijoz yangi xabar yozgan bo'lsa — yopmaymiz
        if ($lastMsg->sent_by === 'user' || $lastMsg->id > $this->followUpMessageId) {
            Log::info("[GuliAutoClose] Ticket #{$ticket->id}: mijoz yangi xabar yozgan (#{$lastMsg->id}), avto-yopish bekor qilindi.");
            return;
        }

        // Mijoz 2 daqiqa ichida javob bermadi — suhbatni avtomatik yakunlaymiz
        Log::info("[GuliAutoClose] Ticket #{$ticket->id}: mijoz javob bermadi, avtomatik yopilmoqda.");
        $guli->autoCloseInactiveTicket($ticket);
    }
}
