<?php
  $groups = [
    ['title' => 'ASOSIY MENYU','items' => [
      ['label'=>'Dashboard','route'=>'admin.dashboard','icon'=>'layout-dashboard'],
      ['label'=>'Foydalanuvchilar','route'=>'admin.users.index','icon'=>'users'],
    ]],
    ['title' => 'MAHSULOTLAR','items' => [
      ['label'=>'Kitoblar','route'=>'admin.books.index','icon'=>'book-open'],
      ['label'=>'Kanstovar','route'=>'admin.stationery.index','icon'=>'pencil-ruler'],
      ['label'=>'Kitob kategoriyalari','route'=>'admin.book-categories.index','icon'=>'layers'],
      ['label'=>'Kanstovar kategoriyalari','route'=>'admin.stationery-categories.index','icon'=>'tags'],
      ['label'=>'Reels','route'=>'admin.reels.index','icon'=>'film'],
      ['label'=>'Market yangiliklari','route'=>'admin.news.index','icon'=>'newspaper'],
    ]],
    ['title' => 'BUYURTMALAR','items' => [
      ['label'=>'Buyurtmalar','route'=>'admin.orders.index','icon'=>'shopping-cart'],
      ['label'=>'Seller buyurtmalari','route'=>'admin.seller-orders.index','icon'=>'package'],
      ['label'=>'Kuryer buyurtmalari','route'=>'admin.courier-orders.index','icon'=>'truck'],
    ]],
    ['title' => 'BIZNES','items' => [
      ['label'=>'Sotuvchilar','route'=>'admin.sellers.index','icon'=>'store'],
      ['label'=>'Tranzaksiyalar','route'=>'admin.transactions.index','icon'=>'arrow-left-right'],
      ['label'=>'Kuryerlar','route'=>'admin.couriers.index','icon'=>'bike'],
      ['label'=>'Promokodlar','route'=>'admin.promocodes.index','icon'=>'badge-percent'],
      ['label'=>'Reklamalar','route'=>'admin.ads.index','icon'=>'megaphone'],
      ['label'=>'Gift sertifikatlar','route'=>'admin.gift-certificates.index','icon'=>'gift'],
      ['label'=>'Mystery Box','route'=>'admin.mystery-box.index','icon'=>'box'],
    ]],
    ['title' => 'JAMIYAT','items' => [
      ['label'=>'Book Club','route'=>'admin.book-club.index','icon'=>'messages-square'],
      ['label'=>'UGC navbati','route'=>'admin.ugc.index','icon'=>'sparkles'],
      ['label'=>'Chat kuzatuv','route'=>'admin.chats.index','icon'=>'message-square'],
      ['label'=>'Shikoyatlar','route'=>'admin.complaints.index','icon'=>'flag'],
    ]],
    ['title' => 'TIZIM','items' => [
      ['label'=>'Support','route'=>'admin.support.index','icon'=>'life-buoy'],
      ['label'=>'Push bildirishnomalar','route'=>'admin.push.index','icon'=>'bell'],
      ['label'=>'Sozlamalar','route'=>'admin.settings.index','icon'=>'settings'],
      ['label'=>'Siyosatlar','route'=>'admin.policies.index','icon'=>'file-text'],
      ['label'=>'Vakansiyalar','route'=>'admin.jobs.index','icon'=>'briefcase'],
      ['label'=>'Karyera arizalari','route'=>'admin.job-applications.index','icon'=>'clipboard-list'],
      ['label'=>'Adminlar','route'=>'admin.admins.index','icon'=>'shield'],
      ['label'=>'API mijozlar','route'=>'admin.api-clients.index','icon'=>'key-round'],
    ]],
  ];
  $current = request()->route() ? request()->route()->getName() : '';
?>

<?php
  $panelAdmin = auth('panel')->user();
?>

<div id="a122-sidebar-overlay" data-sidebar-overlay class="fixed inset-0 bg-slate-950/56 backdrop-blur-sm z-40 lg:hidden hidden"></div>

<aside
  id="a122-sidebar"
  data-sidebar
  data-collapsed="false"
  class="a122-sidebar is-expanded fixed lg:sticky top-0 left-0 z-50 lg:z-auto h-screen shrink-0 border-r border-gray-100 dark:border-white/5 flex flex-col transition-all duration-300 -translate-x-full lg:translate-x-0">
  <div class="flex items-center justify-between px-4 pt-5 pb-4">
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="sidebar-brand min-w-0">
      <img src="<?php echo e(asset('images/logo/logo_black.png')); ?>" alt="Kitobchi" class="sidebar-brand-logo sidebar-brand-logo--light">
      <img src="<?php echo e(asset('images/logo/logo_white.png')); ?>" alt="Kitobchi" class="sidebar-brand-logo sidebar-brand-logo--dark">
      <div class="sidebar-brand-mini">K</div>
    </a>
    <button type="button" data-sidebar-close class="lg:hidden p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5">
      <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
    </button>
  </div>

  <nav class="flex-1 overflow-y-auto px-3 pb-3 no-scrollbar">
    <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="mb-3 sidebar-group">
        <div class="sidebar-group-title px-3 py-2 mt-1 text-[10.5px] font-semibold tracking-[0.12em] text-gray-400 dark:text-gray-500">
          <?php echo e($g['title']); ?>

        </div>

        <div class="space-y-0.5 mt-0.5">
          <?php $__currentLoopData = $g['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $href = \Illuminate\Support\Facades\Route::has($it['route'])
                ? route($it['route'])
                : '#';
              $routePrefix = preg_replace('/\.index$/', '', $it['route']);
              $active = str_starts_with($current, $routePrefix);
              $itemClasses = $active
                ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white';
            ?>
            <a
              href="<?php echo e($href); ?>"
              data-sidebar-link
              data-label="<?php echo e($it['label']); ?>"
              title="<?php echo e($it['label']); ?>"
              class="sidebar-link group flex items-center gap-3 px-3 py-[0.42rem] rounded-xl text-sm font-medium <?php echo e($itemClasses); ?>"
            >
              <span class="sidebar-link__rail <?php echo e($active ? 'is-active' : ''); ?>"></span>
              <span class="sidebar-link__iconwrap">
                <i data-lucide="<?php echo e($it['icon']); ?>" class="w-[17px] h-[17px] shrink-0"></i>
              </span>
              <span class="sidebar-link__label flex-1 truncate"><?php echo e($it['label']); ?></span>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </nav>

  <div class="sidebar-foot">
    <div class="sidebar-foot__label">Workspace</div>
    <div class="sidebar-foot__title">A122 Admin</div>
    <div class="sidebar-foot__meta"><?php echo e($panelAdmin?->name ?? 'Admin'); ?> · secure panel</div>
  </div>
</aside>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/sidebar.blade.php ENDPATH**/ ?>