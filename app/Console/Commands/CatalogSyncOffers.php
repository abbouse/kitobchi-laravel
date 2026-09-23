<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\Catalog\CatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kitobning O'ZINIKI bo'lgan ma'lumoti (nom, muallif, muqova, tavsif, rasmlar)
 * faqat kartada turadi; takliflardagi nusxa — ro'yxat va qidiruv uchun keshi.
 * Bu buyruq keshni karta bilan tenglashtiradi (drift bo'lsa — tuzatadi).
 *
 *   php artisan catalog:sync-offers                # farq bor kartalarni sinxronlaydi
 *   php artisan catalog:sync-offers --dry-run      # faqat farqlarni ko'rsatadi
 *   php artisan catalog:sync-offers --all          # farqni tekshirmay hammasini yozadi
 *   php artisan catalog:sync-offers --edition=123  # bitta karta
 */
class CatalogSyncOffers extends Command
{
    protected $signature = 'catalog:sync-offers
                            {--dry-run : Faqat hisobot}
                            {--all : Farqni tekshirmasdan barcha kartalarni yozish}
                            {--edition= : Faqat shu karta}
                            {--chunk=200}';

    protected $description = "Katalog kartasi ma'lumotini do'kon takliflariga tenglashtiradi";

    public function handle(CatalogService $catalog): int
    {
        $dry = (bool) $this->option('dry-run');
        $all = (bool) $this->option('all');
        $editionId = $this->option('edition') ? (int) $this->option('edition') : null;

        if ($editionId !== null && $editionId <= 0) {
            $this->error("--edition noto'g'ri.");

            return self::INVALID;
        }

        // Kartaning takliflari bor-yo'qligi har karta uchun alohida (indeksli)
        // tekshiriladi — `whereIn(subquery)` har chunk'da butun books indeksini
        // skanerlab, buyruqning asosiy vaqtini yeb qo'yardi.
        $query = BookEdition::query()
            ->usable()
            ->when($editionId, fn ($q) => $q->whereKey($editionId));

        $total = (clone $query)->count();
        $this->info("Tekshiriladigan kartalar: {$total}" . ($dry ? ' (dry-run)' : ''));
        $bar = $this->output->createProgressBar($total);

        $drifted = 0;
        $synced = 0;
        $samples = [];

        $query->orderBy('id')->chunkById((int) $this->option('chunk'), function ($editions) use ($catalog, $dry, $all, &$drifted, &$synced, &$samples, $bar) {
            foreach ($editions as $edition) {
                $bar->advance();
                $stale = $all ? null : $this->staleOfferIds($catalog, $edition);
                if (! $all && empty($stale)) {
                    continue;
                }

                $drifted++;
                if (count($samples) < 20) {
                    $samples[] = [$edition->id, mb_strimwidth((string) $edition->title, 0, 45, '…'), $all ? '—' : count($stale)];
                }

                if (! $dry) {
                    $synced += $catalog->syncOffers($edition, $all ? null : $stale);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->table(["Ko'rsatkich", 'Soni'], [
            ['tekshirildi', $total],
            ['farqli kartalar', $drifted],
            ['yangilangan takliflar', $synced],
        ]);

        if (! empty($samples)) {
            $this->table(['edition_id', 'nom', 'farqli takliflar'], $samples);
        }

        if ($dry) {
            $this->info('Dry-run: bazaga hech narsa yozilmadi.');
        }

        return self::SUCCESS;
    }

    /** @return array<int, int> Kartadan farq qiladigan taklif id'lari */
    private function staleOfferIds(CatalogService $catalog, BookEdition $edition): array
    {
        $expected = $catalog->offerAttributes($edition);
        $expectedImages = $expected['images'];
        unset($expected['images']);

        // MUHIM: kartada bo'sh maydonlar uchun `offerAttributes()` "joriy yil",
        // "O'zbek", "Lotin", "Yumshoq" kabi standart qiymat beradi. Ular bo'yicha
        // solishtirsak, 1-yanvarda butun katalog "farqli" bo'lib chiqib, minglab
        // qator behuda qayta yozilardi (va qayta embed qilinardi). Shuning uchun
        // kartada qiymat yo'q ustunlar tekshirilmaydi.
        foreach (['year' => $edition->year, 'lang' => $edition->lang, 'langType' => $edition->langType, 'coverType' => $edition->coverType, 'pages' => $edition->pages] as $column => $cardValue) {
            if (blank($cardValue)) {
                unset($expected[$column]);
            }
        }

        $rows = DB::table('books')
            ->where('edition_id', $edition->id)
            ->get(array_merge(['id', 'images'], array_keys($expected)));

        $stale = [];
        foreach ($rows as $row) {
            // JSON ustuni matn sifatida solishtirilmaydi (qochirish belgilari
            // yozuvchiga qarab farq qiladi) — massiv holida taqqoslanadi.
            $images = json_decode((string) ($row->images ?? '[]'), true);
            if (! is_array($images) || array_values(array_filter($images, 'is_string')) !== $expectedImages) {
                $stale[] = (int) $row->id;

                continue;
            }

            foreach ($expected as $column => $value) {
                if ((string) ($row->{$column} ?? '') !== (string) ($value ?? '')) {
                    $stale[] = (int) $row->id;
                    break;
                }
            }
        }

        return $stale;
    }
}
