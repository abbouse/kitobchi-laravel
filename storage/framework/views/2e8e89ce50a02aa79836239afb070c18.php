<?php $__env->startSection('title', 'Adminlar'); ?>
<?php $__env->startSection('page-title', 'Adminlar'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Adminlar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Admin','title' => 'Adminlar','subtitle' => ''.e($admins->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Admin','title' => 'Adminlar','subtitle' => ''.e($admins->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, email yoki rol" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.admins.create')); ?>" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
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

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Bloklangan', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
          <?php echo e($label); ?>

          <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($count)); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Adminlar jadvali','meta' => $admins->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Adminlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($admins->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Admin</th>
            <th>Rol</th>
            <th>Ruxsatlar</th>
            <th>Oxirgi kirish</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $roleColors = ['superadmin' => 'danger', 'admin' => 'primary', 'moderator' => 'warning']; ?>
            <tr>
              <td class="text-secondary">#<?php echo e($admin->id); ?></td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <?php echo $__env->make('a122.partials.avatar', [
                    'name' => $admin->name,
                    'image' => $admin->avatar,
                    'class' => 'av',
                  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <div>
                    <div class="fw-semibold"><?php echo e($admin->name); ?></div>
                    <div class="small text-secondary"><?php echo e($admin->email); ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge rounded-pill text-bg-<?php echo e($roleColors[$admin->role] ?? 'secondary'); ?>"><?php echo e($admin->role_label); ?></span></td>
              <td>
                <?php if($admin->isSuperAdmin()): ?>
                  <span class="text-secondary">Barcha ruxsatlar</span>
                <?php else: ?>
                  <div class="d-flex flex-wrap gap-1">
                    <?php $__currentLoopData = array_slice($admin->permissions ?? [], 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis"><?php echo e($permission); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if(count($admin->permissions ?? []) > 3): ?>
                      <span class="small text-secondary">+<?php echo e(count($admin->permissions) - 3); ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if($admin->last_login_at): ?>
                  <div><?php echo e($admin->last_login_at->format('d.m.Y H:i')); ?></div>
                  <div class="small text-secondary"><?php echo e($admin->last_ip); ?></div>
                <?php else: ?>
                  <span class="text-secondary">Hali kirmagan</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($admin->is_active): ?>
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                <?php else: ?>
                  <span class="badge rounded-pill text-bg-danger-subtle border border-danger-subtle text-danger-emphasis">Bloklangan</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="<?php echo e(route('admin.admins.show', $admin)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('admin.admins.edit', $admin)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <?php if($admin->id !== auth('panel')->id()): ?>
                    <form method="POST" action="<?php echo e(route('admin.admins.toggle', $admin)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <button class="btn btn-sm btn-light border kc-table-action" title="<?php echo e($admin->is_active ? 'Bloklash' : 'Faollashtirish'); ?>">
                        <i class="bi bi-<?php echo e($admin->is_active ? 'lock' : 'unlock'); ?>"></i>
                      </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.admins.destroy', $admin)); ?>" onsubmit="return confirm('Adminni o‘chirishni tasdiqlaysizmi?')">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('DELETE'); ?>
                      <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="text-center py-5 text-secondary">Admin topilmadi.</td></tr>
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

  <?php if($admins->hasPages()): ?>
    <div><?php echo e($admins->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/admins/index.blade.php ENDPATH**/ ?>