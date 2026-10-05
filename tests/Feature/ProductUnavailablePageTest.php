<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\ProductUnavailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Mavjud bo'lmagan mahsulot sahifasi: bir xil matn + sababni bildiruvchi kod. */
class ProductUnavailablePageTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
    }

    private function assertCode(string $url, string $code): void
    {
        $this->get($url)->assertStatus(404)
            ->assertSee('Bunday mahsulot mavjud emas')
            ->assertSee($code);
    }

    public function test_reasons_have_individual_codes(): void
    {
        $cat = $this->makeCategory();

        $this->assertCode('/books/999999-x', 'KB-B10');

        $hidden = $this->makeBook($this->makeSeller(), $cat);
        $hidden->forceFill(['is_hidden' => true])->save();
        $this->assertCode("/books/{$hidden->id}-x", 'KB-B30');

        $off = $this->makeBook($this->makeSeller(), $cat);
        $off->forceFill(['status' => false])->save();
        $this->assertCode("/books/{$off->id}-x", 'KB-B31');

        $pending = $this->makeBook($this->makeSeller(), $cat, ['is_approved' => 0]);
        $this->assertCode("/books/{$pending->id}-x", 'KB-B40');

        $shopHidden = $this->makeBook($this->makeSeller(['is_hidden' => 1]), $cat);
        $this->assertCode("/books/{$shopHidden->id}-x", 'KB-B52');

        $blocked = $this->makeBook($this->makeSeller(['status' => 'banned']), $cat);
        $this->assertCode("/books/{$blocked->id}-x", 'KB-B51');

        $archived = $this->makeBook($this->makeSeller(), $cat);
        $archived->forceFill(['archived_at' => now()])->save();
        $this->assertCode("/books/{$archived->id}-x", 'KB-B21');

        // Global katalogdan sotuvdan olingan
        $banned = $this->makeBook($this->makeSeller(), $cat)->fresh();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
        $this->actingAs($admin, 'panel')->post("/boshqaruv/catalog/{$banned->edition_id}/ban", ['reason' => 't']);
        $this->assertCode("/books/{$banned->id}-x", 'KB-B20');

        $this->assertCode('/p/NOPE-123', 'KB-B10');
        $this->assertSame('KB-S10', ProductUnavailability::forStationery(999999)['code']);
    }

    public function test_api_returns_reason_code_for_web(): void
    {
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());
        $book->forceFill(['is_hidden' => true])->save();

        $this->getJson("/api/v1/kitobchi/share/product/{$book->id}?type=book")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'product_unavailable')
            ->assertJsonPath('code', 'KB-B30');
        $this->getJson('/api/v1/kitobchi/share/product/999999?type=stationery')
            ->assertStatus(404)->assertJsonPath('code', 'KB-S10');
    }
}
