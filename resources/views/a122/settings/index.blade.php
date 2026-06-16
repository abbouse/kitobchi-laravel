@extends('a122.layouts.admin')
@section('title', 'Sozlamalar')

@push('styles')
<style>
  .settings-shell {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .settings-shell .kc-page-header {
    border-radius: 2rem;
    padding: 1.5rem 1.65rem;
  }

  .settings-tabs-bar {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .85rem;
    border-radius: 1.65rem;
    border: 1px solid var(--template-border);
    background: var(--template-card);
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
  }

  .settings-tabs-bar .nav-link {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    min-height: 2.85rem;
    padding: 0 1rem;
    border-radius: 1rem;
    color: var(--template-muted);
    font-size: .84rem;
    font-weight: 800;
    border: 1px solid transparent;
    transition: .18s ease;
  }

  .settings-tabs-bar .nav-link:hover {
    color: var(--template-text);
    background: color-mix(in srgb, var(--template-card) 72%, var(--template-bg));
    border-color: var(--template-border);
  }

  .settings-tabs-bar .nav-link.active {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    box-shadow: 0 14px 26px rgba(91,124,250,.22);
  }

  .settings-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, .8fr);
    gap: 1rem;
  }

  .settings-main-card,
  .settings-aside-card {
    border: 1px solid var(--template-border);
    border-radius: 14px;
    background: var(--template-card);
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
    overflow: hidden;
  }

  .settings-main-card .settings-card-header,
  .settings-aside-card .settings-card-header {
    padding: 1.35rem 1.5rem 0;
  }

  .settings-card-title {
    color: var(--template-text);
    font-size: .98rem;
    font-weight: 800;
  }

  .settings-card-sub {
    margin-top: .25rem;
    color: var(--template-muted);
    font-size: .82rem;
  }

  .settings-main-card form,
  .settings-aside-card > div:not(.settings-card-header) {
    padding: 0 1.5rem 1.5rem;
  }

  .settings-block {
    padding: 1.1rem;
    border-radius: 1.4rem;
    border: 1px solid var(--template-border);
    background: color-mix(in srgb, var(--template-card) 82%, var(--template-bg));
  }

  .settings-block + .settings-block {
    margin-top: .9rem;
  }

  .settings-block__head {
    display: flex;
    align-items: center;
    gap: .65rem;
    margin-bottom: .9rem;
    color: var(--template-text);
    font-size: .9rem;
    font-weight: 800;
  }

  .settings-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .9rem;
  }

  .settings-field-grid--three {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .settings-shell .p-form-label {
    margin-bottom: .5rem;
    color: #8c95a6 !important;
    font-size: .72rem !important;
    letter-spacing: .12em;
  }

  .settings-shell .p-form-control {
    min-height: 3.15rem;
    border-radius: 1rem !important;
    background: var(--template-card) !important;
  }

  .settings-shell textarea.p-form-control {
    min-height: 9rem;
  }

  .settings-savebar {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
  }

  .settings-shell .btn-p.primary {
    min-width: 10rem;
    min-height: 3rem;
    border-radius: 1rem !important;
    background: #5b7cfa !important;
    border-color: #5b7cfa !important;
    box-shadow: 0 14px 28px rgba(91,124,250,.24);
  }

  .settings-shell .kc-settings-row {
    padding-inline: 1.2rem;
    min-height: 4rem;
  }

  .settings-shell .kc-settings-row + .kc-settings-row {
    border-top: 1px solid var(--template-border);
  }

  .settings-shell .kc-settings-row:last-child {
    border-bottom: 0;
  }

  .settings-shell .kc-settings-value {
    font-size: .86rem;
  }

  .settings-shell .kc-settings-panel,
  .settings-shell .kc-settings-panel--compact {
    padding: 1rem;
    border-radius: 1.25rem;
    border: 1px solid var(--template-border);
    background: color-mix(in srgb, var(--template-card) 82%, var(--template-bg));
  }

  .settings-shell .kc-settings-check {
    min-height: 3.25rem;
    border-radius: 1rem;
    border-color: var(--template-border);
    background: var(--template-card);
  }

  @media (max-width: 1199px) {
    .settings-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767px) {
    .settings-main-card .settings-card-header,
    .settings-aside-card .settings-card-header,
    .settings-main-card form,
    .settings-aside-card > div:not(.settings-card-header) {
      padding-left: 1rem;
      padding-right: 1rem;
    }

    .settings-field-grid,
    .settings-field-grid--three {
      grid-template-columns: 1fr;
    }
  }
</style>
@endpush

@section('content')

@php $tab = request('tab', 'versions'); @endphp

<div class="settings-shell">
<x-admin.page-header
  eyebrow="System"
  title="Sozlamalar"
  subtitle="Tizim konfiguratsiyasi, app versiyalari va operatsion flaglar bitta toza boshqaruv yuzasida jamlangan."
/>

{{-- Tabs --}}
<div class="settings-tabs-bar">
  <div class="nav nav-pills flex-wrap">
    @foreach([
      'versions'   => ['bi-phone','App versiyalar'],
      'contacts'   => ['bi-headset','Kontaktlar'],
      'app-flags'  => ['bi-toggles','App flaglar'],
      'courier-bonus' => ['bi-bicycle','Kuryer bonus'],
      'telegram'   => ['bi-telegram','Telegram'],
      'commission' => ['bi-percent','Komissiya'],
      'cashback'   => ['bi-cash-stack','Cashback'],
    ] as $key => [$icon, $label])
    <a href="{{ route('admin.settings.index', ['tab'=>$key]) }}"
       class="nav-link {{ $tab===$key ? 'active' : '' }}">
      <i class="bi {{ $icon }}"></i> {{ $label }}
    </a>
    @endforeach
  </div>
</div>

{{-- ══ APP VERSIYALAR ══════════════════════════════════════════════════════ --}}
@if($tab === 'versions')
<div class="settings-grid">
  <div>
    <div class="card-panel settings-main-card">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-phone mr-2" style="color:var(--p-accent)"></i>App versiyalari</div>
          <div class="settings-card-sub">Minimum talab qilinadigan versiyalar</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.versions') }}">
        @csrf @method('PUT')
        <div class="settings-field-grid">
          @foreach([
            ['Kitobchi Business','business','bi-shop-window','warning'],
            ['Kuryer App','courier','bi-bicycle','info'],
            ['Market App','market','bi-bag','accent'],
          ] as [$appName, $key, $icon, $clr])
          <div>
            <div class="settings-block">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi {{ $icon }}" style="color:var(--p-{{ $clr }});font-size:15px"></i>
                <span class="fw-semibold small text-body">{{ $appName }}</span>
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
        <div class="settings-savebar">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="card-panel settings-aside-card">
      <div class="settings-card-header">
        <div class="settings-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi versiyalar</div>
      </div>
      @foreach([
        ['Kitobchi Business iOS', $project?->business_version_ios, 'warning'],
        ['Kitobchi Business Android', $project?->business_version_android, 'warning'],
        ['Kuryer iOS', $project?->courier_version_ios, 'info'],
        ['Kuryer Android', $project?->courier_version_android, 'info'],
        ['Market iOS', $project?->market_version_ios, 'accent'],
        ['Market Android', $project?->market_version_android, 'accent'],
      ] as [$lbl, $val, $clr])
      <div class="kc-settings-row">
        <span class="kc-settings-key">{{ $lbl }}</span>
        <span class="kc-settings-value kc-mono" style="color:var(--p-{{ $clr }})">
          {{ $val ?? '—' }}
        </span>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ══ KONTAKTLAR ═══════════════════════════════════════════════════════ --}}
