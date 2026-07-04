<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderKind;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Books;
use App\Models\CashbackSetting;
use App\Models\CourierOrder;
use App\Models\GiftCertificate;
use App\Models\Gifts;
use App\Models\PromocodeHistory;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OrderService
{
    public function __construct(
        private readonly CashbackHistoryService $cashbackHistoryService,
        private readonly CashbackNotificationService $cashbackNotificationService,
        private readonly PostalResendService $postalResendService,
        private readonly SellerOrderSettlementService $sellerOrderSettlementService,
        private readonly CourierOrderSettlementService $courierOrderSettlementService,
        private readonly ProductReviewPromptService $productReviewPromptService,
        private readonly UserReputationService $userReputationService,
    ) {}

    // =========================================================================
    //  STOCK KAMAYTIRISH
    // =========================================================================

    public function decrementStock(array $data): void
    {
        $product = $data['product'];
        $variant = $data['variant'] ?? null;
        $quantity = $data['quantity'];

        if ($variant) {
            $variant->decrement('stock', $quantity);
            if ($variant->stock < 0) {
                $variant->stock = 0;
                $variant->save();
            }

            return;
        }

        if ($product instanceof Books) {
            $product->decrement('count', $quantity);
            if ($product->count < 0) {
                $product->count = 0;
                $product->save();
            }
        } elseif ($product instanceof Stationery) {
            $product->decrement('stock', $quantity);
            if ($product->stock < 0) {
                $product->stock = 0;
                $product->save();
            }
        }
    }

    // =========================================================================
    //  STOCK QAYTARISH
    // =========================================================================

    public function incrementStock(array $item): void
    {
        $type = $item['type'] ?? 'book';
        $productId = $item['item_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity = $item['count_item'];

        if ($type === 'book') {
            $product = Books::query()->lockForUpdate()->find($productId);
            if (! $product) {
                return;
            }

            $product->count = (int) ($product->count ?? 0) + (int) $quantity;
            $product->save();
        } elseif ($type === 'stationery') {
            if ($variantId) {
                $variant = StationeryVariant::query()->lockForUpdate()->find($variantId);
                if (! $variant) {
                    return;
                }

                $variant->stock = (int) ($variant->stock ?? 0) + (int) $quantity;
                $variant->save();
            } else {
                $product = Stationery::query()->lockForUpdate()->find($productId);
                if (! $product) {
                    return;
                }

                $product->stock = (int) ($product->stock ?? 0) + (int) $quantity;
                $product->save();
            }
        }
    }

    // =========================================================================
    //  STATISTIKA: Yaratilganda
    // =========================================================================

    public function incrementProductStats(array $data, int $soldId): void
    {
        $product = $data['product'];
        $quantity = $data['quantity'];
        $revenue = $data['revenue'];
        $userId = $data['user_id'];
        $type = $data['type'] ?? 'book';

        if (! $product || $type === 'gift') {
            return;
        }

        $product->increment('totalSales', $quantity);
        $product->increment('totalRevenue', $revenue);
        $product->increment('totalSalesWeek', $quantity);
        $product->increment('totalRevenueWeek', $revenue);

        $hasPrev = Sold::where('user_id', $userId)
            ->where('id', '!=', $soldId)
            ->whereJsonContains('items', ['item_id' => $product->id, 'type' => $type])
            ->exists();

        if (! $hasPrev) {
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
        $type = $item['type'] ?? 'book';
        $productId = $item['item_id'];
        $quantity = $item['count_item'];
        $unitRevenue = $item['seller_item_price'] ?? $item['item_price'] ?? 0;
        $revenue = $unitRevenue * $quantity;

        if ($type === 'gift') {
            return;
        }

        $product = $type === 'stationery'
            ? Stationery::find($productId)
            : Books::find($productId);

        if (! $product) {
            return;
        }

        $product->totalSales = max(0, $product->totalSales - $quantity);
        $product->totalRevenue = max(0, $product->totalRevenue - $revenue);
        $product->totalSalesWeek = max(0, $product->totalSalesWeek - $quantity);
        $product->totalRevenueWeek = max(0, $product->totalRevenueWeek - $revenue);
        $product->save();
    }

    // =========================================================================
    //  KARTA SUMMASI HOLDA USHLANGANDA
    // =========================================================================

    public function handleOrderHeld(Sold $order): void
    {
        $order->update([
            'paymentStatus' => PaymentStatusCode::HELD->legacy(),
            'payment_status_code' => PaymentStatusCode::HELD->value,
        ]);

        if ($order->order_kind === OrderKind::POSTAL_RESEND->value) {
            return;
        }

        SellerOrder::where('order_id', $order->id)
            ->where(function ($query) {
                $query->where('status', SellerOrderStatusCode::PAYMENT_PENDING->legacy())
                    ->orWhere('status_code', SellerOrderStatusCode::PAYMENT_PENDING->value);
            })
            ->update([
                'status' => SellerOrderStatusCode::NEW->legacy(),
                'status_code' => SellerOrderStatusCode::NEW->value,
            ]);

        CourierOrder::where('order_id', $order->id)
            ->where(function ($query) {
                $query->where('status', CourierOrderStatusCode::PAYMENT_PENDING->legacy())
                    ->orWhere('status_code', CourierOrderStatusCode::PAYMENT_PENDING->value);
            })
            ->update([
                'status' => CourierOrderStatusCode::PENDING->legacy(),
                'status_code' => CourierOrderStatusCode::PENDING->value,
            ]);
    }

    // =========================================================================
    //  TO'LOV TASDIQLANGANDA
    // =========================================================================

    public function handleOrderPaid(Sold $order, User $user, bool $giveCashback = true): void
    {
        $payload = [
            'paymentStatus' => PaymentStatusCode::PAID->legacy(),
            'payment_status_code' => PaymentStatusCode::PAID->value,
        ];
        if ($order->isCompletedAndPaid() && ! $order->completed_at) {
            $payload['completed_at'] = now();
        }

        $order->update($payload);
        if ($order->order_kind === OrderKind::POSTAL_RESEND->value) {
            $this->postalResendService->activatePaidResendOrder($order->fresh());
        } else {
            SellerOrder::where('order_id', $order->id)
                ->where(function ($query) {
                    $query->where('status', SellerOrderStatusCode::PAYMENT_PENDING->legacy())
                        ->orWhere('status_code', SellerOrderStatusCode::PAYMENT_PENDING->value);
                })
                ->update([
                    'status' => SellerOrderStatusCode::NEW->legacy(),
                    'status_code' => SellerOrderStatusCode::NEW->value,
                ]);

            CourierOrder::where('order_id', $order->id)
                ->where(function ($query) {
                    $query->where('status', CourierOrderStatusCode::PAYMENT_PENDING->legacy())
                        ->orWhere('status_code', CourierOrderStatusCode::PAYMENT_PENDING->value);
                })
                ->update([
                    'status' => CourierOrderStatusCode::PENDING->legacy(),
                    'status_code' => CourierOrderStatusCode::PENDING->value,
                ]);
        }

        if ($giveCashback) {
            $this->processCashbackAfterOrderMutation($order, $user);
        }
    }

    public function processCashbackAfterOrderMutation(Sold $order, ?User $user = null): int
    {
        $order->refresh();

        if ($order->order_kind === OrderKind::POSTAL_RESEND->value) {
            return 0;
        }

        if ($order->payment_status_code !== PaymentStatusCode::PAID->value) {
            return 0;
        }

        if ($order->isCompletedAndPaid()) {
            $this->sellerOrderSettlementService->settleCompletedOrder($order);
            $this->courierOrderSettlementService->settleCompletedOrder($order);

            if ((bool) ($order->is_instore ?? false)) {
                return $this->awardCashbackForPaidOrder($order, $user, notify: true);
            }

            $this->scheduleCashbackRelease($order, $order->completed_at);
        }

        return 0;
    }

    public function scheduleCashbackRelease(Sold $order, ?CarbonInterface $from = null): void
    {
        // Split buyurtmalar keshbek olmaydi — release navbatiga ham qo'yilmaydi.
        if ($this->orderHasSplitContract($order)) {
            return;
        }

        DB::transaction(function () use ($order, $from) {
            $lockedOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder) {
                return;
            }

            if ((bool) ($lockedOrder->is_instore ?? false)) {
                return;
            }

            if (! $lockedOrder->isCompletedAndPaid()) {
                return;
            }

            if ((int) ($lockedOrder->awarded_cashback_amount ?? 0) > 0 || $lockedOrder->cashback_awarded_at) {
                return;
            }

            if ($lockedOrder->cashback_ready_at) {
                return;
            }

            $readyAt = ($from ?? now())->copy()->addDays(7);
            $lockedOrder->cashback_ready_at = $readyAt;
            $lockedOrder->save();
        });
    }

    public function releaseScheduledCashback(Sold $order, ?User $user = null, bool $notify = true): int
    {
        return $this->awardCashbackForPaidOrder($order, $user, notify: $notify);
    }

    public function reverseCompletedOrderSideEffects(Sold $order, ?string $reason = null): void
    {
        $freshOrder = $order->fresh() ?? $order;
        $reason ??= "completed_order_reopened: order={$freshOrder->id}";

        $this->sellerOrderSettlementService->reverseCompletedOrderSettlement($freshOrder, $reason);
        $this->courierOrderSettlementService->reverseCompletedOrderSettlement($freshOrder, $reason);
        $this->productReviewPromptService->closeForOrder($freshOrder, 'order_reopened_after_completion');
    }

    public function reverseAwardedCashbackForOrderReopened(Sold $order, ?string $reason = null): void
    {
        $freshOrder = $order->fresh() ?? $order;
        if (! $freshOrder->user_id) {
            return;
        }

        DB::transaction(function () use ($freshOrder, $reason) {
            $lockedOrder = Sold::query()->lockForUpdate()->find($freshOrder->id);
            if (! $lockedOrder) {
                return;
            }

            if (! $lockedOrder->cashback_ready_at
                && ! $lockedOrder->cashback_notified_at
                && (int) ($lockedOrder->awarded_cashback_amount ?? 0) <= 0
            ) {
                return;
            }

            $revokeAmount = (int) ($lockedOrder->awarded_cashback_amount ?? 0);
            $balanceBefore = (int) DB::table('users')->where('id', $lockedOrder->user_id)->value('cashback');

            if ($revokeAmount > 0) {
                DB::table('users')
                    ->where('id', $lockedOrder->user_id)
                    ->decrement('cashback', $revokeAmount);
            }

            DB::table('solds')->where('id', $lockedOrder->id)->update([
                'awarded_cashback_amount' => 0,
                'cashback_awarded_at' => null,
                'cashback_ready_at' => null,
                'cashback_notified_at' => null,
                'updated_at' => now(),
            ]);

            if ($revokeAmount > 0) {
                $this->cashbackHistoryService->record(
                    userId: (int) $lockedOrder->user_id,
                    action: 'revoked',
                    amount: -$revokeAmount,
                    order: $lockedOrder,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceBefore - $revokeAmount,
                    meta: ['reason' => $reason ?? 'completed_order_reopened'],
                );
            }

            Log::warning('Order cashback reversed after completion reopened', [
                'order_id' => $lockedOrder->id,
                'user_id' => $lockedOrder->user_id,
                'revoke_amount' => $revokeAmount,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Buyurtma nasiya (split) orqali rasmiylashtirilganmi — bekor qilinganlar
     * hisobga olinmaydi (bekor bo'lsa buyurtma oddiy statusiga qaytadi).
     */
    private function orderHasSplitContract(Sold $order): bool
    {
        static $hasTable = null;
        $hasTable ??= Schema::hasTable('split_contracts');

        if (! $hasTable) {
            return false;
        }

        return DB::table('split_contracts')
            ->where('order_id', $order->id)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    public function awardCashbackForPaidOrder(Sold $order, ?User $user = null, bool $notify = false): int
    {
        // Nasiya (split) buyurtmalarga keshbek berilmaydi — foizsiz tarif ustiga
        // keshbek berish ikki tomonlama marja xarajati bo'lardi.
        if ($this->orderHasSplitContract($order)) {
            return 0;
        }

        $user ??= $order->user()->first();
        if (! $user) {
            return 0;
        }

        return DB::transaction(function () use ($order, $user, $notify) {
            $lockedOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder) {
                return 0;
            }

            if ((int) ($lockedOrder->awarded_cashback_amount ?? 0) > 0 || $lockedOrder->cashback_awarded_at) {
                return (int) ($lockedOrder->awarded_cashback_amount ?? 0);
            }

            // In-store xaridlar uchun pickup cashback alohida ishlaydi.
            // Oddiy delivery yoki oddiy pickup buyurtmalar esa delivery tariffidan yuradi.
            $cashbackType = (bool) ($lockedOrder->is_instore ?? false)
                ? CashbackSetting::TYPE_PICKUP
                : CashbackSetting::TYPE_DELIVERY;
            $cashbackPercent = CashbackSetting::getCashbackPercentage(
                (int) $lockedOrder->amount,
                $cashbackType
            );
            if ($cashbackPercent <= 0) {
                return 0;
            }

            $cashbackAmount = (int) (($lockedOrder->amount * $cashbackPercent) / 100);
            if ($cashbackAmount <= 0) {
                return 0;
            }

            $balanceBefore = (int) DB::table('users')->where('id', $user->id)->value('cashback');
            DB::table('users')->where('id', $user->id)->increment('cashback', $cashbackAmount);
            $balanceAfter = $balanceBefore + $cashbackAmount;

            DB::table('solds')->where('id', $lockedOrder->id)->update([
                'awarded_cashback_amount' => $cashbackAmount,
                'cashback_awarded_at' => now(),
                'cashback_ready_at' => null,
                'updated_at' => now(),
            ]);

            $this->cashbackHistoryService->record(
                userId: $user->id,
                action: 'earned',
                amount: $cashbackAmount,
                order: $lockedOrder,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                meta: ['order_amount' => (int) $lockedOrder->amount],
            );

            Log::info("Cashback: user#{$user->id} order#{$lockedOrder->id} +{$cashbackAmount}");

            if ($notify && $this->cashbackNotificationService->sendAwarded(
                $user,
                $lockedOrder,
                $cashbackAmount,
                (bool) ($lockedOrder->is_instore ?? false),
            )) {
                DB::table('solds')->where('id', $lockedOrder->id)->update([
                    'cashback_notified_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $cashbackAmount;
        });
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
        $previousCompletedPaid = $order->isCompletedAndPaid();
        $didCancel = false;
        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);

        // Tez tekshiruv (DB ga bormaydi)
        if ($order->status_code === OrderStatusCode::CANCELLED->value) {
            return ['ok' => false, 'message' => 'Buyurtma allaqachon bekor qilingan.'];
        }

        if ($strict) {
            // paymentStatus=0: naqd (to'lanmagan), paymentStatus=1: karta (to'lanmagan)
            // paymentStatus=2: to'langan → bekor qilish mumkin emas
            if (! in_array($order->payment_status_code, [
                PaymentStatusCode::CASH_PENDING->value,
                PaymentStatusCode::CARD_PENDING->value,
                PaymentStatusCode::HELD->value,
            ], true)) {
                return ['ok' => false, 'message' => 'cancel_order_error_paid'];
            }
        }

        if ($paymentStatus === PaymentStatusCode::HELD) {
            $this->dismissPaylovOrderHold($order, 'order_cancelled');
        }

        DB::transaction(function () use ($order, &$didCancel) {

            // ── STATUS UPDATE — bu butun logikaning kaliti ────────────────
            //
            // WHERE status != 'F' sharti — bu IDEMPOTENCY himoyasi:
            //   - Birinchi chaqiruv: status A/P/B/C → F ga o'zgaradi, affected=1
            //   - Ikkinchi chaqiruv: status allaqachon F → hech narsa o'zgarmaydi, affected=0
            //
            // affected = 0 bo'lsa — quyidagi HECH QANDAY operatsiya bajarilmaydi.
            // Shu sababli stock, cashback, cert 2x qaytarilmaydi.

            $affected = DB::table('solds')
                ->where('id', $order->id)
                ->where('status', '!=', 'F')
                ->update([
                    'status' => OrderStatusCode::CANCELLED->legacy(),
                    'status_code' => OrderStatusCode::CANCELLED->value,
                    'paymentStatus' => PaymentStatusCode::CANCELLED->legacy(),
                    'payment_status_code' => PaymentStatusCode::CANCELLED->value,
                    'cashback_ready_at' => null,
                    'cashback_notified_at' => null,
                    'updated_at' => now(),
                ]);

            // Agar buyurtma allaqachon bekor qilingan bo'lsa — to'xtatamiz
            if ($affected === 0) {
                return; // Transaction commit, lekin hech narsa o'zgarmadi
            }

            $didCancel = true;

            // ── Bu yerga faqat BIRINCHI marta yetib kelinadi ─────────────

            SellerOrder::where('order_id', $order->id)->update([
                'status' => SellerOrderStatusCode::CANCELLED->legacy(),
                'status_code' => SellerOrderStatusCode::CANCELLED->value,
            ]);
            CourierOrder::where('order_id', $order->id)->update([
                'status' => CourierOrderStatusCode::CANCELLED->legacy(),
                'status_code' => CourierOrderStatusCode::CANCELLED->value,
            ]);

            // ── Mahsulot stoki qaytarish ──────────────────────────────────
            foreach ($order->items ?? [] as $item) {
                $type = $item['type'] ?? '';
                if (! in_array($type, ['book', 'stationery'])) {
                    continue;
                }
                $this->incrementStock($item);
                $this->decrementProductStats($item);
            }

            // ── Gift stoki qaytarish ──────────────────────────────────────
            if ($order->gift) {
                Gifts::where('id', $order->gift)->increment('stock', 1);
                $gift = Gifts::find($order->gift);
                if ($gift) {
                    $gift->totalSales = max(0, $gift->totalSales - 1);
                    $gift->totalSalesWeek = max(0, $gift->totalSalesWeek - 1);
                    $gift->save();
                }
            }

            // ── Cashback qaytarish ────────────────────────────────────────
            // withCashback = true faqat karta+cashback ishlatilganda saqlanadi
            // cashbackAmount = qancha ayirilgani
            if ($order->withCashback && (int) $order->cashbackAmount > 0) {
                $refundAmount = (int) $order->cashbackAmount;
                $alreadyRefunded = DB::table('cashback_histories')
                    ->where('sold_id', $order->id)
                    ->where('action', 'refund')
                    ->exists();

                if (! $alreadyRefunded) {
                    $balanceBefore = (int) DB::table('users')->where('id', $order->user_id)->value('cashback');

                    DB::table('users')
                        ->where('id', $order->user_id)
                        ->increment('cashback', $refundAmount);

                    $this->cashbackHistoryService->record(
                        userId: (int) $order->user_id,
                        action: 'refund',
                        amount: $refundAmount,
                        order: $order,
                        balanceBefore: $balanceBefore,
                        balanceAfter: $balanceBefore + $refundAmount,
                    );
                }
            }

            if ((int) ($order->awarded_cashback_amount ?? 0) > 0) {
                $revokeAmount = (int) $order->awarded_cashback_amount;
                $balanceBefore = (int) DB::table('users')->where('id', $order->user_id)->value('cashback');

                DB::table('users')
                    ->where('id', $order->user_id)
                    ->decrement('cashback', $revokeAmount);

                DB::table('solds')->where('id', $order->id)->update([
                    'awarded_cashback_amount' => 0,
                    'cashback_awarded_at' => null,
                    'cashback_ready_at' => null,
                    'cashback_notified_at' => null,
                    'updated_at' => now(),
                ]);

                $this->cashbackHistoryService->record(
                    userId: (int) $order->user_id,
                    action: 'revoked',
                    amount: -$revokeAmount,
                    order: $order,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceBefore - $revokeAmount,
                );
            }

            // ── Gift Sertifikat qaytarish ─────────────────────────────────
            // giftCertAmount = sertifikatdan qancha ayirilgani
            // Bu yerga faqat affected=1 bo'lganda kelinadi → bir marta ishlaydi
            if ($order->gift_certificate_id && (int) $order->giftCertAmount > 0) {
                $certId = (int) $order->gift_certificate_id;
                $returnAmt = (int) $order->giftCertAmount;

                // DB dan fresh qiymat olib, atomic update
                // used → active + nominal tiklash
                // active (partial) → nominal oshirish
                $updatedUsed = DB::table('gift_certificates')
                    ->where('id', $certId)
                    ->where('status', GiftCertificate::STATUS_USED)
                    ->update([
                        'status' => GiftCertificate::STATUS_ACTIVE,
                        'nominal_uzs' => DB::raw("nominal_uzs + {$returnAmt}"),
                        'used_at' => null,
                        'updated_at' => now(),
                    ]);

                if ($updatedUsed === 0) {
                    // 'used' emas — 'active' (partial use) bo'lishi mumkin
                    DB::table('gift_certificates')
                        ->where('id', $certId)
                        ->where('status', GiftCertificate::STATUS_ACTIVE)
                        ->update([
                            'nominal_uzs' => DB::raw("nominal_uzs + {$returnAmt}"),
                            'updated_at' => now(),
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
                        ->where('id', $promo->id)
                        ->where('usedCount', '>', 0)
                        ->decrement('usedCount', 1);

                    PromocodeHistory::where('user_id', $order->user_id)
                        ->where('promocode_id', $promo->id)
                        ->delete();
                }
            }
        });

        if (! $didCancel) {
            return ['ok' => false, 'message' => 'Buyurtma allaqachon bekor qilingan.'];
        }

        if ($previousCompletedPaid) {
            $this->reverseCompletedOrderSideEffects($order, "order_cancelled_after_paid: order={$order->id}");
        }

        if ($order->user_id) {
            $user = User::find($order->user_id);
            if ($user) {
                $this->userReputationService->recalculateUser($user);
            }
        }

        return ['ok' => true, 'message' => 'Buyurtma bekor qilindi.'];
    }

    private function dismissPaylovOrderHold(Sold $order, string $reason): void
    {
        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->where('state', 1)
            ->latest('id')
            ->get()
            ->first(function (Transaction $transaction) {
                $response = is_array($transaction->provider_response) ? $transaction->provider_response : [];

                return ($response['mode'] ?? null) === 'hold'
                    || data_get($response, 'hold.status') === 'held';
            });

        if (! $transaction || blank($transaction->provider_transaction_id)) {
            throw new \RuntimeException('Hold transaction topilmadi.');
        }

        $dismissResponse = PaylovService::make()->dismissHold((string) $transaction->provider_transaction_id);
        $providerResponse = is_array($transaction->provider_response) ? $transaction->provider_response : [];
        $providerResponse['dismiss'] = $dismissResponse;
        $providerResponse['hold']['status'] = 'dismissed';
        $providerResponse['hold']['dismiss_reason'] = $reason;
        $providerResponse['hold']['dismissed_at'] = now()->toIso8601String();

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update([
                'state' => -1,
                'reason' => 0,
                'cancel_time' => (string) intval(round(microtime(true) * 1000)),
                'provider_response' => json_encode($providerResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }
}
