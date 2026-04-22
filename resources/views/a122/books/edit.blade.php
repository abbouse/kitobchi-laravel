@extends('a122.layouts.admin')
@section('title', 'Kitob tahriri')
@section('page-title', 'Kitob tahriri')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.books.show', $book) }}">
    <x-slot name="heading">{{ $book->name }}</x-slot>
    <x-slot name="meta">Katalog kartasi, narx va moderatsiya sozlamalari</x-slot>
  </x-a122.page-header>

  <form method="POST" action="{{ route('admin.books.update', $book) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <section class="card p-5 xl:col-span-8">
        <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="p-form-label">Nomi</label>
            <input name="name" class="p-form-control" required value="{{ old('name', $book->name) }}">
          </div>
          <div>
            <label class="p-form-label">Muallif</label>
            <input name="author" class="p-form-control" required value="{{ old('author', $book->author) }}">
          </div>
          <div>
            <label class="p-form-label">Kategoriya</label>
            <select name="category_id" class="p-form-control" required>
              @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $book->category_id) == $c->id)>{{ $c->name_uz }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="p-form-label">Status</label>
            <select name="is_approved" class="p-form-control">
              <option value="0" @selected(old('is_approved', $book->is_approved) == 0)>Moderatsiyada</option>
              <option value="1" @selected(old('is_approved', $book->is_approved) == 1)>Tasdiqlangan</option>
              <option value="2" @selected(old('is_approved', $book->is_approved) == 2)>Rad etilgan</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="p-form-label">Tavsif</label>
            <textarea name="description" class="p-form-control" rows="7">{{ old('description', $book->description) }}</textarea>
          </div>
        </div>
      </section>

      <section class="card p-5 xl:col-span-4">
        <h3 class="text-lg font-black mb-4">Savdo sozlamalari</h3>
        <div class="space-y-4">
          <div>
            <label class="p-form-label">Narx</label>
            <input name="price" type="number" class="p-form-control" required value="{{ old('price', $book->price) }}">
          </div>
          <div>
            <label class="p-form-label">Ombor soni</label>
            <input name="count" type="number" class="p-form-control" required value="{{ old('count', $book->count) }}">
          </div>
          <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]">
            <input type="checkbox" name="status" value="1" class="rounded" @checked(old('status', $book->status))>
            Mahsulot faol bo‘lsin
          </label>
        </div>
      </section>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="{{ route('admin.books.show', $book) }}" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-check2-circle"></i> O‘zgarishlarni saqlash</button>
    </div>
  </form>
</div>
@endsection
