<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        Storage::fake('public');
        config(['catalog.vision_fallback' => false]);
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'root@test.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $this->actingAs($admin, 'panel');
    }

    public function test_admin_adds_book_to_shop_via_new_edition_and_archives_it(): void
    {
        $cat = $this->makeCategory();
        $seller = $this->makeSeller();

        $this->post('/boshqaruv/books', [
            'seller_id' => $seller->id, 'price' => 40000, 'count' => 7,
            'title' => 'Yangi kitob', 'author' => 'Muallif', 'isbn' => '9789943081239',
            'category_id' => $cat, 'lang' => "O'zbek", 'langType' => 'Lotin', 'coverType' => 'Qattiq', 'pages' => 200,
            'front_image' => UploadedFile::fake()->image('f.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $book = Books::query()->latest('id')->first();
        $this->assertSame('Yangi kitob', $book->name);
        $this->assertSame(1, (int) $book->is_approved);
        $this->assertSame(7, $book->count);
        $edition = BookEdition::find($book->edition_id);
        $this->assertNotNull($edition->verified_at);
        $this->assertTrue((bool) $book->catalog_featured);

        // Ikkinchi do'kon mavjud kartaga
        $other = $this->makeSeller();
        $this->post('/boshqaruv/books', ['seller_id' => $other->id, 'edition_id' => $edition->id, 'price' => 35000, 'count' => 2])
            ->assertSessionHas('success');
        $this->assertSame(2, Books::query()->where('edition_id', $edition->id)->count());

        DB::table('my_carts')->insert(['user_id' => 1, 'product_id' => $book->id, 'product_type' => 'book', 'count_item' => 1, 'priceItem' => 40000, 'created_at' => now(), 'updated_at' => now()]);
        $this->delete("/boshqaruv/books/{$book->id}")->assertSessionHas('success');
        $book->refresh();
        $this->assertNotNull($book->archived_at);
        $this->assertSame(0, $book->count);
        $this->assertSame(0, DB::table('my_carts')->where('product_id', $book->id)->count());

        // Karta faol taklif bilan o'chirilmaydi
        $this->delete("/boshqaruv/catalog/{$edition->id}")->assertSessionHas('error');

        $this->patch("/boshqaruv/books/{$book->id}/restore")->assertSessionHas('success');
        $this->assertNull($book->fresh()->archived_at);
    }

    public function test_submission_approve_makes_offer_live_and_reject_blocks_it(): void
    {
        $this->makeCategory();
        $seller = $this->makeSeller();
        Sanctum::actingAs($seller, ['*'], 'seller');
        $fields = [
            'isbn' => '9789943081239', 'name' => 'Ariza kitobi', 'author' => 'M', 'pages' => 50,
            'language' => 'uz', 'languageWrite' => 'latin', 'coverType' => 'soft', 'description' => 'd',
            'category_id' => DB::table('book_categories')->value('id'), 'price' => 20000, 'count' => 3,
        ];
        $this->post('/api/v1/seller/catalog/submissions', $fields + [
            'front_image' => UploadedFile::fake()->image('f.jpg'), 'back_image' => UploadedFile::fake()->image('b.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $submission = BookEditionSubmission::first();
        $this->post("/boshqaruv/catalog/submissions/{$submission->id}/approve")->assertSessionHas('success');
        $offer = Books::find($submission->book_id);
        $this->assertSame(1, (int) $offer->is_approved);
        $this->assertTrue((bool) $offer->catalog_featured);
        $this->assertSame(BookEdition::STATUS_ACTIVE, BookEdition::find($submission->edition_id)->status);
        $this->assertSame('approved', $submission->fresh()->status);

        // Ikkinchi ariza (boshqa ISBN'siz kitob) — rad etiladi
        $this->post('/api/v1/seller/catalog/submissions', array_merge($fields, ['isbn' => null, 'name' => 'Boshqa']) + [
            'front_image' => UploadedFile::fake()->image('f.jpg'), 'back_image' => UploadedFile::fake()->image('b.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(201);
        $second = BookEditionSubmission::latest('id')->first();
        $this->post("/boshqaruv/catalog/submissions/{$second->id}/reject", ['reason' => 'Rasm sifatsiz'])->assertSessionHas('success');
        $this->assertSame(2, (int) Books::find($second->book_id)->is_approved);
        $this->assertSame(BookEdition::STATUS_REJECTED, BookEdition::find($second->edition_id)->status);
    }

    public function test_catalog_pages_render(): void
    {
        $cat = $this->makeCategory();
        $book = $this->makeBook($this->makeSeller(), $cat);
        $this->withoutVite();

        $this->get('/boshqaruv/catalog')->assertOk();
        $this->get("/boshqaruv/catalog/{$book->edition_id}")->assertOk();
        $this->get('/boshqaruv/catalog/submissions')->assertOk();
        $this->get('/boshqaruv/books?books_tab=archived')->assertOk();
        $this->getJson('/boshqaruv/catalog/search?q=kunlar')->assertOk()->assertJsonPath('data.0.id', $book->edition_id);
    }

    public function test_archived_book_disappears_from_customer_api_and_restore_keeps_previous_state(): void
    {
        $cat = $this->makeCategory();
        $visible = $this->makeBook($this->makeSeller(), $cat, ['price' => 40000]);
        $hiddenBySeller = $this->makeBook($this->makeSeller(), $cat, ['isbn' => null, 'name' => 'Ikkinchi kitob']);
        $hiddenBySeller->update(['is_hidden' => true]);

        $this->delete("/boshqaruv/books/{$visible->id}")->assertSessionHas('success');
        $this->delete("/boshqaruv/books/{$hiddenBySeller->id}")->assertSessionHas('success');

        $ids = collect($this->getJson('/api/v1/kitobchi/products/20')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertEmpty($ids);
        $this->getJson("/api/v1/kitobchi/share/product/{$visible->id}?type=book")->assertStatus(404);

        $this->patch("/boshqaruv/books/{$visible->id}/restore");
        $this->patch("/boshqaruv/books/{$hiddenBySeller->id}/restore");
        $this->assertFalse((bool) $visible->fresh()->is_hidden);
        $this->assertTrue((bool) $hiddenBySeller->fresh()->is_hidden, "do'kon yashirgan kitob arxivdan ham yashirin qaytishi kerak");
    }

    public function test_books_page_can_be_filtered_by_shop(): void
    {
        $cat = $this->makeCategory();
        $a = $this->makeSeller();
        $b = $this->makeSeller();
        $mine = $this->makeBook($a, $cat);
        $this->makeBook($b, $cat, ['isbn' => null, 'name' => 'Boshqa kitob']);

        $this->withoutVite();
        $this->get("/boshqaruv/books?books_seller={$a->id}&books_tab=all")
            ->assertOk()
            ->assertInertia(function (\Inertia\Testing\AssertableInertia $page) use ($mine, $a) {
                $page->component('Books', false)
                    ->where('bookFilters.sellerId', $a->id)
                    ->where('bookCounts.all', 1)
                    ->has('books', 1)
                    ->where('books.0.id', $mine->id);
                $this->assertStringContainsString("books_seller={$a->id}", (string) $page->toArray()['props']['books'][0]['seller']['booksUrl']);
            });
    }

}
