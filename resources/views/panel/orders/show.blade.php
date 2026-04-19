@extends('panel.layouts.panel')
@section('title','Buyurtma #'.$order->id)
@section('page-title','Buyurtma #'.$order->id)
@section('breadcrumb','Panel / Buyurtmalar / #'.$order->id)

@section('content')

@php
  $statusMap = [
    'A' => ['warning', 'Kutilmoqda'],
    'P' => ['muted',   'Qadoqlanmoqda'],
    'B' => ['info',    "Yo'lda"],
    'C' => ['success', 'Yetkazildi'],
    'F' => ['danger',  'Bekor qilindi'],
  ];
  $st = $statusMap[$order->status] ?? ['muted', $order->status];

  $payMap = [
    '0' => ['muted',   'Naqd (qabul qilinganida)'],
    '1' => ['info',    'Karta (kutilmoqda)'],
    '2' => ['success', "To'langan ✓"],
    '3' => ['danger',  'Rad etildi'],
  ];
  $pay = $payMap[(string)$order->paymentStatus] ?? ['muted', '—'];

  $isCancelled    = $order->status === 'F';
  $isGiftToOther  = (bool)($order->is_gift_to_other ?? false);
  $withPackaging  = (bool)($order->with_packaging   ?? false);
  $packagingPrice = (int)($order->packaging_price    ?? 0);

  $itemsSubtotal  = collect($items)->where('type','!=','gift')
                      ->sum(fn($i) => ($i['item_price'] ?? 0) * ($i['count_item'] ?? 1));
  $deliveryPrice  = (int)($order->deliveryPrice  ?? 0);
  $discountAmount = (int)($order->discountAmount ?? 0);
  $cashbackAmount = (int)($order->cashbackAmount ?? 0);
  $certAmount     = (int)($order->giftCertAmount ?? 0);
@endphp

{{-- ── Page header ──────────────────────────────────────────────── --}}
<x-panel.page-header back-href="{{ route('panel.orders.index') }}">
  <x-slot name="heading">
    <div class="flex flex-wrap items-center gap-2">
      <h1 class="page-title mb-0">Buyurtma #{{ $order->id }}</h1>
      <span class="s-pill {{ $st[0] }}">{{ $st[1] }}</span>
      @if($isGiftToOther)
        <span class="s-pill info" style="font-size:11px">👤 Boshqasiga sovg'a</span>
      @endif
      @if($withPackaging)
        <span class="s-pill muted" style="font-size:11px">📦 Qadoqlangan</span>
      @endif
    </div>
  </x-slot>
  <x-slot name="meta">
    <p class="page-sub mt-1">
      {{ $order->created_at?->format('d.m.Y H:i') }}
      · {{ $order->deliveryType }}
      @if($order->user)
        · {{ $order->user->name }} {{ $order->user->lastname }}
      @endif
    </p>
  </x-slot>
  <x-slot name="actions">
    <div class="flex flex-wrap gap-2">
      @if(!in_array($order->status,['C','F']))
      <form method="POST" action="{{ route('panel.orders.cancel', $order) }}"
            onsubmit="return confirm('Buyurtmani bekor qilasizmi?')">
        @csrf @method('PATCH')
        <button class="btn-p danger ghost" style="gap:6px">
          <i class="bi bi-x-circle"></i> Bekor qilish
        </button>
      </form>
      @endif
      <div class="dropdown">
        <button class="btn-p ghost" data-bs-toggle="dropdown" style="gap:8px">
          <i class="bi bi-pencil-square" style="font-size:13px"></i> Status
          <i class="bi bi-chevron-down" style="font-size:10px"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end"
            style="background:var(--p-surface);border:1px solid var(--p-border);
                   border-radius:10px;min-width:170px;padding:6px">
          @foreach(['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor'] as $val=>$lbl)
          <li>
            <form method="POST" action="{{ route('panel.orders.status', $order) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="status" value="{{ $val }}">
              <button type="submit" class="dropdown-item"
                      style="color:{{ $val===$order->status?'var(--p-accent)':'var(--p-text)' }};
                             font-size:13px;padding:8px 14px;border-radius:6px;
                             background:{{ $val===$order->status?'var(--p-elevated)':'transparent' }}">
                {{ $val===$order->status?'● ':'○ ' }}{{ $lbl }}
              </button>
            </form>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </x-slot>
