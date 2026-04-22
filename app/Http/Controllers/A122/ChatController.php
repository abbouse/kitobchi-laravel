<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'user');
        $search = $request->get('search');

        if ($tab === 'ai') {
            return $this->aiIndex($request);
        }

        $q = DB::table('conversations')
            ->select(
                'conversations.id',
                'conversations.user_id',
                'conversations.receiver_id',
                'conversations.shop_id',
                'conversations.type',
                'conversations.last_message_at',
                'u1.name as user1_name',
                'u1.lastname as user1_lastname',
                'u1.avatar as user1_avatar',
                'u1.phone_number as user1_phone',
                'u2.name as user2_name',
                'u2.lastname as user2_lastname',
                'u2.avatar as user2_avatar',
                'u2.phone_number as user2_phone',
                's.shop_name as seller_name',
                's.photo as seller_photo',
                's.id as seller_id_val',
            )
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id');

        if ($tab === 'seller') {
            $q->whereNotNull('conversations.shop_id');
        } else {
            $q->whereNull('conversations.shop_id');
        }

        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('u1.name', 'like', "%$search%")
                    ->orWhere('u1.lastname', 'like', "%$search%")
                    ->orWhere('u1.phone_number', 'like', "%$search%")
                    ->orWhere('u2.name', 'like', "%$search%")
                    ->orWhere('u2.lastname', 'like', "%$search%")
                    ->orWhere('u2.phone_number', 'like', "%$search%")
                    ->orWhere('s.shop_name', 'like', "%$search%");
            });
        }

        $conversations = $q->orderByDesc('conversations.last_message_at')
            ->paginate(20)
            ->withQueryString();

        $counts = $this->getCounts();

        return view('a122.chats.index', compact('conversations', 'counts', 'tab'));
    }

    public function show(Request $request, int $conversation)
    {
        $conversation = DB::table('conversations')
            ->select(
                'conversations.*',
                'u1.name as user1_name',
                'u1.lastname as user1_lastname',
                'u1.avatar as user1_avatar',
                'u1.phone_number as user1_phone',
                'u2.name as user2_name',
                'u2.lastname as user2_lastname',
                'u2.avatar as user2_avatar',
                'u2.phone_number as user2_phone',
                's.shop_name as seller_name',
                's.id as seller_id_val',
            )
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->where('conversations.id', $conversation)
            ->first();

        abort_if(! $conversation, 404);

        $messages = DB::table('messages')
            ->where('conversation_id', $conversation->id)
            ->where('is_deleted', false)
            ->orderBy('created_at')
            ->paginate(50);

        $reportedIds = DB::table('reports')
            ->where('reportable_type', 'conversation_message')
            ->whereIn('reportable_id', $messages->pluck('id'))
            ->pluck('reportable_id')
            ->toArray();

        return view('a122.chats.show', compact('conversation', 'messages', 'reportedIds'));
    }

    public function showAi(Request $request, int $userId)
    {
        $user = DB::table('users')
            ->select('id', 'name', 'lastname', 'avatar', 'phone_number')
            ->where('id', $userId)
            ->first();

        abort_if(! $user, 404);

        $messages = \App\Models\ChatMessage::where('user_id', $userId)
            ->orderBy('created_at')
            ->paginate(50);

        $aiMessageIds = $messages->getCollection()->where('is_ai', true)->pluck('id');
        $itemsByMessage = \App\Models\ChatMessageItem::whereIn('chat_message_id', $aiMessageIds)
            ->with('product')
            ->get()
            ->groupBy('chat_message_id');

        $messages->getCollection()->transform(function ($msg) use ($itemsByMessage) {
            $msg->items = collect();
            if ($msg->is_ai && $itemsByMessage->has($msg->id)) {
                $msg->items = $itemsByMessage[$msg->id]->map(function ($item) {
                    $product = $item->product;
                    if (! $product) {
                        return null;
                    }

                    $type = $item->product_type;
                    $imgs = is_array($product->images ?? null) ? $product->images : json_decode($product->images ?? '[]', true);
                    $image = is_array($imgs) && count($imgs) ? $imgs[0] : null;
                    $price = $type === 'book'
                        ? (($product->discountPrice ?? 0) > 0 ? $product->discountPrice : $product->price)
                        : (($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price);

                    return (object) [
                        'type' => $type,
                        'id' => $product->id,
                        'name' => $product->name,
                        'image' => $image,
                        'author' => $product->author ?? null,
                        'price' => $price,
                    ];
                })->filter()->values();
            }

            return $msg;
        });

        $conversation = (object) [
            'id' => $userId,
            'user_id' => $userId,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'avatar' => $user->avatar,
            'phone_number' => $user->phone_number,
            'updated_at' => $messages->last()?->created_at ?? now(),
        ];

        return view('a122.chats.show-ai', compact('conversation', 'messages'));
    }

    private function aiIndex(Request $request)
    {
        $search = $request->get('search');

        $q = DB::table('chat_messages')
            ->select(
                'chat_messages.user_id',
                'users.name',
                'users.lastname',
                'users.avatar',
                'users.phone_number',
                DB::raw('COUNT(chat_messages.id) as messages_count'),
                DB::raw('MAX(chat_messages.created_at) as last_at'),
                DB::raw('(SELECT message FROM chat_messages cm2 WHERE cm2.user_id = chat_messages.user_id ORDER BY cm2.created_at DESC LIMIT 1) as last_message'),
            )
            ->leftJoin('users', 'users.id', '=', 'chat_messages.user_id')
            ->groupBy('chat_messages.user_id', 'users.name', 'users.lastname', 'users.avatar', 'users.phone_number');

        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('users.name', 'like', "%$search%")
                    ->orWhere('users.lastname', 'like', "%$search%")
                    ->orWhere('users.phone_number', 'like', "%$search%");
            });
        }

        $conversations = $q->orderByDesc('last_at')->paginate(20)->withQueryString();
        $counts = $this->getCounts();
        $tab = 'ai';

        return view('a122.chats.index', compact('conversations', 'counts', 'tab'));
    }

    private function getCounts(): array
    {
        return [
            'user' => DB::table('conversations')->whereNull('shop_id')->count(),
            'seller' => DB::table('conversations')->whereNotNull('shop_id')->count(),
            'ai' => DB::table('chat_messages')->distinct('user_id')->count('user_id'),
        ];
    }
}
