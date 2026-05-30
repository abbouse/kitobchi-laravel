@extends('a122.layouts.admin')
@php $isEdit = isset($publisher); @endphp
@section('title', $isEdit ? 'Nashriyotni tahrirlash' : 'Yangi nashriyot')
@section('page-title', $isEdit ? 'Nashriyotni tahrirlash' : 'Yangi nashriyot')

@section('content')
<div class="max-w-3xl">
  <div class="card-panel p-6">
    <form method="POST" action="{{ $isEdit ? route('admin.publishers.update', $publisher) : route('admin.publishers.store') }}" enctype="multipart/form-data">
      @csrf
      @if($isEdit) @method('PUT') @endif

      @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 text-sm">
          <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-5">
        <div class="space-y-3">
          <div class="w-full aspect-square rounded-[1.5rem] overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] flex items-center justify-center">
            @if(($publisher->image_url ?? null))
              <img src="{{ $publisher->image_url }}" alt="{{ $publisher->name }}" class="w-full h-full object-cover">
            @else
              <div class="text-center text-[var(--p-hint)]">
                <i class="bi bi-building text-4xl"></i>
                <div class="mt-2 text-sm">Rasm yo‘q</div>
              </div>
            @endif
          </div>
          @if($isEdit && $publisher->image)
            <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]">
              <input type="checkbox" name="remove_image" value="1" class="rounded">
              Rasmni olib tashlash
            </label>
          @endif
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Nashriyot nomi <span class="text-rose-500">*</span></label>
            <input name="name" value="{{ old('name', $publisher->name ?? '') }}" class="input" required>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Rasm</label>
            <input type="file" name="image" accept="image/*" class="input">
            <div class="mt-2 text-xs text-gray-500">Logo yoki cover ko‘rinishidagi bitta rasm yetadi.</div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="{{ $isEdit ? 'save' : 'plus' }}" class="w-4 h-4"></i>
          {{ $isEdit ? 'Saqlash' : "Qo'shish" }}
        </button>
        <a href="{{ route('admin.publishers.index') }}" class="btn btn-secondary">Bekor qilish</a>
      </div>
    </form>
  </div>
</div>
@endsection
