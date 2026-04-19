<!DOCTYPE html>
<html lang="uz" class="h-full">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>"/>
    <title><?php echo $__env->yieldContent('title', 'Kirish'); ?> — kitobchi. Admin</title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

    <script>
    (function () {
        var sessionTheme = <?php echo json_encode(session('theme', 'dark'), 512) ?>;
        var ls = localStorage.getItem('kitobchi_theme');
        var t = (ls === 'light' || ls === 'dark') ? ls : sessionTheme;
        document.documentElement.classList.toggle('dark', t === 'dark');
        document.documentElement.setAttribute('data-bs-theme', t);
    })();
    </script>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="min-h-screen font-outfit antialiased bg-gray-50 text-gray-800 selection:bg-brand-500/20 selection:text-gray-900 dark:bg-gray-900 dark:text-white/90 dark:selection:bg-brand-400/25 dark:selection:text-white">
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/layouts/guest.blade.php ENDPATH**/ ?>