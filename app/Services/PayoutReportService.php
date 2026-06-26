<?php

namespace App\Services;

use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\CourierTransaction;
use App\Models\SellerOrderItem;
use App\Models\SellerTransaction;
use Illuminate\Support\Collection;

class PayoutReportService
{
    public function seller(SellerTransaction $transaction): array
    {
        $transaction->loadMissing('seller');
        $sourceTransactions = $this->sellerSourceTransactions($transaction);
        $sellerOrderIds = $sourceTransactions->pluck('seller_order_id')->filter()->unique()->values();
        $itemCounts = $this->sellerProductCounts($sellerOrderIds);
        $rows = $sourceTransactions->map(function (SellerTransaction $row) use ($itemCounts) {
            $productCount = (int) ($itemCounts[(int) $row->seller_order_id] ?? 0);

            return [
                'transaction_id' => (int) $row->id,
                'order_id' => (int) ($row->order_id ?? 0),
                'sub_order_id' => (int) ($row->seller_order_id ?? 0),
                'gross' => (int) ($row->amount ?? 0),
                'commission_percent' => (float) ($row->commissionPercent ?? 0),
                'commission' => (int) ($row->commissionPrice ?? 0),
                'net' => (int) ($row->netAmount ?? $row->amount ?? 0),
                'product_count' => $productCount,
                'date' => optional($row->created_at)?->format('Y-m-d H:i'),
                'description' => (string) ($row->description ?? ''),
            ];
        })->values();

        return $this->basePayload(
            owner: 'seller',
            transaction: $transaction,
            ownerName: (string) ($transaction->seller?->shop_name ?: 'Seller'),
            ownerPhone: (string) ($transaction->seller?->phone_number ?: ''),
            method: (string) ($transaction->card ?: $transaction->seller?->masked_card ?: $transaction->seller?->masked_bank_account ?: ''),
            rows: $rows,
            periodFrom: optional($this->previousSellerWithdrawal($transaction)?->created_at)?->format('Y-m-d H:i'),
            periodTo: optional($transaction->created_at)?->format('Y-m-d H:i'),
        );
    }

    public function courier(CourierTransaction $transaction): array
    {
        $transaction->loadMissing('courier');
        $sourceTransactions = $this->courierSourceTransactions($transaction);
        $orderIds = $sourceTransactions->pluck('order_id')->filter()->unique()->values();
        $itemCounts = $this->courierProductCounts($orderIds);
        $courierOrders = CourierOrder::query()
            ->whereIn('id', $sourceTransactions->pluck('courier_order_id')->filter()->unique()->values())
            ->get(['id', 'courierPrice', 'courierBonus', 'settled_amount'])
            ->keyBy('id');

        $rows = $sourceTransactions->map(function (CourierTransaction $row) use ($itemCounts, $courierOrders) {
            $courierOrder = $courierOrders->get((int) $row->courier_order_id);

            return [
                'transaction_id' => (int) $row->id,
                'order_id' => (int) ($row->order_id ?? 0),
                'sub_order_id' => (int) ($row->courier_order_id ?? 0),
                'gross' => (int) ($row->amount ?? 0),
                'commission_percent' => 0,
                'commission' => 0,
                'net' => (int) ($row->netAmount ?? $row->amount ?? 0),
                'base_payout' => (int) ($courierOrder?->courierPrice ?? 0),
                'bonus' => (int) ($courierOrder?->courierBonus ?? 0),
                'product_count' => (int) ($itemCounts[(int) $row->order_id] ?? 0),
                'date' => optional($row->created_at)?->format('Y-m-d H:i'),
                'description' => (string) ($row->description ?? ''),
            ];
        })->values();

        $courierName = trim(($transaction->courier?->first_name ?? '').' '.($transaction->courier?->last_name ?? '')) ?: 'Kuryer';

        return $this->basePayload(
            owner: 'courier',
            transaction: $transaction,
            ownerName: $courierName,
            ownerPhone: (string) ($transaction->courier?->phone_number ?: ''),
            method: (string) ($transaction->card ?: $transaction->courier?->masked_card ?: ''),
            rows: $rows,
            periodFrom: optional($this->previousCourierWithdrawal($transaction)?->created_at)?->format('Y-m-d H:i'),
            periodTo: optional($transaction->created_at)?->format('Y-m-d H:i'),
        );
    }

