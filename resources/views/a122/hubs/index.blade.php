@extends('a122.layouts.admin')
@section('title', 'Hublar')
@section('page-title', 'Hublar boshqaruvi')

@section('content')
<div class="space-y-6">
  @php
    $help = function (string $id, string $text) {
      return '<button type="button" class="hub-help-trigger inline-flex h-5 w-5 items-center justify-center rounded-full border border-[var(--p-border)] text-[11px] font-bold text-[var(--p-muted)] transition hover:border-[var(--p-accent)] hover:text-[var(--p-accent)]" data-help-target="'.$id.'" aria-label="Izoh">?</button>'
        . '<div id="'.$id.'" class="hub-help-popover hidden max-w-xs rounded-2xl border border-[var(--p-border)] bg-white px-3 py-2 text-xs leading-5 text-[var(--p-text)] shadow-2xl">'.$text.'</div>';
    };
  @endphp
  <x-admin.page-header
    eyebrow="Fulfillment"
    title="Hublar"
    subtitle="Yig‘ish markazlari, first mile, last mile va postal handoff markazlarini shu yerdan boshqaramiz."
  >
    <a href="{{ route('hubdesk.login') }}" target="_blank" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-2"></i>Hub desk / print
    </a>
  </x-admin.page-header>

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label flex items-center gap-1">Jami hublar {!! $help('hub-help-total', 'Marketplace ichida ro‘yxatdan o‘tgan barcha fulfillment markazlari soni.') !!}</div>
          <div class="metric-value text-2xl">{{ $hubs->count() }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label flex items-center gap-1">Faol hublar {!! $help('hub-help-active', 'Faol hub routing engine tomonidan orderga biriktirilishi mumkin. O‘chiq hub yangi order olmaydi.') !!}</div>
          <div class="metric-value text-2xl">{{ $activeHubsCount }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label flex items-center gap-1">First mile markazlari {!! $help('hub-help-first-mile', 'Sellerdan orderni qabul qila oladigan hub. Hub-based yoki postal oqimda first-mile shu markazga oqadi.') !!}</div>
          <div class="metric-value text-2xl">{{ $firstMileHubsCount }}</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label flex items-center gap-1">Postal handoff hublar {!! $help('hub-help-postal', 'Qadoqlangan buyurtmani pochtaga chiqarish huquqiga ega hub. Postal oqim shu capabilityga qaraydi.') !!}</div>
          <div class="metric-value text-2xl">{{ $postalHubsCount }}</div>
        </div>
      </div>
      <div class="mt-4 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 text-sm leading-6 text-[var(--p-muted)]">
        Hub modeli bu endi oddiy ombor emas. U sellerdan qabul qilish, QC, packing, label va dispatch oqimining bosh nuqtasi bo‘ladi. USB-only printer ishlatilsa, hub staff brauzer orqali label va receipt’ni bevosita Laravel print sahifalaridan chop etadi.
      </div>
    </div>
  </section>

  <section class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title flex items-center gap-1">Rol matritsasi {!! $help('hub-help-role-matrix', 'Har bir hub staff roli qaysi navbatni ko‘radi, scan ishlatadimi, label/receipt print qiladimi shu jadvaldan bilinadi. Manager va supervisor barcha huquqlarni oladi.') !!}</div>
        <div class="a122-section-head__meta">Katta marketplace’da kimga nima ko‘rinishi va nimani o‘zgartira olishi shu yerda standartlashadi.</div>
      </div>
    </div>
    <div class="a122-section-body">
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @foreach($roles as $role)
          @php
            $blueprint = $roleBlueprints[$role->value] ?? ['label' => $role->value, 'description' => '—', 'permissions' => []];
          @endphp
          <div class="rounded-3xl border border-[var(--p-border)] bg-white p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold">{{ $blueprint['label'] }}</div>
                <div class="mt-1 text-sm text-[var(--p-hint)] leading-6">{{ $blueprint['description'] }}</div>
              </div>
              <span class="badge badge-muted">{{ $role->value }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              @foreach($blueprint['permissions'] as $permissionKey)
                <span class="badge badge-info">{{ data_get($permissionCatalog, $permissionKey . '.label', $permissionKey) }}</span>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-7">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Hublar ro‘yxati</div>
          <div class="a122-section-head__meta">Ko‘p hubli fulfillment modelini boshqarish uchun tayanch katalog.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-responsive kc-table-shell">
          <table class="table data-table align-middle mb-0">
            <thead>
              <tr>
                <th>Hub</th>
                <th>Hudud</th>
                <th>Priority</th>
                <th>Imkoniyatlar</th>
                <th>Navbat</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($hubs as $hub)
                <tr>
                  <td>
                    <div class="font-semibold">{{ $hub->name }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $hub->code }}</div>
                    @if($hub->is_primary)
                      <div class="mt-2"><span class="badge badge-info">Asosiy hub</span></div>
                    @endif
                  </td>
                  <td>
                    <div>{{ $hub->country_code }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">
                      {{ trim(($hub->region_name ?? '') . ' ' . ($hub->city_name ?? '')) ?: 'Hudud kiritilmagan' }}
                    </div>
                  </td>
                  <td>
                    <div class="font-semibold">{{ $hub->priority ?? 100 }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">Kichikroq son ustun</div>
                  </td>
                  <td>
                    <div class="flex flex-wrap gap-1">
                      @if($hub->supports_first_mile)<span class="badge badge-muted">First mile</span>@endif
                      @if($hub->supports_last_mile)<span class="badge badge-muted">Last mile</span>@endif
                      @if($hub->supports_postal_dispatch)<span class="badge badge-muted">Postal</span>@endif
                    </div>
                  </td>
                  <td>
                    <div class="text-sm">{{ $hub->fulfillments_count }} fulfillment</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $hub->courier_tasks_count }} courier task · {{ $hub->staff_count }} staff</div>
                  </td>
                  <td>
                    <span class="badge {{ $hub->is_active ? 'badge-success' : 'badge-muted' }}">{{ $hub->is_active ? 'Faol' : 'O‘chiq' }}</span>
                  </td>
                  <td class="text-right">
                    <div class="flex justify-end gap-1">
                      <button class="btn btn-sm btn-outline-secondary"
                        onclick="openEditHub(
                          {{ $hub->id }},
                          @js($hub->name),
                          @js($hub->code),
                          @js($hub->country_code),
                          @js($hub->region_name),
                          @js($hub->city_name),
                          @js($hub->address),
                          @js($hub->lat),
                          @js($hub->lon),
                          {{ $hub->is_active ? 'true' : 'false' }},
                          {{ $hub->is_primary ? 'true' : 'false' }},
                          {{ $hub->supports_first_mile ? 'true' : 'false' }},
                          {{ $hub->supports_last_mile ? 'true' : 'false' }},
                          {{ $hub->supports_postal_dispatch ? 'true' : 'false' }},
                          @js(data_get($hub->meta, 'notes')),
                          {{ (int) ($hub->priority ?? 100) }}
                        )">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="POST" action="{{ route('admin.hubs.destroy', $hub) }}" onsubmit="return confirm('Hub o‘chiriladimi?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="py-10 text-center text-sm text-gray-500">Hublar hali kiritilmagan.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-5">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title flex items-center gap-1">Yangi hub {!! $help('hub-help-form', 'Hub routing engine orderni qayerga oqizishini hal qiladigan markaz. Country, hudud va capability’lar to‘g‘ri kiritilishi kerak.') !!}</div>
          <div class="a122-section-head__meta">Ko‘p markazli fulfillment uchun yangi markaz qo‘shing.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.hubs.store') }}" class="grid grid-cols-1 gap-3">
          @csrf
          <input type="text" name="name" class="p-form-control" placeholder="Masalan: Toshkent Markaziy Hub" required>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="code" class="p-form-control" placeholder="TAS-HUB-1" required>
            <input type="text" name="country_code" class="p-form-control" value="UZ" maxlength="8" required>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="region_name" class="p-form-control" placeholder="Viloyat">
            <input type="text" name="city_name" class="p-form-control" placeholder="Shahar">
          </div>
          <textarea name="address" rows="2" class="p-form-control" placeholder="To‘liq manzil"></textarea>
          <div class="grid grid-cols-2 gap-3">
            <input type="number" step="0.0000001" name="lat" class="p-form-control" placeholder="Latitude">
            <input type="number" step="0.0000001" name="lon" class="p-form-control" placeholder="Longitude">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-[var(--p-hint)]">
                Priority
                {!! $help('hub-help-priority', 'Routing paytida kichikroq priority yuqoriroq turadi. Masalan, markaziy hub 10, zaxira hub 100.') !!}
              </label>
              <input type="number" min="1" name="priority" class="p-form-control" value="100" placeholder="100">
            </div>
          </div>
          <textarea name="meta" rows="3" class="p-form-control" placeholder="Izoh / ichki qayd"></textarea>
          <div class="grid grid-cols-2 gap-3">
            <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="is_active" value="1" checked> Faol {!! $help('hub-help-is-active', 'Faol hub yangi order routingida ishtirok etadi. O‘chiq hub faqat tarix uchun qoladi.') !!}</label>
            <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="is_primary" value="1"> Asosiy hub {!! $help('hub-help-primary', 'Bir nechta hub mos tushsa, primary hub ko‘proq ustun turadi. Bu hali ham priority bilan birga ishlaydi.') !!}</label>
            <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="supports_first_mile" value="1" checked> First mile {!! $help('hub-help-form-first-mile', 'Sellerdan hubga order qabul qilishga ruxsat. Hub-based va postal oqim uchun juda muhim.') !!}</label>
            <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="supports_last_mile" value="1" checked> Last mile {!! $help('hub-help-form-last-mile', 'Hubdan mijozga last-mile courier tasklari shu capability orqali chiqadi.') !!}</label>
            <label class="inline-flex items-center gap-2 text-sm font-medium md:col-span-2"><input type="checkbox" name="supports_postal_dispatch" value="1" checked> Postal handoff {!! $help('hub-help-form-postal', 'Hub pochtaga topshirish oqimida ishlay olishini belgilaydi. Postal-only oqim bunga qaraydi.') !!}</label>
          </div>
          <button class="btn-p primary" type="submit"><i class="bi bi-plus-circle"></i> Hub qo‘shish</button>
        </form>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-5">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title flex items-center gap-1">Hub staff {!! $help('hub-help-staff', 'Hub appga kiradigan xodimlar shu yerda yaratiladi. Har biri alohida login bilan ishlaydi, shuning uchun audit va rol nazorati chalkashmaydi.') !!}</div>
          <div class="a122-section-head__meta">Inbound, QC, packing va dispatch uchun alohida akkauntlar.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.hubs.staff.store') }}" class="grid grid-cols-1 gap-3">
          @csrf
          <select name="hub_id" class="p-form-control" required>
            <option value="">Hub tanlang</option>
            @foreach($hubs as $hub)
              <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
            @endforeach
          </select>
          <input type="text" name="full_name" class="p-form-control" placeholder="Ism familiya" required>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="username" class="p-form-control" placeholder="Login" required>
            <input type="text" name="phone_number" class="p-form-control" placeholder="+998..." >
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input type="password" name="password" class="p-form-control" placeholder="Parol" required>
            <select name="role" class="p-form-control" required>
              @foreach($roles as $role)
                <option value="{{ $role->value }}">{{ data_get($roleBlueprints, $role->value . '.label', $role->value) }}</option>
              @endforeach
            </select>
          </div>
          <button class="btn-p primary" type="submit"><i class="bi bi-person-plus"></i> Staff yaratish</button>
        </form>
      </div>
    </section>

    <section class="a122-section xl:col-span-7">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Staff ro‘yxati</div>
          <div class="a122-section-head__meta">Hub appga kira oladigan faol va nofaol akkauntlar.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-responsive kc-table-shell">
          <table class="table data-table align-middle mb-0">
            <thead>
              <tr>
                <th>F.I.SH</th>
                <th>Hub</th>
                <th>Login</th>
                <th>Rol</th>
                <th>So‘nggi faollik</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($staff as $staffMember)
                <tr>
                  <td>
                    <div class="font-semibold">{{ $staffMember->full_name ?: '—' }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $staffMember->phone_number ?: 'Telefon yo‘q' }}</div>
                  </td>
                  <td>
                    <div>{{ $staffMember->hub?->name ?: 'Hub yo‘q' }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $staffMember->hub?->code }}</div>
                  </td>
                  <td>{{ $staffMember->username }}</td>
                  <td>
                    <div><span class="badge badge-muted">{{ data_get($roleBlueprints, $staffMember->role . '.label', $staffMember->role) }}</span></div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">
                      {{ collect($staffMember->permissions ?? [])->take(2)->map(fn ($permission) => data_get($permissionCatalog, $permission . '.label', $permission))->implode(', ') ?: 'Role default access' }}
                    </div>
                  </td>
                  <td>
                    <div class="text-sm">{{ $staffMember->last_seen_at?->format('d.m.Y H:i') ?: 'Hali kirmagan' }}</div>
                    <div class="text-xs text-[var(--p-hint)] mt-1">{{ $staffMember->is_active ? 'Kirishga ruxsat bor' : 'Kirish bloklangan' }}</div>
                  </td>
                  <td>
                    <span class="badge {{ $staffMember->is_active ? 'badge-success' : 'badge-muted' }}">
                      {{ $staffMember->is_active ? 'Faol' : 'O‘chiq' }}
                    </span>
                  </td>
                  <td class="text-right">
                    <div class="flex justify-end gap-2">
                      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEditStaff(
                        {{ $staffMember->id }},
                        {{ $staffMember->hub_id }},
                        @js($staffMember->full_name),
                        @js($staffMember->username),
                        @js($staffMember->phone_number),
                        @js($staffMember->role),
                        @js($staffMember->permissions ?? []),
                        {{ $staffMember->is_active ? 'true' : 'false' }}
                      )">Tahrirlash</button>
                      <form method="POST" action="{{ route('admin.hubs.staff.toggle', $staffMember) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm {{ $staffMember->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                          {{ $staffMember->is_active ? 'O‘chirish' : 'Faollashtirish' }}
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="py-10 text-center text-sm text-gray-500">Hub staff hali yaratilmagan.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</div>

