@extends('a122.layouts.admin')
@section('title', $seller->shop_name)
@section('page-title', 'Sotuvchi profili')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="{{ route('admin.sellers.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        @if($seller->status === 'blocked')
            <form method="POST" action="{{ route('admin.sellers.unblock', $seller) }}" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.sellers.reset-password', $seller) }}" onsubmit="return confirm('Yangi parol sotuvchining telefon raqamiga SMS orqali yuborilsinmi?')">
            @csrf
            <button type="submit" class="btn btn-warning flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="{{ route('admin.sellers.edit', $seller) }}" class="btn btn-primary flex items-center gap-2">
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
                @elseif($seller->status === 'blocked')
                    <span class="badge badge-danger">Bloklangan</span>
                @else
                    <span class="badge badge-muted">{{ $seller->status }}</span>
                @endif
                <span class="badge {{ $warningCount >= 3 ? 'badge-danger' : ($warningCount > 0 ? 'badge-warning' : 'badge-muted') }}">
                    {{ $warningCount }}/3 ogohlantirish
                </span>
                @if($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isFuture())
                    <span class="badge bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300 flex items-center gap-1">
                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                        Premium · {{ $seller->isPremiumExpiresAt->format('Y-m-d') }}
                    </span>
                @elseif($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isPast())
                    <span class="badge bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400 flex items-center gap-1">
                        <i data-lucide="crown-off" class="w-3.5 h-3.5"></i>
                        Premium tugagan
                    </span>
                @endif
                @if($seller->isVerified)
                    <span class="badge bg-sky-100 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300 flex items-center gap-1">
                        <i data-lucide="badge-check" class="w-3.5 h-3.5"></i> Verified
                    </span>
                @endif
                {{-- Shartnoma imzolangan/imzolanmagan: tezkor ko'rinish uchun
                     contract_signed boolean. Sana yo'q bo'lsa ham ishlaydi. --}}
                @if($seller->contract_signed)
                    <span class="badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center gap-1">
                        <i data-lucide="file-signature" class="w-3.5 h-3.5"></i> Shartnoma imzolangan
                    </span>
                @else
                    <span class="badge bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400 flex items-center gap-1">
                        <i data-lucide="file-pen-line" class="w-3.5 h-3.5"></i> Imzolanmagan
                    </span>
                @endif
                @if($seller->contract_expires_at)
                    @php
                        $cDays = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false);
                        if ($seller->contract_status === 'terminated') {
                            $cBadgeCls = 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300';
                            $cIcon = 'file-x';
                            $cText = 'Shartnoma to\'xtatilgan';
                        } elseif ($cDays < 0) {
                            $cBadgeCls = 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
                            $cIcon = 'file-warning';
                            $cText = 'Shartnoma tugagan ('.abs($cDays).' kun)';
                        } elseif ($cDays <= 30) {
                            $cBadgeCls = 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
                            $cIcon = 'clock-alert';
                            $cText = 'Shartnoma '.$cDays.' kunda tugaydi';
                        } else {
                            $cBadgeCls = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
                            $cIcon = 'file-check';
                            $cText = 'Shartnoma faol';
                        }
                    @endphp
                    <span class="badge {{ $cBadgeCls }} flex items-center gap-1">
                        <i data-lucide="{{ $cIcon }}" class="w-3.5 h-3.5"></i> {{ $cText }}
                    </span>
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

