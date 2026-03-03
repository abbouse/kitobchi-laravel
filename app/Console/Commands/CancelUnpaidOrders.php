<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sold;
use App\Models\User;
use App\Models\Gifts;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\SellerOrder;
use App\Models\CourierOrder;
use App\Models\PromocodeHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    protected $signature = 'orders:cancel-unpaid';
    protected $description = 'To\'lanmagan (paymentStatus=1) buyurtmalarni 1 soatdan keyin avtomatik bekor qilish';

    public function handle()
    {
        $this->info(date('d.m.Y, h:i:s').'To\'lanmagan buyurtmalarni avtomatik bekor qilish jarayoni boshlandi...');

        $oneHourAgo = Carbon::now()->subHour();

        $unpaidOrders = Sold::where('paymentStatus', 1)->where('status', 'A')
            ->where('created_at', '<', $oneHourAgo)
            ->get();

        if ($unpaidOrders->isEmpty()) {
            $this->info('Bekor qilinadigan buyurtma topilmadi.');
            return;
        }

        $this->info("Topilgan buyurtmalar soni: {$unpaidOrders->count()}");

        foreach ($unpaidOrders as $order) {
            DB::beginTransaction();
            try {
                // 1. Statuslarni o'zgartirish
                $order->status = 'F';
                $order->paymentStatus = 3; // Rad etildi
                $order->save();

                // 2. Oddiy mahsulotlar stockini qaytarish
                foreach ($order->items ?? [] as $item) {
                    if ($item['type'] === 'gift') {
                        continue;
                    }
                    $this->incrementStock($item);
                }

                // 3. Platforma gift stockini qaytarish
                if ($order->gift) {
                    $gift = Gifts::find($order->gift);
                    if ($gift && $gift->seller_id == 1) {
                        $gift->increment('stock', 1); // stock maydoni
                    }
                }

                // 4. Statistikalarni minus qilish
                $this->decrementStatistics($order);

                // 5. Cashback qaytarish
                if ($order->withCashback && $order->cashbackAmount > 0) {
                    $user = User::find($order->user_id);
                    if ($user) {
                        $user->increment('cashback', $order->cashbackAmount);
                    }
                }

                // 6. Promokod qaytarish
                if ($order->promocode) {
                    $promo = DB::table('promocodes')->where('code', $order->promocode)->first();
                    if ($promo) {
                        DB::table('promocodes')->where('id', $promo->id)->decrement('usedCount');
                        PromocodeHistory::where('user_id', $order->user_id)
                            ->where('promocode_id', $promo->id)
                            ->delete();
                    }
                }

                // 7. Bog'liq orderlarni bekor qilish
                SellerOrder::where('order_id', $order->id)->update(['status' => 3]);
                CourierOrder::where('order_id', $order->id)->update(['status' => 'rejected']);

                DB::commit();
                $this->info("Buyurtma #{$order->id} avtomatik bekor qilindi.");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Avtomatik bekor qilish xatosi (Order ID: {$order->id})", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Buyurtma #{$order->id} bekor qilishda xatolik: " . $e->getMessage());
            }
        }

        $this->info('Jarayon tugadi.');
    }

    private function incrementStock(array $item)
    {
        $type = $item['type'] ?? 'book';
        $productId = $item['item_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity = $item['count_item'];

        if ($type === 'book') {
            $product = Books::find($productId);
            if ($product) {
                $product->increment('count', $quantity);
            }
        } elseif ($type === 'stationery') {
            $product = Stationery::find($productId);
            if ($product) {
                if ($variantId) {
                    $variant = StationeryVariant::find($variantId);
                    if ($variant) {
                        $variant->increment('stock', $quantity);
                    }
                } else {
                    $product->increment('stock', $quantity);
                }
            }
        }
    }

    private function decrementStatistics($order)
    {
        // Book va Stationery
        foreach ($order->items ?? [] as $item) {
            if ($item['type'] === 'gift') {
                continue;
            }

            $productId = $item['item_id'];
            $quantity = $item['count_item'];
            $revenue = $item['item_price'] * $quantity;
            $type = $item['type'];

            $product = null;
            if ($type === 'book') {
                $product = Books::find($productId);
            } elseif ($type === 'stationery') {
                $product = Stationery::find($productId);
            }

            if ($product) {
                $product->decrement('totalSales', $quantity);
                $product->decrement('totalRevenue', $revenue);
                $product->decrement('totalSalesWeek', $quantity);
                $product->decrement('totalRevenueWeek', $revenue);

                $product->totalSales = max(0, $product->totalSales);
                $product->totalRevenue = max(0, $product->totalRevenue);
                $product->totalSalesWeek = max(0, $product->totalSalesWeek);
                $product->totalRevenueWeek = max(0, $product->totalRevenueWeek);

                $product->save();
            }
        }

        // Platforma gift
        if ($order->gift) {
            $gift = Gifts::find($order->gift);
            if ($gift && $gift->seller_id == 1) {
                $gift->decrement('totalSales', 1);
                $gift->decrement('totalRevenue', 0);
                $gift->decrement('totalSalesWeek', 1);
                $gift->decrement('totalRevenueWeek', 0);

                $gift->totalSales = max(0, $gift->totalSales);
                $gift->totalRevenue = max(0, $gift->totalRevenue);
                $gift->totalSalesWeek = max(0, $gift->totalSalesWeek);
                $gift->totalRevenueWeek = max(0, $gift->totalRevenueWeek);

                $gift->save();
            }
        }
    }
}