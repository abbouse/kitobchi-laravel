<?php

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    private const DEFAULT_LIMIT_PER_SECOND = 8;
    private const DEFAULT_LIMIT_PER_MINUTE = 240;
    private const VERSION = 'v1';

    public function __invoke(Request $request, ?string $page = null)
    {
        $pages = $this->pages();
        $currentSlug = $page ?: 'getting-started';

        if (! isset($pages[$currentSlug])) {
            abort(404);
        }

        $base = url('/api/v1/client');

        return view('developers.api-docs', [
            'baseUrl' => $base,
            'version' => self::VERSION,
            'currentSlug' => $currentSlug,
            'currentPage' => $pages[$currentSlug],
            'pages' => $pages,
            'groups' => $this->groups(),
            'toc' => $this->toc($currentSlug),
            'pageEndpoints' => $this->endpointsForPage($currentSlug, $base),
            'openapiUrl' => route('developers.api-openapi'),
            'postmanUrl' => route('developers.api-postman'),
            'defaultLimits' => [
                'per_second' => self::DEFAULT_LIMIT_PER_SECOND,
                'per_minute' => self::DEFAULT_LIMIT_PER_MINUTE,
            ],
        ]);
    }

    /**
     * Machine-readable OpenAPI 3.0 spec — SDK generatorlar, Postman/Insomnia
     * import va avtomatik hujjatlar uchun yagona manba.
     */
    public function openapi(): JsonResponse
    {
        return response()
            ->json($this->buildOpenApi(url('/api/v1/client')), 200, [
                'Access-Control-Allow-Origin' => '*',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Postman Collection (v2.1.0) formati — 1-klikda Postman/Insomnia'ga import qilish uchun.
     */
    public function postman(): JsonResponse
    {
        $base = url('/api/v1/client');
        $items = [];

        foreach ($this->registry() as $groupKey => $endpoints) {
            $groupItems = [];
            foreach ($endpoints as $ep) {
                $path = ltrim($ep['path'], '/');
                $urlParts = explode('/', $path);

                $headers = [
                    ['key' => 'X-App-ID', 'value' => '{{APP_ID}}', 'type' => 'text'],
                    ['key' => 'X-App-Secret', 'value' => '{{APP_SECRET}}', 'type' => 'text'],
                    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
                ];

                $request = [
                    'method' => strtoupper($ep['method']),
                    'header' => $headers,
                    'url' => [
                        'raw' => "{{BASE_URL}}/{$path}",
                        'host' => ['{{BASE_URL}}'],
                        'path' => $urlParts,
                    ],
                    'description' => $ep['summary'] ?? $ep['title'],
                ];

                if (isset($ep['body'])) {
                    $request['body'] = [
                        'mode' => 'raw',
                        'raw' => json_encode($ep['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        'options' => ['raw' => ['language' => 'json']],
                    ];
                }

                $groupItems[] = [
                    'name' => $ep['title'],
                    'request' => $request,
                    'response' => [],
                ];
            }

            $items[] = [
                'name' => ucfirst($groupKey),
                'item' => $groupItems,
            ];
        }

        $collection = [
            'info' => [
                '_postman_id' => 'kitobchi-client-api-v1',
                'name' => 'Kitobchi Client API',
                'description' => 'Kitobchi Client & Affiliate API Postman Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                ['key' => 'BASE_URL', 'value' => $base, 'type' => 'string'],
                ['key' => 'APP_ID', 'value' => 'app_xxxxxxxxxxxxxxxx', 'type' => 'string'],
                ['key' => 'APP_SECRET', 'value' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'type' => 'string'],
            ],
            'item' => $items,
        ];

        return response()->json($collection, 200, [
            'Content-Disposition' => 'attachment; filename="kitobchi-client-api.postman_collection.json"',
            'Access-Control-Allow-Origin' => '*',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Navigatsiya
    // ─────────────────────────────────────────────────────────────────────

    private function pages(): array
    {
        return [
            'getting-started' => [
                'title' => 'Boshlash',
                'description' => 'Client API bilan birinchi so‘rovni yuborish, arxitektura tamoyillari va integratsiya tartibini tushunish.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'authentication' => [
                'title' => 'Autentifikatsiya',
                'description' => 'App ID, secret kalitlar, headerlar, imzolash va xavfsiz saqlash qoidalari.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'rate-limits' => [
                'title' => 'Limit va cache',
                'description' => 'So‘rov limitlari (Rate Limiting), response cache, ETag va 304 Not Modified optimizatsiyasi.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'pagination' => [
                'title' => 'Sahifalash va filtr',
                'description' => 'page, per_page, q va sort parametrlari, meta bloki, filtrlar va bo‘sh natijalar bilan ishlash.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'products' => [
                'title' => 'Mahsulotlar API',
                'description' => 'Kitob, kanselyariya, mualliflar, nashriyotlar, tavsiyalar va seller mahsulotlarini olish.',
                'group' => 'Endpointlar',
            ],
            'search' => [
                'title' => 'Qidiruv API',
                'description' => 'Global qidiruv, autocomplete takliflari, trend so‘rovlar va kategoriyalar bo‘yicha filtr.',
                'group' => 'Endpointlar',
            ],
            'seller' => [
                'title' => 'Seller API (yozish)',
                'description' => 'Do‘konga bog‘langan kalit orqali o‘z zaxirangizni boshqaring — ISBN yoki shtrix-kod bo‘yicha yangilash.',
                'group' => 'Endpointlar',
            ],
            'webhooks' => [
                'title' => 'Webhooklar',
                'description' => 'Hodisalarga obuna bo‘lish, xavfsizlik uchun imzo (HMAC) tekshiruvi va qayta yuborish siyosati.',
                'group' => 'Endpointlar',
            ],
            'deeplink' => [
                'title' => 'Deep Link generator',
                'description' => 'Mobil ilova URL schemelari, Web, Play Market va App Store havolalarini generatsiya qilish.',
                'group' => 'Endpointlar',
            ],
            'errors' => [
                'title' => 'Xatolar',
                'description' => 'HTTP status kodlar, xato JSON formatlari va integratsiyada tekshiriladigan xavfsizlik holatlari.',
                'group' => 'Qo‘shimcha',
            ],
            'changelog' => [
                'title' => 'O‘zgarishlar',
                'description' => 'API versiyalash tarixi, yangilanishlar va kelajakdagi breaking change siyosati.',
                'group' => 'Qo‘shimcha',
            ],
        ];
    }

    private function groups(): array
    {
        return collect($this->pages())
            ->groupBy('group', preserveKeys: true)
            ->map(fn ($items) => $items->keys()->all())
            ->all();
    }

    private function toc(string $slug): array
    {
        return match ($slug) {
            'getting-started' => [
                ['id' => 'overview', 'label' => 'Umumiy tushuncha'],
                ['id' => 'architecture', 'label' => 'Arxitektura va oqim'],
                ['id' => 'first-request', 'label' => 'Birinchi so‘rov'],
                ['id' => 'response-format', 'label' => 'Javob formati'],
                ['id' => 'best-practices', 'label' => 'Tavsiyalar'],
            ],
            'authentication' => [
                ['id' => 'headers', 'label' => 'Zarur headerlar'],
                ['id' => 'credentials', 'label' => 'App ID va Secret'],
                ['id' => 'secrets', 'label' => 'Secret saqlash xavfsizligi'],
                ['id' => 'abilities', 'label' => 'Ruxsatlar (Abilities)'],
            ],
            'rate-limits' => [
                ['id' => 'limits', 'label' => 'Limitlar va headerlar'],
                ['id' => 'cache', 'label' => 'Response cache'],
                ['id' => 'etag', 'label' => 'ETag va 304 javoblar'],
            ],
            'pagination' => [
                ['id' => 'params', 'label' => 'Parametrlar (page, per_page)'],
                ['id' => 'meta', 'label' => 'Meta bloki va navigatsiya'],
                ['id' => 'filtering', 'label' => 'Filtr va qidiruv (q, sort)'],
                ['id' => 'empty-results', 'label' => 'Bo‘sh natijalar'],
            ],
            'products' => [
                ['id' => 'products-list', 'label' => 'Mahsulotlar ro‘yxati'],
                ['id' => 'products-book-detail', 'label' => 'Kitob tafsiloti'],
                ['id' => 'products-stationery-detail', 'label' => 'Kanselyariya tafsiloti'],
                ['id' => 'products-recommendation', 'label' => 'Tavsiyalar'],
                ['id' => 'products-sellers-list', 'label' => 'Sellerlar ro‘yxati'],
                ['id' => 'products-seller-code', 'label' => 'Shtrix-kod / ISBN'],
            ],
            'search' => [
                ['id' => 'search-global', 'label' => 'Global qidiruv'],
                ['id' => 'search-suggestions', 'label' => 'Autocomplete takliflari'],
                ['id' => 'search-trending', 'label' => 'Trend so‘rovlar'],
                ['id' => 'search-categories', 'label' => 'Kategoriyalar'],
                ['id' => 'search-category', 'label' => 'Kategoriya mahsulotlari'],
            ],
            'seller' => [
                ['id' => 'seller-stock-update', 'label' => 'Zaxirani yangilash'],
                ['id' => 'seller-my-products', 'label' => 'Mening mahsulotlarim'],
            ],
            'webhooks' => [
                ['id' => 'events', 'label' => 'Hodisalar turlari'],
                ['id' => 'signature', 'label' => 'HMAC imzo tekshiruvi'],
                ['id' => 'retries', 'label' => 'Qayta yuborish siyosati'],
            ],
            'deeplink' => [
                ['id' => 'deeplink-helper', 'label' => 'Deep Link generator'],
            ],
            'errors' => [
                ['id' => 'statuses', 'label' => 'HTTP status kodlar'],
                ['id' => 'format', 'label' => 'Xato JSON formati'],
                ['id' => 'common-errors', 'label' => 'Asosiy xato holatlari'],
                ['id' => 'checklist', 'label' => 'Tekshiruv ro‘yxati'],
            ],
            'changelog' => [
                ['id' => 'versioning', 'label' => 'Versiyalash siyosati'],
                ['id' => 'v1-release', 'label' => 'v1.0.0 relizi'],
                ['id' => 'future', 'label' => 'Kelajakdagi rejalar'],
            ],
            default => [
                ['id' => 'overview', 'label' => 'Umumiy tushuncha'],
                ['id' => 'first-request', 'label' => 'Birinchi so‘rov'],
                ['id' => 'response', 'label' => 'Javob formati'],
            ],
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Endpoint registry (yagona manba — docs + OpenAPI shundan quriladi)
    // ─────────────────────────────────────────────────────────────────────

    private function registry(): array
    {
        return [
            'products' => [
                [
                    'id' => 'products-book-detail',
                    'method' => 'GET',
                    'path' => '/products/book/{id}',
                    'title' => 'Bitta kitob tafsiloti',
                    'summary' => 'Kitobning to‘liq tavsifi, muallifi, nashriyoti, narxi, qoldig‘i va rasmlarini qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'id', 'type' => 'integer', 'required' => true, 'desc' => 'Kitob ID raqami.', 'example' => 128],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            'id' => 128,
                            'name' => 'Atomic Habits',
                            'price' => 89000,
                            'old_price' => 99000,
                            'image' => 'https://kitobchi.com/storage/books/128.jpg',
                            'type' => 'book',
                            'in_stock' => true,
                            'description' => 'Kichik o‘zgarishlar, ulkan natijalar...',
                            'pages' => 320,
                            'isbn' => '9781847941831',
                            'author' => ['id' => 45, 'name' => 'James Clear'],
                            'seller' => ['id' => 12, 'name' => 'Asaxiy Books'],
                        ],
                    ],
                ],
                [
                    'id' => 'products-stationery-detail',
                    'method' => 'GET',
                    'path' => '/products/stationery/{id}',
                    'title' => 'Kanselyariya mahsuloti tafsiloti',
                    'summary' => 'Kanselyariya tovarining to‘liq ma’lumoti va variantlarini qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'id', 'type' => 'integer', 'required' => true, 'desc' => 'Mahsulot ID si.', 'example' => 54],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            'id' => 54,
                            'name' => 'ErichKrause Qalam to‘plami',
                            'price' => 25000,
                            'type' => 'stationery',
                            'in_stock' => true,
                            'seller' => ['id' => 12, 'name' => 'Kanselyariya Dunyosi'],
                        ],
                    ],
                ],
                [
                    'id' => 'products-by-author',
                    'method' => 'GET',
                    'path' => '/products/by-author/{authorId}',
                    'title' => 'Muallif bo‘yicha kitoblar',
                    'summary' => 'Muayyan muallifning barcha faol kitoblari ro‘yxatini sahifalab qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'authorId', 'type' => 'integer', 'required' => true, 'desc' => 'Muallif ID raqami.', 'example' => 45],
                    ],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifadagi elementlar soni.', 'example' => 20],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 128, 'name' => 'Atomic Habits', 'price' => 89000, 'type' => 'book'],
                        ],
                        'meta' => ['current_page' => 1, 'per_page' => 20, 'total' => 4, 'last_page' => 1],
                    ],
                ],
                [
                    'id' => 'products-by-publisher',
                    'method' => 'GET',
                    'path' => '/products/by-publisher/{publisherId}',
                    'title' => 'Nashriyot bo‘yicha kitoblar',
                    'summary' => 'Muayyan nashriyotning barcha faol kitoblari ro‘yxatini sahifalab qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'publisherId', 'type' => 'integer', 'required' => true, 'desc' => 'Nashriyot ID raqami.', 'example' => 8],
                    ],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifadagi elementlar soni.', 'example' => 20],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 128, 'name' => 'Atomic Habits', 'price' => 89000, 'type' => 'book'],
                        ],
                        'meta' => ['current_page' => 1, 'per_page' => 20, 'total' => 12, 'last_page' => 1],
                    ],
                ],
                [
                    'id' => 'products-list',
                    'method' => 'GET',
                    'path' => '/products/{col}',
                    'title' => 'Mahsulotlar ro‘yxati',
                    'summary' => 'Katalogdagi mahsulotlarni turi bo‘yicha sahifalab qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'col', 'type' => 'string', 'required' => true, 'desc' => 'Mahsulot turi: books yoki stationery.', 'example' => 'books'],
                    ],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami (1 dan boshlanadi).', 'example' => 1],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifadagi elementlar soni (maksimal 100).', 'example' => 20],
                        ['name' => 'q', 'type' => 'string', 'required' => false, 'desc' => 'Nom bo‘yicha filtr.', 'example' => null],
                        ['name' => 'sort', 'type' => 'string', 'required' => false, 'desc' => 'Tartiblash: popular, new, price_asc, price_desc.', 'example' => null],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 128, 'name' => 'Atomic Habits', 'price' => 89000, 'old_price' => 99000, 'image' => 'https://kitobchi.com/storage/books/128.jpg', 'type' => 'book', 'in_stock' => true, 'seller_id' => 12],
                        ],
                        'meta' => ['page' => 1, 'per_page' => 20, 'total' => 342],
                    ],
                ],
                [
                    'id' => 'products-recommendation',
                    'method' => 'GET',
                    'path' => '/products/recommendation/{col}',
                    'title' => 'Tavsiya qilingan mahsulotlar',
                    'summary' => 'Vitrina va “sizga mos” bloklari uchun tavsiya ro‘yxati.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'col', 'type' => 'string', 'required' => true, 'desc' => 'Mahsulot turi: books yoki stationery.', 'example' => 'books'],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 305, 'name' => 'Deep Work', 'price' => 76000, 'image' => 'https://kitobchi.com/storage/books/305.jpg', 'type' => 'book', 'in_stock' => true],
                        ],
                    ],
                ],
                [
                    'id' => 'products-sellers-list',
                    'method' => 'GET',
                    'path' => '/products/sellers/list',
                    'title' => 'Sellerlar va oxirgi mahsulotlari',
                    'summary' => 'Do‘konlar ro‘yxati va har biri uchun eng so‘nggi mahsulotlar.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 12, 'name' => 'Asaxiy Books', 'logo' => 'https://kitobchi.com/storage/sellers/12.png', 'rating' => 4.8, 'latest_products' => []],
                        ],
                    ],
                ],
                [
                    'id' => 'products-seller-by-qr',
                    'method' => 'GET',
                    'path' => '/products/sellers/by-qr/{token}',
                    'title' => 'QR orqali seller',
                    'summary' => 'QR token orqali do‘kon ma’lumotini olish.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'token', 'type' => 'string', 'required' => true, 'desc' => 'Seller QR tokeni.', 'example' => 'qr_9f3c1a'],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => ['id' => 12, 'name' => 'Asaxiy Books', 'rating' => 4.8],
                    ],
                ],
                [
                    'id' => 'products-seller-profile',
                    'method' => 'GET',
                    'path' => '/products/sellers/profile/{id}/{page}',
                    'title' => 'Seller profili',
                    'summary' => 'Do‘kon profili va uning sahifalangan mahsulotlari.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'id', 'type' => 'integer', 'required' => true, 'desc' => 'Seller ID.', 'example' => 12],
                        ['name' => 'page', 'type' => 'integer', 'required' => true, 'desc' => 'Mahsulotlar sahifasi.', 'example' => 1],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => ['id' => 12, 'name' => 'Asaxiy Books', 'products' => [], 'meta' => ['page' => 1, 'total' => 240]],
                    ],
                ],
                [
                    'id' => 'products-seller-code',
                    'method' => 'GET',
                    'path' => '/products/sellers/{sellerId}/code/{code}',
                    'title' => 'Shtrix-kod / ISBN bo‘yicha mahsulot',
                    'summary' => 'Do‘kon ichidan shtrix-kod yoki ISBN orqali bitta mahsulotni topadi. `/isbn/{isbn}` alias ham mavjud.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'sellerId', 'type' => 'integer', 'required' => true, 'desc' => 'Seller ID.', 'example' => 12],
                        ['name' => 'code', 'type' => 'string', 'required' => true, 'desc' => 'Shtrix-kod yoki ISBN.', 'example' => '9781847941831'],
                    ],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => ['id' => 128, 'name' => 'Atomic Habits', 'price' => 89000, 'in_stock' => true, 'seller_id' => 12],
                    ],
                ],
            ],
            'search' => [
                [
                    'id' => 'search-global',
                    'method' => 'GET',
                    'path' => '/search',
                    'title' => 'Global qidiruv',
                    'summary' => 'Nom, muallif, kategoriya va turga qarab natija qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'q', 'type' => 'string', 'required' => true, 'desc' => 'Qidiruv matni.', 'example' => 'python'],
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifadagi elementlar soni.', 'example' => 20],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 128, 'name' => 'Python bilan dasturlash', 'type' => 'book', 'price' => 120000, 'image' => 'https://kitobchi.com/storage/books/128.jpg'],
                        ],
                        'meta' => ['page' => 1, 'total' => 37],
                    ],
                ],
                [
                    'id' => 'search-suggestions',
                    'method' => 'GET',
                    'path' => '/search/suggestions',
                    'title' => 'Autocomplete takliflari',
                    'summary' => 'Qidiruv maydoni uchun tezkor takliflar (nom, muallif, teg).',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'q', 'type' => 'string', 'required' => true, 'desc' => 'Qidiruv boshlanmasi.', 'example' => 'pyth'],
                        ['name' => 'seller_id', 'type' => 'integer', 'required' => false, 'desc' => 'Bitta do‘kon ichida cheklash.', 'example' => null],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => ['Python', 'Python bilan dasturlash', 'Pythonic'],
                    ],
                ],
                [
                    'id' => 'search-trending',
                    'method' => 'GET',
                    'path' => '/search/trending',
                    'title' => 'Trend so‘rovlar',
                    'summary' => 'Oxirgi 7 kunda eng ko‘p qidirilgan so‘rovlar.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['text' => 'atomic habits', 'result_name' => 'Atomic Habits', 'total_count' => 512],
                        ],
                    ],
                ],
                [
                    'id' => 'search-categories',
                    'method' => 'GET',
                    'path' => '/search/categories',
                    'title' => 'Kategoriyalar',
                    'summary' => 'Katalog menyusi va filtr paneli uchun kategoriyalar ro‘yxati.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 3, 'name' => 'Biznes', 'type' => 'book', 'children' => []],
                        ],
                    ],
                ],
                [
                    'id' => 'search-category',
                    'method' => 'GET',
                    'path' => '/search/category/{cat_id}/{type}',
                    'title' => 'Kategoriya mahsulotlari',
                    'summary' => 'Bitta kategoriya ichidagi mahsulotlarni turi bo‘yicha qaytaradi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [
                        ['name' => 'cat_id', 'type' => 'integer', 'required' => true, 'desc' => 'Kategoriya ID.', 'example' => 3],
                        ['name' => 'type', 'type' => 'string', 'required' => true, 'desc' => 'Mahsulot turi: book yoki stationery.', 'example' => 'book'],
                    ],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [],
                        'meta' => ['page' => 1, 'total' => 96],
                    ],
                ],
            ],
            'seller' => [
                [
                    'id' => 'seller-stock-update',
                    'method' => 'POST',
                    'path' => '/products/stock/by-code',
                    'title' => 'Zaxirani kod bo‘yicha yangilash',
                    'summary' => 'Do‘konga bog‘langan kalit orqali ISBN (kitob) yoki shtrix-kod (kanselyariya) bo‘yicha zaxirani o‘rnatadi (`stock`) yoki o‘zgartiradi (`delta`). `stock:write` ability va seller-scoped kalit talab qilinadi.',
                    'ability' => 'stock:write',
                    'cache' => false,
                    'path_params' => [],
                    'query_params' => [],
                    'body_params' => [
                        ['name' => 'code', 'type' => 'string', 'required' => true, 'desc' => 'ISBN yoki shtrix-kod.'],
                        ['name' => 'type', 'type' => 'string', 'required' => false, 'desc' => 'book | stationery. Bo‘sh bo‘lsa avtomatik aniqlanadi.'],
                        ['name' => 'stock', 'type' => 'integer', 'required' => false, 'desc' => 'Yangi mutlaq zaxira (>= 0).'],
                        ['name' => 'delta', 'type' => 'integer', 'required' => false, 'desc' => 'Joriy zaxiraga qo‘shiladigan o‘zgarish (+/-).'],
                    ],
                    'body' => ['code' => '9781847941831', 'stock' => 25],
                    'response' => [
                        'status' => 'success',
                        'success' => true,
                        'type' => 'book',
                        'id' => 128,
                        'name' => 'Atomic Habits',
                        'stock' => 25,
                        'in_stock' => true,
                    ],
                ],
                [
                    'id' => 'seller-my-products',
                    'method' => 'GET',
                    'path' => '/products/mine',
                    'title' => 'Mening mahsulotlarim',
                    'summary' => 'Kalitga bog‘langan do‘konning mahsulotlari va zaxirasi (sahifalangan).',
                    'ability' => 'stock:write',
                    'cache' => false,
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'type', 'type' => 'string', 'required' => false, 'desc' => 'book (default) yoki stationery.', 'example' => 'book'],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifadagi soni (1–100).', 'example' => 50],
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'desc' => 'Sahifa raqami.', 'example' => 1],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            ['id' => 128, 'type' => 'book', 'name' => 'Atomic Habits', 'code' => '9781847941831', 'price' => 89000, 'stock' => 25, 'in_stock' => true],
                        ],
                        'meta' => ['page' => 1, 'per_page' => 50, 'total' => 240],
                    ],
                ],
            ],
            'deeplink' => [
                [
                    'id' => 'deeplink-helper',
                    'method' => 'GET',
                    'path' => '/deeplink',
                    'title' => 'Deep Link generator',
                    'summary' => 'Ilova URL schemelari (kitobchi://...), Web, Play Market va App Store havolalarini avtomatik generatsiya qiladi.',
                    'ability' => 'read',
                    'cache' => true,
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'type', 'type' => 'string', 'required' => false, 'desc' => 'Turi: book, stationery, seller, category.', 'example' => 'book'],
                        ['name' => 'id', 'type' => 'integer', 'required' => true, 'desc' => 'Mahsulot yoki do‘kon ID raqami.', 'example' => 128],
                    ],
                    'response' => [
                        'status' => 'success',
                        'data' => [
                            'type' => 'book',
                            'id' => 128,
                            'web_url' => 'https://kitobchi.com/book/128',
                            'app_scheme_url' => 'kitobchi://book/128',
                            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.kitobchi.app',
                            'app_store_url' => 'https://apps.apple.com/app/kitobchi/id6470000000',
                            'smart_redirect_url' => 'https://kitobchi.com/r/book/128',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function endpointsForPage(string $slug, string $base): array
    {
        $group = $this->registry()[$slug] ?? [];

        return array_map(fn (array $ep) => $this->enrich($ep, $base), $group);
    }

    private function enrich(array $ep, string $base): array
    {
        $exampleUrl = $this->exampleUrl($ep, $base);

        $ep['full_path'] = $base.$ep['path'];
        $ep['example_url'] = $exampleUrl;
        $ep['url_template'] = $base.$ep['path'];
        $ep['response_json'] = json_encode(
            $ep['response'] ?? ['status' => 'success', 'data' => []],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $ep['request_json'] = isset($ep['body'])
            ? json_encode($ep['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;
        $ep['samples'] = $this->codeSamples($ep['method'], $exampleUrl, $ep['body'] ?? null);

        return $ep;
    }

    private function exampleUrl(array $ep, string $base): string
    {
        $path = $ep['path'];
        foreach ($ep['path_params'] ?? [] as $param) {
            $path = str_replace('{'.$param['name'].'}', rawurlencode((string) ($param['example'] ?? $param['name'])), $path);
        }

        $query = [];
        foreach ($ep['query_params'] ?? [] as $param) {
            if (($param['required'] ?? false) || $param['example'] !== null) {
                $query[$param['name']] = $param['example'] ?? '';
            }
        }

        $url = $base.$path;
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    /**
     * @return array<string,array{label:string,lang:string,code:string}>
     */
    private function codeSamples(string $method, string $url, ?array $body = null): array
    {
        $m = strtoupper($method);
        $bodyJson = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $bodyPretty = $body !== null ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        // ── cURL ──
        $curl = "curl --request {$m} \\\n"
            ."  --url '{$url}' \\\n"
            ."  --header 'Accept: application/json' \\\n"
            ."  --header 'X-App-ID: app_xxxxxxxxxxxx' \\\n"
            ."  --header 'X-App-Secret: your-secret'";
        if ($bodyJson !== null) {
            $curl .= " \\\n  --header 'Content-Type: application/json' \\\n"
                ."  --header 'Idempotency-Key: unique-123' \\\n"
                ."  --data '{$bodyJson}'";
        }

        // ── JavaScript (fetch) ──
        $jsHeaders = "    \"Accept\": \"application/json\",\n"
            ."    \"X-App-ID\": \"app_xxxxxxxxxxxx\",\n"
            ."    \"X-App-Secret\": process.env.KITOBCHI_APP_SECRET,\n";
        if ($bodyJson !== null) {
            $js = "const res = await fetch(\"{$url}\", {\n"
                ."  method: \"{$m}\",\n"
                ."  headers: {\n{$jsHeaders}"
                ."    \"Content-Type\": \"application/json\",\n"
                ."    \"Idempotency-Key\": \"unique-123\",\n"
                ."  },\n"
                ."  body: JSON.stringify({$bodyPretty}),\n"
                ."});\n"
                ."const data = await res.json();";
        } else {
            $js = "const res = await fetch(\"{$url}\", {\n"
                ."  headers: {\n{$jsHeaders}  },\n"
                ."});\n"
                ."const data = await res.json();";
        }

        // ── PHP (curl) ──
        if ($bodyJson !== null) {
            $php = "<?php\n\$ch = curl_init(\"{$url}\");\n"
                ."curl_setopt_array(\$ch, [\n"
                ."  CURLOPT_RETURNTRANSFER => true,\n"
                ."  CURLOPT_CUSTOMREQUEST => '{$m}',\n"
                ."  CURLOPT_POSTFIELDS => '{$bodyJson}',\n"
                ."  CURLOPT_HTTPHEADER => [\n"
                ."    'Accept: application/json',\n"
                ."    'Content-Type: application/json',\n"
                ."    'X-App-ID: app_xxxxxxxxxxxx',\n"
                ."    'X-App-Secret: ' . getenv('KITOBCHI_APP_SECRET'),\n"
                ."    'Idempotency-Key: unique-123',\n"
                ."  ],\n"
                ."]);\n\$data = json_decode(curl_exec(\$ch), true);\ncurl_close(\$ch);";
        } else {
            $php = "<?php\n\$ch = curl_init(\"{$url}\");\n"
                ."curl_setopt_array(\$ch, [\n"
                ."  CURLOPT_RETURNTRANSFER => true,\n"
                ."  CURLOPT_HTTPHEADER => [\n"
                ."    'Accept: application/json',\n"
                ."    'X-App-ID: app_xxxxxxxxxxxx',\n"
                ."    'X-App-Secret: ' . getenv('KITOBCHI_APP_SECRET'),\n"
                ."  ],\n"
                ."]);\n\$data = json_decode(curl_exec(\$ch), true);\ncurl_close(\$ch);";
        }

        // ── Python (requests) ──
        if ($bodyJson !== null) {
            $python = "import os, requests\n\n"
                ."res = requests.request(\n    \"{$m}\",\n    \"{$url}\",\n"
                ."    headers={\n"
                ."        \"Accept\": \"application/json\",\n"
                ."        \"X-App-ID\": \"app_xxxxxxxxxxxx\",\n"
                ."        \"X-App-Secret\": os.environ[\"KITOBCHI_APP_SECRET\"],\n"
                ."        \"Idempotency-Key\": \"unique-123\",\n"
                ."    },\n"
                ."    json={$bodyPretty},\n)\ndata = res.json()";
        } else {
            $python = "import os, requests\n\n"
                ."res = requests.get(\n    \"{$url}\",\n"
                ."    headers={\n"
                ."        \"Accept\": \"application/json\",\n"
                ."        \"X-App-ID\": \"app_xxxxxxxxxxxx\",\n"
                ."        \"X-App-Secret\": os.environ[\"KITOBCHI_APP_SECRET\"],\n"
                ."    },\n)\ndata = res.json()";
        }

        return [
            'curl' => ['label' => 'cURL', 'lang' => 'bash', 'code' => $curl],
            'javascript' => ['label' => 'JavaScript', 'lang' => 'javascript', 'code' => $js],
            'php' => ['label' => 'PHP', 'lang' => 'php', 'code' => $php],
            'python' => ['label' => 'Python', 'lang' => 'python', 'code' => $python],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  OpenAPI 3.0
    // ─────────────────────────────────────────────────────────────────────

    private function buildOpenApi(string $base): array
    {
        $paths = [];

        foreach ($this->registry() as $endpoints) {
            foreach ($endpoints as $ep) {
                $parameters = [];

                foreach ($ep['path_params'] ?? [] as $param) {
                    $parameters[] = [
                        'name' => $param['name'],
                        'in' => 'path',
                        'required' => true,
                        'description' => $param['desc'] ?? '',
                        'schema' => ['type' => $param['type'] ?? 'string'],
                        'example' => $param['example'] ?? null,
                    ];
                }

                foreach ($ep['query_params'] ?? [] as $param) {
                    $parameters[] = [
                        'name' => $param['name'],
                        'in' => 'query',
                        'required' => (bool) ($param['required'] ?? false),
                        'description' => $param['desc'] ?? '',
                        'schema' => ['type' => $param['type'] ?? 'string'],
                        'example' => $param['example'] ?? null,
                    ];
                }

                $operation = [
                    'operationId' => str_replace('-', '_', $ep['id']),
                    'summary' => $ep['title'],
                    'description' => $ep['summary'],
                    'tags' => [ucfirst(explode('-', $ep['id'])[0])],
                    'parameters' => $parameters,
                    'responses' => [
                        '200' => [
                            'description' => 'Muvaffaqiyatli javob',
                            'content' => [
                                'application/json' => [
                                    'example' => $ep['response'] ?? ['status' => 'success', 'data' => []],
                                ],
                            ],
                        ],
                        '401' => ['description' => 'Credential headerlari yuborilmagan'],
                        '403' => ['description' => 'Noto‘g‘ri yoki nofaol credential / ability yetarli emas'],
                        '429' => ['description' => 'Rate limit oshib ketdi'],
                    ],
                ];

                if (isset($ep['body'])) {
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => [
                            'application/json' => ['example' => $ep['body']],
                        ],
                    ];
                }

                $paths[$ep['path']][strtolower($ep['method'])] = $operation;
            }
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Kitobchi Client API',
                'version' => self::VERSION,
                'description' => 'Hamkor servislar, tashqi kataloglar va marketplace integratsiyalari uchun read-only Client API.',
                'contact' => ['name' => 'Kitobchi Developers', 'url' => url('/developers/api')],
            ],
            'servers' => [
                ['url' => $base, 'description' => 'Production'],
            ],
            'security' => [
                ['AppId' => [], 'AppSecret' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'AppId' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-App-ID'],
                    'AppSecret' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-App-Secret'],
                ],
            ],
            'paths' => $paths,
        ];
    }
}