{{-- ── Filial QR'lari — "Do'kon ichida" rejimi uchun ───────────────── --}}
@if(!$seller->parent_id && $storeSeller->locations->isNotEmpty())
<div class="card p-5 mb-6">
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <i data-lucide="qr-code" class="w-5 h-5 text-teal-500"></i>
                <h3 class="text-base font-semibold">Filial QR kodlari</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Har bir filial uchun alohida QR ishlatiladi. Mijoz qaysi filialdagi QR'ni skaner qilsa, aynan o'sha filialning "do'kon ichida" rejimi boshlanadi.
                <span class="font-medium">Asosiy filial</span> belgisi esa faqat kuryerlar borishi kerak bo'lgan default manzilni bildiradi.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        @foreach($storeSeller->locations as $location)
            @php
                $qrUrl = $location->qr_url;
                $qrImgSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=520x520&margin=22&format=png&ecc=Q&data=' . urlencode($qrUrl);
            @endphp
            <div class="rounded-[28px] border border-slate-200 dark:border-slate-700 bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.12),_transparent_42%),linear-gradient(135deg,#ffffff,_#f8fafc)] dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-start gap-4 flex-wrap">
                    <div class="shrink-0 rounded-[24px] bg-white p-3 border border-slate-200 shadow-sm">
                        <div class="rounded-2xl overflow-hidden bg-white">
                            <img src="{{ $qrImgSrc }}" alt="Filial QR" width="170" height="170" loading="lazy">
                        </div>
                    </div>
                    <div class="flex-1 min-w-[250px]">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span class="badge {{ $location->is_main ? 'badge-warning' : 'badge-secondary' }}">
                                {{ $location->is_main ? 'Asosiy filial' : 'Filial' }}
                            </span>
                            <span class="badge badge-light">ID: {{ $location->id }}</span>
                            @if($location->is_main)
                                <span class="badge badge-light">Kuryer default filial</span>
                            @endif
                        </div>

                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-1 leading-6">
                            {{ $location->fullAddress }}
                        </p>
                        @if($location->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ $location->description }}</p>
                        @endif

                        <dl class="grid grid-cols-3 gap-2 text-xs mb-3">
                            <dt class="text-slate-500">URL</dt>
                            <dd class="col-span-2 font-mono break-all text-slate-700 dark:text-slate-300">{{ $qrUrl }}</dd>

                            <dt class="text-slate-500">Token</dt>
                            <dd class="col-span-2 font-mono text-slate-700 dark:text-slate-300">{{ $location->qr_token }}</dd>

                            @if($location->qr_rotated_at)
                                <dt class="text-slate-500">Yangilangan</dt>
                                <dd class="col-span-2">{{ $location->qr_rotated_at->format('Y-m-d H:i') }}</dd>
                            @endif
                        </dl>

                        <div class="flex items-center gap-2 flex-wrap pt-1">
                            <a href="{{ $qrImgSrc }}" target="_blank" rel="noopener"
                               class="btn btn-outline-primary btn-sm flex items-center gap-1">
                                <i data-lucide="external-link" class="w-4 h-4"></i> Ochish
                            </a>
                            <a href="{{ $qrImgSrc }}" download="kitobchi-location-{{ $location->id }}.png"
                               class="btn btn-outline-secondary btn-sm flex items-center gap-1">
                                <i data-lucide="download" class="w-4 h-4"></i> Yuklab olish
                            </a>
                            <form method="POST" action="{{ route('admin.sellers.locations.qr.rotate', [$storeSeller, $location]) }}"
                                  onsubmit="return confirm('Eski filial QR ishlamay qoladi. Yangi QR\'ni shu filialga almashtiramizmi?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning btn-sm flex items-center gap-1">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> QR'ni yangilash
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <h3 class="font-bold text-base">Ogohlantirish yuborish</h3>
                <p class="text-sm text-gray-500 mt-1">3 ta faol ogohlantirishdan keyin sotuvchi avtomatik bloklanadi.</p>
            </div>
            @if($isBlocked)
                <span class="badge badge-danger">Seller hozir bloklangan</span>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.sellers.warn', $seller) }}" class="grid grid-cols-1 gap-3">
            @csrf
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Sarlavha</label>
                <input name="title" class="input" maxlength="120" placeholder="Masalan: Qoida buzilishi" value="{{ old('title') }}">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Xabar</label>
                <textarea name="message" rows="4" class="input" placeholder="Sellerga ko‘rinadigan ogohlantirish matni">{{ old('message') }}</textarea>
            </div>
            <div class="flex items-center justify-end">
                <button type="submit" class="btn btn-warning flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i> Ogohlantirish yuborish
                </button>
            </div>
        </form>
    </div>

    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">Bloklash holati</h3>
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Do‘kon statusi</span>
                <span class="badge {{ $isBlocked ? 'badge-danger' : 'badge-success' }}">
                    {{ $isBlocked ? 'Bloklangan' : 'Faol' }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Faol ogohlantirishlar</span>
                <span class="font-semibold">{{ $warningCount }}/3</span>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-white/5 p-4 text-xs text-gray-500">
                Blokdan chiqarilganda ogohlantirish hisobi qayta boshlanadi. Eski ogohlantirishlar audit uchun tarixda saqlanadi.
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-blue-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Jami buyurtma</p>
            <p class="text-xl font-bold leading-tight">{{ number_format($orderCount, 0, '.', ' ') }}</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="circle-check" class="w-5 h-5 text-emerald-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Muvaffaqiyatli</p>
            <p class="text-xl font-bold leading-tight">{{ number_format($seller->successful_orders ?? 0, 0, '.', ' ') }}</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="banknote" class="w-5 h-5 text-green-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Balans</p>
            <p class="text-xl font-bold leading-tight">{{ number_format($seller->balance ?? 0, 0, '.', ' ') }}</p>
            <p class="text-[10px] text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Reyting</p>
            <p class="text-xl font-bold leading-tight">{{ number_format($seller->rating ?? 0, 2) }}</p>
            <p class="text-[10px] text-gray-400">{{ $seller->total_reviews ?? 0 }} sharh</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="timer" class="w-5 h-5 text-indigo-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Javob vaqti</p>
            <p class="text-xl font-bold leading-tight">{{ number_format($seller->response_time_hours ?? 0, 1) }}</p>
            <p class="text-[10px] text-gray-400">soat</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="book-open" class="w-5 h-5 text-purple-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Kitoblar</p>
            <p class="text-xl font-bold leading-tight">{{ $seller->books_count ?? 0 }}</p>
        </div>
    </div>
</div>

{{-- Daromad + premium obuna info bir qatorda --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="trending-up" class="w-6 h-6 text-green-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Jami daromad (tasdiqlangan yechib olishlar)</p>
            <p class="text-2xl font-bold">{{ number_format($totalRevenue, 0, '.', ' ') }} <span class="text-sm text-gray-400 font-normal">UZS</span></p>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
            <i data-lucide="crown" class="w-6 h-6 text-amber-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Premium holati</p>
            @if($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isFuture())
                <p class="text-lg font-bold text-amber-600 dark:text-amber-300">Faol</p>
                <p class="text-xs text-gray-400">
                    Tugaydi: <span class="font-mono">{{ $seller->isPremiumExpiresAt->format('Y-m-d H:i') }}</span>
                    ({{ $seller->isPremiumExpiresAt->diffForHumans() }})
                </p>
            @else
                <p class="text-lg font-bold text-gray-400">Yo'q</p>
                <p class="text-xs text-gray-400">Tahrirlash sahifasidan berish mumkin</p>
            @endif
        </div>
    </div>
</div>

{{-- ══ SHARTNOMA + REKVIZITLAR ════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Shartnoma kartochkasi --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="file-signature" class="w-5 h-5 text-blue-500"></i>
                Shartnoma
            </h3>
            <a href="{{ route('admin.sellers.edit', $seller) }}#contract" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        @if(empty($seller->contract_number) && empty($seller->contract_expires_at) && !$seller->contract_signed)
            <p class="text-sm text-gray-400 text-center py-4">Shartnoma ma'lumotlari kiritilmagan.</p>
        @else
            <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
                {{-- Imzo holati — eng yuqorida, sana bilmagan holda ham ko'rinadi --}}
                <dt class="col-span-1 text-gray-500">Imzo holati</dt>
                <dd class="col-span-2">
                    @if($seller->contract_signed)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Imzolangan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                            <i data-lucide="circle-dashed" class="w-3.5 h-3.5"></i> Imzolanmagan
                        </span>
                    @endif
                </dd>
                @if($seller->contract_number)
                    <dt class="col-span-1 text-gray-500">№</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->contract_number }}</dd>
                @endif
                @if($seller->contract_signed_at)
                    <dt class="col-span-1 text-gray-500">Imzolandi</dt>
                    <dd class="col-span-2">{{ $seller->contract_signed_at->format('Y-m-d') }}</dd>
                @endif
                @if($seller->contract_expires_at)
                    <dt class="col-span-1 text-gray-500">Tugaydi</dt>
                    <dd class="col-span-2">
                        {{ $seller->contract_expires_at->format('Y-m-d') }}
                        @php $d = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false); @endphp
                        <span class="text-xs {{ $d < 0 ? 'text-red-500' : ($d <= 30 ? 'text-amber-500' : 'text-gray-400') }}">
                            ({{ $d < 0 ? abs($d).' kun oldin tugagan' : $d.' kun qoldi' }})
                        </span>
                    </dd>
                @endif
                @if($seller->contract_notes)
                    <dt class="col-span-3 text-gray-500 mt-1">Izoh</dt>
                    <dd class="col-span-3 text-gray-600 dark:text-gray-400">{{ $seller->contract_notes }}</dd>
                @endif
            </dl>
            @if($seller->contract_expires_at)
                <form method="POST" action="{{ route('admin.sellers.contract.extend', $seller) }}" class="mt-3 flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="months" class="select h-9 text-xs flex-1">
                        <option value="3">+3 oy</option>
                        <option value="6">+6 oy</option>
                        <option value="12" selected>+12 oy</option>
                        <option value="24">+24 oy</option>
                    </select>
                    <button type="submit" class="btn btn-secondary text-xs flex items-center gap-1 whitespace-nowrap">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i> Uzaytirish
                    </button>
                </form>
            @endif
        @endif
    </div>

    {{-- Rekvizitlar kartochkasi (maskalangan) --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="landmark" class="w-5 h-5 text-emerald-500"></i>
                Rekvizitlar
            </h3>
            <a href="{{ route('admin.sellers.edit', $seller) }}#legal" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        @php
            $hasAny = $seller->legal_type || $seller->inn || $seller->bank_account || $seller->payment_card;
        @endphp
        @if(!$hasAny)
            <p class="text-sm text-gray-400 text-center py-4">Rekvizitlar kiritilmagan.</p>
        @else
            <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
                @if($seller->legal_type)
                    <dt class="col-span-1 text-gray-500">Turi</dt>
                    <dd class="col-span-2">
                        @switch($seller->legal_type)
                            @case('individual')   Jismoniy shaxs @break
                            @case('entrepreneur') Yakka tartibdagi tadbirkor @break
                            @case('llc')          MChJ @break
                            @case('jsc')          AJ / OAJ @break
                            @default              {{ $seller->legal_type }}
                        @endswitch
                    </dd>
                @endif
                @if($seller->inn)
                    <dt class="col-span-1 text-gray-500">STIR</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->inn }}</dd>
                @endif
                @if($seller->passport_series || $seller->passport_number)
                    <dt class="col-span-1 text-gray-500">Pasport</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->passport_series }} {{ $seller->passport_number }}</dd>
                @endif
                @if($seller->bank_name)
                    <dt class="col-span-1 text-gray-500">Bank</dt>
                    <dd class="col-span-2">{{ $seller->bank_name }}</dd>
                @endif
                @if($seller->bank_account)
                    <dt class="col-span-1 text-gray-500">Hisob</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->masked_bank_account }}</dd>
                @endif
                @if($seller->bank_mfo)
                    <dt class="col-span-1 text-gray-500">MFO</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->bank_mfo }}</dd>
                @endif
                @if($seller->payment_card)
                    <dt class="col-span-1 text-gray-500">Karta</dt>
                    <dd class="col-span-2 font-mono">{{ $seller->masked_card }}</dd>
                @endif
                @if($seller->card_holder)
                    <dt class="col-span-1 text-gray-500">Egasi</dt>
                    <dd class="col-span-2">{{ $seller->card_holder }}</dd>
                @endif
                @if($seller->legal_address)
                    <dt class="col-span-3 text-gray-500 mt-1">Manzil</dt>
                    <dd class="col-span-3">{{ $seller->legal_address }}</dd>
                @endif
            </dl>
        @endif
    </div>
