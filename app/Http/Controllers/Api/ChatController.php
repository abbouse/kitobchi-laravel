<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Seller;
use App\Events\MessageSent;
use App\Events\MessageEdited;
use App\Events\MessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\ConversationUpdated;
use App\Events\MessagesRead;
use App\Jobs\SendMessagePushNotification;

class ChatController extends Controller
{
    // ========== START CONVERSATION ==========
    public function startConversation(Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $type = $request->type;
        $receiverId = ($type === 'personal') ? $request->receiver_id : null;
        $shopId = ($type === 'shop') ? $request->shop_id : null;

        $conversation = Conversation::where('type', $type)
            ->where('user_id', $user->id)
            ->when($type === 'personal', fn($q) => $q->where('receiver_id', $receiverId))
            ->when($type === 'shop', fn($q) => $q->where('shop_id', $shopId))
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'receiver_id' => $receiverId,
                'shop_id' => $shopId,
                'type' => $type,
                'last_message_at' => now(),
                'hidden_by' => "[$user->id, $shopId]",
                'messages_hidden_at' => now(),
            ]);
        } else {
            // 🔥 MUHIM: Agar yashirilgan bo'lsa, qayta ochish (lekin xabarlar qaytmaydi)
            $hiddenBy = json_decode($conversation->hidden_by ?? '[]', true);
            if (in_array($user->id, $hiddenBy)) {
                $hiddenBy = array_values(array_diff($hiddenBy, [$user->id]));
                $conversation->hidden_by = empty($hiddenBy) ? null : json_encode($hiddenBy);
                // messages_hidden_at o'zgartirilmaydi - eski xabarlar hali ham yashirin
                $conversation->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $conversation->id
        ]);
    }

    // ========== GET CONVERSATIONS (Backend filter) ==========
    public function getConversations(Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $userId = $user->id;
        $type = $request->query('type', 'personal');

        $conversations = Conversation::query()
            ->select('id', 'user_id', 'receiver_id', 'shop_id', 'type', 'last_message_at', 'hidden_by')
            ->withCount(['messages as unread_count' => function($q) use ($userId) {
                $q->where('sender_id', '!=', $userId)->where('is_read', 0);
            }])
            ->addSelect([
                'last_message' => Message::select('message')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->where('is_deleted', 0)
                    ->latest()
                    ->limit(1)
            ])
            ->with(['shop', 'user', 'receiver'])
            ->where('type', $type)
            ->where(function($query) use ($userId, $type) {
                if ($type === 'personal') {
                    $query->where('user_id', $userId)->orWhere('receiver_id', $userId);
                } else {
                    $query->where('user_id', $userId)
                          ->orWhereHas('shop', fn($sq) => $sq->where('user_id', $userId));
                }
            })
            ->orderByDesc('last_message_at')
            ->get();

        // 🔥 Backend filtering: hidden_by
        $conversations = $conversations->filter(function($conv) use ($userId) {
            if (!$conv->hidden_by) return true;
            $hiddenBy = json_decode($conv->hidden_by, true);
            return !is_array($hiddenBy) || !in_array($userId, $hiddenBy);
        })->values();

        $conversations->transform(function ($conv) use ($userId) {
            if ($conv->type === 'shop') {
                $conv->other_party_name = $conv->shop?->shop_name ?? "Do'kon";
                $conv->avatar = $conv->shop?->photo;
                $conv->isVerified = $conv->shop?->isVerified;
                $conv->isSupport = ($conv->shop?->id == 1);
            } else {
                $otherUser = ($conv->user_id == $userId) ? $conv->receiver : $conv->user;
                $conv->other_party_name = trim(($otherUser?->name ?? '') . ' ' . ($otherUser?->lastname ?? ''));
                $conv->avatar = $otherUser?->avatar;
                $conv->isVerified = $otherUser?->isVerified;
                $conv->isSupport = $otherUser?->isSupport;
            }
            unset($conv->shop, $conv->user, $conv->receiver, $conv->hidden_by);
            return $conv;
        });

        return response()->json(['status' => 'success', 'data' => $conversations]);
    }

    // ========== GET MESSAGES (Backend filter) ==========
    public function getMessages($id, Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $userId = $user->id;
        $conversation = Conversation::find($id);

        $messages = Message::where('conversation_id', $id)
            ->with('replyTo')
            ->orderBy('created_at', 'desc')
            ->get();

        // 🔥 Backend filtering
        $messages = $messages->filter(function($msg) use ($userId, $conversation) {
            if ($msg->is_deleted) return false;
            // 2. hidden_by tekshirish
            if ($msg->hidden_by) {
                $hiddenBy = json_decode($msg->hidden_by, true);
                if (is_array($hiddenBy) && in_array($userId, $hiddenBy)) {
                    return false;
                }
            }

            // 3. Conversation yashirilgan va xabar eski bo'lsa
            if ($conversation && $conversation->messages_hidden_at) {
                $hiddenAt = \Carbon\Carbon::parse($conversation->messages_hidden_at);
                $msgCreatedAt = \Carbon\Carbon::parse($msg->created_at);
                if ($msgCreatedAt->lt($hiddenAt)) {
                    return false; // Eski xabar - yashirish
                }
            }

            return true;
        })->values();

        // Pagination
        $perPage = 20;
        $page = $request->query('page', 1);
        $total = $messages->count();
        $messages = $messages->forPage($page, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $messages->values()->toArray(),
            'current_page' => (int)$page,
            'last_page' => ceil($total / $perPage),
            'has_more' => $page < ceil($total / $perPage),
        ]);
    }

    // ========== SEND MESSAGE ==========
    public function sendMessage(Request $request, $id = null) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $conversationId = $id ?? $request->conversation_id;
        $receiverId = $request->receiver_id;
        $shopId = $request->shop_id;
        $text = $request->message;
        $type = $request->type ?? 'personal';
        $replyTo = $request->reply_to_id ?? null;

        try {
            $result = DB::transaction(function() use ($user, $receiverId, $shopId, $conversationId, $text, $type, $replyTo) {
                $conversation = $conversationId ? Conversation::find($conversationId) : null;

                if (!$conversation) {
                    if ($type === 'shop' && $shopId) {
                        $conversation = Conversation::where('user_id', $user->id)
                            ->where('shop_id', $shopId)->where('type', 'shop')->first();
                    } else if ($receiverId) {
                        $conversation = Conversation::where('type', 'personal')
                            ->where(function($q) use ($user, $receiverId) {
                                $q->where('user_id', $user->id)->where('receiver_id', $receiverId);
                            })->orWhere(function($q) use ($user, $receiverId) {
                                $q->where('user_id', $receiverId)->where('receiver_id', $user->id);
                            })->first();
                    }
                }

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'user_id' => $user->id,
                        'receiver_id' => ($type === 'personal') ? $receiverId : null,
                        'shop_id' => ($type === 'shop') ? $shopId : null,
                        'type' => $type,
                        'last_message_at' => now()
                    ]);
                }

                // 🔥 Yangi xabar kelganda conversation hidden_by ni tozalash
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

                SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));
                $message->load('replyTo');
                $conversation->update(['last_message_at' => now()]);
                $this->broadcastConversationUpdate($conversation, $user);
                return ['conversation' => $conversation, 'message' => $message];
            });

            broadcast(new MessageSent($result['message']))->toOthers();
            return response()->json(['status' => 'success', 'data' => $result['message']]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ========== EDIT MESSAGE ==========
    public function editMessage($messageId, Request $request) {
        $request->validate(['message' => 'required|string|max:5000']);
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $message = Message::where('id', $messageId)->where('sender_id', $user->id)->first();
        if (!$message) {
            return response()->json(['status' => 'error', 'message' => 'Xabar topilmadi'], 404);
        }

        $message->update(['message' => $request->message, 'is_edited' => 1]);
        $message->load('replyTo');

        broadcast(new MessageEdited($message));

        return response()->json(['status' => 'success', 'data' => $message]);
    }

    // ========== DELETE MESSAGE ==========
    public function deleteMessage($messageId, Request $request) {
        $request->validate(['for_everyone' => 'required|boolean']);
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $message = Message::where('id', $messageId)->where('sender_id', $user->id)->first();
        if (!$message) {
            return response()->json(['status' => 'error', 'message' => 'Xabar topilmadi'], 404);
        }

        $forEveryone = $request->for_everyone;
        $conversationId = $message->conversation_id;

        if ($forEveryone) {
            $message->update(['is_deleted' => 1]);
            broadcast(new MessageDeleted($message->id, $conversationId, $forEveryone));
        } else {
            $hiddenBy = json_decode($message->hidden_by ?? '[]', true);
            if (!in_array($user->id, $hiddenBy)) {
                $hiddenBy[] = $user->id;
                $message->hidden_by = json_encode($hiddenBy);
                $message->save();
            }
        }
        return response()->json(['status' => 'success']);
    }

    // ========== HIDE CONVERSATION ==========
    public function hideConversation($conversationId, Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return response()->json(['status' => 'error', 'message' => 'Topilmadi'], 404);
        }

        // Authorization
        if ($conversation->type === 'personal') {
            $isParticipant = $conversation->user_id == $user->id || 
                             $conversation->receiver_id == $user->id;
        } else {
            $seller = Seller::find($conversation->shop_id);
            $isParticipant = $conversation->user_id == $user->id || 
                             ($seller && $seller->user_id == $user->id);
        }

        if (!$isParticipant) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo\'q'], 403);
        }

        // 🔥 hidden_by ga qo'shish
        $hiddenBy = json_decode($conversation->hidden_by ?? '[]', true);
        if (!in_array($user->id, $hiddenBy)) {
            $hiddenBy[] = $user->id;
            $conversation->hidden_by = json_encode($hiddenBy);
            
            // 🔥 MUHIM: Hozirgi vaqtni saqlab qolish (eski xabarlar yashiriladi)
            $conversation->messages_hidden_at = now();
            $conversation->save();
        }

        return response()->json(['status' => 'success', 'message' => 'Suhbat yashirildi']);
    }

    // ========== MARK AS READ ==========
    public function markAsRead($conversationId, Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $updated = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            broadcast(new MessagesRead($conversationId, $user->id));
        }

        return response()->json(['status' => 'success', 'updated_count' => $updated]);
    }
    public function getRecentContacts(Request $request) {
        $user = $request->user(); 
        if (!$user) return response()->json(['status' => 'error'], 401);

        $userId = $user->id;

        $recent = Conversation::where(function($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->with(['user', 'receiver', 'shop'])
            ->orderByDesc('last_message_at')
            ->limit(15)
            ->get();

        $recent->transform(function ($conv) use ($userId) {
            if ($conv->type === 'shop') {
                $conv->other_party_name = $conv->shop?->shop_name ?? "Do'kon";
                $conv->avatar = $conv->shop?->photo;
                $conv->isVerified = $conv->shop?->isVerified;
                $conv->isSupport = ($conv->shop?->id == 1);
            } else {
                $other = ($conv->user_id == $userId) ? $conv->receiver : $conv->user;
                $conv->other_party_name = trim(($other?->name ?? '') . ' ' . ($other?->lastname ?? ''));
                $conv->avatar = $other?->avatar;
                $conv->isVerified = $other?->isVerified;
                $conv->isSupport = $other?->isSupport;
            }
            return $conv;
        });

        return response()->json(['status' => 'success', 'data' => $recent]);
    }
    public function globalSearch(Request $request) {
        $query = $request->query('query');
        if (empty($query)) return response()->json(['status' => 'success', 'data' => []]);

        $token = $request->bearerToken();
        $currentUser = User::where('remember_token', $token)->first();

        $users = User::where('id', '!=', $currentUser?->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                  ->orWhere('lastname', 'like', "%$query%");
            })
            ->limit(20)
            ->get()
            ->map(function($user) {
                return [
                    'id' => null,
                    'user_id' => $user->id,
                    'receiver_id' => $user->id,
                    'shop_id' => null,
                    'type' => 'personal',
                    'other_party_name' => trim($user->name . ' ' . $user->lastname),
                    'avatar' => $user->avatar,
                    'last_seen_at' => $user->last_seen_at ?? now()->toDateTimeString(),
                    'isVerified' => $user->isVerified,
                    'isSupport' => $user->isSupport,
                ];
            });

        $shops = DB::table('sellers')
            ->where('status', 'approved')
            ->where('shop_name', 'like', "%$query%")
            ->limit(20)
            ->get()
            ->map(function($shop) {
                return [
                    'id' => null,
                    'user_id' => null,
                    'receiver_id' => null,
                    'shop_id' => $shop->id,
                    'type' => 'shop',
                    'other_party_name' => $shop->shop_name,
                    'avatar' => $shop->photo,
                    'last_seen_at' => $shop->created_at ?? now()->toDateTimeString(),
                    'isVerified' => $shop->isVerified == 1 ? true : false,
                    'isSupport' => $shop->id == 1 ? true : false,
                ];
            });

        $results = $users->concat($shops);
        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }
    protected function broadcastConversationUpdate($conversation, $sender) {
        broadcast(new ConversationUpdated($conversation, $sender->id, 'user'));

        if ($conversation->type === 'personal') {
            $receiverId = ($conversation->user_id == $sender->id) ? $conversation->receiver_id : $conversation->user_id;
            if ($receiverId) {
                broadcast(new ConversationUpdated($conversation, $receiverId, 'user'));
            }
        } else {
            $shopId = DB::table('sellers')->where('id', $conversation->shop_id)->value('id');
            if ($shopId) {
                broadcast(new ConversationUpdated($conversation, $shopId, 'seller'));
            }
        }
    }
}