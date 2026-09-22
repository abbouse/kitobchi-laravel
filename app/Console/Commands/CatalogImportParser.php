<?php

namespace App\Console\Commands;

use App\Models\BookEdition;
use App\Models\CatalogParserItem;
use App\Services\Catalog\CatalogService;
use App\Services\CatalogParsers\BookUzParserService;
use App\Support\Isbn;
use Illuminate\Console\Command;

/**
 * Parser keshidagi (book.uz) kitoblardan katalog kartalarini ochadi —
 * do'konlar ISBN skan qilganda kitob tayyor turadi. Taklif yaratilmaydi.
 *
 * DIQQAT: muqova rasmlari va tavsif manba saytga tegishli bo'lishi mumkin;
 * --with-images faqat huquqiy jihat hal qilingandan keyin ishlatilsin.
 */
class CatalogImportParser extends Command
{
    protected $signature = 'catalog:import-parser
                            {--limit=500}
                            {--with-images : Muqova rasmlarini yuklab olish}';

    protected $description = "Parser keshidagi kitoblardan (to'g'ri ISBN bilan) katalog kartalarini ochadi";

    public function handle(BookUzParserService $parser): int
    {
        $created = 0;
        $skipped = 0;
        $limit = (int) $this->option('limit');

        CatalogParserItem::query()
            ->whereNotNull('isbn')
            ->orderBy('id')
            ->chunkById(200, function ($items) use ($parser, &$created, &$skipped, $limit) {
                foreach ($items as $item) {
                    if ($created >= $limit) {
                        return false;
                    }

                    $isbn13 = Isbn::toIsbn13($item->isbn);
                    if (! $isbn13 || BookEdition::query()->usable()->where('isbn13', $isbn13)->exists()) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $payload = $parser->editionPayload($item, (bool) $this->option('with-images'));
                        BookEdition::create($payload + [
                            'isbn13' => $isbn13,
                            'isbn10' => Isbn::toIsbn10($isbn13),
                            'status' => BookEdition::STATUS_ACTIVE,
                            'source' => 'parser',
                            'created_by_type' => 'system',
                            'match_key' => CatalogService::matchKey($payload['title'], $payload['author'], $payload['lang'], $payload['langType'], $payload['coverType'], $payload['publisher_id']),
                        ]);
                        $created++;
                    } catch (\Throwable $e) {
                        $this->warn("#{$item->id}: {$e->getMessage()}");
                        $skipped++;
                    }
                }
            });

        $this->info("Yangi kartalar: {$created}, o'tkazib yuborildi: {$skipped}");

        return self::SUCCESS;
    }
}
