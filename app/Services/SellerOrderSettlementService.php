<?php

namespace App\Services;

use App\Enums\OrderKind;
use App\Enums\SellerOrderStatusCode;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerOrderSettlementService
{
    public const CATEGORY_ORDER_SALE = 'order_sale';

    public const CATEGORY_ORDER_REVERSAL = 'order_reversal';

    public const CATEGORY_WITHDRAWAL = 'withdrawal';

    public const CATEGORY_LEGACY_INFLIGHT_ADJUSTMENT = 'legacy_inflight_adjustment';

    public function __construct(private readonly SellerCommissionService $commissionService) {}

    public function settleCompletedOrder(Sold $order): void
    {
        if ($order->order_kind !== OrderKind::STANDARD->value) {
            return;
        }

        if (! $order->isCompletedAndPaid()) {
            return;
        }

        DB::transaction(function () use ($order) {
            $sellerOrders = SellerOrder::query()
                ->where('order_id', $order->id)
                ->where('status_code', '!=', SellerOrderStatusCode::CANCELLED->value)
                ->lockForUpdate()
                ->get();

            foreach ($sellerOrders as $sellerOrder) {
                if ($this->approvedSaleTransactionsCount($sellerOrder->id) > $this->approvedReversalTransactionsCount($sellerOrder->id)) {
                    continue;
                }

                $seller = Seller::query()->lockForUpdate()->find($sellerOrder->seller_id);
                if (! $seller) {
                    continue;
                }

                $grossAmount = (int) ($sellerOrder->amount ?? 0);
                if ($grossAmount <= 0) {
                    continue;
                }

                $ruleDate = Carbon::parse($sellerOrder->created_at ?? $order->created_at ?? now());
                $commission = $this->commissionService->resolve($seller, $grossAmount, $ruleDate);
                $commissionPercent = (int) $commission['effective_percent'];
                $commissionPrice = (int) $commission['commission_price'];
                $netAmount = max(0, $grossAmount - $commissionPrice);

                SellerTransaction::create([
                    'seller_id' => (int) $seller->id,
                    'order_id' => (int) $order->id,
                    'seller_order_id' => (int) $sellerOrder->id,
                    'type' => 'income',
                    'category' => self::CATEGORY_ORDER_SALE,
                    'card' => null,
                    'amount' => $grossAmount,
                    'commissionPercent' => $commissionPercent,
                    'baseCommissionPercent' => (int) $commission['base_percent'],
                    'commissionPrice' => $commissionPrice,
                    'baseCommissionPrice' => (int) $commission['base_price'],
                    'commissionBenefitAmount' => (int) $commission['benefit_amount'],
                    'commissionPromotionId' => $commission['promotion']?->id,
                    'commissionSource' => (string) $commission['source'],
                    'commissionRuleDate' => $ruleDate,
                    'netAmount' => $netAmount,
                    'status' => SellerTransaction::STATUS_APPROVED,
                    'description' => "Buyurtma #{$order->id} muvaffaqiyatli yakunlandi.",
                ]);

                $seller->balance = (int) $seller->balance + $netAmount;
                $seller->successful_orders = max(0, (int) $seller->successful_orders) + 1;
                $seller->save();

                Log::info('Seller order settled', [
                    'order_id' => $order->id,
                    'seller_order_id' => $sellerOrder->id,
                    'seller_id' => $seller->id,
                    'gross' => $grossAmount,
                    'commission_percent' => $commissionPercent,
                    'commission_price' => $commissionPrice,
                    'base_commission_price' => (int) $commission['base_price'],
                    'commission_benefit' => (int) $commission['benefit_amount'],
                    'commission_source' => (string) $commission['source'],
                    'net' => $netAmount,
                ]);
            }
        });
    }

    public function reverseCompletedOrderSettlement(Sold $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $saleTransactions = SellerTransaction::query()
                ->where('order_id', $order->id)
                ->where('category', self::CATEGORY_ORDER_SALE)
                ->where('status', SellerTransaction::STATUS_APPROVED)
                ->lockForUpdate()
                ->get();

            foreach ($saleTransactions as $saleTransaction) {
                $sellerOrderId = (int) ($saleTransaction->seller_order_id ?? 0);
                if ($sellerOrderId <= 0) {
                    continue;
                }

                if ($this->approvedSaleTransactionsCount($sellerOrderId) <= $this->approvedReversalTransactionsCount($sellerOrderId)) {
                    continue;
                }

                $seller = Seller::query()->lockForUpdate()->find($saleTransaction->seller_id);
                if (! $seller) {
                    continue;
                }

                $netAmount = (int) ($saleTransaction->netAmount ?? 0);

                SellerTransaction::create([
                    'seller_id' => (int) $seller->id,
                    'order_id' => (int) $order->id,
                    'seller_order_id' => $sellerOrderId,
                    'type' => 'expense',
                    'category' => self::CATEGORY_ORDER_REVERSAL,
                    'card' => null,
                    'amount' => (int) $saleTransaction->amount,
                    'commissionPercent' => (int) $saleTransaction->commissionPercent,
                    'baseCommissionPercent' => $saleTransaction->baseCommissionPercent,
                    'commissionPrice' => (int) $saleTransaction->commissionPrice,
                    'baseCommissionPrice' => (int) ($saleTransaction->baseCommissionPrice ?? $saleTransaction->commissionPrice),
                    'commissionBenefitAmount' => (int) ($saleTransaction->commissionBenefitAmount ?? 0),
                    'commissionPromotionId' => $saleTransaction->commissionPromotionId,
                    'commissionSource' => $saleTransaction->commissionSource,
                    'commissionRuleDate' => $saleTransaction->commissionRuleDate,
                    'netAmount' => $netAmount,
                    'status' => SellerTransaction::STATUS_APPROVED,
                    'description' => $reason
                        ? "Buyurtma #{$order->id} qayta ochildi ({$reason})."
                        : "Buyurtma #{$order->id} uchun settlement qaytarildi.",
                ]);

                $seller->balance = (int) $seller->balance - $netAmount;
                $seller->successful_orders = max(0, (int) $seller->successful_orders - 1);
                $seller->save();

                Log::warning('Seller order settlement reversed', [
                    'order_id' => $order->id,
                    'seller_order_id' => $saleTransaction->seller_order_id,
                    'seller_id' => $seller->id,
                    'net_reversed' => $netAmount,
                    'reason' => $reason,
                ]);
            }
        });
    }

    public function resolveCommissionPercent(Seller $seller, int $grossAmount): int
    {
        return (int) $this->commissionService->resolve($seller, $grossAmount)['effective_percent'];
    }

    private function approvedSaleTransactionsCount(int $sellerOrderId): int
    {
        return (int) SellerTransaction::query()
            ->where('seller_order_id', $sellerOrderId)
            ->where('category', self::CATEGORY_ORDER_SALE)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->count();
    }

    private function approvedReversalTransactionsCount(int $sellerOrderId): int
    {
        return (int) SellerTransaction::query()
            ->where('seller_order_id', $sellerOrderId)
            ->where('category', self::CATEGORY_ORDER_REVERSAL)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->count();
    }
}
