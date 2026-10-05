<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\GamePrize;
use App\Models\GameWallet;
use App\Models\User;
use App\Services\PrizeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/** Sovg'alar g'ildiragi: tangalar, aylantirish, bo'laklar, shaxsiy promokodlar. */
class PrizeGameTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        Cache::flush();
        $this->user = User::query()->forceCreate([
            'name' => 'Ali', 'lastname' => 'Valiyev', 'phone_number' => '998901112233',
            'password' => bcrypt('secret'),
        ]);
        Sanctum::actingAs($this->user, ['*'], 'user');
    }

    private function prize(array $attrs): GamePrize
    {
        return GamePrize::create(array_merge([
            'type' => 'coins', 'title_uz' => 'Sovg\'a', 'difficulty' => 'easy', 'chance' => 100,
            'is_active' => true, 'coins_amount' => 3,
        ], $attrs));
    }

    public function test_state_gives_welcome_coins_and_daily_once(): void
    {
        $this->getJson('/api/v1/kitobchi/game/state')
            ->assertOk()->assertJsonPath('data.coins', 20)->assertJsonPath('data.daily.available', true);

        $this->postJson('/api/v1/kitobchi/game/daily')->assertOk()->assertJsonPath('data.coins', 25);
        $this->postJson('/api/v1/kitobchi/game/daily')->assertStatus(422);
        $this->getJson('/api/v1/kitobchi/game/state')->assertJsonPath('data.daily.available', false);
    }

    public function test_spin_costs_coins_and_respects_conditions(): void
    {
        // Shart: kamida 1 ta yetkazilgan buyurtma — yo'q, demak hech narsa tushmaydi
        $this->prize(['min_orders' => 1]);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()
            ->assertJsonPath('data.result.result', 'nothing')
            ->assertJsonPath('data.coins', 10);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->assertJsonPath('data.coins', 0);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertStatus(422);
    }

    public function test_coins_prize_and_max_wins(): void
    {
        $p = $this->prize(['coins_amount' => 7, 'max_wins_per_user' => 1]);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()
            ->assertJsonPath('data.result.result', 'coins')
            ->assertJsonPath('data.coins', 17);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()
            ->assertJsonPath('data.result.result', 'nothing');
        $this->assertSame(1, $p->fresh()->won_count);
    }

    public function test_promocode_prize_is_personal_scoped_and_valid_for_a_month(): void
    {
        $seller = $this->makeSeller();
        $other = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory(), ['price' => 60000]);
        $foreign = $this->makeBook($other, $book->category_id, ['price' => 40000]);

        $this->prize(['type' => 'promocode', 'discount_type' => 'percent', 'discount_value' => 10,
            'scope' => 'seller', 'scope_id' => $seller->id, 'valid_days' => 30]);

        $res = $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()
            ->assertJsonPath('data.result.result', 'promocode')->json('data.result');
        $promo = DB::table('promocodes')->where('code', $res['code'])->first();
        $this->assertSame($this->user->id, (int) $promo->user_id);
        $this->assertSame('seller', $promo->scope_type);
        $this->assertSame('game', $promo->source);
        $this->assertTrue(now()->addDays(29)->lt($promo->expires_at));

        // Faqat boshqa do'kon mahsuloti savatda — rad etiladi
        $cartForeign = DB::table('my_carts')->insertGetId(['user_id' => $this->user->id, 'product_id' => $foreign->id,
            'product_type' => 'book', 'count_item' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/v1/kitobchi/purchase/checkPromo?code='.$res['code'])->assertStatus(400);

        // Mos do'kon mahsuloti qo'shildi — chegirma faqat shu qatordan (60000 * 10%)
        DB::table('my_carts')->insert(['user_id' => $this->user->id, 'product_id' => $book->id,
            'product_type' => 'book', 'count_item' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/v1/kitobchi/purchase/checkPromo?code='.$res['code'])
            ->assertOk()->assertJsonPath('discount', 6000);

        $this->getJson('/api/v1/kitobchi/game/rewards')->assertOk()
            ->assertJsonPath('data.0.code', $res['code'])->assertJsonPath('data.0.status', 'active');
        $this->assertNotNull($cartForeign);
    }

    public function test_fragments_complete_into_free_book_code(): void
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory(), ['price' => 45000]);
        $editionId = (int) $book->fresh()->edition_id;
        $this->assertGreaterThan(0, $editionId, 'Kitob global katalogga ulanmagan');

        $this->prize(['type' => 'fragments', 'fragments_total' => 2, 'edition_id' => $editionId]);

        $first = $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->json('data.result');
        $this->assertSame('fragment', $first['result']);
        $second = $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->json('data.result');
        $this->assertSame('completed', $second['result']);

        $promo = DB::table('promocodes')->where('code', $second['code'])->first();
        $this->assertSame('free_item', $promo->type);
        $this->assertSame($editionId, (int) $promo->scope_id);

        DB::table('my_carts')->insert(['user_id' => $this->user->id, 'product_id' => $book->id,
            'product_type' => 'book', 'count_item' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/v1/kitobchi/purchase/checkPromo?code='.$second['code'])
            ->assertOk()->assertJsonPath('discount', 45000);
    }

    public function test_tasks_claim_once_per_day(): void
    {
        $this->getJson('/api/v1/kitobchi/game/state')->assertJsonPath('data.tasks.0.status', 'todo');
        $this->postJson('/api/v1/kitobchi/game/tasks/club_post/claim')->assertStatus(422);

        DB::table('book_club')->insert(['user_id' => $this->user->id, 'text' => 'Salom', 'is_deleted' => 0,
            'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/v1/kitobchi/game/tasks/club_post/claim')->assertOk()->assertJsonPath('data.coins', 23);
        $this->postJson('/api/v1/kitobchi/game/tasks/club_post/claim')->assertStatus(422);
    }

    public function test_roll_respects_percentages(): void
    {
        $svc = app(PrizeGameService::class);
        $a = new GamePrize(['chance' => 0]);
        $this->assertNull($svc->roll(collect([$a])));
        $b = new GamePrize(['chance' => 100]);
        $this->assertSame($b, $svc->roll(collect([$b])));
        $this->assertSame('Ali V.', PrizeGameService::maskName('Ali Valiyev'));
        $this->assertSame(20, GameWallet::count() ? 0 : 20);
    }

    public function test_admin_manages_prizes_settings_and_chances(): void
    {
        $this->withoutVite();
        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $seller = $this->makeSeller();
        $panel = $this->actingAs($admin, 'panel')->from('/boshqaruv/prize-game');

        $panel->post('/boshqaruv/prize-game/prizes', [
            'type' => 'promocode', 'title_uz' => '10% do\'kon', 'difficulty' => 'hard', 'chance' => 1,
            'discount_type' => 'percent', 'discount_value' => 10, 'scope' => 'seller', 'scope_id' => $seller->id,
            'min_orders' => 2, 'max_wins_per_user' => 1,
        ])->assertRedirect('/boshqaruv/prize-game')->assertSessionHasNoErrors();

        $prize = GamePrize::firstOrFail();
        $this->assertSame('seller', $prize->scope);
        $this->assertSame(2, $prize->min_orders);

        // Bo'laklar uchun kitob majburiy
        $panel->post('/boshqaruv/prize-game/prizes', ['type' => 'fragments', 'title_uz' => 'Kitob', 'difficulty' => 'easy', 'chance' => 5])
            ->assertSessionHasErrors('edition_id');

        $panel->post('/boshqaruv/prize-game/chances', ['chances' => [$prize->id => 2.5]])->assertRedirect();
        $this->assertSame(2.5, $prize->fresh()->chance);

        $panel->post('/boshqaruv/prize-game/settings', ['enabled' => 1, 'spin_cost' => 15, 'daily_coins' => 3, 'order_coins' => 10, 'welcome_coins' => 0])
            ->assertRedirect();
        $this->assertSame(15, app(PrizeGameService::class)->setting('spin_cost'));

        $this->actingAs($admin, 'panel')->get('/boshqaruv/prize-game')->assertOk();
        $this->actingAs($admin, 'panel')->getJson('/boshqaruv/prize-game/lookup?type=seller&q='.urlencode($seller->shop_name))
            ->assertOk()->assertJsonPath('data.0.id', $seller->id);
    }
}
