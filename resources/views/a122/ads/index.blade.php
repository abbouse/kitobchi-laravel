@extends('a122.layouts.admin')
@section('title', 'Reklamalar')
@section('page-title', 'Reklamalar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Marketing" title="Reklamalar" subtitle="{{ $ads->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      @if(request('type'))
        <input type="hidden" name="type" value="{{ request('type') }}">
      @endif
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="ID yoki seller ID" class="form-control">
    </form>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="d-flex flex-wrap align-items-center gap-3">
      <div class="nav nav-pills flex-wrap">
        @foreach([
          ['pending', 'Kutilmoqda'],
          ['approved', 'Tasdiqlangan'],
          ['rejected', 'Rad etilgan'],
          ['active', 'Faol'],
          ['expired', 'Muddati o‘tgan'],
        ] as [$key, $label])
          <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
            {{ $label }}
            <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($counts[$key] ?? 0) }}</span>
          </a>
        @endforeach
      </div>

      <form method="GET" class="d-flex align-items-center gap-2 ms-lg-auto">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <select name="type" class="form-select form-select-sm" style="width: 12rem;">
          <option value="">Barcha tur</option>
          @foreach($types as $type)
            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
          @endforeach
        </select>
        <button class="btn btn-sm btn-primary" type="submit" title="Filtrlash">
          <i class="bi bi-funnel"></i>
        </button>
        <a href="{{ route('admin.ads.index', ['tab' => $tab]) }}" class="btn btn-sm btn-light border" title="Tozalash">
          <i class="bi bi-x-lg"></i>
        </a>
      </form>
    </div>
  </div>

  <x-admin.section-card title="Reklamalar jadvali" :meta="$ads->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Banner</th>
            <th>Sotuvchi</th>
            <th>Tur</th>
            <th>Harakat</th>
            <th>Summa</th>
            <th>Muddat</th>
            <th>Moderatsiya</th>
            <th>To‘lov</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ads as $ad)
            @php
              $moderationClass = match($ad->moderation) {
                'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $moderationLabel = match($ad->moderation) {
                'approved' => 'Tasdiqlangan',
                'rejected' => 'Rad etilgan',
                default => 'Kutilmoqda',
              };
              $paymentClass = match($ad->paymentStatus) {
                'paid' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'failed' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $paymentLabel = match($ad->paymentStatus) {
                'paid' => 'To‘langan',
                'failed' => 'Muvaffaqiyatsiz',
                default => 'Kutilmoqda',
              };
              $expired = $ad->expire_at && $ad->expire_at <= now();
            @endphp
            <tr>
              <td class="text-secondary">#{{ $ad->id }}</td>
              <td>
                @if($ad->banner_img)
                  <a href="{{ $ad->banner_img }}" target="_blank" rel="noopener">
                    <img src="{{ $ad->banner_img }}" alt="" class="rounded border" style="width:72px;height:38px;object-fit:cover;">
                  </a>
                @else
                  <div class="d-flex align-items-center justify-content-center rounded border text-secondary" style="width:72px;height:38px;">
                    <i class="bi bi-image"></i>
                  </div>
                @endif
              </td>
              <td>
                @if($ad->seller)
                  <a href="{{ route('admin.sellers.show', $ad->seller_id) }}" class="fw-semibold text-decoration-none">
                    {{ Str::limit($ad->seller->shop_name ?? $ad->seller_id, 18) }}
                  </a>
                @else
                  <span class="text-secondary">#{{ $ad->seller_id }}</span>
                @endif
              </td>
              <td><span class="badge rounded-pill text-bg-secondary">{{ $ad->type }}</span></td>
              <td class="small text-secondary">{{ $ad->action }} / {{ $ad->product_type }} #{{ $ad->product_id }}</td>
              <td class="fw-semibold">{{ number_format($ad->amount) }}</td>
              <td>
                <span class="{{ $expired ? 'text-danger' : 'text-secondary' }}">{{ $ad->expire_at ? \Carbon\Carbon::parse($ad->expire_at)->format('d.m.Y') : '—' }}</span>
              </td>
              <td><span class="badge rounded-pill {{ $moderationClass }}">{{ $moderationLabel }}</span></td>
              <td><span class="badge rounded-pill {{ $paymentClass }}">{{ $paymentLabel }}</span></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  @if($ad->moderation === 'pending')
                    <form method="POST" action="{{ route('admin.ads.moderate', $ad) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.ads.moderate', $ad) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  @endif
                  <a href="{{ route('admin.ads.show', $ad) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center py-5 text-secondary">Reklama topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($ads->hasPages())
    <div>{{ $ads->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
