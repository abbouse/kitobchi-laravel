@extends('panel.layouts.panel')
@section('title', 'Sotuvchi buyurtmasi #'.$sellerOrder->id)
@section('page-title', 'Buyurtma #'.$sellerOrder->id)

@section('content')

<x-panel.page-header back-href="{{ route('panel.seller-orders.index') }}">
  <x-slot name="heading">Buyurtma #{{ $sellerOrder->id }}</x-slot>
  <x-slot name="meta">{{ $sellerOrder->created_at?->format('d.m.Y H:i') }}</x-slot>
</x-panel.page-header>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  {{-- Chap: mahsulotlar + manzil --}}
  <div class="xl:col-span-8">

    {{-- Mahsulotlar --}}
    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Buyurtma mahsulotlari</div>
        <div class="dash-card-sub">{{ $sellerOrder->items?->count() ?? 0 }} ta pozitsiya</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>Mahsulot</th><th>Tur</th><th>Narx</th><th>Miqdor</th><th>Jami</th></tr>
            </thead>
            <tbody>
              @forelse($sellerOrder->items ?? [] as $item)
              <tr>
                <td>
                  <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                    {{ $item->product?->name ?? 'ID: '.$item->product_id }}
                  </div>
                  @if($item->variant_id)
                    <div style="font-size:11px;color:var(--p-hint)">Variant #{{ $item->variant_id }}</div>
                  @endif
                </td>
                <td>
                  <span class="s-pill {{ $item->type==='book'?'accent':'info' }}">
                    {{ $item->type === 'book' ? 'Kitob' : 'Kantselyariya' }}
                  </span>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--p-muted)">
                  {{ number_format($item->price) }} UZS
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px">{{ $item->quantity }}</td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                  {{ number_format($item->price * $item->quantity) }} UZS
                </td>
              </tr>
              @empty
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">Mahsulotlar yo'q</td></tr>
              @endforelse
            </tbody>
            @if($sellerOrder->items?->count())
            <tfoot>
              <tr style="border-top:2px solid var(--p-border2)">
                <td colspan="4" style="text-align:right;font-weight:600;color:var(--p-muted);font-size:13px">Jami:</td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;color:var(--p-success)">
                  {{ number_format($sellerOrder->amount) }} UZS
                </td>
              </tr>
            </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>

    {{-- Manzil --}}
    @if($sellerOrder->address)
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Yetkazish manzili</div></div>
      <div class="dash-card-body">
        @php $addr = is_array($sellerOrder->address) ? $sellerOrder->address : json_decode($sellerOrder->address, true); @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach([
            ['To\'liq manzil', data_get($addr,'fullAddress') ?? data_get($addr,'address')],
            ['Viloyat/Shahar', data_get($addr,'city') ?? data_get($addr,'region')],
            ['Koordinat',     data_get($addr,'lat') && data_get($addr,'lon') ? data_get($addr,'lat').', '.data_get($addr,'lon') : null],
          ] as [$k,$v])
          @if($v)
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">{{ $k }}</div>
            <div style="font-size:13px;color:var(--p-text)">{{ $v }}</div>
          </div>
          @endif
          @endforeach
        </div>
      </div>
    </div>
    @endif

  </div>

  {{-- O'ng: status + shaxslar --}}
  <div class="xl:col-span-4">

    {{-- Status boshqaruv --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Status</div></div>
      <div class="dash-card-body">
        @php $st = $statuses[$sellerOrder->status] ?? ['label'=>$sellerOrder->status,'class'=>'ob-p']; @endphp
        <div class="mb-3">
          <span class="o-badge {{ $st['class'] }}" style="font-size:13px;padding:6px 14px">
            {{ $st['label'] }}
          </span>
        </div>
        <form method="POST" action="{{ route('panel.seller-orders.status', $sellerOrder) }}">
          @csrf @method('PATCH')
          <label class="p-form-label">Statusni o'zgartirish</label>
          <div class="flex gap-2 mt-1">
            <select name="status" class="p-form-control flex-fill">
              @foreach($statuses as $k => $s)
              <option value="{{ $k }}" {{ $sellerOrder->status == $k ? 'selected' : '' }}>
                {{ $s['label'] }}
              </option>
              @endforeach
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
      </div>
    </div>

    {{-- Sotuvchi --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Sotuvchi</div></div>
      <div class="dash-card-body">
        @if($sellerOrder->seller)
        <div class="flex items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:var(--p-elevated);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
            @if($sellerOrder->seller->photo)
              <img src="{{ $sellerOrder->seller->photo }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <i class="bi bi-shop" style="color:var(--p-hint)"></i>
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">{{ $sellerOrder->seller->shop_name }}</div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $sellerOrder->seller->phone_number }}</div>
          </div>
        </div>
        <a href="{{ route('panel.sellers.show', $sellerOrder->seller_id) }}" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        @else
          <span style="color:var(--p-hint)">ID: {{ $sellerOrder->seller_id }}</span>
        @endif
      </div>
    </div>

    {{-- Mijoz --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mijoz</div></div>
      <div class="dash-card-body">
        @if($sellerOrder->client)
        <div class="flex items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden">
            @if($sellerOrder->client->avatar)
              <img src="{{ $sellerOrder->client->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($sellerOrder->client->name ?? 'U', 0, 1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $sellerOrder->client->name }} {{ $sellerOrder->client->lastname }}
            </div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $sellerOrder->client->phone_number }}</div>
          </div>
        </div>
        <a href="{{ route('panel.users.show', $sellerOrder->client_id) }}" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        @else
          <span style="color:var(--p-hint)">ID: {{ $sellerOrder->client_id ?? '—' }}</span>
        @endif
      </div>
    </div>

    {{-- Kuryer --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Kuryer</div></div>
      <div class="dash-card-body">
        @if($sellerOrder->courier)
          <div style="font-size:14px;font-weight:600;color:var(--p-text)">
            {{ $sellerOrder->courier->first_name }} {{ $sellerOrder->courier->last_name }}
          </div>
          <div style="font-size:12px;color:var(--p-hint)">{{ $sellerOrder->courier->phone_number }}</div>
        @elseif($sellerOrder->courierName)
          <div style="font-size:13px;color:var(--p-text)">{{ $sellerOrder->courierName }}</div>
        @else
          <span style="color:var(--p-hint)">Tayinlanmagan</span>
        @endif
      </div>
    </div>

    {{-- Meta --}}
    <div class="p-card">
      <div class="dash-card-body">
        @foreach([
          ['Buyurtma #',  '#'.$sellerOrder->id],
          ['Asosiy #',    $sellerOrder->order_id ? '#'.$sellerOrder->order_id : '—'],
          ['Yetkazish',   $sellerOrder->delivery_type ?? '—'],
          ['Jami summa',  number_format($sellerOrder->amount).' UZS'],
          ['Sana',        $sellerOrder->created_at?->format('d.m.Y H:i')],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

  </div>
</div>
@endsection