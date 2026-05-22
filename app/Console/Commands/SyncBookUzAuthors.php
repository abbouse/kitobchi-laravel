<?php

namespace App\Console\Commands;

use App\Services\AuthorDirectoryService;
use Illuminate\Console\Command;

class SyncBookUzAuthors extends Command
{
    protected $signature = 'authors:sync-book-uz {--limit=24000}';
    protected $description = 'Book.uz mualliflar katalogini ichki authors jadvaliga sync qiladi';

    public function handle(AuthorDirectoryService $authorDirectory): int
    {
        $limit = max(1, min((int) $this->option('limit'), 24000));
        $result = $authorDirectory->syncBookUzAuthors($limit);

        $this->info("Sync tugadi. Synced: {$result['synced']}, created: {$result['created']}, updated: {$result['updated']}");

        return self::SUCCESS;
    }
}
