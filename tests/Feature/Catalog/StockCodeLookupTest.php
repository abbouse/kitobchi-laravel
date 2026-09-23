<?php

namespace Tests\Feature\Catalog;

use App\Models\Books;
use App\Support\StockCodeLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QOLDIQNI KOD BO'YICHA YOZISH — KATALOGDAN KEYIN.
 *
 * Bir xil ISBN ostida qattiq va yumshoq muqovali alohida kartalar bo'lishi
 * mumkin (`CatalogVariantTest`). Do'konda ikkalasi ham tursa, eski
 * `whereIsbn(...)->first()` tasodifiy taklifni tanlab, noto'g'ri kitobning
 * qoldig'ini yozardi.
 */
class StockCodeLookupTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_single_isbn_match_is_returned(): void
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory());

        $res = StockCodeLookup::findSellerBook((int) $seller->id, '9780306406157');

        $this->assertNotNull($res['book']);
        $this->assertSame((int) $book->id, (int) $res['book']->id);
    }

    public function test_same_isbn_two_printings_is_ambiguous_not_random(): void
    {
        $seller = $this->makeSeller();
        $cat = $this->makeCategory();
        $hard = $this->makeBook($seller, $cat, ['coverType' => 'hard']);
        $soft = $this->makeBook($seller, $cat, ['coverType' => 'soft']);

        $res = StockCodeLookup::findSellerBook((int) $seller->id, '9780306406157');

        $this->assertNull($res['book'], 'Noaniq holatda tasodifiy taklif tanlanmasligi kerak');
        $this->assertSame('ambiguous', $res['reason']);
        $this->assertCount(2, $res['matches']);

        $ids = array_column(StockCodeLookup::candidates($res['matches']), 'id');
        $this->assertContains((int) $hard->id, $ids);
        $this->assertContains((int) $soft->id, $ids);
    }

    public function test_artikul_resolves_one_printing_exactly(): void
    {
        $seller = $this->makeSeller();
        $cat = $this->makeCategory();
        $hard = $this->makeBook($seller, $cat, ['coverType' => 'hard']);
        $this->makeBook($seller, $cat, ['coverType' => 'soft']);

        $artikul = \App\Support\ProductArtikul::generate('book', (int) $hard->id);
        $hard->forceFill(['artikul' => $artikul])->save();

        $res = StockCodeLookup::findSellerBook((int) $seller->id, $artikul);

        $this->assertNotNull($res['book']);
        $this->assertSame((int) $hard->id, (int) $res['book']->id);
    }

    public function test_archived_offer_is_not_matched(): void
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory());
        $book->forceFill(['archived_at' => now()])->save();

        $res = StockCodeLookup::findSellerBook((int) $seller->id, '9780306406157');

        $this->assertNull($res['book']);
        $this->assertSame('not_found', $res['reason']);
    }

    public function test_other_sellers_book_is_not_matched(): void
    {
        $mine = $this->makeSeller();
        $other = $this->makeSeller();
        $this->makeBook($other, $this->makeCategory());

        $res = StockCodeLookup::findSellerBook((int) $mine->id, '9780306406157');

        $this->assertNull($res['book']);
        $this->assertSame('not_found', $res['reason']);
    }

    public function test_legacy_book_create_endpoint_is_closed(): void
    {
        $seller = $this->makeSeller();
        \Laravel\Sanctum\Sanctum::actingAs($seller, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/create', ['name' => 'X'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'catalog_only');
    }
}
