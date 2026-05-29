@extends('a122.layouts.admin')
@section('title', 'Kitob Kategoriyalari')
@section('page-title', 'Kitob Kategoriyalari')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Kitob kategoriyalari" subtitle="Jami: {{ number_format($stats['total']) }} · Faol: {{ number_format($stats['active']) }}">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 22rem;">
      <i class="bi bi-search kc-search__icon"></i>
      <input name="search" value="{{ request('search') }}" placeholder="Kategoriya qidirish" class="form-control">
    </form>
    <a href="{{ route('admin.book-categories.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success kc-flash mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger kc-flash mb-0">{{ session('error') }}</div>
  @endif

  <x-admin.section-card title="Kategoriyalar jadvali" :meta="$categories->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nomi (UZ)</th>
            <th>Nomi (RU)</th>
            <th>Icon</th>
            <th>Kitoblar</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $cat)
            <tr>
              <td class="small text-secondary">#{{ $cat->id }}</td>
              <td class="fw-semibold">{{ $cat->name_uz }}</td>
              <td>{{ $cat->name_ru }}</td>
              <td class="fs-5">{{ $cat->icon }}</td>
              <td><span class="badge rounded-pill text-bg-light border">{{ number_format($cat->books_count) }}</span></td>
              <td>
                @if($cat->is_active)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary-subtle border">Yashirin</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.book-categories.toggle', $cat) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-light border kc-table-action" title="{{ $cat->is_active ? 'Yashirish' : 'Faollashtirish' }}">
                      <i class="bi {{ $cat->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.book-categories.edit', $cat) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.book-categories.destroy', $cat) }}" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Hech narsa topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  <div>{{ $categories->links('a122.partials.pagination') }}</div>
</div>
@endsection
