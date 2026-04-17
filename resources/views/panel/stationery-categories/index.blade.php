{{-- stationery-categories/index.blade.php --}}
@extends('panel.layouts.panel')
@section('title', 'Kantselyariya kategoriyalari')
@section('page-title', 'Kantselyariya kategoriyalari')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
  <div style="font-size:13px;color:var(--p-hint)">
    Jami: <strong style="color:var(--p-text)">{{ $stats['total'] }}</strong> ta,
    Aktiv: <strong style="color:var(--p-success)">{{ $stats['active'] }}</strong> ta
  </div>
  <a href="{{ route('panel.stationery-categories.create') }}" class="btn-p">
    <i class="bi bi-plus-lg"></i> Yangi kategoriya
  </a>
</div>

<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="search" name="search" class="p-form-control" placeholder="Nom bo'yicha..."
           value="{{ request('search') }}" style="width:220px">
    <select name="status" class="p-form-control" style="width:150px">
      <option value="">Barchasi</option>
      <option value="1" {{ request('status')==='1'?'selected':'' }}>Aktiv</option>
      <option value="0" {{ request('status')==='0'?'selected':'' }}>Nofaol</option>
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.stationery-categories.index') }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Icon</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Mahsulotlar</th>
          <th>Slug</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $cat)
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $cat->id }}</td>
          <td style="font-size:22px;text-align:center">{{ $cat->icon ?? '🗂️' }}</td>
          <td style="font-weight:500;color:var(--p-text)">{{ $cat->name_uz }}</td>
          <td style="color:var(--p-muted)">{{ $cat->name_ru }}</td>
          <td>
            <span class="s-pill accent">{{ number_format($cat->stationeries_count) }} ta</span>
          </td>
          <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">{{ $cat->slug ?: '—' }}</td>
          <td>
            <span class="s-pill {{ $cat->is_active?'success':'muted' }}">
              {{ $cat->is_active?'Aktiv':'Nofaol' }}
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="{{ route('panel.stationery-categories.edit', $cat) }}" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="{{ route('panel.stationery-categories.toggle', $cat) }}">
                @csrf @method('PATCH')
                <button class="btn-p ghost sm">
                  <i class="bi bi-{{ $cat->is_active?'eye-slash':'eye' }}"></i>
                </button>
              </form>
              @if($cat->stationeries_count == 0)
              <form method="POST" action="{{ route('panel.stationery-categories.destroy', $cat) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
          Kategoriyalar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($categories->hasPages())
  <div class="p-pagination">{{ $categories->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection