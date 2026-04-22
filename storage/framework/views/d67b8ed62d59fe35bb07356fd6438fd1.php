<?php $__env->startSection('title', 'Kuryer buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmalari'); ?>

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
        <div class="a122-index-header__title">Kuryer buyurtmalari</div>
        <div class="a122-index-header__meta"><?php echo e($orders->total()); ?> ta yetkazib berish yozuvi topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, kuryer yoki foydalanuvchi bo‘yicha qidiring">
        </form>
    </div>
</div>

<div class="tab-pills fade-up mb-3">
    <?php $__currentLoopData = [
        'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
        'in_delivery' => ["Yo'lda", $counts['in_delivery'] ?? 0],
        'delivered' => ['Yetkazildi', $counts['delivered'] ?? 0],
        'rejected' => ['Bekor qilingan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e(($tab ?? 'all') === $key ? 'active' : ''); ?>">
            <?php echo e($label); ?> <span><?php echo e($count); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kuryer</th>
                    <th>Foydalanuvchi</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm">#<?php echo e($order->id); ?></td>
                        <td>
                            <?php if($order->courier): ?>
                                <div class="font-semibold">
                                    <?php echo e(trim(($order->courier->first_name ?? '') . ' ' . ($order->courier->last_name ?? '')) ?: '—'); ?>

                                </div>
                                <?php if($order->courier->phone_number ?? $order->courier->phone): ?>
                                    <div class="text-xs text-gray-500"><?php echo e($order->courier->phone_number ?? $order->courier->phone); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($order->user): ?>
                                <div class="font-medium">
                                    <?php echo e(trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—'); ?>

                                </div>
                                <?php if($order->user->phone_number ?? $order->user->phone): ?>
                                    <div class="text-xs text-gray-500"><?php echo e($order->user->phone_number ?? $order->user->phone); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="<?php echo e(route('admin.courier-orders.status', $order)); ?>" class="inline-flex">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>" <?php if(($order->status ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($statusItem['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </form>
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            <?php echo e($order->created_at ? $order->created_at->format('d.m.Y H:i') : '—'); ?>

                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?php echo e(route('admin.courier-orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday kuryer buyurtmasi topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($orders->hasPages()): ?>
    <div class="mt-4"><?php echo e($orders->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/courier-orders/index.blade.php ENDPATH**/ ?>