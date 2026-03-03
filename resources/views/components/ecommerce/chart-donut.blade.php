@props(['title'=>'','items'=>[],'colors'=>[]])
@php
    $chartId = 'donut_'.Str::slug($title).'_'.substr(md5($title),0,6);
@endphp
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="flex justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
        <x-common.dropdown-menu />
    </div>
    <div id="{{ $chartId }}" style="min-height:260px"></div>
</div>
@push('scripts')
<script>
(function(){
    var items=@json($items);
    var colors=@json($colors);
    new ApexCharts(document.querySelector('#{{ $chartId }}'),{
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
@endpush