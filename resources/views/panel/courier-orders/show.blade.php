@extends('panel.layouts.panel')
@section('title', 'Kuryer buyurtmasi #'.$courierOrder->id)
@section('page-title', 'Kuryer buyurtmasi #'.$courierOrder->id)

@section('content')

<x-panel.page-header back-href="{{ route('panel.courier-orders.index') }}">
  <x-slot name="heading">Kuryer buyurtmasi #{{ $courierOrder->id }}</x-slot>
  <x-slot name="meta">{{ $courierOrder->created_at?->format('d.m.Y H:i') }}</x-slot>
</x-panel.page-header>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  <div class="xl:col-span-8">

    {{-- Buyurtma items --}}
    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Mahsulotlar</div>
        <div class="dash-card-sub">{{ $courierOrder->items?->count() ?? 0 }} ta</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>Mahsulot ID</th><th>Tur</th><th>Miqdor</th><th>Narx</th><th>Sotuvchi</th></tr>
            </thead>
            <tbody>
              @forelse($courierOrder->items ?? [] as $item)
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $item->product_id }}</td>
                <td>
                  <span class="s-pill {{ $item->type==='book'?'accent':'info' }}">
                    {{ $item->type ?? 'book' }}
                  </span>
                </td>
                <td style="font-family:'JetBrains Mono',monospace">{{ $item->quantity }}</td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                  {{ number_format($item->price) }} UZS
                </td>
                <td>
                  @if($item->seller_id)
                  <a href="{{ route('panel.sellers.show', $item->seller_id) }}"
                     style="font-size:12px;color:var(--p-accent)">
                    #{{ $item->seller_id }}
                  </a>
                  @else —
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">Ma'lumot yo'q</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Moliyaviy tafsilot --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Moliyaviy tafsilot</div></div>
      <div class="dash-card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach([
            ['Buyurtma summasi',  number_format($courierOrder->amount).' UZS',        'text',    'bi-cash'],
            ['Kuryer haqi',       number_format($courierOrder->courierPrice).' UZS',   'success', 'bi-person-check'],
            ['Kuryer bonusi',     $courierOrder->courierBonus > 0 ? '+'.number_format($courierOrder->courierBonus).' UZS' : '—', 'warning', 'bi-gift'],
          ] as [$lbl, $val, $clr, $icon])
          <div class="">
            <div style="background:var(--p-elevated);border-radius:10px;padding:16px;text-align:center">
              <i class="bi {{ $icon }}" style="font-size:22px;color:var(--p-{{ $clr }});margin-bottom:8px;display:block"></i>
              <div style="font-size:18px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-{{ $clr }})">{{ $val }}</div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">{{ $lbl }}</div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>

  <div class="xl:col-span-4">

    {{-- Status --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Status</div></div>
      <div class="dash-card-body">
        @php $st = $statuses[$courierOrder->status] ?? ['label'=>$courierOrder->status,'class'=>'ob-p']; @endphp
        <div class="mb-3">
          <span class="o-badge {{ $st['class'] }}" style="font-size:13px;padding:6px 14px">{{ $st['label'] }}</span>
        </div>
        <form method="POST" action="{{ route('panel.courier-orders.status', $courierOrder) }}">
          @csrf @method('PATCH')
          <label class="p-form-label">Statusni o'zgartirish</label>
          <div class="flex gap-2 mt-1">
            <select name="status" class="p-form-control flex-fill">
              @foreach($statuses as $k => $s)
              <option value="{{ $k }}" {{ $courierOrder->status === $k ? 'selected' : '' }}>{{ $s['label'] }}</option>
              @endforeach
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
      </div>
    </div>

    {{-- Kuryer --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Kuryer</div></div>
      <div class="dash-card-body">
        @if($courierOrder->courier)
        <div class="flex items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;background:var(--p-elevated);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
            @if($courierOrder->courier->photo)
              <img src="{{ $courierOrder->courier->photo }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <i class="bi bi-bicycle" style="color:var(--p-hint)"></i>
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $courierOrder->courier->first_name }} {{ $courierOrder->courier->last_name }}
            </div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $courierOrder->courier->phone_number }}</div>
            <div style="font-size:11px;color:var(--p-hint)">{{ $courierOrder->courier->region }}</div>
          </div>
        </div>
        <a href="{{ route('panel.couriers.show', $courierOrder->courier_id) }}" class="btn-p ghost sm">
          Kuryerni ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        @else
        <form method="POST" action="{{ route('panel.courier-orders.assign', $courierOrder) }}">
          @csrf @method('PATCH')
          <label class="p-form-label">Kuryer tayinlash</label>
          <div class="flex gap-2 mt-1">
            <select name="courier_id" class="p-form-control flex-fill">
              <option value="">Kuryer tanlang</option>
              @foreach(\App\Models\Courier::where('status',1)->orderBy('first_name')->get() as $c)
              <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
              @endforeach
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
        @endif
      </div>
    </div>

    {{-- Mijoz --}}
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mijoz</div></div>
      <div class="dash-card-body">
        @if($courierOrder->user)
        <div class="flex items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;overflow:hidden">
            @if($courierOrder->user->avatar)
              <img src="{{ $courierOrder->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($courierOrder->user->name??'U',0,1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $courierOrder->user->name }} {{ $courierOrder->user->lastname }}
            </div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $courierOrder->user->phone_number }}</div>
          </div>
        </div>
        <a href="{{ route('panel.users.show', $courierOrder->user_id) }}" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        @else
          <span style="color:var(--p-hint)">ID: {{ $courierOrder->user_id ?? '—' }}</span>
        @endif
      </div>
    </div>

    {{-- Meta --}}
    <div class="p-card">
      <div class="dash-card-body">
        @foreach([
          ['ID',         '#'.$courierOrder->id],
          ['Buyurtma #', $courierOrder->order_id ? '#'.$courierOrder->order_id : '—'],
          ['Yaratildi',  $courierOrder->created_at?->format('d.m.Y H:i')],
          ['Yangilandi', $courierOrder->updated_at?->format('d.m.Y H:i')],
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