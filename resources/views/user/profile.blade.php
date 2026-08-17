@extends('layouts.marketplace')

@section('title', __('marketplace.profile_title') . ' — Kitobchi')

{{--
    QAYTA QURILDI: piyolamarket.uz'ning /profile sahifasidagi kabi — chapda
    doim ko'rinadigan navigatsiya kartasi (Buyurtmalarim / Sharhlarim /
    Ma'lumotlarim / Hisobdan chiqish), o'ngda esa tanlangan bo'limning
    paneli. Avval bu sahifada HAMMASI bir vaqtda (manzillar + buyurtmalar)
    pastma-past ko'rsatilardi va eski "popcorn" inline-style'lar (qattiq
    #hex ranglar) ishlatilgan edi — endi qolgan marketplace sahifalari
    bilan bir xil dizayn tokenlaridan (--color-tima-*, bg-secondary-*,
    text-primary va h.k.) foydalanadi va tarjima qilingan.

    Yo'naltirilgan (server-rendered) URL bir xil qoladi (/profile) — bo'limlar
    orasidagi almashish sahifani qayta yuklamasdan, faqat JS orqali (hidden/
    visible) amalga oshadi, shu bilan controller/route o'zgartirilmadi.

    YANGI (bu round): piyolamarket'da uchinchi bo'lim — "Sharhlarim" — bor
    edi, bizda yo'q edi. Kitobchida "sharh" tushunchasi BookClub postlari
    orqali ifodalanadi (product sahifasidagi "Xaridorlar sharhlari" bo'limi
    bilan BIR XIL manba — WebAuthController::profile() endi joriy
    foydalanuvchining o'z postlarini $myReviews orqali uzatadi). Shuningdek
    manzillar bo'limida, ro'yxat bo'sh bo'lganda ENDI chiroyli bo'sh holat
    (icon + sarlavha + izoh) ko'rsatiladi — avval bu holatda hech narsa
    chiqmay, to'g'ridan-to'g'ri "yangi manzil qo'shish" formasiga o'tib
    ketardi.

    MUHIM TUZATISH: pastdagi Yandex Maps skriptida haqiqiy XATO bor edi —
    `ymaps.ready(initYandexMap)` chaqiruvi shu <script> blokining ICHIDA,
    Yandex API'ning haqiqiy <script src="https://api-maps.yandex.ru/...">
    tegidan OLDIN turardi (blade fayl oxirida edi) — ya'ni sahifa yuklanganda
    `ymaps` obyekti hali mavjud bo'lmagan paytda chaqirilardi va
    "ymaps is not defined" xatosi bilan bu <script> blokining QOLGAN QISMI
    (jumladan "Yangi manzil qo'shish" formasining submit handleri!) umuman
    ishga tushmay qolardi. Hozircha YANDEX_MAPS_API_KEY bo'sh bo'lgani uchun
    bu xato ko'rinmayapti (butun blok shartli @if orqali chiqarilmaydi), lekin
    kalit qo'shilishi bilan zudlik bilan namoyon bo'lardi. Endi to'g'ri: xarita
    ishga tushirish faqat API skripti YUKLANIB BO'LGANDAN keyin (script
    tegining o'zidagi onload="ymaps.ready(initYandexMap)" orqali) chaqiriladi.
--}}

@push('styles')
<style>
    @media (min-width: 900px) {
        #kcProfileGrid { grid-template-columns: 280px 1fr; }
        #kcProfileGrid > :first-child { position: sticky; top: 1.5rem; align-self: start; }
    }

    .kc-profile-sidebar { background: #fff; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
    .kc-profile-sidebar__user { display: flex; align-items: center; gap: 0.875rem; margin-bottom: 1.25rem; }
    .kc-profile-avatar {
        width: 3.5rem; height: 3.5rem; border-radius: 9999px; flex-shrink: 0;
        background: var(--color-tima-100); color: var(--color-tima-600);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.375rem; font-weight: 800;
    }
    .kc-profile-sidebar__user h2 { font-size: 1.0625rem; font-weight: 800; color: #0f172a; margin: 0 0 0.125rem; }
    .kc-profile-sidebar__user p { color: #64748b; font-size: 0.8125rem; margin: 0; }
    .kc-profile-nav { display: flex; flex-direction: column; gap: 2px; border-top: 1px solid #f1f5f9; padding-top: 1rem; }
    .kc-profile-nav__item {
        display: flex; align-items: center; gap: 0.75rem;
        width: 100%; padding: 0.75rem 0.875rem; border-radius: 0.75rem;
        border: none; background: transparent; cursor: pointer; text-align: left;
        font-family: inherit; font-size: 0.9375rem; font-weight: 600; color: #475569;
        transition: background 0.15s, color 0.15s;
    }
    .kc-profile-nav__item:hover { background: #f8fafc; }
    .kc-profile-nav__item--active { background: var(--color-tima-500); color: #fff; }
    .kc-profile-nav__item--active:hover { background: var(--color-tima-500); }
    .kc-profile-nav__item--danger { color: #ef4444; }
    .kc-profile-nav__item--danger:hover { background: #fef2f2; }

    .kc-profile-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
    .kc-profile-info-item .kc-profile-info-label { display: block; font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.25rem; }
    .kc-profile-info-item .kc-profile-info-value { font-size: 0.9375rem; font-weight: 700; color: #0f172a; }

    /* Sharhlarim — BookClub postlari ro'yxati (product sahifasidagi sharh
       kartasi bilan bir xil ruh, lekin bu yerda o'z ichiga qaysi mahsulot
       haqida ekanini ko'rsatuvchi kichik havola ham qo'shiladi). */
    .kc-my-review-card { padding: 1.25rem; border: 1px solid #f1f5f9; border-radius: 0.875rem; background: #fafafa; }
    .kc-my-review-product {
        display: inline-flex; align-items: center; gap: 0.375rem; margin-bottom: 0.625rem;
        font-size: 0.75rem; font-weight: 700; color: var(--color-tima-600);
        background: var(--color-tima-50); padding: 0.25rem 0.625rem; border-radius: 9999px; text-decoration: none;
    }
</style>
@endpush

@section('content')
<div class="py-6 min-h-dvh">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">

        <!-- ====== BREADCRUMBS ====== -->
        <div class="flex items-center gap-2 mb-6">
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
            <!-- Sidebar: user card + tab navigation (piyolamarket.uz uslubida) -->
            <div class="bg-white rounded-3xl p-5 border border-secondary-100 shadow-sm sticky top-24 flex flex-col gap-4">
                <div class="flex items-center gap-3 pb-4 border-b border-secondary-100">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0">
                        {{ strtoupper(substr($user->name ?: $user->phone_number, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-base font-bold text-neutral-900 truncate leading-snug">{{ $user->name ?: __('marketplace.profile_user') }}</div>
                        <div class="text-xs text-neutral-500 truncate mt-0.5">+{{ $user->phone_number }}</div>
                    </div>
                </div>

                <nav class="flex flex-col gap-1">
                    <button type="button" id="kcProfileTabBtnOrders" onclick="kcProfileSwitchTab('orders')" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all text-left cursor-pointer border-none bg-transparent text-neutral-600 hover:bg-neutral-50">
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
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-secondary-100 hover:bg-secondary-200 text-xs font-semibold text-neutral-700 transition-colors border-none cursor-pointer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                <span>Tahrirlash</span>
                            </button>
                        </div>

                        <!-- 2-Column User Info Grid (Piyola 1:1) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <div class="text-xs text-neutral-400 mb-1">To'liq ism</div>
                                <div class="text-sm font-semibold text-neutral-900">{{ $user->name ?: 'Kiritilmagan' }}</div>
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
                                <div class="text-sm font-semibold text-neutral-900">{{ $user->email ?: 'Kiritilmagan' }}</div>
                            </div>
                        </div>

                        <!-- Section: Saqlangan manzillar (Piyola 1:1) -->
                        <div class="pt-4 border-t border-secondary-100">
                            <h4 class="text-base font-bold text-neutral-900 mb-4">{{ __('marketplace.profile_addresses') }}</h4>

                            @if(count($locations) > 0)
                                <div class="flex flex-col gap-3 mb-6">
                                    @foreach($locations as $loc)
                                        <div class="flex items-center justify-between p-4 border border-secondary-100 rounded-2xl bg-[#F8FAFC]">
                                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-neutral-400 shrink-0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                <div class="text-sm text-neutral-800 truncate">{{ $loc->fullAddress }}</div>
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
                                    <div class="text-xs text-neutral-400 mt-1">Yetkazib berish manzilini qo'shing</div>
                                    <button type="button" onclick="document.getElementById('kcAddressAddBox').classList.toggle('hidden')" class="mt-4 px-6 py-2.5 rounded-2xl bg-primary hover:bg-primary/90 text-white text-sm font-semibold border-none cursor-pointer transition-colors">
                                        Yangi manzil qo'shish
                                    </button>
                                </div>
                            @endif

                            <!-- Add New Location Form (collapsible) -->
                            <div id="kcAddressAddBox" class="mt-4 border border-dashed border-secondary-200 rounded-2xl p-5 bg-[#F8FAFC] hidden">
                                <h5 class="text-sm font-bold text-neutral-900 mb-3">{{ __('marketplace.profile_add_address') }}</h5>
                                <div id="yandex-map" class="w-full h-64 rounded-xl bg-neutral-200 mb-3 overflow-hidden"></div>
                                <form id="newLocationForm" class="flex flex-col gap-3 m-0">
                                    <input type="hidden" id="locLat" name="lat">
                                    <input type="hidden" id="locLon" name="lon">
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-600 mb-1">{{ __('marketplace.profile_address_full_name_label') }}</label>
                                        <input type="text" id="locAddress" name="fullAddress" required
                                            class="w-full px-4 py-2.5 border border-secondary-200 rounded-xl text-sm outline-none focus:ring-2 ring-primary/20 bg-white"
                                            placeholder="{{ __('marketplace.profile_address_placeholder') }}">
                                    </div>
                                    <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-semibold text-sm rounded-xl border-none cursor-pointer transition-colors">
                                        {{ __('marketplace.profile_save_address') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const KC_PROFILE_I18N = {
    confirmDeleteAddress: @json(__('marketplace.profile_confirm_delete_address')),
    mapSelectAlert: @json(__('marketplace.profile_map_select_alert')),
    genericError: @json(__('marketplace.generic_error')),
};

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
    else kcProfileSwitchTab('info'); // default to info as in Piyola
});

// Yandex Maps Logic
@if(config('services.yandex_maps.key'))
function initYandexMap() {
    const map = new ymaps.Map("yandex-map", {
        center: [41.311081, 69.240562], // Tashkent
        zoom: 12,
        controls: ['zoomControl', 'searchControl']
    });

    let placemark = null;

    map.events.add('click', function (e) {
        const coords = e.get('coords');
        updatePlacemark(coords);
    });

    function updatePlacemark(coords) {
        document.getElementById('locLat').value = coords[0];
        document.getElementById('locLon').value = coords[1];

        if (placemark) {
            placemark.geometry.setCoordinates(coords);
        } else {
            placemark = new ymaps.Placemark(coords, {}, { preset: 'islands#redDotIcon', draggable: true });
            map.geoObjects.add(placemark);
            placemark.events.add('dragend', function () {
                updatePlacemark(placemark.geometry.getCoordinates());
            });
        }

        // Reverse geocoding
        ymaps.geocode(coords).then(function (res) {
            const firstGeoObject = res.geoObjects.get(0);
            if (firstGeoObject) {
                document.getElementById('locAddress').value = firstGeoObject.getAddressLine();
            }
        });
    }
}
@endif

// Location AJAX Actions
document.getElementById('newLocationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const lat = document.getElementById('locLat').value;
    const lon = document.getElementById('locLon').value;
    const address = document.getElementById('locAddress').value;

    if(!lat || !lon) {
        alert(KC_PROFILE_I18N.mapSelectAlert);
        return;
    }

    fetch("{{ route('web.profile.location.add') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ lat: lat, lon: lon, fullAddress: address })
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message || KC_PROFILE_I18N.genericError);
        }
    })
    .catch(err => console.error(err));
});

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
</script>
@if(config('services.yandex_maps.key'))
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.key') }}&lang={{ config('services.yandex_maps.lang', 'ru_RU') }}" onload="ymaps.ready(initYandexMap);"></script>
@endif
@endpush
