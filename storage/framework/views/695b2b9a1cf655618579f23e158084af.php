<?php $__env->startSection('title', 'Kantselyariya kategoriyalari'); ?>
<?php $__env->startSection('page-title', 'Kantselyariya kategoriyalari'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Kantselyariya kategoriyalari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Jami <strong><?php echo e($stats['total']); ?></strong> ta &middot;
      <span style="color:var(--p-success)"><?php echo e($stats['active']); ?> ta faol</span> <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.stationery-categories.create')); ?>" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi kategoriya
      </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>


<div class="filter-bar mb-3">
  <form method="GET" class="flex flex-wrap gap-2">
    <input type="search" name="search" class="p-form-control" placeholder="Nom bo'yicha..."
           value="<?php echo e(request('search')); ?>" style="width:220px">
    <select name="status" class="p-form-control" style="width:150px">
      <option value="">Barchasi</option>
      <option value="1" <?php echo e(request('status')==='1'?'selected':''); ?>>Aktiv</option>
      <option value="0" <?php echo e(request('status')==='0'?'selected':''); ?>>Nofaol</option>
    </select>
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i></button>
    <a href="<?php echo e(route('panel.stationery-categories.index')); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Icon</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Mahsulotlar</th>
          <th>Slug</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)"><?php echo e($cat->id); ?></td>
          <td style="font-size:22px;text-align:center"><?php echo e($cat->icon ?? '🗂️'); ?></td>
          <td style="font-weight:500;color:var(--p-text)"><?php echo e($cat->name_uz); ?></td>
          <td style="color:var(--p-muted)"><?php echo e($cat->name_ru); ?></td>
          <td>
            <span class="s-pill accent"><?php echo e(number_format($cat->stationeries_count)); ?> ta</span>
          </td>
          <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace"><?php echo e($cat->slug ?: '—'); ?></td>
          <td>
            <span class="s-pill <?php echo e($cat->is_active?'success':'muted'); ?>">
              <?php echo e($cat->is_active?'Aktiv':'Nofaol'); ?>

            </span>
          </td>
          <td>
            <div class="flex gap-1">
              <a href="<?php echo e(route('panel.stationery-categories.edit', $cat)); ?>" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="<?php echo e(route('panel.stationery-categories.toggle', $cat)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p ghost sm">
                  <i class="bi bi-<?php echo e($cat->is_active?'eye-slash':'eye'); ?>"></i>
                </button>
              </form>
              <?php if($cat->stationeries_count == 0): ?>
              <form method="POST" action="<?php echo e(route('panel.stationery-categories.destroy', $cat)); ?>"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
          Kategoriyalar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($categories->hasPages()): ?>
  <div class="p-pagination"><?php echo e($categories->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/stationery-categories/index.blade.php ENDPATH**/ ?>