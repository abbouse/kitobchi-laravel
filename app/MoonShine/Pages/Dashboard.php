<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use Illuminate\Support\Facades\Cache;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\Gifts;
use App\Models\MarketNews;
use App\Models\Promocode;
use App\Models\Sold;
use App\Models\User;

// ✅ MoonShine 3.x uchun TO'G'RI importlar
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

// ✅ moonshine/apexcharts paketi uchun TO'G'RI importlar
use MoonShine\Apexcharts\Components\DonutChartMetric;
use MoonShine\Apexcharts\Components\LineChartMetric;

class Dashboard extends Page
{
    private const CACHE_DURATION = 300; // 5 daqiqa kesh

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Boshqaruv qismi';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        // Kesh kalitlari ro'yxati
        $cacheKeys = [
            'sold_trend_data', 'sold_count', 'sold_status_a', 'sold_status_b', 'sold_status_c',
            'books_count', 'book_categories_count', 'top_sold_book', 'User_count', 'new_User_7_days',
            'couriers_count', 'delivery_service_count', 'promocodes_count', 'top_promocode',
            'gift_orders_count', 'market_news_count', 'total_revenue', 'books_revenue',
            'category_books_distribution', 'active_User', 'inactive_User', 'top_categories_sold',
            'books_in_stock'
        ];

        // Keshni tozalash (so'rov kelsa)
        if (function_exists('request') && request()->has('clear_cache')) {
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }

        // ✅ Sotuvlar trendi: ['2025-10-10' => 50, '2025-10-11' => 60, ...]
        $soldTrendData = Cache::remember('sold_trend_data', self::CACHE_DURATION, function () {
            return Sold::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();
        });

