@php
  $user = $user ?? null;
  $val = fn($k, $d='') => old($k, data_get($user, $k, $d));
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
  @csrf
  @if(($method ?? 'POST') !== 'POST') @method($method) @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card p-6 lg:col-span-2 space-y-4">
      <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-emerald-500"></i> Asosiy ma'lumotlar</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Ism *</label><input name="name" required value="{{ $val('name') }}" class="input">@error('name')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror</div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Familiya</label><input name="lastname" value="{{ $val('lastname') }}" class="input"></div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Email *</label><input name="email" required value="{{ $val('email') }}" class="input">@error('email')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror</div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Telefon</label><input name="phone_number" value="{{ $val('phone_number') }}" class="input">@error('phone_number')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror</div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Pozitsiya</label><input name="position" value="{{ $val('position') }}" class="input"></div>
      </div>
    </div>

    <div class="card p-6 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="settings" class="w-4 h-4 text-emerald-500"></i> Sozlamalar</h3>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="isVerified" value="1" {{ $val('isVerified') ? 'checked' : '' }} class="rounded"> Tasdiqlangan foydalanuvchi</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_premium" value="1" {{ $val('is_premium') ? 'checked' : '' }} class="rounded"> Premium</label>
      <div class="kpi-soft">
        <div class="metric-label">Admin eslatmasi</div>
        <div class="metric-meta mt-2">Telefon va email maydonlari account identifikatori sifatida ishlatiladi. Yangilashda dublikat cheklovlari saqlanadi.</div>
      </div>
    </div>
  </div>

  <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2">
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Bekor qilish</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check" class="w-4 h-4"></i> Saqlash</button>
  </div>
</form>
