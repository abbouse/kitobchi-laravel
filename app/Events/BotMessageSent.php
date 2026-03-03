<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BotMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $userId, 
        public $data // Bu yerda content va formatlangan kitoblar bo'ladi
    ) {}

    public function broadcastOn()
    {
        // Har bir user uchun shaxsiy bot kanali
        return new PrivateChannel('user.bot.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'BotResponse';
    }
}