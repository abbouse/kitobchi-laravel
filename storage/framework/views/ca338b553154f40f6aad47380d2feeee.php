<?php $__env->startSection('title', 'Stationery'); ?>
<?php $__env->startSection('page-title', 'Stationery'); ?>

<?php $__env->startSection('content'); ?>
<section class="card p-0 overflow-hidden">
  <div class="p-4 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h3 class="font-bold">Kanstovarlar</h3>
    <form method="get" class="relative w-full sm:w-72">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Qidirish..." class="pl-9 pr-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-white/5 border border-transparent focus:border-emerald-500 focus:outline-none w-full">
    </form>
  </div>
  <div class="tab-pills fade-up mb-0 px-4 pt-4">
    <?php $__currentLoopData = [
      'pending' => ['Moderatsiya', $counts['pending'] ?? 0],
      'active' => ['Faol', $counts['active'] ?? 0],
      'rejected' => ['Rad etilgan', $counts['rejected'] ?? 0],
      'all' => ['Barchasi', $counts['all'] ?? 0],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
        <?php echo e($label); ?> <span><?php echo e($count); ?></span>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="overflow-x-auto">
    <table class="tbl" data-index-grid>
      <thead>
      <tr>
        <th>ID</th>
        <th>Nomi</th>
        <th>Kategoriya</th>
        <th>Narx</th>
        <th>Stock</th>
        <th>Status</th>
        <th class="text-right">Amallar</th>
      </tr>
      </thead>
      <tbody>
      <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>#<?php echo e($row['id']); ?></td>
          <td class="font-medium"><?php echo e($row['title']); ?></td>
          <td><?php echo e($row['category']); ?></td>
          <td><?php echo e($row['price']); ?></td>
          <td><?php echo e($row['stock']); ?></td>
          <td><span class="badge badge-<?php echo e($row['status'] === 'active' ? 'success' : ($row['status'] === 'pending' ? 'warning' : 'danger')); ?>"><?php echo e($row['status']); ?></span></td>
          <td>
            <div class="flex items-center justify-end gap-1 flex-wrap">
              <form method="POST" action="<?php echo e(route('admin.stationery.moderate', $row['id'])); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="is_approved" value="1">
                <button type="submit" class="btn-ghost p-2 rounded-lg" title="Faollashtirish"><i data-lucide="badge-check" class="w-4 h-4"></i></button>
              </form>
              <form method="POST" action="<?php echo e(route('admin.stationery.moderate', $row['id'])); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="is_approved" value="2">
                <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish"><i data-lucide="badge-x" class="w-4 h-4"></i></button>
              </form>
              <a href="<?php echo e(route('admin.stationery.show', $row['id'])); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
              <a href="<?php echo e(route('admin.stationery.edit', $row['id'])); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="7" class="text-center text-gray-500">Ma'lumot topilmadi</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4 border-t border-gray-100 dark:border-white/5">
    <?php echo e($items->links('a122.partials.pagination')); ?>

  </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/stationery/index.blade.php ENDPATH**/ ?>