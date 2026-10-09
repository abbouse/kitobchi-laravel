<?php

namespace App\Jobs;

use App\Models\Seller;
use App\Models\SellerSupportTicket;
use App\Services\FCMService;
use App\Services\FcmRecipientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Do'kon ilovasiga support javobi / yopilishi haqida push.
 * Javob uchun: do'kon hali o'qimagan bo'lsagina yuboriladi.
 * data.type = support_ticket — ilova bosilganda shu murojaatni ochadi.
 */
class SendSellerSupportPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $ticketId,
        public string $kind,
        public string $body,
    ) {
    }

    public function handle(FcmRecipientService $recipients): void
    {
        $ticket = SellerSupportTicket::query()->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        if ($this->kind === 'reply' && (int) $ticket->seller_unread_count === 0) {
            return; // Do'kon allaqachon o'qidi
        }

        $sellerIds = Seller::query()
            ->where('id', $ticket->seller_id)
            ->orWhere('parent_id', $ticket->seller_id)
            ->pluck('id');

        $tokens = $sellerIds
            ->flatMap(fn ($id) => $recipients->tokensFor('seller', (int) $id))
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        try {
            (new FCMService('business'))->send(
                $tokens,
                $this->kind === 'closed' ? 'Murojaat yakunlandi' : 'Kitobchi support',
                $this->body,
                [
                    'type' => 'support_ticket',
                    'ticket_id' => (string) $ticket->id,
                    'kind' => $this->kind,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[SellerSupportPush] yuborilmadi', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }
}
