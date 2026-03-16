<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\MarketNews;
use App\Models\Promocode;
use App\Models\Seller;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\User;

class DashboardController extends Controller
{
    private const CACHE_DURATION = 300;

    public function index(Request $request)
    {
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
            // New keys
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

        /* ── Order metrics ── */
        $soldCount     = Cache::remember('dash_sold_count',       $c, fn() => Sold::count());
        $soldStatusA   = Cache::remember('dash_status_a',         $c, fn() => Sold::where('status','A')->count());
        $soldStatusB   = Cache::remember('dash_status_b',         $c, fn() => Sold::where('status','B')->count());
        $soldStatusC   = Cache::remember('dash_status_c',         $c, fn() => Sold::where('status','C')->count());

        /* ── Books ── */
        $booksCount    = Cache::remember('dash_books_count',       $c, fn() => Books::count());
        $catsCount     = Cache::remember('dash_categories_count',  $c, fn() => BookCategories::count());
        $topBook       = Cache::remember('dash_top_book',          $c, fn() => Books::orderBy('totalSales','desc')->first()?->name ?? 'N/A');
        $inStock       = Cache::remember('dash_in_stock',          $c, fn() => Books::where('count','>',0)->count());

        /* ── Users ── */
        $userCount     = Cache::remember('dash_user_count',        $c, fn() => User::count());
        $newUsers      = Cache::remember('dash_new_users',         $c, fn() => User::where('created_at','>=',now()->subDays(7))->count());
        $activeUsers   = Cache::remember('dash_active_users',      $c, fn() => User::whereNotNull('fcm_token')->count());
        $inactiveUsers = Cache::remember('dash_inactive_users',    $c, fn() => User::whereNull('fcm_token')->count());
        $dau           = Cache::remember('dash_dau',               $c, fn() => User::where('last_seen_at','>=',now()->subDay())->count());
        $mau           = Cache::remember('dash_mau',               $c, fn() => User::where('last_seen_at','>=',now()->subDays(30))->count());
        $onlineCount   = Cache::remember('dash_online',            $c, fn() => User::where('last_seen_at','>=',now()->subMinutes(5))->count());

        /* ── Couriers / Delivery ── */
        $couriersCount = Cache::remember('dash_couriers',          $c, fn() => Couriers::count());
        $deliveryCount = Cache::remember('dash_delivery',          $c, fn() => DeliveryService::count());

        /* ── Promos ── */
        $promosCount   = Cache::remember('dash_promo_count',       $c, fn() => Promocode::where('status',1)->count());
        $topPromo      = Cache::remember('dash_top_promo',         $c, fn() => Promocode::orderBy('usedCount','desc')->first()?->code ?? 'N/A');

        /* ── Misc ── */
        $giftsCount    = Cache::remember('dash_gifts',             $c, fn() => Sold::whereNotNull('gift')->count());
        $newsCount     = Cache::remember('dash_news',              $c, fn() => MarketNews::count());

        /* ── Revenue ── */
        $totalRevenue  = Cache::remember('dash_total_revenue',     $c, fn() => floatval(Sold::sum('amount')));
        $booksRevenue  = Cache::remember('dash_books_revenue',     $c, fn() => floatval(Books::sum('totalRevenue')));

        /* ── 7-day sales trend ── */
        $soldTrend7 = Cache::remember('dash_trend_7', $c, function () {
            $rows = Sold::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at','>=',now()->subDays(6))
                ->groupBy('date')->orderBy('date')->get();

            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $day   = now()->subDays($i)->format('Y-m-d');
                $found = $rows->firstWhere('date', $day);
                $result[] = [
                    'date'  => now()->subDays($i)->format('M d'),
                    'count' => $found ? (int)$found->count : 0,
                ];
            }
            return $result;
        });

        /* ── 6-month revenue trend ── */
        $monthlyRevenue = Cache::remember('dash_monthly_rev', $c, function () {
            $rows = Sold::selectRaw("strftime('%Y-%m', created_at) as month, SUM(amount) as total")
                ->where('created_at','>=',now()->subMonths(5)->startOfMonth())
                ->groupBy('month')->orderBy('month')->get();

            $result = [];
            for ($i = 5; $i >= 0; $i--) {
                $key   = now()->subMonths($i)->format('Y-m');
                $found = $rows->firstWhere('month', $key);
                $result[] = [
                    'month' => now()->subMonths($i)->format('M Y'),
                    'total' => $found ? round((float)$found->total) : 0,
                ];
            }
            return $result;
        });

