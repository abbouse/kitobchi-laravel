<?php

namespace App\Console\Commands;

use App\Services\AuthorDirectoryService;
use Illuminate\Console\Command;

class BackfillBookAuthors extends Command
{
    protected $signature = 'authors:backfill-books {--limit=}';
    protected $description = 'Mavjud books yozuvlarini authors jadvali bilan bog‘laydi';

    public function handle(AuthorDirectoryService $authorDirectory): int
    {
        $limit = $this->option('limit');
        $result = $authorDirectory->backfillBooks($limit !== null ? max(1, (int) $limit) : null);

        $this->info("Backfill tugadi. Linked: {$result['linked']}, created_authors: {$result['createdAuthors']}");

        return self::SUCCESS;
    }
}