    private function basePayload(
        string $owner,
        object $transaction,
        string $ownerName,
        string $ownerPhone,
        string $method,
        Collection $rows,
        ?string $periodFrom,
        ?string $periodTo,
    ): array {
        $totals = [
            'orders' => (int) $rows->pluck('order_id')->filter()->unique()->count(),
            'products' => (int) $rows->sum('product_count'),
            'gross' => (int) $rows->sum('gross'),
            'commission' => (int) $rows->sum('commission'),
            'net' => (int) $rows->sum('net'),
            'base_payout' => (int) $rows->sum('base_payout'),
            'bonus' => (int) $rows->sum('bonus'),
        ];

        return [
            'owner' => $owner,
            'document_no' => ($owner === 'courier' ? 'KURYER-HISOB-' : 'SOTUVCHI-HISOB-').$transaction->id,
            'generated_at' => now()->format('Y-m-d H:i'),
            'period' => [
                'from' => $periodFrom ?: 'Boshlanishidan',
                'to' => $periodTo ?: optional($transaction->created_at)?->format('Y-m-d H:i'),
            ],
            'transaction' => [
                'id' => (int) $transaction->id,
                'status' => (string) ($transaction->status ?? 'pending'),
                'amount' => (int) ($transaction->amount ?? 0),
                'net' => (int) ($transaction->netAmount ?? $transaction->amount ?? 0),
                'method' => $method ?: 'Ko‘rsatilmagan',
                'created_at' => optional($transaction->created_at)?->format('Y-m-d H:i'),
                'note' => (string) ($transaction->description ?: $transaction->rejected_desc ?: ''),
            ],
            'recipient' => [
                'name' => $ownerName,
                'phone' => $ownerPhone,
            ],
            'totals' => $totals,
            'rows' => $rows->all(),
        ];
    }

    private function sellerSourceTransactions(SellerTransaction $transaction): Collection
    {
        if (($transaction->category ?: 'withdrawal') !== SellerOrderSettlementService::CATEGORY_WITHDRAWAL) {
            return collect([$transaction]);
        }

        $previous = $this->previousSellerWithdrawal($transaction);

        return SellerTransaction::query()
            ->where('seller_id', $transaction->seller_id)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->whereIn('category', [
                SellerOrderSettlementService::CATEGORY_ORDER_SALE,
                SellerOrderSettlementService::CATEGORY_LEGACY_INFLIGHT_ADJUSTMENT,
            ])
            ->when($previous, fn ($query) => $query->where('created_at', '>', $previous->created_at))
            ->where('created_at', '<=', $transaction->created_at)
            ->orderBy('created_at')
            ->get();
    }

    private function courierSourceTransactions(CourierTransaction $transaction): Collection
    {
        if (($transaction->category ?: 'withdrawal') !== 'withdrawal') {
            return collect([$transaction]);
        }

        $previous = $this->previousCourierWithdrawal($transaction);

        return CourierTransaction::query()
            ->where('courier_id', $transaction->courier_id)
            ->where('status', 'approved')
            ->whereIn('category', ['order_delivery'])
            ->when($previous, fn ($query) => $query->where('created_at', '>', $previous->created_at))
            ->where('created_at', '<=', $transaction->created_at)
            ->orderBy('created_at')
            ->get();
    }

    private function previousSellerWithdrawal(SellerTransaction $transaction): ?SellerTransaction
    {
        return SellerTransaction::query()
            ->where('seller_id', $transaction->seller_id)
            ->where('id', '<', $transaction->id)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->where(fn ($query) => $query->whereNull('category')
                ->orWhere('category', SellerOrderSettlementService::CATEGORY_WITHDRAWAL)
                ->orWhere('category', 'seller_withdrawal'))
            ->latest('id')
            ->first();
    }

    private function previousCourierWithdrawal(CourierTransaction $transaction): ?CourierTransaction
    {
        return CourierTransaction::query()
            ->where('courier_id', $transaction->courier_id)
            ->where('id', '<', $transaction->id)
            ->where('status', 'approved')
            ->where(fn ($query) => $query->whereNull('category')->orWhere('category', 'withdrawal'))
            ->latest('id')
            ->first();
    }

    private function sellerProductCounts(Collection $sellerOrderIds): Collection
    {
        if ($sellerOrderIds->isEmpty()) {
            return collect();
        }

        return SellerOrderItem::query()
            ->whereIn('order_id', $sellerOrderIds)
            ->whereNull('cancelled_at')
            ->selectRaw('order_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('order_id')
            ->pluck('qty', 'order_id')
            ->map(fn ($value) => (int) $value);
    }

    private function courierProductCounts(Collection $orderIds): Collection
    {
        if ($orderIds->isEmpty()) {
            return collect();
        }

        return CourierOrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('order_id')
            ->pluck('qty', 'order_id')
            ->map(fn ($value) => (int) $value);
    }
}
