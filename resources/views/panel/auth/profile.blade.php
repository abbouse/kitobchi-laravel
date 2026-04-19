@extends('panel.layouts.panel')
@section('title', 'Profil')

@section('content')

@php $admin = auth('panel')->user(); @endphp

<x-panel.page-header>
  <x-slot name="heading">Mening profilim</x-slot>
</x-panel.page-header>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
  {{-- Left: avatar card --}}
  <div class="lg:col-span-4 fade-up">
    <div class="p-card" style="text-align:center;padding:32px 20px">
      <div style="width:80px;height:80px;border-radius:50%;
                  background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                  display:flex;align-items:center;justify-content:center;
                  font-size:28px;font-weight:700;color:#fff;
                  margin:0 auto 16px;overflow:hidden;
                  box-shadow:0 8px 24px rgba(91,135,255,.35)">
        @if($admin->avatar)
          <img src="{{ asset('storage/'.$admin->avatar) }}"
               style="width:100%;height:100%;object-fit:cover" alt="{{ $admin->name }}">
        @else
          {{ strtoupper(substr($admin->name, 0, 1)) }}
        @endif
      </div>
      <div style="font-size:18px;font-weight:700;color:var(--p-text);letter-spacing:-.3px">{{ $admin->name }}</div>
      <div style="font-size:13px;color:var(--p-hint);margin-top:4px">{{ $admin->email }}</div>
      <div class="flex justify-center gap-2 mt-3">
        <span class="s-pill accent">{{ $admin->getRoleLabelAttribute() }}</span>
        <span class="s-pill {{ $admin->is_active ? 'success' : 'danger' }}">
          {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
        </span>
      </div>

      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--p-border)">
        @foreach([
          ['bi-geo','Oxirgi IP',$admin->last_ip ?? '—'],
          ['bi-box-arrow-in-right','Kirdi',$admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
          ['bi-calendar','Yaratildi',$admin->created_at?->format('d.m.Y') ?? '—'],
        ] as [$icon,$lbl,$val])
        <div class="flex items-center gap-2 text-start mb-2"
             style="font-size:12px;color:var(--p-hint)">
          <i class="bi {{ $icon }}" style="width:14px;text-align:center"></i>
          <span>{{ $lbl }}:</span>
          <span style="color:var(--p-muted);font-weight:500;font-family:'JetBrains Mono',monospace;font-size:11px">{{ $val }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Right: edit form --}}
  <div class="lg:col-span-8 fade-up">
    <form method="POST" action="{{ route('panel.profile.update') }}" enctype="multipart/form-data">
      @csrf

      <div class="p-card mb-3">
        <div style="padding:0 0 14px;margin-bottom:16px;border-bottom:1px solid var(--p-border);
                    font-size:13px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-person mr-2" style="color:var(--p-accent)"></i>Shaxsiy ma'lumotlar
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name"
                   class="p-form-control @error('name') border-danger @enderror"
                   value="{{ old('name', $admin->name) }}" required maxlength="100">
            @error('name')
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
            @enderror
          </div>
          <div class="">
            <label class="p-form-label">Email <span style="color:var(--p-danger)">*</span></label>
            <input type="email" name="email"
                   class="p-form-control @error('email') border-danger @enderror"
                   value="{{ old('email', $admin->email) }}" required>
            @error('email')
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
            @enderror
          </div>
          <div class="">
            <label class="p-form-label">Avatar rasmi</label>
            <input type="file" name="avatar"
                   class="p-form-control @error('avatar') border-danger @enderror"
                   accept="image/jpeg,image/png,image/jpg">
            <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
              <i class="bi bi-info-circle mr-1"></i>JPG, PNG &middot; Maksimal 2MB
            </div>
            @error('avatar')
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <div class="p-card mb-3">
        <div style="padding:0 0 14px;margin-bottom:16px;border-bottom:1px solid var(--p-border);
                    font-size:13px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-lock mr-2" style="color:var(--p-warning)"></i>Parolni o'zgartirish
          <span style="font-size:11px;color:var(--p-hint);font-weight:400;margin-left:6px">
            (ixtiyoriy)
          </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Joriy parol</label>
            <input type="password" name="current_password"
                   class="p-form-control @error('current_password') border-danger @enderror"
                   autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            @error('current_password')
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
            @enderror
          </div>
          <div class="">
            <label class="p-form-label">Yangi parol</label>
            <input type="password" name="password"
                   class="p-form-control @error('password') border-danger @enderror"
                   autocomplete="new-password" minlength="8" placeholder="Kamida 8 belgi">
            @error('password')
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
            @enderror
          </div>
          <div class="">
            <label class="p-form-label">Yangi parolni tasdiqlash</label>
            <input type="password" name="password_confirmation"
                   class="p-form-control" autocomplete="new-password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button type="reset" class="btn-p ghost">
          <i class="bi bi-arrow-counterclockwise"></i> Tozalash
        </button>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>

    </form>
  </div>
</div>

@endsection
