<?php
namespace App\Http\Controllers\Api\Seller;

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
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ PRODUCT ACCESS: OWNER, ADMIN (1), PRODUCT MANAGER (2)
     */
    private function hasProductAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 yoki 2 → ACCESS
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    /**
     * ✅ LOG YOZISH (FAQAT AMAL UCHUN)
     */
    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }
    
    // ========== GET CONVERSATIONS (Backend filter) ==========
public function getConversations(Request $request) {
    $seller = Auth::guard('seller')->user();
    if (!$seller) return response()->json(['status' => 'error'], 401);

    $sellerId = $seller->id;
    $storeSellerId = $this->getStoreSellerId($seller);

    $conversations = Conversation::query()
        ->select('id', 'user_id', 'shop_id', 'type', 'last_message_at', 'hidden_by')
        // Faqat ushbu sellerga tegishli "shop" turidagi suhbatlarni olamiz
        ->where('type', 'shop')
        ->where('shop_id', $storeSellerId) 
        ->withCount(['messages as unread_count' => function($q) {
            // Seller yubormagan va o'qilmagan xabarlar soni
            $q->where('sender_type', '!=', Seller::class)
              ->where('is_read', 0);
        }])
        ->addSelect([
            'last_message' => Message::select('message')
                ->whereColumn('conversation_id', 'conversations.id')
                ->where('is_deleted', 0)
                ->latest()
                ->limit(1)
        ])
        ->with(['user:id,name,lastname,avatar,isVerified,isSupport']) // Faqat kerakli ustunlar
        ->orderByDesc('last_message_at')
        ->get();

    // 🔥 Backend filtering: hidden_by (Seller o'zi yashirgan bo'lsa chiqarmaydi)
    $conversations = $conversations->filter(function($conv) use ($sellerId, $storeSellerId) {
        if (!$conv->hidden_by) return true;
        $hiddenBy = json_decode($conv->hidden_by, true);
        return !is_array($hiddenBy) || (!in_array($sellerId, $hiddenBy) && !in_array($storeSellerId, $hiddenBy));
    })->values();

    $conversations->transform(function ($conv) {
        $otherUser = $conv->user; 
        
        $conv->other_party_name = trim(($otherUser?->name ?? '') . ' ' . ($otherUser?->lastname ?? ''));
        $conv->avatar = $otherUser?->avatar;
        $conv->isVerified = $otherUser?->isVerified ?? 0;
        $conv->isSupport = $otherUser?->isSupport ?? false;

        unset($conv->user, $conv->hidden_by);
        return $conv;
    });

    return response()->json(['status' => 'success', 'data' => $conversations]);
}

    // ========== GET MESSAGES (Backend filter) ==========
    public function getMessages($id, Request $request) {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['status' => 'error'], 401);

        $userId = $seller->id;
        $conversation = Conversation::find($id);
        $storeSellerId = $this->getStoreSellerId($seller);
        if (!$conversation || (int) $conversation->shop_id !== (int) $storeSellerId) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo\'q'], 403);
        }

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
    $seller = Auth::guard('seller')->user();
    if (!$seller) return response()->json(['status' => 'error'], 401);

    $conversationId = $id ?? $request->conversation_id;
    $text = $request->message;
    $replyTo = $request->reply_to_id ?? null;

    try {
        $result = DB::transaction(function() use ($seller, $conversationId, $text, $replyTo) {
            // 1. Seller faqat mavjud suhbatga yoza oladi
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                throw new \Exception("Seller birinchi bo'lib suhbat boshlay olmaydi.");
            }

            if ((int) $conversation->shop_id !== (int) $this->getStoreSellerId($seller)) {
                throw new \Exception("Ruxsat berilmagan.");
            }

            // 2. Bu suhbat haqiqatdan ham shu do'konnikimi?
            //if ($conversation->shop_id != $seller->id) {
            ///    throw new \Exception("Ruxsat berilmagan.");
            //}

            // 3. Hidden holatni ochish (agar user yashirgan bo'lsa, xabar borganda ko'rinsin)
            if ($conversation->hidden_by) {
                $conversation->update(['hidden_by' => null]);
            }

            // 4. Xabarni saqlash
            $message = $conversation->messages()->create([
                'sender_id' => $seller->id,
                'sender_type' => Seller::class, // ✅ User emas, Seller sinfi
                'reply_to_id' => $replyTo,
                'message' => $text,
                'is_read' => 0,
                'is_edited' => 0,
                'is_deleted' => 0,
            ]);

            $conversation->update(['last_message_at' => now()]);
            
            // Push va Broadcast mantiqi
            SendMessagePushNotification::dispatch($message->id)->delay(now()->addSeconds(2));
            $this->broadcastConversationUpdate($conversation, $seller);

            return $message->load('replyTo');
        });

        broadcast(new MessageSent($result))->toOthers();
        return response()->json(['status' => 'success', 'data' => $result]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
    }
}

    // ========== EDIT MESSAGE ==========
    public function editMessage($messageId, Request $request) {
        $request->validate(['message' => 'required|string|max:5000']);
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['status' => 'error'], 401);

        $message = Message::where('id', $messageId)->where('sender_id', $seller->id)->first();
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
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['status' => 'error'], 401);

        $message = Message::where('id', $messageId)->where('sender_id', $seller->id)->first();
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
            if (!in_array($seller->id, $hiddenBy)) {
                $hiddenBy[] = $seller->id;
                $message->hidden_by = json_encode($hiddenBy);
                $message->save();
            }
        }
        return response()->json(['status' => 'success']);
    }

    // ========== HIDE CONVERSATION ==========
    public function hideConversation($conversationId, Request $request) {
    // 1. Tizimga kirgan sellerni olish
    $authSeller = Auth::guard('seller')->user();
    if (!$authSeller) return response()->json(['status' => 'error'], 401);

    // 2. Suhbatni topish
    $conversation = Conversation::find($conversationId);
    if (!$conversation) {
        return response()->json(['status' => 'error', 'message' => 'Topilmadi'], 404);
    }

    // 3. Authorization - Eng muhim joyi!
    // Suhbatdagi shop_id tizimga kirgan sellerning IDsi bilan bir xilmi?
    if ((int)$conversation->shop_id !== (int)$this->getStoreSellerId($authSeller)) {
        return response()->json(['status' => 'error', 'message' => 'Ruxsat yo\'q'], 403);
    }

    // 4. Hidden_by mantiqi
    $hiddenBy = json_decode($conversation->hidden_by ?? '[]', true);
    if (!is_array($hiddenBy)) $hiddenBy = [];

    if (!in_array($authSeller->id, $hiddenBy)) {
        $hiddenBy[] = $authSeller->id;
        $conversation->hidden_by = json_encode($hiddenBy);
        
        // Eski xabarlarni yashirish uchun vaqtni belgilash
        $conversation->messages_hidden_at = now();
        $conversation->save();
    }

    return response()->json(['status' => 'success', 'message' => 'Suhbat yashirildi']);
}

    // ========== MARK AS READ ==========
    public function markAsRead($conversationId, Request $request) {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['status' => 'error'], 401);

        $conversation = Conversation::find($conversationId);
        if (!$conversation || (int) $conversation->shop_id !== (int) $this->getStoreSellerId($seller)) {
            return response()->json(['status' => 'error', 'message' => 'Ruxsat yo\'q'], 403);
        }

        $updated = Message::where('conversation_id', $conversationId)
            ->where('sender_type', '!=', Seller::class)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            broadcast(new MessagesRead($conversationId, $seller->id));
        }

        return response()->json(['status' => 'success', 'updated_count' => $updated]);
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
