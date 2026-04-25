<?php $__env->startSection('title', 'Kuryerlar'); ?>
<?php $__env->startSection('page-title', 'Kuryerlar'); ?>

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
        <div class="a122-index-header__title">Kuryerlar ro‘yxati</div>
        <div class="a122-index-header__meta"><?php echo e($couriers->total()); ?> ta kuryer topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'all')); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, telefon yoki hudud bo‘yicha qidiring">
        </form>
        <a href="<?php echo e(route('admin.couriers.create')); ?>" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi kuryer
        </a>
    </div>
</div>


<div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    
    <div class="flex items-center gap-2 flex-wrap">
        <?php
            $tabs = [
                'all'      => ['label' => 'Barchasi',    'count' => $counts['all']      ?? 0],
                'approved' => ['label' => 'Tasdiqlangan','count' => $counts['approved'] ?? 0],
                'pending'  => ['label' => 'Kutilmoqda',  'count' => $counts['pending']  ?? 0],
                'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
                'blocked'  => ['label' => 'Bloklangan',  'count' => $counts['blocked']  ?? 0],
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
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ism</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Balans</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $couriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $courier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm"><?php echo e($courier->id); ?></td>
                        <td>
                            <div class="flex items-center gap-3 min-w-0">
                                <?php echo $__env->make('a122.partials.avatar', [
                                    'name' => trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: 'Kuryer',
                                    'image' => $courier->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">
                                        <?php echo e(trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: '—'); ?>

                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm"><?php echo e($courier->phone_number ?? $courier->phone ?? '—'); ?></td>
                        <td class="text-sm"><?php echo e($courier->region ?? '—'); ?></td>
                        <td class="text-sm font-medium whitespace-nowrap">
                            <?php echo e(number_format((float)($courier->balance ?? 0), 0, '.', ' ')); ?> UZS
                        </td>
                        <td>
                            <?php if($courier->status === 'approved'): ?>
                                <span class="badge badge-success">Tasdiqlangan</span>
                            <?php elseif($courier->status === 'pending'): ?>
                                <span class="badge badge-warning">Kutilmoqda</span>
                            <?php elseif($courier->status === 'rejected'): ?>
                                <span class="badge badge-danger">Rad etilgan</span>
                            <?php elseif($courier->status === 'blocked'): ?>
                                <span class="badge badge-danger flex items-center gap-1">
                                    <i data-lucide="ban" class="w-3 h-3"></i> Bloklangan
                                </span>
                            <?php else: ?>
                                <span class="badge badge-muted"><?php echo e($courier->status ?? '—'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="<?php echo e(route('admin.couriers.approve', $courier)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.couriers.reject', $courier)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="<?php echo e(route('admin.couriers.show', $courier)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.couriers.destroy', $courier)); ?>"
                                    onsubmit="return confirm('Kuryerni o\'chirishga ishonchingiz komilmi?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg text-red-500 hover:text-red-600" title="O'chirish">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-8">Hech qanday kuryer topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($couriers->hasPages()): ?>
    <div class="mt-4"><?php echo e($couriers->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/couriers/index.blade.php ENDPATH**/ ?>