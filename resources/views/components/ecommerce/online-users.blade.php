@props([
    'onlineCount' => 0,
    'dau'         => 0,
    'mau'         => 0,
    'users'       => [],
])

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <!-- Header -->
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            🟢 Faol Foydalanuvchilar
        </h3>
        <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
            Jonli
        </span>
    </div>

    <!-- Stats row -->
    <div class="mb-5 grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-800">
        <div class="pr-4 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $onlineCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Onlayn (5 min)</p>
        </div>
        <div class="px-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($dau) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Bugungi (DAU)</p>
        </div>
        <div class="pl-4 text-center">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($mau) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Oylik (MAU)</p>
        </div>
    </div>

    <!-- Recent online users list -->
    @if(empty($users))
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-3">Hozir onlayn foydalanuvchilar yo'q</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($users as $user)
                <div class="flex items-center gap-3 py-2">
                    @if($user['avatar'])
                        <img src="{{ $user['avatar'] }}" alt="{{ $user['name'] }}"
                             class="h-8 w-8 flex-shrink-0 rounded-full object-cover" />
                    @else
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-purple-500 text-sm font-bold text-white">
                            {{ strtoupper(substr($user['name'] ?? '?', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $user['name'] }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $user['phone'] }}</p>
                    </div>
                    <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                        {{ $user['last_seen_at'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
