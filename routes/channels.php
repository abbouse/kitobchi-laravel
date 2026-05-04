<?php
use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use App\Models\Seller;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::with('participants')->find($conversationId);
    if (!$conversation) return false;

    // Foydalanuvchi qaysi Guard orqali kelayotganini tekshiramiz
    $isUser = $user instanceof \App\Models\User;
    $isSeller = $user instanceof \App\Models\Seller;
    $isCourier = $user instanceof \App\Models\Couriers;

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

    if ($conversation->type === 'group') {
        if ($isUser) {
            return $conversation->participants->contains(fn ($participant) => (int) $participant->user_id === (int) $user->id);
        }
    }

    if ($conversation->type === 'courier') {
        if ($isUser) {
            return (int) $user->id === (int) $conversation->user_id;
        }
        if ($isCourier) {
            return (int) $user->id === (int) $conversation->courier_id;
        }
    }

    return false;
}, ['guards' => ['user', 'seller', 'courier']]);
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['user']]);

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

Broadcast::channel('courier.feed', function ($user) {
    // Bu kanal umumiy courier feed uchun. Guard allaqachon `courier` bilan
    // autentifikatsiyadan o'tgan bo'ladi, shuning uchun bu yerda faqat haqiqiy
    // model/id borligini tekshirish kifoya. `instanceof` ba'zi muhitlarda
    // serialize/proxy holatlari sabab ortiqcha qat'iy bo'lib qolishi mumkin.
    return !empty($user?->id);
}, ['guards' => ['courier']]);

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    if ($user instanceof \App\Models\Couriers) {
        return (int) $user->id === (int) $courierId;
    }
    return false;
}, ['guards' => ['courier']]);

// 🔥 GLOBAL ONLINE - Barcha online userlar
Broadcast::channel('global-online', function ($user) {
    return [
        'id' => (string) $user->id,
    ];
}, ['guards' => ['user']]);
Broadcast::channel('user.bot.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['user']]);
