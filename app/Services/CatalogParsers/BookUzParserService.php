<?php

namespace App\Services\CatalogParsers;

use App\Models\BookCategories;
use App\Models\BookTag;
use App\Models\Books;
use App\Models\CatalogParserItem;
use App\Models\Seller;
use App\Services\OpenAIService;
use App\Support\ProductImageVariantGenerator;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookUzParserService
{
    public const PROVIDER = 'book_uz';
    public const SELLER_ID = 55;
    private const BASE_URL = 'https://book.uz';

    public function syncCatalog(?int $limit = null, ?string $singleUrl = null): array
    {
        @set_time_limit(0);

        $urls = $singleUrl
            ? [trim($singleUrl)]
            : $this->discoverProductUrls($limit);

        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($urls as $url) {
            try {
                $payload = $this->parseBookPage($url);
                $normalizedTitle = $this->normalizeComparableText($payload['title'] ?? null);
                $normalizedAuthor = $this->normalizeComparableText($payload['author'] ?? null);
                $matchedBook = $this->findExistingSellerBook($payload);
                $categorySuggestion = $this->suggestCategory($payload);
                $tagSuggestion = $this->suggestTags($payload, $categorySuggestion['category_id'] ?? null);

                CatalogParserItem::query()->updateOrCreate(
                    ['source_url' => $url],
                    [
                        'provider' => self::PROVIDER,
                        'external_id' => $payload['external_id'],
                        'title' => $payload['title'],
                        'author' => $payload['author'],
                        'isbn' => $payload['isbn'],
                        'normalized_title' => $normalizedTitle,
                        'normalized_author' => $normalizedAuthor,
                        'source_category' => $payload['source_category'],
                        'publisher' => $payload['publisher'],
                        'translator' => $payload['translator'],
                        'language' => $payload['language'],
                        'script' => $payload['script'],
                        'cover_type' => $payload['cover_type'],
                        'year' => $payload['year'],
                        'pages' => $payload['pages'],
                        'price_uzs' => $payload['price_uzs'],
                        'rating_value' => $payload['rating_value'],
                        'rating_count' => $payload['rating_count'],
                        'in_stock' => $payload['in_stock'],
                        'primary_image_url' => Arr::first($payload['remote_image_urls']),
                        'remote_image_urls' => $payload['remote_image_urls'],
                        'description' => $payload['description'],
                        'payload' => $payload['payload'],
                        'matched_book_id' => $matchedBook['book']?->id,
                        'match_confidence' => $matchedBook['confidence'],
                        'match_reason' => $matchedBook['reason'],
                        'suggested_category_id' => $categorySuggestion['category_id'],
                        'suggested_category_name' => $categorySuggestion['category_name'],
                        'category_ai_payload' => $categorySuggestion,
                        'suggested_tag_ids' => $tagSuggestion['tag_ids'],
                        'suggested_tag_names' => $tagSuggestion['tag_names'],
                        'tags_ai_payload' => $tagSuggestion,
                        'last_synced_at' => now(),
                        'last_import_error' => null,
                    ]
                );

                $synced++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $url . ' — ' . $e->getMessage();
            }
        }

        return [
            'requested' => count($urls),
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function importItem(CatalogParserItem $item, int $categoryId): Books
    {
        $seller = Seller::query()->findOrFail(self::SELLER_ID);
        $category = BookCategories::query()->findOrFail($categoryId);

        return DB::transaction(function () use ($item, $seller, $category) {
            $book = $item->imported_book_id
                ? Books::query()->find($item->imported_book_id)
                : null;

            if (! $book && $item->matched_book_id) {
                $book = Books::query()->find($item->matched_book_id);
            }

            if (! $book) {
                $book = $this->findExistingSellerBook([
                    'title' => $item->title,
                    'author' => $item->author,
                    'isbn' => $item->isbn,
                ])['book'];
            }

            $images = $this->downloadImages($item);
            $description = $this->buildImportDescription($item);
            $existingVectorData = is_array($book?->vectorData) ? $book->vectorData : [];

            if ($book) {
                $normalizedIsbn = $this->normalizeNumericIsbn($item->isbn);
                $book->update([
                    'isbn' => $normalizedIsbn,
                    'vectorData' => array_merge($existingVectorData, [
                        'parser' => [
                            'provider' => self::PROVIDER,
                            'source_url' => $item->source_url,
                            'publisher' => $item->publisher,
                            'translator' => $item->translator,
                            'source_category' => $item->source_category,
                            'matched_via_parser' => true,
                        ],
                    ]),
                ]);

                $item->forceFill([
                    'matched_book_id' => $book->id,
                    'imported_book_id' => $book->id,
                    'imported_at' => now(),
                    'last_import_error' => null,
                ])->save();

                $this->attachSuggestedTags($book, $item);

                return $book;
            }

            $payload = [
                'name' => $item->title ?: 'Nomsiz kitob',
                'author' => $item->author ?: 'Nomaʼlum muallif',
                'isbn' => $this->normalizeNumericIsbn($item->isbn),
                'category_id' => $category->id,
                'seller_id' => $seller->id,
                'description' => $description,
                'images' => $images,
                'price' => (int) ($item->price_uzs ?? 0),
                'discountPrice' => null,
                'discountExpiresAt' => null,
                'count' => $item->in_stock ? 1 : 0,
                'lang' => $this->normalizeLanguageCode($item->language),
                'langType' => $item->script,
                'coverType' => $item->cover_type,
                'year' => $item->year,
                'pages' => $item->pages,
                'status' => true,
                'is_hidden' => false,
                'is_approved' => 1,
                'recommended' => false,
                'vectorData' => array_merge($existingVectorData, [
                    'parser' => [
                        'provider' => self::PROVIDER,
                        'source_url' => $item->source_url,
                        'publisher' => $item->publisher,
                        'translator' => $item->translator,
                        'source_category' => $item->source_category,
                    ],
                ]),
            ];

            if ($book) {
                $book->update($payload);
            } else {
                $book = Books::query()->create($payload);
            }

            $this->attachSuggestedTags($book, $item);

            $item->forceFill([
                'imported_book_id' => $book->id,
                'imported_at' => now(),
                'last_import_error' => null,
            ])->save();

            return $book;
        });
    }

    public function importMany(iterable $items, int $categoryId): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                $this->importItem($item, $categoryId);
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $item->forceFill([
                    'last_import_error' => Str::limit($e->getMessage(), 65000),
                ])->save();
                $errors[] = '#' . $item->id . ' — ' . $e->getMessage();
            }
        }

        return compact('imported', 'failed', 'errors');
    }

    private function discoverProductUrls(?int $limit = null): array
    {
        $urls = $this->discoverViaSitemaps();

        if (empty($urls)) {
            $urls = $this->discoverViaCatalogCrawl($limit);
        }

        $urls = array_values(array_unique(array_filter($urls, fn ($url) => str_contains($url, '/books/details/'))));

        if ($limit !== null) {
            $urls = array_slice($urls, 0, max(1, $limit));
        }

        return $urls;
    }

    private function discoverViaSitemaps(): array
    {
        $sitemapUrls = array_unique(array_filter([
            ...$this->discoverSitemapsFromRobots(),
            self::BASE_URL . '/sitemap.xml',
            self::BASE_URL . '/sitemap_index.xml',
            self::BASE_URL . '/sitemap-index.xml',
        ]));

        $visited = [];
        $bookUrls = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            $bookUrls = array_merge($bookUrls, $this->readSitemapRecursive($sitemapUrl, $visited));
        }

        return array_values(array_unique($bookUrls));
    }

    private function discoverSitemapsFromRobots(): array
    {
        try {
            $response = $this->http()->get(self::BASE_URL . '/robots.txt');
            if (! $response->successful()) {
                return [];
            }

            preg_match_all('/^Sitemap:\s*(.+)$/mi', $response->body(), $matches);

            return collect($matches[1] ?? [])
                ->map(fn ($url) => trim((string) $url))
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function readSitemapRecursive(string $url, array &$visited): array
    {
        if (isset($visited[$url])) {
            return [];
        }
        $visited[$url] = true;

        try {
            $response = $this->http()->get($url);
            if (! $response->successful()) {
                return [];
            }

            $xml = @simplexml_load_string($response->body());
            if (! $xml) {
                return [];
            }

            $namespaces = $xml->getNamespaces(true);
            $nodes = [];

            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $loc = trim((string) $sitemap->loc);
                    if ($loc !== '') {
                        $nodes = array_merge($nodes, $this->readSitemapRecursive($loc, $visited));
                    }
                }
                return $nodes;
            }

            if (isset($namespaces[''])) {
                $children = $xml->children($namespaces['']);
                if (isset($children->sitemap)) {
                    foreach ($children->sitemap as $sitemap) {
                        $loc = trim((string) $sitemap->loc);
                        if ($loc !== '') {
                            $nodes = array_merge($nodes, $this->readSitemapRecursive($loc, $visited));
                        }
                    }
                    return $nodes;
                }
            }

            foreach ($xml->url as $entry) {
                $loc = trim((string) $entry->loc);
                if ($loc !== '' && str_contains($loc, '/books/details/')) {
                    $nodes[] = $loc;
                }
            }

            if (isset($namespaces[''])) {
                foreach ($xml->children($namespaces[''])->url as $entry) {
                    $loc = trim((string) $entry->loc);
                    if ($loc !== '' && str_contains($loc, '/books/details/')) {
                        $nodes[] = $loc;
                    }
                }
            }

            return $nodes;
        } catch (\Throwable) {
            return [];
        }
    }

    private function discoverViaCatalogCrawl(?int $limit = null): array
    {
        $page = 1;
        $productUrls = [];

        while (true) {
            try {
                $url = self::BASE_URL . '/books?page=' . $page;
                $response = $this->http()->get($url);
                if (! $response->successful()) {
                    break;
                }
                $html = $response->body();
            } catch (\Throwable) {
                break;
            }

            preg_match_all('~https://book\.uz/books/details/[^"\'\s<]+~i', $html, $matches);
            $found = collect($matches[0] ?? [])->unique()->values()->all();

            if (empty($found)) {
                break;
            }

            $productUrls = array_merge($productUrls, $found);

            if ($limit !== null && count(array_unique($productUrls)) >= $limit) {
                break;
            }

            $page++;

            if ($page > 200) {
                break;
            }
        }

        return array_values(array_unique($productUrls));
    }

    private function parseBookPage(string $url): array
    {
        $response = $this->http()->get($url);
        if (! $response->successful()) {
            throw new \RuntimeException('Sahifa olinmadi: HTTP ' . $response->status());
        }

        $html = $response->body();
        $xpath = $this->makeXPath($html);
        $jsonLd = $this->extractJsonLdBlocks($html);
        $productLd = $this->firstProductJsonLd($jsonLd);
        $text = $this->normalizedHtmlText($html);

        $title = $this->extractMetaContent($xpath, 'property', 'og:title')
            ?: Arr::get($productLd, 'name')
            ?: $this->extractFirstText($xpath, [
                "//h1",
                "//*[contains(@class,'product')]//h1",
                "//*[contains(@class,'title')]//h1",
            ]);

        $remoteImages = $this->extractImages($xpath, $productLd, $html);
        $description = trim((string) (
            Arr::get($productLd, 'description')
            ?: $this->extractMetaContent($xpath, 'property', 'og:description')
            ?: $this->extractDescription($xpath)
        ));

        $specs = $this->extractSpecs($xpath, $text);

        $availability = (string) Arr::get($productLd, 'offers.availability', '');
        $inStock = str_contains(Str::lower($availability), 'instock')
            || str_contains(Str::lower($html), 'add to cart')
            || str_contains(Str::lower($html), 'savatga qo')
            || str_contains(Str::lower($html), 'корзин');

        $price = Arr::get($productLd, 'offers.price');
        if ($price === null) {
            $price = $this->extractPrice($text);
        }

        $ratingValue = Arr::get($productLd, 'aggregateRating.ratingValue');
        $ratingCount = Arr::get($productLd, 'aggregateRating.reviewCount')
            ?? Arr::get($productLd, 'aggregateRating.ratingCount');

        $sourceCategory = $this->extractBreadcrumbCategory($xpath, $jsonLd);

        return [
            'external_id' => Str::afterLast(parse_url($url, PHP_URL_PATH) ?: $url, '/'),
            'title' => $title,
            'author' => $this->cleanField($specs['author'] ?? Arr::get($productLd, 'author.name') ?? Arr::get($productLd, 'author')),
            'isbn' => $this->cleanField($specs['isbn'] ?? null),
            'source_category' => $this->cleanField($sourceCategory),
            'publisher' => $this->cleanField($specs['publisher'] ?? null),
            'translator' => $this->cleanField($specs['translator'] ?? null),
            'language' => $this->cleanField($specs['language'] ?? null),
            'script' => $this->cleanField($specs['script'] ?? null),
            'cover_type' => $this->cleanField($specs['cover_type'] ?? null),
            'year' => $this->toInt($specs['year'] ?? null),
            'pages' => $this->toInt($specs['pages'] ?? null),
            'price_uzs' => $this->toInt($price),
            'rating_value' => is_numeric($ratingValue) ? round((float) $ratingValue, 2) : null,
            'rating_count' => $this->toInt($ratingCount),
            'in_stock' => $inStock,
            'remote_image_urls' => $remoteImages,
            'description' => $description,
            'payload' => [
                'json_ld' => $productLd,
                'specs' => $specs,
            ],
        ];
    }

    private function extractImages(DOMXPath $xpath, array $productLd, string $html): array
    {
        $images = Arr::wrap(Arr::get($productLd, 'image'));

        if (empty($images)) {
            foreach ([
                "//meta[@property='og:image']/@content",
                "//img[contains(@class,'product')]/@src",
                "//img[contains(@src,'book')]/@src",
            ] as $query) {
                foreach ($xpath->query($query) as $node) {
                    $images[] = trim((string) $node->nodeValue);
                }
            }
        }

        if (empty($images)) {
            preg_match_all('~https://book\.uz/storage/[^"\'\s<]+~i', $html, $matches);
            $images = $matches[0] ?? [];
        }

        return collect($images)
            ->map(fn ($url) => $this->absoluteUrl((string) $url))
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->all();
    }

    private function extractDescription(DOMXPath $xpath): ?string
    {
        $queries = [
            "//*[contains(@class,'description')]",
            "//*[@id='description']",
            "//section[contains(@class,'description')]",
        ];

        foreach ($queries as $query) {
            $node = $xpath->query($query)?->item(0);
            if ($node instanceof DOMNode) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function extractBreadcrumbCategory(DOMXPath $xpath, array $jsonLd): ?string
    {
        $breadcrumbLd = collect($jsonLd)
            ->first(fn ($item) => is_array($item) && (($item['@type'] ?? null) === 'BreadcrumbList'));

        $name = data_get($breadcrumbLd, 'itemListElement.1.name')
            ?? data_get($breadcrumbLd, 'itemListElement.2.name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $nodes = $xpath->query("//nav//*[self::a or self::span]");
        if (! $nodes) {
            return null;
        }

        $parts = [];
        foreach ($nodes as $node) {
            $value = trim((string) $node->textContent);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return $parts[count($parts) - 2] ?? null;
    }

    private function extractSpecs(DOMXPath $xpath, string $text): array
    {
        $specMap = [
            'author' => ['Muallif', 'Автор', 'Author'],
            'isbn' => ['ISBN'],
            'script' => ['Yozuvi', 'Письмо', 'Script'],
            'year' => ['Nashr yili', 'Год издания', 'Year'],
            'language' => ['Tili', 'Язык', 'Language'],
            'pages' => ['Sahifalar soni', 'Страниц', 'Pages'],
            'publisher' => ['Nashriyot', 'Издательство', 'Publisher'],
            'cover_type' => ['Muqova turi', 'Переплет', 'Cover type'],
            'translator' => ['Tarjimon', 'Переводчик', 'Translator'],
        ];

        $specs = [];

        foreach ($specMap as $key => $aliases) {
            $value = $this->extractSpecFromDom($xpath, $aliases);
            if ($value === null) {
                $value = $this->extractSpecFromText($text, $aliases);
            }
            $specs[$key] = $value;
        }

        return $specs;
    }

    private function extractSpecFromDom(DOMXPath $xpath, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $literal = $this->xpathLiteral($alias);
            $queries = [
                "//*[normalize-space(text())={$literal}]/following-sibling::*[1]",
                "//*[contains(normalize-space(.), {$literal})]/following-sibling::*[1]",
                "//dt[normalize-space(.)={$literal}]/following-sibling::dd[1]",
            ];

            foreach ($queries as $query) {
                $node = $xpath->query($query)?->item(0);
                if ($node instanceof DOMNode) {
                    $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                    if ($text !== '' && mb_strlen($text) < 250) {
                        return $text;
                    }
                }
            }
        }

        return null;
    }

    private function extractSpecFromText(string $text, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $pattern = '/(?:' . preg_quote($alias, '/') . ')\s*[:\-]?\s*([^\n\r|]{1,180})/ui';
            if (preg_match($pattern, $text, $matches)) {
                return trim((string) ($matches[1] ?? ''));
            }
        }

        return null;
    }

    private function extractPrice(string $text): ?int
    {
        if (preg_match('/([\d\s]{3,})\s*(?:so\'m|so‘m|сум)/ui', $text, $matches)) {
            return $this->toInt($matches[1]);
        }

        return null;
    }

    private function extractJsonLdBlocks(string $html): array
    {
        preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(function ($json) {
                $decoded = json_decode(trim((string) $json), true);
                return is_array($decoded) ? $decoded : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function firstProductJsonLd(array $blocks): array
    {
        foreach ($blocks as $block) {
            if (($block['@type'] ?? null) === 'Product') {
                return $block;
            }

            if (($block['@graph'] ?? null) && is_array($block['@graph'])) {
                foreach ($block['@graph'] as $node) {
                    if (($node['@type'] ?? null) === 'Product') {
                        return $node;
                    }
                }
            }
        }

        return [];
    }

    private function makeXPath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        @$document->loadHTML($html);
        return new DOMXPath($document);
    }

    private function extractMetaContent(DOMXPath $xpath, string $attr, string $value): ?string
    {
        $literal = $this->xpathLiteral($value);
        $node = $xpath->query("//meta[@{$attr}={$literal}]/@content")?->item(0);
        return $node ? trim((string) $node->nodeValue) : null;
    }

    private function extractFirstText(DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $node = $xpath->query($query)?->item(0);
            if ($node instanceof DOMNode) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function normalizedText(?DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($node?->textContent ?? '')) ?? '');
    }

    private function normalizedHtmlText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    }

    private function absoluteUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:' . $url;
        }

        return rtrim(self::BASE_URL, '/') . '/' . ltrim($url, '/');
    }

    private function downloadImages(CatalogParserItem $item): array
    {
        $paths = [];

        foreach (array_slice($item->remote_image_urls ?? [], 0, 8) as $index => $url) {
            try {
                $response = $this->http()->get($url);
                if (! $response->successful() || $response->body() === '') {
                    continue;
                }

                $contentType = (string) $response->header('Content-Type');
                $extension = $this->guessImageExtension($url, $contentType);
                $filename = 'books/parser_' . $item->id . '_' . ($index + 1) . '_' . Str::random(8) . '.' . $extension;

                Storage::disk('public')->put($filename, $response->body());
                ProductImageVariantGenerator::generateForPath($filename);
                $paths[] = $filename;
            } catch (\Throwable) {
                continue;
            }
        }

        return array_values(array_unique($paths));
    }

    private function buildImportDescription(CatalogParserItem $item): string
    {
        $chunks = array_filter([
            trim((string) $item->description),
            $item->publisher ? 'Nashriyot: ' . $item->publisher : null,
            $item->translator ? 'Tarjimon: ' . $item->translator : null,
            $item->language ? 'Til: ' . $item->language : null,
            $item->script ? 'Yozuv: ' . $item->script : null,
            $item->source_url ? 'Manba: ' . $item->source_url : null,
        ]);

        return implode("\n\n", $chunks);
    }

    private function attachSuggestedTags(Books $book, CatalogParserItem $item): void
    {
        $tagIds = collect($item->suggested_tag_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (!empty($tagIds)) {
            $book->tags()->syncWithoutDetaching($tagIds);
        }
    }

    private function findExistingSellerBook(array $payload): array
    {
        $normalizedTitle = $this->normalizeComparableText($payload['title'] ?? null);
        $normalizedAuthor = $this->normalizeComparableText($payload['author'] ?? null);
        $normalizedIsbn = $this->normalizeNumericIsbn($payload['isbn'] ?? null);

        if ($normalizedIsbn) {
            $book = Books::query()
                ->where('seller_id', self::SELLER_ID)
                ->where(function ($query) use ($normalizedIsbn) {
                    $query->where('isbn', $normalizedIsbn)
                        ->orWhere('isbn', preg_replace('/[^0-9]/', '', $normalizedIsbn));
                })
                ->first();

            if ($book) {
                return [
                    'book' => $book,
                    'confidence' => 100,
                    'reason' => 'isbn_exact',
                ];
            }
        }

        if ($normalizedTitle === null) {
            return ['book' => null, 'confidence' => null, 'reason' => null];
        }

        $significantTokens = collect(explode(' ', $normalizedTitle))
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->take(4)
            ->values();

        $candidateQuery = Books::query()
            ->where('seller_id', self::SELLER_ID);

        if ($significantTokens->isNotEmpty()) {
            $candidateQuery->where(function ($query) use ($significantTokens) {
                foreach ($significantTokens as $token) {
                    $query->orWhere('name', 'like', '%' . $token . '%');
                }
            });
        }

        $candidates = $candidateQuery
            ->select('id', 'name', 'author', 'isbn')
            ->limit(60)
            ->get();

        $bestBook = null;
        $bestScore = 0;
        $bestReason = null;

        foreach ($candidates as $candidate) {
            $candidateTitle = $this->normalizeComparableText($candidate->name);
            $candidateAuthor = $this->normalizeComparableText($candidate->author);

            $titleSimilarity = $this->similarityPercent($normalizedTitle, $candidateTitle);
            $authorSimilarity = $this->similarityPercent($normalizedAuthor, $candidateAuthor);

            $score = 0;
            $reason = null;

            if ($normalizedTitle !== null && $candidateTitle === $normalizedTitle) {
                $score += 70;
                $reason = 'title_exact';
            } else {
                $score += (int) round($titleSimilarity * 0.7);
                $reason = 'title_similar';
            }

            if ($normalizedAuthor !== null && $candidateAuthor === $normalizedAuthor) {
                $score += 30;
                $reason = $reason === 'title_exact' ? 'title_author_exact' : 'author_exact';
            } else {
                $score += (int) round($authorSimilarity * 0.3);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestBook = $candidate;
                $bestReason = $reason;
            }
        }

        if ($bestBook && $bestScore >= 78) {
            return [
                'book' => $bestBook,
                'confidence' => min(99, $bestScore),
                'reason' => $bestReason ?: 'similarity_match',
            ];
        }

        return ['book' => null, 'confidence' => null, 'reason' => null];
    }

    private function suggestCategory(array $payload): array
    {
        $categories = BookCategories::query()
            ->where('is_active', true)
            ->orderBy('name_uz')
            ->get(['id', 'name_uz', 'name_ru', 'name_en', 'name_ja']);

        $sourceCategory = trim((string) ($payload['source_category'] ?? ''));
        $normalizedSourceCategory = $this->normalizeComparableText($sourceCategory);

        foreach ($categories as $category) {
            $names = array_filter([
                $category->name_uz,
                $category->name_ru,
                $category->name_en,
                $category->name_ja,
            ]);

            foreach ($names as $name) {
                $normalizedName = $this->normalizeComparableText($name);
                if ($normalizedSourceCategory && $normalizedName && (
                    $normalizedName === $normalizedSourceCategory
                    || str_contains($normalizedSourceCategory, $normalizedName)
                    || str_contains($normalizedName, $normalizedSourceCategory)
                )) {
                    return [
                        'category_id' => $category->id,
                        'category_name' => $category->name_uz,
                        'method' => 'heuristic',
                        'reason' => 'source_category_match',
                        'source_category' => $sourceCategory,
                    ];
                }
            }
        }

        try {
            /** @var OpenAIService $ai */
            $ai = app(OpenAIService::class);
            $categoryList = $categories->map(fn ($category) => [
                'id' => $category->id,
                'name_uz' => $category->name_uz,
                'name_ru' => $category->name_ru,
                'name_en' => $category->name_en,
                'name_ja' => $category->name_ja,
            ])->values()->all();

            $result = $ai->askJsonWithMessages([
                [
                    'role' => 'system',
                    'content' => "Sen kitob katalogi klassifikatori bo'lasan. Faqat JSON qaytar. category_id mavjud idlardan biri bo'lsin yoki null bo'lsin.",
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'Choose the best matching internal category for this external book.',
                        'book' => [
                            'title' => $payload['title'] ?? null,
                            'author' => $payload['author'] ?? null,
                            'source_category' => $payload['source_category'] ?? null,
                            'description' => Str::limit((string) ($payload['description'] ?? ''), 700, ''),
                        ],
                        'categories' => $categoryList,
                        'output_schema' => [
                            'category_id' => 'int|null',
                            'category_name' => 'string|null',
                            'reason' => 'string',
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 500, 0.1);

            $categoryId = (int) ($result['category_id'] ?? 0) ?: null;
            $matchedCategory = $categoryId ? $categories->firstWhere('id', $categoryId) : null;

            return [
                'category_id' => $matchedCategory?->id,
                'category_name' => $matchedCategory?->name_uz ?? ($result['category_name'] ?? null),
                'method' => 'ai',
                'reason' => $result['reason'] ?? null,
                'source_category' => $sourceCategory,
                'raw' => $result,
            ];
        } catch (\Throwable) {
            return [
                'category_id' => null,
                'category_name' => null,
                'method' => 'fallback',
                'reason' => 'no_category_match',
                'source_category' => $sourceCategory,
            ];
        }
    }

    private function suggestTags(array $payload, ?int $categoryId): array
    {
        if (!$categoryId) {
            return [
                'tag_ids' => [],
                'tag_names' => [],
                'method' => 'fallback',
                'reason' => 'category_missing',
            ];
        }

        $category = BookCategories::query()
            ->with(['tags:id,tag_name_uz,tag_name_ru,tag_name_en'])
            ->find($categoryId);

        $availableTags = $category?->tags ?? collect();
        if ($availableTags->isEmpty()) {
            return [
                'tag_ids' => [],
                'tag_names' => [],
                'method' => 'fallback',
                'reason' => 'category_has_no_tags',
            ];
        }

        $titleText = Str::lower((string) ($payload['title'] ?? ''));
        $descriptionText = Str::lower((string) ($payload['description'] ?? ''));
        $sourceCategoryText = Str::lower((string) ($payload['source_category'] ?? ''));
        $haystack = $titleText . ' ' . $descriptionText . ' ' . $sourceCategoryText;

        $heuristicMatches = $availableTags->filter(function (BookTag $tag) use ($haystack) {
            $variants = array_filter([
                $tag->tag_name_uz,
                $tag->tag_name_ru,
                $tag->tag_name_en,
            ]);

            foreach ($variants as $variant) {
                $normalized = Str::lower(trim((string) $variant));
                if ($normalized !== '' && str_contains($haystack, $normalized)) {
                    return true;
                }
            }

            return false;
        })->values();

        if ($heuristicMatches->isNotEmpty()) {
            return [
                'tag_ids' => $heuristicMatches->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'tag_names' => $heuristicMatches->pluck('tag_name_uz')->filter()->values()->all(),
                'method' => 'heuristic',
                'reason' => 'text_tag_match',
            ];
        }

        try {
            /** @var OpenAIService $ai */
            $ai = app(OpenAIService::class);
            $tagList = $availableTags->map(fn (BookTag $tag) => [
                'id' => $tag->id,
                'tag_name_uz' => $tag->tag_name_uz,
                'tag_name_ru' => $tag->tag_name_ru,
                'tag_name_en' => $tag->tag_name_en,
            ])->values()->all();

            $result = $ai->askJsonWithMessages([
                [
                    'role' => 'system',
                    'content' => "Sen kitob teglari klassifikatori bo'lasan. Faqat JSON qaytar. tag_ids faqat berilgan tag idlardan iborat bo'lsin.",
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'Pick the most relevant tags for this book from the provided tag list. Use 0 to 5 tags.',
                        'book' => [
                            'title' => $payload['title'] ?? null,
                            'author' => $payload['author'] ?? null,
                            'source_category' => $payload['source_category'] ?? null,
                            'description' => Str::limit((string) ($payload['description'] ?? ''), 700, ''),
                        ],
                        'category' => [
                            'id' => $category?->id,
                            'name_uz' => $category?->name_uz,
                        ],
                        'available_tags' => $tagList,
                        'output_schema' => [
                            'tag_ids' => 'int[]',
                            'reason' => 'string',
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 500, 0.1);

            $validIds = collect($result['tag_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $availableTags->contains('id', $id))
                ->unique()
                ->values();

            return [
                'tag_ids' => $validIds->all(),
                'tag_names' => $availableTags->whereIn('id', $validIds)->pluck('tag_name_uz')->filter()->values()->all(),
                'method' => 'ai',
                'reason' => $result['reason'] ?? null,
                'raw' => $result,
            ];
        } catch (\Throwable) {
            return [
                'tag_ids' => [],
                'tag_names' => [],
                'method' => 'fallback',
                'reason' => 'ai_failed',
            ];
        }
    }

    private function normalizeLanguageCode(?string $language): ?string
    {
        $value = Str::lower(trim((string) $language));

        return match (true) {
            $value === '' => null,
            str_contains($value, 'uz') || str_contains($value, 'o‘z') => 'uz',
            str_contains($value, 'rus') || str_contains($value, 'ru') => 'ru',
            str_contains($value, 'eng') || str_contains($value, 'ing') || str_contains($value, 'en') => 'en',
            str_contains($value, 'ja') || str_contains($value, 'yapon') => 'ja',
            default => Str::limit($value, 10, ''),
        };
    }

    private function guessImageExtension(string $url, string $contentType): string
    {
        if (str_contains($contentType, 'png')) {
            return 'png';
        }
        if (str_contains($contentType, 'webp')) {
            return 'webp';
        }
        if (str_contains($contentType, 'gif')) {
            return 'gif';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? $extension
            : 'jpg';
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', (string) $value);
        if ($clean === null || $clean === '') {
            return null;
        }

        return (int) $clean;
    }

    private function cleanField(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, 255, '');
    }

    private function normalizeNumericIsbn(?string $raw): ?string
    {
        $canonical = Books::normalizeIsbn($raw);
        if ($canonical === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $canonical);
        return $digits !== '' ? $digits : null;
    }

    private function normalizeComparableText(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = mb_strtolower($value);
        $value = str_replace(
            ["’", "'", "`", "ʻ", "ʼ", "ʹ", "“", "”", "\"", "-", "_", "/", "\\", "(", ")", "[", "]", "{", "}", ".", ",", ":", ";", "!", "?", "+", "&"],
            ' ',
            $value
        );
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function similarityPercent(?string $left, ?string $right): int
    {
        if ($left === null || $right === null || $left === '' || $right === '') {
            return 0;
        }

        similar_text($left, $right, $percent);
        return (int) round($percent);
    }

    private function xpathLiteral(string $value): string
    {
        if (! str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        if (! str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);
        return "concat('" . implode("', \"'\", '", $parts) . "')";
    }

    private function http()
    {
        return Http::timeout(25)
            ->retry(2, 500)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; KitobchiParser/1.0; +https://kitobchi.com)',
                'Accept-Language' => 'uz,en;q=0.9,ru;q=0.8',
            ]);
    }
}
