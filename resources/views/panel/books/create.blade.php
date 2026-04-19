@extends('panel.layouts.panel')
@section('title', 'Yangi kitob')
@section('page-title', 'Yangi kitob qo\'shish')
@section('breadcrumb', 'Panel / Kitoblar / Yangi')

@section('content')

<x-panel.page-header back-href="{{ route('panel.books.index') }}">
  <x-slot name="heading">Yangi kitob qo'shish</x-slot>
  <x-slot name="meta">Kitobxona katalogiga yangi kitobi qo'shing</x-slot>
</x-panel.page-header>


<form method="POST" action="{{ route('panel.books.store') }}" enctype="multipart/form-data">
  @csrf

  <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-12 xl:gap-5">

    {{-- Asosiy ma'lumotlar --}}
    <div class="min-w-0 fade-up xl:col-span-7 2xl:col-span-8">
      <div class="p-card">
        <div class="p-card-title mb-4">Asosiy ma'lumotlar</div>
        
        <div class="mb-3">
          <label class="p-form-label">Kitob nomi <span style="color:var(--p-danger)">*</span></label>
          <input type="text" name="name" class="p-form-control @error('name') is-invalid @enderror"
                 value="{{ old('name') }}" required maxlength="255" placeholder="Kitobning to'liq nomini kiriting">
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div class="">
            <label class="p-form-label">Muallif <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="author" class="p-form-control @error('author') is-invalid @enderror"
                   value="{{ old('author') }}" required maxlength="100" placeholder="Muallif ismi">
            @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="">
            <label class="p-form-label">Kategoriya <span style="color:var(--p-danger)">*</span></label>
            <select name="category_id" class="p-form-control @error('category_id') is-invalid @enderror" required>
              <option value="">— Kategoriyani tanlang —</option>
              @foreach($categories ?? [] as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id?'selected':'' }}>
                  {{ $cat->name_uz }}
                </option>
              @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mb-3">
          <label class="p-form-label">Tafsili</label>
          <textarea name="description" class="p-form-control @error('description') is-invalid @enderror"
                    maxlength="1000" rows="4" placeholder="Kitob haqida qisqacha ma'lumot...">{{ old('description') }}</textarea>
          @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Til</label>
            <select name="lang" class="p-form-control @error('lang') is-invalid @enderror">
              <option value="uz" {{ old('lang','uz')==='uz'?'selected':'' }}>O'zbek</option>
              <option value="ru" {{ old('lang')==='ru'?'selected':'' }}>Rus</option>
              <option value="en" {{ old('lang')==='en'?'selected':'' }}>Ingliz</option>
              <option value="other" {{ old('lang')==='other'?'selected':'' }}>Boshqa</option>
            </select>
            @error('lang')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="">
            <label class="p-form-label">Muqova turi</label>
            <select name="coverType" class="p-form-control @error('coverType') is-invalid @enderror">
              <option value="hardcover" {{ old('coverType','hardcover')==='hardcover'?'selected':'' }}>Qattiq muqova</option>
              <option value="softcover" {{ old('coverType')==='softcover'?'selected':'' }}>Yumshoq muqova</option>
              <option value="paperback" {{ old('coverType')==='paperback'?'selected':'' }}>Qog'oz muqova</option>
            </select>
            @error('coverType')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">Sahifalar soni</label>
            <input type="number" name="pages" class="p-form-control @error('pages') is-invalid @enderror"
                   value="{{ old('pages') }}" min="0" placeholder="0">
            @error('pages')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">ISBN</label>
            <input type="text" name="isbn" class="p-form-control @error('isbn') is-invalid @enderror"
                   value="{{ old('isbn') }}" maxlength="20" placeholder="978-0-xxx-xxxxx-x">
            @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">Chop etilgan yil</label>
            <input type="number" name="publishYear" class="p-form-control @error('publishYear') is-invalid @enderror"
                   value="{{ old('publishYear') }}" min="1900" max="{{ date('Y')+1 }}" placeholder="{{ date('Y') }}">
            @error('publishYear')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>

    {{-- O'ng: Narx va QR --}}
    <div class="min-w-0 fade-up xl:col-span-5 2xl:col-span-4">
      
      {{-- Narxlar --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Narxlar (UZS)</div>
        
        <div class="mb-3">
          <label class="p-form-label">Asosiy narx <span style="color:var(--p-danger)">*</span></label>
          <input type="number" name="price" class="p-form-control @error('price') is-invalid @enderror"
                 value="{{ old('price') }}" min="0" step="100" required placeholder="0">
          @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="p-form-label">Chegirma narxi</label>
          <input type="number" name="discountPrice" class="p-form-control @error('discountPrice') is-invalid @enderror"
                 value="{{ old('discountPrice') }}" min="0" step="100" placeholder="0">
          @error('discountPrice')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="p-form-label">Zaxira (dona) <span style="color:var(--p-danger)">*</span></label>
          <input type="number" name="count" class="p-form-control @error('count') is-invalid @enderror"
                 value="{{ old('count', 0) }}" min="0" required placeholder="0">
          @error('count')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Rasm --}}
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Muqova rasmi</div>
        
        <div style="border:2px dashed var(--p-border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:all .2s"
             id="imageDropZone" class="image-drop-zone">
          <input type="file" name="images" id="imageInput" accept="image/*" style="display:none" class="@error('images') is-invalid @enderror">
          
          <div style="font-size:32px;color:var(--p-muted);margin-bottom:8px">
            <i class="bi bi-cloud-arrow-up"></i>
          </div>
          <div style="font-size:14px;font-weight:600;color:var(--p-text);margin-bottom:4px">
            Rasmni yuklang
          </div>
          <div style="font-size:12px;color:var(--p-hint)">
            yoki qo'shish uchun ustiga bosing
          </div>
        </div>

        <div id="imagePreview" style="margin-top:12px;display:none">
          <img id="previewImg" style="max-width:100%;border-radius:8px;max-height:300px">
        </div>

        @error('images')<div class="invalid-feedback" style="color:var(--p-danger)">{{ $message }}</div>@enderror
      </div>

      {{-- Status --}}
      <div class="p-card">
        <div class="p-card-title mb-3">Holat</div>
        
        <div class="mb-3">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" name="status" value="1"
                   {{ old('status') ? 'checked' : '' }}
                   style="width:18px;height:18px;accent-color:var(--p-accent)">
            <span style="font-size:13px;color:var(--p-text)">Marketplace da ko'rsatish</span>
          </label>
        </div>

        <div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="hidden" name="is_hidden" value="0">
            <input type="checkbox" name="is_hidden" value="1"
                   {{ old('is_hidden') ? 'checked' : '' }}
                   style="width:18px;height:18px;accent-color:var(--p-danger)">
            <span style="font-size:13px;color:var(--p-text)">Yashirinli</span>
          </label>
        </div>
      </div>
    </div>
  </div>

  {{-- Tugmalar --}}
  <div class="flex gap-2 justify-end fade-up d3">
    <a href="{{ route('panel.books.index') }}" class="btn-p ghost">Bekor qilish</a>
    <button type="submit" class="btn-p primary">
      <i class="bi bi-plus-circle"></i> Yangi kitob yaratish
    </button>
  </div>
</form>

@push('styles')
<style>
.image-drop-zone {
  transition: all 0.3s ease;
}
.image-drop-zone:hover {
  background: var(--p-elevated);
  border-color: var(--p-accent);
}
.image-drop-zone.dragover {
  background: var(--p-accent-d);
  border-color: var(--p-accent);
}
</style>
@endpush

@push('scripts')
<script>
const dropZone = document.getElementById('imageDropZone');
const imageInput = document.getElementById('imageInput');
const previewImg = document.getElementById('previewImg');
const previewDiv = document.getElementById('imagePreview');

// Rasm dragging
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  const files = e.dataTransfer.files;
  if (files.length) {
    imageInput.files = files;
    handleImageSelect();
  }
});

dropZone.addEventListener('click', () => imageInput.click());

imageInput.addEventListener('change', handleImageSelect);

function handleImageSelect() {
  const file = imageInput.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      previewDiv.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}
</script>
@endpush

@endsection
