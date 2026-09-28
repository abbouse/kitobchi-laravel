<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Do'kon ilovasi: buyurtmalar ro'yxati — sahifalash, holat bo'yicha
 * serverdagi sonlar va to'lov belgisi.
 */
class SellerOrderListTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    private function order(int $sellerId, string $sellerStatus, string $orderStatus, string $payment): int
    {
        $legacy = ['pending' => 'A', 'delivered' => 'C', 'returned' => 'F'][$orderStatus];
        $orderId = DB::table('solds')->insertGetId([
            'amount' => 40000, 'status' => $legacy, 'status_code' => $orderStatus,
            'paymentStatus' => $payment === 'paid' ? 2 : 0, 'payment_status_code' => $payment,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('seller_orders')->insertGetId([
            'seller_id' => $sellerId, 'order_id' => $orderId, 'amount' => 40000,
            'status_code' => $sellerStatus,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_counts_filters_payment_kind_and_pages(): void
    {
        $shop = $this->makeSeller();
        $this->order($shop->id, 'new', 'pending', 'cash_pending');
        $this->order($shop->id, 'new', 'pending', 'paid');
        $this->order($shop->id, 'handed_to_courier', 'delivered', 'paid');
        $returned = $this->order($shop->id, 'handed_to_courier', 'returned', 'cash_pending');
        Sanctum::actingAs($shop, ['*'], 'seller');

        $res = $this->getJson('/api/v1/seller/orders/last-orders?status=all&page=1')->assertOk();
        $this->assertSame(4, $res->json('counts.all'));
        $this->assertSame(2, $res->json('counts.new'));
        $this->assertSame(1, $res->json('counts.delivered'));
        $this->assertSame(1, $res->json('counts.returned'));
        $this->assertFalse($res->json('has_more'));
        $kinds = collect($res->json('data'))->pluck('payment_kind')->sort()->values()->all();
        $this->assertSame(['cash', 'cash', 'paid', 'paid'], $kinds);

        $this->getJson('/api/v1/seller/orders/last-orders?status=returned&page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $returned);

        // Eski ilova (page yo'q) — avvalgidek, counts yo'q
        $this->getJson('/api/v1/seller/orders/last-orders?status=all')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('counts', null);
    }
}
