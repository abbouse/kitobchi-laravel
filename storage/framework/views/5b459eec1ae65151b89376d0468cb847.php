<?php $__env->startSection('title', 'Kitob Kategoriyalari'); ?>
<?php $__env->startSection('page-title', 'Kitob Kategoriyalari'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h2 class="text-xl font-bold">Kitob Kategoriyalari</h2>
    <p class="text-xs text-gray-500 mt-0.5">Jami: <?php echo e($stats['total']); ?>, Faol: <?php echo e($stats['active']); ?></p>
  </div>
  <div class="flex items-center gap-2">
    <form method="GET" class="relative">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Qidirish..." class="input !pl-9 !py-2 w-60">
    </form>
    <a href="<?php echo e(route('admin.book-categories.create')); ?>" class="btn btn-primary">
      <i data-lucide="plus" class="w-4 h-4"></i> Qo'shish
    </a>
  </div>
</div>

<?php if(session('success')): ?>
  <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm flex items-center gap-2">
    <i data-lucide="check-circle" class="w-4 h-4"></i> <?php echo e(session('success')); ?>

  </div>
<?php endif; ?>
<?php if(session('error')): ?>
  <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm flex items-center gap-2">
    <i data-lucide="alert-circle" class="w-4 h-4"></i> <?php echo e(session('error')); ?>

  </div>
<?php endif; ?>

<div class="table-wrap">
  <div class="overflow-x-auto">
    <table class="tbl" data-index-grid>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Icon</th>
          <th>Kitoblar</th>
          <th>Holat</th>
          <th class="text-right">Amallar</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td class="text-gray-500 text-xs"><?php echo e($cat->id); ?></td>
            <td class="font-semibold"><?php echo e($cat->name_uz); ?></td>
            <td class="text-gray-500"><?php echo e($cat->name_ru); ?></td>
            <td class="text-lg"><?php echo e($cat->icon); ?></td>
            <td><span class="font-semibold"><?php echo e($cat->books_count); ?></span></td>
            <td>
              <?php if($cat->is_active): ?>
                <span class="badge badge-success">Faol</span>
              <?php else: ?>
                <span class="badge badge-muted">Yashirin</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex items-center justify-end gap-1">
                <form method="POST" action="<?php echo e(route('admin.book-categories.toggle', $cat)); ?>">
                  <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                  <button class="btn-ghost p-2 rounded-lg" title="<?php echo e($cat->is_active ? 'Yashirish' : 'Faollashtirish'); ?>">
                    <i data-lucide="<?php echo e($cat->is_active ? 'eye-off' : 'eye'); ?>" class="w-4 h-4"></i>
                  </button>
                </form>
                <a href="<?php echo e(route('admin.book-categories.edit', $cat)); ?>" class="btn-ghost p-2 rounded-lg">
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                </a>
                <form method="POST" action="<?php echo e(route('admin.book-categories.destroy', $cat)); ?>" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                  <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                  <button class="btn-ghost p-2 rounded-lg text-rose-500">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="7" class="text-center py-10 text-gray-400">
            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
            Hech narsa topilmadi
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4"><?php echo e($categories->links('a122.partials.pagination')); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/book-categories/index.blade.php ENDPATH**/ ?>