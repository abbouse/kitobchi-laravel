<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Seller;
use App\Models\Message;
use App\Models\User;
use App\Models\Couriers;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $conversation;
    public $forUserId;
    public $targetGuard;

    public function __construct(Conversation $conversation, int $forUserId, string $targetGuard = 'user')
    {
        $this->conversation = $conversation->load([
            'messages' => fn ($q) => $q->latest()->limit(1),
            'participants.user:id,name,lastname,username,avatar,isVerified,isSupport',
        ]);
        $this->forUserId = $forUserId;
        $this->targetGuard = $targetGuard;
    }

    public function broadcastOn(): array
    {
        $channel = match ($this->targetGuard) {
            'seller' => 'seller-store.' . $this->forUserId,
            'courier' => 'courier.' . $this->forUserId,
            default => 'user.' . $this->forUserId,
        };

        return [new PrivateChannel($channel)];
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

    if ($conversation->type === 'group') {
        $participant = $conversation->participants->firstWhere('user_id', $forUserId);
        $unreadCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $forUserId)
            ->where('is_deleted', 0)
            ->when($participant?->last_read_at, fn ($q) => $q->where('created_at', '>', $participant->last_read_at))
            ->count();

        return [
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->title,
                'description' => $conversation->description,
                'is_public' => (bool) $conversation->is_public,
                'public_username' => $conversation->public_username,
                'invite_token' => $conversation->invite_token,
                'invite_link' => $conversation->invite_token ? "kitobchi://group/invite/{$conversation->invite_token}" : null,
                'public_link' => $conversation->public_username ? "kitobchi://group/{$conversation->public_username}" : null,
                'avatar' => $conversation->avatar,
                'created_by_id' => $conversation->created_by_id,
                'user_id' => $conversation->user_id,
                'receiver_id' => $conversation->receiver_id,
                'shop_id' => $conversation->shop_id,
                'last_message_at' => $conversation->last_message_at,
                'other_party_name' => $conversation->title ?: 'Group',
                'last_message' => $conversation->messages->first()?->message ?? null,
                'unread_count' => $unreadCount,
                'participant_count' => $conversation->participants->count(),
                'participants_preview' => $conversation->participants
                    ->filter(fn ($item) => (int) $item->user_id !== (int) $forUserId)
                    ->take(3)
                    ->map(fn ($item) => [
                        'id' => $item->user?->id,
                        'name' => $item->user?->name,
                        'lastname' => $item->user?->lastname,
                        'avatar' => $item->user?->avatar,
                        'username' => $item->user?->username,
                    ])
                    ->values()
                    ->all(),
                'is_muted' => $participant ? $participant->is_muted : false,
                'isVerified' => false,
                'isSupport' => false,
            ]
        ];
    } elseif ($conversation->type === 'shop' && $conversation->shop_id) {
        if ($this->targetGuard === 'seller') {
            $otherUser = User::find($conversation->user_id);
            $otherPartyName = $otherUser?->fullname ?? 'Foydalanuvchi';
            $isVerified = $otherUser?->isVerified ?? false;
            $isSupport = $otherUser?->isSupport ?? false;
            $avatar = $otherUser?->avatar;
            $lastSeenAt = $otherUser?->last_seen_at;
        } else {
            $seller = Seller::find($conversation->shop_id);
            $otherPartyName = $seller?->shop_name ?? 'Do‘kon';
            $isVerified = $seller?->isVerified ?? false;
            $isSupport = $seller?->id == 1 ? true : false;
            $avatar = $seller?->photo;
            $lastSeenAt = null;
        }
    } elseif ($conversation->type === 'courier' && $conversation->courier_id) {
        if ($this->targetGuard === 'courier') {
            $otherUser = User::find($conversation->user_id);
            $otherPartyName = $otherUser?->fullname ?? 'Mijoz';
            $isVerified = $otherUser?->isVerified ?? false;
            $isSupport = $otherUser?->isSupport ?? false;
            $avatar = $otherUser?->avatar;
            $lastSeenAt = $otherUser?->last_seen_at;
        } else {
            $courier = Couriers::find($conversation->courier_id);
            $otherPartyName = $courier?->full_name ?? 'Kuryer';
            $isVerified = true;
            $isSupport = false;
            $avatar = $courier?->photo;
            $lastSeenAt = null;
        }
    } else {
        // Shaxsiy chat: Kim qabul qiluvchi bo'lsa, o'shani topamiz
        $otherId = ($conversation->user_id == $forUserId) 
                   ? $conversation->receiver_id 
                   : $conversation->user_id;
        
        $otherUser = User::find($otherId);
        $otherPartyName = $otherUser?->fullname ?? 'unknown';
        $isVerified = $otherUser?->isVerified ?? false;
        $isSupport = $otherUser?->isSupport ?? false;
        $avatar = $otherUser?->avatar;
        $lastSeenAt = $otherUser?->last_seen_at;
    }

    // 2. Unread count (Sizning kodingiz)
    $unreadQuery = Message::where('conversation_id', $conversation->id)
        ->where('is_read', false);

    if ($conversation->type === 'shop') {
        $unreadQuery->where('sender_type', '!=', $this->targetGuard === 'seller' ? Seller::class : User::class);
    } elseif ($conversation->type === 'courier') {
        $unreadQuery->where('sender_type', '!=', $this->targetGuard === 'courier' ? Couriers::class : User::class);
    } else {
        $unreadQuery->where('sender_id', '!=', $forUserId);
    }

    $unreadCount = $unreadQuery->count();

    $lastMessage = $conversation->messages->first();

    return [
        'conversation' => [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'user_id' => $conversation->user_id,
            'receiver_id' => $conversation->receiver_id,
            'shop_id' => $conversation->shop_id,
            'courier_id' => $conversation->courier_id,
            'order_id' => $conversation->order_id,
            'last_message_at' => $conversation->last_message_at,
            'other_party_name' => $otherPartyName,
            'last_message' => $lastMessage?->message ?? null,
            'unread_count' => $unreadCount,
            'avatar' => $avatar,
            'last_seen_at' => $lastSeenAt,
            'isVerified' => $isVerified,
            'isSupport' => $isSupport,
            'username' => $otherUser?->username,
        ]
    ];
}
}
