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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;0,14..32,900;1,14..32,400&display=swap" rel="stylesheet">

    <!-- Iconify for icon rendering -->
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>

    <!-- Kitobchi Entry CSS (PiyolaMarket Tailwind base) -->
    @vite(['resources/css/kitobchi-entry.css', 'resources/css/kitobchi-marketplace.css'])

    <!-- PiyolaMarket CSS Variables -->
    <style id="kc-colors">
        @layer theme {
            :root, :host {
                --ui-color-primary-50: oklch(97% 0.02 280);
                --ui-color-primary-100: oklch(94% 0.04 280);
                --ui-color-primary-200: oklch(89% 0.08 280);
                --ui-color-primary-300: oklch(82% 0.13 280);
                --ui-color-primary-400: oklch(73% 0.18 280);
                --ui-color-primary-500: oklch(63% 0.22 280);
                --ui-color-primary-600: oklch(55% 0.22 280);
                --ui-color-primary-700: oklch(47% 0.20 280);
                --ui-color-primary-800: oklch(40% 0.17 280);
                --ui-color-primary-900: oklch(34% 0.13 280);
                --ui-color-primary-950: oklch(24% 0.09 280);
                --color-tima-50: oklch(97% 0.02 280);
                --color-tima-100: oklch(94% 0.04 280);
                --color-tima-200: oklch(89% 0.08 280);
                --color-tima-300: oklch(82% 0.13 280);
                --color-tima-400: oklch(73% 0.18 280);
                --color-tima-500: oklch(55% 0.24 290);
                --color-tima-600: oklch(48% 0.24 290);
                --color-tima-700: oklch(41% 0.22 290);
                --color-tima-800: oklch(35% 0.18 290);
                --color-tima-900: oklch(29% 0.14 290);
                --color-tima-950: oklch(20% 0.09 290);
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
                --ui-container: 1280px;
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
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
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
        .kc-cart-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(0,0,0,0.5);
        }
        .kc-cart-overlay.active { display: block; }
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

        /* icon fonts from icomoon (PiyolaMarket uses these) */
        .icon-order::before { content: "🛒"; font-style: normal; }
        .icon-heart::before { content: "♡"; font-style: normal; }
        .icon-globe::before { content: "🌐"; font-style: normal; }

        /* Slider dots */
        [data-state=active].kc-dot { width: 1rem; background: #010101; }
        .kc-dot { height: 0.375rem; width: 0.375rem; background: #d1d5db; border-radius: 9999px; transition: all 0.3s; cursor: pointer; }

        /* bg-secondary-300 */
        .bg-secondary-300 { background-color: oklch(80.9% 0.105 251.813); }

        /* container max-width */
        .kc-container { width: 100%; max-width: var(--ui-container); margin: 0 auto; padding: 0 1rem; }
        @media (min-width: 640px) { .kc-container { padding: 0 1.5rem; } }
        @media (min-width: 1024px) { .kc-container { padding: 0 2rem; } }

        /* main content */
        main { min-height: 100dvh; }
        @media (max-width: 767px) { main { padding-bottom: 71px; } }

        /* Tailwind bg-secondary-300 = slate-300 ish */
        .page-wrapper { display: flex; flex-direction: column; background-color: #e2e8f0; min-height: 100dvh; }
        @media (min-width: 768px) { .page-wrapper { min-height: 100dvh; } }
    </style>

    @stack('styles')
</head>
<body style="margin:0;padding:0;font-family:'Inter',sans-serif;">

<div class="page-wrapper">

    <!-- ====== STICKY HEADER ====== -->
    <header class="layout-sticky-header">
        <div class="kc-container">
            <div style="position:relative;width:100%;background:transparent;">

                <!-- Desktop Header -->
                <div class="hidden md:flex items-center justify-between w-full gap-6" style="display:flex;align-items:center;justify-content:space-between;gap:1.5rem;">

                    <!-- Left: Logo + Kataloglar -->
                    <div class="flex-y-center" style="display:flex;align-items:center;gap:1.5rem;">
                        <a href="{{ url('/') }}" style="display:flex;align-items:center;">
                            <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi" style="height:2rem;width:auto;">
                        </a>

                        <!-- Kataloglar Button (glass pill) -->
                        <a href="{{ route('web.catalog') }}"
                           class="relative overflow-hidden transition-shadow duration-300 rounded-full glass-card-bg flex-y-center cursor-pointer"
                           style="padding:0.25rem 0.75rem;height:3rem;gap:0.5rem;text-decoration:none;color:#111827;background-color:#e2e8f0;">
                            <div class="absolute inset-0 pointer-events-none glass-border" style="border-radius:9999px;"></div>
                            <div style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 0.75rem;border-radius:9999px;transition:all 0.3s;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                    <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                                </svg>
                                <span style="font-size:0.875rem;font-weight:500;white-space:nowrap;">Kataloglar</span>
                            </div>
                        </a>
                    </div>

                    <!-- Center: Search -->
                    <div style="position:relative;flex:1;max-width:600px;">
                        <div class="relative overflow-hidden transition-shadow duration-300 rounded-full glass-card-bg flex-y-center cursor-pointer"
                             style="height:3rem;gap:0.5rem;background-color:#e2e8f0;padding:0 1rem;">
                            <div class="absolute inset-0 pointer-events-none glass-border" style="border-radius:9999px;"></div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;color:#6b7280;">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                            <form action="{{ route('web.catalog') }}" method="GET" style="flex:1;display:flex;">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="Kitobchi'da izlash..."
                                       id="kcSearchInput"
                                       autocomplete="off"
                                       style="flex:1;background:transparent;border:none;outline:none;font-size:0.875rem;color:#111827;font-family:inherit;">
                            </form>
                        </div>
                        <!-- Search popup -->
                        <div id="kcSearchPopup" style="display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.12);z-index:200;overflow:hidden;max-height:400px;overflow-y:auto;"></div>
                    </div>

                    <!-- Right: Cart + Favorites + Language -->
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <div class="relative overflow-hidden transition-shadow duration-300 glass-card-bg flex-y-center"
                             style="padding:0.25rem;height:3rem;border-radius:9999px;background-color:#e2e8f0;">
                            <div class="absolute inset-0 pointer-events-none glass-border" style="border-radius:9999px;"></div>

                            <!-- Cart -->
                            <button onclick="toggleCartDrawer(true)"
                                    style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 0.75rem;border-radius:9999px;background:none;border:none;cursor:pointer;font-family:inherit;transition:all 0.3s;position:relative;">
                                <div style="position:relative;display:flex;align-items:center;justify-content:center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                    <span id="kcCartBadge" style="display:none;position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--color-tima-500);color:#fff;font-size:10px;font-weight:700;border-radius:9999px;display:flex;align-items:center;justify-content:center;padding:0 3px;"></span>
                                </div>
                                <span style="display:none;font-size:0.875rem;font-weight:400;" class="lg-show">Savatcha</span>
                            </button>

                            <!-- Favorites -->
                            <a href="{{ route('web.catalog') }}"
                               style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 0.75rem;border-radius:9999px;text-decoration:none;color:#111827;transition:all 0.3s;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                                <span style="display:none;font-size:0.875rem;font-weight:400;" class="lg-show">Sevimlilar</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile Header -->
                <div style="display:flex;align-items:center;gap:0.75rem;" class="md-hide">
                    <a href="{{ url('/') }}" style="display:flex;align-items:center;flex-shrink:0;">
                        <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi" style="height:1.75rem;width:auto;">
                    </a>
                    <div style="flex:1;position:relative;">
                        <div style="display:flex;align-items:center;gap:0.5rem;padding:0 0.75rem;height:2.5rem;background:#f3f4f6;border-radius:9999px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                            <form action="{{ route('web.catalog') }}" method="GET" style="flex:1;display:flex;">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="Izlash..."
                                       style="flex:1;background:transparent;border:none;outline:none;font-size:0.875rem;font-family:inherit;">
                            </form>
                        </div>
                    </div>
                    <button onclick="toggleCartDrawer(true)" style="position:relative;width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:9999px;border:none;cursor:pointer;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        <span id="kcCartBadgeMobile" style="display:none;position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;background:var(--color-tima-500);color:#fff;font-size:9px;font-weight:700;border-radius:9999px;display:flex;align-items:center;justify-content:center;padding:0 2px;"></span>
                    </button>
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
        <footer style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);color:#fff;padding:3rem 0 2rem;">
            <div class="kc-container">
                <div style="display:grid;grid-template-columns:1fr;gap:2rem;">

                    <!-- Row 1: Brand + Links + Social -->
                    <div style="display:grid;grid-template-columns:1fr;gap:2rem;">

                        <!-- Brand -->
                        <div>
                            <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;margin-bottom:1rem;">
                                <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi" style="height:2rem;width:auto;filter:brightness(0) invert(1);">
                            </a>
                            <p style="color:rgba(255,255,255,0.7);font-size:0.875rem;line-height:1.7;max-width:280px;margin:0;">
                                Original kitoblar va kanselyariya mahsulotlarini onlayn xarid qiling. O'zbekiston bo'ylab tezkor yetkazib berish.
                            </p>
                        </div>

                    </div>

                    <!-- Row 2: Nav columns -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:2rem;">

                        <!-- Katalog -->
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;margin:0 0 1rem;">Katalog</h3>
                            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                                <li><a href="{{ route('web.catalog') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Barcha kitoblar</a></li>
                                <li><a href="{{ route('web.catalog', ['type' => 'stationery']) }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Kanselyariya</a></li>
                            </ul>
                        </div>

                        <!-- Yordam -->
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;margin:0 0 1rem;">Yordam</h3>
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
                                <a href="https://t.me/kitobchi" target="_blank"
                                   class="liquidGlass-wrapper"
                                   style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;">
                                    <div class="liquidGlass-effect"></div>
                                    <div class="liquidGlass-tint"></div>
                                    <div class="liquidGlass-shine"></div>
                                    <div class="liquidGlass-inner">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0zm4.962 7.224c.1-.002.321.023.465.14a.5.5 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024q-.159.037-5.061 3.345q-.72.495-1.302.48c-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789q.04-.324.893-.663q5.247-2.286 6.998-3.014c3.332-1.386 4.025-1.627 4.476-1.635"/>
                                        </svg>
                                    </div>
                                </a>
                                <a href="https://instagram.com/kitobchi" target="_blank"
                                   class="liquidGlass-wrapper"
                                   style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;">
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
                            <a href="tel:+998909999999" style="font-size:1.25rem;font-weight:700;color:#fff;text-decoration:none;display:block;margin-bottom:1rem;">
                                +998 90 999 99 99
                            </a>
                            <!-- App stores -->
                            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                                <a href="#" style="display:inline-flex;align-items:center;gap:0.5rem;background:#000;border-radius:0.75rem;padding:0.5rem 0.75rem;text-decoration:none;transition:opacity 0.2s;" target="_blank">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="white"><path d="M14 2.333C7.557 2.333 2.333 7.557 2.333 14S7.557 25.667 14 25.667 25.667 20.443 25.667 14 20.443 2.333 14 2.333zm.7 16.917h-1.4v-7h1.4v7zm0-9.333h-1.4V8.75h1.4v1.167z"/></svg>
                                    <div style="display:flex;flex-direction:column;line-height:1.2;color:#fff;">
                                        <span style="font-size:10px;">Yuklab olish</span>
                                        <span style="font-size:13px;font-weight:700;">Google Play</span>
                                    </div>
                                </a>
                                <a href="#" style="display:inline-flex;align-items:center;gap:0.5rem;background:#000;border-radius:0.75rem;padding:0.5rem 0.75rem;text-decoration:none;transition:opacity 0.2s;" target="_blank">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="white"><path d="M18.667 2.333H9.333A2.336 2.336 0 0 0 7 4.667v18.666A2.336 2.336 0 0 0 9.333 25.667h9.334A2.336 2.336 0 0 0 21 23.333V4.667A2.336 2.336 0 0 0 18.667 2.333zM14 24.5a1.167 1.167 0 1 1 0-2.334A1.167 1.167 0 0 1 14 24.5zm5.833-4.083H8.167V6.417h11.666v14z"/></svg>
                                    <div style="display:flex;flex-direction:column;line-height:1.2;color:#fff;">
                                        <span style="font-size:10px;">Yuklab olish</span>
                                        <span style="font-size:13px;font-weight:700;">App Store</span>
                                    </div>
                                </a>
                            </div>
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
        <button onclick="toggleCartDrawer(true)"
                style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;background:none;border:none;cursor:pointer;color:#6b7280;font-size:0.6875rem;font-family:inherit;position:relative;">
            <div style="position:relative;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <span id="kcCartBadgeMob" style="display:none;position:absolute;top:-6px;right:-6px;min-width:16px;height:16px;background:var(--color-tima-500);color:#fff;font-size:9px;font-weight:700;border-radius:9999px;align-items:center;justify-content:center;padding:0 2px;"></span>
            </div>
            <span>Savatcha</span>
        </button>
        <a href="{{ route('web.catalog') }}"
           style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0.5rem 0;text-decoration:none;color:#6b7280;font-size:0.6875rem;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Profil</span>
        </a>
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

<!-- ====== CART DRAWER ====== -->
<div id="kcCartOverlay" class="kc-cart-overlay" onclick="if(event.target===this) toggleCartDrawer(false)">
    <div class="kc-cart-panel">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #f3f4f6;">
            <h5 style="font-size:1.125rem;font-weight:800;margin:0;color:#111827;">Savatcha</h5>
            <button onclick="toggleCartDrawer(false)" style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border:none;background:#f3f4f6;border-radius:9999px;cursor:pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div id="kcCartBody" style="flex:1;overflow-y:auto;padding:1rem 1.25rem;">
            <div style="text-align:center;padding:3rem 0;color:#9ca3af;">
                <div style="font-size:3rem;">🛒</div>
                <div style="font-weight:600;margin-top:0.5rem;">Savatchangiz bo'sh</div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:1rem 1.25rem;border-top:1px solid #f3f4f6;background:#fff;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.875rem;">
                <span style="font-size:1rem;font-weight:600;color:#111827;">Jami:</span>
                <span style="font-size:1.25rem;font-weight:800;color:var(--color-tima-500);" id="kcCartTotal">0 so'm</span>
            </div>
            <a href="{{ route('web.checkout') }}"
               style="display:block;width:100%;padding:0.875rem;background:var(--color-tima-500);color:#fff;text-align:center;border-radius:9999px;font-weight:700;font-size:1rem;text-decoration:none;transition:all 0.2s;"
               onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Buyurtmani rasmiylashtirish →
            </a>
        </div>
    </div>
</div>

<!-- ====== SCRIPTS ====== -->
<script>
    // Cart state
    let kcCart = JSON.parse(localStorage.getItem('kc_cart') || '[]');

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

    function addToCart(id, name, price, image) {
        const ex = kcCart.find(i => i.id === id);
        if (ex) { ex.qty++; } else { kcCart.push({ id, name, price, image, qty: 1 }); }
        saveCart();
        toggleCartDrawer(true);
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

    function toggleCartDrawer(open) {
        const overlay = document.getElementById('kcCartOverlay');
        if (!overlay) return;
        overlay.classList.toggle('active', open);
        if (open) renderCartBody();
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function renderCartBody() {
        const body = document.getElementById('kcCartBody');
        const totalEl = document.getElementById('kcCartTotal');
        if (!body) return;

        if (kcCart.length === 0) {
            body.innerHTML = `<div style="text-align:center;padding:3rem 0;color:#9ca3af;">
                <div style="font-size:3rem;">🛒</div>
                <div style="font-weight:600;margin-top:0.5rem;color:#374151;">Savatchangiz bo'sh</div>
                <div style="font-size:0.875rem;margin-top:0.25rem;">Biror mahsulot qo'shing</div>
            </div>`;
            if (totalEl) totalEl.textContent = '0 so\'m';
            return;
        }

        let total = 0;
        let html = '';
        kcCart.forEach(item => {
            total += item.price * item.qty;
            html += `<div style="display:flex;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid #f9fafb;align-items:flex-start;">
                <img src="${item.image}" alt="${item.name}" style="width:52px;height:70px;object-fit:cover;border-radius:0.5rem;flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.8125rem;font-weight:500;color:#111827;margin-bottom:0.25rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${item.name}</div>
                    <div style="font-size:0.875rem;font-weight:700;color:var(--color-tima-500);margin-bottom:0.5rem;">${new Intl.NumberFormat('uz').format(item.price)} so'm</div>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <button onclick="updateQty(${item.id}, -1)" style="width:1.75rem;height:1.75rem;border:1px solid #e5e7eb;border-radius:9999px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;">−</button>
                        <span style="font-weight:600;min-width:1.5rem;text-align:center;">${item.qty}</span>
                        <button onclick="updateQty(${item.id}, 1)" style="width:1.75rem;height:1.75rem;border:1px solid #e5e7eb;border-radius:9999px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;">+</button>
                        <button onclick="removeFromCart(${item.id})" style="margin-left:auto;width:1.75rem;height:1.75rem;border:none;border-radius:9999px;background:#fee2e2;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>`;
        });

        body.innerHTML = html;
        if (totalEl) totalEl.textContent = new Intl.NumberFormat('uz').format(total) + ' so\'m';
    }

    // Live search
    (function() {
        const input = document.getElementById('kcSearchInput');
        const popup = document.getElementById('kcSearchPopup');
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
