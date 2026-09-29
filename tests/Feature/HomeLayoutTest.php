<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\HomeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

class HomeLayoutTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    }

    public function test_layout_returns_active_sections_in_order_and_skips_empty(): void
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory());

        $res = $this->getJson('/api/v1/kitobchi/home/layout')->assertOk();
        $keys = collect($res->json('data.sections'))->pluck('key')->all();

        $this->assertContains('new_arrivals', $keys);
        $this->assertContains('shops', $keys);
        $this->assertSame('shops', end($keys), "Do'konlar eng pastda");
        $this->assertNotContains('coming_soon', $keys, "Predzakaz yo'q — bo'lim chiqmaydi");
        $this->assertNotContains('recently_viewed', $keys, 'Mehmonga shaxsiy bo\'lim yo\'q');

        HomeSection::query()->where('key', 'new_arrivals')->update(['is_active' => false]);
        Cache::flush();
        $keys = collect($this->getJson('/api/v1/kitobchi/home/layout')->json('data.sections'))->pluck('key')->all();
        $this->assertNotContains('new_arrivals', $keys);
    }

    public function test_shops_are_paged(): void
    {
        $category = $this->makeCategory();
        foreach (range(1, 5) as $i) {
            $this->makeBook($this->makeSeller(), $category);
        }

        $first = $this->getJson('/api/v1/kitobchi/home/shops?page=1')->assertOk();
        $this->assertCount(4, $first->json('data'));
        $this->assertTrue($first->json('has_more'));
        $second = $this->getJson('/api/v1/kitobchi/home/shops?page=2')->assertOk();
        $this->assertCount(1, $second->json('data'));
        $this->assertFalse($second->json('has_more'));
    }

    public function test_admin_can_reorder_and_add_category_section(): void
    {
        $this->withoutVite();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $categoryId = $this->makeCategory();

        $this->actingAs($admin, 'panel')->get('/boshqaruv/home-sections')->assertOk();

        $this->post('/boshqaruv/home-sections', [
            'type' => 'category', 'title_uz' => 'Bolalar uchun', 'category_id' => $categoryId,
        ])->assertSessionHas('success');
        $custom = HomeSection::query()->where('type', 'category')->firstOrFail();
        $this->assertSame($categoryId, $custom->settings['category_id']);
        $this->assertLessThan(HomeSection::query()->where('type', 'shops')->value('position'), $custom->position);

        $ids = HomeSection::query()->where('type', '!=', 'shops')->orderByDesc('position')->pluck('id')->all();
        $this->post('/boshqaruv/home-sections/reorder', ['ids' => $ids])->assertSessionHas('success');
        $this->assertSame($ids[0], HomeSection::query()->orderBy('position')->value('id'));

        $core = HomeSection::query()->where('key', 'bestsellers')->firstOrFail();
        $this->delete("/boshqaruv/home-sections/{$core->id}")->assertSessionHas('error');
    }
}
