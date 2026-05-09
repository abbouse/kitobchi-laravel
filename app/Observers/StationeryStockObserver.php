<?php

namespace App\Observers;

use App\Models\Stationery;
use App\Services\ProductStockAlertService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StationeryStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Stationery $product): void
    {
        app(ProductStockAlertService::class)->notifyForStationery($product);
    }
}
