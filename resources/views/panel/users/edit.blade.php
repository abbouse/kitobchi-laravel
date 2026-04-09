@extends('panel.layouts.panel')

@section('title', isset($user) ? 'Tahrirlash: '.$user->name : 'Yangi foydalanuvchi')
@section('page-title', isset($user) ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi')
@section('breadcrumb', 'Panel / Foydalanuvchilar / ' . (isset($user) ? 'Tahrirlash' : 'Yaratish'))

@section('content')

<div class="page-header fade-up d-flex align-items-center gap-3">
  <a href="{{ isset($user) ? route('panel.users.show', $user) : route('panel.users.index') }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">{{ isset($user) ? $user->name.' '.$user->lastname : 'Yangi foydalanuvchi' }}</h1>
    <p class="page-sub">{{ isset($user) ? "ID #$user->id" : "Yangi foydalanuvchi yaratish" }}</p>
  </div>
</div>

<form method="POST"
      action="{{ isset($user) ? route('panel.users.update', $user) : route('panel.users.store') }}"
      enctype="multipart/form-data">
  @csrf
  @if(isset($user)) @method('PUT') @endif

  <div class="row g-3">

    {{-- ── Asosiy ma'lumotlar ─────────── --}}
    <div class="col-xl-8 fade-up d1">
      <div class="p-card">
        <div class="p-card-header">
          <div class="p-card-title">Asosiy ma'lumotlar</div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror"
                   value="{{ old('name', $user->name ?? '') }}" required placeholder="Foydalanuvchi ismi">
            @error('name')<div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Familiya</label>
            <input type="text" name="lastname" class="p-form-control"
                   value="{{ old('lastname', $user->lastname ?? '') }}" placeholder="Familiya">
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Telefon raqami <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="phone_number" class="p-form-control @error('phone_number') border-danger @enderror"
                   value="{{ old('phone_number', $user->phone_number ?? '') }}" placeholder="+998901234567" required>
            @error('phone_number')<div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Email</label>
            <input type="email" name="email" class="p-form-control @error('email') border-danger @enderror"
                   value="{{ old('email', $user->email ?? '') }}" placeholder="email@example.com">
            @error('email')<div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Lavozim / Position</label>
            <input type="text" name="position" class="p-form-control"
                   value="{{ old('position', $user->position ?? '') }}" placeholder="O'quvchi, Yozuvchi...">
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Bio</label>
            <input type="text" name="bio" class="p-form-control"
                   value="{{ old('bio', $user->bio ?? '') }}" placeholder="Qisqa bio">
          </div>
        </div>
      </div>
    </div>

    {{-- ── O'ng panel ──────────────────── --}}
    <div class="col-xl-4 fade-up d2">

      {{-- Avatar --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Avatar</div>
        @if(isset($user) && $user->avatar)
        <div style="margin-bottom:10px">
          <img src="{{ Storage::url($user->avatar) }}" style="width:80px;height:80px;border-radius:50%;object-fit:cover" alt="">
        </div>
        @endif
        <label class="p-form-label">Rasm yuklash</label>
        <input type="file" name="avatar" class="p-form-control" accept="image/*">
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG, WebP · max 2MB</div>
      </div>

      {{-- Pul va ruxsat --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Moliyaviy ma'lumotlar</div>
        <div class="mb-3">
          <label class="p-form-label">Haqiqiy balans (UZS)</label>
          <input type="number" name="real_balance" class="p-form-control"
                 value="{{ old('real_balance', $user->real_balance ?? 0) }}" step="1" min="0">
        </div>
        <div class="mb-3">
          <label class="p-form-label">Cashback (UZS)</label>
          <input type="number" name="cashback" class="p-form-control"
                 value="{{ old('cashback', $user->cashback ?? 0) }}" step="1" min="0">
        </div>
        <div>
          <label class="p-form-label">AI cheklovi (token)</label>
          <input type="number" name="ai_limit" class="p-form-control"
                 value="{{ old('ai_limit', $user->ai_limit ?? 0) }}" step="1" min="0">
        </div>
      </div>

      {{-- Toggle --}}
      <div class="p-card">
        <div class="p-card-title mb-3">Holat va ruxsatlar</div>
        <div class="d-flex align-items-center justify-content-between mb-3" style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Tasdiqlangan</div>
            <div style="font-size:11px;color:var(--p-hint)">Telefon tasdiqlangan</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="isVerified" value="1"
                   {{ old('isVerified', $user->isVerified ?? false) ? 'checked' : '' }}>
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between" style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Premium</div>
            <div style="font-size:11px;color:var(--p-hint)">1 yil muddatga beriladi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_premium" value="1"
                   {{ old('is_premium', $user->is_premium ?? false) ? 'checked' : '' }}>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Submit ───────────────────────── --}}
    <div class="col-12 fade-up d3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          {{ isset($user) ? 'Saqlash' : 'Yaratish' }}
        </button>
        <a href="{{ isset($user) ? route('panel.users.show', $user) : route('panel.users.index') }}" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>
    </div>

  </div>
</form>

@endsection