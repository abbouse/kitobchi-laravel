<?php

namespace App\Console\Commands;

use App\Services\CatalogParsers\ExternalCatalogSyncService;
use Illuminate\Console\Command;

class CatalogSyncExternalParsers extends Command
{
    protected $signature = 'catalog:sync-external
                            {--source=all : Manba: all, qamar_uz, yoki book_uz}
                            {--limit=50 : Kitoblar soni (0 = barchasi)}
                            {--with-images : Muqova rasmlarini yuklab olish}
                            {--categorize-existing : Mavjud kitob nashrlarining kategoriyalarini AI yordamida aniqlash/yangilash}';

    protected $description = "Qamar.uz va Book.uz saytlaridan ISBN'li kitoblarni global katalogga sinxronlash va AI orqali kategoriyalarini aniqlash";

    public function handle(ExternalCatalogSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');

        if ($this->option('categorize-existing')) {
            $this->info("=== Mavjud kitoblarni AI orqali kategoriyalash ===");
            $this->info("Limit: {$limit}");

            $report = $syncService->categorizeExistingEditions($limit, true, function ($message) {
                $this->line("  > {$message}");
            });

            $this->newLine();
            $this->info("=== Natijalar ===");
            $this->table(
                ['Ko\'rsatkich', 'Soni'],
                [
                    ['Ko\'rib chiqildi', $report['total_scanned']],
                    ['Kategoriyasi yangilandi', $report['updated']],
                    ['Xatoliklar', $report['failed']],
                ]
            );

            return self::SUCCESS;
        }

        $source = (string) $this->option('source');
        $withImages = (bool) $this->option('with-images');

        $this->info("=== External Catalog Parser ===");
        $this->info("Manba: {$source} | Limit: {$limit} | Rasmlar: " . ($withImages ? 'Ha' : 'Yo\'q'));

        $report = $syncService->runSync($source, $limit, $withImages, function ($message) {
            $this->line("  > {$message}");
        });

        $this->newLine();
        $this->info("=== Natijalar ===");
        $this->table(
            ['Ko\'rsatkich', 'Soni'],
            [
                ['Ko\'rib chiqildi', $report['total_scanned']],
                ['Yangi ochilgan kartalar', $report['editions_created']],
                ['ISBN to\'ldirilgan/boyitilgan', $report['isbn_enriched']],
                ['Allaqachon mavjud', $report['already_matched']],
                ['ISBN bo\'lmaganlar (o\'tkazildi)', $report['skipped_no_isbn']],
                ['Xatoliklar', $report['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
