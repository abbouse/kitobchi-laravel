<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\SellerTransaction;
use App\Services\SellerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Buyurtma puli yakunlangandan 14 kun o'tib yechishga ochiladi.
 */
class SellerPayoutTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_recent_sales_are_held_and_old_ones_are_withdrawable(): void
    {
        $seller = $this->sellerWithSales();

        $summary = app(SellerPayoutService::class)->summary($seller->fresh());

        $this->assertSame(600000, $summary['balance']);
        $this->assertSame(250000, $summary['held']);          // 3 kunlik (qaytarilmagan) sotuv
        $this->assertSame(350000, $summary['withdrawable']);
        $this->assertSame(now()->subDays(3)->addDays(14)->toDateString(), $summary['next_release']['date']);
    }

    public function test_withdrawal_takes_only_withdrawable_part(): void
    {
        $seller = $this->sellerWithSales();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $this->getJson('/api/v1/seller/transactions/count')
            ->assertOk()
            ->assertJsonPath('data.balance', 600000)
            ->assertJsonPath('data.withdrawable_balance', 350000)
            ->assertJsonPath('data.held_balance', 250000)
            ->assertJsonPath('data.hold_days', 14)
            ->assertJsonPath('data.withdrawal_method.masked', '•••• 4444');

        $this->postJson('/api/v1/seller/transactions/withdrawal', ['amount' => 400000])
            ->assertStatus(400);
        $this->postJson('/api/v1/seller/transactions/withdrawal', ['amount' => 50000])
            ->assertStatus(400); // eng kami 100 000

        $this->postJson('/api/v1/seller/transactions/withdrawal', ['amount' => 200000])
            ->assertOk()
            ->assertJsonPath('amount', 200000);
        $this->assertSame(400000, (int) $seller->fresh()->balance);

        // Eski ilova (GET) — qolgan yechish mumkin bo'lgan summani yechadi
        $this->getJson('/api/v1/seller/transactions/withdrawal')
            ->assertOk()
            ->assertJsonPath('amount', 150000);
        $this->assertSame(250000, (int) $seller->fresh()->balance);

        $this->getJson('/api/v1/seller/transactions/count')
            ->assertJsonPath('data.withdrawable_balance', 0)
            ->assertJsonPath('data.pending_withdrawal', 350000);

        $list = $this->getJson('/api/v1/seller/transactions/latest')->assertOk()->json('data');
        $held = collect($list)->firstWhere('description', 'yangi');
        $this->assertTrue($held['on_hold']);
    }

    private function sellerWithSales(): Seller
    {
        $seller = $this->makeSeller(['payment_card' => '8600 1111 2222 4444']);
        $seller->forceFill(['balance' => 600000, 'role' => 1])->save();

        $this->sale($seller, 1, 350000, 20, 'eski');
        $this->sale($seller, 2, 250000, 3, 'yangi');
        // Qaytarilgan sotuv — ushlab turilmaydi
        $this->sale($seller, 3, 90000, 2, 'qaytgan');
        $this->sale($seller, 3, 90000, 1, 'qaytarish', 'expense', 'order_reversal');

        return $seller;
    }

    private function sale(Seller $seller, int $sellerOrderId, int $net, int $daysAgo, string $description, string $type = 'income', string $category = 'order_sale'): void
    {
        $tx = SellerTransaction::query()->create([
            'seller_id' => $seller->id, 'seller_order_id' => $sellerOrderId, 'type' => $type, 'category' => $category,
            'amount' => $net, 'netAmount' => $net, 'commissionPercent' => 0, 'commissionPrice' => 0,
            'status' => 'approved', 'description' => $description,
        ]);
        $tx->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    }
}