@if($tab === 'contacts')
<div class="settings-grid">
  <div>
    <div class="card-panel settings-main-card">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-headset mr-2" style="color:var(--p-accent)"></i>Ilova kontaktlari</div>
          <div class="settings-card-sub">Call center raqamlari va email manzillar</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.contacts') }}">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-4">
          @foreach([
            ['Kitobchi Market', 'kitobchi', 'bi-bag', 'accent'],
            ['Kitobchi Business', 'business', 'bi-shop-window', 'warning'],
            ['Endi Courier', 'courier', 'bi-bicycle', 'info'],
          ] as [$appName, $key, $icon, $clr])
          <div class="settings-block">
            <div class="d-flex align-items-center gap-2 mb-3">
              <i class="bi {{ $icon }}" style="color:var(--p-{{ $clr }});font-size:16px"></i>
              <span class="fw-semibold small text-body">{{ $appName }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="p-form-label"><i class="bi bi-telephone mr-1"></i>Call center raqami</label>
                <input type="text" name="{{ $key }}_phone" class="p-form-control"
                       value="{{ old($key.'_phone', $project?->{$key.'_phone'}) }}"
                       placeholder="+998 XX XXX XX XX">
              </div>
              <div>
                <label class="p-form-label"><i class="bi bi-envelope mr-1"></i>Email manzil</label>
                <input type="email" name="{{ $key }}_email" class="p-form-control"
                       value="{{ old($key.'_email', $project?->{$key.'_email'}) }}"
                       placeholder="support@example.com">
              </div>
            </div>
          </div>
          @endforeach
        </div>
        <div class="settings-savebar">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="card-panel settings-aside-card">
      <div class="settings-card-header">
        <div class="settings-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi kontaktlar</div>
      </div>
      @foreach([
        ['Kitobchi telefon',  $project?->kitobchi_phone, 'accent',  'bi-telephone'],
        ['Kitobchi email',    $project?->kitobchi_email, 'accent',  'bi-envelope'],
        ['Business telefon',  $project?->business_phone, 'warning', 'bi-telephone'],
        ['Business email',    $project?->business_email, 'warning', 'bi-envelope'],
        ['Courier telefon',   $project?->courier_phone,  'info',    'bi-telephone'],
        ['Courier email',     $project?->courier_email,  'info',    'bi-envelope'],
      ] as [$lbl, $val, $clr, $ico])
      <div class="kc-settings-row">
        <span class="kc-settings-key">
          <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i> {{ $lbl }}
        </span>
        <span class="kc-settings-value kc-mono">
          {{ $val ?? '—' }}
        </span>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ══ APP FLAGLAR ══════════════════════════════════════════════════════ --}}
