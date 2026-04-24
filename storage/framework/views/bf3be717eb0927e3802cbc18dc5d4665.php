<!DOCTYPE html>
<html lang="<?php echo e(config('landing_locales.html_lang.'.app()->getLocale(), 'uz')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#393737">
    <title><?php echo $__env->yieldContent('title', __('errors.meta_title')); ?></title>
    <?php echo $__env->yieldPushContent('meta'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('vendor/popcorn/popcorn-2024.webflow.shared.cef7bd9c3.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/popcorn/popcorn-embed.css')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/kitobchi-popcorn.css']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="kc-landing kc-public-shell">
<div class="page-wrapper">
    <header class="kc-shell-nav">
        <div class="page-padding">
            <div class="container">
                <a href="<?php echo e(url('/')); ?>" class="kc-shell-logo w-inline-block" aria-label="<?php echo e(__('nav.logo_aria')); ?>">
                    <img src="<?php echo e(asset('images/logo/logo_black.png')); ?>" alt="" width="120" height="32" style="height:28px;width:auto;display:block">
                </a>
            </div>
        </div>
    </header>
    <main class="main cc-home kc-shell-main">
        <div class="page-padding">
            <div class="container">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </div>
    </main>
</div>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/layouts/error-public.blade.php ENDPATH**/ ?>