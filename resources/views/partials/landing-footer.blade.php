<div class="footer">
    <div class="page-padding">
        <div class="container">
            <div class="footer-menu">
                <a href="{{ url('/') }}" class="footer-logo w-inline-block" aria-label="{{ __('nav.logo_aria') }}">
                    <div class="svg-embed cc-logo-icon w-embed kc-footer-logo-mark">
                        <img src="{{ asset('images/logo/logo_white.png') }}" alt="" width="132" height="36" decoding="async" class="kc-footer-logo-img" loading="lazy">
                    </div>
                </a>
                <div class="footer-cell">
                    <div class="footer-heading">{{ __('nav.footer_good') }}</div>
                    <!-- <a href="{{ url('/') }}#stats" class="footer-link">{{ __('nav.stats') }}</a> -->
                    <a href="{{ route('careers.index') }}" class="footer-link">{{ __('nav.careers') }}</a>
                    <a href="https://t.me/kitobchi_market" target="_blank" rel="noopener" class="footer-link">Telegram</a>
                    <a href="https://instagram.com/kitobchi_market" target="_blank" rel="noopener" class="footer-link">Instagram</a>
                    <a href="{{ route('developers.api-docs') }}" class="footer-link">{{ __('nav.api_docs') }}</a>
                </div>
                <div class="footer-cell">
                    <div class="footer-heading">{{ __('nav.footer_boring') }}</div>
                    @foreach(\App\Models\Policy::active()->with('translations')->orderBy('sort_order')->limit(2)->get() as $p)
                        <a href="{{ route('legal.policy', $p->slug) }}" class="footer-link">{{ $p->localizedTitle() }}</a>
                    @endforeach
                    <a href="{{ route('legal.index') }}" class="footer-link">{{ __('nav.footer_all_doc') }}</a>
                </div>
                <div class="footer-cell">
                    <div class="footer-heading">{{ __('nav.footer_cool') }}</div>
                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="footer-link">Google Play</a>
                    <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener" class="footer-link">App Store</a>
                </div>
            </div>
            <div class="footer-large-logo w-embed kc-footer-mega-wrap">
                <svg class="kc-footer-word-svg" width="100%" height="100%" viewBox="0 0 1200 246" preserveAspectRatio="xMidYMin meet" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <text x="600" y="170" fill="#fff" font-family="Messina Sans, sans-serif" font-size="240" font-weight="600" letter-spacing="-12.5" text-anchor="middle">kitobchi.</text>
                </svg>
            </div>
        </div>
    </div>
</div>
