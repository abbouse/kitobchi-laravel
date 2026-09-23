<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use App\Models\Author;
use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 3-BOSQICH: "eski tizimdan voz kechish".
 *
 * Kitobning O'ZINIKI bo'lgan ma'lumotlari (nom, muallif, ISBN, muqova, til,
 * sahifa, tavsif, rasmlar) faqat katalog kartasida turadi. Kartaga ULANGAN
 * taklifda ularni HECH BIR yo'l bilan o'zgartirib bo'lmasligi kerak:
 * do'kon API'si, admin formasi, ommaviy (mass) update, model orqali to'g'ridan
 * to'g'ri yozuv — hammasi rad etiladi. Yagona ruxsat: CatalogService::syncOffers.
 */
class CatalogLockTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        config(['catalog.vision_fallback' => false]);
    }

    /** Kartaga ulangan taklif (edition_id bor). */
    private function linkedOffer(): Books
    {
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());
        $this->assertNotNull($book->edition_id, 'Taklif kartaga ulanishi kerak');

        return $book;
    }

    public function test_model_save_cannot_change_book_fields_of_linked_offer(): void
    {
        $book = $this->linkedOffer();

        $book->update([
            'name' => 'BOSHQA NOM',
            'author' => 'Boshqa muallif',
            'isbn' => '9781234567897',
            'description' => 'Boshqa tavsif',
            'images' => ['books/hack.jpg'],
            'price' => 77000,
        ]);

        $fresh = $book->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertSame('Abdulla Qodiriy', $fresh->author);
        $this->assertSame('9780306406157', $fresh->isbn);
        $this->assertSame('Roman', $fresh->description);
        $this->assertSame(['books/a.jpg'], $fresh->images);
        // Taklifga tegishli maydon esa o'zgaradi
        $this->assertSame(77000, (int) $fresh->price);
    }

    public function test_quiet_and_forced_writes_are_also_blocked(): void
    {
        $book = $this->linkedOffer();

        $book->updateQuietly(['name' => 'JIM NOM']);
        $book->forceFill(['author' => 'Majburiy'])->save();
        $book->setAttribute('pages', 999)->saveQuietly();

        $fresh = $book->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertSame('Abdulla Qodiriy', $fresh->author);
        $this->assertSame(300, (int) $fresh->pages);
    }

    public function test_mass_update_skips_linked_offers_but_still_fixes_unlinked(): void
    {
        $linked = $this->linkedOffer();
        $unlinked = $this->makeBook($this->makeSeller(), $this->makeCategory(), ['isbn' => null, 'name' => 'Boshqa kitob']);
        Books::query()->whereKey($unlinked->id)->toBase()->update(['edition_id' => null]);

        Books::query()->where('author', 'Abdulla Qodiriy')->update(['author' => 'YANGI MUALLIF']);

        $this->assertSame('Abdulla Qodiriy', $linked->fresh()->author);
        $this->assertSame('YANGI MUALLIF', $unlinked->fresh()->author);
    }

    public function test_catalog_service_is_the_only_writer(): void
    {
        $book = $this->linkedOffer();
        $edition = BookEdition::query()->findOrFail($book->edition_id);
        $edition->forceFill(['title' => 'Kartadagi yangi nom', 'description' => 'Karta tavsifi'])->save();

        app(CatalogService::class)->syncOffers($edition);

        $fresh = $book->fresh();
        $this->assertSame('Kartadagi yangi nom', $fresh->name);
        $this->assertSame('Karta tavsifi', $fresh->description);
    }

    public function test_seller_api_cannot_send_book_fields_for_linked_offer(): void
    {
        $book = $this->linkedOffer();
        Sanctum::actingAs($book->seller, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/update', [
            'id' => $book->id,
            'name' => 'DO\'KON NOMI',
            'author' => 'Do\'kon muallifi',
            'description' => 'Do\'kon tavsifi',
            'pages' => 10,
            'language' => 'uz', 'languageWrite' => 'latin', 'coverType' => 'soft',
            'category_id' => $book->category_id,
            'price' => 61000,
            'count' => 9,
        ])->assertOk()->assertJson(['catalog_locked' => true]);

        $fresh = $book->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertSame('Abdulla Qodiriy', $fresh->author);
        $this->assertSame(300, (int) $fresh->pages);
        $this->assertSame(61000, (int) $fresh->price);
        $this->assertSame(9, (int) $fresh->totalAvailableStock());
    }

    public function test_admin_form_cannot_change_book_fields_of_linked_offer(): void
    {
        $book = $this->linkedOffer();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'lock@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')
            ->put('/boshqaruv/books/' . $book->id, [
                'name' => 'ADMIN NOMI',
                'author' => 'Admin muallifi',
                'category_id' => $book->category_id,
                'description' => 'Admin tavsifi',
                'price' => 45000,
                'count' => 4,
                'is_approved' => 1,
                'status' => 1,
            ])->assertRedirect();

        $fresh = $book->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertSame('Abdulla Qodiriy', $fresh->author);
        $this->assertSame('Roman', $fresh->description);
        $this->assertSame(45000, (int) $fresh->price);
    }

    /** Kartaga muallif ma'lumotnomasidagi yozuvni bog'laydi. */
    private function withAuthorProfile(Books $book): int
    {
        $author = app(\App\Services\AuthorDirectoryService::class)->resolveOrCreateByName('Abdulla Qodiriy');
        $edition = BookEdition::query()->findOrFail($book->edition_id);
        $edition->forceFill(['author_id' => $author->id, 'author' => $author->name])->save();
        app(CatalogService::class)->syncOffers($edition);

        return (int) $author->id;
    }

    public function test_author_rename_updates_card_and_linked_offer(): void
    {
        $book = $this->linkedOffer();
        $authorId = $this->withAuthorProfile($book);
        $this->assertSame($authorId, (int) $book->fresh()->author_id);

        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'author@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')
            ->put('/boshqaruv/authors/' . $authorId, ['name' => 'Abdulla Qodiriy (tuzatilgan)'])
            ->assertRedirect();

        $this->assertSame('Abdulla Qodiriy (tuzatilgan)', BookEdition::query()->find($book->edition_id)->author);
        $this->assertSame('Abdulla Qodiriy (tuzatilgan)', $book->fresh()->author);
    }

    public function test_seller_sends_correction_request_instead_of_editing(): void
    {
        $book = $this->linkedOffer();
        Sanctum::actingAs($book->seller, ['*'], 'seller');

        $res = $this->postJson('/api/v1/seller/catalog/editions/' . $book->edition_id . '/correction', [
            'field' => 'author',
            'message' => "Muallif ismi noto'g'ri yozilgan",
            'suggested' => 'Abdulla Qodiriy',
        ])->assertStatus(201);

        $submission = BookEditionSubmission::query()->findOrFail($res->json('data.id'));
        $this->assertSame(BookEditionSubmission::TYPE_CORRECTION, $submission->type);
        $this->assertSame(BookEditionSubmission::STATUS_PENDING, $submission->status);
        $this->assertSame((int) $book->edition_id, (int) $submission->edition_id);
        // Karta o'zgarmadi
        $this->assertSame('Abdulla Qodiriy', BookEdition::query()->find($book->edition_id)->author);

        // Ikkinchi ochiq ariza ochilmaydi
        $this->postJson('/api/v1/seller/catalog/editions/' . $book->edition_id . '/correction', [
            'message' => 'Yana bir marta',
        ])->assertStatus(409)->assertJson(['code' => 'correction_pending']);
    }

    public function test_rejecting_correction_does_not_reject_the_offer(): void
    {
        $book = $this->linkedOffer();
        Sanctum::actingAs($book->seller, ['*'], 'seller');
        $id = $this->postJson('/api/v1/seller/catalog/editions/' . $book->edition_id . '/correction', [
            'message' => "Sahifa soni noto'g'ri",
        ])->assertStatus(201)->json('data.id');

        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'fix@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')
            ->post('/boshqaruv/catalog/submissions/' . $id . '/reject', ['reason' => 'Xato emas'])
            ->assertRedirect();

        $this->assertSame(BookEditionSubmission::STATUS_REJECTED, BookEditionSubmission::query()->find($id)->status);
        // Do'kon taklifi va karta tegilmagan
        $this->assertSame(1, (int) $book->fresh()->is_approved);
        $this->assertSame(BookEdition::STATUS_ACTIVE, BookEdition::query()->find($book->edition_id)->status);
    }

    public function test_sync_offers_command_repairs_drift(): void
    {
        $book = $this->linkedOffer();
        $edition = BookEdition::query()->findOrFail($book->edition_id);
        $edition->forceFill(['title' => 'Karta nomi'])->save();
        // Qo'lda (SQL) buzilgan kesh — model qulfidan o'tmaydigan yo'l
        DB::table('books')->where('id', $book->id)->update(['name' => 'ESKI NOM']);

        $this->artisan('catalog:sync-offers')->assertExitCode(0);

        $this->assertSame('Karta nomi', $book->fresh()->name);
    }

    public function test_seller_cannot_open_another_shops_pending_card(): void
    {
        $other = $this->makeSeller();
        $book = $this->makeBook($other, $this->makeCategory(), ['is_approved' => 0]);
        $edition = BookEdition::query()->findOrFail($book->edition_id);
        $edition->forceFill([
            'status' => BookEdition::STATUS_PENDING,
            'source' => 'seller',
            'created_by_type' => 'seller',
            'created_by_id' => $other->id,
        ])->save();

        $intruder = $this->makeSeller();
        Sanctum::actingAs($intruder, ['*'], 'seller');

        $this->getJson('/api/v1/seller/catalog/editions/' . $edition->id)->assertStatus(404);
        $this->postJson('/api/v1/seller/catalog/offers', [
            'edition_id' => $edition->id, 'price' => 10000, 'count' => 1,
        ])->assertStatus(404);
        $this->postJson('/api/v1/seller/catalog/editions/' . $edition->id . '/correction', [
            'message' => "Menga ko'rinib turibdi",
        ])->assertStatus(404);
    }

    public function test_verifying_a_card_does_not_close_correction_requests(): void
    {
        $book = $this->linkedOffer();
        $edition = BookEdition::query()->findOrFail($book->edition_id);
        $edition->forceFill(['verified_at' => null])->save();

        Sanctum::actingAs($book->seller, ['*'], 'seller');
        $correctionId = $this->postJson('/api/v1/seller/catalog/editions/' . $edition->id . '/correction', [
            'message' => "Muqova rasmi boshqa kitobniki",
        ])->assertStatus(201)->json('data.id');

        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'verify@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);
        $this->actingAs($admin, 'panel')->post('/boshqaruv/catalog/' . $edition->id . '/verify')->assertRedirect();

        $this->assertSame(
            BookEditionSubmission::STATUS_PENDING,
            BookEditionSubmission::query()->find($correctionId)->status,
            'Tuzatish taklifi kartani tasdiqlash bilan yopilmasligi kerak'
        );
    }

    public function test_mass_update_still_applies_non_catalog_columns_to_linked_offers(): void
    {
        $book = $this->linkedOffer();

        Books::query()->whereKey($book->id)->update(['name' => 'YANGI NOM', 'is_hidden' => true]);

        $fresh = $book->fresh();
        $this->assertSame("O'tkan kunlar", $fresh->name);
        $this->assertTrue((bool) $fresh->is_hidden, 'Kitob maydoni kesilsa ham qolgan ustunlar yozilishi kerak');
    }

    public function test_stock_by_code_really_changes_book_stock(): void
    {
        $book = $this->linkedOffer();
        Sanctum::actingAs($book->seller, ['*'], 'seller');

        $this->postJson('/api/v1/seller/products/stock/by-code', [
            'code' => $book->isbn, 'type' => 'book', 'stock' => 42,
        ])->assertOk()->assertJson(['success' => true, 'stock' => 42]);

        $this->assertSame(42, (int) $book->fresh()->totalAvailableStock());
    }

    public function test_author_delete_clears_card_and_linked_offer(): void
    {
        $book = $this->linkedOffer();
        $authorId = $this->withAuthorProfile($book);
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'del@test.uz', 'password' => bcrypt('x'),
            'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')->delete('/boshqaruv/authors/' . $authorId)->assertRedirect();

        $this->assertNull(Author::query()->find($authorId));
        $this->assertNull(BookEdition::query()->find($book->edition_id)->author_id);
        $this->assertNull($book->fresh()->author_id);
    }
}
