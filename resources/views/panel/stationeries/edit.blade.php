@extends('panel.layouts.panel')
@section('title', isset($stationery) ? 'Tahrirlash: '.$stationery->name : 'Yangi mahsulot')
@section('page-title', isset($stationery) ? 'Tahrirlash' : 'Yangi mahsulot')

@section('content')
<div class="row g-3 justify-content-center">
  <div class="col-xl-8">
    <form method="POST"
          action="{{ isset($stationery) ? route('panel.stationery.update', $stationery) : route('panel.stationery.store') }}">
      @csrf
      @if(isset($stationery)) @method('PUT') @endif

      {{-- Asosiy sozlamalar --}}
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Moderatsiya sozlamalari</div>
          <div class="dash-card-sub">Faqat admin tomonidan o'zgartiriladi</div>
        </div>
        <div class="dash-card-body">
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="p-label">Tasdiqlash holati</label>
              <select name="is_approved" class="p-form-control @error('is_approved') is-invalid @enderror">
                <option value="0" {{ old('is_approved', $stationery->is_approved ?? 0)==0?'selected':'' }}>Kutilmoqda</option>
                <option value="1" {{ old('is_approved', $stationery->is_approved ?? 0)==1?'selected':'' }}>Tasdiqlangan</option>
                <option value="2" {{ old('is_approved', $stationery->is_approved ?? 0)==2?'selected':'' }}>Rad etilgan</option>
              </select>
              @error('is_approved')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
              <label class="p-label">Ko'rinish</label>
              <select name="is_hidden" class="p-form-control">
                <option value="0" {{ old('is_hidden', $stationery->is_hidden ?? 0)==0?'selected':'' }}>Ko'rinadi</option>
                <option value="1" {{ old('is_hidden', $stationery->is_hidden ?? 0)==1?'selected':'' }}>Yashirilgan</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {{-- Narx va ombor --}}
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Narx va ombor</div>
        </div>
        <div class="dash-card-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="p-label">Narx (UZS) <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="price" class="p-form-control @error('price') is-invalid @enderror"
                     value="{{ old('price', $stationery->price ?? '') }}" min="0" required>
              @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4">
              <label class="p-label">Chegirma narx (UZS)</label>
              <input type="number" name="discount_price" class="p-form-control @error('discount_price') is-invalid @enderror"
                     value="{{ old('discount_price', $stationery->discount_price ?? '') }}" min="0">
              @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4">
              <label class="p-label">Ombordagi miqdor <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="stock" class="p-form-control @error('stock') is-invalid @enderror"
                     value="{{ old('stock', $stationery->stock ?? '') }}" min="0" required>
              @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>

      {{-- Readonly info (agar mavjud bo'lsa) --}}
      @if(isset($stationery))
      <div class="p-card mb-3" style="opacity:.7">
        <div class="dash-card-head">
          <div class="dash-card-title">Sotuvchi ma'lumotlari</div>
          <div class="dash-card-sub"><i class="bi bi-lock"></i> Faqat o'qish</div>
        </div>
        <div class="dash-card-body">
          <div class="row g-3">
            @foreach([
              ['Nomi',        $stationery->name],
              ['Kategoriya',  $stationery->category?->name_uz],
              ['Material',    $stationery->material ?? '—'],
              ['Sotuvchi',    $stationery->seller?->shop_name ?? $stationery->seller_id],
            ] as [$k, $v])
            <div class="col-sm-6">
              <label class="p-label">{{ $k }}</label>
              <input type="text" class="p-form-control" value="{{ $v }}" disabled>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index') }}"
           class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
@endsection