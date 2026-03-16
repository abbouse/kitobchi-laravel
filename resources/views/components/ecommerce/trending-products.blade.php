@props(['books' => []])

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            🔥 Trendagi Kitoblar
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Haftalik sotuvlar bo'yicha</span>
    </div>

    @if(empty($books))
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Ma'lumot yo'q</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($books as $book)
                <div class="flex items-center gap-3 py-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ $book['rank'] }}
                    </span>
                    @if($book['cover'])
                        <img src="{{ $book['cover'] }}" alt="{{ $book['name'] }}"
                             class="h-10 w-7 flex-shrink-0 rounded object-cover shadow-sm" />
                    @else
                        <div class="flex h-10 w-7 flex-shrink-0 items-center justify-center rounded bg-gray-100 text-lg dark:bg-gray-800">📚</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $book['name'] }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $book['author'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                            +{{ $book['weekly_sales'] }}
                        </p>
                        <p class="text-xs text-gray-400">bu hafta</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
