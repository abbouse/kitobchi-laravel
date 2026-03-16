@props(['sellers' => []])

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            🏆 Top Sotuvchilar
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Muvaffaqiyatli buyurtmalar bo'yicha</span>
    </div>

    @if(empty($sellers))
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Ma'lumot yo'q</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($sellers as $seller)
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold
                        {{ $loop->index === 0 ? 'bg-yellow-100 text-yellow-700' : ($loop->index === 1 ? 'bg-gray-200 text-gray-600' : ($loop->index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $seller['rank'] }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $seller['shop_name'] }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $seller['name'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ number_format($seller['successful_orders']) }} buyurtma
                        </p>
                        <p class="text-xs text-yellow-500">
                            ⭐ {{ $seller['rating'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
