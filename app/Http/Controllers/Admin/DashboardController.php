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