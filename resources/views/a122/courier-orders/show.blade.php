@extends('a122.layouts.admin')
@section('title', 'Kuryer buyurtmasi #' . $courierOrder->id)
@section('page-title', 'Kuryer buyurtmasi')
@section('page-eyebrow', 'Last-mile operations')

@section('content')
@php
    $statusKey = (string) ($courierOrder->status ?? '');
    $statusLabel = $statuses[$statusKey]['label'] ?? ($statusKey ?: '—');
    $statusBadgeClass = match ($statusKey) {
        'delivered' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
        'pending', 'pay_process' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
        'in_delivery' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
        'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
        default => 'text-bg-light border',
    };
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Last-mile operations"
        :title="'Kuryer buyurtmasi #' . $courierOrder->id"
        :subtitle="(trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: 'Kuryer yo‘q') . ' · ' . ($courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q')">
        <a href="{{ route('admin.courier-orders.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
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
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Joriy holat" :value="$statusLabel" meta="Courier bosqichi" icon="truck" tone="primary" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Summa" :value="number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ') . ' UZS'" meta="Yetkazish yozuvi summasi" icon="cash-coin" tone="success" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Kuryer" :value="$courierOrder->courier ? 'Biriktirilgan' : 'Yo‘q'" :meta="$courierOrder->courier?->region ?: 'Hudud yo‘q'" icon="person-badge" tone="info" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Sana" :value="optional($courierOrder->created_at)->format('d.m') ?: '—'" :meta="optional($courierOrder->created_at)->format('H:i') ?: 'Vaqt yo‘q'" icon="calendar-event" tone="warning" /></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <x-admin.section-card title="Kuryer ma'lumotlari" meta="Biriktirilgan kuryerning profil va aloqa ma’lumotlari.">
                @if($courierOrder->courier)
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($courierOrder->courier->photo)
                            <img
                                src="{{ Str::startsWith($courierOrder->courier->photo, 'http') ? $courierOrder->courier->photo : asset('storage/' . $courierOrder->courier->photo) }}"
                                alt="Kuryer"
                                class="rounded-circle object-fit-cover"
                                width="56"
                                height="56">
                        @else
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width:56px;height:56px;">
                                {{ strtoupper(substr($courierOrder->courier->first_name ?? 'K', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="fw-bold">
                                <a href="{{ route('admin.couriers.show', $courierOrder->courier) }}" class="link-success text-decoration-none">
                                    {{ trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: '—' }}
                                </a>
                            </div>
                            <div class="small text-secondary">{{ $courierOrder->courier->phone_number ?? $courierOrder->courier->phone ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-4 small">
                        <div class="col-sm-6"><div class="text-secondary mb-1">Viloyat</div><div>{{ $courierOrder->courier->region ?? '—' }}</div></div>
                        <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div>{{ $courierOrder->courier->status ?? '—' }}</div></div>
                    </div>
                @else
                    <div class="text-secondary">Kuryer ma'lumotlari mavjud emas.</div>
                @endif
            </x-admin.section-card>
        </div>

        <div class="col-12 col-lg-6">
            <x-admin.section-card title="Buyurtma ma'lumotlari" meta="Kuryerga tushgan orderning foydalanuvchi, manzil va summa tafsilotlari.">
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Buyurtma ID</div><div class="fw-semibold">#{{ $courierOrder->id }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill {{ $statusBadgeClass }}">{{ $statusLabel }}</span></div></div>
                    <div class="col-sm-6">
                        <div class="text-secondary mb-1">Foydalanuvchi</div>
                        <div>
                            @if($courierOrder->user)
                                <a href="{{ route('admin.users.show', $courierOrder->user) }}" class="link-success text-decoration-none fw-semibold">
                                    {{ trim(($courierOrder->user->first_name ?? $courierOrder->user->name ?? '') . ' ' . ($courierOrder->user->last_name ?? '')) ?: '—' }}
                                </a>
                                <div class="small text-secondary">{{ $courierOrder->user->phone_number ?? $courierOrder->user->phone ?? 'Telefon yo‘q' }}</div>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div>{{ $courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : '—' }}</div></div>
                    @if($courierOrder->address ?? $courierOrder->delivery_address)
                        <div class="col-12"><div class="text-secondary mb-1">Manzil</div><div>{{ $courierOrder->address ?? $courierOrder->delivery_address }}</div></div>
                    @endif
                    @if($courierOrder->amount ?? $courierOrder->total)
                        <div class="col-sm-6"><div class="text-secondary mb-1">Summa</div><div class="fw-bold fs-5">{{ number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ') }} UZS</div></div>
                    @endif
                    @if($courierOrder->note ?? $courierOrder->comment)
                        <div class="col-12"><div class="text-secondary mb-1">Izoh</div><div>{{ $courierOrder->note ?? $courierOrder->comment }}</div></div>
                    @endif
                </div>
            </x-admin.section-card>
        </div>
    </div>
</div>
@endsection
