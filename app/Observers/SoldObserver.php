<?php

namespace App\Observers;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Sold;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\Gifts;
use App\Models\SellerOrder;
use App\Models\CourierOrder;
use App\Models\PromocodeHistory;
use App\Models\User;
use App\Services\SellerOrderSettlementService;
use App\Services\CourierOrderSettlementService;
use App\Services\ProductReviewPromptService;
use App\Services\OrderService;
use App\Services\UserReputationService;
use App\Services\UserPositionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SoldObserver
{
    public function __construct(
        private readonly UserPositionService $positionService,
        private readonly SellerOrderSettlementService $sellerOrderSettlementService,
        private readonly CourierOrderSettlementService $courierOrderSettlementService,
        private readonly ProductReviewPromptService $productReviewPromptService,
        private readonly OrderService $orderService,
        private readonly UserReputationService $userReputationService,
    ) {}

    /**
     * Sold model yangilanganda chaqiriladi.
     * Status F ga o'zgarganda — to'liq rollback bajaradi.
     */
    public function updated(Sold $order): void
    {
        $statusChanged = $order->wasChanged('status');
        $paymentStatusChanged = $order->wasChanged('paymentStatus');
        $previousStatus = (string) $order->getOriginal('status');
        $previousPaymentStatus = (int) $order->getOriginal('paymentStatus');
        $currentStatusCode = $order->status_code;
        $currentPaymentStatusCode = $order->payment_status_code;
        $previousStatusCode = OrderStatusCode::fromLegacy($previousStatus)->value;
        $previousPaymentStatusCode = PaymentStatusCode::fromLegacy($previousPaymentStatus)->value;
        $currentCompletedPaid = Sold::isCompletedPaidState(
            $currentStatusCode,
            $currentPaymentStatusCode,
            $order->deliveryType,
        );
        $previousCompletedPaid = Sold::isCompletedPaidState(
            $previousStatusCode,
            $previousPaymentStatusCode,
            $order->getOriginal('deliveryType') ?? $order->deliveryType,
        );
        $dbCompletedAt = $order->getAttribute('completed_at');
        $completionTimestamp = now();

        if ($currentCompletedPaid && !$dbCompletedAt) {
            DB::table('solds')
                ->where('id', $order->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => $completionTimestamp]);
            $order->forceFill(['completed_at' => $completionTimestamp]);
        } elseif (!$currentCompletedPaid && $dbCompletedAt) {
            DB::table('solds')
                ->where('id', $order->id)
                ->whereNotNull('completed_at')
                ->update(['completed_at' => null]);
            $order->forceFill(['completed_at' => null]);
        }

        if (($statusChanged || $paymentStatusChanged) && !$previousCompletedPaid && $currentCompletedPaid) {
            $this->sellerOrderSettlementService->settleCompletedOrder($order);
            $this->courierOrderSettlementService->settleCompletedOrder($order);
            $this->productReviewPromptService->scheduleForCompletedOrder($order->fresh());
        }

        if (($statusChanged || $paymentStatusChanged) && $previousCompletedPaid && !$currentCompletedPaid) {
            $this->sellerOrderSettlementService->reverseCompletedOrderSettlement(
                $order,
                "old_status={$previousStatusCode}, old_payment={$previousPaymentStatusCode}, new_status={$currentStatusCode}, new_payment={$currentPaymentStatusCode}"
            );
            $this->courierOrderSettlementService->reverseCompletedOrderSettlement(
                $order,
                "old_status={$previousStatusCode}, old_payment={$previousPaymentStatusCode}, new_status={$currentStatusCode}, new_payment={$currentPaymentStatusCode}"
            );
            $this->productReviewPromptService->closeForOrder($order->fresh(), 'order_reverted');
            $this->orderService->reverseAwardedCashbackForOrderReopened(
                $order,
                "old_status={$previousStatusCode}, old_payment={$previousPaymentStatusCode}, new_status={$currentStatusCode}, new_payment={$currentPaymentStatusCode}"
            );
        }

        if (($statusChanged || $paymentStatusChanged) && !$previousCompletedPaid && $currentCompletedPaid && $order->user_id) {
            $user = User::find($order->user_id);
            if ($user) {
                $this->positionService->evaluateAndPromote($user, 'order_completed');
            }
        }

        if (($statusChanged || $paymentStatusChanged) && $order->user_id) {
            $user = User::find($order->user_id);
            if ($user) {
                $this->userReputationService->recalculateUser($user);
            }
        }

        // Faqat status o'zgarganda va yangi qiymat F bo'lganda
        if (!$order->wasChanged('status') || $currentStatusCode !== OrderStatusCode::CANCELLED->value) {
            return;
        }

        // Oldingi status ham F bo'lsa — ikki marta rollback qilmaymiz
        if ($previousStatusCode === OrderStatusCode::CANCELLED->value) {
            Log::info("SoldObserver: order #{$order->id} was already F, skip.");
            return;
        }

        Log::info("SoldObserver: status changed {$previousStatus} → F for order #{$order->id}");

        DB::beginTransaction();
        try {

            // ── 1. paymentStatus = 3 ──────────────────────────────────────────
            DB::table('solds')
                ->where('id', $order->id)
                ->update([
                    'paymentStatus' => PaymentStatusCode::CANCELLED->legacy(),
                    'payment_status_code' => PaymentStatusCode::CANCELLED->value,
                    'updated_at' => now(),
                ]);

            // ── 2. Mahsulot stocklari (filial-darajali qaytarish) ────────────
            $branchStock = app(\App\Services\BranchStockService::class);

            foreach ($order->items ?? [] as $item) {
                $type      = $item['type'] ?? 'book';
                $productId = (int) ($item['item_id'] ?? 0);
                $variantId = (int) ($item['variant_id'] ?? 0);
                $quantity  = (int) ($item['count_item'] ?? 1);

                if (!$productId || $type === 'gift') continue;

                if (in_array($type, ['book', 'stationery'], true)) {
                    $branchStock->incrementForReturn(
                        $type, $productId, $type === 'book' ? 0 : $variantId, $quantity, null,
                        ['ref_type' => 'sold', 'ref_id' => $order->id, 'note' => 'Buyurtma bekor qilindi']
                    );
                }
            }

            // ── 3. Gift stock ─────────────────────────────────────────────────
            if ($order->gift) {
                $branchStock->incrementForReturn(
                    'gift', (int) $order->gift, 0, 1, null,
                    ['ref_type' => 'sold', 'ref_id' => $order->id]
                );
            }

            // ── 4. Statistika minus ───────────────────────────────────────────
            foreach ($order->items ?? [] as $item) {
                $type      = $item['type'] ?? 'book';
                $productId = (int) ($item['item_id'] ?? 0);
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

            // ── 5. Gift statistika ────────────────────────────────────────────
            if ($order->gift) {
                $gift = Gifts::find($order->gift);
                if ($gift) {
                    $gift->decrement('totalSales',     1);
                    $gift->decrement('totalSalesWeek', 1);
                    $gift->totalSales     = max(0, $gift->totalSales);
                    $gift->totalSalesWeek = max(0, $gift->totalSalesWeek);
                    $gift->save();
                }
            }

            // ── 6. Cashback ───────────────────────────────────────────────────
            if ($order->withCashback && $order->cashbackAmount > 0) {
                $user = User::find($order->user_id);
                if ($user) {
                    $user->increment('cashback', (int) $order->cashbackAmount);
                    Log::info("SoldObserver: +{$order->cashbackAmount} cashback → user #{$user->id}");
                }
            }

            // ── 7. Promokod ───────────────────────────────────────────────────
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

                    Log::info("SoldObserver: promo '{$order->promocode}' returned → user #{$order->user_id}");
                }
            }

            // ── 8. SellerOrder va CourierOrder ────────────────────────────────
            $sellerCount  = SellerOrder::where('order_id', $order->id)
                ->update([
                    'status' => SellerOrderStatusCode::CANCELLED->legacy(),
                    'status_code' => SellerOrderStatusCode::CANCELLED->value,
                    'updated_at' => now(),
                ]);

            $courierCount = CourierOrder::where('order_id', $order->id)
                ->update([
                    'status' => CourierOrderStatusCode::CANCELLED->legacy(),
                    'status_code' => CourierOrderStatusCode::CANCELLED->value,
                    'updated_at' => now(),
                ]);

            Log::info("SoldObserver: seller_orders={$sellerCount}, courier_orders={$courierCount} updated for #{$order->id}");

            DB::commit();
            Log::info("SoldObserver: order #{$order->id} rollback completed.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("SoldObserver: FAILED for order #{$order->id}: {$e->getMessage()}", [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }
}
