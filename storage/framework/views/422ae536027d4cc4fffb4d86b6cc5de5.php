<?php $__env->startSection('title', 'Tranzaksiyalar'); ?>
<?php $__env->startSection('page-title', 'Sotuvchi tranzaksiyalari'); ?>

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
        <div class="a122-index-header__title">Tranzaksiyalar ro‘yxati</div>
        <div class="a122-index-header__meta"><?php echo e($transactions->total()); ?> ta tranzaksiya topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'pending')); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, sotuvchi yoki summa bo‘yicha qidiring">
        </form>
    </div>
</div>


<div class="flex items-center gap-2 mb-4 flex-wrap">
    <?php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda',  'count' => $counts['pending']  ?? 0],
            'approved' => ['label' => 'Tasdiqlangan','count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'all'      => ['label' => 'Barchasi',    'count' => $counts['all']      ?? 0],
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
                    <th>Sotuvchi</th>
                    <th>Miqdor</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm">#<?php echo e($transaction->id); ?></td>
                        <td>
                            <div class="font-semibold"><?php echo e($transaction->seller->shop_name ?? '—'); ?></div>
                        </td>
                        <td class="font-semibold text-sm whitespace-nowrap">
                            <?php echo e(number_format((float)($transaction->amount ?? 0), 0, '.', ' ')); ?> UZS
                        </td>
                        <td>
                            <?php if($transaction->status === 'approved'): ?>
                                <span class="badge badge-success">Tasdiqlangan</span>
                            <?php elseif($transaction->status === 'pending'): ?>
                                <span class="badge badge-warning">Kutilmoqda</span>
                            <?php elseif($transaction->status === 'rejected'): ?>
                                <span class="badge badge-danger">Rad etilgan</span>
                            <?php else: ?>
                                <span class="badge badge-muted"><?php echo e($transaction->status ?? '—'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            <?php echo e($transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—'); ?>

                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="<?php echo e(route('admin.transactions.approve', $transaction)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.transactions.reject', $transaction)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="<?php echo e(route('admin.transactions.show', $transaction)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday tranzaksiya topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($transactions->hasPages()): ?>
    <div class="mt-4"><?php echo e($transactions->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/transactions/index.blade.php ENDPATH**/ ?>