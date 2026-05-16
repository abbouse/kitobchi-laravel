@php
  $user = $user ?? null;
  $val = fn($k, $d='') => old($k, data_get($user, $k, $d));
@endphp

<form method="POST" action="{{ $action }}" class="kc-form-shell mt-4">
  @csrf
  @if(($method ?? 'POST') !== 'POST') @method($method) @endif

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="kc-form-card">
        <div class="kc-form-card__title">
          <span class="kc-form-card__icon"><i class="bi bi-person"></i></span>
          <span>Asosiy ma’lumotlar</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="user_name">Ism</label>
            <input id="user_name" name="name" required value="{{ $val('name') }}" class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_lastname">Familiya</label>
            <input id="user_lastname" name="lastname" value="{{ $val('lastname') }}" class="form-control @error('lastname') is-invalid @enderror">
            @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_email">Email</label>
            <input id="user_email" name="email" required value="{{ $val('email') }}" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_phone">Telefon</label>
            <input id="user_phone" name="phone_number" value="{{ $val('phone_number') }}" class="form-control @error('phone_number') is-invalid @enderror">
            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_position">Foydalanuvchi darajasi</label>
            @php($currentPosition = $val('position'))
            <select id="user_position" name="position" class="form-select @error('position') is-invalid @enderror">
              <option value="reader" {{ in_array($currentPosition, ['', 'reader', "O'quvchi"], true) ? 'selected' : '' }}>Kitobxon</option>
              <option value="active_reader" {{ $currentPosition === 'active_reader' ? 'selected' : '' }}>Faol kitobxon</option>
              <option value="book_lover" {{ $currentPosition === 'book_lover' ? 'selected' : '' }}>Kitob muxlisi</option>
              <option value="reviewer" {{ $currentPosition === 'reviewer' ? 'selected' : '' }}>Sharhlovchi</option>
              <option value="collector" {{ $currentPosition === 'collector' ? 'selected' : '' }}>Kitob yig'uvchi</option>
              <option value="book_club_star" {{ $currentPosition === 'book_club_star' ? 'selected' : '' }}>Book Club yulduzi</option>
              <option value="market_explorer" {{ $currentPosition === 'market_explorer' ? 'selected' : '' }}>Market kashfiyotchisi</option>
              <option value="literary_mentor" {{ $currentPosition === 'literary_mentor' ? 'selected' : '' }}>Adabiy yo'lboshchi</option>
            </select>
            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_staff_role">Moderatsiya roli</label>
            @php($currentStaffRole = $val('staff_role'))
            <select id="user_staff_role" name="staff_role" class="form-select @error('staff_role') is-invalid @enderror">
              <option value="">Yo‘q</option>
              <option value="moderator" {{ $currentStaffRole === 'moderator' ? 'selected' : '' }}>Moderator</option>
              <option value="administrator" {{ $currentStaffRole === 'administrator' ? 'selected' : '' }}>Administrator</option>
            </select>
            @error('staff_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="kc-form-card">
        <div class="kc-form-card__title">
          <span class="kc-form-card__icon"><i class="bi bi-sliders"></i></span>
          <span>Sozlamalar</span>
        </div>

        <div class="kc-checkbox-stack">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="isVerified" value="1" {{ $val('isVerified') ? 'checked' : '' }}>
            <span class="form-check-label">Tasdiqlangan foydalanuvchi</span>
          </label>

          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="is_premium" value="1" {{ $val('is_premium') ? 'checked' : '' }}>
            <span class="form-check-label">Premium</span>
          </label>
        </div>

        @if($user && $user->isBlocked())
          <div class="alert alert-danger mt-4 mb-0 rounded-4 border-0 shadow-sm">
            <div class="fw-semibold">Akkaunt bloklangan</div>
            <div class="small mt-1">Muddat: {{ $user->activeBlockLabel() }}</div>
          </div>
        @endif

        <div class="kc-note-card mt-4">
          <span class="kc-note-card__title">Admin eslatmasi</span>
          Telefon va email maydonlari account identifikatori sifatida ishlatiladi. Yangilashda dublikat cheklovlari saqlanadi.
        </div>
      </div>
    </div>
  </div>

  <div class="kc-form-actions">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Bekor qilish</a>
    <button type="submit" class="btn btn-primary rounded-pill px-4">
      <i class="bi bi-check2 me-2"></i>Saqlash
    </button>
  </div>
</form>
