<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Yandex Geocoder — server orqali, bir nechta kalit bilan.
 *
 * Ilovalarda kalit saqlanmaydi: mijoz, do'kon va boshqaruv manzilni shu
 * xizmatdan oladi. Kalitlar turli akkauntlardan (`services.yandex_geocoder.keys`)
 * — biri ishlamasa (kunlik limit tugagan, bloklangan, noto'g'ri) keyingisiga
 * o'tiladi va ishlamagan kalit bir muddat chetga qo'yiladi. Oxirgi ishlagan
 * kalit birinchi sinaladi — har so'rov o'lik kalitdan boshlanmasin.
 *
 * Natijalar keshlanadi: bir nuqta (≈1 m aniqlik) yoki bir qidiruv matni
 * qayta-qayta so'ralganda (xarita siljitilganda) limit sarflanmaydi.
 */
class YandexGeocoderService
{
    private const ENDPOINT = 'https://geocode-maps.yandex.ru/1.x/';

    private const PREFERRED_KEY = 'geocoder:yandex:preferred';

    private const REVERSE_TTL = 2592000; // 30 kun

    private const SEARCH_TTL = 604800;   // 7 kun

    private const NOT_FOUND_TTL = 86400;

    /**
     * Nuqta bo'yicha manzil.
     *
     * @return array<string,mixed>|null  null — manzil topilmadi yoki xizmat ishlamadi
     */
    public function reverse(float $lat, float $lon, string $lang = 'uz'): ?array
    {
        $lang = self::locale($lang);
        $cacheKey = sprintf('geocode:rev:%s:%.5f:%.5f', $lang, $lat, $lon);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached['found'] ? $cached['data'] : null;
        }

        $response = $this->request([
            'geocode' => sprintf('%.6F,%.6F', $lon, $lat),
            'lang' => $lang,
            'results' => 1,
        ]);

        if ($response === null) {
            return null; // xizmat ishlamadi — keshlanmaydi, keyingi so'rov yana urinadi
        }

        $member = Arr::first((array) data_get($response, 'response.GeoObjectCollection.featureMember', []));
        $data = $member ? self::normalize((array) ($member['GeoObject'] ?? [])) : null;

        Cache::put($cacheKey, ['found' => $data !== null, 'data' => $data], $data ? self::REVERSE_TTL : self::NOT_FOUND_TTL);

