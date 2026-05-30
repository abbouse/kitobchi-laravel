@extends('a122.layouts.admin')
@section('title', 'Logistika')
@section('page-title', 'Logistika boshqaruvi')

@section('content')
@php
  $serviceTypeLabel = fn ($type) => $type === 'courier_service' ? 'Kuryer' : 'Pochta';
  $scopeLabel = fn ($scope) => $scope === 'country' ? 'Butun mamlakat' : 'Radius bo‘yicha';
@endphp

<div class="space-y-6">
  <x-admin.page-header
    eyebrow="Delivery operations"
    title="Logistika boshqaruvi"
    subtitle="Hudud, narx, COD va qaysi joyga qaysi xizmat ishlashini markazdan boshqarish."
  >
    <a href="#service-profiles" class="btn btn-outline-secondary">
      <i class="bi bi-sliders me-2"></i>Xizmat profillari
    </a>
  </x-admin.page-header>

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Qoidalar soni</div>
          <div class="metric-value text-2xl">{{ $rules->count() }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Faol xizmat profillari</div>
          <div class="metric-value text-2xl">{{ $services->where('status', true)->count() }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Mamlakatlar</div>
          <div class="metric-value text-2xl">{{ $rules->pluck('country_code')->filter()->unique()->count() }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">COD yoqilgan hududlar</div>
          <div class="metric-value text-2xl">{{ $codEnabledRulesCount }}</div>
        </div>
      </div>
      <div class="mt-4 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 text-sm leading-6 text-[var(--p-muted)]">
        Professional ishlash logikasi: user manzilidan kelgan <strong>lat/lon</strong> birinchi o‘rinda turadi. Radius qoidalari matnga emas, koordinataga qaraydi. Shu sabab foydalanuvchi yozuvini o‘zgartirib tizimni alday olmaydi. Butun mamlakat qoidasi esa zaxira/fallback sifatida ishlaydi.
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-7">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Qoidalar ro‘yxati</div>
          <div class="a122-section-head__meta">Hudud, xizmat, narx va COD siyosati shu yerda boshqariladi.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="GET" action="{{ route('admin.logistics.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
          <select name="cod_filter" class="p-form-control max-w-[220px]">
            <option value="" {{ $codFilter === '' ? 'selected' : '' }}>Barcha hududlar</option>
            <option value="on" {{ $codFilter === 'on' ? 'selected' : '' }}>Faqat COD yoqilgan</option>
            <option value="off" {{ $codFilter === 'off' ? 'selected' : '' }}>Faqat COD o‘chiq</option>
          </select>
          <button class="btn-p ghost" type="submit">
            <i class="bi bi-funnel"></i> Filtrlash
          </button>
          @if($codFilter !== '')
            <a href="{{ route('admin.logistics.index') }}" class="btn-p ghost">
              <i class="bi bi-x-lg"></i> Tozalash
            </a>
          @endif
        </form>
        <div class="table-responsive kc-table-shell">
          <table class="table data-table align-middle mb-0">
            <thead>
              <tr>
                <th>Zona</th>
                <th>Xizmat</th>
                <th>Scope</th>
                <th>Narx</th>
                <th>COD</th>
                <th>Priority</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($rules as $rule)
                @php
                  $service = $rule->deliveryService;
                  $effectiveBase = $rule->base_price ?? $service?->priceKg ?? 0;
                  $effectiveEta = $rule->eta_days ?? $service?->muddat ?? 0;
                @endphp
                <tr>
                  <td>
                    <div class="font-semibold">{{ $rule->zone_name }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">
                      {{ $rule->country_code }}
                      @if($rule->region_name) · {{ $rule->region_name }} @endif
                      @if($rule->district_name) · {{ $rule->district_name }} @endif
                      @if($rule->city_name) · {{ $rule->city_name }} @endif
                    </div>
                    @if($rule->cod_allowed)
                      <div class="mt-2">
                        <span class="badge badge-success">COD faol</span>
                      </div>
                    @endif
                  </td>
                  <td>
                    <div class="font-semibold">{{ $service?->name ?: '—' }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $serviceTypeLabel($service?->type) }}</div>
                  </td>
                  <td>
                    <div>{{ $scopeLabel($rule->scope) }}</div>
                    @if($rule->scope === 'radius')
                      <div class="text-xs text-[var(--p-hint)] mt-1">{{ number_format((float) $rule->radius_km, 1) }} km</div>
                    @endif
                  </td>
                  <td>
                    <div class="font-semibold">{{ number_format((int) $effectiveBase, 0, '.', ' ') }} UZS</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $effectiveEta }} kun · +{{ rtrim(rtrim(number_format((float) $rule->additional_seller_percent, 2, '.', ''), '0'), '.') }}%</div>
                  </td>
                  <td>
                    <span class="badge {{ $rule->cod_allowed ? 'badge-success' : 'badge-danger' }}">
                      {{ $rule->cod_allowed ? 'Yoqilgan' : 'O‘chiq' }}
                    </span>
                  </td>
                  <td>{{ $rule->priority }}</td>
                  <td><span class="badge {{ $rule->is_active ? 'badge-success' : 'badge-muted' }}">{{ $rule->is_active ? 'Faol' : 'O‘chiq' }}</span></td>
                  <td class="text-right">
                    <div class="flex justify-end gap-1">
                      <button class="btn btn-sm btn-outline-secondary"
                              onclick="openEditRule(
                                {{ $rule->id }},
                                @js($rule->zone_name),
                                @js($rule->country_code),
                                @js($rule->scope),
                                @js($rule->region_name),
                                @js($rule->district_name),
                                @js($rule->city_name),
                                @js($rule->center_lat),
                                @js($rule->center_lon),
                                @js($rule->radius_km),
                                {{ (int) $rule->delivery_service_id }},
                                {{ (int) $rule->priority }},
                                @js($rule->base_price),
                                @js($rule->additional_seller_percent),
                                @js($rule->free_price_from),
                                @js($rule->eta_days),
                                {{ $rule->cod_allowed ? 'true' : 'false' }},
                                {{ $rule->is_active ? 'true' : 'false' }},
                                @js($rule->notes)
                              )">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="POST" action="{{ route('admin.logistics.destroy', $rule) }}" onsubmit="return confirm('Qoidani o‘chirasizmi?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="py-10 text-center text-sm text-gray-500">Qoidalar hali kiritilmagan.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-5">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Yangi qoida</div>
          <div class="a122-section-head__meta">Kuryer yoki pochta uchun yangi hudud qoidasi yarating.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.logistics.store') }}" class="grid grid-cols-1 gap-3">
          @csrf
          <input type="text" name="zone_name" class="p-form-control" placeholder="Masalan: Chirchiq markazi courier" required>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="country_code" class="p-form-control" value="UZ" maxlength="8" required>
            <select name="scope" class="p-form-control" required>
              <option value="radius">Radius bo‘yicha</option>
              <option value="country">Butun mamlakat</option>
            </select>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="region_name" class="p-form-control" placeholder="Viloyat">
            <input type="text" name="district_name" class="p-form-control" placeholder="Tuman">
            <input type="text" name="city_name" class="p-form-control" placeholder="Shahar / markaz">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="number" step="0.0000001" name="center_lat" class="p-form-control" placeholder="Latitude">
            <input type="number" step="0.0000001" name="center_lon" class="p-form-control" placeholder="Longitude">
            <input type="number" step="0.1" name="radius_km" class="p-form-control" placeholder="Radius (km)">
          </div>
          <select name="delivery_service_id" class="p-form-control" required>
            <option value="">Xizmat profilini tanlang</option>
            @foreach($services as $service)
              <option value="{{ $service->id }}">{{ $service->name }} · {{ $serviceTypeLabel($service->type) }}</option>
            @endforeach
          </select>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <input type="number" name="priority" class="p-form-control" value="100" min="0" placeholder="Priority">
            <input type="number" name="base_price" class="p-form-control" min="0" placeholder="Bazaviy narx">
            <input type="number" step="0.01" name="additional_seller_percent" class="p-form-control" value="50" min="0" placeholder="+ seller %">
            <input type="number" name="free_price_from" class="p-form-control" min="0" placeholder="Bepul dan">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input type="number" name="eta_days" class="p-form-control" min="0" placeholder="ETA (kun)">
            <textarea name="notes" rows="3" class="p-form-control" placeholder="Izoh"></textarea>
          </div>
          <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm font-medium">
              <input type="hidden" name="cod_allowed" value="0">
              <input type="checkbox" name="cod_allowed" value="1" checked>
              Naqd to‘lov ruxsat
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-medium">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" name="is_active" value="1" checked>
              Faol qoida
            </label>
          </div>
          <div class="flex justify-end">
            <button class="btn-p primary"><i class="bi bi-plus-lg"></i> Qoidani saqlash</button>
          </div>
        </form>
      </div>
    </section>
  </div>

  <section class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Hududni test qilish</div>
        <div class="a122-section-head__meta">Admin lat/lon kiritib, shu nuqtada qaysi variantlar chiqishini oldindan ko‘ra oladi.</div>
      </div>
    </div>
    <div class="a122-section-body space-y-4">
      <form method="GET" action="{{ route('admin.logistics.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
        <input type="number" step="0.0000001" name="preview_lat" value="{{ request('preview_lat') }}" class="p-form-control" placeholder="Latitude">
        <input type="number" step="0.0000001" name="preview_lon" value="{{ request('preview_lon') }}" class="p-form-control" placeholder="Longitude">
        <input type="text" name="preview_country_code" value="{{ request('preview_country_code', 'UZ') }}" class="p-form-control" placeholder="Country code">
        <input type="number" name="preview_seller_count" value="{{ request('preview_seller_count', 1) }}" class="p-form-control" placeholder="Seller soni">
        <input type="number" name="preview_total_sum" value="{{ request('preview_total_sum', 0) }}" class="p-form-control" placeholder="Buyurtma summasi">
        <button class="btn-p primary"><i class="bi bi-search"></i> Tekshirish</button>
        <div class="md:col-span-6">
          <input type="text" name="preview_address" value="{{ request('preview_address') }}" class="p-form-control" placeholder="Manzil matni (ixtiyoriy)">
        </div>
      </form>

      @if($preview)
        <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
          <div class="text-sm font-semibold mb-3">Resolver natijasi</div>
          @if($preview['offers']->isEmpty())
            <div class="text-sm text-rose-600">Bu nuqta uchun faol yetkazish qoidasi topilmadi.</div>
          @else
            <div class="table-responsive kc-table-shell">
              <table class="table data-table align-middle mb-0">
                <thead><tr><th>Xizmat</th><th>Zona</th><th>Narx</th><th>COD</th><th>ETA</th></tr></thead>
                <tbody>
                  @foreach($preview['offers'] as $offer)
                    <tr>
                      <td>{{ $offer['name'] }}</td>
                      <td>{{ $offer['zone_name'] }}</td>
                      <td>{{ number_format((int) $offer['calculated_price'], 0, '.', ' ') }} UZS</td>
                      <td>{{ $offer['cod_allowed'] ? 'Ha' : 'Yo‘q' }}</td>
                      <td>{{ $offer['muddat'] }} kun</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      @endif
    </div>
  </section>

  <section id="service-profiles" class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Xizmat profillari</div>
        <div class="a122-section-head__meta">Zone rule qaysi yetkazish turiga ulanayotganini shu yerda boshqarasiz.</div>
      </div>
    </div>
    <div class="a122-section-body">
      <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
        <div class="xl:col-span-7">
          <div class="table-responsive kc-table-shell">
            <table class="table data-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Nomi</th>
                  <th>Turi</th>
                  <th>Default narx</th>
                  <th>Default ETA</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($services as $service)
                  <tr>
                    <td>{{ $service->name }}</td>
                    <td>{{ $serviceTypeLabel($service->type) }}</td>
                    <td>{{ number_format((int) $service->priceKg, 0, '.', ' ') }} UZS</td>
                    <td>{{ (int) $service->muddat }} kun</td>
                    <td><span class="badge {{ $service->status ? 'badge-success' : 'badge-muted' }}">{{ $service->status ? 'Faol' : 'O‘chiq' }}</span></td>
                    <td class="text-right">
                      <div class="flex justify-end gap-1">
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="openEditService(
                                  {{ $service->id }},
                                  @js($service->name),
                                  @js($service->type),
                                  {{ (int) $service->priceKg }},
                                  {{ (int) $service->muddat }},
                                  @js($service->forCountry),
                                  {{ $service->capital ? 'true' : 'false' }},
                                  {{ (int) $service->freePriceFrom }},
                                  {{ $service->status ? 'true' : 'false' }}
                                )">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.settings.delivery.destroy', $service) }}" onsubmit="return confirm('Xizmat profilini o‘chirasizmi?')">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="py-10 text-center text-sm text-gray-500">Xizmat profillari topilmadi.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="xl:col-span-5">
          <form method="POST" action="{{ route('admin.settings.delivery.store') }}" class="grid grid-cols-1 gap-3">
            @csrf
            <input type="text" name="name" class="p-form-control" placeholder="Masalan: UZ Postal" required>
            <select name="type" class="p-form-control" required>
              <option value="mail_service">Pochta xizmati</option>
              <option value="courier_service">Kuryer xizmati</option>
            </select>
            <div class="grid grid-cols-2 gap-3">
              <input type="number" name="priceKg" class="p-form-control" min="0" placeholder="Default narx" required>
              <input type="number" name="muddat" class="p-form-control" min="1" placeholder="Default ETA" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input type="text" name="forCountry" class="p-form-control" value="uzbekistan" placeholder="forCountry" required>
              <input type="number" name="freePriceFrom" class="p-form-control" min="0" value="0" placeholder="Bepul dan">
            </div>
            <div class="flex flex-wrap gap-4">
              <label class="inline-flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="capital" value="0">
                <input type="checkbox" name="capital" value="1">
                Legacy poytaxt belgisi
              </label>
              <label class="inline-flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" checked>
                Faol profil
              </label>
            </div>
            <div class="flex justify-end">
              <button class="btn-p primary"><i class="bi bi-plus-lg"></i> Profil qo‘shish</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="editRuleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border:1px solid var(--p-border2);border-radius:16px;padding:28px;width:100%;max-width:720px;max-height:90vh;overflow-y:auto;box-shadow:var(--p-shadow)">
    <div class="flex items-center justify-between mb-4">
      <div style="font-size:15px;font-weight:700;color:var(--p-text)">Qoidani tahrirlash</div>
      <button onclick="document.getElementById('editRuleModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--p-hint)">&times;</button>
    </div>
    <form id="editRuleForm" method="POST" class="grid grid-cols-1 gap-3">
      @csrf
      @method('PUT')
      <input type="text" id="er_zone_name" name="zone_name" class="p-form-control" required>
      <div class="grid grid-cols-2 gap-3">
        <input type="text" id="er_country_code" name="country_code" class="p-form-control" required>
        <select id="er_scope" name="scope" class="p-form-control" required>
          <option value="radius">Radius bo‘yicha</option>
          <option value="country">Butun mamlakat</option>
        </select>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="text" id="er_region_name" name="region_name" class="p-form-control" placeholder="Viloyat">
        <input type="text" id="er_district_name" name="district_name" class="p-form-control" placeholder="Tuman">
        <input type="text" id="er_city_name" name="city_name" class="p-form-control" placeholder="Shahar">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="number" step="0.0000001" id="er_center_lat" name="center_lat" class="p-form-control">
        <input type="number" step="0.0000001" id="er_center_lon" name="center_lon" class="p-form-control">
        <input type="number" step="0.1" id="er_radius_km" name="radius_km" class="p-form-control">
      </div>
      <select id="er_delivery_service_id" name="delivery_service_id" class="p-form-control" required>
        @foreach($services as $service)
          <option value="{{ $service->id }}">{{ $service->name }} · {{ $serviceTypeLabel($service->type) }}</option>
        @endforeach
      </select>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <input type="number" id="er_priority" name="priority" class="p-form-control" min="0">
        <input type="number" id="er_base_price" name="base_price" class="p-form-control" min="0">
        <input type="number" step="0.01" id="er_additional_seller_percent" name="additional_seller_percent" class="p-form-control" min="0">
        <input type="number" id="er_free_price_from" name="free_price_from" class="p-form-control" min="0">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input type="number" id="er_eta_days" name="eta_days" class="p-form-control" min="0">
        <textarea id="er_notes" name="notes" rows="3" class="p-form-control"></textarea>
      </div>
      <div class="flex flex-wrap gap-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium">
          <input type="hidden" name="cod_allowed" value="0">
          <input type="checkbox" id="er_cod_allowed" name="cod_allowed" value="1">
          Naqd to‘lov ruxsat
        </label>
        <label class="inline-flex items-center gap-2 text-sm font-medium">
          <input type="hidden" name="is_active" value="0">
          <input type="checkbox" id="er_is_active" name="is_active" value="1">
          Faol qoida
        </label>
      </div>
      <div class="flex justify-end">
        <button class="btn-p primary"><i class="bi bi-check2"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>

