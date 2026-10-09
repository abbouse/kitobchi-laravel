<?php

namespace App\Services;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Jobs\SendMessagePushNotification;
use App\Models\BotTicket;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupportChatBridgeService
{
    public function shouldBridgeConversation(?Conversation $conversation): bool
    {
        if (!$conversation || $conversation->type !== 'shop' || !(int) $conversation->shop_id) {
            return false;
        }

        $conversation->loadMissing('shop');
        $shop = $conversation->shop;

        return (bool) $shop && ((bool) ($shop->isSupport ?? false) || (int) $shop->id === 1);
    }

    public function syncUserMessageFromConversation(Message $message): void
    {
        $conversation = $message->conversation()->with('user')->first();
        if (!$this->shouldBridgeConversation($conversation)) {
            return;
        }

        $ticket = $this->findOrCreateTicket($conversation, $message);

        SessionService::saveMessage(
            ticketId: (int) $ticket->id,
            sentBy: 'user',
            message: $message->message,
            messageType: 'text',
            telegramActorId: $conversation->user_id
        );

        // Javob endi boshqaruvdagi support inboxdan beriladi (SessionService::saveMessage → SupportInboxService hook).
    }

    public function sendReplyToConversation(object|array $ticket, string $body, ?int $operatorTelegramId = null, ?int $adminId = null): Message
    {
        $ticketId     = is_array($ticket) ? ($ticket['id'] ?? null) : ($ticket->id ?? null);
        $sourceConvId = is_array($ticket) ? ($ticket['source_conversation_id'] ?? null) : ($ticket->source_conversation_id ?? null);

        $conversation = Conversation::query()->with('shop')->find($sourceConvId);
        if (!$this->shouldBridgeConversation($conversation)) {
            throw new \RuntimeException('Support chat conversation topilmadi.');
        }

        $senderSeller = $conversation->shop;
        if (!$senderSeller) {
            throw new \RuntimeException('Support seller topilmadi.');
        }

        $message = DB::transaction(function () use ($conversation, $senderSeller, $body) {
            $message = $conversation->messages()->create([
                'sender_id' => $senderSeller->id,
                'sender_type' => Seller::class,
                'message' => $body,
                'is_read' => 0,
                'is_edited' => 0,
                'is_deleted' => 0,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        SessionService::saveMessage(
            ticketId: (int) $ticketId,
            sentBy: $adminId ? 'admin' : 'operator',
            message: $body,
            messageType: 'text',
            operatorId: $operatorTelegramId,
            adminId: $adminId,
            telegramActorId: $operatorTelegramId,
            isDelivered: true
        );

        if ($ticket instanceof BotTicket) {
            $ticket->update([
                'status' => SessionService::STATUS_ACTIVE,
                'updated_at' => now(),
            ]);
        } elseif ($ticketId) {
            SessionService::updateTicket((int) $ticketId, [
                'status' => SessionService::STATUS_ACTIVE,
            ]);
        }

        try {
            SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(8));
        } catch (\Throwable $e) {
            Log::warning('Support chat push yuborishda xato', [
                'ticket_id' => $ticketId,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        $freshConversation = $conversation->fresh();
        $this->safeBroadcast(
            event: new MessageSent($message->load('replyTo')),
            context: [
                'ticket_id' => $ticketId,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'event' => 'MessageSent',
            ]
        );
        $this->safeBroadcast(
            event: new ConversationUpdated($freshConversation, (int) $conversation->user_id, 'user'),
            context: [
                'ticket_id' => $ticketId,
                'conversation_id' => $conversation->id,
                'target' => 'user',
                'target_id' => (int) $conversation->user_id,
            ]
        );
        $this->safeBroadcast(
            event: new ConversationUpdated($freshConversation, (int) $conversation->shop_id, 'seller'),
            context: [
                'ticket_id' => $ticketId,
                'conversation_id' => $conversation->id,
                'target' => 'seller',
                'target_id' => (int) $conversation->shop_id,
            ]
        );

        return $message;
    }

    public function notifyConversationTicketClosed(object|array $ticket, string $reason = ''): void
    {
        $ticketId     = is_array($ticket) ? ($ticket['id'] ?? null) : ($ticket->id ?? null);
        $sourceType   = is_array($ticket) ? ($ticket['source_type'] ?? null) : ($ticket->source_type ?? null);
        $sourceConvId = is_array($ticket) ? ($ticket['source_conversation_id'] ?? null) : ($ticket->source_conversation_id ?? null);

        if ($sourceType !== 'shop_chat' || !(int) $sourceConvId) {
            return;
        }

        $conversation = Conversation::query()->with('shop')->find($sourceConvId);
        if (!$conversation || !$conversation->shop) {
            return;
        }

        $senderSeller = $conversation->shop;
        $closeText = "Murojaatingiz yakunlandi. Yordamimiz foydali bo'ldimi? Iltimos, quyida baholang.";

        try {
            $message = $conversation->messages()->create([
                'sender_id'   => $senderSeller->id,
                'sender_type' => Seller::class,
                'message'     => $closeText,
                'is_read'     => 0,
                'is_edited'   => 0,
                'is_deleted'  => 0,
            ]);

            $conversation->update(['last_message_at' => now()]);

            $freshConversation = $conversation->fresh();
            $this->safeBroadcast(
                event: new MessageSent($message->load('replyTo')),
                context: ['ticket_id' => $ticketId, 'conversation_id' => $conversation->id]
            );
            $this->safeBroadcast(
                event: new ConversationUpdated($freshConversation, (int) $conversation->user_id, 'user'),
                context: ['ticket_id' => $ticketId, 'conversation_id' => $conversation->id]
            );

            try {
                SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(8));
            } catch (\Throwable $e) {
                Log::info('[SupportChatBridgeService] yopilish push navbatga qo‘yilmadi: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning("[SupportChatBridgeService] notifyConversationTicketClosed xatosi: " . $e->getMessage());
        }
    }

    private function safeBroadcast(object $event, array $context = []): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('Support chat broadcast xatosi', array_merge($context, [
                'error' => $e->getMessage(),
            ]));
        }
    }

    private function findOrCreateTicket(Conversation $conversation, Message $message): BotTicket
    {
        $ticket = BotTicket::query()
            ->where('source_type', 'shop_chat')
            ->where('source_conversation_id', $conversation->id)
            ->whereIn('status', [SessionService::STATUS_QUEUE, SessionService::STATUS_ACTIVE])
            ->latest('id')
            ->first();

        if ($ticket) {
            return $ticket;
        }

        $user = $conversation->user;

        $ticket = BotTicket::create([
            'user_id' => (int) $conversation->user_id,
            'source_type' => 'shop_chat',
            'source_conversation_id' => (int) $conversation->id,
            'username' => $user?->username,
            'name' => trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? '')) ?: null,
            'status' => SessionService::STATUS_QUEUE,
            'first_msg' => mb_substr((string) $message->message, 0, 500),
        ]);

        SessionService::saveSystemMessage((int) $ticket->id, 'Support seller chatidan yangi ticket yaratildi.');

        return $ticket;
    }
}
