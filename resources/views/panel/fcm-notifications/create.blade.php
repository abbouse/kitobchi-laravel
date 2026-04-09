@extends('panel.layouts.panel')
@section('title', 'Yangi bildirishnoma')
@section('page-title', 'Push bildirishnoma yuborish')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">

    <div class="d-flex align-items-center gap-3 mb-4 fade-up">
      <a href="{{ route('panel.fcm-notifications.index') }}" class="btn-p ghost icon">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div>
        <h1 class="page-title">Yangi push xabar</h1>
        <p class="page-sub">Foydalanuvchilar, sotuvchilar yoki kuryerlarga</p>
      </div>
    </div>

    {{-- Token count preview --}}
    <div class="row g-2 mb-4 fade-up">
      @foreach($targets as $key => $t)
      <div class="col-4">
        <div class="p-card d-flex align-items-center gap-3" style="padding:14px"
             id="preview-{{ $key }}">
          <div style="width:36px;height:36px;border-radius:9px;display:flex;
                      align-items:center;justify-content:center;font-size:16px;
                      background:var(--p-{{ $t['color'] }}-d,var(--p-elevated));
                      color:var(--p-{{ $t['color'] }});flex-shrink:0">
            <i class="bi {{ $t['icon'] }}"></i>
          </div>
          <div>
            <div style="font-size:18px;font-weight:700;font-family:'DM Mono',monospace;
                        color:var(--p-{{ $t['color'] }})">
              {{ number_format($tokenCounts[$key] ?? 0) }}
            </div>
            <div style="font-size:10px;color:var(--p-hint)">faol qurilma</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <form method="POST" action="{{ route('panel.fcm-notifications.store') }}">
      @csrf

      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head"><div class="dash-card-title">Xabar ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            {{-- Sarlavha --}}
            <div class="col-12">
              <label class="p-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" required maxlength="255"
                     placeholder="Yangi chegirma 20%! 🎉"
                     id="previewTitle">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Matn --}}
            <div class="col-12">
              <label class="p-label">Xabar matni <span style="color:var(--p-danger)">*</span></label>
              <textarea name="description" rows="3"
                        class="p-form-control @error('description') is-invalid @enderror"
                        required maxlength="500"
                        placeholder="Qisqa va aniq yozing. Maksimal 500 belgi."
                        id="previewBody">{{ old('description') }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px;text-align:right">
                <span id="charCount">0</span> / 500
              </div>
            </div>

            {{-- Qabul qiluvchi --}}
            <div class="col-12">
              <label class="p-label mb-2">Qabul qiluvchilar <span style="color:var(--p-danger)">*</span></label>
              <div class="row g-2">
                @foreach($targets as $key => $t)
                <div class="col-12">
                  <label style="display:flex;align-items:center;gap:12px;cursor:pointer;
                                background:var(--p-elevated);border:2px solid var(--p-border);
                                border-radius:10px;padding:14px 16px;transition:all .15s"
                         class="target-label" data-target="{{ $key }}">
                    <input type="radio" name="who" value="{{ $key }}"
                           {{ old('who')===$key ? 'checked' : '' }}
                           style="accent-color:var(--p-{{ $t['color'] }});width:18px;height:18px;flex-shrink:0"
                           class="target-radio">
                    <div style="width:36px;height:36px;border-radius:8px;display:flex;
                                align-items:center;justify-content:center;font-size:16px;
                                background:var(--p-{{ $t['color'] }}-d,var(--p-surface));
                                color:var(--p-{{ $t['color'] }});flex-shrink:0">
                      <i class="bi {{ $t['icon'] }}"></i>
                    </div>
                    <div style="flex:1">
                      <div style="font-size:13px;font-weight:600;color:var(--p-text)">
                        {{ $t['label'] }}
                      </div>
                      <div style="font-size:11px;color:var(--p-hint)">
                        {{ number_format($tokenCounts[$key] ?? 0) }} ta faol qurilma
                      </div>
                    </div>
                    <span class="s-pill {{ $t['color'] }}" style="font-size:13px;font-weight:700;
                                font-family:'DM Mono',monospace">
                      {{ number_format($tokenCounts[$key] ?? 0) }}
                    </span>
                  </label>
                </div>
                @endforeach
              </div>
              @error('who')<div style="font-size:12px;color:var(--p-danger);margin-top:6px">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

      {{-- Preview --}}
      <div class="p-card mb-3 fade-up" style="background:var(--p-elevated);border-style:dashed">
        <div class="dash-card-head">
          <div class="dash-card-title" style="font-size:12px;color:var(--p-hint)">
            <i class="bi bi-phone me-1"></i> Telefon ko'rinishi (preview)
          </div>
        </div>
        <div class="dash-card-body">
          <div style="background:var(--p-surface);border-radius:10px;padding:12px 14px;
                      border:1px solid var(--p-border);max-width:320px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <div style="width:24px;height:24px;border-radius:6px;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          flex-shrink:0"></div>
              <span style="font-size:11px;font-weight:600;color:var(--p-muted)">Kitobchi</span>
              <span style="font-size:10px;color:var(--p-hint);margin-left:auto">hozir</span>
            </div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)" id="phone-title">
              Sarlavha bu yerda ko'rinadi
            </div>
            <div style="font-size:12px;color:var(--p-muted);margin-top:3px" id="phone-body">
              Matn bu yerda ko'rinadi...
            </div>
          </div>
        </div>
      </div>

      {{-- Ogohlantirish --}}
      <div style="padding:12px 16px;background:var(--p-warning-d);border-radius:8px;
                  border:1px solid rgba(245,166,35,.2);margin-bottom:20px"
           class="fade-up">
        <div style="display:flex;gap:8px;font-size:12px;color:var(--p-warning)">
          <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px"></i>
          <div>
            <strong>Diqqat!</strong> Yuborilgan bildirishnomani bekor qilib bo'lmaydi.
            Matnni ehtiyotkorlik bilan yozing. Barcha faol qurilmalarga bir vaqtda yuboriladi.
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end fade-up">
        <a href="{{ route('panel.fcm-notifications.index') }}" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p" id="submitBtn">
          <i class="bi bi-send-fill"></i> Yuborish
        </button>
      </div>
    </form>

  </div>
</div>
@endsection

@push('scripts')
<script>
// Preview
const titleInput = document.getElementById('previewTitle');
const bodyInput  = document.getElementById('previewBody');
const phoneTitle = document.getElementById('phone-title');
const phoneBody  = document.getElementById('phone-body');
const charCount  = document.getElementById('charCount');

titleInput.addEventListener('input', () => {
  phoneTitle.textContent = titleInput.value || 'Sarlavha bu yerda ko\'rinadi';
});
bodyInput.addEventListener('input', () => {
  phoneBody.textContent  = bodyInput.value  || 'Matn bu yerda ko\'rinadi...';
  charCount.textContent  = bodyInput.value.length;
});

// Target radio styling
document.querySelectorAll('.target-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.target-label').forEach(l => {
      l.style.borderColor = 'var(--p-border)';
      l.style.background  = 'var(--p-elevated)';
    });
    const label = this.closest('.target-label');
    const clrMap = { users: 'accent', business: 'warning', courier: 'success' };
    const clr = clrMap[this.value] || 'accent';
    label.style.borderColor = `var(--p-${clr})`;
    label.style.background  = `var(--p-elevated)`;
  });
});

// Olddan tanlanganlarga stil berish
document.querySelectorAll('.target-radio:checked').forEach(r => r.dispatchEvent(new Event('change')));

// Double click protection
document.querySelector('form').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Yuborilmoqda...';
});
</script>
@endpush