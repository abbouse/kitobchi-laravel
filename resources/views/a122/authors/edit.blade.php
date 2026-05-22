@extends('a122.layouts.admin')
@php $isEdit = isset($author); @endphp
@section('title', $isEdit ? 'Muallifni tahrirlash' : 'Yangi muallif')
@section('page-title', $isEdit ? 'Muallifni tahrirlash' : 'Yangi muallif')

@section('content')
<div class="max-w-3xl">
  <div class="card p-6">
    <form method="POST" action="{{ $isEdit ? route('admin.authors.update', $author) : route('admin.authors.store') }}" enctype="multipart/form-data">
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
            @if(($author->image_url ?? null))
              <img src="{{ $author->image_url }}" alt="{{ $author->name }}" class="w-full h-full object-cover">
            @else
              <div class="text-center text-[var(--p-hint)]">
                <i class="bi bi-pen text-4xl"></i>
                <div class="mt-2 text-sm">Rasm yo‘q</div>
              </div>
            @endif
          </div>
          @if($isEdit && $author->image)
            <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]">
              <input type="checkbox" name="remove_image" value="1" class="rounded">
              Rasmni olib tashlash
            </label>
          @endif
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Muallif nomi <span class="text-rose-500">*</span></label>
            <input name="name" value="{{ old('name', $author->name ?? '') }}" class="input" required>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Rasm yuklash</label>
            <input type="file" name="image_file" accept="image/*" class="input">
            <div class="mt-2 text-xs text-gray-500">Portret yoki square cover ko‘rinishidagi rasm yaxshi ishlaydi.</div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Yoki tashqi rasm URL / saqlangan path</label>
            <input name="image" value="{{ old('image', $author->image ?? '') }}" class="input" placeholder="https://... yoki authors/...">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Tashqi ID</label>
              <input name="external_id" value="{{ old('external_id', $author->external_id ?? '') }}" class="input">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Slug</label>
              <input name="slug" value="{{ old('slug', $author->slug ?? '') }}" class="input">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Source URL</label>
            <input name="source_url" value="{{ old('source_url', $author->source_url ?? '') }}" class="input" placeholder="https://book.uz/authors?...">
          </div>
          @if($isEdit)
            <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] px-4 py-3 text-sm text-[var(--p-muted)]">
              Bu muallifga bog‘langan kitoblar: <strong class="text-[var(--p-text)]">{{ $author->books()->count() }}</strong>
            </div>
          @endif
        </div>
      </div>

      <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="{{ $isEdit ? 'save' : 'plus' }}" class="w-4 h-4"></i>
          {{ $isEdit ? 'Saqlash' : "Qo'shish" }}
        </button>
        <a href="{{ route('admin.authors.index') }}" class="btn btn-secondary">Bekor qilish</a>
      </div>
    </form>
  </div>
</div>
@endsection