@if($tab === 'app-flags')
<div class="settings-grid">
  <div>
    <div class="card-panel settings-main-card">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-toggles mr-2" style="color:var(--p-accent)"></i>App sozlamalari</div>
          <div class="settings-card-sub">Global flaglar va qadoqlash narxi</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.app-flags') }}">
        @csrf @method('PUT')

        {{-- Flaglar --}}
        <div class="settings-block mb-3">
          <div class="settings-block__head">
            <i class="bi bi-toggles mr-2" style="color:var(--p-accent)"></i>Global flaglar
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach([
              ['on_premium', 'Premium rejim (onPremium)', 'bi-star-fill', 'warning'],
              ['on_reels',   'Reels yoqilgan (onReels)',  'bi-play-circle-fill', 'info'],
              ['ramadan',    'Ramazon rejim (ramadan)',   'bi-moon-stars-fill', 'accent'],
              ['stop_sales', 'Savdo to\'xtatilgan (stopSales)', 'bi-slash-circle-fill', 'danger'],
            ] as [$field, $label, $ico, $clr])
            <label class="kc-settings-check">
              <input type="hidden" name="{{ $field }}" value="0">
              <input type="checkbox" name="{{ $field }}" value="1"
                     {{ $project?->{$field} ? 'checked' : '' }}
                     style="width:16px;height:16px;accent-color:var(--p-{{ $clr }})">
              <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i>
              <span class="small">{{ $label }}</span>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Qadoqlash narxi --}}
        <div class="settings-block">
          <div class="settings-block__head">
            <i class="bi bi-box-seam mr-2" style="color:var(--p-success)"></i>Qadoqlash narxi
          </div>
          <div class="settings-field-grid settings-field-grid--three">
            <div>
              <label class="p-form-label">Kichik narx (UZS) *</label>
              <input type="number" name="packaging_price_small" class="p-form-control" min="0" required
                     value="{{ old('packaging_price_small', $project?->packaging_price_small ?? 25000) }}"
                     placeholder="25000">
              <div class="kc-settings-note">Chegara dan oz kitob uchun</div>
            </div>
            <div>
              <label class="p-form-label">Katta narx (UZS) *</label>
              <input type="number" name="packaging_price_large" class="p-form-control" min="0" required
                     value="{{ old('packaging_price_large', $project?->packaging_price_large ?? 40000) }}"
                     placeholder="40000">
              <div class="kc-settings-note">Chegara va undan ko'p kitob uchun</div>
            </div>
            <div>
              <label class="p-form-label">Chegara (ta kitob) *</label>
              <input type="number" name="packaging_threshold" class="p-form-control" min="1" required
                     value="{{ old('packaging_threshold', $project?->packaging_threshold ?? 4) }}"
                     placeholder="4">
              <div class="kc-settings-note">Bu va undan ko'p → katta narx</div>
            </div>
          </div>
        </div>

        <div class="settings-savebar">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="card-panel settings-aside-card">
      <div class="settings-card-header">
        <div class="settings-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi holat</div>
      </div>
      @foreach([
        ['onPremium',    $project?->on_premium,  'warning', 'bi-star-fill'],
        ['onReels',      $project?->on_reels,    'info',    'bi-play-circle-fill'],
        ['ramadan',      $project?->ramadan,     'accent',  'bi-moon-stars-fill'],
        ['stopSales',    $project?->stop_sales,  'danger',  'bi-slash-circle-fill'],
      ] as [$lbl, $val, $clr, $ico])
      <div class="kc-settings-row">
        <span class="kc-settings-key">
          <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i> {{ $lbl }}
        </span>
        @if($val)
          <span class="s-pill {{ $clr }}" style="font-size:11px"><i class="bi bi-check-lg"></i> Yoqilgan</span>
        @else
          <span class="s-pill muted" style="font-size:11px">O'chiq</span>
        @endif
      </div>
      @endforeach
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-box-seam mr-1"></i> Kichik qadoqlash</span>
        <span class="kc-settings-value kc-mono" style="color:var(--p-success)">
          {{ number_format($project?->packaging_price_small ?? 25000) }} UZS
        </span>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-box-seam mr-1"></i> Katta qadoqlash</span>
        <span class="kc-settings-value kc-mono" style="color:var(--p-success)">
          {{ number_format($project?->packaging_price_large ?? 40000) }} UZS
        </span>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-hash mr-1"></i> Chegara</span>
        <span class="kc-settings-value kc-mono">
          {{ $project?->packaging_threshold ?? 4 }} ta kitob
        </span>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ══ KURYER BONUS ═══════════════════════════════════════════════════ --}}
