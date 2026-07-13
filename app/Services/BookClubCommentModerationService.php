<?php

namespace App\Services;

use App\Models\BookClubComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookClubCommentModerationService
{
    private const BATCH_SIZE = 60;
    private const OPENAI_MODEL = 'gpt-4o-mini';

    public function __construct(
        private readonly OpenAIService $openAIService,
    ) {}

    public function moderatePending(int $limit = 600): int
    {
        $comments = BookClubComment::query()
            ->with(['post:id,is_deleted', 'user:id,name,lastname'])
            ->whereHas('post', fn ($query) => $query->where('is_deleted', false))
            ->where(function ($query) {
                $query->whereNull('ai_moderation_status')
                    ->orWhere('ai_moderation_status', 'pending')
                    ->orWhere('ai_moderation_status', 'failed');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $this->processCollection($comments);
    }

    public function moderateAll(int $limit = 0): int
    {
        $query = BookClubComment::query()
            ->with(['post:id,is_deleted', 'user:id,name,lastname'])
            ->whereHas('post', fn ($builder) => $builder->where('is_deleted', false))
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $this->processCollection($query->get());
    }

    private function processCollection(Collection $comments): int
    {
        $processed = 0;

        foreach ($comments->chunk(self::BATCH_SIZE) as $chunk) {
            $this->moderateChunk($chunk);
            $processed += $chunk->count();
        }

        return $processed;
    }

    private function moderateChunk(Collection $comments): void
    {
        if ($comments->isEmpty()) {
            return;
        }

        $payload = $comments->map(fn (BookClubComment $comment) => [
            'id' => $comment->id,
            'text' => trim((string) $comment->content),
            'author' => trim(($comment->user?->name ?? '').' '.($comment->user?->lastname ?? '')),
        ])->values()->toArray();

        $result = $this->openAIService->askJsonWithMessages([
            [
                'role' => 'system',
                'content' => <<<'PROMPT'
Siz marketplace community moderatori sifatida faqat Book Club kommentlarini tekshirasiz.

Vazifa:
- Har bir comment uchun `show` yoki `hide` qarorini bering.
- Faqat aniq qoida buzilgan commentni yashiring.
- Reklama bo'yicha konservativ bo'ling: oddiy shaxsiy tavsiya yoki brend nomini tilga olish mumkin.
- Ammo tashqi savdo, trafik yig'ish, aloqa ma'lumotlari bilan chaqiriq, narx/aksiya bilan sotuvga undash, Telegram/Instagram/WhatsApp/telefon/link orqali olib chiqish, takroriy spam, noqonuniy yoki firibgarlik takliflari yashiriladi.
- So'kinish, haqorat, kamsitish, zo'ravon tahdid, pornografik/ochiq jinsiy matn, noqonuniy faoliyat targ'ibi ham yashiriladi.

Qoidalar:
- `show`: oddiy fikr, tajriba, savol, tavsiya, mulohaza.
- `hide`: profanity, harassment, hate, sexual, spam, advertising, contact_solicitation, scam, illegal, unsafe.

Faqat JSON qaytaring:
{
  "comments": [
    {
      "id": 123,
      "action": "show|hide",
      "reason": "clean|profanity|harassment|hate|sexual|spam|advertising|contact_solicitation|scam|illegal|unsafe|other",
      "note": "qisqa izoh"
    }
  ]
}
PROMPT,
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'task' => 'book_club_comment_moderation',
                    'comments' => $payload,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ], 2500, 0.1, long: true);

        $mapped = collect($result['comments'] ?? [])
            ->filter(fn ($item) => is_array($item) && !empty($item['id']))
            ->keyBy(fn ($item) => (int) $item['id']);

        foreach ($comments as $comment) {
            $decision = $mapped->get((int) $comment->id);

            if (!$decision) {
                $comment->forceFill([
                    'ai_moderation_status' => 'failed',
                    'ai_moderated_at' => now(),
                    'ai_moderation_note' => 'AI javobi topilmadi',
                    'ai_moderation_model' => self::OPENAI_MODEL,
                ])->save();
                continue;
            }

            $action = ($decision['action'] ?? 'show') === 'hide' ? 'hide' : 'show';
            $reason = Str::lower((string) ($decision['reason'] ?? 'clean'));
            $note = (string) ($decision['note'] ?? '');
            $moderationNote = Str::limit(
                $reason.($note !== '' ? ': '.$note : ''),
                255,
                ''
            );

            $comment->forceFill([
                'is_hidden_by_ai' => $action === 'hide',
                'ai_moderation_status' => $action === 'hide' ? 'hidden' : 'clean',
                'ai_moderated_at' => now(),
                'ai_moderation_note' => $moderationNote,
                'ai_moderation_model' => self::OPENAI_MODEL,
            ])->save();
        }

        Log::info('BookClub comments moderated by AI', [
            'count' => $comments->count(),
            'hidden' => $comments->where('is_hidden_by_ai', true)->count(),
        ]);
    }
}
