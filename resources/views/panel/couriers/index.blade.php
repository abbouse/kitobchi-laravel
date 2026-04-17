@extends('panel.layouts.panel')
@section('title', 'Kuryerlar')
@section('page-title', 'Kuryerlar')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Kuryerlar</h1>
    <p class="page-sub">Barcha kuryerlar boshqaruvi</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('panel.couriers.export', request()->all()) }}" class="btn-p ghost">
      <i class="bi bi-download"></i> Export
    </a>
    <a href="{{ route('panel.couriers.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i> Yangi kuryer
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    ['Jami',        $counts['all'],      'accent',  'bi-bicycle'],
    ['Tasdiqlangan',$counts['approved'], 'success', 'bi-check-circle'],
    ['Kutilmoqda',  $counts['pending'],  'warning', 'bi-hourglass'],
    ['Rad etilgan', $counts['rejected'], 'danger',  'bi-x-circle'],
  ] as [$l,$v,$c,$i])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-{{ $c }}-d,var(--p-elevated));color:var(--p-{{ $c }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $i }}"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:700;font-family:'JetBrains Mono',monospace;
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
    'all'      => ['Barchasi',      $counts['all']],
    'approved' => ['Tasdiqlangan',  $counts['approved']],
    'pending'  => ['Kutilmoqda',    $counts['pending']],
    'rejected' => ['Rad etilgan',   $counts['rejected']],
  ] as $key => [$label, $cnt])
  <a href="{{ route('panel.couriers.index', array_merge(request()->except('tab','page'), ['tab'=>$key])) }}"
     class="tab-pill {{ $tab===$key ? 'active' : '' }}">
    {{ $label }}
    <span class="tab-count">{{ $cnt }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('panel.couriers.index') }}" id="courierFilter">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="filter-bar fade-up mb-3">
    <div class="search-box" style="width:200px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Ism, telefon, ID...">
    </div>
    <select name="region" class="p-form-control" style="width:160px"
            onchange="courierFilter.submit()">
      <option value="">Barcha viloyat</option>
      @foreach($regions as $r)
        <option value="{{ $r }}" {{ request('region')===$r ? 'selected' : '' }}>{{ $r }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-p primary">
      <i class="bi bi-funnel"></i> Filter
    </button>
    @if(request()->hasAny(['search','region']))
    <a href="{{ route('panel.couriers.index', ['tab'=>$tab]) }}" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    @endif
  </div>
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kuryer</th>
          <th>Telefon</th>
          <th>Viloyat</th>
          <th style="text-align:right">Balans</th>
          <th>Holat</th>
          <th>Qo'shilgan</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($couriers as $courier)
        @php
          [$stCls, $stLbl] = match($courier->status) {
            'approved' => ['success', 'Tasdiqlangan'],
            'rejected' => ['danger',  'Rad etildi'],
            default    => ['warning', 'Kutilmoqda'],
          };
        @endphp
        <tr>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
              #{{ $courier->id }}
            </span>
          </td>

          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,#14b8a6,#0d9488);
                          display:flex;align-items:center;justify-content:center;
                          font-size:13px;font-weight:700;color:#fff">
                @if($courier->photo)
                  <img src="{{ Storage::url($courier->photo) }}"
                       style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($courier->first_name, 0, 1)) }}
                @endif
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  {{ $courier->first_name }} {{ $courier->last_name }}
                </div>
                <div style="font-size:11px;color:var(--p-hint)">{{ $courier->region }}</div>
              </div>
            </div>
          </td>

          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            {{ $courier->phone_number }}
          </td>

          <td style="font-size:13px;color:var(--p-muted)">{{ $courier->region }}</td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:500;color:var(--p-text)">
            {{ number_format($courier->balance ?? 0) }}
            <span style="font-size:11px;color:var(--p-hint)">UZS</span>
          </td>

          <td>
            <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
          </td>

          <td style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            {{ $courier->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="d-flex gap-1 align-items-center">
              <a href="{{ route('panel.couriers.show', $courier) }}"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="{{ route('panel.couriers.edit', $courier) }}"
                 class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>

              {{-- Tezkor approve/reject --}}
              @if($courier->status === 'pending')
              <form method="POST"
                    action="{{ route('panel.couriers.approve', $courier) }}">
                @csrf @method('PATCH')
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              <form method="POST"
                    action="{{ route('panel.couriers.reject', $courier) }}">
                @csrf @method('PATCH')
                <button class="btn-p danger sm" title="Rad etish">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              @elseif($courier->status === 'rejected')
              <form method="POST"
                    action="{{ route('panel.couriers.approve', $courier) }}">
                @csrf @method('PATCH')
                <button class="btn-p ghost sm" title="Qayta tasdiqlash"
                        style="color:var(--p-success)">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              @endif

              <form method="POST"
                    action="{{ route('panel.couriers.destroy', $courier) }}"
                    onsubmit="return confirm('Kuryerni o\'chirishni tasdiqlaysizmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-bicycle"
               style="font-size:32px;display:block;margin-bottom:8px"></i>
            Kuryerlar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($couriers->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $couriers->firstItem() }}–{{ $couriers->lastItem() }} / {{ $couriers->total() }}
    </div>
    {{ $couriers->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection