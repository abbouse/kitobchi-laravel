@extends('panel.layouts.panel')
@section('title', 'Tranzaksiyalar')
@section('page-title', 'Tranzaksiyalar')

@section('content')

{{-- ── Header ──────────────────────────────────────── --}}
<div class="page-header fade-up d-flex align-items-start justify-content-between mb-3">
  <div>
    <h1 class="page-title">Tranzaksiyalar</h1>
    <p class="page-sub">Yechib olish arizalari</p>
  </div>
</div>

{{-- ── Segment switcher: Seller | Courier ────────────── --}}
<div class="d-flex gap-2 mb-4 fade-up">
  <a href="{{ request()->fullUrlWithQuery(['segment'=>'seller','tab'=>'pending','page'=>1]) }}"
     class="btn-p {{ $segment==='seller'?'primary':'ghost' }}"
     style="gap:7px">
    <i class="bi bi-shop-window"></i> Seller
    @if($segment==='courier' && $otherPending > 0)
      <span class="nav-badge warning" style="position:relative;inset:auto">{{ $otherPending }}</span>
    @elseif($segment==='seller' && $counts['pending'] > 0)
      <span style="background:rgba(255,255,255,.2);border-radius:10px;
                   padding:1px 7px;font-size:11px;font-weight:600">
        {{ $counts['pending'] }}
      </span>
    @endif
  </a>
  <a href="{{ request()->fullUrlWithQuery(['segment'=>'courier','tab'=>'pending','page'=>1]) }}"
     class="btn-p {{ $segment==='courier'?'primary':'ghost' }}"
     style="gap:7px">
    <i class="bi bi-bicycle"></i> Kuryer
    @if($segment==='seller' && $otherPending > 0)
      <span class="nav-badge warning" style="position:relative;inset:auto">{{ $otherPending }}</span>
    @elseif($segment==='courier' && $counts['pending'] > 0)
      <span style="background:rgba(255,255,255,.2);border-radius:10px;
                   padding:1px 7px;font-size:11px;font-weight:600">
        {{ $counts['pending'] }}
      </span>
    @endif
  </a>
</div>

{{-- ── Stats ───────────────────────────────────────── --}}
<div class="row g-3 mb-3 fade-up">
  @foreach([
    ['pending',  'Kutilmoqda',    'warning', 'bi-hourglass-split', 'pending_amount'],
    ['approved', 'Tasdiqlangan',  'success', 'bi-check-circle',    'approved_amount'],
    ['rejected', 'Rad etilgan',   'danger',  'bi-x-circle',        null],
    ['all',      'Jami',          'muted',   'bi-list-ul',         null],
  ] as [$key, $lbl, $clr, $icon, $amountKey])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:38px;height:38px;border-radius:9px;flex-shrink:0;font-size:17px;
                  background:var(--p-{{ $clr }}-d,var(--p-elevated));
                  color:var(--p-{{ $clr }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          {{ $lbl }}
        </div>
        <div style="font-size:18px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-{{ $clr }})">
          {{ $counts[$key] }}
        </div>
        @if($amountKey)
        <div style="font-size:10px;color:var(--p-hint)">
          {{ number_format($stats[$amountKey]/1000) }}K UZS
        </div>
        @endif
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- ── Status tabs ─────────────────────────────────── --}}
<div class="tab-pills fade-up mb-3">
  @foreach([
    'pending'  => ['Kutilmoqda',   $counts['pending']],
    'approved' => ['Tasdiqlangan', $counts['approved']],
    'rejected' => ['Rad etilgan',  $counts['rejected']],
    'all'      => ['Barchasi',     $counts['all']],
  ] as $key => [$lbl, $cnt])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
     class="tab-pill {{ $tab===$key?'active':'' }}">
    {{ $lbl }} <span class="tab-count">{{ $cnt }}</span>
  </a>
  @endforeach
</div>

{{-- ── Filter ───────────────────────────────────────── --}}
<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="segment" value="{{ $segment }}">
  <input type="hidden" name="tab"     value="{{ $tab }}">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search"
           placeholder="{{ $segment==='courier'?'Kuryer ismi, telefon...':'Do\'kon nomi, telefon...' }}"
           value="{{ request('search') }}">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
  @if(request('search'))
    <a href="{{ request()->fullUrlWithQuery(['search'=>null]) }}"
       class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
  @endif
</form>

