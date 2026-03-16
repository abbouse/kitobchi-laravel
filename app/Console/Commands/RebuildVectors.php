<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\OpenAIService;

/**
 * Barcha kitob va kanselyariyalar uchun vektorni qayta hisoblash.
 *
 * Ishlatish:
 *   php artisan vectors:rebuild           -- ikkalasini
 *   php artisan vectors:rebuild --type=book
 *   php artisan vectors:rebuild --type=stationery
 *   php artisan vectors:rebuild --force   -- barchasi (mavjud vector bo'lsa ham)
 */
class RebuildVectors extends Command
{
    protected $signature   = 'vectors:rebuild {--type=all} {--force}';
    protected $description = 'Kitob va kanselyariya vectorlarini qayta hisoblaydi';

    public function __construct(protected OpenAIService $ai)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type  = $this->option('type');
        $force = $this->option('force');

        if (in_array($type, ['all', 'book'])) {
            $this->rebuildBooks($force);
        }
        if (in_array($type, ['all', 'stationery'])) {
            $this->rebuildStationery($force);
        }

        $this->info('✅ Vektorlar yangilandi!');
        return 0;
    }

    private function rebuildBooks(bool $force): void
    {
        $query = Books::with(['category', 'seller', 'tags'])
            ->where('is_approved', 1);

        if (!$force) {
            $query->whereNull('vectorData');
        }

        $books = $query->get();
        $bar   = $this->output->createProgressBar($books->count());
        $this->info("📚 Kitoblar: {$books->count()} ta");

        foreach ($books as $book) {
            $text = $this->ai->buildProductEmbedText([
                'name'          => $book->name,
                'author'        => $book->author,
                'category'      => $book->category?->name_uz,
                'tags'          => $book->tags->pluck('tag_name_uz')->toArray(),
                'shop_name'     => $book->seller?->shop_name,
                'description'   => $book->description,
                'totalSales'    => $book->totalSales,
                'totalSalesWeek'=> $book->totalSalesWeek,
            ]);

            $vector = $this->ai->getVector($text);
            $book->update(['vectorData' => $vector]);
            $bar->advance();

            // Rate limit uchun kichik kutish
            usleep(200_000); // 0.2 soniya
        }

        $bar->finish();
        $this->newLine();
    }

    private function rebuildStationery(bool $force): void
    {
        $query = Stationery::with(['category', 'seller', 'tags'])
            ->where('is_approved', 1);

        if (!$force) {
            $query->whereNull('vectorData');
        }

        $items = $query->get();
        $bar   = $this->output->createProgressBar($items->count());
        $this->info("🖊  Kanselyariya: {$items->count()} ta");

        foreach ($items as $item) {
            $text = $this->ai->buildProductEmbedText([
                'name'          => $item->name,
                'category'      => $item->category?->name ?? $item->category?->name_uz,
                'tags'          => $item->tags->pluck('name_uz')->toArray(),
                'shop_name'     => $item->seller?->shop_name,
                'description'   => $item->description,
                'totalSales'    => $item->totalSales,
                'totalSalesWeek'=> $item->totalSalesWeek,
            ]);

            $vector = $this->ai->getVector($text);
            $item->update(['vectorData' => $vector]);
            $bar->advance();

            usleep(200_000);
        }

        $bar->finish();
        $this->newLine();
    }
}