@extends('a122.layouts.admin')
@section('title', isset($admin) ? 'Tahrirlash: '.$admin->name : 'Yangi admin')
@section('page-title', isset($admin) ? 'Adminni tahrirlash' : 'Yangi admin')
@section('breadcrumb', 'Panel / Adminlar / ' . (isset($admin) ? 'Tahrirlash' : 'Yaratish'))

@section('content')

<x-a122.page-header back-href="{{ isset($admin) ? route('admin.admins.show', $admin) : route('admin.admins.index') }}">
  <x-slot name="heading">{{ isset($admin) ? $admin->name : 'Yangi admin' }}</x-slot>
  <x-slot name="meta">{{ isset($admin) ? "ID #$admin->id · ".$admin->role_label : 'Yangi admin yaratish' }}</x-slot>
</x-a122.page-header>

@php
$allPermissions = [
    'users'       => ['icon'=>'bi-people',        'label'=>'Foydalanuvchilar'],
    'books'       => ['icon'=>'bi-book',           'label'=>'Kitoblar'],
    'stationery'  => ['icon'=>'bi-pencil-square',  'label'=>'Kanstovar'],
    'orders'      => ['icon'=>'bi-bag-check',      'label'=>'Buyurtmalar'],
    'sellers'     => ['icon'=>'bi-shop-window',    'label'=>'Sotuvchilar'],
    'hubs'        => ['icon'=>'bi-box-seam',       'label'=>'Hublar'],
    'couriers'    => ['icon'=>'bi-bicycle',        'label'=>'Kuryerlar'],
    'promocodes'  => ['icon'=>'bi-ticket-perforated','label'=>'Promokodlar'],
    'discounts'   => ['icon'=>'bi-percent',        'label'=>'Chegirmalar'],
    'settings'    => ['icon'=>'bi-gear',           'label'=>'Sozlamalar & Kategoriyalar'],
    'admins'      => ['icon'=>'bi-shield-check',   'label'=>'Admin boshqaruvi'],
];
$currentPerms = $admin->permissions ?? [];
@endphp

