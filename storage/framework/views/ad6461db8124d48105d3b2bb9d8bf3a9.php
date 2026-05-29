<?php $__env->startSection('title', 'Yangiliklar'); ?>
<?php $__env->startSection('page-title', 'Yangiliklar'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $currentTab = request('tab', 'all');
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'active' => ['label' => 'Faol', 'count' => $counts['active'] ?? 0],
    'news' => ['label' => 'Yangiliklar', 'count' => $counts['news'] ?? 0],
  ];
?>

<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Content','title' => 'Yangiliklar','subtitle' => ''.e($news->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Content','title' => 'Yangiliklar','subtitle' => ''.e($news->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($currentTab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Sarlavha yoki do‘kon" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.news.create')); ?>" class="btn-p primary">
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

  <?php if(session('success')): ?>
    <div class="alert alert-success border-0 mb-0"><?php echo e(session('success')); ?></div>
  <?php endif; ?>
  <?php if(session('error')): ?>
    <div class="alert alert-danger border-0 mb-0"><?php echo e(session('error')); ?></div>
  <?php endif; ?>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($currentTab === $key ? 'active' : ''); ?>">
          <?php echo e($tab['label']); ?>

          <span class="badge rounded-pill <?php echo e($currentTab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($tab['count'])); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Yangiliklar jadvali','meta' => $news->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Yangiliklar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($news->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sarlavha</th>
            <th>Harakat</th>
            <th>Holat</th>
            <th>Yaratilgan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $news; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $isActive = $item->status === 'active' || $item->status == 1 || $item->status === true;
              $actionLabel = match ($item->action) {
                'news' => 'Yangilik',
                'to_shop' => 'Do‘konga',
                'to_product' => 'Mahsulotga',
                default => $item->action,
              };
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($item->id); ?></td>
              <td class="fw-semibold text-truncate" style="max-width: 22rem;"><?php echo e($item->title); ?></td>
              <td><span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis"><?php echo e($actionLabel); ?></span></td>
              <td>
                <?php if($isActive): ?>
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                <?php else: ?>
                  <span class="badge rounded-pill text-bg-secondary">Yashirin</span>
                <?php endif; ?>
              </td>
              <td class="text-secondary"><?php echo e($item->created_at ? $item->created_at->format('d.m.Y H:i') : '—'); ?></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="<?php echo e(route('admin.news.show', $item)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('admin.news.edit', $item)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="<?php echo e(route('admin.news.toggle', $item)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="<?php echo e($isActive ? 'Yashirish' : 'Faollashtirish'); ?>">
                      <i class="bi bi-<?php echo e($isActive ? 'eye-slash' : 'eye'); ?>"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.news.destroy', $item)); ?>" onsubmit="return confirm('Bu yangilikni o‘chirishga ishonchingiz komilmi?')">
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
            <tr><td colspan="6" class="text-center py-5 text-secondary">Yangilik topilmadi.</td></tr>
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

  <?php if($news->hasPages()): ?>
    <div><?php echo e($news->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/news/index.blade.php ENDPATH**/ ?>