<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SellerTransaction;
use App\Models\CourierTransaction;
use App\Models\Seller;
use App\Models\Couriers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerTransactionController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // INDEX — segment: seller | courier
    // ══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $segment = $request->get('segment', 'seller');
        $tab     = $request->get('tab', 'pending');
        $search  = $request->get('search');

        if ($segment === 'courier') {
            // ── Courier ────────────────────────────────────
            $q = CourierTransaction::with(
                'courier:id,first_name,last_name,phone_number,photo,balance'
            );

            if ($tab !== 'all') $q->where('status', $tab);

            if ($search) {
                $q->whereHas('courier', fn($sq) => $sq
                    ->where('first_name',    'like', "%$search%")
                    ->orWhere('last_name',   'like', "%$search%")
                    ->orWhere('phone_number','like', "%$search%")
                    ->orWhere('id', $search)
                );
            }

            if ($request->filled('courier_id')) {
                $q->where('courier_id', $request->courier_id);
            }

            $transactions = $q->latest()->paginate(25)->withQueryString();

            $counts = [
                'all'      => CourierTransaction::count(),
                'pending'  => CourierTransaction::where('status', 'pending')->count(),
                'approved' => CourierTransaction::where('status', 'approved')->count(),
                'rejected' => CourierTransaction::where('status', 'rejected')->count(),
            ];

            $stats = [
                'pending_amount'  => CourierTransaction::where('status','pending')->sum('amount'),
                'approved_amount' => CourierTransaction::where('status','approved')->sum('netAmount'),
            ];

            // Seller pending badge uchun
            $otherPending = SellerTransaction::where('status','pending')->count();

        } else {
            // ── Seller ─────────────────────────────────────
            $q = SellerTransaction::with(
                'seller:id,shop_name,phone_number,photo,balance'
            );

            if ($tab !== 'all') $q->where('status', $tab);

            if ($search) {
                $q->whereHas('seller', fn($sq) => $sq
                    ->where('shop_name',     'like', "%$search%")
                    ->orWhere('phone_number','like', "%$search%")
                    ->orWhere('id', $search)
                );
            }

            if ($request->filled('seller_id')) {
                $q->where('seller_id', $request->seller_id);
            }

            $transactions = $q->latest()->paginate(25)->withQueryString();

            $counts = [
                'all'      => SellerTransaction::count(),
                'pending'  => SellerTransaction::where('status', 'pending')->count(),
                'approved' => SellerTransaction::where('status', 'approved')->count(),
                'rejected' => SellerTransaction::where('status', 'rejected')->count(),
            ];

            $stats = [
                'pending_amount'  => SellerTransaction::where('status','pending')->sum('amount'),
                'approved_amount' => SellerTransaction::where('status','approved')->sum('netAmount'),
            ];

            // Courier pending badge uchun
            $otherPending = CourierTransaction::where('status','pending')->count();
        }

        return view('panel.seller-transactions.index',
            compact('transactions','counts','tab','stats','segment','otherPending'));
    }

    // ══════════════════════════════════════════════════════════
    // SHOW
    // ══════════════════════════════════════════════════════════
    public function show(Request $request, int $id)
    {
        $segment = $request->get('segment', 'seller');

        if ($segment === 'courier') {
            $tx = CourierTransaction::with('courier')->findOrFail($id);
            return view('panel.seller-transactions.show',
                compact('tx', 'segment'));
        }

        $tx = SellerTransaction::with('seller')->findOrFail($id);
        return view('panel.seller-transactions.show',
            compact('tx', 'segment'));
    }

    // ══════════════════════════════════════════════════════════
    // APPROVE
    // ══════════════════════════════════════════════════════════
    public function approve(Request $request, int $id)
    {
        $segment = $request->get('segment', 'seller');

        if ($segment === 'courier') {
            $tx = CourierTransaction::findOrFail($id);
            if ($tx->status !== 'pending') {
                return back()->with('error', 'Allaqachon ko\'rib chiqilgan.');
            }
            DB::transaction(function () use ($tx) {
                Couriers::where('id', $tx->courier_id)
                    ->decrement('balance', $tx->amount);
                $tx->update(['status' => 'approved']);
            });
            return back()->with('success',
                "Tasdiqlandi. Kuryerdan {$tx->amount} UZS yechildi.");
        }

        // Seller
        $tx = SellerTransaction::findOrFail($id);
        if ($tx->status !== 'pending') {
            return back()->with('error', 'Allaqachon ko\'rib chiqilgan.');
        }
        DB::transaction(function () use ($tx) {
            Seller::where('id', $tx->seller_id)
                ->decrement('balance', $tx->amount);
            $tx->update(['status' => 'approved']);
        });
        return back()->with('success',
            "Tasdiqlandi. Sellerdan {$tx->amount} UZS yechildi.");
    }

    // ══════════════════════════════════════════════════════════
    // REJECT
    // ══════════════════════════════════════════════════════════
    public function reject(Request $request, int $id)
    {
        $request->validate(['rejected_desc' => 'required|string|max:500']);

        $segment = $request->get('segment', 'seller');

        if ($segment === 'courier') {
            $tx = CourierTransaction::findOrFail($id);
            if ($tx->status === 'rejected') {
                return back()->with('error', 'Allaqachon rad etilgan.');
            }
            DB::transaction(function () use ($request, $tx) {
                $wasApproved = $tx->status === 'approved';
                $tx->update([
                    'status'        => 'rejected',
                    'rejected_desc' => $request->rejected_desc,
                ]);
                if ($wasApproved) {
                    Couriers::where('id', $tx->courier_id)
                        ->increment('balance', $tx->amount);
                }
            });
            return back()->with('success',
                $tx->getOriginal('status') === 'approved'
                    ? "Rad etildi. {$tx->amount} UZS kuryerga qaytarildi."
                    : 'Rad etildi.');
        }

        // Seller
        $tx = SellerTransaction::findOrFail($id);
        if ($tx->status === 'rejected') {
            return back()->with('error', 'Allaqachon rad etilgan.');
        }
        DB::transaction(function () use ($request, $tx) {
            $wasApproved = $tx->status === 'approved';
            $tx->update([
                'status'        => 'rejected',
                'rejected_desc' => $request->rejected_desc,
            ]);
            if ($wasApproved) {
                Seller::where('id', $tx->seller_id)
                    ->increment('balance', $tx->amount);
            }
        });
        return back()->with('success',
            $tx->getOriginal('status') === 'approved'
                ? "Rad etildi. {$tx->amount} UZS sellerga qaytarildi."
                : 'Rad etildi.');
    }
}