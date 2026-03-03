@props(['totalRevenue'=>0,'booksRevenue'=>0,'soldCount'=>0,'soldStatusC'=>0])
@php $rate = $soldCount > 0 ? round(($soldStatusC/$soldCount)*100) : 0; @endphp
<div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="rounded-2xl bg-white px-5 pb-8 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
        <div class="flex justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Buyurtmalar holati</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bajarilish darajasi</p>
            </div>
            <x-common.dropdown-menu />
        </div>
        <div class="relative mt-4" style="height:180px">
            <div id="chartTarget" class="h-full"></div>
            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500 pointer-events-none">
                {{ $rate }}%
            </span>
        </div>
        <p class="mx-auto mt-2 max-w-xs text-center text-sm text-gray-500">
            {{ number_format($soldCount) }} ta buyurtmadan {{ number_format($soldStatusC) }} tasi yakunlangan.
        </p>
    </div>
    <div class="flex items-center justify-center gap-5 px-6 py-4 sm:gap-8">
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Umumiy daromad</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($totalRevenue,0,'.',' ') }} so'm</p>
        </div>
        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Kitoblar</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($booksRevenue,0,'.',' ') }} so'm</p>
        </div>
        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div class="text-center">
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Yakunlangan</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($soldStatusC) }}</p>
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
(function(){
    new ApexCharts(document.querySelector('#chartTarget'),{
        series:[{{ $soldStatusC }},{{ max(0, $soldCount - $soldStatusC) }}],
        labels:['Yakunlangan','Jarayonda'],
        chart:{type:'donut',height:180,toolbar:{show:false}},
        colors:['#10B981','#F59E0B'],
        legend:{show:false},
        dataLabels:{enabled:false},
        plotOptions:{pie:{donut:{size:'70%'}}}
    }).render();
})();
</script>
@endpush
@endonce