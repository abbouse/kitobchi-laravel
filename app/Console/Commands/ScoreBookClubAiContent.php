<?php

namespace App\Console\Commands;

use App\Services\BookClubAiScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScoreBookClubAiContent extends Command
{
    protected $signature = 'openai:score-book-club-content {--all=0} {--post-limit=150} {--comment-limit=240}';

    protected $description = 'Book Club post va izohlarini OpenAI orqali batch baholaydi';

    public function handle(BookClubAiScoringService $service): int
    {
        try {
            $counts = $service->scorePendingContent(
                full: (bool) $this->option('all'),
                postLimit: (int) $this->option('post-limit'),
                commentLimit: (int) $this->option('comment-limit'),
            );

            $this->info("AI baholash yakunlandi: {$counts['posts']} ta post, {$counts['comments']} ta izoh.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('openai:score-book-club-content', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
