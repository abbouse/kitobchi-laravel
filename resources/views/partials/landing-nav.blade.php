<nav class="nav">
    <div class="page-padding">
        <div class="container">
            <div class="nav-inner">
                <div class="nav-left">
                    <a href="{{ url('/') }}" class="nav-logo w-inline-block {{ request()->is('/') ? 'w--current' : '' }}" aria-label="{{ __('nav.logo_aria') }}">
                        <img src="{{ asset('images/logo/logo_black.png') }}" alt="{{ __('nav.logo_aria') }}" width="120" height="32" style="height:28px;width:auto;display:block">
                    </a>
                    <div class="nav-menu">
                        <a href="{{ url('/') }}#stats" class="nav-link">{{ __('nav.stats') }}</a>
                        <a href="{{ url('/') }}#business" class="nav-link">{{ __('nav.stores') }}</a>
                        <a href="{{ url('/') }}#faq" class="nav-link">{{ __('nav.faq') }}</a>
                        <a href="{{ route('careers.index') }}" class="nav-link {{ request()->routeIs('careers.index') ? 'w--current' : '' }}">{{ __('nav.careers') }}</a>
                        <a href="{{ route('legal.index') }}" class="nav-link {{ request()->routeIs('legal.index', 'legal.policy') ? 'w--current' : '' }}">{{ __('nav.legal') }}</a>
                        <a href="{{ route('contact.index') }}" class="nav-link {{ request()->routeIs('contact.index') ? 'w--current' : '' }}">{{ __('nav.contact') }}</a>
                    </div>
                </div>
                <div class="nav-right">
                    @include('partials.lang-switcher')
                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta cc-nav w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-hidden="true">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                    </a>
                    <div class="nav-mobile-btn" role="button" tabindex="0" aria-label="{{ __('nav.menu_aria') }}">
                        <div class="hamburger_1_wrap">
                            <div class="hamburger_1_line"></div>
                            <div class="hamburger_embed w-embed"></div>
                            <div class="hamburger_1_line"></div>
                        </div>
                    </div>
                    <div class="nav-menu">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-label="{{ __('nav.download_aria') }}">
                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                            <div class="cta-inner">
                                <div><strong>{{ __('nav.download') }}</strong></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="nav-mobile-menu">
        <div class="nav-mobile-menu-top"></div>
        <div class="page-padding">
            <div class="container">
                <div class="mobile-menu-inner">
                    <div class="mobile-menu-fade" style="--delay: 0.05s;">
                        <a href="{{ url('/') }}#stats" class="nav-mobile-link">{{ __('nav.stats') }}</a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.1s;">
                        <a href="{{ url('/') }}#business" class="nav-mobile-link">{{ __('nav.stores') }}</a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.13s;">
                        <a href="{{ url('/') }}#faq" class="nav-mobile-link">{{ __('nav.faq') }}</a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.15s;">
                        <a href="{{ route('careers.index') }}" class="nav-mobile-link {{ request()->routeIs('careers.index') ? 'w--current' : '' }}">{{ __('nav.careers') }}</a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.17s;">
                        <a href="{{ route('legal.index') }}" class="nav-mobile-link {{ request()->routeIs('legal.index', 'legal.policy') ? 'w--current' : '' }}">{{ __('nav.legal') }}</a>
                        <a href="{{ route('contact.index') }}" class="nav-mobile-link {{ request()->routeIs('contact.index') ? 'w--current' : '' }}">{{ __('nav.contact') }}</a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.22s;">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="nav-mobile-link cc-small">Google Play</a>
                        <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener" class="nav-mobile-link cc-small">App Store</a>
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta-inner w-button kc-smart-store" style="margin-top:12px;display:inline-block" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078">{{ __('nav.download') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<script>
(function () {
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) {
            return;
        }
        document.querySelectorAll('details.kc-lang[open]').forEach(function (d) {
            if (!d.contains(t)) {
                d.removeAttribute('open');
            }
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }
        document.querySelectorAll('details.kc-lang[open]').forEach(function (d) {
            d.removeAttribute('open');
        });
    });
})();
</script>
