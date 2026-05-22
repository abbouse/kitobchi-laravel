@php
  $book = $book ?? null;
  $value = fn ($key, $default = '') => old($key, data_get($book, $key, $default));
  $images = old('images_text', implode("\n", is_array(data_get($book, 'images')) ? data_get($book, 'images') : (json_decode((string) data_get($book, 'images', '[]'), true) ?: [])));
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
    <section class="card p-5 xl:col-span-8">
      <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="{{ $value('name') }}"></div>
        <div>
          <label class="p-form-label">Muallif</label>
          <input name="author" class="p-form-control" required value="{{ $value('author') }}" placeholder="Muallif nomi">
          @if(data_get($book, 'author_id'))
            <div class="mt-1 text-xs text-[var(--p-hint)]">Bog‘langan muallif ID: #{{ data_get($book, 'author_id') }}</div>
          @endif
        </div>
        <div><label class="p-form-label">Tarjimon</label><input name="translator" class="p-form-control" value="{{ $value('translator') }}"></div>
        <div><label class="p-form-label">ISBN</label><input name="isbn" class="p-form-control" value="{{ $value('isbn') }}"></div>
        <div><label class="p-form-label">Sotuvchi</label><select name="seller_id" class="p-form-control"><option value="">Ichki katalog</option>@foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected((string)$value('seller_id') === (string)$seller->id)>{{ $seller->shop_name }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$value('category_id') === (string)$category->id)>{{ $category->name_uz }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Nashriyot</label><select name="publisher_id" class="p-form-control"><option value="">Tanlanmagan</option>@foreach($publishers as $publisher)<option value="{{ $publisher->id }}" @selected((string)$value('publisher_id') === (string)$publisher->id)>{{ $publisher->name }}</option>@endforeach</select></div>
        <div><label class="p-form-label">Moderatsiya</label><select name="is_approved" class="p-form-control"><option value="0" @selected((string)$value('is_approved', 1) === '0')>Moderatsiyada</option><option value="1" @selected((string)$value('is_approved', 1) === '1')>Tasdiqlangan</option><option value="2" @selected((string)$value('is_approved', 1) === '2')>Rad etilgan</option></select></div>
        <div><label class="p-form-label">Til kodi</label><input name="lang" class="p-form-control" placeholder="uz / ru / en" value="{{ $value('lang') }}"></div>
        <div><label class="p-form-label">Yozuv turi</label><input name="langType" class="p-form-control" placeholder="latin / cyrillic" value="{{ $value('langType') }}"></div>
        <div><label class="p-form-label">Muqova turi</label><input name="coverType" class="p-form-control" placeholder="soft / hard" value="{{ $value('coverType') }}"></div>
        <div><label class="p-form-label">Nashr yili</label><input name="year" type="number" class="p-form-control" value="{{ $value('year') }}"></div>
        <div><label class="p-form-label">Sahifalar</label><input name="pages" type="number" class="p-form-control" value="{{ $value('pages') }}"></div>
        <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7">{{ $value('description') }}</textarea></div>
      </div>
    </section>

    <section class="card p-5 xl:col-span-4">
      <h3 class="text-lg font-black mb-4">Savdo va visibility</h3>
      <div class="space-y-4">
        <div><label class="p-form-label">Narx</label><input name="price" type="number" step="0.01" class="p-form-control" required value="{{ $value('price') }}"></div>
        <div><label class="p-form-label">Chegirma narxi</label><input name="discountPrice" type="number" step="0.01" class="p-form-control" value="{{ $value('discountPrice') }}"></div>
        <div><label class="p-form-label">Chegirma tugash vaqti</label><input name="discountExpiresAt" type="datetime-local" class="p-form-control" value="{{ $value('discountExpiresAt') ? \Illuminate\Support\Carbon::parse($value('discountExpiresAt'))->format('Y-m-d\TH:i') : '' }}"></div>
        <div><label class="p-form-label">Ombor soni</label><input name="count" type="number" class="p-form-control" required value="{{ $value('count', 0) }}"></div>
        <div><label class="p-form-label">Recommendation tugash vaqti</label><input name="recommendedExpiresAt" type="datetime-local" class="p-form-control" value="{{ $value('recommendedExpiresAt') ? \Illuminate\Support\Carbon::parse($value('recommendedExpiresAt'))->format('Y-m-d\TH:i') : '' }}"></div>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" @checked($value('status', true))> Mahsulot faol</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="recommended" value="1" class="rounded" @checked($value('recommended', false))> Tavsiya etilgan</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="is_hidden" value="1" class="rounded" @checked($value('is_hidden', false))> Yashirin</label>
      </div>
    </section>
  </div>

  <section class="card p-5">
    <h3 class="text-lg font-black mb-4">Rasmlar</h3>
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <div class="xl:col-span-8">
        <label class="p-form-label">Mavjud rasmlar yo‘li yoki URL</label>
        <textarea name="images_text" class="p-form-control font-mono text-sm" rows="7" placeholder="Har qatorga bitta rasm yo‘li yoki URL">{{ $images }}</textarea>
        <div class="mt-2 text-xs text-[var(--p-hint)]">Qatorni o‘chirib tashlasangiz rasm mahsulotdan olib tashlanadi.</div>
      </div>
      <div class="xl:col-span-4">
        <label class="p-form-label">Yangi rasmlar yuklash</label>
        <input type="file" name="images[]" multiple accept="image/*" class="p-form-control">
      </div>
    </div>
  </section>

  <div class="sticky bottom-4 z-20">
    <div class="card p-4 flex items-center justify-end gap-2 shadow-[var(--p-shadow)]">
      <a href="{{ $cancelHref }}" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-check2-circle"></i> Saqlash</button>
    </div>
  </div>
</form>
