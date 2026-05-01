@extends('a122.layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div x-data="{
  tab: localStorage.getItem('a122-dash-tab') || 'main',
  init() {
    this.$nextTick(() => {
      if (this.tab !== 'main' && !_tabInitialized[this.tab]) {
        _tabInitialized[this.tab] = true;
        setTimeout(() => _initTab(this.tab), 80);
      }
    });
  },
  switchTab(tab) {
    this.tab = tab;
    localStorage.setItem('a122-dash-tab', tab);
    this.$nextTick(() => {
      if (!_tabInitialized[tab]) {
        _tabInitialized[tab] = true;
        setTimeout(() => _initTab(tab), 80);
      } else {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 30);
      }
    });
  }
}" x-init="init()">
<x-a122.page-header class="fade-up">
  <x-slot name="heading">Dashboard</x-slot>
  <x-slot name="meta">Asosiy metrikalar, buyurtmalar oqimi va operatsion holat bir joyda.</x-slot>
  <x-slot name="actions">
    <a href="{{ route('admin.dashboard.live') }}" class="btn-p ghost" target="_blank">
      <i class="bi bi-broadcast-pin"></i> Live monitor
    </a>
    <a href="{{ route('admin.dashboard',['clear_cache'=>1]) }}" class="btn-p ghost">
      <i class="bi bi-arrow-clockwise"></i> Yangilash
    </a>
  </x-slot>
</x-a122.page-header>

<div class="dash-seg-bar mb-4 fade-up" id="dashSegBar">
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'main' }" @click="switchTab('main')"><i class="bi bi-grid-1x2"></i><span>Asosiy</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'orders' }" @click="switchTab('orders')"><i class="bi bi-bag-check"></i><span>Buyurtmalar</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'finance' }" @click="switchTab('finance')"><i class="bi bi-bar-chart-line"></i><span>Moliya</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'users' }" @click="switchTab('users')"><i class="bi bi-people"></i><span>Foydalanuvchilar</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'catalog' }" @click="switchTab('catalog')"><i class="bi bi-building"></i><span>Biznes</span></button>
</div>

@php
  $dashAdmin = $admin ?? auth('panel')->user();
  $dashQuick = [];
  if ($dashAdmin?->hasPermission('orders'))    { $dashQuick[] = ['Buyurtmalar','bi-bag-check-fill',route('admin.orders.index'),$pendingOrders>0?$pendingOrders.' ta kutilmoqda':'Barcha statuslar','rgba(70,95,255,.12)','var(--p-accent)']; }
  if ($dashAdmin?->hasPermission('users'))     { $dashQuick[] = ['Foydalanuvchilar','bi-people-fill',route('admin.users.index'),number_format($totalUsers).' ro\'yxatda','rgba(18,183,106,.12)','var(--p-success)']; }
  if ($dashAdmin?->hasPermission('books'))     { $kb=(int)($kangarooHumanReviewBooks??0); $dashQuick[] = ['Kitoblar','bi-book-fill',route('admin.books.index'),$kb>0?$kb.' ta Kangaroo navbati':'Katalog','rgba(11,111,168,.12)','var(--p-info)']; }
  if ($dashAdmin?->hasPermission('stationery')){ $ks=(int)($kangarooHumanReviewStationery??0); $dashQuick[] = ['Kanstovar','bi-pencil-square',route('admin.stationery.index'),$ks>0?$ks.' ta Kangaroo navbati':'Mahsulotlar','rgba(247,144,9,.12)','var(--p-warning)']; }
  if ($dashAdmin?->hasPermission('sellers'))   { $dashQuick[] = ['Sotuvchilar','bi-shop-window',route('admin.sellers.index'),$pendingSellers>0?$pendingSellers.' ariza':'Do\'konlar','rgba(124,92,252,.12)','#7c5cfc']; }
  if ($dashAdmin?->hasPermission('settings')) {
    $kUgc=(int)($kangarooUgcAdminQueue??0);
    if ($kUgc>0) { $dashQuick[] = ['UGC (Kangaroo)','bi-stars',route('admin.book-club.moderation-queue'),$kUgc.' ta admin navbati','rgba(247,144,9,.18)','var(--p-warning)']; }
    $pendingPay=($pendingSellerTxCount??0)+($pendingCourierTxCount??0);
    $dashQuick[] = ['Tranzaksiyalar','bi-arrow-left-right',route('admin.transactions.index'),$pendingPay>0?$pendingPay.' kutilayotgan':'Hisob-kitoblar','rgba(70,95,255,.1)','var(--p-accent)'];
    $dashQuick[] = ['Shikoyatlar','bi-flag-fill',route('admin.complaints.index'),'Moderatsiya','rgba(240,68,56,.1)','var(--p-danger)'];
    $dashQuick[] = ['Support','bi-headset',route('admin.support.index'),'Murojaatlar','rgba(100,116,139,.15)','var(--p-muted)'];
    $dashQuick[] = ['Sozlamalar','bi-gear-fill',route('admin.settings.index'),'Tizim','rgba(100,116,139,.12)','var(--p-hint)'];
  }
