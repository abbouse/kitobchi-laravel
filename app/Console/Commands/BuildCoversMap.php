<?php

namespace App\Console\Commands;

use App\Services\Catalog\BookCoverResolverService;
use App\Support\Isbn;
use Illuminate\Console\Command;

class BuildCoversMap extends Command
{
    protected $signature = 'catalog:build-covers-map
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}';

    protected $description = 'Missing editions ro\'yxatini faqat mahalliy ishonchli manbalar (Asaxiy, Book.uz) orqali muallif va sarlavhani qat\'iy solishtirib xaritalaydi';

    public function handle(BookCoverResolverService $resolver): int
    {
        $inputPath = storage_path('app/missing_editions.json');
        if (file_exists($inputPath)) {
            $items = json_decode((string) file_get_contents($inputPath), true) ?: [];
        } else {
            $this->info("missing_editions.json topilmadi, ma'lumotlar bazasidan olinmoqda...");
            $items = \App\Models\BookEdition::query()
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('front_image')
                        ->orWhere('front_image', '')
                        ->orWhere('front_image', 'like', '%fixed_%');
                })
                ->get(['id', 'title', 'author', 'isbn13', 'isbn10'])
                ->toArray();
        }

        if (empty($items)) {
            $this->error("Muqovasi yo'q nashrlar topilmadi.");
            return 1;
        }

        $outputPath = storage_path('app/resolved_covers_map.json');
        $map = [];
        if (file_exists($outputPath)) {
            $map = json_decode((string) file_get_contents($outputPath), true) ?: [];
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        $total = count($items);
        $this->info("Boshlandi: {$total} ta kitob uchun O'zbekiston manbalaridan (Asaxiy, Book.uz) qidirilmoqda...");
        $foundInRun = 0;

        foreach ($items as $idx => $item) {
            $id = (string) $item['id'];
            $title = (string) ($item['title'] ?? '');
            $author = (string) ($item['author'] ?? '');
            $rawIsbn = $item['isbn13'] ?: ($item['isbn10'] ?? null);
            $cleanIsbn = $rawIsbn ? Isbn::clean($rawIsbn) : null;

            if (! empty($map[$id])) {
                $this->line(sprintf("[%d/%d] #%s \"%s\" -> <fg=gray>AVVAL TOPILGAN</>", $idx + 1, $total, $id, $title));
                continue;
            }

            $coverUrl = null;
            $source = null;

            // 1. Asaxiy.uz — Muallif va Sarlavha qat'iy tekshiriladi
            if (mb_strlen(trim($title)) >= 3) {
                $coverUrl = $resolver->searchAsaxiy($title, $author);
                if ($coverUrl) {
                    $source = 'Asaxiy (Muallif va Nom tasdiqlandi)';
                }
            }

            // 2. Book.uz API — ISBN bo'yicha
            if (! $coverUrl && $cleanIsbn) {
                $coverUrl = $resolver->searchBookUzByIsbn($cleanIsbn);
                if ($coverUrl) {
                    $source = 'Book.uz (ISBN)';
                }
            }

            // 3. Book.uz API — Sarlavha bo'yicha
            if (! $coverUrl && mb_strlen(trim($title)) >= 2) {
                $coverUrl = $resolver->searchBookUzByTitle($title);
                if ($coverUrl) {
                    $source = 'Book.uz (Sarlavha)';
                }
            }

            if ($coverUrl) {
                $map[$id] = $coverUrl;
                $foundInRun++;
                $this->line(sprintf("[%d/%d] #%s \"%s\" (Muallif: %s) -> <fg=green>TOPILDI (%s)</> (<fg=cyan>%s</>)", $idx + 1, $total, $id, $title, $author ?: '-', $source, $coverUrl));
            } else {
                $this->line(sprintf("[%d/%d] #%s \"%s\" (Muallif: %s) -> <fg=yellow>TOPILMADI</>", $idx + 1, $total, $id, $title, $author ?: '-'));
            }

            if (($idx + 1) % 10 === 0) {
                file_put_contents($outputPath, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        file_put_contents($outputPath, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("Tugadi! Yangi topilganlar: {$foundInRun}. Jami xaritada: " . count($map) . " ta muqova saqlandi.");

        return 0;
    }
}
