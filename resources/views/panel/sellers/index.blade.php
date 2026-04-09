@extends('panel.layouts.panel')
@section('title', 'Sotuvchilar')
@section('page-title', 'Sotuvchilar')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Sotuvchilar</h1>
    <p class="page-sub">Do'konlar va sotuvchilar boshqaruvi</p>
  </div>
  <a href="{{ route('panel.sellers.export', request()->all()) }}" class="btn-p ghost">
    <i class="bi bi-download"></i> Export
  </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    ['Kutilmoqda',    $counts['pending'],  'warning', 'bi-hourglass'],
    ['Tasdiqlangan',  $counts['approved'], 'success', 'bi-shop-window'],
    ['Rad etilgan',   $counts['rejected'], 'danger',  'bi-x-circle'],
    ['Jami',          $counts['all'],      'accent',  'bi-grid'],
  ] as [$l,$v,$c,$i])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-{{ $c }}-d,var(--p-elevated));color:var(--p-{{ $c }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $i }}"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;
                    color:var(--p-{{ $c }})">{{ $v }}</div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em">{{ $l }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-3">
  @foreach([
    'pending'  => 'Kutilmoqda',
    'approved' => 'Tasdiqlangan',
    'rejected' => 'Rad etilgan',
    'all'      => 'Barchasi',
  ] as $key => $label)
  <a href="{{ route('panel.sellers.index', array_merge(request()->except('tab','page'), ['tab'=>$key])) }}"
     class="tab-pill {{ $tab===$key ? 'active' : '' }}">
    {{ $label }}
    <span class="tab-count">{{ $counts[$key] }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('panel.sellers.index') }}" id="sellerFilter">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="filter-bar fade-up mb-3">
    <div class="search-box" style="width:220px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Do'kon nomi, telefon, ID...">
    </div>
    <select name="region" class="p-form-control" style="width:160px"
            onchange="sellerFilter.submit()">
      <option value="">Barcha viloyat</option>
      @foreach($regions as $r)
        <option value="{{ $r }}" {{ request('region')===$r ? 'selected' : '' }}>{{ $r }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>
    @if(request()->hasAny(['search','region']))
    <a href="{{ route('panel.sellers.index', ['tab'=>$tab]) }}" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    @endif
  </div>
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Sotuvchilar ro'yxati</div>
    <div class="p-card-sub">{{ $sellers->total() }} ta natija</div>
  </div>
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th><th>Do'kon</th><th>Telefon</th><th>Viloyat</th>
          <th>Faoliyat</th><th>Balans</th><th>Holat</th><th>Kitob</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($sellers as $seller)
        @php
          $st = trim((string)($seller->status ?? ''));
          $stCls = match($st){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
          $stLbl = match($st){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda' };
          $types = $seller->activity_types; // accessor har doim array qaytaradi
        @endphp
        <tr>
          <td>
            <span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">
              #{{ $seller->id }}
            </span>
          </td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:8px;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-warning),#f97316);
                          display:flex;align-items:center;justify-content:center;
                          font-size:14px;font-weight:700;color:#fff">
                @if($seller->photo)
                  <img src="{{ Storage::url($seller->photo) }}"
                       style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($seller->shop_name, 0, 1)) }}
                @endif
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  {{ $seller->shop_name }}
                </div>
                <div style="font-size:11px;color:var(--p-hint)">
                  {{ $seller->firstname }} {{ $seller->lastname }}
                </div>
              </div>
            </div>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)">
            {{ $seller->phone_number }}
          </td>
          <td style="font-size:13px;color:var(--p-muted)">{{ $seller->region }}</td>
          <td>
            @foreach($types as $type)
              <span class="s-pill accent"
                    style="font-size:10px;padding:2px 7px;margin-right:2px">{{ $type }}</span>
            @endforeach
          </td>
          <td style="font-family:'DM Mono',monospace;font-weight:500;color:var(--p-text)">
            {{ number_format($seller->balance ?? 0) }}
            <span style="font-size:11px;color:var(--p-hint)">UZS</span>
          </td>
          <td>
            <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:13px;font-weight:500;
                     color:var(--p-text)">
            {{ $seller->books_count }}
          </td>
          <td>
            <div class="d-flex gap-1 align-items-center">
              @if($st !== 'approved')
              <form method="POST" action="{{ route('panel.sellers.approve', $seller) }}">
                @csrf @method('PATCH')
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              @endif
              @if($st !== 'rejected')
              <form method="POST" action="{{ route('panel.sellers.reject', $seller) }}">
                @csrf @method('PATCH')
                <button class="btn-p danger sm" title="Rad etish">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              @endif
              <a href="{{ route('panel.sellers.show', $seller) }}" class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('panel.sellers.edit', $seller) }}" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-shop" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Sotuvchilar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($sellers->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $sellers->firstItem() }}–{{ $sellers->lastItem() }} / {{ $sellers->total() }}
    </div>
    <div class="p-pagination">
      @if($sellers->onFirstPage())
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $sellers->previousPageUrl() }}" class="p-page-btn">
          <i class="bi bi-chevron-left"></i>
        </a>
      @endif
      @foreach($sellers->getUrlRange(
        max(1, $sellers->currentPage()-2),
        min($sellers->lastPage(), $sellers->currentPage()+2)
      ) as $page => $url)
        <a href="{{ $url }}"
           class="p-page-btn {{ $page===$sellers->currentPage() ? 'active' : '' }}">
          {{ $page }}
        </a>
      @endforeach
      @if($sellers->hasMorePages())
        <a href="{{ $sellers->nextPageUrl() }}" class="p-page-btn">
          <i class="bi bi-chevron-right"></i>
        </a>
      @else
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection