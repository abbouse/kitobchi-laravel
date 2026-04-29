<?php

namespace App\Services;

use App\Models\BookClubNotification;

class BookClubNotificationTextService
{
    public function format(BookClubNotification $notification, string $locale = 'uz'): array
    {
        $locale = in_array($locale, ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';

        $data = $notification->data ?? [];
        $name = $data['last_user_name'] ?? $this->unknownUserName($locale);
        $count = max(1, (int) ($data['count'] ?? 1));
        $extra = max(0, $count - 1);

        $title = $this->title($notification->type, $locale);
        $body = $this->body($notification->type, $name, $extra, $locale);

        return [
            'title' => $title,
            'body' => $body,
            'group_count' => $count,
        ];
    }

    private function unknownUserName(string $locale): string
    {
        return match ($locale) {
            'ru' => 'Кто-то',
            'en' => 'Someone',
            'ja' => '誰か',
            default => 'Kimdir',
        };
    }

    private function title(string $type, string $locale): string
    {
        $map = [
            'uz' => [
                'like' => 'Yangi like',
                'comment' => 'Yangi izoh',
                'reply' => 'Yangi javob',
                'comment_like' => 'Izohga like',
                'vote' => 'Yangi ovoz',
                'follow' => 'Yangi obuna',
                'repost' => 'Yangi repost',
                'new_post' => 'Yangi post',
                'mention' => 'Siz tilga olindingiz',
            ],
            'ru' => [
                'like' => 'Новый лайк',
                'comment' => 'Новый комментарий',
                'reply' => 'Новый ответ',
                'comment_like' => 'Лайк к комментарию',
                'vote' => 'Новый голос',
                'follow' => 'Новый подписчик',
                'repost' => 'Новый репост',
                'new_post' => 'Новый пост',
                'mention' => 'Вас упомянули',
            ],
            'en' => [
                'like' => 'New like',
                'comment' => 'New comment',
                'reply' => 'New reply',
                'comment_like' => 'Comment liked',
                'vote' => 'New vote',
                'follow' => 'New follower',
                'repost' => 'New repost',
                'new_post' => 'New post',
                'mention' => 'You were mentioned',
            ],
            'ja' => [
                'like' => '新しいいいね',
                'comment' => '新しいコメント',
                'reply' => '新しい返信',
                'comment_like' => 'コメントにいいね',
                'vote' => '新しい投票',
                'follow' => '新しいフォロワー',
                'repost' => '新しいリポスト',
                'new_post' => '新しい投稿',
                'mention' => 'メンションされました',
            ],
        ];

        return $map[$locale][$type] ?? $map[$locale]['like'];
    }

    private function body(string $type, string $name, int $extra, string $locale): string
    {
        $many = $extra > 0;

        return match ($locale) {
            'ru' => match ($type) {
                'like' => $many ? "{$name} и ещё {$extra} человек поставили лайк вашему посту" : "{$name} поставил(а) лайк вашему посту",
                'comment' => $many ? "{$name} и ещё {$extra} человек прокомментировали ваш пост" : "{$name} прокомментировал(а) ваш пост",
                'reply' => $many ? "{$name} и ещё {$extra} человек ответили на ваш комментарий" : "{$name} ответил(а) на ваш комментарий",
                'comment_like' => $many ? "{$name} и ещё {$extra} человек лайкнули ваш комментарий" : "{$name} лайкнул(а) ваш комментарий",
                'vote' => $many ? "{$name} и ещё {$extra} человек проголосовали в вашем опросе" : "{$name} проголосовал(а) в вашем опросе",
                'follow' => "{$name} подписался(ась) на вас",
                'repost' => $many ? "{$name} и ещё {$extra} человек сделали репост вашего поста" : "{$name} сделал(а) репост вашего поста",
                'new_post' => "{$name} опубликовал(а) новый пост",
                'mention' => "{$name} упомянул(а) вас",
                default => "{$name} отправил(а) вам уведомление",
            },
            'en' => match ($type) {
                'like' => $many ? "{$name} and {$extra} others liked your post" : "{$name} liked your post",
                'comment' => $many ? "{$name} and {$extra} others commented on your post" : "{$name} commented on your post",
                'reply' => $many ? "{$name} and {$extra} others replied to your comment" : "{$name} replied to your comment",
                'comment_like' => $many ? "{$name} and {$extra} others liked your comment" : "{$name} liked your comment",
                'vote' => $many ? "{$name} and {$extra} others voted in your poll" : "{$name} voted in your poll",
                'follow' => "{$name} followed you",
                'repost' => $many ? "{$name} and {$extra} others reposted your post" : "{$name} reposted your post",
                'new_post' => "{$name} shared a new post",
                'mention' => "{$name} mentioned you",
                default => "{$name} sent you a notification",
            },
            'ja' => match ($type) {
                'like' => $many ? "{$name}さん他{$extra}人があなたの投稿にいいねしました" : "{$name}さんがあなたの投稿にいいねしました",
                'comment' => $many ? "{$name}さん他{$extra}人があなたの投稿にコメントしました" : "{$name}さんがあなたの投稿にコメントしました",
                'reply' => $many ? "{$name}さん他{$extra}人があなたのコメントに返信しました" : "{$name}さんがあなたのコメントに返信しました",
                'comment_like' => $many ? "{$name}さん他{$extra}人があなたのコメントにいいねしました" : "{$name}さんがあなたのコメントにいいねしました",
                'vote' => $many ? "{$name}さん他{$extra}人があなたの投票に参加しました" : "{$name}さんがあなたの投票に参加しました",
                'follow' => "{$name}さんがあなたをフォローしました",
                'repost' => $many ? "{$name}さん他{$extra}人があなたの投稿をリポストしました" : "{$name}さんがあなたの投稿をリポストしました",
                'new_post' => "{$name}さんが新しい投稿をしました",
                'mention' => "{$name}さんがあなたにメンションしました",
                default => "{$name}さんから新しい通知があります",
            },
            default => match ($type) {
                'like' => $many ? "{$name} va yana {$extra} kishi postingizga like bosdi" : "{$name} postingizga like bosdi",
                'comment' => $many ? "{$name} va yana {$extra} kishi postingizga izoh qoldirdi" : "{$name} postingizga izoh qoldirdi",
                'reply' => $many ? "{$name} va yana {$extra} kishi izohingizga javob yozdi" : "{$name} izohingizga javob yozdi",
                'comment_like' => $many ? "{$name} va yana {$extra} kishi izohingizga like bosdi" : "{$name} izohingizga like bosdi",
                'vote' => $many ? "{$name} va yana {$extra} kishi so'rovnomangizda ovoz berdi" : "{$name} so'rovnomangizda ovoz berdi",
                'follow' => "{$name} sizga obuna bo'ldi",
                'repost' => $many ? "{$name} va yana {$extra} kishi postingizni repost qildi" : "{$name} postingizni repost qildi",
                'new_post' => "{$name} yangi post qoldirdi",
                'mention' => "{$name} sizni tilga oldi",
                default => "{$name} sizga bildirishnoma yubordi",
            },
        };
    }
}
