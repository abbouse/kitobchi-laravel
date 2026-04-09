<?php

namespace App\Services;

use App\Models\Sold;
use App\Models\SellerOrder;
use App\Models\CourierOrder;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\Gifts;
use App\Models\CashbackSetting;
use App\Models\PromocodeHistory;
use App\Models\User;
use App\Models\GiftCertificate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    // =========================================================================
    //  STOCK KAMAYTIRISH
    // =========================================================================

    public function decrementStock(array $data): void
    {
        $product  = $data['product'];
        $variant  = $data['variant'] ?? null;
        $quantity = $data['quantity'];

        if ($variant) {
            $variant->decrement('stock', $quantity);
            if ($variant->stock < 0) { $variant->stock = 0; $variant->save(); }
            return;
        }

        if ($product instanceof Books) {
            $product->decrement('count', $quantity);
            if ($product->count < 0) { $product->count = 0; $product->save(); }
        } elseif ($product instanceof Stationery) {
            $product->decrement('stock', $quantity);
            if ($product->stock < 0) { $product->stock = 0; $product->save(); }
        }
    }

    // =========================================================================
    //  STOCK QAYTARISH
    // =========================================================================

    public function incrementStock(array $item): void
    {
        $type      = $item['type']       ?? 'book';
        $productId = $item['item_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity  = $item['count_item'];

        if ($type === 'book') {
            Books::where('id', $productId)->increment('count', $quantity);
        } elseif ($type === 'stationery') {
            if ($variantId) {
                StationeryVariant::where('id', $variantId)->increment('stock', $quantity);
            } else {
                Stationery::where('id', $productId)->increment('stock', $quantity);
            }
        }
    }

    // =========================================================================
    //  STATISTIKA: Yaratilganda
    // =========================================================================

    public function incrementProductStats(array $data, int $soldId): void
    {
        $product  = $data['product'];
        $quantity = $data['quantity'];
        $revenue  = $data['revenue'];
        $userId   = $data['user_id'];
        $type     = $data['type'] ?? 'book';

        if (!$product || $type === 'gift') return;

        $product->increment('totalSales',       $quantity);
        $product->increment('totalRevenue',      $revenue);
        $product->increment('totalSalesWeek',    $quantity);
        $product->increment('totalRevenueWeek',  $revenue);

        $hasPrev = Sold::where('user_id', $userId)
            ->where('id', '!=', $soldId)
            ->whereJsonContains('items', ['item_id' => $product->id, 'type' => $type])
            ->exists();

        if (!$hasPrev) {
            $product->increment('totalClients');
            $product->increment('totalClientsWeek');
        }
        $product->save();
    }

    // =========================================================================
    //  STATISTIKA: Bekor qilinganda
    // =========================================================================

    public function decrementProductStats(array $item): void
    {
        $type      = $item['type']       ?? 'book';
        $productId = $item['item_id'];
        $quantity  = $item['count_item'];
        $revenue   = ($item['item_price'] ?? 0) * $quantity;

        if ($type === 'gift') return;

        $product = $type === 'stationery'
            ? Stationery::find($productId)
            : Books::find($productId);

        if (!$product) return;

        $product->totalSales       = max(0, $product->totalSales       - $quantity);
        $product->totalRevenue     = max(0, $product->totalRevenue     - $revenue);
        $product->totalSalesWeek   = max(0, $product->totalSalesWeek   - $quantity);
        $product->totalRevenueWeek = max(0, $product->totalRevenueWeek - $revenue);
        $product->save();
    }

    // =========================================================================
    //  TO'LOV TASDIQLANGANDA
    // =========================================================================

    public function handleOrderPaid(Sold $order, User $user, bool $giveCashback = true): void
    {
        $order->update(['paymentStatus' => 2]);
        SellerOrder::where('order_id', $order->id)->update(['status' => 1]);
        CourierOrder::where('order_id', $order->id)->update(['status' => 'pending']);

        if ($giveCashback) {
            $cashbackPercent = CashbackSetting::getCashbackPercentage($order->amount);
            if ($cashbackPercent > 0) {
                $cashbackAmount = (int)(($order->amount * $cashbackPercent) / 100);
                $user->increment('cashback', $cashbackAmount);
                Log::info("Cashback: user#{$user->id} order#{$order->id} +{$cashbackAmount}");
            }
        }
    }

    // =========================================================================
    //  BUYURTMANI BEKOR QILISH
    //
    //  Idempotency kafolati:
    //  - DB::table('solds')->where('status','!=','F')->update(...) → affected rows tekshiriladi
    //  - affected = 0 bo'lsa (allaqachon 'F') → hech narsa qaytarilmaydi, return
    //  - affected = 1 bo'lsa → bir marta ishlaydi, qaytarish operatsiyalari bajariladi
    //
    //  Bu yondashuv bilan parallel yoki takroriy chaqiruv xavfsiz.
    // =========================================================================

    public function cancelOrder(Sold $order, bool $strict = true): array
    {
        // Tez tekshiruv (DB ga bormaydi)
        if ($order->status === 'F') {
            return ['ok' => false, 'message' => 'Buyurtma allaqachon bekor qilingan.'];
        }

        if ($strict) {
            if (now()->gt($order->created_at->addHour())) {
                return ['ok' => false, 'message' => 'cancel_order_error_expired'];
            }
            if (!in_array((int)$order->paymentStatus, [0, 1])) {
                return ['ok' => false, 'message' => 'cancel_order_error_expired'];
            }
        }

        DB::transaction(function () use ($order) {

            // ── STATUS UPDATE — bu butun logikaning kaliti ────────────────
            //
            // WHERE status != 'F' sharti — bu IDEMPOTENCY himoyasi:
            //   - Birinchi chaqiruv: status A/P/B/C → F ga o'zgaradi, affected=1
            //   - Ikkinchi chaqiruv: status allaqachon F → hech narsa o'zgarmaydi, affected=0
            //
            // affected = 0 bo'lsa — quyidagi HECH QANDAY operatsiya bajarilmaydi.
            // Shu sababli stock, cashback, cert 2x qaytarilmaydi.

            $affected = DB::table('solds')
                ->where('id',     $order->id)
                ->where('status', '!=', 'F')
                ->update([
                    'status'        => 'F',
                    'paymentStatus' => 3,
                    'updated_at'    => now(),
                ]);

            // Agar buyurtma allaqachon bekor qilingan bo'lsa — to'xtatamiz
            if ($affected === 0) {
                return; // Transaction commit, lekin hech narsa o'zgarmadi
            }

            // ── Bu yerga faqat BIRINCHI marta yetib kelinadi ─────────────

            SellerOrder::where('order_id', $order->id)->update(['status' => 3]);
            CourierOrder::where('order_id', $order->id)->update(['status' => 'rejected']);

            // ── Mahsulot stoki qaytarish ──────────────────────────────────
            foreach ($order->items ?? [] as $item) {
                $type = $item['type'] ?? '';
                if (!in_array($type, ['book', 'stationery'])) continue;
                $this->incrementStock($item);
                $this->decrementProductStats($item);
            }

            // ── Gift stoki qaytarish ──────────────────────────────────────
            if ($order->gift) {
                Gifts::where('id', $order->gift)->increment('stock', 1);
                $gift = Gifts::find($order->gift);
                if ($gift) {
                    $gift->totalSales     = max(0, $gift->totalSales     - 1);
                    $gift->totalSalesWeek = max(0, $gift->totalSalesWeek - 1);
                    $gift->save();
                }
            }

            // ── Cashback qaytarish ────────────────────────────────────────
            // withCashback = true faqat karta+cashback ishlatilganda saqlanadi
            // cashbackAmount = qancha ayirilgani
            if ($order->withCashback && (int)$order->cashbackAmount > 0) {
                DB::table('users')
                    ->where('id', $order->user_id)
                    ->increment('cashback', (int)$order->cashbackAmount);
            }

            // ── Gift Sertifikat qaytarish ─────────────────────────────────
            // giftCertAmount = sertifikatdan qancha ayirilgani
            // Bu yerga faqat affected=1 bo'lganda kelinadi → bir marta ishlaydi
            if ($order->gift_certificate_id && (int)$order->giftCertAmount > 0) {
                $certId     = (int)$order->gift_certificate_id;
                $returnAmt  = (int)$order->giftCertAmount;

                // DB dan fresh qiymat olib, atomic update
                // used → active + nominal tiklash
                // active (partial) → nominal oshirish
                $updatedUsed = DB::table('gift_certificates')
                    ->where('id',     $certId)
                    ->where('status', GiftCertificate::STATUS_USED)
                    ->update([
                        'status'      => GiftCertificate::STATUS_ACTIVE,
                        'nominal_uzs' => DB::raw("nominal_uzs + {$returnAmt}"),
                        'used_at'     => null,
                        'updated_at'  => now(),
                    ]);

                if ($updatedUsed === 0) {
                    // 'used' emas — 'active' (partial use) bo'lishi mumkin
                    DB::table('gift_certificates')
                        ->where('id',     $certId)
                        ->where('status', GiftCertificate::STATUS_ACTIVE)
                        ->update([
                            'nominal_uzs' => DB::raw("nominal_uzs + {$returnAmt}"),
                            'updated_at'  => now(),
                        ]);
                }
            }

            // ── Promokod qaytarish ────────────────────────────────────────
            if ($order->promocode) {
                $promo = DB::table('promocodes')
                    ->where('code', $order->promocode)
                    ->first();
                if ($promo) {
                    DB::table('promocodes')
                        ->where('id',         $promo->id)
                        ->where('usedCount',  '>', 0)
                        ->decrement('usedCount', 1);

                    PromocodeHistory::where('user_id',      $order->user_id)
                                    ->where('promocode_id', $promo->id)
                                    ->delete();
                }
            }
        });

        // Local instance yangilansin
        $order->status        = 'F';
        $order->paymentStatus = 3;

        return ['ok' => true, 'message' => 'order_canceled'];
    }
}