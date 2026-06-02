@extends('a122.layouts.admin')
@section('title', 'Buyurtmalar')
@section('page-title', 'Buyurtmalar')
@section('page-eyebrow', 'Order operations')

@section('content')
@php
  use App\Support\AdminOrderStatusPresenter;
  $statusTabs = [
    'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
    'shipped' => ['Yo‘lda', $counts['shipped'] ?? 0],
    'paid' => ['Yakunlangan', $counts['paid'] ?? 0],
    'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ];

  $statusLabel = function ($status) {
    return AdminOrderStatusPresenter::mainOrder($status);
  };

  $paymentLabel = function ($status) {
    return AdminOrderStatusPresenter::payment($status);
  };

  $statusBadgeClass = function ($label) {
    return match ($label) {
      'Yetib bordi', 'Mijoz qabul qildi' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
      'Kutilmoqda', 'Qadoqlanmoqda' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
      "Yo'lda", 'Qaytgan' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
      'Bekor qilingan' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
      default => 'text-bg-light border',
    };
  };
@endphp

<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Order operations"
    title="Buyurtmalar"
    subtitle="Mijoz buyurtmalarini status, to‘lov va vaqt bo‘yicha boshqarish uchun markaziy navbat. List sahifaning o‘zidan qidirish, filtrlash va tezkor detailga o‘tish mumkin.">
    <a href="{{ route('admin.dashboard') }}" class="btn-p ghost">
      <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
  </x-admin.page-header>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Kutilayotgan"
        :value="number_format($counts['pending'] ?? 0)"
        meta="Tasdiqlash yoki jarayon boshlanishini kutayotgan buyurtmalar"
        icon="clock-history"
        tone="warning" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Yo‘ldagi buyurtmalar"
        :value="number_format($counts['shipped'] ?? 0)"
        meta="Kuryer yoki logistika oqimida bo‘lgan buyurtmalar"
        icon="truck"
        tone="info" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Yakunlangan"
        :value="number_format($counts['paid'] ?? 0)"
        meta="To‘langan va muvaffaqiyatli yakunlangan buyurtmalar"
        icon="check2-circle"
        tone="success" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Bekor qilingan"
        :value="number_format($counts['cancelled'] ?? 0)"
        meta="User, admin yoki tizim tomonidan bekor qilingan oqimlar"
        icon="x-octagon"
        tone="danger" />
    </div>
  </div>

  <x-admin.section-card title="Filter va qidiruv" meta="Buyurtmalarni ID, mijoz yoki holat bo‘yicha saralash mumkin.">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-xl-5">
        <form method="GET" class="kc-search">
          <input type="hidden" name="tab" value="{{ $tab }}">
          <i class="bi bi-search kc-search__icon"></i>
          <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="ID, mijoz, telefon yoki holat..."
            class="form-control">
        </form>
      </div>
      <div class="col-12 col-xl-7">
        <div class="nav nav-pills gap-2 justify-content-xl-end">
          @foreach($statusTabs as $key => [$label, $count])
            <a
              href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
              class="nav-link {{ $tab === $key ? 'active' : '' }}">
              {{ $label }}
              <span class="badge rounded-pill text-bg-light ms-2 kc-mono">{{ number_format($count) }}</span>
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </x-admin.section-card>

  <x-admin.section-card title="Buyurtmalar jadvali" :meta="$orders->total() . ' ta buyurtma yozuvi topildi.'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Buyurtma</th>
            <th>Mijoz</th>
            <th>Tovarlar</th>
            <th>Summa</th>
            <th>To‘lov</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
            @php
              $currentStatusLabel = $statusLabel($order->status_code ?? $order->status);
              $currentPaymentLabel = $paymentLabel($order->payment_status_code ?? $order->paymentStatus);
              $itemsCount = (int) collect($order->items ?? [])->sum('count_item');
            @endphp
            <tr>
              <td>
                <div class="fw-bold">#ORD-{{ $order->id }}</div>
                <div class="small text-secondary">Platformadagi buyurtma</div>
              </td>
              <td>
                <div class="fw-semibold">{{ trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) }}</div>
                <div class="small text-secondary">{{ $order->user?->phone_number ?: 'Telefon yo‘q' }}</div>
              </td>
              <td class="kc-mono">{{ number_format($itemsCount) }} ta</td>
              <td class="kc-mono fw-semibold">{{ number_format((float) $order->amount, 0) }} UZS</td>
              <td>
                <span class="badge rounded-pill text-bg-light border">{{ $currentPaymentLabel }}</span>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <span class="badge rounded-pill {{ $statusBadgeClass($currentStatusLabel) }}">{{ $currentStatusLabel }}</span>
                  <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                      <option value="A" @selected(in_array(($order->status_code ?? $order->status), ['A','pending'], true))>Kutilmoqda</option>
                      <option value="P" @selected(in_array(($order->status_code ?? $order->status), ['P','packing'], true))>Qadoqlanmoqda</option>
                      <option value="B" @selected(in_array(($order->status_code ?? $order->status), ['B','in_delivery'], true))>Yetkazilmoqda</option>
                      <option value="C" @selected(in_array(($order->status_code ?? $order->status), ['C','delivered'], true))>Yetib bordi</option>
                      <option value="D" @selected(in_array(($order->status_code ?? $order->status), ['D','customer_received'], true))>Mijoz qabul qildi</option>
                      <option value="F" @selected(in_array(($order->status_code ?? $order->status), ['F','cancelled','returned'], true))>Bekor qilingan</option>
                    </select>
                  </form>
                </div>
              </td>
              <td>
                <div class="fw-semibold">{{ optional($order->created_at)->format('d.m.Y') }}</div>
                <div class="small text-secondary">{{ optional($order->created_at)->format('H:i') }}</div>
              </td>
              <td class="text-end">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn-p primary sm">
                  <i class="bi bi-eye me-1"></i>Ko‘rish
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-secondary">
                Buyurtmalar topilmadi.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if(method_exists($orders, 'links'))
    <div>{{ $orders->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
