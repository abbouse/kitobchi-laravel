@extends('panel.layouts.panel')
@section('title', 'Sotuvchi buyurtmalari')
@section('page-title', 'Sotuvchi buyurtmalari')

@section('content')

{{-- Tabs --}}
<div class="tab-pills mb-3">
  <a href="{{ request()->fullUrlWithQuery(['tab'=>'all','page'=>1]) }}"
     class="tab-pill {{ $tab==='all'?'active':'' }}">
    Barchasi <span class="tab-badge">{{ $counts['all'] }}</span>
  </a>
  @foreach($statuses as $key => $s)
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
     class="tab-pill {{ $tab==$key?'active':'' }}">
    {{ $s['label'] }} <span class="tab-badge">{{ $counts[$key] ?? 0 }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="ID, buyurtma ID, sotuvchi..."
           value="{{ request('search') }}" style="width:220px">
    <input type="date" name="date_from" class="p-form-control" value="{{ request('date_from') }}" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="{{ request('date_to') }}"   style="width:145px">
    <button class="btn-p" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="{{ route('panel.seller-orders.index',['tab'=>$tab]) }}" class="btn-p ghost">
      <i class="bi bi-x"></i>
    </a>
  </form>
</div>

{{-- Table --}}
<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table" style="min-width:800px">
      <thead>
        <tr>
          <th>#</th>
          <th>Asosiy #</th>
          <th>Sotuvchi</th>
          <th>Mijoz</th>
          <th>Kuryer</th>
          <th>Summa</th>
          <th>Yetkazish</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $order)
        @php
          $st = $statuses[$order->status] ?? ['label'=>$order->status,'class'=>'ob-p'];
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $order->id }}</td>
          <td>
            @if($order->order_id)
            <a href="{{ route('panel.orders.show', $order->order_id) }}"
               style="font-family:'JetBrains Mono',monospace;color:var(--p-info);font-size:12px">
              #{{ $order->order_id }}
            </a>
            @else —
            @endif
          </td>
          <td>
            @if($order->seller)
            <a href="{{ route('panel.sellers.show', $order->seller_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              {{ Str::limit($order->seller->shop_name ?? $order->seller_id, 20) }}
            </a>
            @else
              <span style="color:var(--p-hint)">ID: {{ $order->seller_id }}</span>
            @endif
          </td>
          <td>
            @if($order->client)
            <a href="{{ route('panel.users.show', $order->client_id) }}"
               style="font-size:13px;color:var(--p-text)">
              {{ $order->client->name }} {{ $order->client->lastname }}
            </a>
            @else
              <span style="color:var(--p-hint)">{{ $order->client_id ?? '—' }}</span>
            @endif
          </td>
          <td style="font-size:12px;color:var(--p-muted)">
            {{ $order->courierName ?? ($order->courier ? $order->courier->first_name.' '.$order->courier->last_name : '—') }}
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
            {{ number_format($order->amount) }}
            <span style="font-size:10px;color:var(--p-hint)">UZS</span>
          </td>
          <td style="font-size:11px;color:var(--p-hint);max-width:120px">
            {{ Str::limit($order->delivery_type ?? '—', 18) }}
          </td>
          <td>
            <form method="POST" action="{{ route('panel.seller-orders.status', $order) }}">
              @csrf @method('PATCH')
              <select name="status" class="p-form-control" style="width:150px;font-size:12px;padding:5px 8px"
                      onchange="this.form.submit()">
                @foreach($statuses as $k => $s)
                <option value="{{ $k }}" {{ $order->status == $k ? 'selected' : '' }}>
                  {{ $s['label'] }}
                </option>
                @endforeach
              </select>
            </form>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
            {{ $order->created_at?->format('d.m H:i') }}
          </td>
          <td>
            <a href="{{ route('panel.seller-orders.show', $order) }}" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-bag-x" style="font-size:32px;display:block;margin-bottom:8px"></i>
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