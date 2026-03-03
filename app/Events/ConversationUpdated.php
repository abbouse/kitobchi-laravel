<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use App\Models\User;
use App\Models\Seller;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $conversation;
    public $forUserId;

    public function __construct(Conversation $conversation, int $forUserId)
    {
        $this->conversation = $conversation->load([
            'messages' => fn($q) => $q->latest()->limit(1)
        ]);
        $this->forUserId = $forUserId;
    }

    public function broadcastOn(): array
{
    return [new PrivateChannel('user.' . $this->forUserId)];
}

    public function broadcastAs(): string
    {
        return 'ConversationUpdated';
    }

    public function broadcastWith(): array
{
    $conversation = $this->conversation;
    $forUserId = $this->forUserId;

    // 1. Suhbatdoshni aniqlaymiz (User modelini olamiz)
    $otherUser = null;
    $otherPartyName = 'unknown';

    if ($conversation->type === 'shop' && $conversation->shop_id) {
        $seller = Seller::find($conversation->shop_id);
        $otherPartyName = $seller?->shop_name ?? 'Do‘kon';
        $isVerified = $seller?->isVerified ?? false;
        $isSupport = $seller?->id == 1 ? true : false;
        // Do'kon bo'lsa, sotuvchining (user) statusini olish kerak bo'lishi mumkin
        // Agar Seller modelida user_id bo'lsa: $otherUser = $seller->user;
    } else {
        // Shaxsiy chat: Kim qabul qiluvchi bo'lsa, o'shani topamiz
        $otherId = ($conversation->user_id == $forUserId) 
                   ? $conversation->receiver_id 
                   : $conversation->user_id;
        
        $otherUser = User::find($otherId);
        $otherPartyName = $otherUser?->fullname ?? 'unknown';
        $isVerified = $otherUser?->isVerified ?? false;
        $isSupport = $otherUser?->isSupport ?? false;
    }

    // 2. Unread count (Sizning kodingiz)
    $unreadCount = Message::where('conversation_id', $conversation->id)
        ->where('sender_id', '!=', $forUserId)
        ->where('is_read', false)
        ->count();

    $lastMessage = $conversation->messages->first();

    return [
        'conversation' => [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'user_id' => $conversation->user_id,
            'receiver_id' => $conversation->receiver_id,
            'shop_id' => $conversation->shop_id,
            'last_message_at' => $conversation->last_message_at,
            'other_party_name' => $otherPartyName,
            'last_message' => $lastMessage?->message ?? null,
            'unread_count' => $unreadCount,
            'avatar' => $otherUser ? $otherUser->avatar : null,
            'last_seen_at' => $otherUser ? $otherUser->last_seen_at : null,
            'isVerified' => $isVerified,
            'isSupport' => $isSupport,
        ]
    ];
}
}