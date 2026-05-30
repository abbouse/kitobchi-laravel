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

<div class="card-panel d-flex align-items-center gap-3 px-3 py-3" style="min-width:180px;">
  <div class="d-inline-grid place-items-center rounded-3" style="width:36px;height:36px;background:color-mix(in srgb,var(--template-brand) 9%,var(--template-card));">
    <i data-lucide="{{ $icon }}" class="w-4 h-4 {{ $t }}"></i>
  </div>
  <div>
    <div class="small text-secondary fw-semibold">{{ $label }}</div>
    <div class="fw-bold">{{ $value }}</div>
  </div>
</div>
