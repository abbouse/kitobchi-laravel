@extends('a122.layouts.admin')
@section('title', 'Profil')
@section('page-title', 'Profil')

@section('content')
@php $admin = auth('panel')->user(); @endphp

<x-a122.page-header>
  <x-slot name="heading">Mening profilim</x-slot>
  <x-slot name="meta">Admin akkaunti, xavfsizlik va shaxsiy ma'lumotlar shu yerda boshqariladi.</x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
  <section class="p-card fade-up">
    <div class="flex flex-col items-center text-center gap-4">
      @include('a122.partials.avatar', [
        'name' => $admin->name,
        'image' => $admin->avatar,
        'class' => 'h-24 w-24 text-3xl rounded-[28px]',
      ])

      <div>
        <h2 class="text-xl font-semibold text-[var(--p-text)]">{{ $admin->name }}</h2>
        <p class="text-sm text-[var(--p-hint)] mt-1">{{ $admin->email }}</p>
      </div>

      <div class="flex flex-wrap justify-center gap-2">
        <span class="s-pill accent">{{ $admin->getRoleLabelAttribute() }}</span>
        <span class="s-pill {{ $admin->is_active ? 'success' : 'danger' }}">
          {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
        </span>
      </div>
    </div>

    <div class="mt-6 space-y-3 border-t border-[var(--p-border)] pt-5">
      @foreach([
        ['icon' => 'map-pinned', 'label' => 'Oxirgi IP', 'value' => $admin->last_ip ?: '—'],
        ['icon' => 'log-in', 'label' => 'Oxirgi kirish', 'value' => $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
        ['icon' => 'calendar-days', 'label' => 'Yaratilgan', 'value' => $admin->created_at?->format('d.m.Y') ?: '—'],
      ] as $item)
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] px-4 py-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--p-soft)] text-[var(--p-accent)]">
            <i data-lucide="{{ $item['icon'] }}" class="h-4.5 w-4.5"></i>
          </div>
          <div class="min-w-0">
            <div class="text-xs uppercase tracking-[0.16em] text-[var(--p-hint)]">{{ $item['label'] }}</div>
            <div class="truncate text-sm font-medium text-[var(--p-text)]">{{ $item['value'] }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-4 fade-up">
    @csrf

    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Shaxsiy ma'lumotlar</div>
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
          <label class="p-form-label">Ism</label>
          <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror" value="{{ old('name', $admin->name) }}" required maxlength="100">
          @error('name')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        </div>
        <div>
          <label class="p-form-label">Email</label>
          <input type="email" name="email" class="p-form-control @error('email') border-danger @enderror" value="{{ old('email', $admin->email) }}" required>
          @error('email')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        </div>
        <div class="lg:col-span-2">
          <label class="p-form-label">Avatar</label>
          <input type="file" name="avatar" class="p-form-control @error('avatar') border-danger @enderror" accept="image/jpeg,image/png,image/jpg">
          <p class="mt-2 text-xs text-[var(--p-hint)]">JPG yoki PNG, maksimal 2MB.</p>
          @error('avatar')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        </div>
      </div>
    </section>

    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Parolni yangilash</div>
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
          <label class="p-form-label">Joriy parol</label>
          <input type="password" name="current_password" class="p-form-control @error('current_password') border-danger @enderror" autocomplete="current-password">
          @error('current_password')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        </div>
        <div>
          <label class="p-form-label">Yangi parol</label>
          <input type="password" name="password" class="p-form-control @error('password') border-danger @enderror" autocomplete="new-password" minlength="8">
          @error('password')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        </div>
        <div class="lg:col-span-2">
          <label class="p-form-label">Yangi parolni tasdiqlash</label>
          <input type="password" name="password_confirmation" class="p-form-control" autocomplete="new-password">
        </div>
      </div>
    </section>

    <div class="flex flex-wrap justify-end gap-2">
      <button type="reset" class="btn-p ghost">Tozalash</button>
      <button type="submit" class="btn-p primary">Saqlash</button>
    </div>
  </form>
</div>
@endsection
