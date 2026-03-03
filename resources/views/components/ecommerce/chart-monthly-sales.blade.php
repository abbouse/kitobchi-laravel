@props(['data'=>[]])
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Oylik daromad</h3>
        <x-common.dropdown-menu />
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="chartMonthlySales" class="-ml-5 h-[220px] min-w-[500px] pl-2 xl:min-w-full"></div>
    </div>
</div>
@once
@push('scripts')
<script>
(function(){
    var raw=@json($data);
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
@endpush
@endonce