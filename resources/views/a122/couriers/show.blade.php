@extends('a122.layouts.admin')
@section('title', trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')))
@section('page-title', 'Kuryer profili')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn btn-primary flex items-center gap-2">
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
            @if($courier->photo)
                <img
                    src="{{ Str::startsWith($courier->photo, 'http') ? $courier->photo : asset('storage/' . $courier->photo) }}"
                    alt="{{ trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) }}"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-blue-100 dark:ring-blue-500/20"
                >
            @else
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    {{ strtoupper(substr($courier->first_name ?? 'K', 0, 1)) }}
                </div>
            @endif
        </div>

        {{-- Main Info --}}
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold">
                    {{ trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: '—' }}
                </h2>
                @if($courier->status === 'approved')
                    <span class="badge badge-success">Tasdiqlangan</span>
                @elseif($courier->status === 'pending')
                    <span class="badge badge-warning">Kutilmoqda</span>
                @elseif($courier->status === 'rejected')
                    <span class="badge badge-danger">Rad etilgan</span>
                @else
                    <span class="badge badge-muted">{{ $courier->status ?? '—' }}</span>
                @endif
            </div>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                @if($courier->phone_number ?? $courier->phone)
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        {{ $courier->phone_number ?? $courier->phone }}
                    </span>
                @endif
                @if($courier->region)
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        {{ $courier->region }}
                    </span>
                @endif
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    {{ $courier->created_at ? $courier->created_at->format('d.m.Y') : '—' }}
                </span>
            </div>
        </div>

        {{-- Approve / Reject --}}
        <div class="flex items-center gap-2 shrink-0">
            @if($courier->status !== 'approved')
                <form method="POST" action="{{ route('admin.couriers.approve', $courier) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            @endif
            @if($courier->status !== 'rejected')
                <form method="POST" action="{{ route('admin.couriers.reject', $courier) }}"
                      onsubmit="return confirm('Kuryerni rad etishga ishonchingiz komilmi?')">
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
            <i data-lucide="package" class="w-6 h-6 text-blue-500"></i>
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
            <p class="text-2xl font-bold">{{ number_format($totalEarned, 0, '.', ' ') }}</p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-500/10 flex items-center justify-center">
            <i data-lucide="wallet" class="w-6 h-6 text-yellow-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Balans</p>
            <p class="text-2xl font-bold">{{ number_format($courier->balance ?? 0, 0, '.', ' ') }}</p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>
</div>

{{-- Recent Orders --}}
<div class="card p-5">
    <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foydalanuvchi</th>
                        <th>Sana</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="text-gray-500 text-sm">#{{ $order->id }}</td>
                            <td>
                                @if($order->user)
                                    {{ trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—' }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">
                                {{ $order->created_at ? $order->created_at->format('d.m.Y H:i') : '—' }}
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
@endsection
