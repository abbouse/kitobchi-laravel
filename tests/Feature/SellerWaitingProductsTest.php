<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BranchStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Hisobot: do'konda tugagan, lekin mijozlar kutayotgan mahsulotlar.
 */
class SellerWaitingProductsTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_out_of_stock_products_ranked_by_customer_demand(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $other = $this->makeSeller();

        // Shu kitob boshqa do'konda ham bor — mijozlar o'shaning taklifini sevimliga qo'shgan
        $mine = $this->makeBook($shop, $cat, ['isbn' => '9780306406157'], 0);
        $theirs = $this->makeBook($other, $cat, ['isbn' => '9780306406157'], 5);
        $quiet = $this->makeBook($shop, $cat, ['isbn' => '9780262033848', 'name' => 'Talabsiz'], 0);
        $inStock = $this->makeBook($shop, $cat, ['isbn' => '9780131103627', 'name' => 'Sotuvda'], 4);

        $users = collect(range(1, 3))->map(fn ($i) => User::query()->forceCreate([
            'name' => "U{$i}", 'lastname' => 'T', 'phone_number' => '99890111000'.$i, 'password' => bcrypt('x'),
        ]));

        foreach ($users as $user) {
            DB::table('favourite_products')->insert([
                'user_id' => $user->id, 'product_id' => $theirs->id, 'product_type' => 'book',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('product_stock_alerts')->insert([
            'user_id' => $users[0]->id, 'product_id' => $mine->id, 'product_type' => 'book',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('favourite_products')->insert([
            'user_id' => $users[0]->id, 'product_id' => $inStock->id, 'product_type' => 'book',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs($shop, ['*'], 'seller');

        $res = $this->getJson('/api/v1/seller/statistics/waiting-products')->assertOk();
        $this->assertSame(2, $res->json('data.out_of_stock'));
        $this->assertSame(1, $res->json('data.waiting_total'));
        $this->assertSame($mine->id, $res->json('data.items.0.product_id'));
        $this->assertSame(3, $res->json('data.items.0.favourites'));
        $this->assertSame(1, $res->json('data.items.0.waiting'));

        // Mahsulot statistikasi ham talabni ko'rsatadi
        $this->getJson("/api/v1/seller/products/stat/{$mine->id}?type=book")
            ->assertOk()
            ->assertJsonPath('data.0.favourites_count', 3)
            ->assertJsonPath('data.0.waiting_count', 1);

        // Qoldiq kelgach — ro'yxatdan chiqadi
        app(BranchStockService::class)->setTotalFromLegacy('book', $mine->id, 0, $shop->id, 2);
        $this->getJson('/api/v1/seller/statistics/waiting-products')
            ->assertJsonPath('data.waiting_total', 0);
        $this->assertNotNull($quiet);
    }

    public function test_statistics_include_comparison_and_stock_health(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $this->makeBook($shop, $cat, ['isbn' => '9780306406157'], 0);
        $this->makeBook($shop, $cat, ['isbn' => '9780262033848'], 2);
        $this->makeBook($shop, $cat, ['isbn' => '9780131103627'], 9);
        Sanctum::actingAs($shop, ['*'], 'seller');

        $this->getJson('/api/v1/seller/statistics/data?period=30d')
            ->assertOk()
            ->assertJsonPath('data.stock_health.out_of_stock', 1)
            ->assertJsonPath('data.stock_health.low_stock', 1)
            ->assertJsonPath('data.stock_health.active', 1)
            ->assertJsonPath('data.previous.sales_count', 0)
            ->assertJsonPath('data.cancelled_count', 0);
    }

    public function test_sales_chart_includes_previous_period_shadow(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $shop->forceFill(['created_at' => now()->subYear()])->save();
        $this->makeBook($shop, $cat, ['isbn' => '9780306406157'], 3);

        $sale = function (\Carbon\Carbon $at, int $amount) use ($shop) {
            $orderId = DB::table('solds')->insertGetId([
                'amount' => $amount, 'status' => 'C', 'status_code' => 'delivered',
                'paymentStatus' => 2, 'payment_status_code' => 'paid',
                'completed_at' => $at, 'created_at' => $at, 'updated_at' => $at,
            ]);
            DB::table('seller_orders')->insert([
                'seller_id' => $shop->id, 'order_id' => $orderId, 'amount' => $amount,
                'created_at' => $at, 'updated_at' => $at,
            ]);
        };
        $today = now()->startOfDay()->addHours(12);
        $sale($today, 50000);
        $sale($today->copy()->subDays(7), 30000); // 7 kunlik grafikda oxirgi kunning "soyasi"

        Sanctum::actingAs($shop, ['*'], 'seller');
        $res = $this->getJson('/api/v1/seller/statistics/sales_chart_data?period=7d')->assertOk();
        $last = collect($res->json('data'))->last();
        $this->assertSame(50000, $last['value']);
        $this->assertSame(30000, $last['previous_value']);
        $this->assertSame(1, $last['previous_orders']);
        $this->assertSame($today->copy()->subDays(7)->toDateString(), $last['previous_time']);
    }
}
