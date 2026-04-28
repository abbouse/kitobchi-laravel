@php
  $abilitySuggestions = ['read', 'write', 'delete', 'admin', 'push', 'orders', 'users'];
@endphp

<div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
  <section class="p-card fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">Asosiy ma'lumotlar</div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="lg:col-span-2">
        <label class="p-form-label">Nomi <span style="color:var(--p-danger)">*</span></label>
        <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror" value="{{ old('name', $apiClient?->name) }}" placeholder="Masalan: iOS App v2, Partner Service" required>
        @error('name')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
      </div>

      @if($apiClient)
        <div>
          <label class="p-form-label">App ID</label>
          <div class="flex items-center gap-2">
            <input type="text" class="p-form-control font-mono text-sm" value="{{ $apiClient->app_id }}" readonly>
            <button type="button" onclick="copyText('{{ $apiClient->app_id }}')" class="btn-p ghost sm">Nusxa</button>
          </div>
          <p class="mt-2 text-xs text-[var(--p-hint)]">Avtomatik yaratiladi va o'zgarmaydi.</p>
        </div>

        <div>
          <label class="p-form-label">App Secret</label>
          <div class="flex items-center gap-2">
            <input type="text" id="secretField" class="p-form-control font-mono text-sm" value="{{ str_repeat('•', 16) }}" readonly>
            <button type="button" onclick="toggleSecret()" class="btn-p ghost sm" id="eyeBtn">Ko'rsatish</button>
            <button type="button" onclick="copyText('{{ addslashes($apiClient->app_secret) }}')" class="btn-p ghost sm">Nusxa</button>
          </div>
          <p class="mt-2 text-xs text-[var(--p-hint)]">Secret faqat ishonchli tizimlarga beriladi.</p>
        </div>
      @endif

      <div class="lg:col-span-2">
        <label class="p-form-label">Huquqlar</label>
        <textarea name="abilities" class="p-form-control @error('abilities') border-danger @enderror font-mono text-sm" rows="5" placeholder='["read","orders"]'>{{ old('abilities', isset($apiClient) && is_array($apiClient?->abilities) ? json_encode($apiClient->abilities, JSON_UNESCAPED_SLASHES) : $apiClient?->abilities) }}</textarea>
        @error('abilities')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        <div class="mt-3 flex flex-wrap gap-2">
          @foreach($abilitySuggestions as $ab)
            <button type="button" onclick="addAbility('{{ $ab }}')" class="btn-p ghost sm">{{ $ab }}</button>
          @endforeach
        </div>
      </div>

      <div>
        <label class="p-form-label">Rate limit / soniya</label>
        <input
          type="number"
          min="1"
          max="10000"
          name="rate_limit_per_second"
          class="p-form-control @error('rate_limit_per_second') border-danger @enderror"
          value="{{ old('rate_limit_per_second', $apiClient?->rate_limit_per_second ?? 8) }}"
          placeholder="8"
        >
        @error('rate_limit_per_second')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        <p class="mt-2 text-xs text-[var(--p-hint)]">Bitta client bir soniyada necha so‘rov yubora oladi.</p>
      </div>

      <div>
        <label class="p-form-label">Rate limit / daqiqa</label>
        <input
          type="number"
          min="1"
          max="500000"
          name="rate_limit_per_minute"
          class="p-form-control @error('rate_limit_per_minute') border-danger @enderror"
          value="{{ old('rate_limit_per_minute', $apiClient?->rate_limit_per_minute ?? 240) }}"
          placeholder="240"
        >
        @error('rate_limit_per_minute')<div class="mt-1 text-xs text-[var(--p-danger)]">{{ $message }}</div>@enderror
        <p class="mt-2 text-xs text-[var(--p-hint)]">Qisqa burst’lardan tashqari umumiy daqiqalik limit.</p>
      </div>
    </div>
  </section>

  <aside class="space-y-4 fade-up">
    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Holat va boshqaruv</div>
      </div>

      <label class="flex items-start gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $apiClient?->is_active ?? true) ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded">
        <span class="min-w-0">
          <span class="block text-sm font-medium text-[var(--p-text)]">Faol holatda saqlash</span>
          <span class="mt-1 block text-xs text-[var(--p-hint)]">Faol bo'lsa, mijoz API so'rov yubora oladi.</span>
        </span>
      </label>

      @if($apiClient)
        <button type="button" onclick="document.getElementById('regenForm').submit()" class="btn-p ghost w-full mt-4">Secretni yangilash</button>
      @endif
    </section>

    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Tez eslatma</div>
      </div>
      <div class="space-y-3 text-sm text-[var(--p-hint)]">
        <p>Huquqlar JSON ko'rinishida saqlanadi. Misol: <code>["read","orders"]</code>.</p>
        <p><code>read</code> bo'lmasa hozirgi client endpointlari ishlamaydi.</p>
        <p>Secret yangilansa, eski secret darhol ishlamay qoladi.</p>
        <p>Rate limit har bir client uchun alohida ishlaydi va response headerlarda ham qaytadi.</p>
      </div>
    </section>
  </aside>
</div>

<div class="mt-4 flex flex-wrap gap-2 fade-up">
  <button type="submit" class="btn-p primary">{{ $apiClient ? 'Saqlash' : 'Yaratish' }}</button>
  <a href="{{ route('admin.api-clients.index') }}" class="btn-p ghost">Bekor</a>
</div>

@if($apiClient)
<form id="regenForm" method="POST"
      action="{{ route('admin.api-clients.regenerate',$apiClient) }}"
      onsubmit="return confirm('Eski secret kalit endi ishlamaydi!')">
  @csrf @method('PATCH')
</form>
@endif

@push('scripts')
<script>
@if($apiClient ?? null)
const SECRET = '{{ addslashes($apiClient->app_secret) }}';
let visible = false;
function toggleSecret(){
  const el  = document.getElementById('secretField');
  visible = !visible;
  el.value    = visible ? SECRET : '•'.repeat(16);
  document.getElementById('eyeBtn').textContent = visible ? 'Yashirish' : 'Ko\\'rsatish';
}
@endif

function copyText(text){
  navigator.clipboard.writeText(text).then(()=>{
    const t = document.createElement('div');
    t.textContent = 'Nusxalandi!';
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:9999;
      background:var(--p-surface);border:1px solid var(--p-border);
      padding:8px 16px;border-radius:8px;font-size:13px;
      color:var(--p-success);box-shadow:0 4px 20px rgba(0,0,0,.15)`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 1800);
  });
}

function addAbility(ab){
  const ta = document.querySelector('textarea[name="abilities"]');
  try{
    let arr = JSON.parse(ta.value || '[]');
    if(!arr.includes(ab)){ arr.push(ab); }
    ta.value = JSON.stringify(arr);
  } catch(e){
    ta.value = JSON.stringify([ab]);
  }
}
</script>
@endpush
