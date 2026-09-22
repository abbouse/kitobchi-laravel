<?php

namespace Tests\Feature\Catalog;

use App\Models\BookEdition;
use App\Models\Books;
use App\Services\BranchStockService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCoreTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysql();
    }

    public function test_isbn_normalization(): void
    {
        $this->assertSame('9780306406157', Isbn::toIsbn13('978-0-306-40615-7'));
        $this->assertSame('9780306406157', Isbn::toIsbn13('0306406152'));
        $this->assertNull(Isbn::toIsbn13('9780306406158'));
        $this->assertSame('checksum', Isbn::problem('9780306406158'));
    }

    public function test_offers_with_same_isbn_share_one_edition_and_only_one_is_featured(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeSeller();
        $b = $this->makeSeller();

        $expensive = $this->makeBook($a, $cat, ['price' => 60000]);
        $cheap = $this->makeBook($b, $cat, ['price' => 45000, 'name' => "O‘tkan kunlar (roman)"]);

        $this->assertNotNull($expensive->edition_id);
        $this->assertSame($expensive->edition_id, $cheap->edition_id);
        $this->assertSame(1, BookEdition::count());

        $this->assertFalse((bool) $expensive->fresh()->catalog_featured);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        $edition = BookEdition::first();
        $this->assertSame(2, $edition->offers_count);
        $this->assertSame(45000, $edition->min_price);
        $this->assertSame(1, Books::query()->catalogFeatured()->count());
    }

    public function test_out_of_stock_offer_loses_buy_box(): void
    {
        $cat = $this->makeCategory();
        $cheap = $this->makeBook($this->makeSeller(), $cat, ['price' => 40000]);
        $other = $this->makeBook($this->makeSeller(), $cat, ['price' => 55000]);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        app(BranchStockService::class)->setTotalFromLegacy('book', $cheap->id, 0, $cheap->seller_id, 0);

        $this->assertFalse((bool) $cheap->fresh()->catalog_featured);
        $this->assertTrue((bool) $other->fresh()->catalog_featured);
        $this->assertSame(1, BookEdition::first()->in_stock_offers_count);
    }

    public function test_hidden_offer_and_hidden_seller_are_not_featured(): void
    {
        $cat = $this->makeCategory();
        $s1 = $this->makeSeller();
        $cheap = $this->makeBook($s1, $cat, ['price' => 30000]);
        $other = $this->makeBook($this->makeSeller(), $cat, ['price' => 50000]);

        $cheap->update(['is_hidden' => true]);
        $this->assertTrue((bool) $other->fresh()->catalog_featured);

        $cheap->update(['is_hidden' => false]);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        $s1->update(['is_hidden' => 1]);
        $this->assertFalse((bool) $cheap->fresh()->catalog_featured);
        $this->assertTrue((bool) $other->fresh()->catalog_featured);
    }

    public function test_same_isbn_different_title_gets_separate_edition(): void
    {
        $cat = $this->makeCategory();
        $this->makeBook($this->makeSeller(), $cat);
        $this->makeBook($this->makeSeller(), $cat, ['name' => 'Kimyo darsligi 7-sinf']);

        $this->assertSame(2, BookEdition::count());
        $this->assertSame(2, Books::query()->catalogFeatured()->count());
    }

    public function test_books_without_isbn_group_only_on_exact_key(): void
    {
        $cat = $this->makeCategory();
        $this->makeBook($this->makeSeller(), $cat, ['isbn' => null]);
        $this->makeBook($this->makeSeller(), $cat, ['isbn' => null]);
        $this->makeBook($this->makeSeller(), $cat, ['isbn' => null, 'langType' => 'cyrillic']);

        $this->assertSame(2, BookEdition::count());
    }

    public function test_merge_moves_offers_and_syncs_metadata(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat);
        $b = $this->makeBook($this->makeSeller(), $cat, ['isbn' => null, 'name' => 'Otkan kunlar']);
        $into = BookEdition::find($a->edition_id);
        $from = BookEdition::find($b->edition_id);
        $this->assertNotSame($into->id, $from->id);

        app(CatalogService::class)->merge($from, $into);

        $this->assertSame($into->id, (int) $b->fresh()->edition_id);
        $this->assertSame("O'tkan kunlar", $b->fresh()->name);
        $this->assertSame(BookEdition::STATUS_MERGED, $from->fresh()->status);
        $this->assertSame(1, Books::query()->catalogFeatured()->count());
    }

    public function test_backfill_links_legacy_books(): void
    {
        config(['catalog.auto_link' => false]);
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat, ['price' => 70000]);
        $b = $this->makeBook($this->makeSeller(), $cat, ['price' => 65000]);
        $c = $this->makeBook($this->makeSeller(), $cat, ['isbn' => null, 'name' => 'Sariq devni minib']);
        $this->assertNull($a->fresh()->edition_id);

        $this->artisan('catalog:backfill --dry-run')->assertSuccessful();
        $this->assertSame(0, BookEdition::count());
        $this->assertNull($a->fresh()->edition_id);

        $this->artisan('catalog:backfill')->assertSuccessful();
        $this->assertSame(2, BookEdition::count());
        $this->assertSame($a->fresh()->edition_id, $b->fresh()->edition_id);
        $this->assertTrue((bool) $b->fresh()->catalog_featured);
        $this->assertFalse((bool) $a->fresh()->catalog_featured);
        $this->assertTrue((bool) $c->fresh()->catalog_featured);
    }
}
