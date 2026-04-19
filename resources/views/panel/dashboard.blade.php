@extends('panel.layouts.panel')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Header --}}
<x-panel.page-header>
  <x-slot name="heading">Dashboard</x-slot>
  <x-slot name="actions">
    <a href="{{ route('panel.dashboard',['clear_cache'=>1]) }}" class="btn-p ghost">
        <i class="bi bi-arrow-clockwise"></i> Yangilash
      </a>
  </x-slot>
</x-panel.page-header>

@php
  $dashAdmin = $admin ?? auth('panel')->user();
  $dashQuick = [];
  if ($dashAdmin?->hasPermission('orders')) {
    $dashQuick[] = ['Buyurtmalar', 'bi-bag-check-fill', route('panel.orders.index'), $pendingOrders > 0 ? $pendingOrders.' ta kutilmoqda' : 'Barcha statuslar', 'rgba(70,95,255,.12)', 'var(--p-accent)'];
  }
  if ($dashAdmin?->hasPermission('users')) {
    $dashQuick[] = ['Foydalanuvchilar', 'bi-people-fill', route('panel.users.index'), number_format($totalUsers).' ro‘yxatda', 'rgba(18,183,106,.12)', 'var(--p-success)'];
  }
  if ($dashAdmin?->hasPermission('books')) {
    $kb = (int) ($kangarooHumanReviewBooks ?? 0);
    $dashQuick[] = ['Kitoblar', 'bi-book-fill', route('panel.books.index'), $kb > 0 ? $kb.' ta Kangaroo inson navbati · katalog' : 'Katalog va moderatsiya', 'rgba(11,111,168,.12)', 'var(--p-info)'];
  }
  if ($dashAdmin?->hasPermission('stationery')) {
    $ks = (int) ($kangarooHumanReviewStationery ?? 0);
    $dashQuick[] = ['Kanstovar', 'bi-pencil-square', route('panel.stationery.index'), $ks > 0 ? $ks.' ta Kangaroo inson navbati' : 'Mahsulotlar', 'rgba(247,144,9,.12)', 'var(--p-warning)'];
  }
  if ($dashAdmin?->hasPermission('sellers')) {
    $dashQuick[] = ['Sotuvchilar', 'bi-shop-window', route('panel.sellers.index'), $pendingSellers > 0 ? $pendingSellers.' ariza' : 'Do‘konlar', 'rgba(124,92,252,.12)', '#7c5cfc'];
  }
  if ($dashAdmin?->hasPermission('settings')) {
    $kUgc = (int) ($kangarooUgcAdminQueue ?? 0);
    if ($kUgc > 0) {
      $dashQuick[] = ['UGC (Kangaroo)', 'bi-stars', route('panel.book-club.moderation-queue'), $kUgc.' ta admin navbati', 'rgba(247,144,9,.18)', 'var(--p-warning)'];
    }
    $pendingPay = ($pendingSellerTxCount ?? 0) + ($pendingCourierTxCount ?? 0);
    $dashQuick[] = ['Tranzaksiyalar', 'bi-arrow-left-right', route('panel.seller-transactions.index'), $pendingPay > 0 ? $pendingPay.' kutilayotgan' : 'Hisob-kitoblar', 'rgba(70,95,255,.1)', 'var(--p-accent)'];
    $dashQuick[] = ['Shikoyatlar', 'bi-flag-fill', route('panel.reports.index'), 'Moderatsiya', 'rgba(240,68,56,.1)', 'var(--p-danger)'];
    $dashQuick[] = ['Support', 'bi-headset', route('panel.bot-tickets.index'), 'Murojaatlar', 'rgba(100,116,139,.15)', 'var(--p-muted)'];
    $dashQuick[] = ['Sozlamalar', 'bi-gear-fill', route('panel.settings.index'), 'Tizim', 'rgba(100,116,139,.12)', 'var(--p-hint)'];
  }
@endphp

@if(count($dashQuick))
<div class="mb-5 fade-up">
  <div class="dash-quick-section-title mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Tezkor havolalar</div>
  <div class="dash-quick-grid">
    @foreach($dashQuick as $q)
      <a href="{{ $q[2] }}" class="dash-quick-card">
        <div class="dq-ico" style="background:{{ $q[4] }};color:{{ $q[5] }}"><i class="bi {{ $q[1] }}"></i></div>
        <span class="dq-lbl">{{ $q[0] }}</span>
        <span class="dq-hint">{{ $q[3] }}</span>
      </a>
    @endforeach
  </div>
</div>
@endif

{{-- ALERTS --}}
@if(!empty($alerts))
<div class="fade-up mb-4">
  @foreach($alerts as [$color,$icon,$title,$desc,$url])
  <div class="alert-item {{ $color }}">
    <i class="bi {{ $icon }} alert-item__icon"></i>
    <div class="alert-item__body"><span class="alert-item__title">{{ $title }}:</span> {{ $desc }}</div>
    <a href="{{ $url }}" class="alert-item__link">Ko'rish →</a>
  </div>
  @endforeach
