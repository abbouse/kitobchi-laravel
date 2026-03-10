<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sold;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now   = Carbon::now();
        $today = Carbon::today();

        // ============================================================
        // KPI KARTALAR
        // ============================================================
        $totalRevenue      = Sold::where('paymentStatus', 2)->sum('amount');
        $monthRevenue      = Sold::where('paymentStatus', 2)
                                 ->whereMonth('created_at', $now->month)
                                 ->whereYear('created_at', $now->year)
                                 ->sum('amount');
        $todayRevenue      = Sold::where('paymentStatus', 2)
                                 ->whereDate('created_at', $today)
                                 ->sum('amount');

        $totalOrders       = Sold::count();
        $completedOrders   = Sold::where('status', 'C')->count();
        $pendingOrders     = Sold::where('status', 'A')->count();
        $cancelledOrders   = Sold::where('status', 'F')->count();
        $todayOrders       = Sold::whereDate('created_at', $today)->count();

        $totalUsers        = User::where(function($q){ $q->where('isDeleted','no')->orWhereNull('isDeleted'); })->count();
        $premiumUsers      = User::where('is_premium', true)->where('premium_until', '>', $now)->count();
        $verifiedUsers     = User::where('isVerified', true)->count();
        $newUsersToday     = User::whereDate('created_at', $today)->count();
        $newUsersWeek      = User::where('created_at', '>=', $now->copy()->subDays(7))->count();
        $newUsersMonth     = User::where('created_at', '>=', $now->copy()->startOfMonth())->count();

        // ============================================================
        // ONLINE FOYDALANUVCHILAR (oxirgi 5 daqiqa)
        // ============================================================
        $onlineUsers       = User::where('last_seen_at', '>=', $now->copy()->subMinutes(5))->count();
        $onlineUsersList   = User::where('last_seen_at', '>=', $now->copy()->subMinutes(5))
                                  ->select('id','name','lastname','avatar','last_seen_at')
                                  ->limit(8)
                                  ->get();

        // ============================================================
        // ISOLAT (uzoq vaqt ko'rinmagan) FOYDALANUVCHILAR
        // ============================================================
        $isolatedUsers     = User::where('last_seen_at', '<=', $now->copy()->subDays(30))
                                  ->orWhereNull('last_seen_at')
                                  ->where(function($q){ $q->where('isDeleted','no')->orWhereNull('isDeleted'); })
                                  ->count();

        // FCM token bo'lganlar (faol) / bo'lmaganlar (nofaol)
        $activeUsers       = User::whereNotNull('fcm_token')->where('fcm_token','!=','')->count();
        $inactiveUsers     = $totalUsers - $activeUsers;

        // ============================================================
        // TOP SOTUVCHILAR (top books by sold count)
        // ============================================================
        $topSellingBooks = DB::table('solds')
            ->join('books', function($join){
                $join->on(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(solds.items, '$[0].product_id'))"), '=', 'books.id');
            })
            ->select('books.id','books.name','books.images','books.price','books.discountPrice',
                     DB::raw('COUNT(solds.id) as sold_count'),
                     DB::raw('SUM(solds.amount) as total_revenue'))
            ->where('solds.paymentStatus', 2)
            ->groupBy('books.id','books.name','books.images','books.price','books.discountPrice')
            ->orderByDesc('sold_count')
            ->limit(5)
            ->get();

        // ============================================================
        // TOP MIJOZLAR (eng ko'p xarid qilganlar)
        // ============================================================
        $topBuyers = Sold::select('user_id',
                            DB::raw('COUNT(*) as order_count'),
                            DB::raw('SUM(amount) as total_spent'))
                         ->where('paymentStatus', 2)
                         ->where('status', 'C')
                         ->groupBy('user_id')
                         ->orderByDesc('total_spent')
                         ->limit(5)
                         ->with('user:id,name,lastname,avatar,phone_number')
                         ->get();

        // ============================================================
        // SO'NGGI BUYURTMALAR
        // ============================================================
        $recentOrders = Sold::with('user:id,name,lastname,avatar')
                            ->latest()
                            ->limit(8)
                            ->get()
                            ->map(function($order) {
                                $statusMap = [
                                    'A' => ['label' => 'Kutilmoqda',     'color' => 'warning'],
                                    'P' => ['label' => 'Qadoqlanmoqda', 'color' => 'info'],
                                    'B' => ['label' => "Yo'lda",        'color' => 'primary'],
                                    'C' => ['label' => 'Yetkazildi',    'color' => 'success'],
                                    'F' => ['label' => 'Bekor qilindi', 'color' => 'error'],
                                ];
                                $s = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'gray'];
                                return [
                                    'id'         => $order->id,
                                    'customer'   => $order->user ? $order->user->full_name : 'Noma\'lum',
                                    'avatar'     => $order->user?->avatar,
                                    'amount'     => number_format($order->amount) . ' UZS',
                                    'status'     => $s['label'],
                                    'color'      => $s['color'],
                                    'date'       => $order->created_at->format('d.m H:i'),
                                    'items_count'=> is_array($order->items) ? count($order->items) : 0,
                                    'gift'       => $order->isGift ?? false,
                                ];
                            });

        // ============================================================
        // OYLIK DAROMAD GRAFIGI (oxirgi 6 oy)
        // ============================================================
        $monthlyRevenue = collect(range(5, 0))->map(function($i) use ($now) {
            $month = $now->copy()->subMonths($i);
            $total = Sold::where('paymentStatus', 2)
                         ->whereMonth('created_at', $month->month)
                         ->whereYear('created_at', $month->year)
                         ->sum('amount');
            return [
                'month' => $month->format('M'),
                'total' => (int)$total,
            ];
        });

        // ============================================================
        // BUYURTMALAR STATUS TAQSIMOTI (donut chart)
        // ============================================================
        $orderStatusDist = [
            ['label' => 'Yetkazildi',    'value' => $completedOrders,                          'color' => '#10B981'],
            ['label' => "Yo'lda",        'value' => Sold::where('status','B')->count(),         'color' => '#3B82F6'],
            ['label' => 'Qadoqlanmoqda', 'value' => Sold::where('status','P')->count(),         'color' => '#8B5CF6'],
            ['label' => 'Kutilmoqda',    'value' => $pendingOrders,                             'color' => '#F59E0B'],
            ['label' => 'Bekor qilindi', 'value' => $cancelledOrders,                           'color' => '#EF4444'],
        ];

        // ============================================================
        // TO'LOV USULLARI (donut chart)
        // ============================================================
        $paymentDist = [
            ['label' => 'Karta',           'value' => Sold::where('paymentStatus', 1)->count(), 'color' => '#465FFF'],
            ['label' => "To'langan",       'value' => Sold::where('paymentStatus', 2)->count(), 'color' => '#10B981'],
            ['label' => 'Qabul qilingan',  'value' => Sold::where('paymentStatus', 0)->count(), 'color' => '#F59E0B'],
            ['label' => 'Rad etildi',      'value' => Sold::where('paymentStatus', 3)->count(), 'color' => '#EF4444'],
        ];

        // ============================================================
        // KUNLIK BUYURTMALAR (oxirgi 7 kun, sparkline)
        // ============================================================
        $dailyOrders = collect(range(6, 0))->map(function($i) use ($now) {
            $day   = $now->copy()->subDays($i);
            $count = Sold::whereDate('created_at', $day->toDateString())->count();
            return ['day' => $day->format('d M'), 'count' => $count];
        });

        return view('pages.dashboard.ecommerce', compact(
            'totalRevenue', 'monthRevenue', 'todayRevenue',
            'totalOrders', 'completedOrders', 'pendingOrders', 'cancelledOrders', 'todayOrders',
            'totalUsers', 'premiumUsers', 'verifiedUsers', 'newUsersToday', 'newUsersWeek', 'newUsersMonth',
            'onlineUsers', 'onlineUsersList', 'isolatedUsers',
            'activeUsers', 'inactiveUsers',
            'topSellingBooks', 'topBuyers',
            'recentOrders', 'monthlyRevenue',
            'orderStatusDist', 'paymentDist', 'dailyOrders'
        ));
    }
}