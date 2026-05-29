<?php $__env->startSection('title', 'Kuryerlar'); ?>
<?php $__env->startSection('page-title', 'Kuryerlar'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
    'pending' => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
    'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
    'blocked' => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
  ];
?>

<div class="d-flex flex-column gap-4">
  <?php if(session('success')): ?>
    <div class="alert alert-success border-0 mb-0"><?php echo e(session('success')); ?></div>
  <?php endif; ?>
  <?php if(session('error')): ?>
    <div class="alert alert-danger border-0 mb-0"><?php echo e(session('error')); ?></div>
  <?php endif; ?>

  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Operations','title' => 'Kuryerlar','subtitle' => ''.e($couriers->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Operations','title' => 'Kuryerlar','subtitle' => ''.e($couriers->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, telefon yoki hudud" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.couriers.create')); ?>" class="btn-p primary">
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
      <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
          <?php echo e($tabItem['label']); ?>

          <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($tabItem['count'])); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Kuryerlar jadvali','meta' => $couriers->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kuryerlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($couriers->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Ism</th>
            <th>Telefon</th>
            <th>Viloyat</th>
            <th>Balans</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $couriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $courier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusClass = match($courier->status) {
                'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'pending' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
                'rejected', 'blocked' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-secondary',
              };
              $statusLabel = match($courier->status) {
                'approved' => 'Tasdiqlangan',
                'pending' => 'Kutilmoqda',
                'rejected' => 'Rad etilgan',
                'blocked' => 'Bloklangan',
                default => $courier->status ?? '—',
              };
              $courierName = trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: 'Kuryer';
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($courier->id); ?></td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <?php echo $__env->make('a122.partials.avatar', [
                    'name' => $courierName,
                    'image' => $courier->photo,
                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <div class="fw-semibold"><?php echo e($courierName); ?></div>
                </div>
              </td>
              <td><?php echo e($courier->phone_number ?? $courier->phone ?? '—'); ?></td>
              <td><?php echo e($courier->region ?? '—'); ?></td>
              <td class="fw-semibold text-nowrap"><?php echo e(number_format((float)($courier->balance ?? 0), 0, '.', ' ')); ?> UZS</td>
              <td><span class="badge rounded-pill <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="<?php echo e(route('admin.couriers.approve', $courier)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.couriers.reject', $courier)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('admin.couriers.show', $courier)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="<?php echo e(route('admin.couriers.destroy', $courier)); ?>" onsubmit="return confirm('Kuryerni o‘chirishga ishonchingiz komilmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="text-center py-5 text-secondary">Kuryer topilmadi.</td></tr>
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

  <?php if($couriers->hasPages()): ?>
    <div><?php echo e($couriers->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/couriers/index.blade.php ENDPATH**/ ?>