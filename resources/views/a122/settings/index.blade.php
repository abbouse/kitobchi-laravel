@extends('a122.layouts.admin')
@section('title', 'Sozlamalar')

@section('content')

@php $tab = request('tab', 'versions'); @endphp

<x-a122.page-header title="Sozlamalar" subtitle="Tizim konfiguratsiyasi va sozlamalari"/>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-4">
  @foreach([
    'versions'   => ['bi-phone','App versiyalar'],
    'contacts'   => ['bi-headset','Kontaktlar'],
    'app-flags'  => ['bi-toggles','App flaglar'],
    'telegram'   => ['bi-telegram','Telegram'],
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

{{-- ══ KONTAKTLAR ═══════════════════════════════════════════════════════ --}}
@if($tab === 'contacts')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-headset mr-2" style="color:var(--p-accent)"></i>Ilova kontaktlari</div>
          <div class="p-card-sub">Call center raqamlari va email manzillar</div>
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
          <div style="padding:16px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px">
            <div class="flex items-center gap-2 mb-3">
              <i class="bi {{ $icon }}" style="color:var(--p-{{ $clr }});font-size:16px"></i>
              <span style="font-size:13px;font-weight:700;color:var(--p-text)">{{ $appName }}</span>
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
        <div class="flex justify-end mt-4">
          <button type="submit" class="btn-p primary"><i class="bi bi-floppy-fill"></i> Saqlash</button>
        </div>
      </form>
    </div>
  </div>

  <div class="xl:col-span-5">
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi kontaktlar</div>
      </div>
      @foreach([
        ['Kitobchi telefon',  $project?->kitobchi_phone, 'accent',  'bi-telephone'],
        ['Kitobchi email',    $project?->kitobchi_email, 'accent',  'bi-envelope'],
        ['Business telefon',  $project?->business_phone, 'warning', 'bi-telephone'],
        ['Business email',    $project?->business_email, 'warning', 'bi-envelope'],
        ['Courier telefon',   $project?->courier_phone,  'info',    'bi-telephone'],
        ['Courier email',     $project?->courier_email,  'info',    'bi-envelope'],
      ] as [$lbl, $val, $clr, $ico])
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span class="flex items-center gap-2" style="font-size:13px;color:var(--p-muted)">
          <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i> {{ $lbl }}
        </span>
        <span style="font-size:12px;font-weight:600;color:var(--p-text);font-family:'JetBrains Mono',monospace">
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
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-toggles mr-2" style="color:var(--p-accent)"></i>App sozlamalari</div>
          <div class="p-card-sub">Global flaglar va qadoqlash narxi</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.app-flags') }}">
        @csrf @method('PUT')

        {{-- Flaglar --}}
        <div style="padding:16px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px;margin-bottom:16px">
          <div style="font-size:13px;font-weight:700;color:var(--p-text);margin-bottom:12px">
            <i class="bi bi-toggles mr-2" style="color:var(--p-accent)"></i>Global flaglar
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach([
              ['on_premium', 'Premium rejim (onPremium)', 'bi-star-fill', 'warning'],
              ['on_reels',   'Reels yoqilgan (onReels)',  'bi-play-circle-fill', 'info'],
              ['ramadan',    'Ramazon rejim (ramadan)',   'bi-moon-stars-fill', 'accent'],
              ['stop_sales', 'Savdo to\'xtatilgan (stopSales)', 'bi-slash-circle-fill', 'danger'],
            ] as [$field, $label, $ico, $clr])
            <label class="p-form-label flex items-center gap-2" style="cursor:pointer;padding:10px;border:1px solid var(--p-border);border-radius:8px">
              <input type="hidden" name="{{ $field }}" value="0">
              <input type="checkbox" name="{{ $field }}" value="1"
                     {{ $project?->{$field} ? 'checked' : '' }}
                     style="width:16px;height:16px;accent-color:var(--p-{{ $clr }})">
              <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i>
              <span style="font-size:13px">{{ $label }}</span>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Qadoqlash narxi --}}
        <div style="padding:16px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px">
          <div style="font-size:13px;font-weight:700;color:var(--p-text);margin-bottom:12px">
            <i class="bi bi-box-seam mr-2" style="color:var(--p-success)"></i>Qadoqlash narxi
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="p-form-label">Kichik narx (UZS) *</label>
              <input type="number" name="packaging_price_small" class="p-form-control" min="0" required
                     value="{{ old('packaging_price_small', $project?->packaging_price_small ?? 25000) }}"
                     placeholder="25000">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Chegara dan oz kitob uchun</div>
            </div>
            <div>
              <label class="p-form-label">Katta narx (UZS) *</label>
              <input type="number" name="packaging_price_large" class="p-form-control" min="0" required
                     value="{{ old('packaging_price_large', $project?->packaging_price_large ?? 40000) }}"
                     placeholder="40000">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Chegara va undan ko'p kitob uchun</div>
            </div>
            <div>
              <label class="p-form-label">Chegara (ta kitob) *</label>
              <input type="number" name="packaging_threshold" class="p-form-control" min="1" required
                     value="{{ old('packaging_threshold', $project?->packaging_threshold ?? 4) }}"
                     placeholder="4">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Bu va undan ko'p → katta narx</div>
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
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi holat</div>
      </div>
      @foreach([
        ['onPremium',    $project?->on_premium,  'warning', 'bi-star-fill'],
        ['onReels',      $project?->on_reels,    'info',    'bi-play-circle-fill'],
        ['ramadan',      $project?->ramadan,     'accent',  'bi-moon-stars-fill'],
        ['stopSales',    $project?->stop_sales,  'danger',  'bi-slash-circle-fill'],
      ] as [$lbl, $val, $clr, $ico])
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span class="flex items-center gap-2" style="font-size:13px;color:var(--p-muted)">
          <i class="bi {{ $ico }}" style="color:var(--p-{{ $clr }})"></i> {{ $lbl }}
        </span>
        @if($val)
          <span class="s-pill {{ $clr }}" style="font-size:11px"><i class="bi bi-check-lg"></i> Yoqilgan</span>
        @else
          <span class="s-pill muted" style="font-size:11px">O'chiq</span>
        @endif
      </div>
      @endforeach
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-box-seam mr-1"></i> Kichik qadoqlash</span>
        <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-success)">
          {{ number_format($project?->packaging_price_small ?? 25000) }} UZS
        </span>
      </div>
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-box-seam mr-1"></i> Katta qadoqlash</span>
        <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-success)">
          {{ number_format($project?->packaging_price_large ?? 40000) }} UZS
        </span>
      </div>
      <div class="flex items-center justify-between py-2">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-hash mr-1"></i> Chegara</span>
        <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
          {{ $project?->packaging_threshold ?? 4 }} ta kitob
        </span>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ══ TELEGRAM ══════════════════════════════════════════════════════════ --}}