</x-panel.page-header>

{{-- ── Alert: bekor qilingan ─────────────────────────────────────── --}}
@if($isCancelled)
<div class="fade-up mb-3"
     style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);
            border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px">
  <i class="bi bi-exclamation-triangle-fill" style="color:var(--p-danger);font-size:18px"></i>
  <div>
    <div style="font-size:13px;font-weight:600;color:var(--p-danger)">Buyurtma bekor qilingan</div>
    <div style="font-size:12px;color:var(--p-muted);margin-top:2px">
      Barcha chegirmalar, cashback va sertifikatlar qaytarilgan
    </div>
  </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

{{-- ════════════════════════════════════════════════════
     CHAP USTUN
     ════════════════════════════════════════════════════ --}}
<div class="xl:col-span-8">

  {{-- ── Mahsulotlar ─────────────────────────────────────── --}}
  <div class="p-card mb-3 fade-up d1">
    <div class="p-card-header">
      <div>
        <div class="p-card-title">Buyurtma tarkibi</div>
        <div class="p-card-sub">
          {{ collect($items)->where('type','!=','gift')->sum('count_item') }} ta mahsulot
        </div>
      </div>
    </div>

    <div class="table-responsive kc-twrap">
      <table class="p-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mahsulot</th>
            <th>Do'kon</th>
            <th style="text-align:right">Narxi</th>
            <th style="text-align:center">Soni</th>
            <th style="text-align:right">Jami</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $i => $item)
          @php
            $isGiftItem = ($item['type'] ?? '') === 'gift';
            $cover = $item['cover'] ?? null;
            $rowPrice = ($item['item_price'] ?? 0) * ($item['count_item'] ?? 1);
          @endphp
          <tr style="{{ $isGiftItem ? 'background:rgba(245,166,35,.04)' : '' }}">
            <td style="color:var(--p-hint);font-size:12px">{{ $i + 1 }}</td>

            <td>
              <div class="flex items-center gap-3">
                {{-- Rasm --}}
                <div style="width:42px;height:56px;border-radius:6px;overflow:hidden;
                            background:var(--p-elevated);flex-shrink:0;
                            display:flex;align-items:center;justify-content:center">
                  @if($cover)
                    <img src="{{ asset('storage/'.$cover) }}"
                         style="width:100%;height:100%;object-fit:cover" alt="">
                  @elseif($isGiftItem)
                    <span style="font-size:22px">🎁</span>
                  @else
                    <i class="bi bi-book" style="color:var(--p-hint);font-size:16px"></i>
                  @endif
                </div>
                {{-- Info --}}
                <div>
                  <div style="font-size:13px;font-weight:600;color:var(--p-text);
                              max-width:220px;white-space:nowrap;overflow:hidden;
                              text-overflow:ellipsis">
                    {{ $item['name'] ?? '—' }}
                  </div>
                  @if($item['author'] ?? null)
                  <div style="font-size:11px;color:var(--p-hint)">
                    {{ $item['author'] }}
                  </div>
                  @endif
                  @if($item['material'] ?? null)
                  <div style="font-size:11px;color:var(--p-hint)">
                    {{ $item['material'] }}
                  </div>
                  @endif
                  @if($item['color_name'] ?? null)
                  <div style="font-size:11px;color:var(--p-hint)">
                    <i class="bi bi-circle-fill" style="font-size:8px"></i>
                    {{ $item['color_name'] }}
                  </div>
                  @endif
                  <div style="font-size:10px;color:var(--p-border);
                              font-family:'JetBrains Mono',monospace;margin-top:2px">
                    ID: {{ $item['item_id'] ?? '—' }}
                    · {{ strtoupper($item['type'] ?? 'book') }}
                    @if($isGiftItem)
                      <span class="s-pill accent" style="font-size:9px;padding:1px 5px">SOVG'A</span>
                    @endif
                  </div>
                </div>
              </div>
            </td>

            <td>
              @if(($item['product'] ?? null)?->seller ?? null)
              <div style="font-size:12px;font-weight:500;color:var(--p-text)">
                {{ $item['product']->seller->shop_name }}
              </div>
              @else
              <span style="color:var(--p-hint);font-size:12px">—</span>
              @endif
            </td>

            <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                       font-size:13px;font-weight:500;color:var(--p-text)">
              @if($isGiftItem)
                <span class="s-pill success" style="font-size:11px">Bepul</span>
              @else
                {{ number_format($item['item_price'] ?? 0) }}
              @endif
            </td>

            <td style="text-align:center;font-family:'JetBrains Mono',monospace;
                       font-size:13px;color:var(--p-muted)">
              {{ $item['count_item'] ?? 1 }}
            </td>

            <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                       font-size:14px;font-weight:700;color:var(--p-text)">
              @if(!$isGiftItem)
                {{ number_format($rowPrice) }}
              @else
                —
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- ── Narx xulosasi ───────────────────────────────── --}}
    <div style="padding:16px;background:var(--p-elevated);border-radius:0 0 12px 12px;
                border-top:1px solid var(--p-border)">

      @php
        $rows = [
          ['label' => 'Mahsulotlar jami', 'value' => $itemsSubtotal, 'sign' => '', 'color' => 'var(--p-text)'],
        ];
        if($deliveryPrice > 0)
          $rows[] = ['label'=>'Yetkazib berish','value'=>$deliveryPrice,'sign'=>'+','color'=>'var(--p-muted)'];
        else
          $rows[] = ['label'=>'Yetkazib berish','value'=>null,'sign'=>'','color'=>'var(--p-success)','free'=>true];

        if($withPackaging && $packagingPrice > 0)
          $rows[] = ['label'=>'Qadoqlash 📦','value'=>$packagingPrice,'sign'=>'+','color'=>'var(--p-muted)'];

        if($discountAmount > 0)
          $rows[] = ['label'=>'Promokod '.$order->promocode,'value'=>$discountAmount,'sign'=>'-','color'=>'var(--p-success)'];

        if($cashbackAmount > 0)
          $rows[] = ['label'=>'Cashback','value'=>$cashbackAmount,'sign'=>'-','color'=>'var(--p-info)'];

        if($certAmount > 0)
          $rows[] = ['label'=>'Gift sertifikat #'.$order->gift_certificate_id,'value'=>$certAmount,'sign'=>'-','color'=>'var(--p-warning)'];
      @endphp

      @foreach($rows as $row)
      <div class="flex justify-between items-center mb-2"
           style="font-size:13px">
        <span style="color:var(--p-muted)">
          {{ $row['label'] }}
          @if($isCancelled && in_array($row['sign'],['-']))
            <span class="s-pill warning" style="font-size:10px;margin-left:4px">qaytarildi</span>
          @endif
        </span>
        <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:{{ $row['color'] }}">
          @if($row['free'] ?? false)
            <span class="s-pill success" style="font-size:11px">Bepul</span>
          @else
            {{ $row['sign'] }} {{ number_format($row['value']) }} UZS
          @endif
        </span>
      </div>
      @endforeach

      {{-- Total line --}}
      <div style="border-top:2px solid var(--p-border);padding-top:12px;margin-top:8px"
           class="flex justify-between items-center">
        <span style="font-size:15px;font-weight:700;color:var(--p-text)">Umumiy to'lov</span>
        <span style="font-size:22px;font-weight:800;font-family:'JetBrains Mono',monospace;
                     color:{{ $isCancelled ? 'var(--p-danger)' : 'var(--p-accent)' }}">
          {{ number_format($order->amount) }}
          <span style="font-size:13px;font-weight:500;color:var(--p-hint)">UZS</span>
        </span>
      </div>
    </div>
  </div>

  {{-- ── Yetkazish manzili ──────────────────────────────── --}}
  @if($order->address)
  @php
    $rawAddr = $order->address;
    if(is_string($rawAddr)) $rawAddr = json_decode($rawAddr, true) ?? [];
    $addr = (isset($rawAddr[0]) && is_array($rawAddr[0])) ? $rawAddr[0] : $rawAddr;
  @endphp
  <div class="p-card mb-3 fade-up d2">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-geo-alt mr-1" style="color:var(--p-accent)"></i>
        Yetkazish manzili
      </div>
      @if(($addr['lat'] ?? null) && ($addr['lon'] ?? null))
      <a href="https://maps.yandex.uz/?text={{ $addr['lat'] }}+{{ $addr['lon'] }}&z=16"
         target="_blank" class="btn-p ghost sm">
        <i class="bi bi-map"></i> Xaritada
      </a>
      @endif
    </div>
    <div style="padding:4px 0">
      @php
        $addrRows = [
          ['icon'=>'bi-house',          'label'=>'Manzil',         'value'=>$addr['fullAddress'] ?? null],
          ['icon'=>'bi-person',         'label'=>'Qabul qiluvchi', 'value'=>$addr['fullName']    ?? null],
          ['icon'=>'bi-telephone',      'label'=>'Telefon',        'value'=>$addr['phoneNumber'] ?? null, 'link'=>'tel'],
        ];
      @endphp
      @foreach($addrRows as $r)
      @if($r['value'])
      <div class="flex items-start gap-3"
           style="padding:10px 0;border-bottom:1px solid var(--p-border)">
        <div style="width:32px;height:32px;border-radius:8px;background:var(--p-elevated);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi {{ $r['icon'] }}" style="font-size:14px;color:var(--p-accent)"></i>
        </div>
        <div>
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:2px">{{ $r['label'] }}</div>
          @if(isset($r['link']) && $r['link']==='tel')
            <a href="tel:{{ $r['value'] }}"
               style="font-size:14px;font-weight:600;color:var(--p-accent);
                      text-decoration:none;font-family:'JetBrains Mono',monospace">
              {{ $r['value'] }}
            </a>
          @else
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $r['value'] }}
            </div>
          @endif
        </div>
      </div>
      @endif
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Qabul qiluvchi (boshqasiga sovg'a) ────────────────── --}}
  @if($isGiftToOther)
  <div class="p-card fade-up d3"
       style="border:1px solid rgba(59,130,246,.25);
              background:linear-gradient(135deg,rgba(59,130,246,.04),transparent)">
    <div class="p-card-header">
      <div class="p-card-title" style="color:var(--p-info)">
        <i class="bi bi-gift mr-1"></i> Qabul qiluvchi ma'lumotlari
      </div>
      <span class="s-pill info" style="font-size:11px">Boshqasiga sovg'a</span>
    </div>
    <div style="padding:4px 0">
      @php
        $recipientRows = [
          ['icon'=>'bi-person-fill',    'label'=>'Ism',     'value'=>$order->recipient_name    ?? null],
          ['icon'=>'bi-telephone-fill', 'label'=>'Telefon', 'value'=>$order->recipient_phone   ?? null, 'link'=>'tel'],
          ['icon'=>'bi-geo-alt-fill',   'label'=>'Viloyat', 'value'=>$order->recipient_region  ?? null],
          ['icon'=>'bi-house-fill',     'label'=>'Manzil',  'value'=>$order->recipient_address ?? null],
        ];
      @endphp
      @foreach($recipientRows as $r)
      @if($r['value'])
      <div class="flex items-center gap-3"
           style="padding:10px 0;border-bottom:1px solid var(--p-border)">
        <div style="width:32px;height:32px;border-radius:8px;
                    background:rgba(59,130,246,.1);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi {{ $r['icon'] }}" style="font-size:13px;color:var(--p-info)"></i>
        </div>
        <div>
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:1px">{{ $r['label'] }}</div>
          @if(isset($r['link']) && $r['link']==='tel')
            <a href="tel:{{ $r['value'] }}"
               style="font-size:14px;font-weight:600;color:var(--p-info);
                      text-decoration:none;font-family:'JetBrains Mono',monospace">
              {{ $r['value'] }}
            </a>
          @else
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $r['value'] }}
            </div>
          @endif
        </div>
      </div>
      @endif
      @endforeach
    </div>
  </div>
  @endif

