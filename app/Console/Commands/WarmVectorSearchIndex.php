<?php

namespace App\Console\Commands;

use App\Services\VectorSearchService;
use Illuminate\Console\Command;

/**
 * `VectorSearchService`ning semantik-qidiruv indeksini (kitob/kanselyariya)
 * FOYDALANUVCHI so'rovidan TASHQARIDA, oldindan tayyor ("issiq") ushlab
 * turadi.
 *
 * MUHIM (tezlik — 2026-08-08 audit): indeks 10 daqiqa TTL bilan
 * keshlanadi, LEKIN `vectors:rebuild` (har 10 daqiqada ishlaydi) har safar
 * biror mahsulot vektori o'zgarganda uni ATAYLAB DARHOL eskirtiradi
 * (`ProductVectorService::invalidateSearchIndex()`). Demak indeks TTL
 * muddatini ham kutmay tez-tez bo'shab qoladi. Bo'sh indeksni birinchi
 * duch kelgan HAQIQIY foydalanuvchi so'rovi o'zi qayta qurishga majbur
 * bo'lardi (butun faol katalogni skanerlash+normalizatsiya) — bu "bu
 * menga mosmi?" bannerining item sahifasida tasodifiy-sekin chiqishining
 * asosiy sabablaridan biri edi (`similarSection()` deyarli har bir
 * holatda ishlaydi).
 *
 * Bu buyruq shunchaki indeksni chaqirib, natijasini tashlab yuboradi —
 * maqsad faqat keshni to'ldirish. `vectors:rebuild`dan KEYIN ishga
 * tushishi kerak (indeks aynan o'sha eskirtirgani uchun), shuning uchun
 * schedule'da bir necha daqiqa keyinroqqa qo'yiladi.
 */
class WarmVectorSearchIndex extends Command
{
    protected $signature = 'reading-intelligence:warm-search-index';

    protected $description = 'Semantik qidiruv indeksini (kitob/kanselyariya) oldindan issiq ushlab turadi';

    public function handle(VectorSearchService $vectorSearch): int
    {
        foreach (['book', 'stationery'] as $type) {
            $count = $vectorSearch->warmIndex($type);
            $this->info("{$type}: {$count} ta vector indeksda tayyor.");
        }

        return self::SUCCESS;
    }
}
