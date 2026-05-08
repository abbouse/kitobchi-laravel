<?php

namespace App\Console\Commands;

use App\Services\BookClubCommentModerationService;
use Illuminate\Console\Command;

class ModerateBookClubComments extends Command
{
    protected $signature = 'openai:moderate-book-club-comments {--all=0} {--limit=0}';
    protected $description = 'Book Club kommentlarini OpenAI orqali haftalik moderatsiya qilish';

    public function handle(BookClubCommentModerationService $service): int
    {
        $all = (bool) $this->option('all');
        $limit = max(0, (int) $this->option('limit'));

        $processed = $all
            ? $service->moderateAll($limit)
            : $service->moderatePending($limit > 0 ? $limit : 600);

        $this->info("Moderatsiya qilingan kommentlar soni: {$processed}");

        return self::SUCCESS;
    }
}
