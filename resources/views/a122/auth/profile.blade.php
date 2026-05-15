@extends('a122.layouts.admin')
@section('title', 'Profil')
@section('page-title', 'Profil')

@section('content')
@php $admin = auth('panel')->user(); @endphp

<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Account workspace"
    title="Mening profilim"
    subtitle="Admin akkaunti, xavfsizlik va shaxsiy ma'lumotlar bir xil boshqaruv tilida shu sahifada yangilanadi." />

  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <section class="kc-panel h-100 fade-up">
        <div class="kc-panel__body">
          <div class="d-flex flex-column align-items-center text-center gap-3">
            @include('a122.partials.avatar', [
              'name' => $admin->name,
              'image' => $admin->avatar,
              'class' => 'rounded-4',
            ])

            <div>
              <h2 class="h4 fw-bold mb-1 text-dark">{{ $admin->name }}</h2>
              <p class="text-secondary mb-0">{{ $admin->email }}</p>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
              <span class="badge rounded-pill text-bg-primary px-3 py-2">{{ $admin->getRoleLabelAttribute() }}</span>
              <span class="badge rounded-pill {{ $admin->is_active ? 'text-bg-success' : 'text-bg-danger' }} px-3 py-2">
                {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
              </span>
            </div>
          </div>

          <div class="mt-4 pt-4 border-top">
            <div class="d-flex flex-column gap-3">
              @foreach([
                ['icon' => 'globe2', 'label' => 'Oxirgi IP', 'value' => $admin->last_ip ?: '—'],
                ['icon' => 'clock-history', 'label' => 'Oxirgi kirish', 'value' => $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
                ['icon' => 'calendar3', 'label' => 'Yaratilgan', 'value' => $admin->created_at?->format('d.m.Y') ?: '—'],
              ] as $item)
                <div class="d-flex align-items-center gap-3 rounded-4 border bg-light-subtle px-3 py-3">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-4 text-primary bg-primary-subtle" style="width:2.75rem;height:2.75rem;">
                    <i class="bi bi-{{ $item['icon'] }}"></i>
                  </span>
                  <div class="min-w-0">
                    <div class="text-uppercase small fw-semibold text-secondary">{{ $item['label'] }}</div>
                    <div class="fw-semibold text-dark text-truncate">{{ $item['value'] }}</div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="col-12 col-xl-8">
      <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="d-flex flex-column gap-4 fade-up">
        @csrf

        <section class="kc-panel">
          <div class="kc-panel__header">
            <h3 class="kc-panel__title">Shaxsiy ma'lumotlar</h3>
            <div class="kc-panel__meta">Email, ism va avatar shu blokdan yangilanadi.</div>
          </div>
          <div class="kc-panel__body">
            <div class="row g-4">
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Ism</label>
                <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror" value="{{ old('name', $admin->name) }}" required maxlength="100">
                @error('name')<div class="mt-1 text-danger small">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Email</label>
                <input type="email" name="email" class="p-form-control @error('email') border-danger @enderror" value="{{ old('email', $admin->email) }}" required>
                @error('email')<div class="mt-1 text-danger small">{{ $message }}</div>@enderror
              </div>
              <div class="col-12">
                <label class="p-form-label">Avatar</label>
                <input type="file" name="avatar" class="p-form-control @error('avatar') border-danger @enderror" accept="image/jpeg,image/png,image/jpg">
                <div class="form-text">JPG yoki PNG, maksimal 2MB.</div>
                @error('avatar')<div class="mt-1 text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </section>

        <section class="kc-panel">
          <div class="kc-panel__header">
            <h3 class="kc-panel__title">Parolni yangilash</h3>
            <div class="kc-panel__meta">Xavfsizlikni kuchaytirish uchun yangi parol kiritishingiz mumkin.</div>
          </div>
          <div class="kc-panel__body">
            <div class="row g-4">
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Joriy parol</label>
                <input type="password" name="current_password" class="p-form-control @error('current_password') border-danger @enderror" autocomplete="current-password">
                @error('current_password')<div class="mt-1 text-danger small">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Yangi parol</label>
                <input type="password" name="password" class="p-form-control @error('password') border-danger @enderror" autocomplete="new-password" minlength="8">
                @error('password')<div class="mt-1 text-danger small">{{ $message }}</div>@enderror
              </div>
              <div class="col-12">
                <label class="p-form-label">Yangi parolni tasdiqlash</label>
                <input type="password" name="password_confirmation" class="p-form-control" autocomplete="new-password">
              </div>
            </div>
          </div>
        </section>

        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button type="reset" class="btn-p ghost">Tozalash</button>
          <button type="submit" class="btn-p primary">Saqlash</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
