@extends('a122.layouts.admin')
@section('title', 'Sozlamalar')

@section('content')

@php $tab = request('tab', 'versions'); @endphp

<x-a122.page-header title="Sozlamalar" subtitle="Tizim konfiguratsiyasi va sozlamalari"/>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-4">
  @foreach([
    'versions'   => ['bi-phone','App versiyalar'],
    'commission' => ['bi-percent','Komissiya'],
    'cashback'   => ['bi-cash-stack','Cashback'],
    'delivery'   => ['bi-truck','Yetkazish'],
  ] as $key => [$icon, $label])
  <a href="{{ route('admin.settings.index', ['tab'=>$key]) }}"
     class="tab-pill {{ $tab===$key ? 'active' : '' }}">
    <i class="bi {{ $icon }}"></i> {{ $label }}
  </a>
  @endforeach
</div>

{{-- ══ APP VERSIYALAR ══════════════════════════════════════════════════════ --}}
@if($tab === 'versions')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-phone mr-2" style="color:var(--p-accent)"></i>App versiyalari</div>
          <div class="p-card-sub">Minimum talab qilinadigan versiyalar</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.versions') }}">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach([
            ['Kitobchi Business','business','bi-shop-window','warning'],
            ['Kuryer App','courier','bi-bicycle','info'],
            ['Market App','market','bi-bag','accent'],
          ] as [$appName, $key, $icon, $clr])
          <div>
            <div style="padding:14px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px">
              <div class="flex items-center gap-2" style="margin-bottom:10px">
                <i class="bi {{ $icon }}" style="color:var(--p-{{ $clr }});font-size:15px"></i>
                <span style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $appName }}</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div>
                  <label class="p-form-label">iOS versiya</label>
                  <input type="text" name="{{ $key }}_version_ios" class="p-form-control"
                         value="{{ old($key.'_version_ios', $project?->{$key.'_version_ios'}) }}"
                         placeholder="1.0.0" required>
                </div>
                <div>
                  <label class="p-form-label">Android versiya</label>
                  <input type="text" name="{{ $key }}_version_android" class="p-form-control"
                         value="{{ old($key.'_version_android', $project?->{$key.'_version_android'}) }}"
                         placeholder="1.0.0" required>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>
        <div class="flex justify-end mt-4">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div class="xl:col-span-5">
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi versiyalar</div>
      </div>
      @foreach([
        ['Kitobchi Business iOS', $project?->business_version_ios, 'warning'],
        ['Kitobchi Business Android', $project?->business_version_android, 'warning'],
        ['Kuryer iOS', $project?->courier_version_ios, 'info'],
        ['Kuryer Android', $project?->courier_version_android, 'info'],
        ['Market iOS', $project?->market_version_ios, 'accent'],
        ['Market Android', $project?->market_version_android, 'accent'],
      ] as [$lbl, $val, $clr])
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)">{{ $lbl }}</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--p-{{ $clr }})">
          {{ $val ?? '—' }}
        </span>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ══ KOMISSIYA ════════════════════════════════════════════════════════ --}}
@if($tab === 'commission')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

  <div class="xl:col-span-8">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-percent mr-2" style="color:var(--p-accent)"></i>Komissiya qoidalari</div>
          <div class="p-card-sub">Narx oralig'iga qarab seller komissiyasi</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
          <thead>
            <tr>
              <th>Narx dan (UZS)</th>
              <th>Narx gacha (UZS)</th>
              <th>Komissiya %</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($commission as $c)
            <tr>
              <td style="font-family:'JetBrains Mono',monospace">{{ number_format($c->priceFrom) }}</td>
              <td style="font-family:'JetBrains Mono',monospace">{{ number_format($c->priceTo) }}</td>
              <td>
                <span class="s-pill accent" style="font-size:12px;font-family:'JetBrains Mono',monospace">
                  {{ $c->percent }}%
                </span>
              </td>
              <td>
                <div class="flex gap-1 justify-end">
                  <button class="btn-p ghost sm"
                          onclick="openEditCommission({{ $c->id }},{{ $c->priceFrom }},{{ $c->priceTo }},{{ $c->percent }})"
                          title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.settings.commission.destroy', $c) }}"
                        onsubmit="return confirm('O\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align:center;padding:30px;color:var(--p-hint)">Komissiya qoidalari yo'q</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="xl:col-span-4">
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title">Yangi qoida qo'shish</div>
      </div>
      <form method="POST" action="{{ route('admin.settings.commission.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="p-form-label">Narx dan (UZS) *</label>
            <input type="number" name="priceFrom" class="p-form-control" min="0" required placeholder="0">
          </div>
          <div>
            <label class="p-form-label">Narx gacha (UZS) *</label>
            <input type="number" name="priceTo" class="p-form-control" min="1" required placeholder="100000">
          </div>
          <div>
            <label class="p-form-label">Komissiya % *</label>
            <input type="number" name="percent" class="p-form-control" min="0" max="100" required placeholder="10">
          </div>
        </div>
        <div class="flex justify-end mt-3">
          <button type="submit" class="btn-p primary"><i class="bi bi-plus-lg"></i> Qo'shish</button>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- Edit Commission Modal --}}
