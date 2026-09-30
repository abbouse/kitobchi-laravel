<?php

namespace App\Services\Catalog;

use App\Support\Isbn;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookCoverResolverService
{
    private const BOOKUZ_API = 'https://backend.book.uz/api/v1';

    private const BROKEN_CLOUDINARY_SUBSTRING = 'dd9xb0bqw';

    private const KNOWN_BAD_PATTERNS = [
        'dd9xb0bqw',
        '1790422172657',
        'Screenshot_2026_09_26_072402',
    ];

    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Kitob uchun mos va haqiqiy muqova rasmini topish va lokal saqlash:
     * 1. Book.uz kesh serveri (agar avvalgi asl Cloudinary havolasi mavjud bo'lsa)
     * 2. Book.uz API (ISBN yoki kitob nomi va uning variantlari orqali)
     * 3. Asaxiy.uz (Book.uz da bo'lmagan o'zbek kitoblari uchun)
     * 4. Google Books (ISBN bo'yicha)
     *
     * @return string|null Saqlangan lokal rasm yo'li (masalan: books/fixed_ed_123_abc.webp) yoki ishlaydigan tashqi havola
     */
    /**
     * Kitob uchun mos va haqiqiy muqova rasmini topish va lokal saqlash:
     * Har bir manbani ketma-ket sinab ko'radi (Book.uz kesh -> Book.uz API -> Asaxiy -> Google Books).
     * Faqat diskka haqiqatdan to'liq yuklab olingan rasmnigina tasdiqlaydi.
     *
     * @return string|null Saqlangan lokal rasm yo'li (masalan: books/fixed_ed_123_abc.webp)
     */
    public function resolveAndStore(string $title, ?string $rawIsbn = null, mixed $rawStoredImage = null, string $prefix = 'cover'): ?string
    {
        // 1. Book.uz original kesh (agar bor bo'lsa)
        $cachedUrl = $this->resolveFromOriginalCloudinary($rawStoredImage);
        if ($cachedUrl) {
            $path = $this->downloadToLocal($cachedUrl, $prefix);
            if ($path) {
                return $path;
            }
        }

        // 2. ISBN bo'yicha Book.uz API
        $cleanIsbn = $rawIsbn ? Isbn::clean($rawIsbn) : null;
        if ($cleanIsbn) {
            $url = $this->searchBookUzByIsbn($cleanIsbn);
            if ($url) {
                $path = $this->downloadToLocal($url, $prefix);
                if ($path) {
                    return $path;
                }
            }
        }

        // 3. Kitob nomi bo'yicha Book.uz API
        if (mb_strlen(trim($title)) >= 2) {
            $url = $this->searchBookUzByTitle($title);
            if ($url) {
                $path = $this->downloadToLocal($url, $prefix);
                if ($path) {
                    return $path;
                }
            }
        }

        // 4. Asaxiy.uz qidiruvi (Book.uz da bo'lmagan yoki yuklab bo'lmagan o'zbek kitoblari uchun)
        if (mb_strlen(trim($title)) >= 3) {
            $url = $this->searchAsaxiy($title);
            if ($url) {
                $path = $this->downloadToLocal($url, $prefix);
                if ($path) {
                    return $path;
                }
            }
        }

        // 5. Google Books API orqali qidiruv (ISBN bo'yicha)
        if ($cleanIsbn) {
            $url = $this->searchGoogleBooks($cleanIsbn);
            if ($url) {
                $path = $this->downloadToLocal($url, $prefix);
                if ($path) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Avvalgi asl Cloudinary havolasidan kesh manzilini aniqlash.
     */
    private function resolveFromOriginalCloudinary(mixed $rawStoredImage): ?string
    {
        $originalCloudinaryUrl = $this->extractOriginalCloudinaryUrl($rawStoredImage);
        if ($originalCloudinaryUrl) {
            $cachedProxyUrl = 'https://book.uz/_next/image?url='.urlencode($originalCloudinaryUrl).'&w=640&q=75';
            if ($this->verifyUrlWorks($cachedProxyUrl)) {
                return $cachedProxyUrl;
            }
        }

        return null;
    }

    /**
     * Tashqi manbalardan (Book.uz, Asaxiy, Google Books) haqiqiy rasm manzilini aniqlash.
     */
    public function resolveRemoteCover(string $title, ?string $rawIsbn = null, mixed $rawStoredImage = null): ?string
    {
        // 1. Agar kitobning avvalgi asl Cloudinary rasmi saqlanib qolgan bo'lsa
        $cachedProxyUrl = $this->resolveFromOriginalCloudinary($rawStoredImage);
        if ($cachedProxyUrl) {
            return $cachedProxyUrl;
        }

        // 2. ISBN bo'yicha Book.uz API qidiruvi
        $cleanIsbn = $rawIsbn ? Isbn::clean($rawIsbn) : null;
        if ($cleanIsbn) {
            $url = $this->searchBookUzByIsbn($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        // 3. Kitob nomi bo'yicha Book.uz API qidiruvi
        if (mb_strlen(trim($title)) >= 2) {
            $url = $this->searchBookUzByTitle($title);
            if ($url) {
                return $url;
            }
        }

        // 4. Asaxiy.uz qidiruvi (Book.uz da bo'lmagan yangi yoki muqobil kitoblar uchun)
        if (mb_strlen(trim($title)) >= 3) {
            $url = $this->searchAsaxiy($title);
            if ($url) {
                return $url;
            }
        }

        // 5. Google Books API orqali qidiruv (ISBN bo'yicha)
        if ($cleanIsbn) {
            $url = $this->searchGoogleBooks($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Tashqi rasmni yuklab olib, o'zimizning storage/books/... ga saqlash.
     * Faqat to'liq yozilgan (>= 1000 bayt) rasmlar saqlanadi.
     */
    public function downloadToLocal(string $url, string $prefix = 'cover'): ?string
    {
        try {
            $downloadUrl = $url;
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ];

            if (str_contains($url, 'book.uz')) {
                $headers['Referer'] = 'https://book.uz/';
            } elseif (str_contains($url, 'asaxiy.uz')) {
                $headers['Referer'] = 'https://asaxiy.uz/';
            }

            if (str_contains($url, self::BROKEN_CLOUDINARY_SUBSTRING) && ! str_contains($url, 'book.uz/_next/image')) {
                $downloadUrl = 'https://book.uz/_next/image?url='.urlencode($url).'&w=640&q=75';
                $headers['Referer'] = 'https://book.uz/';
            }

            $res = $this->http->get($downloadUrl, [
                'headers' => $headers,
            ]);

            if ($res->getStatusCode() !== 200) {
                return null;
            }

            $body = (string) $res->getBody();
            if (strlen($body) < 1000) {
                return null;
            }

            $extension = 'webp';
            $contentType = $res->getHeaderLine('content-type');
            if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
                $extension = 'jpg';
            } elseif (str_contains($contentType, 'png')) {
                $extension = 'png';
            }

            $filename = 'books/fixed_'.$prefix.'_'.Str::random(8).'.'.$extension;
            Storage::disk('public')->put($filename, $body);

            if (Storage::disk('public')->exists($filename) && Storage::disk('public')->size($filename) >= 1000) {
                return $filename;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("[book_cover_resolver] Download failed for {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Book.uz API dan ISBN orqali qidirish.
     */
    private function searchBookUzByIsbn(string $isbn): ?string
    {
        try {
            $response = $this->http->get(self::BOOKUZ_API.'/products', [
                'query' => [
                    'keyword' => $isbn,
                    'limit' => 3,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            $products = $data['data']['products'] ?? [];

            foreach ($products as $p) {
                $img = $p['image'] ?? null;
                if (! is_string($img) || blank($img)) {
                    continue;
                }

                return $this->formatRemoteImage($img);
            }
        } catch (\Throwable $e) {
            Log::debug("[book_cover_resolver] Book.uz ISBN search error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Book.uz API dan kitob nomi va uning variantlari orqali qidirish.
     * Apostroflarni ajratib yubormasdan to'g'ri qidiruv so'zlarini tuzadi.
     */
    private function searchBookUzByTitle(string $expectedTitle): ?string
    {
        $queries = [];
        $rawTrimmed = trim($expectedTitle);
        if ($rawTrimmed !== '') {
            $queries[] = $rawTrimmed;
        }

        $norm = $this->normalizeApostrophe($rawTrimmed);
        if ($norm !== $rawTrimmed) {
            $queries[] = $norm;
        }

        $clean = $this->cleanTitle($expectedTitle);
        if ($clean !== '') {
            $queries[] = $clean;
        }

        // Subtitle split (masalan: "O'zbekiston tavalludi. Ilk SSSR davrida..." -> "O'zbekiston tavalludi")
        $parts = preg_split('/[:.\-—]/u', $expectedTitle);
        if (count($parts) > 1 && mb_strlen(trim($parts[0])) >= 3) {
            $queries[] = $this->cleanTitle($parts[0]);
        }

        $cyr = $this->latinToCyrillic($clean);
        if ($cyr !== $clean) {
            $queries[] = $cyr;
        }

        $queries = array_values(array_unique(array_filter($queries)));

        foreach ($queries as $query) {
            try {
                $response = $this->http->get(self::BOOKUZ_API.'/products', [
                    'query' => [
                        'keyword' => $query,
                        'limit' => 6,
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $data = json_decode((string) $response->getBody(), true);
                $products = $data['data']['products'] ?? [];

                foreach ($products as $p) {
                    $rawTitle = $p['title'] ?? null;
                    $foundTitle = is_array($rawTitle) ? ($rawTitle['uz'] ?? reset($rawTitle)) : (string) $rawTitle;

                    if (! $this->isTitleMatch($expectedTitle, (string) $foundTitle)) {
                        continue;
                    }

                    $img = $p['image'] ?? null;
                    if (! is_string($img) || blank($img)) {
                        continue;
                    }

                    return $this->formatRemoteImage($img);
                }
            } catch (\Throwable $e) {
                Log::debug("[book_cover_resolver] Book.uz Title search error: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * Asaxiy.uz orqali kitob muqovasini qidirish (Book.uz da topilmaganlar uchun kuchli zaxira).
     */
    public function searchAsaxiy(string $title): ?string
    {
        $queries = [];
        $rawTrimmed = trim($title);
        if ($rawTrimmed !== '') {
            $queries[] = $rawTrimmed;
        }

        $clean = $this->cleanTitle($title);
        if ($clean !== '' && $clean !== $rawTrimmed) {
            $queries[] = $clean;
        }

        $norm = $this->normalizeApostrophe($rawTrimmed);
        if ($norm !== $rawTrimmed && $norm !== $clean) {
            $queries[] = $norm;
        }

        // Subtitle split (masalan: "O'zbekiston tavalludi. Ilk SSSR davrida..." -> "O'zbekiston tavalludi")
        $parts = preg_split('/[:.\-—]/u', $title);
        if (count($parts) > 1 && mb_strlen(trim($parts[0])) >= 3) {
            $queries[] = trim($parts[0]);
        }

        $cyr = $this->latinToCyrillic($clean);
        if ($cyr !== $clean) {
            $queries[] = $cyr;
        }

        $queries = array_values(array_unique(array_filter($queries, fn ($q) => mb_strlen($q) >= 3)));

        foreach ($queries as $query) {
            try {
                $url = 'https://asaxiy.uz/uz/product?key='.urlencode($query);
                $res = $this->http->get($url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ],
                ]);

                if ($res->getStatusCode() !== 200) {
                    continue;
                }

                $html = (string) $res->getBody();
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query('//div[contains(@class, "product__item")]');

                foreach ($nodes as $node) {
                    $titleNode = $xpath->query('.//span[contains(@class, "product__item__info-title")]', $node)->item(0);
                    $imgNode = $xpath->query('.//img[contains(@class, "img-fluid")]', $node)->item(0);

                    if (! $titleNode || ! $imgNode) {
                        continue;
                    }

                    $foundTitle = trim($titleNode->textContent);
                    $imgUrl = $imgNode->getAttribute('src') ?: $imgNode->getAttribute('data-fallback');

                    if (! str_starts_with($imgUrl, 'http') || str_contains($imgUrl, 'no-image')) {
                        continue;
                    }

                    // 1. To'g'ridan-to'g'ri tekshirish
                    if ($this->isTitleMatch($title, $foundTitle)) {
                        return $imgUrl;
                    }

                    // 2. Muallif nomini olib tashlab tekshirish ("Tomas Harris: Gannibal" -> "Gannibal")
                    $cleanFoundTitle = preg_replace('/^[^:]+:\s*/u', '', $foundTitle);
                    if ($this->isTitleMatch($title, $cleanFoundTitle)) {
                        return $imgUrl;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("[book_cover_resolver] Asaxiy search error: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * Google Books API orqali ISBN bo'yicha muqova qidirish.
     */
    private function searchGoogleBooks(string $isbn): ?string
    {
        try {
            $res = $this->http->get('https://www.googleapis.com/books/v1/volumes', [
                'query' => ['q' => 'isbn:'.$isbn],
            ]);

            if ($res->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $res->getBody(), true);
            $items = $data['items'] ?? [];

            if (! empty($items[0]['volumeInfo']['imageLinks']['thumbnail'])) {
                $thumb = (string) $items[0]['volumeInfo']['imageLinks']['thumbnail'];

                return str_replace('http://', 'https://', $thumb);
            }
        } catch (\Throwable) {
            // Google Books xatosi e'tiborga olinmaydi
        }

        return null;
    }

    /**
     * Topilgan rasm manzilini ishlaydigan formatga keltirish.
     */
    private function formatRemoteImage(string $url): string
    {
        if (str_contains($url, self::BROKEN_CLOUDINARY_SUBSTRING) && ! str_contains($url, 'book.uz/_next/image')) {
            return 'https://book.uz/_next/image?url='.urlencode($url).'&w=640&q=75';
        }

        return $url;
    }

    /**
     * Saqlangan ma'lumotdan haqiqiy asl Cloudinary manzilini ajratib olish (xato takrorlangan rasmlarni chetlab o'tadi).
     */
    private function extractOriginalCloudinaryUrl(mixed $raw): ?string
    {
        if (is_array($raw)) {
            $first = $raw[0] ?? null;

            return is_string($first) ? $this->extractOriginalCloudinaryUrl($first) : null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        // Agar bu bir xil bo'lib yozilib qolgan xato rasm bo'lsa, uni hisobga olmaymiz
        if (str_contains($raw, '1790422172657') || str_contains($raw, 'Screenshot_2026_09_26_072402') || str_contains($raw, 'fixed_')) {
            return null;
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->extractOriginalCloudinaryUrl($decoded);
            }
        }

        if (str_contains($raw, 'book.uz/_next/image') && str_contains($raw, 'url=')) {
            parse_str(parse_url($raw, PHP_URL_QUERY) ?: '', $params);
            if (! empty($params['url']) && str_contains($params['url'], self::BROKEN_CLOUDINARY_SUBSTRING)) {
                return $params['url'];
            }
        }

        if (str_contains($raw, self::BROKEN_CLOUDINARY_SUBSTRING) && str_starts_with($raw, 'http')) {
            return $raw;
        }

        return null;
    }

    /**
     * Ikki kitob nomining o'zaro mosligini hisoblash (Lotin va Kirillni birxillashtirgan holda).
     */
    public function isTitleMatch(string $expectedTitle, string $actualTitle): bool
    {
        $c1 = $this->toComparableLatin($expectedTitle);
        $c2 = $this->toComparableLatin($actualTitle);

        if ($c1 === '' || $c2 === '') {
            return false;
        }

        if ($c1 === $c2) {
            return true;
        }

        // Agar birining ichida ikkinchisi to'liq so'z sifatida kelsa va uzunligi yaqin bo'lsa
        if (str_contains($c2, $c1) || str_contains($c1, $c2)) {
            $lenRatio = min(mb_strlen($c1), mb_strlen($c2)) / max(mb_strlen($c1), mb_strlen($c2));
            if ($lenRatio >= 0.70) {
                return true;
            }
        }

        similar_text($c1, $c2, $percent);
        if ($percent >= 75.0) {
            return true;
        }

        // So'zlar kesishmasi (kamida 2 ta so'z va kamida 75% so'zlar mos kelishi kerak)
        $w1 = array_values(array_filter(explode(' ', $c1), fn ($w) => mb_strlen($w) >= 3));
        $w2 = array_values(array_filter(explode(' ', $c2), fn ($w) => mb_strlen($w) >= 3));
        if (count($w1) >= 2 && count($w2) >= 2) {
            $intersect = array_intersect($w1, $w2);
            if (count($intersect) >= (int) ceil(count($w1) * 0.75)) {
                return true;
            }
        }

        return false;
    }

    public function normalizeApostrophe(string $text): string
    {
        return str_replace(['‘', '’', 'ʻ', '`', '′', 'ʹ', 'ʼ'], "'", $text);
    }

    public function cleanTitle(string $title): string
    {
        $title = $this->normalizeApostrophe($title);
        $title = mb_strtolower(trim($title));
        // Barcha qavslar va ularning ichidagi qo'shimcha izohlarni olib tashlaymiz
        $title = preg_replace('/\([^\)]*\)/u', ' ', $title);
        $title = preg_replace('/\[[^\]]*\]/u', ' ', $title);
        // O'zbekcha o' va g' harflari uchun bitta tirnoqni saqlab qolamiz (harflar ajralib ketmasligi uchun)
        $title = preg_replace('/[^\p{L}\p{N}\s\']/u', ' ', $title);

        return trim(preg_replace('/\s+/', ' ', $title));
    }

    public function toComparableLatin(string $title): string
    {
        $clean = $this->cleanTitle($title);
        $clean = str_replace("'", '', $clean);

        $map = [
            'ш' => 'sh', 'ч' => 'ch', 'ё' => 'yo', 'ю' => 'yu', 'я' => 'ya', 'ye' => 'e',
            'ў' => 'o', 'ғ' => 'g', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'x', 'ҳ' => 'h', 'ц' => 'ts', 'щ' => 'sh', 'ъ' => '', 'ь' => '', 'э' => 'e',
            'е' => 'e', 'қ' => 'q',
        ];

        $translit = strtr($clean, $map);

        return trim(preg_replace('/\s+/', ' ', $translit));
    }

    public function latinToCyrillic(string $text): string
    {
        $map = [
            'sh' => 'ш', 'ch' => 'ч', 'yo' => 'ё', 'yu' => 'ю', 'ya' => 'я', 'ye' => 'е',
            "o'" => 'ў', "g'" => 'ғ',
            'a' => 'а', 'b' => 'б', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г', 'h' => 'ҳ',
            'i' => 'и', 'j' => 'ж', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о',
            'p' => 'п', 'q' => 'қ', 'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'v' => 'в',
            'x' => 'х', 'y' => 'й', 'z' => 'з',
        ];

        return strtr(mb_strtolower($text), $map);
    }

    private function verifyUrlWorks(string $url): bool
    {
        try {
            $res = $this->http->get($url, [
                'headers' => [
                    'Referer' => 'https://book.uz/',
                ],
            ]);

            return $res->getStatusCode() === 200 && strlen((string) $res->getBody()) > 1000;
        } catch (\Throwable) {
            return false;
        }
    }
}
