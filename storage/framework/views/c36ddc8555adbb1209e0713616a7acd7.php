<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['orders'=>[]]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['orders'=>[]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
$colors=['success'=>'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
         'warning'=>'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
         'info'   =>'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
         'gray'   =>'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400'];
?>
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">So'nggi buyurtmalar</h3>
        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('dashboard')); ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                Barchasi
            </a>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">#ID</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Mijoz</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Summa</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <td class="py-3 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90 flex items-center gap-1">
                            #<?php echo e($order['id']); ?>

                            <?php if($order['gift']): ?><svg class="w-4 h-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg><?php endif; ?>
                        </span>
                    </td>
                    <td class="py-3 whitespace-nowrap"><p class="text-sm text-gray-800 dark:text-white/90"><?php echo e($order['customer']); ?></p></td>
                    <td class="py-3 whitespace-nowrap"><p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo e($order['amount']); ?></p></td>
                    <td class="py-3 whitespace-nowrap">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium <?php echo e($colors[$order['color']] ?? $colors['gray']); ?>"><?php echo e($order['status']); ?></span>
                    </td>
                    <td class="py-3 whitespace-nowrap"><p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($order['date']); ?></p></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/recent-orders.blade.php ENDPATH**/ ?>