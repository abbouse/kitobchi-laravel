@extends('a122.layouts.admin')
@section('title', 'Mystery Box')
@section('page-title', 'Mystery Box')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Commerce" title="Mystery Box" subtitle="{{ $subs->total() }} ta obuna">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Ism yoki telefon" class="form-control">
    </form>
    <a href="{{ route('admin.mystery-box.plans') }}" class="btn btn-light border">
      <i class="bi bi-sliders me-1"></i>Tariflar
    </a>
  </x-admin.page-header>

  @if($dueToday > 0)
    <div class="alert alert-warning border-0 mb-0">
      Bugun {{ number_format($dueToday) }} ta jo‘natish navbati bor.
      @if($dueWeek > $dueToday)
        Bu hafta: {{ number_format($dueWeek) }} ta.
      @endif
    </div>
  @endif

  @if(isset($opsDueNow) && $opsDueNow->count())
    <x-admin.section-card title="Jo‘natish navbati" :meta="$opsDueNow->count() . ' ta'">
      <div class="row g-2">
        @foreach($opsDueNow as $delivery)
          <div class="col-md-6">
            <a href="{{ route('admin.mystery-box.show', $delivery->subscription_id) }}" class="d-flex align-items-center justify-content-between gap-3 rounded border p-3 text-decoration-none">
              <span class="min-w-0">
                <span class="d-block fw-semibold text-truncate">#{{ $delivery->subscription_id }} · {{ $delivery->month_number }}-oy</span>
                <span class="d-block small text-secondary text-truncate">{{ $delivery->subscription?->user?->name }} {{ $delivery->subscription?->user?->lastname }}</span>
                <span class="d-block small text-secondary">{{ $delivery->dispatch_type_label }} · {{ optional($delivery->planned_for_date)->format('d.m.Y') ?? '—' }}</span>
              </span>
              <span class="badge rounded-pill text-bg-warning">{{ $delivery->status_label }}</span>
            </a>
          </div>
        @endforeach
      </div>
    </x-admin.section-card>
  @endif

  <div class="row g-3">
    @foreach([
      ['Faol', $counts['active'], 'bi-check-circle', 'success'],
      ['Kutilmoqda', $counts['pending_payment'], 'bi-hourglass', 'warning'],
      ['To‘xtatilgan', $counts['paused'], 'bi-pause-circle', 'secondary'],
      ['Yakunlandi', $counts['completed'], 'bi-flag', 'info'],
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
        'active' => ['Faol', $counts['active']],
        'all' => ['Barchasi', $counts['all']],
        'pending_payment' => ['Kutilmoqda', $counts['pending_payment']],
        'paused' => ['To‘xtatilgan', $counts['paused']],
        'completed' => ['Yakunlangan', $counts['completed']],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Obunalar jadvali" :meta="$subs->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0 data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Foydalanuvchi</th>
            <th>Tarif</th>
            <th>Manzil</th>
            <th>Progress</th>
            <th>Keyingi yetkazish</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($subs as $sub)
            @php
              $addr = is_array($sub->address) ? $sub->address : [];
              $isOverdue = $sub->next_delivery_at && $sub->next_delivery_at->isPast() && $sub->status === 'active';
              $statusTone = match($sub->status) {
                'active' => 'success',
                'pending_payment' => 'warning',
                'paused' => 'secondary',
                'completed' => 'info',
                default => 'secondary',
              };
            @endphp
            <tr>
              <td class="text-secondary">#{{ $sub->id }}</td>
              <td>
                @if($sub->user)
                  <a href="{{ route('admin.users.show', $sub->user_id) }}" class="fw-semibold text-decoration-none">{{ $sub->user->name }} {{ $sub->user->lastname }}</a>
                  <div class="small text-secondary">{{ $sub->user->phone_number }}</div>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </td>
              <td>
                @if($sub->plan)
                  <div class="fw-semibold">{{ $sub->plan->name_uz }}</div>
                  <div class="small text-secondary">{{ $sub->plan->months }} oy · {{ $sub->books_per_month }} kitob/oy</div>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </td>
              <td class="text-secondary text-truncate" style="max-width: 14rem;">{{ $addr['fullAddress'] ?? '—' }}</td>
              <td style="min-width: 8rem;">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:.4rem;">
                    <div class="progress-bar bg-success" style="width:{{ $sub->progress_pct }}%"></div>
                  </div>
                  <span class="small text-secondary text-nowrap">{{ $sub->delivered_months }}/{{ $sub->total_months }}</span>
                </div>
              </td>
              <td class="text-nowrap {{ $isOverdue ? 'text-danger' : 'text-secondary' }}">
                {{ $sub->next_delivery_at ? $sub->next_delivery_at->format('d.m.Y') : '—' }}
              </td>
              <td><span class="badge rounded-pill text-bg-{{ $statusTone }}">{{ $sub->status_label }}</span></td>
              <td class="text-end">
                <a href="{{ route('admin.mystery-box.show', $sub) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center py-5 text-secondary">Obuna topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($subs->hasPages())
    <div>{{ $subs->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
