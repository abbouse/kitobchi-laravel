<?php

namespace App\Http\Controllers\Api;

use App\Events\ConversationUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageEdited;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Jobs\SendMessagePushNotification;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Seller;
use App\Models\User;
use App\Services\BookClubModerationService;
use App\Services\MentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function __construct(
        private readonly BookClubModerationService $moderationService,
        private readonly MentionService $mentionService,
    ) {}

    public function startConversation(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $type = $request->input('type');
        if (!in_array($type, ['personal', 'shop'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Noto‘g‘ri chat turi'], 422);
        }

        $receiverId = $type === 'personal' ? (int) $request->input('receiver_id') : null;
        $shopId = $type === 'shop' ? (int) $request->input('shop_id') : null;

        $conversation = Conversation::query()
            ->where('type', $type)
            ->where('user_id', $user->id)
            ->when($type === 'personal', fn ($q) => $q->where('receiver_id', $receiverId))
            ->when($type === 'shop', fn ($q) => $q->where('shop_id', $shopId))
            ->first();

        if (!$conversation && $type === 'personal') {
            $conversation = Conversation::query()
                ->where('type', 'personal')
                ->where('user_id', $receiverId)
                ->where('receiver_id', $user->id)
                ->first();
        }

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'receiver_id' => $receiverId,
                'shop_id' => $shopId,
                'type' => $type,
                'last_message_at' => now(),
                'hidden_by' => $type === 'shop' ? json_encode([$user->id, $shopId]) : null,
                'messages_hidden_at' => now(),
            ]);
        } elseif ($conversation->hidden_by) {
            $hiddenBy = json_decode($conversation->hidden_by, true) ?: [];
            if (in_array($user->id, $hiddenBy, true)) {
                $hiddenBy = array_values(array_diff($hiddenBy, [$user->id]));
                $conversation->hidden_by = empty($hiddenBy) ? null : json_encode($hiddenBy);
                $conversation->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $conversation->id,
        ]);
    }

    public function createGroup(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        if ($this->moderationService->isUserBlockedFromWriting((int) $user->id)) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'book_club_user_blocked',
                'message' => "Boshqalarning xavfsizligi uchun siz Book Club va xabar almashish bo'limida vaqtincha bloklangansiz.",
            ], 423);
        }

        if (is_string($request->input('member_ids'))) {
            $decoded = json_decode($request->input('member_ids'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['member_ids' => $decoded]);
            }
        }

        $data = $request->validate([
            'title' => 'required|string|min:2|max:120',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
            'public_username' => [
                'nullable',
                'string',
                'min:4',
                'max:32',
                'regex:/^[a-zA-Z0-9_\\.]+$/',
                'unique:conversations,public_username',
            ],
            'avatar' => 'nullable|image|max:5120',
            'member_ids' => 'required|array|min:1|max:50',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $isPublic = (bool) ($data['is_public'] ?? false);
        $publicUsername = Str::lower(trim((string) ($data['public_username'] ?? '')));
        if ($isPublic && $publicUsername === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Public guruh uchun username kiritilishi shart.',
            ], 422);
        }

        $memberIds = collect($data['member_ids'])
            ->map(fn ($id) => (int) $id)
            ->push((int) $user->id)
            ->unique()
            ->values();

        $conversation = DB::transaction(function () use ($data, $memberIds, $request, $user, $isPublic, $publicUsername) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('chat_groups', 'public');
            }

            $conversation = Conversation::create([
                'type' => 'group',
                'title' => trim((string) $data['title']),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'is_public' => $isPublic,
                'public_username' => $isPublic ? $publicUsername : null,
                'invite_token' => $isPublic ? null : Str::random(32),
                'avatar' => $avatarPath,
                'created_by_id' => $user->id,
                'user_id' => $user->id,
                'last_message_at' => now(),
            ]);

            $rows = $memberIds->map(function (int $memberId) use ($conversation, $user) {
                return [
                    'conversation_id' => $conversation->id,
                    'user_id' => $memberId,
                    'role' => $memberId === (int) $user->id ? 'owner' : 'member',
                    'joined_at' => now(),
                    'last_read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            ConversationParticipant::insert($rows);

            return $conversation->load([
                'participants.user:id,name,lastname,username,avatar,isVerified,isSupport,position,staff_role,role_emoji,role_title,role_place',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $this->serializeConversationForUser($conversation, (int) $user->id),
        ], 201);
    }

    public function updateGroupMute(Request $request, int $conversationId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $data = $request->validate([
            'muted' => 'required|boolean',
        ]);

        $conversation = Conversation::with('participants')->find($conversationId);
        if (!$conversation || $conversation->type !== 'group') {
            return response()->json(['status' => 'error', 'message' => 'Guruh topilmadi'], 404);
        }

        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        $participant->muted_until = $data['muted'] ? now()->addYears(50) : null;
        $participant->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_muted' => $data['muted'] === true,
            ],
        ]);
    }

    public function getConversations(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $type = $request->query('type', 'personal');
        if (!in_array($type, ['personal', 'shop', 'group'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Noto‘g‘ri chat turi'], 422);
        }

        $conversations = Conversation::query()
            ->select([
                'id',
                'type',
                'title',
                'description',
                'is_public',
                'public_username',
                'invite_token',
                'avatar',
                'created_by_id',
                'user_id',
                'receiver_id',
                'shop_id',
                'last_message_at',
                'hidden_by',
            ])
            ->addSelect([
                'last_message' => Message::select('message')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->where('is_deleted', 0)
                    ->latest()
                    ->limit(1),
            ])
            ->with([
                'shop',
                'user',
                'receiver',
                'participants.user:id,name,lastname,username,avatar,isVerified,isSupport,position,staff_role,role_emoji,role_title,role_place',
            ])
            ->where('type', $type)
            ->where(function ($query) use ($user, $type) {
                if ($type === 'personal') {
                    $query->where('user_id', $user->id)->orWhere('receiver_id', $user->id);
                    return;
                }

                if ($type === 'shop') {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('shop', fn ($sq) => $sq->where('user_id', $user->id));
                    return;
                }

                $query->whereHas('participants', fn ($sq) => $sq->where('user_id', $user->id));
            })
            ->orderByDesc('last_message_at')
            ->get()
            ->filter(function (Conversation $conversation) use ($user) {
                if (!$conversation->hidden_by) {
                    return true;
                }

                $hiddenBy = json_decode($conversation->hidden_by, true);
                return !is_array($hiddenBy) || !in_array($user->id, $hiddenBy, true);
            })
            ->values()
            ->map(fn (Conversation $conversation) => $this->serializeConversationForUser($conversation, (int) $user->id));

        return response()->json(['status' => 'success', 'data' => $conversations]);
    }

    public function getConversationDetails(int $id, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with([
            'shop',
            'user',
            'receiver',
            'participants.user:id,name,lastname,username,avatar,isVerified,isSupport,position,staff_role,role_emoji,role_title,role_place,last_seen_at',
        ])->find($id);

        if (!$conversation || !$this->canUserAccessConversation($user, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        $payload = $this->serializeConversationForUser($conversation, (int) $user->id);
        $payload['participants'] = $conversation->participants
            ->map(function (ConversationParticipant $participant) {
                return [
                    'id' => $participant->user?->id,
                    'name' => $participant->user?->name,
                    'lastname' => $participant->user?->lastname,
                    'username' => $participant->user?->username,
                    'avatar' => $participant->user?->avatar,
                    'position' => $participant->user?->position,
                    'role_emoji' => $participant->user?->role_emoji,
                    'role_title' => $participant->user?->role_title,
                    'role_place' => $participant->user?->role_place,
                    'isVerified' => (bool) ($participant->user?->isVerified ?? false),
                    'isSupport' => (bool) ($participant->user?->isSupport ?? false),
                    'role' => $participant->role,
                    'joined_at' => optional($participant->joined_at)?->toIso8601String(),
                    'muted_until' => optional($participant->muted_until)?->toIso8601String(),
                    'last_seen_at' => optional($participant->user?->last_seen_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function getMessages($id, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with('participants')->find($id);
        if (!$conversation || !$this->canUserAccessConversation($user, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        $messages = Message::query()
            ->where('conversation_id', $id)
            ->with(['sender', 'replyTo.sender'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function (Message $message) use ($user, $conversation) {
                if ($message->is_deleted) {
                    return false;
                }

                if ($message->hidden_by) {
                    $hiddenBy = json_decode($message->hidden_by, true);
                    if (is_array($hiddenBy) && in_array($user->id, $hiddenBy, true)) {
                        return false;
                    }
                }

                if ($conversation->messages_hidden_at) {
                    $hiddenAt = \Carbon\Carbon::parse($conversation->messages_hidden_at);
                    if ($message->created_at->lt($hiddenAt)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->query('page', 1));
        $total = $messages->count();
        $paged = $messages->forPage($page, $perPage)->values()->map(fn (Message $message) => $this->serializeMessage($message));

        return response()->json([
            'status' => 'success',
            'data' => $paged,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'has_more' => $page < (int) ceil($total / $perPage),
        ]);
    }

    public function sendMessage(Request $request, $id = null)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        if ($this->moderationService->isUserBlockedFromWriting((int) $user->id)) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'book_club_user_blocked',
                'message' => "Boshqalarning xavfsizligi uchun siz Book Club va xabar almashish bo'limida vaqtincha bloklangansiz.",
            ], 423);
        }

        $conversationId = $id ?? $request->input('conversation_id');
        $receiverId = $request->input('receiver_id');
        $shopId = $request->input('shop_id');
        $text = trim((string) $request->input('message', ''));
        $type = $request->input('type', 'personal');
        $replyTo = $request->input('reply_to_id');

        if ($text === '') {
            return response()->json(['status' => 'error', 'message' => 'Xabar bo‘sh bo‘lishi mumkin emas'], 422);
        }

        try {
            $result = DB::transaction(function () use ($conversationId, $receiverId, $replyTo, $shopId, $text, $type, $user) {
                $conversation = $conversationId ? Conversation::with('participants')->find($conversationId) : null;

                if ($conversation) {
                    if (!$this->canUserAccessConversation($user, $conversation)) {
                        abort(403, 'Ruxsat yo‘q');
                    }
                } elseif ($type === 'group') {
                    abort(404, 'Guruh topilmadi');
                } elseif ($type === 'shop' && $shopId) {
                    $conversation = Conversation::query()
                        ->where('user_id', $user->id)
                        ->where('shop_id', $shopId)
                        ->where('type', 'shop')
                        ->first();
                } elseif ($receiverId) {
                    $conversation = Conversation::query()
                        ->where('type', 'personal')
                        ->where(function ($q) use ($receiverId, $user) {
                            $q->where('user_id', $user->id)->where('receiver_id', $receiverId);
                        })
                        ->orWhere(function ($q) use ($receiverId, $user) {
                            $q->where('user_id', $receiverId)->where('receiver_id', $user->id);
                        })
                        ->first();
                }

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'user_id' => $user->id,
                        'receiver_id' => $type === 'personal' ? $receiverId : null,
                        'shop_id' => $type === 'shop' ? $shopId : null,
                        'type' => $type,
                        'last_message_at' => now(),
                    ]);
                }

                if ($conversation->hidden_by) {
                    $conversation->hidden_by = null;
                    $conversation->save();
                }

                $message = $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'sender_type' => User::class,
                    'reply_to_id' => $replyTo,
                    'message' => $text,
                    'is_read' => 0,
                    'is_edited' => 0,
                    'is_deleted' => 0,
                ]);

                if ($conversation->type === 'group') {
                    $conversation->participants()
                        ->where('user_id', $user->id)
                        ->update(['last_read_at' => now()]);
                }

                $conversation->update(['last_message_at' => now()]);
                $message->load(['sender', 'replyTo.sender', 'conversation.participants']);

                $this->mentionService->notifyMentionedUsers(
                    $this->mentionService->extractMentions($text),
                    $user,
                    'mention',
                    null,
                    [
                        'conversation_id' => (int) $conversation->id,
                        'message_id' => (int) $message->id,
                        'context' => 'chat',
                    ]
                );

                $this->broadcastConversationUpdate($conversation, $user);
                SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));

                return ['conversation' => $conversation, 'message' => $message];
            });

            broadcast(new MessageSent($result['message']))->toOthers();

            return response()->json([
                'status' => 'success',
                'data' => $this->serializeMessage($result['message']),
            ]);
        } catch (\Throwable $e) {
            $status = (int) ($e->getCode() === 403 ? 403 : 500);
            return response()->json([
                'status' => 'error',
                'message' => $status === 403 ? 'Ruxsat yo‘q' : $e->getMessage(),
            ], $status);
        }
    }

    public function editMessage($messageId, Request $request)
    {
        $request->validate(['message' => 'required|string|max:5000']);
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $message = Message::where('id', $messageId)->where('sender_id', $user->id)->first();
        if (!$message) {
            return response()->json(['status' => 'error', 'message' => 'Xabar topilmadi'], 404);
        }

        $message->update(['message' => $request->message, 'is_edited' => 1]);
        $message->load(['sender', 'replyTo.sender']);

        broadcast(new MessageEdited($message));

        return response()->json([
            'status' => 'success',
            'data' => $this->serializeMessage($message),
        ]);
    }

    public function deleteMessage($messageId, Request $request)
    {
        $request->validate(['for_everyone' => 'required|boolean']);
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $message = Message::where('id', $messageId)->where('sender_id', $user->id)->first();
        if (!$message) {
            return response()->json(['status' => 'error', 'message' => 'Xabar topilmadi'], 404);
        }

        if ($request->boolean('for_everyone')) {
            $message->update(['is_deleted' => 1]);
            broadcast(new MessageDeleted($message->id, $message->conversation_id, true));
        } else {
            $hiddenBy = json_decode($message->hidden_by ?? '[]', true) ?: [];
            if (!in_array($user->id, $hiddenBy, true)) {
                $hiddenBy[] = $user->id;
                $message->hidden_by = json_encode($hiddenBy);
                $message->save();
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function hideConversation($conversationId, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with('participants')->find($conversationId);
        if (!$conversation) {
            return response()->json(['status' => 'error', 'message' => 'Topilmadi'], 404);
        }

        if (!$this->canUserAccessConversation($user, $conversation)) {
            return response()->json(['status' => 'error', 'message' => "Ruxsat yo'q"], 403);
        }

        $hiddenBy = json_decode($conversation->hidden_by ?? '[]', true) ?: [];
        if (!in_array($user->id, $hiddenBy, true)) {
            $hiddenBy[] = $user->id;
            $conversation->hidden_by = json_encode($hiddenBy);
            $conversation->messages_hidden_at = now();
            $conversation->save();
        }

        return response()->json(['status' => 'success', 'message' => 'Suhbat yashirildi']);
    }

    public function markAsRead($conversationId, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with('participants')->find($conversationId);
        if (!$conversation || !$this->canUserAccessConversation($user, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        if ($conversation->type === 'group') {
            $conversation->participants()
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);

            return response()->json(['status' => 'success', 'updated_count' => 0]);
        }

        $updated = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            broadcast(new MessagesRead($conversationId, $user->id));
        }

        return response()->json(['status' => 'success', 'updated_count' => $updated]);
    }

    /**
     * Mijoz xabar yozayotganligi haqida real-time signal.
     *
     * Frontend har 4 sekundda bir marta chaqiradi (text input change'da).
     * Bu yerda Cache orqali rate-limit qilinadi: bir foydalanuvchi 4 soniyada
     * faqat 1 marta broadcast qila oladi (Reverb yuklamasini kamaytirish).
     *
     * UserTyping event chat.{conversationId} kanaliga yuboriladi.
     * Tinglovchi mijozlar timeout 5 sekund qo'yib, undan keyin "yozyapti..."
     * yozuvini olib tashlaydi.
     */
    public function typing($conversationId, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $conversation = Conversation::with('participants')->find($conversationId);
        if (!$conversation || !$this->canUserAccessConversation($user, $conversation)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo‘q'], 403);
        }

        // Throttle: per (user, conversation) — 4 sekundda 1 marta.
        $cacheKey = "typing:{$conversationId}:{$user->id}";
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => 'success', 'throttled' => true]);
        }
        Cache::put($cacheKey, 1, 4);

        $name = trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')) ?: 'Foydalanuvchi';

        broadcast(new UserTyping(
            (int) $conversationId,
            (int) $user->id,
            $name,
            $user->avatar ?? null,
        ))->toOthers();

        return response()->json(['status' => 'success']);
    }

    public function getRecentContacts(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error'], 401);
        }

        $recent = Conversation::query()
            ->with([
                'user',
                'receiver',
                'shop',
                'participants.user:id,name,lastname,username,avatar,isVerified,isSupport,position,staff_role,role_emoji,role_title,role_place',
            ])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('receiver_id', $user->id)
                    ->orWhereHas('participants', fn ($sq) => $sq->where('user_id', $user->id));
            })
            ->orderByDesc('last_message_at')
            ->limit(15)
            ->get()
            ->map(fn (Conversation $conversation) => $this->serializeConversationForUser($conversation, (int) $user->id));

        return response()->json(['status' => 'success', 'data' => $recent]);
    }

    public function globalSearch(Request $request)
    {
        $query = trim((string) $request->query('query', ''));
        if ($query === '') {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $currentUser = $request->user();
        $normalizedUsername = $this->mentionService->normalizeUsername($query);

        $users = User::query()
            ->where('id', '!=', $currentUser?->id)
            ->where(function ($q) use ($normalizedUsername, $query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('lastname', 'like', "%{$query}%");

                if ($normalizedUsername) {
                    $q->orWhere('username', 'like', "%{$normalizedUsername}%");
                }
            })
            ->limit(20)
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => null,
                    'user_id' => $user->id,
                    'receiver_id' => $user->id,
                    'shop_id' => null,
                    'type' => 'personal',
                    'other_party_name' => trim($user->name . ' ' . $user->lastname),
                    'avatar' => $user->avatar,
                    'last_seen_at' => $user->last_seen_at ?? now()->toDateTimeString(),
                    'isVerified' => (bool) $user->isVerified,
                    'isSupport' => (bool) $user->isSupport,
                    'username' => $user->username,
                ];
            });

        $shops = DB::table('sellers')
            ->where('status', 'approved')
            ->where('shop_name', 'like', "%{$query}%")
            ->limit(20)
            ->get()
            ->map(function ($shop) {
                return [
                    'id' => null,
                    'user_id' => null,
                    'receiver_id' => null,
                    'shop_id' => $shop->id,
                    'type' => 'shop',
                    'other_party_name' => $shop->shop_name,
                    'avatar' => $shop->photo,
                    'last_seen_at' => $shop->created_at ?? now()->toDateTimeString(),
                    'isVerified' => $shop->isVerified == 1,
                    'isSupport' => $shop->id == 1,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $users->concat($shops)->values(),
        ]);
    }

    protected function broadcastConversationUpdate(Conversation $conversation, User $sender): void
    {
        if ($conversation->type === 'group') {
            $participantIds = $conversation->participants()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($participantIds as $participantId) {
                broadcast(new ConversationUpdated($conversation, $participantId, 'user'));
            }

            return;
        }

        broadcast(new ConversationUpdated($conversation, $sender->id, 'user'));

        if ($conversation->type === 'personal') {
            $receiverId = (int) (($conversation->user_id == $sender->id) ? $conversation->receiver_id : $conversation->user_id);
            if ($receiverId > 0) {
                broadcast(new ConversationUpdated($conversation, $receiverId, 'user'));
            }
            return;
        }

        $shopId = (int) DB::table('sellers')->where('id', $conversation->shop_id)->value('id');
        if ($shopId > 0) {
            broadcast(new ConversationUpdated($conversation, $shopId, 'seller'));
        }
    }

    private function canUserAccessConversation(User $user, Conversation $conversation): bool
    {
        if ($conversation->type === 'personal') {
            return (int) $conversation->user_id === (int) $user->id
                || (int) $conversation->receiver_id === (int) $user->id;
        }

        if ($conversation->type === 'shop') {
            return (int) $conversation->user_id === (int) $user->id
                || $conversation->shop?->user_id === (int) $user->id;
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    private function unreadCountForUser(Conversation $conversation, int $userId): int
    {
        if ($conversation->type === 'group') {
            $participant = $conversation->participants
                ->firstWhere('user_id', $userId);

            $query = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $userId)
                ->where('is_deleted', 0);

            if ($participant?->last_read_at) {
                $query->where('created_at', '>', $participant->last_read_at);
            }

            return (int) $query->count();
        }

        return (int) Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    private function serializeConversationForUser(Conversation $conversation, int $userId): array
    {
        $conversation->loadMissing([
            'shop',
            'user',
            'receiver',
            'participants.user:id,name,lastname,username,avatar,isVerified,isSupport,position,staff_role,role_emoji,role_title,role_place',
        ]);

        $payload = [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'title' => $conversation->title,
            'description' => $conversation->description,
            'is_public' => (bool) $conversation->is_public,
            'public_username' => $conversation->public_username,
            'invite_token' => $conversation->invite_token,
            'invite_link' => $conversation->invite_token ? $this->buildGroupInviteLink($conversation->invite_token) : null,
            'public_link' => $conversation->public_username ? $this->buildPublicGroupLink($conversation->public_username) : null,
            'avatar' => $conversation->avatar,
            'created_by_id' => $conversation->created_by_id,
            'user_id' => $conversation->user_id,
            'receiver_id' => $conversation->receiver_id,
            'shop_id' => $conversation->shop_id,
            'last_message_at' => optional($conversation->last_message_at)?->toIso8601String() ?? $conversation->last_message_at,
            'last_message' => $conversation->last_message,
            'unread_count' => $this->unreadCountForUser($conversation, $userId),
            'isVerified' => false,
            'isSupport' => false,
            'participant_count' => 0,
            'is_muted' => false,
        ];

        if ($conversation->type === 'group') {
            $participants = $conversation->participants;
            $participant = $participants->firstWhere('user_id', $userId);

            $otherMembers = $participants
                ->filter(fn ($item) => (int) $item->user_id !== $userId)
                ->take(3)
                ->map(function ($item) {
                    return [
                        'id' => $item->user?->id,
                        'name' => $item->user?->name,
                        'lastname' => $item->user?->lastname,
                        'avatar' => $item->user?->avatar,
                        'username' => $item->user?->username,
                    ];
                })
                ->values()
                ->all();

            return array_merge($payload, [
                'other_party_name' => $conversation->title ?: 'Group',
                'participant_count' => $participants->count(),
                // Header'da "N online" ko'rsatish uchun barcha a'zolar
                // ID'larini qaytaramiz. Mijoz tarafdagi global online
                // ro'yxati bilan kesib olib aktiv onlinelarni hisoblaydi.
                'participant_ids' => $participants->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
                'participants_preview' => $otherMembers,
                'is_muted' => $participant ? $participant->is_muted : false,
            ]);
        }

        if ($conversation->type === 'shop') {
            return array_merge($payload, [
                'other_party_name' => $conversation->shop?->shop_name ?? "Do'kon",
                'avatar' => $conversation->shop?->photo,
                'isVerified' => (bool) ($conversation->shop?->isVerified ?? false),
                'isSupport' => (bool) (($conversation->shop?->id ?? 0) == 1),
            ]);
        }

        $otherUser = (int) $conversation->user_id === $userId
            ? $conversation->receiver
            : $conversation->user;

        return array_merge($payload, [
            'other_party_name' => trim(($otherUser?->name ?? '') . ' ' . ($otherUser?->lastname ?? '')),
            'avatar' => $otherUser?->avatar,
            'last_seen_at' => optional($otherUser?->last_seen_at)?->toIso8601String() ?? $otherUser?->last_seen_at,
            'isVerified' => (bool) ($otherUser?->isVerified ?? false),
            'isSupport' => (bool) ($otherUser?->isSupport ?? false),
            'username' => $otherUser?->username,
        ]);
    }

    private function serializeMessage(Message $message): array
    {
        $message->loadMissing(['sender', 'replyTo.sender']);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender instanceof User ? $message->sender->fullname : null,
            'sender_avatar' => $message->sender instanceof User ? $message->sender->avatar : null,
            'sender_username' => $message->sender instanceof User ? $message->sender->username : null,
            'message' => $message->message,
            'is_read' => (bool) $message->is_read,
            'is_edited' => (bool) $message->is_edited,
            'is_deleted' => (bool) $message->is_deleted,
            'created_at' => optional($message->created_at)?->toIso8601String(),
            'reply_to_id' => $message->reply_to_id,
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'conversation_id' => $message->replyTo->conversation_id,
                'sender_id' => $message->replyTo->sender_id,
                'sender_name' => $message->replyTo->sender instanceof User ? $message->replyTo->sender->fullname : null,
                'sender_avatar' => $message->replyTo->sender instanceof User ? $message->replyTo->sender->avatar : null,
                'sender_username' => $message->replyTo->sender instanceof User ? $message->replyTo->sender->username : null,
                'message' => $message->replyTo->message,
                'is_read' => (bool) $message->replyTo->is_read,
                'is_edited' => (bool) $message->replyTo->is_edited,
                'is_deleted' => (bool) $message->replyTo->is_deleted,
                'created_at' => optional($message->replyTo->created_at)?->toIso8601String(),
            ] : null,
        ];
    }

    private function buildGroupInviteLink(string $inviteToken): string
    {
        return "kitobchi://group/invite/{$inviteToken}";
    }

    private function buildPublicGroupLink(string $publicUsername): string
    {
        return "kitobchi://group/{$publicUsername}";
    }
}
