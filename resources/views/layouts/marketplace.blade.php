<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kitobchi — Online kitoblar va kanselyariya marketpleysi')</title>

    @stack('meta')

    <!-- Preconnect fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">

    <!-- Iconify for icon rendering -->
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>

    <!-- Kitobchi Entry CSS -->
    @vite(['resources/css/kitobchi-entry.css', 'resources/css/kitobchi-marketplace.css'])

    <!-- Marketplace CSS Variables -->
    <style id="kc-colors">
        /* MUHIM: bu qiymatlar piyolamarket.uz'ning haqiqiy, brauzerdan
           olingan CSS'idan (website_example/css/entry.*.css) chiqarilgan —
           taxminiy emas. --color-tima-500 = #0b0342 ularning asosiy brend
           rangi (to'q ko'k-siyoh navy), oldingi oklch(...290) binafsha
           rang xato/uydirma edi. */
        @layer theme {
            :root, :host {
                --ui-color-primary-50: var(--color-tima-50);
                --ui-color-primary-100: var(--color-tima-100);
                --ui-color-primary-200: var(--color-tima-200);
                --ui-color-primary-300: var(--color-tima-300);
                --ui-color-primary-400: var(--color-tima-400);
                --ui-color-primary-500: var(--color-tima-500);
                --ui-color-primary-600: var(--color-tima-600);
                --ui-color-primary-700: var(--color-tima-700);
                --ui-color-primary-800: var(--color-tima-800);
                --ui-color-primary-900: var(--color-tima-900);
                --ui-color-primary-950: var(--color-tima-950);
                --color-tima-50: #f5f6f8;
                --color-tima-100: #e8eaef;
                --color-tima-200: #d4d8e1;
                --color-tima-300: #b5bcc9;
                --color-tima-400: #8e98ac;
                --color-tima-500: #0b0342;
                --color-tima-600: #090338;
                --color-tima-700: #08022f;
                --color-tima-800: #060226;
                --color-tima-900: #05011f;
                --color-tima-950: #030116;
                --ui-color-secondary-50: oklch(97% 0.014 254.604);
                --ui-color-secondary-100: oklch(93.2% 0.032 255.585);
                --ui-color-secondary-200: oklch(88.2% 0.059 254.128);
                --ui-color-secondary-300: oklch(80.9% 0.105 251.813);
                --ui-color-secondary-400: oklch(70.7% 0.165 254.624);
                --ui-color-secondary-500: oklch(62.3% 0.214 259.815);
                --ui-color-secondary-600: oklch(54.6% 0.245 262.881);
                --ui-color-secondary-700: oklch(48.8% 0.243 264.376);
                --ui-color-secondary-800: oklch(42.4% 0.199 265.638);
                --ui-color-secondary-900: oklch(37.9% 0.146 265.522);
                --ui-color-secondary-950: oklch(28.2% 0.091 267.935);
                --ui-color-success-50: oklch(98.2% 0.018 155.826);
                --ui-color-success-100: oklch(96.2% 0.044 156.743);
                --ui-color-success-200: oklch(92.5% 0.084 155.995);
                --ui-color-success-300: oklch(87.1% 0.15 154.449);
                --ui-color-success-400: oklch(79.2% 0.209 151.711);
                --ui-color-success-500: oklch(72.3% 0.219 149.579);
                --ui-color-success-600: oklch(62.7% 0.194 149.214);
                --ui-color-success-700: oklch(52.7% 0.154 150.069);
                --ui-color-success-800: oklch(44.8% 0.119 151.328);
                --ui-color-success-900: oklch(39.3% 0.095 152.535);
                --ui-color-success-950: oklch(26.6% 0.065 152.934);
                --ui-color-neutral-50: oklch(98.4% 0.003 247.858);
                --ui-color-neutral-100: oklch(96.8% 0.007 247.896);
                --ui-color-neutral-200: oklch(92.9% 0.013 255.508);
                --ui-color-neutral-300: oklch(86.9% 0.022 252.894);
                --ui-color-neutral-400: oklch(70.4% 0.04 256.788);
                --ui-color-neutral-500: oklch(55.4% 0.046 257.417);
                --ui-color-neutral-600: oklch(44.6% 0.043 257.281);
                --ui-color-neutral-700: oklch(37.2% 0.044 257.287);
                --ui-color-neutral-800: oklch(27.9% 0.041 260.031);
                --ui-color-neutral-900: oklch(20.8% 0.042 265.755);
                --ui-color-neutral-950: oklch(12.9% 0.042 264.695);
                --ui-container: 1400px;
            }
            :root, :host, .light {
                --ui-primary: var(--color-tima-500);
                --ui-bg: #fff;
                --ui-text: #111827;
                --ui-text-muted: #6b7280;
                --ui-border: #e5e7eb;
            }
        }

        /* Glass card effect */
        .glass-card-bg {
            background: linear-gradient(135deg, rgba(0,0,0,0.08), rgba(0,0,0,0.05) 15%, rgba(0,0,0,0.02) 35%, rgba(0,0,0,0)), rgba(255,255,255,0.5);
        }
        .glass-border {
            padding: 1px;
            background: linear-gradient(165deg, rgba(255,255,255,0.7), rgba(255,255,255,0.3) 25%, transparent, rgba(255,255,255,0.7) 75%, rgba(255,255,255,0.3));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
        }

        /* LiquidGlass button */
        .liquidGlass-wrapper {
            position: relative;
            display: flex;
            font-weight: 600;
            overflow: hidden;
            color: #000;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 2.2);
            border-radius: 100%;
        }
        .liquidGlass-effect {
            position: absolute;
            z-index: 0;
            inset: 0;
            backdrop-filter: blur(1px);
            -webkit-backdrop-filter: blur(1px);
            overflow: hidden;
        }
        .liquidGlass-tint {
            z-index: 10;
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.1);
        }
        .liquidGlass-shine {
            position: absolute;
            inset: 0;
            z-index: 2;
            overflow: hidden;
            box-shadow: inset 1px 1px 1px rgba(255,255,255,0.9), inset 2px 1px 0 2px #2e3950, inset 0 0 1px 1px rgba(255,255,255,0.9), inset 0 -2px 2px 4px #2e3950;
            border-radius: 100%;
        }
        .liquidGlass-inner {
            position: relative;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 50;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
        }

        /* Flex helpers */
        .flex-y-center { display: flex; align-items: center; }
        .flex-center { display: flex; align-items: center; justify-content: center; }

        /* Chat FAB animation */
        @keyframes chat-fab-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        @keyframes chat-fab-pulse {
            0% { transform: scale(1); opacity: 0.55; }
            70% { transform: scale(1.55); opacity: 0; }
            to { transform: scale(1.55); opacity: 0; }
        }
        .chat-fab-wrap { animation: chat-fab-float 3.2s ease-in-out infinite; }
        .chat-fab-ring { animation: chat-fab-pulse 2.8s cubic-bezier(0.22, 1, 0.36, 1) infinite; }
        .chat-fab-ring--delayed { animation-delay: 1.1s; }

        /* Layout sticky header */
        .layout-sticky-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #fff;
            padding-top: 1rem;
            padding-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            transition: all 0.3s;
        }
        @media (max-width: 767px) {
            .layout-sticky-header { border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; }
        }

        /* Mobile nav bottom bar */
        .kc-mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            padding: 0.5rem 0;
        }
        @media (max-width: 767px) {
            .kc-mobile-nav { display: flex; }
        }

        /* Cart drawer */
        .kc-cart-overlay, .kc-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .kc-cart-overlay.active, .kc-modal-overlay.active { display: flex; align-items: center; justify-content: center; }
        
        .kc-cart-panel {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            max-width: 420px;
            background: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: -4px 0 24px rgba(0,0,0,0.1);
        }

        /* Auth Modal Container */
        .kc-auth-card {
            width: 90%;
            max-width: 400px;
            background: #fff;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            position: relative;
        }

        /* bg-secondary-300 */
        .bg-secondary-300 { background-color: oklch(80.9% 0.105 251.813); }

        /* container max-width */
        .kc-container { width: 100%; max-width: var(--ui-container); margin: 0 auto; padding: 0 1rem; }
        @media (min-width: 640px) { .kc-container { padding: 0 1.5rem; } }
        @media (min-width: 1024px) { .kc-container { padding: 0 2rem; } }

        /* main content */
        main { min-height: 100dvh; }
        @media (max-width: 767px) { main { padding-bottom: 71px; } }

        .page-wrapper { display: flex; flex-direction: column; background-color: #fff; min-height: 100dvh; }
    </style>

    @stack('styles')
</head>
<body style="margin:0;padding:0;font-family:'Urbanist',sans-serif;">

<div class="page-wrapper">

    <!-- ====== STICKY HEADER ====== -->
    <header class="layout-sticky-header py-4 bg-white max-md:rounded-b-2xl sticky top-0 z-50 transition-all duration-300 shadow-lg">
        <div class="px-4 sm:px-6 lg:px-8 kc-container mx-auto relative w-full bg-transparent">
            <!-- Desktop Header -->
            <div class="hidden md:flex items-center justify-between w-full gap-6">
                <!-- Left: Logo + Kataloglar -->
                <div class="flex flex-row items-center gap-6">
                    <a href="{{ url('/') }}" aria-current="page" class="router-link-active router-link-exact-active">
                        <img alt="Kitobchi" class="h-8 w-auto" src="{{ asset('images/logo/logo_blue.png') }}" />
                    </a>
                    <a href="{{ route('web.catalog') }}" aria-current="{{ request()->routeIs('web.catalog') ? 'page' : 'false' }}" class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg p-1! h-12 cursor-pointer bg-secondary-200!" style="text-decoration:none;color:#111827;">
                        <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
                        <div class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex flex-row items-center gap-2 {{ request()->routeIs('web.catalog') ? 'bg-primary-200' : '' }}">
                            <iconify-icon aria-hidden="true" icon="heroicons-solid:squares-2x2" class="w-5 h-5 transition-all duration-300 shrink-0"></iconify-icon>
                            <span class="max-lg:hidden font-medium text-sm transition-all duration-300">Kataloglar</span>
                        </div>
                    </a>
                </div>

                <!-- Center: Search -->
                <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg h-12 grow flex items-center gap-2 text-gray cursor-pointer bg-secondary-200!" style="max-width:600px;">
                    <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
                    <iconify-icon aria-hidden="true" icon="heroicons-solid:magnifying-glass" class="w-5 h-5 text-gray-500"></iconify-icon>
                    <form action="{{ route('web.catalog') }}" method="GET" class="flex flex-1 items-center h-full m-0 p-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Mahsulotni izlash..."
                               id="kcSearchInput"
                               autocomplete="off"
                               class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 font-inherit m-0 p-0 h-full w-full"
                               style="background:transparent;border:none;outline:none;font-size:0.875rem;color:#111827;font-family:inherit;">
                    </form>
                    <div id="kcSearchPopup" style="display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.12);z-index:200;overflow:hidden;max-height:400px;overflow-y:auto;"></div>
                </div>

                <!-- Right: Cart + Favorites + Lang + Profile -->
                <div class="flex flex-row items-center gap-4">
                    <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg flex flex-row items-center p-1! h-12 bg-secondary-200!">
                        <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
                        
                        <a href="{{ route('web.cart') }}" class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex flex-row items-center gap-2 group {{ request()->routeIs('web.cart') ? 'bg-primary-200' : '' }}" style="text-decoration:none;color:#111827;">
                            <div class="flex items-center justify-center relative">
                                <i class="icon-order group-hover:text-green-500 text-lg transition-colors duration-200"></i>
                                <span id="kcCartBadge" style="display:none;position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--color-tima-500);color:#fff;font-size:10px;font-weight:700;border-radius:9999px;display:flex;align-items:center;justify-content:center;padding:0 3px;"></span>
                            </div>
                            <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">Savatcha</span>
                        </a>
                        
                        <a href="{{ route('web.favorites') }}" aria-current="{{ request()->routeIs('web.favorites') ? 'page' : 'false' }}" class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex flex-row items-center gap-2 group {{ request()->routeIs('web.favorites') ? 'bg-primary-200' : '' }}" style="text-decoration:none;color:#111827;">
                            <div class="flex items-center justify-center relative" id="kcFavBadgeWrap">
                                <i class="icon-heart group-hover:text-green-500 text-lg transition-colors duration-200"></i>
                                @if(($kcFavCount ?? 0) > 0)
                                    <span id="kcFavBadge" style="position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--color-tima-500);color:#fff;font-size:10px;font-weight:700;border-radius:9999px;display:flex;align-items:center;justify-content:center;padding:0 3px;">{{ $kcFavCount > 99 ? '99+' : $kcFavCount }}</span>
                                @endif
                            </div>
                            <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">Sevimlilar</span>
                        </a>

                        <div class="relative kc-lang-wrap" id="kcLangWrap">
                            <button onclick="toggleLangMenu(event, 'kcLangMenu')" aria-haspopup="menu" aria-expanded="false" class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex flex-row items-center gap-2 group" style="background:none;border:none;cursor:pointer;font-family:inherit;color:#111827;">
                                <i class="icon-globe text-lg transition-colors duration-200 group-hover:text-green-500"></i>
                                <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">{{ (config('landing_locales.labels')[app()->getLocale()] ?? "O'zbekcha") }}</span>
                            </button>
                            @include('partials.lang-menu', ['menuId' => 'kcLangMenu'])
                        </div>
                    </div>

                    <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg flex flex-row items-center h-12 p-1! cursor-pointer bg-secondary-200!">
                        <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
                        <div class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex flex-row items-center gap-2 {{ request()->routeIs('web.profile') ? 'bg-primary-200' : '' }}">
                            @auth
                                <a href="{{ route('web.profile') }}" class="flex flex-row items-center gap-2 group h-full" style="text-decoration:none;color:#111827;">
                                    <i class="icon-profile text-lg"></i>
                                    <span class="font-normal text-sm leading-5 max-lg:hidden">{{ Str::limit(auth()->user()->name ?: auth()->user()->phone_number, 12) }}</span>
                                </a>
                            @else
                                <button onclick="openAuthModal()" class="flex flex-row items-center gap-2 group h-full" style="background:none;border:none;cursor:pointer;font-family:inherit;color:#111827;">
                                    <i class="icon-profile text-lg"></i>
                                    <span class="font-normal text-sm leading-5 max-lg:hidden">Kirish</span>
                                </button>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Header -->
            <div class="md:hidden">
                <div class="relative overflow-hidden transition-shadow duration-300 rounded-[20px] px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 h-12 text-gray bg-secondary-300! flex items-center justify-center cursor-pointer gap-3 w-full">
                    <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
                    <iconify-icon icon="lucide:search" class="text-xl text-gray-500"></iconify-icon>
                    <form action="{{ route('web.catalog') }}" method="GET" class="flex flex-1 items-center h-full m-0 p-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Kitobchi'da izlash"
                               id="kcSearchInputMobile"
                               autocomplete="off"
                               class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 font-inherit m-0 p-0 h-full w-full"
                               style="background:transparent;border:none;outline:none;font-size:0.875rem;font-family:inherit;color:#111827;">
                    </form>
                    <div id="kcSearchPopupMobile" style="display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.12);z-index:200;overflow:hidden;max-height:60vh;overflow-y:auto;"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- ====== MAIN CONTENT ====== -->
    <main>
        @yield('content')
    </main>

    <!-- ====== FOOTER ====== -->
    <div>
        <footer style="background:var(--color-tima-500);color:#fff;padding:3rem 0 2rem;">
            <div class="kc-container">
                <div style="display:grid;grid-template-columns:1fr;gap:2rem;">

                    <!-- Row 1: Brand + Description -->
                    <div>
                        <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;margin-bottom:1rem;">
                            <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi" style="height:2rem;width:auto;filter:brightness(0) invert(1);">
                        </a>
                        <p style="color:rgba(255,255,255,0.7);font-size:0.875rem;line-height:1.7;max-width:340px;margin:0;">
                            Kitobchi — O'zbekistondagi eng ulkan onlayn kitoblar va kanselyariya marketpleysi. Foydalanuvchilarga sifatli va hamyonbop mahsulotlarni tezda yetkazamiz.
                        </p>
                    </div>

                    <!-- Row 2: Nav columns -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:2rem;">

                        <!-- Katalog -->
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;margin:0 0 1rem;">Kataloglar</h3>
                            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                                <li><a href="{{ route('web.catalog') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Barcha kitoblar</a></li>
                                <li><a href="{{ route('web.catalog', ['type' => 'stationery']) }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Kanselyariya</a></li>
                            </ul>
                        </div>

                        <!-- Yordam -->
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;margin:0 0 1rem;">Mijozlar xizmati</h3>
                            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                                <li><a href="{{ route('legal.terms') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Yetkazib berish</a></li>
                                <li><a href="{{ route('legal.terms') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">To'lovlar</a></li>
                                <li><a href="{{ route('legal.privacy') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Maxfiylik siyosati</a></li>
                            </ul>
                        </div>

                        <!-- Ijtimoiy + Kontakt -->
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;margin:0 0 1rem;">Ijtimoiy tarmoqlar</h3>
                            <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;">
                                <a href="https://t.me/kitobchi" target="_blank" class="liquidGlass-wrapper" style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;">
                                    <div class="liquidGlass-effect"></div>
                                    <div class="liquidGlass-tint"></div>
                                    <div class="liquidGlass-shine"></div>
                                    <div class="liquidGlass-inner">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0zm4.962 7.224c.1-.002.321.023.465.14a.5.5 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024q-.159.037-5.061 3.345q-.72.495-1.302.48c-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789q.04-.324.893-.663q5.247-2.286 6.998-3.014c3.332-1.386 4.025-1.627 4.476-1.635"/>
                                        </svg>
                                    </div>
                                </a>
                                <a href="https://instagram.com/kitobchi" target="_blank" class="liquidGlass-wrapper" style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;">
                                    <div class="liquidGlass-effect"></div>
                                    <div class="liquidGlass-tint"></div>
                                    <div class="liquidGlass-shine"></div>
                                    <div class="liquidGlass-inner">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                            <a href="tel:+998555120102" style="font-size:1.25rem;font-weight:700;color:#fff;text-decoration:none;display:block;margin-bottom:1rem;">
                                +998 55 512 01 02
                            </a>
                        </div>
                    </div>

                    <!-- Bottom copyright -->
                    <div style="border-top:1px solid rgba(255,255,255,0.15);padding-top:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                        <p style="color:rgba(255,255,255,0.5);font-size:0.8125rem;margin:0;">
                            © {{ date('Y') }} Kitobchi. Barcha huquqlar himoyalangan.
                        </p>
                        <div style="display:flex;gap:1rem;">
                            <a href="{{ route('legal.privacy') }}" style="color:rgba(255,255,255,0.5);font-size:0.8125rem;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Maxfiylik</a>
                            <a href="{{ route('legal.terms') }}" style="color:rgba(255,255,255,0.5);font-size:0.8125rem;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Shartlar</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- ====== MOBILE BOTTOM NAV ====== -->
    <nav class="kc-mobile-nav" style="align-items:stretch;">
        <a href="{{ url('/') }}"
           style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;text-decoration:none;color:{{ request()->is('/') ? 'var(--color-tima-500)' : '#6b7280' }};font-size:0.6875rem;font-weight:{{ request()->is('/') ? '600' : '400' }};">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->is('/') ? '2.5' : '2' }}">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Bosh sahifa</span>
        </a>
        <a href="{{ route('web.catalog') }}"
           style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;text-decoration:none;color:{{ request()->is('catalog*') ? 'var(--color-tima-500)' : '#6b7280' }};font-size:0.6875rem;font-weight:{{ request()->is('catalog*') ? '600' : '400' }};">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->is('catalog*') ? '2.5' : '2' }}">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Katalog</span>
        </a>
        <a href="{{ route('web.favorites') }}"
           style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;text-decoration:none;color:{{ request()->routeIs('web.favorites') ? 'var(--color-tima-500)' : '#6b7280' }};font-size:0.6875rem;font-weight:{{ request()->routeIs('web.favorites') ? '600' : '400' }};">
            <div style="position:relative;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ request()->routeIs('web.favorites') ? 'var(--color-tima-500)' : 'none' }}" stroke="currentColor" stroke-width="{{ request()->routeIs('web.favorites') ? '0' : '2' }}">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span id="kcFavBadgeMob" style="position:absolute;top:-6px;right:-6px;min-width:16px;height:16px;background:var(--color-tima-500);color:#fff;font-size:9px;font-weight:700;border-radius:9999px;align-items:center;justify-content:center;padding:0 2px;{{ ($kcFavCount ?? 0) > 0 ? 'display:flex;' : 'display:none;' }}">{{ ($kcFavCount ?? 0) > 99 ? '99+' : ($kcFavCount ?? 0) }}</span>
            </div>
            <span>Sevimlilar</span>
        </a>
        <a href="{{ route('web.cart') }}"
           class="flex flex-col items-center justify-center gap-[4px] py-2 bg-transparent text-gray-500 hover:text-green-500 transition-colors duration-300 {{ request()->routeIs('web.cart') ? 'text-green-500' : '' }}"
           style="flex:1;text-decoration:none;font-size:0.6875rem;font-weight:500;">
            <div class="relative flex items-center justify-center">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <span id="kcCartBadgeMob" style="display:none;position:absolute;top:-6px;right:-6px;min-width:16px;height:16px;background:var(--color-tima-500);color:#fff;font-size:9px;font-weight:700;border-radius:9999px;align-items:center;justify-content:center;padding:0 2px;"></span>
            </div>
            <span>Savatcha</span>
        </button>

        @auth
            <a href="{{ route('web.profile') }}"
               style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;text-decoration:none;color:{{ request()->is('profile*') ? 'var(--color-tima-500)' : '#6b7280' }};font-size:0.6875rem;font-weight:{{ request()->is('profile*') ? '600' : '400' }};">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Profil</span>
            </a>
        @else
            <button onclick="openAuthModal()"
                    style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;background:none;border:none;cursor:pointer;color:#6b7280;font-size:0.6875rem;font-family:inherit;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Kirish</span>
            </button>
        @endauth
    </nav>

    <!-- ====== CHAT FAB ====== -->
    <div style="position:fixed;z-index:91;bottom:5.5rem;right:1rem;pointer-events:auto;">
        <a href="https://t.me/kitobchi" target="_blank" class="chat-fab-wrap"
           style="display:flex;width:3.5rem;height:3.5rem;border-radius:9999px;background:var(--color-tima-500);color:#fff;align-items:center;justify-content:center;text-decoration:none;position:relative;">
            <span class="chat-fab-ring" style="position:absolute;inset:0;border-radius:9999px;background:rgba(99,102,241,0.3);pointer-events:none;"></span>
            <span class="chat-fab-ring chat-fab-ring--delayed" style="position:absolute;inset:0;border-radius:9999px;background:rgba(99,102,241,0.2);pointer-events:none;"></span>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0zm4.962 7.224c.1-.002.321.023.465.14a.5.5 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024q-.159.037-5.061 3.345q-.72.495-1.302.48c-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789q.04-.324.893-.663q5.247-2.286 6.998-3.014c3.332-1.386 4.025-1.627 4.476-1.635"/>
            </svg>
        </a>
    </div>

</div>



<!-- ====== AUTH MODAL ====== -->
<div id="kcAuthModalOverlay" class="kc-modal-overlay" onclick="if(event.target===this) closeAuthModal()">
    <div class="kc-auth-card">
        <button onclick="closeAuthModal()" style="position:absolute;top:1.25rem;right:1.25rem;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border:none;background:#f3f4f6;border-radius:9999px;cursor:pointer;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>

        <!-- Step 1: Phone -->
        <div id="kcAuthStep1">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <h3 style="font-size:1.25rem;font-weight:800;color:#111827;margin:0 0 0.5rem;">Tizimga kirish</h3>
                <p style="color:#6b7280;font-size:0.875rem;margin:0;line-height:1.5;">Buyurtmalaringizni kuzatish va xarid qilish uchun telefon raqamingizni kiriting.</p>
            </div>

            <form id="kcPhoneForm" onsubmit="handleSendCode(event)">
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">Telefon raqami</label>
                    <div style="display:flex;align-items:center;background:#f9fafb;border:1px solid #d1d5db;border-radius:0.75rem;padding:0 0.875rem;height:3rem;">
                        <span style="font-weight:700;color:#111827;margin-right:0.5rem;font-size:0.9375rem;">+998</span>
                        <input type="tel" id="kcAuthPhone" placeholder="90 123 45 67" required
                               style="flex:1;background:transparent;border:none;outline:none;font-size:1rem;font-weight:600;color:#111827;font-family:inherit;">
                    </div>
                </div>

                <div id="kcAuthError1" style="display:none;color:#ef4444;font-size:0.8125rem;margin-bottom:1rem;text-align:center;"></div>

                <button type="submit" id="kcSendBtn"
                        style="width:100%;height:3rem;background:var(--color-tima-500);color:#fff;border:none;border-radius:9999px;font-size:0.9375rem;font-weight:700;cursor:pointer;transition:all 0.2s;">
                    Kodni yuborish →
                </button>
            </form>
        </div>

        <!-- Step 2: Code -->
        <div id="kcAuthStep2" style="display:none;">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <h3 style="font-size:1.25rem;font-weight:800;color:#111827;margin:0 0 0.5rem;">Kodni kiriting</h3>
                <p style="color:#6b7280;font-size:0.875rem;margin:0;line-height:1.5;" id="kcAuthSentMsg">SMS orqali yuborilgan 6 xonali kodni kiriting.</p>
            </div>

            <form id="kcCodeForm" onsubmit="handleVerifyCode(event)">
                <div style="margin-bottom:1.25rem;">
                    <input type="text" id="kcAuthCode" placeholder="222222" maxlength="6" required
                           style="width:100%;height:3.25rem;background:#f9fafb;border:2px solid var(--color-tima-500);border-radius:0.75rem;text-align:center;font-size:1.5rem;font-weight:800;letter-spacing:0.375rem;color:#111827;outline:none;font-family:inherit;">
                </div>

                <div id="kcAuthError2" style="display:none;color:#ef4444;font-size:0.8125rem;margin-bottom:1rem;text-align:center;"></div>

                <button type="submit" id="kcVerifyBtn"
                        style="width:100%;height:3rem;background:var(--color-tima-500);color:#fff;border:none;border-radius:9999px;font-size:0.9375rem;font-weight:700;cursor:pointer;transition:all 0.2s;">
                    Tasdiqlash va Kirish
                </button>

                <button type="button" onclick="showAuthStep1()"
                        style="width:100%;background:none;border:none;color:#6b7280;font-size:0.8125rem;margin-top:0.75rem;cursor:pointer;">
                    ← Raqamni o'zgartirish
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ====== SCRIPTS ====== -->
<script>
    let kcCart = JSON.parse(localStorage.getItem('kc_cart') || '[]');
    let currentAuthPhone = '';

    function saveCart() {
        localStorage.setItem('kc_cart', JSON.stringify(kcCart));
        updateBadges();
        renderCartBody();
    }

    function updateBadges() {
        const total = kcCart.reduce((s, i) => s + i.qty, 0);
        ['kcCartBadge', 'kcCartBadgeMobile', 'kcCartBadgeMob'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (total > 0) {
                el.style.display = 'flex';
                el.textContent = total > 99 ? '99+' : total;
            } else {
                el.style.display = 'none';
            }
        });
    }

    function addToCart(id, name, price, image, url) {
        const ex = kcCart.find(i => i.id === id);
        if (ex) { ex.qty++; if (url) ex.url = url; } else { kcCart.push({ id, name, price, image, qty: 1, url: url || null }); }
        saveCart();
        window.location.href = "{{ route('web.cart') }}";
    }

    function updateQty(id, delta) {
        const idx = kcCart.findIndex(i => i.id === id);
        if (idx === -1) return;
        kcCart[idx].qty += delta;
        if (kcCart[idx].qty <= 0) kcCart.splice(idx, 1);
        saveCart();
    }

    function removeFromCart(id) {
        kcCart = kcCart.filter(i => i.id !== id);
        saveCart();
    }

    // FAVORITES (server-persisted — FavouriteProducts jadvali)
    window.kcIsAuthed = {{ auth()->check() ? 'true' : 'false' }};

    function toggleFavorite(btn, productId, productType) {
        if (!window.kcIsAuthed) {
            if (typeof openAuthModal === 'function') openAuthModal();
            return;
        }

        const icon = btn.querySelector('iconify-icon');
        const wasFav = btn.getAttribute('data-fav') === '1';
        const nowFav = !wasFav;

        applyFavVisual(btn, icon, nowFav);
        bumpFavBadge(nowFav ? 1 : -1);

        fetch("{{ route('web.favorites.toggle') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product_id: productId, product_type: productType }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                // Server har doim "hozir ko'rinadigan" mahsulotlar sonini
                // qaytaradi — optimistik +1/-1 hisobni shu bilan to'g'rilaymiz
                // (masalan boshqa bir sevimli mahsulot orada yashiringan
                // bo'lsa, badge son shu yerda haqiqiy holatga qaytadi).
                if (typeof res.count === 'number') setFavBadge(res.count);
            } else {
                applyFavVisual(btn, icon, wasFav);
                bumpFavBadge(wasFav ? 1 : -1);
                if (res.require_auth && typeof openAuthModal === 'function') openAuthModal();
            }
        })
        .catch(() => {
            applyFavVisual(btn, icon, wasFav);
            bumpFavBadge(wasFav ? 1 : -1);
        });
    }

    function applyFavVisual(btn, icon, isFav) {
        btn.setAttribute('data-fav', isFav ? '1' : '0');
        if (icon) {
            icon.setAttribute('icon', isFav ? 'heroicons-solid:heart' : 'heroicons:heart');
            icon.style.color = isFav ? '#ef4444' : '';
        }
    }

    function bumpFavBadge(delta) {
        let badge = document.getElementById('kcFavBadge');
        const mobBadge = document.getElementById('kcFavBadgeMob');
        const current = badge
            ? (parseInt(badge.textContent, 10) || 0)
            : (mobBadge && mobBadge.style.display !== 'none' ? (parseInt(mobBadge.textContent, 10) || 0) : 0);
        setFavBadge(current + delta);
    }

    // Badge'ni ANIQ songa o'rnatadi (delta emas) — server javobidagi
    // haqiqiy hisoblagichga (faqat hozir ko'rinadigan mahsulotlar) sinxron
    // qilish uchun ishlatiladi, optimistik +1/-1 hisobdagi chetlanishni
    // (masalan boshqa sevimli mahsulot orada yashiringan bo'lsa) tuzatadi.
    function setFavBadge(n) {
        let badge = document.getElementById('kcFavBadge');
        const wrap = document.getElementById('kcFavBadgeWrap');
        const mobBadge = document.getElementById('kcFavBadgeMob');

        // Desktop header badge — dinamik yaratiladi/o'chiriladi (0 bo'lsa DOM'da umuman yo'q).
        if (n <= 0) {
            if (badge) badge.remove();
        } else {
            if (!badge && wrap) {
                badge = document.createElement('span');
                badge.id = 'kcFavBadge';
                badge.style.cssText = 'position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--color-tima-500);color:#fff;font-size:10px;font-weight:700;border-radius:9999px;display:flex;align-items:center;justify-content:center;padding:0 3px;';
                wrap.appendChild(badge);
            }
            if (badge) badge.textContent = n > 99 ? '99+' : String(n);
        }

        // Mobil pastki navigatsiyadagi badge — doim DOM'da bor, faqat
        // ko'rinishi/matni yangilanadi.
        if (mobBadge) {
            if (n <= 0) {
                mobBadge.style.display = 'none';
            } else {
                mobBadge.style.display = 'flex';
                mobBadge.textContent = n > 99 ? '99+' : String(n);
            }
        }
    }

    // LANGUAGE SWITCHER
    function toggleLangMenu(e, menuId) {
        if (e) e.stopPropagation();
        const menu = document.getElementById(menuId);
        if (!menu) return;
        const willOpen = menu.style.display !== 'block';
        document.querySelectorAll('.kc-lang-menu').forEach(m => { m.style.display = 'none'; });
        menu.style.display = willOpen ? 'block' : 'none';
        const btn = e && e.currentTarget;
        if (btn) btn.setAttribute('aria-expanded', String(willOpen));
    }
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.kc-lang-wrap').forEach(function(wrap) {
            if (!wrap.contains(e.target)) {
                const m = wrap.querySelector('.kc-lang-menu');
                if (m) m.style.display = 'none';
            }
        });
    });



    // AUTH MODAL FUNCTIONS
    function openAuthModal() {
        const modal = document.getElementById('kcAuthModalOverlay');
        if (!modal) return;
        showAuthStep1();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAuthModal() {
        const modal = document.getElementById('kcAuthModalOverlay');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function showAuthStep1() {
        document.getElementById('kcAuthStep1').style.display = 'block';
        document.getElementById('kcAuthStep2').style.display = 'none';
        document.getElementById('kcAuthError1').style.display = 'none';
    }

    function handleSendCode(e) {
        e.preventDefault();
        const input = document.getElementById('kcAuthPhone');
        const err = document.getElementById('kcAuthError1');
        const btn = document.getElementById('kcSendBtn');
        const phoneVal = input.value.trim().replace(/\D+/g, '');

        if (phoneVal.length < 9) {
            err.textContent = 'Telefon raqamni to\'liq kiriting.';
            err.style.display = 'block';
            return;
        }

        currentAuthPhone = '998' + (phoneVal.length === 9 ? phoneVal : phoneVal.slice(-9));

        btn.disabled = true;
        btn.textContent = 'Yuborilmoqda...';
        err.style.display = 'none';

        fetch('/auth/send-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ phone_number: currentAuthPhone })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.textContent = 'Kodni yuborish →';
            if (res.status === 'success') {
                document.getElementById('kcAuthStep1').style.display = 'none';
                document.getElementById('kcAuthStep2').style.display = 'block';
                document.getElementById('kcAuthSentMsg').textContent = '+' + currentAuthPhone + ' raqamiga yuborilgan tasdiqlash kodini kiriting.';
            } else {
                err.textContent = res.message || 'Xatolik yuz berdi.';
                err.style.display = 'block';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Kodni yuborish →';
            err.textContent = 'Ulanishda xatolik.';
            err.style.display = 'block';
        });
    }

    function handleVerifyCode(e) {
        e.preventDefault();
        const input = document.getElementById('kcAuthCode');
        const err = document.getElementById('kcAuthError2');
        const btn = document.getElementById('kcVerifyBtn');
        const code = input.value.trim();

        if (code.length < 6) {
            err.textContent = '6 xonali kodni kiriting.';
            err.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Tekshirilmoqda...';
        err.style.display = 'none';

        fetch('/auth/verify-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ phone_number: currentAuthPhone, code: code })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.textContent = 'Tasdiqlash va Kirish';
            if (res.status === 'success') {
                if (res.token) localStorage.setItem('kc_token', res.token);
                window.location.reload();
            } else {
                err.textContent = res.message || 'Kod noto\'g\'ri.';
                err.style.display = 'block';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Tasdiqlash va Kirish';
            err.textContent = 'Ulanishda xatolik.';
            err.style.display = 'block';
        });
    }

    // Live search — desktop va mobil qidiruv maydonlarining ikkalasiga
    // ham ulanadi (avval faqat desktopga ulangan edi, mobilda takliflar
    // popup'i umuman ko'rinmasdi).
    (function() {
        function wireLiveSearch(inputId, popupId) {
            const input = document.getElementById(inputId);
            const popup = document.getElementById(popupId);
            if (!input || !popup) return;
            let timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 2) { popup.style.display = 'none'; return; }
                timer = setTimeout(() => {
                    fetch(`/catalog?search=${encodeURIComponent(q)}&ajax=1`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.items && data.items.length > 0) {
                                popup.innerHTML = data.items.slice(0, 8).map(item => `
                                    <a href="${item.url}" style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;text-decoration:none;color:#111827;border-bottom:1px solid #f3f4f6;transition:background 0.1s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                                        <img src="${item.image}" alt="" style="width:36px;height:48px;object-fit:cover;border-radius:6px;flex-shrink:0;">
                                        <div>
                                            <div style="font-size:0.8125rem;font-weight:500;margin-bottom:2px;">${item.name}</div>
                                            <div style="font-size:0.75rem;color:var(--color-tima-500);font-weight:600;">${new Intl.NumberFormat('uz').format(item.price)} so'm</div>
                                        </div>
                                    </a>`).join('');
                                popup.style.display = 'block';
                            } else {
                                popup.style.display = 'none';
                            }
                        }).catch(() => { popup.style.display = 'none'; });
                }, 250);
            });
            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !popup.contains(e.target)) popup.style.display = 'none';
            });
        }
        wireLiveSearch('kcSearchInput', 'kcSearchPopup');
        wireLiveSearch('kcSearchInputMobile', 'kcSearchPopupMobile');
    })();

    // Init
    document.addEventListener('DOMContentLoaded', () => { updateBadges(); });

    // Responsive helper styles
    const style = document.createElement('style');
    style.textContent = `
        @media(min-width:768px) { .md-hide { display:none!important; } }
        @media(max-width:767px) { [style*="display:flex"].hidden { display:none!important; } }
        @media(min-width:1024px) { .lg-show { display:inline!important; } }
    `;
    document.head.appendChild(style);
</script>

@stack('scripts')
</body>
</html>
