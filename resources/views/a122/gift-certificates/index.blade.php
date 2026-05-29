@extends('a122.layouts.admin')
@section('title', 'Gift sertifikatlar')
@section('page-title', 'Gift sertifikatlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Commerce" title="Gift sertifikatlar" subtitle="{{ $certs->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Kod, telefon yoki ism" class="form-control">
    </form>
  </x-admin.page-header>

  <div class="row g-3">
    <div class="col-12 col-xl-7">
      <x-admin.section-card title="Tariflar" meta="Nominal variantlar">
        <form method="POST" action="{{ route('admin.gift-certificates.options') }}" class="row g-3">
          @csrf
          @method('PUT')
          @for($i = 0; $i < 4; $i++)
            <div class="col-12 col-md-3">
              <label class="form-label">Variant {{ $i + 1 }}</label>
              <input
                type="number"
                name="options[]"
                class="form-control"
                min="1000"
                step="1000"
                value="{{ old("options.$i", $giftCertificateOptions[$i] ?? '') }}"
                placeholder="300000">
            </div>
          @endfor
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn-p primary">
              <i class="bi bi-floppy"></i>
              <span>Saqlash</span>
            </button>
          </div>
        </form>
      </x-admin.section-card>
    </div>
    <div class="col-12 col-xl-5">
      <x-admin.section-card title="Faol nominal" meta="{{ count($giftCertificateOptions) }} ta variant">
        <div class="d-flex flex-wrap gap-2">
          @forelse($giftCertificateOptions as $amount)
            <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ number_format($amount) }} UZS</span>
          @empty
            <span class="text-secondary">Variant yo‘q.</span>
          @endforelse
        </div>
      </x-admin.section-card>
    </div>
  </div>

  <div class="row g-3">
    @foreach([
      ['Jami', $counts['all'] ?? 0, 'bi-gift', 'primary'],
      ['Faol', $counts['active'] ?? 0, 'bi-send', 'info'],
      ['Ishlatilgan', $counts['used'] ?? 0, 'bi-check-circle', 'success'],
      ['Bekor', $counts['cancelled'] ?? 0, 'bi-x-circle', 'danger'],
    ] as [$label, $value, $icon, $tone])
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="bi {{ $icon }}"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value">{{ number_format($value) }}</div>
            <div class="a122-stat-tile__label">{{ $label }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'all' => ['Barchasi', $counts['all'] ?? 0],
        'pending_payment' => ['Kutilmoqda', $counts['pending_payment'] ?? 0],
        'active' => ['Faol', $counts['active'] ?? 0],
        'used' => ['Ishlatilgan', $counts['used'] ?? 0],
        'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Sertifikatlar jadvali" :meta="$certs->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Kod</th>
            <th>Sotib olgan</th>
            <th>Qabul qiluvchi</th>
            <th class="text-end">Miqdor</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($certs as $cert)
            @php
              $statusClass = match($cert->status) {
                'active', 'sent' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'used' => 'text-bg-secondary',
                'paid' => 'text-bg-info-subtle border border-info-subtle text-info-emphasis',
                'cancelled', 'payment_cancelled' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $statusLabel = match($cert->status) {
                'pending_payment' => 'To‘lov kutilmoqda',
                'paid' => 'To‘landi',
                'active', 'sent' => 'Faol',
                'used' => 'Ishlatildi',
                'cancelled' => 'Bekor qilindi',
                'payment_cancelled' => 'To‘lovsiz bekor',
                default => $cert->status,
              };
            @endphp
            <tr>
              <td><code class="kc-inline-code">{{ $cert->code }}</code></td>
              <td>
                @if($cert->buyer)
                  <a href="{{ route('admin.users.show', $cert->buyer_user_id) }}" class="fw-semibold text-decoration-none">
                    {{ $cert->buyer->name }} {{ $cert->buyer->lastname }}
                  </a>
                  <div class="small text-secondary">{{ $cert->buyer->phone_number }}</div>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </td>
              <td>
                @if($cert->recipient)
                  <a href="{{ route('admin.users.show', $cert->recipient_user_id) }}" class="fw-semibold text-decoration-none">
                    {{ $cert->recipient->name }}
                  </a>
                @elseif($cert->recipient_name || $cert->recipient_phone)
                  <div>{{ $cert->recipient_name ?: '—' }}</div>
                  <div class="small text-secondary">{{ $cert->recipient_phone }}</div>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </td>
              <td class="text-end fw-semibold">{{ number_format($cert->nominal_uzs) }} UZS</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td class="text-secondary text-nowrap">{{ $cert->created_at?->format('d.m.Y') }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.gift-certificates.show', $cert) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  @if(!in_array($cert->status, ['used', 'cancelled']))
                    <form method="POST" action="{{ route('admin.gift-certificates.cancel', $cert) }}" onsubmit="return confirm('Bekor qilinsinmi?')">
                      @csrf
                      <button class="btn btn-sm btn-light border kc-table-action text-danger" title="Bekor qilish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Sertifikat topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($certs->hasPages())
    <div>{{ $certs->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
