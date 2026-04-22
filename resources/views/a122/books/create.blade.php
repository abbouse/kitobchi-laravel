@extends('a122.layouts.admin')
@section('title', 'Yangi kitob')
@section('page-title', 'Yangi kitob')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.books.index') }}">
    <x-slot name="heading">Yangi kitob qo‘shish</x-slot>
    <x-slot name="meta">Marketplace katalogiga yangi mahsulot joylashtirish</x-slot>
  </x-a122.page-header>

  <form method="POST" action="{{ route('admin.books.store') }}" class="space-y-6">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <section class="card p-5 xl:col-span-8">
        <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="{{ old('name') }}"></div>
          <div><label class="p-form-label">Muallif</label><input name="author" class="p-form-control" required value="{{ old('author') }}"></div>
          <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name_uz }}</option>@endforeach</select></div>
          <div><label class="p-form-label">Narx</label><input name="price" type="number" class="p-form-control" required value="{{ old('price') }}"></div>
          <div><label class="p-form-label">Ombor soni</label><input name="count" type="number" class="p-form-control" required value="{{ old('count', 0) }}"></div>
          <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7">{{ old('description') }}</textarea></div>
        </div>
      </section>
      <section class="card p-5 xl:col-span-4">
        <h3 class="text-lg font-black mb-4">Nashr parametrlari</h3>
        <div class="space-y-4">
          <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" checked> Faol holatda yaratilsin</label>
          <div class="kpi-soft">
            <div class="metric-label">Tavsiyalar</div>
            <div class="metric-meta mt-2">Mahsulot yaratilgach, `show` sahifada KPI va moderatsiya bloklari ko‘rinadi.</div>
          </div>
        </div>
      </section>
    </div>
    <div class="flex justify-end gap-2">
      <a href="{{ route('admin.books.index') }}" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-plus-circle"></i> Kitobni yaratish</button>
    </div>
  </form>
</div>
@endsection
