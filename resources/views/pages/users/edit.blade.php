@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Tahrirlash: {{ $user->full_name }}" />

@if(session('success'))
    <x-ui.alert variant="success" title="Muvaffaqiyatli!" message="{{ session('success') }}" class="mb-5" />
@endif
@if($errors->any())
    <x-ui.alert variant="error" title="Xatolik!" message="{{ $errors->first() }}" class="mb-5" />
@endif

<form method="POST" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- ===================== LEFT ===================== --}}
        <div class="space-y-5 xl:col-span-1">

            {{-- Avatar --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                x-data="{
                    preview: '{{ $user->avatar }}',
                    handleFile(e) {
                        const f = e.target.files[0];
                        if (!f) return;
                        const r = new FileReader();
                        r.onload = ev => this.preview = ev.target.result;
                        r.readAsDataURL(f);
                    }
                }">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90">Profil rasmi</h4>
                <div class="flex flex-col items-center gap-4">
                    <div class="relative h-24 w-24 cursor-pointer overflow-hidden rounded-full border border-gray-200 dark:border-gray-800"
                        @click="$refs.fileInput.click()">
                        <template x-if="preview">
                            <img :src="preview" alt="avatar" class="h-full w-full object-cover" />
                        </template>
                        <template x-if="!preview">
                            <div class="flex h-full w-full items-center justify-center bg-brand-500 text-2xl font-bold text-white">
                                {{ strtoupper(substr($user->name,0,1)) }}{{ strtoupper(substr($user->lastname,0,1)) }}
                            </div>
                        </template>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <input type="file" name="avatar" x-ref="fileInput" @change="handleFile($event)" accept="image/*" class="hidden" />
                    <p class="text-xs text-gray-400 dark:text-gray-500">JPG, PNG · Maks. 2 MB</p>
                </div>
            </div>

            {{-- Imtiyozlar --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90">Maxsus imtiyozlar</h4>
                <div class="space-y-4">

                    {{-- Premium toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Premium ★</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Premium foydalanuvchi</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="is_premium" value="0">
                            <input type="checkbox" name="is_premium" value="1" {{ $user->is_premium ? 'checked' : '' }} class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    {{-- Premium until --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Premium muddati</label>
                        <input type="datetime-local" name="premium_until"
                            value="{{ $user->premium_until ? \Carbon\Carbon::parse($user->premium_until)->format('Y-m-d\TH:i') : '' }}"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    {{-- Verified --}}
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Tasdiqlangan</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Telefon tasdiqlangan</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="isVerified" value="0">
                            <input type="checkbox" name="isVerified" value="1" {{ $user->isVerified ? 'checked' : '' }} class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    {{-- Support --}}
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Support</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Yordam xizmati</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="isSupport" value="0">
                            <input type="checkbox" name="isSupport" value="1" {{ $user->isSupport ? 'checked' : '' }} class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    {{-- isDeleted --}}
                    <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Hisob holati</label>
                        <select name="isDeleted"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="no"  @selected($user->isDeleted !== 'yes')>Faol</option>
                            <option value="yes" @selected($user->isDeleted === 'yes')>O'chirilgan</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===================== RIGHT ===================== --}}
        <div class="space-y-5 xl:col-span-2">

            {{-- Personal Info --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Shaxsiy ma'lumotlar</h4>
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ism</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Ism"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Familiya</label>
                        <input type="text" name="lastname" value="{{ old('lastname', $user->lastname) }}" placeholder="Familiya"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Telefon</label>
                        <input type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="+998901234567"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="email@example.com"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jinsi</label>
                        <select name="sex"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Noma'lum</option>
                            <option value="M" @selected($user->sex==='M')>Erkak</option>
                            <option value="F" @selected($user->sex==='F')>Ayol</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Til</label>
                        <select name="locale"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="uz" @selected($user->locale==='uz')>O'zbek</option>
                            <option value="ru" @selected($user->locale==='ru')>Rus</option>
                            <option value="en" @selected($user->locale==='en')>Ingliz</option>
                            <option value="ja" @selected($user->locale==='ja')>Yapon</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Lavozim</label>
                        <input type="text" name="position" value="{{ old('position', $user->position) }}" placeholder="O'quvchi, Yozuvchi..."
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bio / Status</label>
                        <textarea name="status" rows="3" placeholder="Foydalanuvchi haqida..."
                            class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 resize-none">{{ old('status', $user->status) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Moliya --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Moliya va limitlar</h4>
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Balans (UZS)</label>
                        <input type="number" name="real_balance" value="{{ old('real_balance', $user->real_balance) }}" placeholder="0"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cashback (UZS)</label>
                        <input type="number" name="cashback" value="{{ old('cashback', $user->cashback) }}" placeholder="0"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">AI limit</label>
                        <input type="number" name="ai_limit" value="{{ old('ai_limit', $user->ai_limit) }}" placeholder="25"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 lg:justify-end">
                <a href="{{ route('admin.users.show', $user->id) }}"
                    class="shadow-theme-xs flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] sm:w-auto">
                    Orqaga
                </a>
                <button type="submit"
                    class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto">
                    Saqlash
                </button>
            </div>

        </div>
    </div>
</form>
@endsection