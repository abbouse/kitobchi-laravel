@extends('a122.layouts.admin')
@section('title', isset($promocode) ? 'Tahrirlash: '.$promocode->code : 'Yangi promokod')
@section('page-title', isset($promocode) ? 'Promokod tahrirlash' : 'Yangi promokod')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-a122.page-header back-href="{{ route('admin.promocodes.index') }}">
  <x-slot name="heading">{{ isset($promocode) ? $promocode->code : 'Yangi promokod' }}</x-slot>
  <x-slot name="meta">{{ isset($promocode) ? 'Promokodni tahrirlash' : 'Yangi chegirma kodi yaratish' }}</x-slot>
</x-a122.page-header>


    <form method="POST"
      action="{{ isset($promocode) ? route('admin.promocodes.update',$promocode) : route('admin.promocodes.store') }}">
      @csrf
      @if(isset($promocode)) @method('PUT') @endif

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Promokod ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            {{-- Kod --}}
            @if(!isset($promocode))
            <div class="">
              <label class="p-form-label">Kod <span style="color:var(--p-danger)">*</span></label>
              <div class="flex gap-2">
                <input type="text" name="code" id="promoCode"
                       class="p-form-control @error('code') is-invalid @enderror"
                       value="{{ old('code') }}" required
                       style="text-transform:uppercase;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:15px;letter-spacing:.05em">
                <button type="button" class="btn-p ghost" onclick="generateCode()">
                  <i class="bi bi-arrow-repeat"></i> Yaratish
                </button>
              </div>
              @error('code')<div style="font-size:12px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
            </div>
            @else
            <div class="">
              <label class="p-form-label">Kod</label>
              <code style="display:block;font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;
                           color:var(--p-accent);background:var(--p-elevated);padding:10px 16px;
                           border-radius:8px;letter-spacing:.08em">{{ $promocode->code }}</code>
            </div>
            @endif

            {{-- Tur --}}
            <div class="">
              <label class="p-form-label">Chegirma turi <span style="color:var(--p-danger)">*</span></label>
              <select name="type" id="promoType" class="p-form-control" onchange="updateAmountLabel()">
                <option value="percent" {{ old('type',$promocode->type??'')=='percent'?'selected':'' }}>Foiz (%)</option>
                <option value="fixed"   {{ old('type',$promocode->type??'')=='fixed'?'selected':'' }}>Miqdor (UZS)</option>
              </select>
            </div>

            {{-- Miqdor --}}
            <div class="">
              <label class="p-form-label" id="amountLabel">Chegirma miqdori <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="amount" class="p-form-control @error('amount') is-invalid @enderror"
                     value="{{ old('amount',$promocode->amount??'') }}" min="1" required>
              @error('amount')<div style="font-size:12px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
            </div>

            {{-- Min buyurtma --}}
            <div class="">
              <label class="p-form-label">Minimal buyurtma (UZS)</label>
              <input type="number" name="min_order_amount" class="p-form-control"
                     value="{{ old('min_order_amount',$promocode->min_order_amount??0) }}" min="0">
            </div>

            {{-- Limit --}}
            <div class="">
              <label class="p-form-label">Foydalanish limiti</label>
              <input type="number" name="usesLimit" class="p-form-control"
                     value="{{ old('usesLimit',$promocode->usesLimit??0) }}" min="0"
                     placeholder="0 = cheksiz">
            </div>

            {{-- Muddat --}}
            <div class="">
              <label class="p-form-label">Amal qilish muddati <span style="color:var(--p-danger)">*</span></label>
              <input type="datetime-local" name="expires_at" class="p-form-control @error('expires_at') is-invalid @enderror"
                     value="{{ old('expires_at', isset($promocode) ? \Carbon\Carbon::parse($promocode->expires_at)->format('Y-m-d\TH:i') : '') }}"
                     required>
              @error('expires_at')<div style="font-size:12px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>@enderror
            </div>

            {{-- Status --}}
            <div class="">
              <label class="p-form-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1"
                       {{ old('status',$promocode->status??1) ? 'checked' : '' }}
                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      @isset($promocode)
      <div class="p-card mb-3" style="background:var(--p-warning-d);border-color:rgba(245,166,35,.2)">
        <div class="dash-card-body" style="padding:14px 20px">
          <div class="flex gap-3">
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)">{{ $promocode->usedCount }}</div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Ishlatildi</div>
            </div>
            <div style="width:1px;background:rgba(245,166,35,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)">{{ $promocode->usesLimit ?: '∞' }}</div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Limit</div>
            </div>
          </div>
        </div>
      </div>
      @endisset

      <div class="flex gap-2 justify-end">
        <a href="{{ route('admin.promocodes.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function generateCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let code = '';
  for (let i = 0; i < 8; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
  document.getElementById('promoCode').value = code;
}

function updateAmountLabel() {
  const type = document.getElementById('promoType').value;
  document.getElementById('amountLabel').textContent = type === 'percent'
    ? 'Chegirma foizi (%) *'
    : 'Chegirma miqdori (UZS) *';
}
updateAmountLabel();
</script>
@endpush