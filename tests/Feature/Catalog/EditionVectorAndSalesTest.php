<?php

namespace Tests\Feature\Catalog;

use App\Models\BookEdition;
use App\Models\BookEditionVector;
use App\Models\Books;
use App\Services\Catalog\BuyBoxService;
use App\Services\OpenAIService;
use App\Services\ProductVectorService;
use App\Services\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kitob vektori kartada (book_edition_vectors) va bozor ro'yxatlari kitobning
 * barcha do'konlardagi sotuvi bo'yicha saralanishi.
 */
class EditionVectorAndSalesTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private int $embedCalls = 0;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();

        $vector = array_fill(0, 1536, 0.0);
        $vector[0] = 1.0;

        $this->partialMock(OpenAIService::class, function ($mock) use ($vector) {
            $mock->shouldReceive('getVector')->andReturnUsing(function () use ($vector) {
                $this->embedCalls++;

                return $vector;
            });
        });

        VectorSearchService::invalidateIndex('book');
    }

    /** @return array{0: Books, 1: Books} [arzon (tanlangan), qimmat] */
    private function twoShopsSameBook(): array
    {
        $cat = $this->makeCategory();
        $cheap = $this->makeBook($this->makeSeller(), $cat, ['price' => 40000]);
        $expensive = $this->makeBook($this->makeSeller(), $cat, ['price' => 60000]);

        $this->assertSame($cheap->edition_id, $expensive->edition_id);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        return [$cheap->fresh(), $expensive->fresh()];
    }

    public function test_one_vector_per_card_and_offer_changes_do_not_reembed(): void
    {
        [$cheap, $expensive] = $this->twoShopsSameBook();

        $this->assertSame(1, BookEditionVector::count());
        $this->assertSame(1, $this->embedCalls, 'Ikkinchi do\'kon qo\'shilganda kitob qayta embed qilinmasligi kerak');
        $this->assertNotNull(BookEditionVector::find($cheap->edition_id)->text_hash);
        $this->assertCount(1536, $expensive->vectorData);

        // Narx, qoldiq, sotuv — vektorga ta'sir qilmaydi
        $expensive->update(['price' => 65000]);
        DB::table('books')->where('id', $cheap->id)->update(['totalSales' => 50, 'totalSalesWeek' => 7]);
        app(ProductVectorService::class)->syncByTypeAndId('book', $cheap->id);
        $this->assertSame(1, $this->embedCalls);

        // Kitob matni o'zgarsa — bir marta qayta embed
        BookEdition::find($cheap->edition_id)->update(['description' => 'Yangi, batafsil tavsif']);
        $this->assertSame(2, $this->embedCalls);

        // Ommaviy yangilanishdan keyin "eskirgan" deb belgilangan karta rebuild'da yangilanadi
        BookEditionVector::markStale([$cheap->edition_id]);
        $this->assertNull(BookEditionVector::find($cheap->edition_id)->text_hash);
        app(ProductVectorService::class)->rebuildType('book');
        $this->assertSame(3, $this->embedCalls);
        $this->assertNotNull(BookEditionVector::find($cheap->edition_id)->text_hash);
    }

    public function test_semantic_search_returns_featured_offer_or_the_shops_own_offer(): void
    {
        [$cheap, $expensive] = $this->twoShopsSameBook();
        $query = BookEditionVector::find($cheap->edition_id)->vector;

        $market = app(VectorSearchService::class)->searchByVector($query, 'book', 10, 0.5);
        $this->assertSame([$cheap->id], $market->pluck('id')->all());

        $shop = app(VectorSearchService::class)->searchByVector($query, 'book', 10, 0.5, false, (int) $expensive->seller_id);
        $this->assertSame([$expensive->id], $shop->pluck('id')->all());
    }

    public function test_market_lists_sort_by_sales_across_all_shops(): void
    {
        [$cheap, $expensive] = $this->twoShopsSameBook();
        $single = $this->makeBook($this->makeSeller(), (int) $cheap->category_id, [
            'name' => 'Boshqa kitob', 'isbn' => '9780262033848', 'author' => 'Boshqa muallif',
        ]);

        // Tanlangan taklif kam sotadi, qimmat do'kon — ko'p; alohida kitob o'rtada
        DB::table('books')->where('id', $cheap->id)->update(['totalSalesWeek' => 2, 'totalSales' => 2]);
        DB::table('books')->where('id', $expensive->id)->update(['totalSalesWeek' => 100, 'totalSales' => 100]);
        DB::table('books')->where('id', $single->id)->update(['totalSalesWeek' => 50, 'totalSales' => 50]);
        app(BuyBoxService::class)->recompute((int) $cheap->edition_id);
        app(BuyBoxService::class)->recompute((int) $single->edition_id);

        $this->assertSame(102, (int) BookEdition::find($cheap->edition_id)->sales_week);

        $market = Books::query()->catalogFeatured()->orderByBookSales('week')->pluck('id')->all();
        $this->assertSame([$cheap->id, $single->id], $market);

        // Do'kon ichida esa o'z sotuvi
        $this->assertSame(2, (int) $cheap->fresh()->totalSalesWeek);
    }

    public function test_sales_stats_command_uses_a_rolling_week_and_updates_the_card(): void
    {
        [$cheap, $expensive] = $this->twoShopsSameBook();
        DB::table('books')->where('id', $cheap->id)->update(['totalSalesWeek' => 99, 'totalSales' => 20]);
        DB::table('books')->where('id', $expensive->id)->update(['totalSalesWeek' => 40, 'totalSales' => 30]);

        $this->sale($cheap, 3, now()->subDays(2));                  // hisoblanadi
        $this->sale($cheap, 5, now()->subDays(10));                 // oynadan tashqarida
        $this->sale($cheap, 7, now()->subDay(), cancelled: true);   // bekor qilingan
        $this->sale($expensive, 4, now()->subDays(6));              // hisoblanadi

        $this->artisan('products:sales-stats')->assertSuccessful();

        $this->assertSame(3, (int) $cheap->fresh()->totalSalesWeek);
        $this->assertSame(4, (int) $expensive->fresh()->totalSalesWeek);

        $edition = BookEdition::find($cheap->edition_id);
        $this->assertSame(7, (int) $edition->sales_week);
        $this->assertSame(50, (int) $edition->sales_total);
    }

    private function sale(Books $book, int $qty, $at, bool $cancelled = false): void
    {
        $orderId = DB::table('solds')->insertGetId([
            'amount' => $qty * 40000,
            'status' => $cancelled ? 'F' : 'P',
            'status_code' => $cancelled ? 'cancelled' : 'processing',
            'created_at' => $at, 'updated_at' => $at,
        ]);
        $sellerOrderId = DB::table('seller_orders')->insertGetId([
            'seller_id' => $book->seller_id, 'order_id' => $orderId, 'amount' => $qty * 40000,
            'cancelled_at' => $cancelled ? $at : null,
            'created_at' => $at, 'updated_at' => $at,
        ]);
        DB::table('seller_order_items')->insert([
            'seller_id' => $book->seller_id, 'order_id' => $sellerOrderId, 'product_id' => $book->id,
            'type' => 'book', 'quantity' => $qty, 'price' => 40000,
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }
}
