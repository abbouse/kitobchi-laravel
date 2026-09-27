<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Support\Isbn;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FixBrokenImages extends Command
{
    protected $signature = 'catalog:fix-broken-images
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--download : Rasmlarni tashqi serverga bog\'lanmasdan o\'zimizning local serverga (storage/books) yuklab olish}
                            {--dry-run : Bazaga yozmasdan faqat tekshiruv rejimida ishlash}';

    protected $description = 'Bloklangan Cloudinary (dd9xb0bqw) rasmlarini Book.uz kesh serveri yoki Google Books orqali tiklash';

    private const BROKEN_SUBSTRING = 'dd9xb0bqw';

    private const BOOKUZ_API = 'https://backend.book.uz/api/v1';

    private Client $http;

    public function __construct()
    {
        parent::__construct();
        $this->http = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
            ],
            'verify' => false,
        ]);
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $download = (bool) $this->option('download');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("=================================================================");
        $this->info("      BLOKLANGAN CLOUDINARY (dd9xb0bqw) RASMLARINI TIKLASH       ");
        $this->info("=================================================================");
        $this->line("Rejim: ".($dryRun ? '<fg=yellow>DRY-RUN (tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy tuzatish (bazadagi rasmlar yangilanadi)</>'));
        $this->line("Lokal saqlash: ".($download ? '<fg=cyan>Ha (rasmlar storage/books ga yuklab olinadi)</>' : '<fg=gray>Yo\'q (ishlaydigan Book.uz kesh havolasi saqlanadi)</>'));
        $this->newLine();

        $this->info("Bazada nosoz 'dd9xb0bqw' rasmli kitoblar qidirilmoqda...");

        $brokenBooksQuery = Books::query()
            ->where(function ($q) {
                $q->where('images', 'like', '%'.self::BROKEN_SUBSTRING.'%');
            })
            ->orderBy('id');

        if ($limit > 0) {
            $brokenBooksQuery->limit($limit);
        }

        $brokenBooks = $brokenBooksQuery->get();
        $this->line("Nosoz rasmli do'kon takliflari (Books): <comment>{$brokenBooks->count()} ta</comment>");

        $brokenEditionsQuery = BookEdition::query()
            ->where(function ($q) {
                $q->where('front_image', 'like', '%'.self::BROKEN_SUBSTRING.'%')
                    ->orWhere('images', 'like', '%'.self::BROKEN_SUBSTRING.'%');
            })
            ->orderBy('id');

        if ($limit > 0) {
            $brokenEditionsQuery->limit($limit);
        }

        $brokenEditions = $brokenEditionsQuery->get();
        $this->line("Nosoz rasmli global kartalar (BookEdition): <comment>{$brokenEditions->count()} ta</comment>");
        $this->newLine();

        $totalCount = $brokenBooks->count() + $brokenEditions->count();
        if ($totalCount === 0) {
            $this->info("✅ Bazada nosoz (dd9xb0bqw) rasmli kitoblar topilmadi!");

            return Command::SUCCESS;
        }

        $fixedBooks = 0;
        $fixedEditions = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        // 1. Books takliflarini tuzatish
        foreach ($brokenBooks as $book) {
            $rawImages = $book->getRawOriginal('images');
            $decoded = is_string($rawImages) ? json_decode($rawImages, true) : (array) $rawImages;
            $oldUrl = is_array($decoded) ? ($decoded[0] ?? null) : null;

            $workingUrl = null;
            if ($oldUrl && str_contains($oldUrl, self::BROKEN_SUBSTRING)) {
                $workingUrl = 'https://book.uz/_next/image?url='.urlencode($oldUrl).'&w=640&q=75';
            }

            if (! $workingUrl) {
                $workingUrl = $this->resolveWorkingImageUrl($book->name, $book->isbn);
            }

            if ($workingUrl) {
                if ($download && ! $dryRun) {
                    $workingUrl = $this->downloadToLocal($workingUrl, 'b_'.$book->id) ?: $workingUrl;
                }

                if (! $dryRun) {
                    $book->update([
                        'images' => Arr::wrap($workingUrl),
                    ]);
                }
                $fixedBooks++;
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        // 2. Global kartalarni (BookEdition) tuzatish
        foreach ($brokenEditions as $edition) {
            $oldUrl = $edition->getRawOriginal('front_image');
            if (! $oldUrl || ! str_contains($oldUrl, self::BROKEN_SUBSTRING)) {
                $rawImages = $edition->getRawOriginal('images');
                $decoded = is_string($rawImages) ? json_decode($rawImages, true) : (array) $rawImages;
                $oldUrl = is_array($decoded) ? ($decoded[0] ?? null) : null;
            }

            $workingUrl = null;
            if ($oldUrl && str_contains($oldUrl, self::BROKEN_SUBSTRING)) {
                $workingUrl = 'https://book.uz/_next/image?url='.urlencode($oldUrl).'&w=640&q=75';
            }

            if (! $workingUrl) {
                $workingUrl = $this->resolveWorkingImageUrl($edition->title, $edition->isbn13 ?: $edition->isbn10);
            }

            if ($workingUrl) {
                if ($download && ! $dryRun) {
                    $workingUrl = $this->downloadToLocal($workingUrl, 'ed_'.$edition->id) ?: $workingUrl;
                }

                if (! $dryRun) {
                    $edition->update([
                        'front_image' => $workingUrl,
                        'images' => Arr::wrap($workingUrl),
                    ]);
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

        $this->info("✅ Jarayon yakunlandi!");

        return Command::SUCCESS;
    }

    /**
     * Book.uz yoki Google Books orqali kitobning ishlaydigan yangi rasmini topish.
     */
    private function resolveWorkingImageUrl(string $title, ?string $rawIsbn): ?string
    {
        $cleanIsbn = $rawIsbn ? Isbn::clean($rawIsbn) : null;

        // 1. Book.uz API dan ISBN orqali qidiramiz
        if ($cleanIsbn) {
            $url = $this->searchBookUz($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        // 2. Book.uz API dan kitob nomi (keyword) bo'yicha qidiramiz
        if (mb_strlen(trim($title)) >= 3) {
            $url = $this->searchBookUz($title);
            if ($url) {
                return $url;
            }
        }

        // 3. Google Books API orqali ISBN bo'yicha qidiramiz
        if ($cleanIsbn) {
            $url = $this->searchGoogleBooks($cleanIsbn);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    private function searchBookUz(string $query): ?string
    {
        try {
            $response = $this->http->get(self::BOOKUZ_API.'/products', [
                'query' => [
                    'keyword' => $query,
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
                if (is_string($img) && filled($img) && ! str_contains($img, self::BROKEN_SUBSTRING)) {
                    return $img;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("[fix_broken_images] Book.uz search error: {$e->getMessage()}");
        }

        return null;
    }

    private function searchGoogleBooks(string $isbn): ?string
    {
        try {
            $response = $this->http->get('https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => 'isbn:'.$isbn,
                    'maxResults' => 1,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            $items = $data['items'] ?? [];

            if (! empty($items[0]['volumeInfo']['imageLinks']['thumbnail'])) {
                $thumb = (string) $items[0]['volumeInfo']['imageLinks']['thumbnail'];

                return str_replace('http://', 'https://', $thumb);
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    /**
     * Tashqi rasmni yuklab olib, storage/books/... ga saqlash.
     */
    private function downloadToLocal(string $url, string $prefix): ?string
    {
        try {
            $downloadUrl = $url;
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
            ];

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
            if ($body === '') {
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
