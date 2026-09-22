<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mavjud takliflarni (books) global katalog kartalariga ulaydi.
 *
 *   php artisan catalog:backfill --dry-run   # hech narsa yozmaydi, hisobot beradi
 *   php artisan catalog:backfill             # ulaydi + buy box hisoblaydi
 *   php artisan catalog:backfill --sync      # + karta ma'lumotini takliflarga ko'chiradi
 *
 * Tartib: avval moderatsiyadan o'tgan va ko'p sotilgan takliflar — karta
 * shulardan ochiladi (eng sifatli nom/rasm/tavsif), qolganlari ularga ulanadi.
 */
class CatalogBackfill extends Command
{
    protected $signature = 'catalog:backfill
                            {--dry-run : Faqat hisobot, bazaga yozilmaydi}
                            {--sync : Karta ma\'lumotini (nom, rasm, tavsif) barcha takliflarga ko\'chirish}
                            {--chunk=500}';

    protected $description = "Mavjud kitoblarni global katalog kartalariga ulaydi (ISBN va aniq kalit bo'yicha)";

    public function handle(CatalogService $catalog, BuyBoxService $buyBox): int
    {
        $dry = (bool) $this->option('dry-run');
        $stats = ['offers' => 0, 'linked_existing' => 0, 'editions_created' => 0, 'invalid_isbn' => 0, 'isbn_conflicts' => 0];
        $conflicts = [];

        $total = Books::query()->whereNull('edition_id')->count();
        $this->info("Katalogga ulanmagan takliflar: {$total}" . ($dry ? ' (dry-run)' : ''));

        if ($dry) {
            DB::beginTransaction();
        }

        try {
            $bar = $this->output->createProgressBar($total);

            do {
                // Sifatli takliflar birinchi: tasdiqlangan → ko'p sotilgan → eski
                $chunk = Books::query()
                    ->whereNull('edition_id')
                    ->orderByDesc('is_approved')
                    ->orderByDesc('totalSales')
                    ->orderBy('id')
                    ->limit((int) $this->option('chunk'))
                    ->get();

                foreach ($chunk as $book) {
                    $stats['offers']++;
                    if (filled($book->isbn) && Isbn::toIsbn13($book->isbn) === null) {
                        $stats['invalid_isbn']++;
                    }

                    $existing = $catalog->findMatchingEdition($book);
                    if ($existing) {
                        $catalog->attachOffer($book, $existing);
                        $stats['linked_existing']++;
                    } else {
                        $isbn13 = Isbn::toIsbn13($book->isbn);
                        if ($isbn13 && BookEdition::query()->usable()->where('isbn13', $isbn13)->exists()) {
                            $stats['isbn_conflicts']++;
                            if (count($conflicts) < 50) {
                                $conflicts[] = [$book->id, $isbn13, mb_strimwidth((string) $book->name, 0, 50, '…')];
                            }
                        }
                        $edition = $catalog->createEditionFromOffer($book, 'backfill');
                        $catalog->attachOffer($book, $edition);
                        $stats['editions_created']++;
                    }
                    $bar->advance();
                }
                // Himoya: biror qator ulanmay qolsa cheksiz aylanmasin
            } while ($chunk->isNotEmpty() && $stats['offers'] < $total);

            $bar->finish();
            $this->newLine(2);
        } finally {
            if ($dry) {
                DB::rollBack();
            }
        }

        $this->table(['Ko\'rsatkich', 'Soni'], collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if (! empty($conflicts)) {
            $this->warn("Bir xil ISBN, lekin boshqa nomli kitoblar (admin tekshirsin — Katalog → Dublikatlar):");
            $this->table(['book_id', 'isbn13', 'nom'], $conflicts);
        }

        if ($dry) {
            $this->info('Dry-run: hech narsa saqlanmadi.');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->info("Karta ma'lumoti takliflarga ko'chirilmoqda…");
            $synced = 0;
            BookEdition::query()->usable()->has('offers', '>', 1)
                ->chunkById(200, function ($editions) use ($catalog, &$synced) {
                    foreach ($editions as $edition) {
                        $synced += $catalog->syncOffers($edition);
                    }
                });
            $this->info("Sinxronlangan takliflar: {$synced}");
        }

        $this->info('Buy box hisoblanmoqda…');
        $count = $buyBox->recomputeAll();
        $this->info("Tayyor: {$count} ta karta.");

        return self::SUCCESS;
    }
}
