<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FixBrokenImages extends Command
{
    protected $signature = 'catalog:fix-broken-images
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--download : Rasmlarni tashqi serverga bog\'lanmasdan o\'zimizning local serverga (storage/books) yuklab olish}
                            {--all-duplicated : 2 va undan ko\'p kitobda bir xil takrorlanib qolgan rasmli kitoblarni ham qayta qidirib to\'g\'irlash}
                            {--duplicate-substring= : Takrorlanib qolgan rasm havolasining bir qismi (masalan: 1790422172657)}
                            {--dry-run : Bazaga yozmasdan faqat tekshiruv rejimida ishlash}';

    protected $description = 'Nosoz Cloudinary (dd9xb0bqw) yoki noto\'g\'ri takrorlangan kitob rasmlarini Book.uz kesh serveri yoki API orqali tiklash';

    private const BROKEN_SUBSTRING = 'dd9xb0bqw';

    private const BOOKUZ_API = 'https://backend.book.uz/api/v1';

    private Client $http;

    private CatalogService $catalogService;

    public function __construct(CatalogService $catalogService)
    {
        parent::__construct();
        $this->catalogService = $catalogService;
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

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $download = (bool) $this->option('download');
        $dryRun = (bool) $this->option('dry-run');
        $allDuplicated = (bool) $this->option('all-duplicated');
        $dupSubstring = trim((string) $this->option('duplicate-substring'));

        $this->info('=================================================================');
        $this->info('           KITOB RASMLARINI TIKLASH VA TO\'G\'IRLASH               ');
        $this->info('=================================================================');
        $this->line('Rejim: '.($dryRun ? '<fg=yellow>DRY-RUN (tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy tuzatish (bazadagi rasmlar yangilanadi)</>'));
        $this->line('Lokal saqlash: '.($download ? '<fg=cyan>Ha (rasmlar storage/books ga yuklab olinadi)</>' : '<fg=gray>Yo\'q (ishlaydigan Book.uz kesh havolasi saqlanadi)</>'));
        if ($allDuplicated) {
            $this->line('Takrorlangan rasmlar: <fg=yellow>Barcha dublikat rasmli kitoblar qayta qidiriladi</>');
        }
        if ($dupSubstring !== '') {
            $this->line("Filtr belgisi: <fg=yellow>{$dupSubstring}</>");
        }
        $this->newLine();

        // 1. Qidiriladigan Books takliflarini aniqlaymiz
        $dupBookIds = collect();
        if ($allDuplicated) {
            $dupBookIds = DB::table('books')
                ->whereIn('images', function ($sub) {
                    $sub->select('images')
                        ->from('books')
                        ->whereNotNull('images')
                        ->groupBy('images')
                        ->havingRaw('count(*) > 1');
                })
                ->pluck('id');
        }

        $brokenBooksQuery = Books::query()
            ->where(function ($q) use ($dupSubstring, $dupBookIds) {
                $q->where('images', 'like', '%'.self::BROKEN_SUBSTRING.'%');

                if ($dupSubstring !== '') {
                    $q->orWhere('images', 'like', '%'.$dupSubstring.'%');
                }

                if ($dupBookIds->isNotEmpty()) {
                    $q->orWhereIn('id', $dupBookIds);
                }
            })
            ->orderBy('id');

        if ($limit > 0) {
            $brokenBooksQuery->limit($limit);
        }

        $brokenBooks = $brokenBooksQuery->get();
        $this->line("Tuzatilishi kerak bo'lgan do'kon takliflari (Books): <comment>{$brokenBooks->count()} ta</comment>");

        // 2. Qidiriladigan BookEdition global kartalarini aniqlaymiz (agar jadval mavjud bo'lsa)
        $brokenEditions = collect();
        if (Schema::hasTable('book_editions')) {
            $dupEditionIds = collect();
            if ($allDuplicated) {
                $dupEditionIds = DB::table('book_editions')
                    ->whereNull('deleted_at')
                    ->whereIn('front_image', function ($sub) {
                        $sub->select('front_image')
                            ->from('book_editions')
                            ->whereNull('deleted_at')
                            ->whereNotNull('front_image')
                            ->groupBy('front_image')
                            ->havingRaw('count(*) > 1');
                    })
                    ->pluck('id');
            }

            $brokenEditionsQuery = BookEdition::query()
                ->where(function ($q) use ($dupSubstring, $dupEditionIds) {
                    $q->where('front_image', 'like', '%'.self::BROKEN_SUBSTRING.'%')
                        ->orWhere('images', 'like', '%'.self::BROKEN_SUBSTRING.'%');

                    if ($dupSubstring !== '') {
                        $q->orWhere('front_image', 'like', '%'.$dupSubstring.'%')
                            ->orWhere('images', 'like', '%'.$dupSubstring.'%');
                    }

                    if ($dupEditionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $dupEditionIds);
                    }
                })
                ->orderBy('id');

            if ($limit > 0) {
                $brokenEditionsQuery->limit($limit);
            }

            $brokenEditions = $brokenEditionsQuery->get();
        }
        $this->line("Tuzatilishi kerak bo'lgan global kartalar (BookEdition): <comment>{$brokenEditions->count()} ta</comment>");
        $this->newLine();

        $totalCount = $brokenBooks->count() + $brokenEditions->count();
        if ($totalCount === 0) {
            $this->info("✅ Bazada nosoz yoki takrorlangan rasmli kitoblar topilmadi!");

            return Command::SUCCESS;
        }

        $fixedBooks = 0;
        $fixedEditions = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        // 1. Books takliflarini tuzatish
        foreach ($brokenBooks as $book) {
            $workingUrl = $this->resolveBookCover($book->name, $book->isbn, $book->getRawOriginal('images'));

            if ($workingUrl) {
                if ($download && ! $dryRun) {
                    $workingUrl = $this->downloadToLocal($workingUrl, 'b_'.$book->id) ?: $workingUrl;
                }

                if (! $dryRun) {
                    Books::writingFromCatalog(function () use ($book, $workingUrl) {
                        $book->update([
                            'images' => Arr::wrap($workingUrl),
                        ]);
                    });

                    // Agar global kartaga ulangan bo'lsa, uni ham sinxronlaymiz
                    if ($book->edition_id && $book->edition) {
                        $edition = $book->edition;
                        if (empty($edition->front_image) || str_contains((string) $edition->front_image, self::BROKEN_SUBSTRING) || ($dupSubstring && str_contains((string) $edition->front_image, $dupSubstring))) {
                            $edition->update([
                                'front_image' => $workingUrl,
                                'images' => Arr::wrap($workingUrl),
                            ]);
                            $this->catalogService->syncOffers($edition);
                        }
                    }
                }
                $fixedBooks++;
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        // 2. Global kartalarni (BookEdition) tuzatish
        foreach ($brokenEditions as $edition) {
            $rawFront = $edition->getRawOriginal('front_image');
            $rawImages = $edition->getRawOriginal('images');
            $workingUrl = $this->resolveBookCover($edition->title, $edition->isbn13 ?: $edition->isbn10, $rawFront ?: $rawImages);

            if ($workingUrl) {
                if ($download && ! $dryRun) {
                    $workingUrl = $this->downloadToLocal($workingUrl, 'ed_'.$edition->id) ?: $workingUrl;
                }

                if (! $dryRun) {
                    $edition->update([
                        'front_image' => $workingUrl,
                        'images' => Arr::wrap($workingUrl),
                    ]);
                    $this->catalogService->syncOffers($edition);
                }
                $fixedEditions++;
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Ko\'rsatkich', 'Soni'],
            [
                ['Tuzatilgan kitoblar (Books)', $fixedBooks],
                ['Tuzatilgan global kartalar (BookEdition)', $fixedEditions],
                ['Yangi rasmi topilmaganlar', $notFound],
            ]
        );

        $this->info('✅ Jarayon muvaffaqiyatli yakunlandi!');

        return Command::SUCCESS;
    }

    /**
     * Kitob uchun mos va ishlaydigan rasm havolasini topish:
     * 1. Agar avvalgi asl dd9xb0bqw havolasi bo'lsa, Book.uz kesh serveridan to'g'ridan-to'g'ri olamiz.
     * 2. Aks holda Book.uz API dan keyword (ISBN yoki aniq sarlavha) orqali izlaymiz va nomini tekshiramiz.
     * 3. Google Books orqali ISBN bo'yicha izlaymiz.
     */
    private function resolveBookCover(string $title, ?string $rawIsbn, mixed $rawStoredImage): ?string
    {
        // 1. Agar kitobning avvalgi asl rasmi saqlanib qolgan bo'lsa:
        $originalCloudinaryUrl = $this->extractCloudinaryUrl($rawStoredImage);
        if ($originalCloudinaryUrl) {
            $cachedProxyUrl = 'https://book.uz/_next/image?url='.urlencode($originalCloudinaryUrl).'&w=640&q=75';

            // Tekshirib ko'ramiz (keshda bormi)
            if ($this->verifyUrlWorks($cachedProxyUrl)) {
                return $cachedProxyUrl;
            }
        }

        // 2. Book.uz API orqali qidiramiz (aniq nom va ISBN bilan)
        $cleanIsbn = $rawIsbn ? Isbn::clean($rawIsbn) : null;
        if ($cleanIsbn) {
            $url = $this->searchBookUzByIsbn($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        if (mb_strlen(trim($title)) >= 3) {
            $url = $this->searchBookUzByTitle($title);
            if ($url) {
                return $url;
            }
        }

        // 3. Google Books API orqali qidiramiz
        if ($cleanIsbn) {
            $url = $this->searchGoogleBooks($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Saqlangan ma'lumotdan asl Cloudinary manzilini ajratib olish.
     */
    private function extractCloudinaryUrl(mixed $raw): ?string
    {
        if (is_array($raw)) {
            $first = $raw[0] ?? null;

            return is_string($first) ? $this->extractCloudinaryUrl($first) : null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->extractCloudinaryUrl($decoded);
            }
        }

        // Agar Next.js image url ko'rinishida bo'lsa
        if (str_contains($raw, 'book.uz/_next/image') && str_contains($raw, 'url=')) {
            parse_str(parse_url($raw, PHP_URL_QUERY) ?: '', $params);
            if (! empty($params['url']) && str_contains($params['url'], self::BROKEN_SUBSTRING)) {
                return $params['url'];
            }
        }

        // To'g'ridan-to'g'ri dd9xb0bqw bo'lsa
        if (str_contains($raw, self::BROKEN_SUBSTRING) && str_starts_with($raw, 'http')) {
            return $raw;
        }

        return null;
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
            Log::debug("[fix_broken_images] Book.uz ISBN search error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Book.uz API dan kitob nomi orqali qidirish va nom mosligini qat'iy tekshirish.
     */
    private function searchBookUzByTitle(string $expectedTitle): ?string
    {
        $cleanSearch = $this->cleanTitle($expectedTitle);
        if ($cleanSearch === '') {
            return null;
        }

        try {
            $response = $this->http->get(self::BOOKUZ_API.'/products', [
                'query' => [
                    'keyword' => $cleanSearch,
                    'limit' => 5,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            $products = $data['data']['products'] ?? [];

            foreach ($products as $p) {
                $rawTitle = $p['title'] ?? null;
                $foundTitle = is_array($rawTitle) ? ($rawTitle['uz'] ?? reset($rawTitle)) : (string) $rawTitle;

                // Nom mosligini qat'iy tekshiramiz — noto'g'ri kitob olinmasin!
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
            Log::debug("[fix_broken_images] Book.uz Title search error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Topilgan rasm manzilini ishlaydigan formatga keltirish.
     */
    private function formatRemoteImage(string $url): string
    {
        // Agar eski dd9xb0bqw bo'lsa, Book.uz ning kesh proksisiga o'giramiz
        if (str_contains($url, self::BROKEN_SUBSTRING) && ! str_contains($url, 'book.uz/_next/image')) {
            return 'https://book.uz/_next/image?url='.urlencode($url).'&w=640&q=75';
        }

        return $url;
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
     * Ikki kitob nomining o'zaro mosligini hisoblash.
     */
    public function isTitleMatch(string $expectedTitle, string $actualTitle): bool
    {
        $c1 = $this->cleanTitle($expectedTitle);
        $c2 = $this->cleanTitle($actualTitle);

        if ($c1 === '' || $c2 === '') {
            return false;
        }

        if ($c1 === $c2 || str_contains($c2, $c1) || str_contains($c1, $c2)) {
            return true;
        }

        similar_text($c1, $c2, $percent);
        if ($percent >= 55.0) {
            return true;
        }

        // So'zlar kesishmasi (kamida 2 ta asosiy so'z bir xil bo'lishi kerak)
        $w1 = array_values(array_filter(explode(' ', $c1), fn ($w) => mb_strlen($w) >= 3));
        $w2 = array_values(array_filter(explode(' ', $c2), fn ($w) => mb_strlen($w) >= 3));
        if (count($w1) > 0 && count($w2) > 0) {
            $intersect = array_intersect($w1, $w2);
            if (count($intersect) >= max(2, (int) round(count($w1) * 0.6))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kitob nomini taqqoslash uchun tozalash.
     */
    private function cleanTitle(string $title): string
    {
        $title = mb_strtolower(trim($title));
        $title = preg_replace('/\((lotin|kirill?|ruscha|o[\'’`]?zbekcha|yumshoq|qattiq)[^\)]*\)/u', '', $title);
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);

        return trim(preg_replace('/\s+/', ' ', $title));
    }

    /**
     * Havola haqiqatan ham 200 OK qaytarishini tekshirish.
     */
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

    /**
     * Tashqi rasmni yuklab olib, o'zimizning storage/books/... ga saqlash.
     */
    private function downloadToLocal(string $url, string $prefix): ?string
    {
        try {
            $downloadUrl = $url;
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ];

            if (str_contains($url, 'book.uz')) {
                $headers['Referer'] = 'https://book.uz/';
            }

            if (str_contains($url, self::BROKEN_SUBSTRING) && ! str_contains($url, 'book.uz/_next/image')) {
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

            return $filename;
        } catch (\Throwable $e) {
            Log::warning("[fix_broken_images] Download failed for {$url}: {$e->getMessage()}");

            return null;
        }
    }
}
