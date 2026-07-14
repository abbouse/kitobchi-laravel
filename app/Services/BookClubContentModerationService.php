<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\BookClubComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookClubContentModerationService
{
    private const BATCH_SIZE = 25;
    private const MODEL = 'gpt-4o-mini';

    public function __construct(
        private readonly OpenAIService $openAIService,
        private readonly BookClubContentPolicy $policy,
    ) {}

    /** @return array{posts:int,comments:int,hidden:int,shown:int,failed:int} */
    public function moderatePending(?int $postLimit = null, ?int $commentLimit = null): array
    {
        $posts = BookClub::query()
            ->where('is_deleted', false)
            // Admin qo'lda hal qilgan (manual) kontentni AI qayta baholamaydi — qaror qaytmaydi.
            ->where(fn ($query) => $query
                ->whereNull('ai_moderation_status')
                ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']))
            ->where(function ($query) {
                $query->whereNull('ai_moderation_status')
                    ->orWhere('ai_moderation_status', 'pending')
                    ->orWhere(function ($failed) {
                        $failed->where('ai_moderation_status', 'failed')
                            ->where('ai_moderated_at', '<=', now()->subMinutes(30));
                    })
                    ->orWhereColumn('updated_at', '>', 'ai_moderated_at');
            })
            ->orderBy('id')
            ->limit($postLimit ?? (int) config('book_club_moderation.post_limit', 150))
            ->get(['id', 'user_id', 'text', 'is_hidden_by_ai', 'ai_moderation_status']);

        $comments = BookClubComment::query()
            ->whereHas('post', fn ($query) => $query->where('is_deleted', false))
            ->where(fn ($query) => $query
                ->whereNull('ai_moderation_status')
                ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']))
            ->where(function ($query) {
                $query->whereNull('ai_moderation_status')
                    ->orWhere('ai_moderation_status', 'pending')
                    ->orWhere(function ($failed) {
                        $failed->where('ai_moderation_status', 'failed')
                            ->where('ai_moderated_at', '<=', now()->subMinutes(30));
                    })
                    ->orWhereColumn('updated_at', '>', 'ai_moderated_at');
            })
            ->orderBy('id')
            ->limit($commentLimit ?? (int) config('book_club_moderation.comment_limit', 300))
            ->get(['id', 'post_id', 'user_id', 'content', 'is_hidden_by_ai', 'ai_moderation_status']);

        return $this->process($posts, $comments);
    }

    /** @return array{posts:int,comments:int,hidden:int,shown:int,failed:int} */
    public function moderateAll(int $limit = 0): array
    {
        $postsQuery = BookClub::query()
            ->where('is_deleted', false)
            ->where(fn ($query) => $query
                ->whereNull('ai_moderation_status')
                ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']))
            ->orderBy('id');
        $commentsQuery = BookClubComment::query()
            ->whereHas('post', fn ($query) => $query->where('is_deleted', false))
            ->where(fn ($query) => $query
                ->whereNull('ai_moderation_status')
                ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']))
            ->orderBy('id');

        if ($limit > 0) {
            $postsQuery->limit($limit);
            $commentsQuery->limit($limit);
        }

        return $this->process(
            $postsQuery->get(['id', 'user_id', 'text', 'is_hidden_by_ai', 'ai_moderation_status']),
            $commentsQuery->get(['id', 'post_id', 'user_id', 'content', 'is_hidden_by_ai', 'ai_moderation_status']),
        );
    }

    /**
     * @param Collection<int, BookClub> $posts
     * @param Collection<int, BookClubComment> $comments
     * @return array{posts:int,comments:int,hidden:int,shown:int,failed:int}
     */
    private function process(Collection $posts, Collection $comments): array
    {
        $stats = [
            'posts' => $posts->count(),
            'comments' => $comments->count(),
            'hidden' => 0,
            'shown' => 0,
            'failed' => 0,
        ];

        $items = collect()
            ->concat($posts->map(fn (BookClub $post) => [
                'key' => 'post:'.$post->id,
                'type' => 'post',
                'id' => (int) $post->id,
                'user_id' => (int) $post->user_id,
                'text' => trim((string) $post->text),
                'policy' => $this->policy->inspect($post->text),
                'model' => $post,
            ]))
            ->concat($comments->map(fn (BookClubComment $comment) => [
                'key' => 'comment:'.$comment->id,
                'type' => 'comment',
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->post_id,
                'user_id' => (int) $comment->user_id,
                'text' => trim((string) $comment->content),
                'policy' => $this->policy->inspect($comment->content),
                'model' => $comment,
            ]));

        foreach ($items->chunk(self::BATCH_SIZE) as $chunk) {
            $this->holdPendingChunk($chunk);
            $decisions = $this->requestDecisions($chunk);

            foreach ($chunk as $item) {
                $decision = $decisions->get($item['key']);
                if (! $decision) {
                    $this->markFailed($item['model'], $item['policy'], 'AI javobida kontent qarori topilmadi');
                    $stats['failed']++;
                    continue;
                }

                $action = ($decision['action'] ?? 'hide') === 'show' ? 'show' : 'hide';
                $reason = $this->normalizeReason((string) ($decision['reason'] ?? 'other'));
                $note = Str::limit(trim((string) ($decision['note'] ?? '')), 360, '');
                $confidence = max(0, min(1, (float) ($decision['confidence'] ?? 0)));

                // Ishonch past bo'lsa yashirmaymiz — zararsiz post bekorga ketmasligi uchun
                // (ijtimoiy tarmoq uslubi). Og'ir toifalarda bo'sag'a pastroq.
                if ($action === 'hide') {
                    $severeReasons = ['scam', 'sexual', 'hate', 'harassment', 'illegal', 'suspicious_link'];
                    $threshold = in_array($reason, $severeReasons, true)
                        ? (float) config('book_club_moderation.severe_confidence', 0.55)
                        : (float) config('book_club_moderation.hide_confidence', 0.80);
                    if ($confidence < $threshold) {
                        $action = 'show';
                    }
                }

                // Faqat KUCHLI link xavfi (qisqartirilgan / yashirilgan / IP) majburan
                // yashiriladi. Oddiy tashqi link o'zi yashirish sababi emas.
                $policy = $item['policy'];
                $severeLink = ($policy['shortener_domains'] ?? []) !== []
                    || in_array('obfuscated_link', $policy['signals'] ?? [], true)
                    || in_array('ip_address_link', $policy['signals'] ?? [], true);
                if ($severeLink) {
                    $action = 'hide';
                    $reason = 'suspicious_link';
                }

                $this->storeDecision(
                    $item['model'],
                    $action,
                    $reason,
                    $note,
                    $confidence,
                    $item['policy'],
                );

                $stats[$action === 'hide' ? 'hidden' : 'shown']++;
            }
        }

        Log::info('Book Club AI moderation completed', $stats);

        return $stats;
    }

    private function requestDecisions(Collection $chunk): Collection
    {
        $payload = $chunk->map(fn (array $item) => [
            'key' => $item['key'],
            'type' => $item['type'],
            'text' => $item['text'],
            'signals' => $item['policy']['signals'],
            'trusted_domains' => $item['policy']['trusted_domains'],
            'trusted_platform_mentions' => $item['policy']['trusted_platform_mentions'],
            'untrusted_domains' => $item['policy']['untrusted_domains'],
        ])->values()->all();

        $result = $this->openAIService->askJsonWithMessages([
            [
                'role' => 'system',
                'content' => <<<'PROMPT'
Siz Kitobchi Book Club hamjamiyatining qat'iy, lekin adolatli moderatsiya AI'isiz.

Har bir post yoki izohga `show` yoki `hide` qarorini bering.

SHOW:
- kitob, mahsulot, o'qish, kundalik hayot haqidagi fikr, savol, tavsiya va madaniy bahs;
- qonuniy reklama, aksiya yoki tavsiya, agar u `trusted_domains`dagi mashhur global yoki O'zbekiston platformasiga tegishli bo'lsa;
- brend/platforma nomini oddiy tilga olish;
- konstruktiv tanqid va qo'pol bo'lmagan salbiy fikr.

HIDE:
- `untrusted_domains`dagi, qisqartirilgan, yashirilgan yoki IP ko'rinishidagi link;
- takroriy reklama, ma'nosiz flood, bot matni, engagement bait yoki bir xil xabarni tarqatish;
- firibgarlik, piramida, kazino/stavka, soxta daromad va noqonuniy savdo;
- so'kinish, shaxsiy haqorat, tahdid, nafrat/kamsitish, pornografik yoki ochiq jinsiy kontent;
- shaxsiy telefon/messengerdan foydalanib shubhali savdo yoki trafik yig'ish;
- mutlaqo ma'nosiz yoki hamjamiyatga zararli xabar.

Muhim:
- Reklama borligi o'zi hide sababi emas. Ishonchli platformadagi qonuniy reklama mumkin.
- `signals` yordamchi belgi; kontekstni tushunib qaror bering.
- Link allowlistda bo'lmasa `hide` va `suspicious_link` tanlang.
- Noaniqlikda zarar aniq bo'lmasa `show`, ammo scam/link xavfida `hide`.

Faqat JSON:
{
  "items": [
    {
      "key": "post:123",
      "action": "show|hide",
      "reason": "clean|allowed_ad|spam|suspicious_link|profanity|harassment|hate|sexual|scam|illegal|unsafe|contact_solicitation|nonsense|other",
      "confidence": 0.0,
      "note": "qisqa va aniq sabab"
    }
  ]
}
PROMPT,
            ],
            [
                'role' => 'user',
                'content' => json_encode(['items' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ], 3200, 0.05, long: true);

        return collect($result['items'] ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['key'] ?? null))
            ->keyBy(fn ($row) => (string) $row['key']);
    }

    private function holdPendingChunk(Collection $chunk): void
    {
        if (! config('book_club_moderation.hold_pending', true)) {
            return;
        }

        foreach ($chunk as $item) {
            if (in_array($item['model']->ai_moderation_status, [null, 'pending', 'failed'], true)) {
                $item['model']->forceFill(['is_hidden_by_ai' => true])->save();
            }
        }
    }

    private function storeDecision(
        BookClub|BookClubComment $model,
        string $action,
        string $reason,
        string $note,
        float $confidence,
        array $policy,
    ): void {
        $model->forceFill([
            'is_hidden_by_ai' => $action === 'hide',
            'ai_moderation_status' => $action === 'hide' ? 'hidden' : 'clean',
            'ai_moderated_at' => now(),
            'ai_moderation_note' => Str::limit($reason.($note !== '' ? ': '.$note : ''), 255, ''),
            'ai_moderation_model' => self::MODEL,
            'ai_moderation_meta' => array_merge($policy, [
                'reason' => $reason,
                'confidence' => $confidence,
                'policy_version' => 1,
            ]),
        ])->save();
    }

    private function markFailed(BookClub|BookClubComment $model, array $policy, string $message): void
    {
        $model->forceFill([
            // Fail-open: AI javob bermasa ham zararsiz kontentni yashirmaymiz —
            // joriy ko'rinish saqlanadi (keyingi urinishda qayta baholanadi).
            'is_hidden_by_ai' => (bool) $model->is_hidden_by_ai,
            'ai_moderation_status' => 'failed',
            'ai_moderated_at' => now(),
            'ai_moderation_note' => $message,
            'ai_moderation_model' => self::MODEL,
            'ai_moderation_meta' => array_merge($policy, ['policy_version' => 1]),
        ])->save();
    }

    private function normalizeReason(string $reason): string
    {
        $reason = Str::lower(trim($reason));
        $allowed = [
            'clean', 'allowed_ad', 'spam', 'suspicious_link', 'profanity',
            'harassment', 'hate', 'sexual', 'scam', 'illegal', 'unsafe',
            'contact_solicitation', 'nonsense', 'other',
        ];

        return in_array($reason, $allowed, true) ? $reason : 'other';
    }
}
