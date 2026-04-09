@extends('panel.layouts.panel')
@section('title', 'Yangi foydalanuvchi')
@section('page-title', 'Yangi foydalanuvchi')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">
    <form method="POST" action="{{ route('panel.users.store') }}">
      @csrf

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Shaxsiy ma'lumotlar</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            <div class="col-sm-6">
              <label class="p-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" required maxlength="30">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Familiya</label>
              <input type="text" name="lastname"
                     class="p-form-control @error('lastname') is-invalid @enderror"
                     value="{{ old('lastname') }}" maxlength="30">
              @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control @error('phone_number') is-invalid @enderror"
                     value="{{ old('phone_number') }}" required
                     placeholder="998901234567" maxlength="12">
              @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Email</label>
              <input type="email" name="email"
                     class="p-form-control @error('email') is-invalid @enderror"
                     value="{{ old('email') }}" maxlength="40">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-sm-6">
              <label class="p-label">Jinsi</label>
              <select name="sex" class="p-form-control">
                <option value="">Tanlanmagan</option>
                <option value="male"   {{ old('sex')==='male'?'selected':'' }}>Erkak</option>
                <option value="female" {{ old('sex')==='female'?'selected':'' }}>Ayol</option>
              </select>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Premium</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="is_premium" value="0">
                <input type="checkbox" name="is_premium" value="1"
                       {{ old('is_premium') ? 'checked' : '' }}
                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Premium foydalanuvchi</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('panel.users.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p">
          <i class="bi bi-person-plus"></i> Yaratish
        </button>
      </div>
    </form>
  </div>
</div>
@endsection