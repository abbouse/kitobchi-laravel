@props(['orders'=>[]])
@php
$colors=['success'=>'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
         'warning'=>'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
         'info'   =>'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
         'gray'   =>'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400'];
@endphp
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">So'nggi buyurtmalar</h3>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                Barchasi
            </a>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">#ID</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Mijoz</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Summa</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Sana</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <td class="py-3 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90 flex items-center gap-1">
                            #{{ $order['id'] }}
                            @if($order['gift'])<svg class="w-4 h-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>@endif
                        </span>
                    </td>
                    <td class="py-3 whitespace-nowrap"><p class="text-sm text-gray-800 dark:text-white/90">{{ $order['customer'] }}</p></td>
                    <td class="py-3 whitespace-nowrap"><p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $order['amount'] }}</p></td>
                    <td class="py-3 whitespace-nowrap">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $colors[$order['color']] ?? $colors['gray'] }}">{{ $order['status'] }}</span>
                    </td>
                    <td class="py-3 whitespace-nowrap"><p class="text-xs text-gray-500 dark:text-gray-400">{{ $order['date'] }}</p></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>