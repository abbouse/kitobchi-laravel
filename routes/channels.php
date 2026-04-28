<?php
use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use App\Models\Seller;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) return false;

    // Foydalanuvchi qaysi Guard orqali kelayotganini tekshiramiz
    $isUser = $user instanceof \App\Models\User;
    $isSeller = $user instanceof \App\Models\Seller;

    // 1. Agar suhbat DO'KON bilan bo'lsa
    if ($conversation->type === 'shop') {
        if ($isUser) {
            return (int) $user->id === (int) $conversation->user_id;
        }
        if ($isSeller) {
            return (int) $user->id === (int) $conversation->shop_id;
        }
    }

    // 2. Agar suhbat SHAXSIY (User to User) bo'lsa
    if ($conversation->type === 'personal') {
        if ($isUser) {
            return (int) $user->id === (int) $conversation->user_id 
                || (int) $user->id === (int) $conversation->receiver_id;
        }
    }

    return false;
}, ['guards' => ['user', 'seller']]);
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// 🔥 SELLER CHANNEL - Do'kon egasi uchun (agar kerak bo'lsa)
Broadcast::channel('seller.{sellerId}', function ($user, $sellerId) {
    if ($user instanceof \App\Models\Seller) {
        return (int) $user->id === (int) $sellerId;
    }
});

Broadcast::channel('seller-store.{storeId}', function ($user, $storeId) {
    if ($user instanceof \App\Models\Seller) {
        return (int) $user->id === (int) $storeId || (int) ($user->parent_id ?? 0) === (int) $storeId;
    }
    return false;
}, ['guards' => ['seller']]);

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    if ($user instanceof \App\Models\Couriers) {
        return (int) $user->id === (int) $courierId;
    }
    return false;
}, ['guards' => ['courier']]);

Broadcast::channel('courier.feed', function ($user) {
    return $user instanceof \App\Models\Couriers;
}, ['guards' => ['courier']]);

// 🔥 GLOBAL ONLINE - Barcha online userlar
Broadcast::channel('global-online', function ($user) {
    if (Auth::guard('user')->user()) {
        return [
            'id' => (string) $user->id,
        ];
    }
    return false;
});
Broadcast::channel('user.bot.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