<form method="POST"
      action="{{ isset($admin) ? route('admin.admins.update', $admin) : route('admin.admins.store') }}">
  @csrf
  @if(isset($admin)) @method('PUT') @endif

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

    {{-- ── Asosiy ─────────────────────── --}}
    <div class="xl:col-span-7 fade-up">
      <div class="card-panel">
        <div class="card-panel-title mb-3">Asosiy ma'lumotlar</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror"
                   value="{{ old('name', $admin->name ?? '') }}" required>
            @error('name')<div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="">
            <label class="p-form-label">Email <span style="color:var(--p-danger)">*</span></label>
            <input type="email" name="email" class="p-form-control @error('email') border-danger @enderror"
                   value="{{ old('email', $admin->email ?? '') }}" required>
            @error('email')<div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="">
            <label class="p-form-label">Parol {{ isset($admin) ? '(ixtiyoriy)' : '*' }}</label>
            <input type="password" name="password" class="p-form-control"
                   placeholder="{{ isset($admin) ? 'O\'zgartirish uchun to\'ldiring...' : 'Kamida 8 belgi' }}"
                   {{ isset($admin) ? '' : 'required' }}>
          </div>
          <div class="">
            <label class="p-form-label">Parolni tasdiqlang</label>
            <input type="password" name="password_confirmation" class="p-form-control" placeholder="Parolni qaytaring">
          </div>
          <div class="">
            <label class="p-form-label">Rol <span style="color:var(--p-danger)">*</span></label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="roleSelector">
              @foreach(['superadmin'=>['Super Admin','Barcha bo\'limlarga cheksiz kirish','danger'],'admin'=>['Admin','Ko\'pgina bo\'limlar boshqaruvi','accent'],'moderator'=>['Moderator','Faqat belgilangan bo\'limlar','warning']] as $val=>[$label,$desc,$color])
              <div class="md:col-span-4">
                <label style="display:block;cursor:pointer">
                  <input type="radio" name="role" value="{{ $val }}"
                         {{ old('role', $admin->role ?? 'moderator') === $val ? 'checked' : '' }}
                         style="display:none" class="role-radio">
                  <div class="role-card" data-role="{{ $val }}" style="padding:14px;border-radius:10px;border:2px solid var(--p-border);background:var(--p-elevated);transition:all .15s;text-align:center">
                    <div style="font-size:14px;font-weight:600;color:var(--p-{{ $color }},var(--p-accent))">{{ $label }}</div>
                    <div style="font-size:11px;color:var(--p-hint);margin-top:3px">{{ $desc }}</div>
                  </div>
                </label>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Ruxsatlar ───────────────────── --}}
    <div class="xl:col-span-5 fade-up">
      <div class="card-panel" id="permissionsCard">
        <div class="card-panel-title mb-1">Ruxsatlar</div>
        <div style="font-size:12px;color:var(--p-hint);margin-bottom:16px">
          Superadmin uchun avtomatik barcha ruxsatlar beriladi
        </div>

        <div id="permissionsWrap">
          @foreach($allPermissions as $perm => $info)
          <div class="flex items-center justify-between mb-3"
               style="padding:10px;background:var(--p-elevated);border-radius:8px">
            <div class="flex items-center gap-2">
              <i class="bi {{ $info['icon'] }}" style="font-size:14px;color:var(--p-muted)"></i>
              <span style="font-size:13px;color:var(--p-text)">{{ $info['label'] }}</span>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input perm-check" type="checkbox"
                     name="permissions[]" value="{{ $perm }}" id="perm_{{ $perm }}"
                     {{ in_array($perm, old('permissions', $currentPerms)) ? 'checked' : '' }}>
            </div>
          </div>
          @endforeach

          <div class="flex gap-2 mt-2">
            <button type="button" class="btn-p ghost sm" onclick="toggleAllPerms(true)">
              Barchasini belgilash
            </button>
            <button type="button" class="btn-p ghost sm" onclick="toggleAllPerms(false)">
              Barchasini olib tashlash
            </button>
          </div>
        </div>
      </div>

      {{-- Holat --}}
      <div class="card-panel mt-3">
        <div class="flex items-center justify-between">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Aktiv</div>
            <div style="font-size:11px;color:var(--p-hint)">Tizimga kira oladi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $admin->is_active ?? true) ? 'checked' : '' }}>
          </div>
        </div>
      </div>
    </div>

    {{-- Submit --}}
    <div class=" fade-up d3">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          {{ isset($admin) ? 'Saqlash' : 'Yaratish' }}
        </button>
        <a href="{{ isset($admin) ? route('admin.admins.show',$admin) : route('admin.admins.index') }}"
           class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
// Role tanlanganda UI yangilanishi
function updateRoleUI() {
  const selected = document.querySelector('.role-radio:checked')?.value;
  document.querySelectorAll('.role-card').forEach(card => {
    const isActive = card.dataset.role === selected;
    card.style.borderColor = isActive ? 'var(--p-accent)' : 'var(--p-border)';
    card.style.background  = isActive ? 'var(--p-accent-d)' : 'var(--p-elevated)';
  });

  // Superadmin uchun permissionlarni yashirish
  const wrap = document.getElementById('permissionsWrap');
  if (selected === 'superadmin') {
    wrap.style.opacity = '.4';
    wrap.style.pointerEvents = 'none';
  } else {
    wrap.style.opacity = '1';
    wrap.style.pointerEvents = 'auto';
  }
}

document.querySelectorAll('.role-radio').forEach(r => {
  r.addEventListener('change', updateRoleUI);
});

// Role cardga click
document.querySelectorAll('.role-card').forEach(card => {
  card.addEventListener('click', () => {
    const radio = document.querySelector(`.role-radio[value="${card.dataset.role}"]`);
    if (radio) { radio.checked = true; updateRoleUI(); }
  });
});

updateRoleUI();

// Barcha permission toggle
function toggleAllPerms(state) {
  document.querySelectorAll('.perm-check').forEach(c => c.checked = state);
}
</script>
@endpush

@endsection
