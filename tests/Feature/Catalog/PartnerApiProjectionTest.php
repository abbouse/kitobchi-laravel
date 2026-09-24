<?php

namespace Tests\Feature\Catalog;

use App\Models\ApiClient;
use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HAMKOR API — do'konga nima berilishi mumkin.
 *
 * Do'kon o'z tizimini bizga ulaydi. Unga kitobning nomi, ISBN'i va O'ZI
 * kiritgan maydonlari (narx, chegirma, qoldiq, artikul) kerak. Kitob
 * kartasining qolgani (muallif, tavsif, rasmlar, nashriyot, til, muqova,
 * kategoriya, teglar) bizniki — berilmaydi.
 *
 * Eng muhimi: `offers` maydoni RAQOBATCHI do'konlarning narxi va qoldig'i edi.
 */
class PartnerApiProjectionTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    private function partnerHeaders(?int $sellerId = null): array
    {
        $client = ApiClient::query()->create([
            'name' => 'Sinov hamkori',
            'seller_id' => $sellerId,
            'app_id' => 'app_projection_test',
            'app_secret' => 'sec_projection_test_1234567890123456789012',
            'abilities' => ['read', 'stock:write'],
            'is_active' => true,
        ]);

        return ['X-App-ID' => $client->app_id, 'X-App-Secret' => $client->app_secret];
    }

    public function test_book_detail_hides_card_fields_and_rival_offers(): void
    {
        $cat = $this->makeCategory();
        $mine = $this->makeBook($this->makeSeller(), $cat, ['price' => 50000]);
        // Ayni kartada raqobatchi — uning narxi chiqmasligi kerak
        $this->makeBook($this->makeSeller(), $cat, ['price' => 30000]);

        $data = $this->withHeaders($this->partnerHeaders())
            ->getJson('/api/v1/client/products/book/' . $mine->id)
            ->assertOk()
            ->json('data');

        foreach ([
            'author', 'description', 'images', 'image', 'image_urls',
            'medium_images', 'thumb_images', 'publisher', 'lang', 'langType',
            'coverType', 'year', 'pages', 'category', 'category_id', 'tags',
            'offers', 'other_printings', 'min_price', 'offers_count', 'favourite',
        ] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $data, "Hamkorga `{$forbidden}` berilmasligi kerak");
        }

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('isbn', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('stock', $data);
        $this->assertArrayHasKey('artikul', $data);
        $this->assertArrayHasKey('deeplink', $data);
        $this->assertArrayHasKey('web_url', $data['deeplink']);
    }

    public function test_list_endpoints_are_projected_too(): void
    {
        $this->makeBook($this->makeSeller(), $this->makeCategory());

        $rows = $this->withHeaders($this->partnerHeaders())
            ->getJson('/api/v1/client/products/new')
            ->assertOk()
            ->json('data');

        foreach ((array) $rows as $row) {
            if (! is_array($row) || ! array_key_exists('name', $row)) {
                continue;
            }
            $this->assertArrayNotHasKey('author', $row);
            $this->assertArrayNotHasKey('images', $row);
            $this->assertArrayNotHasKey('category', $row);
        }
    }

    public function test_shop_cannot_read_another_shops_store_by_isbn(): void
    {
        $cat = $this->makeCategory();
        $mine = $this->makeBook($this->makeSeller(), $cat);
        $rival = $this->makeBook($this->makeSeller(), $cat);

        $headers = $this->partnerHeaders((int) $mine->seller_id);

        // O'zinikiga ruxsat
        $this->withHeaders($headers)
            ->getJson("/api/v1/client/products/sellers/{$mine->seller_id}/isbn/9780306406157")
            ->assertOk();

        // Raqobatchinikiga yo'q
        $this->withHeaders($headers)
            ->getJson("/api/v1/client/products/sellers/{$rival->seller_id}/isbn/9780306406157")
            ->assertStatus(403);
    }

    public function test_my_products_carries_rating_and_deeplink(): void
    {
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());
        Books::query()->whereKey($book->id)->update([
            'ugc_aggregate_score' => 4.5,
            'ugc_reviews_count' => 12,
            'artikul' => \App\Support\ProductArtikul::generate('book', (int) $book->id),
        ]);

        $row = $this->withHeaders($this->partnerHeaders((int) $book->seller_id))
            ->getJson('/api/v1/client/products/mine')
            ->assertOk()
            ->json('data.0');

        $this->assertSame(4.5, $row['rating']);
        $this->assertSame(12, $row['reviews_count']);
        $this->assertArrayHasKey('deeplink', $row);
        $this->assertArrayHasKey('short_url', $row['deeplink']);
    }

    public function test_my_reviews_returns_only_own_books(): void
    {
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());

        $this->withHeaders($this->partnerHeaders((int) $book->seller_id))
            ->getJson('/api/v1/client/products/mine/reviews')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_messages_are_in_uzbek(): void
    {
        $this->getJson('/api/v1/client/products/mine')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Kalit yuborilmagan.');
    }
}
