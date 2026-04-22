@extends('a122.layouts.admin')
@section('title', 'Sotuvchilar')
@section('page-title', 'Sotuvchilar')

@section('content')
@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        {{ session('error') }}
    </div>
@endif

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Sotuvchilar ro‘yxati</div>
        <div class="a122-index-header__meta">{{ $sellers->total() }} ta sotuvchi topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ request('tab', 'pending') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Do‘kon, telefon yoki hudud bo‘yicha qidiring">
        </form>
    </div>
</div>

{{-- Tabs --}}
<div class="flex items-center gap-2 mb-4 flex-wrap">
    @php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
        ];
    @endphp
    @foreach($tabs as $key => $tabItem)
        <a
            href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
            class="btn {{ $tab === $key ? 'btn-primary' : 'btn-secondary' }} flex items-center gap-2 text-sm"
        >
            {{ $tabItem['label'] }}
            <span class="badge {{ $tab === $key ? 'badge-info' : 'badge-muted' }}">{{ $tabItem['count'] }}</span>
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Do'kon nomi</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Kitoblar</th>
                    <th>Buyurtmalar</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sellers as $seller)
                    <tr>
                        <td class="text-gray-500 text-sm">{{ $seller->id }}</td>
                        <td>
                            <div class="flex items-center gap-3 min-w-0">
                                @include('a122.partials.avatar', [
                                    'name' => trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? '')) ?: ($seller->shop_name ?? 'S'),
                                    'image' => $seller->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ])
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ $seller->shop_name }}</div>
                                    @if($seller->firstname || $seller->lastname)
                                        <div class="text-xs text-gray-500 truncate">{{ trim($seller->firstname . ' ' . $seller->lastname) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">{{ $seller->phone_number ?: '—' }}</td>
                        <td class="text-sm">{{ $seller->region ?: '—' }}</td>
                        <td>
                            <span class="badge badge-muted">{{ $seller->books_count ?? 0 }}</span>
                        </td>
                        <td>
                            <span class="badge badge-muted">{{ $seller->orders_count ?? 0 }}</span>
                        </td>
                        <td>
                            @if($seller->status === 'approved')
                                <span class="badge badge-success">Tasdiqlangan</span>
                            @elseif($seller->status === 'pending')
                                <span class="badge badge-warning">Kutilmoqda</span>
                            @elseif($seller->status === 'rejected')
                                <span class="badge badge-danger">Rad etilgan</span>
                            @else
                                <span class="badge badge-muted">{{ $seller->status }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="{{ route('admin.sellers.approve', $seller) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.sellers.reject', $seller) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.sellers.edit', $seller) }}" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-8">Hech qanday sotuvchi topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($sellers->hasPages())
    <div class="mt-4">{{ $sellers->links('a122.partials.pagination') }}</div>
@endif
@endsection
