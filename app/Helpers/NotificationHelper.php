<?php

namespace App\Helpers;

use App\Models\BookClubNotification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationHelper
{
    /**
     * Bildirishnoma yuborish yoki mavjudini yangilash (guruhlash)
     * * @param int $receiverId  Kimga boradi
     * @param object $sender   Yuboruvchi (User model)
     * @param string $type     'like', 'comment', 'new_post', 'follow'
     * @param int|null $postId Post ID (agar bo'lsa)
     */
    public static function send($receiverId, $sender, $type, $postId = null)
    {
        // 1. O'ziga o'zi bildirishnoma bormasligi kerak
        if ($receiverId == $sender->id) {
            return;
        }

        // 2. Guruhlash kalitini shakllantirish
        // Bu kalit orqali tizim bitta postdagi likelarni bitta xabarga yig'adi
        $groupKey = self::generateGroupKey($type, $postId, $sender->id);

        // 3. O'qilmagan mavjud bildirishnomani qidirish
        $notification = BookClubNotification::where('user_id', $receiverId)
            ->where('group_key', $groupKey)
            ->where('is_read', 0)
            ->first();

        if ($notification) {
            // Guruhlash logikasi: Mavjud xabarni yangilash
            $data = $notification->data;

            // Agar bu foydalanuvchi ishi oldin ro'yxatga kirmagan bo'lsa (masalan, like-unlike-like)
            if (!in_array($sender->id, $data['user_ids'] ?? [])) {
                $data['count'] = ($data['count'] ?? 1) + 1;
                $data['user_ids'][] = $sender->id; // Takrorlanmaslik uchun ID saqlaymiz
                $data['last_user_name'] = $sender->name . ' ' . ($sender->lastname ?? '');
                $data['last_user_avatar'] = $sender->avatar;
                
                $notification->update([
                    'data' => $data,
                    'updated_at' => now()
                ]);
            }
        } else {
            // Yangi bildirishnoma yaratish
            BookClubNotification::create([
                'user_id' => $receiverId,
                'type' => $type,
                'post_id' => $postId,
                'group_key' => $groupKey,
                'data' => [
                    'count' => 1,
                    'last_user_name' => $sender->name . ' ' . ($sender->lastname ?? ''),
                    'last_user_avatar' => $sender->avatar,
                    'user_ids' => [$sender->id]
                ],
                'is_read' => 0
            ]);
        }
    }

    /**
     * Guruhlash uchun noyob kalit yaratish
     */
    private static function generateGroupKey($type, $postId, $senderId)
    {
        switch ($type) {
            case 'like':
            case 'comment':
                return "{$type}_post_{$postId}"; // Bitta postdagi hamma likelar birga
            case 'new_post':
                return "new_post_user_{$senderId}"; // Bir kishi bir nechta post yozsa
            case 'follow':
                return "follow_user_{$senderId}";
            default:
                return "general_{$type}_{$senderId}";
        }
    }

    /**
     * Notificationni o'qilgan deb belgilash
     */
    public static function markAsRead($notificationId)
    {
        return BookClubNotification::where('id', $notificationId)->update(['is_read' => 1]);
    }

    /**
     * Foydalanuvchining barcha notificationlarini o'qilgan qilish
     */
    public static function markAllRead($userId)
    {
        return BookClubNotification::where('user_id', $userId)->update(['is_read' => 1]);
    }
}