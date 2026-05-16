<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\CourierOrder;
use App\Services\AdminOrderStatusSyncService;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    public function __construct(private readonly AdminOrderStatusSyncService $statusSync) {}

    public function index(Request $request)
    {
        $q = CourierOrder::with([
            'courier:id,first_name,last_name,phone_number',
            'user:id,name,lastname,phone_number',
            'order:id,status,paymentStatus',
        ]);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('courier_id', $s)
                ->orWhereHas('courier', fn ($courierQuery) => $courierQuery
                    ->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%"))
                ->orWhereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%$s%")
                    ->orWhere('lastname', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%"))
            );
        }

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') {
            $q->where('status', $tab);
        }

        $orders = $q->latest()->paginate(25)->withQueryString();

        $counts = collect(array_keys(AdminOrderStatusSyncService::COURIER_STATUSES))->mapWithKeys(fn($status) => [
            $status => CourierOrder::where('status', $status)->count(),
        ])->all();
        $counts['all'] = CourierOrder::count();
        $statuses = AdminOrderStatusSyncService::COURIER_STATUSES;

        return view('a122.courier-orders.index', compact('orders', 'counts', 'tab', 'statuses'));
    }

    public function show(CourierOrder $courierOrder)
    {
        $courierOrder->load(['courier', 'user', 'items']);
        $statuses = AdminOrderStatusSyncService::COURIER_STATUSES;
        return view('a122.courier-orders.show', compact('courierOrder', 'statuses'));
    }

    public function updateStatus(Request $request, CourierOrder $courierOrder)
    {
        $request->validate([
            'status' => 'required|in:pay_process,pending,in_delivery,delivered,customer_received,rejected',
        ]);

        $this->statusSync->updateCourierOrder($courierOrder, (string) $request->status);

        return back()->with('success', 'Holat yangilandi.');
    }
}
