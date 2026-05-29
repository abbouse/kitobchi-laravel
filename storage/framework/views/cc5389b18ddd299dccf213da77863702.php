<?php
  $panelAdmin = auth('panel')->user();
  $quickLinks = [
    ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['label' => 'Foydalanuvchilar', 'href' => route('admin.users.index')],
    ['label' => 'Kitoblar', 'href' => route('admin.books.index')],
    ['label' => 'Kanstovar', 'href' => route('admin.stationery.index')],
    ['label' => 'Sotuvchilar', 'href' => route('admin.sellers.index')],
    ['label' => 'Buyurtmalar', 'href' => route('admin.orders.index')],
    ['label' => 'Support', 'href' => route('admin.support.index')],
    ['label' => 'Sozlamalar', 'href' => route('admin.settings.index')],
  ];
?>

<header class="kc-topbar">
  <div class="container-fluid px-3 px-lg-4 px-xxl-5">
    <div class="kc-topbar__inner d-flex align-items-center gap-3">
      <button
        type="button"
        data-sidebar-toggle
        aria-expanded="true"
        class="btn btn-white shadow-sm border kc-topbar__menu"
        aria-label="Menyu">
        <i class="bi bi-list fs-5"></i>
      </button>

      <div class="kc-topbar__titleblock min-w-0">
        <div class="kc-topbar__eyebrow"><?php echo $__env->yieldContent('page-eyebrow', 'A122 control room'); ?></div>
        <div class="kc-topbar__title text-truncate"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></div>
      </div>

      <div class="kc-topbar__search flex-grow-1">
        <form class="kc-search" onsubmit="event.preventDefault();const input=this.querySelector('input');const option=[...document.querySelectorAll('#a122-quick-nav-list option')].find(o=>o.value===input.value);if(option?.dataset?.href){window.location=option.dataset.href;}">
          <i class="bi bi-search kc-search__icon"></i>
          <input type="text" list="a122-quick-nav-list" class="form-control" placeholder="Qidiruv yoki tezkor o‘tish" />
          <datalist id="a122-quick-nav-list">
            <?php $__currentLoopData = $quickLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($link['label']); ?>" data-href="<?php echo e($link['href']); ?>"></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </datalist>
        </form>
      </div>

      <a href="<?php echo e(route('admin.support.index')); ?>" class="kc-topbar__icon-btn d-none d-sm-inline-flex" aria-label="Support">
        <i class="bi bi-bell"></i>
      </a>

      <button data-theme-toggle class="kc-topbar__icon-btn d-none d-sm-inline-flex" aria-label="Tema almashtirish">
        <i class="bi bi-moon-stars"></i>
      </button>

      <div class="dropdown">
        <button
          class="btn kc-topbar__user dropdown-toggle d-inline-flex align-items-center gap-2"
          type="button"
          data-bs-toggle="dropdown"
          aria-expanded="false">
          <?php echo $__env->make('a122.partials.avatar', [
            'name' => $panelAdmin?->name ?? 'Admin',
            'image' => $panelAdmin?->avatar,
            'class' => 'kc-topbar__user-avatar',
          ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <span class="text-start d-none d-lg-inline-block">
            <span class="d-block fw-semibold text-dark"><?php echo e($panelAdmin?->name ?? 'Admin'); ?></span>
            <span class="d-block small text-secondary"><?php echo e($panelAdmin?->role_label ?? 'Panel user'); ?></span>
          </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
          <li class="px-2 py-2 border-bottom">
            <div class="fw-semibold"><?php echo e($panelAdmin?->name ?? 'Admin'); ?></div>
            <div class="small text-secondary"><?php echo e($panelAdmin?->email ?? 'email yo‘q'); ?></div>
          </li>
          <?php if($panelAdmin): ?>
            <li>
              <a href="<?php echo e(route('admin.admins.edit', $panelAdmin)); ?>" class="dropdown-item rounded-3 py-2">
                <i class="bi bi-person-gear me-2"></i>
                Admin sozlamalari
              </a>
            </li>
          <?php endif; ?>
          <li><hr class="dropdown-divider my-2"></li>
          <li>
            <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
              <?php echo csrf_field(); ?>
              <button type="submit" class="dropdown-item rounded-3 py-2 text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>
                Logout
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/topbar.blade.php ENDPATH**/ ?>