</div>

{{-- ══ HUJJATLAR + SHARTNOMA TARIXI ═══════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Hujjatlar --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="folder" class="w-5 h-5 text-indigo-500"></i>
                Hujjatlar ({{ $seller->documents->count() }})
            </h3>
            <a href="{{ route('admin.sellers.edit', $seller) }}#documents" class="text-xs text-blue-500 hover:underline">Yuklash / boshqarish</a>
        </div>
        @if($seller->documents->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Hujjatlar yuklanmagan.</p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach($seller->documents->take(6) as $doc)
                    <li class="flex items-center gap-3 py-2.5">
                        <i data-lucide="{{ $doc->type_icon }}" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate">{{ $doc->type_label }}@if($doc->original_name) — <span class="text-gray-400">{{ $doc->original_name }}</span>@endif</p>
                            <p class="text-[10px] text-gray-400">{{ $doc->created_at?->format('Y-m-d H:i') }}</p>
                        </div>
                        <a href="{{ $doc->file_url }}" target="_blank" class="text-blue-500 hover:underline text-xs flex items-center gap-1 flex-shrink-0">
                            <i data-lucide="external-link" class="w-3 h-3"></i> Ochish
                        </a>
                    </li>
                @endforeach
            </ul>
            @if($seller->documents->count() > 6)
                <p class="text-xs text-gray-400 mt-2 text-center">Yana {{ $seller->documents->count() - 6 }} ta hujjat...</p>
            @endif
        @endif
    </div>

    {{-- Shartnoma tarixi --}}
    <div class="card p-5">
        <h3 class="font-bold text-base flex items-center gap-2 mb-4">
            <i data-lucide="history" class="w-5 h-5 text-amber-500"></i>
            Shartnoma tarixi
        </h3>
        @if($seller->contractHistory->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Tarix yo'q.</p>
        @else
            <ol class="space-y-2">
                @foreach($seller->contractHistory->take(8) as $h)
                    <li class="flex items-start gap-3 text-xs">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-{{ $h->action_color }}-100 text-{{ $h->action_color }}-700 dark:bg-{{ $h->action_color }}-500/10 dark:text-{{ $h->action_color }}-400 font-medium flex-shrink-0">
                            {{ $h->action_label }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-gray-700 dark:text-gray-300">
                                @if($h->old_expires_at && $h->new_expires_at)
                                    <span class="font-mono">{{ $h->old_expires_at->format('Y-m-d') }}</span>
                                    →
                                    <span class="font-mono font-semibold">{{ $h->new_expires_at->format('Y-m-d') }}</span>
                                @elseif($h->new_expires_at)
                                    <span class="font-mono">{{ $h->new_expires_at->format('Y-m-d') }}</span>
                                @endif
                            </div>
                            @if($h->notes)
                                <p class="text-gray-500 mt-0.5 truncate">{{ $h->notes }}</p>
                            @endif
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                {{ $h->created_at?->format('Y-m-d H:i') }}
                                @if($h->performer)
                                    · {{ trim($h->performer->name . ' ' . $h->performer->lastname) }}
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
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

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Seller loglari</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Log</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffLogs as $log)
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                {{ $log->created_at ? $log->created_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td>{{ $log->text ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-gray-400 py-6">Loglar topilmadi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Ogohlantirish va unblock tarixi</h3>
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
                                    {{ $banLog->type === 'warning' ? 'Ogohlantirish' : 'Unblock' }}
                                </span>
                            </td>
                            <td>{{ $banLog->title ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-300">{{ $banLog->message ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-6">Ogohlantirishlar tarixi topilmadi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
