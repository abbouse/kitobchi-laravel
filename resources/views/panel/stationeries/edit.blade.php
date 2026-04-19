@extends('panel.layouts.panel')
@section('title', isset($stationery) ? 'Tahrirlash: '.$stationery->name : 'Yangi mahsulot')
@section('page-title', isset($stationery) ? 'Tahrirlash' : 'Yangi mahsulot')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index') }}">
  <x-slot name="heading">{{ isset($stationery) ? $stationery->name : 'Yangi mahsulot' }}</x-slot>
  <x-slot name="meta">{{ isset($stationery) ? 'Mahsulotni tahrirlash · ID: #'.$stationery->id : 'Yangi kantselyariya mahsuloti' }}</x-slot>
</x-panel.page-header>


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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Tasdiqlash holati</label>
              <select name="is_approved" class="p-form-control @error('is_approved') is-invalid @enderror">
                <option value="0" {{ old('is_approved', $stationery->is_approved ?? 0)==0?'selected':'' }}>Kutilmoqda</option>
                <option value="1" {{ old('is_approved', $stationery->is_approved ?? 0)==1?'selected':'' }}>Tasdiqlangan</option>
                <option value="2" {{ old('is_approved', $stationery->is_approved ?? 0)==2?'selected':'' }}>Rad etilgan</option>
              </select>
              @error('is_approved')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="">
              <label class="p-form-label">Ko'rinish</label>
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Narx (UZS) <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="price" class="p-form-control @error('price') is-invalid @enderror"
                     value="{{ old('price', $stationery->price ?? '') }}" min="0" required>
              @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="">
              <label class="p-form-label">Chegirma narx (UZS)</label>
              <input type="number" name="discount_price" class="p-form-control @error('discount_price') is-invalid @enderror"
                     value="{{ old('discount_price', $stationery->discount_price ?? '') }}" min="0">
              @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="">
              <label class="p-form-label">Ombordagi miqdor <span style="color:var(--p-danger)">*</span></label>
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach([
              ['Nomi',        $stationery->name],
              ['Kategoriya',  $stationery->category?->name_uz],
              ['Material',    $stationery->material ?? '—'],
              ['Sotuvchi',    $stationery->seller?->shop_name ?? $stationery->seller_id],
            ] as [$k, $v])
            <div class="">
              <label class="p-form-label">{{ $k }}</label>
              <input type="text" class="p-form-control" value="{{ $v }}" disabled>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      <div class="flex gap-2 justify-end">
        <a href="{{ isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index') }}"
           class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
</div>
@endsection