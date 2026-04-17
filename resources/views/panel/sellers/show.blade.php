@extends('panel.layouts.panel')
@section('title', $seller->shop_name)
@section('page-title', $seller->shop_name)

@section('content')

@php
  $st    = trim((string)($seller->status ?? ''));
  $stCls = match($st){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
  $stLbl = match($st){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda' };
  $types = $seller->activity_types; // accessor — har doim array
@endphp

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.sellers.index') }}" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">{{ $seller->shop_name }}</h1>
      <p class="page-sub" style="display:flex;align-items:center;gap:8px">
        ID: #{{ $seller->id }}
        <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
        @if($seller->is_hidden)
          <span class="s-pill danger" style="font-size:11px">Yashirin</span>
        @endif
      </p>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    @if($st !== 'approved')
    <form method="POST" action="{{ route('panel.sellers.approve', $seller) }}">
      @csrf @method('PATCH')
      <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
    </form>
    @endif
    @if($st !== 'rejected')
    <form method="POST" action="{{ route('panel.sellers.reject', $seller) }}"
          onsubmit="return confirm('Rad etasizmi?')">
      @csrf @method('PATCH')
      <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Rad etish</button>
    </form>
    @endif
    <a href="{{ route('panel.sellers.edit', $seller) }}" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    [$seller->books->count(),                          'Kitoblar',    'accent',  'bi-book'],
    [$seller->stationeries->count(),                   'Kanstovar',   'warning', 'bi-pencil-square'],
    [number_format($orderCount),                       'Buyurtmalar', 'success', 'bi-bag-check'],
    [number_format($totalRevenue/1_000_000,1).'M UZS', 'Daromad',     'info',    'bi-graph-up'],
  ] as [$val,$lbl,$clr,$icon])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:16px">
      <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;font-size:18px;
                  background:var(--p-{{ $clr }}-d,var(--p-elevated));
                  color:var(--p-{{ $clr }});display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-text)">{{ $val }}</div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em">{{ $lbl }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<div class="row g-3">

  {{-- ── CHAP: Profil ─────────────────────────────────────────── --}}
  <div class="col-xl-4">

    <div class="p-card mb-3 fade-up">
      <div style="text-align:center;padding:24px 20px 16px">
        <div style="width:72px;height:72px;border-radius:12px;margin:0 auto 12px;overflow:hidden;
                    background:linear-gradient(135deg,var(--p-warning),#f97316);
                    display:flex;align-items:center;justify-content:center;
                    font-size:28px;font-weight:700;color:#fff">
          @if($seller->photo)
            <img src="{{ Storage::url($seller->photo) }}"
                 style="width:100%;height:100%;object-fit:cover">
          @else
            {{ strtoupper(substr($seller->shop_name, 0, 1)) }}
          @endif
        </div>
        <div style="font-size:17px;font-weight:700;color:var(--p-text)">
          {{ $seller->shop_name }}
        </div>
        <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
          {{ $seller->firstname }} {{ $seller->lastname }}
        </div>
        <div class="d-flex justify-content-center gap-2 mt-2">
          <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
          @if($seller->is_hidden)
            <span class="s-pill danger" style="font-size:11px">Yashirin</span>
          @endif
        </div>
      </div>

      <div style="border-top:1px solid var(--p-border);padding:16px 20px 8px">
        @foreach([
          ['bi-telephone',   'Telefon',         $seller->phone_number],
          ['bi-geo-alt',     'Viloyat',          $seller->region],
          ['bi-star-fill',   'Reyting',          number_format($seller->rating ?? 0, 1).' / 5.0'],
          ['bi-bag-check',   'Muvaffaqiyatli',   number_format($seller->successful_orders ?? 0).' buyurtma'],
          ['bi-wallet2',     'Balans',           number_format($seller->balance ?? 0).' UZS'],
          ['bi-percent',     'Komissiya',        ($seller->commission_percent ? $seller->commission_percent.'%' : 'Global')],
          ['bi-calendar',    "Qo'shildi",        $seller->created_at?->format('d.m.Y')],
        ] as [$icon,$label,$value])
        <div class="d-flex align-items-start gap-3 mb-3">
          <div style="width:28px;height:28px;border-radius:7px;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi {{ $icon }}" style="font-size:12px;color:var(--p-muted)"></i>
          </div>
          <div>
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em">{{ $label }}</div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $value }}</div>
          </div>
        </div>
        @endforeach

        @if(count($types))
        <div class="d-flex flex-wrap gap-1 mt-1 pb-2">
          @foreach($types as $type)
            <span class="s-pill accent" style="font-size:11px">{{ $type }}</span>
          @endforeach
        </div>
        @endif
      </div>

      @if($isMainShop)
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <a href="{{ route('panel.sellers.staff.create', $seller) }}" class="btn-p ghost"
           style="width:100%;justify-content:center">
          <i class="bi bi-person-plus"></i> Hodim qo'shish
        </a>
      </div>
      @else
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">Asosiy do'kon</div>
        @if($parentShop)
        <a href="{{ route('panel.sellers.show', $parentShop) }}"
           style="font-size:13px;font-weight:500;color:var(--p-accent);text-decoration:none">
          {{ $parentShop->shop_name }} →
        </a>
        @endif
      </div>
      @endif
    </div>

    {{-- Manzillar --}}
    @if($locations->count())
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-geo-alt me-1"></i> Manzillar</div>
        <span class="s-pill muted" style="font-size:10px">{{ $locations->count() }} ta</span>
      </div>
      <div style="padding:0 18px 14px">
        @foreach($locations as $loc)
        <div style="padding:9px 0;border-bottom:1px solid var(--p-border);
                    {{ $loop->last ? 'border-bottom:none' : '' }}">
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">
            {{ $loc->fullAddress }}
            @if($loc->is_main)
              <span class="s-pill success ms-1" style="font-size:10px">Asosiy</span>
            @endif
          </div>
          @if($loc->description ?? null)
          <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
            {{ $loc->description }}
          </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Ban loglari --}}
    @if($banLogs->count())
    <div class="p-card mb-3 fade-up"
         style="border-color:rgba(255,92,106,.2);background:var(--p-danger-d)">
      <div class="p-card-header">
        <div class="p-card-title" style="color:var(--p-danger)">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> Ban loglari
        </div>
        <span class="s-pill danger" style="font-size:10px">{{ $banLogs->count() }}</span>
      </div>
      <div style="padding:0 18px 14px">
        @foreach($banLogs as $log)
        <div style="padding:9px 0;border-bottom:1px solid rgba(255,92,106,.15);
                    {{ $loop->last ? 'border-bottom:none' : '' }}">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="s-pill {{ ($log->type ?? '')=='warning' ? 'warning' : 'muted' }}"
                  style="font-size:10px">
              {{ ($log->type ?? '') === 'warning' ? 'Ogohlantirish' : 'Ban' }}
            </span>
            <span style="font-size:10px;color:var(--p-hint)">
              {{ $log->created_at?->format('d.m.Y') }}
            </span>
          </div>
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $log->title }}</div>
          @if($log->message ?? null)
          <div style="font-size:12px;color:var(--p-muted);margin-top:2px">
            {{ Str::limit($log->message, 80) }}
          </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  {{-- ── O'NG: Tabs ───────────────────────────────────────────── --}}
  <div class="col-xl-8">

    @php $activeTab = request('section', 'orders'); @endphp
    <div class="d-flex gap-2 flex-wrap mb-3 fade-up">
      @foreach([
        ['orders',       'Buyurtmalar',    'bi-bag-check',  $orderCount],
        ['transactions', 'Tranzaksiyalar', 'bi-credit-card', null],
        ['staff',        'Hodimlar',       'bi-people',      $staff->count()],
        ['ads',          'Reklamalar',     'bi-megaphone',   $ads->count()],
      ] as [$key,$lbl,$icon,$cnt])
      <a href="{{ request()->fullUrlWithQuery(['section'=>$key]) }}"
         class="btn-p {{ $activeTab===$key?'':'ghost' }} sm" style="gap:5px">
        <i class="bi {{ $icon }}"></i> {{ $lbl }}
        @if($cnt !== null)
          <span class="tab-badge">{{ $cnt }}</span>
        @endif
      </a>
      @endforeach
    </div>

    {{-- Buyurtmalar --}}
    @if($activeTab === 'orders')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">So'nggi buyurtmalar</div>
        <a href="{{ route('panel.seller-orders.index', ['seller_id'=>$seller->id]) }}"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr><th>#ID</th><th>Summa</th><th>Yetkazish</th><th>Status</th><th>Sana</th></tr>
          </thead>
          <tbody>
            @forelse($recentOrders as $order)
            @php
              $os = trim((string)($order->status ?? ''));
              $oCls = match($os){
                'completed'=>'success','processing'=>'info',
                'cancelled'=>'danger',default=>'warning'
              };
              $oLbl = match($os){
                'completed'=>'Yakunlandi','processing'=>'Jarayonda',
                'cancelled'=>'Bekor',default=>'Kutilmoqda'
              };
            @endphp
            <tr>
              <td>
                <a href="{{ route('panel.seller-orders.show', $order->id) }}"
                   style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">
                  #{{ $order->order_id ?? $order->id }}
                </a>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:500;color:var(--p-text)">
                {{ number_format($order->amount ?? 0) }} UZS
              </td>
              <td style="font-size:12px;color:var(--p-muted)">
                {{ $order->delivery_type ?? '—' }}
              </td>
              <td><span class="s-pill {{ $oCls }}" style="font-size:11px">{{ $oLbl }}</span></td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                {{ $order->created_at?->format('d.m H:i') }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">
                Buyurtmalar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Tranzaksiyalar --}}
    @if($activeTab === 'transactions')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Tranzaksiyalar</div>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr><th>#</th><th>Miqdor</th><th>Komissiya</th><th>Toza</th><th>Status</th><th>Sana</th></tr>
          </thead>
          <tbody>
            @forelse($transactions as $tx)
            @php
              $txS   = trim((string)($tx->status ?? ''));
              $txCls = match($txS){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
              $txLbl = match($txS){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda' };
            @endphp
            <tr>
              <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#{{ $tx->id }}</td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                {{ number_format($tx->amount ?? 0) }}
              </td>
              <td style="font-size:12px;color:var(--p-danger)">
                {{ $tx->commissionPercent ?? 0 }}%
                @if($tx->commissionPrice ?? null)
                  <span style="color:var(--p-hint)">({{ number_format($tx->commissionPrice) }})</span>
                @endif
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-success)">
                {{ number_format($tx->netAmount ?? $tx->amount ?? 0) }}
              </td>
              <td><span class="s-pill {{ $txCls }}" style="font-size:11px">{{ $txLbl }}</span></td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                {{ $tx->created_at?->format('d.m.Y H:i') }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" style="text-align:center;padding:24px;color:var(--p-hint)">
                Tranzaksiyalar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Hodimlar --}}
    @if($activeTab === 'staff')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Hodimlar</div>
        @if($isMainShop)
        <a href="{{ route('panel.sellers.staff.create', $seller) }}" class="btn-p primary sm">
          <i class="bi bi-plus"></i> Qo'shish
        </a>
        @endif
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr><th>Hodim</th><th>Telefon</th><th>Rol</th><th>Holat</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($staff as $member)
            @php
              $ms   = trim((string)($member->staff_status ?? 'active'));
              $mCls = $ms === 'active' ? 'success' : 'muted';
              $mLbl = $ms === 'active' ? 'Faol' : 'Nofaol';
            @endphp
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                              display:flex;align-items:center;justify-content:center;
                              font-size:12px;font-weight:600;color:#fff">
                    @if($member->photo)
                      <img src="{{ Storage::url($member->photo) }}"
                           style="width:100%;height:100%;object-fit:cover">
                    @else
                      {{ strtoupper(substr($member->firstname ?? 'H', 0, 1)) }}
                    @endif
                  </div>
                  <span style="font-size:13px;font-weight:500;color:var(--p-text)">
                    {{ $member->firstname }} {{ $member->lastname }}
                  </span>
                </div>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
                {{ $member->phone_number }}
              </td>
              <td>
                <span class="s-pill muted" style="font-size:11px">
                  {{ \App\Http\Controllers\Panel\SellerController::ROLES[$member->role ?? ''] ?? ($member->role ?? '—') }}
                </span>
              </td>
              <td><span class="s-pill {{ $mCls }}" style="font-size:11px">{{ $mLbl }}</span></td>
              <td>
                <div class="d-flex gap-1">
                  <form method="POST"
                        action="{{ route('panel.sellers.staff.toggle', $member) }}">
                    @csrf @method('PATCH')
                    <button class="btn-p ghost sm"
                            title="{{ $ms === 'active' ? "To'xtatish" : 'Faollashtirish' }}">
                      <i class="bi bi-{{ $ms === 'active' ? 'pause' : 'play' }}-fill"></i>
                    </button>
                  </form>
                  <a href="{{ route('panel.sellers.edit', $member) }}" class="btn-p ghost sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">
                Hodimlar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Reklamalar --}}
    @if($activeTab === 'ads')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Reklamalar</div>
        <a href="{{ route('panel.seller-ads.index', ['seller_id'=>$seller->id]) }}"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr><th>Reklama</th><th>Format</th><th>Moderatsiya</th><th>Muddat</th></tr>
          </thead>
          <tbody>
            @forelse($ads as $ad)
            @php
              $adM   = trim((string)($ad->moderation ?? ''));
              $adCls = match($adM){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
              $adLbl = match($adM){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda' };
            @endphp
            <tr>
              <td>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  {{ Str::limit($ad->title ?? $ad->name ?? '—', 30) }}
                </div>
                @if($ad->subtitle ?? null)
                <div style="font-size:11px;color:var(--p-hint)">{{ Str::limit($ad->subtitle, 40) }}</div>
                @endif
              </td>
              <td style="font-size:12px;color:var(--p-muted)">{{ $ad->format ?? '—' }}</td>
              <td>
                <span class="s-pill {{ $adCls }}" style="font-size:11px">{{ $adLbl }}</span>
              </td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                @if($ad->expires_at ?? null)
                  {{ \Carbon\Carbon::parse($ad->expires_at)->format('d.m.Y') }}
                @else
                  —
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align:center;padding:24px;color:var(--p-hint)">
                Reklamalar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

  </div>
</div>

@endsection