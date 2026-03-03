<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['data'=>[]]));

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

foreach (array_filter((['data'=>[]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Oylik daromad</h3>
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
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="chartMonthlySales" class="-ml-5 h-[220px] min-w-[500px] pl-2 xl:min-w-full"></div>
    </div>
</div>
<?php if (! $__env->hasRenderedOnce('962e96c8-f5cd-4d92-8c73-e48daf02443e')): $__env->markAsRenderedOnce('962e96c8-f5cd-4d92-8c73-e48daf02443e'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    var raw=<?php echo json_encode($data, 15, 512) ?>;
    new ApexCharts(document.querySelector('#chartMonthlySales'),{
        series:[{name:"Daromad",data:raw.map(d=>d.total)}],
        chart:{type:'bar',height:220,toolbar:{show:false},fontFamily:'inherit'},
        colors:['#465FFF'],
        plotOptions:{bar:{borderRadius:6,columnWidth:'50%'}},
        dataLabels:{enabled:false},
        xaxis:{categories:raw.map(d=>d.month),axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:'#9CA3AF',fontSize:'12px'}}},
        yaxis:{labels:{style:{colors:'#9CA3AF'},formatter:v=>v.toLocaleString()}},
        grid:{borderColor:'#E5E7EB',strokeDashArray:4},
        tooltip:{y:{formatter:v=>v.toLocaleString()+" so'm"}}
    }).render();
})();
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/chart-monthly-sales.blade.php ENDPATH**/ ?>