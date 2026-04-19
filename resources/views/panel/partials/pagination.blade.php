@if($paginator->hasPages())
<div class="flex items-center justify-between flex-wrap gap-2"
     style="padding: 14px 20px; border-top: 1px solid var(--p-border)">

  {{-- Info --}}
  <div style="font-size:12px;color:var(--p-hint)">
    <span style="color:var(--p-text);font-weight:500">{{ number_format($paginator->firstItem()) }}</span>
    –
    <span style="color:var(--p-text);font-weight:500">{{ number_format($paginator->lastItem()) }}</span>
    /
    {{ number_format($paginator->total()) }} ta natija
  </div>

  {{-- Sahifalar --}}
  <div class="flex items-center gap-1">

    {{-- Oldingi --}}
    @if($paginator->onFirstPage())
      <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" class="p-page-btn">
        <i class="bi bi-chevron-left"></i>
      </a>
    @endif

    {{-- Sahifa raqamlari --}}
    @php
      $current  = $paginator->currentPage();
      $last     = $paginator->lastPage();
      $from     = max(1, $current - 2);
      $to       = min($last, $current + 2);
    @endphp

    @if($from > 1)
      <a href="{{ $paginator->url(1) }}" class="p-page-btn">1</a>
      @if($from > 2)
        <span class="p-page-btn disabled" style="cursor:default">…</span>
      @endif
    @endif

    @foreach(range($from, $to) as $page)
      @if($page == $current)
        <span class="p-page-btn active">{{ $page }}</span>
      @else
        <a href="{{ $paginator->url($page) }}" class="p-page-btn">{{ $page }}</a>
      @endif
    @endforeach

    @if($to < $last)
      @if($to < $last - 1)
        <span class="p-page-btn disabled" style="cursor:default">…</span>
      @endif
      <a href="{{ $paginator->url($last) }}" class="p-page-btn">{{ $last }}</a>
    @endif

    {{-- Keyingi --}}
    @if($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" class="p-page-btn">
        <i class="bi bi-chevron-right"></i>
      </a>
    @else
      <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
    @endif

  </div>
</div>
@endif