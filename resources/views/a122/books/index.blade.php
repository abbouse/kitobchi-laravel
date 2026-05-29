@extends('a122.layouts.admin')
@section('title', 'Kitoblar')
@section('page-title', 'Kitoblar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Kitoblar" subtitle="{{ $books->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Kitob, muallif, kategoriya" class="form-control">
    </form>
    <a href="{{ route('admin.books.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'pending' => ['Moderatsiya', $counts['pending'] ?? 0],
        'active' => ['Faol', $counts['active'] ?? 0],
        'rejected' => ['Rad etilgan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Kitoblar jadvali" :meta="$books->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Kitob</th>
            <th>Kategoriya</th>
            <th>Narx</th>
            <th>Stok</th>
            <th>Status</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($books as $book)
            @php
              $statusLabel = (int) ($book->is_approved ?? 0) === 1 ? 'active' : ((int) ($book->is_approved ?? 0) === 2 ? 'banned' : 'pending');
              $statusClass = $statusLabel === 'active'
                ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                : ($statusLabel === 'pending'
                  ? 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis'
                  : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis');
            @endphp
            <tr>
              <td>
                <div class="fw-semibold">{{ $book->name }}</div>
                <div class="small text-secondary">{{ $book->authorProfile?->name ?: ($book->author ?: '—') }}</div>
              </td>
              <td>{{ $book->category?->name_uz ?: '—' }}</td>
              <td class="fw-semibold">{{ number_format((float) $book->price, 0) }} UZS</td>
              <td>{{ (int) ($book->count ?? 0) }}</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.books.moderate', $book) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Faollashtirish">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.books.moderate', $book) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.books.show', $book) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center py-5 text-secondary">Kitoblar topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if(isset($books) && method_exists($books, 'links'))
    <div>{{ $books->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
