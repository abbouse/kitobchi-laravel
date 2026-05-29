@extends('a122.layouts.admin')
@section('title', 'Sotuvchilar')
@section('page-title', 'Sotuvchilar')

@section('content')
@if(session('success'))
    <div class="alert alert-success kc-flash mb-4">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger kc-flash mb-4">
        {{ session('error') }}
    </div>
@endif

<x-admin.page-header
    eyebrow="Seller management"
    title="Sotuvchilar ro‘yxati"
    subtitle="{{ $sellers->total() }} ta sotuvchi topildi. Moderatsiya, aloqa va faollik holatini shu yerdan boshqaring.">
    <form method="GET" class="kc-search kc-topbar__search">
            <input type="hidden" name="tab" value="{{ request('tab', 'pending') }}">
            <i class="bi bi-search kc-search__icon"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Do‘kon, telefon yoki hudud bo‘yicha qidiring">
    </form>
</x-admin.page-header>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <x-admin.stat-card
            label="Kutilayotgan sellerlar"
            :value="number_format($counts['pending'] ?? 0)"
            meta="Moderatsiya yoki hujjat tekshiruvini kutayotgan do‘konlar"
            icon="hourglass-split"
            tone="warning" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <x-admin.stat-card
            label="Tasdiqlangan"
            :value="number_format($counts['approved'] ?? 0)"
            meta="Savdoga chiqqan va faol ishlayotgan sellerlar"
            icon="shop"
            tone="success" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <x-admin.stat-card
            label="Rad etilgan"
            :value="number_format($counts['rejected'] ?? 0)"
            meta="Qayta ko‘rib chiqish yoki tuzatish kutayotgan arizalar"
            icon="x-octagon"
            tone="danger" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <x-admin.stat-card
            label="Bloklangan"
            :value="number_format($counts['blocked'] ?? 0)"
            meta="Policy yoki ogohlantirish sabab cheklangan do‘konlar"
            icon="shield-lock"
            tone="info" />
    </div>
</div>

<div class="kc-filter-card mb-4">
    <div class="nav nav-pills flex-wrap">
    @php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'blocked'  => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
            'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
        ];
    @endphp
    @foreach($tabs as $key => $tabItem)
        <a
            href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
            class="nav-link {{ $tab === $key ? 'active' : '' }}"
        >
            {{ $tabItem['label'] }}
            <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $tabItem['count'] }}</span>
        </a>
    @endforeach
</div>
</div>

<x-admin.section-card title="Sotuvchilar jadvali" :meta="$sellers->total() . ' ta seller yozuvi topildi.'">
    <div class="table-responsive kc-table-shell">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Do'kon nomi</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Kitoblar</th>
                    <th>Buyurtmalar</th>
                    <th>Ogohlantirish</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sellers as $seller)
                    <tr>
                        <td class="text-secondary small">#{{ $seller->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                @include('a122.partials.avatar', [
                                    'name' => trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? '')) ?: ($seller->shop_name ?? 'S'),
                                    'image' => $seller->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ])
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $seller->shop_name }}</div>
                                    @if($seller->firstname || $seller->lastname)
                                        <div class="small text-secondary text-truncate">{{ trim($seller->firstname . ' ' . $seller->lastname) }}</div>
                                    @endif
                                    @if($seller->district || $seller->address)
                                        <div class="small text-secondary text-truncate">{{ $seller->district ?: $seller->address }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $seller->phone_number ?: '—' }}</td>
                        <td>{{ $seller->region ?: '—' }}</td>
                        <td>
                            <span class="badge text-bg-light border">{{ $seller->books_count ?? 0 }}</span>
                        </td>
                        <td>
                            <span class="badge text-bg-light border">{{ $seller->orders_count ?? 0 }}</span>
                        </td>
                        <td>
                            @php $warningCount = (int) ($seller->active_warning_count ?? 0); @endphp
                            <span class="badge rounded-pill {{ $warningCount >= 3 ? 'text-bg-danger' : ($warningCount > 0 ? 'text-bg-warning' : 'text-bg-light border') }}">
                                {{ $warningCount }}/3
                            </span>
                        </td>
                        <td>
                            @if($seller->status === 'approved')
                                <span class="badge rounded-pill text-bg-success">Tasdiqlangan</span>
                            @elseif($seller->status === 'pending')
                                <span class="badge rounded-pill text-bg-warning">Kutilmoqda</span>
                            @elseif($seller->status === 'rejected')
                                <span class="badge rounded-pill text-bg-danger">Rad etilgan</span>
                            @elseif($seller->status === 'blocked')
                                <span class="badge rounded-pill text-bg-dark">Bloklangan</span>
                            @else
                                <span class="badge rounded-pill text-bg-light border">{{ $seller->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                                @if($seller->status !== 'approved')
                                    <form method="POST" action="{{ route('admin.sellers.approve', $seller) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.sellers.reject', $seller) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                                @if($seller->status === 'blocked')
                                    <form method="POST" action="{{ route('admin.sellers.unblock', $seller) }}" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Blokdan chiqarish">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko'rish">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.sellers.edit', $seller) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">Hech qanday sotuvchi topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.section-card>

@if($sellers->hasPages())
    <div class="mt-4">{{ $sellers->links('a122.partials.pagination') }}</div>
@endif
@endsection
