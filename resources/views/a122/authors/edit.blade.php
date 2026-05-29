@extends('a122.layouts.admin')
@php $isEdit = isset($author); @endphp
@section('title', $isEdit ? 'Muallifni tahrirlash' : 'Yangi muallif')
@section('page-title', $isEdit ? 'Muallifni tahrirlash' : 'Yangi muallif')
@section('page-eyebrow', 'Author profile')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Author profile"
    title="{{ $isEdit ? 'Muallifni tahrirlash' : 'Yangi muallif' }}"
    subtitle="Muallif kartasi, preview rasmi va source metadata shu form orqali boshqariladi.">
    <a href="{{ route('admin.authors.index') }}" class="btn-p ghost">
      <i class="bi bi-arrow-left"></i>
      <span>Mualliflarga qaytish</span>
    </a>
  </x-admin.page-header>

  @if($errors->any())
    <div class="alert alert-danger kc-flash mb-0">
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success kc-flash mb-0">{{ session('success') }}</div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <x-admin.section-card title="Preview" meta="Real rasm bo‘lmasa default author avatar avtomatik ko‘rsatiladi.">
        <div class="d-flex flex-column gap-3">
          <div class="rounded-5 overflow-hidden border bg-light" style="aspect-ratio:1/1;max-width:300px;">
            <img src="{{ $author->display_image_url ?? (new \App\Models\Author())->display_image_url }}" alt="{{ $author->name ?? 'Muallif' }}" class="w-100 h-100 object-fit-cover">
          </div>

          @if($isEdit)
            <div class="d-flex flex-wrap gap-2">
              @if($author->image_url)
                <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Custom image</span>
              @else
                <span class="badge rounded-pill text-bg-light border">Default avatar</span>
              @endif

              @if($author->has_multiple_authors)
                <span class="badge rounded-pill text-bg-secondary">Ko‘p muallif</span>
              @elseif($author->needs_ai_portrait)
                <span class="badge rounded-pill text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">AI prompt mumkin</span>
              @endif
            </div>
          @endif

          @if($isEdit && ($author->image ?? null))
            <label class="d-flex align-items-center gap-2 small text-secondary">
              <input type="checkbox" name="remove_image" value="1" class="form-check-input mt-0" form="author-form">
              Mavjud rasmni olib tashlash
            </label>
          @endif

          @if($isEdit)
            <div class="small text-secondary">
              Bu muallifga bog‘langan kitoblar: <strong class="text-dark">{{ number_format($author->books_count ?? 0) }}</strong>
            </div>
          @endif
        </div>
      </x-admin.section-card>

      @if($isEdit && $author->needs_ai_portrait)
        <x-admin.section-card title="AI rasm oqimi" meta="Single-author kartalar uchun ChatGPT image prompt tayyorlab olish mumkin.">
          <div class="d-flex flex-column gap-3">
            <form method="POST" action="{{ route('admin.authors.generate-image-prompt', $author) }}">
              @csrf
              <button type="submit" class="btn-p primary w-100 justify-content-center">
                <i class="bi bi-stars"></i>
                <span>ChatGPT prompt yaratish</span>
              </button>
            </form>

            @if(session('author_ai_prompt'))
              <div>
                <label class="form-label small text-uppercase fw-semibold text-secondary">Tayyor prompt</label>
                <textarea id="author-ai-prompt" class="form-control rounded-4 border-0 shadow-sm" rows="7" readonly>{{ session('author_ai_prompt') }}</textarea>
                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="btn-p ghost sm" onclick="navigator.clipboard.writeText(document.getElementById('author-ai-prompt').value)">
                    <i class="bi bi-copy"></i>
                    <span>Copy</span>
                  </button>
                </div>
              </div>
            @else
              <div class="small text-secondary">Prompt yaratilgach shu yerda copy qilishga tayyor holatda chiqadi.</div>
            @endif
          </div>
        </x-admin.section-card>
      @elseif($isEdit && $author->has_multiple_authors)
        <x-admin.section-card title="AI rasm kerak emas" meta="Nom ichida bir nechta muallif borligi aniqlandi, shu sabab default avatar qoldiriladi.">
          <div class="small text-secondary">Ko‘p muallifli kartalarda real portret majburiy emas. Kerak bo‘lsa keyin qo‘lda cover yoki umumiy avatar yuklashingiz mumkin.</div>
        </x-admin.section-card>
      @endif
    </div>

    <div class="col-12 col-xl-8">
      <x-admin.section-card title="Muallif ma'lumotlari" meta="Asosiy ism, source metadata va rasm manbasi shu form orqali yangilanadi.">
        <form id="author-form" method="POST" action="{{ $isEdit ? route('admin.authors.update', $author) : route('admin.authors.store') }}" enctype="multipart/form-data" class="row g-4">
          @csrf
          @if($isEdit)
            @method('PUT')
          @endif

          <div class="col-12">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Muallif nomi</label>
            <input name="name" value="{{ old('name', $author->name ?? '') }}" class="form-control rounded-4 border-0 shadow-sm" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Rasm yuklash</label>
            <input type="file" name="image_file" accept="image/*" class="form-control rounded-4 border-0 shadow-sm">
            <div class="small text-secondary mt-2">Square yoki shoulders-up portret yaxshi ishlaydi.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Tashqi rasm URL yoki path</label>
            <input name="image" value="{{ old('image', $author->image ?? '') }}" class="form-control rounded-4 border-0 shadow-sm" placeholder="https://... yoki authors/...">
            <div class="small text-secondary mt-2">URL yoki `storage/authors/...` path ishlatishingiz mumkin.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Tashqi ID</label>
            <input name="external_id" value="{{ old('external_id', $author->external_id ?? '') }}" class="form-control rounded-4 border-0 shadow-sm">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Slug</label>
            <input name="slug" value="{{ old('slug', $author->slug ?? '') }}" class="form-control rounded-4 border-0 shadow-sm">
          </div>

          <div class="col-12">
            <label class="form-label small text-uppercase fw-semibold text-secondary">Source URL</label>
            <input name="source_url" value="{{ old('source_url', $author->source_url ?? '') }}" class="form-control rounded-4 border-0 shadow-sm" placeholder="https://book.uz/authors?...">
          </div>

          <div class="col-12 d-flex flex-wrap justify-content-end gap-2 pt-2">
            <a href="{{ route('admin.authors.index') }}" class="btn-p ghost">Bekor qilish</a>
            <button type="submit" class="btn-p primary">
              <i class="bi {{ $isEdit ? 'bi-floppy' : 'bi-plus-lg' }}"></i>
              <span>{{ $isEdit ? 'Saqlash' : "Qo'shish" }}</span>
            </button>
          </div>
        </form>
      </x-admin.section-card>
    </div>
  </div>
</div>
@endsection
