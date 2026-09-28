<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Naqd buyurtma "qaytgan" deb belgilanganda mijozga naqd to'lov yopiladi.
 */
class CashOrderReturnTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
        // Push (Firebase) test muhitida yo'q
        $this->mock(\App\Services\OrderStatusPushService::class)->shouldIgnoreMissing();
        $this->mock(\App\Services\CourierBroadcaster::class)->shouldIgnoreMissing();
    }

    public static function states(): array
    {
        return [
            'kutilmoqda' => ['A', 'pending', 'cash_pending', 0, 'new'],
            'qadoqlanmoqda' => ['P', 'packing', 'cash_pending', 1, 'accepted'],
            'yolda' => ['B', 'in_delivery', 'cash_pending', 2, 'handed_to_courier'],
            // Kuryer "yetkazildi" bosgan — naqd buyurtma avtomatik "to'langan" bo'lgan
            'yetkazildi' => ['C', 'delivered', 'paid', 2, 'handed_to_courier'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('states')]
    public function test_cash_order_can_be_marked_returned_and_blocks_cash(string $legacy, string $code, string $payment, int $soLegacy, string $soCode): void
    {
        [$order, $user] = $this->cashOrder($legacy, $code, $payment, $soLegacy, $soCode);

        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/orders')
            ->patch("/boshqaruv/orders/{$order->id}/status", ['status' => 'returned'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $user->refresh();

        $this->assertSame('returned', $order->status_code);
        $this->assertSame('cash_pending', $order->payment_status_code, 'Qaytgan naqd buyurtmada pul olinmagan');
        $this->assertFalse((bool) $user->cash_on_delivery_allowed);
        $this->assertSame(1, (int) $user->cod_return_strikes);
        $this->assertFalse($user->canUseCashOnDelivery());
    }

    public function test_card_paid_order_still_needs_refund_flow(): void
    {
        [$order] = $this->cashOrder('C', 'delivered', 'paid', 2, 'handed_to_courier');
        DB::table('transactions')->insert([
            'owner_id' => $order->user_id, 'order_id' => $order->id, 'amount' => 50000,
            'payment_type' => 'order', 'provider' => 'paylov', 'state' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'panel')
            ->from('/boshqaruv/orders')
            ->patch("/boshqaruv/orders/{$order->id}/status", ['status' => 'returned'])
            ->assertSessionHas('error');

        $this->assertSame('delivered', $order->fresh()->status_code);
    }

    /** @return array{0: Sold, 1: User} */
    private function cashOrder(string $legacy, string $code, string $payment, int $soLegacy, string $soCode): array
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory());
        $user = User::query()->forceCreate([
            'name' => 'Test', 'lastname' => 'User', 'phone_number' => '998901112233', 'password' => bcrypt('x'),
        ]);

        $order = Sold::query()->forceCreate([
            'user_id' => $user->id, 'amount' => 50000, 'deliveryType' => 'courier',
            'status' => $legacy, 'status_code' => $code,
            'paymentStatus' => $payment === 'paid' ? 2 : 0, 'payment_status_code' => $payment,
            'items' => [['product_id' => $book->id, 'type' => 'book', 'count' => 1, 'price' => 50000]],
        ]);
        $sellerOrderId = DB::table('seller_orders')->insertGetId([
            'seller_id' => $seller->id, 'order_id' => $order->id, 'client_id' => $user->id, 'amount' => 50000,
            'status' => $soLegacy, 'status_code' => $soCode, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('seller_order_items')->insert([
            'seller_id' => $seller->id, 'order_id' => $sellerOrderId, 'product_id' => $book->id, 'type' => 'book',
            'quantity' => 1, 'price' => 50000, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$order->fresh(), $user];
    }

    private function admin(): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'root' . uniqid() . '@test.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
    }
}
