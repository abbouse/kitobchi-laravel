<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['totalRevenue'=>0,'booksRevenue'=>0,'soldCount'=>0,'soldStatusC'=>0]));

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

foreach (array_filter((['totalRevenue'=>0,'booksRevenue'=>0,'soldCount'=>0,'soldStatusC'=>0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php $rate = $soldCount > 0 ? round(($soldStatusC/$soldCount)*100) : 0; ?>
<div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="rounded-2xl bg-white px-5 pb-8 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
        <div class="flex justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Buyurtmalar holati</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bajarilish darajasi</p>
            </div>
            <?php if (isset($component)) { $__componentOriginala50c193cb6f2974616f14721445453d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala50c193cb6f2974616f14721445453d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.dropdown-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.dropdown-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala50c193cb6f2974616f14721445453d4)): ?>
<?php $attributes = $__attributesOriginala50c193cb6f2974616f14721445453d4; ?>
<?php unset($__attributesOriginala50c193cb6f2974616f14721445453d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala50c193cb6f2974616f14721445453d4)): ?>
<?php $component = $__componentOriginala50c193cb6f2974616f14721445453d4; ?>
<?php unset($__componentOriginala50c193cb6f2974616f14721445453d4); ?>
<?php endif; ?>
        </div>
        <div class="relative mt-4" style="height:180px">
            <div id="chartTarget" class="h-full"></div>
            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500 pointer-events-none">
                <?php echo e($rate); ?>%
            </span>
        </div>
        <p class="mx-auto mt-2 max-w-xs text-center text-sm text-gray-500">
            <?php echo e(number_format($soldCount)); ?> ta buyurtmadan <?php echo e(number_format($soldStatusC)); ?> tasi yakunlangan.
        </p>
    </div>
    <div class="flex items-center justify-center gap-5 px-6 py-4 sm:gap-8">
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Umumiy daromad</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($totalRevenue,0,'.',' ')); ?> so'm</p>
        </div>
        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Kitoblar</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($booksRevenue,0,'.',' ')); ?> so'm</p>
        </div>
        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Yakunlangan</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($soldStatusC)); ?></p>
        </div>
    </div>
</div>
<?php if (! $__env->hasRenderedOnce('5c2e9219-3877-4b92-90c4-6d12325c90d7')): $__env->markAsRenderedOnce('5c2e9219-3877-4b92-90c4-6d12325c90d7'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    new ApexCharts(document.querySelector('#chartTarget'),{
        series:[<?php echo e($soldStatusC); ?>,<?php echo e(max(0, $soldCount - $soldStatusC)); ?>],
        labels:['Yakunlangan','Jarayonda'],
        chart:{type:'donut',height:180,toolbar:{show:false}},
        colors:['#10B981','#F59E0B'],
        legend:{show:false},
        dataLabels:{enabled:false},
        plotOptions:{pie:{donut:{size:'70%'}}}
    }).render();
})();
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/monthly-target.blade.php ENDPATH**/ ?>