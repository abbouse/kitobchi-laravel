@extends('layouts.landing')

@section('title', $title ?? 'Kitobchi — Ilovaga o\'tish')

@push('meta')
    @include('partials.seo-social', [
        'title' => $title ?? 'Kitobchi',
        'description' => $description ?? 'Kitobchi ilovasida ochish',
        'canonical' => request()->url(),
    ])
    <meta name="apple-itunes-app" content="app-id=6753818078">
@endpush

@section('content')
<div class="kc-redirect-page">
    <div class="page-padding">
        <div class="container">
            <div class="kc-redirect-card">
                <div class="eyebrow-pill u-mb-m">
                    <div class="eyebrow-pill-inner"><div>Kitobchi App</div></div>
                    <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                </div>

                <h1 class="section-heading u-mb-m">{{ $title ?? 'Kitobchi' }}</h1>
                <p class="subheading u-mb-xl">{{ $description ?? 'Kitobchi mobil ilovasiga yo\'naltirilmoqda...' }}</p>

                <div class="kc-redirect-action">
                    <button type="button" class="cta w-inline-block" id="openAppBtn" onclick="kcOpenApp()">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong><span class="kc-share-spinner" id="loadingSpinner" aria-hidden="true"></span>{{ __('errors.share_open_app') }}</strong></div>
                        </div>
                    </button>
                </div>

                <div class="kc-redirect-divider u-mt-xl u-mb-l">
                    <span>{{ __('errors.share_download') }}</span>
                </div>

                <div class="kc-redirect-stores">
                    <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" class="kc-store-badge" target="_blank" rel="noopener">
                        <img src="{{ asset('vendor/popcorn/images/feature-pin.png') }}" width="20" height="20" alt="">
                        <span>App Store</span>
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" class="kc-store-badge" target="_blank" rel="noopener">
                        <img src="{{ asset('vendor/popcorn/images/feature-event.png') }}" width="20" height="20" alt="">
                        <span>Google Play</span>
                    </a>
                </div>

                <div class="u-mt-xl">
                    <a href="{{ url('/') }}" class="kc-back-home-link">&larr; {{ __('errors.cta_home') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const APP_SCHEME = @json($appScheme ?? 'kitobchi://');
    const APP_STORE_URL = 'https://apps.apple.com/uz/app/kitobchi/id6753818078';
    const PLAY_STORE_URL = 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi';
    let appOpened = false;

    window.kcOpenApp = function () {
        const btn = document.getElementById('openAppBtn');
        const spin = document.getElementById('loadingSpinner');
        if (btn) btn.disabled = true;
        if (spin) spin.classList.add('is-on');
        window.location.href = APP_SCHEME;
        setTimeout(function () {
            if (!appOpened && !document.hidden) {
                const ua = navigator.userAgent || '';
                if (/iPad|iPhone|iPod/i.test(ua)) {
                    window.location.href = APP_STORE_URL;
                } else if (/Android/i.test(ua)) {
                    window.location.href = PLAY_STORE_URL;
                }
            }
            if (btn) btn.disabled = false;
            if (spin) spin.classList.remove('is-on');
        }, 2000);
    };

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            appOpened = true;
        }
    });

    window.addEventListener('load', function () {
        if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent || '')) {
            setTimeout(function () {
                if (typeof kcOpenApp === 'function') {
                    kcOpenApp();
                }
            }, 600);
        }
    });
})();
</script>
@endpush
