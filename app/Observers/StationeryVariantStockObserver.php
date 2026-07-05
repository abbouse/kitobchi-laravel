<?php

namespace App\Observers;

use App\Models\StationeryVariant;
use App\Services\ProductStockAlertService;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StationeryVariantStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(StationeryVariant $variant): void
    {
        app(ProductStockAlertService::class)->notifyForVariant($variant);

        if (! $variant->wasChanged('stock')) {
            return;
        }

        $webhooks = app(WebhookService::class);
        if (! $webhooks->hasActiveWebhooks()) {
            return;
        }

        $webhooks->dispatch('product.stock_changed', [
            'id' => (int) ($variant->stationery_id ?? 0),
            'type' => 'stationery',
            'variant_id' => $variant->id,
            'stock' => (int) ($variant->stock ?? 0),
            'in_stock' => (int) ($variant->stock ?? 0) > 0,
        ]);
    }
}