        /* ── Category distribution (donut) ── */
        $categoryDist = Cache::remember('dash_category_dist', $c, fn() =>
            BookCategories::withCount('books')->get()
                ->filter(fn($cat) => $cat->books_count > 0)
                ->map(fn($cat) => ['label' => $cat->title, 'value' => $cat->books_count])
                ->values()->toArray()
        );

        /* ── Top 5 categories by sales — efficient JOIN ── */
        $topCatsSold = Cache::remember('dash_top_cats', $c, fn() =>
            Books::join('book_categories', 'books.category_id', '=', 'book_categories.id')
                ->selectRaw('book_categories.title as label, SUM(books.totalSales) as value')
                ->groupBy('book_categories.id', 'book_categories.title')
                ->orderByDesc('value')
                ->limit(5)
                ->get()
                ->toArray()
        );

        /* ── User status donut ── */
        $userStatus = [
            ['label' => 'Push yoqilgan', 'value' => $activeUsers],
            ['label' => 'Nofaol',        'value' => $inactiveUsers],
        ];

        /* ── Recent orders ── */
        $recentOrders = Sold::with('user')->latest()->take(10)->get()->map(function ($sold) {
            $map = [
                'A' => ['label' => 'Yetkazish kerak', 'color' => 'warning'],
                'B' => ['label' => 'Jarayonda',       'color' => 'info'],
                'C' => ['label' => 'Yakunlangan',     'color' => 'success'],
                'F' => ['label' => 'Bekor qilindi',   'color' => 'gray'],
            ];
            $st = $map[$sold->status] ?? ['label' => "Noma'lum", 'color' => 'gray'];
            return [
                'id'       => $sold->id,
                'customer' => $sold->user?->name ?? 'Mehmon',
                'amount'   => number_format((float)$sold->amount, 0, '.', ' ') . " so'm",
                'status'   => $st['label'],
                'color'    => $st['color'],
                'date'     => $sold->created_at->format('d.m.Y'),
                'gift'     => !is_null($sold->gift),
            ];
        })->toArray();

        /* ── NEW: Top 5 sellers ── */
        $topSellers = Cache::remember('dash_top_sellers', $c, fn() =>
            Seller::orderByDesc('successful_orders')
                ->whereNotNull('shop_name')
                ->limit(5)
                ->get(['id','firstname','lastname','shop_name','successful_orders','rating','balance'])
                ->map(fn($s, $i) => [
                    'rank'             => $i + 1,
                    'name'             => trim($s->firstname . ' ' . $s->lastname),
                    'shop_name'        => $s->shop_name,
                    'successful_orders'=> (int)$s->successful_orders,
                    'rating'           => round((float)$s->rating, 1),
                    'balance'          => number_format((float)$s->balance, 0, '.', ' ') . " so'm",
                ])
                ->toArray()
        );

        /* ── NEW: Top 10 trending books (by weekly sales) ── */
        $trendingBooks = Cache::remember('dash_trending_books', $c, fn() =>
            Books::orderByDesc('totalSalesWeek')
                ->where('totalSalesWeek', '>', 0)
                ->limit(10)
                ->get(['id','name','author','images','totalSalesWeek','totalSales'])
                ->map(fn($b, $i) => [
                    'rank'           => $i + 1,
                    'name'           => $b->name,
                    'author'         => $b->author,
                    'cover'          => is_array($b->images) ? ($b->images[0] ?? null) : null,
                    'weekly_sales'   => (int)$b->totalSalesWeek,
                    'total_sales'    => (int)$b->totalSales,
                ])
                ->toArray()
        );

        /* ── NEW: Recently online users (last 15 min) ── */
        $onlineUsersList = Cache::remember('dash_online_users_list', 60, fn() =>
            User::where('last_seen_at', '>=', now()->subMinutes(15))
                ->orderByDesc('last_seen_at')
                ->limit(20)
                ->get(['id','name','lastname','avatar','last_seen_at','phone_number'])
                ->map(fn($u) => [
                    'id'           => $u->id,
                    'name'         => $u->full_name,
                    'phone'        => $u->phone_number,
                    'avatar'       => $u->avatar,
                    'last_seen_at' => $u->last_seen_at?->diffForHumans() ?? 'N/A',
                ])
                ->toArray()
        );

