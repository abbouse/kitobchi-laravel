@extends('a122.layouts.admin')
@section('title', 'Kuryer buyurtmalari')
@section('page-title', 'Kuryer buyurtmalari')
@section('page-eyebrow', 'Last-mile operations')

@section('content')
@php
    $tabs = [
        'pay_process' => ["To'lov jarayonida", $counts['pay_process'] ?? 0],
        'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
        'in_delivery' => ["Yo'lda", $counts['in_delivery'] ?? 0],
        'delivered' => ['Yetib bordi', $counts['delivered'] ?? 0],
        'customer_received' => ['Mijoz qabul qildi', $counts['customer_received'] ?? 0],
        'rejected' => ['Bekor qilingan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ];

    $statusBadgeClass = function (string $key): string {
        return match ($key) {
            'delivered', 'customer_received' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'pending', 'pay_process' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'in_delivery' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
            'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            default => 'text-bg-light border',
        };
    };
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Last-mile operations"
        title="Kuryer buyurtmalari"
        subtitle="Kuryerga biriktirilgan oxirgi mil yozuvlari, foydalanuvchi kontaktlari va tezkor status almashuvlari shu navbatda boshqariladi.">
        <a href="{{ route('admin.couriers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-bicycle me-2"></i>Kuryerlar
        </a>
    </x-admin.page-header>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        @foreach($tabs as $key => [$label, $count])
            <div class="col-12 col-md-6 col-xl-2">
                <x-admin.stat-card
                    :label="$label"
                    :value="number_format($count)"
                    meta="Kuryer navbatidagi yozuvlar"
                    icon="truck"
                    :tone="match($key) {
                        'delivered', 'customer_received' => 'success',
                        'pending', 'pay_process' => 'warning',
                        'in_delivery' => 'info',
                        'rejected' => 'danger',
                        default => 'dark',
                    }" />
            </div>
        @endforeach
    </div>

    <x-admin.section-card title="Filter va qidiruv" meta="Kuryer, foydalanuvchi yoki delivery yozuvi bo‘yicha kerakli entryni tez toping.">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="ID, kuryer yoki foydalanuvchi bo‘yicha qidiring"
                        class="form-control rounded-pill ps-5">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    @foreach($tabs as $key => [$label, $count])
                        <a
                            href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                            class="nav-link {{ ($tab ?? 'all') === $key ? 'active' : '' }}">
                            {{ $label }}
                            <span class="badge rounded-pill text-bg-light ms-2 font-monospace">{{ number_format($count) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Kuryer orderlar jadvali" :meta="$orders->total() . ' ta delivery yozuvi topildi.'">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Kuryer</th>
                        <th>Foydalanuvchi</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold">#{{ $order->id }}</td>
                            <td>
                                @if($order->courier)
                                    <div class="fw-semibold">{{ trim(($order->courier->first_name ?? '') . ' ' . ($order->courier->last_name ?? '')) ?: '—' }}</div>
                                    <div class="small text-secondary">{{ $order->courier->phone_number ?? $order->courier->phone ?? 'Telefon yo‘q' }}</div>
                                @else
                                    <span class="text-secondary">Kuryer yo‘q</span>
                                @endif
                            </td>
                            <td>
                                @if($order->user)
                                    <div class="fw-semibold">{{ trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—' }}</div>
                                    <div class="small text-secondary">{{ $order->user->phone_number ?? $order->user->phone ?? 'Telefon yo‘q' }}</div>
                                @else
                                    <span class="text-secondary">Foydalanuvchi yo‘q</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill {{ $statusBadgeClass((string) ($order->status ?? '')) }}">
                                        {{ $statuses[$order->status]['label'] ?? ($order->status ?: '—') }}
                                    </span>
                                    <form method="POST" action="{{ route('admin.courier-orders.status', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                                            @foreach($statuses as $value => $statusItem)
                                                <option value="{{ $value }}" @selected(($order->status ?? '') === $value)>{{ $statusItem['label'] }}</option>
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
                                <a href="{{ route('admin.courier-orders.show', $order) }}" class="btn btn-sm btn-dark rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">Hech qanday kuryer buyurtmasi topilmadi.</td>
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
