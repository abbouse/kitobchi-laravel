<?php

namespace App\Observers;

use App\Models\Stationery;
use App\Services\ProductStockAlertService;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StationeryStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Stationery $product): void
    {
        app(ProductStockAlertService::class)->notifyForStationery($product);
        $this->emitWebhook($product);
    }

    private function emitWebhook(Stationery $product): void
    {
        $webhooks = app(WebhookService::class);
        if (! $webhooks->hasActiveWebhooks()) {
            return;
        }

        $payload = [
            'id' => $product->id,
            'type' => 'stationery',
            'name' => $product->name,
            'price' => (int) ($product->price ?? 0),
            'stock' => (int) ($product->stock ?? 0),
            'in_stock' => (int) ($product->stock ?? 0) > 0,
        ];

        if ($product->wasRecentlyCreated) {
            $webhooks->dispatch('product.created', $payload);

            return;
        }

        if ($product->wasChanged('stock')) {
            $webhooks->dispatch('product.stock_changed', $payload);
        }

        if ($product->wasChanged(['price', 'name', 'is_approved', 'is_hidden'])) {
            $webhooks->dispatch('product.updated', $payload);
        }
    }
}
