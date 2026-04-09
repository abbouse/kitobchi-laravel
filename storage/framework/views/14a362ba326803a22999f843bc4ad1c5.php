<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['promos' => []]));

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

foreach (array_filter((['promos' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
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
            🎟️ Promo-kod Tahlili
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Top 10 faol kodlar</span>
    </div>

    <?php if(empty($promos)): ?>
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Faol promo-kodlar yo'q</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
                        <th class="pb-2 text-xs font-medium text-gray-400 dark:text-gray-500">Kod</th>
                        <th class="pb-2 text-xs font-medium text-gray-400 dark:text-gray-500 text-right">Chegirma</th>
                        <th class="pb-2 text-xs font-medium text-gray-400 dark:text-gray-500 text-right">Foydalanish</th>
                        <th class="pb-2 text-xs font-medium text-gray-400 dark:text-gray-500 text-right">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    <?php $__currentLoopData = $promos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="py-2 pr-3">
                                <span class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    <?php echo e($promo['code']); ?>

                                </span>
                            </td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">
                                <?php echo e($promo['amount']); ?>

                            </td>
                            <td class="py-2 text-right font-medium text-gray-800 dark:text-white/90">
                                <?php echo e(number_format($promo['used_count'])); ?>

                            </td>
                            <td class="py-2 text-right">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    <?php echo e($promo['fill_rate'] > 10 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'); ?>">
                                    <?php echo e($promo['fill_rate']); ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/promo-analytics.blade.php ENDPATH**/ ?>