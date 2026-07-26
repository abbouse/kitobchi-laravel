<?php

namespace App\Services;

use App\Contracts\PostalTrackingProvider;
use App\Models\OrderFulfillment;
use App\Services\PostalTracking\PostalOrderStatusTransitionService;
use App\Services\PostalTracking\UzPostTrackingProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PostalTrackingService
{
    /** @var array<string, PostalTrackingProvider> */
    private array $providers;

    public function __construct(
        UzPostTrackingProvider $uzPost,
        private readonly PostalOrderStatusTransitionService $transitionService,
    ) {
        $this->providers = [
            $uzPost->code() => $uzPost,
        ];
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function providerOptions(): array
    {
        return collect($this->providers)
            ->map(fn (PostalTrackingProvider $provider): array => [
                'code' => $provider->code(),
                'name' => $provider->name(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function providerCodes(): array
    {
        return array_keys($this->providers);
    }

    public function isValidTrackingNumber(string $providerCode, string $trackingNumber): bool
    {
        $provider = $this->providers[Str::lower(trim($providerCode))] ?? null;

        return $provider?->isValidTrackingNumber($trackingNumber) ?? false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sync(OrderFulfillment $fulfillment, bool $force = false): ?array
    {
        $trackingNumber = Str::upper(trim((string) $fulfillment->postal_tracking_number));
        $providerCode = Str::lower(trim((string) $fulfillment->postal_provider));
        if ($trackingNumber === '' || $providerCode === '') {
            return null;
        }

        $provider = $this->providers[$providerCode] ?? null;
        if (! $provider) {
            return null;
        }

        $meta = (array) ($fulfillment->meta ?? []);
        $cached = data_get($meta, 'postal_tracking');
        if (! $force
            && is_array($cached)
            && $this->matchesTrackingIdentity($cached, $providerCode, $trackingNumber)
            && $this->isFresh($cached, $providerCode)) {
            return $this->reconcileAndReturn($fulfillment, $cached);
        }

        try {
            $result = [
                ...$provider->track($trackingNumber),
                'provider_code' => $provider->code(),
                'provider_name' => $provider->name(),
                'tracking_number' => $trackingNumber,
                'synced_at' => now()->toIso8601String(),
                'last_attempt_at' => now()->toIso8601String(),
                'last_error' => null,
            ];

            data_set($meta, 'postal_tracking', $result);
            $this->saveMeta($fulfillment, $meta);

            return $this->reconcileAndReturn($fulfillment, $result);
        } catch (Throwable $error) {
            $trackingMeta = is_array($cached)
                && $this->matchesTrackingIdentity($cached, $providerCode, $trackingNumber)
                    ? $cached
                    : [];
            $trackingMeta['provider_code'] = $provider->code();
            $trackingMeta['provider_name'] = $provider->name();
            $trackingMeta['tracking_number'] = $trackingNumber;
            $trackingMeta['last_attempt_at'] = now()->toIso8601String();
            $trackingMeta['last_error'] = Str::limit($error->getMessage(), 250);
            data_set($meta, 'postal_tracking', $trackingMeta);
            $this->saveMeta($fulfillment, $meta);

            Log::warning('[Postal tracking] Sync failed', [
                'fulfillment_id' => $fulfillment->id,
                'order_id' => $fulfillment->order_id,
                'provider' => $providerCode,
                'tracking_number' => $trackingNumber,
                'error' => $error->getMessage(),
            ]);

            return $this->reconcileAndReturn($fulfillment, $trackingMeta);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function localizedLabel(array $payload, ?string $locale = null): string
    {
        $locale = Str::lower($locale ?: app()->getLocale());
        $locale = in_array($locale, ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';
        $labels = (array) ($payload['labels'] ?? []);

        return trim((string) (
            $labels[$locale]
                ?? $labels['uz']
                ?? $labels['en']
                ?? $payload['status_code']
                ?? ''
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function matchesTrackingIdentity(
        array $payload,
        string $providerCode,
        string $trackingNumber,
    ): bool {
        return Str::lower(trim((string) ($payload['provider_code'] ?? ''))) === $providerCode
            && Str::upper(trim((string) ($payload['tracking_number'] ?? ''))) === $trackingNumber;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isFresh(array $payload, string $providerCode): bool
    {
        $attemptedAt = $payload['last_attempt_at'] ?? $payload['synced_at'] ?? null;
        if (! is_string($attemptedAt) || $attemptedAt === '') {
            return false;
        }

        try {
            $minutes = max(1, (int) config(
                "services.{$providerCode}.sync_interval_minutes",
                10,
            ));

            return Carbon::parse($attemptedAt)->greaterThan(now()->subMinutes($minutes));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function publicPayload(array $payload): ?array
    {
        if (empty($payload['status_code'])) {
            return null;
        }

        unset($payload['last_error']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function reconcileAndReturn(
        OrderFulfillment $fulfillment,
        array $payload,
    ): ?array {
        $public = $this->publicPayload($payload);
        if ($public) {
            $this->transitionService->reconcile($fulfillment, $public);
        }

        return $public;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function saveMeta(OrderFulfillment $fulfillment, array $meta): void
    {
        $fulfillment->forceFill(['meta' => $meta])->saveQuietly();
    }
}
