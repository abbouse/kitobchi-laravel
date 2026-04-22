@extends('a122.layouts.admin')
@section('title', 'Buyurtma #' . $sellerOrder->id)
@section('page-title', 'Sotuvchi buyurtmasi')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="{{ route('admin.seller-orders.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
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

@php
    $customerName = trim(($sellerOrder->client?->name ?? '') . ' ' . ($sellerOrder->client?->lastname ?? '')) ?: ($address['fullName'] ?? '—');
    $customerPhone = $sellerOrder->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
    $statusVal = $sellerOrder->status ?? 0;
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Info Card --}}
    <div class="xl:col-span-2 card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-gray-400"></i>
            Buyurtma ma'lumotlari
        </h3>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 mb-1">Buyurtma ID</dt>
                <dd class="font-semibold">#{{ $sellerOrder->id }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sana</dt>
                <dd>{{ $sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : '—' }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Asosiy buyurtma</dt>
                <dd class="font-semibold">#{{ $sellerOrder->order_id }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sotuvchi</dt>
                <dd class="font-semibold">{{ $sellerOrder->seller->shop_name ?? '—' }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Summa</dt>
                <dd class="font-bold text-lg">{{ number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') }} UZS</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mijoz ismi</dt>
                <dd>{{ $customerName }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mijoz telefoni</dt>
                <dd>{{ $customerPhone }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Yetkazib berish turi</dt>
                <dd>{{ $summary['delivery_type'] }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Mahsulotlar soni</dt>
                <dd>{{ $summary['items_count'] }} ta</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                <dd>
                    @if($statusVal == 0)
                        <span class="badge badge-muted">{{ $statuses[0]['label'] ?? "To'lov jarayonida" }}</span>
                    @elseif($statusVal == 1)
                        <span class="badge badge-info">{{ $statuses[1]['label'] ?? 'Yangi' }}</span>
                    @elseif($statusVal == 2)
                        <span class="badge badge-warning">{{ $statuses[2]['label'] ?? "Do'kon qabul qildi" }}</span>
                    @elseif($statusVal == 3)
                        <span class="badge badge-success">{{ $statuses[3]['label'] ?? "Kuryerga berildi" }}</span>
                    @elseif($statusVal == 4)
                        <span class="badge badge-danger">{{ $statuses[4]['label'] ?? 'Bekor qilindi' }}</span>
                    @else
                        <span class="badge badge-muted">{{ $statusVal }}</span>
                    @endif
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Manzil</dt>
                <dd>{{ $address['fullAddress'] ?? 'Manzil kiritilmagan' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Status Update --}}
    <div class="card p-5 h-fit">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-5 h-5 text-gray-400"></i>
            Holatni o'zgartirish
        </h3>

        @if($errors->any())
            <div class="mb-3 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-3 py-2 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.seller-orders.status', $sellerOrder) }}">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label class="text-xs text-gray-500 mb-1 block">Yangi holat</label>
                <select name="status" class="select">
                    @foreach($statuses as $value => $status)
                        <option value="{{ $value }}" @selected(($sellerOrder->status ?? '') == $value)>
                            {{ $status['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </form>
    </div>

</div>

<div class="mt-6 card p-5">
    <h3 class="font-bold text-base mb-4 flex items-center gap-2">
        <i data-lucide="package-search" class="w-5 h-5 text-gray-400"></i>
        Sotuvchiga tegishli mahsulotlar
    </h3>

    @if($items->isEmpty())
        <div class="text-sm text-gray-500">Bu buyurtma uchun sotuvchiga tegishli mahsulotlar topilmadi.</div>
    @else
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Mahsulot</th>
                        <th>Turi</th>
                        <th>Miqdor</th>
                        <th>Narx</th>
                        <th>Jami</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $item['name'] }}</div>
                                @if(!empty($item['author']))
                                    <div class="text-xs text-gray-500">{{ $item['author'] }}</div>
                                @endif
                            </td>
                            <td class="capitalize">{{ $item['type'] }}</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>{{ number_format((float) $item['price'], 0, '.', ' ') }} UZS</td>
                            <td class="font-semibold">{{ number_format((float) $item['price'] * (int) $item['quantity'], 0, '.', ' ') }} UZS</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
