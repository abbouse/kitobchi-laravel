@php
  $item = $item ?? null;
  $value = fn ($key, $default = '') => old($key, data_get($item, $key, $default));
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
  @csrf
  @if(($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card p-5 xl:col-span-8">
      <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="{{ $value('name') }}"></div>
        <div><label class="p-form-label">Material</label><input name="material" class="p-form-control" value="{{ $value('material') }}"></div>
        <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($value('category_id') == $category->id)>{{ $category->name_uz }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Status</label><select name="is_approved" class="p-form-control"><option value="0" @selected($value('is_approved', 1) == 0)>Moderatsiyada</option><option value="1" @selected($value('is_approved', 1) == 1)>Tasdiqlangan</option><option value="2" @selected($value('is_approved', 1) == 2)>Rad etilgan</option></select></div>
        <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7">{{ $value('description') }}</textarea></div>
      </div>
    </section>

    <section class="card p-5 xl:col-span-4">
      <h3 class="text-lg font-black mb-4">Savdo parametrlari</h3>
      <div class="space-y-4">
        <div><label class="p-form-label">Narx</label><input name="price" type="number" class="p-form-control" required value="{{ $value('price') }}"></div>
        <div><label class="p-form-label">Chegirma narxi</label><input name="discount_price" type="number" class="p-form-control" value="{{ $value('discount_price') }}"></div>
        <div><label class="p-form-label">Ombor</label><input name="stock" type="number" class="p-form-control" required value="{{ $value('stock', 0) }}"></div>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" @checked($value('status', true))> Mahsulot faol</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="recommended" value="1" class="rounded" @checked($value('recommended', false))> Tavsiya etilgan</label>
      </div>
    </section>
  </div>

  <div class="flex justify-end gap-2">
    <a href="{{ route('admin.stationery.index') }}" class="btn-p ghost">Bekor qilish</a>
    <button class="btn-p primary"><i class="bi bi-check2-circle"></i> Saqlash</button>
  </div>
</form>