        return $data;
    }

    /**
     * Matn bo'yicha qidiruv (boshqaruv xaritasi).
     *
     * @return list<array<string,mixed>>|null  null — xizmat ishlamadi
     */
    public function search(string $query, string $lang = 'uz', int $limit = 5): ?array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
        if ($query === '') {
            return [];
        }

        $lang = self::locale($lang);
        $limit = max(1, min(10, $limit));
        $cacheKey = 'geocode:q:' . $lang . ':' . $limit . ':' . md5(mb_strtolower($query));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request([
            'geocode' => $query,
            'lang' => $lang,
            'results' => $limit,
        ]);

        if ($response === null) {
            return null;
        }

        $items = collect((array) data_get($response, 'response.GeoObjectCollection.featureMember', []))
            ->map(fn ($member) => self::normalize((array) ($member['GeoObject'] ?? [])))
            ->filter()
            ->values()
            ->all();

        Cache::put($cacheKey, $items, $items ? self::SEARCH_TTL : self::NOT_FOUND_TTL);

        return $items;
    }

    /** Sozlangan kalitlar soni (holat sahifasi uchun). */
    public function keyCount(): int
    {
        return count($this->keys());
    }

    /**
     * Kalitlarni navbat bilan sinaydi.
     *
     * @return array<string,mixed>|null
     */
    private function request(array $params): ?array
    {
        $keys = $this->keys();
        if ($keys === []) {
            Log::error('Yandex geocoder: kalit sozlanmagan (YANDEX_GEOCODER_API_KEY_1..)');

            return null;
        }

        foreach ($this->attemptOrder($keys) as $index) {
            $key = $keys[$index];

            try {
                $response = Http::timeout((float) config('services.yandex_geocoder.timeout', 5))
                    ->connectTimeout(3)
                    ->acceptJson()
                    ->get(self::ENDPOINT, $params + ['apikey' => $key, 'format' => 'json']);
            } catch (\Throwable $e) {
                // Tarmoq xatosi — kalit aybdor emas, lekin shu daqiqada boshqasini sinaymiz
                $this->coolDown($key, 60, $index, 'network: ' . $e->getMessage());

                continue;
            }

            $status = $response->status();

            if ($response->successful() && is_array($response->json())) {
                Cache::forever(self::PREFERRED_KEY, self::fingerprint($key));

                return $response->json();
            }

            if ($status === 400) {
                // So'rovning o'zi noto'g'ri — boshqa kalit ham yordam bermaydi
                Log::warning('Yandex geocoder: noto\'g\'ri so\'rov', ['params' => Arr::except($params, ['apikey'])]);

                return null;
            }

            $this->coolDown($key, $this->cooldownSeconds($status), $index, "http {$status}");
        }

        Log::error('Yandex geocoder: birorta kalit ishlamadi', ['keys' => count($keys)]);

        return null;
    }

    /**
     * Sinash tartibi: oxirgi ishlagan kalit, keyin qolganlari; chetga
     * qo'yilganlar oxirida emas — umuman sinalmaydi. Hammasi chetda bo'lsa,
     * birinchisi baribir sinaladi (limit yangilangan bo'lishi mumkin).
     *
     * @param  list<string>  $keys
     * @return list<int>
     */
    private function attemptOrder(array $keys): array
    {
        $preferred = Cache::get(self::PREFERRED_KEY);
        $order = array_keys($keys);

        usort($order, fn (int $a, int $b) => [
            self::fingerprint($keys[$a]) === $preferred ? 0 : 1, $a,
        ] <=> [
            self::fingerprint($keys[$b]) === $preferred ? 0 : 1, $b,
        ]);

        $alive = array_values(array_filter($order, fn (int $i) => ! Cache::has(self::cooldownKey($keys[$i]))));

        return $alive !== [] ? $alive : [$order[0]];
    }

    /**
     * 403 — odatda kunlik limit tugagan yoki kalit bloklangan: limit Moskva
     * vaqti bilan yarim tunda yangilanadi. 401 — kalit noto'g'ri. 429 — juda
     * tez-tez so'rov. 5xx — Yandex tomonida vaqtinchalik nosozlik.
     */
    private function cooldownSeconds(int $status): int
    {
        return match (true) {
            $status === 403 => max(600, (int) now('Europe/Moscow')->diffInSeconds(now('Europe/Moscow')->addDay()->startOfDay(), true)),
            $status === 401 => 86400,
            $status === 429 => 600,
            default => 120,
        };
    }

    private function coolDown(string $key, int $seconds, int $index, string $reason): void
    {
        Cache::put(self::cooldownKey($key), true, $seconds);
        if (Cache::get(self::PREFERRED_KEY) === self::fingerprint($key)) {
            Cache::forget(self::PREFERRED_KEY);
        }

        Log::warning('Yandex geocoder: kalit chetga qo\'yildi', [
            'key' => $index + 1, // YANDEX_GEOCODER_API_KEY_{n} — kalitning o'zi logga yozilmaydi
            'seconds' => $seconds,
            'reason' => $reason,
        ]);
    }

    /** @return list<string> */
    private function keys(): array
    {
        return array_values(array_filter((array) config('services.yandex_geocoder.keys', []), fn ($k) => is_string($k) && $k !== ''));
    }

    private static function cooldownKey(string $key): string
    {
        return 'geocoder:yandex:cooldown:' . self::fingerprint($key);
    }

    private static function fingerprint(string $key): string
    {
        return substr(hash('sha256', $key), 0, 16);
    }

    private static function locale(string $lang): string
    {
        return match (strtolower(substr($lang, 0, 2))) {
            'uz' => 'uz_UZ',
            'en' => 'en_US',
            default => 'ru_RU',
        };
    }

    /**
     * Yandex GeoObject → ilovalar uchun sodda tuzilma.
     *
     * @return array<string,mixed>|null
     */
    private static function normalize(array $geo): ?array
    {
        $pos = explode(' ', trim((string) data_get($geo, 'Point.pos', '')));
        if (count($pos) !== 2) {
            return null;
        }

        $meta = (array) data_get($geo, 'metaDataProperty.GeocoderMetaData', []);
        $address = (array) ($meta['Address'] ?? []);
        $components = collect((array) ($address['Components'] ?? []))
            ->map(fn ($c) => ['kind' => (string) ($c['kind'] ?? ''), 'name' => (string) ($c['name'] ?? '')])
            ->filter(fn ($c) => $c['name'] !== '')
            ->values();

        $country = (array) data_get($meta, 'AddressDetails.Country', []);
        $adminArea = (array) ($country['AdministrativeArea'] ?? []);
        $subAdmin = (array) ($adminArea['SubAdministrativeArea'] ?? []);
        $locality = (array) ($subAdmin['Locality'] ?? $adminArea['Locality'] ?? []);

        // Eng ichki mahalla/tuman (DependentLocality ichma-ich keladi)
        $dependent = $locality['DependentLocality'] ?? null;
        while (is_array($dependent) && isset($dependent['DependentLocality'])) {
            $dependent = $dependent['DependentLocality'];
        }

        $first = fn (string $kind) => $components->firstWhere('kind', $kind)['name'] ?? null;
        $last = fn (string $kind) => $components->where('kind', $kind)->last()['name'] ?? null;
        $formatted = (string) ($address['formatted'] ?? $meta['text'] ?? '');

        return [
            'formatted' => $formatted,
            'name' => (string) ($geo['name'] ?? ''),
            'description' => (string) ($geo['description'] ?? ''),
            'kind' => (string) ($meta['kind'] ?? ''),
            'precision' => (string) ($meta['precision'] ?? ''),
            'country_code' => ($address['country_code'] ?? $country['CountryNameCode'] ?? null) ?: null,
            'country' => ($country['CountryName'] ?? $first('country')) ?: null,
            'administrative_area' => ($adminArea['AdministrativeAreaName'] ?? null) ?: null,
            'sub_administrative_area' => ($subAdmin['SubAdministrativeAreaName'] ?? null) ?: null,
            'locality' => ($locality['LocalityName'] ?? $first('locality')) ?: null,
            'dependent_locality' => (is_array($dependent) ? ($dependent['DependentLocalityName'] ?? null) : null) ?: null,
            'province' => $first('province'),
            'area' => $first('area'),
            'district' => $last('district'),
            'street' => $first('street'),
            'house' => $first('house'),
            'components' => $components->all(),
            'lat' => (float) $pos[1],
            'lon' => (float) $pos[0],
        ];
    }
}
