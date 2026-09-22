<?php

namespace Tests\Feature\Catalog;

use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerCatalogApiTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const API = '/api/v1/seller/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysql();
        Storage::fake('public');
        config(['catalog.vision_fallback' => false]);
    }

    private function actingSeller()
    {
        $seller = $this->makeSeller();
        Sanctum::actingAs($seller, ['*'], 'seller');

        return $seller;
    }

    public function test_lookup_rejects_bad_checksum(): void
    {
        $this->actingSeller();
        $this->getJson(self::API . 'catalog/lookup?isbn=9780306406158')
            ->assertStatus(422)
            ->assertJson(['code' => 'isbn_checksum']);
    }

    public function test_found_book_is_added_with_price_and_stock_only(): void
    {
        $cat = $this->makeCategory();
        $original = $this->makeBook($this->makeSeller(), $cat, ['price' => 60000]);
        BookEdition::query()->update(['verified_at' => now()]);
        $seller = $this->actingSeller();

        $lookup = $this->getJson(self::API . 'catalog/lookup?isbn=0306406152')->assertOk();
        $lookup->assertJson(['found' => true, 'isbn13' => '9780306406157']);
        $editionId = $lookup->json('editions.0.id');
        $this->assertSame($original->edition_id, $editionId);
        $this->assertSame([], $lookup->json('editions.0.my_offers'));

        $res = $this->postJson(self::API . 'catalog/offers', [
            'edition_id' => $editionId, 'price' => 52000, 'count' => 3,
        ])->assertStatus(201);

        $offer = Books::find($res->json('offer.id'));
        $this->assertSame($seller->id, (int) $offer->seller_id);
        $this->assertSame(1, (int) $offer->is_approved);
        $this->assertSame("O'tkan kunlar", $offer->name);
        $this->assertSame(3, $offer->count);
        $this->assertTrue((bool) $offer->catalog_featured);
        $this->assertFalse((bool) $original->fresh()->catalog_featured);
        $this->assertNotEmpty($offer->artikul);

        $this->postJson(self::API . 'catalog/offers', [
            'edition_id' => $editionId, 'price' => 50000, 'count' => 1,
        ])->assertStatus(409)->assertJson(['code' => 'offer_exists']);
    }

    public function test_submission_requires_both_covers(): void
    {
        $this->actingSeller();
        $this->postJson(self::API . 'catalog/submissions', $this->submissionFields([
            'front_image' => UploadedFile::fake()->image('front.jpg'),
        ]))->assertStatus(422)->assertJsonValidationErrors(['back_image']);
    }

    public function test_submission_creates_pending_edition_offer_and_ticket(): void
    {
        $this->makeCategory();
        $seller = $this->actingSeller();

        $res = $this->post(self::API . 'catalog/submissions', $this->submissionFields([
            'front_image' => UploadedFile::fake()->image('front.jpg', 600, 900),
            'back_image' => UploadedFile::fake()->image('back.jpg', 600, 900),
            'back_isbn_client' => '9789943081239',
        ]), ['Accept' => 'application/json'])->assertStatus(201);

        $res->assertJson(['isbn_check' => 'matched']);
        $submission = BookEditionSubmission::first();
        $this->assertSame('pending', $submission->status);
        $edition = BookEdition::find($submission->edition_id);
        $this->assertSame(BookEdition::STATUS_PENDING, $edition->status);
        $this->assertSame('9789943081239', $edition->isbn13);
        $this->assertCount(2, $edition->images);
        $offer = Books::find($submission->book_id);
        $this->assertSame((int) $edition->id, (int) $offer->edition_id);
        $this->assertSame($seller->id, (int) $offer->seller_id);
        $this->assertSame(0, (int) $offer->is_approved);
        $this->assertSame(4, $offer->count);
    }

    public function test_submission_for_existing_isbn_redirects_to_offer_flow(): void
    {
        $cat = $this->makeCategory();
        $this->makeBook($this->makeSeller(), $cat, ['isbn' => '9789943081239', 'name' => 'Kitob nomi']);
        $this->actingSeller();

        $this->post(self::API . 'catalog/submissions', $this->submissionFields([
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
        ]), ['Accept' => 'application/json'])->assertStatus(409)->assertJson(['code' => 'edition_exists']);
    }

    public function test_back_cover_barcode_mismatch_is_rejected(): void
    {
        if (! app(\App\Services\Catalog\BarcodeIsbnReader::class)->zbarAvailable()) {
            $this->markTestSkipped('zbarimg o\'rnatilmagan');
        }
        $this->makeCategory();
        $this->actingSeller();

        $back = UploadedFile::fake()->createWithContent('back.png', $this->ean13Png('9780306406157'));

        $this->post(self::API . 'catalog/submissions', $this->submissionFields([
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => $back,
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson(['code' => 'isbn_mismatch', 'back_isbn' => '9780306406157']);

        $this->assertSame(0, BookEdition::count());
    }

    public function test_back_cover_barcode_match_is_verified_on_server(): void
    {
        if (! app(\App\Services\Catalog\BarcodeIsbnReader::class)->zbarAvailable()) {
            $this->markTestSkipped('zbarimg o\'rnatilmagan');
        }
        $this->makeCategory();
        $this->actingSeller();

        $this->post(self::API . 'catalog/submissions', $this->submissionFields([
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->createWithContent('back.png', $this->ean13Png('9789943081239')),
        ]), ['Accept' => 'application/json'])->assertStatus(201)->assertJson(['isbn_check' => 'matched']);

        $this->assertSame('zbar', BookEditionSubmission::first()->back_isbn_server_method);
    }

    public function test_legacy_update_of_catalog_locked_offer_changes_only_price_and_stock(): void
    {
        $cat = $this->makeCategory();
        $this->makeBook($this->makeSeller(), $cat);
        $seller = $this->actingSeller();
        $mine = $this->makeBook($seller, $cat, ['price' => 50000]);
        $this->assertNotNull($mine->edition_id);

        $this->postJson(self::API . 'products/update', [
            'id' => $mine->id, 'name' => 'Boshqa nom', 'author' => 'X', 'pages' => 10,
            'language' => 'uz', 'languageWrite' => 'latin', 'coverType' => 'soft',
            'price' => 47000, 'count' => 9, 'description' => 'd', 'category_id' => $cat,
            'existingImages' => json_encode(['books/a.jpg']),
        ])->assertOk()->assertJson(['catalog_locked' => true]);

        $fresh = $mine->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertSame(47000, (int) $fresh->price);
        $this->assertSame(9, $fresh->count);
    }

    private function submissionFields(array $extra = []): array
    {
        return $extra + [
            'isbn' => '978-9943-08-123-9',
            'name' => 'Kitob nomi', 'author' => 'Muallif', 'pages' => 120, 'year' => 2024,
            'language' => 'uz', 'languageWrite' => 'latin', 'coverType' => 'soft',
            'description' => 'Tavsif', 'category_id' => \DB::table('book_categories')->value('id'),
            'price' => 30000, 'count' => 4,
        ];
    }

    /** Test uchun haqiqiy EAN-13 shtrix-kod rasmi (GD). */
    private function ean13Png(string $code): string
    {
        $L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
        $G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
        $R = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];
        $parity = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];
        $bits = '101';
        $p = $parity[(int) $code[0]];
        for ($i = 1; $i <= 6; $i++) {
            $bits .= $p[$i - 1] === 'L' ? $L[(int) $code[$i]] : $G[(int) $code[$i]];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $bits .= $R[(int) $code[$i]];
        }
        $bits .= '101';

        $scale = 4;
        $img = imagecreatetruecolor((strlen($bits) + 20) * $scale, 200);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);
        foreach (str_split($bits) as $i => $bit) {
            if ($bit === '1') {
                imagefilledrectangle($img, (10 + $i) * $scale, 20, (11 + $i) * $scale - 1, 180, $black);
            }
        }
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }
}
