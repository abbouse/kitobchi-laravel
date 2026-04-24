<?php
    $canonical = $canonical ?? url()->current();
    $ogType = $ogType ?? 'website';
    $robots = $robots ?? 'index,follow';
    $site = config('app.name', 'Kitobchi');
    $desc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description))), 160, '…');
    $ogTitle = \Illuminate\Support\Str::limit($title, 88, '…');
    $ogImage = $ogImage ?? config('seo.og_image') ?: url('/images/logo/logo_blue.png');
    if ($ogImage !== '' && ! str_starts_with($ogImage, 'http')) {
        $ogImage = url($ogImage);
    }
?>
<link rel="canonical" href="<?php echo e($canonical); ?>">
<meta name="robots" content="<?php echo e(e($robots)); ?>">
<meta name="description" content="<?php echo e(e($desc)); ?>">
<meta name="author" content="<?php echo e(e($site)); ?>">
<meta property="og:site_name" content="<?php echo e(e($site)); ?>">
<meta property="og:type" content="<?php echo e(e($ogType)); ?>">
<meta property="og:title" content="<?php echo e(e($ogTitle)); ?>">
<meta property="og:description" content="<?php echo e(e($desc)); ?>">
<meta property="og:url" content="<?php echo e($canonical); ?>">
<meta property="og:locale" content="uz_UZ">
<meta property="og:image" content="<?php echo e($ogImage); ?>">
<meta property="og:image:alt" content="<?php echo e(e($site)); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo e(e(\Illuminate\Support\Str::limit($title, 70, '…'))); ?>">
<meta name="twitter:description" content="<?php echo e(e($desc)); ?>">
<meta name="twitter:image" content="<?php echo e($ogImage); ?>">
<?php if(!empty($articleModified)): ?>
    <meta property="article:modified_time" content="<?php echo e($articleModified); ?>">
<?php endif; ?>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/partials/seo-social.blade.php ENDPATH**/ ?>