<div id="editServiceModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border:1px solid var(--p-border2);border-radius:16px;padding:28px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:var(--p-shadow)">
    <div class="flex items-center justify-between mb-4">
      <div style="font-size:15px;font-weight:700;color:var(--p-text)">Xizmat profilini tahrirlash</div>
      <button onclick="document.getElementById('editServiceModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--p-hint)">&times;</button>
    </div>
    <form id="editServiceForm" method="POST" class="grid grid-cols-1 gap-3">
      @csrf
      @method('PUT')
      <input type="text" id="es_name" name="name" class="p-form-control" required>
      <select id="es_type" name="type" class="p-form-control" required>
        <option value="mail_service">Pochta xizmati</option>
        <option value="courier_service">Kuryer xizmati</option>
      </select>
      <div class="grid grid-cols-2 gap-3">
        <input type="number" id="es_priceKg" name="priceKg" class="p-form-control" min="0" required>
        <input type="number" id="es_muddat" name="muddat" class="p-form-control" min="1" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input type="text" id="es_forCountry" name="forCountry" class="p-form-control" required>
        <input type="number" id="es_freePriceFrom" name="freePriceFrom" class="p-form-control" min="0">
      </div>
      <div class="flex flex-wrap gap-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium">
          <input type="hidden" name="capital" value="0">
          <input type="checkbox" id="es_capital" name="capital" value="1">
          Legacy poytaxt belgisi
        </label>
        <label class="inline-flex items-center gap-2 text-sm font-medium">
          <input type="hidden" name="status" value="0">
          <input type="checkbox" id="es_status" name="status" value="1">
          Faol profil
        </label>
      </div>
      <div class="flex justify-end">
        <button class="btn-p primary"><i class="bi bi-check2"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditRule(id, zoneName, countryCode, scope, regionName, districtName, cityName, centerLat, centerLon, radiusKm, deliveryServiceId, priority, basePrice, additionalSellerPercent, freePriceFrom, etaDays, codAllowed, isActive, notes) {
  document.getElementById('editRuleForm').action = "{{ url('a122/logistics') }}/" + id;
  document.getElementById('er_zone_name').value = zoneName ?? '';
  document.getElementById('er_country_code').value = countryCode ?? 'UZ';
  document.getElementById('er_scope').value = scope ?? 'radius';
  document.getElementById('er_region_name').value = regionName ?? '';
  document.getElementById('er_district_name').value = districtName ?? '';
  document.getElementById('er_city_name').value = cityName ?? '';
  document.getElementById('er_center_lat').value = centerLat ?? '';
  document.getElementById('er_center_lon').value = centerLon ?? '';
  document.getElementById('er_radius_km').value = radiusKm ?? '';
  document.getElementById('er_delivery_service_id').value = String(deliveryServiceId ?? '');
  document.getElementById('er_priority').value = priority ?? 100;
  document.getElementById('er_base_price').value = basePrice ?? '';
  document.getElementById('er_additional_seller_percent').value = additionalSellerPercent ?? 50;
  document.getElementById('er_free_price_from').value = freePriceFrom ?? '';
  document.getElementById('er_eta_days').value = etaDays ?? '';
  document.getElementById('er_cod_allowed').checked = !!codAllowed;
  document.getElementById('er_is_active').checked = !!isActive;
  document.getElementById('er_notes').value = notes ?? '';
  document.getElementById('editRuleModal').style.display = 'flex';
}

document.getElementById('editRuleModal').addEventListener('click', function (e) {
  if (e.target === this) this.style.display = 'none';
});

function openEditService(id, name, type, priceKg, muddat, forCountry, capital, freePriceFrom, status) {
  document.getElementById('editServiceForm').action = "{{ url('a122/settings/delivery') }}/" + id;
  document.getElementById('es_name').value = name ?? '';
  document.getElementById('es_type').value = type ?? 'mail_service';
  document.getElementById('es_priceKg').value = priceKg ?? 0;
  document.getElementById('es_muddat').value = muddat ?? 1;
  document.getElementById('es_forCountry').value = forCountry ?? 'uzbekistan';
  document.getElementById('es_capital').checked = !!capital;
  document.getElementById('es_freePriceFrom').value = freePriceFrom ?? 0;
  document.getElementById('es_status').checked = !!status;
  document.getElementById('editServiceModal').style.display = 'flex';
}

document.getElementById('editServiceModal').addEventListener('click', function (e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
@endsection
