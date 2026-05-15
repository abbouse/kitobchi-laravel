@if($paginator->hasPages())
  @php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $from = max(1, $current - 2);
    $to = min($last, $current + 2);
  @endphp

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="small text-secondary">
      <span class="fw-semibold text-dark">{{ number_format($paginator->firstItem()) }}</span>
      –
      <span class="fw-semibold text-dark">{{ number_format($paginator->lastItem()) }}</span>
      / {{ number_format($paginator->total()) }} ta natija
    </div>

    <nav aria-label="Pagination">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
          <a class="page-link rounded-pill px-3" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" tabindex="{{ $paginator->onFirstPage() ? '-1' : '0' }}">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>

        @if($from > 1)
          <li class="page-item">
            <a class="page-link rounded-pill px-3" href="{{ $paginator->url(1) }}">1</a>
          </li>
          @if($from > 2)
            <li class="page-item disabled"><span class="page-link rounded-pill px-3">…</span></li>
          @endif
        @endif

        @foreach(range($from, $to) as $page)
          <li class="page-item {{ $page === $current ? 'active' : '' }}">
            @if($page === $current)
              <span class="page-link rounded-pill px-3">{{ $page }}</span>
            @else
              <a class="page-link rounded-pill px-3" href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
          </li>
        @endforeach

        @if($to < $last)
          @if($to < $last - 1)
            <li class="page-item disabled"><span class="page-link rounded-pill px-3">…</span></li>
          @endif
          <li class="page-item">
            <a class="page-link rounded-pill px-3" href="{{ $paginator->url($last) }}">{{ $last }}</a>
          </li>
        @endif

        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
          <a class="page-link rounded-pill px-3" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" tabindex="{{ $paginator->hasMorePages() ? '0' : '-1' }}">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
@endif
