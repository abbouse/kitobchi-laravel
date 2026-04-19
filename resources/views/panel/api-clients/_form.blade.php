{{-- resources/views/panel/api-clients/_form.blade.php --}}

<div class="p-card mb-3 fade-up">
  <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
  <div style="padding:0 18px 18px">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

      <div class="">
        <label class="p-form-label">
          Nomi <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="text" name="name" class="p-form-control @error('name') border-danger @enderror"
               value="{{ old('name', $apiClient?->name) }}"
               placeholder="Masalan: iOS App v2, Partner Service" required>
        @error('name')
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
        @enderror
      </div>

      {{-- Faqat mavjud mijoz uchun kalitlarni ko'rsatish --}}
      @if($apiClient)
      <div class="">
        <label class="p-form-label">App ID</label>
        <div class="flex items-center gap-2">
          <input type="text" class="p-form-control" value="{{ $apiClient->app_id }}"
                 readonly style="font-family:'JetBrains Mono',monospace;font-size:13px;
                                 background:var(--p-elevated);color:var(--p-accent)">
          <button type="button" onclick="copyText('{{ $apiClient->app_id }}')"
                  class="btn-p ghost sm"><i class="bi bi-copy"></i></button>
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Avtomatik — o'zgartirib bo'lmaydi</div>
      </div>

      <div class="">
        <label class="p-form-label">App Secret</label>
        <div class="flex items-center gap-2">
          <input type="text" id="secretField" class="p-form-control"
                 value="{{ str_repeat('•', 16) }}"
                 readonly style="font-family:'JetBrains Mono',monospace;font-size:13px;
                                 background:var(--p-elevated);color:var(--p-muted)">
          <button type="button" onclick="toggleSecret()" class="btn-p ghost sm" id="eyeBtn">
            <i class="bi bi-eye"></i>
          </button>
          <button type="button" onclick="copyText('{{ addslashes($apiClient->app_secret) }}')"
                  class="btn-p ghost sm"><i class="bi bi-copy"></i></button>
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
          Yangilash uchun
          <a href="#" onclick="document.getElementById('regenForm').submit();return false"
             style="color:var(--p-warning)">Secret yangilash →</a>
        </div>
      </div>
      @endif

      <div class="">
        <label class="p-form-label">
          Huquqlar (JSON array)
          <span style="color:var(--p-hint);font-size:11px;font-weight:400">
            — e.g. ["read","write","delete"]
          </span>
        </label>
        <textarea name="abilities" class="p-form-control @error('abilities') border-danger @enderror"
                  rows="3" style="font-family:'JetBrains Mono',monospace;font-size:13px"
                  placeholder='["read"]'>{{ old('abilities', $apiClient?->abilities) }}</textarea>
        @error('abilities')
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
        @enderror
        <div style="font-size:11px;color:var(--p-hint);margin-top:5px">
          Mavjud huquqlar:
          @foreach(['read','write','delete','admin','push','orders','users'] as $ab)
          <code onclick="addAbility('{{ $ab }}')"
                style="cursor:pointer;background:var(--p-elevated);padding:1px 6px;
                       border-radius:4px;font-size:10px;margin-right:3px;
                       color:var(--p-accent)">{{ $ab }}</code>
          @endforeach
        </div>
      </div>

      <div class="">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="hidden" name="is_active" value="0">
          <input type="checkbox" name="is_active" value="1"
                 {{ old('is_active', $apiClient?->is_active ?? true) ? 'checked' : '' }}
                 style="width:18px;height:18px;accent-color:var(--p-accent)">
          <span style="font-size:13px;color:var(--p-text)">Faol</span>
          <span style="font-size:11px;color:var(--p-hint)">(so'rovlarga javob beradi)</span>
        </label>
      </div>

    </div>
  </div>
</div>

<div class="flex gap-2 fade-up">
  <button type="submit" class="btn-p primary">
    <i class="bi bi-check-lg"></i>
    {{ $apiClient ? 'Saqlash' : 'Yaratish' }}
  </button>
  <a href="{{ route('panel.api-clients.index') }}" class="btn-p ghost">Bekor</a>
</div>

@if($apiClient)
{{-- Hidden regen form --}}
<form id="regenForm" method="POST"
      action="{{ route('panel.api-clients.regenerate',$apiClient) }}"
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
  const eye = document.getElementById('eyeBtn').querySelector('i');
  visible = !visible;
  el.value    = visible ? SECRET : '•'.repeat(16);
  eye.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
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