@if($tab === 'telegram')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
  <div class="xl:col-span-7">
    <div class="p-card">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><i class="bi bi-telegram mr-2" style="color:#229ED9"></i>Telegram Login</div>
          <div class="p-card-sub">OIDC orqali Telegram autentifikatsiya sozlamalari</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.settings.telegram') }}">
        @csrf @method('PUT')

        <div style="padding:16px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px;margin-bottom:16px">
          <label class="p-form-label flex items-center gap-2" style="cursor:pointer;padding:10px;border:1px solid var(--p-border);border-radius:8px;margin-bottom:0">
            <input type="hidden" name="telegram_login_enabled" value="0">
            <input type="checkbox" name="telegram_login_enabled" value="1"
                   {{ $project?->telegram_login_enabled ? 'checked' : '' }}
                   style="width:16px;height:16px;accent-color:#229ED9">
            <i class="bi bi-power" style="color:#229ED9"></i>
            <span style="font-size:13px">Telegram login yoqilgan</span>
          </label>
        </div>

        <div style="padding:16px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px;margin-bottom:16px">
          <div style="font-size:13px;font-weight:700;color:var(--p-text);margin-bottom:12px">Sozlamalar</div>
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
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">iOS universal link. Custom scheme emas, aynan Telegram bergan HTTPS redirect bo'lishi kerak.</div>
            </div>
            <div>
              <label class="p-form-label">Android Redirect URI</label>
              <input type="text" name="telegram_redirect_uri_android" class="p-form-control"
                     value="{{ old('telegram_redirect_uri_android', $project?->telegram_redirect_uri_android ?? 'https://app2854400165-login.tg.dev/tglogin') }}"
                     placeholder="https://app2854400165-login.tg.dev/tglogin">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Android App Link. `kitobchi://telegram-auth` bu yer uchun noto'g'ri.</div>
            </div>
            <div>
              <label class="p-form-label">Scopes</label>
              <input type="text" name="telegram_scopes" class="p-form-control"
                     value="{{ old('telegram_scopes', $project?->telegram_scopes ?? 'openid profile phone') }}"
                     placeholder="openid profile phone">
            </div>
          </div>
        </div>

        <div style="font-size:11px;color:var(--p-hint);padding:10px 0">
          Maxfiy <code>client_secret</code> admin panelda saqlanmaydi. Uni server <code>.env</code> fayliga yozing: <code>TELEGRAM_LOGIN_CLIENT_SECRET=...</code>
        </div>
        <div style="font-size:11px;color:var(--p-muted);padding:0 0 10px 0">
          To'g'ri qiymatlar: iOS <code>https://app3206985527-login.tg.dev</code>, Android <code>https://app2854400165-login.tg.dev/tglogin</code>.
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
        <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-info)"></i>Hozirgi holat</div>
      </div>
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-power mr-1" style="color:#229ED9"></i> Holat</span>
        @if($project?->telegram_login_enabled)
          <span class="s-pill info" style="font-size:11px"><i class="bi bi-check-lg"></i> Yoqilgan</span>
        @else
          <span class="s-pill muted" style="font-size:11px">O'chiq</span>
        @endif
      </div>
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-key mr-1"></i> Client ID</span>
        <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
          {{ $project?->telegram_client_id ? substr($project->telegram_client_id, 0, 12).'...' : '—' }}
        </span>
      </div>
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-apple mr-1"></i> iOS URI</span>
        <span style="font-size:11px;color:var(--p-muted)">{{ $project?->telegram_redirect_uri_ios ? 'sozlangan' : 'default' }}</span>
      </div>
      <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--p-border)">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-android2 mr-1"></i> Android URI</span>
        <span style="font-size:11px;color:var(--p-muted)">{{ $project?->telegram_redirect_uri_android ? 'sozlangan' : 'default' }}</span>
      </div>
      <div class="flex items-center justify-between py-2">
        <span style="font-size:13px;color:var(--p-muted)"><i class="bi bi-shield-lock mr-1"></i> Scopes</span>
        <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
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
    <div class="p-card">
      <div class="p-card-header">
        <div class="p-card-title">Yangi qoida qo'shish</div>
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
