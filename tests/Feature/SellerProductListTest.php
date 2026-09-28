<?php

namespace Tests\Feature;

use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Do'kon ilovasi: mahsulotlar ro'yxati (tur, saralash, filtr) va ommaviy amallar.
 */
class SellerProductListTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_list_sorts_filters_and_returns_only_requested_type(): void
    {
        $seller = $this->makeSeller();
        $cat = $this->makeCategory();
        $cheap = $this->makeBook($seller, $cat, ['name' => 'Arzon 50%_kitob', 'isbn' => '9780306406157', 'price' => 20000], 9);
        $pricey = $this->makeBook($seller, $cat, ['name' => 'Qimmat', 'isbn' => '9780262033848', 'price' => 90000], 1);
        $pricey->forceFill(['status' => false])->save();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $res = $this->getJson('/api/v1/seller/products/last?type=books&sort=price_desc')->assertOk();
        $this->assertSame([$pricey->id, $cheap->id], collect($res->json('data.books.data'))->pluck('id')->all());
        $this->assertSame([], $res->json('data.stationery.data'));
        $this->assertSame(1, $res->json('counts.books.inactive'));

        $res = $this->getJson('/api/v1/seller/products/last?type=books&sort=stock_asc')->assertOk();
        $this->assertSame([$pricey->id, $cheap->id], collect($res->json('data.books.data'))->pluck('id')->all());

        $res = $this->getJson('/api/v1/seller/products/last?type=books&books_filter=inactive')->assertOk();
        $this->assertSame([$pricey->id], collect($res->json('data.books.data'))->pluck('id')->all());

        // `%` va `_` — oddiy belgi
        $res = $this->getJson('/api/v1/seller/products/last?type=books&books_search='.urlencode('50%_'))->assertOk();
        $this->assertSame([$cheap->id], collect($res->json('data.books.data'))->pluck('id')->all());
    }

    public function test_bulk_actions(): void
    {
        $seller = $this->makeSeller();
        $other = $this->makeSeller();
        $cat = $this->makeCategory();
        $a = $this->makeBook($seller, $cat, ['isbn' => '9780306406157', 'price' => 45000, 'discountPrice' => 40000]);
        $b = $this->makeBook($seller, $cat, ['isbn' => '9780262033848', 'price' => 10000]);
        $foreign = $this->makeBook($other, $cat, ['isbn' => '9780131103627', 'price' => 30000]);
        Sanctum::actingAs($seller, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/bulk', [
            'type' => 'book', 'action' => 'price_percent', 'value' => 10, 'ids' => [$a->id, $b->id, $foreign->id],
        ])->assertOk()->assertJsonPath('updated', 2)->assertJsonPath('skipped', 1);

        $this->assertSame(49500, (int) $a->fresh()->price);
        $this->assertSame(44000, (int) $a->fresh()->discountPrice);
        $this->assertSame(11000, (int) $b->fresh()->price);
        $this->assertSame(30000, (int) $foreign->fresh()->price);

        $this->postJson('/api/v1/seller/products/bulk', ['type' => 'book', 'action' => 'deactivate', 'ids' => [$a->id, $b->id]])
            ->assertOk()->assertJsonPath('updated', 2);
        $this->assertFalse((bool) $a->fresh()->status);

        $this->postJson('/api/v1/seller/products/bulk', ['type' => 'book', 'action' => 'remove', 'ids' => [$b->id]])
            ->assertOk()->assertJsonPath('updated', 1);
        $this->assertTrue((bool) Books::find($b->id)->is_hidden);

        $this->postJson('/api/v1/seller/products/bulk', ['type' => 'book', 'action' => 'price_percent', 'ids' => [$a->id]])
            ->assertStatus(422);
    }
}
