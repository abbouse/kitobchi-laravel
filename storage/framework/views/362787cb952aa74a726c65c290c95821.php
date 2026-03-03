<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title'=>'','items'=>[],'colors'=>[]]));

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

foreach (array_filter((['title'=>'','items'=>[],'colors'=>[]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $chartId = 'donut_'.Str::slug($title).'_'.substr(md5($title),0,6);
?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="flex justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($title); ?></h3>
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
    <div id="<?php echo e($chartId); ?>" style="min-height:260px"></div>
</div>
<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    var items=<?php echo json_encode($items, 15, 512) ?>;
    var colors=<?php echo json_encode($colors, 15, 512) ?>;
    new ApexCharts(document.querySelector('#<?php echo e($chartId); ?>'),{
        series:items.map(i=>i.value),
        labels:items.map(i=>i.label),
        colors:colors.length?colors:['#6366F1','#8B5CF6','#EC4899','#14B8A6','#F59E0B'],
        chart:{type:'donut',height:260,toolbar:{show:false},fontFamily:'inherit'},
        legend:{position:'bottom',fontSize:'13px'},
        dataLabels:{enabled:false},
        plotOptions:{pie:{donut:{size:'65%'}}}
    }).render();
})();
</script>
<?php $__env->stopPush(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/chart-donut.blade.php ENDPATH**/ ?>