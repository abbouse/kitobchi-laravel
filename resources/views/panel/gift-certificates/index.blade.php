@extends('panel.layouts.panel')
@section('title', 'Gift Sertifikatlar')
@section('page-title', 'Gift Sertifikatlar')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Gift Sertifikatlar</x-slot>
  <x-slot name="meta">Foydalanuvchilar sotib olgan sovg'a sertifikatlari</x-slot>
</x-panel.page-header>


{{-- Stats --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  @foreach([
    ['Jami',        $counts['all'],             'accent',  'bi-gift'],
    ['Yuborilgan',  $counts['sent'],             'info',    'bi-send'],
    ['Ishlatilgan', $counts['used'],             'success', 'bi-check-circle'],
    ['Bekor',       $counts['cancelled'],        'danger',  'bi-x-circle'],
  ] as [$l,$v,$c,$i])
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:14px">
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
    'all'             => ['Barchasi',        $counts['all']],
    'pending_payment' => ['Kutilmoqda',       $counts['pending_payment']],
    'sent'            => ['Yuborilgan',       $counts['sent']],
    'used'            => ['Ishlatilgan',      $counts['used']],
    'cancelled'       => ['Bekor qilingan',   $counts['cancelled']],
  ] as $k => [$l, $c])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
     class="tab-pill {{ $tab===$k?'active':'' }}">
    {{ $l }} <span class="tab-count">{{ $c }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="search-box" style="width:250px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Kod, telefon, ism...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
  @if(request('search'))
  <a href="{{ request()->fullUrlWithQuery(['search'=>null]) }}" class="btn-p ghost">
    <i class="bi bi-x"></i>
  </a>
  @endif
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>Kod</th>
          <th>Sotib olgan</th>
          <th>Qabul qiluvchi</th>
          <th style="text-align:right">Miqdor</th>
          <th>Holat</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($certs as $c)
        @php
          $stCls = match($c->status){
            'active'           => 'success',
            'used'             => 'muted',
            'paid'             => 'info',
            'sent'             => 'accent',
            'cancelled'        => 'danger',
            'payment_cancelled'=> 'danger',
            'pending_payment'  => 'warning',
            default            => 'warning',
          };
          $stLbl = match($c->status){
            'pending_payment'  => "To'lov kutilmoqda",
            'paid'             => "To'landi",
            'active'           => 'Faol',
            'sent'             => 'Yuborildi',
            'used'             => 'Ishlatildi',
            'cancelled'        => 'Bekor qilindi',
            'payment_cancelled'=> "To'lovsiz bekor",
            default            => $c->status,   // ← 'default' emas, default keyword
          };
        @endphp
        <tr>
          <td>
            <code style="font-family:'JetBrains Mono',monospace;font-size:12px;
                         font-weight:600;color:var(--p-accent);
                         background:var(--p-accent-d);padding:2px 8px;border-radius:5px">
              {{ $c->code }}
            </code>
          </td>

          <td>
            @if($c->buyer)
            <a href="{{ route('panel.users.show',$c->buyer_user_id) }}"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              {{ $c->buyer->name }} {{ $c->buyer->lastname }}
            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $c->buyer->phone_number }}
            </div>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>

          <td>
            @if($c->recipient)
            <a href="{{ route('panel.users.show',$c->recipient_user_id) }}"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              {{ $c->recipient->name }}
            </a>
            @elseif($c->recipient_name || $c->recipient_phone)
            <div style="font-size:12.5px;color:var(--p-muted)">
              {{ $c->recipient_name }}
            </div>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $c->recipient_phone }}
            </div>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:700;color:var(--p-success);font-size:13px">
            {{ number_format($c->nominal_uzs) }} UZS
          </td>

          <td><span class="s-pill {{ $stCls }}" style="font-size:10px">{{ $stLbl }}</span></td>

          <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace;
                     white-space:nowrap">
            {{ $c->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="flex gap-1">
              <a href="{{ route('panel.gift-certificates.show',$c) }}"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>

              @if(!in_array($c->status,['used','cancelled']))
              <form method="POST"
                    action="{{ route('panel.gift-certificates.cancel',$c) }}"
                    onsubmit="return confirm('Bekor qilinsinmi?')">
                @csrf @method('PATCH')
                <button class="btn-p danger sm"><i class="bi bi-x-lg"></i></button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-gift" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Sertifikatlar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($certs->hasPages())
  <div class="flex justify-between items-center px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $certs->firstItem() }}–{{ $certs->lastItem() }} / {{ $certs->total() }}
    </div>
    {{ $certs->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection