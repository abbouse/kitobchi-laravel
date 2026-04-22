@extends('a122.layouts.admin')
@section('title', 'Kuryerlar')
@section('page-title', 'Kuryerlar')

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
        <div class="a122-index-header__title">Kuryerlar ro‘yxati</div>
        <div class="a122-index-header__meta">{{ $couriers->total() }} ta kuryer topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Ism, telefon yoki hudud bo‘yicha qidiring">
        </form>
        <a href="{{ route('admin.couriers.create') }}" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi kuryer
        </a>
    </div>
</div>

{{-- Top bar --}}
<div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    {{-- Tabs --}}
    <div class="flex items-center gap-2 flex-wrap">
        @php
            $tabs = [
                'all'      => ['label' => 'Barchasi',    'count' => $counts['all']      ?? 0],
                'approved' => ['label' => 'Tasdiqlangan','count' => $counts['approved'] ?? 0],
                'pending'  => ['label' => 'Kutilmoqda',  'count' => $counts['pending']  ?? 0],
                'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
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
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ism</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Balans</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($couriers as $courier)
                    <tr>
                        <td class="text-gray-500 text-sm">{{ $courier->id }}</td>
                        <td>
                            <div class="flex items-center gap-3 min-w-0">
                                @include('a122.partials.avatar', [
                                    'name' => trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: 'Kuryer',
                                    'image' => $courier->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ])
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">
                                        {{ trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">{{ $courier->phone_number ?? $courier->phone ?? '—' }}</td>
                        <td class="text-sm">{{ $courier->region ?? '—' }}</td>
                        <td class="text-sm font-medium whitespace-nowrap">
                            {{ number_format((float)($courier->balance ?? 0), 0, '.', ' ') }} UZS
                        </td>
                        <td>
                            @if($courier->status === 'approved')
                                <span class="badge badge-success">Tasdiqlangan</span>
                            @elseif($courier->status === 'pending')
                                <span class="badge badge-warning">Kutilmoqda</span>
                            @elseif($courier->status === 'rejected')
                                <span class="badge badge-danger">Rad etilgan</span>
                            @else
                                <span class="badge badge-muted">{{ $courier->status ?? '—' }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <form method="POST" action="{{ route('admin.couriers.approve', $courier) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Tasdiqlash">
                                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.couriers.reject', $courier) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                                        <i data-lucide="badge-x" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.couriers.show', $courier) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}"
                                    onsubmit="return confirm('Kuryerni o\'chirishga ishonchingiz komilmi?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost p-2 rounded-lg text-red-500 hover:text-red-600" title="O'chirish">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-8">Hech qanday kuryer topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($couriers->hasPages())
    <div class="mt-4">{{ $couriers->links('a122.partials.pagination') }}</div>
@endif
@endsection
