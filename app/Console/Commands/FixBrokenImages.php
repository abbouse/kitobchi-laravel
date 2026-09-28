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

class FixBrokenImages extends Command
{
    protected $signature = 'catalog:fix-broken-images
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--download : Rasmlarni tashqi serverga bog\'lanmasdan o\'zimizning local serverga (storage/books) yuklab olish}
                            {--all : Barcha muammoli (nosoz, bir xil takrorlangan va muqovasiz) kitoblarni birvarakayiga to\'g\'irlash}
                            {--missing : Rasmi umuman yo\'q (bo\'sh / muqovasiz) kitoblarni ham qidirib rasm o\'rnatish}
                            {--all-duplicated : 2 va undan ko\'p kitobda bir xil takrorlanib qolgan rasmli kitoblarni ham qayta qidirib to\'g\'irlash}
                            {--duplicate-substring= : Takrorlanib qolgan rasm havolasining bir qismi (masalan: 1790422172657)}
                            {--dry-run : Bazaga yozmasdan faqat tekshiruv rejimida ishlash}';

    protected $description = 'Nosoz Cloudinary, noto\'g\'ri takrorlangan yoki muqovasiz kitob rasmlarini Book.uz, Asaxiy yoki Google Books orqali tiklash';

    private const KNOWN_BAD_PATTERNS = [
        'dd9xb0bqw',
        '1790422172657',
        'Screenshot_2026_09_26_072402',
    ];

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly BookCoverResolverService $resolver,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $download = (bool) $this->option('download');
        $dryRun = (bool) $this->option('dry-run');
        $all = (bool) $this->option('all');
        $missing = (bool) $this->option('missing') || $all;
        $allDuplicated = (bool) $this->option('all-duplicated') || $all;
        $dupSubstring = trim((string) $this->option('duplicate-substring'));

        $this->info('=================================================================');
        $this->info('           KITOB RASMLARINI TIKLASH VA TO\'G\'IRLASH               ');
        $this->info('=================================================================');
        $this->line('Rejim: '.($dryRun ? '<fg=yellow>DRY-RUN (tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy tuzatish (bazadagi rasmlar yangilanadi)</>'));
        $this->line('Lokal saqlash: '.($download ? '<fg=cyan>Ha (rasmlar storage/books ga yuklab olinadi)</>' : '<fg=gray>Yo\'q (ishlaydigan tashqi havola saqlanadi)</>'));
        $this->line('Muqovasizlar: '.($missing ? '<fg=cyan>Ha (rasmi bo\'sh kitoblar ham kiritildi)</>' : '<fg=gray>Yo\'q (faqat nosoz va dublikatlar)</>'));
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

        $brokenBooksQuery = Books::query()
            ->where(function ($q) use ($dupSubstring, $dupBookIds, $missing) {
                foreach (self::KNOWN_BAD_PATTERNS as $pattern) {
                    $q->orWhere('images', 'like', '%'.$pattern.'%');
                }

                if ($dupSubstring !== '') {
                    $q->orWhere('images', 'like', '%'.$dupSubstring.'%');
                }

                if ($dupBookIds->isNotEmpty()) {
                    $q->orWhereIn('id', $dupBookIds);
                }

                if ($missing) {
                    $q->orWhereNull('images')
                        ->orWhere('images', '')
                        ->orWhere('images', '[]')
                        ->orWhere('images', '[""]');
                }
            })
            ->orderBy('id');

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

            $brokenEditionsQuery = BookEdition::query()
                ->where(function ($q) use ($dupSubstring, $dupEditionIds, $missing) {
                    foreach (self::KNOWN_BAD_PATTERNS as $pattern) {
                        $q->orWhere('front_image', 'like', '%'.$pattern.'%')
                            ->orWhere('images', 'like', '%'.$pattern.'%');
                    }

                    if ($dupSubstring !== '') {
                        $q->orWhere('front_image', 'like', '%'.$dupSubstring.'%')
                            ->orWhere('images', 'like', '%'.$dupSubstring.'%');
                    }

                    if ($dupEditionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $dupEditionIds);
                    }

                    if ($missing) {
                        $q->orWhereNull('front_image')
                            ->orWhere('front_image', '')
                            ->orWhere('front_image', '[]')
                            ->orWhere('front_image', '[""]');
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
            $this->info("✅ Bazada muammoli kitoblar topilmadi!");

            return Command::SUCCESS;
        }

        $fixedBooks = 0;
        $fixedEditions = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        // 1. Books takliflarini tuzatish
        foreach ($brokenBooks as $book) {
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
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        // 2. Global kartalarni (BookEdition) tuzatish
        foreach ($brokenEditions as $edition) {
            $rawFront = $edition->getRawOriginal('front_image');
            $rawImages = $edition->getRawOriginal('images');
            $workingUrl = $download
                ? $this->resolver->resolveAndStore($edition->title, $edition->isbn13 ?: $edition->isbn10, $rawFront ?: $rawImages, 'ed_'.$edition->id)
                : $this->resolver->resolveRemoteCover($edition->title, $edition->isbn13 ?: $edition->isbn10, $rawFront ?: $rawImages);

            if ($workingUrl) {
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
}
