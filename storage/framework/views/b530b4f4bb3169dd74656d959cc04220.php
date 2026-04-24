<?php $__env->startSection('title', 'Reels'); ?>
<?php $__env->startSection('page-title', 'Reels'); ?>

<?php $__env->startSection('content'); ?>
<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Reels ro‘yxati</div>
        <div class="a122-index-header__meta"><?php echo e($reels->total()); ?> ta reel topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Sarlavha yoki tavsif bo‘yicha qidiring">
        </form>
        <a href="<?php echo e(route('admin.reels.create')); ?>" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi reel
        </a>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sarlavha</th>
                    <th>Tavsif</th>
                    <th>Tartib</th>
                    <th>Videolar soni</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $reels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm"><?php echo e($reel->id); ?></td>
                        <td class="font-semibold"><?php echo e($reel->title); ?></td>
                        <td class="text-sm text-gray-500 max-w-xs truncate"><?php echo e($reel->description ?: '—'); ?></td>
                        <td><?php echo e($reel->order); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo e($reel->items_count ?? $reel->items->count()); ?></span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?php echo e(route('admin.reels.show', $reel)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="<?php echo e(route('admin.reels.edit', $reel)); ?>" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.reels.destroy', $reel)); ?>" onsubmit="return confirm('Bu reelni o\'chirishga ishonchingiz komilmi?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg text-red-500 hover:text-red-700" title="O'chirish">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday reel topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($reels->hasPages()): ?>
    <div class="mt-4"><?php echo e($reels->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/reels/index.blade.php ENDPATH**/ ?>