        /* ── NEW: Expense breakdown (SellerTransaction) ── */
        $expenseTotalPayout    = Cache::remember('dash_expense_total',      $c, fn() => floatval(SellerTransaction::sum('netAmount')));
        $expenseCommission     = Cache::remember('dash_expense_commission', $c, fn() => floatval(SellerTransaction::sum('commissionPrice')));
        $expensePendingCount   = Cache::remember('dash_expense_pending',    $c, fn() => SellerTransaction::where('status','pending')->count());
        $expensePaidCount      = Cache::remember('dash_expense_paid',       $c, fn() => SellerTransaction::where('status','paid')->count());
        $expenseRejectedCount  = Cache::remember('dash_expense_rejected',   $c, fn() => SellerTransaction::where('status','rejected')->count());

        /* ── NEW: Promo analytics ── */
        $promoAnalytics = Cache::remember('dash_promo_analytics', $c, function () use ($soldCount) {
            return Promocode::where('status', 1)
                ->orderByDesc('usedCount')
                ->limit(10)
                ->get(['id','code','discount','usedCount','type'])
                ->map(fn($p) => [
                    'code'       => $p->code,
                    'discount'   => $p->discount,
                    'type'       => $p->type ?? '%',
                    'used_count' => (int)$p->usedCount,
                    'usage_rate' => $soldCount > 0 ? round($p->usedCount / $soldCount * 100, 1) : 0,
                ])
                ->toArray();
        });

        return compact(
            'soldCount','soldStatusA','soldStatusB','soldStatusC',
            'booksCount','catsCount','topBook','inStock',
            'userCount','newUsers','activeUsers','inactiveUsers',
            'dau','mau','onlineCount',
            'couriersCount','deliveryCount',
            'promosCount','topPromo',
            'giftsCount','newsCount',
            'totalRevenue','booksRevenue',
            'soldTrend7','monthlyRevenue',
            'categoryDist','topCatsSold','userStatus',
            'recentOrders',
            // New
            'topSellers','trendingBooks','onlineUsersList',
            'expenseTotalPayout','expenseCommission',
            'expensePendingCount','expensePaidCount','expenseRejectedCount',
            'promoAnalytics',
        );
    }
}


class DashboardController extends Controller
{
    private const CACHE_DURATION = 300;

