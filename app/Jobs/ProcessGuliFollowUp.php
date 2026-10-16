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
 * Smart Follow-Up: Guli javob bergandan 2-3 daqiqa o'tsa,
 * mijoz hali javob bermagan bo'lsa — yengil eslatma yuboradi.
 */
class ProcessGuliFollowUp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function __construct(
        public int    $ticketId,
        public int    $lastMessageId, // Guli bergan oxirgi javob ID'si
    ) {
    }

    public function handle(GuliAgentService $guli): void
    {
        $ticket = BotTicket::query()->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        // Ticket yopilgan yoki operator olgan bo'lsa — to'xtatamiz
        if (! $guli->shouldHandle($ticket)) {
            Log::info("[GuliFollowUp] Ticket #{$ticket->id} yopilgan/operatorda, follow-up bekor.");
            return;
        }

        // Oxirgi xabarni tekshiramiz — agar mijozdan yangi xabar kelgan bo'lsa
        // (follow-up dispatchdan keyin) — kerak emas
        $lastMsg = BotTicketMessage::query()
            ->where('ticket_id', $this->ticketId)
            ->whereIn('sent_by', ['user', 'operator', 'admin'])
            ->where('message_type', '<>', 'note')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastMsg) {
            return;
        }

        // Agar oxirgi xabar mijozdan kelgan bo'lsa — allaqachon javob berilgan
        if ($lastMsg->sent_by === 'user') {
            Log::info("[GuliFollowUp] Ticket #{$ticket->id}: mijoz o'zi javob bergan, follow-up kerak emas.");
            return;
        }

        // Agar oxirgi javob bizning follow-up xabarimizdan keyin bo'lsa ham o'tkazib yuboramiz
        if ($lastMsg->id > $this->lastMessageId) {
            Log::info("[GuliFollowUp] Ticket #{$ticket->id}: yangi xabar bor (#{$lastMsg->id} > #{$this->lastMessageId}), o'tkazib yuborildi.");
            return;
        }

        // Oxirgi Guli javobi ID bilan mos kelishi kerak
        if ($lastMsg->id !== $this->lastMessageId) {
            return;
        }

        // Follow-up xabarini yuborish (Boshqa savollaringiz yo'qmi?)
        $followUpMsg = $guli->sendFollowUp($ticket);

        // Agar mijoz 2 daqiqa ichida javob bermasa — avtomatik yopish navbatga qo'yiladi
        if ($followUpMsg) {
            ProcessGuliAutoClose::dispatch($ticket->id, $followUpMsg->id)
                ->delay(now()->addMinutes(2));
        }
    }
}
