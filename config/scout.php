<?php

use App\Models\Books;
use App\Models\Stationery;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | Bu qiymat qidiruv indekslash uchun ishlatiladigan default "driver"ni
    | belgilaydi — biz uchun bu Meilisearch (o'z serverimizda ishlaydigan,
    | bepul, xato-toleranti to'liq matnli qidiruv motori).
    |
    | MUHIM: agar MEILISEARCH_HOST sozlanmagan bo'lsa yoki server ishlab
    | turmasa, SearchController shu narsani ushlab, avtomatik ravishda
    | eski MySQL (FULLTEXT/LIKE) yo'liga qaytadi — sayt hech qachon
    | "qidiruv ishlamayapti" holatiga tushmaydi.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | true — mahsulot saqlanganda (yaratilganda/yangilanganda) indeksga
    | yozish navbatga (queue) qo'yiladi, so'rov sekinlashmaydi. Loyihada
    | QUEUE_CONNECTION allaqachon sozlangan (database) — worker ishlab
    | turishi kerak (`php artisan queue:work`), aks holda yozuvlar
    | indekslanmay navbatda kutib qoladi.
    |
    */

    'queue' => env('SCOUT_QUEUE', true),

    'after_commit' => true,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    'identify' => env('SCOUT_IDENTIFY', false),

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | `index-settings` — har bir modelning indeksi qanday sozlanishini
    | (qaysi maydonlar qidiriladi, qaysilari filtr/saralash uchun ochiq,
    | reyting qoidalari) shu yerda deklarativ belgilaymiz. O'zgartirgandan
    | so'ng serverda ishga tushirish kerak:
    |
    |     php artisan scout:sync-index-settings
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),

        'index-settings' => [
            Books::class => [
                // Qidiriladigan maydonlar — tartib MUHIM: birinchi maydonlar
                // moslikda yuqoriroq vazn oladi (nom > muallif > teglar > ...).
                'searchableAttributes' => [
                    'name',
                    'author',
                    'artikul',
                    'tags',
                    'category_name',
                    'description',
                ],

                'filterableAttributes' => [
                    'category_id',
                    'seller_id',
                    'price',
                    'lang',
                    'in_stock',
                ],

                'sortableAttributes' => [
                    'price',
                    'totalSalesWeek',
                    'totalSales',
                    'created_at',
                ],

                // Meilisearch default reyting qoidalari + bizning sotuv
                // ko'rsatkichimiz oxirida tie-breaker sifatida ("teng
                // relevance'da ko'proq sotilgan mahsulot yuqorida").
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'totalSalesWeek:desc',
                ],

                // Bo'sh natija bo'lganda "sinonim" sifatida ko'rib chiqiladigan
                // so'zlar — kelajakda kengaytirish uchun boshlang'ich skelet.
                'synonyms' => [
                    'kitob' => ['book', 'книга'],
                    'kanselyariya' => ['stationery', 'канцелярия', 'kantselyariya'],
                ],

                // Default: 4-9 harfli so'zda 1 xato, 9+ da 2 xato ruxsat
                // etiladi (Meilisearch default'i) — qo'lda o'zgartirish shart
                // emas, lekin aniq ko'rsatib qo'yildi (kelgusida sozlash oson
                // bo'lsin uchun).
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 9,
                    ],
                ],
            ],

            Stationery::class => [
                'searchableAttributes' => [
                    'name',
                    'artikul',
                    'tags',
                    'category_name',
                    'material',
                    'description',
                ],

                'filterableAttributes' => [
                    'category_id',
                    'seller_id',
                    'price',
                    'in_stock',
                ],

                'sortableAttributes' => [
                    'price',
                    'totalSalesWeek',
                    'totalSales',
                    'created_at',
                ],

                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'totalSalesWeek:desc',
                ],

                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 9,
                    ],
                ],
            ],
        ],
    ],

];
