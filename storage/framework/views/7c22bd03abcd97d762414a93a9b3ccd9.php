<nav class="nav">
    <div class="page-padding">
        <div class="container">
            <div class="nav-inner">
                <div class="nav-left">
                    <a href="<?php echo e(url('/')); ?>" class="nav-logo w-inline-block <?php echo e(request()->is('/') ? 'w--current' : ''); ?>" aria-label="<?php echo e(__('nav.logo_aria')); ?>">
                        <img src="<?php echo e(asset('images/logo/logo_black.png')); ?>" alt="<?php echo e(__('nav.logo_aria')); ?>" width="120" height="32" style="height:28px;width:auto;display:block">
                    </a>
                    <div class="nav-menu">
                        <a href="<?php echo e(url('/')); ?>#stats" class="nav-link"><?php echo e(__('nav.stats')); ?></a>
                        <a href="<?php echo e(url('/')); ?>#business" class="nav-link"><?php echo e(__('nav.stores')); ?></a>
                        <a href="<?php echo e(url('/')); ?>#faq" class="nav-link"><?php echo e(__('nav.faq')); ?></a>
                        <a href="<?php echo e(route('careers.index')); ?>" class="nav-link <?php echo e(request()->routeIs('careers.index') ? 'w--current' : ''); ?>"><?php echo e(__('nav.careers')); ?></a>
                        <a href="<?php echo e(route('legal.index')); ?>" class="nav-link <?php echo e(request()->routeIs('legal.index', 'legal.policy') ? 'w--current' : ''); ?>"><?php echo e(__('nav.legal')); ?></a>
                    </div>
                </div>
                <div class="nav-right">
                    <?php echo $__env->make('partials.lang-switcher', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta cc-nav w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-hidden="true">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                    </a>
                    <div class="nav-mobile-btn" role="button" tabindex="0" aria-label="<?php echo e(__('nav.menu_aria')); ?>">
                        <div class="hamburger_1_wrap">
                            <div class="hamburger_1_line"></div>
                            <div class="hamburger_embed w-embed"></div>
                            <div class="hamburger_1_line"></div>
                        </div>
                    </div>
                    <div class="nav-menu">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-label="<?php echo e(__('nav.download_aria')); ?>">
                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                            <div class="cta-inner">
                                <div><strong><?php echo e(__('nav.download')); ?></strong></div>
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
                        <a href="<?php echo e(url('/')); ?>#stats" class="nav-mobile-link"><?php echo e(__('nav.stats')); ?></a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.1s;">
                        <a href="<?php echo e(url('/')); ?>#business" class="nav-mobile-link"><?php echo e(__('nav.stores')); ?></a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.13s;">
                        <a href="<?php echo e(url('/')); ?>#faq" class="nav-mobile-link"><?php echo e(__('nav.faq')); ?></a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.15s;">
                        <a href="<?php echo e(route('careers.index')); ?>" class="nav-mobile-link <?php echo e(request()->routeIs('careers.index') ? 'w--current' : ''); ?>"><?php echo e(__('nav.careers')); ?></a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.17s;">
                        <a href="<?php echo e(route('legal.index')); ?>" class="nav-mobile-link <?php echo e(request()->routeIs('legal.index', 'legal.policy') ? 'w--current' : ''); ?>"><?php echo e(__('nav.legal')); ?></a>
                    </div>
                    <div class="mobile-menu-fade" style="--delay: 0.22s;">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="nav-mobile-link cc-small">Google Play</a>
                        <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener" class="nav-mobile-link cc-small">App Store</a>
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta-inner w-button kc-smart-store" style="margin-top:12px;display:inline-block" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078"><?php echo e(__('nav.download')); ?></a>
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
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/partials/landing-nav.blade.php ENDPATH**/ ?>