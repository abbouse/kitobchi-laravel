<?php
  $groups = [
    ['title' => 'Main menu', 'items' => [
      ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'speedometer2'],
      ['label' => 'Buyurtmalar', 'route' => 'admin.orders.index', 'icon' => 'cart3'],
      ['label' => 'Foydalanuvchilar', 'route' => 'admin.users.index', 'icon' => 'people'],
      ['label' => 'Promokodlar', 'route' => 'admin.promocodes.index', 'icon' => 'ticket-perforated'],
      ['label' => 'Kitob kategoriyalari', 'route' => 'admin.book-categories.index', 'icon' => 'collection'],
      ['label' => 'Tranzaksiyalar', 'route' => 'admin.transactions.index', 'icon' => 'credit-card'],
      ['label' => 'Sotuvchilar', 'route' => 'admin.sellers.index', 'icon' => 'shop'],
    ]],
    ['title' => 'Product', 'items' => [
      ['label' => 'Kitoblar', 'route' => 'admin.books.index', 'icon' => 'book'],
      ['label' => 'Kanstovar', 'route' => 'admin.stationery.index', 'icon' => 'pencil-square'],
      ['label' => 'Mualliflar', 'route' => 'admin.authors.index', 'icon' => 'pen'],
      ['label' => 'Nashriyotlar', 'route' => 'admin.publishers.index', 'icon' => 'building'],
      ['label' => 'Kanstovar kategoriyalari', 'route' => 'admin.stationery-categories.index', 'icon' => 'tags'],
      ['label' => 'Reels', 'route' => 'admin.reels.index', 'icon' => 'camera-reels'],
      ['label' => 'Market yangiliklari', 'route' => 'admin.news.index', 'icon' => 'newspaper'],
    ]],
    ['title' => 'Operations', 'items' => [
      ['label' => 'Seller buyurtmalari', 'route' => 'admin.seller-orders.index', 'icon' => 'box-seam'],
      ['label' => 'Kuryer buyurtmalari', 'route' => 'admin.courier-orders.index', 'icon' => 'truck'],
      ['label' => 'Logistika', 'route' => 'admin.logistics.index', 'icon' => 'map'],
      ['label' => 'Hublar', 'route' => 'admin.hubs.index', 'icon' => 'buildings'],
      ['label' => 'Kuryerlar', 'route' => 'admin.couriers.index', 'icon' => 'bicycle'],
    ]],
    ['title' => 'Business', 'items' => [
      ['label' => 'Hamkor blogerlar', 'route' => 'admin.bloggers.index', 'icon' => 'stars'],
      ['label' => 'Reklamalar', 'route' => 'admin.ads.index', 'icon' => 'megaphone'],
      ['label' => 'Gift sertifikatlar', 'route' => 'admin.gift-certificates.index', 'icon' => 'gift'],
      ['label' => 'Mystery Box', 'route' => 'admin.mystery-box.index', 'icon' => 'box2-heart'],
    ]],
    ['title' => 'Admin', 'items' => [
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
  class="sidebar kc-sidebar is-expanded">
  <div class="kc-sidebar__surface">
    <div class="brand kc-sidebar__brand">
      <div class="d-flex align-items-start justify-content-between gap-2">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="kc-sidebar__brand-link">
          <span class="brand-logo kc-sidebar__brand-mark">
            <i class="bi bi-book-half"></i>
          </span>
          <span class="kc-sidebar__brand-copy">
            <span class="brand-name kc-sidebar__brand-title d-block">Kitobchi</span>
            <span class="brand-sub kc-sidebar__brand-subtitle d-block">Admin Panel</span>
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

    <div class="nav-group kc-sidebar__scroll">
      <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <section class="kc-sidebar__group">
          <div class="nav-title kc-sidebar__group-title"><?php echo e($group['title']); ?></div>
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
                class="nav-item nav-link <?php echo e($active ? 'active' : ''); ?>">
                <span class="kc-sidebar__icon"><i class="bi bi-<?php echo e($item['icon']); ?>"></i></span>
                <span class="kc-sidebar__label"><?php echo e($item['label']); ?></span>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </nav>
        </section>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="sidebar-footer kc-sidebar__footer">
      <div class="kc-sidebar__workspace">
        <div class="kc-sidebar__workspace-label">Signed in</div>
        <div class="kc-sidebar__workspace-title"><?php echo e($panelAdmin?->name ?? 'Admin'); ?></div>
        <div class="kc-sidebar__workspace-meta"><?php echo e($panelAdmin?->role_label ?? 'Administrator'); ?></div>
      </div>
      <a href="<?php echo e(url('/')); ?>" class="kc-sidebar__shop-link" target="_blank" rel="noopener">
        <span><i class="bi bi-box-arrow-up-right"></i></span>
        <span>Kitobchi shop</span>
      </a>
    </div>
  </div>
</aside>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/sidebar.blade.php ENDPATH**/ ?>