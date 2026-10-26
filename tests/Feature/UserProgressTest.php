<?php

namespace Tests\Feature;

use App\Models\Sold;
use App\Models\User;
use App\Services\UserPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Kitobxon darajalari (12 oylik xarid) va klub nishonlari. */
class UserProgressTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::query()->forceCreate(['name' => 'A', 'lastname' => 'B', 'phone_number' => '99890'.random_int(1000000, 9999999), 'password' => bcrypt('x')]);
    }

    private function order(User $u, int $amount, ?string $completedAt = null): void
    {
        Sold::query()->forceCreate([
            'user_id' => $u->id, 'amount' => $amount, 'deliveryPrice' => 0, 'deliveryType' => 'courier_service', 'status' => 'C',
            'status_code' => 'delivered', 'paymentStatus' => 2, 'payment_status_code' => 'paid', 'items' => [],
            'completed_at' => $completedAt ?? now()->subDays(3),
        ]);
    }

    public function test_level_needs_both_orders_and_spend_and_badges_are_awarded(): void
    {
        $svc = app(UserPositionService::class);
        $u = $this->user();

        $this->order($u, 50000);
        $this->order($u, 50000);
        $this->assertSame('active_reader', $svc->evaluateAndPromote($u, 'order_completed'));

        // 5 ta buyurtma, lekin summa 300 mingdan kam — "Kitob muxlisi" emas
        foreach (range(1, 3) as $_) {
            $this->order($u, 20000);
        }
        $this->assertNull($svc->evaluateAndPromote($u, 'order_completed'));
        $this->assertSame('active_reader', $u->fresh()->position);

        $this->order($u, 300000);
        $this->assertSame('book_lover', $svc->evaluateAndPromote($u, 'order_completed'));

        // 12 oydan eski buyurtmalar hisobga olinmaydi
        $old = $this->user();
        $this->order($old, 500000, now()->subMonths(13)->toDateTimeString());
        $this->order($old, 500000, now()->subMonths(13)->toDateTimeString());
        $this->assertNull($svc->evaluateAndPromote($old, 'order_completed'));

        // Nishon: birinchi xarid
        Sanctum::actingAs($u, ['*'], 'user');
        $res = $this->getJson('/api/v1/kitobchi/book_club/badges')->assertOk();
        $first = collect($res->json('data'))->firstWhere('key', 'first_order');
        $this->assertTrue($first['earned']);
        $this->assertSame('book_lover', $res->json('level.current.key'));
        $this->assertSame('bibliophile', $res->json('level.next.key'));

        // Boshqa foydalanuvchi progress va xarid summasini ko'rmaydi
        Sanctum::actingAs($old, ['*'], 'user');
        $res = $this->getJson('/api/v1/kitobchi/book_club/badges?user_id='.$u->id)->assertOk();
        $this->assertNull($res->json('level'));
        $this->assertArrayNotHasKey('current', $res->json('data.0'));
    }

    public function test_demotion_is_gradual(): void
    {
        $svc = app(UserPositionService::class);
        $u = $this->user();
        $u->forceFill(['position' => 'bibliophile', 'position_earned_at' => now()->subDays(10)])->save();

        // 90 kun o'tmagan — tushmaydi
        $this->assertNull($svc->evaluateAndPromote($u, 'order_completed'));

        $u->forceFill(['position_earned_at' => now()->subDays(120)])->save();
        $this->assertSame('book_lover', $svc->evaluateAndPromote($u, 'order_completed'));

        // Qayta hisoblash buyrug'i esa darhol aniq darajaga o'rnatadi
        $this->assertSame('reader', $svc->evaluateAndPromote($u, 'backfill_recalculation'));
    }
}
