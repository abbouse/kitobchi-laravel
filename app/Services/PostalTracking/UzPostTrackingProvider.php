<?php

namespace App\Services\PostalTracking;

use App\Contracts\PostalTrackingProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UzPostTrackingProvider implements PostalTrackingProvider
{
    public function __construct(
        private readonly PostalTrackingStatusCatalog $statusCatalog,
    ) {}

    public function code(): string
    {
        return 'uzpost';
    }

    public function name(): string
    {
        return 'UzPost';
    }

    public function isValidTrackingNumber(string $trackingNumber): bool
    {
        return preg_match(
            '/^[A-Z0-9-]{6,64}$/',
            Str::upper(trim($trackingNumber)),
        ) === 1;
    }

    public function track(string $trackingNumber): array
    {
        $trackingNumber = Str::upper(trim($trackingNumber));
        if (! $this->isValidTrackingNumber($trackingNumber)) {
            throw new RuntimeException('UzPost trek raqami formati noto‘g‘ri.');
        }

        $baseUrl = (string) config('services.uzpost.tracking_url');
        if ($baseUrl === '') {
            throw new RuntimeException('UzPost tracking URL sozlanmagan.');
        }

        $response = Http::acceptJson()
            ->withUserAgent('Kitobchi/1.0 (+https://kitobchi.com)')
            ->connectTimeout(max(1, (int) config('services.uzpost.connect_timeout', 3)))
            ->timeout(max(2, (int) config('services.uzpost.timeout', 7)))
            ->retry(2, 250, throw: false)
            ->get($baseUrl.'/'.rawurlencode($trackingNumber).'/');

        $payload = $response->json();
        if ($response->status() === 404
            || data_get($payload, 'code') === 'order_not_found'
            || (
                is_array($payload)
                && array_key_exists('header', $payload)
                && data_get($payload, 'header') === null
                && data_get($payload, 'shipox') === null
                && data_get($payload, 'gdeposilka') === null
            )) {
            throw new RuntimeException('UzPost jo‘natmasi topilmadi.');
        }

        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException('UzPost tracking vaqtincha javob bermadi.');
        }

        return $this->normalize($payload);
    }

    private function normalize(array $payload): array
    {
        $events = $this->events($payload);
        $headerStatus = $this->statusCatalog->normalize(
            $this->scalar(data_get($payload, 'header.data.status')),
        );

        if ($events === [] && $headerStatus === 'unknown') {
            throw new RuntimeException('UzPost javobida tracking statusi topilmadi.');
        }

        usort(
            $events,
            static fn (array $left, array $right): int => (
                strtotime((string) ($right['status_at'] ?? '')) ?: 0
            ) <=> (
                strtotime((string) ($left['status_at'] ?? '')) ?: 0
            ),
        );

        $destination = data_get($payload, 'header.data.locations.1');
        $recipientPostcode = $this->clean(data_get($destination, 'postcode'));
        if ($recipientPostcode) {
            foreach ($events as &$event) {
                if (($event['status_code'] ?? null) === 'ready_for_issue') {
                    $event['postal_index'] = $recipientPostcode;
                }
            }
            unset($event);
        }

        $latest = collect($events)->firstWhere('status_code', $headerStatus)
            ?? $events[0]
            ?? [
                'status_code' => $headerStatus,
                'labels' => $this->statusCatalog->labels($headerStatus),
                'status_at' => null,
                'location' => null,
                'postal_index' => $headerStatus === 'ready_for_issue'
                    ? $recipientPostcode
                    : null,
                'step' => $this->statusCatalog->step($headerStatus),
                'terminal' => $this->statusCatalog->isTerminal($headerStatus),
            ];

        $statusCode = $headerStatus !== 'unknown'
            ? $headerStatus
            : $this->statusCatalog->normalize($latest['status_code'] ?? null);
        $labels = $this->statusCatalog->labels($statusCode);

        return [
            'status_code' => $statusCode,
            'labels' => $labels,
            'status_at' => $latest['status_at'] ?? null,
            'location' => $this->clean($latest['location'] ?? null),
            'step' => $this->statusCatalog->step($statusCode),
            'terminal' => $this->statusCatalog->isTerminal($statusCode),
            'recipient_address' => $this->clean(data_get($destination, 'address')),
            'recipient_postcode' => $recipientPostcode,
            'events' => $events,
        ];
    }

    /**
     * UzPost bir endpointda mahalliy Shipox, xalqaro GdePosylka va eski
     * pochta formatlarini qaytarishi mumkin. Hammasini bitta eventga keltiramiz.
     *
     * @return array<int, array<string, mixed>>
     */
    private function events(array $payload): array
    {
        $events = [];

        foreach ((array) data_get($payload, 'data', []) as $event) {
            if (is_array($event)) {
                $events[] = $this->normalizeEvent($event);
            }
        }

        foreach ((array) data_get($payload, 'shipox.data.list', []) as $event) {
            if (is_array($event)) {
                $events[] = $this->normalizeEvent($event);
            }
        }

        foreach ((array) data_get($payload, 'gdeposilka.data.checkpoints', []) as $event) {
            if (! is_array($event)) {
                continue;
            }

            $events[] = $this->normalizeEvent([
                ...$event,
                'date' => $event['time'] ?? null,
                'location' => $event['location_translated'] ?? $event['location'] ?? null,
                'status_desc' => $event['status_name']
                    ?? $event['message']
                    ?? $event['description']
                    ?? null,
            ]);
        }

        if (array_is_list($payload)) {
            foreach ($payload as $row) {
                if (! is_array($row)) {
                    continue;
                }

                foreach ((array) data_get($row, 'OperationalMailitems.0.Events.TMailitemEventScanning', []) as $event) {
                    if (! is_array($event)) {
                        continue;
                    }

                    $type = (array) ($event['IPSEventType'] ?? []);
                    $events[] = $this->normalizeEvent([
                        'status' => $type['Code'] ?? $type['Name'] ?? null,
                        'status_uz' => $type['LocalName_uz'] ?? $type['Name'] ?? null,
                        'status_ru' => $type['LocalName_ru'] ?? $type['Name'] ?? null,
                        'status_eng' => $type['Name'] ?? null,
                        'date' => $event['LocalDateTime'] ?? null,
                        'location' => data_get($event, 'EventOffice.Name'),
                    ]);
                }
            }
        }

        $unique = [];
        foreach ($events as $event) {
            if (empty($event['status_code'])) {
                continue;
            }

            $key = implode('|', [
                (string) $event['status_code'],
                (string) ($event['status_at'] ?? ''),
                (string) ($event['location'] ?? ''),
            ]);
            $unique[$key] = $event;
        }

        return array_values($unique);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeEvent(array $event): array
    {
        $warehouse = (array) ($event['warehouse'] ?? []);
        $statusCode = $this->scalar($event['status'] ?? null)
            ?? $this->scalar($event['code'] ?? null)
            ?? $this->scalar($event['event_code'] ?? null)
            ?? $this->scalar($event['status_desc'] ?? null);
        $statusCode = $this->statusCatalog->normalize($statusCode);

        return [
            'status_code' => $statusCode,
            'labels' => $this->statusCatalog->labels($statusCode),
            'status_at' => $this->date(
                $event['date']
                    ?? $event['time']
                    ?? $event['created_at']
                    ?? null,
            ),
            'location' => $this->clean(
                $warehouse['public_name']
                    ?? $warehouse['name']
                    ?? $event['location']
                    ?? $event['location_translated']
                    ?? null,
            ),
            'step' => $this->statusCatalog->step($statusCode),
            'terminal' => $this->statusCatalog->isTerminal($statusCode),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function scalar(mixed $value): ?string
    {
        return is_scalar($value) ? $this->clean((string) $value) : null;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value !== '' ? $value : null;
    }
}
