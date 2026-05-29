@extends('a122.layouts.admin')
@section('title', 'Vakansiyalar')
@section('page-title', 'Vakansiyalar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Content" title="Vakansiyalar" subtitle="{{ $vacancies->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Lavozim, joy yoki tur" class="form-control">
    </form>
    <a href="{{ route('careers.index') }}" target="_blank" rel="noopener" class="btn btn-light border">Careers</a>
    <a href="{{ route('admin.jobs.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success border-0 mb-0">{{ session('success') }}</div>
  @endif

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Yashirin', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Vakansiyalar jadvali" :meta="$vacancies->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th></th>
            <th>Nomi</th>
            <th>Turi</th>
            <th>Joy</th>
            <th>Tartib</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($vacancies as $row)
            @php
              $icon = $row->resolvedIcon();
              $bi = match ($icon) {
                'code' => 'code-slash',
                'palette' => 'palette-fill',
                'shop' => 'shop',
                'megaphone' => 'megaphone-fill',
                'people' => 'people-fill',
                'chart' => 'graph-up-arrow',
                default => 'briefcase-fill',
              };
            @endphp
            <tr>
              <td class="text-secondary">#{{ $row->id }}</td>
              <td class="text-secondary" title="{{ \App\Models\Vacancy::iconOptions()[$icon] ?? '' }}"><i class="bi bi-{{ $bi }}"></i></td>
              <td class="fw-semibold">{{ $row->title }}</td>
              <td class="text-secondary">{{ $row->contract_type ?: '—' }}</td>
              <td class="text-secondary">{{ $row->location ?: '—' }}</td>
              <td>{{ $row->sort_order }}</td>
              <td>
                @if($row->is_active)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary">Yashirin</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.jobs.edit', $row) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.jobs.toggle', $row) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rinishni almashtirish">
                      <i class="bi bi-{{ $row->is_active ? 'eye-slash' : 'eye' }}"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.jobs.destroy', $row) }}" onsubmit="return confirm('O‘chirilsinmi?');">
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
            <tr><td colspan="8" class="text-center py-5 text-secondary">Vakansiya topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($vacancies->hasPages())
    <div>{{ $vacancies->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
