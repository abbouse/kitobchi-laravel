<!DOCTYPE html>
<html lang="uz" class="h-full kc-panel-root">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>"/>
<title><?php echo $__env->yieldContent('title', 'Admin paneli'); ?> | Kitobchi</title>
<style>[x-cloak]{display:none!important}</style>


<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>


<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        current: '<?php echo e(session('theme', 'dark')); ?>',
        init() {
            const saved = localStorage.getItem('kitobchi_theme') || this.current;
            this.apply(saved);
        },
        toggle() {
            const next = this.current === 'dark' ? 'light' : 'dark';
            this.apply(next);
            localStorage.setItem('kitobchi_theme', next);
            fetch('<?php echo e(route('panel.theme')); ?>', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: next })
            });
        },
        apply(theme) {
            this.current = theme;
            const html = document.documentElement;
            const body = document.body;
            if (theme === 'dark') {
                html.classList.add('dark');
                body.classList.add('dark', 'bg-gray-900');
                body.classList.remove('bg-gray-50');
            } else {
                html.classList.remove('dark');
                body.classList.remove('dark', 'bg-gray-900');
                body.classList.add('bg-gray-50');
            }
            html.setAttribute('data-bs-theme', theme);
        }
    });

    Alpine.store('sidebar', {
        isExpanded: window.innerWidth >= 1280,
        isMobileOpen: false,
        toggleExpanded() { this.isExpanded = !this.isExpanded; this.isMobileOpen = false; },
        toggleMobileOpen() { this.isMobileOpen = !this.isMobileOpen; },
        setMobileOpen(val) { this.isMobileOpen = val; },
    });
});
</script>


<script>
(function() {
    const theme = localStorage.getItem('kitobchi_theme') || '<?php echo e(session('theme', 'dark')); ?>';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-bs-theme', 'light');
    }
})();
</script>

<?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="bg-gray-50 selection:bg-brand-500/20 selection:text-gray-900 dark:selection:bg-brand-400/25 dark:selection:text-white"
      x-data="{ loaded: true }"
      x-init="
        $store.theme.init();
        $store.sidebar.isExpanded = window.innerWidth >= 1280;
        const checkResize = () => {
          if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
          } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
          }
        };
        window.addEventListener('resize', checkResize);
      ">

  
  <div class="fixed left-0 top-0 z-[999999] flex h-screen w-screen items-center justify-center bg-white dark:bg-gray-900"
       x-show="loaded"
       x-transition.opacity.duration.200ms
       x-init="
         const done = () => { setTimeout(() => { loaded = false }, 280) };
         if (document.readyState === 'loading') {
           document.addEventListener('DOMContentLoaded', done, { once: true });
         } else {
           done();
         }
       ">
    <div class="h-12 w-12 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
  </div>

  <div class="min-h-screen xl:flex">

    
    <div :class="$store.sidebar.isMobileOpen ? 'block xl:hidden' : 'hidden'"
         @click="$store.sidebar.setMobileOpen(false)"
         class="fixed z-[100010] h-screen w-full bg-gray-900/50"></div>

    
    <?php echo $__env->make('panel.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="flex-1 transition-all duration-300 ease-in-out"
         :class="{
           'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen,
           'xl:ml-[90px]':  !$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen,
           'ml-0': $store.sidebar.isMobileOpen
         }">

      
      <?php echo $__env->make('panel.partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      
      <div id="panel-main" class="kc-panel-main w-full min-w-0 p-4 pb-10 md:p-6 md:pb-12 xl:px-8 2xl:px-10">
        <div class="kc-page-shell">

        
        <?php if(session('success')): ?>
        <div class="p-alert success fade-up mb-4">
          <i class="bi bi-check-circle-fill"></i> <?php echo e(session('success')); ?>

        </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
        <div class="p-alert danger fade-up mb-4">
          <i class="bi bi-x-circle-fill"></i> <?php echo e(session('error')); ?>

        </div>
        <?php endif; ?>
        <?php if(session('warning')): ?>
        <div class="p-alert warning fade-up mb-4">
          <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e(session('warning')); ?>

        </div>
        <?php endif; ?>

        <div class="kc-page-stack space-y-6 md:space-y-7">
          <?php echo $__env->yieldContent('content'); ?>
        </div>
        </div>
      </div>

    </div>
  </div>

  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
  // Auto-hide flash alerts
  setTimeout(() => {
    document.querySelectorAll('.p-alert').forEach(el => {
      el.style.transition = 'opacity .4s, transform .4s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-4px)';
      setTimeout(() => el.remove(), 400);
    });
  }, 4000);
  </script>

  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/layouts/panel.blade.php ENDPATH**/ ?>