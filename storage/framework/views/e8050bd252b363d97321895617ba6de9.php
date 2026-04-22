<?php $__env->startSection('title', trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? ''))); ?>
<?php $__env->startSection('page-title', 'Kuryer profili'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>" class="btn btn-primary flex items-center gap-2">
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
            <?php if($courier->photo): ?>
                <img
                    src="<?php echo e(Str::startsWith($courier->photo, 'http') ? $courier->photo : asset('storage/' . $courier->photo)); ?>"
                    alt="<?php echo e(trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? ''))); ?>"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-blue-100 dark:ring-blue-500/20"
                >
            <?php else: ?>
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    <?php echo e(strtoupper(substr($courier->first_name ?? 'K', 0, 1))); ?>

                </div>
            <?php endif; ?>
        </div>

        
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold">
                    <?php echo e(trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: '—'); ?>

                </h2>
                <?php if($courier->status === 'approved'): ?>
                    <span class="badge badge-success">Tasdiqlangan</span>
                <?php elseif($courier->status === 'pending'): ?>
                    <span class="badge badge-warning">Kutilmoqda</span>
                <?php elseif($courier->status === 'rejected'): ?>
                    <span class="badge badge-danger">Rad etilgan</span>
                <?php else: ?>
                    <span class="badge badge-muted"><?php echo e($courier->status ?? '—'); ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                <?php if($courier->phone_number ?? $courier->phone): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        <?php echo e($courier->phone_number ?? $courier->phone); ?>

                    </span>
                <?php endif; ?>
                <?php if($courier->region): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <?php echo e($courier->region); ?>

                    </span>
                <?php endif; ?>
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <?php echo e($courier->created_at ? $courier->created_at->format('d.m.Y') : '—'); ?>

                </span>
            </div>
        </div>

        
        <div class="flex items-center gap-2 shrink-0">
            <?php if($courier->status !== 'approved'): ?>
                <form method="POST" action="<?php echo e(route('admin.couriers.approve', $courier)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            <?php endif; ?>
            <?php if($courier->status !== 'rejected'): ?>
                <form method="POST" action="<?php echo e(route('admin.couriers.reject', $courier)); ?>"
                      onsubmit="return confirm('Kuryerni rad etishga ishonchingiz komilmi?')">
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
            <i data-lucide="package" class="w-6 h-6 text-blue-500"></i>
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
            <p class="text-2xl font-bold"><?php echo e(number_format($totalEarned, 0, '.', ' ')); ?></p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-500/10 flex items-center justify-center">
            <i data-lucide="wallet" class="w-6 h-6 text-yellow-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Balans</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($courier->balance ?? 0, 0, '.', ' ')); ?></p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>
</div>


<div class="card p-5">
    <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foydalanuvchi</th>
                        <th>Sana</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-gray-500 text-sm">#<?php echo e($order->id); ?></td>
                            <td>
                                <?php if($order->user): ?>
                                    <?php echo e(trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—'); ?>

                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-500">
                                <?php echo e($order->created_at ? $order->created_at->format('d.m.Y H:i') : '—'); ?>

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/couriers/show.blade.php ENDPATH**/ ?>