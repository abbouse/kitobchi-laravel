@extends('a122.layouts.admin')
@section('title', 'Nashriyotlar')
@section('page-title', 'Nashriyotlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Nashriyotlar" subtitle="{{ number_format($stats['total']) }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 22rem;">
      <i class="bi bi-search kc-search__icon"></i>
      <input name="search" value="{{ request('search') }}" placeholder="Nashriyot qidirish" class="form-control">
    </form>
    <a href="{{ route('admin.publishers.create') }}" class="btn-p primary">
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

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <x-admin.stat-card label="Jami nashriyot" :value="number_format($stats['total'])" icon="building" tone="primary" />
    </div>
    <div class="col-12 col-md-4">
      <x-admin.stat-card label="Rasmli kartalar" :value="number_format($stats['with_image'])" icon="image" tone="success" />
    </div>
    <div class="col-12 col-md-4">
      <x-admin.stat-card label="Bog‘langan kitoblar" :value="number_format($stats['linked_books'])" icon="book" tone="info" />
    </div>
  </div>

  <x-admin.section-card title="Nashriyotlar jadvali" :meta="$publishers->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Rasm</th>
            <th>Nomi</th>
            <th>Kitoblar</th>
            <th>Yangilangan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($publishers as $publisher)
            <tr>
              <td class="small text-secondary">#{{ $publisher->id }}</td>
              <td>
                <div style="width:56px;height:56px;" class="rounded-4 overflow-hidden border bg-light d-flex align-items-center justify-content-center">
                  @if($publisher->image_url)
                    <img src="{{ $publisher->image_url }}" alt="{{ $publisher->name }}" class="w-100 h-100 object-fit-cover">
                  @else
                    <i class="bi bi-building text-secondary"></i>
                  @endif
                </div>
              </td>
              <td class="fw-semibold">{{ $publisher->name }}</td>
              <td><span class="badge rounded-pill text-bg-light border">{{ number_format($publisher->books_count) }}</span></td>
              <td class="text-secondary">{{ optional($publisher->updated_at)->format('d.m.Y H:i') ?: '—' }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.publishers.edit', $publisher) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.publishers.destroy', $publisher) }}" onsubmit="return confirm('Nashriyotni o\\'chirishni tasdiqlaysizmi?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center py-5 text-secondary">Hali nashriyotlar qo‘shilmagan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  <div>{{ $publishers->links('a122.partials.pagination') }}</div>
</div>
@endsection
