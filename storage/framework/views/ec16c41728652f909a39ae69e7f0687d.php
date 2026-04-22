<?php $__env->startSection('title', 'Sotuvchilar'); ?>
<?php $__env->startSection('page-title', 'Sotuvchilar'); ?>

<?php $__env->startSection('content'); ?>
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

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Sotuvchilar ro‘yxati</div>
        <div class="a122-index-header__meta"><?php echo e($sellers->total()); ?> ta sotuvchi topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'pending')); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Do‘kon, telefon yoki hudud bo‘yicha qidiring">
        </form>
    </div>
</div>


<div class="flex items-center gap-2 mb-4 flex-wrap">
    <?php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
        ];
    ?>
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a
            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key])); ?>"
            class="btn <?php echo e($tab === $key ? 'btn-primary' : 'btn-secondary'); ?> flex items-center gap-2 text-sm"
        >
            <?php echo e($tabItem['label']); ?>

            <span class="badge <?php echo e($tab === $key ? 'badge-info' : 'badge-muted'); ?>"><?php echo e($tabItem['count']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Do'kon nomi</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Kitoblar</th>
                    <th>Buyurtmalar</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $sellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm"><?php echo e($seller->id); ?></td>
                        <td>
                            <div class="flex items-center gap-3 min-w-0">
                                <?php echo $__env->make('a122.partials.avatar', [
                                    'name' => trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? '')) ?: ($seller->shop_name ?? 'S'),
                                    'image' => $seller->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <div class="min-w-0">
                                    <div class="font-semibold truncate"><?php echo e($seller->shop_name); ?></div>
                                    <?php if($seller->firstname || $seller->lastname): ?>
                                        <div class="text-xs text-gray-500 truncate"><?php echo e(trim($seller->firstname . ' ' . $seller->lastname)); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm"><?php echo e($seller->phone_number ?: '—'); ?></td>
                        <td class="text-sm"><?php echo e($seller->region ?: '—'); ?></td>
                        <td>
                            <span class="badge badge-muted"><?php echo e($seller->books_count ?? 0); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-muted"><?php echo e($seller->orders_count ?? 0); ?></span>
                        </td>
                        <td>
                            <?php if($seller->status === 'approved'): ?>
                                <span class="badge badge-success">Tasdiqlangan</span>
                            <?php elseif($seller->status === 'pending'): ?>
                                <span class="badge badge-warning">Kutilmoqda</span>
                            <?php elseif($seller->status === 'rejected'): ?>
                                <span class="badge badge-danger">Rad etilgan</span>
                            <?php else: ?>
                                <span class="badge badge-muted"><?php echo e($seller->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="<?php echo e(route('admin.sellers.approve', $seller)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.sellers.reject', $seller)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="<?php echo e(route('admin.sellers.show', $seller)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-8">Hech qanday sotuvchi topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($sellers->hasPages()): ?>
    <div class="mt-4"><?php echo e($sellers->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/sellers/index.blade.php ENDPATH**/ ?>