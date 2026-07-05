<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApiWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bitta webhook yetkazishi. 2xx qaytmasa — oshib boruvchi kechikish bilan
 * qayta uriniladi (backoff). Har yetkazish HMAC-SHA256 bilan imzolanadi.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Qayta urinishlar orasidagi kechikish (soniya): 1m, 5m, 30m, 2s. */
    public array $backoff = [60, 300, 1800, 7200];

    public function __construct(
        public int $webhookId,
        public string $event,
        public array $data,
        public string $deliveryId,
    ) {}

    public function handle(): void
    {
        $hook = ApiWebhook::find($this->webhookId);
        if (! $hook || ! $hook->is_active) {
            return;
        }

        $body = json_encode([
            'event' => $this->event,
            'sent_at' => now()->toIso8601String(),
            'data' => $this->data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $signature = 'sha256='.hash_hmac('sha256', (string) $body, (string) $hook->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Kitobchi-Event' => $this->event,
                    'X-Kitobchi-Delivery' => $this->deliveryId,
                    'X-Kitobchi-Signature' => $signature,
                ])
                ->withBody((string) $body, 'application/json')
                ->post($hook->url);

            if ($response->successful()) {
                $hook->forceFill([
                    'last_delivered_at' => now(),
                    'failure_count' => 0,
                ])->save();

                return;
            }

            throw new \RuntimeException('Webhook non-2xx: '.$response->status());
        } catch (\Throwable $e) {
            $hook->increment('failure_count');

            Log::warning('[Webhook] delivery failed', [
                'webhook_id' => $hook->id,
                'event' => $this->event,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // queue backoff bo'yicha qayta uradi
        }
    }
}
