<?php

namespace App\Http\Controllers\Api\Courier;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Http\Controllers\Controller;
use App\Jobs\SendMessagePushNotification;
use App\Models\Conversation;
use App\Models\Couriers;
use App\Models\Message;
use App\Models\Sold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:courier');
    }

    public function index(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversations = Conversation::query()
            ->select([
                'id',
                'type',
                'user_id',
                'courier_id',
                'order_id',
                'last_message_at',
            ])
            ->where('type', 'courier')
            ->where('courier_id', $courier->id)
            ->addSelect([
                'last_message' => Message::select('message')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->where('is_deleted', 0)
                    ->latest()
                    ->limit(1),
            ])
            ->with([
                'user:id,name,lastname,avatar,username,isVerified,isSupport',
                'order:id,status,deliveryType',
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (Conversation $conversation) => $this->serializeConversationForCourier($conversation, (int) $courier->id));

        return response()->json(['status' => 'success', 'data' => $conversations]);
    }

    public function startForOrder(Request $request, int $orderId)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['status' => 'error'], 401);
        }

        $order = Sold::query()
            ->where('id', $orderId)
            ->where('courier_id', $courier->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Buyurtma topilmadi'], 404);
        }

        if ($this->isConversationClosedByOrder($order)) {
            return response()->json(['status' => 'error', 'message' => 'Yozishma yopilgan'], 423);
        }

        if ((string) $order->deliveryType === 'pickup' || (bool) ($order->is_instore ?? false)) {
            return response()->json(['status' => 'error', 'message' => 'Bu buyurtma kuryer orqali emas'], 422);
        }

        $conversation = Conversation::query()
            ->where('type', 'courier')
            ->where('user_id', $order->user_id)
            ->where('courier_id', $courier->id)
            ->where('order_id', $orderId)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $order->user_id,
                'courier_id' => $courier->id,
                'order_id' => $orderId,
                'type' => 'courier',
                'last_message_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->serializeConversationForCourier(
                $conversation->loadMissing(['user', 'order']),
                (int) $courier->id
            ),
        ]);
    }

    public function getMessages(int $conversationId)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with(['user', 'order'])->find($conversationId);
        if (!$conversation || !$this->canCourierAccessConversation($courier, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        $messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->with(['sender', 'replyTo.sender'])
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->values()
            ->map(fn (Message $message) => $this->serializeMessage($message));

        return response()->json([
            'status' => 'success',
            'data' => $messages,
            'current_page' => 1,
            'last_page' => 1,
            'has_more' => false,
        ]);
    }

    public function sendMessage(Request $request, int $conversationId)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['status' => 'error'], 401);
        }

        $text = trim((string) $request->input('message', ''));
        if ($text === '') {
            return response()->json(['status' => 'error', 'message' => 'Xabar bo‘sh bo‘lishi mumkin emas'], 422);
        }

        $conversation = Conversation::with(['user', 'order'])->find($conversationId);
        if (!$conversation || !$this->canCourierAccessConversation($courier, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        if ($this->isConversationClosed($conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Yozishma yopilgan'], 423);
        }

        $message = DB::transaction(function () use ($conversation, $courier, $text) {
            $message = $conversation->messages()->create([
                'sender_id' => $courier->id,
                'sender_type' => Couriers::class,
                'message' => $text,
                'is_read' => 0,
                'is_edited' => 0,
                'is_deleted' => 0,
            ]);

            $conversation->update(['last_message_at' => now()]);
            $message->load(['sender', 'replyTo.sender']);

            SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));
            $this->broadcastConversationUpdate($conversation);

            return $message;
        });

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'status' => 'success',
            'data' => $this->serializeMessage($message),
        ]);
    }

    public function markAsRead(int $conversationId)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::find($conversationId);
        if (!$conversation || !$this->canCourierAccessConversation($courier, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        $updated = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_type', '!=', Couriers::class)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            broadcast(new MessagesRead($conversationId, $courier->id));
        }

        return response()->json(['status' => 'success', 'updated_count' => $updated]);
    }

    private function canCourierAccessConversation(Couriers $courier, Conversation $conversation): bool
    {
        return $conversation->type === 'courier'
            && (int) $conversation->courier_id === (int) $courier->id;
    }

    private function isConversationClosed(Conversation $conversation): bool
    {
        if ($conversation->type !== 'courier') {
            return false;
        }

        $order = $conversation->relationLoaded('order')
            ? $conversation->order
            : Sold::find($conversation->order_id);

        return $order ? $this->isConversationClosedByOrder($order) : false;
    }

    private function isConversationClosedByOrder(Sold $order): bool
    {
        return in_array((string) $order->status, ['C', 'F'], true);
    }

    private function broadcastConversationUpdate(Conversation $conversation): void
    {
        if ((int) $conversation->user_id > 0) {
            broadcast(new ConversationUpdated($conversation, (int) $conversation->user_id, 'user'));
        }

        if ((int) ($conversation->courier_id ?? 0) > 0) {
            broadcast(new ConversationUpdated($conversation, (int) $conversation->courier_id, 'courier'));
        }
    }

    private function serializeConversationForCourier(Conversation $conversation, int $courierId): array
    {
        $conversation->loadMissing(['user', 'order']);

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'user_id' => $conversation->user_id,
            'courier_id' => $conversation->courier_id,
            'order_id' => $conversation->order_id,
            'last_message_at' => optional($conversation->last_message_at)?->toIso8601String() ?? $conversation->last_message_at,
            'last_message' => $conversation->last_message,
            'unread_count' => (int) Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('sender_type', '!=', Couriers::class)
                ->where('is_read', false)
                ->count(),
            'other_party_name' => trim(($conversation->user?->name ?? '') . ' ' . ($conversation->user?->lastname ?? '')),
            'avatar' => $conversation->user?->avatar,
            'username' => $conversation->user?->username,
            'isVerified' => (bool) ($conversation->user?->isVerified ?? false),
            'isSupport' => (bool) ($conversation->user?->isSupport ?? false),
            'status' => $conversation->order?->status,
            'delivery_type' => $conversation->order?->deliveryType,
        ];
    }

    private function serializeMessage(Message $message): array
    {
        $message->loadMissing(['sender', 'replyTo.sender']);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $this->senderName($message->sender),
            'sender_avatar' => $this->senderAvatar($message->sender),
            'sender_username' => $message->sender?->username ?? null,
            'message' => $message->message,
            'is_read' => (bool) $message->is_read,
            'is_edited' => (bool) $message->is_edited,
            'is_deleted' => (bool) $message->is_deleted,
            'created_at' => optional($message->created_at)?->toIso8601String() ?? $message->created_at,
            'reply_to_id' => $message->reply_to_id,
            'reply_to' => $message->replyTo ? $this->serializeMessage($message->replyTo) : null,
        ];
    }

    private function senderName(mixed $sender): string
    {
        if ($sender instanceof Couriers) {
            return $sender->full_name ?: 'Kuryer';
        }

        return trim(($sender?->name ?? '') . ' ' . ($sender?->lastname ?? '')) ?: 'Foydalanuvchi';
    }

    private function senderAvatar(mixed $sender): ?string
    {
        if ($sender instanceof Couriers) {
            return $sender->photo;
        }

        return $sender?->avatar;
    }
}
