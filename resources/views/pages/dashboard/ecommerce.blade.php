@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Dashboard" />

<div class="space-y-5">

    {{-- ============================================================
         ROW 1: 4 asosiy KPI kartalar
    ============================================================ --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Jami daromad --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-500/10 shrink-0">
                    <svg class="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-right">
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Jami daromad</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalRevenue) }}</h4>
                    <p class="text-xs text-gray-400 mt-0.5">UZS</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400">Bugun:</span>
                <span class="text-xs font-semibold text-success-500">+{{ number_format($todayRevenue) }} UZS</span>
            </div>
        </div>

        {{-- Jami buyurtmalar --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-success-50 dark:bg-success-500/10 shrink-0">
                    <svg class="w-6 h-6 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div class="text-right">
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Jami buyurtmalar</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalOrders) }}</h4>
                    <p class="text-xs text-gray-400 mt-0.5">ta buyurtma</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <span class="text-xs text-success-500 font-medium">{{ $completedOrders }} yakunlangan</span>
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span class="text-xs text-warning-500 font-medium">{{ $pendingOrders }} kutilmoqda</span>
            </div>
        </div>

        {{-- Foydalanuvchilar --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 shrink-0">
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="text-right">
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Foydalanuvchilar</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalUsers) }}</h4>
                    <p class="text-xs text-gray-400 mt-0.5">ta foydalanuvchi</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <span class="flex items-center gap-1 text-xs text-success-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-success-500 inline-block"></span>
                    {{ $onlineUsers }} online
                </span>
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span class="text-xs text-brand-500">+{{ $newUsersToday }} bugun</span>
            </div>
        </div>

        {{-- Premium --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 shrink-0">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div class="text-right">
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Premium a'zolar</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ number_format($premiumUsers) }}</h4>
                    <p class="text-xs text-gray-400 mt-0.5">ta premium</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full bg-yellow-400" style="width: {{ $totalUsers > 0 ? round(($premiumUsers/$totalUsers)*100) : 0 }}%"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $totalUsers > 0 ? round(($premiumUsers/$totalUsers)*100) : 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ROW 2: Oylik daromad grafigi + Buyurtmalar holati
    ============================================================ --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- Oylik daromad bar chart --}}
        <div class="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Oylik daromad</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Oxirgi 6 oy</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bu oy</p>
                    <p class="text-sm font-bold text-brand-500">{{ number_format($monthRevenue) }} UZS</p>
                </div>
            </div>
            <div id="chartMonthlyRevenue" class="min-h-[200px]"></div>
        </div>

        {{-- Buyurtmalar status donut --}}
        <div class="rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="rounded-2xl bg-white px-5 pb-5 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Buyurtmalar holati</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jami: {{ number_format($totalOrders) }} ta</p>
                </div>
                <div id="chartOrderStatus" style="min-height:200px"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-5 py-4 sm:px-6">
                @foreach($orderStatusDist as $item)
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:{{ $item['color'] }}"></span>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['label'] }}</p>
                        <p class="text-xs font-semibold text-gray-800 dark:text-white/90">{{ number_format($item['value']) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============================================================
         ROW 3: Foydalanuvchilar + Online + Isolat
    ============================================================ --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- Foydalanuvchilar statistikasi --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Foydalanuvchilar</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Holat bo'yicha</p>
            </div>
            <div class="space-y-4">
                @php
                    $userRows = [
                        ['label' => 'Online (5 daqiqa)',  'count' => $onlineUsers,    'rate' => $totalUsers > 0 ? round($onlineUsers/$totalUsers*100) : 0,    'bar' => 'bg-success-500', 'bg' => 'bg-success-50 dark:bg-success-500/10', 'ic' => 'text-success-500'],
                        ['label' => 'Faol (FCM token)',   'count' => $activeUsers,    'rate' => $totalUsers > 0 ? round($activeUsers/$totalUsers*100) : 0,    'bar' => 'bg-brand-500',   'bg' => 'bg-brand-50 dark:bg-brand-500/10',   'ic' => 'text-brand-500'],
                        ['label' => 'Nofaol',             'count' => $inactiveUsers,  'rate' => $totalUsers > 0 ? round($inactiveUsers/$totalUsers*100) : 0,  'bar' => 'bg-gray-300 dark:bg-gray-600',  'bg' => 'bg-gray-50 dark:bg-gray-800', 'ic' => 'text-gray-400'],
                        ['label' => 'Isolat (30+ kun)',   'count' => $isolatedUsers,  'rate' => $totalUsers > 0 ? round($isolatedUsers/$totalUsers*100) : 0,  'bar' => 'bg-error-500',   'bg' => 'bg-error-50 dark:bg-error-500/10',   'ic' => 'text-error-500'],
                        ['label' => "Bu hafta qo'shildi",'count' => $newUsersWeek,   'rate' => $totalUsers > 0 ? min(100,round($newUsersWeek/$totalUsers*100)) : 0, 'bar' => 'bg-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-500/10', 'ic' => 'text-blue-500'],
                    ];
                @endphp
                @foreach($userRows as $row)
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="flex items-center justify-center w-7 h-7 {{ $row['bg'] }} rounded-lg shrink-0">
                            <svg class="w-3.5 h-3.5 {{ $row['ic'] }} fill-current" viewBox="0 0 24 24">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $row['label'] }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="relative h-1.5 w-20 rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="absolute left-0 top-0 h-full rounded-full {{ $row['bar'] }}" style="width:{{ $row['rate'] }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-white/90 w-8 text-right">{{ number_format($row['count']) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Jami</p>
                    <p class="text-base font-bold text-gray-800 dark:text-white/90">{{ number_format($totalUsers) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Premium</p>
                    <p class="text-base font-bold text-yellow-500">{{ number_format($premiumUsers) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Bugun yangi</p>
                    <p class="text-base font-bold text-brand-500">+{{ number_format($newUsersToday) }}</p>
                </div>
            </div>
        </div>

        {{-- Online foydalanuvchilar --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Hozir online</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Oxirgi 5 daqiqada faol</p>
                </div>
                <span class="flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-success-500 inline-block animate-pulse"></span>
                    {{ $onlineUsers }} online
                </span>
            </div>

            @if($onlineUsersList->count() > 0)
            <div class="space-y-3">
                @foreach($onlineUsersList as $ou)
                <div class="flex items-center gap-3">
                    <div class="relative h-9 w-9 shrink-0">
                        @if($ou->avatar)
                            <img src="{{ $ou->avatar }}" class="h-full w-full rounded-full object-cover" />
                        @else
                            <div class="h-full w-full rounded-full bg-brand-500 flex items-center justify-center text-xs font-bold text-white">
                                {{ strtoupper(substr($ou->name,0,1)) }}{{ strtoupper(substr($ou->lastname,0,1)) }}
                            </div>
                        @endif
                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-success-500 dark:border-gray-900"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ $ou->name }} {{ $ou->lastname }}</p>
                        <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($ou->last_seen_at)->diffForHumans() }}</p>
                    </div>
                    <a href="{{ route('admin.users.show', $ou->id) }}" class="text-xs text-brand-500 hover:underline shrink-0">Ko'rish</a>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-8 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">Hozir hech kim online emas</p>
            </div>
            @endif

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-error-500 font-medium">{{ $isolatedUsers }} ta isolat foydalanuvchi (30+ kun)</span>
                    <a href="{{ route('admin.users.index', ['filter' => 'inactive']) }}" class="text-brand-500 hover:underline">Ko'rish</a>
                </div>
            </div>
        </div>

        {{-- To'lov usullari + kunlik orders --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">To'lov holati</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buyurtmalar bo'yicha</p>
            </div>
            <div id="chartPayment" style="min-height:180px"></div>

            {{-- Bugungi stats --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <p class="mb-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Oxirgi 7 kun</p>
                <div class="flex items-end justify-between gap-1 h-16">
                    @foreach($dailyOrders as $day)
                    @php $maxCount = $dailyOrders->max('count') ?: 1; $pct = round(($day['count']/$maxCount)*100); @endphp
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <span class="text-xs text-gray-400">{{ $day['count'] }}</span>
                        <div class="w-full rounded-t-sm bg-brand-500 opacity-80 hover:opacity-100 transition-opacity" style="height: {{ max(4, $pct * 0.4) }}px"></div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ substr($day['day'],0,2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ROW 4: Top sotuvchilar + Top mijozlar
    ============================================================ --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

        {{-- Top sotuvchi kitoblar --}}
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Top sotuvchi kitoblar</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Eng ko'p sotilganlar</p>
                </div>
            </div>
            @if($topSellingBooks->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($topSellingBooks as $i => $book)
                <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors sm:px-6">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-sm font-bold
                        {{ $i === 0 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400' :
                          ($i === 1 ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' :
                          ($i === 2 ? 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400' :
                          'bg-gray-50 text-gray-500 dark:bg-gray-800/50 dark:text-gray-500')) }}">
                        {{ $i + 1 }}
                    </span>
                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                        @php $imgs = json_decode($book->images ?? '[]', true); @endphp
                        @if(is_array($imgs) && count($imgs) > 0)
                            <img src="{{ $imgs[0] }}" alt="{{ $book->name }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ $book->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($book->sold_count) }} ta sotildi</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($book->total_revenue) }}</p>
                        <p class="text-xs text-gray-400">UZS</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-12 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yo'q</p>
            </div>
            @endif
        </div>

        {{-- Top mijozlar --}}
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Top mijozlar</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Eng ko'p xarid qilganlar</p>
                </div>
            </div>
            @if($topBuyers->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($topBuyers as $i => $buyer)
                <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors sm:px-6">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-sm font-bold
                        {{ $i === 0 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400' :
                          ($i === 1 ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' :
                          ($i === 2 ? 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400' :
                          'bg-gray-50 text-gray-500 dark:bg-gray-800/50 dark:text-gray-500')) }}">
                        {{ $i + 1 }}
                    </span>
                    <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full">
                        @if($buyer->user?->avatar)
                            <img src="{{ $buyer->user->avatar }}" alt="{{ $buyer->user->full_name }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">
                                {{ strtoupper(substr($buyer->user?->name ?? 'U', 0, 1)) }}{{ strtoupper(substr($buyer->user?->lastname ?? '', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">
                            {{ $buyer->user ? $buyer->user->full_name : '#'.$buyer->user_id }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($buyer->order_count) }} ta buyurtma</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold text-success-500">{{ number_format($buyer->total_spent) }}</p>
                        <p class="text-xs text-gray-400">UZS</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-12 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yo'q</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         ROW 5: So'nggi buyurtmalar
    ============================================================ --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">So'nggi buyurtmalar</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Bugun: {{ $todayOrders }} ta yangi</p>
            </div>
            <a href="#" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                Barchasi
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">#ID</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Mijoz</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Summa</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Mahsulotlar</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Sana</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-5 py-3 sm:px-6">
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90 flex items-center gap-1">
                                #{{ $order['id'] }}
                                @if($order['gift'])
                                <svg class="w-3.5 h-3.5 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                </svg>
                                @endif
                            </span>
                        </td>
                        <td class="px-5 py-3 sm:px-6">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full">
                                    @if($order['avatar'])
                                        <img src="{{ $order['avatar'] }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                                            {{ strtoupper(substr($order['customer'],0,1)) }}
                                        </div>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $order['customer'] }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-3 sm:px-6">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $order['amount'] }}</p>
                        </td>
                        <td class="px-5 py-3 sm:px-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order['items_count'] }} ta</p>
                        </td>
                        <td class="px-5 py-3 sm:px-6">
                            @php
                                $statusColors = [
                                    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
                                    'info'    => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
                                    'primary' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                                    'error'   => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
                                    'gray'    => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
                                ];
                            @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$order['color']] ?? $statusColors['gray'] }}">
                                {{ $order['status'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 sm:px-6">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order['date'] }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">Buyurtmalar yo'q</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- end space-y-5 --}}

@push('scripts')
<script>
// Oylik daromad grafigi
(function(){
    var data = @json($monthlyRevenue);
    new ApexCharts(document.querySelector('#chartMonthlyRevenue'), {
        series: [{ name: 'Daromad', data: data.map(d => d.total) }],
        chart: { type: 'bar', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#465FFF'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        xaxis: {
            categories: data.map(d => d.month),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#9CA3AF', fontSize: '12px' } }
        },
        yaxis: { labels: { style: { colors: '#9CA3AF' }, formatter: v => (v/1000000).toFixed(1)+'M' } },
        grid: { borderColor: '#F3F4F6', strokeDashArray: 4 },
        tooltip: { y: { formatter: v => v.toLocaleString() + " UZS" } }
    }).render();
})();

// Buyurtmalar status donut
(function(){
    var items = @json($orderStatusDist);
    new ApexCharts(document.querySelector('#chartOrderStatus'), {
        series: items.map(i => i.value),
        labels: items.map(i => i.label),
        colors: items.map(i => i.color),
        chart: { type: 'donut', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
        legend: { position: 'bottom', fontSize: '12px', labels: { colors: '#6B7280' } },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } }
    }).render();
})();

// To'lov usullari donut
(function(){
    var items = @json($paymentDist);
    new ApexCharts(document.querySelector('#chartPayment'), {
        series: items.map(i => i.value),
        labels: items.map(i => i.label),
        colors: items.map(i => i.color),
        chart: { type: 'donut', height: 180, toolbar: { show: false }, fontFamily: 'inherit' },
        legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#6B7280' } },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '60%' } } }
    }).render();
})();
</script>
@endpush

@endsection