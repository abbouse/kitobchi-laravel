<?php $__env->startSection('title', 'Kitob kategoriyalari'); ?>
<?php $__env->startSection('page-title', 'Kitob kategoriyalari'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <div style="font-size:13px;color:var(--p-hint)">
      Jami: <strong style="color:var(--p-text)"><?php echo e($stats['total']); ?></strong> ta,
      Aktiv: <strong style="color:var(--p-success)"><?php echo e($stats['active']); ?></strong> ta
    </div>
  </div>
  <a href="<?php echo e(route('panel.book-categories.create')); ?>" class="btn-p">
    <i class="bi bi-plus-lg"></i> Yangi kategoriya
  </a>
</div>


<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="search" name="search" class="p-form-control" placeholder="Nom bo'yicha..."
           value="<?php echo e(request('search')); ?>" style="width:220px">
    <select name="status" class="p-form-control" style="width:150px">
      <option value="">Barchasi</option>
      <option value="1" <?php echo e(request('status')==='1'?'selected':''); ?>>Aktiv</option>
      <option value="0" <?php echo e(request('status')==='0'?'selected':''); ?>>Nofaol</option>
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="<?php echo e(route('panel.book-categories.index')); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Icon</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Kitoblar</th>
          <th>Status</th>
          <th>Teglar</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent)"><?php echo e($cat->id); ?></td>
          <td style="font-size:22px;text-align:center"><?php echo e($cat->icon ?? '📚'); ?></td>
          <td style="font-weight:500;color:var(--p-text)"><?php echo e($cat->name_uz); ?></td>
          <td style="color:var(--p-muted)"><?php echo e($cat->name_ru); ?></td>
          <td>
            <span class="s-pill accent"><?php echo e(number_format($cat->books_count)); ?> ta</span>
          </td>
          <td>
            <span class="s-pill <?php echo e($cat->is_active ? 'success' : 'muted'); ?>">
              <?php echo e($cat->is_active ? 'Aktiv' : 'Nofaol'); ?>

            </span>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            <?php echo e($cat->tags?->pluck('tag_name_uz')->take(3)->implode(', ') ?: '—'); ?>

          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?php echo e(route('panel.book-categories.edit', $cat)); ?>" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="<?php echo e(route('panel.book-categories.toggle', $cat)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p ghost sm" title="<?php echo e($cat->is_active ? 'O\'chirish' : 'Faollashtirish'); ?>">
                  <i class="bi bi-<?php echo e($cat->is_active ? 'eye-slash' : 'eye'); ?>"></i>
                </button>
              </form>
              <?php if($cat->books_count == 0): ?>
              <form method="POST" action="<?php echo e(route('panel.book-categories.destroy', $cat)); ?>"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">Kategoriyalar topilmadi</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($categories->hasPages()): ?>
  <div class="p-pagination"><?php echo e($categories->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/book-categories/index.blade.php ENDPATH**/ ?>