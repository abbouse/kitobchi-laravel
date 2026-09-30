<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rasmi yo'q, noto'g'ri yoki yuklangan (fixed_) kitoblar ro'yxatini chiqaradi.
 * Admin keyinchalik shu ro'yxatni ko'rib, qo'lda to'g'irlaydi.
 */
class ListBrokenImages extends Command
{
    protected $signature = 'catalog:list-broken-images
                            {--limit=0 : Maksimal natijalar soni (0 = barchasi)}
                            {--missing : Faqat rasmi umuman yo\'q bo\'lganlar}
                            {--bad-pattern : Faqat noto\'g\'ri pattern bilan yuklangan rasmlar}
                            {--fixed : Faqat ilgari fixed_ prefiksi bilan saqlangan rasmlar}
                            {--duplicate : Takrorlangan rasmlari bor kitoblar}
                            {--all : Barcha muammoli kitoblar (standart)}
                            {--csv= : Natijani CSV faylga saqlash (masalan: storage/broken.csv)}
                            {--json= : Natijani JSON faylga saqlash}
                            {--sort=id : Tartiblash: id | title | updated_at}';

    protected $description = 'Rasmi yo\'q, noto\'g\'ri yoki dublikat bo\'lgan kitoblar ro\'yxatini chiqaradi (admin uchun)';

    private const KNOWN_BAD_PATTERNS = [
        'dd9xb0bqw',
        '1790422172657',
        'Screenshot_2026_09_26_072402',
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $limit      = (int) $this->option('limit');
        $missing    = (bool) $this->option('missing');
        $badPattern = (bool) $this->option('bad-pattern');
        $fixed      = (bool) $this->option('fixed');
        $duplicate  = (bool) $this->option('duplicate');
        $all        = (bool) $this->option('all');
        $csvPath    = (string) $this->option('csv');
        $jsonPath   = (string) $this->option('json');
        $sort       = in_array($this->option('sort'), ['id', 'title', 'updated_at']) ? $this->option('sort') : 'id';

        // Standart: hech qaysi flag berilmasa — barchasi
        if ($all || (! $missing && ! $badPattern && ! $fixed && ! $duplicate)) {
            $missing    = true;
            $badPattern = true;
            $fixed      = true;
            $duplicate  = true;
        }

        $this->info('=================================================================');
        $this->info('        MUAMMOLI RASMLI KITOBLAR RO\'YXATI                       ');
        $this->info('=================================================================');
        $this->newLine();

        // Takrorlanuvchi front_image larni oldindan aniqlaymiz
        $dupEditionIds = collect();
        if ($duplicate && Schema::hasTable('book_editions')) {
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

        $rows = collect();

        if (Schema::hasTable('book_editions')) {
            $query = BookEdition::query()
                ->where(function ($q) use ($missing, $badPattern, $fixed, $duplicate, $dupEditionIds) {
                    if ($missing) {
                        $q->orWhereNull('front_image')
                            ->orWhere('front_image', '')
                            ->orWhere('front_image', '[]')
                            ->orWhere('front_image', '[""]');
                    }
                    if ($badPattern) {
                        foreach (self::KNOWN_BAD_PATTERNS as $p) {
                            $q->orWhere('front_image', 'like', '%'.$p.'%')
                              ->orWhere('images', 'like', '%'.$p.'%');
                        }
                    }
                    if ($fixed) {
                        $q->orWhere('front_image', 'like', '%fixed_%')
                          ->orWhere('images', 'like', '%fixed_%');
                    }
                    if ($duplicate && $dupEditionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $dupEditionIds);
                    }
                })
                ->select(['id', 'title', 'author', 'isbn13', 'isbn10', 'front_image', 'images', 'status', 'updated_at', 'source'])
                ->orderBy($sort);

            if ($limit > 0) {
                $query->limit($limit);
            }

            foreach ($query->get() as $ed) {
                $rawFront  = (string) $ed->getRawOriginal('front_image');
                $rawImages = (string) $ed->getRawOriginal('images');
                $reason    = $this->detectReason($rawFront, $rawImages, $dupEditionIds->contains($ed->id));

                $rows->push([
                    'type'       => 'BookEdition',
                    'id'         => $ed->id,
                    'title'      => $ed->title,
                    'author'     => $ed->author ?? '-',
                    'isbn'       => $ed->isbn13 ?? $ed->isbn10 ?? '-',
                    'status'     => $ed->status ?? '-',
                    'reason'     => $reason,
                    'image'      => $rawFront ?: ($rawImages ?: '-'),
                    'source'     => $ed->source ?? '-',
                    'updated_at' => $ed->updated_at?->format('Y-m-d H:i'),
                    'admin_url'  => url('/boshqaruv/catalog/'.$ed->id.'/edit'),
                ]);
            }
        }

        if ($rows->isEmpty()) {
            $this->info('✅ Muammoli kitob topilmadi — hammasi tartibda!');
            return Command::SUCCESS;
        }

        $this->line("Topilgan muammoli kitoblar: <comment>{$rows->count()} ta</comment>");
        $this->newLine();

        // Muammo turi bo'yicha taqsimot
        $this->info('📊 Muammo turlari bo\'yicha taqsimot:');
        foreach ($rows->groupBy('reason') as $reason => $group) {
            $this->line("  <fg=yellow>{$reason}</>: <comment>{$group->count()} ta</comment>");
        }
        $this->newLine();

        // Jadval (max 50 qator ko'rsatiladi)
        $this->table(
            ['Tur', 'ID', 'Nomi', 'Muallif', 'ISBN', 'Muammo', 'Manba', 'Yangilangan'],
            $rows->take(50)->map(fn($r) => [
                $r['type'],
                $r['id'],
                mb_strimwidth($r['title'], 0, 35, '…'),
                mb_strimwidth($r['author'], 0, 20, '…'),
                $r['isbn'],
                $r['reason'],
                $r['source'],
                $r['updated_at'],
            ])->toArray()
        );

        if ($rows->count() > 50) {
            $this->line('<fg=yellow>⚠  Jadvaldagi natijalar 50 ta bilan cheklandi. To\'liq ro\'yxat uchun --csv yoki --json ishlating.</>');
        }

        $this->newLine();

        if ($csvPath !== '') {
            $this->exportCsv($rows, $csvPath);
        }
        if ($jsonPath !== '') {
            $this->exportJson($rows, $jsonPath);
        }
        if ($csvPath === '' && $jsonPath === '') {
            $this->line('<fg=cyan>💡 To\'liq ro\'yxatni saqlash: --csv=storage/broken.csv yoki --json=storage/broken.json</>');
        }

        return Command::SUCCESS;
    }

    private function detectReason(string $rawFront, string $rawImages, bool $isDuplicate): string
    {
        $reasons = [];

        if ($rawFront === '' || $rawFront === 'null' || $rawFront === '[]' || $rawFront === '[""]') {
            $reasons[] = 'RASMSIZ';
        }

        foreach (self::KNOWN_BAD_PATTERNS as $p) {
            if (str_contains($rawFront, $p) || str_contains($rawImages, $p)) {
                $reasons[] = 'XATO_PATTERN';
                break;
            }
        }

        if (str_contains($rawFront, 'fixed_') || str_contains($rawImages, 'fixed_')) {
            $reasons[] = 'FIXED_PREFIKSI';
        }

        if ($isDuplicate) {
            $reasons[] = 'DUBLIKAT';
        }

        return implode(', ', $reasons) ?: 'NOMA\'LUM';
    }

    private function exportCsv(\Illuminate\Support\Collection $rows, string $path): void
    {
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);
        @mkdir(dirname($fullPath), 0755, true);

        $fp = fopen($fullPath, 'w');
        if (! $fp) {
            $this->error("CSV faylga yozib bo'lmadi: {$fullPath}");
            return;
        }
        fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM — Excel uchun
        fputcsv($fp, ['Tur', 'ID', 'Nomi', 'Muallif', 'ISBN', 'Status', 'Muammo', 'Rasm URL', 'Manba', 'Yangilangan', 'Admin URL'], ';');
        foreach ($rows as $r) {
            fputcsv($fp, array_values($r), ';');
        }
        fclose($fp);
        $this->info("✅ CSV saqlandi: <comment>{$fullPath}</comment>");
    }

    private function exportJson(\Illuminate\Support\Collection $rows, string $path): void
    {
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);
        @mkdir(dirname($fullPath), 0755, true);
        file_put_contents(
            $fullPath,
            json_encode($rows->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $this->info("✅ JSON saqlandi: <comment>{$fullPath}</comment>");
    }
}