@endphp

{{-- ── HERO STRIP ──────────────────────────────────────────────────────────── --}}
<div class="a122-section fade-up mb-4">
<div class="a122-section-body p-0">
<div class="dash-hero-strip">
  <div class="dash-hero-metric">
    <div class="dash-hero-label">GMV (brutto)</div>
    <div class="dash-hero-val">{{ number_format($gmvTotal/1_000_000,1) }}<span class="dash-hero-unit">M</span></div>
    <div class="dash-hero-sub">Bu oy {{ number_format($gmvMonth/1_000_000,1) }}M UZS</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">To'langan daromad</div>
    <div class="dash-hero-val">{{ number_format($totalRevenue/1_000_000,1) }}<span class="dash-hero-unit">M</span></div>
    <div class="dash-hero-sub">Bugun +{{ number_format($todayRevenue/1000) }}K UZS</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">Aktiv buyurtmalar</div>
    <div class="dash-hero-val">{{ number_format($pendingOrders+$packingOrders+$onwayOrders) }}</div>
    <div class="dash-hero-sub"><span class="live-dot live-dot--xs"></span>&ensp;{{ number_format($onlineUsers) }} online</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">Kutilayotgan to'lovlar</div>
    <div class="dash-hero-val {{ ($pendingSellerTxCount+$pendingCourierTxCount)>0?'dash-hero-val--warn':'' }}">{{ number_format($pendingSellerTxCount+$pendingCourierTxCount) }}</div>
    <div class="dash-hero-sub">Seller + Kuryer arizalar</div>
  </div>
</div>
</div>
</div>

{{-- ── ALERTS ───────────────────────────────────────────────────────────────── --}}
@if(!empty($alerts))
<div class="a122-section fade-up mb-4">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Diqqat talab qiladigan holatlar</div>
      <div class="a122-section-head__meta">Moderatsiya, to‘lov va navbatlar bo‘yicha tezkor signal bloklari.</div>
    </div>
  </div>
  <div class="a122-section-body">
  @foreach($alerts as [$color,$icon,$title,$desc,$url])
  <div class="alert-item {{ $color }}">
    <i class="bi {{ $icon }} alert-item__icon"></i>
    <div class="alert-item__body"><span class="alert-item__title">{{ $title }}:</span> {{ $desc }}</div>
    <a href="{{ $url }}" class="alert-item__link">Ko'rish →</a>
  </div>
  @endforeach
</div>
</div>
@endif

