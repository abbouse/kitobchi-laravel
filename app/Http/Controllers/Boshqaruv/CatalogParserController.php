<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Services\CatalogParsers\ExternalCatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogParserController extends Controller
{
    public function __construct(
        private readonly ExternalCatalogSyncService $syncService,
    ) {}

    /**
     * Parser statistikasi.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'stats' => $this->syncService->getStats(),
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

        try {
            $report = $this->syncService->runSync($source, $limit, $withImages);

            return response()->json([
                'success' => true,
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
}
