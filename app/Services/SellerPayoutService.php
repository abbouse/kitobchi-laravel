<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\SellerTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Do'kon balansidan yechish qoidalari.
 *
 * Buyurtma yakunlanganda (SellerOrderSettlementService) uning puli darhol
 * balansga tushadi, lekin `hold_days` (standart 14) kun "ushlab turiladi":
 * shu muddatda mijoz qaytarishi mumkin, qaytsa pul balansdan ayriladi.
 * Muddat o'tgach summa "yechish mumkin" bo'ladi.
 *
 *   joriy balans   = sellers.balance
 *   ushlab turilgan = oxirgi `hold_days` kunda yakunlangan (qaytarilmagan)
 *                     buyurtmalarning sof summasi
 *   yechish mumkin = joriy balans − ushlab turilgan (0 dan kam emas)
 *
 * Balansdan qilingan xarajatlar (premium, katalog joyi) avval yechish mumkin
 * bo'lgan qismdan ayriladi — ushlab turilgan summa himoyada qoladi.
 */
class SellerPayoutService
{
    public function holdDays(): int
    {
        return max(0, (int) config('services.seller_payouts.hold_days', 14));
    }

    public function minWithdrawal(): int
    {
        return max(0, (int) config('services.seller_payouts.min_withdrawal', 100000));
    }

    /**
     * @return array{
     *   balance:int, held:int, withdrawable:int, pending_withdrawal:int,
     *   total_withdrawn:int, hold_days:int, min_withdrawal:int,
     *   next_release: array{date:string, amount:int}|null,
     *   upcoming: list<array{date:string, amount:int, orders:int}>
     * }
     */
    public function summary(Seller $store): array
    {
        $balance = (int) ($store->balance ?? 0);
        $holds = $this->activeHolds((int) $store->id);
        $held = min(max(0, $balance), (int) $holds->sum('amount'));

        $upcoming = $holds
            ->groupBy(fn (array $hold) => $hold['available_at']->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'date' => $date,
                'amount' => (int) $rows->sum('amount'),
                'orders' => $rows->count(),
            ])
            ->sortKeys()
            ->values();

        return [
            'balance' => $balance,
            'held' => $held,
            'withdrawable' => max(0, $balance - $held),
            'pending_withdrawal' => (int) SellerTransaction::query()
                ->where('seller_id', $store->id)
                ->where('category', SellerOrderSettlementService::CATEGORY_WITHDRAWAL)
                ->where('status', SellerTransaction::STATUS_PENDING)
                ->sum('amount'),
            'total_withdrawn' => (int) ($store->total_withdrawal ?? 0),
            'hold_days' => $this->holdDays(),
            'min_withdrawal' => $this->minWithdrawal(),
            'next_release' => $upcoming->first() ? ['date' => $upcoming->first()['date'], 'amount' => $upcoming->first()['amount']] : null,
            'upcoming' => $upcoming->take(7)->all(),
        ];
    }

    /** Sotuv tranzaksiyasi qachon yechishga ochiladi (ushlab turilmasa — null). */
    public function availableAt(SellerTransaction $transaction): ?Carbon
    {
        if ($transaction->category !== SellerOrderSettlementService::CATEGORY_ORDER_SALE || ! $transaction->created_at) {
            return null;
        }

        return Carbon::parse($transaction->created_at)->addDays($this->holdDays());
    }

    /**
     * Hali ushlab turilgan sotuvlar: har do'kon buyurtmasi uchun oxirgi
     * sotuv yozuvi, agar u qaytarilmagan bo'lsa (sotuvlar soni > qaytarishlar).
     *
     * @return Collection<int, array{seller_order_id:int, amount:int, available_at:Carbon}>
     */
    private function activeHolds(int $sellerId): Collection
    {
        $days = $this->holdDays();
        if ($days <= 0) {
            return collect();
        }

        $recentSales = SellerTransaction::query()
            ->where('seller_id', $sellerId)
            ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->where('created_at', '>', now()->subDays($days))
            ->whereNotNull('seller_order_id')
            ->orderBy('id')
            ->get(['id', 'seller_order_id', 'netAmount', 'created_at'])
            ->keyBy('seller_order_id'); // bir buyurtma qayta yakunlansa — oxirgisi

        if ($recentSales->isEmpty()) {
            return collect();
        }

        $counts = SellerTransaction::query()
            ->whereIn('seller_order_id', $recentSales->keys()->all())
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->whereIn('category', [
                SellerOrderSettlementService::CATEGORY_ORDER_SALE,
                SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL,
            ])
            ->selectRaw('seller_order_id, category, COUNT(*) as c')
            ->groupBy('seller_order_id', 'category')
            ->get()
            ->groupBy('seller_order_id');

        return $recentSales
            ->filter(function (SellerTransaction $sale) use ($counts) {
                $rows = $counts->get($sale->seller_order_id, collect());
                $sales = (int) ($rows->firstWhere('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)->c ?? 0);
                $reversals = (int) ($rows->firstWhere('category', SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL)->c ?? 0);

                return $sales > $reversals;
            })
            ->map(fn (SellerTransaction $sale) => [
                'seller_order_id' => (int) $sale->seller_order_id,
                'amount' => max(0, (int) $sale->netAmount),
                'available_at' => Carbon::parse($sale->created_at)->addDays($days),
            ])
            ->values();
    }
}