@if($tab === 'courier-bonus')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="card-panel">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-bicycle mr-2" style="color:var(--p-info)"></i>Kuryer km va bonus tizimi</div>
          <div class="settings-card-sub">Bazaviy haq, km narxi va masofa oralig'iga qarab bonuslar.</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.courier-bonus') }}">
        @csrf @method('PUT')
        <div class="kc-settings-panel">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="p-form-label">Bazaviy haq</label>
              <input type="number" name="courier_base_fee" class="p-form-control" min="0"
                     value="{{ old('courier_base_fee', $project?->courier_base_fee ?? 3000) }}">
            </div>
            <div>
              <label class="p-form-label">1 km narxi</label>
              <input type="number" name="courier_price_per_km" class="p-form-control" min="0"
                     value="{{ old('courier_price_per_km', $project?->courier_price_per_km ?? 1500) }}">
            </div>
            <div>
              <label class="p-form-label">Minimal payout</label>
              <input type="number" name="courier_min_fee" class="p-form-control" min="0"
                     value="{{ old('courier_min_fee', $project?->courier_min_fee ?? 5000) }}">
            </div>
            <div class="md:col-span-2">
              <label class="p-form-label">Masofa bonuslari</label>
              @php $bonusRules = old('courier_bonus_rules', $project?->courier_bonus_rules ?? []); @endphp
              @for($i = 0; $i < 5; $i++)
                @php $rule = $bonusRules[$i] ?? []; @endphp
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <input type="number" step="0.1" name="courier_bonus_rules[{{ $i }}][from_km]" class="p-form-control" min="0" placeholder="Dan km" value="{{ $rule['from_km'] ?? '' }}">
                  <input type="number" step="0.1" name="courier_bonus_rules[{{ $i }}][to_km]" class="p-form-control" min="0" placeholder="Gacha km" value="{{ $rule['to_km'] ?? '' }}">
                  <input type="number" name="courier_bonus_rules[{{ $i }}][bonus_amount]" class="p-form-control" min="0" placeholder="Bonus so'm" value="{{ $rule['bonus_amount'] ?? '' }}">
                </div>
              @endfor
            </div>
          </div>
        </div>
        <div class="flex justify-end mt-4">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div class="xl:col-span-5">
    <div class="card-panel">
      <div class="settings-card-header">
        <div class="settings-card-title"><i class="bi bi-activity mr-2" style="color:var(--p-warning)"></i>Joriy konfiguratsiya</div>
      </div>
      @foreach([
        ['Bazaviy haq', $project?->courier_base_fee ?? 3000, 'so\'m'],
        ['1 km narxi', $project?->courier_price_per_km ?? 1500, 'so\'m'],
        ['Minimal payout', $project?->courier_min_fee ?? 5000, 'so\'m'],
      ] as [$lbl, $val, $suffix])
      <div class="kc-settings-row">
        <span class="kc-settings-key">{{ $lbl }}</span>
        <span class="kc-settings-value kc-mono">
          {{ number_format((int) $val) }} {{ $suffix }}
        </span>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ══ TELEGRAM ══════════════════════════════════════════════════════════ --}}
