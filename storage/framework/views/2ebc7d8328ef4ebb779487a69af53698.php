<?php $__env->startSection('title', 'Buyurtma #' . $sellerOrder->id); ?>
<?php $__env->startSection('page-title', 'Sotuvchi buyurtmasi'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.seller-orders.index')); ?>" class="btn btn-secondary flex items-center gap-2">
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

<?php
    $customerName = trim(($sellerOrder->client?->name ?? '') . ' ' . ($sellerOrder->client?->lastname ?? '')) ?: ($address['fullName'] ?? '—');
    $customerPhone = $sellerOrder->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
    $statusVal = $sellerOrder->status ?? 0;
?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    
    <div class="xl:col-span-2 card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-gray-400"></i>
            Buyurtma ma'lumotlari
        </h3>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 mb-1">Buyurtma ID</dt>
                <dd class="font-semibold">#<?php echo e($sellerOrder->id); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sana</dt>
                <dd><?php echo e($sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : '—'); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Asosiy buyurtma</dt>
                <dd class="font-semibold">#<?php echo e($sellerOrder->order_id); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sotuvchi</dt>
                <dd class="font-semibold"><?php echo e($sellerOrder->seller->shop_name ?? '—'); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Summa</dt>
                <dd class="font-bold text-lg"><?php echo e(number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ')); ?> UZS</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mijoz ismi</dt>
                <dd><?php echo e($customerName); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mijoz telefoni</dt>
                <dd><?php echo e($customerPhone); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Yetkazib berish turi</dt>
                <dd><?php echo e($summary['delivery_type']); ?></dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mahsulotlar soni</dt>
                <dd><?php echo e($summary['items_count']); ?> ta</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                <dd>
                    <?php if($statusVal == 0): ?>
                        <span class="badge badge-muted"><?php echo e($statuses[0]['label'] ?? "To'lov jarayonida"); ?></span>
                    <?php elseif($statusVal == 1): ?>
                        <span class="badge badge-info"><?php echo e($statuses[1]['label'] ?? 'Yangi'); ?></span>
                    <?php elseif($statusVal == 2): ?>
                        <span class="badge badge-warning"><?php echo e($statuses[2]['label'] ?? "Do'kon qabul qildi"); ?></span>
                    <?php elseif($statusVal == 3): ?>
                        <span class="badge badge-success"><?php echo e($statuses[3]['label'] ?? "Kuryerga berildi"); ?></span>
                    <?php elseif($statusVal == 4): ?>
                        <span class="badge badge-danger"><?php echo e($statuses[4]['label'] ?? 'Bekor qilindi'); ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted"><?php echo e($statusVal); ?></span>
                    <?php endif; ?>
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Manzil</dt>
                <dd><?php echo e($address['fullAddress'] ?? 'Manzil kiritilmagan'); ?></dd>
            </div>
        </dl>
    </div>

    
    <div class="card p-5 h-fit">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-5 h-5 text-gray-400"></i>
            Holatni o'zgartirish
        </h3>

        <?php if($errors->any()): ?>
            <div class="mb-3 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-3 py-2 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('admin.seller-orders.status', $sellerOrder)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div class="mb-4">
                <label class="text-xs text-gray-500 mb-1 block">Yangi holat</label>
                <select name="status" class="select">
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(($sellerOrder->status ?? '') == $value): echo 'selected'; endif; ?>>
                            <?php echo e($status['label']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </form>
    </div>

</div>

<div class="mt-6 card p-5">
    <h3 class="font-bold text-base mb-4 flex items-center gap-2">
        <i data-lucide="package-search" class="w-5 h-5 text-gray-400"></i>
        Sotuvchiga tegishli mahsulotlar
    </h3>

    <?php if($items->isEmpty()): ?>
        <div class="text-sm text-gray-500">Bu buyurtma uchun sotuvchiga tegishli mahsulotlar topilmadi.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Mahsulot</th>
                        <th>Turi</th>
                        <th>Miqdor</th>
                        <th>Narx</th>
                        <th>Jami</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <div class="font-semibold"><?php echo e($item['name']); ?></div>
                                <?php if(!empty($item['author'])): ?>
                                    <div class="text-xs text-gray-500"><?php echo e($item['author']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="capitalize"><?php echo e($item['type']); ?></td>
                            <td><?php echo e($item['quantity']); ?></td>
                            <td><?php echo e(number_format((float) $item['price'], 0, '.', ' ')); ?> UZS</td>
                            <td class="font-semibold"><?php echo e(number_format((float) $item['price'] * (int) $item['quantity'], 0, '.', ' ')); ?> UZS</td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/seller-orders/show.blade.php ENDPATH**/ ?>