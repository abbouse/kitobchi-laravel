<?php

namespace App\Services\CatalogParsers;

use App\Models\BookCategories;
use App\Models\BookEdition;
use App\Models\Books;
use App\Models\CatalogParserItem;
use App\Models\Publisher;
use App\Services\AuthorDirectoryService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use App\Support\ProductImageVariantGenerator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExternalCatalogSyncService
{
    private Client $http;

    public function __construct(
        private readonly QamarUzParserService $qamarParser,
        private readonly BookUzParserService $bookUzParser,
        private readonly AuthorDirectoryService $authorDirectory,
    ) {
        $this->http = new Client([
            'timeout' => 12,
            'connect_timeout' => 6,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Boshqaruv statistikasi: jami kartalar, ISBN mavjud/yo'q, ulanmaganlar.
     */
    public function getStats(): array
    {
        return [
            'total_editions' => BookEdition::query()->where('status', '!=', BookEdition::STATUS_MERGED)->count(),
            'with_isbn' => BookEdition::query()->whereNotNull('isbn13')->count(),
            'without_isbn' => BookEdition::query()->whereNull('isbn13')->count(),
            'unlinked_books' => Books::query()->whereNull('edition_id')->count(),
            'parser_items_cached' => CatalogParserItem::query()->whereNotNull('isbn')->count(),
        ];
    }

    /**
     * Berilgan manbadan kitoblarni parse qilish va sinxronlash.
     *
     * @param string $source 'qamar_uz' | 'book_uz' | 'all'
     * @param int $limit Maksimal kitoblar soni (0 = chegarasiz)
     * @param bool $withImages Rasmlarni serverga yuklab olish
     * @param callable|null $logger Progress haqida xabar berish uchun callback
     */
    public function runSync(string $source = 'all', int $limit = 50, bool $withImages = true, ?callable $logger = null): array
    {
        @set_time_limit(0);

        $results = [
            'source' => $source,
            'total_scanned' => 0,
            'editions_created' => 0,
            'isbn_enriched' => 0,
            'already_matched' => 0,
            'skipped_no_isbn' => 0,
            'failed' => 0,
            'items' => [],
        ];

        $itemsToProcess = [];

        // 1. Qamar.uz dan yig'ish
        if ($source === 'qamar_uz' || $source === 'all') {
            $qamarLimit = ($source === 'all' && $limit > 0) ? (int) ceil($limit / 2) : $limit;
            if ($logger) {
                $logger("qamar.uz sitemap o'qilmoqda...");
            }
            $urls = $this->qamarParser->discoverBookUrls($qamarLimit > 0 ? $qamarLimit * 2 : null);

            $collected = 0;
            foreach ($urls as $url) {
                if ($qamarLimit > 0 && $collected >= $qamarLimit) {
                    break;
                }
                $parsed = $this->qamarParser->parseBookPage($url);
                if ($parsed && ! empty($parsed['isbn13'])) {
                    $itemsToProcess[] = $parsed;
                    $collected++;
                }
            }
        }

        // 2. Book.uz dan yig'ish
        if ($source === 'book_uz' || $source === 'all') {
            $bookUzLimit = ($source === 'all' && $limit > 0) ? (int) floor($limit / 2) : $limit;
            if ($logger) {
                $logger("book.uz kitoblari tekshirilmoqda...");
            }

            // Avval keshdagi CatalogParserItem tekshiriladi
            $cachedItems = CatalogParserItem::query()
                ->where('provider', BookUzParserService::PROVIDER)
                ->whereNotNull('isbn')
                ->latest('id')
                ->when($bookUzLimit > 0, fn ($q) => $q->limit($bookUzLimit))
                ->get();

            if ($cachedItems->isNotEmpty()) {
                foreach ($cachedItems as $item) {
                    $isbn13 = Isbn::toIsbn13($item->isbn);
                    if ($isbn13) {
                        $itemsToProcess[] = [
                            'source' => 'book_uz',
                            'source_url' => $item->source_url,
                            'external_id' => $item->external_id,
                            'title' => $item->title,
                            'author' => $item->author,
                            'isbn' => $isbn13,
                            'isbn13' => $isbn13,
                            'isbn10' => Isbn::toIsbn10($isbn13),
                            'publisher' => $item->publisher,
                            'pages' => $item->pages,
                            'cover_type' => $item->cover_type,
                            'language' => $item->language,
                            'image_url' => $item->primary_image_url,
                            'description' => $item->description,
                            'price_uzs' => $item->price_uzs,
                        ];
                    }
                }
            }
        }

        // Agar umumiy limit belgilangan bo'lsa
        if ($limit > 0 && count($itemsToProcess) > $limit) {
            $itemsToProcess = array_slice($itemsToProcess, 0, $limit);
        }

        $results['total_scanned'] = count($itemsToProcess);

        // 3. Har bir kitobni qayta ishlash va DB'ga kiritish / yangilash
        foreach ($itemsToProcess as $idx => $item) {
            try {
                $syncRes = $this->syncSingleItem($item, $withImages);
                $action = $syncRes['action'] ?? 'skipped';

                if ($action === 'isbn_enriched') {
                    $results['isbn_enriched']++;
                } elseif ($action === 'edition_created') {
                    $results['editions_created']++;
                } elseif ($action === 'already_matched') {
                    $results['already_matched']++;
                } elseif ($action === 'skipped_no_isbn') {
                    $results['skipped_no_isbn']++;
                }

                if (count($results['items']) < 150) {
                    $results['items'][] = [
                        'title' => $item['title'] ?? '',
                        'isbn' => $item['isbn13'] ?? '',
                        'source' => $item['source'] ?? '',
                        'action' => $action,
                        'message' => $syncRes['message'] ?? '',
                    ];
                }

                if ($logger && ($idx + 1) % 10 === 0) {
                    $logger(($idx + 1) . " / " . count($itemsToProcess) . " kitob ko'rib chiqildi...");
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                Log::warning('ExternalCatalogSync xatosi: ' . $e->getMessage(), ['item' => $item]);
            }
        }

        return $results;
    }

    /**
     * Bitta kitobni sinxronlash (Yaratish yoki ISBN'ini to'ldirish).
     */
    public function syncSingleItem(array $item, bool $withImages = true): array
    {
        $rawIsbn = $item['isbn'] ?? ($item['isbn13'] ?? null);
        $isbn13 = Isbn::toIsbn13($rawIsbn);

        if (! $isbn13) {
            return [
                'action' => 'skipped_no_isbn',
                'message' => "To'g'ri ISBN topilmadi",
            ];
        }

        $isbn10 = Isbn::toIsbn10($isbn13);
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            return [
                'action' => 'skipped_empty_title',
                'message' => 'Nomi bo\'sh',
            ];
        }

        // 1. Bizning bazadan shu nomli kitobni qidiramiz
        $matchedEdition = $this->findEditionByTitle($title);

        if ($matchedEdition) {
            // Nomi bir xil kitob bizda mavjud!
            // Agar bizdagi kitobda ISBN yo'q bo'lsa yoki boshqacha bo'lsa:
            if (! $matchedEdition->isbn13 || $matchedEdition->isbn13 !== $isbn13) {
                $oldIsbn = $matchedEdition->isbn13;
                $matchedEdition->isbn13 = $isbn13;
                $matchedEdition->isbn10 = $isbn10;

                // Yetishmayotgan ma'lumotlarni ham to'ldiramiz
                if (empty($matchedEdition->description) && ! empty($item['description'])) {
                    $matchedEdition->description = $item['description'];
                }
                if (empty($matchedEdition->pages) && ! empty($item['pages'])) {
                    $matchedEdition->pages = (int) $item['pages'];
                }
                if (empty($matchedEdition->publisher_id) && ! empty($item['publisher'])) {
                    $matchedEdition->publisher_id = $this->resolvePublisherId($item['publisher']);
                }

                if ($withImages && empty($matchedEdition->front_image) && ! empty($item['image_url'])) {
                    $downloaded = $this->downloadAndSaveCover($item['image_url'], $title);
                    if ($downloaded) {
                        $matchedEdition->front_image = $downloaded;
                        $matchedEdition->images = array_values(array_unique(array_filter([$downloaded])));
                    }
                }

                $matchedEdition->save();

                // Unga ulangan barcha do'kon takliflaridagi ISBN'ni ham yangilaymiz
                Books::query()->where('edition_id', $matchedEdition->id)->update(['isbn' => $isbn13]);

                return [
                    'action' => 'isbn_enriched',
                    'edition_id' => $matchedEdition->id,
                    'title' => $matchedEdition->title,
                    'message' => "Mavjud kitob ISBN'i to'ldirildi: " . ($oldIsbn ?: "yo'q edi") . " → {$isbn13}",
                ];
            }

            // ISBN allaqachon bir xil, yetishmayotgan tavsif yoki sahifani boyitish
            $enriched = false;
            if (empty($matchedEdition->description) && ! empty($item['description'])) {
                $matchedEdition->description = $item['description'];
                $enriched = true;
            }
            if (empty($matchedEdition->pages) && ! empty($item['pages'])) {
                $matchedEdition->pages = (int) $item['pages'];
                $enriched = true;
            }
            if ($enriched) {
                $matchedEdition->save();
            }

            return [
                'action' => 'already_matched',
                'edition_id' => $matchedEdition->id,
                'title' => $matchedEdition->title,
                'message' => "Kitob va ISBN allaqachon mavjud",
            ];
        }

        // 2. Nomi bo'yicha topilmadi. Balki bu ISBN bo'yicha boshqa nom bilan bor-dir?
        $editionByIsbn = BookEdition::query()->usable()->where('isbn13', $isbn13)->first();
        if ($editionByIsbn) {
            return [
                'action' => 'already_matched',
                'edition_id' => $editionByIsbn->id,
                'title' => $editionByIsbn->title,
                'message' => "Ushbu ISBN bilan boshqa karta mavjud: {$editionByIsbn->title}",
            ];
        }

        // 3. Bizda bu kitob umuman yo'q — YANGI GLOBAL KARTA OCHAMIZ
        $author = $this->authorDirectory->resolveOrCreateByName($item['author'] ?? null);
        $publisherId = $this->resolvePublisherId($item['publisher'] ?? null);
        $categoryId = $this->resolveDefaultCategory();

        $imagePath = null;
        if ($withImages && ! empty($item['image_url'])) {
            $imagePath = $this->downloadAndSaveCover($item['image_url'], $title);
        }

        $coverType = CatalogService::canonCover($item['cover_type'] ?? null) ?: 'Yumshoq';
        $lang = CatalogService::canonLang($item['language'] ?? null) ?: "O'zbek";
        $langType = CatalogService::canonScript($item['script'] ?? null) ?: 'Lotin';

        $newEdition = BookEdition::create([
            'isbn13' => $isbn13,
            'isbn10' => $isbn10,
            'title' => $title,
            'author' => $author?->name ?: ($item['author'] ?? null),
            'author_id' => $author?->id,
            'publisher_id' => $publisherId,
            'category_id' => $categoryId,
            'lang' => $lang,
            'langType' => $langType,
            'coverType' => $coverType,
            'year' => ! empty($item['year']) ? (int) $item['year'] : null,
            'pages' => ! empty($item['pages']) ? (int) $item['pages'] : null,
            'description' => $item['description'] ?? null,
            'front_image' => $imagePath,
            'images' => $imagePath ? [$imagePath] : [],
            'status' => BookEdition::STATUS_ACTIVE,
            'source' => 'parser',
            'created_by_type' => 'admin',
            'verified_at' => now(),
            'match_key' => CatalogService::matchKey($title, $author?->name, $lang, $langType, $coverType, $publisherId),
        ]);

        // Agar books jadvalida shu nomdagi ulanmagan kitoblar bo'lsa, ularni darhol ulaymiz
        $unlinked = Books::query()
            ->whereNull('edition_id')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($title)])
            ->get();

        foreach ($unlinked as $unlinkedBook) {
            $unlinkedBook->forceFill([
                'edition_id' => $newEdition->id,
                'isbn' => $isbn13,
            ])->saveQuietly();
        }

        return [
            'action' => 'edition_created',
            'edition_id' => $newEdition->id,
            'title' => $newEdition->title,
            'message' => "Yangi kitob kartasi ochildi (ISBN: {$isbn13})",
        ];
    }

    /**
     * Nomi bo'yicha mavjud BookEdition'ni topish (aniq yoki o'xshash).
     */
    public function findEditionByTitle(string $title): ?BookEdition
    {
        $clean = trim($title);
        if ($clean === '') {
            return null;
        }

        // 1. To'g'ridan-to'g'ri bir xil nom
        $exact = BookEdition::query()
            ->usable()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($clean)])
            ->orderByRaw('CASE WHEN isbn13 IS NOT NULL THEN 0 ELSE 1 END')
            ->first();

        if ($exact) {
            return $exact;
        }

        // 2. Prefiks yoki LIKE orqali 20 ta nomzod olib, CatalogService::titlesSimilar bilan tekshirish
        $prefix = mb_substr($clean, 0, min(25, mb_strlen($clean)));
        $candidates = BookEdition::query()
            ->usable()
            ->where('title', 'like', $prefix . '%')
            ->limit(20)
            ->get();

        foreach ($candidates as $candidate) {
            if (CatalogService::titlesSimilar($candidate->title, $clean)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Nashriyot ID sini aniqlash yoki yaratish.
     */
    private function resolvePublisherId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $publisher = Publisher::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($publisher) {
            return (int) $publisher->id;
        }

        try {
            $created = Publisher::query()->create(['name' => $name]);
            return (int) $created->id;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Asosiy standart kategoriyani aniqlash.
     */
    private function resolveDefaultCategory(): int
    {
        return (int) (BookCategories::query()->value('id') ?? 1);
    }

    /**
     * Muqova rasmini yuklab olib, public storage'ga saqlash.
     */
    private function downloadAndSaveCover(string $imageUrl, string $title): ?string
    {
        try {
            $response = $this->http->get($imageUrl);
            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $body = (string) $response->getBody();
            if (strlen($body) < 500) {
                return null;
            }

            $extension = 'jpg';
            if (str_contains($imageUrl, '.png')) {
                $extension = 'png';
            } elseif (str_contains($imageUrl, '.webp')) {
                $extension = 'webp';
            }

            $filename = 'books/catalog/' . Str::slug(Str::limit($title, 30, '')) . '_' . Str::random(8) . '.' . $extension;
            Storage::disk('public')->put($filename, $body);

            // Varianlar yaratish (agar generator mavjud bo'lsa)
            try {
                ProductImageVariantGenerator::generateForPath($filename);
            } catch (\Throwable) {
                // E'tiborsiz qoldiriladi
            }

            return $filename;
        } catch (\Throwable $e) {
            Log::warning('Muqova rasmini yuklab olishda xatolik: ' . $imageUrl, ['error' => $e->getMessage()]);
            return null;
        }
    }
}
