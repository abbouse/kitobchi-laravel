{{-- ══ Yetkazish xizmatlari ══════════════════════════════════════════ --}}
@if($activeTab === 'delivery')
<div class="row g-3">

  {{-- ── Ro'yxat ───────────────────────────────────────────────────── --}}
  <div class="col-12">
    <div class="p-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Yetkazish xizmatlari</div>
          <div class="dash-card-sub">{{ $delivery->count() }} ta xizmat</div>
        </div>
      </div>
      <div class="dash-card-body p-0">
        <table class="p-table">
          <thead>
            <tr>
              <th>Nomi</th>
              <th>Turi</th>
              <th>Narx (UZS)</th>
              <th>Muddat</th>
              <th>Mamlakat</th>
              <th>Toshkent</th>
              <th>Bepul dan</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($delivery as $d)
            <tr>
              <td style="font-weight:600;color:var(--p-text)">{{ $d->name }}</td>
              <td>
                <span class="s-pill {{ $d->type === 'courier_service' ? 'accent' : 'muted' }}" style="font-size:11px">
                  <i class="bi {{ $d->type === 'courier_service' ? 'bi-truck' : 'bi-send' }} me-1"></i>
                  {{ $d->type === 'courier_service' ? 'Kuryer' : 'Pochta' }}
                </span>
              </td>
              <td style="font-family:'DM Mono',monospace;font-size:13px">
                {{ number_format($d->priceKg) }}
              </td>
              <td style="font-family:'DM Mono',monospace;font-size:12px">
                {{ $d->muddat }} kun
              </td>
              <td style="font-size:12px;color:var(--p-muted)">{{ $d->forCountry }}</td>
              <td>
                @if($d->capital)
                  <span class="s-pill success" style="font-size:11px"><i class="bi bi-check-lg"></i> Ha</span>
                @else
                  <span class="s-pill muted" style="font-size:11px">Yo'q</span>
                @endif
              </td>
              <td style="font-size:12px;color:var(--p-muted)">
                {{ $d->freePriceFrom > 0 ? number_format($d->freePriceFrom).' UZS' : '—' }}
              </td>
              <td>
                {{-- Inline status toggle --}}
                <form method="POST"
                      action="{{ route('panel.settings.delivery.update', $d) }}"
                      style="display:inline">
                  @csrf @method('PUT')
                  <input type="hidden" name="name"          value="{{ $d->name }}">
                  <input type="hidden" name="type"          value="{{ $d->type }}">
                  <input type="hidden" name="priceKg"       value="{{ $d->priceKg }}">
                  <input type="hidden" name="muddat"        value="{{ $d->muddat }}">
                  <input type="hidden" name="forCountry"    value="{{ $d->forCountry }}">
                  <input type="hidden" name="capital"       value="{{ $d->capital ? '1' : '0' }}">
                  <input type="hidden" name="freePriceFrom" value="{{ $d->freePriceFrom }}">
                  <input type="hidden" name="status"        value="{{ $d->status ? '0' : '1' }}">
                  <button type="submit"
                          class="btn-p {{ $d->status ? 'success' : 'ghost' }} sm"
                          title="{{ $d->status ? 'Faol — o\'chirish' : 'Nofaol — yoqish' }}">
                    <i class="bi {{ $d->status ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                  </button>
                </form>
              </td>
              <td>
                <div class="d-flex gap-1">
                  {{-- Edit tugmasi — modal ochadi --}}
                  <button class="btn-p ghost sm"
                          onclick="openEditDelivery({{ $d->id }}, '{{ $d->name }}', '{{ $d->type }}', {{ $d->priceKg }}, {{ $d->muddat }}, '{{ $d->forCountry }}', {{ $d->capital ? 'true' : 'false' }}, {{ $d->freePriceFrom }}, {{ $d->status ? 'true' : 'false' }})"
                          title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </button>
                  {{-- O'chirish --}}
                  <form method="POST"
                        action="{{ route('panel.settings.delivery.destroy', $d) }}"
                        onsubmit="return confirm('O\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" style="text-align:center;padding:30px;color:var(--p-hint)">
                Xizmatlar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ── Yangi xizmat qo'shish ────────────────────────────────────── --}}
  <div class="col-xl-5">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Yangi xizmat qo'shish</div>
      </div>
      <div class="dash-card-body">
        <form method="POST" action="{{ route('panel.settings.delivery.store') }}">
          @csrf
          <div class="row g-3">

            <div class="col-12">
              <label class="p-label">Xizmat nomi *</label>
              <input type="text" name="name" class="p-form-control"
                     required placeholder="Kuryer yetkazish">
            </div>

            <div class="col-12">
              <label class="p-label">Turi *</label>
              <select name="type" class="p-form-control" required>
                <option value="courier_service">🚚 Kuryer xizmati</option>
                <option value="mail_service">📦 Pochta xizmati</option>
              </select>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Narx (UZS) *</label>
              <input type="number" name="priceKg" class="p-form-control"
                     min="0" required placeholder="16000">
            </div>

            <div class="col-sm-6">
              <label class="p-label">Muddat (kun) *</label>
              <input type="number" name="muddat" class="p-form-control"
                     min="1" value="1" required>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Mamlakat</label>
              <input type="text" name="forCountry" class="p-form-control"
                     value="uzbekistan" placeholder="uzbekistan">
            </div>

            <div class="col-sm-6">
              <label class="p-label">Bepul yetkazish dan (UZS)</label>
              <input type="number" name="freePriceFrom" class="p-form-control"
                     min="0" value="0">
            </div>

            <div class="col-sm-6">
              <label class="p-label d-flex align-items-center gap-2">
                <input type="hidden"   name="capital" value="0">
                <input type="checkbox" name="capital" value="1"
                       style="width:16px;height:16px">
                Toshkent uchun
              </label>
              <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
                Belgilanmasa — faqat viloyatlar
              </div>
            </div>

            <div class="col-sm-6">
              <label class="p-label d-flex align-items-center gap-2">
                <input type="hidden"   name="status" value="0">
                <input type="checkbox" name="status" value="1" checked
                       style="width:16px;height:16px">
                Faol holat
              </label>
            </div>

          </div>
          <div class="d-flex justify-content-end mt-3">
            <button class="btn-p"><i class="bi bi-plus-lg"></i> Qo'shish</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

{{-- ══ Edit Modal ═══════════════════════════════════════════════════ --}}
<div id="editDeliveryModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;
            align-items:center;justify-content:center">
  <div style="background:var(--p-card-bg,#fff);border-radius:16px;padding:28px;
              width:100%;max-width:520px;max-height:90vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(0,0,0,.2)">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div style="font-size:16px;font-weight:700">Xizmatni tahrirlash</div>
      <button onclick="closeEditDelivery()"
              style="background:none;border:none;font-size:20px;cursor:pointer;
                     color:var(--p-muted,#888)">&times;</button>
    </div>

    <form id="editDeliveryForm" method="POST">
      @csrf @method('PUT')
      <div class="row g-3">

        <div class="col-12">
          <label class="p-label">Xizmat nomi *</label>
          <input type="text" id="edit_name" name="name"
                 class="p-form-control" required>
        </div>

        <div class="col-12">
          <label class="p-label">Turi *</label>
          <select id="edit_type" name="type" class="p-form-control" required>
            <option value="courier_service">🚚 Kuryer xizmati</option>
            <option value="mail_service">📦 Pochta xizmati</option>
          </select>
        </div>

        <div class="col-sm-6">
          <label class="p-label">Narx (UZS) *</label>
          <input type="number" id="edit_priceKg" name="priceKg"
                 class="p-form-control" min="0" required>
        </div>

        <div class="col-sm-6">
          <label class="p-label">Muddat (kun) *</label>
          <input type="number" id="edit_muddat" name="muddat"
                 class="p-form-control" min="1" required>
        </div>

        <div class="col-sm-6">
          <label class="p-label">Mamlakat</label>
          <input type="text" id="edit_forCountry" name="forCountry"
                 class="p-form-control">
        </div>

        <div class="col-sm-6">
          <label class="p-label">Bepul yetkazish dan (UZS)</label>
          <input type="number" id="edit_freePriceFrom" name="freePriceFrom"
                 class="p-form-control" min="0">
        </div>

        <div class="col-sm-6">
          <label class="p-label d-flex align-items-center gap-2">
            <input type="hidden"   name="capital" value="0">
            <input type="checkbox" id="edit_capital" name="capital" value="1"
                   style="width:16px;height:16px">
            Toshkent uchun
          </label>
        </div>

        <div class="col-sm-6">
          <label class="p-label d-flex align-items-center gap-2">
            <input type="hidden"   name="status" value="0">
            <input type="checkbox" id="edit_status" name="status" value="1"
                   style="width:16px;height:16px">
            Faol holat
          </label>
        </div>

      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" onclick="closeEditDelivery()" class="btn-p ghost">
          Bekor qilish
        </button>
        <button type="submit" class="btn-p">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditDelivery(id, name, type, priceKg, muddat, forCountry, capital, freePriceFrom, status) {
  const base = "{{ url('panel/settings/delivery') }}";
  document.getElementById('editDeliveryForm').action = base + '/' + id;
  document.getElementById('edit_name').value         = name;
  document.getElementById('edit_type').value         = type;
  document.getElementById('edit_priceKg').value      = priceKg;
  document.getElementById('edit_muddat').value       = muddat;
  document.getElementById('edit_forCountry').value   = forCountry;
  document.getElementById('edit_freePriceFrom').value= freePriceFrom;
  document.getElementById('edit_capital').checked    = capital;
  document.getElementById('edit_status').checked     = status;
  document.getElementById('editDeliveryModal').style.display = 'flex';
}

function closeEditDelivery() {
  document.getElementById('editDeliveryModal').style.display = 'none';
}

// Modal tashqarisiga bosish — yopish
document.getElementById('editDeliveryModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditDelivery();
});
</script>
@endif