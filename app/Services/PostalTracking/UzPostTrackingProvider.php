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
    public function code(): string
    {
        return 'uzpost';
    }

    public function name(): string
    {
        return 'UzPost';
    }

    public function track(string $trackingNumber): array
    {
        $trackingNumber = Str::upper(trim($trackingNumber));
        if (! preg_match('/^[A-Z0-9-]{6,64}$/', $trackingNumber)) {
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
        $headerStatus = $this->scalar(data_get($payload, 'header.data.status'));

        if ($events === [] && $headerStatus === null) {
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

        $latest = $events[0] ?? [
            'status_code' => $headerStatus,
            'labels' => [],
            'status_at' => null,
            'location' => null,
        ];

        $statusCode = $this->scalar($latest['status_code'] ?? null)
            ?? $headerStatus
            ?? 'unknown';
        $labels = $this->labels((array) ($latest['labels'] ?? []), $statusCode);
        $destination = data_get($payload, 'header.data.locations.1');

        return [
            'status_code' => Str::lower($statusCode),
            'labels' => $labels,
            'status_at' => $latest['status_at'] ?? null,
            'location' => $this->clean($latest['location'] ?? null),
            'step' => $this->stepFor($statusCode, $labels),
            'terminal' => $this->isTerminal($statusCode),
            'recipient_address' => $this->clean(data_get($destination, 'address')),
            'recipient_postcode' => $this->clean(data_get($destination, 'postcode')),
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

        return array_values(array_filter(
            $events,
            static fn (array $event): bool => ! empty($event['status_code']),
        ));
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
        $fallback = $this->scalar($event['status_desc'] ?? null)
            ?? $this->scalar($event['description'] ?? null)
            ?? $this->scalar($event['name'] ?? null)
            ?? $statusCode;

        return [
            'status_code' => $statusCode,
            'labels' => [
                'uz' => $this->scalar($event['status_uz'] ?? null)
                    ?? $this->scalar($event['comment_uz'] ?? null)
                    ?? $fallback,
                'ru' => $this->scalar($event['status_ru'] ?? null)
                    ?? $this->scalar($event['comment_ru'] ?? null)
                    ?? $fallback,
                'en' => $this->scalar($event['status_eng'] ?? null)
                    ?? $this->scalar($event['status_en'] ?? null)
                    ?? $this->scalar($event['comment_eng'] ?? null)
                    ?? $fallback,
            ],
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
        ];
    }

    /**
     * @param  array<string, mixed>  $labels
     * @return array{uz: string, ru: string, en: string, ja: string}
     */
    private function labels(array $labels, string $fallback): array
    {
        $uz = $this->clean($labels['uz'] ?? null) ?? $fallback;
        $ru = $this->clean($labels['ru'] ?? null) ?? $uz;
        $en = $this->clean($labels['en'] ?? null) ?? $uz;

        return [
            'uz' => $uz,
            'ru' => $ru,
            'en' => $en,
            'ja' => $en,
        ];
    }

    /**
     * @param  array{uz: string, ru: string, en: string, ja: string}  $labels
     */
    private function stepFor(string $statusCode, array $labels): string
    {
        $value = Str::lower($statusCode.' '.implode(' ', $labels));

        foreach ([
            'ready_for_issue',
            'out_for_delivery',
            'issued_to_recipient',
            'delivered',
            'returned',
            'return_to_sender',
            'olib ketishga tayyor',
            'qabul qiluvchiga berildi',
            'готов к выдаче',
            'выдан получателю',
        ] as $needle) {
            if (str_contains($value, $needle)) {
                return 'handoff';
            }
        }

        return 'in_transit';
    }

    private function isTerminal(string $statusCode): bool
    {
        $statusCode = Str::lower($statusCode);

        return in_array($statusCode, [
            'issued_to_recipient',
            'delivered',
            'returned',
            'returned_to_sender',
            'return_to_sender',
            'cancelled',
            'destroyed',
        ], true);
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
