@extends('panel.layouts.panel')
@section('title', isset($bookCategory) ? 'Tahrirlash: '.$bookCategory->name_uz : "Yangi kategoriya")
@section('page-title', isset($bookCategory) ? 'Kategoriyani tahrirlash' : "Yangi kategoriya")

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ route('panel.book-categories.index') }}">
  <x-slot name="heading">{{ isset($bookCategory) ? $bookCategory->name_uz : 'Yangi kategoriya' }}</x-slot>
  <x-slot name="meta">{{ isset($bookCategory) ? 'Kategoriyani tahrirlash' : 'Yangi kitob kategoriyasi' }}</x-slot>
</x-panel.page-header>


    <form method="POST"
      action="{{ isset($bookCategory) ? route('panel.book-categories.update',$bookCategory) : route('panel.book-categories.store') }}">
      @csrf
      @if(isset($bookCategory)) @method('PUT') @endif

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Asosiy ma'lumotlar</div></div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            {{-- Icon emoji --}}
            <div class="">
              <label class="p-form-label">Icon (emoji, 1 ta belgi)</label>
              <input type="text" name="icon" class="p-form-control" maxlength="2"
                     value="{{ old('icon', $bookCategory->icon ?? '') }}"
                     style="font-size:24px;width:80px;text-align:center">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Masalan: 📚 🎓 🔬</div>
            </div>

            {{-- Nomlar --}}
            @foreach([
              ['name_uz','O\'zbekcha nomi','UZ'],
              ['name_ru','Ruscha nomi','RU'],
              ['name_en','Inglizcha nomi','EN'],
              ['name_ja','Yaponcha nomi','JA'],
            ] as [$field, $label, $lang])
            <div class="">
              <label class="p-form-label">
                {{ $label }} <span class="s-pill muted" style="font-size:10px">{{ $lang }}</span>
                <span style="color:var(--p-danger)">*</span>
              </label>
              <input type="text" name="{{ $field }}" class="p-form-control @error($field) is-invalid @enderror"
                     value="{{ old($field, $bookCategory->$field ?? '') }}" required>
              @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @endforeach

            {{-- Status --}}
            <div class="">
              <label class="p-form-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $bookCategory->is_active ?? true) ? 'checked' : '' }}
                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv ko'rsatilsin</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      {{-- Teglar --}}
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Teglar</div>
          <div class="dash-card-sub">Kategoriyaga tegishli teglar</div>
        </div>
        <div class="dash-card-body">
          <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;
                          background:var(--p-elevated);border:1px solid var(--p-border);
                          border-radius:20px;padding:5px 12px;transition:all .15s"
                   class="tag-pill">
              <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                     style="accent-color:var(--p-accent)"
                     {{ in_array($tag->id, old('tags', $bookCategory?->tags?->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
              <span style="font-size:12px;color:var(--p-text)">{{ $tag->tag_name_uz }}</span>
            </label>
            @endforeach
          </div>
        </div>
      </div>

      <div class="flex gap-2 justify-end">
        <a href="{{ route('panel.book-categories.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
</div>
@endsection