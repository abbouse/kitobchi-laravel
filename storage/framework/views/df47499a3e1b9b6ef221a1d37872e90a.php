<?php $__env->startSection('title', 'Mystery Box'); ?>
<?php $__env->startSection('page-title', 'Mystery Box'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Commerce','title' => 'Mystery Box','subtitle' => ''.e($subs->total()).' ta obuna']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Commerce','title' => 'Mystery Box','subtitle' => ''.e($subs->total()).' ta obuna']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism yoki telefon" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.mystery-box.plans')); ?>" class="btn btn-light border">
      <i class="bi bi-sliders me-1"></i>Tariflar
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

  <?php if($dueToday > 0): ?>
    <div class="alert alert-warning border-0 mb-0">
      Bugun <?php echo e(number_format($dueToday)); ?> ta jo‘natish navbati bor.
      <?php if($dueWeek > $dueToday): ?>
        Bu hafta: <?php echo e(number_format($dueWeek)); ?> ta.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if(isset($opsDueNow) && $opsDueNow->count()): ?>
    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Jo‘natish navbati','meta' => $opsDueNow->count() . ' ta']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Jo‘natish navbati','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($opsDueNow->count() . ' ta')]); ?>
      <div class="row g-2">
        <?php $__currentLoopData = $opsDueNow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-md-6">
            <a href="<?php echo e(route('admin.mystery-box.show', $delivery->subscription_id)); ?>" class="d-flex align-items-center justify-content-between gap-3 rounded border p-3 text-decoration-none">
              <span class="min-w-0">
                <span class="d-block fw-semibold text-truncate">#<?php echo e($delivery->subscription_id); ?> · <?php echo e($delivery->month_number); ?>-oy</span>
                <span class="d-block small text-secondary text-truncate"><?php echo e($delivery->subscription?->user?->name); ?> <?php echo e($delivery->subscription?->user?->lastname); ?></span>
                <span class="d-block small text-secondary"><?php echo e($delivery->dispatch_type_label); ?> · <?php echo e(optional($delivery->planned_for_date)->format('d.m.Y') ?? '—'); ?></span>
              </span>
              <span class="badge rounded-pill text-bg-warning"><?php echo e($delivery->status_label); ?></span>
            </a>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
  <?php endif; ?>

  <div class="row g-3">
    <?php $__currentLoopData = [
      ['Faol', $counts['active'], 'bi-check-circle', 'success'],
      ['Kutilmoqda', $counts['pending_payment'], 'bi-hourglass', 'warning'],
      ['To‘xtatilgan', $counts['paused'], 'bi-pause-circle', 'secondary'],
      ['Yakunlandi', $counts['completed'], 'bi-flag', 'info'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'active' => ['Faol', $counts['active']],
        'all' => ['Barchasi', $counts['all']],
        'pending_payment' => ['Kutilmoqda', $counts['pending_payment']],
        'paused' => ['To‘xtatilgan', $counts['paused']],
        'completed' => ['Yakunlangan', $counts['completed']],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Obunalar jadvali','meta' => $subs->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Obunalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($subs->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0 data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Foydalanuvchi</th>
            <th>Tarif</th>
            <th>Manzil</th>
            <th>Progress</th>
            <th>Keyingi yetkazish</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $addr = is_array($sub->address) ? $sub->address : [];
              $isOverdue = $sub->next_delivery_at && $sub->next_delivery_at->isPast() && $sub->status === 'active';
              $statusTone = match($sub->status) {
                'active' => 'success',
                'pending_payment' => 'warning',
                'paused' => 'secondary',
                'completed' => 'info',
                default => 'secondary',
              };
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($sub->id); ?></td>
              <td>
                <?php if($sub->user): ?>
                  <a href="<?php echo e(route('admin.users.show', $sub->user_id)); ?>" class="fw-semibold text-decoration-none"><?php echo e($sub->user->name); ?> <?php echo e($sub->user->lastname); ?></a>
                  <div class="small text-secondary"><?php echo e($sub->user->phone_number); ?></div>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($sub->plan): ?>
                  <div class="fw-semibold"><?php echo e($sub->plan->name_uz); ?></div>
                  <div class="small text-secondary"><?php echo e($sub->plan->months); ?> oy · <?php echo e($sub->books_per_month); ?> kitob/oy</div>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td class="text-secondary text-truncate" style="max-width: 14rem;"><?php echo e($addr['fullAddress'] ?? '—'); ?></td>
              <td style="min-width: 8rem;">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:.4rem;">
                    <div class="progress-bar bg-success" style="width:<?php echo e($sub->progress_pct); ?>%"></div>
                  </div>
                  <span class="small text-secondary text-nowrap"><?php echo e($sub->delivered_months); ?>/<?php echo e($sub->total_months); ?></span>
                </div>
              </td>
              <td class="text-nowrap <?php echo e($isOverdue ? 'text-danger' : 'text-secondary'); ?>">
                <?php echo e($sub->next_delivery_at ? $sub->next_delivery_at->format('d.m.Y') : '—'); ?>

              </td>
              <td><span class="badge rounded-pill text-bg-<?php echo e($statusTone); ?>"><?php echo e($sub->status_label); ?></span></td>
              <td class="text-end">
                <a href="<?php echo e(route('admin.mystery-box.show', $sub)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="text-center py-5 text-secondary">Obuna topilmadi.</td></tr>
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

  <?php if($subs->hasPages()): ?>
    <div><?php echo e($subs->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/mystery-box/index.blade.php ENDPATH**/ ?>