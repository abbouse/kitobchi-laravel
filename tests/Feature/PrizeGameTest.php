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

    public function test_surprise_box_goes_to_next_order_and_returns_on_cancel(): void
    {
        $this->prize(['type' => 'surprise', 'coins_amount' => null, 'title_uz' => "Sovg'a qutisi"]);
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->assertJsonPath('data.result.result', 'surprise');

        $svc = app(\App\Services\PrizeGameService::class);
        $this->assertNotNull($svc->attachSurpriseToOrder($this->user->id, 501));
        $this->assertNull($svc->attachSurpriseToOrder($this->user->id, 502), 'Bitta quti — bitta buyurtma');

        $svc->releaseSurpriseForOrder(501);
        $this->assertNotNull($svc->attachSurpriseToOrder($this->user->id, 503), 'Bekor qilinsa keyingisiga o\'tadi');

        // Yutuqlar lentasida ham ko'rinadi
        Cache::forget('game_winners');
        $this->assertSame('surprise', $svc->winners()[0]['kind']);
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

    public function test_promocode_prize_is_personal_scoped_and_valid_for_15_days(): void
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
        $this->assertTrue(now()->addDays(14)->lt($promo->expires_at));
        $this->assertTrue(now()->addDays(16)->gt($promo->expires_at), 'Promokod 15 kun amal qiladi');

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

    public function test_won_book_costs_1000_for_the_winner_and_is_not_won_twice(): void
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory(), ['price' => 45000]);
        $editionId = (int) $book->fresh()->edition_id;
        $this->assertGreaterThan(0, $editionId, 'Kitob global katalogga ulanmagan');

        $p = $this->prize(['type' => 'fragments', 'fragments_total' => 2, 'edition_id' => $editionId]);

        $this->assertSame('fragment', $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->json('data.result.result'));
        $second = $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->json('data.result');
        $this->assertSame('completed', $second['result']);
        $this->assertArrayNotHasKey('code', $second, 'Promokod yo\'q');
        $this->assertSame(1000, $second['price']);

        // Savatga qo'shish — 1 dona, user uchun 1000 so'm
        $this->postJson("/api/v1/kitobchi/game/rewards/{$second['reward_id']}/cart")->assertOk();
        $row = DB::table('my_carts')->where('user_id', $this->user->id)->first();
        $this->assertSame(1, (int) $row->count_item);
        $this->assertSame((int) $second['reward_id'], (int) $row->game_reward_id);
        $this->getJson('/api/v1/kitobchi/cart_user')->assertOk()
            ->assertJsonPath('data.items.0.product.discountPrice', 1000)
            ->assertJsonPath('data.items.0.game_reward', true);

        // Mahsulot sahifasida ham shu user uchun 1000 so'm
        $this->getJson("/api/v1/kitobchi/share/product/{$book->id}?type=book")->assertOk()
            ->assertJsonPath('data.game_reward.price', 1000);

        // Yutilgan kitob qayta tushmaydi
        $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->assertJsonPath('data.result.result', 'nothing');
        $this->assertSame(1, $p->fresh()->won_count);

        // Muddati o'tsa — oddiy narx
        DB::table('game_rewards')->where('id', $second['reward_id'])->update(['expires_at' => now()->subDay()]);
        $this->getJson('/api/v1/kitobchi/cart_user')->assertOk()->assertJsonPath('data.items.0.game_reward', false);
    }

    public function test_board_has_eight_cells_and_spin_stops_on_won_prize(): void
    {
        $p = $this->prize(['coins_amount' => 5]);
        $board = $this->getJson('/api/v1/kitobchi/game/state')->assertOk()->json('data.board');
        $this->assertCount(8, $board);
        $this->assertContains($p->id, $board);
        $this->assertContains(null, $board);

        $data = $this->postJson('/api/v1/kitobchi/game/spin')->assertOk()->json('data');
        $this->assertSame('coins', $data['result']['result']);
        $this->assertSame($p->id, $data['board'][$data['cell']]);
    }

    public function test_counts_show_game_coins_without_creating_wallet(): void
    {
        $this->getJson('/api/v1/kitobchi/counts')->assertOk()->assertJsonPath('data.game_coins', 20);
        $this->assertSame(0, GameWallet::where('user_id', $this->user->id)->count());

        $this->getJson('/api/v1/kitobchi/game/state')->assertOk();
        $this->postJson('/api/v1/kitobchi/game/daily')->assertOk();
        $this->getJson('/api/v1/kitobchi/counts')->assertOk()->assertJsonPath('data.game_coins', 25);
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
        // Soddalashtirilgan forma: qo'shimcha shartlar e'tiborga olinmaydi
        $this->assertSame(0, $prize->min_orders);
        $this->assertNull($prize->max_wins_per_user);

        // Nomi bo'sh — qiymatdan avtomatik; soni bilan
        $panel->post('/boshqaruv/prize-game/prizes', [
            'type' => 'coins', 'chance' => 5, 'coins_amount' => 50, 'stock' => 3,
        ])->assertSessionHasNoErrors();
        $coins = GamePrize::where('type', 'coins')->firstOrFail();
        $this->assertSame('+50 tanga', $coins->title_uz);
        $this->assertSame(3, $coins->stock);

        // Bo'laklar uchun kitob majburiy
        $panel->post('/boshqaruv/prize-game/prizes', ['type' => 'fragments', 'title_uz' => 'Kitob', 'chance' => 5])
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