</div>
@endif

{{-- Operativ: qisqa metrikalar + 7 kun (KPI dan oldin — avvalo trend) --}}
<div class="dash-section fade-up">
  <div class="dash-section-head">
    <h2 class="dash-section-title">Operativ ko‘rinish</h2>
    <p class="dash-section-desc">Bugun va hafta bo‘yicha tezkor raqamlar; diagrammalar katta ekranda yonma-yon.</p>
  </div>
  <div class="dash-insight-grid mb-4">
    <div class="dash-insight-pill dip-success">
      <div class="dip-lbl">Bugungi daromad</div>
      <div class="dip-val">{{ number_format($todayRevenue / 1_000_000, 2) }} <span class="dip-val-unit">M</span></div>
    </div>
    <div class="dash-insight-pill">
      <div class="dip-lbl">Bugun buyurtma</div>
      <div class="dip-val">{{ number_format($todayOrders) }} <span class="dip-val-unit">ta</span></div>
    </div>
    <div class="dash-insight-pill dip-accent">
      <div class="dip-lbl">Hafta daromad</div>
      <div class="dip-val">{{ number_format($weekRevenue / 1_000_000, 2) }} <span class="dip-val-unit">M</span></div>
    </div>
    <div class="dash-insight-pill dip-info">
      <div class="dip-lbl">Hafta buyurtma</div>
      <div class="dip-val">{{ number_format($weekOrders) }} <span class="dip-val-unit">ta</span></div>
    </div>
    <div class="dash-insight-pill dip-warning">
      <div class="dip-lbl">Kutilmoqda</div>
      <div class="dip-val">{{ number_format($pendingOrders) }} <span class="dip-val-unit">ta</span></div>
    </div>
  </div>
  <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Buyurtmalar</div>
          <div class="dash-card-sub">Oxirgi 7 kun · soni</div>
        </div>
        <a href="{{ route('panel.orders.index') }}" class="btn-p ghost sm">Ro‘yxat <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartOrdersWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">To‘langan daromad</div>
          <div class="dash-card-sub">Oxirgi 7 kun · mln UZS</div>
        </div>
        <span class="hidden text-xs font-medium text-gray-500 sm:inline dark:text-gray-400">To‘langan</span>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartRevenueWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
  </div>
</div>

<div class="dash-section fade-up mb-1">
  <h2 class="dash-section-title">Batafsil ko‘rsatkichlar</h2>
  <p class="dash-section-desc">Jami va bo‘limlar bo‘yicha; kartani bosing yoki ustiga keling.</p>
</div>

