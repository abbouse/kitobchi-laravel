<!DOCTYPE html>
<html lang="uz" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title><?php echo $__env->yieldContent('title', 'Kirish'); ?> — Veritas Admin</title>
  <style>[x-cloak]{display:none!important}</style>
  <script>
    (function () {
      const saved = localStorage.getItem('a122-theme') || localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const dark = saved ? saved === 'dark' : prefersDark;
      document.documentElement.classList.toggle('dark', dark);
      document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    })();
  </script>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/js/a122-admin.js']); ?>
  <?php echo $__env->yieldPushContent('styles'); ?>
  <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="a122-login-body text-gray-900 dark:text-gray-100 antialiased">
  <?php echo $__env->yieldContent('content'); ?>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/layouts/guest.blade.php ENDPATH**/ ?>