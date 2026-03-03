@props(['label'=>'','value'=>0,'icon'=>'chart-bar','color'=>'gray','compact'=>false])
@php
    $bg   = ['brand'=>'bg-brand-50 dark:bg-brand-500/10','success'=>'bg-success-50 dark:bg-success-500/10','warning'=>'bg-warning-50 dark:bg-warning-500/10','info'=>'bg-blue-50 dark:bg-blue-500/10','gray'=>'bg-gray-100 dark:bg-gray-800'][$color] ?? 'bg-gray-100';
    $ic   = ['brand'=>'text-brand-500','success'=>'text-success-500','warning'=>'text-warning-500','info'=>'text-blue-500','gray'=>'text-gray-600 dark:text-gray-300'][$color] ?? 'text-gray-600';
@endphp
<div class="rounded-2xl border border-gray-200 bg-white {{ $compact ? 'p-4' : 'p-5 md:p-6' }} dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-center {{ $compact ? 'w-9 h-9' : 'w-12 h-12' }} {{ $bg }} rounded-xl">
        <x-ecommerce.icon name="{{ $icon }}" class="w-5 h-5 {{ $ic }}" />
    </div>
    <div class="{{ $compact ? 'mt-3' : 'mt-5' }}">
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <h4 class="{{ $compact ? 'mt-1 text-xl' : 'mt-2 text-2xl' }} font-bold text-gray-800 dark:text-white/90">{{ $value }}</h4>
    </div>
</div>