<div id="editStaffModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4">
  <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
      <div>
        <div class="text-lg font-semibold text-slate-900">Hub staffni tahrirlash</div>
        <div class="text-sm text-slate-500">Rol, hub va print access’ni shu yerdan boshqaramiz.</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeEditStaff()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-0">
      <form id="editStaffForm" method="POST" class="grid grid-cols-1 gap-3 px-6 py-5 border-b xl:border-b-0 xl:border-r border-slate-100">
        @csrf
        @method('PUT')
        <select id="editStaffHubId" name="hub_id" class="p-form-control" required>
          @foreach($hubs as $hub)
            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
          @endforeach
        </select>
        <input type="text" id="editStaffFullName" name="full_name" class="p-form-control" placeholder="Ism familiya" required>
        <div class="grid grid-cols-2 gap-3">
          <input type="text" id="editStaffUsername" name="username" class="p-form-control" placeholder="Login" required>
          <input type="text" id="editStaffPhone" name="phone_number" class="p-form-control" placeholder="+998...">
        </div>
        <select id="editStaffRole" name="role" class="p-form-control" required>
          @foreach($roles as $role)
            <option value="{{ $role->value }}">{{ data_get($roleBlueprints, $role->value . '.label', $role->value) }}</option>
          @endforeach
        </select>
        <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" id="editStaffActive" name="is_active" value="1"> Faol</label>

        <div>
          <div class="mb-2 text-sm font-semibold text-slate-900">Qo‘shimcha access</div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-auto rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] p-3">
            @foreach($permissionCatalog as $permissionKey => $permissionMeta)
              <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" class="edit-staff-permission" name="permissions[]" value="{{ $permissionKey }}">
                <span>
                  <span class="font-medium text-slate-900">{{ $permissionMeta['label'] }}</span>
                  <span class="mt-0.5 block text-xs text-slate-500">{{ $permissionMeta['description'] }}</span>
                </span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-p ghost" onclick="closeEditStaff()">Bekor</button>
          <button type="submit" class="btn-p primary">Saqlash</button>
        </div>
      </form>

      <form id="resetStaffPasswordForm" method="POST" class="grid grid-cols-1 gap-3 px-6 py-5">
        @csrf
        <div class="text-sm font-semibold text-slate-900">Parolni yangilash</div>
        <div class="text-sm leading-6 text-slate-500">Printer yonidagi kompyuterlar uchun oddiy va xavfsiz parol berib turing. Eski parol darhol bekor bo‘ladi.</div>
        <input type="password" name="password" class="p-form-control" placeholder="Yangi parol" required>
        <button type="submit" class="btn-p primary">Parolni yangilash</button>
      </form>
    </div>
  </div>
