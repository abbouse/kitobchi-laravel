<?php

namespace App\Services\CatalogParsers;

use App\Models\BookCategories;
use App\Models\BookEdition;
use App\Models\Books;
use App\Models\BookTag;
use App\Models\CatalogParserItem;
use App\Models\Publisher;
use App\Services\AuthorDirectoryService;
use App\Services\Catalog\CatalogService;
use App\Services\OpenAIService;
use App\Support\Isbn;
use App\Support\ProductImageVariantGenerator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
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
                            'category_raw' => $item->source_category ?? $item->suggested_category_name ?? null,
                            'suggested_category_id' => $item->suggested_category_id ? (int) $item->suggested_category_id : null,
                            'raw_tags' => data_get($item->payload, 'tags', []),
                            'suggested_tag_ids' => is_array($item->suggested_tag_ids) ? $item->suggested_tag_ids : null,
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
            if (empty($matchedEdition->category_id) || (int) $matchedEdition->category_id === 1) {
                $resolvedCatId = $this->resolveCategoryWithAi(
                    title: $matchedEdition->title,
                    author: $matchedEdition->author ?: ($item['author'] ?? null),
                    description: $matchedEdition->description ?: ($item['description'] ?? null),
                    rawCategory: $item['category_raw'] ?? ($item['category'] ?? null),
                    suggestedId: $item['suggested_category_id'] ?? null
                );
                if ($resolvedCatId > 0 && $resolvedCatId !== (int) $matchedEdition->category_id) {
                    $matchedEdition->category_id = $resolvedCatId;
                    $enriched = true;
                }
            }
            if (empty($matchedEdition->tag_ids)) {
                $tagIds = $this->resolveTagsWithAi(
                    title: $matchedEdition->title,
                    author: $matchedEdition->author ?: ($item['author'] ?? null),
                    description: $matchedEdition->description ?: ($item['description'] ?? null),
                    categoryId: (int) ($matchedEdition->category_id ?: 1),
                    rawTags: $item['raw_tags'] ?? ($item['tags'] ?? null),
                    suggestedTagIds: $item['suggested_tag_ids'] ?? null
                );
                if (! empty($tagIds)) {
                    $matchedEdition->tag_ids = $tagIds;
                    $enriched = true;
                }
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
        $categoryId = $this->resolveCategoryWithAi(
            title: $title,
            author: $author?->name ?: ($item['author'] ?? null),
            description: $item['description'] ?? null,
            rawCategory: $item['category_raw'] ?? ($item['category'] ?? null),
            suggestedId: $item['suggested_category_id'] ?? null
        );

        $tagIds = $this->resolveTagsWithAi(
            title: $title,
            author: $author?->name ?: ($item['author'] ?? null),
            description: $item['description'] ?? null,
            categoryId: $categoryId,
            rawTags: $item['raw_tags'] ?? ($item['tags'] ?? null),
            suggestedTagIds: $item['suggested_tag_ids'] ?? null
        );

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
            'tag_ids' => $tagIds,
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
                'category_id' => $categoryId,
            ])->saveQuietly();

            // Do'kon taklifi uchun book_tag_relations ni ham yangilaymiz
            if (! empty($tagIds)) {
                $now = now();
                $tagRows = array_map(fn ($tagId) => [
                    'book_id' => $unlinkedBook->id,
                    'tag_id' => (int) $tagId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $tagIds);
                DB::table('book_tag_relations')->insertOrIgnore($tagRows);
            }
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
     * Kitob uchun mos toifani AI va ko'p bosqichli leksik tahlil orqali aniqlash.
     *
     * Bosqichlar:
     * 1. Agar oldindan aniqlangan suggested_category_id mavjud va DBda bor bo'lsa -> shuni qaytaradi.
     * 2. Agar tashqi saytdan aniq kategoriya kelgan bo'lsa -> DBdagi toifalar bilan bevosita solishtiradi (tezkor heuristic).
     * 3. Cache'ni tekshiradi (md5(title|author|raw_category)) -> 30 kun saqlanadi.
     * 4. OpenAI gpt-4o-mini'ga kitob nomi, muallifi, tashqi toifasi va tavsifini yuborib,
     *    mavjud faol kategoriyalar orasidan yagona to'g'ri category_id ni tanlashni topshiradi.
     * 5. OpenAI ulanishi xato bersa yoki token tugasa -> kalit so'zlar bo'yicha aqlli fallback ishlaydi.
     */
    public function resolveCategoryWithAi(
        string $title,
        ?string $author = null,
        ?string $description = null,
        ?string $rawCategory = null,
        ?int $suggestedId = null
    ): int {
        $categories = BookCategories::query()
            ->where('is_active', true)
            ->get(['id', 'name_uz', 'name_ru', 'name_en', 'name_ja']);

        if ($categories->isEmpty()) {
            return 1;
        }

        // 1. Agar aniq ishonchli suggested ID berilgan bo'lsa
        if ($suggestedId && $categories->contains('id', $suggestedId)) {
            return (int) $suggestedId;
        }

        // 2. Tashqi toifa nomi bilan bevosita moslikni tekshirish
        $normalizedRaw = mb_strtolower(trim((string) $rawCategory));
        if ($normalizedRaw !== '') {
            foreach ($categories as $cat) {
                $names = array_filter([$cat->name_uz, $cat->name_ru, $cat->name_en]);
                foreach ($names as $name) {
                    $normName = mb_strtolower(trim($name));
                    if ($normName !== '' && ($normName === $normalizedRaw || str_contains($normalizedRaw, $normName) || str_contains($normName, $normalizedRaw))) {
                        return (int) $cat->id;
                    }
                }
            }
        }

        // 3. AI orqali kesh bilan aniqlash
        $cacheKey = 'catalog_cat_ai:' . md5(mb_strtolower(trim($title . '|' . ($author ?? '') . '|' . $normalizedRaw)));

        return (int) Cache::remember($cacheKey, now()->addDays(30), function () use ($title, $author, $description, $rawCategory, $categories) {
            try {
                if (! config('services.openai.key')) {
                    throw new \RuntimeException('OpenAI API key not configured.');
                }

                /** @var OpenAIService $ai */
                $ai = app(OpenAIService::class);

                $categoryList = $categories->map(fn ($category) => [
                    'id' => (int) $category->id,
                    'name_uz' => $category->name_uz,
                    'name_ru' => $category->name_ru,
                ])->values()->all();

                $prompt = json_encode([
                    'task' => 'Kitob uchun eng mos keluvchi yagona category_id ni tanlang.',
                    'book' => [
                        'title' => $title,
                        'author' => $author,
                        'external_category' => $rawCategory,
                        'description' => Str::limit(strip_tags((string) $description), 500, ''),
                    ],
                    'available_categories' => $categoryList,
                    'rules' => [
                        'Faqat available_categories ro\'yxatidagi "id" lardan birini tanlang.',
                        'Diniy, ma\'rifiy, islomiy kitoblar uchun tegishli diniy toifani tanlang.',
                        'Roman, qissa, she\'riyat, detektiv, fantastika uchun badiiy adabiyot toifasini tanlang.',
                        'Biznes, moliya, iqtisod, boshqaruv uchun biznes toifasini tanlang.',
                        'Psixologiya, o\'zini rivojlantirish, motivatsiya uchun psixologiya toifasini tanlang.',
                        'Bolalar ertaklari, bolalar kitoblari uchun bolalar adabiyotini tanlang.',
                        'Agar to\'g\'ridan-to\'g\'ri mos toifa bo\'lmasa, mavjudlari ichidan eng yaqin toifani tanlang.',
                        'Qat\'iy ravishda faqat toza JSON formatida javob bering.',
                    ],
                    'output_format' => [
                        'category_id' => 'int (mavjud id lardan biri)',
                        'category_name' => 'string',
                        'confidence' => 'float (0.0 - 1.0)',
                        'reason' => 'string',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $result = $ai->askJsonWithMessages([
                    [
                        'role' => 'system',
                        'content' => "Sen kitoblar marketpleysi katalogi uchun professional klassifikator AI san. Faqat toza JSON qaytar. Mavjud category_id lardan eng munosibini tanla.",
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ], 300, 0.1);

                $selectedId = (int) ($result['category_id'] ?? 0);
                if ($selectedId > 0 && $categories->contains('id', $selectedId)) {
                    Log::info("AI Category aniqlandi: [{$title}] -> ID {$selectedId} ({$result['category_name']})", [
                        'confidence' => $result['confidence'] ?? null,
                        'reason' => $result['reason'] ?? null,
                    ]);

                    return $selectedId;
                }
            } catch (\Throwable $e) {
                Log::warning('AI category resolution xatolik: ' . $e->getMessage());
            }

            // Fallback: Agar AI ishlamasa yoki xato bersa, leksik qoidalar
            return $this->resolveFallbackCategoryByKeywords($title, $description, $categories);
        });
    }

    /**
     * AI ishlamagan taqdirda kalit so'zlar bo'yicha eng yaqin toifani aniqlash.
     */
    private function resolveFallbackCategoryByKeywords(string $title, ?string $description, $categories): int
    {
        $text = mb_strtolower($title . ' ' . strip_tags((string) $description));

        $rules = [
            'diniy' => ['islom', 'qur\'on', 'quron', 'hadis', 'namoz', 'sahoba', 'zikr', 'aqida', 'fiqh', 'siyrat', 'olloh', 'payg\'ambar', 'tafseer', 'tafsir', 'duo', 'jannat', 'shariat'],
            'bolalar' => ['bolalar', 'ertak', 'alifbo', 'rangli', 'kichkintoy', 'she\'rlar bolalar'],
            'biznes' => ['biznes', 'moliya', 'iqtisod', 'marketing', 'menejment', 'startap', 'startup', 'investitsiya', 'pul', 'savdo', 'daromad', 'kapital', 'menejer'],
            'psixologiya' => ['psixologiya', 'shaxsiy rivojlanish', 'muvaffaqiyat', 'ong osti', 'odat', 'motivatsiya', 'baxt', 'tushkunlik', 'depressiya', 'emotsional'],
            'tarix' => ['tarix', 'sulola', 'imperiya', 'temur', 'bobur', 'sulton', 'jang', 'urush', 'davlat', 'xonlik', 'shajarasi'],
            'lug\'at' => ['lug\'at', 'dictionary', 'grammatika', 'til o\'rganish', 'grammar', 'ruscha', 'inglizcha'],
            'badiiy' => ['roman', 'qissa', 'hikoya', 'doston', 'she\'r', 'asar', 'detektiv', 'fantastika', 'novella', 'triller', 'afsona'],
        ];

        foreach ($rules as $catKeyword => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $matched = $categories->first(function ($c) use ($catKeyword) {
                        return str_contains(mb_strtolower($c->name_uz ?? ''), $catKeyword)
                            || str_contains(mb_strtolower($c->name_ru ?? ''), $catKeyword);
                    });
                    if ($matched) {
                        return (int) $matched->id;
                    }
                }
            }
        }

        // Badiiy toifasi mavjud bo'lsa shuni, aks holda birinchi toifani oladi
        $badiiy = $categories->first(fn ($c) => str_contains(mb_strtolower($c->name_uz ?? ''), 'badiiy'));

        return (int) ($badiiy?->id ?? $categories->first()?->id ?? 1);
    }

    /**
     * Mavjud kitob nashrlarining kategoriyalarini AI yordamida ommaviy aniqlash / qayta yangilash.
     */
    public function categorizeExistingEditions(int $limit = 50, bool $onlyUncategorized = true, ?callable $logger = null): array
    {
        @set_time_limit(0);

        $query = BookEdition::query()
            ->where('status', '!=', BookEdition::STATUS_MERGED)
            ->when($onlyUncategorized, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('category_id')->orWhere('category_id', 1);
                });
            })
            ->latest('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $editions = $query->get();
        $updated = 0;
        $failed = 0;

        foreach ($editions as $edition) {
            try {
                if ($logger) {
                    $logger("AI klassifikatsiya: {$edition->title}");
                }

                $newCatId = $this->resolveCategoryWithAi(
                    title: $edition->title,
                    author: $edition->author,
                    description: $edition->description
                );

                $newTagIds = $this->resolveTagsWithAi(
                    title: $edition->title,
                    author: $edition->author,
                    description: $edition->description,
                    categoryId: $newCatId ?: (int) $edition->category_id
                );

                $changed = false;
                if ($newCatId && (int) $edition->category_id !== $newCatId) {
                    $edition->category_id = $newCatId;
                    $changed = true;
                }
                if (! empty($newTagIds) && empty($edition->tag_ids)) {
                    $edition->tag_ids = $newTagIds;
                    $changed = true;
                }

                if ($changed) {
                    $edition->save();

                    // Bog'langan takliflarni ham sinxronlaymiz
                    try {
                        CatalogService::syncOffers($edition->id);
                    } catch (\Throwable) {
                        // ignore
                    }

                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("Editsiya kategoriyasini yangilashda xato: {$edition->id}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'total_scanned' => $editions->count(),
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * Kitob uchun mos teglarni AI va matn tahlili orqali aniqlash (0-5 ta teg).
     *
     * @param string $title
     * @param string|null $author
     * @param string|null $description
     * @param int $categoryId
     * @param array|null $rawTags
     * @param array|null $suggestedTagIds
     * @return array<int>
     */
    public function resolveTagsWithAi(
        string $title,
        ?string $author = null,
        ?string $description = null,
        int $categoryId = 1,
        ?array $rawTags = null,
        ?array $suggestedTagIds = null
    ): array {
        // 1. Shu kategoriyaga bog'langan yoki umumiy teglarni olamiz
        $categoryTags = BookTag::query()
            ->whereHas('categories', fn ($q) => $q->where('category_id', $categoryId))
            ->get(['id', 'tag_name_uz', 'tag_name_ru', 'tag_name_en']);

        $availableTags = $categoryTags->isNotEmpty()
            ? $categoryTags
            : BookTag::query()->get(['id', 'tag_name_uz', 'tag_name_ru', 'tag_name_en']);

        if ($availableTags->isEmpty()) {
            return [];
        }

        // 2. Agar oldindan aniqlangan suggested_tag_ids bo'lsa va ular mavjud bo'lsa
        if (! empty($suggestedTagIds) && is_array($suggestedTagIds)) {
            $validIds = $availableTags->pluck('id')->all();
            $filtered = array_values(array_intersect($suggestedTagIds, $validIds));
            if (! empty($filtered)) {
                return array_map('intval', array_slice($filtered, 0, 5));
            }
        }

        // 3. Tezkor Heuristic: Kitob matni yoki tashqi teglarda to'g'ridan-to'g'ri teg nomi bormi?
        $text = mb_strtolower($title . ' ' . strip_tags((string) $description) . ' ' . implode(' ', (array) $rawTags));
        $matchedTagIds = [];

        foreach ($availableTags as $tag) {
            $names = array_filter([$tag->tag_name_uz, $tag->tag_name_ru, $tag->tag_name_en]);
            foreach ($names as $name) {
                $norm = mb_strtolower(trim((string) $name));
                if (mb_strlen($norm) >= 3 && str_contains($text, $norm)) {
                    $matchedTagIds[] = (int) $tag->id;
                    break;
                }
            }
        }

        $matchedTagIds = array_values(array_unique($matchedTagIds));
        if (count($matchedTagIds) >= 2) {
            return array_slice($matchedTagIds, 0, 5);
        }

        // 4. AI orqali kesh bilan aniqlash
        $cacheKey = 'catalog_tags_ai:' . md5(mb_strtolower(trim($title . '|' . ($author ?? '') . '|' . $categoryId)));

        return (array) Cache::remember($cacheKey, now()->addDays(30), function () use ($title, $author, $description, $categoryId, $rawTags, $availableTags, $matchedTagIds) {
            try {
                if (! config('services.openai.key')) {
                    throw new \RuntimeException('OpenAI API key not configured.');
                }

                /** @var OpenAIService $ai */
                $ai = app(OpenAIService::class);

                $tagList = $availableTags->map(fn ($tag) => [
                    'id' => (int) $tag->id,
                    'name_uz' => $tag->tag_name_uz,
                    'name_ru' => $tag->tag_name_ru,
                ])->values()->all();

                $prompt = json_encode([
                    'task' => 'Kitob uchun berilgan teglardan eng mos keluvchi 1 tadan 5 tagacha teglarni tanlang.',
                    'book' => [
                        'title' => $title,
                        'author' => $author,
                        'category_id' => $categoryId,
                        'description' => Str::limit(strip_tags((string) $description), 500, ''),
                        'raw_tags' => array_slice((array) $rawTags, 0, 10),
                    ],
                    'available_tags' => $tagList,
                    'rules' => [
                        'Faqat available_tags ro\'yxatidagi "id" lardan tanlang.',
                        'Eng ko\'pi bilan 5 ta eng muhim tegni tanlang.',
                        'Agar hech biri mos kelmasa bo\'sh massiv [] qaytaring.',
                        'Format: {"tag_ids": [int, ...], "tag_names": [string, ...], "reason": string}',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $result = $ai->askJsonWithMessages([
                    [
                        'role' => 'system',
                        'content' => "Sen kitob katalogi uchun teglar klassifikatori AI san. Faqat toza JSON qaytar.",
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ], 300, 0.1);

                $selectedIds = (array) ($result['tag_ids'] ?? []);
                $validAvailableIds = $availableTags->pluck('id')->all();
                $finalIds = array_values(array_intersect($selectedIds, $validAvailableIds));

                if (! empty($finalIds)) {
                    Log::info("AI Tags aniqlandi: [{$title}] -> " . json_encode($finalIds));

                    return array_map('intval', array_slice($finalIds, 0, 5));
                }
            } catch (\Throwable $e) {
                Log::warning('AI tags resolution xatolik: ' . $e->getMessage());
            }

            return array_slice($matchedTagIds, 0, 5);
        });
    }

    /**
     * Asosiy standart kategoriyani aniqlash.
     */
    private function resolveDefaultCategory(): int
    {
        return (int) (BookCategories::query()->where('is_active', true)->value('id') ?? 1);
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
