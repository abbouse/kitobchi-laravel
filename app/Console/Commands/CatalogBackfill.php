<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mavjud takliflarni (books) global katalog kartalariga ulaydi.
 *
 *   php artisan catalog:backfill --dry-run   # hech narsa yozmaydi, faqat hisobot
 *   php artisan catalog:backfill             # ulaydi + buy box hisoblaydi
 *   php artisan catalog:backfill --sync      # + karta ma'lumotini takliflarga ko'chirish
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
        return $this->option('dry-run')
            ? $this->dryRun($catalog)
            : $this->apply($catalog, $buyBox);
    }

    /**
     * HECH NARSA YOZMAYDI. Tranzaksiya ham ochilmaydi (katta jadvalda uzoq
     * tranzaksiya buyurtma va tahrirlarni bloklab qo'yardi) — rejalashtirilgan
     * kartalar xotirada hisoblanadi.
     */
    private function dryRun(CatalogService $catalog): int
    {
        $stats = ['offers' => 0, 'linked_existing' => 0, 'editions_created' => 0, 'invalid_isbn' => 0, 'isbn_conflicts' => 0];
        $conflicts = [];
        $plannedIsbn = [];   // isbn13 => [nom, ...]
        $plannedKeys = [];   // match_key => true

        $total = Books::query()->whereNull('edition_id')->count();
        $this->info("Katalogga ulanmagan takliflar: {$total} (dry-run)");
        $bar = $this->output->createProgressBar($total);

        // MUHIM: chunkById() bu yerda ishlatib bo'lmaydi — u "id > oxirgi id"
        // sharti qo'shadi, tartib esa is_approved/totalSales bo'yicha bo'lgani
        // uchun qatorlarning katta qismi o'tkazib yuborilardi. Shuning uchun
        // avval id'lar to'liq ro'yxati (yengil), keyin bo'lak-bo'lak yuklanadi.
        $orderedIds = Books::query()
            ->whereNull('edition_id')
            ->orderByDesc('is_approved')
            ->orderByDesc('totalSales')
            ->orderBy('id')
            ->pluck('id');

        $orderedIds->chunk((int) $this->option('chunk'))
            ->each(function ($chunkIds) use ($catalog, &$stats, &$conflicts, &$plannedIsbn, &$plannedKeys, $bar) {
                $position = array_flip($chunkIds->values()->all());
                $books = Books::query()
                    ->whereIn('id', $chunkIds->all())
                    ->get()
                    ->sortBy(fn ($book) => $position[$book->id] ?? PHP_INT_MAX)
                    ->values();

                foreach ($books as $book) {
                    $stats['offers']++;
                    $bar->advance();

                    $isbn13 = Isbn::toIsbn13($book->isbn);
                    if (filled($book->isbn) && $isbn13 === null) {
                        $stats['invalid_isbn']++;
                    }

                    // 1. Bazadagi mavjud karta
                    if ($catalog->findMatchingEdition($book)) {
                        $stats['linked_existing']++;

                        continue;
                    }

                    // 2. Shu yugurishda rejalashtirilgan karta
                    if ($isbn13 !== null) {
                        foreach ($plannedIsbn[$isbn13] ?? [] as $title) {
                            if (CatalogService::titlesSimilar($book->name, $title)) {
                                $stats['linked_existing']++;

                                continue 2;
                            }
                        }
                        if (isset($plannedIsbn[$isbn13]) || BookEdition::query()->usable()->where('isbn13', $isbn13)->exists()) {
                            $stats['isbn_conflicts']++;
                            if (count($conflicts) < 50) {
                                $conflicts[] = [$book->id, $isbn13, mb_strimwidth((string) $book->name, 0, 50, '…')];
                            }
                        }
                        $plannedIsbn[$isbn13][] = (string) $book->name;
                    } else {
                        $key = CatalogService::offerMatchKey($book);
                        if ($key !== null && isset($plannedKeys[$key])) {
                            $stats['linked_existing']++;

                            continue;
                        }
                        if ($key !== null) {
                            $plannedKeys[$key] = true;
                        }
                    }

                    $stats['editions_created']++;
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->report($stats, $conflicts);
        $this->info('Dry-run: bazaga hech narsa yozilmadi.');

        return self::SUCCESS;
    }

    private function apply(CatalogService $catalog, BuyBoxService $buyBox): int
    {
        $stats = ['offers' => 0, 'linked_existing' => 0, 'editions_created' => 0, 'invalid_isbn' => 0, 'isbn_conflicts' => 0, 'failed' => 0];
        $conflicts = [];
        $failedIds = [];

        $total = Books::query()->whereNull('edition_id')->count();
        $this->info("Katalogga ulanmagan takliflar: {$total}");
        $bar = $this->output->createProgressBar($total);

        // Har taklifda buy box hisoblanmasin — oxirida hammasi bir marta hisoblanadi
        $buyBox->pause();

        try {
            do {
                $chunk = Books::query()
                    ->whereNull('edition_id')
                    ->when($failedIds, fn ($q) => $q->whereNotIn('id', $failedIds))
                    ->orderByDesc('is_approved')
                    ->orderByDesc('totalSales')
                    ->orderBy('id')
                    ->limit((int) $this->option('chunk'))
                    ->get();

                foreach ($chunk as $book) {
                    $stats['offers']++;
                    $bar->advance();

                    try {
                        if (filled($book->isbn) && Isbn::toIsbn13($book->isbn) === null) {
                            $stats['invalid_isbn']++;
                        }

                        $existing = $catalog->findMatchingEdition($book);
                        if ($existing) {
                            $catalog->attachOffer($book, $existing);
                            $stats['linked_existing']++;

                            continue;
                        }

                        $isbn13 = Isbn::toIsbn13($book->isbn);
                        if ($isbn13 && BookEdition::query()->usable()->where('isbn13', $isbn13)->exists()) {
                            $stats['isbn_conflicts']++;
                            if (count($conflicts) < 50) {
                                $conflicts[] = [$book->id, $isbn13, mb_strimwidth((string) $book->name, 0, 50, '…')];
                            }
                        }

                        $catalog->attachOffer($book, $catalog->createEditionFromOffer($book, 'backfill'));
                        $stats['editions_created']++;
                    } catch (\Throwable $e) {
                        // Bitta yomon qator butun backfillni to'xtatmasin
                        $stats['failed']++;
                        $failedIds[] = (int) $book->id;
                        Log::warning('catalog:backfill row failed', ['book_id' => $book->id, 'error' => $e->getMessage()]);
                    }
                }
            } while ($chunk->isNotEmpty() && $stats['offers'] < $total + count($failedIds));
        } finally {
            $buyBox->resume();
        }

        $bar->finish();
        $this->newLine(2);
        $this->report($stats, $conflicts);

        if (! empty($failedIds)) {
            $this->warn('Ulanmagan takliflar (log: catalog:backfill row failed): ' . implode(', ', array_slice($failedIds, 0, 30)));
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

    private function report(array $stats, array $conflicts): void
    {
        $this->table(["Ko'rsatkich", 'Soni'], collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if (! empty($conflicts)) {
            $this->warn('Bir xil ISBN, lekin boshqa nomli kitoblar (admin tekshirsin — Katalog → Dublikatlar):');
            $this->table(['book_id', 'isbn13', 'nom'], $conflicts);
        }
    }
}
