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
use Illuminate\Http\Request as HttpRequest;
use App\Http\Controllers\PushController;

class CancelUnpaidOrders extends Command
{
    protected $signature   = 'orders:cancel-unpaid';
    protected $description = "To'lanmagan (paymentStatus=1) buyurtmalarni 1 soatdan keyin avtomatik bekor qilish + push xabar";

    // ── Ko'p tillik push xabarlari ────────────────────────────────────────────
    private const PUSH_MESSAGES = [
        'uz' => [
            'title' => "Buyurtma #{id} bekor qilindi ❌",
            'body'  => "#{id}-raqamli buyurtmangiz to'lov amalga oshirilmagani sababli avtomatik bekor qilindi. Savolingiz bo'lsa, biz bilan bog'laning.",
        ],
        'ru' => [
            'title' => "Заказ #{id} отменён ❌",
            'body'  => "Ваш заказ #{id} был автоматически отменён в связи с неоплатой. Если у вас есть вопросы — свяжитесь с нами.",
        ],
        'en' => [
            'title' => "Order #{id} cancelled ❌",
            'body'  => "Your order #{id} has been automatically cancelled due to non-payment. If you have any questions, please contact us.",
        ],
        'ja' => [
            'title' => "注文 #{id} がキャンセルされました ❌",
            'body'  => "注文 #{id} は未払いのため自動的にキャンセルされました。ご不明な点がございましたら、お問い合わせください。",
        ],
    ];

    // =========================================================================
    //  HANDLE
    // =========================================================================

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — To'lanmagan buyurtmalarni bekor qilish boshlandi...");

        $oneHourAgo = Carbon::now()->subHour();

        $unpaidOrders = Sold::where('paymentStatus', 1)
            ->where('status', 'A')
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
                // ── 1. Statusni o'zgartirish ──────────────────────────────────
                $order->status        = 'F';
                $order->paymentStatus = 3;
                $order->save();

                // ── 2. Mahsulot stocklarini qaytarish ─────────────────────────
                foreach ($order->items ?? [] as $item) {
                    if (($item['type'] ?? '') === 'gift') continue;
                    $this->incrementStock($item);
                }

                // ── 3. Gift stockini qaytarish ────────────────────────────────
                if ($order->gift) {
                    $gift = Gifts::find($order->gift);
                    if ($gift && $gift->seller_id == 1) {
                        $gift->increment('stock', 1);
                    }
                }

                // ── 4. Statistikani minus qilish ──────────────────────────────
                $this->decrementStatistics($order);

                // ── 5. Cashback qaytarish ─────────────────────────────────────
                if ($order->withCashback && $order->cashbackAmount > 0) {
                    $user = User::find($order->user_id);
                    if ($user) {
                        $user->increment('cashback', (int) $order->cashbackAmount);
                        Log::info("CancelUnpaid: +{$order->cashbackAmount} cashback → user #{$user->id}");
                    }
                }

                // ── 6. Promokod qaytarish ─────────────────────────────────────
                if ($order->promocode) {
                    $promo = DB::table('promocodes')->where('code', $order->promocode)->first();
                    if ($promo) {
                        DB::table('promocodes')
                            ->where('id', $promo->id)
                            ->where('usedCount', '>', 0)
                            ->decrement('usedCount');

                        PromocodeHistory::where('user_id', $order->user_id)
                            ->where('promocode_id', $promo->id)
                            ->delete();

                        Log::info("CancelUnpaid: promo '{$order->promocode}' returned → user #{$order->user_id}");
                    }
                }

                // ── 7. Bog'liq sub-orderlarni bekor qilish ────────────────────
                SellerOrder::where('order_id', $order->id)->update([
                    'status'     => 3,
                    'updated_at' => now(),
                ]);
                CourierOrder::where('order_id', $order->id)->update([
                    'status'     => 'rejected',
                    'updated_at' => now(),
                ]);

                DB::commit();

                $this->info("Buyurtma #{$order->id} bekor qilindi.");

                // ── 8. Push xabar yuborish (transaction tashqarisida) ─────────
                $this->sendCancelPush($order);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("CancelUnpaid xatosi (order #{$order->id})", [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                $this->error("Buyurtma #{$order->id} — xatolik: {$e->getMessage()}");
            }
        }

