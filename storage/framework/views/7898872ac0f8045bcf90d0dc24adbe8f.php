<?php $__env->startSection('title', 'Kitoblar'); ?>
<?php $__env->startSection('page-title', 'Kitoblar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Catalog','title' => 'Kitoblar','subtitle' => ''.e($books->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Catalog','title' => 'Kitoblar','subtitle' => ''.e($books->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Kitob, muallif, do'kon yoki kategoriya" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.books.create')); ?>" class="btn-p primary">
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

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Moderatsiyada','value' => number_format($counts['pending'] ?? 0),'icon' => 'hourglass-split','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Moderatsiyada','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['pending'] ?? 0)),'icon' => 'hourglass-split','tone' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Faol kitoblar','value' => number_format($counts['active'] ?? 0),'icon' => 'patch-check','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Faol kitoblar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['active'] ?? 0)),'icon' => 'patch-check','tone' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Rad etilgan','value' => number_format($counts['rejected'] ?? 0),'icon' => 'x-octagon','tone' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Rad etilgan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['rejected'] ?? 0)),'icon' => 'x-octagon','tone' => 'danger']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Jami katalog','value' => number_format($counts['all'] ?? 0),'icon' => 'collection','tone' => 'dark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Jami katalog','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['all'] ?? 0)),'icon' => 'collection','tone' => 'dark']); ?>
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

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'pending' => ['Moderatsiya', $counts['pending'] ?? 0],
        'active' => ['Faol', $counts['active'] ?? 0],
        'rejected' => ['Rad etilgan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
          <?php echo e($label); ?>

          <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($count)); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php if($tab === 'pending'): ?>
      <div class="small text-secondary mt-3">
        Hozir birinchi bo‘lib moderatsiyaga tushgan kitoblar ko‘rsatilmoqda. Shu yerning o‘zidan tasdiqlash yoki rad etish mumkin.
      </div>
    <?php endif; ?>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Kitoblar jadvali','meta' => $books->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kitoblar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($books->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Kitob</th>
            <th>Do‘kon</th>
            <th>Kategoriya</th>
            <th>Narx</th>
            <th>Stok</th>
            <th>Status</th>
            <th>Yangilangan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusClass = $row['status'] === 'active'
                ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                : ($row['status'] === 'pending'
                  ? 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis'
                  : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis');
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($row['id']); ?></td>
              <td>
                <div class="fw-semibold"><?php echo e($row['title']); ?></div>
                <div class="small text-secondary"><?php echo e($row['author']); ?></div>
              </td>
              <td><?php echo e($row['seller']); ?></td>
              <td><?php echo e($row['category']); ?></td>
              <td class="fw-semibold"><?php echo e($row['price']); ?></td>
              <td><?php echo e($row['stock']); ?></td>
              <td><span class="badge rounded-pill <?php echo e($statusClass); ?>"><?php echo e($row['status_label']); ?></span></td>
              <td class="small text-secondary"><?php echo e($row['updated_at']); ?></td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="<?php echo e(route('admin.books.moderate', $row['id'])); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Faollashtirish">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.books.moderate', $row['id'])); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('admin.books.show', $row['id'])); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('admin.books.edit', $row['id'])); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="9" class="text-center py-5 text-secondary">Bu filtr bo‘yicha kitob topilmadi.</td></tr>
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

  <?php if(isset($books) && method_exists($books, 'links')): ?>
    <div><?php echo e($books->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/books/index.blade.php ENDPATH**/ ?>