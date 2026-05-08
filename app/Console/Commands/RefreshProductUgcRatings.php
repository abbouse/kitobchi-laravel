<?php

namespace App\Console\Commands;

use App\Services\ProductUgcRatingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshProductUgcRatings extends Command
{
    protected $signature = 'products:refresh-ugc-ratings';

    protected $description = 'AI baholangan product izohlaridan mahsulot UGC reytingini qayta hisoblaydi';

    public function handle(ProductUgcRatingService $service): int
    {
        try {
            $service->refreshAll();
            $this->info('Mahsulot UGC reytinglari yangilandi.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('products:refresh-ugc-ratings', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
