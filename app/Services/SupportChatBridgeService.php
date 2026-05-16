<?php

namespace App\Services;

use App\Handlers\UserHandler;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Jobs\SendMessagePushNotification;
use App\Models\BotTicket;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

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

        $bot = app(Nutgram::class);

        if ((int) ($ticket->operator_id ?? 0) > 0 && $ticket->status === SessionService::STATUS_ACTIVE) {
            $bot->sendMessage(
                "💬 <b>Support chat #{$ticket->id}</b>\n👤 "
                . SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id)
                . "\n\n" . e($message->message),
                chat_id: (int) $ticket->operator_id,
                parse_mode: 'HTML'
            );
            return;
        }

        if ($ticket->status !== SessionService::STATUS_QUEUE) {
            $ticket->update([
                'status' => SessionService::STATUS_QUEUE,
                'operator_id' => null,
                'closed_at' => null,
                'close_reason' => null,
            ]);
            SessionService::saveSystemMessage((int) $ticket->id, "Mijoz support chatga yana yozdi, ticket qayta navbatga tushdi.");
        }

        UserHandler::dispatchTicket($bot, [
            'id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'username' => $ticket->username,
            'name' => $ticket->name,
            'first_msg' => $message->message,
            'created_at' => optional($ticket->created_at)->toDateTimeString() ?? now()->toDateTimeString(),
        ]);
    }

    public function sendReplyToConversation(BotTicket $ticket, string $body, ?int $operatorTelegramId = null, ?int $adminId = null): Message
    {
        $conversation = Conversation::query()->with('shop')->find($ticket->source_conversation_id);
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
            ticketId: (int) $ticket->id,
            sentBy: $adminId ? 'admin' : 'operator',
            message: $body,
            messageType: 'text',
            operatorId: $operatorTelegramId,
            adminId: $adminId,
            telegramActorId: $operatorTelegramId,
            isDelivered: true
        );

        $ticket->update([
            'status' => SessionService::STATUS_ACTIVE,
            'updated_at' => now(),
        ]);

        try {
            SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));
        } catch (\Throwable $e) {
            Log::warning('Support chat push yuborishda xato', [
                'ticket_id' => $ticket->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        $freshConversation = $conversation->fresh();
        $this->safeBroadcast(
            event: new MessageSent($message->load('replyTo')),
            context: [
                'ticket_id' => $ticket->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'event' => 'MessageSent',
            ]
        );
        $this->safeBroadcast(
            event: new ConversationUpdated($freshConversation, (int) $conversation->user_id, 'user'),
            context: [
                'ticket_id' => $ticket->id,
                'conversation_id' => $conversation->id,
                'target' => 'user',
                'target_id' => (int) $conversation->user_id,
            ]
        );
        $this->safeBroadcast(
            event: new ConversationUpdated($freshConversation, (int) $conversation->shop_id, 'seller'),
            context: [
                'ticket_id' => $ticket->id,
                'conversation_id' => $conversation->id,
                'target' => 'seller',
                'target_id' => (int) $conversation->shop_id,
            ]
        );

        return $message;
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
