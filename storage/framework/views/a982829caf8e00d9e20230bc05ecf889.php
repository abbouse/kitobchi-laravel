<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['books' => []]));

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

foreach (array_filter((['books' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
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
            🔥 Trendagi Kitoblar
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Haftalik sotuvlar bo'yicha</span>
    </div>

    <?php if(empty($books)): ?>
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Ma'lumot yo'q</p>
    <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <?php echo e($book['rank']); ?>

                    </span>
                    <?php if($book['cover']): ?>
                        <img src="<?php echo e($book['cover']); ?>" alt="<?php echo e($book['name']); ?>"
                             class="h-10 w-7 flex-shrink-0 rounded object-cover shadow-sm" />
                    <?php else: ?>
                        <div class="flex h-10 w-7 flex-shrink-0 items-center justify-center rounded bg-gray-100 text-lg dark:bg-gray-800">📚</div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            <?php echo e($book['name']); ?>

                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            <?php echo e($book['author']); ?>

                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                            +<?php echo e($book['weekly_sales']); ?>

                        </p>
                        <p class="text-xs text-gray-400">bu hafta</p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/trending-products.blade.php ENDPATH**/ ?>