{{-- ── Table ────────────────────────────────────────── --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>{{ $segment==='courier'?'Kuryer':'Sotuvchi' }}</th>
          <th>Karta</th>
          <th style="text-align:right">Miqdor</th>
          <th>Komissiya</th>
          <th style="text-align:right">Toza summa</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $tx)
        @php
          $clr = match($tx->status){
            'approved'=>'success','rejected'=>'danger',default=>'warning'
          };
          // Segment ga mos entity
          $entity     = $segment==='courier' ? $tx->courier : $tx->seller;
          $entityName = $segment==='courier'
            ? (($entity->first_name??'').' '.($entity->last_name??''))
            : ($entity->shop_name ?? '—');
          $entityRoute = $segment==='courier'
            ? route('panel.couriers.show', $tx->courier_id ?? 0)
            : route('panel.sellers.show',  $tx->seller_id  ?? 0);
          $entityPhoto = $entity->photo ?? null;
          $entityPhone = $entity->phone_number ?? '—';
          $entityBal   = $entity->balance ?? 0;
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #{{ $tx->id }}
          </td>

          <td>
            @if($entity)
            <div class="d-flex align-items-center gap-2">
              <div style="width:30px;height:30px;border-radius:{{ $segment==='courier'?'50%':'8px' }};
                          overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,
                            {{ $segment==='courier'?'var(--p-info),#0ea5e9':'var(--p-warning),#f97316' }});
                          display:flex;align-items:center;justify-content:center;
                          font-size:12px;font-weight:700;color:#fff">
                @if($entityPhoto)
                  <img src="{{ asset('storage/'.$entityPhoto) }}"
                       style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($entityName,0,1)) }}
                @endif
              </div>
              <div>
                <a href="{{ $entityRoute }}"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  {{ $entityName }}
                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  Balans: {{ number_format($entityBal) }} UZS
                </div>
              </div>
            </div>
            @else
              <span style="color:var(--p-hint);font-size:12px">
                #{{ $segment==='courier' ? $tx->courier_id : $tx->seller_id }}
              </span>
            @endif
          </td>

          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            {{ $tx->card ? '****'.substr($tx->card,-4) : '—' }}
          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:600;color:var(--p-text);font-size:13px">
            {{ number_format($tx->amount) }}
          </td>

          <td>
            @if($tx->commissionPercent)
            <span class="s-pill danger" style="font-size:10px">
              {{ $tx->commissionPercent }}%
              @if($tx->commissionPrice)
                <span style="opacity:.7">· {{ number_format($tx->commissionPrice) }}</span>
              @endif
            </span>
            @else
              <span style="color:var(--p-hint);font-size:12px">—</span>
            @endif
          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:700;color:var(--p-success);font-size:13px">
            {{ number_format($tx->netAmount ?? $tx->amount) }}
          </td>

          <td>
            <span class="s-pill {{ $clr }}" style="font-size:10px">
              {{ match($tx->status){
                'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda'
              } }}
            </span>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $tx->created_at?->format('d.m.Y H:i') }}
          </td>

          <td>
            <div class="d-flex gap-1 align-items-center">

              {{-- Tasdiqlash (faqat pending) --}}
              @if($tx->status === 'pending')
              <form method="POST"
                    action="{{ route('panel.seller-transactions.approve', $tx->id) }}"
                    onsubmit="return confirm('Tasdiqlashni xohlaysizmi?')">
                @csrf @method('PATCH')
                <input type="hidden" name="segment" value="{{ $segment }}">
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              @endif

              {{-- Rad etish --}}
              @if($tx->status !== 'rejected')
              <button class="btn-p danger sm"
                      onclick="openReject(
                        {{ $tx->id }},
                        '{{ addslashes($entityName) }}',
                        {{ $tx->amount }},
                        '{{ $tx->status }}',
                        '{{ $segment }}'
                      )" title="Rad etish">
                <i class="bi bi-x-lg"></i>
              </button>
              @endif

              {{-- Ko'rish --}}
              <a href="{{ route('panel.seller-transactions.show', $tx->id) }}?segment={{ $segment }}"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:30px;display:block;margin-bottom:8px"></i>
            Tranzaksiyalar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($transactions->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
      / {{ $transactions->total() }}
    </div>
    {{ $transactions->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

{{-- ── Reject modal ─────────────────────────────────── --}}
<div id="reject-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
            z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border-radius:14px;padding:24px;
              width:100%;max-width:460px;border:1px solid var(--p-border);
              box-shadow:0 20px 60px rgba(0,0,0,.4)">
    <div class="d-flex align-items-start justify-content-between mb-3">
      <div>
        <div style="font-size:16px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-x-circle-fill me-1" style="color:var(--p-danger)"></i>
          Rad etish
        </div>
        <div id="reject-subtitle" style="font-size:12px;color:var(--p-hint);margin-top:3px"></div>
      </div>
      <button onclick="closeReject()"
              style="background:none;border:none;cursor:pointer;
                     color:var(--p-muted);font-size:20px;line-height:1">×</button>
    </div>

    <div id="reject-warn-approved"
         style="display:none;padding:10px 12px;background:var(--p-warning-d);
                border-radius:8px;border:1px solid rgba(245,166,35,.2);
                margin-bottom:14px;font-size:12px;color:var(--p-warning)">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      Bu tranzaksiya oldin <strong>tasdiqlangan</strong> edi.
      Rad etilsa <strong><span id="reject-amount"></span> UZS qaytariladi</strong>.
    </div>

    <form id="reject-form" method="POST">
      @csrf @method('PATCH')
      <input type="hidden" name="segment" id="reject-segment" value="seller">
      <label class="p-form-label">
        Rad etish sababi <span style="color:var(--p-danger)">*</span>
      </label>
      <textarea name="rejected_desc" class="p-form-control" rows="3" required
                style="margin-bottom:14px"
                placeholder="Karta ma'lumotlari noto'g'ri, hujjat taqdim etilmadi...">
      </textarea>
      <div class="d-flex gap-2 justify-content-end">
        <button type="button" onclick="closeReject()" class="btn-p ghost">Bekor</button>
        <button type="submit" class="btn-p danger">
          <i class="bi bi-x-circle"></i> Rad etish
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
function openReject(id, name, amount, status, segment) {
  document.getElementById('reject-form').action =
    `/panel/seller-transactions/${id}/reject`;
  document.getElementById('reject-segment').value  = segment;
  document.getElementById('reject-subtitle').textContent =
    name + ' · ' + amount.toLocaleString() + ' UZS';
  document.getElementById('reject-amount').textContent =
    amount.toLocaleString();

  document.getElementById('reject-warn-approved').style.display =
    status === 'approved' ? 'block' : 'none';

  const modal = document.getElementById('reject-modal');
  modal.style.display = 'flex';
  modal.querySelector('textarea').value = '';
  setTimeout(() => modal.querySelector('textarea').focus(), 80);
}

function closeReject() {
  document.getElementById('reject-modal').style.display = 'none';
}

document.getElementById('reject-modal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeReject();
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeReject();
});
</script>
@endpush