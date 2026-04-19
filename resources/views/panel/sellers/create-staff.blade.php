@extends('panel.layouts.panel')
@section('title', 'Hodim qo\'shish')
@section('page-title', 'Hodim qo\'shish')

@section('content')

<x-panel.page-header back-href="{{ route('panel.sellers.show', $seller) }}">
  <x-slot name="heading">Yangi hodim</x-slot>
  <x-slot name="meta">{{ $seller->shop_name }} uchun</x-slot>
</x-panel.page-header>


<div class="kc-page-inner w-full min-w-0 fade-up">
    <form method="POST"
          action="{{ route('panel.sellers.staff.store', $seller) }}"
          enctype="multipart/form-data">
      @csrf

      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Hodim ma'lumotlari</div></div>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="firstname"
                     class="p-form-control @error('firstname') border-danger @enderror"
                     value="{{ old('firstname') }}" required maxlength="100">
              @error('firstname')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Familiya</label>
              <input type="text" name="lastname" class="p-form-control"
                     value="{{ old('lastname') }}" maxlength="100">
            </div>

            <div class="">
              <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control @error('phone_number') border-danger @enderror"
                     value="{{ old('phone_number') }}" required placeholder="998901234567">
              @error('phone_number')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Parol <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password"
                     class="p-form-control @error('password') border-danger @enderror"
                     required minlength="6" autocomplete="new-password">
              @error('password')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Rol <span style="color:var(--p-danger)">*</span></label>
              <select name="role" class="p-form-control @error('role') border-danger @enderror"
                      required>
                <option value="">Tanlang...</option>
                @foreach($roles as $key => $label)
                <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>
                  {{ $label }}
                </option>
                @endforeach
              </select>
              @error('role')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="">
              <label class="p-form-label">Holat</label>
              <select name="staff_status" class="p-form-control">
                <option value="active" {{ old('staff_status','active')==='active' ? 'selected':'' }}>
                  Faol
                </option>
                <option value="inactive" {{ old('staff_status')==='inactive' ? 'selected':'' }}>
                  Nofaol
                </option>
              </select>
            </div>

            <div class="">
              <label class="p-form-label">Profil rasmi</label>
              <input type="file" name="photo" class="p-form-control" accept="image/*">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
            </div>

          </div>
        </div>
      </div>

      {{-- Do'kon info --}}
      <div class="p-card mb-3" style="background:var(--p-elevated)">
        <div style="padding:14px 18px;display:flex;align-items:center;gap:12px">
          <div style="width:36px;height:36px;border-radius:8px;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-warning),#f97316);
                      display:flex;align-items:center;justify-content:center;
                      font-size:15px;font-weight:700;color:#fff">
            @if($seller->photo)
              <img src="{{ Storage::url($seller->photo) }}"
                   style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($seller->shop_name, 0, 1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              {{ $seller->shop_name }}
            </div>
            <div style="font-size:11px;color:var(--p-hint)">
              Hodim shu do'konga biriktiriladi
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-person-plus"></i> Hodim qo'shish
        </button>
        <a href="{{ route('panel.sellers.show', $seller) }}" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>

    </form>
</div>

@endsection