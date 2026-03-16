@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Foydalanuvchilar" />

<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Jami</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['total']) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Premium</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['premium']) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Tasdiqlangan</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['verified']) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Bugun qo'shilgan</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['today']) }}</h4>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        {{-- Header: title + filters --}}
        <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                Foydalanuvchilar ro'yxati
            </h3>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Qidirish..."
                        class="h-9 w-48 rounded-lg border border-gray-300 bg-transparent pl-9 pr-4 text-sm text-gray-700 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                </div>

                <select name="filter" class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Barchasi</option>
                    <option value="premium"  @selected(request('filter')==='premium')>Premium</option>
                    <option value="verified" @selected(request('filter')==='verified')>Tasdiqlangan</option>
                    <option value="support"  @selected(request('filter')==='support')>Support</option>
                    <option value="deleted"  @selected(request('filter')==='deleted')>O'chirilgan</option>
                </select>

                <select name="sort" class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="latest"  @selected(request('sort','latest')==='latest')>Yangilari avval</option>
                    <option value="oldest"  @selected(request('sort')==='oldest')>Eskisi avval</option>
                    <option value="balance" @selected(request('sort')==='balance')>Balans bo'yicha</option>
                    <option value="name"    @selected(request('sort')==='name')>Ism bo'yicha</option>
                </select>

                <button type="submit"
                    class="flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Filter
                </button>

                @if(request()->hasAny(['search','filter','sort']))
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Tozalash
                </a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Foydalanuvchi</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Telefon / Email</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Balans</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Qo'shilgan</p>
                        </th>
                        <th class="px-5 py-3 text-right sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amallar</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">

                        {{-- Avatar + Name --}}
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->full_name }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->lastname, 0, 1)) }}
                                        </div>
                                    @endif
                                    @if($user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->diffInMinutes() < 5)
                                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-success-500 dark:border-gray-900"></span>
                                    @endif
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $user->full_name }}
                                        @if($user->is_premium && $user->isPremium())
                                            <span class="text-yellow-400">★</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        #{{ $user->id }}
                                        @if($user->isSupport) · <span class="text-brand-500">Support</span> @endif
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Contact --}}
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm text-gray-700 dark:text-gray-300">{{ $user->phone_number }}</span>
                            @if($user->email)
                                <span class="block text-xs text-gray-400 dark:text-gray-500 max-w-[160px] truncate">{{ $user->email }}</span>
                            @endif
                        </td>

                        {{-- Balance --}}
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90">{{ number_format($user->real_balance) }} UZS</span>
                            @if($user->cashback > 0)
                                <span class="block text-xs text-success-500">+{{ number_format($user->cashback) }} cashback</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap gap-1">
                                @if($user->isVerified)
                                    <x-ui.badge variant="light" color="success" size="sm">Tasdiqlangan</x-ui.badge>
                                @else
                                    <x-ui.badge variant="light" color="warning" size="sm">Tasdiqlanmagan</x-ui.badge>
                                @endif
                                @if($user->is_premium && $user->isPremium())
                                    <x-ui.badge variant="solid" color="warning" size="sm">Premium</x-ui.badge>
                                @endif
                                @if($user->isDeleted === 'yes')
                                    <x-ui.badge variant="light" color="error" size="sm">O'chirilgan</x-ui.badge>
                                @endif
                            </div>
                        </td>

                        {{-- Date --}}
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm text-gray-700 dark:text-gray-300">{{ $user->created_at->format('d.m.Y') }}</span>
                            <span class="block text-xs text-gray-400 dark:text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                    Ko'rish
                                </a>
                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                    class="flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white hover:bg-brand-600">
                                    Tahrirlash
                                </a>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Foydalanuvchi topilmadi</p>
                            @if(request()->hasAny(['search','filter']))
                                <a href="{{ route('admin.users.index') }}" class="mt-2 inline-block text-xs text-brand-500 hover:underline">Filtrlarni tozalash</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            
        </div>
        @endif

    </div>
</div>
@endsection