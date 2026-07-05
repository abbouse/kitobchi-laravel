<?php

namespace App\Console\Commands;

use App\Services\SellerOrderCancellationService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class FinalizePendingSellerItemCancellations extends Command
{
    protected $signature = 'seller-orders:finalize-pending-item-cancellations {--limit=100}';

    protected $description = '30 daqiqalik qaytarish muddati tugagan seller item cancel refundlarini yakunlaydi';

    public function handle(SellerOrderCancellationService $service): int
    {
        try {
            $processed = $service->finalizePendingItemCancellations((int) $this->option('limit'));
        } catch (QueryException $e) {
            // Bu buyruq har daqiqada fon rejimida ishlaydi. Baza bir zumga javob
            // bermasa (masalan, backup oynasida ulanish uzilsa) — production.ERROR
            // bilan yiqilmaymiz. Ogohlantirish yozamiz, keyingi daqiqada qayta uriniladi.
            Log::warning('[FinalizePendingSellerItemCancellations] DB unavailable, skipping this run', [
                'error' => $e->getMessage(),
            ]);

            return self::SUCCESS;
        }

        $this->info("Finalized pending item cancellations: {$processed}");

        return self::SUCCESS;
    }
}
