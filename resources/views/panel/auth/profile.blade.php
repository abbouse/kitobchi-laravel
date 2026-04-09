@extends('panel.layouts.panel')
@section('title', 'Profil')
@section('page-title', 'Mening profilim')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">

    @php $admin = auth('panel')->user(); @endphp

    {{-- Avatar va status --}}
    <div class="p-card mb-3">
      <div class="dash-card-body" style="text-align:center;padding:32px">
        <div style="width:84px;height:84px;border-radius:50%;
                    background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                    display:flex;align-items:center;justify-content:center;
                    font-size:32px;font-weight:700;color:#fff;
                    margin:0 auto 16px;overflow:hidden;flex-shrink:0">
          @if($admin->avatar)
            <img src="{{ asset('storage/'.$admin->avatar) }}"
                 style="width:100%;height:100%;object-fit:cover"
                 alt="{{ $admin->name }}">
          @else
            {{ strtoupper(substr($admin->name, 0, 1)) }}
          @endif
        </div>
        <div style="font-size:20px;font-weight:700;color:var(--p-text)">{{ $admin->name }}</div>
        <div style="font-size:13px;color:var(--p-hint)">{{ $admin->email }}</div>
        <div class="mt-2 d-flex justify-content-center gap-2">
          <span class="s-pill accent">{{ $admin->getRoleLabelAttribute() }}</span>
          <span class="s-pill {{ $admin->is_active ? 'success' : 'danger' }}">
            {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
          </span>
        </div>
      </div>
    </div>

    {{-- Forma --}}
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlarni tahrirlash</div></div>
      <div class="dash-card-body">
        {{-- enctype multipart — avatar file upload uchun --}}
        <form method="POST" action="{{ route('panel.profile.update') }}"
              enctype="multipart/form-data">
          @csrf

          <div class="row g-3">

            {{-- Ism --}}
            <div class="col-12">
              <label class="p-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control @error('name') is-invalid @enderror"
                     value="{{ old('name', $admin->name) }}" required maxlength="100">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Email --}}
            <div class="col-12">
              <label class="p-label">Email <span style="color:var(--p-danger)">*</span></label>
              <input type="email" name="email"
                     class="p-form-control @error('email') is-invalid @enderror"
                     value="{{ old('email', $admin->email) }}" required>
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Avatar (file upload) --}}
            <div class="col-12">
              <label class="p-label">Avatar rasmi</label>
              <input type="file" name="avatar"
                     class="p-form-control @error('avatar') is-invalid @enderror"
                     accept="image/jpeg,image/png,image/jpg">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
                JPG, JPEG, PNG · Maksimal 2MB
              </div>
              @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Parol bo'limi --}}
            <div class="col-12" style="padding-top:8px">
              <div style="border-top:1px solid var(--p-border);padding-top:16px;
                          font-size:12px;font-weight:600;color:var(--p-muted);
                          text-transform:uppercase;letter-spacing:.07em">
                Parolni o'zgartirish
                <span style="font-weight:400;font-size:11px;text-transform:none;letter-spacing:0">
                  (ixtiyoriy — to'ldirmasangiz o'zgarmaydi)
                </span>
              </div>
            </div>

            <div class="col-12">
              <label class="p-label">Joriy parol</label>
              <input type="password" name="current_password"
                     class="p-form-control @error('current_password') is-invalid @enderror"
                     autocomplete="current-password">
              @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Yangi parol</label>
              <input type="password" name="password"
                     class="p-form-control @error('password') is-invalid @enderror"
                     autocomplete="new-password" minlength="8">
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Yangi parolni tasdiqlash</label>
              <input type="password" name="password_confirmation"
                     class="p-form-control" autocomplete="new-password">
            </div>

          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="reset" class="btn-p ghost">Tozalash</button>
            <button type="submit" class="btn-p">
              <i class="bi bi-check-lg"></i> Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Meta info --}}
    <div class="p-card mt-3">
      <div class="dash-card-body" style="padding:12px 20px">
        <div class="row g-2" style="font-size:12px;color:var(--p-hint)">
          <div class="col-sm-4">
            <i class="bi bi-geo me-1"></i>
            Oxirgi IP: <strong style="color:var(--p-muted)">{{ $admin->last_ip ?? '—' }}</strong>
          </div>
          <div class="col-sm-4">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            Kirdi: <strong style="color:var(--p-muted)">
              {{ $admin->last_login_at
                  ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i')
                  : '—' }}
            </strong>
          </div>
          <div class="col-sm-4">
            <i class="bi bi-calendar me-1"></i>
            Yaratildi: <strong style="color:var(--p-muted)">{{ $admin->created_at?->format('d.m.Y') }}</strong>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection