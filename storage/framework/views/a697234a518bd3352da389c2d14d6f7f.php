<?php
  $groups = [
    ['title' => 'Asosiy', 'items' => [
      ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'speedometer2'],
      ['label' => 'Foydalanuvchilar', 'route' => 'admin.users.index', 'icon' => 'people'],
    ]],
    ['title' => 'Katalog', 'items' => [
      ['label' => 'Kitoblar', 'route' => 'admin.books.index', 'icon' => 'book'],
      ['label' => 'Mualliflar', 'route' => 'admin.authors.index', 'icon' => 'pen'],
      ['label' => 'Nashriyotlar', 'route' => 'admin.publishers.index', 'icon' => 'building'],
      ['label' => 'Parser', 'route' => 'admin.parsers.index', 'icon' => 'database-down'],
      ['label' => 'Kanstovar', 'route' => 'admin.stationery.index', 'icon' => 'pencil-square'],
      ['label' => 'Kitob kategoriyalari', 'route' => 'admin.book-categories.index', 'icon' => 'collection'],
      ['label' => 'Kanstovar kategoriyalari', 'route' => 'admin.stationery-categories.index', 'icon' => 'tags'],
      ['label' => 'Reels', 'route' => 'admin.reels.index', 'icon' => 'camera-reels'],
      ['label' => 'Market yangiliklari', 'route' => 'admin.news.index', 'icon' => 'newspaper'],
    ]],
    ['title' => 'Operatsiya', 'items' => [
      ['label' => 'Buyurtmalar', 'route' => 'admin.orders.index', 'icon' => 'bag-check'],
      ['label' => 'Seller buyurtmalari', 'route' => 'admin.seller-orders.index', 'icon' => 'box-seam'],
      ['label' => 'Kuryer buyurtmalari', 'route' => 'admin.courier-orders.index', 'icon' => 'truck'],
      ['label' => 'Logistika', 'route' => 'admin.logistics.index', 'icon' => 'map'],
      ['label' => 'Hublar', 'route' => 'admin.hubs.index', 'icon' => 'buildings'],
      ['label' => 'Kuryerlar', 'route' => 'admin.couriers.index', 'icon' => 'bicycle'],
      ['label' => 'Tranzaksiyalar', 'route' => 'admin.transactions.index', 'icon' => 'cash-stack'],
    ]],
    ['title' => 'Biznes', 'items' => [
      ['label' => 'Sotuvchilar', 'route' => 'admin.sellers.index', 'icon' => 'shop'],
      ['label' => 'Hamkor blogerlar', 'route' => 'admin.bloggers.index', 'icon' => 'stars'],
      ['label' => 'Promokodlar', 'route' => 'admin.promocodes.index', 'icon' => 'ticket-perforated'],
      ['label' => 'Reklamalar', 'route' => 'admin.ads.index', 'icon' => 'megaphone'],
      ['label' => 'Gift sertifikatlar', 'route' => 'admin.gift-certificates.index', 'icon' => 'gift'],
      ['label' => 'Mystery Box', 'route' => 'admin.mystery-box.index', 'icon' => 'box2-heart'],
    ]],
    ['title' => 'Jamoa va tizim', 'items' => [
      ['label' => 'Book Club', 'route' => 'admin.book-club.index', 'icon' => 'chat-left-dots'],
      ['label' => 'Chat kuzatuv', 'route' => 'admin.chats.index', 'icon' => 'chat-square-text'],
      ['label' => 'Shikoyatlar', 'route' => 'admin.complaints.index', 'icon' => 'flag'],
      ['label' => 'Support', 'route' => 'admin.support.index', 'icon' => 'headset'],
      ['label' => 'Push bildirishnomalar', 'route' => 'admin.push.index', 'icon' => 'bell'],
      ['label' => 'Sozlamalar', 'route' => 'admin.settings.index', 'icon' => 'gear'],
      ['label' => 'Siyosatlar', 'route' => 'admin.policies.index', 'icon' => 'file-earmark-text'],
      ['label' => 'Vakansiyalar', 'route' => 'admin.jobs.index', 'icon' => 'briefcase'],
      ['label' => 'Karyera arizalari', 'route' => 'admin.job-applications.index', 'icon' => 'clipboard2-check'],
      ['label' => 'Adminlar', 'route' => 'admin.admins.index', 'icon' => 'shield-lock'],
      ['label' => 'API mijozlar', 'route' => 'admin.api-clients.index', 'icon' => 'key'],
    ]],
  ];

  $current = request()->route()?->getName() ?? '';
  $panelAdmin = auth('panel')->user();
?>

<div id="a122-sidebar-overlay" data-sidebar-overlay class="kc-sidebar-overlay d-none d-lg-none"></div>

<aside
  id="a122-sidebar"
  data-sidebar
  data-collapsed="false"
  class="kc-sidebar is-expanded">
  <div class="kc-sidebar__surface">
    <div class="kc-sidebar__brand">
      <div class="d-flex align-items-start justify-content-between gap-2">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="kc-sidebar__brand-link">
          <span class="kc-sidebar__brand-mark">
            <i class="bi bi-grid"></i>
          </span>
          <span class="kc-sidebar__brand-copy">
            <span class="kc-sidebar__brand-title d-block">Kitobchi Admin</span>
            <span class="kc-sidebar__brand-subtitle d-block">Operational workspace</span>
          </span>
        </a>
        <div class="d-flex align-items-center gap-2">
          <button type="button" data-sidebar-toggle class="kc-sidebar__collapse d-none d-lg-inline-flex" aria-label="Sidebarni yig‘ish yoki ochish">
            <i class="bi bi-layout-sidebar-inset"></i>
          </button>
          <button type="button" data-sidebar-close class="btn btn-sm btn-outline-light border-0 d-lg-none">
          <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="kc-sidebar__scroll">
      <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <section class="kc-sidebar__group">
          <div class="kc-sidebar__group-title"><?php echo e($group['title']); ?></div>
          <nav class="nav flex-column kc-sidebar__nav">
            <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $routePrefix = preg_replace('/\.index$/', '', $item['route']);
                $active = str_starts_with($current, $routePrefix);
              ?>
              <a
                href="<?php echo e(\Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#'); ?>"
                data-sidebar-link
                data-label="<?php echo e($item['label']); ?>"
                title="<?php echo e($item['label']); ?>"
                class="nav-link <?php echo e($active ? 'active' : ''); ?>">
                <span class="kc-sidebar__icon"><i class="bi bi-<?php echo e($item['icon']); ?>"></i></span>
                <span class="text-truncate"><?php echo e($item['label']); ?></span>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </nav>
        </section>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="kc-sidebar__footer">
      <div class="kc-sidebar__workspace">
        <div class="kc-sidebar__workspace-label">Panel</div>
        <div class="kc-sidebar__workspace-title"><?php echo e($panelAdmin?->name ?? 'Admin'); ?></div>
        <div class="kc-sidebar__workspace-meta"><?php echo e($panelAdmin?->role_label ?? 'Administrator'); ?></div>
      </div>
    </div>
  </div>
</aside>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/sidebar.blade.php ENDPATH**/ ?>