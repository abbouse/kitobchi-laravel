<?php $__env->startSection('title', 'Push bildirishnomalar'); ?>
<?php $__env->startSection('page-title', 'Push bildirishnomalar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Operations','title' => 'Push bildirishnomalar','subtitle' => ''.e($notifications->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Operations','title' => 'Push bildirishnomalar','subtitle' => ''.e($notifications->total()).' ta yozuv']); ?>
    <a href="<?php echo e(route('admin.push.create')); ?>" class="btn-p primary">
      <i class="bi bi-send"></i>
      <span>Yuborish</span>
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
    <?php $__currentLoopData = [
      [$counts['all'] ?? 0, 'Jami', 'bi-bell', 'primary'],
      [$counts['users'] ?? 0, 'Userlar', 'bi-people', 'info'],
      [$counts['business'] ?? 0, 'Sellerlar', 'bi-shop-window', 'warning'],
      [$counts['courier'] ?? 0, 'Kuryerlar', 'bi-bicycle', 'success'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-<?php echo e($tone); ?>-subtle text-<?php echo e($tone); ?>">
            <i class="bi <?php echo e($icon); ?>"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value"><?php echo e(number_format($value)); ?></div>
            <div class="a122-stat-tile__label"><?php echo e($label); ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Pushlar jadvali','meta' => $notifications->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pushlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notifications->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sarlavha</th>
            <th>Matn</th>
            <th>Qabul qiluvchi</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $target = $targets[$notification->who] ?? ['label' => $notification->who, 'color' => 'secondary', 'icon' => 'bi-bell']; ?>
            <tr>
              <td class="text-secondary">#<?php echo e($notification->id); ?></td>
              <td class="fw-semibold"><?php echo e($notification->name); ?></td>
              <td class="text-secondary text-truncate" style="max-width: 24rem;"><?php echo e($notification->description); ?></td>
              <td>
                <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">
                  <i class="bi <?php echo e($target['icon']); ?> me-1"></i><?php echo e($target['label']); ?>

                </span>
              </td>
              <td class="text-secondary text-nowrap"><?php echo e($notification->created_at?->format('d.m.Y H:i')); ?></td>
              <td class="text-end">
                <form method="POST" action="<?php echo e(route('admin.push.destroy', $notification)); ?>" onsubmit="return confirm('O‘chirilsinmi?')" class="d-inline">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('DELETE'); ?>
                  <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="text-center py-5 text-secondary">Bildirishnoma topilmadi.</td></tr>
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

  <?php if($notifications->hasPages()): ?>
    <div><?php echo e($notifications->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/push/index.blade.php ENDPATH**/ ?>