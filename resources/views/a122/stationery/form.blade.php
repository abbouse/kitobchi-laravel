@php
  $item = $item ?? null;
  $value = fn ($key, $default = '') => old($key, data_get($item, $key, $default));
  $images = old('images_text', implode("\n", is_array(data_get($item, 'images')) ? data_get($item, 'images') : []));
  $variants = old('variant_color_name')
      ? collect(old('variant_color_name'))->map(function ($name, $index) {
          return [
              'id' => old('variant_id.' . $index),
              'color_name' => $name,
              'stock' => old('variant_stock.' . $index),
              'image_path' => old('variant_image_existing.' . $index),
          ];
      })->all()
      : (($item?->variants ?? collect())->map(fn ($variant) => [
          'id' => $variant->id,
          'color_name' => $variant->color_name,
          'stock' => $variant->stock,
          'image_path' => $variant->image_path,
      ])->all());
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
  @csrf
  @if(($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  @if($errors->any())
    <div class="p-alert warning">
      <i class="bi bi-exclamation-triangle"></i>
      <div>
        <div class="font-semibold mb-1">Formada xatolar bor.</div>
        <ul class="list-disc pl-5 space-y-0.5">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card-panel p-5 xl:col-span-8">
      <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="{{ $value('name') }}"></div>
        <div><label class="p-form-label">Sotuvchi</label><select name="seller_id" class="p-form-control"><option value="">Ichki katalog</option>@foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected((string)$value('seller_id') === (string)$seller->id)>{{ $seller->shop_name }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Shtrix-kod</label><input name="barcode" class="p-form-control" value="{{ $value('barcode') }}"></div>
        <div><label class="p-form-label">Material</label><input name="material" class="p-form-control" value="{{ $value('material') }}"></div>
        <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($value('category_id') == $category->id)>{{ $category->name_uz }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Status</label><select name="is_approved" class="p-form-control"><option value="0" @selected($value('is_approved', 1) == 0)>Moderatsiyada</option><option value="1" @selected($value('is_approved', 1) == 1)>Tasdiqlangan</option><option value="2" @selected($value('is_approved', 1) == 2)>Rad etilgan</option></select></div>
        <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7">{{ $value('description') }}</textarea></div>
      </div>
    </section>

    <section class="card-panel p-5 xl:col-span-4">
      <h3 class="text-lg font-black mb-4">Savdo parametrlari</h3>
      <div class="space-y-4">
        <div><label class="p-form-label">Narx</label><input name="price" type="number" class="p-form-control" required value="{{ $value('price') }}"></div>
        <div><label class="p-form-label">Chegirma narxi</label><input name="discount_price" type="number" class="p-form-control" value="{{ $value('discount_price') }}"></div>
        <div><label class="p-form-label">Chegirma tugash vaqti</label><input name="discountExpiresAt" type="datetime-local" class="p-form-control" value="{{ $value('discountExpiresAt') ? \Illuminate\Support\Carbon::parse($value('discountExpiresAt'))->format('Y-m-d\TH:i') : '' }}"></div>
        <div><label class="p-form-label">Ombor</label><input name="stock" type="number" class="p-form-control" required value="{{ $value('stock', 0) }}"></div>
        <div><label class="p-form-label">Recommendation tugash vaqti</label><input name="recommendedExpiresAt" type="datetime-local" class="p-form-control" value="{{ $value('recommendedExpiresAt') ? \Illuminate\Support\Carbon::parse($value('recommendedExpiresAt'))->format('Y-m-d\TH:i') : '' }}"></div>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" @checked($value('status', true))> Mahsulot faol</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="recommended" value="1" class="rounded" @checked($value('recommended', false))> Tavsiya etilgan</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="is_hidden" value="1" class="rounded" @checked($value('is_hidden', false))> Yashirin</label>
      </div>
    </section>
  </div>

  <section class="card-panel p-5">
    <h3 class="text-lg font-black mb-4">Rasmlar</h3>
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <div class="xl:col-span-8">
        <label class="p-form-label">Mavjud rasmlar yo‘li yoki URL</label>
        <textarea name="images_text" class="p-form-control font-mono text-sm" rows="6" placeholder="Har qatorga bitta rasm yo‘li yoki URL">{{ $images }}</textarea>
      </div>
      <div class="xl:col-span-4">
        <label class="p-form-label">Yangi rasmlar yuklash</label>
        <input type="file" name="images[]" multiple accept="image/*" class="p-form-control">
      </div>
    </div>
  </section>

  <section class="card-panel p-5">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h3 class="text-lg font-black">Variantlar</h3>
      <button type="button" class="btn-p ghost" onclick="window.a122AddVariantRow?.()"><i class="bi bi-plus-circle"></i> Variant qo‘shish</button>
    </div>
    <div id="variantRows" class="space-y-4">
      @forelse($variants as $index => $variant)
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border border-[var(--p-border)] rounded-2xl p-4">
          <input type="hidden" name="variant_id[]" value="{{ $variant['id'] ?? '' }}">
          <div class="md:col-span-4"><label class="p-form-label">Rang nomi</label><input name="variant_color_name[]" class="p-form-control" value="{{ $variant['color_name'] ?? '' }}"></div>
          <div class="md:col-span-2"><label class="p-form-label">Stock</label><input name="variant_stock[]" type="number" class="p-form-control" value="{{ $variant['stock'] ?? 0 }}"></div>
          <div class="md:col-span-4"><label class="p-form-label">Rasm yo‘li</label><input name="variant_image_existing[]" class="p-form-control font-mono text-sm" value="{{ $variant['image_path'] ?? '' }}"></div>
          <div class="md:col-span-2"><label class="p-form-label">Yangi rasm</label><input type="file" name="variant_image[]" accept="image/*" class="p-form-control"></div>
        </div>
      @empty
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border border-[var(--p-border)] rounded-2xl p-4">
          <input type="hidden" name="variant_id[]" value="">
          <div class="md:col-span-4"><label class="p-form-label">Rang nomi</label><input name="variant_color_name[]" class="p-form-control"></div>
          <div class="md:col-span-2"><label class="p-form-label">Stock</label><input name="variant_stock[]" type="number" class="p-form-control" value="0"></div>
          <div class="md:col-span-4"><label class="p-form-label">Rasm yo‘li</label><input name="variant_image_existing[]" class="p-form-control font-mono text-sm"></div>
          <div class="md:col-span-2"><label class="p-form-label">Yangi rasm</label><input type="file" name="variant_image[]" accept="image/*" class="p-form-control"></div>
        </div>
      @endforelse
    </div>
  </section>

  <div class="sticky bottom-4 z-20">
    <div class="card-panel p-4 flex justify-end gap-2 shadow-[var(--p-shadow)]">
      <a href="{{ $item ? route('admin.stationery.show', $item->id) : route('admin.stationery.index') }}" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-check2-circle"></i> Saqlash</button>
    </div>
  </div>
</form>

@once
  @push('scripts')
    <script>
      window.a122AddVariantRow = function () {
        const target = document.getElementById('variantRows');
        if (!target) return;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-12 gap-3 border border-[var(--p-border)] rounded-2xl p-4';
        row.innerHTML = `
          <input type="hidden" name="variant_id[]" value="">
          <div class="md:col-span-4"><label class="p-form-label">Rang nomi</label><input name="variant_color_name[]" class="p-form-control"></div>
          <div class="md:col-span-2"><label class="p-form-label">Stock</label><input name="variant_stock[]" type="number" class="p-form-control" value="0"></div>
          <div class="md:col-span-4"><label class="p-form-label">Rasm yo‘li</label><input name="variant_image_existing[]" class="p-form-control font-mono text-sm"></div>
          <div class="md:col-span-2"><label class="p-form-label">Yangi rasm</label><input type="file" name="variant_image[]" accept="image/*" class="p-form-control"></div>
        `;
        target.appendChild(row);
      };
    </script>
  @endpush
@endonce
