@props([
    'totalPayout'    => 0,
    'commission'     => 0,
    'pendingCount'   => 0,
    'paidCount'      => 0,
    'rejectedCount'  => 0,
])

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            💸 Xarajatlar Tahlili
        </h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Sotuvchi tranzaksiyalari</span>
    </div>

    <!-- Revenue vs Commission -->
    <div class="mb-5 grid grid-cols-2 gap-4">
        <div class="rounded-xl bg-green-50 p-4 dark:bg-green-900/10">
            <p class="text-xs text-green-600 dark:text-green-400 mb-1">Jami to'lovlar</p>
            <p class="text-lg font-bold text-green-700 dark:text-green-300">
                {{ number_format($totalPayout, 0, '.', ' ') }} so'm
            </p>
        </div>
        <div class="rounded-xl bg-orange-50 p-4 dark:bg-orange-900/10">
            <p class="text-xs text-orange-600 dark:text-orange-400 mb-1">Komissiya</p>
            <p class="text-lg font-bold text-orange-700 dark:text-orange-300">
                {{ number_format($commission, 0, '.', ' ') }} so'm
            </p>
        </div>
    </div>

    <!-- Transaction status breakdown -->
    <div class="space-y-3">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Tranzaksiya holatlari</p>

        <div class="flex items-center gap-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20">⏳</span>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Kutilmoqda</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($pendingCount) }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/20">✅</span>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-300">To'landi</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($paidCount) }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-500 dark:bg-red-900/20">❌</span>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Rad etildi</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format($rejectedCount) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
