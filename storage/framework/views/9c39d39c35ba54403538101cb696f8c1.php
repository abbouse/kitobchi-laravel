<?php
    $pageTitle = $title ?? 'Kitobchi — Mobil Ilovada Ochish';
    $pageDesc = $description ?? 'Kitobchi mobil ilovasiga yo\'naltirilmoqda...';
    $productImg = isset($image) ? $image : asset('images/logo/logo_blue.png');
    $redirectUrl = $appScheme ?? 'kitobchi://';
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data=' . urlencode(request()->url());
?>

<?php $__env->startSection('title', $pageTitle); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $pageTitle,
        'description' => $pageDesc,
        'canonical' => request()->url(),
        'ogImage' => $productImg,
        'ogType' => 'product',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <meta name="apple-itunes-app" content="app-id=6753818078">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-redirect-page">
    <div class="page-padding">
        <div class="container">
            <div class="kc-redirect-card">
                
                <!-- Eyebrow Tag -->
                <div class="eyebrow-pill u-mb-m">
                    <div class="eyebrow-pill-inner">
                        <div><strong>Kitobchi Mobil Ilovasi</strong></div>
                    </div>
                    <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                </div>

                <!-- Product Showcase Hero -->
                <?php if(isset($product) && $product): ?>
                    <div class="kc-redirect-product-hero">
                        <div class="kc-redirect-cover">
                            <?php if($product->first_image): ?>
                                <img src="<?php echo e(asset('storage/' . $product->first_image)); ?>" alt="<?php echo e($product->name); ?>">
                            <?php else: ?>
                                <div class="ph">&#128218;</div>
                            <?php endif; ?>
                        </div>
                        <div class="kc-redirect-details">
                            <h1 class="kc-redirect-title"><?php echo e($product->name); ?></h1>
                            <?php if(isset($product->author) && $product->author): ?>
                                <div class="kc-redirect-meta">Muallif: <strong><?php echo e($product->author); ?></strong></div>
                            <?php endif; ?>
                            
                            <div class="kc-redirect-price-row">
                                <span class="kc-redirect-price"><?php echo e(number_format(($product->discountPrice ?: $product->discount_price) ?: $product->price)); ?> UZS</span>
                                <?php if(($product->discountPrice ?: $product->discount_price) > 0 && ($product->discountPrice ?: $product->discount_price) < $product->price): ?>
                                    <del class="kc-redirect-old-price"><?php echo e(number_format($product->price)); ?> UZS</del>
                                <?php endif; ?>
                                <span class="kc-stock-badge <?php echo e(($product->stock ?? $product->count ?? 1) > 0 ? 'in-stock' : 'out-stock'); ?>">
                                    <?php echo e(($product->stock ?? $product->count ?? 1) > 0 ? '✓ Sotuvda mavjud' : 'Omborda tugagan'); ?>

                                </span>
                            </div>

                            <?php if($product->description): ?>
                                <p class="kc-redirect-desc">
                                    <?php echo e(\Illuminate\Support\Str::limit(strip_tags($product->description), 160)); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <h1 class="section-heading u-mb-m"><?php echo e($pageTitle); ?></h1>
                    <p class="subheading u-mb-l"><?php echo e($pageDesc); ?></p>
                <?php endif; ?>

                <!-- Redirect Progress Bar -->
                <div class="kc-redirect-progress-wrap u-my-l">
                    <div class="kc-redirect-progress-bar" id="redirectProgressBar"></div>
                    <div class="kc-redirect-status-text" id="redirectStatusText">Mobil ilovaga yo'naltirilmoqda...</div>
                </div>

                <!-- Primary CTA Action -->
                <div class="kc-redirect-actions">
                    <a href="<?php echo e($redirectUrl); ?>" class="cta w-inline-block" id="openAppBtn" onclick="kcOpenApp(event)">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong><span class="kc-share-spinner" id="loadingSpinner" aria-hidden="true"></span><?php echo e(__('errors.share_open_app')); ?></strong></div>
                        </div>
                    </a>

                    <?php if(isset($webUrl) && $webUrl): ?>
                        <div class="u-mt-m">
                            <a href="<?php echo e($webUrl); ?>" class="kc-redirect-web-btn">Veb-saytda ko'rish &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- App Store & Google Play Badges -->
                <div class="kc-redirect-divider u-mt-xl u-mb-l">
                    <span>Yoki mobil ilovani yuklab oling:</span>
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
                    <a href="<?php echo e(url('/')); ?>" class="kc-back-home-link">&larr; Bosh sahifaga qaytish</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const APP_SCHEME = <?php echo json_encode($redirectUrl, 15, 512) ?>;
    const APP_STORE_URL = 'https://apps.apple.com/uz/app/kitobchi/id6753818078';
    const PLAY_STORE_URL = 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi';
    let appOpened = false;

    window.kcOpenApp = function (e) {
        if (e) e.preventDefault();
        const spin = document.getElementById('loadingSpinner');
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
            if (spin) spin.classList.remove('is-on');
        }, 2200);
    };

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            appOpened = true;
        }
    });

    // Auto trigger deep-link on mobile devices
    window.addEventListener('load', function () {
        const progressBar = document.getElementById('redirectProgressBar');
        if (progressBar) {
            progressBar.style.width = '100%';
        }
        if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent || '')) {
            setTimeout(function () {
                if (typeof kcOpenApp === 'function') {
                    kcOpenApp();
                }
            }, 700);
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.landing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/share/redirect.blade.php ENDPATH**/ ?>