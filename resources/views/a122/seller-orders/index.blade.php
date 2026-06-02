@extends('a122.layouts.admin')
@section('title', 'Sotuvchi buyurtmalari')
@section('page-title', 'Sotuvchi buyurtmalari')
@section('page-eyebrow', 'Seller fulfillment')

@section('content')
@php
    use App\Support\AdminOrderStatusPresenter;
    $tabs = ['all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0]];
    foreach ($statuses as $value => $statusItem) {
        $tabs[(string) $value] = ['label' => $statusItem['label'], 'count' => $counts[$value] ?? 0];
    }

    $statusBadgeClass = function (string $badge): string {
        return match ($badge) {
            'badge-success' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'badge-danger' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            'badge-warning' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'badge-info' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
            default => 'text-bg-light border',
        };
    };
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Seller fulfillment"
        title="Sotuvchi buyurtmalari"
        subtitle="Seller kesimida yig‘ilgan fulfillment navbati, status o‘zgarishlari va tezkor operatsion boshqaruv shu jadvalda yuradi.">
        <a href="{{ route('admin.orders.index') }}" class="btn-p ghost">
            <i class="bi bi-arrow-left me-2"></i>Asosiy buyurtmalar
        </a>
    </x-admin.page-header>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <x-admin.stat-card
                label="Jami seller orderlar"
                :value="number_format($counts['all'] ?? 0)"
                meta="Seller kesimida yaratilgan barcha fulfillment yozuvlari"
                icon="box-seam"
                tone="dark" />
        </div>
        @foreach($statuses as $value => $statusItem)
            <div class="col-12 col-md-6 col-xl-3">
                <x-admin.stat-card
                    :label="$statusItem['label']"
                    :value="number_format($counts[$value] ?? 0)"
                    meta="Joriy seller order bosqichidagi yozuvlar"
                    icon="diagram-3"
                    :tone="match($statusItem['badge']) {
                        'badge-success' => 'success',
                        'badge-danger' => 'danger',
                        'badge-warning' => 'warning',
                        'badge-info' => 'info',
                        default => 'primary',
                    }" />
            </div>
        @endforeach
    </div>

    <x-admin.section-card title="Filter va qidiruv" meta="Seller, mijoz yoki order ID bo‘yicha kerakli yozuvni tez topish mumkin.">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="kc-search">
                    <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
                    <i class="bi bi-search kc-search__icon"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="ID, sotuvchi yoki mijoz bo‘yicha qidiring"
                        class="form-control">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    @foreach($tabs as $key => $tabItem)
                        <a
                            href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                            class="nav-link {{ (string) $tab === (string) $key ? 'active' : '' }}">
                            {{ $tabItem['label'] }}
                            <span class="badge rounded-pill text-bg-light ms-2 font-monospace">{{ number_format($tabItem['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Seller orderlar jadvali" :meta="$orders->total() . ' ta yozuv topildi.'">
        <div class="table-responsive kc-table-shell">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Sotuvchi</th>
                        <th>Mijoz</th>
                        <th>Summa</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $customerName = trim(($order->client?->name ?? '') . ' ' . ($order->client?->lastname ?? ''));
                            $address = collect($order->order?->address ?? [])->first();
                            $fallbackName = $address['fullName'] ?? '—';
                            $customerPhone = $order->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
                            $itemsCount = collect($order->order?->items ?? [])->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)->sum(fn ($item) => (int) ($item['count_item'] ?? 1));
                            $orderAmount = (float) ($order->amount ?? 0);
                            $statusCode = $order->status_code ?? \App\Enums\SellerOrderStatusCode::fromLegacy($order->status ?? 1)->value;
                            $statusMeta = $statuses[$statusCode] ?? ['label' => AdminOrderStatusPresenter::sellerOrder($statusCode), 'badge' => 'badge-muted'];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold">#{{ $order->id }}</div>
                                <div class="small text-secondary">ORD #{{ $order->order_id }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->seller->shop_name ?? '—' }}</div>
                                <div class="small text-secondary">{{ trim(($order->seller->firstname ?? '') . ' ' . ($order->seller->lastname ?? '')) ?: 'Sotuvchi' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $customerName ?: $fallbackName }}</div>
                                <div class="small text-secondary">{{ $customerPhone }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold font-monospace">{{ number_format($orderAmount, 0, '.', ' ') }} UZS</div>
                                <div class="small text-secondary">{{ $itemsCount }} ta mahsulot</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill {{ $statusBadgeClass($statusMeta['badge']) }}">{{ $statusMeta['label'] }}</span>
                                    <form method="POST" action="{{ route('admin.seller-orders.status', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                                            @foreach($statuses as $value => $statusItem)
                                                <option value="{{ $value }}" @selected($statusCode === $value)>{{ $statusItem['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->created_at ? $order->created_at->format('d.m.Y') : '—' }}</div>
                                <div class="small text-secondary">{{ $order->created_at ? $order->created_at->format('H:i') : '—' }}</div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.seller-orders.show', $order) }}" class="btn-p primary sm">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">Hech qanday buyurtma topilmadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section-card>

    @if($orders->hasPages())
        <div>{{ $orders->links('a122.partials.pagination') }}</div>
    @endif
</div>
@endsection
