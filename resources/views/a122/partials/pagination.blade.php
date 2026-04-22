@if($paginator->hasPages())
<div class="a122-pagination">
  <div class="a122-pagination__meta">
    <span style="color:var(--p-text);font-weight:500">{{ number_format($paginator->firstItem()) }}</span>
    –
    <span style="color:var(--p-text);font-weight:500">{{ number_format($paginator->lastItem()) }}</span>
    /
    {{ number_format($paginator->total()) }} ta natija
  </div>

  <div class="a122-pagination__pages">
    @if($paginator->onFirstPage())
      <span class="a122-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" class="a122-page-btn">
        <i class="bi bi-chevron-left"></i>
      </a>
    @endif

    @php
      $current = $paginator->currentPage();
      $last = $paginator->lastPage();
      $from = max(1, $current - 2);
      $to = min($last, $current + 2);
    @endphp

    @if($from > 1)
      <a href="{{ $paginator->url(1) }}" class="a122-page-btn">1</a>
      @if($from > 2)
        <span class="a122-page-btn disabled" style="cursor:default">…</span>
      @endif
    @endif

    @foreach(range($from, $to) as $page)
      @if($page === $current)
        <span class="a122-page-btn active">{{ $page }}</span>
      @else
        <a href="{{ $paginator->url($page) }}" class="a122-page-btn">{{ $page }}</a>
      @endif
    @endforeach

    @if($to < $last)
      @if($to < $last - 1)
        <span class="a122-page-btn disabled" style="cursor:default">…</span>
      @endif
      <a href="{{ $paginator->url($last) }}" class="a122-page-btn">{{ $last }}</a>
    @endif

    @if($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" class="a122-page-btn">
        <i class="bi bi-chevron-right"></i>
      </a>
    @else
      <span class="a122-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
    @endif
  </div>
</div>
@endif
