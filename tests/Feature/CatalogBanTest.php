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

    public function test_bulk_ban_shows_in_rejected_tab_and_unbans(): void
    {
        $this->withoutVite();
        $cat = $this->makeCategory();
        $a = $this->makeBook($this->makeSeller(), $cat)->fresh();
        $b = $this->makeBook($this->makeSeller(), $cat, ['name' => 'Boshqa kitob', 'isbn' => '9780306406164'])->fresh();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $ids = array_values(array_unique([$a->edition_id, $b->edition_id]));

        $this->actingAs($admin, 'panel')->from('/boshqaruv/catalog')
            ->post('/boshqaruv/catalog/bulk', ['action' => 'ban', 'ids' => $ids, 'reason' => 'test'])
            ->assertSessionHas('success');
        $this->assertFalse((bool) $a->fresh()->status);

        $this->actingAs($admin, 'panel')->get('/boshqaruv/catalog?tab=rejected')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('counts.rejected', count($ids))->has('editions', count($ids)));
        $this->actingAs($admin, 'panel')->get('/boshqaruv/catalog?tab=deleted')
            ->assertInertia(fn ($page) => $page->has('editions', 0));

        $this->actingAs($admin, 'panel')->from('/boshqaruv/catalog')
            ->post('/boshqaruv/catalog/bulk', ['action' => 'unban', 'ids' => $ids])
            ->assertSessionHas('success');
        $this->assertNull($a->fresh()->archived_at);

        $this->actingAs($admin, 'panel')->get('/boshqaruv/catalog?sort=offers&stock=in')->assertOk();
    }
}
