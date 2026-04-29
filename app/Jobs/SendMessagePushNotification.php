<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\PushController;
use Illuminate\Http\Request;

class SendMessagePushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $messageId;

    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        // 1. Eager Loading bilan yuklash
        $message = Message::with(['conversation.user', 'conversation.shop', 'conversation.participants.user'])
            ->find($this->messageId);

        if (!$message || $message->is_read) return;

        $conversation     = $message->conversation;
        $tokens           = [];
        $appKey           = 'kitobchi';
        $senderName       = "Yangi xabar";
        $senderAvatar     = null;
        $bodyPrefix       = null; // group push'da "SenderName: " prefix uchun

        // 2. Yo'nalishni aniqlash
        if ($conversation->type === 'group') {
            // Group push'da TITLE = guruh nomi, BODY = "SenderName: message".
            // Avval senderName guruh title'i o'rnida turardi — bu Telegram
            // standartiga zid edi, mijoz "Abbos: salom" o'rniga faqat
            // "Abbos" ko'rardi va guruh ekanligini bilmasdi.
            $sender = User::find($message->sender_id);
            $senderFull = trim(($sender->name ?? '') . ' ' . ($sender->lastname ?? '')) ?: 'Foydalanuvchi';

            $senderName   = trim((string) ($conversation->title ?? '')) ?: 'Guruh';
            $senderAvatar = $conversation->avatar ?? ($sender->avatar ?? null);
            $bodyPrefix   = $senderFull;

            $tokens = $conversation->participants
                ->filter(function ($participant) use ($message) {
                    if ((int) $participant->user_id === (int) $message->sender_id) {
                        return false;
                    }

                    return $participant->muted_until === null || $participant->muted_until->isPast();
                })
                ->flatMap(fn ($participant) => $participant->user?->devices?->pluck('fcm_token') ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();
        } elseif ($conversation->type === 'personal') {
            // ── User <-> User ─────────────────────────────────────────────
            $sender       = User::find($message->sender_id);
            $senderName   = trim(($sender->name ?? '') . ' ' . ($sender->lastname ?? '')) ?: "Foydalanuvchi";
            $senderAvatar = $sender->avatar ?? null;

            $receiverId = ($message->sender_id == $conversation->user_id)
                ? $conversation->receiver_id
                : $conversation->user_id;

            $receiver = User::with('devices')->find($receiverId);
            if ($receiver) {
                $tokens = $receiver->devices->pluck('fcm_token')->filter()->toArray();
            }

        } else {
            if ($message->sender_id == $conversation->user_id) {
                // ── User → Shop (Business App) ─────────────────────────────
                $appKey = 'business';

                $sender       = User::find($message->sender_id);
                $senderName   = trim(($sender->name ?? '') . ' ' . ($sender->lastname ?? '')) ?: "Foydalanuvchi";
                $senderAvatar = $sender->avatar ?? null;

                $seller = Seller::with('devices')->find($conversation->shop_id);
                if ($seller) {
                    $tokens = $seller->devices->pluck('fcm_token')->filter()->toArray();
                }
            } else {
                // ── Shop → User (Kitobchi App) ─────────────────────────────
                $shop         = Seller::find($conversation->shop_id);
                $senderName   = $shop->shop_name ?? "Do'kon";
                $senderAvatar = $shop->photo ?? null;

                $user = User::with('devices')->find($conversation->user_id);
                if ($user) {
                    $tokens = $user->devices->pluck('fcm_token')->filter()->toArray();
                }
            }
        }

        // 3. Tokenlarni tozalash (null, bo'sh, dublikat)
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) return;

        // 4. Push yuborish
        $this->dispatchPush($appKey, $message, $conversation, $tokens, $senderName, $senderAvatar, $bodyPrefix);
    }

    private function dispatchPush(
        string  $appKey,
        Message $message,
                $conversation,
        array   $tokens,
        string  $senderName,
        ?string $senderAvatar,
        ?string $bodyPrefix = null
    ): void {
        // Group push uchun body "SenderName: message" formatida bo'ladi —
        // shu orqali bildirgi panelida kim yozganligi ko'rinadi va title
        // guruh nomi bilan to'lib qoladi. Personal/shop chat uchun
        // bodyPrefix null bo'ladi va body shunchaki message matni bo'ladi.
        $body = $bodyPrefix !== null && $bodyPrefix !== ''
            ? "{$bodyPrefix}: {$message->message}"
            : $message->message;

        $pushRequest = new Request([
            'app_key' => $appKey,
            'title'   => $senderName,
            'body'    => $body,
            'tokens'  => $tokens,
            'data'    => [
                'type'             => 'chat',
                'conversation_id'  => (string) $conversation->id,
                'other_party_name' => $senderName,
                'avatar'           => $senderAvatar,
            ],
        ]);

        app(PushController::class)->sendPush($pushRequest);
    }
}
