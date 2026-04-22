@php
  $label = trim((string) ($name ?? '')) ?: 'A';
  $tokens = preg_split('/\s+/u', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $first = $tokens[0] ?? $label;
  $last = count($tokens) > 1 ? end($tokens) : null;
  $initials = mb_strtoupper(mb_substr($first, 0, 1) . ($last ? mb_substr((string) $last, 0, 1) : ''), 'UTF-8');

  $seed = abs(crc32($label . '|' . (string) ($image ?? '')));
  $palettes = [
    ['bg' => 'linear-gradient(135deg, #111827 0%, #374151 100%)', 'fg' => '#f9fafb'],
    ['bg' => 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)', 'fg' => '#f0fdfa'],
    ['bg' => 'linear-gradient(135deg, #1d4ed8 0%, #60a5fa 100%)', 'fg' => '#eff6ff'],
    ['bg' => 'linear-gradient(135deg, #7c3aed 0%, #c084fc 100%)', 'fg' => '#faf5ff'],
    ['bg' => 'linear-gradient(135deg, #be123c 0%, #fb7185 100%)', 'fg' => '#fff1f2'],
    ['bg' => 'linear-gradient(135deg, #c2410c 0%, #fb923c 100%)', 'fg' => '#fff7ed'],
    ['bg' => 'linear-gradient(135deg, #166534 0%, #4ade80 100%)', 'fg' => '#f0fdf4'],
    ['bg' => 'linear-gradient(135deg, #334155 0%, #94a3b8 100%)', 'fg' => '#f8fafc'],
  ];
  $palette = $palettes[$seed % count($palettes)];

  $imageValue = trim((string) ($image ?? ''));
  $imageUrl = null;
  if ($imageValue !== '') {
      $imageUrl = str_starts_with($imageValue, 'http://')
          || str_starts_with($imageValue, 'https://')
          || str_starts_with($imageValue, 'data:')
          || str_starts_with($imageValue, '/storage/')
          || str_starts_with($imageValue, '/')
          ? $imageValue
          : asset('storage/' . ltrim($imageValue, '/'));
  }

  $avatarClass = trim('a122-avatar ' . ($class ?? ''));
@endphp

<span
  class="{{ $avatarClass }}"
  style="--a122-avatar-bg: {{ $palette['bg'] }}; --a122-avatar-fg: {{ $palette['fg'] }};"
  aria-label="{{ $label }}"
>
  <span class="a122-avatar__fallback">{{ $initials }}</span>
  @if($imageUrl)
    <img
      src="{{ $imageUrl }}"
      alt="{{ $label }}"
      loading="lazy"
      onerror="this.classList.add('is-broken');"
    >
  @endif
</span>