    public function index(Request $request)
    {
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
        ];
        foreach ($keys as $key) Cache::forget($key);
    }

    private function getData(): array
    {
        $c = self::CACHE_DURATION;

        /* ── Metrics ── */
        $soldCount     = Cache::remember('dash_sold_count',       $c, fn() => Sold::count());
        $soldStatusA   = Cache::remember('dash_status_a',         $c, fn() => Sold::where('status','A')->count());
        $soldStatusB   = Cache::remember('dash_status_b',         $c, fn() => Sold::where('status','B')->count());
        $soldStatusC   = Cache::remember('dash_status_c',         $c, fn() => Sold::where('status','C')->count());

        $booksCount    = Cache::remember('dash_books_count',       $c, fn() => Books::count());
        $catsCount     = Cache::remember('dash_categories_count',  $c, fn() => BookCategories::count());
        $topBook       = Cache::remember('dash_top_book',          $c, fn() => Books::orderBy('totalSales','desc')->first()?->name ?? 'N/A');
        $inStock       = Cache::remember('dash_in_stock',          $c, fn() => Books::where('count','>',0)->count());

        $userCount     = Cache::remember('dash_user_count',        $c, fn() => User::count());
        $newUsers      = Cache::remember('dash_new_users',         $c, fn() => User::where('created_at','>=',now()->subDays(7))->count());
        $activeUsers   = Cache::remember('dash_active_users',      $c, fn() => User::whereNotNull('fcm_token')->count());
        $inactiveUsers = Cache::remember('dash_inactive_users',    $c, fn() => User::whereNull('fcm_token')->count());

        $couriersCount = Cache::remember('dash_couriers',          $c, fn() => Couriers::count());
        $deliveryCount = Cache::remember('dash_delivery',          $c, fn() => DeliveryService::count());

        $promosCount   = Cache::remember('dash_promo_count',       $c, fn() => Promocode::where('status',1)->count());
        $topPromo      = Cache::remember('dash_top_promo',         $c, fn() => Promocode::orderBy('usedCount','desc')->first()?->code ?? 'N/A');

        $giftsCount    = Cache::remember('dash_gifts',             $c, fn() => Sold::whereNotNull('gift')->count());
        $newsCount     = Cache::remember('dash_news',              $c, fn() => MarketNews::count());

        $totalRevenue  = Cache::remember('dash_total_revenue',     $c, fn() => floatval(Sold::sum('amount')));
        $booksRevenue  = Cache::remember('dash_books_revenue',     $c, fn() => floatval(Books::sum('totalRevenue')));

        /* ── 7-day sales trend ── */
        $soldTrend7 = Cache::remember('dash_trend_7', $c, function () {
            $rows = Sold::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at','>=',now()->subDays(6))
                ->groupBy('date')->orderBy('date')->get();

            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $day   = now()->subDays($i)->format('Y-m-d');
                $found = $rows->firstWhere('date', $day);
                $result[] = [
                    'date'  => now()->subDays($i)->format('M d'),
                    'count' => $found ? (int)$found->count : 0,
                ];
            }
            return $result;
        });

        /* ── 6-month revenue trend ── */
        $monthlyRevenue = Cache::remember('dash_monthly_rev', $c, function () {
            $rows = Sold::selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, SUM(amount) as total")
                ->where('created_at','>=',now()->subMonths(5)->startOfMonth())
                ->groupBy('month')->orderBy('month')->get();

            $result = [];
            for ($i = 5; $i >= 0; $i--) {
                $key   = now()->subMonths($i)->format('Y-m');
                $found = $rows->firstWhere('month', $key);
                $result[] = [
                    'month' => now()->subMonths($i)->format('M Y'),
                    'total' => $found ? round((float)$found->total) : 0,
                ];
            }
            return $result;
        });

        /* ── Category distribution (donut) ── */
        $categoryDist = Cache::remember('dash_category_dist', $c, fn() =>
            BookCategories::withCount('books')->get()
                ->filter(fn($c) => $c->books_count > 0)
                ->map(fn($c) => ['label' => $c->title, 'value' => $c->books_count])
                ->values()->toArray()
        );

        /* ── Top 5 categories by sales (donut) ── */
        $topCatsSold = Cache::remember('dash_top_cats', $c, function () {
            $categories = BookCategories::all()->keyBy('id');
            $books      = Books::all()->keyBy('id');
            $sales      = [];

            Sold::chunk(200, function ($solds) use ($books, $categories, &$sales) {
                foreach ($solds as $sold) {
                    if (!is_array($sold->items)) continue;
                    foreach ($sold->items as $item) {
                        $bookId = $item['item_id']    ?? null;
                        $qty    = $item['count_item'] ?? 0;
                        if ($bookId && $qty > 0 && $books->has($bookId)) {
                            $catId = $books->get($bookId)->category_id;
                            if ($categories->has($catId)) {
                                $title = $categories->get($catId)->title;
                                $sales[$title] = ($sales[$title] ?? 0) + $qty;
                            }
                        }
                    }
                }
            });

            return collect($sales)->filter()->sortDesc()->take(5)
                ->map(fn($v,$k) => ['label' => $k, 'value' => $v])
                ->values()->toArray();
        });

        /* ── User status (donut) ── */
        $userStatus = [
            ['label' => 'Faol',   'value' => $activeUsers],
            ['label' => "Nofaol", 'value' => $inactiveUsers],
        ];

        /* ── Recent orders ── */
        $recentOrders = Sold::with('user')->latest()->take(10)->get()->map(function ($sold) {
            $map = [
                'A' => ['label' => 'Yetkazish kerak', 'color' => 'warning'],
                'B' => ['label' => 'Jarayonda',       'color' => 'info'],
                'C' => ['label' => 'Yakunlangan',     'color' => 'success'],
            ];
            $st = $map[$sold->status] ?? ['label' => "Noma'lum", 'color' => 'gray'];
            return [
                'id'       => $sold->id,
                'customer' => $sold->user?->name ?? 'Mehmon',
                'amount'   => number_format((float)$sold->amount, 0, '.', ' ') . " so'm",
                'status'   => $st['label'],
                'color'    => $st['color'],
                'date'     => $sold->created_at->format('d.m.Y'),
                'gift'     => !is_null($sold->gift),
            ];
        })->toArray();

        return compact(
            'soldCount','soldStatusA','soldStatusB','soldStatusC',
            'booksCount','catsCount','topBook','inStock',
            'userCount','newUsers','activeUsers','inactiveUsers',
            'couriersCount','deliveryCount',
            'promosCount','topPromo',
            'giftsCount','newsCount',
            'totalRevenue','booksRevenue',
            'soldTrend7','monthlyRevenue',
            'categoryDist','topCatsSold','userStatus',
            'recentOrders',
        );
    }
}