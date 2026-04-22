<?php $__env->startSection('title', 'Kuryer buyurtmasi #' . $courierOrder->id); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmasi'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <a href="<?php echo e(route('admin.courier-orders.index')); ?>" class="btn btn-secondary flex items-center gap-2 w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="bike" class="w-5 h-5 text-gray-400"></i>
            Kuryer ma'lumotlari
        </h3>
        <?php if($courierOrder->courier): ?>
            <div class="flex items-center gap-4 mb-4">
                <?php if($courierOrder->courier->photo): ?>
                    <img
                        src="<?php echo e(Str::startsWith($courierOrder->courier->photo, 'http') ? $courierOrder->courier->photo : asset('storage/' . $courierOrder->courier->photo)); ?>"
                        alt="Kuryer"
                        class="w-14 h-14 rounded-full object-cover ring-4 ring-blue-100 dark:ring-blue-500/20"
                    >
                <?php else: ?>
                    <div class="w-14 h-14 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-xl font-bold text-gray-500 dark:text-gray-300">
                        <?php echo e(strtoupper(substr($courierOrder->courier->first_name ?? 'K', 0, 1))); ?>

                    </div>
                <?php endif; ?>
                <div>
                    <div class="font-bold text-base">
                        <?php echo e(trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: '—'); ?>

                    </div>
                    <div class="text-sm text-gray-500"><?php echo e($courierOrder->courier->phone_number ?? $courierOrder->courier->phone ?? '—'); ?></div>
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-500 mb-1">Viloyat</dt>
                    <dd><?php echo e($courierOrder->courier->region ?? '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                    <dd>
                        <?php $cs = $courierOrder->courier->status ?? ''; ?>
                        <?php if($cs === 'approved'): ?>
                            <span class="badge badge-success">Tasdiqlangan</span>
                        <?php elseif($cs === 'pending'): ?>
                            <span class="badge badge-warning">Kutilmoqda</span>
                        <?php elseif($cs === 'rejected'): ?>
                            <span class="badge badge-danger">Rad etilgan</span>
                        <?php else: ?>
                            <span class="badge badge-muted"><?php echo e($cs ?: '—'); ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        <?php else: ?>
            <p class="text-gray-400 text-sm">Kuryer ma'lumotlari mavjud emas</p>
        <?php endif; ?>
    </div>

    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
            Buyurtma ma'lumotlari
        </h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 mb-1">Buyurtma ID</dt>
                <dd class="font-semibold">#<?php echo e($courierOrder->id); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                <dd>
                    <?php $status = $courierOrder->status ?? ''; ?>
                    <?php if(isset($statuses[$status])): ?>
                        <span class="badge <?php echo e($statuses[$status]['badge']); ?>"><?php echo e($statuses[$status]['label']); ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted"><?php echo e($status ?: '—'); ?></span>
                    <?php endif; ?>
                </dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Foydalanuvchi</dt>
                <dd>
                    <?php if($courierOrder->user): ?>
                        <div class="font-medium"><?php echo e(trim(($courierOrder->user->first_name ?? $courierOrder->user->name ?? '') . ' ' . ($courierOrder->user->last_name ?? '')) ?: '—'); ?></div>
                        <?php if($courierOrder->user->phone_number ?? $courierOrder->user->phone): ?>
                            <div class="text-xs text-gray-500"><?php echo e($courierOrder->user->phone_number ?? $courierOrder->user->phone); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-gray-400">—</span>
                    <?php endif; ?>
                </dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sana</dt>
                <dd><?php echo e($courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : '—'); ?></dd>
            </div>

            <?php if($courierOrder->address ?? $courierOrder->delivery_address): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Manzil</dt>
                <dd><?php echo e($courierOrder->address ?? $courierOrder->delivery_address); ?></dd>
            </div>
            <?php endif; ?>

            <?php if($courierOrder->amount ?? $courierOrder->total): ?>
            <div>
                <dt class="text-xs text-gray-500 mb-1">Summa</dt>
                <dd class="font-bold text-base"><?php echo e(number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ')); ?> UZS</dd>
            </div>
            <?php endif; ?>

            <?php if($courierOrder->note ?? $courierOrder->comment): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Izoh</dt>
                <dd class="text-gray-600 dark:text-gray-300"><?php echo e($courierOrder->note ?? $courierOrder->comment); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/courier-orders/show.blade.php ENDPATH**/ ?>