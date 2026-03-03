<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Xabarni model ko'rinishida qabul qilamiz
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Flutter'dagi SocketService bilan bir xil kanal nomi
        return new PrivateChannel('chat.' . $this->message->conversation_id);
    }

    // Soketda xabar nomi qanday chiqishini belgilaydi
    public function broadcastAs()
    {
        return 'MessageSent';
    }
}