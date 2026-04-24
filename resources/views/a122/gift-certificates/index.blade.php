@extends('a122.layouts.admin')
@section('title', 'Gift Sertifikatlar')
@section('page-title', 'Gift Sertifikatlar')

@section('content')

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Gift sertifikatlar</div>
    <div class="a122-index-header__meta">{{ $certs->total() }} ta sertifikat topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Kod, telefon yoki ism bo'yicha qidiring">
    </form>
  </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  @foreach([
    ['Jami',        $counts['all'],             'accent',  'bi-gift'],
    ['Faol',        $counts['active'],          'info',    'bi-send'],
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
    'active'          => ['Faol',             $counts['active']],
    'used'            => ['Ishlatilgan',      $counts['used']],
    'cancelled'       => ['Bekor qilingan',   $counts['cancelled']],
  ] as $k => [$l, $c])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
     class="tab-pill {{ $tab===$k?'active':'' }}">
    {{ $l }} <span class="tab-count">{{ $c }}</span>
  </a>
  @endforeach
</div>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
            'sent'             => 'success',
            'cancelled'        => 'danger',
            'payment_cancelled'=> 'danger',
            'pending_payment'  => 'warning',
            default            => 'warning',
          };
          $stLbl = match($c->status){
            'pending_payment'  => "To'lov kutilmoqda",
            'paid'             => "To'landi",
            'active'           => 'Faol',
            'sent'             => 'Faol',
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
            <a href="{{ route('admin.users.show',$c->buyer_user_id) }}"
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
            <a href="{{ route('admin.users.show',$c->recipient_user_id) }}"
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
              <a href="{{ route('admin.gift-certificates.show',$c) }}"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>

              @if(!in_array($c->status,['used','cancelled']))
              <form method="POST"
                    action="{{ route('admin.gift-certificates.cancel',$c) }}"
                    onsubmit="return confirm('Bekor qilinsinmi?')">
                @csrf
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
    {{ $certs->links('a122.partials.pagination') }}
  </div>
  @endif
</div>

@endsection
