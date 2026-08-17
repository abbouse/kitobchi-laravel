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
<div class="kc-page-surface" style="min-height:80dvh;padding:2rem 0;background:#f8fafc;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        <div class="flex items-center gap-2 mb-5">
            <a href="{{ route('web.catalog') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300" title="{{ __('marketplace.back_to_catalog') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
            <nav aria-label="breadcrumb" class="relative min-w-0">
                <ol class="flex items-center gap-2">
                    <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                        <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_home') }}</a>
                    </li>
                    <li class="flex text-gray text-xs">/</li>
                    <li class="flex min-w-0 text-[#8F8FA1] text-sm font-semibold">{{ __('marketplace.profile_title') }}</li>
                </ol>
            </nav>
        </div>

        <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;" id="kcProfileGrid">
            <!-- Sidebar: user card + tab navigation (piyolamarket.uz uslubida) -->
            <div class="kc-profile-sidebar">
                <div class="kc-profile-sidebar__user">
                    <div class="kc-profile-avatar">{{ strtoupper(substr($user->name ?: $user->phone_number, 0, 1)) }}</div>
                    <div>
                        <h2>{{ $user->name ?: __('marketplace.profile_user') }}</h2>
                        <p>+{{ $user->phone_number }}</p>
                    </div>
                </div>

                <nav class="kc-profile-nav">
                    <button type="button" id="kcProfileTabBtnOrders" onclick="kcProfileSwitchTab('orders')" class="kc-profile-nav__item kc-profile-nav__item--active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        {{ __('marketplace.profile_orders') }}
                    </button>
                    <button type="button" id="kcProfileTabBtnReviews" onclick="kcProfileSwitchTab('reviews')" class="kc-profile-nav__item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        {{ __('marketplace.profile_reviews') }}
                    </button>
                    <button type="button" id="kcProfileTabBtnInfo" onclick="kcProfileSwitchTab('info')" class="kc-profile-nav__item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/><path d="M6 12h4"/></svg>
                        {{ __('marketplace.profile_info') }}
                    </button>
                    <form action="{{ route('web.auth.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="kc-profile-nav__item kc-profile-nav__item--danger">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            {{ __('marketplace.profile_logout') }}
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Right column: tab panels -->
            <div style="min-width:0;">

                <!-- ====== ORDERS PANEL (default) ====== -->
                <div id="kcProfilePanelOrders" class="kc-profile-panel">
                    <div class="kc-panel-card">
                        <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;">
                            {{ __('marketplace.profile_orders_title') }} ({{ count($orders) }})
                        </h3>

                        @if(count($orders) === 0)
                            <div style="text-align:center;padding:3rem 1rem;">
                                <div class="kc-icon-empty" aria-hidden="true">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                </div>
                                <h4 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">{{ __('marketplace.profile_no_orders') }}</h4>
                                <p style="color:#64748b;font-size:0.875rem;margin:0 0 1.5rem;">{{ __('marketplace.profile_no_orders_desc') }}</p>
                                <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                                    {{ __('marketplace.go_to_catalog') }}
                                </a>
                            </div>
                        @else
                            <div style="display:flex;flex-direction:column;gap:1rem;">
                                @foreach($orders as $order)
                                    <div style="padding:1.25rem;border:1px solid #f1f5f9;border-radius:0.875rem;background:#fafafa;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
                                            <div>
                                                <span style="font-weight:800;color:#0f172a;">{{ __('marketplace.profile_order_number', ['number' => $order->order_number ?? $order->id]) }}</span>
                                                <span style="color:#64748b;font-size:0.8125rem;margin-left:0.5rem;">
                                                    {{ optional($order->created_at)->format('d.m.Y, H:i') }}
                                                </span>
                                            </div>
                                            <span style="padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:700;
                                                @if($order->paymentStatus == 2) background:#dcfce7;color:#15803d;
                                                @elseif($order->paymentStatus == 1) background:#fef9c3;color:#a16207;
                                                @else background:#f1f5f9;color:#475569; @endif">
                                                @if($order->paymentStatus == 2) {{ __('marketplace.profile_status_paid') }}
                                                @elseif($order->paymentStatus == 1) {{ __('marketplace.profile_status_pending') }}
                                                @else {{ __('marketplace.profile_status_accepted') }} @endif
                                            </span>
                                        </div>

                                        <div style="font-size:0.875rem;color:#334155;margin-bottom:0.75rem;">
                                            <strong>{{ __('marketplace.profile_address') }}</strong> {{ $order->address ?? $order->city ?? __('marketplace.profile_address_unset') }}
                                        </div>

                                        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:0.75rem;">
                                            <span style="font-size:0.875rem;color:#64748b;">{{ __('marketplace.profile_total') }}</span>
                                            <span style="font-size:1.125rem;font-weight:800;color:var(--color-tima-500);">
                                                {{ number_format($order->summa ?? $order->price ?? 0, 0, ',', ' ') }} {{ __('marketplace.currency') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ====== SHARHLARIM PANEL (BookClub postlari — piyolamarket'dagi
                     "Sharhlarim" bo'limi bilan bir xil g'oya) ====== -->
                <div id="kcProfilePanelReviews" class="kc-profile-panel" style="display:none;">
                    <div class="kc-panel-card">
                        <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;">
                            {{ __('marketplace.profile_reviews') }} ({{ count($myReviews ?? []) }})
                        </h3>

                        @if(count($myReviews ?? []) === 0)
                            <div style="text-align:center;padding:3rem 1rem;">
                                <div class="kc-icon-empty" aria-hidden="true">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                    </svg>
                                </div>
                                <h4 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">{{ __('marketplace.profile_no_reviews') }}</h4>
                                <p style="color:#64748b;font-size:0.875rem;margin:0;">{{ __('marketplace.profile_no_reviews_desc') }}</p>
                            </div>
                        @else
                            <div style="display:flex;flex-direction:column;gap:1rem;">
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
                                    <div class="kc-my-review-card">
                                        @if($rvUrl && $rvName)
                                            <a href="{{ $rvUrl }}" class="kc-my-review-product">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                                {{ \Illuminate\Support\Str::limit($rvName, 40) }}
                                            </a>
                                        @endif

                                        @if(trim((string) $review->text) !== '')
                                            <p style="font-size:0.875rem;line-height:1.6;color:#374151;margin:0 0 0.625rem;">{{ $review->text }}</p>
                                        @endif

                                        @if($review->images->isNotEmpty())
                                            <div style="display:flex;gap:0.375rem;overflow-x:auto;margin-bottom:0.625rem;">
                                                @foreach($review->images->take(4) as $img)
                                                    <img src="{{ asset('storage/' . $img->image) }}" alt="" loading="lazy" style="width:3.5rem;height:3.5rem;border-radius:0.625rem;object-fit:cover;flex-shrink:0;">
                                                @endforeach
                                            </div>
                                        @endif

                                        <div style="display:flex;align-items:center;justify-content:space-between;">
                                            <span style="font-size:0.75rem;color:#94a3b8;">{{ optional($review->created_at)->translatedFormat('d M Y') }}</span>
                                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                                @if(($review->likes_count ?? 0) > 0)
                                                    <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:600;color:#ef4444;">
                                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                                                        {{ __('marketplace.reviews_helpful', ['count' => $review->likes_count]) }}
                                                    </span>
                                                @endif
                                                @if(($review->comments_count ?? 0) > 0)
                                                    <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:600;color:#9ca3af;">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.97-4.03 9-9 9-1.5 0-2.91-.37-4.15-1.02L3 21l1.05-3.16A8.96 8.96 0 013 12c0-4.97 4.03-9 9-9s9 4.03 9 9z"/></svg>
                                                        {{ __('marketplace.reviews_comments_count', ['count' => $review->comments_count]) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ====== INFO PANEL (ma'lumotlar + manzillar) ====== -->
                <div id="kcProfilePanelInfo" class="kc-profile-panel" style="display:none;">
                    <div class="kc-panel-card" style="margin-bottom:1.5rem;">
                        <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;">{{ __('marketplace.profile_info') }}</h3>
                        <div class="kc-profile-info-grid">
                            <div class="kc-profile-info-item">
                                <span class="kc-profile-info-label">{{ __('marketplace.profile_full_name') }}</span>
                                <span class="kc-profile-info-value">{{ $user->name ?: __('marketplace.profile_not_entered') }}</span>
                            </div>
                            <div class="kc-profile-info-item">
                                <span class="kc-profile-info-label">{{ __('marketplace.profile_phone') }}</span>
                                <span class="kc-profile-info-value">+{{ $user->phone_number }}</span>
                            </div>
                            @if(!empty($user->email))
                                <div class="kc-profile-info-item">
                                    <span class="kc-profile-info-label">Email</span>
                                    <span class="kc-profile-info-value">{{ $user->email }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="kc-panel-card">
                        <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                            {{ __('marketplace.profile_addresses') }}
                        </h3>

                        @if(count($locations) > 0)
                            <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.5rem;">
                                @foreach($locations as $loc)
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:1px solid {{ $user->mainAddressID == $loc->id ? 'var(--color-tima-500)' : '#e2e8f0' }};border-radius:0.75rem;background:{{ $user->mainAddressID == $loc->id ? 'var(--color-tima-50)' : '#fff' }};">
                                        <div style="display:flex;align-items:center;gap:0.75rem;flex:1;">
                                            <svg width="20" height="20" fill="none" stroke="{{ $user->mainAddressID == $loc->id ? 'var(--color-tima-500)' : '#94a3b8' }}" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <div style="font-size:0.875rem;color:#334155;">{{ $loc->fullAddress }}</div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.5rem;">
                                            @if($user->mainAddressID != $loc->id)
                                                <button onclick="setMainLocation({{ $loc->id }})" style="padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:600;color:var(--color-tima-600);background:var(--color-tima-50);border:none;border-radius:0.375rem;cursor:pointer;">{{ __('marketplace.profile_set_main') }}</button>
                                                <button onclick="deleteLocation({{ $loc->id }})" style="padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:600;color:#ef4444;background:#fef2f2;border:none;border-radius:0.375rem;cursor:pointer;">{{ __('marketplace.profile_delete') }}</button>
                                            @else
                                                <span style="font-size:0.75rem;font-weight:700;color:var(--color-tima-500);background:var(--color-tima-100);padding:0.25rem 0.5rem;border-radius:9999px;">{{ __('marketplace.profile_main') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- YANGI: avval manzil bo'lmaganda bu yerda hech narsa
                                 chiqmasdi, to'g'ridan-to'g'ri pastdagi "yangi manzil
                                 qo'shish" formasiga o'tib ketardi — piyolamarket'dagi
                                 kabi endi chiroyli bo'sh holat ko'rsatiladi. --}}
                            <div style="text-align:center;padding:2rem 1rem;margin-bottom:1.5rem;background:#f8fafc;border-radius:0.875rem;">
                                <div class="kc-icon-empty" aria-hidden="true" style="margin-left:auto;margin-right:auto;">
                                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <h4 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 0.375rem;">{{ __('marketplace.profile_addresses_empty_title') }}</h4>
                                <p style="color:#64748b;font-size:0.8125rem;margin:0;">{{ __('marketplace.profile_addresses_empty_desc') }}</p>
                            </div>
                        @endif

                        <!-- Add New Location Form -->
                        <div style="border:1px dashed #cbd5e1;border-radius:0.75rem;padding:1.25rem;background:#f8fafc;">
                            <h4 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1rem;">{{ __('marketplace.profile_add_address') }}</h4>
                            <div id="yandex-map" style="width:100%;height:300px;border-radius:0.5rem;background:#e2e8f0;margin-bottom:1rem;overflow:hidden;"></div>
                            <form id="newLocationForm" style="display:flex;flex-direction:column;gap:0.75rem;margin:0;">
                                <input type="hidden" id="locLat" name="lat">
                                <input type="hidden" id="locLon" name="lon">
                                <div>
                                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#475569;margin-bottom:0.25rem;">{{ __('marketplace.profile_address_full_name_label') }}</label>
                                    <input type="text" id="locAddress" name="fullAddress" required
                                        style="width:100%;padding:0.75rem 1rem;border:1px solid #cbd5e1;border-radius:0.5rem;font-size:0.875rem;outline:none;"
                                        placeholder="{{ __('marketplace.profile_address_placeholder') }}">
                                </div>
                                <button type="submit" class="kc-primary-btn" style="width:100%;padding:0.75rem;font-size:0.875rem;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;gap:0.5rem;">
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
@endsection

@push('scripts')
<script>
const KC_PROFILE_I18N = {
    confirmDeleteAddress: @json(__('marketplace.profile_confirm_delete_address')),
    mapSelectAlert: @json(__('marketplace.profile_map_select_alert')),
    genericError: @json(__('marketplace.generic_error')),
};

// ── Tab switching (Buyurtmalarim / Sharhlarim / Ma'lumotlarim) — sahifa
// qayta yuklanmaydi, faqat tanlangan panel ko'rsatiladi (piyolamarket.uz'dagi
// kabi chap navigatsiya bilan o'ng panel almashadi).
function kcProfileSwitchTab(tab) {
    const panels = { orders: 'kcProfilePanelOrders', reviews: 'kcProfilePanelReviews', info: 'kcProfilePanelInfo' };
    const buttons = { orders: 'kcProfileTabBtnOrders', reviews: 'kcProfileTabBtnReviews', info: 'kcProfileTabBtnInfo' };

    Object.keys(panels).forEach(key => {
        const panelEl = document.getElementById(panels[key]);
        const btnEl = document.getElementById(buttons[key]);
        if (panelEl) panelEl.style.display = key === tab ? 'block' : 'none';
        if (btnEl) btnEl.classList.toggle('kc-profile-nav__item--active', key === tab);
    });

    if (history.replaceState) {
        history.replaceState(null, '', tab === 'orders' ? '{{ route("web.profile") }}' : '{{ route("web.profile") }}#' + tab);
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.replace('#', '');
    if (hash === 'info' || hash === 'reviews') kcProfileSwitchTab(hash);
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
