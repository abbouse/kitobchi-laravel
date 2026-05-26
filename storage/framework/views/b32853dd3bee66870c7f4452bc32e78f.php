<!DOCTYPE html>
<html lang="uz" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — Kitobchi Admin</title>
  <style>[x-cloak]{display:none!important}</style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
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

  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/css/a122-bootstrap-admin.css', 'resources/js/a122-admin.js']); ?>
  <?php echo $__env->yieldPushContent('styles'); ?>
  <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="kc-admin-body overflow-x-hidden">
  <div
    id="a122-shell"
    data-sidebar-shell
    class="kc-shell d-flex sidebar-expanded">
    <?php echo $__env->make('a122.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="kc-main flex-grow-1 d-flex flex-column">
      <?php echo $__env->make('a122.partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <div class="kc-main__viewport flex-grow-1 d-flex flex-column">
        <main class="kc-content flex-grow-1">
          <div class="container-fluid px-3 px-lg-4 px-xxl-5">
            <div class="mx-auto kc-shell-width">
              <?php echo $__env->yieldContent('content'); ?>
            </div>
          </div>
        </main>
        <footer class="kc-footer border-top bg-white bg-opacity-75">
          <div class="container-fluid px-3 px-lg-4 px-xxl-5 py-3">
            <div class="mx-auto kc-shell-width d-flex flex-wrap align-items-center justify-content-between gap-2 small text-secondary">
              <span>© <?php echo e(date('Y')); ?> Kitobchi Admin</span>
              <span>Calm, human-centered operations workspace</span>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <?php echo $__env->yieldPushContent('vendor_scripts'); ?>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/layouts/admin.blade.php ENDPATH**/ ?>