<?php

namespace App\Observers;

use App\Models\Books;
use App\Services\ProductStockAlertService;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Books $book): void
    {
        app(ProductStockAlertService::class)->notifyForBook($book);
        $this->emitWebhook($book);
    }

    private function emitWebhook(Books $book): void
    {
        $webhooks = app(WebhookService::class);
        if (! $webhooks->hasActiveWebhooks()) {
            return;
        }

        $payload = [
            'id' => $book->id,
            'type' => 'book',
            'name' => $book->name,
            'price' => (int) ($book->price ?? 0),
            'count' => (int) ($book->count ?? 0),
            'in_stock' => (int) ($book->count ?? 0) > 0,
        ];

        if ($book->wasRecentlyCreated) {
            $webhooks->dispatch('product.created', $payload);

            return;
        }

        if ($book->wasChanged('count')) {
            $webhooks->dispatch('product.stock_changed', $payload);
        }

        if ($book->wasChanged(['price', 'name', 'is_approved', 'is_hidden'])) {
            $webhooks->dispatch('product.updated', $payload);
        }
    }
}
