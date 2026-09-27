<?php

namespace App\Console\Commands;

use App\Services\CatalogParsers\ExternalCatalogSyncService;
use Illuminate\Console\Command;

class CatalogEnrichDescriptions extends Command
{
    protected $signature = 'catalog:enrich-descriptions
                            {--limit=50 : Kitoblar soni (0 = barchasi)}';

    protected $description = "Tavsifi yo'q kitob nashrlariga AI orqali avtomatik ravishda chiroyli va mazmunli tavsif yozib chiqish";

    public function handle(ExternalCatalogSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("=== Kitoblar tavsifini AI bilan to'ldirish ===");
        $this->info("Limit: " . ($limit > 0 ? $limit : 'Barchasi'));

        $report = $syncService->enrichMissingDescriptionsWithAi($limit, function ($message) {
            $this->line("  > {$message}");
        });

        $this->newLine();
        $this->info("=== Natijalar ===");
        $this->table(
            ['Ko\'rsatkich', 'Soni'],
            [
                ['Ko\'rib chiqildi', $report['total']],
                ['Tavsif yozildi va saqlandi', $report['updated']],
                ['Xatoliklar', $report['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
