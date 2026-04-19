@extends('panel.layouts.panel')
@section('title', 'Kantselyariya mahsulotlari')
@section('page-title', 'Kantselyariya')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Kantselyariya mahsulotlari</x-slot>
  <x-slot name="meta">Sotuvchilar yuklagan mahsulotlar moderatsiyasi</x-slot>
</x-panel.page-header>


{{-- Stats --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  @foreach([
    ['pending',  'Kutilmoqda',   'warning', 'hourglass-split'],
    ['approved', 'Tasdiqlangan', 'success', 'check-circle'],
    ['rejected', 'Rad etilgan',  'danger',  'x-circle'],
    ['hidden',   'Yashirilgan',  'muted',   'eye-slash'],
  ] as [$key, $lbl, $clr, $icon])
  <div class="">
    <div class="p-card flex items-center gap-3">
      <div style="width:40px;height:40px;border-radius:10px;background:var(--p-{{ $clr }}-d ?? var(--p-elevated));display:flex;align-items:center;justify-content:center;color:var(--p-{{ $clr }});font-size:18px;flex-shrink:0">
        <i class="bi bi-{{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">{{ number_format($counts[$key]) }}</div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">{{ $lbl }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Tabs --}}
<div class="tab-pills mb-3">
  @foreach([
    ['pending','Kutilmoqda','warning'],
    ['approved','Tasdiqlangan','success'],
    ['rejected','Rad etilgan','danger'],
    ['hidden','Yashirilgan','muted'],
  ] as [$key,$lbl,$clr])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
     class="tab-pill {{ $tab===$key?'active':'' }}">
    {{ $lbl }}
    <span class="tab-badge">{{ $counts[$key] }}</span>
  </a>
  @endforeach
</div>

{{-- Filter bar --}}
<div class="filter-bar mb-3">
  <form method="GET" class="flex flex-wrap gap-2 items-center">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="ID, nomi, seller ID..."
           value="{{ request('search') }}" style="width:220px">
    <select name="category_id" class="p-form-control" style="width:180px">
      <option value="">Barcha kategoriya</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ request('category_id')==$cat->id?'selected':'' }}>
          {{ $cat->icon }} {{ $cat->name_uz }}
        </option>
      @endforeach
    </select>
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="{{ route('panel.stationery.index', ['tab'=>$tab]) }}" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    <div class="ml-auto flex gap-2">
      <a href="{{ route('panel.stationery.export') }}" class="btn-p ghost">
        <i class="bi bi-download"></i> Export
      </a>
    </div>
  </form>
</div>

{{-- Table --}}
<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th style="width:50px">#</th>
          <th>Mahsulot</th>
          <th>Kategoriya</th>
          <th>Sotuvchi</th>
          <th>Narx</th>
          <th>Ombor</th>
          <th>Sotildi</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($stationeries as $item)
        @php
          $img = is_array($item->images) ? ($item->images[0] ?? null) : null;
          $approvedClass = match($item->is_approved) {
            1 => 's-pill success', 2 => 's-pill danger', default => 's-pill warning'
          };
          $approvedLabel = match($item->is_approved) {
            1 => 'Tasdiqlangan', 2 => 'Rad etildi', default => 'Kutilmoqda'
          };
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">{{ $item->id }}</td>
          <td>
            <div class="flex items-center gap-2">
              <div style="width:36px;height:36px;border-radius:8px;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center">
                @if($img)
                  <img src="{{ asset('storage/' . $img) }}" style="width:100%;height:100%;object-fit:cover" alt="">
                @else
                  <i class="bi bi-box" style="color:var(--p-hint)"></i>
                @endif
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ Str::limit($item->name,30) }}</div>
                @if($item->material)
                  <div style="font-size:11px;color:var(--p-hint)">{{ $item->material }}</div>
                @endif
              </div>
            </div>
          </td>
          <td style="font-size:12px;color:var(--p-muted)">{{ $item->category?->name_uz ?? '—' }}</td>
          <td>
            <a href="{{ route('panel.sellers.show', $item->seller_id) }}"
               style="font-size:12px;color:var(--p-accent)">
              {{ $item->seller?->shop_name ?? $item->seller_id }}
            </a>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--p-text)">
            {{ number_format($item->price) }}
            @if($item->discount_price)
              <div style="font-size:11px;color:var(--p-danger);text-decoration:line-through">{{ number_format($item->discount_price) }}</div>
            @endif
          </td>
          <td>
            <span class="s-pill {{ $item->stock > 0 ? 'success' : 'danger' }}">
              {{ $item->stock }} ta
            </span>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">{{ number_format($item->totalSales) }}</td>
          <td>
            <div class="flex flex-col gap-1">
              <span class="{{ $approvedClass }}">{{ $approvedLabel }}</span>
              @if($item->is_hidden)
                <span class="s-pill muted" style="font-size:10px">Yashirin</span>
              @endif
            </div>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">{{ $item->created_at?->format('d.m.Y') }}</td>
          <td>
            <div class="flex gap-1">
              @if($item->is_approved == 0)
                <form method="POST" action="{{ route('panel.stationery.moderate', $item) }}">
                  @csrf @method('PATCH')
                  <input type="hidden" name="action" value="approve">
                  <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
                </form>
                <form method="POST" action="{{ route('panel.stationery.moderate', $item) }}">
                  @csrf @method('PATCH')
                  <input type="hidden" name="action" value="reject">
                  <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
                </form>
              @endif
              <a href="{{ route('panel.stationery.show', $item) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="{{ route('panel.stationery.edit', $item) }}" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-box" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Mahsulotlar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($stationeries->hasPages())
  <div class="p-pagination">{{ $stationeries->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection