<!DOCTYPE html>
<html lang="{{ config('landing_locales.html_lang.'.app()->getLocale(), 'uz') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#393737">
    @stack('meta')
    <title>@yield('title', 'Kitobchi')</title>
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-2024.webflow.shared.cef7bd9c3.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-embed.css') }}">
    @vite(['resources/css/kitobchi-popcorn.css'])
    @stack('head')
    @yield('additional_styles')
</head>
<body class="kc-landing kc-legal-doc">
<div class="page-wrapper">
    @include('partials.landing-nav')
    {{-- Popcorn: .footer margin-top:-64px — pastki radius .main.cc-home | .main.cc-careers; @section('main_classes') --}}
    <main class="main @yield('main_classes', 'cc-home')">
        @yield('content')
    </main>
    @include('partials.landing-footer')
</div>
<script>
(function () {
    const btn = document.querySelector('.nav-mobile-btn');
    const nav = document.querySelector('.nav');
    if (!btn || !nav) return;
    btn.addEventListener('click', function () {
        nav.classList.toggle('open');
        this.querySelector('.hamburger_1_wrap')?.classList.toggle('open');
        document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
    });
    btn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            btn.click();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.classList.contains('open')) {
            nav.classList.remove('open');
            btn.querySelector('.hamburger_1_wrap')?.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
})();
</script>
@stack('scripts')
@yield('additional_scripts')
</body>
</html>
