<?php $__env->startSection('title', $seller->shop_name); ?>
<?php $__env->startSection('page-title', 'Sotuvchi profili'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.sellers.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn btn-primary flex items-center gap-2">
        <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
    </a>
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


<div class="card p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        
        <div class="shrink-0">
            <?php if($seller->photo): ?>
                <img
                    src="<?php echo e(Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo)); ?>"
                    alt="<?php echo e($seller->shop_name); ?>"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-emerald-100 dark:ring-emerald-500/20"
                >
            <?php else: ?>
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    <?php echo e(strtoupper(substr($seller->shop_name ?? 'S', 0, 1))); ?>

                </div>
            <?php endif; ?>
        </div>

        
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold"><?php echo e($seller->shop_name); ?></h2>
                <?php if($seller->status === 'approved'): ?>
                    <span class="badge badge-success">Tasdiqlangan</span>
                <?php elseif($seller->status === 'pending'): ?>
                    <span class="badge badge-warning">Kutilmoqda</span>
                <?php elseif($seller->status === 'rejected'): ?>
                    <span class="badge badge-danger">Rad etilgan</span>
                <?php else: ?>
                    <span class="badge badge-muted"><?php echo e($seller->status); ?></span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-gray-500 mt-1"><?php echo e(trim($seller->firstname . ' ' . $seller->lastname)); ?></p>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                <?php if($seller->phone_number): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i> <?php echo e($seller->phone_number); ?>

                    </span>
                <?php endif; ?>
                <?php if($seller->region): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> <?php echo e($seller->region); ?>

                    </span>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="flex items-center gap-2 shrink-0">
            <?php if($seller->status !== 'approved'): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.approve', $seller)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            <?php endif; ?>
            <?php if($seller->status !== 'rejected'): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.reject', $seller)); ?>" onsubmit="return confirm('Sotuvchini rad etishga ishonchingiz komilmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-danger flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
            <i data-lucide="shopping-bag" class="w-6 h-6 text-blue-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami buyurtmalar</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($orderCount, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="banknote" class="w-6 h-6 text-green-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami daromad</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($totalRevenue, 0, '.', ' ')); ?></p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center">
            <i data-lucide="book-open" class="w-6 h-6 text-purple-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Kitoblar</p>
            <p class="text-2xl font-bold"><?php echo e($seller->books_count ?? 0); ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-gray-500 text-sm">#<?php echo e($order->id); ?></td>
                                <td class="font-semibold"><?php echo e(number_format((float)($order->amount ?? $order->total ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td class="text-sm text-gray-500">
                                    <?php echo e($order->created_at ? $order->created_at->format('d.m.Y') : '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 py-6">Buyurtmalar yo'q</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi tranzaksiyalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Holat</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-gray-500 text-sm">#<?php echo e($tx->id); ?></td>
                                <td class="font-semibold"><?php echo e(number_format((float)($tx->amount ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td>
                                    <?php if(in_array($tx->status, ['success', 'completed', 'approved'])): ?>
                                        <span class="badge badge-success">Muvaffaqiyatli</span>
                                    <?php elseif(in_array($tx->status, ['pending', 'processing'])): ?>
                                        <span class="badge badge-warning">Kutilmoqda</span>
                                    <?php elseif(in_array($tx->status, ['failed', 'rejected', 'cancelled'])): ?>
                                        <span class="badge badge-danger">Rad etilgan</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted"><?php echo e($tx->status ?? '—'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <?php echo e($tx->created_at ? $tx->created_at->format('d.m.Y') : '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-6">Tranzaksiyalar yo'q</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/sellers/show.blade.php ENDPATH**/ ?>