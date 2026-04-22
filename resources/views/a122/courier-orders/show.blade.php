@extends('a122.layouts.admin')
@section('title', 'Kuryer buyurtmasi #' . $courierOrder->id)
@section('page-title', 'Kuryer buyurtmasi')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.courier-orders.index') }}" class="btn btn-secondary flex items-center gap-2 w-fit">
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Kuryer ma'lumotlari --}}
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="bike" class="w-5 h-5 text-gray-400"></i>
            Kuryer ma'lumotlari
        </h3>
        @if($courierOrder->courier)
            <div class="flex items-center gap-4 mb-4">
                @if($courierOrder->courier->photo)
                    <img
                        src="{{ Str::startsWith($courierOrder->courier->photo, 'http') ? $courierOrder->courier->photo : asset('storage/' . $courierOrder->courier->photo) }}"
                        alt="Kuryer"
                        class="w-14 h-14 rounded-full object-cover ring-4 ring-blue-100 dark:ring-blue-500/20"
                    >
                @else
                    <div class="w-14 h-14 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-xl font-bold text-gray-500 dark:text-gray-300">
                        {{ strtoupper(substr($courierOrder->courier->first_name ?? 'K', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="font-bold text-base">
                        {{ trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: '—' }}
                    </div>
                    <div class="text-sm text-gray-500">{{ $courierOrder->courier->phone_number ?? $courierOrder->courier->phone ?? '—' }}</div>
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-500 mb-1">Viloyat</dt>
                    <dd>{{ $courierOrder->courier->region ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                    <dd>
                        @php $cs = $courierOrder->courier->status ?? ''; @endphp
                        @if($cs === 'approved')
                            <span class="badge badge-success">Tasdiqlangan</span>
                        @elseif($cs === 'pending')
                            <span class="badge badge-warning">Kutilmoqda</span>
                        @elseif($cs === 'rejected')
                            <span class="badge badge-danger">Rad etilgan</span>
                        @else
                            <span class="badge badge-muted">{{ $cs ?: '—' }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        @else
            <p class="text-gray-400 text-sm">Kuryer ma'lumotlari mavjud emas</p>
        @endif
    </div>

    {{-- Buyurtma ma'lumotlari --}}
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4 flex items-center gap-2">
            <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
            Buyurtma ma'lumotlari
        </h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 mb-1">Buyurtma ID</dt>
                <dd class="font-semibold">#{{ $courierOrder->id }}</dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Holat</dt>
                <dd>
                    @php $status = $courierOrder->status ?? ''; @endphp
                    @if(isset($statuses[$status]))
                        <span class="badge {{ $statuses[$status]['badge'] }}">{{ $statuses[$status]['label'] }}</span>
                    @else
                        <span class="badge badge-muted">{{ $status ?: '—' }}</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Foydalanuvchi</dt>
                <dd>
                    @if($courierOrder->user)
                        <div class="font-medium">{{ trim(($courierOrder->user->first_name ?? $courierOrder->user->name ?? '') . ' ' . ($courierOrder->user->last_name ?? '')) ?: '—' }}</div>
                        @if($courierOrder->user->phone_number ?? $courierOrder->user->phone)
                            <div class="text-xs text-gray-500">{{ $courierOrder->user->phone_number ?? $courierOrder->user->phone }}</div>
                        @endif
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-xs text-gray-500 mb-1">Sana</dt>
                <dd>{{ $courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : '—' }}</dd>
            </div>

            @if($courierOrder->address ?? $courierOrder->delivery_address)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Manzil</dt>
                <dd>{{ $courierOrder->address ?? $courierOrder->delivery_address }}</dd>
            </div>
            @endif

            @if($courierOrder->amount ?? $courierOrder->total)
            <div>
                <dt class="text-xs text-gray-500 mb-1">Summa</dt>
                <dd class="font-bold text-base">{{ number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ') }} UZS</dd>
            </div>
            @endif

            @if($courierOrder->note ?? $courierOrder->comment)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 mb-1">Izoh</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $courierOrder->note ?? $courierOrder->comment }}</dd>
            </div>
            @endif
        </dl>
    </div>

</div>
@endsection
