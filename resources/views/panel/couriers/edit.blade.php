@extends('panel.layouts.panel')
@section('title', isset($courier) ? 'Tahrirlash: '.$courier->first_name : 'Yangi kuryer')
@section('page-title', isset($courier) ? 'Kuryerni tahrirlash' : 'Yangi kuryer')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ isset($courier) ? route('panel.couriers.show', $courier) : route('panel.couriers.index') }}"
     class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">
      {{ isset($courier) ? $courier->first_name.' '.$courier->last_name : 'Yangi kuryer' }}
    </h1>
    <p class="page-sub">{{ isset($courier) ? "ID #$courier->id" : "Yangi kuryer qo'shish" }}</p>
  </div>
</div>

<form method="POST"
      action="{{ isset($courier) ? route('panel.couriers.update', $courier) : route('panel.couriers.store') }}"
      enctype="multipart/form-data">
  @csrf
  @if(isset($courier)) @method('PUT') @endif

  <div class="row g-3">

    <div class="col-xl-8 fade-up">
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
        <div style="padding:0 18px 18px">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="first_name"
                     class="p-form-control @error('first_name') border-danger @enderror"
                     value="{{ old('first_name', $courier->first_name ?? '') }}"
                     required maxlength="25">
              @error('first_name')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Familiya <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="last_name"
                     class="p-form-control @error('last_name') border-danger @enderror"
                     value="{{ old('last_name', $courier->last_name ?? '') }}"
                     required maxlength="25">
              @error('last_name')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control @error('phone_number') border-danger @enderror"
                     value="{{ old('phone_number', $courier->phone_number ?? '') }}"
                     required maxlength="20" placeholder="998901234567">
              @error('phone_number')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Viloyat <span style="color:var(--p-danger)">*</span></label>
              <select name="region"
                      class="p-form-control @error('region') border-danger @enderror"
                      required>
                <option value="">Tanlang...</option>
                @foreach([
                  "Toshkent shahri","Toshkent viloyati","Samarqand","Buxoro",
                  "Namangan","Andijon","Farg'ona","Qashqadaryo","Surxondaryo",
                  "Jizzax","Sirdaryo","Navoiy","Xorazm","Qoraqalpog'iston"
                ] as $region)
                <option value="{{ $region }}"
                  {{ old('region', $courier->region ?? '') === $region ? 'selected' : '' }}>
                  {{ $region }}
                </option>
                @endforeach
              </select>
              @error('region')
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="p-form-label">
                Parol
                @if(isset($courier))
                  <span style="color:var(--p-hint);font-size:11px">(o'zgartirish uchun to'ldiring)</span>
                @else
                  <span style="color:var(--p-danger)">*</span>
                @endif
              </label>
              <input type="password" name="password" class="p-form-control"
                     placeholder="{{ isset($courier) ? 'Yangi parol...' : 'Parol kiriting' }}"
                     {{ isset($courier) ? '' : 'required' }} minlength="6"
                     autocomplete="new-password">
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Balans (UZS)</label>
              <input type="number" name="balance" class="p-form-control"
                     value="{{ old('balance', $courier->balance ?? 0) }}"
                     min="0" step="1">
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 fade-up">

      {{-- Foto --}}
      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Foto</div></div>
        <div style="padding:0 18px 18px">
          @if(isset($courier) && $courier->photo)
          <div style="margin-bottom:12px;text-align:center">
            <img src="{{ Storage::url($courier->photo) }}"
                 style="width:72px;height:72px;border-radius:50%;object-fit:cover;
                        border:2px solid var(--p-border)">
          </div>
          @endif
          <label class="p-form-label">Rasm yuklash</label>
          <input type="file" name="photo" class="p-form-control" accept="image/*">
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
        </div>
      </div>

      {{-- Holat --}}
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Holat</div></div>
        <div style="padding:0 18px 18px">
          @php $curStatus = old('status', $courier->status ?? 'pending'); @endphp
          @foreach([
            ['approved', 'Tasdiqlangan', 'success', 'Buyurtma qabul qila oladi'],
            ['pending',  'Kutilmoqda',  'warning', 'Ko\'rib chiqilmoqda'],
            ['rejected', 'Rad etildi',  'danger',  'Tizimga kirishi bloklangan'],
          ] as [$val, $lbl, $clr, $hint])
          <label style="display:flex;align-items:center;gap:10px;padding:10px;
                         border-radius:8px;cursor:pointer;margin-bottom:4px;
                         background:{{ trim((string)$curStatus)===$val ? 'var(--p-'.$clr.'-d)' : 'transparent' }};
                         border:1px solid {{ trim((string)$curStatus)===$val ? 'var(--p-'.$clr.')' : 'transparent' }};
                         transition:all .15s"
                 onclick="this.parentElement.querySelectorAll('label').forEach(l=>l.style.background='transparent'&&(l.style.border='1px solid transparent'));this.style.background='var(--p-{{ $clr }}-d)';this.style.border='1px solid var(--p-{{ $clr }})'">
            <input type="radio" name="status" value="{{ $val }}"
                   {{ trim((string)$curStatus)===$val ? 'checked' : '' }}
                   style="accent-color:var(--p-{{ $clr }})">
            <div style="flex:1">
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                <span class="s-pill {{ $clr }}" style="font-size:10px;margin-right:4px">{{ $lbl }}</span>
              </div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:1px">{{ $hint }}</div>
            </div>
          </label>
          @endforeach
          @error('status')
            <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
          @enderror
        </div>
      </div>

    </div>

    <div class="col-12 fade-up">
      <div class="d-flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          {{ isset($courier) ? 'Saqlash' : 'Yaratish' }}
        </button>
        <a href="{{ isset($courier) ? route('panel.couriers.show',$courier) : route('panel.couriers.index') }}"
           class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>

  </div>
</form>

@endsection