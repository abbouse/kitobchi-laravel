<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Foydalanuvchi xabar yozayotganligi to'g'risida real-time bildirgi.
 *
 * Frontend `chat.{conversationId}` kanalida `.UserTyping` event'ini
 * tinglaydi va header'da "X yozyapti..." ko'rinadi.
 *
 * Yuborish: ChatController::typing endpointidan throttled chaqiriladi
 * (har user uchun 4 sekundda 1 marta).
 */
class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversationId;
    public int $userId;
    public string $userName;
    public ?string $userAvatar;

    public function __construct(
        int $conversationId,
        int $userId,
        string $userName,
        ?string $userAvatar = null
    ) {
        $this->conversationId = $conversationId;
        $this->userId         = $userId;
        $this->userName       = $userName;
        $this->userAvatar     = $userAvatar;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'UserTyping';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id'         => $this->userId,
            'user_name'       => $this->userName,
            'user_avatar'     => $this->userAvatar,
            'at'              => now()->timestamp,
        ];
    }
}
