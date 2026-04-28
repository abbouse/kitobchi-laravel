<?php

namespace App\Services;

use App\Models\BookClubWarning;
use App\Models\User;

class BookClubModerationService
{
    public const BLOCK_THRESHOLD = 7;

    public function activeWarningCountForUser(int $userId): int
    {
        return BookClubWarning::active()
            ->where('user_id', $userId)
            ->count();
    }

    public function isUserBlockedFromWriting(int $userId): bool
    {
        return $this->activeWarningCountForUser($userId) >= self::BLOCK_THRESHOLD;
    }

    public function blockPayloadForUser(User $user): array
    {
        $count = $this->activeWarningCountForUser((int) $user->id);

        return [
            'warning_count' => $count,
            'block_threshold' => self::BLOCK_THRESHOLD,
            'message' => "Boshqalarning xavfsizligi uchun siz Book Club va xabar almashish bo'limida vaqtincha bloklangansiz.",
        ];
    }

    public function warningMetaForPost(int $postId, int $viewerId, int $authorId): ?array
    {
        if ($viewerId !== $authorId) {
            return null;
        }

        $warning = BookClubWarning::active()
            ->where('post_id', $postId)
            ->latest('id')
            ->first();

        if (!$warning) {
            return null;
        }

        return [
            'is_warned' => true,
            'warnings_count' => $this->activeWarningCountForUser($authorId),
            'block_threshold' => self::BLOCK_THRESHOLD,
            'warned_at' => optional($warning->created_at)?->toIso8601String(),
        ];
    }
}
