<?php

namespace App\Console\Commands;

use App\Services\CatalogParsers\BookUzParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBookUzCatalog extends Command
{
    protected $signature = 'parser:sync-book-uz
                            {--limit= : Nechta mahsulotgacha o‘qilsin}
                            {--url= : Faqat bitta mahsulot linkini sync qilish}';

    protected $description = 'book.uz katalogini parser cache jadvaliga curl orqali sync qiladi';

    public function handle(BookUzParserService $service): int
    {
        try {
            $result = $service->syncCatalog(
                $this->option('limit') !== null ? (int) $this->option('limit') : null,
                $this->option('url') ? (string) $this->option('url') : null,
            );

            $this->info("Sync tugadi. Requested: {$result['requested']}, success: {$result['synced']}, failed: {$result['failed']}");

            if (! empty($result['errors'])) {
                foreach (array_slice($result['errors'], 0, 10) as $error) {
                    $this->warn($error);
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('parser:sync-book-uz failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
