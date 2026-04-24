<?php $__env->startSection('title', $seller->shop_name); ?>
<?php $__env->startSection('page-title', 'Sotuvchi profili'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.sellers.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <?php if($seller->status === 'blocked'): ?>
            <form method="POST" action="<?php echo e(route('admin.sellers.unblock', $seller)); ?>" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <button type="submit" class="btn btn-success flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('admin.sellers.reset-password', $seller)); ?>" onsubmit="return confirm('Yangi parol sotuvchining telefon raqamiga SMS orqali yuborilsinmi?')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-warning flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
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
                <?php elseif($seller->status === 'blocked'): ?>
                    <span class="badge badge-danger">Bloklangan</span>
                <?php else: ?>
                    <span class="badge badge-muted"><?php echo e($seller->status); ?></span>
                <?php endif; ?>
                <span class="badge <?php echo e($warningCount >= 3 ? 'badge-danger' : ($warningCount > 0 ? 'badge-warning' : 'badge-muted')); ?>">
                    <?php echo e($warningCount); ?>/3 ogohlantirish
                </span>
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

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <h3 class="font-bold text-base">Ogohlantirish yuborish</h3>
                <p class="text-sm text-gray-500 mt-1">3 ta faol ogohlantirishdan keyin sotuvchi avtomatik bloklanadi.</p>
            </div>
            <?php if($isBlocked): ?>
                <span class="badge badge-danger">Seller hozir bloklangan</span>
            <?php endif; ?>
        </div>
        <form method="POST" action="<?php echo e(route('admin.sellers.warn', $seller)); ?>" class="grid grid-cols-1 gap-3">
            <?php echo csrf_field(); ?>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Sarlavha</label>
                <input name="title" class="input" maxlength="120" placeholder="Masalan: Qoida buzilishi" value="<?php echo e(old('title')); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Xabar</label>
                <textarea name="message" rows="4" class="input" placeholder="Sellerga ko‘rinadigan ogohlantirish matni"><?php echo e(old('message')); ?></textarea>
            </div>
            <div class="flex items-center justify-end">
                <button type="submit" class="btn btn-warning flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i> Ogohlantirish yuborish
                </button>
            </div>
        </form>
    </div>

    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">Bloklash holati</h3>
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Do‘kon statusi</span>
                <span class="badge <?php echo e($isBlocked ? 'badge-danger' : 'badge-success'); ?>">
                    <?php echo e($isBlocked ? 'Bloklangan' : 'Faol'); ?>

                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Faol ogohlantirishlar</span>
                <span class="font-semibold"><?php echo e($warningCount); ?>/3</span>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-white/5 p-4 text-xs text-gray-500">
                Blokdan chiqarilganda ogohlantirish hisobi qayta boshlanadi. Eski ogohlantirishlar audit uchun tarixda saqlanadi.
            </div>
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

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Seller loglari</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $staffLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                <?php echo e($log->created_at ? $log->created_at->format('d.m.Y H:i') : '—'); ?>

                            </td>
                            <td><?php echo e($log->text ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="2" class="text-center text-gray-400 py-6">Loglar topilmadi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Ogohlantirish va unblock tarixi</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Turi</th>
                        <th>Sarlavha</th>
                        <th>Xabar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $banLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banLog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                <?php echo e($banLog->created_at ? $banLog->created_at->format('d.m.Y H:i') : '—'); ?>

                            </td>
                            <td>
                                <span class="badge <?php echo e($banLog->type === 'warning' ? 'badge-warning' : 'badge-success'); ?>">
                                    <?php echo e($banLog->type === 'warning' ? 'Ogohlantirish' : 'Unblock'); ?>

                                </span>
                            </td>
                            <td><?php echo e($banLog->title ?? '—'); ?></td>
                            <td class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($banLog->message ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-6">Ogohlantirishlar tarixi topilmadi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/sellers/show.blade.php ENDPATH**/ ?>