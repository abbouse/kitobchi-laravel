@extends('a122.layouts.admin')
@section('title', 'Yangiliklar')
@section('page-title', 'Yangiliklar')

@section('content')
@php
  $currentTab = request('tab', 'all');
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'active' => ['label' => 'Faol', 'count' => $counts['active'] ?? 0],
    'news' => ['label' => 'Yangiliklar', 'count' => $counts['news'] ?? 0],
  ];
@endphp

<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Content" title="Yangiliklar" subtitle="{{ $news->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $currentTab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Sarlavha yoki do‘kon" class="form-control">
    </form>
    <a href="{{ route('admin.news.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success border-0 mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 mb-0">{{ session('error') }}</div>
  @endif

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach($tabs as $key => $tab)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $currentTab === $key ? 'active' : '' }}">
          {{ $tab['label'] }}
          <span class="badge rounded-pill {{ $currentTab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($tab['count']) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Yangiliklar jadvali" :meta="$news->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sarlavha</th>
            <th>Harakat</th>
            <th>Holat</th>
            <th>Yaratilgan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($news as $item)
            @php
              $isActive = $item->status === 'active' || $item->status == 1 || $item->status === true;
              $actionLabel = match ($item->normalizedAction()) {
                'to_bottomsheet' => 'Bottomsheet',
                'to_shop' => 'Do‘konga',
                'to_product' => 'Mahsulotga',
                'to_collection' => 'To‘plamga',
                default => $item->normalizedAction(),
              };
            @endphp
            <tr>
              <td class="text-secondary">#{{ $item->id }}</td>
              <td class="fw-semibold text-truncate" style="max-width: 22rem;">{{ $item->title }}</td>
              <td><span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $actionLabel }}</span></td>
              <td>
                @if($isActive)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary">Yashirin</span>
                @endif
              </td>
              <td class="text-secondary">{{ $item->created_at ? $item->created_at->format('d.m.Y H:i') : '—' }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.news.show', $item) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.news.toggle', $item) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="{{ $isActive ? 'Yashirish' : 'Faollashtirish' }}">
                      <i class="bi bi-{{ $isActive ? 'eye-slash' : 'eye' }}"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.news.destroy', $item) }}" onsubmit="return confirm('Bu yangilikni o‘chirishga ishonchingiz komilmi?')">
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
            <tr><td colspan="6" class="text-center py-5 text-secondary">Yangilik topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($news->hasPages())
    <div>{{ $news->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
