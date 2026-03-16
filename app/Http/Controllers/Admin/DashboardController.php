<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Modellar
use App\Models\User;
use App\Models\Sold;
use App\Models\Books;
use App\Models\BookCategories;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\MarketNews;
use App\Models\Promocode;
use App\Models\Seller;
use App\Models\SellerTransaction;

class DashboardController extends Controller
{
    private const CACHE_DURATION = 300; // 5 daqiqa

    public function index(Request $request)
    {
        // Keshni tozalash buyrug'i kelsa
        if ($request->has('clear_cache')) {
            $this->clearCache();
            return redirect()->route('dashboard')->with('cache_cleared', true);
        }

        return view('pages.dashboard.ecommerce', $this->getData());
    }

    private function clearCache(): void
    {
        $keys = [
            'dash_sold_count','dash_status_a','dash_status_b','dash_status_c',
            'dash_books_count','dash_categories_count','dash_top_book','dash_in_stock',
            'dash_user_count','dash_new_users','dash_active_users','dash_inactive_users',
            'dash_couriers','dash_delivery','dash_promo_count','dash_top_promo',
            'dash_gifts','dash_news','dash_total_revenue','dash_books_revenue',
            'dash_category_dist','dash_top_cats','dash_trend_7','dash_monthly_rev',
            'dash_dau','dash_mau','dash_online','dash_top_sellers',
            'dash_trending_books','dash_online_users_list',
            'dash_expense_total','dash_expense_commission','dash_expense_pending',
            'dash_expense_paid','dash_expense_rejected','dash_promo_analytics',
        ];
        foreach ($keys as $key) Cache::forget($key);
    }

    private function getData(): array
    {
        $c = self::CACHE_DURATION;
        $now = Carbon::now();

        /* ── Order metrics ── */
        $soldCount     = Cache::remember('dash_sold_count', $c, fn() => Sold::count());
        $soldStatusA   = Cache::remember('dash_status_a',   $c, fn() => Sold::where('status','A')->count());
        $soldStatusB   = Cache::remember('dash_status_b',   $c, fn() => Sold::where('status','B')->count());
        $soldStatusC   = Cache::remember('dash_status_c',   $c, fn() => Sold::where('status','C')->count());

        /* ── Revenue ── */
        $totalRevenue  = Cache::remember('dash_total_revenue', $c, fn() => floatval(Sold::where('paymentStatus', 2)->sum('amount')));
        
        /* ── Users ── */
        $userCount     = Cache::remember('dash_user_count', $c, fn() => User::count());
        $onlineCount   = Cache::remember('dash_online',     $c, fn() => User::where('last_seen_at', '>=', now()->subMinutes(5))->count());

        /* ── Charts & Trends ── */
        $monthlyRevenue = Cache::remember('dash_monthly_rev', $c, function () {
            $result = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $total = Sold::where('paymentStatus', 2)
                             ->whereMonth('created_at', $month->month)
                             ->whereYear('created_at', $month->year)
                             ->sum('amount');
                $result[] = [
                    'month' => $month->format('M'),
                    'total' => (int)$total,
                ];
            }
            return $result;
        });

        /* ── Status Distributions (Donut Charts) ── */
        $orderStatusDist = [
            ['label' => 'Yetkazildi',    'value' => $soldStatusC, 'color' => '#10B981'],
            ['label' => "Yo'lda",        'value' => $soldStatusB, 'color' => '#3B82F6'],
            ['label' => 'Kutilmoqda',    'value' => $soldStatusA, 'color' => '#F59E0B'],
            ['label' => 'Bekor qilindi', 'value' => Sold::where('status','F')->count(), 'color' => '#EF4444'],
        ];

        /* ── Top Sellers ── */
        $topSellers = Cache::remember('dash_top_sellers', $c, fn() =>
            Seller::orderByDesc('successful_orders')
                ->whereNotNull('shop_name')
                ->limit(5)
                ->get()
                ->map(fn($s, $i) => [
                    'rank'      => $i + 1,
                    'name'      => $s->firstname . ' ' . $s->lastname,
                    'shop_name' => $s->shop_name,
                    'orders'    => $s->successful_orders,
                    'balance'   => number_format($s->balance) . " so'm",
                ])->toArray()
        );

        // Qo'shimcha barcha kerakli ma'lumotlarni compact qilib qaytaramiz
        return array_merge(
            compact('soldCount', 'soldStatusA', 'soldStatusB', 'soldStatusC', 'totalRevenue', 'userCount', 'onlineCount', 'monthlyRevenue', 'orderStatusDist', 'topSellers'),
            [
                'recentOrders' => $this->getRecentOrders(),
            ]
        );
    }

    private function getRecentOrders()
    {
        return Sold::with('user')->latest()->take(10)->get()->map(function ($order) {
            return [
                'id'       => $order->id,
                'customer' => $order->user?->name ?? 'Mehmon',
                'amount'   => number_format($order->amount) . " so'm",
                'status'   => $order->status,
                'date'     => $order->created_at->format('d.m.Y H:i'),
            ];
        });
    }
}