@props([
  'label',
  'value',
  'meta' => null,
  'icon' => 'graph-up',
  'tone' => 'primary',
])

@php
  $toneMap = [
    'primary' => ['bg' => 'var(--kc-primary-soft)', 'color' => 'var(--kc-primary)'],
    'success' => ['bg' => 'rgba(25, 135, 84, 0.1)', 'color' => '#198754'],
    'info' => ['bg' => 'rgba(13, 110, 253, 0.1)', 'color' => '#0d6efd'],
    'warning' => ['bg' => 'rgba(255, 193, 7, 0.18)', 'color' => '#b58105'],
    'danger' => ['bg' => 'rgba(220, 53, 69, 0.12)', 'color' => '#dc3545'],
    'dark' => ['bg' => 'rgba(15, 23, 42, 0.08)', 'color' => '#0f172a'],
  ][$tone] ?? ['bg' => 'var(--kc-primary-soft)', 'color' => 'var(--kc-primary)'];
@endphp

<div {{ $attributes->merge(['class' => 'kc-stat-card']) }}>
  <div class="d-flex align-items-start justify-content-between gap-3">
    <div class="kc-stat-card__icon" style="background:{{ $toneMap['bg'] }};color:{{ $toneMap['color'] }}">
      <i class="bi bi-{{ $icon }}"></i>
    </div>
  </div>
  <div class="kc-stat-card__label">{{ $label }}</div>
  <div class="kc-stat-card__value kc-mono">{!! $value !!}</div>
  @if($meta)
    <div class="kc-stat-card__meta">{{ $meta }}</div>
  @endif
</div>
