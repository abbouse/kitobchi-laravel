<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\BookClubWarning;
use App\Models\FcmNotifications;
use App\Models\User;

class BookClubModerationService
{
    public const BLOCK_THRESHOLD = 7;
    public const AI_WARNING_SCORE_THRESHOLD = 1.80;

    public function __construct(
        private readonly UserReputationService $userReputationService,
    ) {
    }

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

    public function syncAiWarningForPost(BookClub $post, float $score, ?string $note = null): void
    {
        if ((int) $post->user_id <= 0 || (bool) $post->is_deleted) {
            return;
        }

        $warning = BookClubWarning::query()
            ->where('post_id', $post->id)
            ->whereNull('admin_id')
            ->first();

        if ($score <= self::AI_WARNING_SCORE_THRESHOLD) {
            $shouldNotify = !$warning || !$warning->is_active;

            $warning = BookClubWarning::query()->updateOrCreate(
                [
                    'post_id' => $post->id,
                    'admin_id' => null,
                ],
                [
                    'user_id' => (int) $post->user_id,
                    'note' => $this->buildAiWarningNote($score, $note),
                    'is_active' => true,
                ]
            );

            $user = User::query()->find((int) $post->user_id);
            if ($user) {
                $this->userReputationService->recalculateUser($user);
                if ($shouldNotify) {
                    $this->sendWarningNotification($user);
                }
            }

            return;
        }

        if ($warning && $warning->is_active) {
            $warning->forceFill([
                'is_active' => false,
                'note' => $this->buildAiWarningNote($score, $note),
            ])->save();

            $user = User::query()->find((int) $post->user_id);
            if ($user) {
                $this->userReputationService->recalculateUser($user);
            }
        }
    }

    private function buildAiWarningNote(float $score, ?string $note = null): string
    {
        $base = "AI moderatsiya posti nomaqbul deb belgiladi (score: ".number_format($score, 2).").";
        $extra = trim((string) $note);

        return $extra !== ''
            ? mb_substr($base.' '.$extra, 0, 5000)
            : $base;
    }

    private function sendWarningNotification(User $user): void
    {
        $warningCount = $this->activeWarningCountForUser((int) $user->id);
        $isBlocked = $warningCount >= self::BLOCK_THRESHOLD;

        FcmNotifications::create([
            'name' => $isBlocked
                ? "Book Club yozish vaqtincha yopildi"
                : "Book Club bo'yicha ogohlantirish",
            'description' => $isBlocked
                ? "Postlaringiz orasida nomaqbul kontent topildi. Shu sabab Book Club va xabar yozish vaqtincha to'xtatildi."
                : "Postlaringizdan birida nomaqbul kontent aniqlandi. Iltimos, keyingi postlarda qoidalarga rioya qiling.",
            'who' => (int) $user->id,
            'is_read' => false,
        ]);
    }
}