</div>

<div id="editHubModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4">
  <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
      <div>
        <div class="text-lg font-semibold text-slate-900">Hubni tahrirlash</div>
        <div class="text-sm text-slate-500">Fulfillment markazi sozlamalari</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeEditHub()"><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="editHubForm" method="POST" class="grid grid-cols-1 gap-3 px-6 py-5">
      @csrf
      @method('PUT')
      <input type="text" id="editHubName" name="name" class="p-form-control" required>
      <div class="grid grid-cols-2 gap-3">
        <input type="text" id="editHubCode" name="code" class="p-form-control" required>
        <input type="text" id="editHubCountry" name="country_code" class="p-form-control" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input type="text" id="editHubRegion" name="region_name" class="p-form-control">
        <input type="text" id="editHubCity" name="city_name" class="p-form-control">
      </div>
      <textarea id="editHubAddress" name="address" rows="2" class="p-form-control"></textarea>
      <div class="grid grid-cols-2 gap-3">
        <input type="number" step="0.0000001" id="editHubLat" name="lat" class="p-form-control">
        <input type="number" step="0.0000001" id="editHubLon" name="lon" class="p-form-control">
      </div>
      <input type="number" min="1" id="editHubPriority" name="priority" class="p-form-control" placeholder="Priority">
      <textarea id="editHubMeta" name="meta" rows="3" class="p-form-control"></textarea>
      <div class="grid grid-cols-2 gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" id="editHubActive" name="is_active" value="1"> Faol</label>
        <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" id="editHubPrimary" name="is_primary" value="1"> Asosiy hub</label>
        <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" id="editHubFirstMile" name="supports_first_mile" value="1"> First mile</label>
        <label class="inline-flex items-center gap-2 text-sm font-medium"><input type="checkbox" id="editHubLastMile" name="supports_last_mile" value="1"> Last mile</label>
        <label class="inline-flex items-center gap-2 text-sm font-medium md:col-span-2"><input type="checkbox" id="editHubPostal" name="supports_postal_dispatch" value="1"> Postal handoff</label>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" class="btn-p ghost" onclick="closeEditHub()">Bekor</button>
        <button type="submit" class="btn-p primary">Saqlash</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function openEditStaff(id, hubId, fullName, username, phoneNumber, role, permissions, isActive) {
  document.getElementById('editStaffForm').action = "{{ url('a122/hubs/staff') }}/" + id;
  document.getElementById('resetStaffPasswordForm').action = "{{ url('a122/hubs/staff') }}/" + id + "/reset-password";
  document.getElementById('editStaffHubId').value = hubId ?? '';
  document.getElementById('editStaffFullName').value = fullName ?? '';
  document.getElementById('editStaffUsername').value = username ?? '';
  document.getElementById('editStaffPhone').value = phoneNumber ?? '';
  document.getElementById('editStaffRole').value = role ?? '';
  document.getElementById('editStaffActive').checked = !!isActive;

  document.querySelectorAll('.edit-staff-permission').forEach((checkbox) => {
    checkbox.checked = Array.isArray(permissions) ? permissions.includes(checkbox.value) : false;
  });

  document.getElementById('editStaffModal').classList.remove('hidden');
  document.getElementById('editStaffModal').classList.add('flex');
}

