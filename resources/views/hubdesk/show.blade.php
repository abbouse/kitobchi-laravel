@extends('hubdesk.layouts.hub')
@section('title', 'Buyurtma #ORD-' . $fulfillment->order_id)

@php
  $order      = $fulfillment->order ?? null;
  $customer   = $order?->user?->full_name ?: 'Mijoz';
  $phone      = $order?->user?->phone_number ?: null;
  $exception  = data_get($fulfillment->meta, 'exception');
  $hasLabel   = in_array('print.label',   $permissions ?? [], true);
  $hasReceipt = in_array('print.receipt', $permissions ?? [], true);

  // ── Status pipeline ───────────────────────────────────────────────────
  // Best-effort mapping from a free-form status_code to a step index.
  $status = strtolower((string) $fulfillment->status_code);
  $stepIndex = 0; // 0..4 for inbound/qc/pack/dispatch + 4 for shipped
  if     (str_contains($status, 'dispatch') || str_contains($status, 'sent') || str_contains($status, 'ship'))  $stepIndex = 4;
  elseif (str_contains($status, 'label')    || str_contains($status, 'pack'))                                  $stepIndex = 3;
  elseif (str_contains($status, 'qc'))                                                                          $stepIndex = 2;
  elseif (str_contains($status, 'arrive')   || str_contains($status, 'in'))                                     $stepIndex = 1;

  $blocked = !empty($exception) && empty(data_get($exception, 'resolved'));

  $steps = [
    ['Inbound',  'Qabul'],
    ['QC',       'Sifat tekshiruvi'],
    ['Packing',  'Qadoqlash'],
    ['Label',    'Label chop etish'],
    ['Dispatch', 'Yuborildi'],
  ];

  // ── Map status_code to a top-level pill ───────────────────────────────
  $pillClass = 'hd-pill--inbound';
  if     ($stepIndex >= 4) $pillClass = 'hd-pill--dispatch';
  elseif ($stepIndex >= 3) $pillClass = 'hd-pill--pack';
  elseif ($stepIndex >= 2) $pillClass = 'hd-pill--qc';
  if ($blocked)            $pillClass = 'hd-pill--exception';
@endphp

@section('content')

<div class="hd-page-head">
  <div>
    <a href="{{ route('hubdesk.index') }}" class="hd-btn hd-btn--ghost hd-btn--sm" style="margin-bottom:10px;">← Orqaga</a>
    <h1 class="hd-page-title">
      <span class="hd-mono">#ORD-{{ $fulfillment->order_id }}</span>
    </h1>
    <p class="hd-page-meta">
      <span class="hd-strong">{{ $fulfillment->hub?->name ?: 'Hub' }}</span>
      <span class="hd-muted">·</span>
      <span class="hd-pill {{ $pillClass }}">{{ $fulfillment->status_code ?: '—' }}</span>
    </p>
  </div>

  <div class="hd-page-actions">
    @if($hasLabel)
      <a href="{{ route('hubdesk.print.label', $fulfillment) }}" target="_blank" rel="noopener"
         class="hd-btn hd-btn--primary hd-btn--xl">🖨 Label chop etish</a>
    @endif
    @if($hasReceipt)
      <a href="{{ route('hubdesk.print.receipt', $fulfillment) }}" target="_blank" rel="noopener"
         class="hd-btn hd-btn--ink hd-btn--xl">🧾 Receipt chop etish</a>
    @endif
  </div>
</div>

{{-- Status pipeline ─────────────────────────────────────────── --}}
<section class="hd-card" aria-label="Holat zinapoyasi">
  <div class="hd-card-head">
    <div>
      <div class="hd-card-title">Buyurtma holati</div>
      <div class="hd-card-sub">Hozirgi bosqich pushti rangda ko‘rinadi.</div>
    </div>
  </div>
  <div class="hd-pipe" role="list">
    @foreach($steps as $i => [$label, $hint])
      @php
        $state = '';
        if ($blocked && $i === $stepIndex) $state = 'is-blocked';
        elseif ($i <  $stepIndex)          $state = 'is-done';
        elseif ($i == $stepIndex)          $state = 'is-current';
      @endphp
      <div class="hd-pipe-step {{ $state }}" role="listitem" title="{{ $hint }}">
        <span class="hd-pipe-step-num">{{ $i + 1 }}</span>
        <span>{{ $label }}</span>
      </div>
    @endforeach
  </div>
