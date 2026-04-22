@extends('a122.layouts.admin')
@section('title', 'Buyurtmalar')
@section('page-title', 'Buyurtmalar')

@section('content')
<div>
  <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    <div>
      <h2 class="text-xl font-bold tracking-tight">Buyurtmalar</h2>
      <p class="text-xs text-gray-500 mt-0.5">{{ $orders->total() }} ta yozuv topildi</p>
    </div>
    <form method="GET" class="relative min-w-[220px]">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="ID, mijoz, holat..." class="input !pl-9 !py-2 w-full">
    </form>
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
        $statusLabel = match ((string) $order->status) {
          'A', 'P' => 'pending',
          'B' => 'shipped',
          'C' => 'paid',
          'F' => 'cancelled',
          default => 'pending',
        };
        $paymentLabel = match ((int) $order->paymentStatus) {
          2 => 'Paid',
          1 => 'Card',
          0 => 'Cash',
          default => 'Other',
        };
      @endphp
      <div class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="font-semibold">#ORD-{{ $order->id }}</div>
            <div class="text-xs text-gray-500">{{ trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) }}</div>
          </div>
          <span class="badge {{ $statusLabel === 'paid' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : ($statusLabel === 'shipped' ? 'badge-info' : 'badge-danger')) }}">{{ $statusLabel }}</span>
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

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Buyurtma</th><th>Mijoz</th><th>Tovarlar</th><th>Summa</th><th>To'lov</th><th>Holat</th><th>Sana</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          @forelse($orders as $order)
            @php
              $statusLabel = match ((string) $order->status) {
                'A', 'P' => 'pending',
                'B' => 'shipped',
                'C' => 'paid',
                'F' => 'cancelled',
                default => 'pending',
              };
              $paymentLabel = match ((int) $order->paymentStatus) {
                2 => 'Paid',
                1 => 'Card',
                0 => 'Cash',
                default => 'Other',
              };
            @endphp
            <tr>
              <td>#ORD-{{ $order->id }}</td>
              <td>{{ trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) }}</td>
              <td>{{ (int) collect($order->items ?? [])->sum('count_item') }}</td>
              <td>{{ number_format((float) $order->amount, 0) }} UZS</td>
              <td>{{ $paymentLabel }}</td>
              <td>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="inline-flex">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                    <option value="A" @selected($order->status === 'A')>Yangi</option>
                    <option value="P" @selected($order->status === 'P')>Qadoqlanmoqda</option>
                    <option value="B" @selected($order->status === 'B')>Yo'lda</option>
                    <option value="C" @selected($order->status === 'C')>Yakunlangan</option>
                    <option value="F" @selected($order->status === 'F')>Bekor qilingan</option>
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
@if(isset($orders) && method_exists($orders, 'links'))
  <div class="mt-4">{{ $orders->links('a122.partials.pagination') }}</div>
@endif
@endsection
