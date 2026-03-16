{{-- resources/views/ecommerce/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">

    {{-- ── Cache clear banner ── --}}
    @if(session('cache_cleared'))
    <div class="col-span-12">
        <div class="rounded-2xl border border-success-200 bg-success-50 px-5 py-3 text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400 flex items-center gap-2">
            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Kesh muvaffaqiyatli tozalandi.
        </div>
    </div>
    @endif

    {{-- ── Clear cache button ── --}}
    <div class="col-span-12 flex justify-end">
        <a href="{{ route('dashboard', ['clear_cache' => 1]) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181-3.181A1.652 1.652 0 0 1 8.847 10.42M16.023 9.348 12.98 12.381m0 0-3.182 3.182m0-3.182A1.652 1.652 0 0 0 10.42 8.847"/>
            </svg>
            Keshni tozalash
        </a>
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 1 — 4 metrics (sales)  +  2 metric cards
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12 space-y-6 xl:col-span-7">

        {{-- 4 small metrics (sotuvlar) --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 md:gap-6">
            @php
                $salesMetrics = [
                    ['label' => 'Jami sotuvlar',        'value' => $soldCount,   'icon' => 'shopping-bag', 'color' => 'brand'],
                    ['label' => 'Yetkazish kerak',      'value' => $soldStatusA, 'icon' => 'paper-plane',  'color' => 'warning'],
                    ['label' => 'Jarayonda',            'value' => $soldStatusB, 'icon' => 'signal',       'color' => 'info'],
                    ['label' => 'Yakunlangan',          'value' => $soldStatusC, 'icon' => 'check-circle', 'color' => 'success'],
                ];
            @endphp
            @foreach($salesMetrics as $m)
            <x-ecommerce.metric-card :label="$m['label']" :value="$m['value']" :icon="$m['icon']" :color="$m['color']" />
            @endforeach
        </div>

        {{-- Monthly Sales chart --}}
        <x-ecommerce.chart-monthly-sales :data="$monthlyRevenue" />

    </div>

    {{-- Monthly Target / KPI sidebar --}}
    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.monthly-target
            :totalRevenue="$totalRevenue"
            :booksRevenue="$booksRevenue"
            :soldCount="$soldCount"
            :soldStatusC="$soldStatusC"
        />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 2 — Statistics / sales trend chart (full)
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12">
        <x-ecommerce.chart-statistics :trend="$soldTrend7" />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 3 — Metric cards (2 columns)
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 md:gap-6">
            @php
                $infoMetrics = [
                    ['label' => 'Kitoblar',              'value' => $booksCount,    'icon' => 'book-open'],
                    ['label' => 'Kategoriyalar',         'value' => $catsCount,     'icon' => 'folder'],
                    ['label' => 'Foydalanuvchilar',      'value' => $userCount,     'icon' => 'users'],
                    ['label' => 'Yangi (7 kun)',         'value' => $newUsers,      'icon' => 'user-plus'],
                    ['label' => 'Kuryerlar',             'value' => $couriersCount, 'icon' => 'truck'],
                    ['label' => 'Yetkazish usullari',   'value' => $deliveryCount, 'icon' => 'inbox-stack'],
                    ['label' => 'Faol promokodlar',     'value' => $promosCount,   'icon' => 'receipt-percent'],
                    ['label' => "Sovg'ali buyurtmalar", 'value' => $giftsCount,    'icon' => 'gift'],
                    ['label' => 'Yangiliklar',           'value' => $newsCount,     'icon' => 'rss'],
                    ['label' => 'Stokdagi kitoblar',    'value' => $inStock,       'icon' => 'archive-box'],
                    ['label' => 'Faol foydalanuvchilar','value' => $activeUsers,   'icon' => 'wifi'],
                    ['label' => 'Nofaol',               'value' => $inactiveUsers, 'icon' => 'wifi-off'],
                ];
            @endphp
            @foreach($infoMetrics as $m)
            <x-ecommerce.metric-card :label="$m['label']" :value="$m['value']" :icon="$m['icon']" color="gray" compact />
            @endforeach
        </div>
    </div>

    {{-- Special text metrics --}}
    <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
        <x-ecommerce.text-metric label="Eng ko'p sotilgan kitob" :value="$topBook" icon="star" />
        <x-ecommerce.text-metric label="Eng ko'p ishlatilgan promokod" :value="$topPromo" icon="receipt-percent" />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 4 — Charts (donuts)
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.chart-donut
            title="Kategoriyalar bo'yicha kitoblar"
            :items="$categoryDist"
            :colors="['#6366F1','#8B5CF6','#EC4899','#14B8A6','#F59E0B','#10B981','#3B82F6','#EF4444']"
        />
    </div>

    <div class="col-span-12 xl:col-span-7">
        <x-ecommerce.chart-donut
            title="Top 5 kategoriyalar (sotuv bo'yicha)"
            :items="$topCatsSold"
            :colors="['#FF5733','#33FF57','#3357FF','#FF33A1','#33FFF5']"
        />
    </div>

    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.chart-donut
            title="Foydalanuvchilar statusi"
            :items="$userStatus"
            :colors="['#10B981','#F43F5E']"
        />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 5 — Customer demographic + Recent orders
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.customer-demographic :activeUsers="$activeUsers" :inactiveUsers="$inactiveUsers" :newUsers="$newUsers" :userCount="$userCount" />
    </div>

    <div class="col-span-12 xl:col-span-7">
        <x-ecommerce.recent-orders :orders="$recentOrders" />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 6 — Online users (DAU/MAU) + Expense breakdown
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12 xl:col-span-7">
        <x-ecommerce.online-users
            :onlineCount="$onlineCount"
            :dau="$dau"
            :mau="$mau"
            :users="$onlineUsersList"
        />
    </div>

    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.expense-breakdown
            :totalPayout="$expenseTotalPayout"
            :commission="$expenseCommission"
            :pendingCount="$expensePendingCount"
            :paidCount="$expensePaidCount"
            :rejectedCount="$expenseRejectedCount"
        />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 7 — Top sellers + Trending products
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.top-sellers :sellers="$topSellers" />
    </div>

    <div class="col-span-12 xl:col-span-7">
        <x-ecommerce.trending-products :books="$trendingBooks" />
    </div>

    {{-- ════════════════════════════════════════════════
         ROW 8 — Promo analytics (full width)
         ════════════════════════════════════════════════ --}}
    <div class="col-span-12">
        <x-ecommerce.promo-analytics :promos="$promoAnalytics" />
    </div>

</div>
@endsection