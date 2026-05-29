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

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm rounded-5 h-100 overflow-hidden']) }} style="background:linear-gradient(180deg, rgba(255,255,255,0.99), rgba(248,250,253,0.98));">
  <div class="card-body p-4 p-xl-4">
    <div class="d-flex align-items-start justify-content-between gap-3">
      <div class="d-inline-flex align-items-center justify-content-center rounded-4 flex-shrink-0" style="width:3.15rem;height:3.15rem;background:{{ $toneMap['bg'] }};color:{{ $toneMap['color'] }}">
        <i class="bi bi-{{ $icon }}"></i>
      </div>
      <div class="small text-uppercase fw-bold text-secondary" style="letter-spacing:.14em;">Overview</div>
    </div>
    <div class="small text-secondary mt-4" style="letter-spacing:.02em;">{{ $label }}</div>
    <div class="h3 mb-0 mt-1 text-dark fw-bold font-monospace" style="letter-spacing:-0.03em;">{!! $value !!}</div>
    @if($meta)
      <div class="small text-secondary mt-2" style="line-height:1.7;">{{ $meta }}</div>
    @endif
  </div>
</div>
