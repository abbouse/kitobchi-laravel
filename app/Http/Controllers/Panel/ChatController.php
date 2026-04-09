<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    // ── Index — tab: user | seller | ai ───────────────────────
    public function index(Request $request)
    {
        $tab    = $request->get('tab', 'user');
        $search = $request->get('search');

        // ── AI tab ─────────────────────────────────────────────
        if ($tab === 'ai') {
            return $this->aiIndex($request);
        }

        // ── User-User yoki User-Seller ─────────────────────────
        // conversations jadval: user_id, receiver_id, shop_id, type
        $q = DB::table('conversations')
            ->select(
                'conversations.id',
                'conversations.user_id',
                'conversations.receiver_id',
                'conversations.shop_id',
                'conversations.type',
                'conversations.last_message_at',
                'conversations.hidden_by',
                // User (suhbatni boshlovchi)
                'u1.name    as user1_name',
                'u1.lastname as user1_lastname',
                'u1.avatar  as user1_avatar',
                'u1.phone_number as user1_phone',
                // Receiver (personal chat uchun)
                'u2.name    as user2_name',
                'u2.lastname as user2_lastname',
                'u2.avatar  as user2_avatar',
                'u2.phone_number as user2_phone',
                // Seller
                's.shop_name as seller_name',
                's.photo     as seller_photo',
                's.id        as seller_id_val',
            )
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id');

        // Tab filtri
        if ($tab === 'seller') {
            $q->whereNotNull('conversations.shop_id');
        } else {
            // personal
            $q->whereNull('conversations.shop_id');
        }

        // Search
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('u1.name',         'like', "%$search%")
                   ->orWhere('u1.lastname',   'like', "%$search%")
                   ->orWhere('u1.phone_number','like',"%$search%")
                   ->orWhere('u2.name',        'like', "%$search%")
                   ->orWhere('u2.lastname',   'like', "%$search%")
                   ->orWhere('u2.phone_number','like',"%$search%")
                   ->orWhere('s.shop_name',   'like', "%$search%");
            });
        }

        $conversations = $q->orderByDesc('conversations.last_message_at')
            ->paginate(20)
            ->withQueryString();

        $counts = $this->getCounts();

        return view('panel.chats.index',
            compact('conversations', 'counts', 'tab'));
    }

    // ── Conversation xabarlari ─────────────────────────────────
    public function show(Request $request, int $id)
    {
        $conversation = DB::table('conversations')
            ->select(
                'conversations.*',
                'u1.name        as user1_name',
                'u1.lastname    as user1_lastname',
                'u1.avatar      as user1_avatar',
                'u1.phone_number as user1_phone',
                'u2.name        as user2_name',
                'u2.lastname    as user2_lastname',
                'u2.avatar      as user2_avatar',
                'u2.phone_number as user2_phone',
                's.shop_name    as seller_name',
                's.id           as seller_id_val',
            )
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->where('conversations.id', $id)
            ->first();

        abort_if(!$conversation, 404);

        // Xabarlar — sender_id + sender_type (User::class)
        $messages = DB::table('messages')
            ->where('conversation_id', $id)
            ->where('is_deleted', false)
            ->orderBy('created_at')
            ->paginate(50);

        // Shikoyat qilingan xabar IDlari
        $reportedIds = DB::table('reports')
            ->where('reportable_type', 'conversation_message')
            ->whereIn('reportable_id', $messages->pluck('id'))
            ->pluck('reportable_id')
            ->toArray();

        return view('panel.chats.show',
            compact('conversation', 'messages', 'reportedIds'));
    }

    // ── AI suhbatlar ───────────────────────────────────────────
    private function aiIndex(Request $request)
    {
        $search = $request->get('search');

        // chat_messages jadvalidan unique user_id lar
        $q = DB::table('chat_messages')
            ->select(
                'chat_messages.user_id',
                'users.name',
                'users.lastname',
                'users.avatar',
                'users.phone_number',
                DB::raw('COUNT(chat_messages.id) as messages_count'),
                DB::raw('MAX(chat_messages.created_at) as last_at'),
                DB::raw('(SELECT message FROM chat_messages cm2
                          WHERE cm2.user_id = chat_messages.user_id
                          ORDER BY cm2.created_at DESC LIMIT 1) as last_message'),
            )
            ->leftJoin('users', 'users.id', '=', 'chat_messages.user_id')
            ->groupBy(
                'chat_messages.user_id',
                'users.name', 'users.lastname',
                'users.avatar', 'users.phone_number'
            );

        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('users.name',         'like', "%$search%")
                   ->orWhere('users.lastname',   'like', "%$search%")
                   ->orWhere('users.phone_number','like',"%$search%");
            });
        }

        $conversations = $q->orderByDesc('last_at')
            ->paginate(20)
            ->withQueryString();

        $counts = $this->getCounts();
        $tab    = 'ai';

        return view('panel.chats.index',
            compact('conversations', 'counts', 'tab'));
    }

    // ── AI user xabarlari ─────────────────────────────────────
    public function showAi(Request $request, int $userId)
    {
        $user = DB::table('users')
            ->select('id', 'name', 'lastname', 'avatar', 'phone_number')
            ->where('id', $userId)
            ->first();

        abort_if(!$user, 404);

        // Xabarlarni ChatMessage model orqali olish
        $messages = \App\Models\ChatMessage::where('user_id', $userId)
            ->orderBy('created_at')
            ->paginate(50);

        // AI xabarlar uchun itemlarni eager load
        // morphMap config da: 'book' => Books::class, 'stationery' => Stationery::class
        $aiMessageIds = $messages->getCollection()
            ->where('is_ai', true)->pluck('id');

        $itemsByMessage = \App\Models\ChatMessageItem::whereIn('chat_message_id', $aiMessageIds)
            ->with('product') // morphTo — morphMap orqali avtomatik Books yoki Stationery yuklaydi
            ->get()
            ->groupBy('chat_message_id');

        // Har xabarga formatlangan itemlarni biriktir
        $messages->getCollection()->transform(function ($msg) use ($itemsByMessage) {
            $msg->items = collect();
            if ($msg->is_ai && $itemsByMessage->has($msg->id)) {
                $msg->items = $itemsByMessage[$msg->id]->map(function ($item) {
                    $product = $item->product; // morphTo — Books yoki Stationery
                    if (!$product) return null;

                    $type  = $item->product_type; // 'book' yoki 'stationery'
                    $imgs  = is_array($product->images ?? null)
                        ? $product->images
                        : json_decode($product->images ?? '[]', true);
                    $image = is_array($imgs) && count($imgs) ? $imgs[0] : null;

                    // Narx: Books da discountPrice, Stationery da discount_price
                    $price = $type === 'book'
                        ? (($product->discountPrice ?? 0) > 0 ? $product->discountPrice : $product->price)
                        : (($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price);

                    return (object) [
                        'type'   => $type,
                        'id'     => $product->id,
                        'name'   => $product->name,
                        'image'  => $image,
                        'author' => $product->author ?? null, // faqat Books da bor
                        'price'  => $price,
                    ];
                })->filter()->values();
            }
            return $msg;
        });

        $conversation = (object) [
            'id'           => $userId,
            'user_id'      => $userId,
            'name'         => $user->name,
            'lastname'     => $user->lastname,
            'avatar'       => $user->avatar,
            'phone_number' => $user->phone_number,
            'updated_at'   => $messages->last()?->created_at ?? now(),
        ];

        return view('panel.chats.show-ai',
            compact('conversation', 'messages'));
    }

    // ── Counts helper ─────────────────────────────────────────
    private function getCounts(): array
    {
        return [
            'user'   => DB::table('conversations')->whereNull('shop_id')->count(),
            'seller' => DB::table('conversations')->whereNotNull('shop_id')->count(),
            'ai'     => DB::table('chat_messages')->distinct('user_id')->count('user_id'),
        ];
    }
}