<div id="editCommissionModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border:1px solid var(--p-border2);border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:var(--p-shadow)">
    <div class="flex items-center justify-between mb-4">
      <div style="font-size:15px;font-weight:700;color:var(--p-text)">Komissiyani tahrirlash</div>
      <button onclick="document.getElementById('editCommissionModal').style.display='none'"
              style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--p-hint)">&times;</button>
    </div>
    <form id="editCommissionForm" method="POST">
      @csrf @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="p-form-label">Narx dan</label>
          <input type="number" id="ec_priceFrom" name="priceFrom" class="p-form-control" min="0" required>
        </div>
        <div>
          <label class="p-form-label">Narx gacha</label>
          <input type="number" id="ec_priceTo" name="priceTo" class="p-form-control" min="1" required>
        </div>
        <div>
          <label class="p-form-label">Komissiya %</label>
          <input type="number" id="ec_percent" name="percent" class="p-form-control" min="0" max="100" required>
        </div>
      </div>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" class="btn-p ghost"
                onclick="document.getElementById('editCommissionModal').style.display='none'">Bekor qilish</button>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEditCommission(id, from, to, pct) {
  document.getElementById('editCommissionForm').action = "{{ url('a122/settings/commission') }}/" + id;
  document.getElementById('ec_priceFrom').value = from;
  document.getElementById('ec_priceTo').value   = to;
  document.getElementById('ec_percent').value   = pct;
  document.getElementById('editCommissionModal').style.display = 'flex';
}
document.getElementById('editCommissionModal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
@endif

{{-- ══ CASHBACK ════════════════════════════════════════════════════════ --}}
@if($tab === 'cashback')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

  <div class="xl:col-span-8">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-cash-stack mr-2" style="color:var(--p-success)"></i>Cashback qoidalari</div>
          <div class="p-card-sub">Xarid summasiga qarab cashback foizi</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
          <thead>
            <tr>
              <th>Xarid dan (UZS)</th>
              <th>Xarid gacha (UZS)</th>
              <th>Cashback %</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($cashback as $cb)
            <tr>
              <td style="font-family:'JetBrains Mono',monospace">{{ number_format($cb->fromUzs) }}</td>
              <td style="font-family:'JetBrains Mono',monospace">{{ number_format($cb->toUzs) }}</td>
              <td>
                <span class="s-pill success" style="font-size:12px;font-family:'JetBrains Mono',monospace">
                  {{ $cb->cashback }}%
                </span>
              </td>
              <td>
                <div class="flex gap-1 justify-end">
                  <button class="btn-p ghost sm"
                          onclick="openEditCashback({{ $cb->id }},{{ $cb->fromUzs }},{{ $cb->toUzs }},{{ $cb->cashback }})"
                          title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.settings.cashback.destroy', $cb) }}"
                        onsubmit="return confirm('O\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align:center;padding:30px;color:var(--p-hint)">Cashback qoidalari yo'q</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="xl:col-span-4">
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title">Yangi qoida qo'shish</div>
      </div>
      <form method="POST" action="{{ route('admin.settings.cashback.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="p-form-label">Xarid dan (UZS) *</label>
            <input type="number" name="fromUzs" class="p-form-control" min="0" required placeholder="0">
          </div>
          <div>
            <label class="p-form-label">Xarid gacha (UZS) *</label>
            <input type="number" name="toUzs" class="p-form-control" min="1" required placeholder="500000">
          </div>
          <div>
            <label class="p-form-label">Cashback % *</label>
            <input type="number" name="cashback" class="p-form-control" min="0" max="100" required placeholder="5">
          </div>
        </div>
        <div class="flex justify-end mt-3">
          <button type="submit" class="btn-p primary"><i class="bi bi-plus-lg"></i> Qo'shish</button>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- Edit Cashback Modal --}}
<div id="editCashbackModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border:1px solid var(--p-border2);border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:var(--p-shadow)">
    <div class="flex items-center justify-between mb-4">
      <div style="font-size:15px;font-weight:700;color:var(--p-text)">Cashbackni tahrirlash</div>
      <button onclick="document.getElementById('editCashbackModal').style.display='none'"
              style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--p-hint)">&times;</button>
    </div>
    <form id="editCashbackForm" method="POST">
      @csrf @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="p-form-label">Xarid dan</label>
          <input type="number" id="ecb_fromUzs" name="fromUzs" class="p-form-control" min="0" required>
        </div>
        <div>
          <label class="p-form-label">Xarid gacha</label>
          <input type="number" id="ecb_toUzs" name="toUzs" class="p-form-control" min="1" required>
        </div>
        <div>
          <label class="p-form-label">Cashback %</label>
          <input type="number" id="ecb_cashback" name="cashback" class="p-form-control" min="0" max="100" required>
        </div>
      </div>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" class="btn-p ghost"
                onclick="document.getElementById('editCashbackModal').style.display='none'">Bekor qilish</button>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEditCashback(id, from, to, cb) {
  document.getElementById('editCashbackForm').action = "{{ url('a122/settings/cashback') }}/" + id;
  document.getElementById('ecb_fromUzs').value  = from;
  document.getElementById('ecb_toUzs').value    = to;
  document.getElementById('ecb_cashback').value = cb;
  document.getElementById('editCashbackModal').style.display = 'flex';
}
document.getElementById('editCashbackModal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
@endif

{{-- ══ YETKAZISH XIZMATLARI ═════════════════════════════════════════════ --}}
@if($tab === 'delivery')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

  <div class="xl:col-span-7">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-truck mr-2" style="color:var(--p-info)"></i>Yetkazish xizmatlari</div>
          <div class="p-card-sub">{{ $delivery->count() }} ta xizmat</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
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
                  <i class="bi {{ $d->type === 'courier_service' ? 'bi-truck' : 'bi-send' }} mr-1"></i>
                  {{ $d->type === 'courier_service' ? 'Kuryer' : 'Pochta' }}
                </span>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:13px">{{ number_format($d->priceKg) }}</td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px">{{ $d->muddat }} kun</td>
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
                <form method="POST" action="{{ route('admin.settings.delivery.update', $d) }}" style="display:inline">
                  @csrf @method('PUT')
                  <input type="hidden" name="name"          value="{{ $d->name }}">
                  <input type="hidden" name="type"          value="{{ $d->type }}">
                  <input type="hidden" name="priceKg"       value="{{ $d->priceKg }}">
                  <input type="hidden" name="muddat"        value="{{ $d->muddat }}">
                  <input type="hidden" name="forCountry"    value="{{ $d->forCountry }}">
                  <input type="hidden" name="capital"       value="{{ $d->capital ? '1' : '0' }}">
                  <input type="hidden" name="freePriceFrom" value="{{ $d->freePriceFrom }}">
                  <input type="hidden" name="status"        value="{{ $d->status ? '0' : '1' }}">
                  <button type="submit" class="btn-p {{ $d->status ? 'success' : 'ghost' }} sm">
                    <i class="bi {{ $d->status ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                  </button>
                </form>
              </td>
              <td>
                <div class="flex gap-1">
                  <button class="btn-p ghost sm"
                          onclick="openEditDelivery({{ $d->id }},'{{ addslashes($d->name) }}','{{ $d->type }}',{{ $d->priceKg }},{{ $d->muddat }},'{{ $d->forCountry }}',{{ $d->capital?'true':'false' }},{{ $d->freePriceFrom }},{{ $d->status?'true':'false' }})">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.settings.delivery.destroy', $d) }}"
                        onsubmit="return confirm('O\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" style="text-align:center;padding:30px;color:var(--p-hint)">Xizmatlar yo'q</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="xl:col-span-5">
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title">Yangi xizmat qo'shish</div>
      </div>
      <form method="POST" action="{{ route('admin.settings.delivery.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="p-form-label">Xizmat nomi *</label>
            <input type="text" name="name" class="p-form-control" required placeholder="Kuryer yetkazish">
          </div>
          <div>
            <label class="p-form-label">Turi *</label>
            <select name="type" class="p-form-control" required>
              <option value="courier_service">Kuryer xizmati</option>
              <option value="mail_service">Pochta xizmati</option>
            </select>
          </div>
          <div>
            <label class="p-form-label">Narx (UZS) *</label>
            <input type="number" name="priceKg" class="p-form-control" min="0" required placeholder="16000">
          </div>
          <div>
            <label class="p-form-label">Muddat (kun) *</label>
            <input type="number" name="muddat" class="p-form-control" min="1" value="1" required>
          </div>
          <div>
            <label class="p-form-label">Mamlakat</label>
            <input type="text" name="forCountry" class="p-form-control" value="uzbekistan" placeholder="uzbekistan">
          </div>
          <div>
            <label class="p-form-label">Bepul dan (UZS)</label>
            <input type="number" name="freePriceFrom" class="p-form-control" min="0" value="0">
          </div>
          <div>
            <label class="p-form-label flex items-center gap-2" style="cursor:pointer">
              <input type="hidden" name="capital" value="0">
              <input type="checkbox" name="capital" value="1"
                     style="width:16px;height:16px;accent-color:var(--p-accent)">
              Toshkent uchun
            </label>
          </div>
          <div>
            <label class="p-form-label flex items-center gap-2" style="cursor:pointer">
              <input type="hidden" name="status" value="0">
              <input type="checkbox" name="status" value="1" checked
                     style="width:16px;height:16px;accent-color:var(--p-success)">
              Faol holat
            </label>
          </div>
        </div>
        <div class="flex justify-end mt-3">
          <button type="submit" class="btn-p primary"><i class="bi bi-plus-lg"></i> Qo'shish</button>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- Edit Delivery Modal --}}
<div id="editDeliveryModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border:1px solid var(--p-border2);border-radius:16px;
              padding:28px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:var(--p-shadow)">
    <div class="flex items-center justify-between mb-4">
      <div style="font-size:15px;font-weight:700;color:var(--p-text)">Xizmatni tahrirlash</div>
      <button onclick="document.getElementById('editDeliveryModal').style.display='none'"
              style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--p-hint)">&times;</button>
    </div>
    <form id="editDeliveryForm" method="POST">
      @csrf @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="p-form-label">Xizmat nomi *</label>
          <input type="text" id="ed_name" name="name" class="p-form-control" required>
        </div>
        <div>
          <label class="p-form-label">Turi *</label>
          <select id="ed_type" name="type" class="p-form-control" required>
            <option value="courier_service">Kuryer xizmati</option>
            <option value="mail_service">Pochta xizmati</option>
          </select>
        </div>
        <div>
          <label class="p-form-label">Narx (UZS) *</label>
          <input type="number" id="ed_priceKg" name="priceKg" class="p-form-control" min="0" required>
        </div>
        <div>
          <label class="p-form-label">Muddat (kun) *</label>
          <input type="number" id="ed_muddat" name="muddat" class="p-form-control" min="1" required>
        </div>
        <div>
          <label class="p-form-label">Mamlakat</label>
          <input type="text" id="ed_forCountry" name="forCountry" class="p-form-control">
        </div>
        <div>
          <label class="p-form-label">Bepul dan (UZS)</label>
          <input type="number" id="ed_freePriceFrom" name="freePriceFrom" class="p-form-control" min="0">
        </div>
        <div>
          <label class="p-form-label flex items-center gap-2" style="cursor:pointer">
            <input type="hidden" name="capital" value="0">
            <input type="checkbox" id="ed_capital" name="capital" value="1"
                   style="width:16px;height:16px;accent-color:var(--p-accent)">
            Toshkent uchun
          </label>
        </div>
        <div>
          <label class="p-form-label flex items-center gap-2" style="cursor:pointer">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" id="ed_status" name="status" value="1"
                   style="width:16px;height:16px;accent-color:var(--p-success)">
            Faol holat
          </label>
        </div>
      </div>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" class="btn-p ghost"
                onclick="document.getElementById('editDeliveryModal').style.display='none'">Bekor qilish</button>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEditDelivery(id, name, type, priceKg, muddat, forCountry, capital, freePriceFrom, status) {
  document.getElementById('editDeliveryForm').action = "{{ url('panel/settings/delivery') }}/" + id;
  document.getElementById('ed_name').value          = name;
  document.getElementById('ed_type').value          = type;
  document.getElementById('ed_priceKg').value       = priceKg;
  document.getElementById('ed_muddat').value        = muddat;
  document.getElementById('ed_forCountry').value    = forCountry;
  document.getElementById('ed_freePriceFrom').value = freePriceFrom;
  document.getElementById('ed_capital').checked     = capital;
  document.getElementById('ed_status').checked      = status;
  document.getElementById('editDeliveryModal').style.display = 'flex';
}
document.getElementById('editDeliveryModal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
@endif

@endsection
