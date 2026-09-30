<?php

namespace App\Console\Commands;

use App\Services\Catalog\BookCoverResolverService;
use App\Support\Isbn;
use Illuminate\Console\Command;

class BuildCoversMap extends Command
{
    protected $signature = 'catalog:build-covers-map
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}';

    protected $description = 'Missing editions ro\'yxatini Asaxiy, Labirint va Book.uz orqali qidirib, resolved_covers_map.json xaritasini hosil qiladi';

    public function handle(BookCoverResolverService $resolver): int
    {
        $inputPath = storage_path('app/missing_editions.json');
        if (! file_exists($inputPath)) {
            $this->error("Fayl topilmadi: {$inputPath}");
            return 1;
        }

        $items = json_decode((string) file_get_contents($inputPath), true);
        if (! is_array($items) || empty($items)) {
            $this->error("Missing editions ro'yxati bo'sh yoki noto'g'ri formatda.");
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
        $this->info("Boshlandi: {$total} ta kitob uchun muqova qidirilmoqda...");
        $foundInRun = 0;

        foreach ($items as $idx => $item) {
            $id = (string) $item['id'];
            $title = (string) ($item['title'] ?? '');
            $isbn = $item['isbn13'] ?: ($item['isbn10'] ?? null);

            if (! empty($map[$id])) {
                $this->line(sprintf("[%d/%d] #%s \"%s\" -> <fg=gray>AVVAL TOPILGAN</>", $idx + 1, $total, $id, $title));
                continue;
            }

            $coverUrl = null;
            $source = null;

            // 1. Agar ruscha bo'lsa yoki ISBN 9785/5 bo'lsa -> Labirint (100% aniq)
            $cleanIsbn = $isbn ? Isbn::clean($isbn) : null;
            if ($cleanIsbn && (str_starts_with($cleanIsbn, '9785') || str_starts_with($cleanIsbn, '5'))) {
                $coverUrl = $resolver->searchLabirint($cleanIsbn, $title);
                if ($coverUrl) {
                    $source = 'Labirint (ISBN)';
                }
            }

            // 2. Asaxiy.uz qidiruvi (O'zbekcha va umumiy kitoblar)
            if (! $coverUrl && mb_strlen(trim($title)) >= 3) {
                $coverUrl = $resolver->searchAsaxiy($title);
                if ($coverUrl) {
                    $source = 'Asaxiy';
                }
            }

            // 3. Agar ruscha sarlavhali bo'lsa va hali topilmagan bo'lsa -> Labirint sarlavha bo'yicha
            if (! $coverUrl && preg_match('/[\p{Cyrillic}]/u', $title)) {
                $coverUrl = $resolver->searchLabirint(null, $title);
                if ($coverUrl) {
                    $source = 'Labirint (Nomi)';
                }
            }

            if ($coverUrl) {
                $map[$id] = $coverUrl;
                $foundInRun++;
                $this->line(sprintf("[%d/%d] #%s \"%s\" -> <fg=green>TOPILDI (%s)</> (<fg=cyan>%s</>)", $idx + 1, $total, $id, $title, $source, $coverUrl));
            } else {
                $this->line(sprintf("[%d/%d] #%s \"%s\" -> <fg=yellow>TOPILMADI</>", $idx + 1, $total, $id, $title));
            }

            if (($idx + 1) % 10 === 0) {
                file_put_contents($outputPath, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        file_put_contents($outputPath, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("Tugadi! Jami xaritada: " . count($map) . " ta muqova saqlandi.");

        return 0;
    }
}
