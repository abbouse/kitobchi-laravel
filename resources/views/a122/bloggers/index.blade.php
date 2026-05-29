@extends('a122.layouts.admin')
@section('title', 'Hamkor blogerlar')
@section('page-title', 'Hamkor blogerlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Marketing" title="Hamkor blogerlar" subtitle="{{ $bloggers->total() }} ta yozuv">
    <a href="{{ route('admin.bloggers.create') }}" class="btn-p primary">
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

  <div class="row g-3">
    @foreach([
      ['Jami', $stats['total'], 'bi-people', 'primary'],
      ['Faol', $stats['active'], 'bi-check-circle', 'success'],
      ['Nofaol', $stats['inactive'], 'bi-slash-circle', 'secondary'],
      ['Jo‘natmalar', $stats['shipments'], 'bi-box-seam', 'warning'],
    ] as [$label, $value, $icon, $tone])
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="bi {{ $icon }}"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value">{{ number_format($value) }}</div>
            <div class="a122-stat-tile__label">{{ $label }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="kc-filter-card">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-lg">
        <label class="form-label">Qidiruv</label>
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Ism, familiya yoki telefon">
      </div>
      <div class="col-12 col-lg-3">
        <label class="form-label">Holat</label>
        <select name="tab" class="form-select">
          <option value="all" @selected($tab === 'all')>Barchasi</option>
          <option value="active" @selected($tab === 'active')>Faol</option>
          <option value="inactive" @selected($tab === 'inactive')>Nofaol</option>
        </select>
      </div>
      <div class="col-12 col-lg-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('admin.bloggers.index') }}" class="btn btn-light border">Tozalash</a>
      </div>
    </form>
  </div>

  <x-admin.section-card title="Blogerlar jadvali" :meta="$bloggers->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Bloger</th>
            <th>Aloqa</th>
            <th>Faollik</th>
            <th>Jo‘natmalar</th>
            <th>Tarmoqlar</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bloggers as $blogger)
            @php $socialCount = count($blogger->socialLinks()); @endphp
            <tr>
              <td>
                <div class="fw-semibold">{{ $blogger->full_name }}</div>
                <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($blogger->address ?: 'Manzil kiritilmagan', 72) }}</div>
              </td>
              <td>{{ $blogger->phone_number ?: '—' }}</td>
              <td>
                @if($blogger->is_active_now)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">{{ $blogger->status_label }}</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary">{{ $blogger->status_label }}</span>
                @endif
                <div class="small text-secondary">{{ optional($blogger->active_until)->format('d.m.Y H:i') ?: 'Muddat yo‘q' }}</div>
              </td>
              <td class="fw-semibold">{{ number_format($blogger->shipments_count) }}</td>
              <td>{{ $socialCount ? $socialCount . ' ta link' : '—' }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.bloggers.show', $blogger) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.bloggers.edit', $blogger) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.bloggers.destroy', $blogger) }}" onsubmit="return confirm('Blogerni o‘chirishni tasdiqlaysizmi?')">
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
            <tr><td colspan="6" class="text-center py-5 text-secondary">Bloger topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  <div>{{ $bloggers->links('a122.partials.pagination') }}</div>
</div>
@endsection