@if($tab === 'telegram')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="card-panel">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-telegram mr-2" style="color:#229ED9"></i>Telegram Login</div>
          <div class="settings-card-sub">OIDC orqali Telegram autentifikatsiya sozlamalari</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.telegram') }}">
        @csrf @method('PUT')

        <div class="kc-settings-panel mb-3">
          <label class="kc-settings-check">
            <input type="hidden" name="telegram_login_enabled" value="0">
            <input type="checkbox" name="telegram_login_enabled" value="1"
                   {{ $project?->telegram_login_enabled ? 'checked' : '' }}
                   style="width:16px;height:16px;accent-color:#229ED9">
            <i class="bi bi-power" style="color:#229ED9"></i>
            <span class="small">Telegram login yoqilgan</span>
          </label>
        </div>

        <div class="kc-settings-panel mb-3">
          <div class="kc-settings-block-title">Sozlamalar</div>
          <div class="grid grid-cols-1 gap-3">
            <div>
              <label class="p-form-label">Client ID</label>
              <input type="text" name="telegram_client_id" class="p-form-control"
                     value="{{ old('telegram_client_id', $project?->telegram_client_id) }}"
                     placeholder="BotFather client id">
            </div>
            <div>
              <label class="p-form-label">iOS Redirect URI</label>
              <input type="text" name="telegram_redirect_uri_ios" class="p-form-control"
                     value="{{ old('telegram_redirect_uri_ios', $project?->telegram_redirect_uri_ios ?? 'https://app3206985527-login.tg.dev') }}"
                     placeholder="https://app3206985527-login.tg.dev">
              <div class="kc-settings-note">iOS uchun Telegram bergan <code>https://app...-login.tg.dev</code> redirect ishlatiladi. Xcode'da Associated Domains ham shu host bilan mos bo'lishi shart.</div>
            </div>
            <div>
              <label class="p-form-label">Android Redirect URI</label>
              <input type="text" name="telegram_redirect_uri_android" class="p-form-control"
                     value="{{ old('telegram_redirect_uri_android', $project?->telegram_redirect_uri_android ?? 'https://app2854400165-login.tg.dev/tglogin') }}"
                     placeholder="https://app2854400165-login.tg.dev/tglogin">
              <div class="kc-settings-note">Android App Link hosti AndroidManifest bilan aynan bir xil bo'lishi kerak.</div>
            </div>
            <div>
              <label class="p-form-label">Scopes</label>
              <input type="text" name="telegram_scopes" class="p-form-control"
                     value="{{ old('telegram_scopes', $project?->telegram_scopes ?? 'openid profile phone') }}"
                     placeholder="openid profile phone">
            </div>
          </div>
        </div>

        <div class="kc-settings-note py-2">
          Maxfiy <code>client_secret</code> admin panelda saqlanmaydi. Uni server <code>.env</code> fayliga yozing: <code>TELEGRAM_LOGIN_CLIENT_SECRET=...</code>
        </div>
        <div class="kc-settings-note pb-2">
          To'g'ri qiymatlar: iOS <code>https://app3206985527-login.tg.dev</code>, Android <code>https://app2854400165-login.tg.dev/tglogin</code>.
        </div>

        <div class="flex justify-end mt-4">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div class="xl:col-span-5">
    <div class="card-panel">
      <div class="settings-card-header">
        <div class="settings-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi holat</div>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-power mr-1" style="color:#229ED9"></i> Holat</span>
        @if($project?->telegram_login_enabled)
          <span class="s-pill info" style="font-size:11px"><i class="bi bi-check-lg"></i> Yoqilgan</span>
        @else
          <span class="s-pill muted" style="font-size:11px">O'chiq</span>
        @endif
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-key mr-1"></i> Client ID</span>
        <span class="kc-settings-value kc-mono">
          {{ $project?->telegram_client_id ? substr($project->telegram_client_id, 0, 12).'...' : '—' }}
        </span>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-apple mr-1"></i> iOS URI</span>
        <span class="kc-settings-value">{{ $project?->telegram_redirect_uri_ios ? 'sozlangan' : 'default' }}</span>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-android2 mr-1"></i> Android URI</span>
        <span class="kc-settings-value">{{ $project?->telegram_redirect_uri_android ? 'sozlangan' : 'default' }}</span>
      </div>
      <div class="kc-settings-row">
        <span class="kc-settings-key"><i class="bi bi-shield-lock mr-1"></i> Scopes</span>
        <span class="kc-settings-value kc-mono">
          {{ $project?->telegram_scopes ?? 'openid profile phone' }}
        </span>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ══ KOMISSIYA ════════════════════════════════════════════════════════ --}}