</section>

{{-- Exception banner ────────────────────────────────────────── --}}
@if(!empty($exception))
  @php
    $resolved = (bool) data_get($exception, 'resolved');
    $alertCls = $resolved ? 'hd-alert--success' : 'hd-alert--danger';
  @endphp
  <div class="hd-alert {{ $alertCls }}">
    <span aria-hidden="true">{{ $resolved ? '✓' : '⚠' }}</span>
    <div>
      <b>{{ $resolved ? 'Yopilgan exception' : 'Faol exception' }}: {{ data_get($exception, 'code', 'other') }}</b>
      <div style="margin-top:4px;">{{ data_get($exception, 'note') ?: 'Izoh yo‘q.' }}</div>
      @if($resolved && data_get($exception, 'resolved_note'))
        <div style="margin-top:4px;font-size:13px;opacity:.85;">{{ data_get($exception, 'resolved_note') }}</div>
      @endif
    </div>
  </div>
@endif

{{-- Key data ───────────────────────────────────────────────── --}}
<section class="hd-card" aria-label="Asosiy ma‘lumotlar">
  <div class="hd-card-head">
    <div>
      <div class="hd-card-title">Yetkazib berish ma‘lumotlari</div>
      <div class="hd-card-sub">Chop etishdan oldin tekshirib chiqing.</div>
    </div>
  </div>

  <div class="hd-kv-grid">
    <div class="hd-kv">
      <div class="hd-kv-label">Label / Tracking</div>
      <div class="hd-kv-value">{{ $fulfillment->label_code ?: '—' }}</div>
      <div class="hd-kv-hint">{{ $fulfillment->postal_tracking_number ?: 'Tracking raqami hali yo‘q' }}</div>
    </div>

    <div class="hd-kv">
      <div class="hd-kv-label">Mijoz</div>
      <div class="hd-kv-value" style="font-family:'Outfit','Inter',sans-serif;font-size:16px;">{{ $customer }}</div>
      <div class="hd-kv-hint">{{ $phone ?: 'Telefon yo‘q' }}</div>
    </div>

    <div class="hd-kv {{ $fulfillment->is_cod ? 'hd-kv--cod' : '' }}">
      <div class="hd-kv-label">{{ $fulfillment->is_cod ? 'COD — naqd yig‘iladi' : 'COD' }}</div>
      <div class="hd-kv-value">
        @if($fulfillment->is_cod)
          {{ number_format((int) $fulfillment->cash_collect_amount, 0, '.', ' ') }} UZS
        @else
          Yo‘q
        @endif
      </div>
      <div class="hd-kv-hint">{{ $fulfillment->is_cod ? 'Mijozdan to‘lov olishni unutmang' : 'Old to‘langan' }}</div>
    </div>

    <div class="hd-kv">
      <div class="hd-kv-label">Hub kodi</div>
      <div class="hd-kv-value">{{ $fulfillment->hub?->code ?: '—' }}</div>
      <div class="hd-kv-hint">{{ $fulfillment->hub?->name ?: 'Hub belgilanmagan' }}</div>
    </div>
  </div>
</section>

{{-- Help footnote ───────────────────────────────────────────── --}}
<div class="hd-card" style="background: transparent; box-shadow: none; border: 0;">
  <div class="hd-muted" style="font-size:13px;">
    <b class="hd-strong">Eslatma:</b> chop etish yangi oynada ochiladi. Agar printer ishlamasa,
    <span class="hd-strong">brauzerni qayta yuklash</span> kifoya bo‘lishi mumkin —
    chop etishdan oldin printer USB‑kabel ulanganligini tekshiring.
  </div>
</div>

@endsection
