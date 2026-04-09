@extends('panel.layouts.panel')
@section('title', isset($stationeryCategory) ? 'Tahrirlash: '.$stationeryCategory->name_uz : 'Yangi kategoriya')
@section('page-title', isset($stationeryCategory) ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">
    <form method="POST"
      action="{{ isset($stationeryCategory) ? route('panel.stationery-categories.update',$stationeryCategory) : route('panel.stationery-categories.store') }}">
      @csrf
      @if(isset($stationeryCategory)) @method('PUT') @endif

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Kategoriya ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            <div class="col-12">
              <label class="p-label">Icon (emoji)</label>
              <input type="text" name="icon" class="p-form-control" maxlength="2"
                     value="{{ old('icon', $stationeryCategory->icon ?? '') }}"
                     style="font-size:24px;width:80px;text-align:center">
            </div>

            @foreach([
              ['name_uz','O\'zbekcha','UZ'],
              ['name_ru','Ruscha','RU'],
              ['name_en','Inglizcha','EN'],
              ['name_ja','Yaponcha','JA'],
            ] as [$field,$label,$lang])
            <div class="col-sm-6">
              <label class="p-label">
                {{ $label }} <span class="s-pill muted" style="font-size:10px">{{ $lang }}</span>
                <span style="color:var(--p-danger)">*</span>
              </label>
              <input type="text" name="{{ $field }}"
                     class="p-form-control @error($field) is-invalid @enderror"
                     value="{{ old($field, $stationeryCategory->$field ?? '') }}" required>
              @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @endforeach

            <div class="col-sm-6">
              <label class="p-label">Slug</label>
              <input type="text" name="slug" class="p-form-control"
                     value="{{ old('slug', $stationeryCategory->slug ?? '') }}"
                     placeholder="avtomatik-yaratiladi">
            </div>

            <div class="col-sm-6">
              <label class="p-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $stationeryCategory->is_active ?? true) ? 'checked' : '' }}
                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      {{-- Teglar --}}
      @if($tags->count())
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Teglar</div>
          <div class="dash-card-sub">Kategoriyaga tegishli teglar</div>
        </div>
        <div class="dash-card-body">
          <div class="d-flex flex-wrap gap-2">
            @foreach($tags as $tag)
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;
                          background:var(--p-elevated);border:1px solid var(--p-border);
                          border-radius:20px;padding:5px 12px;transition:all .15s">
              <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                     style="accent-color:var(--p-accent)"
                     {{ in_array($tag->id, old('tags', $stationeryCategory?->tags?->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
              <span style="font-size:12px;color:var(--p-text)">{{ $tag->name_uz }}</span>
            </label>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('panel.stationery-categories.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
@endsection