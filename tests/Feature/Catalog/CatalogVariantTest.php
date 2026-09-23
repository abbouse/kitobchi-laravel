<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use App\Models\BookEdition;
use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * BIR XIL ISBN — BOSHQA NASHR.
 *
 * ISBN standarti bo'yicha qattiq va yumshoq muqova, boshqa til yoki tarjima
 * alohida ISBN olishi kerak. O'zbekiston/MDH amaliyotida nashriyotlar ISBN'ni
 * qayta ishlatadi, shuning uchun bitta ISBN ostida fizik jihatdan boshqa kitob
 * chiqishi mumkin. Ular BITTA kartaga qo'shilmasligi kerak — aks holda do'kon
 * qattiq muqovali kitobni sotib, mijozga "yumshoq muqova" ko'rsatilardi (va
 * do'kon buni tuzata olmaydi, chunki kitob ma'lumoti kartada qulflangan).
 */
class CatalogVariantTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        config(['catalog.vision_fallback' => false]);
    }

    public function test_same_isbn_different_cover_creates_separate_cards(): void
    {
        $cat = $this->makeCategory();
        $soft = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        $hard = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard']);

        $this->assertNotNull($soft->edition_id);
        $this->assertNotNull($hard->edition_id);
        $this->assertNotSame(
            (int) $soft->edition_id,
            (int) $hard->edition_id,
            "Qattiq va yumshoq muqova bitta kartaga qo'shilmasligi kerak"
        );
        // Ikkalasi ham ro'yxatda ko'rinadi (har biri o'z kartasining yagona taklifi)
        $this->assertTrue((bool) $soft->fresh()->catalog_featured);
        $this->assertTrue((bool) $hard->fresh()->catalog_featured);
    }

    public function test_same_isbn_same_cover_still_shares_one_card(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard', 'price' => 60000]);
        $b = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard', 'price' => 50000]);

        $this->assertSame((int) $a->edition_id, (int) $b->edition_id);
    }

    public function test_empty_cover_does_not_split_the_catalog(): void
    {
        $cat = $this->makeCategory();
        $known = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard']);
        $unknown = $this->makeBook($this->makeSeller(), $cat, ['coverType' => '']);

        $this->assertSame(
            (int) $known->edition_id,
            (int) $unknown->edition_id,
            "Ma'lumot yo'q bo'lsa ajratilmaydi — aks holda eski katalog bo'linib ketardi"
        );
    }

    public function test_different_language_creates_separate_cards(): void
    {
        $cat = $this->makeCategory();
        $uz = $this->makeBook($this->makeSeller(), $cat, ['lang' => 'uz']);
        $ru = $this->makeBook($this->makeSeller(), $cat, ['lang' => 'ru']);

        $this->assertNotSame((int) $uz->edition_id, (int) $ru->edition_id);
    }

    public function test_seller_offer_on_wrong_variant_is_rejected(): void
    {
        $cat = $this->makeCategory();
        $card = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        BookEdition::query()->update(['verified_at' => now()]);

        $seller = $this->makeSeller();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $this->postJson('/api/v1/seller/catalog/offers', [
            'edition_id' => $card->edition_id,
            'price' => 55000,
            'count' => 2,
            'coverType' => 'hard',
        ])->assertStatus(409)->assertJson(['code' => 'variant_mismatch']);

        // Mos muqova bilan — odatdagidek qo'shiladi
        $this->postJson('/api/v1/seller/catalog/offers', [
            'edition_id' => $card->edition_id,
            'price' => 55000,
            'count' => 2,
            'coverType' => 'soft',
        ])->assertStatus(201);
    }

    public function test_submission_with_same_isbn_but_other_cover_opens_new_card(): void
    {
        $cat = $this->makeCategory();
        $existing = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        BookEdition::query()->update(['verified_at' => now(), 'status' => BookEdition::STATUS_ACTIVE]);

        $seller = $this->makeSeller();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $response = $this->post('/api/v1/seller/catalog/submissions', [
            'isbn' => '9780306406157',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'name' => "O'tkan kunlar",
            'author' => 'Abdulla Qodiriy',
            'pages' => 300,
            'language' => 'uz',
            'languageWrite' => 'latin',
            'coverType' => 'hard',
            'description' => 'Qattiq muqovali nashr',
            'category_id' => $cat,
            'price' => 70000,
            'count' => 2,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertNotSame(
            (int) $existing->edition_id,
            (int) $response->json('data.edition_id'),
            'Boshqa muqova — alohida karta ochilishi kerak'
        );
        $this->assertSame(2, BookEdition::query()->where('isbn13', '9780306406157')->count());
    }

    public function test_submission_with_same_isbn_and_same_cover_is_still_blocked(): void
    {
        $cat = $this->makeCategory();
        $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        BookEdition::query()->update(['verified_at' => now(), 'status' => BookEdition::STATUS_ACTIVE]);

        Sanctum::actingAs($this->makeSeller(), ['*'], 'seller');

        $this->post('/api/v1/seller/catalog/submissions', [
            'isbn' => '9780306406157',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'name' => "O'tkan kunlar",
            'author' => 'Abdulla Qodiriy',
            'pages' => 300,
            'language' => 'uz',
            'languageWrite' => 'latin',
            'coverType' => 'soft',
            'description' => 'Xuddi shu nashr',
            'category_id' => $cat,
            'price' => 70000,
            'count' => 2,
        ], ['Accept' => 'application/json'])->assertStatus(409)->assertJson(['code' => 'edition_exists']);
    }

    public function test_customer_book_page_lists_other_printings(): void
    {
        $cat = $this->makeCategory();
        $soft = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft', 'price' => 40000]);
        $hard = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard', 'price' => 65000]);
        BookEdition::query()->update(['status' => BookEdition::STATUS_ACTIVE, 'verified_at' => now()]);
        app(\App\Services\Catalog\BuyBoxService::class)->recomputeAll();

        $payload = $this->getJson('/api/v1/kitobchi/share/product/' . $soft->id . '?type=book')->assertOk();

        $printings = $payload->json('data.other_printings');
        $this->assertCount(1, $printings);
        $this->assertSame((int) $hard->edition_id, $printings[0]['edition_id']);
        $this->assertSame($hard->id, $printings[0]['product_id']);
        $this->assertStringContainsString('Qattiq', $printings[0]['variant']);
    }

    public function test_admin_cannot_merge_different_printings_without_force(): void
    {
        $cat = $this->makeCategory();
        $soft = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        $hard = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard']);

        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'variant@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')
            ->post('/boshqaruv/catalog/' . $soft->edition_id . '/merge', ['into_id' => $hard->edition_id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame((int) $soft->edition_id, (int) $soft->fresh()->edition_id);

        // Admin ataylab birlashtirsa — ruxsat
        $this->actingAs($admin, 'panel')
            ->post('/boshqaruv/catalog/' . $soft->edition_id . '/merge', ['into_id' => $hard->edition_id, 'force' => 1])
            ->assertRedirect();

        $this->assertSame((int) $hard->edition_id, (int) $soft->fresh()->edition_id);
    }

    /**
     * KRITIK: kartadagi qiymat ilovaga chiqib, keyin qaytib kelganda o'ziga
     * o'zi mos kelishi shart. Ilgari "Русский" → ilovaga "uz" ketib, qaytib
     * kelganda server uni "boshqa nashr" deb rad etardi (409).
     */
    public function test_card_value_round_trips_without_false_mismatch(): void
    {
        $cat = $this->makeCategory();
        $book = $this->makeBook($this->makeSeller(), $cat, [
            'lang' => 'Русский', 'langType' => 'Кириллица', 'coverType' => 'Твёрдый',
        ]);
        BookEdition::query()->update(['verified_at' => now(), 'status' => BookEdition::STATUS_ACTIVE]);

        $seller = $this->makeSeller();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $lookup = $this->getJson('/api/v1/seller/catalog/lookup?isbn=9780306406157')->assertOk();
        $edition = $lookup->json('editions.0');
        $this->assertSame('ru', $edition['language']);
        $this->assertSame('cyrillic', $edition['languageWrite']);
        $this->assertSame('hard', $edition['coverType']);

        // Ilova kartadan olgan qiymatlarni qaytarib yuboradi — rad etilmasligi kerak
        $this->postJson('/api/v1/seller/catalog/offers', [
            'edition_id' => $edition['id'],
            'price' => 50000,
            'count' => 1,
            'coverType' => $edition['coverType'],
            'language' => $edition['language'],
            'languageWrite' => $edition['languageWrite'],
        ])->assertStatus(201);
    }

    public function test_sync_does_not_stamp_defaults_onto_offers(): void
    {
        $cat = $this->makeCategory();
        $hard = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'Qattiq', 'pages' => 512]);
        $edition = BookEdition::query()->findOrFail($hard->edition_id);
        // Kartada muqova/sahifa yo'q (eski backfill kartalarida uchraydi)
        $edition->forceFill(['coverType' => null, 'pages' => null, 'description' => 'Yangi tavsif'])->save();

        app(\App\Services\Catalog\CatalogService::class)->syncOffers($edition);

        $fresh = $hard->fresh();
        $this->assertSame('Yangi tavsif', $fresh->description, 'Kartadagi tavsif ko\'chishi kerak');
        $this->assertSame('Qattiq', $fresh->coverType, "Kartada bo'sh maydon taklifni bosib yozmasligi kerak");
        $this->assertSame(512, (int) $fresh->pages);
    }

    public function test_other_printings_skips_unrelated_book_with_reused_isbn(): void
    {
        $cat = $this->makeCategory();
        $mine = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        $sameIsbnOtherBook = $this->makeBook($this->makeSeller(), $cat, [
            'coverType' => 'hard', 'name' => 'Butunlay boshqa kitob',
        ]);
        BookEdition::query()->update(['status' => BookEdition::STATUS_ACTIVE, 'verified_at' => now()]);
        app(\App\Services\Catalog\BuyBoxService::class)->recomputeAll();

        $this->assertNotSame((int) $mine->edition_id, (int) $sameIsbnOtherBook->edition_id);

        $printings = $this->getJson('/api/v1/kitobchi/share/product/' . $mine->id . '?type=book')
            ->assertOk()->json('data.other_printings');

        $this->assertSame([], $printings, "Qayta ishlatilgan ISBN'li begona kitob ko'rsatilmasligi kerak");
    }

    public function test_backfill_keeps_printings_apart(): void
    {
        $cat = $this->makeCategory();
        $soft = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'soft']);
        $hard = $this->makeBook($this->makeSeller(), $cat, ['coverType' => 'hard']);

        // Eski holatga qaytaramiz (katalogsiz)
        Books::query()->toBase()->update(['edition_id' => null]);
        BookEdition::query()->forceDelete();

        $this->artisan('catalog:backfill')->assertExitCode(0);

        $this->assertSame(2, BookEdition::query()->count());
        $this->assertNotSame((int) $soft->fresh()->edition_id, (int) $hard->fresh()->edition_id);
    }
}
