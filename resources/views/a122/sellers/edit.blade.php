@extends('a122.layouts.admin')
@section('title', 'Sotuvchini tahrirlash')
@section('page-title', 'Sotuvchini tahrirlash')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary flex items-center gap-2 w-fit">
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

@if($errors->any())
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ══ TABS NAV ════════════════════════════════════════════════════ --}}
<div class="tab-pills fade-up mb-4" role="tablist">
    @foreach([
        'main'       => ['bi-person-circle', 'Asosiy'],
        'legal'      => ['bi-bank',          'Rekvizitlar'],
        'contract'   => ['bi-file-earmark-text', 'Shartnoma'],
        'documents'  => ['bi-folder',       'Hujjatlar'],
        'premium'    => ['bi-star',         'Premium'],
    ] as $key => [$icon, $label])
        <button type="button"
                class="tab-pill"
                data-seller-tab="{{ $key }}"
                @if($loop->first) data-seller-tab-active @endif>
            <i class="bi {{ $icon }}"></i> {{ $label }}
        </button>
    @endforeach
</div>

{{-- ══ MAIN FORM (tabs 1–3 va 5) ══════════════════════════════════════ --}}
<form method="POST" action="{{ route('admin.sellers.update', $seller) }}" enctype="multipart/form-data" class="space-y-4" id="sellerEditForm">
    @csrf
    @method('PUT')

    {{-- ────────────────────────────────────────── TAB 1: ASOSIY ───── --}}
    <section data-seller-panel="main" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Do'kon nomi <span class="text-red-500">*</span></label>
            <input name="shop_name" class="input" required placeholder="Do'kon nomini kiriting"
                   value="{{ old('shop_name', $seller->shop_name) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism</label>
            <input name="firstname" class="input" placeholder="Ism"
                   value="{{ old('firstname', $seller->firstname) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya</label>
            <input name="lastname" class="input" placeholder="Familiya"
                   value="{{ old('lastname', $seller->lastname) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam <span class="text-red-500">*</span></label>
            <input name="phone_number" class="input" placeholder="+998901234567" required
                   value="{{ old('phone_number', $seller->phone_number) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat <span class="text-red-500">*</span></label>
            <input name="region" class="input" placeholder="Viloyat nomi" required
                   value="{{ old('region', $seller->region) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending"  @selected(old('status', $seller->status) === 'pending')>Kutilmoqda</option>
                <option value="approved" @selected(old('status', $seller->status) === 'approved')>Tasdiqlangan</option>
                <option value="rejected" @selected(old('status', $seller->status) === 'rejected')>Rad etilgan</option>
                <option value="blocked"  @selected(old('status', $seller->status) === 'blocked')>Bloklangan</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Balans (UZS)</label>
            <input name="balance" type="number" class="input" min="0" step="1"
                   placeholder="0" value="{{ old('balance', $seller->balance ?? 0) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Komissiya foizi (%)</label>
            <input name="commission_percent" type="number" class="input" min="0" max="100" step="1"
                   placeholder="Default CommissionSetting"
                   value="{{ old('commission_percent', $seller->commission_percent) }}">
            <p class="text-[10px] text-gray-400 mt-1">Bo'sh qoldirilsa, umumiy CommissionSetting'dan olinadi.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi</label>
            @if($seller->photo)
                <div class="mb-2 flex items-center gap-3">
                    <img src="{{ Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo) }}"
                         alt="Joriy rasm"
                         class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-400">Joriy rasm. Yangi rasm yuklash uchun faylni tanlang.</span>
                </div>
            @endif
            <input name="photo" type="file" class="input" accept="image/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Yangi parol <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="password" type="password" class="input"
                   placeholder="Bo'sh qoldirilsa, o'zgarmaydi" autocomplete="new-password">
        </div>
    </section>

    {{-- ────────────────────────────────── TAB 2: REKVIZITLAR ────────── --}}
    <section data-seller-panel="legal" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Huquqiy ma'lumotlar</h3>
            <p class="text-[11px] text-gray-400">Shartnoma tuzish uchun zarur rekvizitlar.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Huquqiy shakl</label>
            <select name="legal_type" class="select">
                <option value="">— tanlang —</option>
                @foreach([
                    'individual'   => 'Jismoniy shaxs',
                    'entrepreneur' => 'Yakka tartibdagi tadbirkor',
                    'llc'          => 'MChJ',
                    'jsc'          => 'AJ / OAJ',
                ] as $v => $l)
                    <option value="{{ $v }}" @selected(old('legal_type', $seller->legal_type) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">STIR (INN)</label>
            <input name="inn" class="input" placeholder="12345678"
                   value="{{ old('inn', $seller->inn) }}">
        </div>

        {{-- Pasport --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport seriyasi</label>
            <input name="passport_series" class="input" placeholder="AB"
                   value="{{ old('passport_series', $seller->passport_series) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport raqami</label>
            <input name="passport_number" class="input" placeholder="1234567"
                   value="{{ old('passport_number', $seller->passport_number) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Kim tomonidan berilgan</label>
            <input name="passport_issued_by" class="input" placeholder="Toshkent shahar IIB"
                   value="{{ old('passport_issued_by', $seller->passport_issued_by) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Berilgan sana</label>
            <input name="passport_issued_at" type="date" class="input"
                   value="{{ old('passport_issued_at', optional($seller->passport_issued_at)->format('Y-m-d')) }}">
        </div>

        {{-- Bank --}}
        <div class="md:col-span-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mt-2 mb-1">Bank rekvizitlari</h4>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Bank nomi</label>
            <input name="bank_name" class="input" placeholder="Ipak Yo'li Banki"
                   value="{{ old('bank_name', $seller->bank_name) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Hisob raqami (20 xona)</label>
            <input name="bank_account" class="input" placeholder="20208000000000000000"
                   value="{{ old('bank_account', $seller->bank_account) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">MFO</label>
            <input name="bank_mfo" class="input" placeholder="00420"
                   value="{{ old('bank_mfo', $seller->bank_mfo) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">SWIFT <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="bank_swift" class="input" placeholder="UZSBUZ22"
                   value="{{ old('bank_swift', $seller->bank_swift) }}">
        </div>

        {{-- Karta --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta raqami</label>
            <input name="payment_card" class="input" placeholder="8600 0304 1234 5678"
                   value="{{ old('payment_card', $seller->payment_card) }}">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta egasi</label>
            <input name="card_holder" class="input" placeholder="ABBOS TOORDALIEV"
                   value="{{ old('card_holder', $seller->card_holder) }}">
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Huquqiy manzil</label>
            <input name="legal_address" class="input" placeholder="Toshkent sh., Yunusobod tumani, ..."
                   value="{{ old('legal_address', $seller->legal_address) }}">
        </div>
    </section>

    {{-- ─────────────────────────────────── TAB 3: SHARTNOMA ─────────── --}}
    <section data-seller-panel="contract" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Shartnoma ma'lumotlari</h3>
            <p class="text-[11px] text-gray-400">Shartnoma raqami, sanasi va holati. "Tez uzaytirish" tugmasi Hujjatlar tabida.</p>
        </div>

        {{-- Imzolangan toggle — sanasi bilmagan holda ham belgilash mumkin --}}
        <div class="md:col-span-2">
            <div class="p-3 rounded-lg border {{ $seller->contract_signed ? 'border-emerald-300 bg-emerald-50/60 dark:bg-emerald-400/5 dark:border-emerald-400/20' : 'border-gray-200 bg-gray-50/60 dark:bg-white/5 dark:border-white/10' }} flex items-start gap-3">
                <input type="checkbox" name="contract_signed" id="contract_signed" value="1"
                       class="mt-1 w-4 h-4 rounded border-emerald-300 text-emerald-500 focus:ring-emerald-400"
                       {{ old('contract_signed', $seller->contract_signed) ? 'checked' : '' }}>
                <div class="flex-1">
                    <label for="contract_signed" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 cursor-pointer">
                        Shartnoma imzolangan
                    </label>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        Ushbu seller bilan shartnoma imzolanganini belgilang. Sana bilmasangiz ham belgilash mumkin —
                        sana keyinroq qo'shilishi mumkin. Belgisiz seller "shartnomasi imzolanmagan" deb hisoblanadi.
                    </p>
                </div>
            </div>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Shartnoma raqami</label>
            <input name="contract_number" class="input" placeholder="№ 2026-042"
                   value="{{ old('contract_number', $seller->contract_number) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="contract_status" class="select">
                @foreach([
                    'none'       => 'Yo\'q',
                    'active'     => 'Faol',
                    'expiring'   => 'Tugashga yaqin',
                    'expired'    => 'Tugagan',
                    'terminated' => 'To\'xtatilgan',
                ] as $v => $l)
                    <option value="{{ $v }}" @selected(old('contract_status', $seller->contract_status) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Imzolangan sana</label>
            <input name="contract_signed_at" type="date" class="input"
                   value="{{ old('contract_signed_at', optional($seller->contract_signed_at)->format('Y-m-d')) }}">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tugash sanasi</label>
            <input name="contract_expires_at" type="date" class="input"
                   value="{{ old('contract_expires_at', optional($seller->contract_expires_at)->format('Y-m-d')) }}">
            @if($seller->contract_expires_at)
                @php $days = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false); @endphp
                <p class="text-[10px] mt-1 {{ $days < 0 ? 'text-red-500' : ($days <= 30 ? 'text-amber-500' : 'text-gray-400') }}">
                    @if($days < 0)
                        {{ abs($days) }} kun oldin tugagan.
                    @else
                        {{ $days }} kun qoldi.
                    @endif
                </p>
            @endif
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Izohlar</label>
            <textarea name="contract_notes" class="input" rows="2" placeholder="Shartnoma bo'yicha qo'shimcha izohlar">{{ old('contract_notes', $seller->contract_notes) }}</textarea>
        </div>
    </section>

    {{-- ─────────────────────────────────── TAB 5: PREMIUM ───────────── --}}
    <section data-seller-panel="premium" class="card-panel p-5 hidden">
        <div class="p-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:bg-amber-400/5 dark:border-amber-400/20 space-y-4">
            <div>
                <div class="text-sm font-semibold text-amber-700 dark:text-amber-300">Premium obuna</div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                    Admin endi premiumni ilovadagi planlar bilan bir xil beradi. Berilgan plan seller uchun haqiqiy subscription yaratadi yoki amaldagini uzaytiradi.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-amber-200/70 dark:border-amber-400/20 bg-white/80 dark:bg-white/5 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Hozirgi holat</div>
                    @if(($premiumState['is_premium'] ?? false) && !empty($premiumState['premium_expires_at']))
                        <div class="text-lg font-semibold text-amber-600 dark:text-amber-300">Faol premium</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Plan: <span class="font-medium">{{ $premiumState['subscription_plan'] ?? 'manual' }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Tugaydi:
                            <span class="font-mono">
                                {{ \Illuminate\Support\Carbon::parse($premiumState['premium_expires_at'])->format('Y-m-d H:i') }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Qoldi: {{ $premiumState['premium_days_left'] ?? 0 }} kun
                        </div>
                    @else
                        <div class="text-lg font-semibold text-gray-500">Premium yo'q</div>
                        <div class="text-xs text-gray-500 mt-1">Seller hozir faol premium obunada emas.</div>
                    @endif
                </div>

                <div class="rounded-xl border border-amber-200/70 dark:border-amber-400/20 bg-white/80 dark:bg-white/5 p-4 space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Admin premium action</label>
                        <select name="premium_action" id="premiumActionSelect" class="select"
                                onchange="document.getElementById('premiumPlanWrap').classList.toggle('hidden', this.value !== 'grant')">
                            <option value="keep" @selected(old('premium_action', 'keep') === 'keep')>O'zgartirmaslik</option>
                            <option value="grant" @selected(old('premium_action') === 'grant')>Plan bo'yicha premium berish</option>
                            <option value="revoke" @selected(old('premium_action') === 'revoke')>Premiumni o'chirish</option>
                        </select>
                    </div>

                    <div id="premiumPlanWrap" class="{{ old('premium_action') === 'grant' ? '' : 'hidden' }}">
                        <label class="text-xs text-gray-500 mb-1 block">Premium plan <span class="text-red-500">*</span></label>
                        <select name="premium_plan" class="select">
                            <option value="">Planni tanlang</option>
                            @foreach($premiumPlans as $plan)
                                <option value="{{ $plan['type'] }}" @selected(old('premium_plan') === $plan['type'])>
                                    {{ $plan['label'] }} · {{ number_format($plan['price'], 0, '.', ' ') }} UZS
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">
                            Bu action seller uchun active subscription yaratadi yoki amaldagi obunani uzaytiradi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
        <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary">Bekor</a>
        <button type="submit" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i> Saqlash
        </button>
    </div>
</form>

{{-- ══ TAB 4: HUJJATLAR — alohida form ════════════════════════════════ --}}
<section data-seller-panel="documents" class="card-panel p-5 hidden mt-4">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Hujjatlar ro'yxati</h3>

    {{-- Upload form --}}
    <form method="POST" action="{{ route('admin.sellers.documents.store', $seller) }}" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 rounded-lg bg-gray-50 dark:bg-white/5 mb-4">
        @csrf
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Turi</label>
            <select name="type" class="select" required>
                @foreach([
                    'passport'        => 'Pasport',
                    'contract'        => 'Shartnoma',
                    'inn_certificate' => 'STIR guvohnomasi',
                    'license'         => 'Litsenziya',
                    'bank_details'    => 'Bank rekvizitlari',
                    'addendum'        => 'Qo\'shimcha kelishuv',
                    'other'           => 'Boshqa',
                ] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Fayl (PDF/JPG/PNG, ≤10MB)</label>
            <input name="file" type="file" class="input" accept="application/pdf,image/*" required>
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                <i data-lucide="upload" class="w-4 h-4"></i> Yuklash
            </button>
        </div>
        <div class="md:col-span-4">
            <label class="text-xs text-gray-500 mb-1 block">Izoh <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="description" class="input" placeholder="Masalan: 2026 yilgi shartnoma, pasport 1-2 beti">
        </div>
    </form>

    {{-- Existing documents list --}}
    @if($seller->documents->isEmpty())
        <p class="text-sm text-gray-400 text-center py-4">Hali hech qanday hujjat yuklanmagan.</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach($seller->documents as $doc)
                <div class="flex items-center gap-3 py-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $doc->type_icon }}" class="w-5 h-5 text-gray-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ $doc->type_label }}</span>
                            @if($doc->original_name)
                                <span class="text-xs text-gray-400 truncate">— {{ $doc->original_name }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                            @if($doc->file_size_kb)
                                <span>{{ number_format($doc->file_size_kb) }} KB</span>
                            @endif
                            <span>{{ $doc->created_at?->format('Y-m-d H:i') }}</span>
                            @if($doc->uploader)
                                <span>yukladi: {{ trim($doc->uploader->name . ' ' . $doc->uploader->lastname) }}</span>
                            @endif
                        </div>
                        @if($doc->description)
                            <p class="text-xs text-gray-500 mt-1">{{ $doc->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ $doc->file_url }}" target="_blank"
                           class="btn btn-secondary px-3 py-1.5 text-xs flex items-center gap-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ko'rish
                        </a>
                        <form method="POST" action="{{ route('admin.sellers.documents.destroy', [$seller, $doc]) }}"
                              onsubmit="return confirm('Hujjatni o\'chirishga ishonchingiz komilmi?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-3 py-1.5 text-xs flex items-center gap-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Quick contract extend --}}
    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tez uzaytirish</h4>
        <p class="text-xs text-gray-400 mb-3">Mavjud <span class="font-mono">contract_expires_at</span> sanasiga tanlangan oy qo'shadi va <span class="font-mono">seller_contract_history</span> ga log yozadi.</p>
        <form method="POST" action="{{ route('admin.sellers.contract.extend', $seller) }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            @method('PATCH')
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Oy soni</label>
                <select name="months" class="select" required>
                    <option value="3">3 oy</option>
                    <option value="6">6 oy</option>
                    <option value="12" selected>12 oy</option>
                    <option value="24">24 oy</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500 mb-1 block">Izoh</label>
                <input name="notes" class="input" placeholder="Masalan: seller o'z vaqtida to'lov qildi">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i> Uzaytirish
                </button>
            </div>
        </form>
    </div>

    {{-- Contract history timeline --}}
    @if($seller->contractHistory->isNotEmpty())
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Shartnoma tarixi</h4>
            <ol class="space-y-2">
                @foreach($seller->contractHistory as $h)
                    <li class="flex items-start gap-3 text-xs">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-{{ $h->action_color }}-100 text-{{ $h->action_color }}-700 dark:bg-{{ $h->action_color }}-500/10 dark:text-{{ $h->action_color }}-400 font-medium flex-shrink-0">
                            {{ $h->action_label }}
                        </span>
                        <div class="flex-1">
                            <div class="text-gray-700 dark:text-gray-300">
                                @if($h->old_expires_at && $h->new_expires_at)
                                    <span class="font-mono">{{ $h->old_expires_at->format('Y-m-d') }}</span>
                                    →
                                    <span class="font-mono font-semibold">{{ $h->new_expires_at->format('Y-m-d') }}</span>
                                @elseif($h->new_expires_at)
                                    Tugash: <span class="font-mono">{{ $h->new_expires_at->format('Y-m-d') }}</span>
                                @endif
                                @if($h->contract_number)
                                    · № {{ $h->contract_number }}
                                @endif
                            </div>
                            @if($h->notes)
                                <p class="text-gray-500 mt-0.5">{{ $h->notes }}</p>
                            @endif
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                {{ $h->created_at?->format('Y-m-d H:i') }}
                                @if($h->performer)
                                    · {{ trim($h->performer->name . ' ' . $h->performer->lastname) }}
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</section>

{{-- ══ TAB SWITCHER JS ════════════════════════════════════════════════ --}}
<script>
(function(){
    const pills   = document.querySelectorAll('[data-seller-tab]');
    const panels  = document.querySelectorAll('[data-seller-panel]');
    function activate(key){
        pills.forEach(p => p.classList.toggle('active', p.dataset.sellerTab === key));
        panels.forEach(s => s.classList.toggle('hidden', s.dataset.sellerPanel !== key));
        if (history && history.replaceState) {
            history.replaceState(null, '', '#' + key);
        }
    }
    pills.forEach(p => p.addEventListener('click', () => activate(p.dataset.sellerTab)));
    // Initial: URL hash dan yoki birinchi pill dan
    const hash = (location.hash || '').replace('#','');
    const valid = ['main','legal','contract','documents','premium'];
    activate(valid.includes(hash) ? hash : 'main');
})();
</script>
@endsection
