<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

class SearchRichSuggestionsTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        Cache::flush();
    }

    public function test_rich_suggestions_are_grouped_and_legacy_list_kept(): void
    {
        $category = $this->makeCategory();
        $seller = $this->makeSeller(['shop_name' => "Qodiriy kitoblari"]);
        $this->makeBook($seller, $category);
        $this->makeBook($this->makeSeller(), $category); // o'sha nashr, boshqa do'kon

        $res = $this->getJson('/api/v1/kitobchi/search/suggestions?q=tkan&rich=1')->assertOk();
        $this->assertNotEmpty($res->json('data'), 'Eski ilovalar uchun oddiy ro\'yxat');
        $books = $res->json('groups.books');
        $this->assertCount(1, $books, 'Bir nashr — bitta karta');
        $this->assertSame("O'tkan kunlar", $books[0]['name']);
        $this->assertSame(2, $books[0]['offers_count']);

        $shops = $this->getJson('/api/v1/kitobchi/search/suggestions?q=Qodiriy&rich=1')->json('groups.shops');
        $this->assertSame('Qodiriy kitoblari', $shops[0]['shop_name'] ?? null);

        $legacy = $this->getJson('/api/v1/kitobchi/search/suggestions?q=tkan')->assertOk();
        $this->assertNull($legacy->json('groups'));
    }
}
