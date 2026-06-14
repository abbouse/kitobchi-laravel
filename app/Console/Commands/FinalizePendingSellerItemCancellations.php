<?php

namespace App\Console\Commands;

use App\Services\SellerOrderCancellationService;
use Illuminate\Console\Command;

class FinalizePendingSellerItemCancellations extends Command
{
    protected $signature = 'seller-orders:finalize-pending-item-cancellations {--limit=100}';

    protected $description = '30 daqiqalik qaytarish muddati tugagan seller item cancel refundlarini yakunlaydi';

    public function handle(SellerOrderCancellationService $service): int
    {
        $processed = $service->finalizePendingItemCancellations((int) $this->option('limit'));

        $this->info("Finalized pending item cancellations: {$processed}");

        return self::SUCCESS;
    }
}
