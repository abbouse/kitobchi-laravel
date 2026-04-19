@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$seller->shop_name)
@section('page-title', 'Sotuvchini tahrirlash')

@section('content')

@php
  $isMainShop = $isMainShop ?? true;
  $curTypes   = $seller->activity_types; // accessor — har doim array
@endphp

<x-panel.page-header back-href="{{ route('panel.sellers.show', $seller) }}">
  <x-slot name="heading">{{ $seller->shop_name }}</x-slot>
  <x-slot name="meta">ID: #{{ $seller->id }}</x-slot>
</x-panel.page-header>


<form method="POST" action="{{ route('panel.sellers.update', $seller) }}"
      enctype="multipart/form-data">
  @csrf @method('PUT')

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

    {{-- ── Asosiy ─────────────────────────────────────────────── --}}
    <div class="xl:col-span-8 fade-up">
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Do'kon nomi <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="shop_name"
                     class="p-form-control @error('shop_name') border-danger @enderror"
                     value="{{ old('shop_name', $seller->shop_name) }}" required>
              @error('shop_name')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Ism</label>
              <input type="text" name="firstname" class="p-form-control"
                     value="{{ old('firstname', $seller->firstname) }}" placeholder="Ism">
            </div>

            <div class="">
              <label class="p-form-label">Familiya</label>
              <input type="text" name="lastname" class="p-form-control"
                     value="{{ old('lastname', $seller->lastname) }}" placeholder="Familiya">
            </div>

            <div class="">
              <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control @error('phone_number') border-danger @enderror"
                     value="{{ old('phone_number', $seller->phone_number) }}" required>
              @error('phone_number')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Viloyat <span style="color:var(--p-danger)">*</span></label>
              <select name="region" class="p-form-control" required>
                @foreach([
                  "Toshkent shahri","Toshkent viloyati","Samarqand","Buxoro",
                  "Namangan","Andijon","Farg'ona","Qashqadaryo","Surxondaryo",
                  "Jizzax","Sirdaryo","Navoiy","Xorazm","Qoraqalpog'iston"
                ] as $region)
                <option value="{{ $region }}"
                  {{ old('region', $seller->region) === $region ? 'selected' : '' }}>
                  {{ $region }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="">
              <label class="p-form-label">
                Yangi parol
                <span style="color:var(--p-hint);font-size:11px">(ixtiyoriy)</span>
              </label>
              <input type="password" name="password" class="p-form-control"
                     placeholder="Yangi parol..." autocomplete="new-password">
            </div>

            <div class="">
              <label class="p-form-label">Balans (UZS)</label>
              <input type="number" name="balance" class="p-form-control"
                     value="{{ old('balance', $seller->balance ?? 0) }}" min="0" step="1">
            </div>

            <div class="">
              <label class="p-form-label">
                Komissiya foizi (%)
                <span style="color:var(--p-hint);font-size:11px">— global: {{ \App\Models\CommissionSetting::orderBy('priceFrom')->first()?->percent ?? '—' }}%</span>
              </label>
              <div style="position:relative">
                <input type="number" name="commission_percent" class="p-form-control"
                       value="{{ old('commission_percent', $seller->commission_percent ?? '') }}"
                       min="0" max="100" step="0.1"
                       placeholder="Global komissiya">
                <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                             font-size:13px;color:var(--p-hint)">%</span>
              </div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
                Bo'sh qoldirilsa global komissiya qo'llaniladi
              </div>
            </div>

            {{-- Hodim uchun rol --}}
            @if(!$isMainShop)
            <div class="">
              <label class="p-form-label">Rol</label>
              <select name="role" class="p-form-control">
                <option value="">Tanlang</option>
                @foreach($roles as $rKey => $rLabel)
                <option value="{{ $rKey }}"
                  {{ old('role', $seller->role) === $rKey ? 'selected' : '' }}>
                  {{ $rLabel }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="">
              <label class="p-form-label">Hodim holati</label>
              <select name="staff_status" class="p-form-control">
                <option value="active"
                  {{ old('staff_status', $seller->staff_status) === 'active' ? 'selected' : '' }}>
                  Faol
                </option>
                <option value="inactive"
                  {{ old('staff_status', $seller->staff_status) === 'inactive' ? 'selected' : '' }}>
                  Nofaol
                </option>
              </select>
            </div>
            @endif

            {{-- Faoliyat turlari --}}
            @if($isMainShop)
            <div class="">
              <label class="p-form-label">Faoliyat turlari</label>
              <div class="flex gap-3 flex-wrap">
                @foreach(['Kitob', 'Kanstovar'] as $type)
                @php
                  $checked = in_array($type, old('activity_types', $curTypes));
                @endphp
                <label style="display:flex;align-items:center;gap:8px;padding:10px 16px;
                               background:var(--p-elevated);border-radius:8px;cursor:pointer;
                               border:1px solid {{ $checked ? 'var(--p-accent)' : 'transparent' }}">
                  <input type="checkbox" name="activity_types[]" value="{{ $type }}"
                         id="type_{{ $type }}"
                         {{ $checked ? 'checked' : '' }}
                         style="accent-color:var(--p-accent)">
                  <span style="font-size:13px;color:var(--p-text)">{{ $type }}</span>
                </label>
                @endforeach
              </div>
            </div>
            @endif

          </div>
        </div>
      </div>
    </div>

    {{-- ── O'ng panel ──────────────────────────────────────────── --}}
    <div class="xl:col-span-4 fade-up">

      {{-- Foto --}}
      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Rasm</div></div>
        <div style="padding:0 18px 18px">
          @if($seller->photo)
          <div style="margin-bottom:12px;text-align:center">
            <img src="{{ Storage::url($seller->photo) }}"
                 style="width:72px;height:72px;border-radius:10px;object-fit:cover;
                        border:2px solid var(--p-border)">
          </div>
          @endif
          <input type="file" name="photo" class="p-form-control" accept="image/*">
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
        </div>
      </div>

      {{-- Holat --}}
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Holat</div></div>
        <div style="padding:0 18px 18px">
          <label class="p-form-label mb-2">Moderatsiya</label>
          <select name="status" class="p-form-control mb-3">
            @foreach([
              'pending'  => 'Kutilmoqda',
              'approved' => 'Tasdiqlangan',
              'rejected' => 'Rad etilgan',
            ] as $val => $lbl)
            <option value="{{ $val }}"
              {{ old('status', $seller->status) === $val ? 'selected' : '' }}>
              {{ $lbl }}
            </option>
            @endforeach
          </select>

          <div style="display:flex;align-items:center;justify-content:space-between;
                      padding:10px;background:var(--p-elevated);border-radius:8px">
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">Yashirin</div>
              <div style="font-size:11px;color:var(--p-hint)">Marketplaceda ko'rinmaydi</div>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" name="is_hidden" value="1"
                     {{ old('is_hidden', $seller->is_hidden) ? 'checked' : '' }}>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Submit --}}
    <div class=" fade-up">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
        <a href="{{ route('panel.sellers.show', $seller) }}" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>
    </div>

  </div>
</form>

@endsection