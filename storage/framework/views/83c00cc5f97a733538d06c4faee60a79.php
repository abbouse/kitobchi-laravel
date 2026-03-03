<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['trend'=>[]]));

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

foreach (array_filter((['trend'=>[]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Sotuvlar trendi</h3>
            <p class="mt-1 text-gray-500 text-sm dark:text-gray-400">So'nggi 7 kunlik buyurtmalar</p>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="chartStatistics" class="-ml-4 min-w-[700px] pl-2 xl:min-w-full" style="height:280px"></div>
    </div>
</div>
<?php if (! $__env->hasRenderedOnce('a2fe27b2-cf95-4114-9377-31b3f2109dfc')): $__env->markAsRenderedOnce('a2fe27b2-cf95-4114-9377-31b3f2109dfc'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    var raw=<?php echo json_encode($trend, 15, 512) ?>;
    new ApexCharts(document.querySelector('#chartStatistics'),{
        series:[{name:'Buyurtmalar',data:raw.map(d=>d.count)}],
        chart:{type:'area',height:280,toolbar:{show:false},fontFamily:'inherit'},
        colors:['#465FFF'],
        fill:{type:'gradient',gradient:{opacityFrom:0.4,opacityTo:0.05}},
        stroke:{curve:'smooth',width:2},
        dataLabels:{enabled:false},
        xaxis:{categories:raw.map(d=>d.date),axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:'#9CA3AF',fontSize:'12px'}}},
        yaxis:{labels:{style:{colors:'#9CA3AF'}}},
        grid:{borderColor:'#E5E7EB',strokeDashArray:4},
        markers:{size:4,strokeColors:'#fff',strokeWidth:2}
    }).render();
})();
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/chart-statistics.blade.php ENDPATH**/ ?>