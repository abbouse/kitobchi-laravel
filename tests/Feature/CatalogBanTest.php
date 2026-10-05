<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BookEdition;
use App\Models\Books;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

class CatalogBanTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    }

    public function test_admin_bans_and_unbans_edition(): void
    {
        $this->withoutVite();
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());
        $book->refresh();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);

        $this->actingAs($admin, 'panel')
            ->from("/boshqaruv/catalog/{$book->edition_id}")
            ->post("/boshqaruv/catalog/{$book->edition_id}/ban", ['reason' => 'test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $book->refresh();
        $this->assertFalse((bool) $book->status);
        $this->assertNotNull($book->archived_at);
        $this->actingAs($admin, 'panel')->get("/boshqaruv/catalog/{$book->edition_id}")->assertOk();

        $this->actingAs($admin, 'panel')
            ->from("/boshqaruv/catalog/{$book->edition_id}")
            ->post("/boshqaruv/catalog/{$book->edition_id}/unban")
            ->assertSessionHas('success');
        $this->assertNull($book->fresh()->archived_at);
    }
}
