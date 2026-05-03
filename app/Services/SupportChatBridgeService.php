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
use SergiX44\Nutgram\Nutgram;

class SupportChatBridgeService
{
    public function shouldBridgeConversation(?Conversation $conversation): bool
    {
        return (bool) $conversation
            && $conversation->type === 'shop'
            && (int) $conversation->shop_id === 1;
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
        $conversation = Conversation::query()->find($ticket->source_conversation_id);
        if (!$this->shouldBridgeConversation($conversation)) {
            throw new \RuntimeException('Support chat conversation topilmadi.');
        }

        $senderSeller = Seller::query()->find(1);
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

        SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));
        broadcast(new MessageSent($message->load('replyTo')));
        broadcast(new ConversationUpdated($conversation->fresh(), (int) $conversation->user_id, 'user'));
        broadcast(new ConversationUpdated($conversation->fresh(), 1, 'seller'));

        return $message;
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
