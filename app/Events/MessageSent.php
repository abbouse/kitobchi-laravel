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

    public function broadcastWith(): array
    {
        $message = $this->message->loadMissing(['sender', 'replyTo.sender']);

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
                'is_read' => (bool) $message->is_read,
                'is_edited' => (bool) $message->is_edited,
                'is_deleted' => (bool) $message->is_deleted,
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
                    'is_read' => (bool) $message->replyTo->is_read,
                    'is_edited' => (bool) $message->replyTo->is_edited,
                    'is_deleted' => (bool) $message->replyTo->is_deleted,
                    'created_at' => $message->replyTo->created_at ? $message->replyTo->created_at->toISOString() : null,
                ] : null,
            ],
        ];
    }
}
