@props(['label'=>'','value'=>'','icon'=>'star'])
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] flex items-center gap-4">
    <div class="flex items-center justify-center w-12 h-12 bg-brand-50 rounded-xl dark:bg-brand-500/10 shrink-0">
        <x-ecommerce.icon name="{{ $icon }}" class="w-6 h-6 text-brand-500" />
    </div>
    <div class="min-w-0">
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <p class="mt-1 font-semibold text-gray-800 dark:text-white/90 truncate">{{ $value }}</p>
    </div>
</div>