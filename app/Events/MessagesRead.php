<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $readerId;

    public function __construct($conversationId, $readerId)
    {
        $this->conversationId = $conversationId;
        $this->readerId = $readerId;
    }

    public function broadcastOn()
    {
        // Aynan shu chat kanaliga yuboramiz
        return new PrivateChannel('chat.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        // Flutterdagi .listen('.MessagesRead') bilan mos kelishi shart
        return 'MessagesRead';
    }
}