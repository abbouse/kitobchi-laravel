<?php $__env->startSection('title', 'Kitoblar'); ?>
<?php $__env->startSection('page-title', 'Kitoblar'); ?>

<?php $__env->startSection('content'); ?>
<div>
  <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    <div>
      <h2 class="text-xl font-bold tracking-tight">Kitoblar</h2>
      <p class="text-xs text-gray-500 mt-0.5"><?php echo e($books->total()); ?> ta yozuv topildi</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="GET" class="relative min-w-[220px]">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Sarlavha, muallif, kategoriya..." class="input !pl-9 !py-2 w-full">
      </form>
      <a href="<?php echo e(route('admin.books.create')); ?>" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
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
  <div class="hidden">
    <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
        $statusLabel = (int) ($book->is_approved ?? 0) === 1 ? 'active' : ((int) ($book->is_approved ?? 0) === 2 ? 'banned' : 'pending');
      ?>
      <div class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="font-semibold"><?php echo e($book->name); ?></div>
            <div class="text-xs text-gray-500"><?php echo e($book->authorProfile?->name ?: ($book->author ?: '—')); ?></div>
          </div>
          <span class="badge <?php echo e($statusLabel === 'active' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : 'badge-danger')); ?>"><?php echo e($statusLabel); ?></span>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
          <div><div class="text-xs text-gray-500">Kategoriya</div><div><?php echo e($book->category?->name_uz ?: '—'); ?></div></div>
          <div><div class="text-xs text-gray-500">Narx</div><div><?php echo e(number_format((float) $book->price, 0)); ?> UZS</div></div>
          <div><div class="text-xs text-gray-500">Stok</div><div><?php echo e((int) ($book->count ?? 0)); ?></div></div>
          <div><div class="text-xs text-gray-500">ID</div><div>#<?php echo e($book->id); ?></div></div>
        </div>
        <div class="mt-4 flex items-center justify-end gap-1">
          <a href="<?php echo e(route('admin.books.show', $book)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
          <a href="<?php echo e(route('admin.books.edit', $book)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="card p-6 text-sm text-gray-500">Kitoblar topilmadi.</div>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Kitob</th><th>Kategoriya</th><th>Narx</th><th>Stok</th><th>Status</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusLabel = (int) ($book->is_approved ?? 0) === 1 ? 'active' : ((int) ($book->is_approved ?? 0) === 2 ? 'banned' : 'pending');
            ?>
            <tr>
              <td><div class="font-semibold"><?php echo e($book->name); ?></div><div class="text-xs text-gray-500"><?php echo e($book->authorProfile?->name ?: ($book->author ?: '—')); ?></div></td>
              <td><?php echo e($book->category?->name_uz ?: '—'); ?></td>
              <td><?php echo e(number_format((float) $book->price, 0)); ?> UZS</td>
              <td><?php echo e((int) ($book->count ?? 0)); ?></td>
              <td><span class="badge <?php echo e($statusLabel === 'active' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : 'badge-danger')); ?>"><?php echo e($statusLabel); ?></span></td>
              <td>
                <div class="flex items-center justify-end gap-1 flex-wrap">
                  <form method="POST" action="<?php echo e(route('admin.books.moderate', $book)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Faollashtirish">
                      <i data-lucide="badge-check" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.books.moderate', $book)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                      <i data-lucide="badge-x" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('admin.books.show', $book)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                  <a href="<?php echo e(route('admin.books.edit', $book)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Kitoblar topilmadi.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php if(isset($books) && method_exists($books, 'links')): ?>
  <div class="mt-4"><?php echo e($books->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/books/index.blade.php ENDPATH**/ ?>