<?php $__env->startSection('title', $title ?? 'Kitobchi — Ilovaga o\'tish'); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $title ?? 'Kitobchi',
        'description' => $description ?? 'Kitobchi ilovasida ochish',
        'canonical' => request()->url(),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <meta name="apple-itunes-app" content="app-id=6753818078">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-redirect-page">
    <div class="page-padding">
        <div class="container">
            <div class="kc-redirect-card">
                <div class="eyebrow-pill u-mb-m">
                    <div class="eyebrow-pill-inner"><div>Kitobchi App</div></div>
                    <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                </div>

                <h1 class="section-heading u-mb-m"><?php echo e($title ?? 'Kitobchi'); ?></h1>
                <p class="subheading u-mb-xl"><?php echo e($description ?? 'Kitobchi mobil ilovasiga yo\'naltirilmoqda...'); ?></p>

                <div class="kc-redirect-action">
                    <button type="button" class="cta w-inline-block" id="openAppBtn" onclick="kcOpenApp()">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong><span class="kc-share-spinner" id="loadingSpinner" aria-hidden="true"></span><?php echo e(__('errors.share_open_app')); ?></strong></div>
                        </div>
                    </button>
                </div>

                <div class="kc-redirect-divider u-mt-xl u-mb-l">
                    <span><?php echo e(__('errors.share_download')); ?></span>
                </div>

                <div class="kc-redirect-stores">
                    <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" class="kc-store-badge" target="_blank" rel="noopener">
                        <img src="<?php echo e(asset('vendor/popcorn/images/feature-pin.png')); ?>" width="20" height="20" alt="">
                        <span>App Store</span>
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" class="kc-store-badge" target="_blank" rel="noopener">
                        <img src="<?php echo e(asset('vendor/popcorn/images/feature-event.png')); ?>" width="20" height="20" alt="">
                        <span>Google Play</span>
                    </a>
                </div>

                <div class="u-mt-xl">
                    <a href="<?php echo e(url('/')); ?>" class="kc-back-home-link">&larr; <?php echo e(__('errors.cta_home')); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const APP_SCHEME = <?php echo json_encode($appScheme ?? 'kitobchi://', 15, 512) ?>;
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.landing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/share/redirect.blade.php ENDPATH**/ ?>