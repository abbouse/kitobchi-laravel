<?php

namespace App\Services;

use App\Models\PostOffice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manzil uchun pochta indeksi — userdan so'ramasdan, o'zimiz aniqlaymiz.
 *
 * O'zpochta bo'limlari ro'yxati (rasmiy xarita API'si, ~1600 bo'lim, indeks + koordinata)
 * bazada saqlanadi; nuqtaga eng yaqin bo'lim indeksi olinadi. Bo'lim topilmasa
 * (bazada yo'q yoki juda uzoq) — Yandex javobidagi postal_code.
 */
class PostalCodeService
{
    public const SOURCE_URL = 'https://new.pochta.uz/api/v1/maps/new/post/offices/';

    /** Shundan uzoqdagi bo'lim indeksiga ishonmaymiz (km). */
    public const MAX_DISTANCE_KM = 30.0;

    private const CACHE_KEY = 'post_offices:all:v1';

    /** @return list<array{index:string, lat:float, lon:float}> */
    public function offices(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), fn () => PostOffice::query()
            ->get(['postal_index', 'lat', 'lon'])
            ->map(fn (PostOffice $o) => ['index' => (string) $o->postal_index, 'lat' => (float) $o->lat, 'lon' => (float) $o->lon])
            ->all());
    }

    /** Nuqta uchun indeks; topilmasa — $fallback (masalan Yandex postal_code). */
    public function codeFor(?float $lat, ?float $lon, ?string $fallback = null): ?string
    {
        if ($lat !== null && $lon !== null) {
            $nearest = self::nearest($this->offices(), $lat, $lon);
            if ($nearest !== null) {
                return $nearest['index'];
            }
        }
        $fallback = preg_replace('/\D/', '', (string) $fallback);

        return strlen($fallback) === 6 ? $fallback : null;
    }

    /**
     * Eng yaqin bo'lim (toza mantiq, test uchun statik).
     *
     * @param  list<array{index:string, lat:float, lon:float}>  $offices
     * @return array{index:string, lat:float, lon:float, km:float}|null
     */
    public static function nearest(array $offices, float $lat, float $lon, float $maxKm = self::MAX_DISTANCE_KM): ?array
    {
        $best = null;
        $bestKm = INF;
        // ~0.5° ≈ 55 km — avval tez tekshiruv, keyin aniq masofa
        foreach ($offices as $o) {
            if (abs($o['lat'] - $lat) > 0.5 || abs($o['lon'] - $lon) > 0.7) {
                continue;
            }
            $km = self::distanceKm($lat, $lon, $o['lat'], $o['lon']);
            if ($km < $bestKm) {
                $bestKm = $km;
                $best = $o;
            }
        }

        return $best !== null && $bestKm <= $maxKm ? $best + ['km' => round($bestKm, 3)] : null;
    }

    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }

    /**
     * O'zpochta javobidan bo'limlarni ajratib oladi. Javob tuzilishi o'zgarsa ham
     * ishlashi uchun maydon nomlari bir nechta variantda qidiriladi.
     *
     * @return list<array{postal_index:string, lat:float, lon:float, name:?string, address:?string}>
     */
    public static function parseOffices(mixed $json): array
    {
        $list = $json;
        if (is_array($json) && ! array_is_list($json)) {
            // O'zpochta: {"result": [{"index":"100000","lat":"41.31","lng":"69.28", ...}]}
            foreach (['result', 'data', 'results', 'offices', 'items', 'features'] as $key) {
                if (isset($json[$key]) && is_array($json[$key])) {
                    $list = $json[$key];
                    break;
                }
            }
        }
        if (! is_array($list) || ! array_is_list($list)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $props = isset($item['properties']) && is_array($item['properties']) ? $item['properties'] + $item : $item;

            $index = null;
            foreach (['index', 'post_index', 'postIndex', 'postal_code', 'postcode', 'zip', 'code'] as $k) {
                $v = preg_replace('/\D/', '', (string) ($props[$k] ?? ''));
                if (strlen($v) === 6) {
                    $index = $v;
                    break;
                }
            }
            [$lat, $lon] = self::coordinates($props);
            if ($index === null || $lat === null || $lon === null) {
                continue;
            }
            $key = $index.'|'.round($lat, 5).'|'.round($lon, 5);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $name = $props['name_uz'] ?? $props['name'] ?? $props['title'] ?? null;
            $address = $props['address'] ?? $props['street'] ?? null;
            $out[] = [
                'postal_index' => $index,
                'lat' => $lat,
                'lon' => $lon,
                'name' => is_string($name) ? mb_substr(trim($name), 0, 255) : null,
                'address' => is_string($address) ? mb_substr(trim($address), 0, 500) : null,
            ];
        }

        return $out;
    }

    /** @return array{0:?float, 1:?float} */
    private static function coordinates(array $p): array
    {
        $pairs = [
            [$p['lat'] ?? $p['latitude'] ?? null, $p['lon'] ?? $p['lng'] ?? $p['long'] ?? $p['longitude'] ?? null],
        ];
        foreach (['location', 'coords', 'coordinates', 'point', 'geometry'] as $k) {
            $c = $p[$k] ?? null;
            if (is_array($c) && isset($c['coordinates']) && is_array($c['coordinates'])) {
                $c = $c['coordinates'];
            }
            if (is_array($c)) {
                if (array_is_list($c) && count($c) >= 2) {
                    $pairs[] = [$c[0], $c[1]];
                    $pairs[] = [$c[1], $c[0]];
                } else {
                    $pairs[] = [$c['lat'] ?? $c['latitude'] ?? null, $c['lon'] ?? $c['lng'] ?? $c['longitude'] ?? null];
                }
            } elseif (is_string($c) && preg_match('/(-?\d+(?:\.\d+)?)[,\s]+(-?\d+(?:\.\d+)?)/', $c, $m)) {
                $pairs[] = [$m[1], $m[2]];
                $pairs[] = [$m[2], $m[1]];
            }
        }
        foreach ($pairs as [$lat, $lon]) {
            if (! is_numeric($lat) || ! is_numeric($lon)) {
                continue;
            }
            $lat = (float) $lat;
            $lon = (float) $lon;
            // O'zbekiston chegarasi — koordinatalar tartibini ham shu bilan aniqlaymiz
            if ($lat >= 37.0 && $lat <= 46.0 && $lon >= 55.5 && $lon <= 73.5) {
                return [$lat, $lon];
            }
        }

        return [null, null];
    }

    /** O'zpochta'dan yangilab olish. Qaytaradi: saqlangan bo'limlar soni (xato bo'lsa null). */
    public function sync(): ?int
    {
        try {
            $response = Http::timeout(40)->retry(2, 2000)
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'Kitobchi/1.0 (+https://kitobchi.com)'])
                ->get(self::SOURCE_URL);
            if (! $response->successful()) {
                Log::warning('UzPost offices sync failed', ['status' => $response->status()]);

                return null;
            }
            $offices = self::parseOffices($response->json());
        } catch (\Throwable $e) {
            Log::warning('UzPost offices sync error', ['error' => $e->getMessage()]);

            return null;
        }

        // Javob buzilgan bo'lsa eski ro'yxatni o'chirib yubormaymiz
        if (count($offices) < 200) {
            Log::warning('UzPost offices sync: too few offices parsed', ['count' => count($offices)]);

            return null;
        }

        DB::transaction(function () use ($offices) {
            PostOffice::query()->delete();
            $now = now();
            foreach (array_chunk($offices, 500) as $chunk) {
                PostOffice::insert(array_map(fn ($o) => $o + ['created_at' => $now, 'updated_at' => $now], $chunk));
            }
        });
        Cache::forget(self::CACHE_KEY);

        return count($offices);
    }
}
