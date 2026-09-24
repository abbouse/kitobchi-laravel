<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCatalogApiTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const API = '/api/v1/kitobchi/';

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_lists_show_one_card_per_book_and_detail_lists_all_offers(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat, ['price' => 60000]);
        $b = $this->makeBook($this->makeSeller(), $cat, ['price' => 48000]);
        $c = $this->makeBook($this->makeSeller(), $cat, ['price' => 30000], 0); // sotuvda yo'q
        $other = $this->makeBook($this->makeSeller(), $cat, ['isbn' => null, 'name' => 'Boshqa kitob']);

        $list = $this->getJson(self::API . 'products/20')->assertOk();
        $ids = collect($list->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$b->id, $other->id], $ids);

        $card = collect($list->json('data'))->firstWhere('id', $b->id);
        $this->assertSame(2, $card['offers_count']);
        $this->assertSame(48000, $card['min_price']);
        $this->assertSame($b->edition_id, $card['edition_id']);
        // Eski kalitlar joyida
        foreach (['id', 'name', 'author', 'images', 'price', 'discountPrice', 'count', 'seller'] as $key) {
            $this->assertArrayHasKey($key, $card);
        }

        // Sotuvda yo'q, lekin eng arzon taklif sahifasi ham ochiladi
        $detail = $this->getJson(self::API . "share/product/{$a->id}?type=book")->assertOk();
        $offers = collect($detail->json('data.offers'));
        // Mijoz qaysi do'kon orqali kirgan bo'lsa (`$a`) — o'sha birinchi va
        // tanlangan turadi; keyin karta g'olibi (`$b`), so'ng qolganlari.
        $this->assertSame([$a->id, $b->id, $c->id], $offers->pluck('id')->all());
        $this->assertTrue($offers->firstWhere('id', $a->id)['is_current']);
        $this->assertTrue($offers->firstWhere('id', $b->id)['is_featured']);
        $this->assertFalse($offers->firstWhere('id', $c->id)['in_stock']);

        $this->getJson(self::API . "share/product/{$c->id}?type=book")->assertOk();
    }

    public function test_search_returns_one_card_per_book_but_shop_filter_shows_its_own_offer(): void
    {
        $cat = $this->makeCategory();
        $s1 = $this->makeSeller();
        $a = $this->makeBook($s1, $cat, ['price' => 60000]);
        $b = $this->makeBook($this->makeSeller(), $cat, ['price' => 48000]);

        $res = $this->getJson(self::API . 'search?q=' . urlencode("O'tkan kunlar") . '&type=book')->assertOk();
        $ids = collect($res->json('data.books') ?? $res->json('books') ?? $res->json('data'))->pluck('id')->filter()->all();
        $this->assertContains($b->id, $ids);
        $this->assertNotContains($a->id, $ids);

        $res = $this->getJson(self::API . 'search?q=' . urlencode("O'tkan kunlar") . "&type=book&seller_id={$s1->id}")->assertOk();
        $ids = collect($res->json('data.books') ?? $res->json('books') ?? $res->json('data'))->pluck('id')->filter()->all();
        $this->assertContains($a->id, $ids);
    }

    public function test_sellers_rail_and_similar_work_without_n_plus_one(): void
    {
        $cat = $this->makeCategory();
        $seller = $this->makeSeller();
        for ($i = 0; $i < 6; $i++) {
            $this->makeBook($seller, $cat, ['isbn' => null, 'name' => "Kitob {$i}"], 3);
        }
        \Illuminate\Support\Facades\Cache::flush();
        // Kitob yaratishdan qolgan "javobdan keyin" ishlari shu yerda tugasin
        $this->getJson(self::API . 'news')->assertOk();

        \DB::enableQueryLog();
        $res = $this->getJson(self::API . 'products/sellers/list')->assertOk();
        $log = collect(\DB::getQueryLog())->pluck('query');
        // N+1 belgilari: har kartada alohida qoldiq SUM yoki muallif so'rovi
        $stockQueries = $log->filter(fn ($q) => str_starts_with($q, 'select sum(quantity - reserved)'))->count();
        $authorQueries = $log->filter(fn ($q) => str_contains($q, 'from `authors`'))->count();
        \DB::disableQueryLog();

        $row = collect($res->json('data'))->firstWhere('seller_id', $seller->id) ?? collect($res->json('data'))->first();
        $this->assertCount(6, $row['books']);
        $this->assertSame(3, $row['books'][0]['count']);
        $this->assertSame(0, $stockQueries, 'qoldiq N+1');
        $this->assertLessThanOrEqual(1, $authorQueries, 'muallif N+1');

        $first = \App\Models\Books::query()->first();
        $this->getJson(self::API . "products/book/{$first->id}/similar")->assertOk()->assertJsonStructure(['data', 'meta' => ['total']]);
        $this->getJson(self::API . 'home')->assertOk();
    }

    public function test_dedupe_kill_switch_restores_previous_behaviour(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat, ['price' => 60000]);
        $b = $this->makeBook($this->makeSeller(), $cat, ['price' => 48000]);

        config(['catalog.dedupe' => false]);
        $ids = collect($this->getJson(self::API . 'products/20')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids, "o'chirgich yoqilganda ikkala taklif ham chiqadi");

        config(['catalog.dedupe' => true]);
        $ids = collect($this->getJson(self::API . 'products/20')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertSame([$b->id], $ids);
    }
}
