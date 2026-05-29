@extends('a122.layouts.admin')

@section('title', 'Kanstovarlar')
@section('page-title', 'Kanstovarlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Kanstovarlar" subtitle="{{ $items->total() }} ta yozuv">
    <form method="get" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Nomi yoki kategoriya" class="form-control">
    </form>
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

  <x-admin.section-card title="Kanstovarlar jadvali" :meta="$items->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nomi</th>
            <th>Kategoriya</th>
            <th>Narx</th>
            <th>Stok</th>
            <th>Status</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            @php
              $statusClass = $row['status'] === 'active'
                ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                : ($row['status'] === 'pending'
                  ? 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis'
                  : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis');
            @endphp
            <tr>
              <td class="text-secondary">#{{ $row['id'] }}</td>
              <td class="fw-semibold">{{ $row['title'] }}</td>
              <td>{{ $row['category'] }}</td>
              <td class="fw-semibold">{{ $row['price'] }}</td>
              <td>{{ $row['stock'] }}</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $row['status'] }}</span></td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.stationery.moderate', $row['id']) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Faollashtirish">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.stationery.moderate', $row['id']) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.stationery.show', $row['id']) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.stationery.edit', $row['id']) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Kanstovar topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if(isset($items) && method_exists($items, 'links'))
    <div>{{ $items->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
