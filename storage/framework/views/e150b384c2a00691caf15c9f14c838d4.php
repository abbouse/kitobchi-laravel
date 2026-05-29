<?php $__env->startSection('title', 'Shikoyatlar'); ?>
<?php $__env->startSection('page-title', 'Shikoyatlar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Operations','title' => 'Shikoyatlar','subtitle' => ''.e($reports->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Operations','title' => 'Shikoyatlar','subtitle' => ''.e($reports->total()).' ta yozuv']); ?>
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
      [$counts['pending'] ?? 0, 'Kutilmoqda', 'bi-hourglass-split', 'warning'],
      [$counts['reviewed'] ?? 0, 'Ko‘rilgan', 'bi-check2-circle', 'success'],
      [$counts['dismissed'] ?? 0, 'Rad etilgan', 'bi-slash-circle', 'secondary'],
      [($counts['pending'] ?? 0) + ($counts['reviewed'] ?? 0) + ($counts['dismissed'] ?? 0), 'Jami', 'bi-flag', 'primary'],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Shikoyatlar jadvali','meta' => $reports->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Shikoyatlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reports->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Shikoyatchi</th>
            <th>Tur / ID</th>
            <th>Sabab</th>
            <th>Izoh</th>
            <th>Holat</th>
            <th>Vaqt</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusClass = match($report->status) {
                'reviewed' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'dismissed' => 'text-bg-secondary',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $statusLabel = match($report->status) {
                'reviewed' => 'Ko‘rildi',
                'dismissed' => 'Rad',
                default => 'Yangi',
              };
              $typeLabel = match($report->reportable_type) {
                'conversation_message' => 'Xabar',
                'book_club' => 'Book Club',
                default => $report->reportable_type,
              };
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($report->id); ?></td>
              <td>
                <?php if($report->user): ?>
                  <a href="<?php echo e(route('admin.users.show', $report->user_id)); ?>" class="fw-semibold text-decoration-none">
                    <?php echo e($report->user->name); ?> <?php echo e($report->user->lastname); ?>

                  </a>
                  <div class="small text-secondary"><?php echo e($report->user->phone_number); ?></div>
                <?php else: ?>
                  <span class="text-secondary">#<?php echo e($report->user_id); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis"><?php echo e($typeLabel); ?></span>
                <div class="small text-secondary">#<?php echo e($report->reportable_id); ?></div>
              </td>
              <td class="fw-semibold"><?php echo e($report->reason); ?></td>
              <td class="text-secondary text-truncate" style="max-width: 12rem;"><?php echo e($report->comment ?: '—'); ?></td>
              <td><span class="badge rounded-pill <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
              <td class="text-secondary text-nowrap"><?php echo e(\Carbon\Carbon::parse($report->created_at)->format('d.m.Y H:i')); ?></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="<?php echo e(route('admin.complaints.show', $report)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if($report->status === 'pending'): ?>
                    <form method="POST" action="<?php echo e(route('admin.complaints.status', $report)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="status" value="reviewed">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Ko‘rildi">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.complaints.status', $report)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="status" value="dismissed">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <?php if($report->status !== 'pending'): ?>
                    <form method="POST" action="<?php echo e(route('admin.complaints.status', $report)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="status" value="pending">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Qayta ochish">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="text-center py-5 text-secondary">Shikoyat topilmadi.</td></tr>
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

  <?php if($reports->hasPages()): ?>
    <div><?php echo e($reports->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/complaints/index.blade.php ENDPATH**/ ?>