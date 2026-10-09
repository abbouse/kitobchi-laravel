<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Do'kon ilovasi (kitobchibusiness): support murojaatiga javob/yopilish real-vaqtda.
 */
class SellerSupportTicketUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sellerId,
        public array $ticket,
        public ?array $message = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('seller-store.' . $this->sellerId)];
    }

    public function broadcastAs(): string
    {
        return 'SupportTicketUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket' => $this->ticket,
            'message' => $this->message,
        ];
    }
}
