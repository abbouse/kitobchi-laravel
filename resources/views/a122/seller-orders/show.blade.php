@extends('a122.layouts.admin')
@section('title', 'Buyurtma #' . $sellerOrder->id)
@section('page-title', 'Sotuvchi buyurtmasi')
@section('page-eyebrow', 'Seller fulfillment')

@section('content')
@php
    $customerName = trim(($sellerOrder->client?->name ?? '') . ' ' . ($sellerOrder->client?->lastname ?? '')) ?: ($address['fullName'] ?? '—');
    $customerPhone = $sellerOrder->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
    $statusVal = $sellerOrder->status_code ?? \App\Enums\SellerOrderStatusCode::fromLegacy($sellerOrder->status ?? 1)->value;
    $statusMeta = $statuses[$statusVal] ?? ['label' => $statusVal, 'badge' => 'badge-muted'];
    $statusBadgeClass = match ($statusMeta['badge']) {
        'badge-success' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
        'badge-danger' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
        'badge-warning' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
        'badge-info' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
        default => 'text-bg-light border',
    };
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Seller fulfillment"
        :title="'Seller buyurtma #' . $sellerOrder->id"
        :subtitle="($sellerOrder->seller->shop_name ?? 'Sotuvchi yo‘q') . ' · ' . $customerName . ' · ' . ($sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q')">
        <a href="{{ route('admin.seller-orders.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Ro‘yxatga qaytish
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
            <x-admin.stat-card label="Seller summasi" :value="number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') . ' UZS'" meta="Sellerga tegishli payout qismi" icon="cash-coin" tone="success" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-admin.stat-card label="Mahsulotlar" :value="number_format($summary['items_count'])" meta="Seller itemlari" icon="box-seam" tone="info" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-admin.stat-card label="Yetkazish turi" :value="$summary['delivery_type']" meta="Fulfillment yo‘li" icon="truck" tone="warning" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-admin.stat-card label="Holat" :value="$statusMeta['label']" meta="Joriy seller bosqichi" icon="diagram-3" tone="primary" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <x-admin.section-card title="Buyurtma ma'lumotlari" meta="Asosiy order, mijoz, summa va yetkazish bo‘yicha tafsilotlar.">
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Buyurtma ID</div><div class="fw-semibold">#{{ $sellerOrder->id }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div>{{ $sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Asosiy buyurtma</div><div class="fw-semibold"><a href="{{ route('admin.orders.show', $sellerOrder->order_id) }}" class="link-success text-decoration-none">#{{ $sellerOrder->order_id }}</a></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sotuvchi</div><div class="fw-semibold">@if($sellerOrder->seller)<a href="{{ route('admin.sellers.show', $sellerOrder->seller) }}" class="link-success text-decoration-none">{{ $sellerOrder->seller->shop_name }}</a>@else—@endif</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Summa</div><div class="fw-bold fs-5">{{ number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') }} UZS</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Mijoz</div><div>@if($sellerOrder->client)<a href="{{ route('admin.users.show', $sellerOrder->client) }}" class="link-success text-decoration-none">{{ $customerName }}</a>@else{{ $customerName }}@endif</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Telefon</div><div>{{ $customerPhone }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Yetkazib berish turi</div><div>{{ $summary['delivery_type'] }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Mahsulotlar soni</div><div>{{ $summary['items_count'] }} ta</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill {{ $statusBadgeClass }}">{{ $statusMeta['label'] }}</span></div></div>
                    <div class="col-12"><div class="text-secondary mb-1">Manzil</div><div>{{ $address['fullAddress'] ?? 'Manzil kiritilmagan' }}</div></div>
                </div>
            </x-admin.section-card>
        </div>

        <div class="col-12 col-xl-4">
            <x-admin.section-card title="Holatni o'zgartirish" meta="Seller order statusini shu blokdan yangilash mumkin.">
                @if($errors->any())
                    <div class="alert alert-danger rounded-4 small">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.seller-orders.status', $sellerOrder) }}" class="d-grid gap-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="form-label">Yangi holat</label>
                        <select name="status" class="form-select">
                            @foreach($statuses as $value => $status)
                                <option value="{{ $value }}" @selected($statusVal === $value)>{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-floppy me-2"></i>Saqlash
                    </button>
                </form>
            </x-admin.section-card>
        </div>
    </div>

    <x-admin.section-card title="Sotuvchiga tegishli mahsulotlar" meta="Aynan shu sellerga biriktirilgan order itemlari va summalari.">
        @if($items->isEmpty())
            <div class="text-secondary">Bu buyurtma uchun sotuvchiga tegishli mahsulotlar topilmadi.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mahsulot</th>
                            <th>Turi</th>
                            <th>Miqdor</th>
                            <th>Narx</th>
                            <th>Jami</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item['name'] }}</div>
                                    @if(!empty($item['author']))
                                        <div class="small text-secondary">{{ $item['author'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $item['type'] }}</td>
                                <td class="font-monospace">{{ number_format((int) ($item['count_item'] ?? 1)) }}</td>
                                <td class="font-monospace">{{ number_format((float) ($item['price'] ?? 0), 0, '.', ' ') }} UZS</td>
                                <td class="fw-semibold font-monospace">{{ number_format((float) (($item['price'] ?? 0) * ($item['count_item'] ?? 1)), 0, '.', ' ') }} UZS</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.section-card>
</div>
@endsection
