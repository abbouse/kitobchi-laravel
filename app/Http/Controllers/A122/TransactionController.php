<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\SellerTransaction;
use App\Models\Seller;
use App\Services\SellerOrderSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private function isReviewableTransaction(SellerTransaction $transaction): bool
    {
        return $transaction->category === null
            || $transaction->category === SellerOrderSettlementService::CATEGORY_WITHDRAWAL;
    }

    private function payoutQuery()
    {
        return SellerTransaction::query()
            ->where(function ($query) {
                $query->whereNull('category')
                    ->orWhere('category', SellerOrderSettlementService::CATEGORY_WITHDRAWAL);
            });
    }

    public function index(Request $request)
    {
        $q = $this->payoutQuery()->with(['seller:id,shop_name,phone_number']);

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') $q->where('status', $tab);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('seller_id', $s)
                ->orWhere('amount', $s)
                ->orWhereHas('seller', fn ($sellerQuery) => $sellerQuery
                    ->where('shop_name', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%"))
            );
        }

        if ($request->date_from) $q->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $q->whereDate('created_at', '<=', $request->date_to);

        $transactions = $q->latest()->paginate(25)->withQueryString();

        $counts = [
            'all'      => (clone $this->payoutQuery())->count(),
            'pending'  => (clone $this->payoutQuery())->where('status', 'pending')->count(),
            'approved' => (clone $this->payoutQuery())->where('status', 'approved')->count(),
            'rejected' => (clone $this->payoutQuery())->where('status', 'rejected')->count(),
        ];

        return view('a122.transactions.index', compact('transactions', 'counts', 'tab'));
    }

    public function show(SellerTransaction $transaction)
    {
        $transaction->load('seller');
        $baseSellerQuery = $this->payoutQuery()->where('seller_id', $transaction->seller_id);
        $sellerTransactions = $transaction->seller_id
            ? (clone $baseSellerQuery)
                ->whereKeyNot($transaction->id)
                ->latest()
                ->take(6)
                ->get()
            : collect();
        $sellerTotals = $transaction->seller_id
            ? [
                'approved_count' => (clone $baseSellerQuery)->where('status', 'approved')->count(),
                'approved_sum' => (float) (clone $baseSellerQuery)->where('status', 'approved')->sum('netAmount'),
                'pending_sum' => (float) (clone $baseSellerQuery)->where('status', 'pending')->sum('netAmount'),
            ]
            : ['approved_count' => 0, 'approved_sum' => 0, 'pending_sum' => 0];

        return view('a122.transactions.show', compact('transaction', 'sellerTransactions', 'sellerTotals'));
    }

    public function approve(SellerTransaction $transaction)
    {
        if (!$this->isReviewableTransaction($transaction)) {
            return back()->with('error', "Bu tranzaksiya qo'lda tasdiqlanmaydi.");
        }

        if ($transaction->status !== 'pending') {
            return back()->with('error', "Faqat kutilayotgan tranzaksiyani tasdiqlash mumkin.");
        }

        DB::transaction(function () use ($transaction) {
            $lockedTransaction = SellerTransaction::query()->lockForUpdate()->find($transaction->id);
            if (!$lockedTransaction || $lockedTransaction->status !== 'pending') {
                return;
            }

            $seller = Seller::query()->lockForUpdate()->find($lockedTransaction->seller_id);
            $lockedTransaction->update(['status' => 'approved']);

            if ($seller) {
                $seller->total_withdrawal = (int) $seller->total_withdrawal + (int) ($lockedTransaction->netAmount ?? 0);
                $seller->save();
            }
        });

        return back()->with('success', "To'lov tasdiqlandi.");
    }

    public function reject(SellerTransaction $transaction)
    {
        if (!$this->isReviewableTransaction($transaction)) {
            return back()->with('error', "Bu tranzaksiya qo'lda rad etilmaydi.");
        }

        if ($transaction->status !== 'pending') {
            return back()->with('error', "Faqat kutilayotgan tranzaksiyani rad etish mumkin.");
        }

        DB::transaction(function () use ($transaction) {
            $lockedTransaction = SellerTransaction::query()->lockForUpdate()->find($transaction->id);
            if (!$lockedTransaction || $lockedTransaction->status !== 'pending') {
                return;
            }

            $seller = Seller::query()->lockForUpdate()->find($lockedTransaction->seller_id);
            $lockedTransaction->update(['status' => 'rejected']);

            if ($seller) {
                $seller->balance = (int) $seller->balance + (int) $lockedTransaction->amount;
                $seller->save();
            }
        });

        return back()->with('success', "To'lov rad etildi.");
    }
}
