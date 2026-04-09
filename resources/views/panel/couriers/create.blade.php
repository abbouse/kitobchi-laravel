@extends('panel.layouts.panel')
@section('title', 'Yangi kuryer')
@section('page-title', 'Yangi kuryer qo\'shish')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">
    {{-- enctype kerak - photo file upload --}}
    <form method="POST" action="{{ route('panel.couriers.store') }}"
          enctype="multipart/form-data">
      @csrf

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Kuryer ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            <div class="col-sm-6">
              <label class="p-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="first_name"
                     class="p-form-control @error('first_name') is-invalid @enderror"
                     value="{{ old('first_name') }}" required maxlength="25">
              @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Familiya <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="last_name"
                     class="p-form-control @error('last_name') is-invalid @enderror"
                     value="{{ old('last_name') }}" required maxlength="25">
              @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control @error('phone_number') is-invalid @enderror"
                     value="{{ old('phone_number') }}" required
                     placeholder="998901234567" maxlength="20">
              @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Viloyat / Hudud <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="region"
                     class="p-form-control @error('region') is-invalid @enderror"
                     value="{{ old('region') }}" required maxlength="50"
                     placeholder="Toshkent, Samarqand...">
              @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Parol <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password"
                     class="p-form-control @error('password') is-invalid @enderror"
                     required minlength="6" autocomplete="new-password">
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" checked
                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv (buyurtma qabul qiladi)</span>
              </label>
            </div>

            <div class="col-12">
              <label class="p-label">Profil rasmi</label>
              <input type="file" name="photo"
                     class="p-form-control @error('photo') is-invalid @enderror"
                     accept="image/jpeg,image/png,image/jpg">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
                JPG, PNG · Maksimal 2MB
              </div>
              @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('panel.couriers.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p">
          <i class="bi bi-bicycle"></i> Kuryer qo'shish
        </button>
      </div>
    </form>
  </div>
</div>
@endsection