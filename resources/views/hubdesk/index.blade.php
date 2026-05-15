@extends('hubdesk.layouts.hub')
@section('title', 'Hub Desk')

@php
  /**
   * Map a free-form status_code string ("packed", "qc_passed", "exception", …)
   * to a station pill class. Falls back to a neutral pill if no keyword matches.
   */
  $statusPill = function ($code) {
      $s = strtolower((string) $code);
      if (str_contains($s, 'except'))                            return ['hd-pill--exception', 'Exception'];
      if (str_contains($s, 'dispatch') || str_contains($s, 'sent') || str_contains($s, 'ship'))
                                                                  return ['hd-pill--dispatch', $code ?: 'Yuborildi'];
      if (str_contains($s, 'label')   || str_contains($s, 'pack'))
                                                                  return ['hd-pill--pack',     $code ?: 'Qadoqda'];
      if (str_contains($s, 'qc'))                                 return ['hd-pill--qc',       $code ?: 'QC'];
      if (str_contains($s, 'arrive')  || str_contains($s, 'in'))
                                                                  return ['hd-pill--inbound',  $code ?: 'Inbound'];
      return ['', $code ?: '—'];
  };

  $hasLabel   = in_array('print.label',   $permissions ?? [], true);
  $hasReceipt = in_array('print.receipt', $permissions ?? [], true);

  $readyLabel    = (int) ($counts['ready_to_label']    ?? 0);
  $readyDispatch = (int) ($counts['ready_to_dispatch'] ?? 0);
  $withException = (int) ($counts['with_exceptions']   ?? 0);
@endphp

@section('content')

<div class="hd-page-head">
  <div>
    <div class="hd-page-eyebrow">{{ $staff->hub?->name ?: 'Hub' }}</div>
    <h1 class="hd-page-title">Bugungi navbat</h1>
    <p class="hd-page-meta">Yetib kelgan, qadoqlanayotgan va yuborilishi kerak bo‘lgan buyurtmalar.</p>
  </div>
  <div class="hd-page-actions">
    <a href="{{ route('hubdesk.index') }}" class="hd-btn hd-btn--ghost" title="Yangilash">
      ↻ Yangilash
    </a>
  </div>
</div>

@if($withException > 0)
  <div class="hd-alert hd-alert--danger">
    <span aria-hidden="true">⚠</span>
    <div>
      <b>{{ number_format($withException) }} ta exception</b> hozir hal qilinishi kerak. Operatsiya muhandisini chaqiring yoki ro‘yxatdan ko‘ring.
    </div>
  </div>
@endif

{{-- KPI tiles ──────────────────────────────────────────────── --}}
<section class="hd-pipeline" aria-label="Hub holati">
  <div class="hd-tile hd-tile--pack">
    <div class="hd-tile-label">Label tayyor</div>
    <div class="hd-tile-value">{{ number_format($readyLabel) }}</div>
    <div class="hd-tile-hint">Chop etish va qadoqlash navbati</div>
  </div>
  <div class="hd-tile hd-tile--dispatch">
    <div class="hd-tile-label">Dispatch tayyor</div>
    <div class="hd-tile-value">{{ number_format($readyDispatch) }}</div>
    <div class="hd-tile-hint">Kuryerga topshirish navbati</div>
  </div>
  <div class="hd-tile hd-tile--exception">
    <div class="hd-tile-label">Exception</div>
    <div class="hd-tile-value">{{ number_format($withException) }}</div>
    <div class="hd-tile-hint">Muammoli buyurtmalar — ko‘rib chiqing</div>
  </div>
</section>

{{-- Search ──────────────────────────────────────────────────── --}}
<form method="GET" class="hd-search" role="search">
  <span class="hd-search-icon" aria-hidden="true">🔍</span>
  <input type="text" name="q" value="{{ request('q') }}"
         placeholder="Order ID, label, tracking, mijoz yoki telefon…"
         autocomplete="off" enterkeyhint="search"
         aria-label="Buyurtma qidirish">
  <button type="submit" class="hd-btn hd-btn--primary">Qidirish</button>
  @if(request('q'))
    <a href="{{ route('hubdesk.index') }}" class="hd-btn hd-btn--ghost" title="Tozalash">Tozalash</a>
  @endif
