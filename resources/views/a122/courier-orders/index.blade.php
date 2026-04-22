@extends('a122.layouts.admin')
@section('title', 'Kuryer buyurtmalari')
@section('page-title', 'Kuryer buyurtmalari')

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
        <div class="a122-index-header__title">Kuryer buyurtmalari</div>
        <div class="a122-index-header__meta">{{ $orders->total() }} ta yetkazib berish yozuvi topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="ID, kuryer yoki foydalanuvchi bo‘yicha qidiring">
        </form>
    </div>
</div>

<div class="tab-pills fade-up mb-3">
    @foreach([
        'pay_process' => ["To'lov jarayonida", $counts['pay_process'] ?? 0],
        'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
        'in_delivery' => ["Yo'lda", $counts['in_delivery'] ?? 0],
        'delivered' => ['Yetkazildi', $counts['delivered'] ?? 0],
        'rejected' => ['Bekor qilingan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ ($tab ?? 'all') === $key ? 'active' : '' }}">
            {{ $label }} <span>{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kuryer</th>
                    <th>Foydalanuvchi</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="text-gray-500 text-sm">#{{ $order->id }}</td>
                        <td>
                            @if($order->courier)
                                <div class="font-semibold">
                                    {{ trim(($order->courier->first_name ?? '') . ' ' . ($order->courier->last_name ?? '')) ?: '—' }}
                                </div>
                                @if($order->courier->phone_number ?? $order->courier->phone)
                                    <div class="text-xs text-gray-500">{{ $order->courier->phone_number ?? $order->courier->phone }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            @if($order->user)
                                <div class="font-medium">
                                    {{ trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—' }}
                                </div>
                                @if($order->user->phone_number ?? $order->user->phone)
                                    <div class="text-xs text-gray-500">{{ $order->user->phone_number ?? $order->user->phone }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.courier-orders.status', $order) }}" class="inline-flex">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                                    @foreach($statuses as $value => $statusItem)
                                        <option value="{{ $value }}" @selected(($order->status ?? '') === $value)>{{ $statusItem['label'] }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            {{ $order->created_at ? $order->created_at->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.courier-orders.show', $order) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday kuryer buyurtmasi topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($orders->hasPages())
    <div class="mt-4">{{ $orders->links('a122.partials.pagination') }}</div>
@endif
@endsection
