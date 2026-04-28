<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderLifecycleUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $channels,
        public array $payload,
    ) {
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn (string $channel) => new PrivateChannel($channel),
            $this->channels
        );
    }

    public function broadcastAs(): string
    {
        return 'OrderLifecycleUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