        return [
            Grid::make([

                // ── Keshni tozalash tugmasi ───────────────────────────────────
                Column::make([
                    '<div style="margin-bottom:1rem;">
                        <a href="?clear_cache=1"
                           style="display:inline-flex;align-items:center;gap:8px;
                                  background:#EC4176;color:#fff;border-radius:8px;
                                  padding:8px 18px;text-decoration:none;font-size:14px;
                                  font-weight:500;"
                           onmouseover="this.style.opacity=.8"
                           onmouseout="this.style.opacity=1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor"
                                 style="width:18px;height:18px;">
                              <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992
                                       m-4.993 0 3.181-3.181A1.652 1.652 0 0 1 8.847 10.42
                                       M16.023 9.348 12.98 12.381m0 0-3.182 3.182
                                       m0-3.182A1.652 1.652 0 0 0 10.42 8.847" />
                            </svg>
                            Keshni tozalash
                        </a>
                    </div>',
                ])->columnSpan(12),

                // ════════════════════════════════════════════
                // SOTUVLAR BO'LIMI
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Sotuvlar')
                        ->value(Cache::remember('sold_count', self::CACHE_DURATION,
                            fn() => Sold::count()))
                        ->icon('shopping-bag'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Yetkazish kerak')
                        ->value(Cache::remember('sold_status_a', self::CACHE_DURATION,
                            fn() => Sold::where('status', 'A')->count()))
                        ->icon('paper-airplane'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Jarayonda')
                        ->value(Cache::remember('sold_status_b', self::CACHE_DURATION,
                            fn() => Sold::where('status', 'B')->count()))
                        ->icon('signal'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Yakunlangan buyurtma')
                        ->value(Cache::remember('sold_status_c', self::CACHE_DURATION,
                            fn() => Sold::where('status', 'C')->count()))
                        ->icon('check-circle'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // KITOBLAR VA KATEGORIYALAR
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Umumiy kitoblar')
                        ->value(Cache::remember('books_count', self::CACHE_DURATION,
                            fn() => Books::count()))
                        ->icon('book-open'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Kategoriyalar')
                        ->value(Cache::remember('book_categories_count', self::CACHE_DURATION,
                            fn() => BookCategories::count()))
                        ->icon('folder'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make("Eng ko'p sotilgan kitob")
                        ->value(Cache::remember('top_sold_book', self::CACHE_DURATION,
                            fn() => Books::orderBy('totalSales', 'desc')->first()?->name ?? 'N/A'))
                        ->icon('star'),
                ])->columnSpan(6),

                // ════════════════════════════════════════════
                // FOYDALANUVCHILAR
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Umumiy foydalanuvchilar')
                        ->value(Cache::remember('User_count', self::CACHE_DURATION,
                            fn() => User::count()))
                        ->icon('users'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Yangi foydalanuvchilar (7 kun)')
                        ->value(Cache::remember('new_User_7_days', self::CACHE_DURATION,
                            fn() => User::where('created_at', '>=', now()->subDays(7))->count()))
                        ->icon('user-plus'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Bugungi faol (DAU)')
                        ->value(Cache::remember('dau_count', self::CACHE_DURATION,
                            fn() => User::where('last_seen_at', '>=', now()->subDay())->count()))
                        ->icon('signal'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make("Onlayn (5 daqiqa)")
                        ->value(Cache::remember('online_count', self::CACHE_DURATION,
                            fn() => User::where('last_seen_at', '>=', now()->subMinutes(5))->count()))
                        ->icon('wifi'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // KURYERLAR VA YETKAZIB BERISH
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Kuryerlar')
                        ->value(Cache::remember('couriers_count', self::CACHE_DURATION,
                            fn() => Couriers::count()))
                        ->icon('truck'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Yetkazib berish usullari')
                        ->value(Cache::remember('delivery_service_count', self::CACHE_DURATION,
                            fn() => DeliveryService::count()))
                        ->icon('inbox-stack'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // PROMOKODLAR
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Faol promokodlar')
                        ->value(Cache::remember('promocodes_count', self::CACHE_DURATION,
                            fn() => Promocode::where('status', 1)->count()))
                        ->icon('receipt-percent'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make("Eng ko'p ishlatilgan promokod")
                        ->value(Cache::remember('top_promocode', self::CACHE_DURATION,
                            fn() => Promocode::orderBy('usedCount', 'desc')->first()?->code ?? 'N/A'))
                        ->icon('receipt-percent'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // SOVGʻALAR VA YANGILIKLAR
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make("Sovg'ali buyurtmalar")
                        ->value(Cache::remember('gift_orders_count', self::CACHE_DURATION,
                            fn() => Sold::whereNotNull('gift')->count()))
                        ->icon('gift'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Yangiliklar')
                        ->value(Cache::remember('market_news_count', self::CACHE_DURATION,
                            fn() => MarketNews::count()))
                        ->icon('rss'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // DAROMAD
                // ════════════════════════════════════════════
                Column::make([
                    ValueMetric::make('Umumiy daromad')
                        ->value(Cache::remember('total_revenue', self::CACHE_DURATION,
                            fn() => number_format(floatval(Sold::sum('amount')), 0) . " so'm"))
                        ->icon('currency-dollar'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Kitoblardan daromad')
                        ->value(Cache::remember('books_revenue', self::CACHE_DURATION,
                            fn() => number_format(floatval(Books::sum('totalRevenue')), 0) . " so'm"))
                        ->icon('currency-dollar'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Stokdagi kitoblar')
                        ->value(Cache::remember('books_in_stock', self::CACHE_DURATION,
                            fn() => Books::where('count', '>', 0)->count()))
                        ->icon('archive-box'),
                ])->columnSpan(3),

                // ════════════════════════════════════════════
                // CHARTLAR (moonshine/apexcharts)
                // ════════════════════════════════════════════

                // ✅ DonutChartMetric — to'g'ri: ->values(['label' => $value])
                Column::make([
                    DonutChartMetric::make("Kategoriyalar bo'yicha kitoblar")
                        ->values(
                            Cache::remember('category_books_distribution', self::CACHE_DURATION,
                                fn() => BookCategories::withCount('books')
                                    ->get()
                                    ->pluck('books_count', 'title')
                                    ->toArray()
                            )
                        )
                        ->columnSpan(6),
                ])->columnSpan(6),

                // Sotuvlar trendi (7 kun) — LineChartMetric
                Column::make([
                    LineChartMetric::make('Sotuvlar trendi (7 kun)')
                        ->line(
                            ['Sotuvlar soni' => $soldTrendData],
                            '#EC4176'
                        )
                        ->columnSpan(6),
                ])->columnSpan(6),

                // DonutChartMetric — foydalanuvchilar statusi (so'nggi 30 kun faolligi)
                Column::make([
                    DonutChartMetric::make('Foydalanuvchilar faolligi (30 kun)')
                        ->values([
                            'Oylik faol (MAU)'   => Cache::remember('mau_count', self::CACHE_DURATION,
                                fn() => User::where('last_seen_at', '>=', now()->subDays(30))->count()),
                            'Push yoqilgan' => Cache::remember('active_User', self::CACHE_DURATION,
                                fn() => User::whereNotNull('fcm_token')->count()),
                            'Nofaol (30 kun)' => Cache::remember('inactive_User', self::CACHE_DURATION,
                                fn() => User::where(fn($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDays(30)))->count()),
                        ])
                        ->colors(['#10B981', '#3B82F6', '#F43F5E'])
                        ->columnSpan(6),
                ])->columnSpan(6),

                // DonutChartMetric — top 5 kategoriya (samarali JOIN orqali)
                Column::make([
                    DonutChartMetric::make("Top 5 kategoriyalar (sotuv bo'yicha)")
                        ->values(
                            Cache::remember('top_categories_sold', self::CACHE_DURATION,
                                function () {
                                    $results = Books::join('book_categories', 'books.category_id', '=', 'book_categories.id')
                                        ->selectRaw('book_categories.title, SUM(books.totalSales) as total_sales')
                                        ->groupBy('book_categories.id', 'book_categories.title')
                                        ->orderByDesc('total_sales')
                                        ->limit(5)
                                        ->pluck('total_sales', 'title')
                                        ->toArray();

                                    return empty($results) ? ["Ma'lumot yo'q" => 0] : $results;
                                }
                            )
                        )
                        ->colors(['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#33FFF5'])
                        ->height(400)
                        ->columnSpan(6),
                ])->columnSpan(6),

            ]),
        ];
    }
}