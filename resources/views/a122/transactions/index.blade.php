@extends('a122.layouts.admin')
@section('title', 'Tranzaksiyalar')
@section('page-title', 'Sotuvchi tranzaksiyalari')
@section('page-eyebrow', 'Payout operations')

@section('content')
@php
    $tabs = [
        'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
        'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
        'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
        'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    ];

    $statusBadgeClass = function (?string $status): string {
        return match ($status) {
            'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'pending' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            default => 'text-bg-light border',
        };
    };
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Payout operations"
        title="Tranzaksiyalar ro‘yxati"
        subtitle="Seller payout yozuvlari, tasdiqlash navbati va moliyaviy oqim holatlari shu bo‘limda boshqariladi.">
        <a href="{{ route('admin.sellers.index') }}" class="btn-p ghost">
            <i class="bi bi-shop me-2"></i>Sotuvchilar
        </a>
    </x-admin.page-header>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        @foreach($tabs as $key => $tabItem)
            <div class="col-12 col-md-6 col-xl-3">
                <x-admin.stat-card
                    :label="$tabItem['label']"
                    :value="number_format($tabItem['count'])"
                    meta="Payout navbatidagi yozuvlar"
                    icon="cash-stack"
                    :tone="match($key) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'dark',
                    }" />
            </div>
        @endforeach
    </div>

    <x-admin.section-card title="Filter va qidiruv" meta="ID, seller yoki summa bo‘yicha kerakli payout yozuvini toping.">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="kc-search">
                    <input type="hidden" name="tab" value="{{ request('tab', 'pending') }}">
                    <i class="bi bi-search kc-search__icon"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="ID, sotuvchi yoki summa bo‘yicha qidiring"
                        class="form-control">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    @foreach($tabs as $key => $tabItem)
                        <a
                            href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                            class="nav-link {{ $tab === $key ? 'active' : '' }}">
                            {{ $tabItem['label'] }}
                            <span class="badge rounded-pill text-bg-light ms-2 font-monospace">{{ number_format($tabItem['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Tranzaksiyalar jadvali" :meta="$transactions->total() . ' ta tranzaksiya topildi.'">
        <div class="table-responsive kc-table-shell">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Sotuvchi</th>
                        <th>Miqdor</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="fw-semibold">#{{ $transaction->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $transaction->seller->shop_name ?? '—' }}</div>
                            </td>
                            <td class="fw-semibold font-monospace">{{ number_format((float)($transaction->amount ?? 0), 0, '.', ' ') }} UZS</td>
                            <td><span class="badge rounded-pill {{ $statusBadgeClass($transaction->status) }}">{{ $transaction->status_label ?? $transaction->status ?? '—' }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $transaction->created_at ? $transaction->created_at->format('d.m.Y') : '—' }}</div>
                                <div class="small text-secondary">{{ $transaction->created_at ? $transaction->created_at->format('H:i') : '—' }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                    @if($transaction->status === 'pending')
                                        <form method="POST" action="{{ route('admin.transactions.approve', $transaction) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-p success sm">
                                                <i class="bi bi-check2-circle me-1"></i>Tasdiqlash
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.transactions.reject', $transaction) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-p danger sm">
                                                <i class="bi bi-x-circle me-1"></i>Rad etish
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn-p primary sm">
                                        <i class="bi bi-eye me-1"></i>Ko‘rish
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">Hech qanday tranzaksiya topilmadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section-card>

    @if($transactions->hasPages())
        <div>{{ $transactions->links('a122.partials.pagination') }}</div>
    @endif
</div>
@endsection
