<!DOCTYPE html>
<html lang="<?php echo e(config('landing_locales.html_lang.'.app()->getLocale(), 'uz')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#393737">
    <?php echo $__env->yieldPushContent('meta'); ?>
    <title><?php echo $__env->yieldContent('title', 'Kitobchi'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('vendor/popcorn/popcorn-2024.webflow.shared.cef7bd9c3.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/popcorn/popcorn-embed.css')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/kitobchi-popcorn.css']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo $__env->yieldContent('additional_styles'); ?>
</head>
<body class="kc-landing kc-legal-doc">
<div class="page-wrapper">
    <?php echo $__env->make('partials.landing-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    
    <main class="main <?php echo $__env->yieldContent('main_classes', 'cc-home'); ?>">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php echo $__env->make('partials.landing-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php echo $__env->yieldPushContent('scripts'); ?>
<?php echo $__env->yieldContent('additional_scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/legal/layouts/app.blade.php ENDPATH**/ ?>