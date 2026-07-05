<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\ApiWebhook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Hodisalarni obuna bo'lgan API mijozlarning webhook uchlariga tarqatadi.
 *
 * Ishlatish (masalan mahsulot observer'idan):
 *   app(WebhookService::class)->dispatch('product.stock_changed', [
 *       'id' => $book->id, 'type' => 'book', 'in_stock' => $book->count > 0,
 *   ]);
 */
class WebhookService
{
    public const EVENTS = [
        'product.created',
        'product.updated',
        'product.stock_changed',
        'seller.updated',
    ];

    /**
     * Hodisani mos webhooklarga (navbat orqali) yuboradi.
     *
     * @return int Nechta yetkazish rejalashtirilgani.
     */
    private const HAS_ACTIVE_CACHE_KEY = 'api_webhooks:has_active';

    public function dispatch(string $event, array $data, ?int $clientId = null): int
    {
        // Har bir mahsulot saqlanishida DB so'rovi bo'lmasligi uchun: obuna umuman
        // yo'q bo'lsa (odatiy holat) — darhol chiqamiz (natija 60s cache'lanadi).
        if (! $this->hasActiveWebhooks()) {
            return 0;
        }

        $query = ApiWebhook::query()->where('is_active', true);
        if ($clientId !== null) {
            $query->where('api_client_id', $clientId);
        }

        $scheduled = 0;

        foreach ($query->get() as $hook) {
            if (! $hook->subscribedTo($event)) {
                continue;
            }

            DeliverWebhook::dispatch($hook->id, $event, $data, (string) Str::uuid());
            $scheduled++;
        }

        return $scheduled;
    }

    /** Kamida bitta faol webhook bormi (60s cache). */
    public function hasActiveWebhooks(): bool
    {
        if (! Schema::hasTable('api_webhooks')) {
            return false;
        }

        return (bool) Cache::remember(
            self::HAS_ACTIVE_CACHE_KEY,
            now()->addSeconds(60),
            fn () => ApiWebhook::query()->where('is_active', true)->exists(),
        );
    }

    /** Webhooklar o'zgarganda (admin) chaqiriladi. */
    public static function flushCache(): void
    {
        Cache::forget(self::HAS_ACTIVE_CACHE_KEY);
    }
}
