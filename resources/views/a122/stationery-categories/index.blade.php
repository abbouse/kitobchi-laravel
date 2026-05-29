@extends('a122.layouts.admin')
@section('title', 'Kanstovar kategoriyalari')
@section('page-title', 'Kanstovar kategoriyalari')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Kanstovar kategoriyalari" subtitle="Jami: {{ number_format($stats['total']) }} · Faol: {{ number_format($stats['active']) }}">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 22rem;">
      <i class="bi bi-search kc-search__icon"></i>
      <input name="search" value="{{ request('search') }}" placeholder="Kategoriya nomi" class="form-control">
    </form>
    <a href="{{ route('admin.stationery-categories.create') }}" class="btn-p primary">
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

  <x-admin.section-card title="Kategoriyalar jadvali" :meta="$categories->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nomi UZ</th>
            <th>Nomi RU</th>
            <th>Icon</th>
            <th>Mahsulotlar</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $cat)
            <tr>
              <td class="text-secondary">#{{ $cat->id }}</td>
              <td class="fw-semibold">{{ $cat->name_uz }}</td>
              <td class="text-secondary">{{ $cat->name_ru }}</td>
              <td><span class="fs-5">{{ $cat->icon }}</span></td>
              <td class="fw-semibold">{{ number_format($cat->stationeries_count) }}</td>
              <td>
                @if($cat->is_active)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary">Yashirin</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.stationery-categories.toggle', $cat) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-sm btn-light border kc-table-action" title="{{ $cat->is_active ? 'Yashirish' : 'Faollashtirish' }}">
                      <i class="bi bi-{{ $cat->is_active ? 'eye-slash' : 'eye' }}"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.stationery-categories.edit', $cat) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.stationery-categories.destroy', $cat) }}" onsubmit="return confirm('O‘chirishni tasdiqlaysizmi?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Kategoriya topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($categories->hasPages())
    <div>{{ $categories->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
