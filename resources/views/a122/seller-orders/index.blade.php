@extends('a122.layouts.admin')
@section('title', 'Sotuvchi buyurtmalari')
@section('page-title', 'Sotuvchi buyurtmalari')

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
        <div class="a122-index-header__title">Sotuvchi buyurtmalari</div>
        <div class="a122-index-header__meta">{{ $orders->total() }} ta buyurtma topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="ID, sotuvchi yoki mijoz bo‘yicha qidiring">
        </form>
    </div>
</div>

{{-- Tabs --}}
<div class="flex items-center gap-2 mb-4 flex-wrap">
    @php
        $tabs = ['all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0]];
        foreach ($statuses as $value => $statusItem) {
            $tabs[(string) $value] = ['label' => $statusItem['label'], 'count' => $counts[$value] ?? 0];
        }
    @endphp
    @foreach($tabs as $key => $tabItem)
        <a
            href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
            class="btn {{ $tab == $key ? 'btn-primary' : 'btn-secondary' }} flex items-center gap-2 text-sm"
        >
            {{ $tabItem['label'] }}
            <span class="badge {{ $tab == $key ? 'badge-info' : 'badge-muted' }}">{{ $tabItem['count'] }}</span>
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
                    <th>Mijoz</th>
                    <th>Summa</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $customerName = trim(($order->client?->name ?? '') . ' ' . ($order->client?->lastname ?? ''));
                        $address = collect($order->order?->address ?? [])->first();
                        $fallbackName = $address['fullName'] ?? '—';
                        $customerPhone = $order->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
                        $itemsCount = collect($order->order?->items ?? [])->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)->sum(fn ($item) => (int) ($item['count_item'] ?? 1));
                        $orderAmount = (float) ($order->amount ?? 0);
                    @endphp
                    @php($statusCode = $order->status_code ?? \App\Enums\SellerOrderStatusCode::fromLegacy($order->status ?? 1)->value)
                    <tr>
                        <td class="text-gray-500 text-sm">
                            <div>#{{ $order->id }}</div>
                            <div class="text-[11px] text-gray-400">ORD #{{ $order->order_id }}</div>
                        </td>
                        <td>
                            <div class="font-semibold">{{ $order->seller->shop_name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ trim(($order->seller->firstname ?? '') . ' ' . ($order->seller->lastname ?? '')) ?: 'Sotuvchi' }}</div>
                        </td>
                        <td>
                            <div class="font-medium">{{ $customerName ?: $fallbackName }}</div>
                            <div class="text-xs text-gray-500">{{ $customerPhone }}</div>
                        </td>
                        <td class="font-semibold text-sm whitespace-nowrap">
                            <div>{{ number_format($orderAmount, 0, '.', ' ') }} UZS</div>
                            <div class="text-xs text-gray-500">{{ $itemsCount }} ta mahsulot</div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.seller-orders.status', $order) }}" class="inline-flex">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                                    @foreach($statuses as $value => $statusItem)
                                        <option value="{{ $value }}" @selected($statusCode === $value)>{{ $statusItem['label'] }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">
                            {{ $order->created_at ? $order->created_at->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <a href="{{ route('admin.seller-orders.show', $order) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-8">Hech qanday buyurtma topilmadi</td>
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
