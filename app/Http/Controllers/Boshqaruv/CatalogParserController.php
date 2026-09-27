<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Services\CatalogParsers\ExternalCatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Services\CatalogParsers\BookUzStockSyncService;

class CatalogParserController extends Controller
{
    public function __construct(
        private readonly ExternalCatalogSyncService $syncService,
        private readonly BookUzStockSyncService $bookUzStockSync,
    ) {}

    /**
     * Parser statistikasi va fondagi jarayon holati.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'stats' => $this->syncService->getStats(),
            'progress' => \Illuminate\Support\Facades\Cache::get('catalog_parser_progress'),
            'enrich_progress' => \Illuminate\Support\Facades\Cache::get('catalog_enrich_description_progress'),
            'bookuz_stock_stats' => $this->bookUzStockSync->getStats(),
            'bookuz_stock_progress' => \Illuminate\Support\Facades\Cache::get(BookUzStockSyncService::CACHE_PROGRESS_KEY),
        ]);
    }

    /**
     * Parserni ishga tushirish (Qamar.uz va/yoki Book.uz).
     */
    public function run(Request $request): JsonResponse
    {
        $source = (string) $request->input('source', 'all');
        if (! in_array($source, ['qamar_uz', 'book_uz', 'all'], true)) {
            $source = 'all';
        }

        $limit = (int) $request->input('limit', 50);
        $withImages = (bool) $request->input('with_images', true);

        // Agar limit 0 (barcha kitoblar) bo'lsa yoki 100 dan ortiq bo'lsa:
        // Veb brauzer 60 soniyada timeout bermasligi uchun orqa fonda (CLI background) ishga tushiramiz!
        if ($limit === 0 || $limit > 100) {
            $artisan = base_path('artisan');
            $withImagesFlag = $withImages ? '--with-images' : '';
            $limitFlag = $limit > 0 ? "--limit={$limit}" : '--limit=0';
            $phpBinary = $this->getCliPhpBinary();
            $logFile = storage_path('logs/catalog-parser.log');

            $cmd = sprintf(
                '%s %s catalog:sync-external --source=%s %s %s >> %s 2>&1 &',
                escapeshellcmd($phpBinary),
                escapeshellarg($artisan),
                escapeshellarg($source),
                $limitFlag,
                $withImagesFlag,
                escapeshellarg($logFile)
            );

            @exec($cmd);

            $progressData = [
                'running' => true,
                'source' => $source,
                'total_scanned' => 0,
                'total_target' => 0,
                'editions_created' => 0,
                'isbn_enriched' => 0,
                'already_matched' => 0,
                'skipped_no_isbn' => 0,
                'failed' => 0,
                'last_title' => "Fonda ish boshlanmoqda...",
                'started_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            \Illuminate\Support\Facades\Cache::put('catalog_parser_progress', $progressData, 7200);

            return response()->json([
                'success' => true,
                'is_background' => true,
                'message' => "Barcha kitoblarni sinxronlash orqa fonda (background rejimida) ishga tushirildi! Veb-sahifa qotib qolmaydi. Jarayonni quyidagi ko'rsatkichlar orqali real vaqtda kuzatib turishingiz mumkin.",
                'stats' => $this->syncService->getStats(),
                'progress' => $progressData,
            ]);
        }

        try {
            $report = $this->syncService->runSync($source, $limit, $withImages);

            return response()->json([
                'success' => true,
                'is_background' => false,
                'message' => "Sinxronlash muvaffaqiyatli yakunlandi.",
                'report' => $report,
                'stats' => $this->syncService->getStats(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Xatolik yuz berdi: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mavjud kitoblarning kategoriyasini AI orqali aniqlash / qayta yangilash.
     */
    public function categorizeExisting(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $onlyUncategorized = (bool) $request->input('only_uncategorized', true);

        try {
            $report = $this->syncService->categorizeExistingEditions($limit, $onlyUncategorized);

            return response()->json([
                'success' => true,
                'message' => "AI klassifikatsiya yakunlandi: {$report['updated']} ta kitob yangilandi.",
                'report' => $report,
                'stats' => $this->syncService->getStats(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Xatolik yuz berdi: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tavsifi yo'q kitob nashrlariga AI orqali sekin tavsif yozib chiqish.
     */
    public function enrichDescriptions(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);

        // Agar limit 0 (barcha kitoblar) bo'lsa yoki 15 dan ortiq bo'lsa:
        // Orqa fonda (CLI background) ishga tushiramiz, brauzer qotib qolmaydi.
        if ($limit === 0 || $limit > 15) {
            $artisan = base_path('artisan');
            $limitFlag = $limit > 0 ? "--limit={$limit}" : '--limit=0';
            $phpBinary = $this->getCliPhpBinary();
            $logFile = storage_path('logs/catalog-enrich.log');

            $cmd = sprintf(
                '%s %s catalog:enrich-descriptions %s >> %s 2>&1 &',
                escapeshellcmd($phpBinary),
                escapeshellarg($artisan),
                $limitFlag,
                escapeshellarg($logFile)
            );

            @exec($cmd);

            $progressData = [
                'running' => true,
                'total' => 0,
                'processed' => 0,
                'updated' => 0,
                'failed' => 0,
                'last_title' => "Fonda AI tavsif yozish boshlanmoqda...",
                'updated_at' => now()->toIso8601String(),
            ];

            \Illuminate\Support\Facades\Cache::put('catalog_enrich_description_progress', $progressData, 7200);

            return response()->json([
                'success' => true,
                'is_background' => true,
                'message' => "Tavsifi yo'q kitoblarga AI orqali tavsif yozish orqa fonda (background rejimida) ishga tushirildi! Sayt qotmaydi, jarayonni real vaqtda kuzatib turishingiz mumkin.",
                'stats' => $this->syncService->getStats(),
                'enrich_progress' => $progressData,
            ]);
        }

        try {
            $report = $this->syncService->enrichMissingDescriptionsWithAi($limit);

            return response()->json([
                'success' => true,
                'is_background' => false,
                'message' => "AI tavsiflash yakunlandi: {$report['updated']} ta kitobga tavsif yozildi.",
                'report' => $report,
                'stats' => $this->syncService->getStats(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Xatolik yuz berdi: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seller 55 kitoblarini Book.uz saytidagi Qatortol (111) va Chorsu (777) filiallari qoldiqlari bilan sinxronlash.
     */
    public function syncBookUzStock(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $importNew = $request->boolean('import_new', true);
        $dryRun = (bool) $request->input('dry_run', false);

        if ($limit === 0 || $limit > 100) {
            $artisan = base_path('artisan');
            $limitFlag = $limit > 0 ? "--limit={$limit}" : '--limit=0';
            $skipNewFlag = ! $importNew ? '--skip-new' : '';
            $dryRunFlag = $dryRun ? '--dry-run' : '';
            $phpBinary = $this->getCliPhpBinary();
            $logFile = storage_path('logs/bookuz-stock-sync.log');

            $cmd = sprintf(
                '%s %s catalog:sync-bookuz-stock %s %s %s >> %s 2>&1 &',
                escapeshellcmd($phpBinary),
                escapeshellarg($artisan),
                $limitFlag,
                $skipNewFlag,
                $dryRunFlag,
                escapeshellarg($logFile)
            );

            @exec($cmd);

            $progressData = [
                'running' => true,
                'scanned' => 0,
                'total_target' => 0,
                'matched' => 0,
                'not_found' => 0,
                'in_stock' => 0,
                'zeroed' => 0,
                'stock_changed' => 0,
                'price_changed' => 0,
                'new_imported' => 0,
                'global_linked' => 0,
                'qatortol_stock_count' => 0,
                'chorsu_stock_count' => 0,
                'total_stock_count' => 0,
                'last_title' => 'Fonda Book.uz Qatortol va Chorsu filiallari qoldiqlarini sinxronlash boshlanmoqda...',
                'started_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            \Illuminate\Support\Facades\Cache::put(
                \App\Services\CatalogParsers\BookUzStockSyncService::CACHE_PROGRESS_KEY,
                $progressData,
                7200
            );

            return response()->json([
                'success' => true,
                'is_background' => true,
                'message' => "Book.uz (Qatortol va Chorsu filiallari) qoldiqlarini sinxronlash orqa fonda (background) ishga tushirildi! Sayt qotmaydi, jarayonni quyidagi ko'rsatkichlar orqali real vaqtda kuzatib turishingiz mumkin.",
                'stats' => $this->syncService->getStats(),
                'bookuz_stock_stats' => $this->bookUzStockSync->getStats(),
                'bookuz_stock_progress' => $progressData,
            ]);
        }

        try {
            $report = $this->bookUzStockSync->syncSeller55Stock($limit, $importNew, $dryRun);

            return response()->json([
                'success' => true,
                'is_background' => false,
                'message' => "Book.uz (Qatortol & Chorsu) bilan sinxronlash yakunlandi: {$report['stock_changed']} ta qoldiq, {$report['price_changed']} ta narx yangilandi, {$report['new_imported']} ta yangi kitob qo'shilib global kartaga ulandi.",
                'report' => $report,
                'stats' => $this->syncService->getStats(),
                'bookuz_stock_stats' => $this->bookUzStockSync->getStats(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Xatolik yuz berdi: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Veb-server muhitida (FPM/CGI/CLI) haqiqiy CLI PHP binarini aniqlaydi.
     */
    private function getCliPhpBinary(): string
    {
        $finder = new \Symfony\Component\Process\PhpExecutableFinder();
        $php = $finder->find(false);
        if ($php && ! str_contains($php, 'fpm')) {
            return $php;
        }

        foreach (['/opt/homebrew/bin/php', '/usr/local/bin/php', '/usr/bin/php', 'php'] as $candidate) {
            if ($candidate === 'php' || @is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}

