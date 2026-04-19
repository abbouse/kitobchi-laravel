@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$book->name)
@section('page-title', 'Kitobni tahrirlash')
@section('breadcrumb', 'Panel / Kitoblar / Tahrirlash')

@section('content')

<x-panel.page-header back-href="{{ route('panel.books.show', $book) }}">
  <x-slot name="heading">{{ $book->name }}</x-slot>
  <x-slot name="meta">ID: #{{ $book->id }} · {{ $book->author }}</x-slot>
</x-panel.page-header>


{{-- Info: faqat moderatsiya maydoni tahrirlanadi --}}
<div style="padding:12px 16px;border-radius:10px;background:var(--p-info-d,rgba(56,189,248,0.10));border:1px solid rgba(56,189,248,0.2);font-size:13px;color:var(--p-info,#38bdf8);margin-bottom:20px;display:flex;align-items:center;gap:10px">
  <i class="bi bi-info-circle-fill"></i>
  Kitob ma'lumotlari sotuvchi tomonidan kiritilgan. Faqat moderatsiya, holat va narq tahrirlash mumkin.
</div>

<form method="POST" action="{{ route('panel.books.update', $book) }}">
  @csrf @method('PUT')

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

    {{-- ── Chap: Ko'rish ──────────────────────────── --}}
    <div class="xl:col-span-7 fade-up">
      <div class="p-card h-100">
        <div class="p-card-title mb-3">Kitob ma'lumotlari (faqat ko'rish)</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Kitob nomi</label>
            <input type="text" class="p-form-control" value="{{ $book->name }}" disabled>
          </div>
          <div class="">
            <label class="p-form-label">Muallif</label>
            <input type="text" class="p-form-control" value="{{ $book->author }}" disabled>
          </div>
          <div class="">
            <label class="p-form-label">Kategoriya</label>
            <input type="text" class="p-form-control" value="{{ $book->category?->name_uz ?? '—' }}" disabled>
          </div>
          <div class="">
            <label class="p-form-label">Sotuvchi</label>
            <input type="text" class="p-form-control" value="{{ $book->seller?->shop_name ?? '—' }}" disabled>
          </div>
          <div class="md:col-span-4">
            <label class="p-form-label">Til</label>
            <input type="text" class="p-form-control" value="{{ $book->lang }}" disabled>
          </div>
          <div class="md:col-span-4">
            <label class="p-form-label">Muqova</label>
            <input type="text" class="p-form-control" value="{{ $book->coverType }}" disabled>
          </div>
          <div class="md:col-span-4">
            <label class="p-form-label">Sahifalar</label>
            <input type="text" class="p-form-control" value="{{ $book->pages }} bet" disabled>
          </div>
        </div>
      </div>
    </div>

    {{-- ── O'ng: Tahrirlash ──────────────────────── --}}
    <div class="xl:col-span-5 fade-up">

      {{-- Narxlar --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Narxlar</div>
        <div class="mb-3">
          <label class="p-form-label">Asosiy narx (UZS)</label>
          <input type="number" name="price" class="p-form-control"
                 value="{{ old('price', $book->price) }}" min="0" step="100">
        </div>
        <div class="mb-3">
          <label class="p-form-label">Chegirma narxi (UZS)</label>
          <input type="number" name="discountPrice" class="p-form-control"
                 value="{{ old('discountPrice', $book->discountPrice) }}" min="0" step="100">
        </div>
        <div>
          <label class="p-form-label">Zaxira (dona)</label>
          <input type="number" name="count" class="p-form-control"
                 value="{{ old('count', $book->count) }}" min="0">
        </div>
      </div>

      {{-- Moderatsiya --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Moderatsiya</div>
        <div class="mb-3">
          <label class="p-form-label">Moderatsiya holati</label>
          <select name="is_approved" class="p-form-control">
            <option value="0" {{ old('is_approved',$book->is_approved)==='0'?'selected':'' }}>⟳ Kutilmoqda</option>
            <option value="1" {{ old('is_approved',$book->is_approved)==='1'?'selected':'' }}>✓ Tasdiqlangan</option>
            <option value="2" {{ old('is_approved',$book->is_approved)==='2'?'selected':'' }}>✗ Rad etilgan</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="p-form-label">Marketplace ko'rinishi</label>
          <select name="status" class="p-form-control">
            <option value="1" {{ old('status',$book->status)?'selected':'' }}>Ko'rinadi</option>
            <option value="0" {{ !old('status',$book->status)?'selected':'' }}>Ko'rinmaydi</option>
          </select>
        </div>
        <div class="flex items-center justify-between"
             style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Yashirin</div>
            <div style="font-size:11px;color:var(--p-hint)">Hech qayerda ko'rinmaydi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_hidden" value="1"
                   {{ old('is_hidden',$book->is_hidden) ? 'checked':'' }}>
          </div>
        </div>
      </div>
    </div>

    {{-- Submit --}}
    <div class=" fade-up d3">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
        <a href="{{ route('panel.books.show',$book) }}" class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>

  </div>
</form>

@endsection