{{-- ROW 1: KPI (6 ta — katta ekranda bir qator) --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6 xl:gap-3">

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--success"><i class="bi bi-graph-up-arrow"></i></div>
        <span class="kpi-change up"><i class="bi bi-arrow-up-short kpi-change-ico"></i> Bugun: {{ number_format($todayRevenue/1000) }}K</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Jami daromad</div>
        <div class="kpi-value">{{ number_format($totalRevenue/1_000_000,1) }}<span class="kpi-value-unit"> M UZS</span></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Bu oy</span>
        <span class="kpi-foot-mono">{{ number_format($monthRevenue/1_000_000,1) }}M</span>
      </div>
    </div>
  </div>

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--accent"><i class="bi bi-bag-check"></i></div>
        <span class="kpi-change up"><i class="bi bi-plus kpi-change-ico"></i> {{ $todayOrders }} bugun</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Buyurtmalar</div>
        <div class="kpi-value">{{ number_format($totalOrders) }}</div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-warn"><i class="bi bi-clock kpi-footer-ico"></i> {{ $pendingOrders }} kutmoqda</span>
        <span class="kpi-foot-danger"><i class="bi bi-x-circle kpi-footer-ico"></i> {{ $cancelledOrders }} bekor</span>
      </div>
    </div>
  </div>

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--info"><i class="bi bi-patch-check"></i></div>
        <span class="kpi-change {{ $completionRate >= 70 ? 'up' : 'neutral' }}">{{ $completionRate }}%</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Yakunlanish darajasi</div>
        <div class="kpi-value">{{ number_format($completedOrders) }}</div>
      </div>
      <div class="kpi-footer">
        <div class="kpi-prog-cell">
          <div class="dash-prog-track dash-prog-track--thin">
            <div class="dash-prog-fill" style="width:{{ $completionRate }}%;background:var(--p-info)"></div>
          </div>
        </div>
        <span class="kpi-foot-hint-xs">{{ $cancellationRate }}% bekor</span>
      </div>
    </div>
  </div>

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--warning"><i class="bi bi-people"></i></div>
        <span class="kpi-change up"><span class="live-dot live-dot--xs"></span>{{ $onlineUsers }} online</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Foydalanuvchilar</div>
        <div class="kpi-value">{{ number_format($totalUsers) }}</div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Bugun yangi</span>
        <span class="kpi-foot-mono kpi-foot-mono--success">+{{ $newUsersToday }}</span>
      </div>
    </div>
  </div>

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--gift"><i class="bi bi-gift"></i></div>
        <span class="kpi-change {{ $giftUsed>0?'up':'neutral' }}"><i class="bi bi-check-circle kpi-change-ico-sm"></i> {{ $giftUsed }} ishlatildi</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Gift Sertifikatlar</div>
        <div class="kpi-value">{{ number_format($giftTotal) }}</div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Yuborilgan</span>
        @if($giftPending>0)
          <span class="kpi-foot-warn-strong">{{ $giftPending }} kutmoqda</span>
        @else
          <span class="kpi-foot-mono">{{ $giftSent }}</span>
        @endif
      </div>
    </div>
  </div>

  <div class="fade-up">
    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--teal"><i class="bi bi-box-seam"></i></div>
        @if($mysteryDueCount>0)
          <span class="kpi-change down"><i class="bi bi-exclamation-triangle kpi-change-ico-sm"></i> {{ $mysteryDueCount }} navbatda</span>
        @else
          <span class="kpi-change neutral">Navbat yo'q</span>
        @endif
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Mystery Box</div>
        <div class="kpi-value">{{ number_format($mysteryActive) }}</div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Faol obuna</span>
        <span class="kpi-foot-hint-sm">{{ $mysteryPending }} kutmoqda</span>
      </div>
    </div>
  </div>
</div>

{{-- ROW 2: Diagrammalar --}}
<div class="grid grid-cols-1 gap-3 sm:gap-4 xl:grid-cols-12 xl:gap-4">
  <div class="xl:col-span-8 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Daromad dinamikasi</div>
          <div class="dash-card-sub">Oxirgi 6 oy · UZS</div>
        </div>
        <div class="flex items-center gap-2">
          <div class="period-toggle" id="revPeriodToggle">
            <button class="period-btn active" data-period="month" onclick="switchRevPeriod(this,'month')">Oy</button>
            <button class="period-btn" data-period="week" onclick="switchRevPeriod(this,'week')">Hafta</button>
            <button class="period-btn" data-period="today" onclick="switchRevPeriod(this,'today')">Bugun</button>
          </div>
          <a href="{{ route('panel.orders.index') }}" class="btn-p ghost sm">Buyurtmalar <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartRevenue" class="dash-chart-host dash-chart-host--288"></div></div>
      </div>
    </div>
  </div>
  <div class="xl:col-span-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div><div class="dash-card-title">Holat taqsimoti</div><div class="dash-card-sub">Jami {{ number_format($totalOrders) }} ta</div></div>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartDonut" class="dash-chart-host dash-chart-host--210"></div></div>
        {{-- TailAdmin-style divider stats --}}
        <div class="donut-stat-row">
          @foreach([['Yetkazildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']] as $idx => [$l,$v,$c])
          @if($idx > 0)<div class="stat-divider"></div>@endif
          <div class="stat-cell donut-stat-cell">
            <div class="stat-cell-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="stat-cell-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ROW 3: MOLIYAVIY HISOBOT (superadmin only) --}}
