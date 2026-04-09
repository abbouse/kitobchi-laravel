<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CourierOrder;
use App\Models\Couriers;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    private const STATUSES = [
        'pay_process' => ['label' => "To'lov jarayonida", 'class' => 'ob-p'],
        'pending'     => ['label' => 'Kutilmoqda',        'class' => 'ob-a'],
        'in_delivery' => ['label' => "Yo'lda",            'class' => 'ob-b'],
        'delivered'   => ['label' => 'Yetkazildi',        'class' => 'ob-c'],
        'rejected'    => ['label' => 'Rad etildi',        'class' => 'ob-f'],
    ];

    public function index(Request $request)
    {
        $q = CourierOrder::with(['courier', 'user', 'order']);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('order_id', $s)
                ->orWhere('courier_id', $s)
                ->orWhere('user_id', $s)
            );
        }

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') {
            $q->where('status', $tab);
        }

        if ($request->filled('courier_id')) {
            $q->where('courier_id', $request->courier_id);
        }
        if ($request->date_from) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $orders   = $q->latest()->paginate(25)->withQueryString();
        $couriers = Couriers::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        $counts = collect(array_keys(self::STATUSES))->mapWithKeys(fn($s) => [
            $s => CourierOrder::where('status', $s)->count(),
        ])->all();
        $counts['all'] = CourierOrder::count();

        $statuses = self::STATUSES;

        return view('panel.courier-orders.index', compact('orders', 'counts', 'tab', 'statuses', 'couriers'));
    }

    public function show(CourierOrder $courierOrder)
    {
        $courierOrder->load(['courier', 'user', 'order', 'items']);
        $statuses = self::STATUSES;
        return view('panel.courier-orders.show', compact('courierOrder', 'statuses'));
    }

    public function updateStatus(Request $request, CourierOrder $courierOrder)
    {
        $request->validate([
            'status' => 'required|in:pay_process,pending,in_delivery,delivered,rejected',
        ]);
        $courierOrder->update(['status' => $request->status]);
        return back()->with('success', 'Status yangilandi.');
    }

    public function assignCourier(Request $request, CourierOrder $courierOrder)
    {
        $request->validate(['courier_id' => 'required|exists:couriers,id']);
        $courierOrder->update(['courier_id' => $request->courier_id]);
        return back()->with('success', 'Kuryer tayinlandi.');
    }
}