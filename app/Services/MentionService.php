<?php

namespace App\Services;

use App\Helpers\NotificationHelper;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MentionService
{
    public function normalizeUsername(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = ltrim($value, '@');
        $value = preg_replace('/[^a-z0-9_.]/', '', $value ?? '');

        if ($value === '' || mb_strlen($value) < 3 || mb_strlen($value) > 32) {
            return null;
        }

        return $value;
    }

    public function extractMentions(?string $text): array
    {
        $text = (string) $text;
        if ($text === '') {
            return [];
        }

        preg_match_all('/(?:^|[^\w])@([A-Za-z0-9_\.]{3,32})/u', $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($username) => $this->normalizeUsername($username))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function resolveUsers(array $usernames): Collection
    {
        if (empty($usernames)) {
            return collect();
        }

        return User::query()
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy(fn (User $user) => mb_strtolower((string) $user->username));
    }

    public function notifyMentionedUsers(array $usernames, User $sender, string $type, ?int $postId = null, array $extra = []): void
    {
        $users = $this->resolveUsers($usernames);

        foreach ($users as $mentionedUser) {
            if ((int) $mentionedUser->id === (int) $sender->id) {
                continue;
            }

            try {
                NotificationHelper::send(
                    (int) $mentionedUser->id,
                    $sender,
                    $type,
                    $postId,
                    $extra
                );
            } catch (\Throwable $e) {
                Log::warning('[BookClubMention] Mention notification failed', [
                    'receiver_id' => (int) $mentionedUser->id,
                    'sender_id' => (int) $sender->id,
                    'type' => $type,
                    'post_id' => $postId,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
