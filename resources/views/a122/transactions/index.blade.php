@extends('a122.layouts.admin')
@section('title', 'Tranzaksiyalar')
@section('page-title', 'Sotuvchi tranzaksiyalari')

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
        <div class="a122-index-header__title">Tranzaksiyalar ro‘yxati</div>
        <div class="a122-index-header__meta">{{ $transactions->total() }} ta tranzaksiya topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ request('tab', 'pending') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="ID, sotuvchi yoki summa bo‘yicha qidiring">
        </form>
    </div>
</div>

{{-- Tabs --}}
<div class="flex items-center gap-2 mb-4 flex-wrap">
    @php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda',  'count' => $counts['pending']  ?? 0],
            'approved' => ['label' => 'Tasdiqlangan','count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'all'      => ['label' => 'Barchasi',    'count' => $counts['all']      ?? 0],
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
                    <th>Sotuvchi</th>
                    <th>Miqdor</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="text-gray-500 text-sm">#{{ $transaction->id }}</td>
                        <td>
                            <div class="font-semibold">{{ $transaction->seller->shop_name ?? '—' }}</div>
                        </td>
                        <td class="font-semibold text-sm whitespace-nowrap">
                            {{ number_format((float)($transaction->amount ?? 0), 0, '.', ' ') }} UZS
                        </td>
                        <td>
                            @if($transaction->status === 'approved')
                                <span class="badge badge-success">Tasdiqlangan</span>
                            @elseif($transaction->status === 'pending')
                                <span class="badge badge-warning">Kutilmoqda</span>
                            @elseif($transaction->status === 'rejected')
                                <span class="badge badge-danger">Rad etilgan</span>
                            @else
                                <span class="badge badge-muted">{{ $transaction->status ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            {{ $transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="{{ route('admin.transactions.approve', $transaction) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.transactions.reject', $transaction) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday tranzaksiya topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($transactions->hasPages())
    <div class="mt-4">{{ $transactions->links('a122.partials.pagination') }}</div>
@endif
@endsection
