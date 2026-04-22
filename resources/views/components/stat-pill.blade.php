{{-- <x-stat-pill label="Bugungi daromad" value="$2,840" icon="dollar-sign" tone="emerald" /> --}}
@props(['label'=>'','value'=>'','icon'=>'activity','tone'=>'emerald'])

@php
  $tones = [
    'emerald' => 'text-emerald-600 dark:text-emerald-400',
    'sky'     => 'text-sky-600 dark:text-sky-400',
    'violet'  => 'text-violet-600 dark:text-violet-400',
    'amber'   => 'text-amber-600 dark:text-amber-400',
    'rose'    => 'text-rose-600 dark:text-rose-400',
  ];
  $t = $tones[$tone] ?? $tones['emerald'];
@endphp

<div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-[#0f1218] border border-gray-100 dark:border-white/5 shadow-soft min-w-[180px]">
  <div class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-white/5 flex items-center justify-center">
    <i data-lucide="{{ $icon }}" class="w-4 h-4 {{ $t }}"></i>
  </div>
  <div class="leading-tight">
    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="text-sm font-bold">{{ $value }}</div>
  </div>
</div>
