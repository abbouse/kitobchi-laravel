<?php

namespace Tests\Feature;

use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kuryer oqimi testlari uchun buyurtma yig'uvchi.
 */
trait CourierFlowFixtures
{
    protected function makeCourier(int $balance = 200000, ?string $phone = null): Couriers
    {
        return Couriers::query()->forceCreate([
            'first_name' => 'Kuryer', 'last_name' => 'T', 'status' => 'approved', 'is_online' => true,
            'region' => 'Toshkent', 'phone_number' => $phone ?? '+99890'.random_int(1000000, 9999999),
            'password' => bcrypt('x'), 'balance' => $balance,
        ]);
    }

    /**
     * @return array{sold: Sold, courierOrder: CourierOrder, fulfillmentId: int, sellerId: int}
     */
    protected function makeCourierOrder(string $mode = 'direct_courier', bool $cod = true, int $amount = 50000, string $fulfillmentStatus = 'ready_for_pickup'): array
    {
        $seller = $this->makeSeller();
        $book = $this->makeBook($seller, $this->makeCategory());
        $locationId = DB::table('seller_locations')->insertGetId([
            'seller_id' => $seller->id, 'lat' => 41.31, 'lon' => 69.28, 'fullAddress' => 'Do\'kon manzili',
            'is_main' => 1,
        ]);
        $user = User::query()->forceCreate([
            'name' => 'Mijoz', 'lastname' => 'T', 'phone_number' => '99890'.random_int(1000000, 9999999), 'password' => bcrypt('x'),
        ]);

        $sold = Sold::query()->forceCreate([
            'user_id' => $user->id, 'amount' => $amount, 'deliveryType' => 'courier',
            'status' => 'P', 'status_code' => 'packing',
            'paymentStatus' => $cod ? 0 : 2, 'payment_status_code' => $cod ? 'cash_pending' : 'paid',
            'qr' => 'QR'.random_int(100000, 999999),
            'address' => [['fullAddress' => 'Mijoz manzili', 'lat' => 41.33, 'lon' => 69.30, 'fullName' => 'Mijoz', 'phoneNumber' => '+998901112233']],
            'items' => [['item_id' => $book->id, 'product_id' => $book->id, 'type' => 'book', 'count_item' => 1, 'price' => $amount, 'seller_id' => $seller->id]],
        ]);
        $sellerOrderId = DB::table('seller_orders')->insertGetId([
            'seller_id' => $seller->id, 'order_id' => $sold->id, 'client_id' => $user->id, 'amount' => $amount,
            'status' => 2, 'status_code' => 'accepted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('seller_order_items')->insert([
            'seller_id' => $seller->id, 'order_id' => $sellerOrderId, 'product_id' => $book->id, 'type' => 'book',
            'quantity' => 1, 'price' => $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $hubId = null;
        if ($mode !== 'direct_courier') {
            $hubId = DB::table('hubs')->insertGetId([
                'name' => 'Markaziy hub', 'code' => 'H'.random_int(100, 999), 'country_code' => 'UZ',
                'address' => 'Hub manzili', 'lat' => 41.30, 'lon' => 69.25, 'is_active' => 1, 'is_primary' => 1,
                'supports_first_mile' => 1, 'supports_last_mile' => 1, 'supports_postal_dispatch' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $fulfillmentId = DB::table('order_fulfillments')->insertGetId([
            'order_id' => $sold->id, 'hub_id' => $hubId, 'fulfillment_mode' => $mode, 'status_code' => $fulfillmentStatus,
            'first_mile_mode' => 'courier', 'last_mile_mode' => 'courier_delivery', 'routing_version' => 'v1',
            'is_cod' => $cod ? 1 : 0, 'cash_collect_amount' => $cod ? $amount : 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $courierOrder = CourierOrder::query()->forceCreate([
            'order_id' => $sold->id, 'user_id' => $user->id, 'amount' => $amount,
            'status' => 'pending', 'status_code' => 'pending',
        ]);
        DB::table('courier_order_items')->insert([
            'order_id' => $sold->id, 'seller_id' => $seller->id, 'seller_location_id' => $locationId,
            'product_id' => $book->id, 'quantity' => 1, 'price' => $amount, 'type' => 'book',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['sold' => $sold->fresh(), 'courierOrder' => $courierOrder->fresh(), 'fulfillmentId' => $fulfillmentId, 'sellerId' => $seller->id];
    }
}
