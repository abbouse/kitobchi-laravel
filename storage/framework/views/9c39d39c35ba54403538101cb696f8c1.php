<?php $__env->startSection('title', $title ?? __('errors.meta_title')); ?>

<?php $__env->startPush('meta'); ?>
    <meta name="description" content="<?php echo e($description ?? 'Kitobchi'); ?>">
    <meta property="og:title" content="<?php echo e($title ?? 'Kitobchi'); ?>">
    <meta property="og:description" content="<?php echo e($description ?? 'Kitobchi'); ?>">
    <meta property="og:image" content="<?php echo e(asset('images/og-cover.png')); ?>">
    <meta property="og:url" content="<?php echo e(request()->url()); ?>">
    <meta property="og:type" content="website">
    <meta name="apple-itunes-app" content="app-id=6753818078">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="kc-share-card">
        <div class="kc-error-pill-wrap">
            <div class="eyebrow-pill">
                <div class="eyebrow-pill-inner"><div>Kitobchi</div></div>
                <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
            </div>
        </div>
        <h1 class="section-heading kc-error-heading"><?php echo e($title ?? 'Kitobchi'); ?></h1>
        <p class="subheading kc-error-lead"><?php echo e($description ?? ''); ?></p>

        <div class="kc-error-actions">
            <button type="button" class="cta w-inline-block" id="openAppBtn" onclick="kcOpenApp()">
                <div class="cta-bg u-rainbow u-blur-perf"></div>
                <div class="cta-inner">
                    <div><strong><span class="kc-share-spinner" id="loadingSpinner" aria-hidden="true"></span><?php echo e(__('errors.share_open_app')); ?></strong></div>
                </div>
            </button>
        </div>

        <p class="kc-share-divider"><span><?php echo e(__('errors.share_download')); ?></span></p>

        <div class="kc-share-stores">
            <a href="https://apps.apple.com/app/id6753818078" class="kc-store-link" target="_blank" rel="noopener">
                <svg class="kc-store-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                App Store
            </a>
            <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" class="kc-store-link" target="_blank" rel="noopener">
                <svg class="kc-store-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.18 23.76c.3.17.64.22.99.14l12.75-7.36-2.88-2.88-10.86 10.1zm-1.67-20.1c-.06.2-.1.42-.1.65v19.38c0 .23.04.45.1.65l.07.06 10.85-10.85v-.25L1.58 3.6l-.07.06zM20.56 10.4l-2.88-1.66-3.23 3.23 3.23 3.23 2.9-1.67c.83-.48.83-1.26-.02-1.73zm-18.3 12.24l12.75-7.36-2.88-2.88L2.38 22.49l-.12 1.15z"/></svg>
                Google Play
            </a>
        </div>

        <p class="kc-share-foot">
            <a href="<?php echo e(url('/')); ?>" class="kc-share-home-link"><?php echo e(__('errors.cta_home')); ?></a>
        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const APP_SCHEME = <?php echo json_encode($appScheme ?? 'kitobchi://', 15, 512) ?>;
    const APP_STORE_URL = 'https://apps.apple.com/app/id6753818078';
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

<?php echo $__env->make('layouts.error-public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/share/redirect.blade.php ENDPATH**/ ?>