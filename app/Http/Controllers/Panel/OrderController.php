<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    // ── INDEX ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Sold::with('user:id,name,lastname,phone_number,avatar');

        $tab = $request->input('tab', 'A');
        if ($tab !== 'all') $query->where('status', $tab);

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('id', $s)
                ->orWhereHas('user', fn($u) => $u
                    ->where('name',         'like', "%$s%")
                    ->orWhere('phone_number','like', "%$s%")
                )
            );
        }
        if ($f = $request->input('gift_filter')) {
    if ($f === 'gift')      $query->whereNotNull('gift');
    if ($f === 'other')     $query->where('is_gift_to_other', true);
    if ($f === 'packaging') $query->where('with_packaging', true);
}

        if ($request->filled('payment_status')) {
            $query->where('paymentStatus', $request->input('payment_status'));
        }
        if ($request->filled('delivery_type')) {
            $query->where('deliveryType', $request->input('delivery_type'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'A'   => Sold::where('status','A')->count(),
            'P'   => Sold::where('status','P')->count(),
            'B'   => Sold::where('status','B')->count(),
            'C'   => Sold::where('status','C')->count(),
            'F'   => Sold::where('status','F')->count(),
            'all' => Sold::count(),
        ];

        $stats = [
            'today_count'   => Sold::whereDate('created_at', today())->count(),
            'today_revenue' => (float) Sold::whereDate('created_at', today())->where('paymentStatus', 2)->sum('amount'),
            'total_revenue' => (float) Sold::where('paymentStatus', 2)->sum('amount'),
        ];

        return view('panel.orders.index', compact('orders', 'counts', 'tab', 'stats'));
    }

    // ── SHOW ───────────────────────────────────────────────────
    public function show(Sold $order)
    {
        $order->load('user');

        $items = collect($order->items ?? [])->map(function ($item) {
            $type    = $item['type'] ?? 'book';
            $product = match ($type) {
                'stationery' => Stationery::with('seller:id,shop_name')->find($item['item_id'] ?? 0),
                'gift'       => \App\Models\Gifts::with('seller:id,shop_name')->find($item['item_id'] ?? 0),
                default      => Books::with('seller:id,shop_name')->find($item['item_id'] ?? 0),
            };
            $item['product'] = $product;
            return $item;
        });

        // Gift ob'ekti (order.gift kolonnasidan)
        $orderGift = $order->gift
            ? \App\Models\Gifts::with('seller:id,shop_name')->find($order->gift)
            : null;

        // Gift sertifikat ob'ekti
        $orderCert = $order->gift_certificate_id
            ? \App\Models\GiftCertificate::find($order->gift_certificate_id)
            : null;

        return view('panel.orders.show', compact('order', 'items', 'orderGift', 'orderCert'));
    }

    // ── STATUS O'ZGARTIRISH ────────────────────────────────────
    public function updateStatus(Request $request, Sold $order)
    {
        $request->validate(['status' => 'required|in:A,P,B,C,F']);
        $newStatus = $request->input('status');

        // C (yetkazildi) → paymentStatus ni ham 2 ga o'tkazamiz
        if ($newStatus === 'C' && $order->paymentStatus != 2) {
            $order->paymentStatus = 2;
        }

        $order->status = $newStatus;
        $order->save();

        return back()->with('success', 'Buyurtma holati yangilandi.');
    }

    // ── ADMIN TOMONIDAN BEKOR QILISH ───────────────────────────
    public function adminCancel(Request $request, Sold $order)
    {
        if (in_array($order->status, ['C', 'F'])) {
            return back()->with('error', 'Yetkazilgan yoki allaqachon bekor qilingan buyurtmani bekor qilib bo\'lmaydi.');
        }

        $result = $this->orderService->cancelOrder($order, strict: false);

        return back()->with($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Buyurtma bekor qilindi va stoklar qaytarildi.' : $result['message']
        );
    }

    // ── EXPORT ─────────────────────────────────────────────────
    public function export(Request $request)
    {
        return Excel::download(
            new OrdersExport($request->all()),
            'orders_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}