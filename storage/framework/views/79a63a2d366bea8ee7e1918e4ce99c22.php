<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['sellers' => []]));

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

foreach (array_filter((['sellers' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            🏆 Top Sotuvchilar
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Muvaffaqiyatli buyurtmalar bo'yicha</span>
    </div>

    <?php if(empty($sellers)): ?>
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Ma'lumot yo'q</p>
    <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__currentLoopData = $sellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold
                        <?php echo e($loop->index === 0 ? 'bg-yellow-100 text-yellow-700' : ($loop->index === 1 ? 'bg-gray-200 text-gray-600' : ($loop->index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'))); ?>">
                        <?php echo e($seller['rating']); ?>

                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            <?php echo e($seller['shop_name']); ?>

                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            <?php echo e($seller['name']); ?>

                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                            <?php echo e(number_format($seller['successful_orders'])); ?> buyurtma
                        </p>
                        <p class="text-xs text-yellow-500">
                            ⭐ {
                        </p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/top-sellers.blade.php ENDPATH**/ ?>