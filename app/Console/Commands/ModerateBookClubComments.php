<?php

namespace App\Console\Commands;

use App\Services\BookClubContentModerationService;
use Illuminate\Console\Command;

class ModerateBookClubComments extends Command
{
    protected $signature = 'openai:moderate-book-club-comments {--all=0} {--limit=0} {--post-limit=0} {--comment-limit=0}';
    protected $description = 'Book Club post va izohlarini OpenAI orqali moderatsiya qilish';

    public function handle(BookClubContentModerationService $service): int
    {
        $all = (bool) $this->option('all');
        $limit = max(0, (int) $this->option('limit'));

        $result = $all
            ? $service->moderateAll($limit)
            : $service->moderatePending(
                (int) $this->option('post-limit') ?: null,
                (int) $this->option('comment-limit') ?: ($limit ?: null),
            );

        $this->info(sprintf(
            'Moderatsiya: %d post, %d izoh; %d ochildi, %d yashirildi, %d xato.',
            $result['posts'],
            $result['comments'],
            $result['shown'],
            $result['hidden'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
