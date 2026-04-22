@extends('a122.layouts.admin')
@section('title', 'Yangi kuryer')
@section('page-title', 'Yangi kuryer qo\'shish')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary flex items-center gap-2 w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
</div>

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
    <form method="POST" action="{{ route('admin.couriers.store') }}" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        {{-- First Name --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism <span class="text-red-500">*</span></label>
            <input
                name="first_name"
                class="input"
                required
                placeholder="Ism"
                value="{{ old('first_name') }}"
            >
        </div>

        {{-- Last Name --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya <span class="text-red-500">*</span></label>
            <input
                name="last_name"
                class="input"
                required
                placeholder="Familiya"
                value="{{ old('last_name') }}"
            >
        </div>

        {{-- Phone --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam <span class="text-red-500">*</span></label>
            <input
                name="phone_number"
                class="input"
                required
                placeholder="+998901234567"
                value="{{ old('phone_number') }}"
            >
        </div>

        {{-- Region --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat</label>
            <input
                name="region"
                class="input"
                placeholder="Viloyat nomi"
                value="{{ old('region') }}"
            >
        </div>

        {{-- Password --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Parol <span class="text-red-500">*</span></label>
            <input
                name="password"
                type="password"
                class="input"
                required
                placeholder="Parol kiriting"
                autocomplete="new-password"
            >
        </div>

        {{-- Status --}}
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending"  @selected(old('status', 'pending') === 'pending')>Kutilmoqda</option>
                <option value="approved" @selected(old('status') === 'approved')>Tasdiqlangan</option>
                <option value="rejected" @selected(old('status') === 'rejected')>Rad etilgan</option>
            </select>
        </div>

        {{-- Photo --}}
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="photo" type="file" class="input" accept="image/*">
        </div>

        {{-- Actions --}}
        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary">Bekor</a>
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </div>
    </form>
</div>
@endsection
