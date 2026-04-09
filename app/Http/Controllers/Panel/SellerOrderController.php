<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerOrderController extends Controller
{
    // Status map: 0=yangi, 1=qabul qilindi, 2=tayyorlanmoqda, 3=yetkazildi, 4=bekor
    private const STATUSES = [
        1 => ['label' => 'Yangi buyurtma',  'class' => 'ob-b'],
        2 => ['label' => 'Qabul qilindi', 'class' => 'ob-b'],
        3 => ['label' => 'Kuryerga berildi',     'class' => 'ob-c'],
        4 => ['label' => 'Bekor qilindi',  'class' => 'ob-f'],
    ];

    public function index(Request $request)
    {
        $q = SellerOrder::with(['seller', 'client', 'courier']);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('order_id', $s)
                ->orWhere('seller_id', $s)
                ->orWhere('client_id', $s)
            );
        }

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all' && is_numeric($tab)) {
            $q->where('status', $tab);
        }

        if ($request->filled('seller_id')) {
            $q->where('seller_id', $request->seller_id);
        }
        if ($request->date_from) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $q->latest()->paginate(25)->withQueryString();

        $counts = collect([0, 1, 2, 3, 4])->mapWithKeys(fn($s) => [
            $s => SellerOrder::where('status', $s)->count(),
        ])->all();
        $counts['all'] = SellerOrder::count();

        $statuses = self::STATUSES;

        return view('panel.seller-orders.index', compact('orders', 'counts', 'tab', 'statuses'));
    }

    public function show(SellerOrder $sellerOrder)
    {
        $sellerOrder->load(['seller', 'client', 'courier', 'items.product']);
        $statuses = self::STATUSES;
        return view('panel.seller-orders.show', compact('sellerOrder', 'statuses'));
    }

    public function updateStatus(Request $request, SellerOrder $sellerOrder)
    {
        $request->validate(['status' => 'required|in:0,1,2,3,4']);
        $sellerOrder->update(['status' => $request->status]);
        return back()->with('success', 'Status yangilandi.');
    }
}