</form>

{{-- Fulfillment list ────────────────────────────────────────── --}}
<section class="hd-card hd-card--flush" aria-label="Buyurtmalar ro‘yxati">
  <div style="padding: 16px clamp(16px,2vw,22px); display:flex; align-items:center; gap:10px;">
    <div>
      <div class="hd-card-title">Buyurtmalar</div>
      <div class="hd-card-sub">
        @if(method_exists($fulfillments, 'total'))
          Jami {{ number_format($fulfillments->total()) }} ta — sahifa {{ $fulfillments->currentPage() }}/{{ max(1, $fulfillments->lastPage()) }}
        @else
          Ro‘yxat — {{ count($fulfillments) }} ta
        @endif
      </div>
    </div>
  </div>

  <div class="hd-list" style="padding: 0 clamp(12px,2vw,18px) clamp(12px,2vw,18px);">
    @forelse($fulfillments as $fulfillment)
      @php
        $order = $fulfillment->order;
        $customer = $order?->user?->full_name ?: 'Mijoz';
        [$pillClass, $pillText] = $statusPill($fulfillment->status_code);
        $detailHref = route('hubdesk.show', $fulfillment);
      @endphp
      <article class="hd-row">
        <div class="hd-row-id">
          <a href="{{ $detailHref }}" class="hd-order-id">#ORD-{{ $fulfillment->order_id }}</a>
          <span class="hd-row-hint">Hub: {{ $fulfillment->hub?->code ?: '—' }}</span>
        </div>

        <div class="hd-row-customer">
          <span class="hd-row-name">{{ $customer }}</span>
          <span class="hd-row-phone">{{ $order?->user?->phone_number ?: 'Telefon yo‘q' }}</span>
        </div>

        <div class="hd-row-status">
          <span class="hd-pill {{ $pillClass }}">{{ $pillText }}</span>
          @if($fulfillment->label_code)
            <span class="hd-pill hd-pill--mono" title="Label kodi">{{ $fulfillment->label_code }}</span>
          @endif
        </div>

        <div class="hd-row-actions">
          <a href="{{ $detailHref }}" class="hd-btn hd-btn--ghost hd-btn--sm">Ko‘rish</a>
          @if($hasLabel)
            <a href="{{ route('hubdesk.print.label', $fulfillment) }}" target="_blank" rel="noopener"
               class="hd-btn hd-btn--ink hd-btn--sm" title="Label chop etish">
              🖨 Label
            </a>
          @endif
          @if($hasReceipt)
            <a href="{{ route('hubdesk.print.receipt', $fulfillment) }}" target="_blank" rel="noopener"
               class="hd-btn hd-btn--ghost hd-btn--sm" title="Receipt chop etish">
              🧾 Receipt
            </a>
          @endif
        </div>
      </article>
    @empty
      <div class="hd-empty">
        <div class="hd-empty-glyph" aria-hidden="true">📦</div>
        <div>
          Hozircha buyurtma yo‘q. Yangi buyurtmalar kelganda shu yerda ko‘rinadi.
        </div>
      </div>
    @endforelse
  </div>

  @if(method_exists($fulfillments, 'links') && $fulfillments->hasPages())
    <div class="hd-pagination" style="padding: 14px clamp(16px,2vw,22px);">
      {{ $fulfillments->onEachSide(1)->links() }}
    </div>
  @endif
</section>

@endsection

@push('scripts')
<script>
  // Lightweight keyboard shortcuts for desk operators.
  // "/" focuses the search bar; "r" reloads the page.
  document.addEventListener('keydown', (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === '/') {
      const i = document.querySelector('.hd-search input');
      if (i) { e.preventDefault(); i.focus(); i.select(); }
    } else if (e.key.toLowerCase() === 'r' && !e.ctrlKey && !e.metaKey) {
      e.preventDefault();
      window.location.reload();
    }
  });
</script>
@endpush
