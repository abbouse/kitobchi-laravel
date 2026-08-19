<!DOCTYPE html>
<html lang="{{ config('landing_locales.html_lang.'.app()->getLocale(), 'uz') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#393737">
    <title>@yield('title', __('errors.meta_title'))</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20260819">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=20260819">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=20260819">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=20260819">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=20260819">

    @stack('meta')
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-2024.webflow.shared.cef7bd9c3.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-embed.css') }}">
    @vite(['resources/css/kitobchi-popcorn.css'])
    @stack('head')
</head>
<body class="kc-landing kc-public-shell">
<div class="page-wrapper">
    <header class="kc-shell-nav">
        <div class="page-padding">
            <div class="container">
                <a href="{{ url('/') }}" class="kc-shell-logo w-inline-block" aria-label="{{ __('nav.logo_aria') }}">
                    <img src="{{ asset('images/logo/logo_black.png') }}" alt="" width="120" height="32" style="height:28px;width:auto;display:block">
                </a>
            </div>
        </div>
    </header>
    <main class="main cc-home kc-shell-main">
        <div class="page-padding">
            <div class="container">
                @yield('content')
            </div>
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
