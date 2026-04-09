@extends('panel.layouts.panel')

@section('title', 'Foydalanuvchilar')
@section('page-title', 'Foydalanuvchilar')
@section('breadcrumb', 'Panel / Foydalanuvchilar')

@section('content')

{{-- ── Page header ──────────────────────────────── --}}
<div class="page-header fade-up d-flex align-items-start justify-content-between">
  <div>
    <h1 class="page-title">Foydalanuvchilar</h1>
    <p class="page-sub">Barcha ro'yxatdan o'tgan foydalanuvchilar</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('panel.users.import') }}" class="btn-p ghost">
      <i class="bi bi-upload"></i> Import
    </a>
    <a href="{{ route('panel.users.export', request()->all()) }}" class="btn-p ghost">
      <i class="bi bi-download"></i> Export
    </a>
    <a href="{{ route('panel.users.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i> Yangi foydalanuvchi
    </a>
  </div>
</div>

{{-- ── Metric cards ─────────────────────────────── --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3 fade-up d1">
    <div class="metric-card" style="border-top-color: var(--p-accent)">
      <div class="metric-icon" style="background:var(--p-accent-d);color:var(--p-accent)"><i class="bi bi-people-fill"></i></div>
      <div class="metric-label">Jami</div>
      <div class="metric-value">{{ number_format($stats['total']) }}</div>
      <span class="s-pill accent"><i class="bi bi-plus"></i>{{ $stats['today'] }} bugun</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d2">
    <div class="metric-card" style="border-top-color: var(--p-warning)">
      <div class="metric-icon" style="background:var(--p-warning-d);color:var(--p-warning)"><i class="bi bi-star-fill"></i></div>
      <div class="metric-label">Premium</div>
      <div class="metric-value">{{ number_format($stats['premium']) }}</div>
      <span class="s-pill warning">{{ $stats['total'] > 0 ? round($stats['premium']/$stats['total']*100) : 0 }}% ulushi</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d3">
    <div class="metric-card" style="border-top-color: var(--p-success)">
      <div class="metric-icon" style="background:var(--p-success-d);color:var(--p-success)"><i class="bi bi-circle-fill"></i></div>
      <div class="metric-label">Online</div>
      <div class="metric-value">{{ number_format($stats['online']) }}</div>
      <span class="s-pill success">Hozir aktiv</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d4">
    <div class="metric-card" style="border-top-color: var(--p-info)">
      <div class="metric-icon" style="background:var(--p-info-d);color:var(--p-info)"><i class="bi bi-person-plus-fill"></i></div>
      <div class="metric-label">Bugun yangi</div>
      <div class="metric-value">{{ number_format($stats['today']) }}</div>
      <span class="s-pill info">Bugun qo'shildi</span>
    </div>
  </div>
</div>

{{-- ── Filter bar ───────────────────────────────── --}}
<form method="GET" action="{{ route('panel.users.index') }}" id="filterForm">
<div class="filter-bar fade-up">
  <div class="search-box" style="width:220px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ism, telefon, ID..."/>
  </div>

  <select name="is_premium" class="p-form-control" style="width:140px" onchange="document.getElementById('filterForm').submit()">
    <option value="">Premium</option>
    <option value="1" {{ request('is_premium') === '1' ? 'selected' : '' }}>Premium ✓</option>
    <option value="0" {{ request('is_premium') === '0' ? 'selected' : '' }}>Oddiy</option>
  </select>

  <select name="isVerified" class="p-form-control" style="width:140px" onchange="document.getElementById('filterForm').submit()">
    <option value="">Tasdiqlash</option>
    <option value="1" {{ request('isVerified') === '1' ? 'selected' : '' }}>Tasdiqlangan</option>
    <option value="0" {{ request('isVerified') === '0' ? 'selected' : '' }}>Tasdiqlanmagan</option>
  </select>

  <select name="sort_by" class="p-form-control" style="width:150px" onchange="document.getElementById('filterForm').submit()">
    <option value="id"           {{ request('sort_by','id') === 'id'           ? 'selected' : '' }}>ID bo'yicha</option>
    <option value="created_at"   {{ request('sort_by') === 'created_at'         ? 'selected' : '' }}>Sana bo'yicha</option>
    <option value="real_balance" {{ request('sort_by') === 'real_balance'       ? 'selected' : '' }}>Balans bo'yicha</option>
  </select>

  <select name="sort_dir" class="p-form-control" style="width:110px" onchange="document.getElementById('filterForm').submit()">
    <option value="desc" {{ request('sort_dir','desc') === 'desc' ? 'selected' : '' }}>Kamayib</option>
    <option value="asc"  {{ request('sort_dir') === 'asc'         ? 'selected' : '' }}>O'sib</option>
  </select>

  <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>

  @if(request()->hasAny(['search','is_premium','isVerified','sort_by']))
  <a href="{{ route('panel.users.index') }}" class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
  @endif
</div>
</form>

{{-- ── Table ────────────────────────────────────── --}}
<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Foydalanuvchilar ro'yxati</div>
      <div class="p-card-sub">Jami {{ $users->total() }} ta natija</div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Foydalanuvchi</th>
          <th>Telefon</th>
          <th>Balans</th>
          <th>Premium</th>
          <th>Ishonchlilik</th>
          <th>Qo'shilgan</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
        <tr>
          <td>
            <span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">#{{ $user->id }}</span>
          </td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="av av-blue">
                @if($user->avatar)
                  <img src="{{ Storage::url($user->avatar) }}" alt="">
                @else
                  {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                @endif
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  {{ $user->name }} {{ $user->lastname }}
                </div>
                <div style="font-size:11px;color:var(--p-hint)">{{ $user->verifyCode ?: 'Avtorizatsiyadan o\'tgan' }}</div>
              </div>
            </div>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px">@if($user->phone_number)
        +{{ preg_replace('/(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})/', '$1 ($2) $3 $4 $5', $user->phone_number) }}
    @else
        —
    @endif</td>
          <td>
            <span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--p-text)">
              {{ number_format($user->real_balance ?? 0) }}
            </span>
            <span style="font-size:11px;color:var(--p-hint)"> UZS</span>
          </td>
          <td>
            @if($user->is_premium)
              <span class="s-pill warning"><i class="bi bi-star-fill" style="font-size:9px"></i> Premium</span>
            @else
              <span class="s-pill muted">Oddiy</span>
            @endif
          </td>
          <td>
            @if($user->isVerified)
              <span class="s-pill success">Ishonchli</span>
            @else
              <span class="s-pill danger">Tekshirilmagan</span>
            @endif
          </td>
          <td style="font-size:12px;color:var(--p-hint);font-family:'DM Mono',monospace">
            {{ $user->created_at?->format('d.m.Y') }}
          </td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <a href="{{ route('panel.users.show', $user) }}" class="btn-p ghost sm" title="Ko'rish">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('panel.users.edit', $user) }}" class="btn-p ghost sm" title="Tahrirlash">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="{{ route('panel.users.destroy', $user) }}"
                    onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-p danger sm" title="O'chirish">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Foydalanuvchilar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($users->hasPages())
  <div class="d-flex align-items-center justify-content-between mt-3" style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $users->firstItem() }}–{{ $users->lastItem() }} / {{ $users->total() }} ta natija
    </div>
    <div class="p-pagination">
      @if($users->onFirstPage())
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $users->previousPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      @endif

      @foreach($users->getUrlRange(max(1,$users->currentPage()-2), min($users->lastPage(),$users->currentPage()+2)) as $page => $url)
        <a href="{{ $url }}" class="p-page-btn {{ $page === $users->currentPage() ? 'active' : '' }}">{{ $page }}</a>
      @endforeach

      @if($users->hasMorePages())
        <a href="{{ $users->nextPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      @else
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection