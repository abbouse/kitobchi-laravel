<?php

namespace App\Services\CatalogParsers;

use App\Models\Books;
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

    public const TARGET_STORE_ID = '8cac779b-ab52-11ec-0a80-09ec0007a15e';

    public const TARGET_STORE_KEYWORD = 'qatortol';

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
     * Book.uz mahsulot ob'ektidan Qatortol filiali qoldig'ini ajratib olish.
     * Saytdagi: "Toshkent - Qatortol - bosh do'kon X dona"
     */
    public function extractQatortolStock(array $product): int
    {
        $branchStocks = $product['branchStocks'] ?? [];
        if (! is_array($branchStocks)) {
            return 0;
        }

        foreach ($branchStocks as $branch) {
            if (! is_array($branch)) {
                continue;
            }

            $storeId = (string) ($branch['storeId'] ?? '');
            $storeName = (string) ($branch['storeName'] ?? '');

            $isQatortol = $storeId === self::TARGET_STORE_ID
                || stripos($storeName, self::TARGET_STORE_KEYWORD) !== false;

            if ($isQatortol) {
                // available miqdori yoki quantity
                $avail = isset($branch['available']) ? (int) $branch['available'] : (int) ($branch['quantity'] ?? 0);

                return max(0, $avail);
            }
        }

        return 0;
    }

    /**
     * Book.uz backend API'sidan barcha yoki limitlangan mahsulotlarni yuklash.
     * /api/v1/products har sahifada 100 tagacha mahsulot va ularning branchStocks'ini beradi.
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
        $withQatortol = [];

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

            $qatortolStock = $this->extractQatortolStock($p);
            if ($qatortolStock > 0) {
                $withQatortol[] = [
                    'product' => $p,
                    'stock' => $qatortolStock,
                ];
            }
        }

        return [
            'by_barcode' => $byBarcode,
            'by_slug' => $bySlug,
            'by_title' => $byTitle,
            'with_qatortol' => $withQatortol,
        ];
    }

    /**
     * Seller 55 uchun asosiy filial mavjudligini kafolatlash.
     */
    public function ensureSellerLocation(): int
    {
        $location = SellerLocation::query()
            ->where('seller_id', self::SELLER_ID)
            ->where('is_deleted', false)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first();

        if ($location) {
            return (int) $location->id;
        }

        $newLoc = SellerLocation::create([
            'seller_id' => self::SELLER_ID,
            'fullAddress' => 'Toshkent sh., Qatortol ko\'chasi, Book.uz bosh do\'koni',
            'description' => 'Qatortol - bosh do\'kon',
            'is_main' => true,
            'is_deleted' => false,
        ]);

        return (int) $newLoc->id;
    }

    /**
     * Seller 55 kitoblarini Book.uz saytining "Toshkent - Qatortol - bosh do'kon" qoldig'i bilan sinxronlash.
     */
    public function syncSeller55Stock(
        ?int $limit = null,
        bool $importNew = false,
        bool $dryRun = false,
        ?callable $logger = null
    ): array {
        @set_time_limit(0);

        $startedAt = now();
        $locationId = $this->ensureSellerLocation();

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
            'total_stock_count' => 0,
            'last_title' => 'Book.uz katalogi yuklanmoqda...',
            'started_at' => $startedAt->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);

        if ($logger) {
            $logger("Book.uz mahsulotlari API dan olinmoqda...");
        }

        // 1. Book.uz dan barcha mahsulotlarni yuklab olamiz
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

            // Qatortol qoldig'ini hisoblash
            if ($matchedProduct) {
                $progressData['matched']++;
                $qatortolStock = $this->extractQatortolStock($matchedProduct);
            } else {
                $progressData['not_found']++;
                // Saytda yoki filialda topilmagan kitob qoldig'i 0 ga tushadi
                $qatortolStock = 0;
            }

            // Narxni tekshirish va yangilash (Book.uz dagi hozirgi narx)
            $remotePrice = (int) ($matchedProduct['price'] ?? 0);
            $remoteDiscountPrice = (int) ($matchedProduct['discountPrice'] ?? 0);
            $oldPrice = (int) ($book->price ?? 0);
            $oldDiscountPrice = (int) ($book->discountPrice ?? 0);
            $priceChanged = ($remotePrice > 0 && ($oldPrice !== $remotePrice || $oldDiscountPrice !== $remoteDiscountPrice));

            if ($priceChanged) {
                $progressData['price_changed']++;
            }

            // Hozirgi mavjud qoldiq
            $oldStock = $this->branchStockService->totalAvailable('book', (int) $book->id);
            $stockChanged = ($oldStock !== $qatortolStock);

            if ($qatortolStock > 0) {
                $progressData['in_stock']++;
                $progressData['total_stock_count'] += $qatortolStock;
            } else {
                $progressData['zeroed']++;
            }

            if ($stockChanged) {
                $progressData['stock_changed']++;
            }

            if (! $dryRun) {
                if ($stockChanged) {
                    $this->branchStockService->setTotalFromLegacy(
                        'book',
                        (int) $book->id,
                        0,
                        self::SELLER_ID,
                        $qatortolStock,
                        $locationId,
                        [
                            'actor_type' => 'system',
                            'note' => "Book.uz Qatortol sync: {$qatortolStock} dona (avval: {$oldStock})",
                        ]
                    );
                }

                $bookUpdates = [];
                if ($priceChanged) {
                    $bookUpdates['price'] = $remotePrice;
                    $bookUpdates['discountPrice'] = $remoteDiscountPrice;
                }
                // Agar kitobda qoldiq paydo bo'lsa, statusini faollashtiramiz
                if ($qatortolStock > 0 && (! $book->status || $book->is_hidden)) {
                    $bookUpdates['status'] = true;
                    $bookUpdates['is_hidden'] = false;
                }
                if (! empty($bookUpdates)) {
                    $book->update($bookUpdates);
                }
            }

            if (($stockChanged || $priceChanged) && count($updatedDetails) < 50) {
                $updatedDetails[] = [
                    'id' => $book->id,
                    'name' => $book->name,
                    'isbn' => $book->isbn,
                    'old_stock' => $oldStock,
                    'new_stock' => $qatortolStock,
                    'old_price' => $oldPrice,
                    'new_price' => $remotePrice > 0 ? $remotePrice : $oldPrice,
                    'match' => $matchMethod ?: 'not_found',
                ];
            }

            // Har 10 ta kitobda keshni yangilab turamiz
            if ($index % 10 === 0) {
                $progressData['updated_at'] = now()->toIso8601String();
                Cache::put(self::CACHE_PROGRESS_KEY, $progressData, 7200);
            }
        }

        // 4. Qo'shimcha rejim: Book.uz dagi Qatortol qoldig'i bor yangi kitoblarni Seller 55 ga import qilish
        if ($importNew && ! $dryRun) {
            if ($logger) {
                $logger("Qatortol filiali mavjud yangi kitoblar tekshirilmoqda...");
            }

            $existingSellerIsbns = Books::query()
                ->where('seller_id', self::SELLER_ID)
                ->whereNotNull('isbn')
                ->pluck('isbn')
                ->map(fn ($i) => Isbn::clean($i))
                ->filter()
                ->flip()
                ->all();

            foreach ($indices['with_qatortol'] as $item) {
                $p = $item['product'];
                $stock = $item['stock'];

                $barcode = Isbn::clean((string) ($p['barcode'] ?? $p['isbn'] ?? ''));
                if ($barcode !== '' && isset($existingSellerIsbns[$barcode])) {
                    continue; // Sellerda allaqachon bor
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

                try {
                    $newBook = Books::create([
                        'seller_id' => self::SELLER_ID,
                        'name' => $title,
                        'author' => $author,
                        'isbn' => $barcode ?: null,
                        'price' => (int) ($p['price'] ?? 0),
                        'discountPrice' => (int) ($p['discountPrice'] ?? 0),
                        'images' => ! empty($p['image']) ? Arr::wrap($p['image']) : [],
                        'year' => (int) ($p['year'] ?? 0),
                        'pages' => (int) ($p['numberOfPage'] ?? 0),
                        'status' => true,
                        'is_approved' => 1,
                        'is_hidden' => false,
                        'description' => $desc,
                    ]);

                    $this->branchStockService->setTotalFromLegacy(
                        'book',
                        (int) $newBook->id,
                        0,
                        self::SELLER_ID,
                        $stock,
                        $locationId,
                        [
                            'actor_type' => 'system',
                            'note' => "Book.uz Qatortol yangi import: {$stock} dona",
                        ]
                    );

                    try {
                        $this->catalogService->linkOffer($newBook, 'import');
                    } catch (\Throwable) {
                        // ignore catalog link error
                    }

                    $progressData['new_imported']++;
                    $progressData['total_stock_count'] += $stock;
                    if ($barcode !== '') {
                        $existingSellerIsbns[$barcode] = true;
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
            $logger("Sinxronlash tugadi. Jami: {$progressData['scanned']} ta tekshirildi, {$progressData['stock_changed']} tasining qoldig'i yangilandi.");
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
            'total_stock_count' => $progressData['total_stock_count'],
            'updated_samples' => $updatedDetails,
            'duration_seconds' => now()->diffInSeconds($startedAt),
        ];
    }
}
