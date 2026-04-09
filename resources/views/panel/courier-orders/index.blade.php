@extends('panel.layouts.panel')
@section('title', 'Kuryer buyurtmalari')
@section('page-title', 'Kuryer buyurtmalari')

@section('content')

{{-- Tabs --}}
<div class="tab-pills mb-3">
  <a href="{{ request()->fullUrlWithQuery(['tab'=>'all','page'=>1]) }}"
     class="tab-pill {{ $tab==='all'?'active':'' }}">
    Barchasi <span class="tab-badge">{{ $counts['all'] }}</span>
  </a>
  @foreach($statuses as $key => $s)
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
     class="tab-pill {{ $tab===$key?'active':'' }}">
    {{ $s['label'] }} <span class="tab-badge">{{ $counts[$key] ?? 0 }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="ID, buyurtma, kuryer..."
           value="{{ request('search') }}" style="width:200px">
    <select name="courier_id" class="p-form-control" style="width:180px">
      <option value="">Barcha kuryerlar</option>
      @foreach($couriers as $c)
      <option value="{{ $c->id }}" {{ request('courier_id')==$c->id?'selected':'' }}>
        {{ $c->first_name }} {{ $c->last_name }}
      </option>
      @endforeach
    </select>
    <input type="date" name="date_from" class="p-form-control" value="{{ request('date_from') }}" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="{{ request('date_to') }}"   style="width:145px">
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.courier-orders.index',['tab'=>$tab]) }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

{{-- Table --}}
<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table" style="min-width:780px">
      <thead>
        <tr>
          <th>#</th>
          <th>Asosiy #</th>
          <th>Kuryer</th>
          <th>Mijoz</th>
          <th>Summa</th>
          <th>Kuryer haq</th>
          <th>Bonus</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $order)
        @php $st = $statuses[$order->status] ?? ['label'=>$order->status,'class'=>'ob-p']; @endphp
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $order->id }}</td>
          <td>
            @if($order->order_id)
            <a href="{{ route('panel.orders.show', $order->order_id) }}"
               style="font-family:'DM Mono',monospace;color:var(--p-info);font-size:12px">
              #{{ $order->order_id }}
            </a>
            @else —
            @endif
          </td>
          <td>
            @if($order->courier)
            <a href="{{ route('panel.couriers.show', $order->courier_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              {{ $order->courier->first_name }} {{ $order->courier->last_name }}
            </a>
            @else
              <span style="color:var(--p-hint)">{{ $order->courier_id ?? '—' }}</span>
            @endif
          </td>
          <td>
            @if($order->user)
            <a href="{{ route('panel.users.show', $order->user_id) }}"
               style="font-size:13px;color:var(--p-text)">
              {{ $order->user->name }} {{ $order->user->lastname }}
            </a>
            @else
              <span style="color:var(--p-hint)">{{ $order->user_id ?? '—' }}</span>
            @endif
          </td>
          <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-text)">
            {{ number_format($order->amount) }}
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-success)">
            {{ number_format($order->courierPrice) }}
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-warning)">
            {{ $order->courierBonus > 0 ? '+'.number_format($order->courierBonus) : '—' }}
          </td>
          <td><span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span></td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">{{ $order->created_at?->format('d.m H:i') }}</td>
          <td>
            <a href="{{ route('panel.courier-orders.show', $order) }}" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-bicycle" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Buyurtmalar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())
  <div class="p-pagination">{{ $orders->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection