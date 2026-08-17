@extends('layouts.marketplace')

@section('title', __('marketplace.profile_title') . ' — Kitobchi')

@push('meta')
<meta name="robots" content="noindex, follow">
@endpush

@push('styles')
<!-- Leaflet Map CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    .leaflet-container {
        font-family: inherit;
        z-index: 10 !important;
    }
</style>
@endpush

@section('content')
<div class="py-4 md:py-6 min-h-dvh grow">
    <!-- ====== MOBILE TOP BAR (PiyolaMarket 1:1) ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300">
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
            <div class="grid grid-cols-5 items-center gap-2">
                <div class="col-span-1">
                    <a href="javascript:history.back()" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                    </a>
                </div>
                <div class="col-span-3">
                    <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">{{ __('marketplace.profile_title') }}</h1>
                </div>
                <div class="col-span-1 flex justify-end"></div>
            </div>
        </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">

        <!-- ====== BREADCRUMBS (Desktop) ====== -->
        <div class="flex items-center gap-2 mb-6 max-md:hidden">
            <a href="{{ route('web.catalog') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300 shrink-0" title="{{ __('marketplace.back_to_catalog') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
            <nav aria-label="breadcrumb" class="relative min-w-0">
                <ol class="flex items-center gap-2 text-sm text-[#8F8FA1]">
                    <li>
                        <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_home') }}</a>
                    </li>
                    <li class="text-gray-300">/</li>
                    <li class="text-neutral-900 font-semibold">{{ __('marketplace.profile_title') }}</li>
                </ol>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6 items-start" id="kcProfileGrid">
            <!-- Sidebar: user card + tab navigation (PiyolaMarket 1:1) -->
            <div class="bg-secondary-50 md:bg-secondary-50 rounded-3xl p-4 md:p-6 border border-secondary-100 sticky top-24 flex flex-col gap-4">
                <div class="flex items-center gap-3 pb-4 border-b border-secondary-100">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0">
                        {{ strtoupper(substr($user->name ?: $user->phone_number, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div id="kcProfileSidebarName" class="text-base font-bold text-neutral-900 truncate leading-snug">{{ $user->name ?: __('marketplace.profile_user') }}</div>
                        <div class="text-xs text-neutral-500 truncate mt-0.5">+{{ $user->phone_number }}</div>
                    </div>
                </div>

                <nav class="flex flex-col gap-1">
                    <button type="button" id="kcProfileTabBtnOrders" onclick="kcProfileSwitchTab('orders')" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left cursor-pointer border-none bg-transparent text-neutral-600 hover:bg-neutral-50">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        <span>{{ __('marketplace.profile_orders') }}</span>
                    </button>
                    <button type="button" id="kcProfileTabBtnReviews" onclick="kcProfileSwitchTab('reviews')" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left cursor-pointer border-none bg-transparent text-neutral-600 hover:bg-neutral-50">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        <span>{{ __('marketplace.profile_reviews') }}</span>
                    </button>
                    <button type="button" id="kcProfileTabBtnInfo" onclick="kcProfileSwitchTab('info')" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all text-left cursor-pointer border-none bg-[#F1F5F9] text-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/><path d="M6 12h4"/></svg>
                        <span>{{ __('marketplace.profile_info') }}</span>
                    </button>
                    <form action="{{ route('web.auth.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left cursor-pointer border-none bg-transparent text-red-500 hover:bg-red-50">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>{{ __('marketplace.profile_logout') }}</span>
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Right column: tab panels -->
            <div class="w-full min-w-0">

                <!-- ====== ORDERS PANEL ====== -->
                <div id="kcProfilePanelOrders" class="kc-profile-panel" style="display:none;">
                    <div class="bg-white p-6 md:p-8 rounded-3xl border border-secondary-100 shadow-sm">
                        <h3 class="text-xl font-bold text-neutral-900 mb-6">
                            {{ __('marketplace.profile_orders_title') }} ({{ count($orders) }})
                        </h3>

                        @if(count($orders) === 0)
                            <div class="text-center py-12 px-4">
                                <div class="w-16 h-16 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-neutral-900 mb-1">{{ __('marketplace.profile_no_orders') }}</h4>
                                <p class="text-neutral-400 text-sm mb-6">{{ __('marketplace.profile_no_orders_desc') }}</p>
                                <a href="{{ route('web.catalog') }}" class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-white font-semibold px-6 py-3 rounded-2xl text-sm transition-colors">
                                    {{ __('marketplace.go_to_catalog') }}
                                </a>
                            </div>
                        @else
                            <div class="flex flex-col gap-4">
                                @foreach($orders as $order)
                                    <div class="p-5 border border-secondary-100 rounded-2xl bg-[#F8FAFC]">
                                        <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <span class="font-bold text-neutral-900">{{ __('marketplace.profile_order_number', ['number' => $order->order_number ?? $order->id]) }}</span>
                                                <span class="text-neutral-400 text-xs ml-2">
                                                    {{ optional($order->created_at)->format('d.m.Y, H:i') }}
                                                </span>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                                @if($order->paymentStatus == 2) bg-emerald-100 text-emerald-700
                                                @elseif($order->paymentStatus == 1) bg-amber-100 text-amber-700
                                                @else bg-neutral-200 text-neutral-700 @endif">
                                                @if($order->paymentStatus == 2) {{ __('marketplace.profile_status_paid') }}
                                                @elseif($order->paymentStatus == 1) {{ __('marketplace.profile_status_pending') }}
                                                @else {{ __('marketplace.profile_status_accepted') }} @endif
                                            </span>
                                        </div>

                                        <div class="text-sm text-neutral-600 mb-3">
                                            <span class="font-semibold">{{ __('marketplace.profile_address') }}:</span> {{ $order->address ?? $order->city ?? __('marketplace.profile_address_unset') }}
                                        </div>

                                        <div class="flex justify-between items-center border-t border-neutral-200/60 pt-3">
                                            <span class="text-sm text-neutral-400">{{ __('marketplace.profile_total') }}</span>
                                            <span class="text-lg font-bold text-primary">
                                                {{ number_format($order->summa ?? $order->price ?? 0, 0, ',', ' ') }} {{ __('marketplace.currency') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ====== SHARHLARIM PANEL ====== -->
                <div id="kcProfilePanelReviews" class="kc-profile-panel" style="display:none;">
                    <div class="bg-white p-6 md:p-8 rounded-3xl border border-secondary-100 shadow-sm">
                        <h3 class="text-xl font-bold text-neutral-900 mb-6">
                            {{ __('marketplace.profile_reviews') }} ({{ count($myReviews ?? []) }})
                        </h3>

                        @if(count($myReviews ?? []) === 0)
                            <div class="text-center py-12 px-4">
                                <div class="w-16 h-16 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-neutral-900 mb-1">{{ __('marketplace.profile_no_reviews') }}</h4>
                                <p class="text-neutral-400 text-sm">{{ __('marketplace.profile_no_reviews_desc') }}</p>
                            </div>
                        @else
                            <div class="flex flex-col gap-4">
                                @foreach($myReviews as $review)
                                    @php
                                        $rvName = data_get($review->product_snapshot, 'name');
                                        $rvType = $review->product_type === 'stationery' ? 'stationery' : 'book';
                                        $rvSlug = $rvName ? \Illuminate\Support\Str::slug($rvName) : 'mahsulot';
                                        $rvUrl = $review->product_id
                                            ? ($rvType === 'stationery'
                                                ? route('web.stationery.show', ['id' => $review->product_id, 'slug' => $rvSlug])
                                                : route('web.books.show', ['id' => $review->product_id, 'slug' => $rvSlug]))
                                            : null;
                                    @endphp
                                    <div class="p-5 border border-secondary-100 rounded-2xl bg-[#F8FAFC]">
                                        @if($rvUrl && $rvName)
                                            <a href="{{ $rvUrl }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full mb-3 no-underline">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                                {{ \Illuminate\Support\Str::limit($rvName, 40) }}
                                            </a>
                                        @endif

                                        @if(trim((string) $review->text) !== '')
                                            <p class="text-sm text-neutral-700 leading-relaxed mb-3">{{ $review->text }}</p>
                                        @endif

                                        @if($review->images->isNotEmpty())
                                            <div class="flex gap-2 overflow-x-auto mb-3">
                                                @foreach($review->images->take(4) as $img)
                                                    <img src="{{ asset('storage/' . $img->image) }}" alt="" loading="lazy" class="w-14 h-14 rounded-xl object-cover shrink-0">
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between text-xs text-neutral-400">
                                            <span>{{ optional($review->created_at)->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ====== INFO PANEL (PiyolaMarket 1:1) ====== -->
                <div id="kcProfilePanelInfo" class="kc-profile-panel">
                    <div class="bg-white p-6 md:p-8 rounded-3xl border border-secondary-100 shadow-sm flex flex-col gap-6">
                        <!-- Top Header -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-neutral-900">{{ __('marketplace.profile_info') }}</h3>
                            <button type="button" onclick="kcOpenEditProfileModal()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-secondary-100 hover:bg-secondary-200 text-xs font-semibold text-neutral-700 transition-colors border-none cursor-pointer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                <span>Tahrirlash</span>
                            </button>
                        </div>

                        <!-- 2-Column User Info Grid (Piyola 1:1) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">To'liq ism</div>
                                <div id="kcProfileInfoName" class="text-sm font-semibold text-neutral-900">{{ trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')) ?: 'Kiritilmagan' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">Tug'ilgan sana</div>
                                <div class="text-sm font-semibold text-neutral-900">Kiritilmagan</div>
                            </div>
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">Jins</div>
                                <div class="text-sm font-semibold text-neutral-900">Erkak</div>
                            </div>
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">Telefon raqam</div>
                                <div class="text-sm font-semibold text-neutral-900">+{{ $user->phone_number }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">Elektron pochta</div>
                                <div id="kcProfileInfoEmail" class="text-sm font-semibold text-neutral-900">{{ $user->email ?: 'Kiritilmagan' }}</div>
                            </div>
                        </div>

                        <!-- Section: Saqlangan manzillar (Piyola 1:1) -->
                        <div class="pt-6 border-t border-secondary-100">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-bold text-neutral-900">{{ __('marketplace.profile_addresses') }}</h4>
                                @if(count($locations) > 0)
                                    <button type="button" onclick="kcOpenAddressModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition-colors border-none cursor-pointer">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                                        <span>Yangi manzil</span>
                                    </button>
                                @endif
                            </div>

                            @if(count($locations) > 0)
                                <div class="flex flex-col gap-3" id="kcLocationsList">
                                    @foreach($locations as $loc)
                                        <div class="flex items-center justify-between p-4 border border-secondary-100 rounded-2xl bg-[#F8FAFC] gap-3">
                                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm font-semibold text-neutral-900 truncate">{{ $loc->fullAddress }}</div>
                                                    @if($loc->region_name)
                                                        <div class="text-xs text-neutral-400 mt-0.5">{{ $loc->region_name }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                @if($user->mainAddressID == $loc->id)
                                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold">Asosiy</span>
                                                @else
                                                    <button type="button" onclick="setMainLocation({{ $loc->id }})" class="px-2.5 py-1 rounded-lg bg-secondary-100 hover:bg-secondary-200 text-neutral-600 text-xs font-semibold border-none cursor-pointer transition-colors">
                                                        Asosiy qilish
                                                    </button>
                                                @endif
                                                @if($user->mainAddressID != $loc->id)
                                                    <button type="button" onclick="deleteLocation({{ $loc->id }})" class="p-2 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors border-none bg-transparent cursor-pointer" title="O'chirish">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14ZM10 11v6M14 11v6"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-[#F8FAFC] rounded-2xl p-8 text-center flex flex-col items-center justify-center">
                                    <div class="w-14 h-14 rounded-full bg-neutral-200/60 text-neutral-500 flex items-center justify-center mb-3">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </div>
                                    <div class="text-base font-bold text-neutral-900">Saqlangan manzillar mavjud emas</div>
                                    <div class="text-xs text-neutral-400 mt-1 mb-4">Yetkazib berish manzilini qo'shing</div>
                                    <button type="button" onclick="kcOpenAddressModal()" class="px-6 py-2.5 rounded-2xl bg-primary hover:bg-primary/90 text-white text-sm font-semibold border-none cursor-pointer transition-colors shadow-sm flex items-center gap-2">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                                        <span>Yangi manzil qo'shish</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ====== YANGI MANZIL QO'SHISH MODAL ====== -->
<div id="kcAddressModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-neutral-900 leading-tight">Yangi manzil qo'shish</h3>
                    <p class="text-xs text-neutral-400">Yetkazib berish manzilini belgilang</p>
                </div>
            </div>
            <button type="button" onclick="kcCloseAddressModal()" class="w-9 h-9 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-600 flex items-center justify-center border-none cursor-pointer transition-colors">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mode Switcher Tabs (Map vs Manual) -->
        <div class="px-5 pt-3">
            <div class="flex items-center gap-1.5 p-1 bg-neutral-100 rounded-2xl w-full">
                <button type="button" id="kcTabBtnMap" onclick="kcSwitchAddressMode('map')" class="flex-1 py-2 rounded-xl text-xs font-bold transition-all border-none cursor-pointer bg-white text-primary shadow-sm flex items-center justify-center gap-1.5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Xaritadan tanlash</span>
                </button>
                <button type="button" id="kcTabBtnManual" onclick="kcSwitchAddressMode('manual')" class="flex-1 py-2 rounded-xl text-xs font-medium transition-all border-none cursor-pointer bg-transparent text-neutral-600 hover:text-neutral-900 flex items-center justify-center gap-1.5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>Qo'lda kiritish</span>
                </button>
            </div>
        </div>

        <!-- Modal Body: Scrollable -->
        <div class="p-5 overflow-y-auto flex-1 flex flex-col gap-4">
            
            <!-- MODE 1: XARITA (Leaflet + OpenStreetMap) -->
            <div id="kcAddressMapContainer" class="flex flex-col gap-3">
                <div id="kcLocationStatusBanner" class="p-2.5 rounded-xl bg-amber-50 border border-amber-200/60 text-xs text-amber-800 flex items-center gap-2" style="display:none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span id="kcLocationStatusText">Joylashuv aniqlanmoqda...</span>
                </div>

                <div class="relative w-full h-64 sm:h-72 rounded-2xl overflow-hidden border border-secondary-200 bg-neutral-100">
                    <div id="kcLeafletMap" class="w-full h-full"></div>
                    <button type="button" onclick="kcDetectCurrentLocation()" class="absolute bottom-3 right-3 z-20 px-3 py-2 bg-white/95 backdrop-blur-xs text-neutral-800 text-xs font-bold rounded-xl shadow-md border border-neutral-200/80 hover:bg-white flex items-center gap-1.5 cursor-pointer">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><circle cx="12" cy="12" r="10"/><polygon points="12 2 15 8 12 14 9 8 12 2"/></svg>
                        <span>Mening joylashuvim</span>
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-600 mb-1">Tanlangan / Aniqlangan manzil</label>
                    <input type="text" id="kcMapSelectedAddress" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20" placeholder="Xaritada nuqtani bosing yoki manzilni yozing">
                </div>
            </div>

            <!-- MODE 2: QO'LDA KIRITISH (Smart Fallback Form) -->
            <div id="kcAddressManualContainer" class="flex flex-col gap-3" style="display:none;">
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Viloyat / Shahar <span class="text-red-500">*</span></label>
                    <select id="kcManualRegion" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20 cursor-pointer">
                        <option value="Toshkent shahri">Toshkent shahri</option>
                        <option value="Toshkent viloyati">Toshkent viloyati</option>
                        <option value="Andijon viloyati">Andijon viloyati</option>
                        <option value="Farg'ona viloyati">Farg'ona viloyati</option>
                        <option value="Namangan viloyati">Namangan viloyati</option>
                        <option value="Samarqand viloyati">Samarqand viloyati</option>
                        <option value="Buxoro viloyati">Buxoro viloyati</option>
                        <option value="Navoiy viloyati">Navoiy viloyati</option>
                        <option value="Qashqadaryo viloyati">Qashqadaryo viloyati</option>
                        <option value="Surxondaryo viloyati">Surxondaryo viloyati</option>
                        <option value="Jizzax viloyati">Jizzax viloyati</option>
                        <option value="Sirdaryo viloyati">Sirdaryo viloyati</option>
                        <option value="Xorazm viloyati">Xorazm viloyati</option>
                        <option value="Qoraqalpog'iston Respublikasi">Qoraqalpog'iston Respublikasi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Tuman / Shahar <span class="text-red-500">*</span></label>
                    <input type="text" id="kcManualDistrict" placeholder="Masalan: Chilonzor tumani yoki Samarqand sh." class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Ko'cha, uy, xonadon <span class="text-red-500">*</span></label>
                    <input type="text" id="kcManualStreet" placeholder="Masalan: Qatortol ko'chasi, 28-uy, 14-xonadon" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Mo'ljal (ixtiyoriy)</label>
                    <input type="text" id="kcManualLandmark" placeholder="Masalan: Rayhon restorani ro'parasida" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
                </div>
            </div>

        </div>

        <!-- Modal Footer Button -->
        <div class="p-5 border-t border-secondary-100 bg-[#FAFAFA]">
            <button type="button" id="kcBtnSaveAddress" onclick="kcSubmitAddress()" class="w-full h-13 bg-primary hover:bg-primary/90 text-white font-bold rounded-2xl flex items-center justify-center gap-2 text-base transition-all border-none cursor-pointer shadow-sm">
                <span>Manzilni saqlash</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</div>

<!-- ====== PROFIL MA'LUMOTLARINI TAHRIRLASH MODAL ====== -->
<div id="kcEditProfileModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="text-base font-bold text-neutral-900 leading-tight">Ma'lumotlarni tahrirlash</h3>
            <button type="button" onclick="kcCloseEditProfileModal()" class="w-9 h-9 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-600 flex items-center justify-center border-none cursor-pointer transition-colors">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 flex flex-col gap-4">
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-1">Ism <span class="text-red-500">*</span></label>
                <input type="text" id="kcEditName" value="{{ $user->name }}" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-1">Familiya</label>
                <input type="text" id="kcEditLastname" value="{{ $user->lastname }}" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-1">Elektron pochta</label>
                <input type="email" id="kcEditEmail" value="{{ $user->email }}" class="w-full px-4 py-3 bg-[#F8FAFC] border border-secondary-200 rounded-xl text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20">
            </div>
        </div>
        <div class="p-5 border-t border-secondary-100 bg-[#FAFAFA]">
            <button type="button" onclick="kcSubmitProfileEdit()" class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-2xl flex items-center justify-center text-sm transition-all border-none cursor-pointer">
                Saqlash
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
const KC_PROFILE_I18N = {
    confirmDeleteAddress: @json(__('marketplace.profile_confirm_delete_address')),
    genericError: @json(__('marketplace.generic_error')),
};

let kcCurrentAddressMode = 'map'; // 'map' | 'manual'
let kcLeafletMapInstance = null;
let kcLeafletMarker = null;
let kcSelectedCoords = { lat: 41.2995, lon: 69.2401 };

function kcProfileSwitchTab(tab) {
    const panels = { orders: 'kcProfilePanelOrders', reviews: 'kcProfilePanelReviews', info: 'kcProfilePanelInfo' };
    const buttons = { orders: 'kcProfileTabBtnOrders', reviews: 'kcProfileTabBtnReviews', info: 'kcProfileTabBtnInfo' };

    Object.keys(panels).forEach(key => {
        const panelEl = document.getElementById(panels[key]);
        const btnEl = document.getElementById(buttons[key]);
        if (panelEl) panelEl.style.display = key === tab ? 'block' : 'none';
        if (btnEl) {
            if (key === tab) {
                btnEl.className = 'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all text-left cursor-pointer border-none bg-[#F1F5F9] text-primary';
            } else {
                btnEl.className = 'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left cursor-pointer border-none bg-transparent text-neutral-600 hover:bg-neutral-50';
            }
        }
    });

    if (history.replaceState) {
        history.replaceState(null, '', tab === 'orders' ? '{{ route("web.profile") }}' : '{{ route("web.profile") }}#' + tab);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.replace('#', '');
    if (hash === 'orders' || hash === 'reviews') kcProfileSwitchTab(hash);
    else kcProfileSwitchTab('info');
});

// ====== ADDRESS MODAL & MAP LOGIC ======
function kcOpenAddressModal() {
    const modal = document.getElementById('kcAddressModal');
    if (modal) modal.style.display = 'flex';
    kcSwitchAddressMode('map');
    setTimeout(() => {
        kcInitLeafletMap();
        kcDetectCurrentLocation();
    }, 150);
}

function kcCloseAddressModal() {
    const modal = document.getElementById('kcAddressModal');
    if (modal) modal.style.display = 'none';
}

function kcSwitchAddressMode(mode) {
    kcCurrentAddressMode = mode;
    const mapBox = document.getElementById('kcAddressMapContainer');
    const manualBox = document.getElementById('kcAddressManualContainer');
    const btnMap = document.getElementById('kcTabBtnMap');
    const btnManual = document.getElementById('kcTabBtnManual');

    if (mode === 'map') {
        if (mapBox) mapBox.style.display = 'flex';
        if (manualBox) manualBox.style.display = 'none';
        btnMap.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all border-none cursor-pointer bg-white text-primary shadow-sm flex items-center justify-center gap-1.5';
        btnManual.className = 'flex-1 py-2 rounded-xl text-xs font-medium transition-all border-none cursor-pointer bg-transparent text-neutral-600 hover:text-neutral-900 flex items-center justify-center gap-1.5';
        if (kcLeafletMapInstance) {
            setTimeout(() => kcLeafletMapInstance.invalidateSize(), 100);
        }
    } else {
        if (mapBox) mapBox.style.display = 'none';
        if (manualBox) manualBox.style.display = 'flex';
        btnManual.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all border-none cursor-pointer bg-white text-primary shadow-sm flex items-center justify-center gap-1.5';
        btnMap.className = 'flex-1 py-2 rounded-xl text-xs font-medium transition-all border-none cursor-pointer bg-transparent text-neutral-600 hover:text-neutral-900 flex items-center justify-center gap-1.5';
    }
}

function kcInitLeafletMap() {
    const el = document.getElementById('kcLeafletMap');
    if (!el || typeof L === 'undefined') return;

    if (kcLeafletMapInstance) {
        kcLeafletMapInstance.invalidateSize();
        return;
    }

    // Default to Tashkent coordinates
    const defaultLat = 41.2995;
    const defaultLng = 69.2401;

    kcLeafletMapInstance = L.map('kcLeafletMap', {
        center: [defaultLat, defaultLng],
        zoom: 13,
        zoomControl: true
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(kcLeafletMapInstance);

    // Marker
    kcLeafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(kcLeafletMapInstance);

    kcLeafletMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        kcSetMapCoordinates(pos.lat, pos.lng);
    });

    kcLeafletMapInstance.on('click', function(e) {
        const pos = e.latlng;
        kcLeafletMarker.setLatLng(pos);
        kcSetMapCoordinates(pos.lat, pos.lng);
    });
}

function kcSetMapCoordinates(lat, lng) {
    kcSelectedCoords = { lat: lat, lon: lng };
    kcReverseGeocode(lat, lng);
}

function kcReverseGeocode(lat, lng) {
    const input = document.getElementById('kcMapSelectedAddress');
    if (!input) return;

    // Fetch from OpenStreetMap Nominatim
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=uz,ru,en`)
        .then(r => r.json())
        .then(data => {
            if (data && data.display_name) {
                input.value = data.display_name;
            } else {
                input.value = `Koordinata: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }
        })
        .catch(() => {
            input.value = `Koordinata: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        });
}

function kcDetectCurrentLocation() {
    const banner = document.getElementById('kcLocationStatusBanner');
    const statusText = document.getElementById('kcLocationStatusText');

    if (!navigator.geolocation) {
        if (banner) {
            banner.style.display = 'flex';
            statusText.textContent = "Brauzeringiz joylashuvni aniqlashni qo'llab-quvvatlamaydi. Xaritadan tanlang yoki qo'lda kiriting.";
        }
        return;
    }

    if (banner) {
        banner.style.display = 'flex';
        statusText.textContent = "Joylashuvingiz aniqlanmoqda...";
    }

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            kcSelectedCoords = { lat: lat, lon: lng };

            if (banner) {
                banner.className = 'p-2.5 rounded-xl bg-emerald-50 border border-emerald-200/60 text-xs text-emerald-800 flex items-center gap-2';
                statusText.textContent = "📍 Joylashuvingiz aniqlandi! Zarur bo'lsa xaritani bosing.";
            }

            if (kcLeafletMapInstance && kcLeafletMarker) {
                kcLeafletMapInstance.setView([lat, lng], 16);
                kcLeafletMarker.setLatLng([lat, lng]);
                kcReverseGeocode(lat, lng);
            }
        },
        function (err) {
            if (banner) {
                banner.className = 'p-2.5 rounded-xl bg-amber-50 border border-amber-200/60 text-xs text-amber-800 flex items-center gap-2';
                statusText.textContent = "⚠️ Joylashuvga ruxsat berilmadi. Xaritadan nuqtani bosing yoki yuqoridagi 'Qo'lda kiritish' tabini tanlang.";
            }
        },
        { timeout: 8000, enableHighAccuracy: true }
    );
}

function kcSubmitAddress() {
    let payload = {};
    const btn = document.getElementById('kcBtnSaveAddress');

    if (kcCurrentAddressMode === 'map') {
        const address = (document.getElementById('kcMapSelectedAddress').value || '').trim();
        if (!address) {
            alert("Iltimos, xaritadan manzilni belgilang yoki yozing");
            return;
        }
        payload = {
            lat: kcSelectedCoords.lat,
            lon: kcSelectedCoords.lon,
            fullAddress: address,
        };
    } else {
        const region = document.getElementById('kcManualRegion').value;
        const district = (document.getElementById('kcManualDistrict').value || '').trim();
        const street = (document.getElementById('kcManualStreet').value || '').trim();
        const landmark = (document.getElementById('kcManualLandmark').value || '').trim();

        if (!district || !street) {
            alert("Iltimos, Tuman va Ko'cha/uy maydonlarini to'ldiring");
            return;
        }

        let full = `${region}, ${district}, ${street}`;
        if (landmark) full += ` (Mo'ljal: ${landmark})`;

        payload = {
            region_name: region,
            district_name: district,
            fullAddress: full,
        };
    }

    if (btn) {
        btn.disabled = true;
        btn.textContent = "Saqlanmoqda...";
    }

    fetch("{{ route('web.profile.location.add') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            kcCloseAddressModal();
            window.location.reload();
        } else {
            alert(data.message || KC_PROFILE_I18N.genericError);
            if (btn) {
                btn.disabled = false;
                btn.textContent = "Manzilni saqlash";
            }
        }
    })
    .catch(err => {
        console.error(err);
        alert(KC_PROFILE_I18N.genericError);
        if (btn) {
            btn.disabled = false;
            btn.textContent = "Manzilni saqlash";
        }
    });
}

function deleteLocation(id) {
    if(!confirm(KC_PROFILE_I18N.confirmDeleteAddress)) return;
    fetch(`/profile/location/${id}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') window.location.reload();
        else alert(data.message || KC_PROFILE_I18N.genericError);
    });
}

function setMainLocation(id) {
    fetch(`/profile/location/${id}/main`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') window.location.reload();
        else alert(data.message || KC_PROFILE_I18N.genericError);
    });
}

// ====== EDIT PROFILE MODAL ======
function kcOpenEditProfileModal() {
    const modal = document.getElementById('kcEditProfileModal');
    if (modal) modal.style.display = 'flex';
}

function kcCloseEditProfileModal() {
    const modal = document.getElementById('kcEditProfileModal');
    if (modal) modal.style.display = 'none';
}

function kcSubmitProfileEdit() {
    const name = (document.getElementById('kcEditName').value || '').trim();
    const lastname = (document.getElementById('kcEditLastname').value || '').trim();
    const email = (document.getElementById('kcEditEmail').value || '').trim();

    if (!name) {
        alert("Ism kiritilishi shart");
        return;
    }

    fetch("{{ route('web.profile.update') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ name, lastname, email })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            kcCloseEditProfileModal();
            const fullName = `${name} ${lastname}`.trim();
            const nameEl = document.getElementById('kcProfileInfoName');
            const sideNameEl = document.getElementById('kcProfileSidebarName');
            const emailEl = document.getElementById('kcProfileInfoEmail');
            if (nameEl) nameEl.textContent = fullName;
            if (sideNameEl) sideNameEl.textContent = name;
            if (emailEl) emailEl.textContent = email || 'Kiritilmagan';
            if (typeof kcShowToast === 'function') {
                kcShowToast("Ma'lumotlar muvaffaqiyatli yangilandi", "success");
            }
        } else {
            alert(data.message || KC_PROFILE_I18N.genericError);
        }
    })
    .catch(err => console.error(err));
}
</script>
@endpush
