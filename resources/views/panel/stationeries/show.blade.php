@extends('panel.layouts.panel')
@section('title', $stationery->name)
@section('page-title', $stationery->name)

@section('content')

<x-panel.page-header back-href="{{ route('panel.stationery.index') }}">
  <x-slot name="heading">{{ $stationery->name }}</x-slot>
  <x-slot name="meta">ID: #{{ $stationery->id }} · {{ $stationery->category?->name_uz ?? '—' }}</x-slot>
</x-panel.page-header>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  {{-- Chap: asosiy ma'lumot --}}
  <div class="xl:col-span-8">

    {{-- Rasmlar --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Rasmlar</div></div>
      <div class="dash-card-body">
        @php $imgs = is_array($stationery->images) ? $stationery->images : []; @endphp
        @if(count($imgs))
        <div class="flex flex-wrap gap-2">
          @foreach($imgs as $img)
          <a href="{{ asset('storage/' . $img) }}" target="_blank"
             style="display:block;width:110px;height:110px;border-radius:10px;overflow:hidden;background:var(--p-elevated)">
            <img src="{{ asset('storage/' . $img) }}" style="width:100%;height:100%;object-fit:cover" alt="">
          </a>
          @endforeach
        </div>
        @else
          <p style="color:var(--p-hint)">Rasm yo'q</p>
        @endif
      </div>
    </div>

    {{-- Ma'lumotlar --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mahsulot ma'lumotlari</div></div>
      <div class="dash-card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach([
            ['ID',          $stationery->id],
            ['Nomi',        $stationery->name],
            ['Kategoriya',  $stationery->category?->name_uz ?? '—'],
            ['Material',    $stationery->material ?? '—'],
            ['Narx',        number_format($stationery->price).' UZS'],
            ['Chegirma',    $stationery->discount_price ? number_format($stationery->discount_price).' UZS' : '—'],
            ['Ombor',       $stationery->stock.' ta'],
            ['Status',      $stationery->status ? 'Aktiv' : 'Nofaol'],
            ['Qo\'shildi',  $stationery->created_at?->format('d.m.Y H:i')],
          ] as [$k, $v])
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">{{ $k }}</div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">{{ $v }}</div>
          </div>
          @endforeach
        </div>

        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">TAVSIF</div>
          <p style="font-size:13px;color:var(--p-muted);line-height:1.7">{{ $stationery->description }}</p>
        </div>
      </div>
    </div>

    {{-- Variantlar --}}
    @if($stationery->variants && $stationery->variants->count())
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Variantlar (ranglar)</div></div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Rang nomi</th><th>Ombor</th><th>Rasm</th></tr>
            </thead>
            <tbody>
              @foreach($stationery->variants as $v)
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $v->id }}</td>
                <td style="font-weight:500">{{ $v->color_name }}</td>
                <td><span class="s-pill {{ $v->stock>0?'success':'danger' }}">{{ $v->stock }} ta</span></td>
                <td>
                  @if($v->image_path)
                    <a href="{{ $v->image_path }}" target="_blank">
                      <img src="{{ $v->image_path }}" style="width:40px;height:40px;border-radius:6px;object-fit:cover" alt="">
                    </a>
                  @else
                    <span style="color:var(--p-hint)">—</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

  </div>

  {{-- O'ng: status + seller + statistika --}}
  <div class="xl:col-span-4">

    {{-- Moderatsiya --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Moderatsiya</div></div>
      <div class="dash-card-body">
        @php
          $cls = match($stationery->is_approved) { 1=>'success', 2=>'danger', default=>'warning' };
          $lbl = match($stationery->is_approved) { 1=>'Tasdiqlangan', 2=>'Rad etilgan', default=>'Kutilmoqda' };
        @endphp
        <div class="mb-3">
          <span class="s-pill {{ $cls }}" style="font-size:13px;padding:6px 14px">{{ $lbl }}</span>
          @if($stationery->is_hidden)
            <span class="s-pill muted ml-2">Yashirin</span>
          @endif
        </div>
        @include('panel.partials.kangaroo-listing-moderation', ['model' => $stationery])

        @if($stationery->is_approved == 0)
        <div class="flex gap-2">
          <form method="POST" action="{{ route('panel.stationery.moderate', $stationery) }}" class="flex-fill">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="approve">
            <button class="btn-p success" style="width:100%"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
          </form>
          <form method="POST" action="{{ route('panel.stationery.moderate', $stationery) }}" class="flex-fill">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="reject">
            <button class="btn-p danger" style="width:100%"><i class="bi bi-x-lg"></i> Rad etish</button>
          </form>
        </div>
        @endif
        <div class="mt-3">
          <a href="{{ route('panel.stationery.edit', $stationery) }}" class="btn-p ghost" style="width:100%">
            <i class="bi bi-pencil"></i> Tahrirlash
          </a>
        </div>
      </div>
    </div>

    {{-- Sotuvchi --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Sotuvchi</div></div>
      <div class="dash-card-body">
        <div class="flex items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center">
            @if($stationery->seller?->photo)
              <img src="{{ asset('storage/' . $stationery->seller->photo) }}" style="width:100%;height:100%;object-fit:cover" alt="">
            @else
              <i class="bi bi-shop" style="color:var(--p-hint)"></i>
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">{{ $stationery->seller?->shop_name ?? 'Noma\'lum' }}</div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $stationery->seller?->phone_number }}</div>
          </div>
        </div>
        @if($stationery->seller)
        <a href="{{ route('panel.sellers.show', $stationery->seller_id) }}" class="btn-p ghost sm">
          Sotuvchini ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        @endif
      </div>
    </div>

    {{-- Statistika --}}
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Statistika</div></div>
      <div class="dash-card-body">
        @foreach([
          ['Jami sotildi',    $stationery->totalSales,        'success'],
          ['Jami daromad',    number_format($stationery->totalRevenue).' UZS', 'accent'],
          ['Jami mijozlar',   $stationery->totalClients,      'info'],
          ["Bu hafta sotildi", $stationery->totalSalesWeek,   'warning'],
          ['Haftalik daromad', number_format($stationery->totalRevenueWeek).' UZS', 'warning'],
        ] as [$lbl, $val, $clr])
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $lbl }}</span>
          <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-{{ $clr }})">{{ $val }}</span>
        </div>
        @endforeach
      </div>
    </div>

  </div>
</div>
@endsection