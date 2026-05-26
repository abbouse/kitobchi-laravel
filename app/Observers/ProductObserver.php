<?php

namespace App\Observers;

use App\Jobs\SyncProductVectorJob;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\ProductVectorService;

class ProductObserver
{
    public function saved(Books|Stationery $product): void
    {
        /** @var ProductVectorService $service */
        $service = app(ProductVectorService::class);

        if (! $service->shouldQueueSync($product)) {
            return;
        }

        SyncProductVectorJob::dispatch(
            $product instanceof Books ? 'book' : 'stationery',
            (int) $product->id,
        );
    }
}
