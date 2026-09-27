<?php

namespace App\Services\CatalogParsers;

use App\Support\Isbn;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QamarUzParserService
{
    private const BASE_URL = 'https://qamar.uz';
    private const SITEMAP_URL = 'https://qamar.uz/sitemap.xml';

    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 15,
            'connect_timeout' => 8,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Sitemapdagi barcha kitob URL'larini aniqlaydi.
     *
     * @return array<string>
     */
    public function discoverBookUrls(?int $limit = null): array
    {
        try {
            $response = $this->http->get(self::SITEMAP_URL);
            $xml = (string) $response->getBody();

            preg_match_all('/<loc>(https:\/\/qamar\.uz\/kitob\/[^<]+)<\/loc>/', $xml, $matches);
            $urls = array_values(array_unique($matches[1] ?? []));

            if ($limit !== null && $limit > 0) {
                return array_slice($urls, 0, $limit);
            }

            return $urls;
        } catch (\Throwable $e) {
            Log::error('QamarUzParser: sitemap olishda xatolik', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Bitta kitob sahifasini o'qib, Schema.org JSON-LD orqali ma'lumotlarni chiqarib oladi.
     */
    public function parseBookPage(string $url): ?array
    {
        try {
            $response = $this->http->get($url);
            $html = (string) $response->getBody();

            if (! preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                return null;
            }

            $bookData = null;
            foreach ($matches[1] as $jsonStr) {
                $decoded = json_decode($jsonStr, true);
                if (is_array($decoded) && ($decoded['@type'] ?? '') === 'Book') {
                    $bookData = $decoded;
                    break;
                }
            }

            if (! $bookData) {
                return null;
            }

            $rawIsbn = $bookData['isbn'] ?? null;
            $isbn13 = Isbn::toIsbn13($rawIsbn);

            // Faqat ISBN mavjud bo'lgan kitoblar talab qilingan
            if (! $isbn13) {
                return null;
            }

            $title = trim((string) ($bookData['name'] ?? ''));
            if ($title === '') {
                return null;
            }

            $authorName = null;
            if (isset($bookData['author'])) {
                $authorName = is_array($bookData['author'])
                    ? ($bookData['author']['name'] ?? null)
                    : (string) $bookData['author'];
            }

            $publisherName = null;
            if (isset($bookData['publisher'])) {
                $publisherName = is_array($bookData['publisher'])
                    ? ($bookData['publisher']['name'] ?? null)
                    : (string) $bookData['publisher'];
            }

            $format = (string) ($bookData['bookFormat'] ?? '');
            $coverType = str_contains(strtolower($format), 'hard') ? 'hard' : 'soft';

            $pages = isset($bookData['numberOfPages']) ? (int) $bookData['numberOfPages'] : null;
            $imageUrl = $bookData['image'] ?? null;
            $description = trim((string) ($bookData['description'] ?? ''));
            $price = isset($bookData['offers']['price']) ? (int) $bookData['offers']['price'] : null;

            $categoryRaw = null;
            foreach ($matches[1] as $jsonStr) {
                $decoded = json_decode($jsonStr, true);
                if (is_array($decoded) && ($decoded['@type'] ?? '') === 'BreadcrumbList' && ! empty($decoded['itemListElement'])) {
                    $items = collect($decoded['itemListElement'])->sortBy('position')->values();
                    if ($items->count() >= 2) {
                        $catItem = $items[$items->count() - 2];
                        $catName = is_array($catItem) ? ($catItem['name'] ?? data_get($catItem, 'item.name')) : null;
                        if ($catName && ! in_array(mb_strtolower(trim((string) $catName)), ['bosh sahifa', 'glavnaya', 'home', 'katalog', 'kitoblar'], true)) {
                            $categoryRaw = trim((string) $catName);
                        }
                    }
                }
            }

            if (! $categoryRaw && ! empty($bookData['genre'])) {
                $categoryRaw = is_array($bookData['genre']) ? implode(', ', $bookData['genre']) : (string) $bookData['genre'];
            }

            if (! $categoryRaw && preg_match('/class=["\'][^"\']*breadcrumb[^"\']*["\']>(.*?)<\/(?:nav|ul|ol|div)>/is', $html, $bMatch)) {
                if (preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $bMatch[1], $aMatches)) {
                    $crumbs = array_map('strip_tags', $aMatches[1]);
                    $crumbs = array_values(array_filter(array_map('trim', $crumbs)));
                    $crumbs = array_values(array_filter($crumbs, fn ($c) => ! in_array(mb_strtolower($c), ['bosh sahifa', 'glavnaya', 'home', 'katalog', 'bosh sahifaga'], true)));
                    if (! empty($crumbs)) {
                        $categoryRaw = end($crumbs);
                    }
                }
            }

            $rawTags = [];
            if (! empty($bookData['keywords'])) {
                $rawTags = is_array($bookData['keywords'])
                    ? $bookData['keywords']
                    : array_map('trim', explode(',', (string) $bookData['keywords']));
            } elseif (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\']([^"\']+)["\']/i', $html, $kMatch)) {
                $rawTags = array_map('trim', explode(',', $kMatch[1]));
            }
            $rawTags = array_values(array_unique(array_filter($rawTags)));

            return [
                'source' => 'qamar_uz',
                'source_url' => $url,
                'external_id' => Str::afterLast(parse_url($url, PHP_URL_PATH) ?: $url, '/'),
                'title' => $title,
                'author' => $authorName ? trim($authorName) : null,
                'isbn' => $isbn13,
                'isbn13' => $isbn13,
                'isbn10' => Isbn::toIsbn10($isbn13),
                'publisher' => $publisherName ? trim($publisherName) : null,
                'pages' => $pages,
                'cover_type' => $coverType,
                'language' => $bookData['inLanguage'] ?? 'uz',
                'image_url' => $imageUrl,
                'description' => $description,
                'price_uzs' => $price,
                'category_raw' => $categoryRaw,
                'raw_tags' => $rawTags,
            ];
        } catch (\Throwable $e) {
            Log::warning('QamarUzParser: kitobni o\'qishda xatolik ' . $url, ['error' => $e->getMessage()]);
            return null;
        }
    }
}
