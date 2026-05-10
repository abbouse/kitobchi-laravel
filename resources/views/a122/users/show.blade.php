@extends('a122.layouts.admin')
@section('title', 'Foydalanuvchi')
@section('page-title', 'Foydalanuvchi profili')

@section('content')
@php
  $fullName = data_get($user, 'full_name');
  $spentSeconds = (int) (data_get($user, 'total_seconds_spent') ?? data_get($user, 'total_seconds_spend') ?? 0);
  $spentHours = $spentSeconds > 0 ? number_format($spentSeconds / 3600, 1) : '0';
  $mainAddressId = (int) data_get($user, 'mainAddressID', 0);
  $mainAddress = $addresses->firstWhere('id', $mainAddressId) ?? $addresses->first();
  $verifyToken = data_get($user, 'verifyCode');
  $isActivated = blank($verifyToken);
  $staffRoleLabel = match ((string) data_get($user, 'staff_role')) {
    'administrator' => 'Administrator',
    'moderator' => 'Moderator',
    default => '—',
  };
@endphp

<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.users.index') }}">
    <x-slot name="heading">{{ $fullName }}</x-slot>
    <x-slot name="meta">ID #{{ $user->id }} · {{ data_get($user,'phone_number') ?: 'Telefon yo‘q' }} · {{ optional($user->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q' }}</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.users.edit', $user) }}" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
    </x-slot>
  </x-a122.page-header>

  <section class="a122-section overflow-hidden">
    <div class="px-6 py-6 border-b border-[var(--p-border)] bg-[var(--p-surface)]">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          @include('a122.partials.avatar', [
            'name' => $fullName,
            'image' => $user->avatar,
            'class' => 'w-24 h-24 rounded-[28px] text-3xl font-black shadow-xl ring-4 ring-white/50 dark:ring-white/10',
          ])

          <div class="space-y-3">
            <div>
              <h2 class="text-2xl font-black tracking-tight">{{ $fullName }}</h2>
              <div class="text-sm text-[var(--p-muted)] mt-1">
                {{ data_get($user,'email') ?: 'Email ko‘rsatilmagan' }}
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="badge badge-info">{{ data_get($user,'position','reader') }}</span>
              @if(data_get($user,'staff_role'))
                <span class="badge badge-warning">{{ data_get($user,'staff_role') === 'administrator' ? 'Administrator' : 'Moderator' }}</span>
              @endif
              <span class="badge {{ $user->isVerified ? 'badge-success' : 'badge-warning' }}">{{ $user->isVerified ? 'Tasdiqlangan' : 'Tasdiqlanmagan' }}</span>
              <span class="badge {{ $user->is_premium ? 'badge-warning' : 'badge-muted' }}">{{ $user->is_premium ? 'Premium' : 'Standard' }}</span>
              @if($user->isBlocked())
                <span class="badge badge-danger">Bloklangan</span>
              @endif
              @if($user->isSupport)
                <span class="badge badge-info">Support</span>
              @endif
              @if(data_get($user,'role_title'))
                <span class="badge badge-muted">{{ trim((data_get($user,'role_emoji') ?: '').' '.data_get($user,'role_title')) }}</span>
              @endif
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 min-[520px]:grid-cols-4 lg:min-w-[420px]">
          <div class="kpi-soft">
            <div class="metric-label">Buyurtmalar</div>
            <div class="metric-value text-2xl">{{ $stats['orders_count'] }}</div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">To‘langan</div>
            <div class="metric-value text-2xl">{{ $stats['paid_orders_count'] }}</div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">Balans</div>
            <div class="metric-value text-xl">{{ number_format((float) data_get($user,'real_balance',0), 0, '.', ' ') }}</div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">Cashback</div>
            <div class="metric-value text-xl">{{ number_format((float) data_get($user,'cashback',0), 0, '.', ' ') }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-px bg-[var(--p-border)] md:grid-cols-4 xl:grid-cols-8">
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Kartalar</div>
        <div class="metric-value text-xl mt-2">{{ $stats['cards_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Qurilmalar</div>
        <div class="metric-value text-xl mt-2">{{ $stats['devices_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Manzillar</div>
        <div class="metric-value text-xl mt-2">{{ $stats['addresses_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Followers</div>
        <div class="metric-value text-xl mt-2">{{ $stats['followers_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Following</div>
        <div class="metric-value text-xl mt-2">{{ $stats['following_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Gift sertifikat</div>
        <div class="metric-value text-xl mt-2">{{ $stats['gift_certificates_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Mystery Box</div>
        <div class="metric-value text-xl mt-2">{{ $stats['mystery_subscriptions_count'] }}</div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Faollik</div>
        <div class="metric-value text-xl mt-2">{{ $spentHours }} soat</div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-4">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Profil ma’lumotlari</div>
          <div class="a122-section-head__meta">Aloqa, premium va asosiy akkaunt atributlari.</div>
        </div>
        <div class="a122-section-head__actions">
          <span class="badge badge-muted">Asosiy</span>
        </div>
      </div>
      <div class="a122-section-body">

      <div class="data-grid two">
        <div class="data-kv"><dt>Telefon</dt><dd>{{ data_get($user,'phone_number') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Email</dt><dd>{{ data_get($user,'email') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Username</dt><dd>{{ data_get($user,'username') ? '@'.data_get($user,'username') : '—' }}</dd></div>
        <div class="data-kv"><dt>Telegram ID</dt><dd>{{ data_get($user,'telegram_id') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Til</dt><dd>{{ data_get($user,'locale') ?: 'uz' }}</dd></div>
        <div class="data-kv"><dt>Staff roli</dt><dd>{{ $staffRoleLabel }}</dd></div>
        <div class="data-kv"><dt>Aktivlashtirish holati</dt><dd>{{ $isActivated ? 'Profilga kirgan / aktivlashtirilgan' : 'Tasdiqlash kutilmoqda' }}</dd></div>
        <div class="data-kv"><dt>verifyToken</dt><dd>{{ $verifyToken ?: 'null' }}</dd></div>
        <div class="data-kv"><dt>Premium</dt><dd>{{ $user->is_premium ? 'Faol' : 'Yo‘q' }}</dd></div>
        <div class="data-kv"><dt>Premium muddati</dt><dd>{{ optional(data_get($user,'premium_until'))->format('d.m.Y H:i') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>AI limiti</dt><dd>{{ number_format((int) data_get($user,'ai_limit', 0)) }}</dd></div>
        <div class="data-kv"><dt>Oxirgi faollik</dt><dd>{{ optional(data_get($user,'last_seen_at'))->format('d.m.Y H:i') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Ro‘yxatdan o‘tgan</dt><dd>{{ optional(data_get($user,'created_at'))->format('d.m.Y H:i') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Spent time</dt><dd>{{ number_format($spentSeconds) }} sec</dd></div>
        <div class="data-kv"><dt>Balans</dt><dd>{{ number_format((float) data_get($user,'real_balance',0), 0, '.', ' ') }} UZS</dd></div>
        <div class="data-kv"><dt>Cashback</dt><dd>{{ number_format((float) data_get($user,'cashback',0), 0, '.', ' ') }} UZS</dd></div>
        <div class="data-kv"><dt>Asosiy manzil</dt><dd>{{ data_get($mainAddress, 'fullAddress') ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Blok holati</dt><dd>{{ $user->isBlocked() ? $user->activeBlockLabel() : 'Faol' }}</dd></div>
      </div>

      @if(data_get($user,'bio'))
        <div class="mt-5 p-4 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)]">
          <div class="metric-label mb-2">Bio</div>
          <div class="content-prose">{{ data_get($user,'bio') }}</div>
        </div>
      @endif

      <div class="mt-5 flex justify-end">
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Foydalanuvchini o‘chirishga ishonchingiz komilmi?')">
          @csrf
          @method('DELETE')
          <button class="btn-p danger"><i class="bi bi-trash3"></i> Foydalanuvchini o‘chirish</button>
        </form>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-8">
      <div class="a122-section-body">
      <div class="mb-5 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-5">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-black">Akkaunt boshqaruvi</h3>
            <p class="text-sm text-[var(--p-muted)] mt-1">Bloklangan foydalanuvchining barcha tokenlari o‘chiriladi va keyingi kirish rad etiladi.</p>
          </div>
          @if($user->isBlocked())
            <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
              @csrf
              <button class="btn-p success"><i class="bi bi-unlock"></i> Blokdan chiqarish</button>
            </form>
          @endif
        </div>

        @if($user->isBlocked())
          <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <div><strong>Muddat:</strong> {{ $user->activeBlockLabel() }}</div>
            <div class="mt-2"><strong>Sabab:</strong> {{ $user->block_reason ?: '—' }}</div>
          </div>
        @else
          <form method="POST" action="{{ route('admin.users.block', $user) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            @csrf
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Blok muddati</label>
              <select name="block_period" class="input" required>
                <option value="10_days">10 kun</option>
                <option value="1_month">1 oy</option>
                <option value="1_year">1 yil</option>
                <option value="3_years">3 yil</option>
                <option value="forever">Abadiy</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Blok sababi</label>
              <textarea name="block_reason" rows="4" class="input" required placeholder="Nega bloklanayotganini yozing"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
              <button class="btn-p danger"><i class="bi bi-ban"></i> Akkauntni bloklash</button>
            </div>
          </form>
        @endif
      </div>

      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">So‘nggi buyurtmalar</h3>
        <span class="badge badge-info">{{ $stats['orders_count'] }} ta</span>
      </div>

      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Status</th><th>To‘lov</th><th>Yetkazish</th><th>Summa</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            @forelse($recentOrders as $order)
              @php
                $statusLabel = match ((string) $order->status) {
                  'A', 'P' => 'Kutilmoqda',
                  'B' => 'Yo‘lda',
                  'C' => 'Yetkazildi',
                  'F' => 'Bekor',
                  default => 'Noma’lum',
                };
                $statusColor = match ((string) $order->status) {
                  'A', 'P' => 'warning',
                  'B' => 'info',
                  'C' => 'success',
                  'F' => 'danger',
                  default => 'muted',
                };
                $paymentLabel = (int) $order->paymentStatus === 2 ? 'To‘langan' : 'Kutilmoqda';
                $deliveryLabel = match ((int) $order->deliveryType) {
                  1 => 'Kuryer',
                  2 => 'Olib ketish',
                  default => 'Standart',
                };
              @endphp
              <tr>
                <td class="font-semibold">#ORD-{{ $order->id }}</td>
                <td><span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span></td>
                <td>{{ $paymentLabel }}</td>
                <td>{{ $deliveryLabel }}</td>
                <td>{{ number_format((float) $order->amount, 0, '.', ' ') }} UZS</td>
                <td>{{ optional($order->created_at)->format('d.m.Y H:i') }}</td>
                <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Kartalar</h3>
        <span class="badge badge-info">{{ $cards->count() }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($cards as $card)
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-semibold tracking-[0.18em] uppercase text-[var(--p-muted)]">Card</div>
                <div class="mt-2 text-lg font-black">**** **** **** {{ substr((string) $card->card_number, -4) ?: '****' }}</div>
              </div>
              <span class="badge {{ $card->is_verified ? 'badge-success' : 'badge-warning' }}">{{ $card->is_verified ? 'Tasdiqlangan' : 'Kutilmoqda' }}</span>
            </div>
            <div class="text-xs text-[var(--p-muted)] mt-3">{{ optional($card->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q' }}</div>
          </div>
        @empty
          <div class="text-sm text-gray-500">Karta topilmadi.</div>
        @endforelse
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Qurilmalar</h3>
        <span class="badge badge-info">{{ $devices->count() }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($devices as $device)
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold">{{ $device->device_name ?: 'Noma’lum qurilma' }}</div>
                <div class="text-sm text-[var(--p-muted)] mt-1">{{ strtoupper($device->platform ?: 'platform yo‘q') }} · {{ $device->device_id ?: 'ID yo‘q' }}</div>
              </div>
              <span class="badge {{ $device->fcm_token ? 'badge-success' : 'badge-muted' }}">Push</span>
            </div>
            <div class="text-xs text-[var(--p-muted)] mt-3">{{ optional($device->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q' }}</div>
          </div>
        @empty
          <div class="text-sm text-gray-500">Qurilmalar topilmadi.</div>
        @endforelse
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Manzillar</h3>
        <span class="badge badge-info">{{ $addresses->count() }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($addresses as $address)
          @php
            $addressText = $address->fullAddress ?: 'Manzil kiritilmagan';
            $encodedAddress = rawurlencode($addressText);
            $googleMapsUrl = ($address->lat && $address->lon)
              ? ('https://www.google.com/maps?q=' . $address->lat . ',' . $address->lon)
              : ('https://www.google.com/maps/search/?api=1&query=' . $encodedAddress);
            $yandexMapsUrl = ($address->lat && $address->lon)
              ? ('https://yandex.uz/maps/?pt=' . $address->lon . ',' . $address->lat . '&z=16&l=map')
              : ('https://yandex.uz/maps/?text=' . $encodedAddress);
          @endphp
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="text-sm leading-6">{{ $addressText }}</div>
              @if((int) $address->id === $mainAddressId)
                <span class="badge badge-success">Asosiy</span>
              @endif
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-[var(--p-muted)]">
              <span>{{ optional($address->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q' }}</span>
              <div class="flex flex-wrap gap-2">
                <a href="{{ $yandexMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-[var(--p-accent)] font-semibold">Yandex Maps</a>
                <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-[var(--p-accent)] font-semibold">Google Maps</a>
              </div>
            </div>
          </div>
        @empty
          <div class="text-sm text-gray-500">Manzillar topilmadi.</div>
        @endforelse
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-6">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Followers</h3>
        <span class="badge badge-info">{{ $stats['followers_count'] }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($followers as $person)
          <a href="{{ route('admin.users.show', $person->id) }}" class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-3 hover:border-[var(--p-accent)] transition">
            @if($person->avatar)
              <img src="{{ asset('storage/'.$person->avatar) }}" class="w-11 h-11 rounded-2xl object-cover" alt="{{ trim(($person->name ?? '').' '.($person->lastname ?? '')) }}">
            @else
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-black text-white bg-[linear-gradient(135deg,#10b981,#2563eb)]">
                {{ strtoupper(substr((string) ($person->name ?? 'U'), 0, 1)) }}
              </div>
            @endif
            <div class="min-w-0">
              <div class="font-semibold truncate">{{ trim(($person->name ?? '').' '.($person->lastname ?? '')) ?: 'Noma’lum foydalanuvchi' }}</div>
              <div class="text-sm text-[var(--p-muted)] truncate">{{ $person->phone_number ?: 'Telefon yo‘q' }}</div>
            </div>
          </a>
        @empty
          <div class="text-sm text-gray-500">Followers topilmadi.</div>
        @endforelse
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-6">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Following</h3>
        <span class="badge badge-info">{{ $stats['following_count'] }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($following as $person)
          <a href="{{ route('admin.users.show', $person->id) }}" class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-3 hover:border-[var(--p-accent)] transition">
            @if($person->avatar)
              <img src="{{ asset('storage/'.$person->avatar) }}" class="w-11 h-11 rounded-2xl object-cover" alt="{{ trim(($person->name ?? '').' '.($person->lastname ?? '')) }}">
            @else
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-black text-white bg-[linear-gradient(135deg,#f59e0b,#ef4444)]">
                {{ strtoupper(substr((string) ($person->name ?? 'U'), 0, 1)) }}
              </div>
            @endif
            <div class="min-w-0">
              <div class="font-semibold truncate">{{ trim(($person->name ?? '').' '.($person->lastname ?? '')) ?: 'Noma’lum foydalanuvchi' }}</div>
              <div class="text-sm text-[var(--p-muted)] truncate">{{ $person->phone_number ?: 'Telefon yo‘q' }}</div>
            </div>
          </a>
        @empty
          <div class="text-sm text-gray-500">Following topilmadi.</div>
        @endforelse
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-7">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Gift sertifikatlar</h3>
        <span class="badge badge-info">{{ $stats['gift_certificates_count'] }} ta</span>
      </div>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Kod</th><th>Rol</th><th>Status</th><th>Miqdor</th><th>Muddat</th><th></th></tr></thead>
          <tbody>
            @forelse($giftCertificates as $certificate)
              <tr>
                <td class="font-semibold">{{ $certificate->code }}</td>
                <td>{{ (int) $certificate->buyer_user_id === (int) $user->id ? 'Sotib olgan' : 'Qabul qilgan' }}</td>
                <td><span class="badge badge-{{ $certificate->status_color }}">{{ $certificate->status_label }}</span></td>
                <td>{{ number_format((int) $certificate->nominal_uzs, 0, '.', ' ') }} UZS</td>
                <td>{{ optional($certificate->expires_at)->format('d.m.Y') ?: '—' }}</td>
                <td class="text-right"><a href="{{ route('admin.gift-certificates.show', $certificate) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Gift sertifikatlar topilmadi.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-5">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Mystery Box obunalari</h3>
        <span class="badge badge-info">{{ $mysterySubscriptions->count() }} ta</span>
      </div>
      <div class="space-y-3">
        @forelse($mysterySubscriptions as $subscription)
          <a href="{{ route('admin.mystery-box.show', $subscription) }}" class="block rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 hover:border-[var(--p-accent)] transition">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold">{{ $subscription->plan?->name_uz ?: 'Mystery Box obuna' }}</div>
                <div class="text-sm text-[var(--p-muted)] mt-1">
                  {{ $subscription->books_per_month }} ta kitob / {{ $subscription->total_months }} oy
                </div>
              </div>
              <span class="badge badge-{{ $subscription->status_color }}">{{ $subscription->status_label }}</span>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
              <div class="rounded-xl bg-[var(--p-surface)] px-3 py-2">
                <div class="metric-label">Narx</div>
                <div class="font-semibold mt-1">{{ number_format((int) $subscription->price_uzs, 0, '.', ' ') }} UZS</div>
              </div>
              <div class="rounded-xl bg-[var(--p-surface)] px-3 py-2">
                <div class="metric-label">Progress</div>
                <div class="font-semibold mt-1">{{ $subscription->delivered_months }}/{{ $subscription->total_months }}</div>
              </div>
            </div>

            <div class="mt-4 h-2 rounded-full bg-[var(--p-surface)] overflow-hidden">
              <div class="h-full rounded-full bg-[linear-gradient(90deg,#10b981,#2563eb)]" style="width: {{ min(100, max(0, $subscription->progress_pct)) }}%"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-[var(--p-muted)]">
              <span>Keyingi yetkazish: {{ optional($subscription->next_delivery_at)->format('d.m.Y') ?: '—' }}</span>
              <span>{{ $subscription->deliveries->count() }} ta delivery</span>
            </div>
          </a>
        @empty
          <div class="text-sm text-gray-500">Mystery Box obunalari topilmadi.</div>
        @endforelse
      </div>
      </div>
    </section>
  </div>
</div>
@endsection