@if($isSuperAdmin)
<div class="grid grid-cols-1 xl:grid-cols-12 gap-3 mb-4">

  {{-- Daromad-chiqim tahlili --}}
  <div class="xl:col-span-5 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi bi-calculator mr-1 dash-card-ico-accent"></i>Moliyaviy hisobot</div>
        <div class="dash-card-sub">Jami · Bu oy</div>
      </div>
      <div class="dash-card-body">

        @php
          $finRows = [
            ['accent',  'bi-activity',         'GMV (brutto aylanma)',  'Barcha buyurtmalar summasi',                      $gmvTotal,            $gmvMonth,            false],
            ['success', 'bi-check-circle',      "To'langan daromad",    'paymentStatus = 2',                               $totalRevenue,        $monthRevenue,        false],
            ['info',    'bi-truck',             'Yetkazish daromadi',   'Delivery fee',                                    $totalDeliveryIncome, $monthDeliveryIncome, false],
            ['purple',  'bi-percent',           'Seller komissiya',     "O'rtacha {$avgCommissionPct}%",                  $totalCommissionEarned,$monthCommissionEarned,false],
            ['teal',    'bi-box-seam',          'Mystery Box daromad',  'Faol + yakunlangan',                              $mysteryRevTotal,     $mysteryRevMonth,     false],
            ['pink',    'bi-gift',              'Gift Sertifikat',      'Ishlatilgan · Buyurtmada: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0,false],
        ];
          $finCosts = [
            ['danger',  'bi-ticket-perforated', 'Promokod chegirma',    "{$promoOrdersCount} ta buyurtmada",               $totalPromoDiscount,  $monthPromoDiscount,  true],
            ['warning', 'bi-cash-stack',        'Cashback to\'lovi',    "Foydalanuvchilarga qaytarildi",                  $totalCashbackPaid,   $monthCashbackPaid,   true],
            ['muted',   'bi-shop-window',       'Seller to\'lovlari',   "Approved · Kutilmoqda: ".number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout,true],
            ['muted',   'bi-bicycle',           'Kuryer to\'lovlari',   "Delivered · Kutilmoqda: ".number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout,true],
            ['danger',  'bi-x-circle',          'Bekor buyurtma',       'status=F buyurtmalar',                            $cancelledRevLoss,    $cancelledMonthLoss,  true],
        ];
        @endphp

        @foreach($finRows as [$clr,$ico,$lbl,$sub,$total,$month,$minus])
        <div class="fin-row">
          @php
            $finBg  = match($clr){ 'purple'=>'rgba(124,92,252,.12)', 'teal'=>'rgba(20,184,166,.1)', 'pink'=>'rgba(236,72,153,.1)', default=>"var(--p-{$clr}-d)" };
            $finClr = match($clr){ 'purple'=>'#7c5cfc', 'teal'=>'#14b8a6', 'pink'=>'#ec4899', default=>"var(--p-{$clr})" };
          @endphp
          <div class="fin-icon" style="background:{{ $finBg }};color:{{ $finClr }}">
            <i class="bi {{ $ico }}"></i>
          </div>
          <div class="grow">
            <div class="fin-title">{{ $lbl }}</div>
            <div class="fin-sub">{{ $sub }}</div>
          </div>
          <div>
            <div class="fin-val" style="color:{{ $finClr }}">
              {{ number_format($total/1_000_000,1) }}M
            </div>
            @if($month > 0)<div class="fin-month">Bu oy: {{ number_format($month/1000) }}K</div>@endif
          </div>
        </div>
        @endforeach

        <div class="fin-divider"></div>

        @foreach($finCosts as [$clr,$ico,$lbl,$sub,$total,$month,$minus])
        <div class="fin-row">
          @php $costBg = $clr==='muted' ? 'var(--p-elevated)' : "var(--p-{$clr}-d)"; @endphp
          <div class="fin-icon" style="background:{{ $costBg }};color:var(--p-{{ $clr }})">
            <i class="bi {{ $ico }}"></i>
          </div>
          <div class="grow">
            <div class="fin-title fin-lbl-tone-{{ $clr }}">{{ $lbl }}</div>
            <div class="fin-sub">{{ $sub }}</div>
          </div>
          <div>
            <div class="fin-val" style="color:var(--p-{{ $clr }})">−{{ number_format($total/1_000_000,1) }}M</div>
            @if($month > 0)<div class="fin-month">Bu oy: −{{ number_format($month/1000) }}K</div>@endif
          </div>
        </div>
        @endforeach

        <div class="fin-divider"></div>

        {{-- Platform sof foyda --}}
        <div class="fin-row fin-row--profit">
          <div class="fin-icon fin-icon--success-plain">
            <i class="bi bi-stars"></i>
          </div>
          <div class="grow">
            <div class="fin-title--bold">Platform sof foyda</div>
            <div class="fin-sub">Komissiya + Yetkazish − Chiqimlar</div>
          </div>
          <div>
            <div class="fin-val fin-val--lg fin-val--success">
              {{ number_format($platformProfit/1_000_000,2) }}M
            </div>
            <div class="fin-month">Bu oy: {{ number_format($platformProfitMonth/1000) }}K</div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- O'ng: AOV + Mahsulot turi + Xaridorlar --}}
  <div class="xl:col-span-7 fade-up">

    <div class="dash-card mb-3">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">AOV trend (o'rtacha buyurtma)</div>
          <div class="dash-card-sub">Joriy: {{ number_format($avgOrderValue) }} UZS · O'rtacha komissiya: {{ $avgCommissionPct }}%</div>
        </div>
      </div>
      <div class="dash-card-body"><div id="chartAov" class="dash-chart-host dash-chart-host--130"></div></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Mahsulot turi</div>
            <div class="dash-card-sub">Daromad bo'yicha</div>
          </div>
          <div class="dash-card-body">
            @php
              $typeTotal = max(1,$revenueByType['book']+$revenueByType['stationery']);
              $bookPct = round($revenueByType['book']/$typeTotal*100,1);
              $statPct = round($revenueByType['stationery']/$typeTotal*100,1);
            @endphp
            <div id="chartTypePie" class="dash-chart-host dash-chart-host--120"></div>
            @foreach([['Kitoblar',$revenueByType['book'],'accent','bi-book',$bookPct],['Kanstovar',$revenueByType['stationery'],'warning','bi-pencil-square',$statPct]] as [$l,$v,$c,$i,$p])
            <div class="type-legend-row">
              <i class="bi {{ $i }} type-legend-ico type-legend-ico--{{ $c }}"></i>
              <span class="type-legend-label">{{ $l }}</span>
              <span class="type-legend-val">{{ number_format($v/1000) }}K</span>
              <span class="s-pill {{ $c }} type-legend-pill">{{ $p }}%</span>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Xaridorlar (bu oy)</div>
            <div class="dash-card-sub">Yangi vs Takroriy</div>
          </div>
          <div class="dash-card-body">
            @php $totalB=max(1,$repeatBuyersMonth+$newBuyersMonth); $repeatPct=$totalB>1?round($repeatBuyersMonth/$totalB*100):0; @endphp
            <div id="chartBuyers" class="dash-chart-host dash-chart-host--120"></div>
            @foreach([['Yangi xaridor',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'accent','2+ marta']] as [$l,$v,$c,$s])
            <div class="buyer-legend-row">
              <div class="buyer-legend-dot buyer-legend-dot--{{ $c }}"></div>
              <div class="buyer-legend-stack">
                <div class="buyer-legend-name">{{ $l }}</div>
                <div class="buyer-legend-sub">{{ $s }}</div>
              </div>
              <span class="buyer-legend-count">{{ number_format($v) }}</span>
            </div>
            @endforeach
            <div class="dash-tile-divider">
              <div class="dash-repeat-head">
                <span>Qayta qaytish</span>
                <span class="dash-repeat-pct">{{ $repeatPct }}%</span>
              </div>
              <div class="dash-prog-track">
                <div class="dash-prog-fill" style="width:{{ $repeatPct }}%;background:var(--p-accent)"></div>
              </div>
            </div>
            @if($deliveryTypeSplit->count())
            <div class="dash-tile-divider dash-tile-divider--10">
              <div class="dash-delivery-head">Yetkazish turi</div>
              @foreach($deliveryTypeSplit->take(3) as $dt)
              <div class="dash-delivery-line">
                <span class="dash-delivery-name">{{ $dt->deliveryType }}</span>
                <span class="dash-delivery-val">{{ number_format($dt->cnt) }} ta</span>
              </div>
              @endforeach
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ROW 4: User holat + Online + Top mahsulotlar --}}
<div class="grid grid-cols-1 xl:grid-cols-12 gap-3 mb-4">
  <div class="xl:col-span-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Foydalanuvchilar holati</div>
        <div class="dash-card-sub">{{ number_format($totalUsers) }} ta jami</div>
      </div>
      <div class="dash-card-body">
        @foreach([['Online (5 daqiqa)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'accent'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolat (30+ kun)',$isolatedUsers,'danger'],['Bu hafta yangi',$newUsersWeek,'muted']] as [$l,$v,$c])
        <div class="dash-prog">
          <div class="dash-prog-top">
            <span class="dash-prog-label">{{ $l }}</span>
            <span class="dash-prog-val">{{ number_format($v) }}</span>
          </div>
          <div class="dash-prog-track">
            <div class="dash-prog-fill" style="width:{{ $totalUsers>0?min(round($v/$totalUsers*100),100):0 }}%;background:var(--p-{{ $c }})"></div>
          </div>
        </div>
        @endforeach
        <div class="stat-grid stat-grid--dash">
          @foreach([['Jami',$totalUsers,'text'],['Premium',$premiumUsers,'warning'],['+Bugun',$newUsersToday,'success']] as [$l,$v,$c])
          <div class="stat-cell stat-cell--dash">
            <div class="stat-cell-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="stat-cell-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
        <div class="user-sparkline-block">
          <div class="sparkline-cap">7 kunlik yangi userlar</div>
          <div id="chartUserSparkline" class="dash-chart-host dash-chart-host--60"></div>
        </div>
        @if($isolatedUsers>0)
        <div class="alert-item danger alert-item--mt">
          <i class="bi bi-person-x alert-item__i--shrink"></i>
          <span>{{ number_format($isolatedUsers) }} ta user 30+ kun yo'q</span>
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="xl:col-span-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Hozir online</div>
        <div class="dash-card-sub dash-card-sub--row">
          <span class="live-dot"></span> {{ $onlineUsers }} nafar
        </div>
      </div>
      <div class="dash-card-body">
        @forelse($onlineUsersList as $u)
        <a href="{{ route('panel.users.show',$u->id) }}" class="dash-row-link">
          <div class="d-av d-av--accent">
            @if($u->avatar)<img src="{{ $u->avatar }}">@else{{ strtoupper(substr($u->name??'U',0,1)) }}@endif
          </div>
          <div class="dash-row-main">
            <div class="dash-row-title">{{ $u->name }} {{ $u->lastname }}</div>
            <div class="dash-row-meta">{{ $u->last_seen_at ? \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() : '—' }}</div>
          </div>
          <span class="live-dot"></span>
        </a>
        @empty
        <div class="dash-empty">
          <i class="bi bi-wifi-off dash-empty__ico"></i>Hozir hech kim online emas
        </div>
        @endforelse
        <a href="{{ route('panel.users.index') }}" class="btn-p ghost btn-p-block-dash">
          Barcha foydalanuvchilar <i class="bi bi-arrow-right ml-1"></i>
        </a>
      </div>
    </div>
  </div>

  <div class="xl:col-span-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mahsulotlar</div>
        <div class="dash-card-sub">Eng ko'p sotilganlar</div>
      </div>
      <div class="dash-card-body">
        @forelse($topMixedProducts as $i => $product)
        @php $imgs=is_array($product->images)?$product->images:json_decode($product->images??'[]',true);$img=$imgs[0]??null; @endphp
        <div class="top-row">
          <span class="rank-num {{ $i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n')) }}">{{ $i+1 }}</span>
          <div class="book-thumb">
            @if($img)
              <img src="{{ $img }}">
            @else
              <i class="bi bi-{{ $product->_type==='stationery'?'box':'book' }}"></i>
            @endif
          </div>
          <div class="top-row-body">
            <div class="dash-row-title--md">{{ $product->name }}</div>
            <div class="top-row-meta-row">
              <span class="s-pill {{ $product->_type==='stationery'?'warning':'info' }} s-pill--dash-xs">{{ $product->_type==='stationery'?'Kanstovar':'Kitob' }}</span>
              <span class="top-row-rev-hint">{{ number_format($product->total_revenue/1000) }}K UZS</span>
            </div>
          </div>
          <div class="top-row-count">
            <div class="top-row-count-val">{{ number_format($product->sold_count) }}</div>
            <div class="top-row-count-hint">ta</div>
          </div>
        </div>
        @empty
        <div class="dash-empty"><i class="bi bi-box dash-empty__ico"></i>Ma'lumot yo'q</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- ROW 5: Mystery navbat + Top buyers + So'nggi buyurtmalar --}}
