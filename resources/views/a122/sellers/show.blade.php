@extends('a122.layouts.admin')
@section('title', $seller->shop_name)
@section('page-title', 'Sotuvchi profili')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="{{ route('admin.sellers.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="{{ route('admin.sellers.edit', $seller) }}" class="btn btn-primary flex items-center gap-2">
        <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
    </a>
</div>

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

{{-- Top Info Card --}}
<div class="card p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        {{-- Avatar --}}
        <div class="shrink-0">
            @if($seller->photo)
                <img
                    src="{{ Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo) }}"
                    alt="{{ $seller->shop_name }}"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-emerald-100 dark:ring-emerald-500/20"
                >
            @else
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    {{ strtoupper(substr($seller->shop_name ?? 'S', 0, 1)) }}
                </div>
            @endif
        </div>

        {{-- Main Info --}}
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold">{{ $seller->shop_name }}</h2>
                @if($seller->status === 'approved')
                    <span class="badge badge-success">Tasdiqlangan</span>
                @elseif($seller->status === 'pending')
                    <span class="badge badge-warning">Kutilmoqda</span>
                @elseif($seller->status === 'rejected')
                    <span class="badge badge-danger">Rad etilgan</span>
                @else
                    <span class="badge badge-muted">{{ $seller->status }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">{{ trim($seller->firstname . ' ' . $seller->lastname) }}</p>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                @if($seller->phone_number)
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i> {{ $seller->phone_number }}
                    </span>
                @endif
                @if($seller->region)
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> {{ $seller->region }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Approve / Reject Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            @if($seller->status !== 'approved')
                <form method="POST" action="{{ route('admin.sellers.approve', $seller) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            @endif
            @if($seller->status !== 'rejected')
                <form method="POST" action="{{ route('admin.sellers.reject', $seller) }}" onsubmit="return confirm('Sotuvchini rad etishga ishonchingiz komilmi?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
            <i data-lucide="shopping-bag" class="w-6 h-6 text-blue-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami buyurtmalar</p>
            <p class="text-2xl font-bold">{{ number_format($orderCount, 0, '.', ' ') }}</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="banknote" class="w-6 h-6 text-green-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami daromad</p>
            <p class="text-2xl font-bold">{{ number_format($totalRevenue, 0, '.', ' ') }}</p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center">
            <i data-lucide="book-open" class="w-6 h-6 text-purple-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Kitoblar</p>
            <p class="text-2xl font-bold">{{ $seller->books_count ?? 0 }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Recent Orders --}}
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="text-gray-500 text-sm">#{{ $order->id }}</td>
                                <td class="font-semibold">{{ number_format((float)($order->amount ?? $order->total ?? 0), 0, '.', ' ') }} UZS</td>
                                <td class="text-sm text-gray-500">
                                    {{ $order->created_at ? $order->created_at->format('d.m.Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 py-6">Buyurtmalar yo'q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi tranzaksiyalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Holat</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="text-gray-500 text-sm">#{{ $tx->id }}</td>
                                <td class="font-semibold">{{ number_format((float)($tx->amount ?? 0), 0, '.', ' ') }} UZS</td>
                                <td>
                                    @if(in_array($tx->status, ['success', 'completed', 'approved']))
                                        <span class="badge badge-success">Muvaffaqiyatli</span>
                                    @elseif(in_array($tx->status, ['pending', 'processing']))
                                        <span class="badge badge-warning">Kutilmoqda</span>
                                    @elseif(in_array($tx->status, ['failed', 'rejected', 'cancelled']))
                                        <span class="badge badge-danger">Rad etilgan</span>
                                    @else
                                        <span class="badge badge-muted">{{ $tx->status ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-500">
                                    {{ $tx->created_at ? $tx->created_at->format('d.m.Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-6">Tranzaksiyalar yo'q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
