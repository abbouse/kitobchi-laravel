<?php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEdited implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;
    
    public $message;
    
    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender', 'replyTo.sender']);
    }
    
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->message->conversation_id)];
    }
    
    public function broadcastAs(): string
    {
        return 'MessageEdited';
    }
    
    public function broadcastWith(): array
    {
        $message = $this->message;
        
        return [
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender instanceof \App\Models\User ? $message->sender->fullname : null,
                'sender_avatar' => $message->sender instanceof \App\Models\User ? $message->sender->avatar : null,
                'sender_username' => $message->sender instanceof \App\Models\User ? $message->sender->username : null,
                'message' => $message->message,
                'is_read' => $message->is_read ? true : false,
                'is_edited' => $message->is_edited ? true : false,
                'is_deleted' => $message->is_deleted ? true : false,
                'created_at' => $message->created_at ? $message->created_at->toISOString() : null,
                'reply_to_id' => $message->reply_to_id,
                'reply_to' => $message->replyTo ? [
                    'id' => $message->replyTo->id,
                    'conversation_id' => $message->replyTo->conversation_id,
                    'sender_id' => $message->replyTo->sender_id,
                    'sender_name' => $message->replyTo->sender instanceof \App\Models\User ? $message->replyTo->sender->fullname : null,
                    'sender_avatar' => $message->replyTo->sender instanceof \App\Models\User ? $message->replyTo->sender->avatar : null,
                    'sender_username' => $message->replyTo->sender instanceof \App\Models\User ? $message->replyTo->sender->username : null,
                    'message' => $message->replyTo->message,
                    'is_read' => $message->replyTo->is_read ? true : false,
                    'is_edited' => $message->replyTo->is_edited ? true : false,
                    'is_deleted' => $message->replyTo->is_deleted ? true : false,
                    'created_at' => $message->replyTo->created_at ? $message->replyTo->created_at->toISOString() : null,
                ] : null,
            ]
        ];
    }
}
