<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\BookClubComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookClubAiScoringService
{
    private const POST_CHUNK = 25;
    private const COMMENT_CHUNK = 40;
    private const MODEL_NAME = 'gpt-4o-mini';

    public function __construct(
        private readonly OpenAIService $openAIService,
        private readonly BookClubModerationService $moderationService,
        private readonly ProductReviewFeedbackPushService $productReviewFeedbackPushService,
    ) {
    }

    /**
     * @return array{posts:int,comments:int}
     */
    public function scorePendingContent(bool $full = false, int $postLimit = 150, int $commentLimit = 240): array
    {
        $this->syncRepostAiScores();

        $posts = BookClub::query()
            ->where('is_deleted', false)
            ->where(function ($query) {
                $query->where('repost', false)->orWhereNull('repost');
            })
            ->when(!$full, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('ai_post_checked_at')
                        ->orWhereColumn('updated_at', '>', 'ai_post_checked_at')
                        ->orWhereIn('ai_post_status', ['pending', 'failed']);
                });
            })
            ->orderByDesc('updated_at')
            ->limit($postLimit)
            ->get([
                'id',
                'user_id',
                'product_id',
                'product_type',
                'text',
                'is_deleted',
                'updated_at',
                'ai_post_feedback_notified_at',
            ]);

        $comments = BookClubComment::query()
            ->when(!$full, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('ai_checked_at')
                        ->orWhereColumn('updated_at', '>', 'ai_checked_at')
                        ->orWhereIn('ai_status', ['pending', 'failed']);
                });
            })
            ->orderByDesc('updated_at')
            ->limit($commentLimit)
            ->get(['id', 'post_id', 'content', 'parent_id']);

        $this->scorePosts($posts);
        $this->scoreComments($comments);

        return [
            'posts' => $posts->count(),
            'comments' => $comments->count(),
        ];
    }

    private function syncRepostAiScores(int $limit = 300): void
    {
        BookClub::query()
            ->where('is_deleted', false)
            ->where('repost', true)
            ->where(function ($query) {
                $query->whereNull('ai_post_score')
                    ->orWhereNull('ai_post_checked_at')
                    ->orWhereIn('ai_post_status', ['pending', 'failed', 'skipped_repost']);
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get([
                'id',
                'reposted_user_id',
                'text',
                'product_id',
                'product_type',
                'ai_post_status',
            ])
            ->each(function (BookClub $repost): void {
                if (!$repost->reposted_user_id) {
                    BookClub::query()->whereKey($repost->id)->update([
                        'ai_post_status' => 'skipped_repost',
                        'ai_post_note' => 'Repost: original post topilmadi',
                        'ai_post_checked_at' => now(),
                    ]);
                    return;
                }

                $original = BookClub::query()
                    ->where('is_deleted', false)
                    ->where(function ($query) {
                        $query->where('repost', false)->orWhereNull('repost');
                    })
                    ->where('user_id', $repost->reposted_user_id)
                    ->where('text', $repost->text)
                    ->where('product_id', $repost->product_id)
                    ->where('product_type', $repost->product_type)
                    ->where('ai_post_status', 'scored')
                    ->whereNotNull('ai_post_score')
                    ->orderByDesc('id')
                    ->first([
                        'ai_post_score',
                        'ai_post_note',
                        'ai_post_model',
                        'ai_post_checked_at',
                    ]);

                if (!$original) {
                    BookClub::query()->whereKey($repost->id)->update([
                        'ai_post_status' => 'skipped_repost',
                        'ai_post_note' => 'Repost: original post bahosi kutilmoqda',
                        'ai_post_checked_at' => now(),
                    ]);
                    return;
                }

                BookClub::query()->whereKey($repost->id)->update([
                    'ai_post_score' => $original->ai_post_score,
                    'ai_post_status' => 'scored',
                    'ai_post_note' => $original->ai_post_note ?: 'Repost: original post AI bahosi ishlatildi',
                    'ai_post_model' => $original->ai_post_model,
                    'ai_post_checked_at' => $original->ai_post_checked_at ?: now(),
                    'ai_post_feedback_notified_at' => now(),
                ]);
            });
    }

    /**
     * @param Collection<int, BookClub> $posts
     */
    public function scorePosts(Collection $posts): void
    {
        $posts->chunk(self::POST_CHUNK)->each(function (Collection $chunk): void {
            $payload = $chunk->map(fn (BookClub $post) => [
                'id' => (int) $post->id,
                'text' => trim((string) $post->text),
            ])->values()->all();

            $result = $this->openAIService->askJsonWithMessages([
                [
                    'role' => 'system',
                    'content' => <<<TXT
Sen Kitobchi Book Club uchun kontent baholovchi moderatsiya yordamchisisan.
Vazifa: har bir post matniga 1 dan 5 gacha sifat bahosi ber.

Baholash mezonlari:
1 = spam, haqorat, zararli, butunlay befoyda.
2 = juda sust, mavzudan tashqari, deyarli foydasiz.
3 = oddiy, qabul qilsa bo'ladi, lekin kuchli qiymat bermaydi.
4 = foydali, aniq, o'qishga arziydi.
5 = juda foydali, ishonchli, yaxshi yozilgan, jamoaga qiymat beradi.

Faqat postning sifati, foydaliligi, aniqligi va madaniyligiga qaragin. Faqat ijobiy kayfiyatga qarab yuqori ball bermagin.
Qisqa, lekin mazmunli postga ham adolatli baho ber.

Natijani faqat JSON qil:
{
  "posts": [
    {"id": 1, "score": 4.2, "note": "Qisqa, lekin foydali fikr", "confidence": 0.83}
  ]
}

note 90 belgidan oshmasin.
TXT
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(['posts' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 2200, 0.2);

            $rows = collect($result['posts'] ?? [])->keyBy(fn ($row) => (int) ($row['id'] ?? 0));

            foreach ($chunk as $post) {
                $row = $rows->get((int) $post->id);

                if (!$row || !is_numeric($row['score'] ?? null)) {
                    $this->markPostFailed($post->id, 'AI javobida post topilmadi');
                    continue;
                }

                $score = $this->clampScore((float) $row['score']);
                $aiNote = Str::limit(trim((string) ($row['note'] ?? '')), 255, '');

                BookClub::query()->whereKey($post->id)->update([
                    'ai_post_score' => $score,
                    'ai_post_status' => 'scored',
                    'ai_post_note' => $aiNote,
                    'ai_post_model' => self::MODEL_NAME,
                    'ai_post_checked_at' => now(),
                ]);

                $post->forceFill([
                    'ai_post_score' => $score,
                    'ai_post_note' => $aiNote,
                    'ai_post_status' => 'scored',
                ]);

                $this->moderationService->syncAiWarningForPost($post, $score, $aiNote);

                if ($this->shouldSendProductFeedbackPush($post)) {
                    if ($this->productReviewFeedbackPushService->sendForPost($post, $score)) {
                        BookClub::query()->whereKey($post->id)->update([
                            'ai_post_feedback_notified_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * @param Collection<int, BookClubComment> $comments
     */
    public function scoreComments(Collection $comments): void
    {
        $comments->chunk(self::COMMENT_CHUNK)->each(function (Collection $chunk): void {
            $payload = $chunk->map(fn (BookClubComment $comment) => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->post_id,
                'is_reply' => $comment->parent_id !== null,
                'content' => trim((string) $comment->content),
            ])->values()->all();

            $result = $this->openAIService->askJsonWithMessages([
                [
                    'role' => 'system',
                    'content' => <<<TXT
Sen Kitobchi Book Club izohlari uchun sifat baholovchi yordamchisisan.
Har bir izohga 1 dan 5 gacha baho ber.

Mezon:
1 = haqorat, spam, zararli yoki butunlay befoyda.
2 = juda sust, mavzuga deyarli yordam bermaydi.
3 = oddiy, qabul qilsa bo'ladi.
4 = foydali, mulohazali, mavzuga hissa qo'shadi.
5 = juda foydali, chuqur, ishonchli, boshqalarga yordam beradi.

Qisqa izohlarni avtomatik past baholama; agar mazmunli bo'lsa adolatli baho ber.
Natijani faqat JSON qil:
{
  "comments": [
    {"id": 10, "score": 4.0, "note": "Mavzuga mos va foydali", "confidence": 0.8}
  ]
}
note 90 belgidan oshmasin.
TXT
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(['comments' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 2600, 0.2);

            $rows = collect($result['comments'] ?? [])->keyBy(fn ($row) => (int) ($row['id'] ?? 0));

            foreach ($chunk as $comment) {
                $row = $rows->get((int) $comment->id);

                if (!$row || !is_numeric($row['score'] ?? null)) {
                    $this->markCommentFailed($comment->id, 'AI javobida izoh topilmadi');
                    continue;
                }

                BookClubComment::query()->whereKey($comment->id)->update([
                    'ai_score' => $this->clampScore((float) $row['score']),
                    'ai_status' => 'scored',
                    'ai_note' => Str::limit(trim((string) ($row['note'] ?? '')), 255, ''),
                    'ai_model' => self::MODEL_NAME,
                    'ai_checked_at' => now(),
                ]);
            }
        });
    }

    private function markPostFailed(int $postId, string $note): void
    {
        BookClub::query()->whereKey($postId)->update([
            'ai_post_status' => 'failed',
            'ai_post_note' => Str::limit($note, 255, ''),
            'ai_post_model' => self::MODEL_NAME,
            'ai_post_checked_at' => now(),
        ]);
    }

    private function markCommentFailed(int $commentId, string $note): void
    {
        BookClubComment::query()->whereKey($commentId)->update([
            'ai_status' => 'failed',
            'ai_note' => Str::limit($note, 255, ''),
            'ai_model' => self::MODEL_NAME,
            'ai_checked_at' => now(),
        ]);
    }

    private function clampScore(float $score): float
    {
        return round(max(1, min(5, $score)), 2);
    }

    private function shouldSendProductFeedbackPush(BookClub $post): bool
    {
        if (!$post->product_id || !in_array((string) $post->product_type, ['book', 'stationery'], true)) {
            return false;
        }

        $notifiedAt = $post->ai_post_feedback_notified_at;
        if ($notifiedAt === null) {
            return true;
        }

        return $post->updated_at && $post->updated_at->gt($notifiedAt);
    }
}
