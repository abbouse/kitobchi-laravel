<?php

namespace App\Observers;

use App\Models\StationeryVariant;
use App\Services\ProductStockAlertService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StationeryVariantStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(StationeryVariant $variant): void
    {
        app(ProductStockAlertService::class)->notifyForVariant($variant);
    }
}
