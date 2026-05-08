@extends('a122.layouts.admin')
@section('title', 'Buyurtmalar')
@section('page-title', 'Buyurtmalar')

@section('content')
<div>
  <x-a122.page-header>
    <x-slot name="heading">Buyurtmalar</x-slot>
    <x-slot name="meta">{{ $orders->total() }} ta buyurtma yozuvi topildi.</x-slot>
  </x-a122.page-header>

  <div class="a122-index-header">
    <div>
      <div class="a122-index-header__title">Filter va qidiruv</div>
      <div class="a122-index-header__meta">Buyurtmalarni ID, mijoz yoki status bo‘yicha filtrlash mumkin.</div>
    </div>
    <div class="a122-index-header__actions">
      <form method="GET" class="a122-index-search-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <i data-lucide="search" class="w-4 h-4"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ID, mijoz, holat..." class="a122-index-search-input">
      </form>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
    @foreach([
      'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
      'shipped' => ['Yo‘lda', $counts['shipped'] ?? 0],
      'paid' => ['Yakunlangan', $counts['paid'] ?? 0],
      'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
      'all' => ['Barchasi', $counts['all'] ?? 0],
    ] as $key => [$label, $count])
      <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
        {{ $label }} <span>{{ $count }}</span>
      </a>
    @endforeach
  </div>
  <div class="hidden">
    @forelse($orders as $order)
      @php
        $statusLabel = match ((string) ($order->status_code ?? $order->status)) {
          'A', 'pending' => 'Kutilmoqda',
          'P', 'packing' => 'Qadoqlanmoqda',
          'B', 'in_delivery' => "Yo'lda",
          'C', 'delivered' => 'Yakunlangan',
          'returned' => 'Qaytgan',
          'F', 'cancelled' => 'Bekor qilingan',
          default => 'Kutilmoqda',
        };
        $paymentLabel = match ((string) ($order->payment_status_code ?? $order->paymentStatus)) {
          '2', 'paid' => "To'langan",
          '1', 'card_pending', 'pending' => 'Karta kutilmoqda',
          '0', 'cash_pending' => 'Naqd kutilmoqda',
          '3', 'cancelled', 'rejected' => "To'lov bekor qilingan",
          default => 'Noma’lum',
        };
      @endphp
      <div class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="font-semibold">#ORD-{{ $order->id }}</div>
            <div class="text-xs text-gray-500">{{ trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) }}</div>
          </div>
          <span class="badge {{ $statusLabel === 'Yakunlangan' ? 'badge-success' : (in_array($statusLabel, ['Kutilmoqda','Qadoqlanmoqda']) ? 'badge-warning' : (in_array($statusLabel, ["Yo'lda",'Qaytgan']) ? 'badge-info' : 'badge-danger')) }}">{{ $statusLabel }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
          <div><div class="text-xs text-gray-500">Tovarlar</div><div>{{ (int) collect($order->items ?? [])->sum('count_item') }} ta</div></div>
          <div><div class="text-xs text-gray-500">Summa</div><div>{{ number_format((float) $order->amount, 0) }} UZS</div></div>
          <div><div class="text-xs text-gray-500">To'lov</div><div>{{ $paymentLabel }}</div></div>
          <div><div class="text-xs text-gray-500">Sana</div><div>{{ optional($order->created_at)->format('Y-m-d') }}</div></div>
        </div>
        <div class="mt-4 flex justify-end">
          <a href="{{ route('admin.orders.show', $order) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
        </div>
      </div>
    @empty
      <div class="card p-6 text-sm text-gray-500">Buyurtmalar topilmadi.</div>
    @endforelse
  </div>

  <div class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Buyurtmalar jadvali</div>
        <div class="a122-section-head__meta">Statusni shu jadvalning o‘zidan boshqarish va buyurtma tafsilotiga tez o‘tish mumkin.</div>
      </div>
    </div>
    <div class="a122-section-body">
    <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Buyurtma</th><th>Mijoz</th><th>Tovarlar</th><th>Summa</th><th>To'lov</th><th>Holat</th><th>Sana</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          @forelse($orders as $order)
            @php
              $statusLabel = match ((string) ($order->status_code ?? $order->status)) {
                'A', 'pending' => 'Kutilmoqda',
                'P', 'packing' => 'Qadoqlanmoqda',
                'B', 'in_delivery' => "Yo'lda",
                'C', 'delivered' => 'Yakunlangan',
                'returned' => 'Qaytgan',
                'F', 'cancelled' => 'Bekor qilingan',
                default => 'Kutilmoqda',
              };
              $paymentLabel = match ((string) ($order->payment_status_code ?? $order->paymentStatus)) {
                '2', 'paid' => "To'langan",
                '1', 'card_pending', 'pending' => 'Karta kutilmoqda',
                '0', 'cash_pending' => 'Naqd kutilmoqda',
                '3', 'cancelled', 'rejected' => "To'lov bekor qilingan",
                default => 'Noma’lum',
              };
            @endphp
            <tr>
              <td><span class="font-semibold">#ORD-{{ $order->id }}</span></td>
              <td>{{ trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) }}</td>
              <td>{{ (int) collect($order->items ?? [])->sum('count_item') }}</td>
              <td>{{ number_format((float) $order->amount, 0) }} UZS</td>
              <td><span class="badge badge-muted">{{ $paymentLabel }}</span></td>
              <td>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="inline-flex">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                    <option value="A" @selected(in_array(($order->status_code ?? $order->status), ['A','pending'], true))>Kutilmoqda</option>
                    <option value="P" @selected(in_array(($order->status_code ?? $order->status), ['P','packing'], true))>Qadoqlanmoqda</option>
                    <option value="B" @selected(in_array(($order->status_code ?? $order->status), ['B','in_delivery'], true))>Yo'lda</option>
                    <option value="C" @selected(in_array(($order->status_code ?? $order->status), ['C','delivered'], true))>Yetkazildi</option>
                    <option value="F" @selected(in_array(($order->status_code ?? $order->status), ['F','cancelled','returned'], true))>Bekor qilingan</option>
                  </select>
                </form>
              </td>
              <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
              <td>
                <div class="flex items-center justify-end gap-1 flex-wrap">
                  <a href="{{ route('admin.orders.show', $order) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  </div>
  </div>
</div>
@if(isset($orders) && method_exists($orders, 'links'))
  <div class="mt-4">{{ $orders->links('a122.partials.pagination') }}</div>
@endif
@endsection
