<?php

namespace App\Helpers;

use App\Jobs\SendBookClubPushNotification;
use App\Models\BookClubNotification;
use Illuminate\Support\Carbon;

class NotificationHelper
{
    /**
     * Bildirishnoma yuborish yoki mavjudini yangilash (guruhlash)
     * * @param int $receiverId  Kimga boradi
     * @param object $sender   Yuboruvchi (User model)
     * @param string $type     'like', 'comment', 'new_post', 'follow'
     * @param int|null $postId Post ID (agar bo'lsa)
     */
    public static function send($receiverId, $sender, $type, $postId = null, array $extra = [])
    {
        if ($receiverId == $sender->id) {
            return null;
        }

        $groupKey = self::generateGroupKey($type, $postId, $sender->id, $extra['comment_id'] ?? null);
        $notification = BookClubNotification::where('user_id', $receiverId)
            ->where('group_key', $groupKey)
            ->where('is_read', 0)
            ->first();

        $senderFullName = trim(($sender->name ?? '') . ' ' . ($sender->lastname ?? ''));
        $senderFullName = $senderFullName !== '' ? $senderFullName : ($sender->name ?? 'Kimdir');
        $actor = self::makeActorPayload($sender, $senderFullName);

        if ($notification) {
            $data = self::mergeActorData($notification->data ?? [], $actor, $extra);
            $notification->update([
                'type' => $type,
                'post_id' => $postId,
                'data' => $data,
                'updated_at' => now(),
            ]);
        } else {
            $notification = BookClubNotification::create([
                'user_id' => $receiverId,
                'type' => $type,
                'post_id' => $postId,
                'group_key' => $groupKey,
                'data' => array_merge([
                    'count' => 1,
                    'last_user_id' => $sender->id,
                    'last_user_name' => $senderFullName,
                    'last_user_avatar' => $sender->avatar,
                    'user_ids' => [$sender->id],
                    'actors' => [$actor],
                ], $extra),
                'is_read' => 0
            ]);
        }

        SendBookClubPushNotification::dispatch($notification->id)->delay(now()->addSeconds(2));

        return $notification;
    }

    public static function removeActor(BookClubNotification $notification, int $userId): ?BookClubNotification
    {
        $data = $notification->data ?? [];

        $data['user_ids'] = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $userId)
            ->values()
            ->all();

        $data['actors'] = collect($data['actors'] ?? [])
            ->filter(fn ($actor) => (int) ($actor['user_id'] ?? 0) !== $userId)
            ->sortByDesc(fn ($actor) => $actor['acted_at'] ?? '')
            ->values()
            ->take(8)
            ->all();

        if (empty($data['user_ids'])) {
            $notification->delete();
            return null;
        }

        $notification->update([
            'data' => self::syncSummary($data),
            'updated_at' => now(),
        ]);

        return $notification->fresh();
    }

    /**
     * Guruhlash uchun noyob kalit yaratish
     */
    private static function generateGroupKey($type, $postId, $senderId, $commentId = null)
    {
        switch ($type) {
            case 'like':
            case 'comment':
            case 'vote':
            case 'repost':
                return "{$type}_post_{$postId}"; // Bitta postdagi hamma likelar birga
            case 'reply':
            case 'comment_like':
                return "{$type}_comment_{$commentId}";
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

    private static function makeActorPayload($sender, string $fullName): array
    {
        return [
            'user_id' => (int) $sender->id,
            'name' => $fullName,
            'avatar' => $sender->avatar,
            'acted_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private static function mergeActorData(array $data, array $actor, array $extra = []): array
    {
        $actors = collect($data['actors'] ?? [])
            ->filter(fn ($item) => (int) ($item['user_id'] ?? 0) !== (int) $actor['user_id'])
            ->prepend($actor)
            ->sortByDesc(fn ($item) => $item['acted_at'] ?? '')
            ->values()
            ->take(8)
            ->all();

        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->push((int) $actor['user_id'])
            ->unique()
            ->values()
            ->all();

        $data = array_merge($data, $extra, [
            'actors' => $actors,
            'user_ids' => $userIds,
        ]);

        return self::syncSummary($data);
    }

    private static function syncSummary(array $data): array
    {
        $actors = collect($data['actors'] ?? [])
            ->sortByDesc(fn ($item) => $item['acted_at'] ?? '')
            ->values();

        $lastActor = $actors->first();
        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $data['actors'] = $actors->take(8)->all();
        $data['user_ids'] = $userIds->all();
        $data['count'] = $userIds->count();
        $data['last_user_id'] = $lastActor['user_id'] ?? ($data['last_user_id'] ?? null);
        $data['last_user_name'] = $lastActor['name'] ?? ($data['last_user_name'] ?? null);
        $data['last_user_avatar'] = $lastActor['avatar'] ?? ($data['last_user_avatar'] ?? null);

        return $data;
    }
}
