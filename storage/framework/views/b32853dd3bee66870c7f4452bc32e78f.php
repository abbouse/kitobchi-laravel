<!DOCTYPE html>
<html lang="uz" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — Kitobchi Admin</title>
  <style>[x-cloak]{display:none!important}</style>
  <script>
    (function () {
      const saved = localStorage.getItem('a122-theme') || localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const dark = saved ? saved === 'dark' : prefersDark;
      const sidebarExpanded = JSON.parse(localStorage.getItem('a122-sidebar-expanded') ?? 'true');
      document.documentElement.classList.toggle('dark', dark);
      document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-sidebar-expanded', sidebarExpanded ? 'true' : 'false');
    })();
  </script>

  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/css/kitobchi-pastel.css', 'resources/js/a122-admin.js']); ?>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>

  <?php echo $__env->yieldPushContent('styles'); ?>
  <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="a122-admin-body text-gray-900 dark:text-gray-100 antialiased overflow-x-hidden">
  <div
    id="a122-shell"
    data-sidebar-shell
    class="min-h-screen a122-shell sidebar-expanded">
    <?php echo $__env->make('a122.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="flex-1 min-w-0 flex flex-col">
      <?php echo $__env->make('a122.partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <main class="a122-main flex-1 px-4 sm:px-6 lg:px-8 py-6">
        <div class="mx-auto w-full max-w-[1720px] space-y-5">
        <?php echo $__env->yieldContent('content'); ?>
        </div>
      </main>
      <footer class="a122-footer px-6 py-4 text-xs">
        <div class="mx-auto flex w-full max-w-[1720px] items-center justify-between gap-3 flex-wrap">
          <span>© <?php echo e(date('Y')); ?> Kitobchi Admin</span>
          <span>A122 control workspace</span>
        </div>
      </footer>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <?php echo $__env->yieldPushContent('vendor_scripts'); ?>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/layouts/admin.blade.php ENDPATH**/ ?>