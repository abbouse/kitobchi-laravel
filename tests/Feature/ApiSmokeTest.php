<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApiClient;
use App\Models\Books;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * BARCHA GET endpointlarini "tirik" holatda tekshiradi: mijoz (kitobchi),
 * do'kon (seller), kuryer (courier), hub, hamkor API (client), boshqaruv.
 *
 * Maqsad — 500 (Server Error) bermasligi: global katalog o'zgarishlaridan
 * keyin biror so'rov ustun/relation yo'qligidan yiqilmasligini kafolatlaydi.
 * 401/403/404/422 kutilgan javoblar hisoblanadi (ma'lumot yoki huquq yo'qligi).
 */
class ApiSmokeTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    /** Tashqi xizmatga chiqadigan yoki og'ir GET yo'llari. */
    private const SKIP = [
        'telegram', 'webhook', 'hook', 'instagram', 'parser', 'export',
        'generate', 'backup', 'horizon', 'telescope', 'sanctum', 'ignition',
        'livewire', 'broadcasting', 'storage/', 'push-notify', 'qr-image',
    ];

    /**
     * AVVALDAN MAVJUD nosozliklar (bu o'zgarishlarga aloqasi yo'q): route bor,
     * lekin controller metodi yoki blade view'i yo'q. Ilovalar bu yo'llarni
     * chaqirmaydi; ro'yxat "tuzatish kerak" belgisi sifatida shu yerda turadi.
     */
    private const KNOWN_BROKEN = [
        'api/update_locale',                                   // UserController::updateLocale — 2 ta argument kutadi
        'api/v1/client/search/category',                       // SearchController::category yo'q
        'api/v1/kitobchi/blog',                                // NewsController::blog yo'q
        'api/v1/kitobchi/cart_user/count',                     // CartController::count yo'q
        'api/v1/kitobchi/shared-cart/my',                      // SharedCartController::myLinks yo'q
        'api/v1/kitobchi/purchase/make/final',                 // PurchaseController::finalStep yo'q
        'api/v1/kitobchi/cart/check_book',                     // UserController::book_in_cart yo'q
        'api/v1/kitobchi/cart/1/minus',                        // UserController::cart_minus yo'q
        'api/v1/seller/products/stationery/categories',        // getStationeryTagsForCategory yo'q
        'api/v1/courier/profile',                              // CourierController::my_data yo'q
        'catalog', 'cart', 'favorites', 'checkout', 'profile', // eski blade sahifalar (view'lar yo'q)
        // Ma'lumot yo'qligidan (ilovada mavjud id bilan ishlaydi, lekin 404 qaytarsa to'g'riroq bo'lardi):
        'api/v1/kitobchi/book_club/repost', 'api/v1/kitobchi/book_club/vote',
    ];

    private function isKnownBroken(string $uri): bool
    {
        foreach (self::KNOWN_BROKEN as $known) {
            if ($uri === $known || str_starts_with($uri, $known . '/')) {
                return true;
            }
        }

        return false;
    }

    private array $fixtures = [];

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        config(['catalog.vision_fallback' => false]);
    }

    public function test_all_get_endpoints_respond_without_server_error(): void
    {
        $this->seedFixtures();

        $failures = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();
            if ($this->shouldSkip($uri)) {
                continue;
            }

            $guard = $this->guardFor($uri);
            if ($guard === null) {
                continue;
            }

            $filled = $this->fillParameters($uri);
            if ($filled === null) {
                continue;
            }

            $this->authenticateAs($guard);
            $headers = $guard === 'client'
                ? ['X-App-ID' => 'smoke_app', 'X-App-Secret' => 'smoke_secret', 'Accept' => 'application/json']
                : ['Accept' => 'application/json'];

            try {
                $response = $this->withHeaders($headers)->get('/' . ltrim($filled, '/'));
                $status = $response->getStatusCode();
            } catch (\Throwable $e) {
                $failures[] = sprintf('%s [%s] EXCEPTION %s: %s', $filled, $guard, get_class($e), $e->getMessage());
                continue;
            }

            $checked++;
            if ($status >= 500 && ! $this->isKnownBroken($filled)) {
                $message = $response->baseResponse instanceof \Illuminate\Http\JsonResponse
                    ? json_encode($response->json('message') ?? '')
                    : mb_strimwidth(strip_tags((string) $response->getContent()), 0, 200, '…');
                $failures[] = sprintf('%s [%s] → %d %s', $filled, $guard, $status, $message);
            }
        }

        $this->assertGreaterThan(150, $checked, 'Juda kam endpoint tekshirildi');
        $this->assertSame([], $failures, "Server xatosi bergan endpointlar:\n" . implode("\n", $failures));
    }

    // ── Fixtures ────────────────────────────────────────────────────────

    private function seedFixtures(): void
    {
        $categoryId = $this->makeCategory();
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $categoryId, [], 5);
        $second = $this->makeBook($this->makeSeller(), $categoryId, ['price' => 35000], 2);

        $user = User::query()->forceCreate([
            'name' => 'Test', 'lastname' => 'User', 'phone_number' => '998901112233',
            'password' => bcrypt('secret'),
        ]);

        DB::table('locations')->insert([
            'user_id' => 1, 'fullAddress' => 'Toshkent', 'lat' => '41.3', 'lon' => '69.2',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $courier = DB::table('couriers')->insertGetId(array_filter([
            'first_name' => 'Kuryer', 'last_name' => 'Test', 'phone_number' => '998901112244',
            'password' => bcrypt('secret'), 'region' => 'Toshkent', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]));

        ApiClient::query()->create([
            'name' => 'Smoke', 'app_id' => 'smoke_app', 'app_secret' => 'smoke_secret',
            'abilities' => ['read'], 'is_active' => true,
        ]);

        $this->fixtures = [
            'user' => $user,
            'seller' => $seller,
            'courier' => \App\Models\Couriers::query()->find($courier),
            'admin' => Admin::query()->forceCreate([
                'name' => 'Root', 'email' => 'smoke@test.uz', 'password' => bcrypt('x'),
                'role' => 'superadmin', 'is_active' => 1,
            ]),
            'book' => $book,
            'second' => $second,
            'category' => $categoryId,
        ];
    }

    private function authenticateAs(string $guard): void
    {
        match ($guard) {
            'user' => Sanctum::actingAs($this->fixtures['user'], ['*'], 'user'),
            'seller' => Sanctum::actingAs($this->fixtures['seller'], ['*'], 'seller'),
            'courier' => $this->fixtures['courier']
                ? Sanctum::actingAs($this->fixtures['courier'], ['*'], 'courier')
                : null,
            'panel' => $this->actingAs($this->fixtures['admin'], 'panel'),
            default => null,
        };
    }

    // ── Yo'llar ─────────────────────────────────────────────────────────

    private function shouldSkip(string $uri): bool
    {
        foreach (self::SKIP as $needle) {
            if (str_contains($uri, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function guardFor(string $uri): ?string
    {
        return match (true) {
            str_starts_with($uri, 'api/v1/seller') => 'seller',
            str_starts_with($uri, 'api/v1/courier') => 'courier',
            str_starts_with($uri, 'api/v1/kitobchi') => 'user',
            str_starts_with($uri, 'api/v1/client') => 'client',
            str_starts_with($uri, 'api/v1/hub') => 'hub',
            str_starts_with($uri, 'boshqaruv') => 'panel',
            str_starts_with($uri, 'api/') => 'guest',
            $uri === '/' || ! str_contains($uri, 'api') => 'guest',
            default => null,
        };
    }

    /** Route parametrlarini haqiqiy qiymatlar bilan to'ldiradi (to'ldirib bo'lmasa — null). */
    private function fillParameters(string $uri): ?string
    {
        if (! str_contains($uri, '{')) {
            return $uri;
        }

        $book = $this->fixtures['book'];
        $values = [
            'id' => (string) $book->id,
            'product' => (string) $book->id,
            'productId' => (string) $book->id,
            'book' => (string) $book->id,
            'bookId' => (string) $book->id,
            'type' => 'book',
            'col' => '5',
            'limit' => '5',
            'page' => '1',
            'isbn' => (string) $book->isbn,
            'code' => (string) $book->artikul,
            'artikul' => (string) $book->artikul,
            'slug' => 'test',
            'category' => (string) $this->fixtures['category'],
            'categoryId' => (string) $this->fixtures['category'],
            'category_id' => (string) $this->fixtures['category'],
            'cat_id' => (string) $this->fixtures['category'],
            'sellerId' => (string) $this->fixtures['seller']->id,
            'seller' => (string) $this->fixtures['seller']->id,
            'authorId' => '1',
            'author' => '1',
            'publisherId' => '1',
            'publisher' => '1',
            'edition' => '1',
            'locale' => 'uz',
            'token' => 'test-token',
            'orderId' => '1',
            'order' => '1',
            'user' => (string) $this->fixtures['user']->id,
        ];

        $result = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($values) {
            return $values[$m[1]] ?? ($values[lcfirst($m[1])] ?? '1');
        }, $uri);

        return $result;
    }
}
