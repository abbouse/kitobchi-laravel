@extends('a122.layouts.admin')
@section('title', 'Yangi admin')
@section('page-title', 'Yangi admin yaratish')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-a122.page-header back-href="{{ route('admin.admins.index') }}">
  <x-slot name="heading">Yangi admin yaratish</x-slot>
  <x-slot name="meta">Panel uchun yangi foydalanuvchi qo'shish</x-slot>
</x-a122.page-header>


    <form method="POST" action="{{ route('admin.admins.store') }}">
      @csrf

      {{-- Asosiy --}}
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Admin ma'lumotlari</div>
        </div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" required maxlength="100">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="">
              <label class="p-form-label">Email <span style="color:var(--p-danger)">*</span></label>
              <input type="email" name="email"
                     class="p-form-control @error('email') is-invalid @enderror"
                     value="{{ old('email') }}" required>
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="">
              <label class="p-form-label">Parol <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password"
                     class="p-form-control @error('password') is-invalid @enderror"
                     required minlength="8" autocomplete="new-password">
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="">
              <label class="p-form-label">Parolni tasdiqlash <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password_confirmation"
                     class="p-form-control" required autocomplete="new-password">
            </div>

          </div>
        </div>
      </div>

      {{-- Rol --}}
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Rol va ruxsatlar</div>
        </div>
        <div class="dash-card-body">

          <label class="p-form-label mb-2">Rol <span style="color:var(--p-danger)">*</span></label>
          <div class="flex gap-2 mb-3" id="roleCards">
            @foreach([
              ['superadmin', 'Superadmin', 'bi-shield-fill-check', 'danger',  'Barcha ruxsatlar'],
              ['admin',      'Admin',      'bi-person-badge',       'accent',  'Ko\'pchilik ruxsatlar'],
              ['moderator',  'Moderator',  'bi-eye',                'warning', 'Cheklangan ruxsatlar'],
            ] as [$val, $lbl, $icon, $clr, $desc])
            <label style="flex:1;cursor:pointer">
              <input type="radio" name="role" value="{{ $val }}"
                     class="hidden role-radio"
                     {{ old('role','moderator') === $val ? 'checked' : '' }}>
              <div class="role-card" data-role="{{ $val }}"
                   style="border:2px solid var(--p-border);border-radius:10px;
                          padding:14px;text-align:center;transition:all .2s;
                          {{ old('role','moderator') === $val ? 'border-color:var(--p-'.$clr.');background:var(--p-'.$clr.'-d, var(--p-elevated))' : '' }}">
                <i class="bi {{ $icon }}"
                   style="font-size:22px;color:var(--p-{{ $clr }});display:block;margin-bottom:6px"></i>
                <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $lbl }}</div>
                <div style="font-size:11px;color:var(--p-hint);margin-top:2px">{{ $desc }}</div>
              </div>
            </label>
            @endforeach
          </div>
          @error('role')<div style="font-size:12px;color:var(--p-danger)">{{ $message }}</div>@enderror

          {{-- Ruxsatlar (superadmin uchun yashirin) --}}
          <div id="permissionsBlock" style="{{ old('role','moderator') === 'superadmin' ? 'display:none' : '' }}">
            <label class="p-form-label mb-2">Ruxsatlar</label>
            @php
              $allPerms = [
                'users'      => ['Foydalanuvchilar', 'bi-people'],
                'books'      => ['Kitoblar',         'bi-book'],
                'stationery' => ['Kanstovar',        'bi-pencil-square'],
                'orders'     => ['Buyurtmalar',      'bi-bag-check'],
                'sellers'    => ['Sotuvchilar',      'bi-shop-window'],
                'couriers'   => ['Kuryerlar',        'bi-bicycle'],
                'promocodes' => ['Promokodlar',      'bi-ticket-perforated'],
                'settings'   => ['Sozlamalar',       'bi-gear'],
                'admins'     => ['Adminlar',         'bi-shield-check'],
              ];
              $oldPerms = old('permissions', []);
            @endphp
            <div class="flex flex-wrap gap-2">
              @foreach($allPerms as $perm => [$label, $icon])
              <label style="cursor:pointer;background:var(--p-elevated);
                            border:1px solid var(--p-border);border-radius:8px;
                            padding:8px 14px;display:flex;align-items:center;gap:7px;
                            transition:all .15s" class="perm-label">
                <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                       style="accent-color:var(--p-accent);width:16px;height:16px"
                       {{ in_array($perm, $oldPerms) ? 'checked' : '' }}>
                <i class="bi {{ $icon }}" style="color:var(--p-muted);font-size:14px"></i>
                <span style="font-size:12px;color:var(--p-text)">{{ $label }}</span>
              </label>
              @endforeach
            </div>
            <div class="mt-2">
              <button type="button" onclick="toggleAllPerms(true)"
                      class="btn-p ghost sm">Barchasini belgilash</button>
              <button type="button" onclick="toggleAllPerms(false)"
                      class="btn-p ghost sm ml-1">Barchasini olib tashlash</button>
            </div>
          </div>

          {{-- Holat --}}
          <div class="mt-3">
            <label class="p-form-label">Holat</label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" name="is_active" value="1" checked
                     style="width:18px;height:18px;accent-color:var(--p-accent)">
              <span style="font-size:13px;color:var(--p-text)">Faol (kirish mumkin)</span>
            </label>
          </div>

        </div>
      </div>

      <div class="flex gap-2 justify-end">
        <a href="{{ route('admin.admins.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-person-plus"></i> Admin yaratish
        </button>
      </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Rol tanlash
document.querySelectorAll('.role-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-card').forEach(c => {
      c.style.borderColor = 'var(--p-border)';
      c.style.background  = '';
    });
    const card = document.querySelector(`.role-card[data-role="${this.value}"]`);
    const colors = { superadmin: 'danger', admin: 'accent', moderator: 'warning' };
    const clr = colors[this.value] || 'accent';
    card.style.borderColor = `var(--p-${clr})`;
    card.style.background  = `var(--p-elevated)`;

    document.getElementById('permissionsBlock').style.display =
      this.value === 'superadmin' ? 'none' : '';
  });
});

function toggleAllPerms(state) {
  document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = state);
}
</script>
@endpush