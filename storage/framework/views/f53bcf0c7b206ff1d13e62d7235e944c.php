<?php $__env->startSection('title', 'Sotuvchi buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Sotuvchi buyurtmalari'); ?>

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
        <div class="a122-index-header__title">Sotuvchi buyurtmalari</div>
        <div class="a122-index-header__meta"><?php echo e($orders->total()); ?> ta buyurtma topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'all')); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, sotuvchi yoki mijoz bo‘yicha qidiring">
        </form>
    </div>
</div>


<div class="flex items-center gap-2 mb-4 flex-wrap">
    <?php
        $tabs = ['all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0]];
        foreach ($statuses as $value => $statusItem) {
            $tabs[(string) $value] = ['label' => $statusItem['label'], 'count' => $counts[$value] ?? 0];
        }
    ?>
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a
            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key])); ?>"
            class="btn <?php echo e($tab == $key ? 'btn-primary' : 'btn-secondary'); ?> flex items-center gap-2 text-sm"
        >
            <?php echo e($tabItem['label']); ?>

            <span class="badge <?php echo e($tab == $key ? 'badge-info' : 'badge-muted'); ?>"><?php echo e($tabItem['count']); ?></span>
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
                    <th>Mijoz</th>
                    <th>Summa</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $customerName = trim(($order->client?->name ?? '') . ' ' . ($order->client?->lastname ?? ''));
                        $address = collect($order->order?->address ?? [])->first();
                        $fallbackName = $address['fullName'] ?? '—';
                        $customerPhone = $order->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
                        $itemsCount = collect($order->order?->items ?? [])->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)->sum(fn ($item) => (int) ($item['count_item'] ?? 1));
                        $orderAmount = (float) ($order->amount ?? 0);
                    ?>
                    <tr>
                        <td class="text-gray-500 text-sm">
                            <div>#<?php echo e($order->id); ?></div>
                            <div class="text-[11px] text-gray-400">ORD #<?php echo e($order->order_id); ?></div>
                        </td>
                        <td>
                            <div class="font-semibold"><?php echo e($order->seller->shop_name ?? '—'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e(trim(($order->seller->firstname ?? '') . ' ' . ($order->seller->lastname ?? '')) ?: 'Sotuvchi'); ?></div>
                        </td>
                        <td>
                            <div class="font-medium"><?php echo e($customerName ?: $fallbackName); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($customerPhone); ?></div>
                        </td>
                        <td class="font-semibold text-sm whitespace-nowrap">
                            <div><?php echo e(number_format($orderAmount, 0, '.', ' ')); ?> UZS</div>
                            <div class="text-xs text-gray-500"><?php echo e($itemsCount); ?> ta mahsulot</div>
                        </td>
                        <td>
                            <form method="POST" action="<?php echo e(route('admin.seller-orders.status', $order)); ?>" class="inline-flex">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>" <?php if((int) ($order->status ?? 0) === (int) $value): echo 'selected'; endif; ?>><?php echo e($statusItem['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </form>
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            <?php echo e($order->created_at ? $order->created_at->format('d.m.Y H:i') : '—'); ?>

                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <a href="<?php echo e(route('admin.seller-orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-8">Hech qanday buyurtma topilmadi</td>
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

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/seller-orders/index.blade.php ENDPATH**/ ?>