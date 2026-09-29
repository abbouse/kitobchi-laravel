<?php

namespace Tests\Feature\Catalog;

use App\Models\BookEdition;
use App\Models\Books;
use App\Support\ProductPayloadFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Oldindan buyurtma: faqat hali chiqmagan kitob, jo'natish kuni kelajakda.
 */
class BookPreorderTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_seller_puts_new_book_on_preorder_and_can_switch_it_off(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $book = $this->makeBook($shop, $cat, ['year' => (int) now()->year], 20);
        $release = today()->addDays(20)->toDateString();
        Sanctum::actingAs($shop, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/update', [
            'id' => $book->id, 'price' => 60000, 'discountPrice' => 0, 'count' => 20,
            'preorder_release_date' => $release,
        ])->assertOk();

        $fresh = Books::find($book->id);
        $this->assertSame($release, $fresh->preorder_release_date->toDateString());
        $this->assertTrue($fresh->isPreorderActive());

        $payload = ProductPayloadFormatter::format($fresh, ['type' => 'book']);
        $this->assertTrue($payload['is_preorder']);
        $this->assertSame($release, $payload['preorder_release_date']);

        // O'tgan sana — rad
        $this->postJson('/api/v1/seller/products/update', [
            'id' => $book->id, 'price' => 60000, 'count' => 20,
            'preorder_release_date' => today()->toDateString(),
        ])->assertStatus(422)->assertJsonPath('code', 'preorder_not_allowed');

        // O'chirish
        $this->postJson('/api/v1/seller/products/update', [
            'id' => $book->id, 'price' => 60000, 'count' => 20, 'preorder_release_date' => '',
        ])->assertOk();
        $this->assertNull(Books::find($book->id)->preorder_release_date);
    }

    public function test_old_or_already_released_books_cannot_be_preordered(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $other = $this->makeSeller();
        $old = $this->makeBook($shop, $cat, ['year' => 2019, 'isbn' => '9780262033848'], 5);
        Sanctum::actingAs($shop, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/update', [
            'id' => $old->id, 'price' => 50000, 'count' => 5,
            'preorder_release_date' => today()->addDays(10)->toDateString(),
        ])->assertStatus(422);

        // Boshqa do'konda sotuvda bo'lgan yangi kitob
        $mine = $this->makeBook($shop, $cat, ['year' => (int) now()->year], 0);
        $this->makeBook($other, $cat, ['year' => (int) now()->year], 3);
        $this->postJson('/api/v1/seller/products/update', [
            'id' => $mine->id, 'price' => 50000, 'count' => 10,
            'preorder_release_date' => today()->addDays(10)->toDateString(),
        ])->assertStatus(422)->assertJsonPath('code', 'preorder_not_allowed');
    }

    public function test_new_offer_from_catalog_can_start_as_preorder(): void
    {
        $cat = $this->makeCategory();
        $shop = $this->makeSeller();
        $publisherShop = $this->makeSeller();
        $seed = $this->makeBook($publisherShop, $cat, ['year' => (int) now()->year], 0);
        $edition = BookEdition::find($seed->edition_id);
        Sanctum::actingAs($shop, ['*'], 'seller');

        $release = today()->addDays(20)->toDateString();
        $this->postJson('/api/v1/seller/catalog/offers', [
            'edition_id' => $edition->id, 'price' => 70000, 'count' => 50,
            'preorder_release_date' => $release,
        ])->assertCreated()
            ->assertJsonPath('offer.is_preorder', true)
            ->assertJsonPath('offer.preorder_release_date', $release);
    }
}