        $this->info('Jarayon tugadi.');
    }

    // =========================================================================
    //  PUSH XABAR — foydalanuvchi tiliga mos
    // =========================================================================

    /**
     * Bekor qilingan buyurtma egasiga uning tilidagi push xabar yuboradi.
     * FCM token connected_devices jadvalidan olinadi.
     */
    private function sendCancelPush(Sold $order): void
    {
        try {
            // Foydalanuvchi va uning tili
            $user = User::find($order->user_id);
            if (!$user) {
                Log::warning("CancelUnpaid push: user #{$order->user_id} topilmadi, push yuborilmadi.");
                return;
            }

            $lang = in_array($user->lang ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->lang ?? 'uz')
                : 'uz';

            $msgs  = self::PUSH_MESSAGES[$lang];
            $id    = $order->id;
            $title = str_replace('{id}', $id, $msgs['title']);
            $body  = str_replace('{id}', $id, $msgs['body']);

            // Bu userning FCM tokenlarini olish (connected_devices.user_id mavjud)
            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                Log::info("CancelUnpaid push: user #{$user->id} uchun FCM token topilmadi.");
                return;
            }

            $pushData = [
                'app_key' => 'kitobchi',
                'title'   => $title,
                'body'    => $body,
                'tokens'  => $tokens,
                'data'    => [
                    'type'     => 'order_cancelled',
                    'order_id' => (string) $id,
                ],
            ];

            $request = new HttpRequest();
            $request->replace($pushData);

            $pushController = app(\App\Http\Controllers\PushController::class);
            $response       = $pushController->sendPush($request);

            Log::info("CancelUnpaid push sent: order #{$id}, user #{$user->id}, lang={$lang}", [
                'response' => $response->getContent(),
            ]);

        } catch (\Throwable $e) {
            // Push yuborilmasa ham asosiy jarayon to'xtatilmasin
            Log::error("CancelUnpaid push xatosi (order #{$order->id})", [
                'message' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    //  STOCK YORDAMCHI METODLAR
    // =========================================================================

    private function incrementStock(array $item): void
    {
        $type      = $item['type']       ?? 'book';
        $productId = (int) ($item['item_id']   ?? 0);
        $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
        $quantity  = (int) ($item['count_item'] ?? 1);

        if (!$productId) return;

        if ($type === 'book') {
            $product = Books::find($productId);
            if ($product) $product->increment('count', $quantity);

        } elseif ($type === 'stationery') {
            $product = Stationery::find($productId);
            if ($product) {
                if ($variantId) {
                    $variant = StationeryVariant::find($variantId);
                    if ($variant) $variant->increment('stock', $quantity);
                } else {
                    $product->increment('stock', $quantity);
                }
            }
        }
    }

    // =========================================================================
    //  STATISTIKA YORDAMCHI METOD
    // =========================================================================

    private function decrementStatistics(Sold $order): void
    {
        // Mahsulotlar
        foreach ($order->items ?? [] as $item) {
            $type      = $item['type'] ?? 'book';
            $productId = (int) ($item['item_id']   ?? 0);
            $quantity  = (int) ($item['count_item'] ?? 1);
            $revenue   = (float) ($item['item_price'] ?? 0) * $quantity;

            if (!$productId || $type === 'gift') continue;

            $product = match ($type) {
                'book'       => Books::find($productId),
                'stationery' => Stationery::find($productId),
                default      => null,
            };

            if (!$product) continue;

            $product->decrement('totalSales',       $quantity);
            $product->decrement('totalRevenue',     $revenue);
            $product->decrement('totalSalesWeek',   $quantity);
            $product->decrement('totalRevenueWeek', $revenue);

            $product->totalSales       = max(0, $product->totalSales);
            $product->totalRevenue     = max(0, $product->totalRevenue);
            $product->totalSalesWeek   = max(0, $product->totalSalesWeek);
            $product->totalRevenueWeek = max(0, $product->totalRevenueWeek);
            $product->save();
        }

        // Platforma giftlari
        if ($order->gift) {
            $gift = Gifts::find($order->gift);
            if ($gift && $gift->seller_id == 1) {
                $gift->decrement('totalSales',     1);
                $gift->decrement('totalSalesWeek', 1);
                $gift->totalSales     = max(0, $gift->totalSales);
                $gift->totalSalesWeek = max(0, $gift->totalSalesWeek);
                $gift->save();
            }
        }
    }
}