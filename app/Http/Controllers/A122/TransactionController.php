<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\SellerTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $q = SellerTransaction::with(['seller:id,shop_name,phone_number']);

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
            'all'      => SellerTransaction::count(),
            'pending'  => SellerTransaction::where('status', 'pending')->count(),
            'approved' => SellerTransaction::where('status', 'approved')->count(),
            'rejected' => SellerTransaction::where('status', 'rejected')->count(),
        ];

        return view('a122.transactions.index', compact('transactions', 'counts', 'tab'));
    }

    public function show(SellerTransaction $transaction)
    {
        $transaction->load('seller');
        $sellerTransactions = $transaction->seller_id
            ? SellerTransaction::query()
                ->where('seller_id', $transaction->seller_id)
                ->whereKeyNot($transaction->id)
                ->latest()
                ->take(6)
                ->get()
            : collect();
        $sellerTotals = $transaction->seller_id
            ? [
                'approved_count' => SellerTransaction::where('seller_id', $transaction->seller_id)->where('status', 'approved')->count(),
                'approved_sum' => (float) SellerTransaction::where('seller_id', $transaction->seller_id)->where('status', 'approved')->sum('amount'),
                'pending_sum' => (float) SellerTransaction::where('seller_id', $transaction->seller_id)->where('status', 'pending')->sum('amount'),
            ]
            : ['approved_count' => 0, 'approved_sum' => 0, 'pending_sum' => 0];

        return view('a122.transactions.show', compact('transaction', 'sellerTransactions', 'sellerTotals'));
    }

    public function approve(SellerTransaction $transaction)
    {
        $transaction->update(['status' => 'approved', 'approved_at' => now()]);
        return back()->with('success', "To'lov tasdiqlandi.");
    }

    public function reject(SellerTransaction $transaction)
    {
        $transaction->update(['status' => 'rejected']);
        return back()->with('success', "To'lov rad etildi.");
    }
}
