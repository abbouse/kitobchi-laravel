<!DOCTYPE html>
<html lang="{{ config('landing_locales.html_lang.'.app()->getLocale(), 'uz') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#393737">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20260822">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20260822">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=20260822">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=20260822">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}?v=20260822">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=20260822">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=20260822">

    @stack('meta')
    @php
        $seoService = app(\App\Services\SeoService::class);
        $websiteSchema = $seoService->buildWebSiteSearchSchema();
    @endphp
    <script type="application/ld+json">
        {!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <title>@yield('title', 'Kitobchi — kitob va kanselyariya marketpleysi')</title>
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-2024.webflow.shared.cef7bd9c3.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/popcorn/popcorn-embed.css') }}">
    @vite(['resources/css/kitobchi-popcorn.css'])
    @stack('head')
</head>
<body class="kc-landing">
<div class="page-wrapper">
    @include('partials.landing-nav')
    <main class="main cc-home">
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
(function () {
    function kcIsAppleDevice() {
        var ua = navigator.userAgent || '';
        if (/Android/i.test(ua)) {
            return false;
        }
        return /iPhone|iPod|iPad|CPU iPhone|CPU iPad|Macintosh|Mac OS X|MacIntel/i.test(ua);
    }
    document.querySelectorAll('a.kc-smart-store[data-play][data-appstore]').forEach(function (a) {
        var play = a.getAttribute('data-play');
        var appstore = a.getAttribute('data-appstore');
        if (!play || !appstore) {
            return;
        }
        a.setAttribute('href', kcIsAppleDevice() ? appstore : play);
    });
})();
(function () {
    /* popcorn-html / Webflow: `.scroll-observe` → `.visible` (`.visible .country-tag-wrap` animatsiya) */
    var nodes = document.querySelectorAll('.scroll-observe');
    if (!nodes.length) {
        return;
    }
    if (!('IntersectionObserver' in window)) {
        nodes.forEach(function (el) {
            el.classList.add('visible');
        });
        return;
    }
    nodes.forEach(function (el) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                el.classList.add('visible');
                io.unobserve(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
        io.observe(el);
    });
})();
</script>
<script>
(function () {
    var canvas = document.getElementById('uzMapCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var dpr = window.devicePixelRatio || 1;
    var SPACING = 16, DOT_R = 1.8;
    var t = 0;

    function resize() {
        var w = canvas.offsetWidth;
        var h = canvas.offsetHeight;
        canvas.width  = w * dpr;
        canvas.height = h * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();
    window.addEventListener('resize', resize);

    function draw() {
        var w = canvas.offsetWidth;
        var h = canvas.offsetHeight;
        ctx.clearRect(0, 0, w, h);
        t += 0.006;
        var cols = Math.ceil(w / SPACING) + 1;
        var rows = Math.ceil(h / SPACING) + 1;
        for (var c = 0; c < cols; c++) {
            for (var r = 0; r < rows; r++) {
                var x = c * SPACING;
                var y = r * SPACING;
                var wave = Math.sin(t + (x + y) * 0.055);
                var alpha = 0.13 + 0.22 * (wave + 1) / 2;
                ctx.beginPath();
                ctx.arc(x, y, DOT_R, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(100, 145, 185, ' + alpha + ')';
                ctx.fill();
            }
        }
        requestAnimationFrame(draw);
    }
    draw();
})();
(function () {
    function kcFmt(n) {
        n = Math.round(n);
        if (n < 1000) return String(n);
        if (n < 1000000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    }
    function kcEase(t) {
        return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    }
    function kcCountUp(valEl, target, duration) {
        var start = null;
        valEl.textContent = '0';
        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            valEl.textContent = kcFmt(target * kcEase(progress));
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    var statsSection = document.getElementById('stats');
    if (!statsSection) return;
    var cards = statsSection.querySelectorAll('.js-count[data-count-target]');
    if (!cards.length) return;
    var fired = false;
    var observer = new IntersectionObserver(function (entries) {
        if (fired) return;
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            fired = true;
            observer.disconnect();
            cards.forEach(function (card, i) {
                var target = parseInt(card.getAttribute('data-count-target'), 10);
                var valEl = card.querySelector('.kc-stat-num__val');
                if (!valEl || !target) return;
                setTimeout(function () { kcCountUp(valEl, target, 1600); }, i * 120);
            });
        });
    }, { threshold: 0.25 });
    observer.observe(statsSection);
})();
</script>
@include('partials.app-download-bottomsheet')
</body>
</html>