</div>{{-- /col-xl-8 --}}

{{-- ════════════════════════════════════════════════════
     O'NG USTUN
     ════════════════════════════════════════════════════ --}}
<div class="xl:col-span-4">

  {{-- ── Mijoz ────────────────────────────────────────── --}}
  <div class="p-card mb-3 fade-up d1">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-person-circle mr-1" style="color:var(--p-accent)"></i> Mijoz
      </div>
      @if($order->user)
      <a href="{{ route('panel.users.show', $order->user) }}"
         class="btn-p ghost sm" style="font-size:11px">
        Profilga <i class="bi bi-arrow-right"></i>
      </a>
      @endif
    </div>
    @if($order->user)
    <div class="flex items-center gap-3">
      <div class="av av-blue"
           style="width:48px;height:48px;font-size:18px;flex-shrink:0;border-radius:14px">
        @if($order->user->avatar)
          <img src="{{ Storage::url($order->user->avatar) }}" alt=""
               style="width:100%;height:100%;object-fit:cover;border-radius:14px">
        @else
          {{ strtoupper(substr($order->user->name, 0, 1)) }}
        @endif
      </div>
      <div>
        <div style="font-size:15px;font-weight:700;color:var(--p-text)">
          {{ $order->user->name }} {{ $order->user->lastname }}
        </div>
        <div style="font-size:12px;color:var(--p-hint);
                    font-family:'JetBrains Mono',monospace;margin-top:2px">
          {{ $order->user->phone_number }}
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
          ID: {{ $order->user->id }}
        </div>
      </div>
    </div>
    @else
    <div style="color:var(--p-hint);font-size:13px;padding:8px 0">Mehmon foydalanuvchi</div>
    @endif
  </div>

  {{-- ── To'lov tafsilotlari ──────────────────────────── --}}
  <div class="p-card mb-3 fade-up d2">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-credit-card mr-1" style="color:var(--p-accent)"></i> To'lov
      </div>
      <span class="s-pill {{ $pay[0] }}" style="font-size:11px">{{ $pay[1] }}</span>
    </div>

    @php
      $infoRows = [
        ['label'=>'Buyurtma holati',   'slot'=>'status'],
        ['label'=>'To\'lov holati',    'slot'=>'payment'],
        ['label'=>'Yetkazish',         'slot'=>'delivery'],
        ['label'=>'Yetkazish narxi',   'slot'=>'delivery_price'],
        ['label'=>'Mahsulotlar jami',  'value'=>number_format($itemsSubtotal).' UZS'],
        ['label'=>'Qadoqlash 📦',      'value'=>$withPackaging ? number_format($packagingPrice).' UZS' : null, 'pill'=>'muted'],
        ['label'=>'Promokod',          'slot'=>'promo'],
        ['label'=>'Cashback',          'slot'=>'cashback'],
        ['label'=>'Gift sertifikat',   'slot'=>'cert'],
        ['label'=>'Yaratildi',         'value'=>$order->created_at?->format('d.m.Y H:i')],
        ['label'=>'Yangilandi',        'value'=>$order->updated_at?->format('d.m.Y H:i')],
      ];
    @endphp

    <div style="padding:4px 0">
      @foreach($infoRows as $row)
      @php $skip = false; @endphp

      @if(isset($row['slot']))
        @if($row['slot']==='status')
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <span class="s-pill {{ $st[0] }}" style="font-size:11px">{{ $st[1] }}</span>
          </div>
        @elseif($row['slot']==='payment')
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <span class="s-pill {{ $pay[0] }}" style="font-size:11px">{{ $pay[1] }}</span>
          </div>
        @elseif($row['slot']==='delivery')
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <span style="font-size:12px;font-weight:600;color:var(--p-text)">
              {{ $order->deliveryType ?? '—' }}
            </span>
          </div>
        @elseif($row['slot']==='delivery_price')
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            @if($deliveryPrice > 0)
              <span style="font-size:12px;font-family:'JetBrains Mono',monospace;
                           font-weight:600;color:var(--p-text)">
                {{ number_format($deliveryPrice) }} UZS
              </span>
            @else
              <span class="s-pill success" style="font-size:11px">Bepul</span>
            @endif
          </div>
        @elseif($row['slot']==='promo')
          @if($order->promocode)
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <div class="flex items-center gap-2">
              <code style="font-family:'JetBrains Mono',monospace;font-size:12px;
                           font-weight:700;color:var(--p-accent)">
                {{ $order->promocode }}
              </code>
              <span style="font-size:12px;color:var(--p-success);font-family:'JetBrains Mono',monospace">
                -{{ number_format($discountAmount) }}
              </span>
              @if($isCancelled)
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              @endif
            </div>
          </div>
          @endif
        @elseif($row['slot']==='cashback')
          @if($cashbackAmount > 0)
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <div class="flex items-center gap-2">
              <span style="font-size:12px;color:var(--p-info);
                           font-family:'JetBrains Mono',monospace;font-weight:600">
                -{{ number_format($cashbackAmount) }} UZS
              </span>
              @if($isCancelled)
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              @endif
            </div>
          </div>
          @endif
        @elseif($row['slot']==='cert')
          @if($certAmount > 0)
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
            <div class="flex items-center gap-2">
              <code style="font-family:'JetBrains Mono',monospace;font-size:11px;
                           color:var(--p-warning)">#{{ $order->gift_certificate_id }}</code>
              <span style="font-size:12px;color:var(--p-warning);
                           font-family:'JetBrains Mono',monospace;font-weight:600">
                -{{ number_format($certAmount) }}
              </span>
              @if($isCancelled)
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              @endif
            </div>
          </div>
          @endif
        @endif

      @elseif(isset($row['value']) && $row['value'] !== null)
        <div class="flex justify-between items-center"
             style="padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
          @if(isset($row['pill']))
            <span class="s-pill {{ $row['pill'] }}" style="font-size:11px">{{ $row['value'] }}</span>
          @else
            <span style="font-size:12px;font-weight:600;color:var(--p-text)">{{ $row['value'] }}</span>
          @endif
        </div>
      @endif
      @endforeach
    </div>

    {{-- Umumiy --}}
    <div style="margin-top:12px;padding:14px;background:var(--p-elevated);
                border-radius:10px;display:flex;justify-content:space-between;
                align-items:center">
      <span style="font-size:13px;font-weight:600;color:var(--p-muted)">Umumiy to'lov</span>
      <span style="font-size:20px;font-weight:800;font-family:'JetBrains Mono',monospace;
                   color:{{ $isCancelled ? 'var(--p-danger)' : 'var(--p-accent)' }}">
        {{ number_format($order->amount) }}
        <span style="font-size:12px;font-weight:500;color:var(--p-hint)">UZS</span>
      </span>
    </div>
  </div>

  {{-- ── Sovg'a ────────────────────────────────────────── --}}
  @if($order->gift && isset($orderGift) && $orderGift)
  <div class="p-card mb-3 fade-up d3">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-gift mr-1" style="color:var(--p-accent)"></i> Sovg'a
      </div>
    </div>
    <div class="flex items-center gap-3">
      <div style="width:48px;height:48px;border-radius:10px;overflow:hidden;
                  background:var(--p-elevated);display:flex;align-items:center;
                  justify-content:center;flex-shrink:0">
        @if($orderGift->images[0] ?? null)
          <img src="{{ Storage::url($orderGift->images[0]) }}"
               style="width:100%;height:100%;object-fit:cover">
        @else
          <span style="font-size:24px">🎁</span>
        @endif
      </div>
      <div>
        <div style="font-size:14px;font-weight:700;color:var(--p-text)">
          {{ $orderGift->name }}
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
          {{ $orderGift->seller?->shop_name ?? 'Kitobchi' }}
        </div>
      </div>
      <div style="margin-left:auto">
        <span class="s-pill success" style="font-size:11px">Bepul 🎉</span>
      </div>
    </div>
  </div>
  @endif

  {{-- ── Gift sertifikat ─────────────────────────────────── --}}
  @if(isset($orderCert) && $orderCert)
  <div class="p-card mb-3 fade-up d3"
       style="border:1px solid rgba(245,166,35,.3)">
    <div class="p-card-header">
      <div class="p-card-title" style="color:var(--p-warning)">
        🎟 Gift Sertifikat
      </div>
      <span class="s-pill {{ $isCancelled ? 'warning' : 'success' }}" style="font-size:11px">
        {{ $isCancelled ? 'Qaytarildi' : 'Ishlatildi' }}
      </span>
    </div>
    <div style="padding:4px 0">
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Kod</span>
        <code style="font-family:'JetBrains Mono',monospace;font-size:14px;
                     font-weight:800;color:var(--p-warning)">{{ $orderCert->code }}</code>
      </div>
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Nominal</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
                     font-weight:600;color:var(--p-text)">
          {{ number_format($orderCert->nominal_uzs) }} UZS
        </span>
      </div>
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Chegirma</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
                     font-weight:600;color:var(--p-warning)">
          -{{ number_format($certAmount) }} UZS
        </span>
      </div>
      @if($orderCert->buyer ?? null)
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Sotib olgan</span>
        <span style="font-size:12px;color:var(--p-text);font-weight:500">
          {{ $orderCert->buyer->name ?? '—' }}
        </span>
      </div>
      @endif
    </div>
  </div>
  @endif

  {{-- ── Xaridor tilagi ───────────────────────────────────── --}}
  @if($order->buyerWish)
  <div class="p-card mb-3 fade-up d4">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-chat-quote mr-1" style="color:var(--p-accent)"></i> Xaridor tilagi
      </div>
    </div>
    <div style="font-size:13px;color:var(--p-muted);font-style:italic;
                line-height:1.7;background:var(--p-elevated);border-radius:8px;
                padding:12px;border-left:3px solid var(--p-accent)">
      "{{ $order->buyerWish }}"
    </div>
  </div>
  @endif

  {{-- ── QR kod ──────────────────────────────────────────── --}}
  @if($order->qr)
  <div class="p-card fade-up d4">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-qr-code mr-1" style="color:var(--p-accent)"></i> QR kod
      </div>
      <div class="flex gap-2">
        <button onclick="toggleQr()" class="btn-p ghost sm" id="qrToggleBtn">
          <i class="bi bi-eye" id="qrEye"></i>
        </button>
        <button onclick="copyQr()" id="qrCopyBtn" class="btn-p ghost sm">
          <i class="bi bi-copy" id="qrCopyIcon"></i>
          <span id="qrCopyLabel" style="font-size:11px">Nusxa</span>
        </button>
      </div>
    </div>
    <div id="qrMasked" style="font-family:'JetBrains Mono',monospace;font-size:14px;
                              color:var(--p-muted);letter-spacing:.08em;
                              padding:8px 0">
      {{ str_repeat('•', min(20, strlen($order->qr) - 4)) }}{{ substr($order->qr,-4) }}
    </div>
    <div id="qrFull" style="display:none;padding:10px;background:var(--p-elevated);
                             border-radius:8px;font-family:'JetBrains Mono',monospace;
                             font-size:12px;color:var(--p-text);font-weight:600;
                             word-break:break-all;line-height:1.7;user-select:all">
      {{ $order->qr }}
    </div>
  </div>
  @endif

</div>{{-- /col-xl-4 --}}
</div>{{-- /row --}}

@push('scripts')
@if($order->qr)
<script>
let qrOpen = false;
const QR_VAL = '{{ addslashes($order->qr) }}';

function toggleQr() {
  qrOpen = !qrOpen;
  document.getElementById('qrMasked').style.display = qrOpen ? 'none'  : 'block';
  document.getElementById('qrFull').style.display   = qrOpen ? 'block' : 'none';
  document.getElementById('qrEye').className        = qrOpen ? 'bi bi-eye-slash' : 'bi bi-eye';
}

function copyQr() {
  navigator.clipboard.writeText(QR_VAL).then(() => {
    const icon  = document.getElementById('qrCopyIcon');
    const label = document.getElementById('qrCopyLabel');
    icon.className    = 'bi bi-check-lg';
    icon.style.color  = 'var(--p-success)';
    label.textContent = 'Nusxalandi!';
    setTimeout(() => {
      icon.className    = 'bi bi-copy';
      icon.style.color  = '';
      label.textContent = 'Nusxa';
    }, 2000);
  });
}
</script>
@endif
@endpush

@endsection