function closeEditStaff() {
  document.getElementById('editStaffModal').classList.add('hidden');
  document.getElementById('editStaffModal').classList.remove('flex');
}

function openEditHub(id, name, code, country, region, city, address, lat, lon, isActive, isPrimary, firstMile, lastMile, postal, meta, priority = 100) {
  document.getElementById('editHubForm').action = "{{ url('a122/hubs') }}/" + id;
  document.getElementById('editHubName').value = name ?? '';
  document.getElementById('editHubCode').value = code ?? '';
  document.getElementById('editHubCountry').value = country ?? '';
  document.getElementById('editHubRegion').value = region ?? '';
  document.getElementById('editHubCity').value = city ?? '';
  document.getElementById('editHubAddress').value = address ?? '';
  document.getElementById('editHubLat').value = lat ?? '';
  document.getElementById('editHubLon').value = lon ?? '';
  document.getElementById('editHubPriority').value = priority ?? 100;
  document.getElementById('editHubMeta').value = meta ?? '';
  document.getElementById('editHubActive').checked = !!isActive;
  document.getElementById('editHubPrimary').checked = !!isPrimary;
  document.getElementById('editHubFirstMile').checked = !!firstMile;
  document.getElementById('editHubLastMile').checked = !!lastMile;
  document.getElementById('editHubPostal').checked = !!postal;
  document.getElementById('editHubModal').classList.remove('hidden');
  document.getElementById('editHubModal').classList.add('flex');
}

function closeEditHub() {
  document.getElementById('editHubModal').classList.add('hidden');
  document.getElementById('editHubModal').classList.remove('flex');
}

document.addEventListener('click', function (event) {
  if (event.target.id === 'editStaffModal') {
    closeEditStaff();
  }
  if (event.target.id === 'editHubModal') {
    closeEditHub();
  }

  const trigger = event.target.closest('.hub-help-trigger');
  const popovers = document.querySelectorAll('.hub-help-popover');

  if (!trigger) {
    popovers.forEach((popover) => popover.classList.add('hidden'));
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  const targetId = trigger.getAttribute('data-help-target');
  const popover = document.getElementById(targetId);
  if (!popover) return;

  const shouldOpen = popover.classList.contains('hidden');
  popovers.forEach((item) => item.classList.add('hidden'));
  if (!shouldOpen) return;

  const rect = trigger.getBoundingClientRect();
  popover.classList.remove('hidden');
  popover.style.position = 'fixed';
  popover.style.left = Math.min(window.innerWidth - popover.offsetWidth - 16, rect.left - 8) + 'px';
  popover.style.top = (rect.bottom + 8) + 'px';
  popover.style.zIndex = '60';
});
</script>
@endpush
