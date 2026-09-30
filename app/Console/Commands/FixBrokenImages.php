<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\Catalog\BookCoverResolverService;
use App\Services\Catalog\CatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FixBrokenImages extends Command
{
    protected $signature = 'catalog:fix-broken-images
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--download : Rasmlarni tashqi serverga bog\'lanmasdan o\'zimizning local serverga (storage/books) yuklab olish}
                            {--all : Barcha muammoli (nosoz, bir xil takrorlangan va muqovasiz) kitoblarni birvarakayiga to\'g\'irlash (standart: ha)}
                            {--fixed : Ilgari xato yuklangan (books/fixed_...) rasmli kitoblarni ham tozalab qayta qidirish}
                            {--missing : Faqat rasmi umuman yo\'q (bo\'sh / muqovasiz) kitoblarni qidirib rasm o\'rnatish}
                            {--all-duplicated : 2 va undan ko\'p kitobda bir xil takrorlanib qolgan rasmli kitoblarni ham qayta qidirib to\'g\'irlash}
                            {--duplicate-substring= : Takrorlanib qolgan rasm havolasining bir qismi (masalan: 1790422172657)}
                            {--latest-first : Yangi kitoblardan boshlab (id DESC) tekshirish (standart: ha)}
                            {--oldest-first : Eski kitoblardan boshlab (id ASC) tekshirish}
                            {--resume : Avval tekshirilgan kitoblarni qayta ko\'rmasdan, to\'xtagan joyidan davom ettirish}
                            {--no-resume : Avval tekshirilgan xotirani chetlab o\'tib, barcha kitoblarni qaytadan ko\'rib chiqish}
                            {--clear-checkpoint : Oldingi sessiya xotirasini (checkpoint) tozalab, noldan boshlash}
                            {--dry-run : Bazaga yozmasdan faqat tekshiruv rejimida ishlash}';

    protected $description = 'Nosoz Cloudinary, noto\'g\'ri takrorlangan yoki muqovasiz kitob rasmlarini Book.uz, Asaxiy yoki Google Books orqali tiklash';

    private const KNOWN_BAD_PATTERNS = [
        'dd9xb0bqw',
        '1790422172657',
        'Screenshot_2026_09_26_072402',
    ];

    private const CHECKPOINT_FILE = 'fix_images_checkpoint.json';

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly BookCoverResolverService $resolver,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        DB::disableQueryLog();

        $limit = (int) $this->option('limit');
        $download = (bool) $this->option('download');
        $dryRun = (bool) $this->option('dry-run');
        $all = (bool) $this->option('all');
        $fixed = (bool) $this->option('fixed');
        $missing = (bool) $this->option('missing');
        $allDuplicated = (bool) $this->option('all-duplicated');
        $dupSubstring = trim((string) $this->option('duplicate-substring'));
        $oldestFirst = (bool) $this->option('oldest-first');
        $sortOrder = $oldestFirst ? 'asc' : 'desc';

        // Standart rejimda: faqat haqiqatdan muammoli kitoblar (rasmi yo'q, diskda yo'q yoki nosoz patternli) tekshiriladi.
        // Ishlayotgan, to'g'irlangan (fixed_) rasmlar umuman qayta tekshirilmaydi!
        if (! $allDuplicated && empty($dupSubstring)) {
            $missing = true;
        }

        if ($this->option('clear-checkpoint')) {
            $this->clearCheckpoint();
            $checkpoint = ['editions' => [], 'books' => []];
            $this->info("🧹 Oldingi tekshiruv xotirasi (checkpoint) tozalandi.");
        } else {
            $checkpoint = $this->loadCheckpoint();
        }

        // Faqat foydalanuvchi ataylab --resume deb yozgandagina davom ettiriladi.
        $resume = (bool) $this->option('resume') && ! (bool) $this->option('no-resume');

        $this->info('=================================================================');
        $this->info('           KITOB RASMLARINI TIKLASH VA TO\'G\'IRLASH               ');
        $this->info('=================================================================');
        $this->line('Rejim: '.($dryRun ? '<fg=yellow>DRY-RUN (tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy tuzatish (bazadagi rasmlar yangilanadi)</>'));
        $this->line('Lokal saqlash: '.($download ? '<fg=cyan>Ha (rasmlar storage/books ga yuklab olinadi)</>' : '<fg=gray>Yo\'q (ishlaydigan tashqi havola saqlanadi)</>'));
        $this->line('Muqovasizlar: '.($missing ? '<fg=cyan>Ha (rasmi bo\'sh kitoblar kiritildi)</>' : '<fg=gray>Yo\'q</>'));
        $this->line('Diskda yo\'q / buzilganlar: <fg=cyan>Ha (avtomatik tekshiriladi)</>');
        $this->line('Dublikat va nosozlar: '.($allDuplicated ? '<fg=cyan>Ha</>' : '<fg=gray>Yo\'q</>'));
        $this->line('Ilgari yuklangan fixed_ rasmlar: '.($fixed ? '<fg=yellow>Ha (majburiy qayta yuklanadi)</>' : '<fg=gray>Yo\'q (ishlayotganlari tegilmaydi)</>'));
        $this->line('Tartib: '.($sortOrder === 'desc' ? '<fg=cyan>Eng yangi kitoblardan (id DESC)</>' : '<fg=gray>Eski kitoblardan (id ASC)</>'));
        $this->line('Davom ettirish: '.($resume ? '<fg=green>Ha (faqat avval muvaffaqiyatli topilganlar o\'tkazib yuboriladi)</>' : '<fg=cyan>Yo\'q</>'));
        if ($resume && (count($checkpoint['editions']) > 0 || count($checkpoint['books']) > 0)) {
            $this->line("Xotiradagi ko'rilgan kitoblar soni: <comment>".count($checkpoint['editions'])." ta global karta, ".count($checkpoint['books'])." ta do'kon taklifi</comment>");
        }
        $this->newLine();

        // 1. Books takliflarini aniqlaymiz
        $dupBookIds = collect();
        if ($allDuplicated) {
            $dupBookIds = DB::table('books')
                ->whereIn('images', function ($sub) {
                    $sub->select('images')
                        ->from('books')
                        ->whereNotNull('images')
                        ->where('images', '!=', '[]')
                        ->groupBy('images')
                        ->havingRaw('count(*) > 1');
                })
                ->pluck('id');
        }

        // Diskda fayli yo'q yoki 0 bayt bo'lgan Books
        $missingDiskBookIds = Books::query()
            ->whereNotNull('images')
            ->where('images', '!=', '[]')
            ->where('images', '!=', '[""]')
            ->where('images', 'not like', '%http%')
            ->get(['id', 'images'])
            ->filter(function ($b) {
                $imgs = Arr::wrap($b->images ?? []);
                foreach ($imgs as $img) {
                    if (is_string($img) && $img !== '' && ! str_starts_with($img, 'http')) {
                        if (! Storage::disk('public')->exists($img) || Storage::disk('public')->size($img) < 500) {
                            return true;
                        }
                    }
                }

                return false;
            })
            ->pluck('id');

        $brokenBooksQuery = Books::query()
            ->where(function ($q) use ($dupSubstring, $dupBookIds, $missing, $fixed, $missingDiskBookIds) {
                foreach (self::KNOWN_BAD_PATTERNS as $pattern) {
                    $q->orWhere('images', 'like', '%'.$pattern.'%');
                }

                // Faqat foydalanuvchi ataylab --fixed buyrug'ini bersagina fixed_ rasmlar qayta olinadi
                if ($fixed) {
                    $q->orWhere('images', 'like', '%fixed_%');
                }

                if ($dupSubstring !== '') {
                    $q->orWhere('images', 'like', '%'.$dupSubstring.'%');
                }

                if ($dupBookIds->isNotEmpty()) {
                    $q->orWhereIn('id', $dupBookIds);
                }

                if ($missingDiskBookIds->isNotEmpty()) {
                    $q->orWhereIn('id', $missingDiskBookIds);
                }

                if ($missing) {
                    $q->orWhereNull('images')
                        ->orWhere('images', '')
                        ->orWhere('images', '[]')
                        ->orWhere('images', '[""]');
                }
            });

        if ($resume && ! empty($checkpoint['books'])) {
            $skipBookIds = collect($checkpoint['books'])
                ->filter(fn ($status) => $status === 'found')
                ->keys();
            if ($missingDiskBookIds->isNotEmpty()) {
                $skipBookIds = $skipBookIds->diff($missingDiskBookIds);
            }
            if ($skipBookIds->isNotEmpty()) {
                $brokenBooksQuery->whereNotIn('id', $skipBookIds->all());
            }
        }

        $brokenBooksQuery->orderBy('id', $sortOrder);

        if ($limit > 0) {
            $brokenBooksQuery->limit($limit);
        }

        $brokenBooks = $brokenBooksQuery->get();
        $this->line("Tuzatilishi kerak bo'lgan do'kon takliflari (Books): <comment>{$brokenBooks->count()} ta</comment>");

        // 2. Global kartalarni (BookEdition) aniqlaymiz
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
                            ->where('front_image', '!=', '')
                            ->groupBy('front_image')
                            ->havingRaw('count(*) > 1');
                    })
                    ->pluck('id');
            }

            // Diskda fayli yo'q yoki 0 bayt bo'lgan BookEdition
            $missingDiskEditionIds = BookEdition::query()
                ->whereNull('deleted_at')
                ->whereNotNull('front_image')
                ->where('front_image', '!=', '')
                ->where('front_image', 'not like', 'http%')
                ->pluck('front_image', 'id')
                ->filter(fn ($path) => ! Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) < 500)
                ->keys();

            $brokenEditionsQuery = BookEdition::query()
                ->where(function ($q) use ($dupSubstring, $dupEditionIds, $missing, $fixed, $missingDiskEditionIds) {
                    foreach (self::KNOWN_BAD_PATTERNS as $pattern) {
                        $q->orWhere('front_image', 'like', '%'.$pattern.'%')
                            ->orWhere('images', 'like', '%'.$pattern.'%');
                    }

                    // Faqat foydalanuvchi ataylab --fixed buyrug'ini bersagina fixed_ rasmlar qayta olinadi
                    if ($fixed) {
                        $q->orWhere('front_image', 'like', '%fixed_%')
                            ->orWhere('images', 'like', '%fixed_%');
                    }

                    if ($dupSubstring !== '') {
                        $q->orWhere('front_image', 'like', '%'.$dupSubstring.'%')
                            ->orWhere('images', 'like', '%'.$dupSubstring.'%');
                    }

                    if ($dupEditionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $dupEditionIds);
                    }

                    if ($missingDiskEditionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $missingDiskEditionIds);
                    }

                    if ($missing) {
                        $q->orWhereNull('front_image')
                            ->orWhere('front_image', '')
                            ->orWhere('front_image', '[]')
                            ->orWhere('front_image', '[""]');
                    }
                });

            if ($resume && ! empty($checkpoint['editions'])) {
                $skipEditionIds = collect($checkpoint['editions'])
                    ->filter(fn ($status) => $status === 'found')
                    ->keys();
                if ($missingDiskEditionIds->isNotEmpty()) {
                    $skipEditionIds = $skipEditionIds->diff($missingDiskEditionIds);
                }
                if ($skipEditionIds->isNotEmpty()) {
                    $brokenEditionsQuery->whereNotIn('id', $skipEditionIds->all());
                }
            }

            $brokenEditionsQuery->orderBy('id', $sortOrder);

            if ($limit > 0) {
                $brokenEditionsQuery->limit($limit);
            }

            $brokenEditions = $brokenEditionsQuery->get();
        }
        $this->line("Tuzatilishi kerak bo'lgan global kartalar (BookEdition): <comment>{$brokenEditions->count()} ta</comment>");
        $this->newLine();

        $totalCount = $brokenBooks->count() + $brokenEditions->count();
        if ($totalCount === 0) {
            $this->info("✅ Bazada tekshirilishi kerak bo'lgan muammoli kitoblar qolmadi!");

            return Command::SUCCESS;
        }

        $fixedBooks = 0;
        $fixedEditions = 0;
        $notFound = 0;
        $processed = 0;
        $unresolvedList = [];

        // 1. Global kartalarni (BookEdition) BIRINCHI tuzatish (chunki ular asosiy manba)
        $this->info("--- GLOBAL KARTALARNI (BookEdition) TEKSHIRISH ---");
        foreach ($brokenEditions as $edition) {
            $processed++;
            $rawFront = (string) $edition->getRawOriginal('front_image');
            $rawImages = (string) $edition->getRawOriginal('images');

            $isBadImage = false;
            foreach (self::KNOWN_BAD_PATTERNS as $bp) {
                if (str_contains($rawFront, $bp) || str_contains($rawImages, $bp)) {
                    $isBadImage = true;
                    break;
                }
            }
            if (! $isBadImage && (str_contains($rawFront, 'fixed_') || str_contains($rawImages, 'fixed_'))) {
                $isBadImage = true;
            }

            $workingUrl = $download
                ? $this->resolver->resolveAndStore($edition->title, $edition->isbn13 ?: $edition->isbn10, $rawFront ?: $rawImages, 'ed_'.$edition->id)
                : $this->resolver->resolveRemoteCover($edition->title, $edition->isbn13 ?: $edition->isbn10, $rawFront ?: $rawImages);

            if ($workingUrl) {
                if (! $dryRun) {
                    if ($rawFront && str_contains($rawFront, 'fixed_') && $rawFront !== $workingUrl) {
                        @Storage::disk('public')->delete($rawFront);
                    }
                    $edition->update([
                        'front_image' => $workingUrl,
                        'images' => Arr::wrap($workingUrl),
                    ]);
                    $this->catalogService->syncOffers($edition);
                }
                $fixedEditions++;
                $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=green>TOPILDI VA SAQLANDI</>", $processed, $totalCount, $edition->id, $edition->title));
                $checkpoint['editions'][$edition->id] = 'found';
            } else {
                $notFound++;
                $unresolvedList[] = [
                    'id' => $edition->id,
                    'type' => 'edition',
                    'title' => $edition->title,
                    'author' => $edition->author,
                    'isbn' => $edition->isbn13 ?: $edition->isbn10,
                    'edit_url' => url('/boshqaruv/catalog/editions/'.$edition->id.'/edit'),
                ];
                if (! $dryRun && $isBadImage) {
                    if ($rawFront && str_contains($rawFront, 'fixed_')) {
                        @Storage::disk('public')->delete($rawFront);
                    }
                    $edition->update([
                        'front_image' => null,
                        'images' => [],
                    ]);
                    $this->catalogService->syncOffers($edition);
                    $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=yellow>TOPILMADI (xato muqova tozalandi)</>", $processed, $totalCount, $edition->id, $edition->title));
                } else {
                    $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=yellow>INTERNETDAN TOPILMADI</>", $processed, $totalCount, $edition->id, $edition->title));
                }
                $checkpoint['editions'][$edition->id] = 'not_found';
            }

            if ($processed % 10 === 0) {
                $this->saveCheckpoint($checkpoint);
            }
        }

        // 2. Books takliflarini tuzatish
        if ($brokenBooks->isNotEmpty()) {
            $this->newLine();
            $this->info("--- DO'KON TAKLIFLARINI (Books) TEKSHIRISH ---");
            foreach ($brokenBooks as $book) {
                $processed++;
                $rawBookImages = (string) $book->getRawOriginal('images');

                $isBadBookImage = false;
                foreach (self::KNOWN_BAD_PATTERNS as $bp) {
                    if (str_contains($rawBookImages, $bp)) {
                        $isBadBookImage = true;
                        break;
                    }
                }
                if (! $isBadBookImage && str_contains($rawBookImages, 'fixed_')) {
                    $isBadBookImage = true;
                }

                $workingUrl = $download
                    ? $this->resolver->resolveAndStore($book->name, $book->isbn, $book->getRawOriginal('images'), 'b_'.$book->id)
                    : $this->resolver->resolveRemoteCover($book->name, $book->isbn, $book->getRawOriginal('images'));

                if ($workingUrl) {
                    if (! $dryRun) {
                        Books::writingFromCatalog(function () use ($book, $workingUrl) {
                            $book->update([
                                'images' => Arr::wrap($workingUrl),
                            ]);
                        });

                        if ($book->edition_id && $book->edition) {
                            $edition = $book->edition;
                            $edition->update([
                                'front_image' => $workingUrl,
                                'images' => Arr::wrap($workingUrl),
                            ]);
                            $this->catalogService->syncOffers($edition);
                        }
                    }
                    $fixedBooks++;
                    $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=green>TOPILDI VA SAQLANDI</>", $processed, $totalCount, $book->id, $book->name));
                    $checkpoint['books'][$book->id] = 'found';
                } else {
                    $notFound++;
                    $unresolvedList[] = [
                        'id' => $book->id,
                        'type' => 'book',
                        'title' => $book->name,
                        'author' => $book->author,
                        'isbn' => $book->isbn,
                        'edit_url' => url('/boshqaruv/catalog?search='.$book->id),
                    ];
                    if (! $dryRun && $isBadBookImage) {
                        Books::writingFromCatalog(function () use ($book) {
                            $book->update([
                                'images' => [],
                            ]);
                        });
                        $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=yellow>TOPILMADI (xato rasm tozalandi)</>", $processed, $totalCount, $book->id, $book->name));
                    } else {
                        $this->line(sprintf("[%d/%d] #%d \"%s\" -> <fg=yellow>INTERNETDAN TOPILMADI</>", $processed, $totalCount, $book->id, $book->name));
                    }
                    $checkpoint['books'][$book->id] = 'not_found';
                }

                if ($processed % 10 === 0) {
                    $this->saveCheckpoint($checkpoint);
                }
            }
        }

        $this->saveCheckpoint($checkpoint);
        $this->saveUnresolvedReport($unresolvedList);
        $this->newLine(2);

        $this->table(
            ['Ko\'rsatkich', 'Soni'],
            [
                ['Tuzatilgan global kartalar (BookEdition)', $fixedEditions],
                ['Tuzatilgan do\'kon takliflari (Books)', $fixedBooks],
                ['Yangi rasmi topilmaganlar', $notFound],
            ]
        );

        $this->info('✅ Jarayon muvaffaqiyatli yakunlandi!');

        return Command::SUCCESS;
    }

    private function saveUnresolvedReport(array $list): void
    {
        if (empty($list)) {
            return;
        }

        $dir = storage_path('app');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // 1. JSON hisobot
        $jsonPath = storage_path('app/missing_covers_report.json');
        file_put_contents($jsonPath, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. CSV hisobot
        $csvPath = storage_path('app/missing_covers_report.csv');
        $fp = fopen($csvPath, 'w');
        fputcsv($fp, ['ID', 'Turi', 'Kitob nomi', 'Muallif', 'ISBN', 'Boshqaruv tahrirlash havolasi']);
        foreach ($list as $item) {
            fputcsv($fp, [
                $item['id'],
                $item['type'] === 'edition' ? 'Global nashr' : 'Do\'kon taklifi',
                $item['title'],
                $item['author'],
                $item['isbn'],
                $item['edit_url'],
            ]);
        }
        fclose($fp);

        $this->newLine();
        $this->info("📑 Topilmagan muqovasiz kitoblar ro'yxati (admin qo'lda to'ldirishi uchun) saqlandi:");
        $this->line("   - CSV: <comment>storage/app/missing_covers_report.csv</comment>");
        $this->line("   - JSON: <comment>storage/app/missing_covers_report.json</comment>");
    }

    private function checkpointPath(): string
    {
        return storage_path('app/'.self::CHECKPOINT_FILE);
    }

    private function loadCheckpoint(): array
    {
        $path = $this->checkpointPath();
        if (file_exists($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                return [
                    'editions' => $data['editions'] ?? [],
                    'books' => $data['books'] ?? [],
                ];
            }
        }

        return ['editions' => [], 'books' => []];
    }

    private function saveCheckpoint(array $data): void
    {
        try {
            $dir = storage_path('app');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($this->checkpointPath(), json_encode($data));
        } catch (\Throwable) {}
    }

    private function clearCheckpoint(): void
    {
        $path = $this->checkpointPath();
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
