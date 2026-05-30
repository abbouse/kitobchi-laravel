{{-- ================================================================
    <x-kpi-card title="..." value="..." change="+12%" trend="up" icon="dollar-sign" color="emerald" />
    ================================================================ --}}
@props([
  'title'  => '',
  'value'  => '',
  'change' => null,
  'trend'  => 'up',         // up|down|flat
  'icon'   => 'activity',
  'color'  => 'emerald',    // emerald|sky|violet|amber|rose|slate
])

@php
  $colorMap = [
    'emerald' => ['bg'=>'bg-emerald-50 dark:bg-emerald-500/10','txt'=>'text-emerald-600 dark:text-emerald-400'],
    'sky'     => ['bg'=>'bg-sky-50 dark:bg-sky-500/10',         'txt'=>'text-sky-600 dark:text-sky-400'],
    'violet'  => ['bg'=>'bg-violet-50 dark:bg-violet-500/10',   'txt'=>'text-violet-600 dark:text-violet-400'],
    'amber'   => ['bg'=>'bg-amber-50 dark:bg-amber-500/10',     'txt'=>'text-amber-600 dark:text-amber-400'],
    'rose'    => ['bg'=>'bg-rose-50 dark:bg-rose-500/10',       'txt'=>'text-rose-600 dark:text-rose-400'],
    'slate'   => ['bg'=>'bg-slate-100 dark:bg-white/5',         'txt'=>'text-slate-600 dark:text-slate-300'],
  ];
  $c = $colorMap[$color] ?? $colorMap['emerald'];
  $trendCls = $trend === 'up'   ? 'text-emerald-600 dark:text-emerald-400'
            : ($trend === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500');
  $trendIcon = $trend === 'up'  ? 'trending-up'
             : ($trend === 'down' ? 'trending-down' : 'minus');
@endphp

<div class="card-panel p-4 h-100">
  <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
    <div class="small text-secondary fw-semibold">{{ $title }}</div>
    <div class="d-inline-grid place-items-center rounded-3 {{ $c['bg'] }}" style="width:38px;height:38px;">
      <i data-lucide="{{ $icon }}" class="w-4 h-4 {{ $c['txt'] }}"></i>
    </div>
  </div>
  <div class="h3 fw-bold mb-0">{{ $value }}</div>
  @if($change !== null)
    <div class="mt-2 d-flex align-items-center gap-1 small fw-semibold {{ $trendCls }}">
      <i data-lucide="{{ $trendIcon }}" class="w-3.5 h-3.5"></i>
      <span>{{ $change }}</span>
      <span class="text-secondary fw-normal">o'tgan davrga nisbatan</span>
    </div>
  @endif
</div>