{{-- ── QUICK LINKS ──────────────────────────────────────────────────────────── --}}
@if(count($dashQuick))
<div class="a122-section mb-5 fade-up">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Tezkor bo‘limlar</div>
      <div class="a122-section-head__meta">Eng ko‘p ishlatiladigan boshqaruv sahifalariga bir bosishda o‘tish.</div>
    </div>
  </div>
  <div class="a122-section-body">
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
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 1: ASOSIY — KPI + Insight pills                                      --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="dash-tab-panel" id="dash-panel-main" x-show="tab === 'main'" x-cloak>

  <div class="dash-insight-grid mb-4 fade-up">
    <div class="dash-insight-pill dip-success">
      <div class="dip-lbl">Bugungi daromad</div>
      <div class="dip-val">{{ number_format($todayRevenue/1_000_000,2) }}<span class="dip-val-unit"> M</span></div>
    </div>
    <div class="dash-insight-pill">
      <div class="dip-lbl">Bugun buyurtma</div>
      <div class="dip-val">{{ number_format($todayOrders) }}<span class="dip-val-unit"> ta</span></div>
    </div>
    <div class="dash-insight-pill dip-accent">
      <div class="dip-lbl">Hafta daromad</div>
      <div class="dip-val">{{ number_format($weekRevenue/1_000_000,2) }}<span class="dip-val-unit"> M</span></div>
    </div>
    <div class="dash-insight-pill dip-info">
      <div class="dip-lbl">Hafta buyurtma</div>
      <div class="dip-val">{{ number_format($weekOrders) }}<span class="dip-val-unit"> ta</span></div>
    </div>
    <div class="dash-insight-pill dip-warning">
      <div class="dip-lbl">Kutilmoqda</div>
      <div class="dip-val">{{ number_format($pendingOrders) }}<span class="dip-val-unit"> ta</span></div>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6 xl:gap-3 fade-up">

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
        <span class="kpi-foot-danger"><i class="bi bi-x-circle kpi-footer-ico"></i> {{ $cancelledOrders }}</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--info"><i class="bi bi-patch-check"></i></div>
        <span class="kpi-change {{ $completionRate>=70?'up':'neutral' }}">{{ $completionRate }}%</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Yakunlanish</div>
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

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--gift"><i class="bi bi-gift"></i></div>
        <span class="kpi-change {{ $giftUsed>0?'up':'neutral' }}"><i class="bi bi-check-circle kpi-change-ico-sm"></i> {{ $giftUsed }} ishlatildi</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Gift Sertifikat</div>
        <div class="kpi-value">{{ number_format($giftTotal) }}</div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Faol</span>
        @if($giftPending>0)
          <span class="kpi-foot-warn-strong">{{ $giftPending }} kutmoqda</span>
        @else
          <span class="kpi-foot-mono">{{ $giftSent }}</span>
        @endif
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--teal"><i class="bi bi-box-seam"></i></div>
        @if($mysteryDueCount>0)
          <span class="kpi-change down"><i class="bi bi-exclamation-triangle kpi-change-ico-sm"></i> {{ $mysteryDueCount }} navbat</span>
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

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 2: BUYURTMALAR — Charts + Donut + So'nggi buyurtmalar                --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="dash-tab-panel" id="dash-panel-orders" x-show="tab === 'orders'" x-cloak>

  <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4 mb-4 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Buyurtmalar — 7 kun</div>
          <div class="dash-card-sub">Soni bo'yicha</div>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn-p ghost sm">Ro'yxat <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartOrdersWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Daromad — 7 kun</div>
          <div class="dash-card-sub">Mln UZS (to'langan)</div>
        </div>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartRevenueWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4">
    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div class="dash-card-title">Holat bo'yicha</div>
          <div class="dash-card-sub">Jami {{ number_format($totalOrders) }} ta</div>
        </div>
        <div class="dash-card-body pt-0">
          <div class="dash-chart-surface"><div id="chartDonut" class="dash-chart-host dash-chart-host--210"></div></div>
          <div class="donut-stat-row">
            @foreach([['Yetkazildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Qadoqda',$packingOrders,'accent'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']] as $idx => [$l,$v,$c])
            @if($idx>0)<div class="stat-divider"></div>@endif
            <div class="stat-cell donut-stat-cell">
              <div class="stat-cell-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
              <div class="stat-cell-lbl">{{ $l }}</div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <div class="xl:col-span-8 fade-up">
      <div class="dash-card">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">So'nggi buyurtmalar</div>
            <div class="dash-card-sub">Oxirgi 10 ta</div>
          </div>
          <a href="{{ route('admin.orders.index') }}" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="dash-card-body p-0">
          <div class="recent-orders-wrap">
            @forelse($recentOrders as $order)
            @php $bc=match($order['status']){'Yetkazildi'=>'ob-c',"Yo'lda"=>'ob-b','Qadoqlanmoqda'=>'ob-pk','Kutilmoqda'=>'ob-a','Bekor qilindi'=>'ob-f',default=>'ob-p'}; @endphp
            <div class="ro-row">
              <div class="ro-id"><span class="p-mono-id">#{{ $order['id'] }}</span>@if($order['gift'])<span class="ro-gift">🎁</span>@endif</div>
              <div class="ro-customer">
                <div class="d-av d-av--accent d-av--sm-text">@if($order['avatar'])<img src="{{ $order['avatar'] }}">@else{{ strtoupper(substr($order['customer'],0,1)) }}@endif</div>
                <span class="ro-name">{{ $order['customer'] }}</span>
              </div>
              <div class="ro-amount">{{ $order['amount'] }} <span class="p-currency-suffix">UZS</span></div>
              <div class="ro-status"><span class="o-badge {{ $bc }}">{{ $order['status'] }}</span></div>
              <div class="ro-date">{{ $order['date'] }}</div>
              <div class="ro-action"><a href="{{ route('admin.orders.show',$order['id']) }}" class="btn-p ghost sm"><i class="bi bi-arrow-right"></i></a></div>
            </div>
            @empty
            <div class="dash-empty"><i class="bi bi-bag-x dash-empty__ico"></i>Buyurtmalar yo'q</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

  @php $hasMysteryQueue = $mysteryDueToday->count() || $mysteryDueSoon->count(); @endphp
  @if($hasMysteryQueue)
  <div class="dash-card fade-up">
    <div class="dash-card-head">
      <div>
        <div class="dash-card-title"><i class="bi bi-box-seam mr-1 {{ $mysteryDueCount>0?'dash-title-ico--danger':'dash-title-ico--accent' }}"></i>Mystery Box navbati</div>
        <div class="dash-card-sub">
          @if($mysteryDueCount>0)
            <span class="dash-sub-danger">{{ $mysteryDueCount }} ta kechikdi</span>
          @else
            {{ $mysteryDueSoon->count() }} ta 7 kun ichida
          @endif
        </div>
      </div>
      <a href="{{ route('admin.mystery-box.subscriptions',['tab'=>'active']) }}" class="btn-p ghost sm">Barchasi</a>
    </div>
    <div class="dash-card-body">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
        @foreach($mysteryDueToday->take(4) as $sub)
        <a href="{{ route('admin.mystery-box.subscription',$sub) }}" class="dash-row-link">
          <div class="d-av d-av--teal">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div class="dash-row-main">
            <div class="dash-row-title--md">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div class="dash-row-meta--plain">{{ $sub->plan?->name_uz }} · {{ $sub->next_delivery_at?->diffForHumans() }}</div>
          </div>
          <span class="s-pill danger s-pill--dash-tight">Navbatda</span>
        </a>
        @endforeach
        @foreach($mysteryDueSoon->take(4) as $sub)
        <a href="{{ route('admin.mystery-box.subscription',$sub) }}" class="dash-row-link dash-row-link--compact">
          <div class="d-av d-av--teal">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div class="dash-row-main">
            <div class="dash-row-title--sm">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div class="dash-row-meta--2xs">{{ $sub->next_delivery_at?->format('d.m.Y') }}</div>
          </div>
        </a>
        @endforeach
      </div>
    </div>
  </div>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 3: MOLIYA — Revenue chart + Financial report (superadmin)            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="dash-tab-panel" id="dash-panel-finance" x-show="tab === 'finance'" x-cloak>

  <div class="dash-card mb-4 fade-up">
    <div class="dash-card-head">
      <div>
        <div class="dash-card-title">Daromad dinamikasi</div>
        <div class="dash-card-sub">Oy / Hafta / Bugun · mln UZS</div>
      </div>
      <div class="flex items-center gap-2">
        <div class="period-toggle" id="revPeriodToggle">
          <button class="period-btn active" data-period="month" onclick="switchRevPeriod(this,'month')">Oy</button>
          <button class="period-btn" data-period="week" onclick="switchRevPeriod(this,'week')">Hafta</button>
          <button class="period-btn" data-period="today" onclick="switchRevPeriod(this,'today')">Bugun</button>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn-p ghost sm">Buyurtmalar <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="dash-card-body pt-0">
      <div class="dash-chart-surface"><div id="chartRevenue" class="dash-chart-host dash-chart-host--288"></div></div>
    </div>
  </div>

  @if($isSuperAdmin)
  @php
    $finRows = [
      ['accent','bi-activity','GMV (brutto)','Barcha buyurtmalar',$gmvTotal,$gmvMonth],
      ['success','bi-check-circle',"To'langan daromad",'paymentStatus = 2',$totalRevenue,$monthRevenue],
      ['info','bi-truck','Yetkazish','Delivery fee',$totalDeliveryIncome,$monthDeliveryIncome],
      ['purple','bi-percent','Seller komissiya',"O'rtacha {$avgCommissionPct}%",$totalCommissionEarned,$monthCommissionEarned],
      ['teal','bi-box-seam','Mystery Box','Faol + yakunlangan',$mysteryRevTotal,$mysteryRevMonth],
      ['pink','bi-gift','Gift Sertifikat','Ishlatilgan: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0],
    ];
    $finCosts = [
      ['danger','bi-ticket-perforated','Promokod',"{$promoOrdersCount} ta buyurtmada",$totalPromoDiscount,$monthPromoDiscount],
      ['warning','bi-cash-stack','Cashback','Foydalanuvchilarga qaytarildi',$totalCashbackPaid,$monthCashbackPaid],
      ['muted','bi-shop-window','Seller payout','Kutilmoqda: '.number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout],
      ['muted','bi-bicycle','Kuryer payout','Kutilmoqda: '.number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout],
      ['danger','bi-x-circle','Bekor yo\'qotish','status=F',$cancelledRevLoss,$cancelledMonthLoss],
    ];
  @endphp

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-5 fade-up">
      <div class="fin-card">
        <div class="fin-section">
          <div class="fin-section-label fin-section-label--income"><i class="bi bi-arrow-up-circle-fill"></i> Daromadlar</div>
          @foreach($finRows as [$clr,$ico,$lbl,$sub,$total,$month])
          @php
            $bg=match($clr){'purple'=>'rgba(124,92,252,.13)','teal'=>'rgba(20,184,166,.11)','pink'=>'rgba(236,72,153,.1)',default=>"var(--p-{$clr}-d)"};
            $clrVal=match($clr){'purple'=>'#7c5cfc','teal'=>'#14b8a6','pink'=>'#ec4899',default=>"var(--p-{$clr})"};
          @endphp
          <div class="fin2-row">
            <div class="fin2-ico" style="background:{{ $bg }};color:{{ $clrVal }}"><i class="bi {{ $ico }}"></i></div>
            <div class="fin2-body">
              <div class="fin2-name">{{ $lbl }}</div>
              <div class="fin2-sub">{{ $sub }}</div>
            </div>
            <div class="fin2-nums">
              <div class="fin2-total" style="color:{{ $clrVal }}">{{ number_format($total/1_000_000,1) }}<span class="fin2-unit">M</span></div>
              @if($month>0)<div class="fin2-month">{{ number_format($month/1000) }}K / oy</div>@endif
            </div>
          </div>
          @endforeach
        </div>
        <div class="fin-section fin-section--cost">
          <div class="fin-section-label fin-section-label--cost"><i class="bi bi-arrow-down-circle-fill"></i> Chiqimlar</div>
          @foreach($finCosts as [$clr,$ico,$lbl,$sub,$total,$month])
          @php $costBg=$clr==='muted'?'var(--p-elevated)':"var(--p-{$clr}-d)"; @endphp
          <div class="fin2-row">
            <div class="fin2-ico" style="background:{{ $costBg }};color:var(--p-{{ $clr }})"><i class="bi {{ $ico }}"></i></div>
            <div class="fin2-body">
              <div class="fin2-name fin2-name--cost">{{ $lbl }}</div>
              <div class="fin2-sub">{{ $sub }}</div>
            </div>
            <div class="fin2-nums">
              <div class="fin2-total fin2-total--cost">−{{ number_format($total/1_000_000,1) }}<span class="fin2-unit">M</span></div>
              @if($month>0)<div class="fin2-month">{{ number_format($month/1000) }}K / oy</div>@endif
            </div>
          </div>
          @endforeach
        </div>
        <div class="fin-profit-row">
          <div class="fin-profit-ico"><i class="bi bi-stars"></i></div>
          <div class="fin-profit-body">
            <div class="fin-profit-label">Platform sof foyda</div>
            <div class="fin-profit-sub">Komissiya + Yetkazish − Chiqimlar</div>
          </div>
          <div class="fin-profit-val">
            <div class="fin-profit-num">{{ number_format($platformProfit/1_000_000,2) }}<span class="fin2-unit"> M</span></div>
            <div class="fin2-month">Bu oy: {{ number_format($platformProfitMonth/1000) }}K</div>
          </div>
        </div>
      </div>
    </div>

    <div class="xl:col-span-7 fade-up flex flex-col gap-4">
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">AOV dinamikasi</div>
          <div class="dash-card-sub">Joriy: {{ number_format($avgOrderValue) }} UZS · Komissiya: {{ $avgCommissionPct }}%</div>
        </div>
        <div class="dash-card-body"><div id="chartAov" class="dash-chart-host dash-chart-host--130"></div></div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Mahsulot turi</div>
            <div class="dash-card-sub">Daromad ulushi</div>
          </div>
          <div class="dash-card-body">
            @php $typeTotal=max(1,$revenueByType['book']+$revenueByType['stationery']);$bookPct=round($revenueByType['book']/$typeTotal*100,1);$statPct=round($revenueByType['stationery']/$typeTotal*100,1); @endphp
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
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Xaridorlar (bu oy)</div>
            <div class="dash-card-sub">Yangi vs Takroriy</div>
          </div>
          <div class="dash-card-body">
            @php $totalB=max(1,$repeatBuyersMonth+$newBuyersMonth);$repeatPct=$totalB>1?round($repeatBuyersMonth/$totalB*100):0; @endphp
            <div id="chartBuyers" class="dash-chart-host dash-chart-host--120"></div>
            @foreach([['Yangi',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'accent','2+ marta']] as [$l,$v,$c,$s])
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
              <div class="dash-repeat-head"><span>Qayta qaytish</span><span class="dash-repeat-pct">{{ $repeatPct }}%</span></div>
              <div class="dash-prog-track"><div class="dash-prog-fill" style="width:{{ $repeatPct }}%;background:var(--p-accent)"></div></div>
            </div>
          </div>
        </div>
      </div>
      @if($deliveryTypeSplit->count())
      <div class="dash-card">
        <div class="dash-card-head"><div class="dash-card-title">Yetkazish turlari</div></div>
        <div class="dash-card-body">
          @foreach($deliveryTypeSplit->take(5) as $dt)
          <div class="dash-delivery-line">
            <span class="dash-delivery-name">{{ $dt->deliveryType }}</span>
            <span class="dash-delivery-val">{{ number_format($dt->cnt) }} ta</span>
          </div>
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
  @else
  <div class="dash-empty"><i class="bi bi-lock dash-empty__ico"></i>Moliyaviy hisobot faqat superadmin uchun</div>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 4: FOYDALANUVCHILAR — Holat + Online + Top mijozlar                  --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="dash-tab-panel" id="dash-panel-users" x-show="tab === 'users'" x-cloak>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Foydalanuvchilar holati</div>
            <div class="dash-card-sub">{{ number_format($totalUsers) }} ta jami ro'yxatda</div>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="dash-card-body">
          <div class="user-mini-strip">
            @foreach([[$totalUsers,'Jami','text'],[$onlineUsers,'Online','success'],[$premiumUsers,'Premium','warning'],[$newUsersToday,'+Bugun','accent']] as [$v,$l,$c])
            <div class="user-mini-cell">
              <div class="user-mini-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
              <div class="user-mini-lbl">{{ $l }}</div>
            </div>
            @endforeach
          </div>
          <div class="mt-3">
            @foreach([['Online (5 min)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'accent'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolyat (30+ kun)',$isolatedUsers,'danger']] as [$l,$v,$c])
            <div class="user-prog-row">
              <div class="user-prog-left">
                <span class="user-prog-dot" style="background:var(--p-{{ $c }})"></span>
                <span class="user-prog-lbl">{{ $l }}</span>
              </div>
              <div class="user-prog-mid">
                <div class="user-prog-bar">
                  <div class="user-prog-fill" style="width:{{ $totalUsers>0?min(round($v/$totalUsers*100),100):0 }}%;background:var(--p-{{ $c }})"></div>
                </div>
              </div>
              <span class="user-prog-val">{{ number_format($v) }}</span>
            </div>
            @endforeach
          </div>
          <div class="user-sparkline-block">
            <div class="sparkline-cap">Yangi userlar — 7 kun</div>
            <div id="chartUserSparkline" class="dash-chart-host dash-chart-host--60"></div>
          </div>
          @if($isolatedUsers>0)
          <div class="alert-item danger alert-item--mt alert-item--compact">
            <i class="bi bi-person-x alert-item__i--shrink"></i>
            <span>{{ number_format($isolatedUsers) }} ta user 30+ kun yo'q</span>
            <a href="{{ route('admin.users.index') }}" class="alert-item__link">Ko'rish →</a>
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Hozir online</div>
            <div class="dash-card-sub dash-card-sub--row"><span class="live-dot"></span>&ensp;{{ $onlineUsers }} nafar</div>
          </div>
        </div>
        <div class="dash-card-body">
          @forelse($onlineUsersList as $u)
          <a href="{{ route('admin.users.show',$u->id) }}" class="dash-row-link">
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
          <div class="dash-empty"><i class="bi bi-wifi-off dash-empty__ico"></i>Hozir hech kim online emas</div>
          @endforelse
          <a href="{{ route('admin.users.index') }}" class="btn-p ghost btn-p-block-dash mt-3">Barcha foydalanuvchilar <i class="bi bi-arrow-right ml-1"></i></a>
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Top mijozlar</div>
            <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn-p ghost sm">Barchasi</a>
        </div>
        <div class="dash-card-body">
          @forelse($topBuyers as $i => $buyer)
          <a href="{{ route('admin.users.show',$buyer->user_id) }}" class="top-buyer-row">
            <span class="rank-num {{ $i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n')) }}">{{ $i+1 }}</span>
            <div class="d-av d-av--accent">
              @if($buyer->user?->avatar)<img src="{{ $buyer->user->avatar }}">@else{{ strtoupper(substr($buyer->user?->name??'U',0,1)) }}@endif
            </div>
            <div class="top-buyer-body">
              <div class="dash-row-title--md">{{ $buyer->user?$buyer->user->name.' '.$buyer->user->lastname:'ID:'.$buyer->user_id }}</div>
              <div class="top-row-rev-hint">{{ $buyer->order_count }} ta buyurtma</div>
            </div>
            <div class="top-buyer-spend">
              <div class="top-buyer-amount">{{ number_format($buyer->total_spent/1000) }}K</div>
              <div class="top-row-count-hint">UZS</div>
            </div>
          </a>
          @empty
          <div class="dash-empty"><i class="bi bi-person-x dash-empty__ico"></i>Ma'lumot yo'q</div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 5: BIZNES — Top mahsulotlar + Sotuvchilar + Kuryerlar                --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="dash-tab-panel" id="dash-panel-catalog" x-show="tab === 'catalog'" x-cloak>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

    <div class="xl:col-span-7 fade-up">
      <div class="dash-card" style="height:100%">
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
              @if($img)<img src="{{ $img }}">@else<i class="bi bi-{{ $product->_type==='stationery'?'box':'book' }}"></i>@endif
            </div>
            <div class="top-row-body">
              <div class="dash-row-title--md">{{ $product->name }}</div>
              <div class="top-row-meta-row">
                <span class="s-pill {{ $product->_type==='stationery'?'warning':'info' }} s-pill--dash-xs">{{ $product->_type==='stationery'?'Kanstovar':'Kitob' }}</span>
                <span class="top-row-rev-hint">{{ number_format($product->total_revenue/1000) }}K rev.</span>
              </div>
            </div>
            <div class="top-row-count">
              <div class="top-row-count-val">{{ number_format($product->sold_count) }}</div>
              <div class="top-row-count-hint">dona</div>
            </div>
          </div>
          @empty
          <div class="dash-empty"><i class="bi bi-box dash-empty__ico"></i>Ma'lumot yo'q</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="xl:col-span-5 fade-up flex flex-col gap-4">
      <div class="biz-stat-card biz-stat-card--success">
        <div class="biz-stat-head">
          <div class="biz-stat-ico biz-stat-ico--success"><i class="bi bi-shop-window"></i></div>
          <div>
            <div class="biz-stat-title">Sotuvchilar</div>
            <div class="biz-stat-sub">Do'konlar platformada</div>
          </div>
          <a href="{{ route('admin.sellers.index') }}" class="btn-p ghost sm ml-auto">Ko'rish <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="biz-stat-nums">
          @foreach([[$approvedSellers,'Faol','success'],[$totalSellers,'Jami','text'],[$pendingSellers,'Ariza','warning']] as [$v,$l,$c])
          <div class="biz-num-cell">
            <div class="biz-num-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="biz-num-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
        <div class="biz-stat-bar-wrap">
          <div class="biz-stat-bar-label">
            <span>Faollik darajasi</span>
            <span>{{ $totalSellers>0?round($approvedSellers/$totalSellers*100):0 }}%</span>
          </div>
          <div class="biz-prog-track">
            <div class="biz-prog-fill biz-prog-fill--success" style="width:{{ $totalSellers>0?round($approvedSellers/$totalSellers*100):0 }}%"></div>
          </div>
        </div>
        @if($pendingSellers>0)
        <div class="alert-item warning alert-item--compact mt-2">
          <i class="bi bi-clock"></i><span>{{ $pendingSellers }} ta yangi ariza</span>
          <a href="{{ route('admin.sellers.index',['tab'=>'pending']) }}" class="alert-item__link">Ko'rish →</a>
        </div>
        @endif
      </div>

      <div class="biz-stat-card biz-stat-card--info">
        <div class="biz-stat-head">
          <div class="biz-stat-ico biz-stat-ico--info"><i class="bi bi-bicycle"></i></div>
          <div>
            <div class="biz-stat-title">Kuryerlar</div>
            <div class="biz-stat-sub">Faol yetkazuvchilar</div>
          </div>
          <a href="{{ route('admin.couriers.index') }}" class="btn-p ghost sm ml-auto">Ko'rish <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="biz-stat-nums">
          @foreach([[$activeCouriers,'Faol','info'],[$totalCouriers,'Jami','text'],[0,'Navbatda','muted']] as [$v,$l,$c])
          <div class="biz-num-cell">
            <div class="biz-num-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="biz-num-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
        <div class="biz-stat-bar-wrap">
          <div class="biz-stat-bar-label">
            <span>Faollik darajasi</span>
            <span>{{ $totalCouriers>0?round($activeCouriers/$totalCouriers*100):0 }}%</span>
          </div>
          <div class="biz-prog-track">
            <div class="biz-prog-fill biz-prog-fill--info" style="width:{{ $totalCouriers>0?round($activeCouriers/$totalCouriers*100):0 }}%"></div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

@endsection

@push('scripts')
<script>
const isDark = document.documentElement.classList.contains('dark');
const C = {
  text:isDark?'#eef0f7':'#1a1d2e', muted:isDark?'#555c75':'#9ca3af',
  grid:isDark?'rgba(255,255,255,0.05)':'rgba(0,0,0,0.06)',
  surface:isDark?'#181c27':'#ffffff',
  accent:'#4f7cff', success:isDark?'#22c98e':'#16a34a',
  warning:isDark?'#f5a623':'#d97706', danger:isDark?'#ff5c6a':'#dc2626',
  info:isDark?'#38bdf8':'#0284c7', teal:'#14b8a6', pink:'#ec4899', purple:'#7c5cfc',
};

@php
  $revLabels   = collect($monthlyRevenue)->pluck('month')->toJson();
  $revAmounts  = collect($monthlyRevenue)->map(fn($m)=>round($m['total']/1_000_000,1))->toJson();
  $weekLabels  = collect($dailyRevenue)->pluck('day')->toJson();
  $weekAmounts = collect($dailyRevenue)->map(fn($d)=>round($d['total']/1_000_000,2))->toJson();
  $ordWeekLabels = collect($dailyOrders)->pluck('day')->toJson();
  $ordWeekCounts = collect($dailyOrders)->pluck('count')->toJson();
@endphp

const revData = {
  month: { labels:{!! $revLabels !!},  data:{!! $revAmounts !!},  formatter:v=>v+'M' },
  week:  { labels:{!! $weekLabels !!}, data:{!! $weekAmounts !!}, formatter:v=>v+'M' },
  today: { labels:['Bugun'], data:[{{ round($todayRevenue/1_000_000,2) }}], formatter:v=>v+'M' },
};

// ── Tab system ───────────────────────────────────────────────────────────────
const _tabInitialized = { main: true };
function _initTab(tab) {
  if (tab === 'orders')  { try { _initOrderCharts();  } catch(e) { console.error('orders chart:', e); } }
  if (tab === 'finance') { try { _initFinanceCharts(); } catch(e) { console.error('finance chart:', e); } }
  if (tab === 'users')   { try { _initUserCharts();   } catch(e) { console.error('users chart:', e); } }
}

// ── Orders tab ───────────────────────────────────────────────────────────────
function _initOrderCharts() {
  new ApexCharts(document.getElementById('chartOrdersWeek'), {
    series:[{name:'Buyurtmalar',data:{!! $ordWeekCounts !!}}],
    chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:450}},
    colors:[C.accent],stroke:{curve:'smooth',width:2.5},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.35,opacityTo:0.02,stops:[0,90]}},
    dataLabels:{enabled:false},
    xaxis:{categories:{!! $ordWeekLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>Math.round(v)}},
    grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
    markers:{size:0,hover:{size:5}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();

  new ApexCharts(document.getElementById('chartRevenueWeek'), {
    series:[{name:'Daromad',data:{!! $weekAmounts !!}}],
    chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:450}},
    colors:[C.success],stroke:{curve:'smooth',width:2.5},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.32,opacityTo:0.02,stops:[0,92]}},
    dataLabels:{enabled:false},
    xaxis:{categories:{!! $weekLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
    grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
    markers:{size:0,hover:{size:5}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartDonut'), {
    series:[{{ $completedOrders }},{{ $onwayOrders }},{{ $packingOrders }},{{ $pendingOrders }},{{ $cancelledOrders }}],
    labels:['Yetkazildi',"Yo'lda",'Qadoqlanmoqda','Kutilmoqda','Bekor'],
    colors:[C.success,C.info,C.accent,C.warning,C.danger],
    chart:{type:'donut',height:212,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
    dataLabels:{enabled:false},
    plotOptions:{pie:{donut:{size:'74%',labels:{show:true,total:{show:true,label:'Jami',fontSize:'12px',color:C.muted,formatter:()=>'{{ number_format($totalOrders) }}'},value:{fontSize:'20px',fontWeight:700,color:C.text,fontFamily:'JetBrains Mono,monospace'}}}}}},
    stroke:{width:2,colors:[C.surface]},
    tooltip:{theme:isDark?'dark':'light'},
  }).render();
}

// ── Finance tab ──────────────────────────────────────────────────────────────
let _revChart = null;
function _initFinanceCharts() {
  _revChart = new ApexCharts(document.getElementById('chartRevenue'), {
    series:[{name:'Daromad',data:revData.month.data}],
    chart:{type:'bar',height:288,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:500}},
    colors:[C.accent],
    plotOptions:{bar:{borderRadius:8,columnWidth:'46%',dataLabels:{position:'top'}}},
    dataLabels:{enabled:true,formatter:v=>v+'M',offsetY:-22,style:{fontSize:'11px',colors:[C.muted],fontFamily:'JetBrains Mono,monospace'}},
    xaxis:{categories:revData.month.labels,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'12px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
    grid:{borderColor:C.grid,strokeDashArray:5,xaxis:{lines:{show:false}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
    fill:{type:'gradient',gradient:{shade:'dark',type:'vertical',gradientToColors:['#2650cc'],stops:[0,100]}},
  });
  _revChart.render();

  @if($isSuperAdmin)
  @php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); @endphp
  new ApexCharts(document.getElementById('chartAov'), {
    series:[{name:'AOV',data:{!! $aovVals !!}}],
    chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    colors:[C.teal],stroke:{curve:'smooth',width:2.5},
    markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
    xaxis:{categories:{!! $aovLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
    grid:{borderColor:C.grid,strokeDashArray:4},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartTypePie'), {
    series:[{{ $revenueByType['book'] }},{{ $revenueByType['stationery'] }}],
    labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartBuyers'), {
    series:[{{ $newBuyersMonth }},{{ $repeatBuyersMonth }}],
    labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();
  @endif
}

// ── Users tab ────────────────────────────────────────────────────────────────
function _initUserCharts() {
  @php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); @endphp
  new ApexCharts(document.getElementById('chartUserSparkline'), {
    series:[{name:'Yangi user',data:{!! $sparkCounts !!}}],
    chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
    colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
    xaxis:{categories:{!! $sparkDays !!}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();
}

// ── Period toggle ────────────────────────────────────────────────────────────
function switchRevPeriod(btn, period) {
  document.querySelectorAll('#revPeriodToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (!_revChart) return;
  const d = revData[period];
  _revChart.updateOptions({
    series:[{name:'Daromad',data:d.data}],
    xaxis:{categories:d.labels},
    dataLabels:{formatter:d.formatter},
    yaxis:{labels:{formatter:d.formatter}},
  });
}
</script>
@endpush
