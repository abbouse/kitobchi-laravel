<?php $__env->startSection('title', 'Foydalanuvchilar'); ?>
<?php $__env->startSection('page-title', 'Foydalanuvchilar'); ?>
<?php $__env->startSection('page-eyebrow', 'User operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'buyers' => ['label' => 'Xarid qilganlar', 'count' => $counts['buyers'] ?? 0],
    'with_cards' => ['label' => 'Karta ulanganlar', 'count' => $counts['with_cards'] ?? 0],
    'pending' => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
    'active' => ['label' => 'Faol', 'count' => $counts['active'] ?? 0],
    'premium' => ['label' => 'Premium', 'count' => $counts['premium'] ?? 0],
    'blocked' => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
  ];
?>

<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'User operations','title' => 'Foydalanuvchilar','subtitle' => ''.e($users->total()).' ta foydalanuvchi yozuvi topildi. Verifikatsiya, premium va access boshqaruvi shu sahifadan boshqariladi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'User operations','title' => 'Foydalanuvchilar','subtitle' => ''.e($users->total()).' ta foydalanuvchi yozuvi topildi. Verifikatsiya, premium va access boshqaruvi shu sahifadan boshqariladi.']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input
        type="text"
        name="search"
        value="<?php echo e(request('search')); ?>"
        placeholder="Ism, email, telefon yoki rol..."
        class="form-control">
    </form>
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Yangi foydalanuvchi</span>
    </a>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Jami foydalanuvchi','value' => number_format($counts['all'] ?? 0),'meta' => 'Platformadagi barcha ro‘yxatdan o‘tgan akkauntlar','icon' => 'people','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Jami foydalanuvchi','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['all'] ?? 0)),'meta' => 'Platformadagi barcha ro‘yxatdan o‘tgan akkauntlar','icon' => 'people','tone' => 'primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Xarid qilganlar','value' => number_format($counts['buyers'] ?? 0),'meta' => 'Buyurtma yoki to‘lov aktivligi mavjud userlar','icon' => 'bag-check','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Xarid qilganlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['buyers'] ?? 0)),'meta' => 'Buyurtma yoki to‘lov aktivligi mavjud userlar','icon' => 'bag-check','tone' => 'success']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Karta ulanganlar','value' => number_format($counts['with_cards'] ?? 0),'meta' => 'Saved card bilan checkoutni tez bajaradigan userlar','icon' => 'credit-card-2-front','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Karta ulanganlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['with_cards'] ?? 0)),'meta' => 'Saved card bilan checkoutni tez bajaradigan userlar','icon' => 'credit-card-2-front','tone' => 'info']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Premium / blok','value' => number_format($counts['premium'] ?? 0) . ' / ' . number_format($counts['blocked'] ?? 0),'meta' => 'Loyalty va moderation segmentlari bir ko‘rinishda','icon' => 'shield-lock','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Premium / blok','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['premium'] ?? 0) . ' / ' . number_format($counts['blocked'] ?? 0)),'meta' => 'Loyalty va moderation segmentlari bir ko‘rinishda','icon' => 'shield-lock','tone' => 'warning']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Segmentlar va filtrlash','meta' => 'Faollik, access va checkout signaliga qarab userlarni tez ajrating.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Segmentlar va filtrlash','meta' => 'Faollik, access va checkout signaliga qarab userlarni tez ajrating.']); ?>
    <div class="d-flex flex-column gap-3">
      <div class="kc-filter-card">
        <div class="nav nav-pills flex-wrap">
          <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a
              href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
              class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
              <?php echo e($tabItem['label']); ?>

              <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>">
                <?php echo e(number_format($tabItem['count'])); ?>

              </span>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
      <div class="small text-secondary">
        Premium, blocked va staff role belgilarini jadval ichidan bir qarashda ko‘rish mumkin.
      </div>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Foydalanuvchilar jadvali','meta' => $users->total() . ' ta yozuv yuklandi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Foydalanuvchilar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($users->total() . ' ta yozuv yuklandi.')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Foydalanuvchi</th>
            <th>Rol va access</th>
            <th>Status</th>
            <th>Kontakt</th>
            <th>Qo‘shilgan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3 min-w-0">
                  <?php echo $__env->make('a122.partials.avatar', [
                    'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
                    'image' => $user->avatar,
                    'class' => 'w-10 h-10 rounded-4 text-sm',
                  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <div class="min-w-0">
                    <div class="fw-semibold text-truncate"><?php echo e(trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—'); ?></div>
                    <div class="small text-secondary text-truncate"><?php echo e($user->email ?: 'Email yo‘q'); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill text-bg-light border"><?php echo e($user->position ?: 'reader'); ?></span>
                  <?php if($user->staff_role): ?>
                    <span class="badge rounded-pill text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">
                      <?php echo e($user->staff_role === 'administrator' ? 'Administrator' : 'Moderator'); ?>

                    </span>
                  <?php endif; ?>
                  <?php if($user->is_premium): ?>
                    <span class="badge rounded-pill text-bg-primary-subtle border border-primary-subtle text-primary-emphasis">Premium</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill <?php echo e($user->isVerified ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis' : 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis'); ?>">
                    <?php echo e($user->isVerified ? 'Faol' : 'Kutilmoqda'); ?>

                  </span>
                  <?php if($user->isBlocked()): ?>
                    <span class="badge rounded-pill text-bg-danger-subtle border border-danger-subtle text-danger-emphasis">Bloklangan</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div class="small fw-semibold text-dark"><?php echo e($user->phone_number ?: 'Telefon yo‘q'); ?></div>
                <div class="small text-secondary"><?php echo e($user->email ?: '—'); ?></div>
              </td>
              <td>
                <div class="fw-semibold"><?php echo e(optional($user->created_at)->format('d.m.Y') ?: '—'); ?></div>
                <div class="small text-secondary"><?php echo e(optional($user->created_at)->format('H:i') ?: ''); ?></div>
              </td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="<?php echo e(route('admin.users.verify', $user)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="<?php echo e($user->isVerified ? 'Tasdiqni bekor qilish' : 'Tasdiqlash'); ?>">
                      <i class="bi <?php echo e($user->isVerified ? 'bi-patch-minus' : 'bi-patch-check'); ?>"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.users.premium', $user)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="<?php echo e($user->is_premium ? 'Premiumni o‘chirish' : 'Premiumni yoqish'); ?>">
                      <i class="bi <?php echo e($user->is_premium ? 'bi-gem' : 'bi-stars'); ?>"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-secondary">Foydalanuvchilar topilmadi.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

  <?php if(method_exists($users, 'links')): ?>
    <div><?php echo e($users->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/index.blade.php ENDPATH**/ ?>