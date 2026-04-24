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

<div class="card p-5">
    <form method="POST" action="{{ route('admin.sellers.update', $seller) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @method('PUT')

        {{-- Shop Name --}}
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Do'kon nomi <span class="text-red-500">*</span></label>
            <input
                name="shop_name"
                class="input"
                required
                placeholder="Do'kon nomini kiriting"
                value="{{ old('shop_name', $seller->shop_name) }}"
            >
        </div>

        {{-- First & Last Name --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism</label>
            <input
                name="firstname"
                class="input"
                placeholder="Ism"
                value="{{ old('firstname', $seller->firstname) }}"
            >
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya</label>
            <input
                name="lastname"
                class="input"
                placeholder="Familiya"
                value="{{ old('lastname', $seller->lastname) }}"
            >
        </div>

        {{-- Phone --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam</label>
            <input
                name="phone_number"
                class="input"
                placeholder="+998901234567"
                value="{{ old('phone_number', $seller->phone_number) }}"
            >
        </div>

        {{-- Region --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat</label>
            <input
                name="region"
                class="input"
                placeholder="Viloyat nomi"
                value="{{ old('region', $seller->region) }}"
            >
        </div>

        {{-- Status --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending" @selected(old('status', $seller->status) === 'pending')>Kutilmoqda</option>
                <option value="approved" @selected(old('status', $seller->status) === 'approved')>Tasdiqlangan</option>
                <option value="rejected" @selected(old('status', $seller->status) === 'rejected')>Rad etilgan</option>
                <option value="blocked" @selected(old('status', $seller->status) === 'blocked')>Bloklangan</option>
            </select>
        </div>

        {{-- Balance --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Balans (UZS)</label>
            <input
                name="balance"
                type="number"
                class="input"
                min="0"
                step="1"
                placeholder="0"
                value="{{ old('balance', $seller->balance ?? 0) }}"
            >
        </div>

        {{-- Photo --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi</label>
            @if($seller->photo)
                <div class="mb-2 flex items-center gap-3">
                    <img
                        src="{{ Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo) }}"
                        alt="Joriy rasm"
                        class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-white/10"
                    >
                    <span class="text-xs text-gray-400">Joriy rasm. Yangi rasm yuklash uchun faylni tanlang.</span>
                </div>
            @endif
            <input name="photo" type="file" class="input" accept="image/*">
        </div>

        {{-- Password --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Yangi parol <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input
                name="password"
                type="password"
                class="input"
                placeholder="Bo'sh qoldirilsa, o'zgarmaydi"
                autocomplete="new-password"
            >
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary">Bekor</a>
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </div>
    </form>
</div>
@endsection
