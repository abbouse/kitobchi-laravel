@extends('a122.layouts.admin')
@section('title', 'Karyera arizalari')
@section('page-title', 'Karyera arizalari')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Content" title="Karyera arizalari" subtitle="{{ $applications->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 28rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="ID, ism, email yoki Telegram" class="form-control">
    </form>
    @if(request('search'))
      <a href="{{ route('admin.job-applications.index', ['tab' => $tab]) }}" class="btn btn-light border">Tozalash</a>
    @endif
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success border-0 mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 mb-0">{{ session('error') }}</div>
  @endif

  <div class="row g-3">
    @foreach([
      [$counts['all'], 'Jami', 'bi-briefcase', 'primary'],
      [$counts['vacancy'], 'Vakansiya', 'bi-person-workspace', 'info'],
      [$counts['inquiry'], 'Murojaat', 'bi-chat-square-text', 'warning'],
      [$counts['new'], 'Yangi', 'bi-stars', 'success'],
    ] as [$value, $label, $icon, $tone])
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
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'all' => ['Barchasi', $counts['all']],
        'vacancy' => ['Vakansiya', $counts['vacancy']],
        'inquiry' => ['Murojaat', $counts['inquiry']],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Arizalar jadvali" :meta="$applications->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Turi</th>
            <th>Lavozim</th>
            <th>Ism</th>
            <th>Email</th>
            <th>Telegram</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($applications as $row)
            <tr>
              <td class="text-secondary">#{{ $row->id }}</td>
              <td>
                <span class="badge rounded-pill {{ $row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'text-bg-secondary' : 'text-bg-info-subtle border border-info-subtle text-info-emphasis' }}">
                  {{ $row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat' : 'Vakansiya' }}
                </span>
              </td>
              <td class="fw-semibold">{{ $row->vacancy?->title ?? '—' }}</td>
              <td>{{ $row->full_name }}</td>
              <td class="text-secondary">{{ $row->email }}</td>
              <td class="text-secondary">{{ $row->telegram_username ? '@' . $row->telegram_username : '—' }}</td>
              <td>
                <form method="POST" action="{{ route('admin.job-applications.status', $row) }}">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($statuses as $key => $status)
                      <option value="{{ $key }}" {{ $row->status === $key ? 'selected' : '' }}>{{ $status['label'] }}</option>
                    @endforeach
                  </select>
                </form>
              </td>
              <td class="text-secondary text-nowrap">{{ $row->created_at?->format('d.m.Y H:i') }}</td>
              <td class="text-end">
                <a href="{{ route('admin.job-applications.show', $row) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center py-5 text-secondary">Ariza topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  <div>{{ $applications->links('a122.partials.pagination') }}</div>
</div>
@endsection
