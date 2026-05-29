<?php $__env->startSection('title', 'Karyera arizalari'); ?>
<?php $__env->startSection('page-title', 'Karyera arizalari'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Content','title' => 'Karyera arizalari','subtitle' => ''.e($applications->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Content','title' => 'Karyera arizalari','subtitle' => ''.e($applications->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 28rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, ism, email yoki Telegram" class="form-control">
    </form>
    <?php if(request('search')): ?>
      <a href="<?php echo e(route('admin.job-applications.index', ['tab' => $tab])); ?>" class="btn btn-light border">Tozalash</a>
    <?php endif; ?>
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

  <?php if(session('success')): ?>
    <div class="alert alert-success border-0 mb-0"><?php echo e(session('success')); ?></div>
  <?php endif; ?>
  <?php if(session('error')): ?>
    <div class="alert alert-danger border-0 mb-0"><?php echo e(session('error')); ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <?php $__currentLoopData = [
      [$counts['all'], 'Jami', 'bi-briefcase', 'primary'],
      [$counts['vacancy'], 'Vakansiya', 'bi-person-workspace', 'info'],
      [$counts['inquiry'], 'Murojaat', 'bi-chat-square-text', 'warning'],
      [$counts['new'], 'Yangi', 'bi-stars', 'success'],
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

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'all' => ['Barchasi', $counts['all']],
        'vacancy' => ['Vakansiya', $counts['vacancy']],
        'inquiry' => ['Murojaat', $counts['inquiry']],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Arizalar jadvali','meta' => $applications->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Arizalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($applications->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Turi</th>
            <th>Lavozim</th>
            <th>Ism</th>
            <th>Email</th>
            <th>Telegram</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td class="text-secondary">#<?php echo e($row->id); ?></td>
              <td>
                <span class="badge rounded-pill <?php echo e($row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'text-bg-secondary' : 'text-bg-info-subtle border border-info-subtle text-info-emphasis'); ?>">
                  <?php echo e($row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat' : 'Vakansiya'); ?>

                </span>
              </td>
              <td class="fw-semibold"><?php echo e($row->vacancy?->title ?? '—'); ?></td>
              <td><?php echo e($row->full_name); ?></td>
              <td class="text-secondary"><?php echo e($row->email); ?></td>
              <td class="text-secondary"><?php echo e($row->telegram_username ? '@' . $row->telegram_username : '—'); ?></td>
              <td>
                <form method="POST" action="<?php echo e(route('admin.job-applications.status', $row)); ?>">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('PATCH'); ?>
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($key); ?>" <?php echo e($row->status === $key ? 'selected' : ''); ?>><?php echo e($status['label']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </select>
                </form>
              </td>
              <td class="text-secondary text-nowrap"><?php echo e($row->created_at?->format('d.m.Y H:i')); ?></td>
              <td class="text-end">
                <a href="<?php echo e(route('admin.job-applications.show', $row)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="9" class="text-center py-5 text-secondary">Ariza topilmadi.</td></tr>
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

  <div><?php echo e($applications->links('a122.partials.pagination')); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/job-applications/index.blade.php ENDPATH**/ ?>