<div class="grid grid-cols-1 xl:grid-cols-12 gap-3 mb-4">

  @if($mysteryDueToday->count()||$mysteryDueSoon->count())
  <div class="xl:col-span-4 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">
            <i class="bi bi-box-seam mr-1 {{ $mysteryDueCount>0?'dash-title-ico--danger':'dash-title-ico--accent' }}"></i>Mystery Box navbati
          </div>
          <div class="dash-card-sub">
            @if($mysteryDueCount>0)
              <span class="dash-sub-danger">{{ $mysteryDueCount }} ta kechikdi!</span>
            @else
              7 kun ichida {{ $mysteryDueSoon->count() }} ta
            @endif
          </div>
        </div>
        <a href="{{ route('panel.mystery-box.subscriptions',['tab'=>'active']) }}" class="btn-p ghost sm">Barchasi</a>
      </div>
      <div class="dash-card-body">
        @if($mysteryDueToday->count())
        <div class="dash-myst-head"><i class="bi bi-exclamation-triangle mr-1"></i>Bugun / Kechikkan</div>
        @foreach($mysteryDueToday as $sub)
        <a href="{{ route('panel.mystery-box.subscription',$sub) }}" class="dash-row-link">
          <div class="d-av d-av--teal">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div class="dash-row-main">
            <div class="dash-row-title--md">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div class="dash-row-meta--plain">{{ $sub->plan?->name_uz }} · {{ $sub->next_delivery_at?->diffForHumans() }}</div>
          </div>
          <span class="s-pill danger s-pill--dash-tight">Navbatda</span>
        </a>
        @endforeach
        @endif
        @if($mysteryDueSoon->count())
        <div class="dash-myst-subhead @if($mysteryDueToday->count()) dash-myst-subhead--spaced @endif">Yaqin 7 kun</div>
        @foreach($mysteryDueSoon as $sub)
        <a href="{{ route('panel.mystery-box.subscription',$sub) }}" class="dash-row-link dash-row-link--compact">
          <div class="d-av d-av--teal">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div class="dash-row-main">
            <div class="dash-row-title--sm">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div class="dash-row-meta--2xs">{{ $sub->next_delivery_at?->format('d.m.Y') }}</div>
          </div>
        </a>
        @endforeach
        @endif
      </div>
    </div>
  </div>
  @endif

  <div class="{{ ($mysteryDueToday->count()||$mysteryDueSoon->count())?'xl:col-span-4':'xl:col-span-5' }} fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mijozlar</div>
        <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
      </div>
      <div class="dash-card-body">
        <table class="p-table">
          <thead><tr><th>#</th><th>Mijoz</th><th>Buyurtma</th><th class="p-th-end">Xarid</th></tr></thead>
          <tbody>
            @forelse($topBuyers as $i => $buyer)
            <tr>
              <td><span class="rank-num {{ $i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n')) }}">{{ $i+1 }}</span></td>
              <td>
                <a href="{{ route('panel.users.show',$buyer->user_id) }}" class="dash-row-link--inline">
                  <div class="d-av d-av--accent">
                    @if($buyer->user?->avatar)<img src="{{ $buyer->user->avatar }}">@else{{ strtoupper(substr($buyer->user?->name??'U',0,1)) }}@endif
                  </div>
                  <span class="p-name-125">{{ $buyer->user?$buyer->user->name.' '.$buyer->user->lastname:'ID:'.$buyer->user_id }}</span>
                </a>
              </td>
              <td class="p-mono-12-muted">{{ $buyer->order_count }} ta</td>
              <td class="p-th-end p-mono-13-strong p-tone-success">{{ number_format($buyer->total_spent/1000) }}K</td>
            </tr>
            @empty
            <tr><td colspan="4" class="p-table-cell-empty--sm">Ma'lumot yo'q</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="{{ ($mysteryDueToday->count()||$mysteryDueSoon->count())?'xl:col-span-4':'xl:col-span-7' }} fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div><div class="dash-card-title">So'nggi buyurtmalar</div><div class="dash-card-sub">Oxirgi 10 ta</div></div>
        <a href="{{ route('panel.orders.index') }}" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table p-table--dash-recent">
            <thead><tr><th>#ID</th><th>Mijoz</th><th>Summa</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
            <tbody>
              @forelse($recentOrders as $order)
              @php $bc=match($order['status']){'Yetkazildi'=>'ob-c',"Yo'lda"=>'ob-b','Kutilmoqda'=>'ob-a','Bekor qilindi'=>'ob-f',default=>'ob-p'}; @endphp
              <tr>
                <td>
                  <span class="p-mono-id">#{{ $order['id'] }}</span>
                  @if($order['gift'])<span>🎁</span>@endif
                </td>
                <td>
                  <div class="flex items-center gap-2">
                    <div class="d-av d-av--accent d-av--sm-text">
                      @if($order['avatar'])<img src="{{ $order['avatar'] }}">@else{{ strtoupper(substr($order['customer'],0,1)) }}@endif
                    </div>
                    <span class="p-name-125">{{ $order['customer'] }}</span>
                  </div>
                </td>
                <td class="p-mono-13-strong">{{ $order['amount'] }} <span class="p-currency-suffix">UZS</span></td>
                <td><span class="o-badge {{ $bc }}">{{ $order['status'] }}</span></td>
                <td class="p-mono-date-hint">{{ $order['date'] }}</td>
                <td><a href="{{ route('panel.orders.show',$order['id']) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a></td>
              </tr>
              @empty
              <tr><td colspan="6" class="p-table-cell-empty">Buyurtmalar yo'q</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ROW 6: Biznes holat --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
  @foreach([['Sotuvchilar',$approvedSellers,$totalSellers,'sellers.index','success','bi-shop-window',$pendingSellers,'Yangi ariza'],['Kuryerlar',$activeCouriers,$totalCouriers,'couriers.index','info','bi-bicycle',0,'']] as [$title,$active,$total,$route,$color,$icon,$pending,$pendingLbl])
  <div class="fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi {{ $icon }} mr-1 dash-title-ico--{{ $color }}"></i>{{ $title }}</div>
        <a href="{{ route('panel.'.$route) }}" class="btn-p ghost sm">Ko'rish</a>
      </div>
      <div class="dash-card-body">
        <div class="dash-biz-row">
          <div class="dash-biz-stat">
            <div class="dash-biz-num dash-biz-num--{{ $color }}">{{ number_format($active) }}</div>
            <div class="dash-biz-lbl">Faol</div>
          </div>
          <div class="dash-biz-grow">
            <div class="dash-prog-top">
              <span class="dash-prog-label">Faollik</span>
              <span class="dash-prog-val">{{ $total>0?round($active/$total*100):0 }}%</span>
            </div>
            <div class="dash-prog-track">
              <div class="dash-prog-fill" style="width:{{ $total>0?round($active/$total*100):0 }}%;background:var(--p-{{ $color }})"></div>
            </div>
            <div class="dash-biz-foot">Jami: {{ number_format($total) }} ta</div>
            @if($pending>0)
            <div class="alert-item warning alert-item--compact">
              <i class="bi bi-clock"></i><span>{{ $pending }} ta {{ $pendingLbl }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@push('scripts')
<script>
const isDark = document.documentElement.getAttribute('data-bs-theme') !== 'light';
const C = {
  text:isDark?'#eef0f7':'#1a1d2e', muted:isDark?'#555c75':'#9ca3af',
  grid:isDark?'rgba(255,255,255,0.05)':'rgba(0,0,0,0.06)',
  surface:isDark?'#181c27':'#ffffff',
  accent:'#4f7cff', success:isDark?'#22c98e':'#16a34a',
  warning:isDark?'#f5a623':'#d97706', danger:isDark?'#ff5c6a':'#dc2626',
  info:isDark?'#38bdf8':'#0284c7', teal:'#14b8a6', pink:'#ec4899', purple:'#7c5cfc',
};

@php
  $revLabels  = collect($monthlyRevenue)->pluck('month')->toJson();
  $revAmounts = collect($monthlyRevenue)->map(fn($m)=>round($m['total']/1_000_000,1))->toJson();

  $weekLabels  = collect($dailyRevenue)->pluck('day')->toJson();
  $weekAmounts = collect($dailyRevenue)->map(fn($d) => round($d['total'] / 1_000_000, 2))->toJson();
  $ordWeekLabels = collect($dailyOrders)->pluck('day')->toJson();
  $ordWeekCounts = collect($dailyOrders)->pluck('count')->toJson();
@endphp

const revData = {
  month: { labels: {!! $revLabels !!}, data: {!! $revAmounts !!}, unit:'M', formatter:v=>v+'M' },
  week:  { labels: {!! $weekLabels !!},  data: {!! $weekAmounts !!}, unit:'M', formatter:v=>v+'M' },
  today: { labels: ['Bugun'], data: [{{ round($todayRevenue/1_000_000,2) }}], unit:'M', formatter:v=>v+'M' },
};

new ApexCharts(document.getElementById('chartOrdersWeek'),{
  series:[{name:'Buyurtmalar',data:{!! $ordWeekCounts !!}}],
  chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif',animations:{enabled:true,speed:450}},
  colors:[C.accent],
  stroke:{curve:'smooth',width:2.5},
  fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.35,opacityTo:0.02,stops:[0,90]}},
  dataLabels:{enabled:false},
  xaxis:{categories:{!! $ordWeekLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>Math.round(v)}},
  grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
  markers:{size:0,hover:{size:5}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();

new ApexCharts(document.getElementById('chartRevenueWeek'),{
  series:[{name:'Daromad',data:{!! $weekAmounts !!}}],
  chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif',animations:{enabled:true,speed:450}},
  colors:[C.success],
  stroke:{curve:'smooth',width:2.5},
  fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.32,opacityTo:0.02,stops:[0,92]}},
  dataLabels:{enabled:false},
  xaxis:{categories:{!! $weekLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
  grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
  markers:{size:0,hover:{size:5}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
}).render();

const revChart = new ApexCharts(document.getElementById('chartRevenue'),{
  series:[{name:'Daromad',data: revData.month.data}],
  chart:{type:'bar',height:288,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif',animations:{enabled:true,speed:500}},
  colors:[C.accent],
  plotOptions:{bar:{borderRadius:8,columnWidth:'46%',dataLabels:{position:'top'}}},
  dataLabels:{enabled:true,formatter:v=>v+'M',offsetY:-22,style:{fontSize:'11px',colors:[C.muted],fontFamily:'JetBrains Mono, monospace'}},
  xaxis:{categories: revData.month.labels,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'12px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
  grid:{borderColor:C.grid,strokeDashArray:5,xaxis:{lines:{show:false}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
  fill:{type:'gradient',gradient:{shade:'dark',type:'vertical',gradientToColors:['#2650cc'],stops:[0,100]}},
});
revChart.render();

function switchRevPeriod(btn, period) {
  document.querySelectorAll('#revPeriodToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const d = revData[period];
  revChart.updateOptions({
    series:[{name:'Daromad',data:d.data}],
    xaxis:{categories:d.labels},
    dataLabels:{formatter:d.formatter},
    yaxis:{labels:{formatter:d.formatter}},
  });
}

new ApexCharts(document.getElementById('chartDonut'),{
  series:[{{ $completedOrders }},{{ $onwayOrders }},{{ $pendingOrders }},{{ $cancelledOrders }}],
  labels:['Yetkazildi',"Yo'lda",'Kutilmoqda','Bekor'],colors:[C.success,C.info,C.warning,C.danger],
  chart:{type:'donut',height:212,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif'},
  legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
  dataLabels:{enabled:false},
  plotOptions:{pie:{donut:{size:'74%',labels:{show:true,total:{show:true,label:'Jami',fontSize:'12px',color:C.muted,formatter:()=>'{{ number_format($totalOrders) }}'},value:{fontSize:'20px',fontWeight:700,color:C.text,fontFamily:'JetBrains Mono, monospace'}}}}},
  stroke:{width:2,colors:[C.surface]},tooltip:{theme:isDark?'dark':'light'},
}).render();

@php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); @endphp
new ApexCharts(document.getElementById('chartUserSparkline'),{
  series:[{name:'Yangi user',data:{!! $sparkCounts !!}}],
  chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
  colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
  xaxis:{categories:{!! $sparkDays !!}},tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();

@if($isSuperAdmin)
@php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); @endphp
new ApexCharts(document.getElementById('chartAov'),{
  series:[{name:'AOV',data:{!! $aovVals !!}}],
  chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif'},
  colors:[C.teal],stroke:{curve:'smooth',width:2.5},
  markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
  xaxis:{categories:{!! $aovLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
  grid:{borderColor:C.grid,strokeDashArray:4},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
}).render();

new ApexCharts(document.getElementById('chartTypePie'),{
  series:[{{ $revenueByType['book'] }},{{ $revenueByType['stationery'] }}],
  labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
}).render();

new ApexCharts(document.getElementById('chartBuyers'),{
  series:[{{ $newBuyersMonth }},{{ $repeatBuyersMonth }}],
  labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();
@endif
</script>
@endpush