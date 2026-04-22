@extends('a122.layouts.admin')
@php $isEdit = isset($stationeryCategory); @endphp
@section('title', $isEdit ? 'Kategoriya tahrirlash' : 'Yangi kategoriya')
@section('page-title', $isEdit ? 'Kategoriya tahrirlash' : 'Yangi kanstovar kategoriyasi')

@section('content')
<div class="max-w-2xl">
  <div class="card p-6">
    <form method="POST" action="{{ $isEdit ? route('admin.stationery-categories.update', $stationeryCategory) : route('admin.stationery-categories.store') }}">
      @csrf
      @if($isEdit) @method('PUT') @endif

      @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 text-sm">
          <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (O'zbekcha) <span class="text-rose-500">*</span></label>
          <input name="name_uz" value="{{ old('name_uz', $stationeryCategory->name_uz ?? '') }}" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Ruscha) <span class="text-rose-500">*</span></label>
          <input name="name_ru" value="{{ old('name_ru', $stationeryCategory->name_ru ?? '') }}" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Inglizcha)</label>
          <input name="name_en" value="{{ old('name_en', $stationeryCategory->name_en ?? '') }}" class="input">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Yaponcha)</label>
          <input name="name_ja" value="{{ old('name_ja', $stationeryCategory->name_ja ?? '') }}" class="input">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Icon (emoji)</label>
          <input name="icon" value="{{ old('icon', $stationeryCategory->icon ?? '') }}" class="input" maxlength="10" placeholder="✏️">
        </div>
        <div class="flex items-center gap-3 pt-6">
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
              {{ old('is_active', $stationeryCategory->is_active ?? true) ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-emerald-500 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            <span class="ml-3 text-sm font-medium">Faol</span>
          </label>
        </div>
      </div>

      <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="{{ $isEdit ? 'save' : 'plus' }}" class="w-4 h-4"></i>
          {{ $isEdit ? 'Saqlash' : "Qo'shish" }}
        </button>
        <a href="{{ route('admin.stationery-categories.index') }}" class="btn btn-secondary">Bekor qilish</a>
      </div>
    </form>
  </div>
</div>
@endsection