@if($tab === 'commission')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

  <div class="xl:col-span-8">
    <div class="card-panel">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-percent mr-2" style="color:var(--p-accent)"></i>Komissiya qoidalari</div>
          <div class="settings-card-sub">Narx oralig'iga qarab seller komissiyasi</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="table data-table align-middle mb-0" data-index-grid>
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
    <div class="card-panel">
      <div class="settings-card-header">
        <div class="settings-card-title">Yangi qoida qo'shish</div>
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
    <div class="card-panel">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-cash-stack mr-2" style="color:var(--p-success)"></i>Cashback qoidalari</div>
          <div class="settings-card-sub">Xarid summasiga qarab cashback foizi</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="table data-table align-middle mb-0" data-index-grid>
          <thead>
            <tr>
              <th>Tur</th>
              <th>Xarid dan (UZS)</th>
              <th>Xarid gacha (UZS)</th>
              <th>Cashback %</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($cashback as $cb)
            @php $isPickup = ($cb->type ?? 'delivery') === 'pickup'; @endphp
            <tr>
              <td>
                @if($isPickup)
                  <span class="s-pill" style="background:#FFE4B3;color:#A66200;font-size:11px;font-weight:700">
                    <i class="bi bi-shop"></i> Do'kondan (pickup)
                  </span>
                @else
                  <span class="s-pill" style="background:#E0F2FF;color:#0d4a82;font-size:11px;font-weight:700">
                    <i class="bi bi-truck"></i> Yetkazib berish
                  </span>
                @endif
              </td>
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
                          onclick="openEditCashback({{ $cb->id }},{{ $cb->fromUzs }},{{ $cb->toUzs }},{{ $cb->cashback }},'{{ $cb->type ?? 'delivery' }}')"
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
              <td colspan="5" style="text-align:center;padding:30px;color:var(--p-hint)">Cashback qoidalari yo'q</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="xl:col-span-4">
    <div class="card-panel">
      <div class="settings-card-header">
        <div class="settings-card-title">Yangi qoida qo'shish</div>
      </div>
      <form method="POST" action="{{ route('admin.settings.cashback.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="md:col-span-2">
            <label class="p-form-label">Buyurtma turi *</label>
            <select name="type" class="p-form-control" required>
              <option value="delivery">Yetkazib berish (delivery)</option>
              <option value="pickup">Do'kondan olib ketish (pickup) — Kitob OL!</option>
            </select>
          </div>
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
        <div class="md:col-span-2">
          <label class="p-form-label">Buyurtma turi</label>
          <select id="ecb_type" name="type" class="p-form-control" required>
            <option value="delivery">Yetkazib berish (delivery)</option>
            <option value="pickup">Do'kondan olib ketish (pickup)</option>
          </select>
        </div>
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
function openEditCashback(id, from, to, cb, type) {
  document.getElementById('editCashbackForm').action = "{{ url('a122/settings/cashback') }}/" + id;
  document.getElementById('ecb_fromUzs').value  = from;
  document.getElementById('ecb_toUzs').value    = to;
  document.getElementById('ecb_cashback').value = cb;
  document.getElementById('ecb_type').value     = type || 'delivery';
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
    <div class="card-panel">
      <div class="settings-card-header">
        <div>
          <div class="settings-card-title"><i class="bi bi-truck mr-2" style="color:var(--p-info)"></i>Yetkazish xizmatlari</div>
          <div class="settings-card-sub">{{ $delivery->count() }} ta xizmat</div>
        </div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="table data-table align-middle mb-0" data-index-grid>
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
    <div class="card-panel">
      <div class="settings-card-header">
        <div class="settings-card-title">Yangi xizmat qo'shish</div>
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
  document.getElementById('editDeliveryForm').action = "{{ url('a122/settings/delivery') }}/" + id;
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

</div>
@endsection
