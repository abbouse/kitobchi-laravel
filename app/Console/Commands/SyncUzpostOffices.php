<?php

namespace App\Console\Commands;

use App\Models\Locations;
use App\Services\PostalCodeService;
use Illuminate\Console\Command;

class SyncUzpostOffices extends Command
{
    protected $signature = 'postal:sync-uzpost {--backfill : Indeksi yo\'q manzillarga indeks qo\'yish} {--all : --backfill bilan: hamma manzillarni qayta hisoblash}';

    protected $description = "O'zpochta bo'limlari (indeks + koordinata) ro'yxatini yangilaydi va manzillarga pochta indeksini qo'yadi";

    public function handle(PostalCodeService $postal): int
    {
        $count = $postal->sync();
        if ($count === null) {
            $this->warn("O'zpochta ro'yxati yangilanmadi (log'ga qarang) — eski ro'yxat qoldi.");
        } else {
            $this->info("O'zpochta bo'limlari: {$count}");
        }

        if ($this->option('backfill')) {
            $filled = 0;
            Locations::query()
                ->whereNotNull('lat')->whereNotNull('lon')
                ->when(! $this->option('all'), fn ($q) => $q->whereNull('uzpost_index'))
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($postal, &$filled) {
                    foreach ($rows as $loc) {
                        $code = $postal->codeFor((float) $loc->lat, (float) $loc->lon);
                        if ($code !== null && $code !== $loc->uzpost_index) {
                            $loc->forceFill(['uzpost_index' => $code])->saveQuietly();
                            $filled++;
                        }
                    }
                });
            $this->info("Indeks qo'yilgan manzillar: {$filled}");
        }

        return self::SUCCESS;
    }
}
