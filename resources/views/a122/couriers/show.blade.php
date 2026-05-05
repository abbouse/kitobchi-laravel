@extends('a122.layouts.admin')
@section('title', trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')))
@section('page-title', 'Kuryer profili')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.couriers.reset-password', $courier) }}" onsubmit="return confirm('Yangi parol kuryerning telefon raqamiga SMS orqali yuborilsinmi?')">
            @csrf
            <button type="submit" class="btn btn-warning flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
        </a>
    </div>
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
                @elseif($courier->status === 'blocked')
                    <span class="badge badge-danger flex items-center gap-1">
                        <i data-lucide="ban" class="w-3.5 h-3.5"></i> Bloklangan
                    </span>
                @else
                    <span class="badge badge-muted">{{ $courier->status ?? '—' }}</span>
                @endif

                {{-- Faol ogohlantirishlar soni --}}
                @if(($warningCount ?? 0) > 0)
                    @php
                        $wnClass = $warningCount >= 3
                            ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                            : ($warningCount >= 2
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400');
                    @endphp
                    <span class="badge {{ $wnClass }} flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        Ogohlantirish {{ $warningCount }}/3
                    </span>
                @endif
                {{-- Transport turi --}}
                <span class="badge bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 flex items-center gap-1">
                    <i data-lucide="{{ $courier->transport_icon }}" class="w-3.5 h-3.5"></i> {{ $courier->transport_label }}
                </span>
                {{-- Verifikatsiya holati --}}
                @php
                    $vColor = $courier->verification_color;
                    $vClass = match($vColor) {
                        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'amber'   => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                        'red'     => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                        default   => 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400',
                    };
                    $vIcon = match($courier->verification_status) {
                        'verified'   => 'shield-check',
                        'pending'    => 'shield-question',
                        'rejected'   => 'shield-x',
                        default      => 'shield',
                    };
                @endphp
                <span class="badge {{ $vClass }} flex items-center gap-1">
                    <i data-lucide="{{ $vIcon }}" class="w-3.5 h-3.5"></i> {{ $courier->verification_label }}
                </span>
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

        {{-- Approve / Warn / Unblock --}}
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            @if($courier->status !== 'approved' && $courier->status !== 'blocked')
                <form method="POST" action="{{ route('admin.couriers.approve', $courier) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            @endif

            @if($courier->status === 'blocked')
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.remove('hidden')"
                        class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            @elseif($courier->status === 'approved')
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.remove('hidden')"
                        class="btn btn-warning flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Ogohlantirish
                </button>
            @endif

            @if($courier->status !== 'rejected' && $courier->status !== 'blocked')
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

@php
    $courierLat = (float) ($courier->current_lat ?? 0);
    $courierLon = (float) ($courier->current_lon ?? 0);
    $hasCourierLocation = !($courierLat == 0.0 && $courierLon == 0.0);
    $courierStaticMap = $hasCourierLocation
        ? 'https://static-maps.yandex.ru/1.x/?lang=ru_RU&size=650,280&z=13&l=map&pt='.$courierLon.','.$courierLat.',pm2blm'
        : null;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="map-pinned" class="w-5 h-5 text-blue-500"></i>
                Hozirgi joylashuv
            </h3>
            @if($courier->location_updated_at)
                <span class="text-xs text-gray-400">{{ $courier->location_updated_at->format('d.m.Y H:i') }}</span>
            @endif
        </div>
        @if($hasCourierLocation)
            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-white/10">
                <img src="{{ $courierStaticMap }}" alt="Courier location map" class="w-full h-64 object-cover">
            </div>
            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ number_format($courierLat, 6) }}, {{ number_format($courierLon, 6) }}
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-white/10 px-4 py-10 text-sm text-gray-400 text-center">
                Kuryerning joriy lokatsiyasi hali kelmagan.
            </div>
        @endif
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="route" class="w-5 h-5 text-emerald-500"></i>
                Aktiv yo‘nalishlar
            </h3>
            <span class="text-xs text-gray-400">{{ $activeOrders->count() }} ta aktiv</span>
        </div>
        @if($activeOrders->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-white/10 px-4 py-10 text-sm text-gray-400 text-center">
                Aktiv buyurtmalar yo‘q.
            </div>
        @else
            <div class="space-y-4">
                @foreach($activeOrders as $activeOrder)
                    @php
                        $routePoints = collect($activeOrder->route_points ?? []);
                        $firstPoint = $routePoints->first();
                        $lastPoint = $routePoints->last();
                        $pins = [];
                        if ($hasCourierLocation) {
                            $pins[] = $courierLon.','.$courierLat.',pm2blm';
                        }
                        foreach($routePoints as $point) {
                            $pins[] = ((float) ($point['lon'] ?? 0)).','.((float) ($point['lat'] ?? 0)).','.($point['type'] === 'customer' ? 'pm2grm' : 'pm2orm');
                        }
                        $routeMap = !empty($pins)
                            ? 'https://static-maps.yandex.ru/1.x/?lang=ru_RU&size=650,240&z=11&l=map&pt='.implode('~', $pins)
                            : null;
                    @endphp
                    <div class="rounded-2xl border border-gray-200 dark:border-white/10 p-4">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white">Order #{{ $activeOrder->order_id }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($activeOrder->route_distance_km ?? 0, 2) }} km route</div>
                            </div>
                            <a href="{{ route('admin.courier-orders.show', $activeOrder) }}" class="text-xs text-[var(--p-accent)] hover:underline">Ochish</a>
                        </div>
                        @if($routeMap)
                            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 mb-3">
                                <img src="{{ $routeMap }}" alt="Courier route map" class="w-full h-52 object-cover">
                            </div>
                        @endif
                        <div class="space-y-2 text-sm">
                            @foreach($routePoints as $idx => $point)
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full {{ ($point['type'] ?? 'shop') === 'customer' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700' }}">{{ $idx + 1 }}</span>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $point['name'] ?? 'Nuqta' }}</div>
                                        @if(!empty($point['address']))
                                            <div class="text-xs text-gray-500 truncate">{{ $point['address'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ══ TRANSPORT + KARTA + HUJJATLAR ════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Transport va karta kartochkasi --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="{{ $courier->transport_icon }}" class="w-5 h-5 text-blue-500"></i>
                Transport va karta
            </h3>
            <a href="{{ route('admin.couriers.edit', $courier) }}#transport" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
            <dt class="col-span-1 text-gray-500">Transport</dt>
            <dd class="col-span-2">{{ $courier->transport_label }}</dd>

            @if(in_array($courier->transport_type, ['motorcycle', 'car']) && ($courier->vehicle_brand || $courier->vehicle_plate_number))
                <dt class="col-span-1 text-gray-500">Vosita</dt>
                <dd class="col-span-2">
                    {{ trim(($courier->vehicle_brand ?? '') . ' ' . ($courier->vehicle_model ?? '')) ?: '—' }}
                    @if($courier->vehicle_color) · {{ $courier->vehicle_color }} @endif
                </dd>
                @if($courier->vehicle_plate_number)
                    <dt class="col-span-1 text-gray-500">Davlat raqami</dt>
                    <dd class="col-span-2 font-mono">{{ $courier->vehicle_plate_number }}</dd>
                @endif
                @if($courier->driver_license_expires_at)
                    <dt class="col-span-1 text-gray-500">Guvohnoma tugaydi</dt>
                    <dd class="col-span-2">
                        {{ $courier->driver_license_expires_at->format('Y-m-d') }}
                        @php $ld = (int) now()->startOfDay()->diffInDays($courier->driver_license_expires_at, false); @endphp
                        <span class="text-xs {{ $ld < 0 ? 'text-red-500' : ($ld <= 60 ? 'text-amber-500' : 'text-gray-400') }}">
                            ({{ $ld < 0 ? abs($ld).' kun oldin tugagan' : $ld.' kun qoldi' }})
                        </span>
                    </dd>
                @endif
            @endif

            @if($courier->payment_card)
                <dt class="col-span-1 text-gray-500 mt-2">Karta</dt>
                <dd class="col-span-2 font-mono mt-2">{{ $courier->masked_card }}</dd>
            @endif
            @if($courier->card_holder)
                <dt class="col-span-1 text-gray-500">Karta egasi</dt>
                <dd class="col-span-2">{{ $courier->card_holder }}</dd>
            @endif
            @if($courier->inn)
                <dt class="col-span-1 text-gray-500">STIR</dt>
                <dd class="col-span-2 font-mono">{{ $courier->inn }}</dd>
            @endif
            @if($courier->home_address)
                <dt class="col-span-1 text-gray-500">Manzil</dt>
                <dd class="col-span-2">{{ $courier->home_address }}</dd>
            @endif
            @if($courier->birthdate)
                <dt class="col-span-1 text-gray-500">Tug'ilgan</dt>
                <dd class="col-span-2">{{ $courier->birthdate->format('Y-m-d') }}</dd>
            @endif
        </dl>
    </div>

    {{-- Hujjatlar kartochkasi --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="folder" class="w-5 h-5 text-blue-500"></i>
                Hujjatlar
                @if($courier->documents->count() > 0)
                    <span class="text-xs text-gray-400">({{ $courier->documents->count() }})</span>
                @endif
            </h3>
            <a href="{{ route('admin.couriers.edit', $courier) }}#documents" class="text-xs text-blue-500 hover:underline">Boshqarish</a>
        </div>
        @if($courier->documents->isEmpty())
            <p class="text-sm text-gray-400 text-center py-6">Hujjatlar yuklanmagan.</p>
        @else
            <div class="space-y-2">
                @foreach($courier->documents->take(6) as $doc)
                    <a href="{{ $doc->file_url }}" target="_blank"
                       class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5">
                        <i data-lucide="{{ $doc->type_icon }}" class="w-4 h-4 text-blue-500 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $doc->type_label }}</div>
                            @if($doc->original_name)
                                <div class="text-[10px] text-gray-400 truncate">{{ $doc->original_name }}</div>
                            @endif
                        </div>
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                    </a>
                @endforeach
            </div>
        @endif

        @if($courier->verification_notes)
            <div class="mt-4 p-3 rounded-lg bg-amber-50/60 dark:bg-amber-400/5 border border-amber-200 dark:border-amber-400/20 text-xs">
                <p class="font-semibold text-amber-700 dark:text-amber-300 mb-1">Verifikatsiya izohlari</p>
                <p class="text-gray-600 dark:text-gray-400">{{ $courier->verification_notes }}</p>
            </div>
        @endif
    </div>
</div>

{{-- ══ OGOHLANTIRISHLAR TARIXI ════════════════════════════════════════ --}}
<div class="card p-5 mb-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="font-bold text-base flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
            Ogohlantirish va blok tarixi
        </h3>
        <div class="flex items-center gap-2 flex-wrap">
            @php
                $progressClass = match(true) {
                    ($warningCount ?? 0) >= 3 => 'text-red-600 dark:text-red-400',
                    ($warningCount ?? 0) >= 2 => 'text-amber-600 dark:text-amber-400',
                    ($warningCount ?? 0) >= 1 => 'text-yellow-600 dark:text-yellow-400',
                    default                  => 'text-gray-500',
                };
            @endphp
            <span class="text-xs {{ $progressClass }}">
                Faol ogohlantirishlar: <strong>{{ $warningCount ?? 0 }}/3</strong>
            </span>
            @if($courier->status === 'approved')
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.remove('hidden')"
                        class="btn btn-warning btn-sm flex items-center gap-1 text-xs">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Ogohlantirish
                </button>
            @endif
            @if($courier->status === 'blocked')
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.remove('hidden')"
                        class="btn btn-primary btn-sm flex items-center gap-1 text-xs">
                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Blokdan chiqarish
                </button>
            @endif
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="mb-4">
        <div class="h-2 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
            @php
                $pct = min(100, (($warningCount ?? 0) / 3) * 100);
                $barClass = match(true) {
                    ($warningCount ?? 0) >= 3 => 'bg-red-500',
                    ($warningCount ?? 0) >= 2 => 'bg-amber-500',
                    ($warningCount ?? 0) >= 1 => 'bg-yellow-500',
                    default                  => 'bg-gray-300 dark:bg-white/20',
                };
            @endphp
            <div class="h-full {{ $barClass }} transition-all" style="width: {{ $pct }}%"></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-1">3 ta ogohlantirishdan keyin kuryer avtomatik bloklanadi.</p>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Turi</th>
                        <th>Sarlavha</th>
                        <th>Xabar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banLogs as $banLog)
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                {{ $banLog->created_at ? $banLog->created_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $banLog->type === 'warning' ? 'badge-warning' : 'badge-success' }}">
                                    {{ $banLog->type === 'warning' ? 'Ogohlantirish' : 'Blokdan chiqarish' }}
                                </span>
                            </td>
                            <td>{{ $banLog->title ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-300">{{ $banLog->message ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-6">
                                Ogohlantirishlar tarixi topilmadi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Warn Modal --}}
<div id="warn-modal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
                Kuryerga ogohlantirish
            </h3>
            <button type="button"
                    onclick="document.getElementById('warn-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.couriers.warn', $courier) }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Sarlavha *</label>
                <input type="text" name="title" maxlength="120" required
                       placeholder="Masalan: Buyurtma kechiktirish"
                       class="form-input w-full">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Xabar *</label>
                <textarea name="message" rows="4" maxlength="2000" required
                          placeholder="Kuryerga yuboriladigan ogohlantirish matni..."
                          class="form-input w-full"></textarea>
            </div>
            <div class="rounded-lg bg-amber-50 dark:bg-amber-400/5 border border-amber-200 dark:border-amber-400/20 p-3 text-xs text-amber-700 dark:text-amber-300">
                <i data-lucide="info" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                Eslatma: 3-marta ogohlantirilganda kuryer avtomatik bloklanadi.
                Hozirgi soni: <strong>{{ $warningCount ?? 0 }}/3</strong>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.add('hidden')"
                        class="btn btn-secondary">Bekor qilish</button>
                <button type="submit" class="btn btn-warning">Yuborish</button>
            </div>
        </form>
    </div>
</div>

{{-- Unblock Modal --}}
<div id="unblock-modal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i data-lucide="unlock" class="w-5 h-5 text-emerald-500"></i>
                Kuryerni blokdan chiqarish
            </h3>
            <button type="button"
                    onclick="document.getElementById('unblock-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.couriers.unblock', $courier) }}" class="space-y-3">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Izoh (ixtiyoriy)</label>
                <textarea name="message" rows="3" maxlength="2000"
                          placeholder="Blokdan chiqarish sababi..."
                          class="form-input w-full"></textarea>
            </div>
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-400/5 border border-emerald-200 dark:border-emerald-400/20 p-3 text-xs text-emerald-700 dark:text-emerald-300">
                <i data-lucide="info" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                Blokdan chiqarilgandan keyin ogohlantirishlar hisobi qayta boshlanadi.
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.add('hidden')"
                        class="btn btn-secondary">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Blokdan chiqarish</button>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Courier-order</th>
                            <th>Foydalanuvchi</th>
                            <th>To'lov</th>
                            <th>Holat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            @php
                                $paymentLabel = match ((int) data_get($order, 'order.paymentStatus')) {
                                    2 => 'Karta',
                                    1 => 'Tasdiq kutilmoqda',
                                    0 => 'Naqd',
                                    default => '—',
                                };
                                $statusLabel = match ((string) data_get($order, 'order.status')) {
                                    'A', 'P' => 'Kutilmoqda',
                                    'B' => "Yo'lda",
                                    'C' => 'Yetkazildi',
                                    'F' => 'Bekor qilingan',
                                    default => (string) ($order->status ?: '—'),
                                };
                            @endphp
                            <tr>
                                <td class="text-sm">
                                    <a href="{{ route('admin.courier-orders.show', $order) }}" class="font-semibold text-[var(--p-accent)] hover:underline">#{{ $order->id }}</a>
                                    @if($order->order_id)
                                        <div class="text-xs text-[var(--p-muted)] mt-1">
                                            Order: <a href="{{ route('admin.orders.show', $order->order_id) }}" class="hover:underline">#{{ $order->order_id }}</a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($order->user)
                                        <a href="{{ route('admin.users.show', $order->user_id) }}" class="font-semibold hover:underline">
                                            {{ trim(($order->user->name ?? '').' '.($order->user->lastname ?? '')) ?: 'Foydalanuvchi' }}
                                        </a>
                                        <div class="text-xs text-[var(--p-muted)] mt-1">{{ $order->user->phone_number ?: 'Telefon yo‘q' }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="text-sm text-[var(--p-muted)]">{{ $paymentLabel }}</td>
                                <td><span class="badge badge-muted">{{ $statusLabel }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-6">Buyurtmalar yo'q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi tranzaksiyalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Net</th>
                            <th>Holat</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td class="text-gray-500 text-sm">#{{ $tx->id }}</td>
                                <td class="font-semibold">{{ number_format((float) ($tx->netAmount ?? $tx->amount ?? 0), 0, '.', ' ') }} UZS</td>
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
                                <td class="text-sm text-gray-500">{{ $tx->created_at ? $tx->created_at->format('d.m.Y H:i') : '—' }}</td>
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
