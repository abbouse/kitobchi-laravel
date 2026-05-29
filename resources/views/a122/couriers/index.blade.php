@extends('a122.layouts.admin')
@section('title', 'Kuryerlar')
@section('page-title', 'Kuryerlar')

@section('content')
@php
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
    'pending' => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
    'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
    'blocked' => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
  ];
@endphp

<div class="d-flex flex-column gap-4">
  @if(session('success'))
    <div class="alert alert-success border-0 mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 mb-0">{{ session('error') }}</div>
  @endif

  <x-admin.page-header eyebrow="Operations" title="Kuryerlar" subtitle="{{ $couriers->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Ism, telefon yoki hudud" class="form-control">
    </form>
    <a href="{{ route('admin.couriers.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach($tabs as $key => $tabItem)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $tabItem['label'] }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($tabItem['count']) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Kuryerlar jadvali" :meta="$couriers->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Ism</th>
            <th>Telefon</th>
            <th>Viloyat</th>
            <th>Balans</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($couriers as $courier)
            @php
              $statusClass = match($courier->status) {
                'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'pending' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
                'rejected', 'blocked' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-secondary',
              };
              $statusLabel = match($courier->status) {
                'approved' => 'Tasdiqlangan',
                'pending' => 'Kutilmoqda',
                'rejected' => 'Rad etilgan',
                'blocked' => 'Bloklangan',
                default => $courier->status ?? '—',
              };
              $courierName = trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: 'Kuryer';
            @endphp
            <tr>
              <td class="text-secondary">#{{ $courier->id }}</td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  @include('a122.partials.avatar', [
                    'name' => $courierName,
                    'image' => $courier->photo,
                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                  ])
                  <div class="fw-semibold">{{ $courierName }}</div>
                </div>
              </td>
              <td>{{ $courier->phone_number ?? $courier->phone ?? '—' }}</td>
              <td>{{ $courier->region ?? '—' }}</td>
              <td class="fw-semibold text-nowrap">{{ number_format((float)($courier->balance ?? 0), 0, '.', ' ') }} UZS</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.couriers.approve', $courier) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.couriers.reject', $courier) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.couriers.show', $courier) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}" onsubmit="return confirm('Kuryerni o‘chirishga ishonchingiz komilmi?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Kuryer topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($couriers->hasPages())
    <div>{{ $couriers->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
