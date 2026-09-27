<?php

namespace App\Services\CatalogParsers;

use App\Models\BookCategories;
use App\Models\Books;
use App\Models\BranchStock;
use App\Models\CatalogParserItem;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Services\BranchStockService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookUzStockSyncService
{
    public const SELLER_ID = 55;

    public const API_BASE = 'https://backend.book.uz/api/v1';

    // Book.uz dagi Qatortol va Chorsu filiallarining ichki storeId'lari
    public const STORE_ID_QATORTOL = '8cac779b-ab52-11ec-0a80-09ec0007a15e';

    public const STORE_ID_CHORSU = '9a503767-453b-11f0-0a80-0f9d0033e459';

    public const CACHE_PROGRESS_KEY = 'bookuz_stock_sync_progress';

    private Client $http;

    public function __construct(
        private readonly BranchStockService $branchStockService,
        private readonly CatalogService $catalogService,
    ) {
        $this->http = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Seller 55 kitoblari va qoldiq holati statistikasi.
     */
    public function getStats(): array
    {
        $sellerBooks = Books::query()->where('seller_id', self::SELLER_ID);
        $totalCount = (clone $sellerBooks)->count();

        return [
            'seller_id' => self::SELLER_ID,
            'total_books' => $totalCount,
            'active_books' => (clone $sellerBooks)->where('status', true)->count(),
            'progress' => Cache::get(self::CACHE_PROGRESS_KEY),
        ];
    }

    /**
     * Seller 55 ning Qatortol (description 111) va Chorsu (description 777) filiallarini aniqlash.
     *
     * @return array{qatortol: SellerLocation, chorsu: SellerLocation}
     */
    public function resolveSellerBranches(): array
    {
        $locations = SellerLocation::query()
            ->where('seller_id', self::SELLER_ID)
            ->where('is_deleted', false)
            ->orderBy('id')
            ->get();

        $qatortol = null;
        $chorsu = null;

        // 1. Foydalanuvchi ko'rsatgan qoida: description '111' bo'lsa Qatortol, '777' bo'lsa Chorsu
        foreach ($locations as $loc) {
            $desc = (string) $loc->description;
            if (str_contains($desc, '111')) {
                $qatortol = $loc;
            } elseif (str_contains($desc, '777')) {
                $chorsu = $loc;
            }
        }

        // 2. Nom va manzil orqali tekshirish (fallback)
        if (! $qatortol) {
            foreach ($locations as $loc) {
                $text = (string) ($loc->description.' '.$loc->fullAddress);
                if (stripos($text, 'qatortol') !== false || stripos($text, 'bosh do\'kon') !== false) {
                    $qatortol = $loc;
                    break;
                }
            }
        }

        if (! $chorsu) {
            foreach ($locations as $loc) {
                $text = (string) ($loc->description.' '.$loc->fullAddress);
                if (stripos($text, 'chorsu') !== false) {
                    $chorsu = $loc;
                    break;
                }
            }
        }

        // 3. Fallback: agar hanuz Qatortol topilmasa, asosiy (is_main) filialni olamiz yoki birinchisini
        if (! $qatortol) {
            $qatortol = $locations->firstWhere('is_main', true) ?? $locations->first();
        }

        // Agar bazada umuman filial bo'lmasa, yaratamiz
        if (! $qatortol) {
            $qatortol = SellerLocation::create([
                'seller_id' => self::SELLER_ID,
                'fullAddress' => 'Toshkent sh., Qatortol ko\'chasi, Book.uz bosh do\'koni',
                'description' => '111 - Toshkent - Qatortol - bosh do\'kon',
                'is_main' => true,
                'is_deleted' => false,
            ]);
        }

        // Agar Chorsu topilmasa, Qatortoldan boshqa ikkinchi filial bormi?
        if (! $chorsu) {
            $other = $locations->first(fn ($l) => $l->id !== $qatortol->id);
            if ($other) {
                $chorsu = $other;
            } else {
                $chorsu = SellerLocation::create([
                    'seller_id' => self::SELLER_ID,
                    'fullAddress' => 'Toshkent sh., Chorsu, Book.uz filiali',
                    'description' => '777 - Toshkent - Chorsu filial',
                    'is_main' => false,
                    'is_deleted' => false,
                ]);
            }
        }

        return [
            'qatortol' => $qatortol,
            'chorsu' => $chorsu,
        ];
    }

    /**
     * Book.uz mahsulot ob'ektidan Qatortol va Chorsu filiallari qoldiqlarini ajratib olish.
     * Saytdagi ko'rinishlar:
     * - "Toshkent - Qatortol - bosh do'kon X dona" (storeId: 8cac779b-ab52-11ec-0a80-09ec0007a15e)
     * - "Toshkent - Chorsu filial Y dona" (storeId: 9a503767-453b-11f0-0a80-0f9d0033e459)
     *
     * @return array{qatortol: int, chorsu: int, total: int}
     */
    public function extractBranchStocks(array $product): array
    {
        $qatortolStock = 0;
        $chorsuStock = 0;

        $branchStocks = $product['branchStocks'] ?? [];
        if (is_array($branchStocks)) {
            foreach ($branchStocks as $branch) {
                if (! is_array($branch)) {
                    continue;
                }

                $storeId = (string) ($branch['storeId'] ?? '');
                $storeName = (string) ($branch['storeName'] ?? '');
                $avail = isset($branch['available']) ? (int) $branch['available'] : (int) ($branch['quantity'] ?? 0);
                $avail = max(0, $avail);

                $isQatortol = $storeId === self::STORE_ID_QATORTOL
                    || stripos($storeName, 'qatortol') !== false
                    || stripos($storeName, 'bosh do\'kon') !== false;

                $isChorsu = $storeId === self::STORE_ID_CHORSU
                    || stripos($storeName, 'chorsu') !== false;

                if ($isQatortol) {
                    $qatortolStock = $avail;
                } elseif ($isChorsu) {
                    $chorsuStock = $avail;
                }
            }
        }

        return [
            'qatortol' => $qatortolStock,
            'chorsu' => $chorsuStock,
            'total' => $qatortolStock + $chorsuStock,
        ];
    }

    /**
     * Qatortol qoldig'i (backward compatibility).
     */
    public function extractQatortolStock(array $product): int
    {
        return $this->extractBranchStocks($product)['qatortol'];
    }

    /**
     * Chorsu qoldig'i.
     */
    public function extractChorsuStock(array $product): int
    {
        return $this->extractBranchStocks($product)['chorsu'];
    }

    /**
     * Book.uz backend API'sidan barcha yoki limitlangan mahsulotlarni yuklash.
     */
    public function fetchBookUzProducts(?int $limit = null, ?callable $logger = null, ?callable $onProgress = null): array
    {
        $products = [];
        $page = 1;
        $perPage = ($limit !== null && $limit > 0 && $limit < 100) ? $limit : 100;
        $totalRemote = null;

        while (true) {
            if ($logger) {
                $logger("Book.uz API: {$page}-sahifa so'ralmoqda...");
            }

            try {
                $response = $this->http->get(self::API_BASE.'/products', [
                    'query' => [
                        'page' => $page,
                        'limit' => $perPage,
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    Log::warning("[bookuz_stock_sync] HTTP {$response->getStatusCode()} on page {$page}");
                    break;
                }

                $json = json_decode((string) $response->getBody(), true);
                if (! is_array($json) || empty($json['data']['products'])) {
                    break;
                }

                $batch = $json['data']['products'];
                $totalRemote = (int) ($json['data']['pagination']['total'] ?? 0);

                foreach ($batch as $p) {
                    if (is_array($p)) {
                        $products[] = $p;
                        if ($limit !== null && $limit > 0 && count($products) >= $limit) {
                            break 2;
                        }
                    }
                }

                if ($onProgress) {
                    $onProgress(count($products), $totalRemote ?: count($products));
                }

                $totalPages = (int) ($json['data']['pagination']['pages'] ?? 1);
                if ($page >= $totalPages) {
                    break;
                }

                $page++;
                if ($page > 200) {
                    break;
                }

                usleep(50000); // 50ms yumshoq tanaffus
            } catch (\Throwable $e) {
                Log::error("[bookuz_stock_sync] Error fetching page {$page}: {$e->getMessage()}");
                if ($logger) {
                    $logger("Xatolik ({$page}-sahifa): {$e->getMessage()}");
                }
                break;
            }
        }

        return $products;
    }

    /**
     * Kitob nomi matnini qidiruv va solishtirish uchun normalizatsiya qilish.
     */
    public function normalizeTitle(mixed $title): string
    {
        if (is_array($title)) {
            $title = $title['uz'] ?? reset($title);
        }

        $str = Str::lower(trim((string) $title));
        $str = str_replace(['‘', '’', '`', 'ʻ', '\'', '"', '«', '»', '—', '-', '(', ')', '.', ',', '!', '?'], ' ', $str);

        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Book.uz katalogidan tezkor qidiruv indekslarini tuzish.
     */
    public function buildIndex(array $products): array
    {
        $byBarcode = [];
        $bySlug = [];
        $byTitle = [];
        $withStock = [];

        foreach ($products as $p) {
            $slug = trim((string) ($p['slug'] ?? ''));
            if ($slug !== '') {
                $bySlug[$slug] = $p;
            }

            $rawBarcode = trim((string) ($p['barcode'] ?? $p['isbn'] ?? ''));
            if ($rawBarcode !== '') {
                $clean = Isbn::clean($rawBarcode);
                if ($clean !== '') {
                    $byBarcode[$clean] = $p;
                }
                $isbn13 = Isbn::toIsbn13($rawBarcode);
                if ($isbn13) {
                    $byBarcode[$isbn13] = $p;
                }
            }

            $rawTitle = $p['title'] ?? null;
            $normTitle = $this->normalizeTitle($rawTitle);
            if ($normTitle !== '' && mb_strlen($normTitle) >= 3) {
                $byTitle[$normTitle] = $p;
            }

            $branchStocks = $this->extractBranchStocks($p);
            if ($branchStocks['total'] > 0) {
                $withStock[] = [
                    'product' => $p,
                    'qatortol' => $branchStocks['qatortol'],
                    'chorsu' => $branchStocks['chorsu'],
                    'total' => $branchStocks['total'],
                ];
            }
        }

        return [
            'by_barcode' => $byBarcode,
            'by_slug' => $bySlug,
            'by_title' => $byTitle,
            'with_stock' => $withStock,
            'with_qatortol' => $withStock, // backward-compat
        ];
    }

    /**
     * Seller 55 kitoblarini Book.uz saytidagi Qatortol (111) va Chorsu (777) filiallari qoldiqlari bilan sinxronlash.
     * Agar Book.uz da bor kitob bizda (Seller 55 da) bo'lmasa, uni qo'shib global kitobga (BookEdition) ulaydi.
     */
    public function syncSeller55Stock(
        ?int $limit = null,
        bool $importNew = true,
        bool $dryRun = false,
        ?callable $logger = null
    ): array {
        @set_time_limit(0);

        $startedAt = now();
        $branches = $this->resolveSellerBranches();
        $qatortolLoc = $branches['qatortol'];
        $chorsuLoc = $branches['chorsu'];

        $progressData = [
            'running' => true,
            'scanned' => 0,
            'total_target' => 0,
            'matched' => 0,
            'not_found' => 0,
            'in_stock' => 0,
            'zeroed' => 0,
            'stock_changed' => 0,
            'price_changed' => 0,
            'new_imported' => 0,
            'global_linked' => 0,
            'qatortol_stock_count' => 0,
            'chorsu_stock_count' => 0,
            'total_stock_count' => 0,
            'last_title' => 'Book.uz katalogi yuklanmoqda...',
            'started_at' => $startedAt->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);

        if ($logger) {
            $logger("Filiallar: Qatortol [ID: {$qatortolLoc->id}, desc: {$qatortolLoc->description}], Chorsu [ID: {$chorsuLoc->id}, desc: {$chorsuLoc->description}]");
            $logger("Book.uz mahsulotlari API dan olinmoqda...");
        }

        // 1. Book.uz dan mahsulotlarni yuklab olamiz
        $bookUzProducts = $this->fetchBookUzProducts(null, $logger, function ($current, $total) use (&$progressData) {
            $progressData['last_title'] = "Book.uz dan {$current}/{$total} ta mahsulot yuklandi...";
            $progressData['updated_at'] = now()->toIso8601String();
            Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);
        });

        if ($logger) {
            $logger("Book.uz dan jami ".count($bookUzProducts)." ta mahsulot yuklandi. Indeks tuzilmoqda...");
        }

        // 2. Qidiruv indekslarini tuzamiz
        $indices = $this->buildIndex($bookUzProducts);

        // 3. Seller 55 kitoblarini yuklaymiz
        $sellerBooksQuery = Books::query()
            ->where('seller_id', self::SELLER_ID)
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $sellerBooksQuery->limit($limit);
        }

        $sellerBooks = $sellerBooksQuery->get();
        $progressData['total_target'] = $sellerBooks->count();
        Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);

        // Parser items keshidan Book.uz slug xaritasini ham tayyorlaymiz
        $parserItemsMap = CatalogParserItem::query()
            ->where('provider', BookUzParserService::PROVIDER)
            ->whereNotNull('imported_book_id')
            ->pluck('source_url', 'imported_book_id')
            ->all();

        $updatedDetails = [];

        foreach ($sellerBooks as $index => $book) {
            $progressData['scanned']++;
            $progressData['last_title'] = Str::limit($book->name, 45);

            $matchedProduct = null;
            $matchMethod = null;

            // 1-usul: CatalogParserItem source_url orqali slug mosligi
            $sourceUrl = $parserItemsMap[$book->id] ?? null;
            if ($sourceUrl) {
                $slugCandidate = basename(parse_url($sourceUrl, PHP_URL_PATH) ?? '');
                if (isset($indices['by_slug'][$slugCandidate])) {
                    $matchedProduct = $indices['by_slug'][$slugCandidate];
                    $matchMethod = 'parser_slug';
                }
            }

            // 2-usul: ISBN / Barcode mosligi
            if (! $matchedProduct && filled($book->isbn)) {
                $cleanIsbn = Isbn::clean($book->isbn);
                if (isset($indices['by_barcode'][$cleanIsbn])) {
                    $matchedProduct = $indices['by_barcode'][$cleanIsbn];
                    $matchMethod = 'isbn';
                }
            }

            // 3-usul: Nomi bo'yicha moslik
            if (! $matchedProduct && filled($book->name)) {
                $normTitle = $this->normalizeTitle($book->name);
                if (isset($indices['by_title'][$normTitle])) {
                    $matchedProduct = $indices['by_title'][$normTitle];
                    $matchMethod = 'title';
                }
            }

            // Filiallar bo'yicha qoldiqni hisoblash
            if ($matchedProduct) {
                $progressData['matched']++;
                $bStocks = $this->extractBranchStocks($matchedProduct);
                $qatortolStock = $bStocks['qatortol'];
                $chorsuStock = $bStocks['chorsu'];
            } else {
                $progressData['not_found']++;
                $qatortolStock = 0;
                $chorsuStock = 0;
            }
            $newTotalStock = $qatortolStock + $chorsuStock;

            // Narxni tekshirish va yangilash (Book.uz dagi hozirgi narx)
            $remotePrice = (int) ($matchedProduct['price'] ?? 0);
            $remoteDiscountPrice = (int) ($matchedProduct['discountPrice'] ?? 0);
            $oldPrice = (int) ($book->price ?? 0);
            $oldDiscountPrice = (int) ($book->discountPrice ?? 0);
            $priceChanged = ($remotePrice > 0 && ($oldPrice !== $remotePrice || $oldDiscountPrice !== $remoteDiscountPrice));

            if ($priceChanged) {
                $progressData['price_changed']++;
            }

            // Hozirgi filiallardagi qoldiqlar
            $oldQatortol = (int) (BranchStock::query()
                ->where('product_type', 'book')
                ->where('product_id', $book->id)
                ->where('seller_location_id', $qatortolLoc->id)
                ->value('quantity') ?? 0);

            $oldChorsu = (int) (BranchStock::query()
                ->where('product_type', 'book')
                ->where('product_id', $book->id)
                ->where('seller_location_id', $chorsuLoc->id)
                ->value('quantity') ?? 0);

            $oldTotalStock = $oldQatortol + $oldChorsu;
            $stockChanged = ($oldQatortol !== $qatortolStock || $oldChorsu !== $chorsuStock);

            if ($newTotalStock > 0) {
                $progressData['in_stock']++;
                $progressData['qatortol_stock_count'] += $qatortolStock;
                $progressData['chorsu_stock_count'] += $chorsuStock;
                $progressData['total_stock_count'] += $newTotalStock;
            } else {
                $progressData['zeroed']++;
            }

            if ($stockChanged) {
                $progressData['stock_changed']++;
            }

            if (! $dryRun) {
                // 1. Qatortol filiali qoldig'ini o'rnatish
                if ($oldQatortol !== $qatortolStock) {
                    $this->branchStockService->setBranchQuantity(
                        'book',
                        (int) $book->id,
                        0,
                        self::SELLER_ID,
                        (int) $qatortolLoc->id,
                        $qatortolStock,
                        'bookuz_sync',
                        [
                            'actor_type' => 'system',
                            'note' => "Book.uz sync Qatortol (111): {$qatortolStock} dona (avval: {$oldQatortol})",
                        ]
                    );
                }

                // 2. Chorsu filiali qoldig'ini o'rnatish
                if ($oldChorsu !== $chorsuStock) {
                    $this->branchStockService->setBranchQuantity(
                        'book',
                        (int) $book->id,
                        0,
                        self::SELLER_ID,
                        (int) $chorsuLoc->id,
                        $chorsuStock,
                        'bookuz_sync',
                        [
                            'actor_type' => 'system',
                            'note' => "Book.uz sync Chorsu (777): {$chorsuStock} dona (avval: {$oldChorsu})",
                        ]
                    );
                }

                $bookUpdates = [];
                if ($priceChanged) {
                    $bookUpdates['price'] = $remotePrice;
                    $bookUpdates['discountPrice'] = $remoteDiscountPrice;
                }
                // Agar filiallarda qoldiq bo'lsa, kitobni faollashtiramiz
                if ($newTotalStock > 0 && (! $book->status || $book->is_hidden)) {
                    $bookUpdates['status'] = true;
                    $bookUpdates['is_hidden'] = false;
                }
                if (! empty($bookUpdates)) {
                    $book->update($bookUpdates);
                }

                // Agar global kitobga ulanmagan bo'lsa, ulab qo'yamiz
                if (empty($book->edition_id)) {
                    try {
                        $edition = $this->catalogService->linkOffer($book, 'import');
                        if ($edition) {
                            $progressData['global_linked']++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[bookuz_stock_sync] Failed to link existing book {$book->id} to edition: {$e->getMessage()}");
                    }
                }
            }

            if (($stockChanged || $priceChanged) && count($updatedDetails) < 50) {
                $updatedDetails[] = [
                    'id' => $book->id,
                    'name' => $book->name,
                    'isbn' => $book->isbn,
                    'old_stock' => $oldTotalStock,
                    'new_stock' => $newTotalStock,
                    'qatortol_stock' => $qatortolStock,
                    'chorsu_stock' => $chorsuStock,
                    'old_price' => $oldPrice,
                    'new_price' => $remotePrice > 0 ? $remotePrice : $oldPrice,
                    'match' => $matchMethod ?: 'not_found',
                    'global_linked' => ! empty($book->edition_id),
                ];
            }

            // Har 10 ta kitobda keshni yangilab turamiz
            if ($index % 10 === 0) {
                $progressData['updated_at'] = now()->toIso8601String();
                Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);
            }
        }

        // 4. Book.uz dagi filiallarida (Qatortol yoki Chorsu) qoldig'i bor yangi kitoblarni Seller 55 ga qo'shish va global kitobga ulash
        if ($importNew && ! $dryRun) {
            if ($logger) {
                $logger("Book.uz dagi filiallarida (Qatortol/Chorsu) qoldig'i bor yangi kitoblar tekshirilmoqda...");
            }

            $existingSellerIsbns = Books::query()
                ->where('seller_id', self::SELLER_ID)
                ->whereNotNull('isbn')
                ->pluck('isbn')
                ->map(fn ($i) => Isbn::clean($i))
                ->filter()
                ->flip()
                ->all();

            $existingSellerTitles = Books::query()
                ->where('seller_id', self::SELLER_ID)
                ->pluck('name')
                ->map(fn ($n) => $this->normalizeTitle($n))
                ->filter()
                ->flip()
                ->all();

            $defaultCategoryId = (int) (BookCategories::query()->where('is_active', true)->value('id') ?? 1);

            foreach ($indices['with_stock'] as $item) {
                $p = $item['product'];
                $qatortolStock = (int) $item['qatortol'];
                $chorsuStock = (int) $item['chorsu'];
                $totalStock = (int) $item['total'];

                if ($totalStock <= 0) {
                    continue;
                }

                $barcode = Isbn::clean((string) ($p['barcode'] ?? $p['isbn'] ?? ''));
                $normTitle = $this->normalizeTitle($p['title'] ?? null);

                // Agar sellerda allaqachon mavjud bo'lsa, o'tkazib yuboramiz
                if ($barcode !== '' && isset($existingSellerIsbns[$barcode])) {
                    continue;
                }
                if ($normTitle !== '' && isset($existingSellerTitles[$normTitle])) {
                    continue;
                }

                $title = is_array($p['title'] ?? null)
                    ? ($p['title']['uz'] ?? reset($p['title']))
                    : ($p['title'] ?? 'Nomsiz kitob');

                $author = is_array($p['author'] ?? null)
                    ? ($p['author'][0]['name'] ?? 'Nomaʼlum')
                    : ($p['authorName'] ?? 'Nomaʼlum');

                $desc = is_array($p['description'] ?? null)
                    ? ($p['description']['uz'] ?? '')
                    : ($p['description'] ?? '');

                $images = ! empty($p['image']) ? Arr::wrap($p['image']) : [];
                if (empty($images) && ! empty($p['images']) && is_array($p['images'])) {
                    $images = array_filter(Arr::flatten($p['images']));
                }

                try {
                    $newBook = Books::create([
                        'seller_id' => self::SELLER_ID,
                        'category_id' => $defaultCategoryId,
                        'name' => trim((string) $title),
                        'author' => trim((string) $author),
                        'isbn' => $barcode ?: null,
                        'price' => (int) ($p['price'] ?? 0),
                        'discountPrice' => (int) ($p['discountPrice'] ?? 0),
                        'images' => $images,
                        'year' => (int) ($p['year'] ?? 0),
                        'pages' => (int) ($p['numberOfPage'] ?? $p['pages'] ?? 0),
                        'lang' => 'uz',
                        'langType' => 'latin',
                        'coverType' => 'soft',
                        'status' => true,
                        'is_approved' => 1,
                        'is_hidden' => false,
                        'description' => $desc,
                    ]);

                    // Qatortol filial qoldig'i (111)
                    if ($qatortolStock > 0) {
                        $this->branchStockService->setBranchQuantity(
                            'book',
                            (int) $newBook->id,
                            0,
                            self::SELLER_ID,
                            (int) $qatortolLoc->id,
                            $qatortolStock,
                            'bookuz_sync',
                            [
                                'actor_type' => 'system',
                                'note' => "Book.uz yangi import - Qatortol (111): {$qatortolStock} dona",
                            ]
                        );
                    }

                    // Chorsu filial qoldig'i (777)
                    if ($chorsuStock > 0) {
                        $this->branchStockService->setBranchQuantity(
                            'book',
                            (int) $newBook->id,
                            0,
                            self::SELLER_ID,
                            (int) $chorsuLoc->id,
                            $chorsuStock,
                            'bookuz_sync',
                            [
                                'actor_type' => 'system',
                                'note' => "Book.uz yangi import - Chorsu (777): {$chorsuStock} dona",
                            ]
                        );
                    }

                    // Global kartaga ulaymiz (BookEdition)
                    try {
                        $edition = $this->catalogService->linkOffer($newBook, 'import');
                        if ($edition) {
                            $progressData['global_linked']++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[bookuz_stock_sync] Failed to link new book {$newBook->id} to edition: {$e->getMessage()}");
                    }

                    $progressData['new_imported']++;
                    $progressData['qatortol_stock_count'] += $qatortolStock;
                    $progressData['chorsu_stock_count'] += $chorsuStock;
                    $progressData['total_stock_count'] += $totalStock;

                    if ($barcode !== '') {
                        $existingSellerIsbns[$barcode] = true;
                    }
                    if ($normTitle !== '') {
                        $existingSellerTitles[$normTitle] = true;
                    }

                    if (count($updatedDetails) < 50) {
                        $updatedDetails[] = [
                            'id' => $newBook->id,
                            'name' => $newBook->name,
                            'isbn' => $newBook->isbn,
                            'old_stock' => 0,
                            'new_stock' => $totalStock,
                            'qatortol_stock' => $qatortolStock,
                            'chorsu_stock' => $chorsuStock,
                            'old_price' => 0,
                            'new_price' => (int) ($p['price'] ?? 0),
                            'match' => 'new_import',
                            'global_linked' => true,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning("[bookuz_stock_sync] Failed to import new book {$title}: {$e->getMessage()}");
                }
            }
        }

        $progressData['running'] = false;
        $progressData['last_title'] = 'Sinxronlash yakunlandi!';
        $progressData['completed_at'] = now()->toIso8601String();
        $progressData['updated_at'] = now()->toIso8601String();
        Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);

        if ($logger) {
            $logger("Sinxronlash tugadi. Jami: {$progressData['scanned']} ta tekshirildi, {$progressData['stock_changed']} ta qoldiq va {$progressData['price_changed']} ta narx yangilandi. Yangi qo'shildi: {$progressData['new_imported']}.");
        }

        return [
            'success' => true,
            'dry_run' => $dryRun,
            'scanned' => $progressData['scanned'],
            'matched' => $progressData['matched'],
            'not_found' => $progressData['not_found'],
            'in_stock' => $progressData['in_stock'],
            'zeroed' => $progressData['zeroed'],
            'stock_changed' => $progressData['stock_changed'],
            'price_changed' => $progressData['price_changed'],
            'new_imported' => $progressData['new_imported'],
            'global_linked' => $progressData['global_linked'],
            'qatortol_stock_count' => $progressData['qatortol_stock_count'],
            'chorsu_stock_count' => $progressData['chorsu_stock_count'],
            'total_stock_count' => $progressData['total_stock_count'],
            'updated_samples' => $updatedDetails,
            'duration_seconds' => now()->diffInSeconds($startedAt),
        ];
    }
}
