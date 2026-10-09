<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Boshqaruvdagi support inbox uchun real-vaqt yangilanishi.
 * kind: message | thread
 */
class SupportInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $kind,
        public string $segment,
        public string $key,
        public ?array $thread = null,
        public ?array $message = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('support.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'SupportInboxUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'kind' => $this->kind,
            'segment' => $this->segment,
            'key' => $this->key,
            'thread' => $this->thread,
            'message' => $this->message,
        ];
    }
}
