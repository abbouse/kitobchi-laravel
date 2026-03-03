<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;
    
    public $messageId;
    public $conversationId;
    public $forEveryone;
    
    public function __construct(int $messageId, int $conversationId, bool $forEveryone)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->forEveryone = $forEveryone;
    }
    
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->conversationId)];
    }
    
    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }
    
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'for_everyone' => $this->forEveryone,
        ];
    }
}