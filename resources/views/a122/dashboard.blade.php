@extends('a122.layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
  [x-cloak] { display: none !important; }

  .dash-shell {
    --dash-ink: #0f172a;
    --dash-copy: #475569;
    --dash-muted: #94a3b8;
    --dash-line: rgba(15, 23, 42, 0.08);
    --dash-line-strong: rgba(15, 23, 42, 0.12);
    --dash-surface: rgba(255, 255, 255, 0.92);
    --dash-surface-soft: rgba(248, 250, 252, 0.86);
    --dash-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
    position: relative;
    padding: 4px 0 28px;
  }

  .dash-shell::before {
    content: "";
    position: absolute;
    inset: -24px -18px auto;
    height: 320px;
    border-radius: 36px;
    background:
      radial-gradient(circle at 0% 0%, rgba(37, 99, 235, 0.16), transparent 34%),
      radial-gradient(circle at 100% 0%, rgba(14, 165, 233, 0.14), transparent 28%),
      linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(244, 247, 251, 0));
    pointer-events: none;
    z-index: 0;
  }

  .dash-stage,
  .dash-shell > div[x-show] {
    position: relative;
    z-index: 1;
  }

  .dash-stage {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-bottom: 20px;
  }

  .dash-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.9fr);
    gap: 18px;
    padding: 24px;
    border-radius: 32px;
    border: 1px solid var(--dash-line);
    background:
      radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 28%),
      radial-gradient(circle at 85% 14%, rgba(16, 185, 129, 0.10), transparent 20%),
      linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,250,252,.94));
    box-shadow: var(--dash-shadow);
    overflow: hidden;
  }

  .dash-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 36px;
    padding: 0 14px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  .dash-hero__eyebrow::before {
    content: "";
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.12);
  }

  .dash-hero__title {
    margin-top: 18px;
    color: var(--dash-ink);
    font-size: clamp(2rem, 2.6vw, 3rem);
    font-weight: 900;
    line-height: .98;
    letter-spacing: -0.06em;
    max-width: 16ch;
  }

  .dash-hero__subtitle {
    margin-top: 12px;
    max-width: 62ch;
    color: var(--dash-copy);
    font-size: .98rem;
    line-height: 1.8;
  }

  .dash-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
  }

  .dash-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    background: rgba(255, 255, 255, 0.96);
    color: var(--dash-ink);
    font-size: .83rem;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
  }

  .dash-chip i { color: #2563eb; }

  .dash-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
  }

  .dash-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 999px;
    border: 1px solid var(--dash-line-strong);
    text-decoration: none;
    font-size: .85rem;
    font-weight: 800;
    transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease;
  }

  .dash-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(15, 23, 42, 0.10);
  }

  .dash-btn--dark {
    background: #0f172a;
    border-color: #0f172a;
    color: #fff;
  }

  .dash-btn--light {
    background: rgba(255,255,255,.88);
    color: var(--dash-ink);
  }

  .dash-hero-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .dash-status-box {
    padding: 20px;
    border-radius: 28px;
    background:
      radial-gradient(circle at top right, rgba(34, 197, 94, 0.16), transparent 28%),
      linear-gradient(180deg, #0f172a 0%, #16243b 100%);
    color: #fff;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.18);
  }

  .dash-status-box__label {
    color: rgba(255,255,255,.65);
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
  }

  .dash-status-box__value {
    margin-top: 10px;
    font-size: 2.1rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.07em;
  }

  .dash-status-box__meta {
    margin-top: 10px;
    font-size: .88rem;
    line-height: 1.65;
    color: rgba(255,255,255,.82);
  }

  .dash-side-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .dash-side-stat {
    padding: 16px;
    border-radius: 22px;
    border: 1px solid var(--dash-line);
    background: rgba(255,255,255,.9);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
  }

  .dash-side-stat__label {
    color: var(--dash-muted);
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
  }

  .dash-side-stat__value {
    margin-top: 8px;
    color: var(--dash-ink);
    font-size: 1.45rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.05em;
  }

  .dash-side-stat__meta {
    margin-top: 6px;
    color: var(--dash-copy);
    font-size: .78rem;
    line-height: 1.5;
  }

  .dash-metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .dash-metric-card {
    position: relative;
    overflow: hidden;
    padding: 18px;
    border-radius: 26px;
    border: 1px solid var(--dash-line);
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.92));
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
  }

  .dash-metric-card::after {
    content: "";
    position: absolute;
    inset: auto -16px -40px auto;
    width: 120px;
    height: 120px;
    border-radius: 999px;
    background: var(--metric-glow, rgba(37, 99, 235, 0.12));
    pointer-events: none;
  }

  .dash-metric-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }

  .dash-metric-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 18px;
    font-size: 1.15rem;
    color: var(--metric-color, #2563eb);
    background: var(--metric-bg, rgba(37, 99, 235, 0.10));
  }

  .dash-metric-card__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
  }

  .dash-metric-card__label {
    margin-top: 18px;
    color: #64748b;
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
  }

  .dash-metric-card__value {
    margin-top: 8px;
    color: var(--dash-ink);
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.06em;
  }

  .dash-metric-card__meta {
    margin-top: 8px;
    color: var(--dash-copy);
    font-size: .82rem;
    line-height: 1.6;
    min-height: 2.8em;
  }

  .dash-metric-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
    color: #475569;
    font-size: .78rem;
  }

  .dash-metric-card__footer strong {
    color: var(--dash-ink);
    font-weight: 800;
  }

  .dash-signal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .dash-shell .kc-alert-card,
  .dash-signal {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 18px;
    border-radius: 24px;
    border: 1px solid var(--dash-line);
    background: rgba(255,255,255,.92);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
    text-decoration: none;
  }

  .dash-signal__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 16px;
    flex-shrink: 0;
    font-size: 1.05rem;
  }

  .dash-signal__eyebrow {
    color: var(--dash-muted);
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
  }

  .dash-signal__title {
    margin-top: 4px;
    color: var(--dash-ink);
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.35;
  }

  .dash-signal__meta {
    margin-top: 6px;
    color: var(--dash-copy);
    font-size: .84rem;
    line-height: 1.6;
  }

  .dash-surface,
  .dash-shell .kc-tab-card,
  .dash-shell .a122-section,
  .dash-shell .card {
    border: 1px solid var(--dash-line);
    border-radius: 28px !important;
    background: var(--dash-surface);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
    backdrop-filter: blur(12px);
  }

  .dash-surface {
    padding: 18px;
  }

  .dash-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
  }

  .dash-panel-head__eyebrow {
    color: var(--dash-muted);
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
  }

  .dash-panel-head__title {
    margin-top: 2px;
    color: var(--dash-ink);
    font-size: 1.1rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .dash-panel-head__meta {
    margin-top: 5px;
    color: var(--dash-copy);
    font-size: .84rem;
    line-height: 1.6;
  }

  .dash-quick-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
  }

  .dash-shell .kc-quick-link {
    height: 100%;
    min-height: 152px;
    padding: 18px;
    border-radius: 24px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    background:
      linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.90));
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }

  .dash-shell .kc-quick-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 24px 42px rgba(15, 23, 42, 0.10);
    border-color: rgba(37, 99, 235, 0.14);
  }

  .dash-shell .kc-quick-link__icon {
    width: 48px;
    height: 48px;
    border-radius: 18px;
    font-size: 1.15rem;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.42);
  }

  .dash-shell .kc-quick-link__title {
    margin-top: 18px;
    color: var(--dash-ink);
    font-size: .98rem;
    font-weight: 800;
  }

  .dash-shell .kc-quick-link__meta {
    margin-top: 8px;
    color: var(--dash-copy);
    font-size: .82rem;
    line-height: 1.6;
  }

  .dash-shell .kc-tab-card {
    padding: 8px !important;
    background: rgba(255,255,255,.78);
  }

  #dashSegBar {
    gap: 8px;
  }

  #dashSegBar .nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 16px;
    border-radius: 16px;
    color: #64748b;
    font-size: .84rem;
    font-weight: 800;
    transition: all .16s ease;
  }

  #dashSegBar .nav-link.active {
    background: #0f172a;
    color: #fff;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.05), 0 10px 22px rgba(15, 23, 42, 0.16);
  }

  .dash-shell .card {
    overflow: hidden;
  }

  .dash-shell .card-header {
    background: transparent !important;
    border-bottom: 0 !important;
    padding: 1.35rem 1.35rem .45rem !important;
  }

  .dash-shell .card-body {
    padding: 0 1.35rem 1.35rem !important;
  }

  .dash-shell .card-header .btn,
  .dash-shell .card-body .btn,
  .dash-shell .a122-section .btn {
    border-radius: 999px !important;
  }

  .dash-shell .progress {
    background: rgba(148, 163, 184, 0.16);
    border-radius: 999px;
  }

  .dash-shell .table-wrap,
  .dash-shell .overflow-x-auto {
    border-radius: 22px;
  }

  .dash-shell .alert {
    border-radius: 22px;
  }

  .dash-shell .hover-bg-light:hover {
    background: rgba(248, 250, 252, 0.95);
  }

  .dash-shell .dash-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 160px;
    color: var(--dash-muted);
    font-size: .88rem;
    font-weight: 700;
    border: 1px dashed rgba(148, 163, 184, 0.35);
    border-radius: 22px;
    background: rgba(248,250,252,.7);
  }

  .dash-shell .dash-empty__ico {
    font-size: 1rem;
  }

  @media (max-width: 1399px) {
    .dash-metric-grid,
    .dash-quick-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 1199px) {
    .dash-hero {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767px) {
    .dash-shell::before {
      inset: -10px -10px auto;
      height: 240px;
      border-radius: 24px;
    }

    .dash-stage {
      gap: 14px;
    }

    .dash-hero {
      padding: 18px;
      border-radius: 24px;
    }

    .dash-hero__title {
      max-width: none;
      font-size: 1.85rem;
    }

    .dash-metric-grid,
    .dash-signal-grid,
    .dash-side-grid,
    .dash-quick-grid {
      grid-template-columns: 1fr;
    }

    .dash-shell .kc-tab-card {
      overflow-x: auto;
    }

    #dashSegBar {
      flex-wrap: nowrap;
      min-width: max-content;
    }
  }
</style>
@endpush

@section('content')

@php
  // Mirrors DashboardController::assetFromStorage so raw DB filenames render
  // as proper URLs in <img src> instead of 404-ing as bare paths.
  $resolveImg = function ($value) {
      $v = trim((string) $value);
      if ($v === '') return null;
      if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://') || str_starts_with($v, '/')) {
          return $v;
      }
      return asset('storage/' . ltrim($v, '/'));
  };
@endphp

<div class="dash-shell" x-data="{
  tab: localStorage.getItem('a122-dash-tab') || 'main',
  init() {
    this.$nextTick(() => {
      if (this.tab !== 'main' && !_tabInitialized[this.tab]) {
        _tabInitialized[this.tab] = true;
        setTimeout(() => _ensureTabInit(this.tab), 80);
      }
    });
  },
  switchTab(tab) {
    this.tab = tab;
    localStorage.setItem('a122-dash-tab', tab);
    this.$nextTick(() => {
      if (!_tabInitialized[tab]) {
        _tabInitialized[tab] = true;
        setTimeout(() => _ensureTabInit(tab), 80);
      } else {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 30);
      }
    });
  }
}" x-init="init()">
@php
  $dashAdmin = $admin ?? auth('panel')->user();
  $dashQuick = [];
  if ($dashAdmin?->hasPermission('orders'))    { $dashQuick[] = ['Buyurtmalar','bi-bag-check-fill',route('admin.orders.index'),$pendingOrders>0?$pendingOrders.' ta kutilmoqda':'Barcha statuslar','rgba(70,95,255,.12)','var(--p-accent)']; }
  if ($dashAdmin?->hasPermission('users'))     { $dashQuick[] = ['Foydalanuvchilar','bi-people-fill',route('admin.users.index'),number_format($totalUsers).' ro\'yxatda','rgba(18,183,106,.12)','var(--p-success)']; }
  if ($dashAdmin?->hasPermission('books'))     { $dashQuick[] = ['Kitoblar','bi-book-fill',route('admin.books.index'),'Katalog','rgba(11,111,168,.12)','var(--p-info)']; }
  if ($dashAdmin?->hasPermission('stationery')){ $dashQuick[] = ['Kanstovar','bi-pencil-square',route('admin.stationery.index'),'Mahsulotlar','rgba(247,144,9,.12)','var(--p-warning)']; }
  if ($dashAdmin?->hasPermission('sellers'))   { $dashQuick[] = ['Sotuvchilar','bi-shop-window',route('admin.sellers.index'),$pendingSellers>0?$pendingSellers.' ariza':'Do\'konlar','rgba(124,92,252,.12)','#7c5cfc']; }
  if ($dashAdmin?->hasPermission('settings')) {
    $pendingPay=($pendingSellerTxCount??0)+($pendingCourierTxCount??0);
    $dashQuick[] = ['Tranzaksiyalar','bi-arrow-left-right',route('admin.transactions.index'),$pendingPay>0?$pendingPay.' kutilayotgan':'Hisob-kitoblar','rgba(70,95,255,.1)','var(--p-accent)'];
    $dashQuick[] = ['Shikoyatlar','bi-flag-fill',route('admin.complaints.index'),'Moderatsiya','rgba(240,68,56,.1)','var(--p-danger)'];
    $dashQuick[] = ['Support','bi-headset',route('admin.support.index'),'Murojaatlar','rgba(100,116,139,.15)','var(--p-muted)'];
    $dashQuick[] = ['Sozlamalar','bi-gear-fill',route('admin.settings.index'),'Tizim','rgba(100,116,139,.12)','var(--p-hint)'];
  }
@endphp

@php
  $dashMoment = now()->hour < 12 ? 'Xayrli tong' : (now()->hour < 18 ? 'Xayrli kun' : 'Xayrli kech');
  $opsLoad = $pendingOrders + $packingOrders + $onwayOrders;
  $payoutQueue = $pendingSellerTxCount + $pendingCourierTxCount;
  $dashFocusTitle = $pendingOrders > 0
      ? "{$pendingOrders} ta buyurtma admin e'tiborini kutmoqda"
      : ($payoutQueue > 0
          ? "{$payoutQueue} ta payout navbati yig‘ilib turibdi"
          : "Tizim barqaror, jiddiy bottleneck ko‘rinmayapti");
  $dashFocusMeta = $pendingOrders > 0
      ? "Eng tez foyda beradigan action: buyurtma oqimini bo‘shatish, qadoq va yo‘ldagi navbatni pasaytirish."
      : ($payoutQueue > 0
          ? "Moliya oqimida navbat bor. Seller va kuryer payoutlarini bir marta ko‘zdan kechirish foydali bo‘ladi."
          : "Bugungi snapshot sog‘lom ko‘rinmoqda. Endi ko‘proq trend, foyda va foydalanuvchi xulqiga e’tibor qaratish mumkin.");
  $completionTone = $completionRate >= 75 ? 'success' : ($completionRate >= 55 ? 'warning' : 'danger');
  $heroChips = [
      ['bi-bag-check', number_format($todayOrders) . " ta bugungi buyurtma"],
      ['bi-people', number_format($newUsersToday) . " ta yangi user"],
      ['bi-patch-check', $completionRate . "% yakunlanish"],
      ['bi-shop-window', number_format($approvedSellers) . " ta faol do‘kon"],
  ];
  $topMetrics = [
      [
          'icon' => 'bi-bag-check',
          'color' => '#2563eb',
          'bg' => 'rgba(37, 99, 235, 0.10)',
          'glow' => 'rgba(37, 99, 235, 0.14)',
          'badge' => number_format($pendingOrders) . " kutilmoqda",
          'label' => 'Aktiv buyurtmalar',
          'value' => number_format($opsLoad),
          'meta' => "Qadoqlanayotgan va yo‘ldagi oqim ham shu yerda jamlangan.",
          'footer_left' => "Yo'lda: " . number_format($onwayOrders),
          'footer_right' => "Qadoq: " . number_format($packingOrders),
      ],
      [
          'icon' => 'bi-wifi',
          'color' => '#0284c7',
          'bg' => 'rgba(2, 132, 199, 0.10)',
          'glow' => 'rgba(2, 132, 199, 0.14)',
          'badge' => '+' . number_format($newUsersToday) . ' bugun',
          'label' => 'Online foydalanuvchilar',
          'value' => number_format($onlineUsers),
          'meta' => "So‘nggi 5 daqiqada ilova ichida faol bo‘lgan userlar.",
          'footer_left' => "Premium: " . number_format($premiumUsers),
          'footer_right' => "Tasdiqlangan: " . number_format($verifiedUsers),
      ],
      [
          'icon' => 'bi-cash-coin',
          'color' => '#d97706',
          'bg' => 'rgba(217, 119, 6, 0.10)',
          'glow' => 'rgba(217, 119, 6, 0.14)',
          'badge' => number_format($payoutQueue) . ' navbat',
          'label' => 'Kutilayotgan payout',
          'value' => number_format($payoutQueue),
          'meta' => "Seller va kuryer hisob-kitoblari kechikmasligi uchun shu blok eng muhimlaridan biri.",
          'footer_left' => "Seller: " . number_format($pendingSellerTxCount),
          'footer_right' => "Kuryer: " . number_format($pendingCourierTxCount),
      ],
      [
          'icon' => 'bi-graph-up-arrow',
          'color' => '#16a34a',
          'bg' => 'rgba(22, 163, 74, 0.10)',
          'glow' => 'rgba(22, 163, 74, 0.14)',
          'badge' => number_format($weekRevenue / 1000000, 1) . 'M hafta',
          'label' => "Bugungi to'langan aylanma",
          'value' => number_format($todayRevenue / 1000000, 2) . 'M',
          'meta' => "Faqat paid bo‘lgan buyurtmalar summasi. Real oqim kayfiyatini shu yaxshi ko‘rsatadi.",
          'footer_left' => "AOV: " . number_format($avgOrderValue),
          'footer_right' => "Profit: " . number_format($platformProfitMonth / 1000) . 'K',
      ],
  ];
@endphp

<div class="dash-stage">
  <section class="dash-hero">
    <div>
      <span class="dash-hero__eyebrow">A122 control room</span>
      <div class="dash-hero__title">{{ $dashMoment }}, jamoa. Bugungi operatsion manzara shu yerda.</div>
      <div class="dash-hero__subtitle">
        Dashboard endi faqat raqamlar ombori emas, balki qayerga birinchi qarash kerakligini aytib beradigan boshqaruv paneli.
        Buyurtma oqimi, foydalanuvchi ritmi, payout navbati va biznes signal bir sahifada, ortiqcha stresssiz o‘qiladi.
      </div>

      <div class="dash-chip-row">
        @foreach($heroChips as [$icon, $text])
          <span class="dash-chip"><i class="bi {{ $icon }}"></i>{{ $text }}</span>
        @endforeach
      </div>

      <div class="dash-hero__actions">
        <a href="{{ route('admin.dashboard.live') }}" class="dash-btn dash-btn--dark" target="_blank">
          <i class="bi bi-broadcast-pin"></i> Live monitor
        </a>
        <a href="{{ route('admin.dashboard',['clear_cache'=>1]) }}" class="dash-btn dash-btn--light">
          <i class="bi bi-arrow-clockwise"></i> Snapshotni yangilash
        </a>
      </div>
    </div>

    <div class="dash-hero-side">
      <div class="dash-status-box">
        <div class="dash-status-box__label">Bugungi fokus</div>
        <div class="dash-status-box__value">{{ $dashFocusTitle }}</div>
        <div class="dash-status-box__meta">{{ $dashFocusMeta }}</div>
      </div>

      <div class="dash-side-grid">
        <div class="dash-side-stat">
          <div class="dash-side-stat__label">Yakunlanish</div>
          <div class="dash-side-stat__value">{{ $completionRate }}%</div>
          <div class="dash-side-stat__meta">Mijoz qabul qildi statusiga yetib borgan buyurtmalar ulushi.</div>
        </div>
        <div class="dash-side-stat">
          <div class="dash-side-stat__label">Seller navbati</div>
          <div class="dash-side-stat__value">{{ number_format($pendingSellers) }}</div>
          <div class="dash-side-stat__meta">Tasdiq yoki ko‘rib chiqishni kutayotgan do‘kon arizalari.</div>
        </div>
        <div class="dash-side-stat">
          <div class="dash-side-stat__label">Haftalik aylanma</div>
          <div class="dash-side-stat__value">{{ number_format($weekRevenue / 1000000, 1) }}M</div>
          <div class="dash-side-stat__meta">Joriy hafta paid oqimining tez o‘qiladigan snapshoti.</div>
        </div>
        <div class="dash-side-stat">
          <div class="dash-side-stat__label">Izolyat userlar</div>
          <div class="dash-side-stat__value">{{ number_format($isolatedUsers) }}</div>
          <div class="dash-side-stat__meta">30+ kun faol bo‘lmagan userlar segmenti.</div>
        </div>
      </div>
    </div>
  </section>

  <div class="dash-metric-grid">
    @foreach($topMetrics as $metric)
      <article class="dash-metric-card" style="--metric-color:{{ $metric['color'] }};--metric-bg:{{ $metric['bg'] }};--metric-glow:{{ $metric['glow'] }};">
        <div class="dash-metric-card__top">
          <span class="dash-metric-card__icon"><i class="bi {{ $metric['icon'] }}"></i></span>
          <span class="dash-metric-card__badge">{{ $metric['badge'] }}</span>
        </div>
        <div class="dash-metric-card__label">{{ $metric['label'] }}</div>
        <div class="dash-metric-card__value">{{ $metric['value'] }}</div>
        <div class="dash-metric-card__meta">{{ $metric['meta'] }}</div>
        <div class="dash-metric-card__footer">
          <span>{{ $metric['footer_left'] }}</span>
          <strong>{{ $metric['footer_right'] }}</strong>
        </div>
      </article>
    @endforeach
  </div>

  @if(!empty($alerts))
    <div class="dash-signal-grid">
      @foreach($alerts as [$color,$icon,$title,$desc,$url])
        @php
          $signal = match($color) {
            'danger' => ['rgba(239, 68, 68, 0.12)', '#dc2626', 'Kritik signal'],
            'warning' => ['rgba(245, 158, 11, 0.14)', '#d97706', 'Diqqat kerak'],
            'success' => ['rgba(34, 197, 94, 0.12)', '#16a34a', 'Yaxshi holat'],
            'info' => ['rgba(37, 99, 235, 0.12)', '#2563eb', 'Operatsion signal'],
            default => ['rgba(100, 116, 139, 0.14)', '#64748b', 'Signal'],
          };
        @endphp
        <a href="{{ $url }}" class="dash-signal">
          <span class="dash-signal__icon" style="background:{{ $signal[0] }};color:{{ $signal[1] }}">
            <i class="bi {{ $icon }}"></i>
          </span>
          <span>
            <span class="dash-signal__eyebrow">{{ $signal[2] }}</span>
            <span class="dash-signal__title d-block">{{ $title }}</span>
            <span class="dash-signal__meta d-block">{{ $desc }}</span>
          </span>
        </a>
      @endforeach
    </div>
  @endif

  @if(count($dashQuick))
    <section class="dash-surface">
      <div class="dash-panel-head">
        <div>
          <div class="dash-panel-head__eyebrow">Quick actions</div>
          <div class="dash-panel-head__title">Eng ko‘p ishlatiladigan yo‘llar shu yerda</div>
          <div class="dash-panel-head__meta">Operator miyasi chalg‘imasligi uchun adminning eng issiq bo‘limlari alohida ajratildi.</div>
        </div>
      </div>
      <div class="dash-quick-grid">
        @foreach($dashQuick as $q)
          <a href="{{ $q[2] }}" class="kc-quick-link">
            <span class="kc-quick-link__icon" style="background:{{ $q[4] }};color:{{ $q[5] }}">
              <i class="bi {{ $q[1] }}"></i>
            </span>
            <div class="kc-quick-link__title">{{ $q[0] }}</div>
            <div class="kc-quick-link__meta">{{ $q[3] }}</div>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  <div class="kc-tab-card">
    <div class="nav nav-pills flex-wrap" id="dashSegBar">
      <button type="button" class="nav-link" :class="{ 'active': tab === 'main' }" @click="switchTab('main')"><i class="bi bi-grid-1x2 me-2"></i>Asosiy</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'orders' }" @click="switchTab('orders')"><i class="bi bi-bag-check me-2"></i>Buyurtmalar</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'finance' }" @click="switchTab('finance')"><i class="bi bi-bar-chart-line me-2"></i>Moliya</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'users' }" @click="switchTab('users')"><i class="bi bi-people me-2"></i>Foydalanuvchilar</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'catalog' }" @click="switchTab('catalog')"><i class="bi bi-building me-2"></i>Biznes</button>
    </div>
  </div>
</div>


{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 1: ASOSIY — KPI + Insight pills (pure Bootstrap)                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'main'" x-cloak>

  {{-- Insight pills: tonally tinted Bootstrap cards --}}
  <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-5">
    @php
      $insightPills = [
        ['Bugungi daromad', number_format($todayRevenue/1_000_000,2), 'M',  'success'],
        ['Bugun buyurtma',  number_format($todayOrders),              'ta', 'secondary'],
        ['Hafta daromad',   number_format($weekRevenue/1_000_000,2),  'M',  'primary'],
        ['Hafta buyurtma',  number_format($weekOrders),               'ta', 'info'],
        ['Kutilmoqda',      number_format($pendingOrders),            'ta', 'warning'],
      ];
    @endphp
    @foreach($insightPills as [$lbl, $val, $unit, $tone])
      <div class="col">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-{{ $tone }}-subtle">
          <div class="card-body p-3">
            <div class="small fw-semibold text-{{ $tone }}-emphasis text-uppercase" style="letter-spacing:.08em;">{{ $lbl }}</div>
            <div class="h4 mb-0 mt-2 fw-bold text-dark font-monospace">{{ $val }}<span class="ms-1 fs-6 text-secondary fw-normal">{{ $unit }}</span></div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- 6 KPI cards: standard Bootstrap card with icon + value + footer --}}
  @php
    $kpiCards = [
      [
        'icon' => 'bi-graph-up-arrow', 'tone' => 'success',
        'badge_tone' => 'success', 'badge_label' => 'Bugun: '.number_format($todayRevenue/1000).'K',
        'label' => 'Jami daromad',
        'value' => number_format($totalRevenue/1_000_000,1), 'unit' => 'M UZS',
        'footer_left' => 'Bu oy', 'footer_right' => number_format($monthRevenue/1_000_000,1).'M',
      ],
      [
        'icon' => 'bi-bag-check', 'tone' => 'primary',
        'badge_tone' => 'success', 'badge_label' => $todayOrders.' bugun',
        'label' => 'Buyurtmalar',
        'value' => number_format($totalOrders), 'unit' => '',
        'footer_left' => $pendingOrders.' kutmoqda', 'footer_left_tone' => 'warning',
        'footer_right' => $cancelledOrders.' bekor', 'footer_right_tone' => 'danger',
      ],
      [
        'icon' => 'bi-patch-check', 'tone' => 'info',
        'badge_tone' => $completionRate >= 70 ? 'success' : 'secondary', 'badge_label' => $completionRate.'%',
        'label' => 'Yakunlanish',
        'value' => number_format($completedOrders), 'unit' => '',
        'progress' => $completionRate, 'progress_tone' => 'info',
        'footer_right' => $cancellationRate.'% bekor', 'footer_right_tone' => 'secondary',
      ],
      [
        'icon' => 'bi-people', 'tone' => 'warning',
        'badge_tone' => 'success', 'badge_label' => $onlineUsers.' online',
        'label' => 'Foydalanuvchilar',
        'value' => number_format($totalUsers), 'unit' => '',
        'footer_left' => 'Bugun yangi', 'footer_right' => '+'.$newUsersToday, 'footer_right_tone' => 'success',
      ],
      [
        'icon' => 'bi-gift', 'tone' => 'danger',
        'badge_tone' => $giftUsed > 0 ? 'success' : 'secondary', 'badge_label' => $giftUsed.' ishlatildi',
        'label' => 'Gift Sertifikat',
        'value' => number_format($giftTotal), 'unit' => '',
        'footer_left' => 'Faol',
        'footer_right' => $giftPending > 0 ? $giftPending.' kutmoqda' : (string) $giftSent,
        'footer_right_tone' => $giftPending > 0 ? 'warning' : 'secondary',
      ],
      [
        'icon' => 'bi-box-seam', 'tone' => 'dark',
        'badge_tone' => $mysteryDueCount > 0 ? 'danger' : 'secondary',
        'badge_label' => $mysteryDueCount > 0 ? $mysteryDueCount.' navbat' : "Navbat yo'q",
        'label' => 'Mystery Box',
        'value' => number_format($mysteryActive), 'unit' => '',
        'footer_left' => 'Faol obuna', 'footer_right' => $mysteryPending.' kutmoqda', 'footer_right_tone' => 'secondary',
      ],
    ];
  @endphp
  <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-6">
    @foreach($kpiCards as $k)
      <div class="col">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex align-items-start justify-content-between gap-2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $k['tone'] }}-subtle text-{{ $k['tone'] }}-emphasis" style="width:2.75rem;height:2.75rem;font-size:1.25rem;">
                <i class="bi {{ $k['icon'] }}"></i>
              </span>
              <span class="badge rounded-pill text-bg-{{ $k['badge_tone'] }}-subtle text-{{ $k['badge_tone'] }}-emphasis fw-semibold">
                {{ $k['badge_label'] }}
              </span>
            </div>
            <div>
              <div class="small text-secondary">{{ $k['label'] }}</div>
              <div class="h3 mb-0 mt-1 fw-bold text-dark font-monospace">{{ $k['value'] }}<span class="ms-1 fs-6 text-secondary fw-normal">{{ $k['unit'] }}</span></div>
            </div>
            <div class="mt-auto">
              @if(isset($k['progress']))
                <div class="progress mb-2" role="progressbar" style="height:.35rem;">
                  <div class="progress-bar bg-{{ $k['progress_tone'] }}" style="width:{{ $k['progress'] }}%"></div>
                </div>
              @endif
              <div class="d-flex justify-content-between small">
                <span class="text-{{ $k['footer_left_tone'] ?? 'secondary' }}">{{ $k['footer_left'] ?? '' }}</span>
                <span class="font-monospace fw-semibold text-{{ $k['footer_right_tone'] ?? 'dark' }}">{{ $k['footer_right'] ?? '' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 2: BUYURTMALAR (pure Bootstrap)                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'orders'" x-cloak>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between gap-3">
          <div>
            <h3 class="h6 fw-semibold mb-1 text-dark">Buyurtmalar — 7 kun</h3>
            <div class="small text-secondary">Soni bo'yicha</div>
          </div>
          <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light border rounded-pill">
            Ro'yxat <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
          <div id="chartOrdersWeek" style="min-height:220px;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
          <h3 class="h6 fw-semibold mb-1 text-dark">Daromad — 7 kun</h3>
          <div class="small text-secondary">Mln UZS (to'langan)</div>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
          <div id="chartRevenueWeek" style="min-height:220px;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    {{-- Status donut --}}
    <div class="col-12 col-xl-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
          <h3 class="h6 fw-semibold mb-1 text-dark">Holat bo'yicha</h3>
          <div class="small text-secondary">Jami {{ number_format($totalOrders) }} ta</div>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
          <div id="chartDonut" style="min-height:210px;"></div>
          <div class="row g-2 row-cols-5 mt-2 text-center">
            @foreach([['Mijoz qabul qildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Qadoqda',$packingOrders,'primary'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']] as [$l,$v,$c])
              <div class="col">
                <div class="fw-bold text-{{ $c }}-emphasis font-monospace">{{ number_format($v) }}</div>
                <div class="small text-secondary text-truncate">{{ $l }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    {{-- Recent orders list --}}
    <div class="col-12 col-xl-8">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between gap-3">
          <div>
            <h3 class="h6 fw-semibold mb-1 text-dark">So'nggi buyurtmalar</h3>
            <div class="small text-secondary">Oxirgi 10 ta</div>
          </div>
          <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light border rounded-pill">
            Barchasi <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="card-body p-0">
          @php
            $statusToneMap = [
              'Yetib bordi'   => 'info',
              'Mijoz qabul qildi' => 'success',
              "Yo'lda"        => 'info',
              'Qadoqlanmoqda' => 'primary',
              'Kutilmoqda'    => 'warning',
              'Bekor qilindi' => 'danger',
            ];
          @endphp
          @forelse($recentOrders as $order)
            @php $bs = $statusToneMap[$order['status']] ?? 'secondary'; @endphp
            <div class="d-flex align-items-center gap-3 px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
              <span class="font-monospace fw-semibold text-dark">#{{ $order['id'] }}</span>
              @if($order['gift'])<span title="Sovg'a">🎁</span>@endif
              <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                @php $av = $resolveImg($order['avatar'] ?? null); @endphp
                @if($av)
                  <img src="{{ $av }}" alt="" class="rounded-circle border" style="width:32px;height:32px;object-fit:cover;">
                @else
                  <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary-emphasis fw-bold" style="width:32px;height:32px;">
                    {{ strtoupper(substr($order['customer'],0,1)) }}
                  </span>
                @endif
                <span class="text-truncate fw-medium">{{ $order['customer'] }}</span>
              </div>
              <div class="text-end fw-semibold text-nowrap font-monospace small">{{ $order['amount'] }} <span class="text-secondary fw-normal">UZS</span></div>
              <span class="badge rounded-pill text-bg-{{ $bs }}-subtle text-{{ $bs }}-emphasis fw-semibold">{{ $order['status'] }}</span>
              <span class="small text-secondary text-nowrap d-none d-md-inline">{{ $order['date'] }}</span>
              <a href="{{ route('admin.orders.show',$order['id']) }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0;">
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          @empty
            <div class="text-center text-secondary py-5">
              <i class="bi bi-bag-x display-6 d-block mb-2 text-secondary opacity-50"></i>
              Buyurtmalar yo'q
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Mystery Box queue --}}
  @php $hasMysteryQueue = $mysteryDueToday->count() || $mysteryDueSoon->count(); @endphp
  @if($hasMysteryQueue)
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between gap-3">
        <div>
          <h3 class="h6 fw-semibold mb-1 text-dark">
            <i class="bi bi-box-seam me-2 text-{{ $mysteryDueCount > 0 ? 'danger' : 'primary' }}"></i>Mystery Box navbati
          </h3>
          <div class="small text-secondary">
            @if($mysteryDueCount > 0)
              <span class="text-danger fw-semibold">{{ $mysteryDueCount }} ta kechikdi</span>
            @else
              {{ $mysteryDueSoon->count() }} ta 7 kun ichida
            @endif
          </div>
        </div>
        <a href="{{ route('admin.mystery-box.subscriptions',['tab'=>'active']) }}" class="btn btn-sm btn-light border rounded-pill">Barchasi</a>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-2 row-cols-1 row-cols-md-2">
          @foreach($mysteryDueToday->take(4) as $delivery)
            <div class="col">
              <a href="{{ route('admin.mystery-box.subscription',$delivery->subscription_id) }}" class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white text-decoration-none">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger-emphasis fw-bold flex-shrink-0" style="width:36px;height:36px;">
                  {{ $delivery->month_number }}
                </span>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-semibold text-dark text-truncate">{{ $delivery->subscription?->user?->name }} {{ $delivery->subscription?->user?->lastname }}</div>
                  <div class="small text-secondary text-truncate">{{ $delivery->subscription?->plan?->name_uz }} · {{ $delivery->dispatch_type_label }} · {{ optional($delivery->planned_for_date)->format('d.m.Y') }}</div>
                </div>
                <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis fw-semibold">{{ $delivery->status_label }}</span>
              </a>
            </div>
          @endforeach
          @foreach($mysteryDueSoon->take(4) as $delivery)
            <div class="col">
              <a href="{{ route('admin.mystery-box.subscription',$delivery->subscription_id) }}" class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white text-decoration-none">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold flex-shrink-0" style="width:36px;height:36px;">
                  {{ $delivery->month_number }}
                </span>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-semibold text-dark text-truncate small">{{ $delivery->subscription?->user?->name }} {{ $delivery->subscription?->user?->lastname }}</div>
                  <div class="small text-secondary">{{ optional($delivery->planned_for_date)->format('d.m.Y') }} · {{ $delivery->dispatch_type_label }}</div>
                </div>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 3: MOLIYA (pure Bootstrap)                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'finance'" x-cloak>

  {{-- Revenue chart with period toggle (Bootstrap btn-group) --}}
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <h3 class="h6 fw-semibold mb-1 text-dark">Daromad dinamikasi</h3>
        <div class="small text-secondary">Oy / Hafta / Bugun · mln UZS</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group" id="revPeriodToggle">
          <button type="button" class="btn btn-primary"          data-period="month" onclick="switchRevPeriod(this,'month')">Oy</button>
          <button type="button" class="btn btn-outline-secondary" data-period="week"  onclick="switchRevPeriod(this,'week')">Hafta</button>
          <button type="button" class="btn btn-outline-secondary" data-period="today" onclick="switchRevPeriod(this,'today')">Bugun</button>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light border rounded-pill">
          Buyurtmalar <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
    <div class="card-body pt-0 px-4 pb-4">
      <div id="chartRevenue" style="min-height:288px;"></div>
    </div>
  </div>

  @if($isSuperAdmin)
    @php
      $finRows = [
        ['primary','bi-activity','GMV (brutto)','Barcha buyurtmalar',$gmvTotal,$gmvMonth],
        ['success','bi-check-circle',"To'langan aylanma",'paymentStatus = paid',$totalRevenue,$monthRevenue],
        ['info','bi-truck','Yetkazish','Delivery fee',$totalDeliveryIncome,$monthDeliveryIncome],
        ['primary','bi-percent','Seller komissiya',"O'rtacha {$avgCommissionPct}%",$totalCommissionEarned,$monthCommissionEarned],
        ['success','bi-box-seam','Mystery Box','Faol + yakunlangan',$mysteryRevTotal,$mysteryRevMonth],
        ['danger','bi-gift','Gift Sertifikat','Ishlatilgan: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0],
      ];
      $finCosts = [
        ['danger','bi-ticket-perforated','Promokod',"{$promoOrdersCount} ta buyurtmada",$totalPromoDiscount,$monthPromoDiscount],
        ['warning','bi-cash-stack','Cashback','Foydalanuvchilarga qaytarildi',$totalCashbackPaid,$monthCashbackPaid],
        ['secondary','bi-shop-window','Seller bank payout','Kutilmoqda: '.number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout],
        ['secondary','bi-bicycle','Kuryer payout','Kutilmoqda: '.number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout],
        ['danger','bi-x-circle','Bekor yo\'qotish','status=F',$cancelledRevLoss,$cancelledMonthLoss],
      ];
    @endphp

    <div class="row g-3">
      {{-- Income + Cost + Profit summary --}}
      <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-0">
            {{-- Income section --}}
            <div class="px-4 pt-4 pb-3">
              <h4 class="h6 fw-semibold text-success-emphasis mb-3">
                <i class="bi bi-arrow-up-circle-fill me-1"></i> Daromadlar
              </h4>
              @foreach($finRows as [$tone,$ico,$lbl,$sub,$total,$month])
                <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis flex-shrink-0" style="width:2.25rem;height:2.25rem;">
                    <i class="bi {{ $ico }}"></i>
                  </span>
                  <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-dark text-truncate">{{ $lbl }}</div>
                    <div class="small text-secondary text-truncate">{{ $sub }}</div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-{{ $tone }}-emphasis font-monospace">{{ number_format($total/1_000_000,1) }}<span class="small text-secondary fw-normal">M</span></div>
                    @if($month > 0)<div class="small text-secondary font-monospace">{{ number_format($month/1000) }}K / oy</div>@endif
                  </div>
                </div>
              @endforeach
            </div>
            {{-- Cost section --}}
            <div class="px-4 py-3 bg-light border-top border-bottom">
              <h4 class="h6 fw-semibold text-danger-emphasis mb-3">
                <i class="bi bi-arrow-down-circle-fill me-1"></i> Chiqimlar
              </h4>
              @foreach($finCosts as [$tone,$ico,$lbl,$sub,$total,$month])
                <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis flex-shrink-0" style="width:2.25rem;height:2.25rem;">
                    <i class="bi {{ $ico }}"></i>
                  </span>
                  <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-dark text-truncate">{{ $lbl }}</div>
                    <div class="small text-secondary text-truncate">{{ $sub }}</div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-danger-emphasis font-monospace">−{{ number_format($total/1_000_000,1) }}<span class="small text-secondary fw-normal">M</span></div>
                    @if($month > 0)<div class="small text-secondary font-monospace">{{ number_format($month/1000) }}K / oy</div>@endif
                  </div>
                </div>
              @endforeach
            </div>
            {{-- Profit row --}}
            <div class="d-flex align-items-center gap-3 px-4 py-3 bg-primary-subtle">
              <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                <i class="bi bi-stars"></i>
              </span>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-primary-emphasis">Platform sof foyda</div>
                <div class="small text-primary-emphasis opacity-75">Komissiya + Yetkazish − Promo − Cashback − Kuryer</div>
              </div>
              <div class="text-end">
                <div class="h5 mb-0 fw-bold text-primary-emphasis font-monospace">{{ number_format($platformProfit/1_000_000,2) }}<span class="small text-secondary fw-normal"> M</span></div>
                <div class="small text-primary-emphasis font-monospace">Bu oy: {{ number_format($platformProfitMonth/1000) }}K</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-7 d-flex flex-column gap-3">
        {{-- AOV chart --}}
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
            <h3 class="h6 fw-semibold mb-1 text-dark">AOV dinamikasi</h3>
            <div class="small text-secondary">Joriy: {{ number_format($avgOrderValue) }} UZS · Komissiya: {{ $avgCommissionPct }}%</div>
          </div>
          <div class="card-body pt-0 px-4 pb-4">
            <div id="chartAov" style="min-height:130px;"></div>
          </div>
        </div>

        {{-- Mahsulot turi + Xaridorlar --}}
        <div class="row g-3 row-cols-1 row-cols-md-2">
          <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
                <h3 class="h6 fw-semibold mb-1 text-dark">Mahsulot turi</h3>
                <div class="small text-secondary">Daromad ulushi</div>
              </div>
              <div class="card-body pt-0 px-4 pb-4">
                @php
                  $typeTotal = max(1, $revenueByType['book'] + $revenueByType['stationery']);
                  $bookPct = round($revenueByType['book']/$typeTotal*100, 1);
                  $statPct = round($revenueByType['stationery']/$typeTotal*100, 1);
                @endphp
                <div id="chartTypePie" style="min-height:120px;"></div>
                @foreach([['Kitoblar',$revenueByType['book'],'primary','bi-book',$bookPct],['Kanstovar',$revenueByType['stationery'],'warning','bi-pencil-square',$statPct]] as [$l,$v,$c,$i,$p])
                  <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <i class="bi {{ $i }} text-{{ $c }}-emphasis"></i>
                    <span class="text-dark fw-medium flex-grow-1">{{ $l }}</span>
                    <span class="small text-secondary font-monospace">{{ number_format($v/1000) }}K</span>
                    <span class="badge rounded-pill text-bg-{{ $c }}-subtle text-{{ $c }}-emphasis fw-semibold">{{ $p }}%</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
                <h3 class="h6 fw-semibold mb-1 text-dark">Xaridorlar (bu oy)</h3>
                <div class="small text-secondary">Yangi vs Takroriy</div>
              </div>
              <div class="card-body pt-0 px-4 pb-4">
                @php
                  $totalB = max(1, $repeatBuyersMonth + $newBuyersMonth);
                  $repeatPct = $totalB > 1 ? round($repeatBuyersMonth/$totalB*100) : 0;
                @endphp
                <div id="chartBuyers" style="min-height:120px;"></div>
                @foreach([['Yangi',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'primary','2+ marta']] as [$l,$v,$c,$s])
                  <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <span class="rounded-circle bg-{{ $c }} d-inline-block" style="width:.625rem;height:.625rem;"></span>
                    <div class="flex-grow-1">
                      <div class="fw-medium text-dark small">{{ $l }}</div>
                      <div class="text-secondary" style="font-size:.7rem;">{{ $s }}</div>
                    </div>
                    <span class="fw-semibold text-dark font-monospace">{{ number_format($v) }}</span>
                  </div>
                @endforeach
                <div class="mt-2 pt-2 border-top">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small text-secondary">Qayta qaytish</span>
                    <span class="fw-bold text-primary-emphasis font-monospace">{{ $repeatPct }}%</span>
                  </div>
                  <div class="progress" role="progressbar" style="height:.4rem;">
                    <div class="progress-bar bg-primary" style="width:{{ $repeatPct }}%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        @if($deliveryTypeSplit->count())
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
              <h3 class="h6 fw-semibold mb-0 text-dark">Yetkazish turlari</h3>
            </div>
            <div class="card-body pt-0 px-4 pb-3">
              @foreach($deliveryTypeSplit->take(5) as $dt)
                <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                  <span class="text-dark fw-medium">{{ $dt->deliveryType }}</span>
                  <span class="text-secondary font-monospace fw-semibold">{{ number_format($dt->cnt) }} ta</span>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        @if(count($salesGeoCountries))
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
              <h3 class="h6 fw-semibold mb-1 text-dark">Hududlar bo‘yicha sotuvlar</h3>
              <div class="small text-secondary">Davlatni tanlang, sotuv bo‘lgan viloyatlar avtomatik chiqadi</div>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
              <div class="btn-group btn-group-sm mb-3" role="group" id="salesGeoCountryToggle">
                @foreach($salesGeoCountries as $country)
                  <button type="button"
                          class="btn {{ $salesGeoDefaultCountry === $country['key'] ? 'btn-primary' : 'btn-outline-secondary' }}"
                          data-country="{{ $country['key'] }}"
                          onclick="switchSalesGeoCountry(this,'{{ $country['key'] }}')">
                    {{ $country['label'] }}
                  </button>
                @endforeach
              </div>
              <div class="row g-3">
                <div class="col-12 col-xl-8">
                  <div id="chartSalesGeo" style="min-height:220px;"></div>
                </div>
                <div class="col-12 col-xl-4">
                  <div id="salesGeoCountrySummary" class="row row-cols-2 g-2 mb-3"></div>
                  <div id="salesGeoRegionList" class="d-flex flex-column gap-2"></div>
                </div>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  @else
    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
      <div class="card-body">
        <i class="bi bi-lock display-6 d-block mb-2 text-secondary opacity-50"></i>
        <div class="text-secondary">Moliyaviy hisobot faqat superadmin uchun</div>
      </div>
    </div>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 4: FOYDALANUVCHILAR (pure Bootstrap)                                  --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'users'" x-cloak>
  <div class="row g-3">

    {{-- User stats with progress bars --}}
    <div class="col-12 col-xl-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between gap-2">
          <div>
            <h3 class="h6 fw-semibold mb-1 text-dark">Foydalanuvchilar holati</h3>
            <div class="small text-secondary">{{ number_format($totalUsers) }} ta jami ro'yxatda</div>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light border rounded-pill">
            Barchasi <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
          {{-- Quick stat strip --}}
          <div class="row g-2 row-cols-4 mb-3 text-center">
            @foreach([[$totalUsers,'Jami','dark'],[$onlineUsers,'Online','success'],[$premiumUsers,'Premium','warning'],[$newUsersToday,'+Bugun','primary']] as [$v,$l,$c])
              <div class="col">
                <div class="fw-bold text-{{ $c }}-emphasis font-monospace">{{ number_format($v) }}</div>
                <div class="small text-secondary">{{ $l }}</div>
              </div>
            @endforeach
          </div>
          {{-- Progress rows --}}
          @foreach([['Online (5 min)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'primary'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolyat (30+ kun)',$isolatedUsers,'danger']] as [$l,$v,$c])
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="rounded-circle bg-{{ $c }} d-inline-block flex-shrink-0" style="width:.5rem;height:.5rem;"></span>
              <span class="small text-dark fw-medium" style="min-width:9rem;">{{ $l }}</span>
              <div class="progress flex-grow-1" role="progressbar" style="height:.4rem;">
                <div class="progress-bar bg-{{ $c }}" style="width:{{ $totalUsers>0?min(round($v/$totalUsers*100),100):0 }}%"></div>
              </div>
              <span class="small fw-semibold text-dark font-monospace">{{ number_format($v) }}</span>
            </div>
          @endforeach

          <div class="mt-3 pt-3 border-top">
            <div class="small text-secondary mb-1">Yangi userlar — 7 kun</div>
            <div id="chartUserSparkline" style="min-height:60px;"></div>
          </div>

          @if($isolatedUsers > 0)
            <a href="{{ route('admin.users.index') }}" class="alert alert-danger d-flex align-items-center gap-2 mb-0 mt-3 small text-decoration-none">
              <i class="bi bi-person-x"></i>
              <span class="flex-grow-1">{{ number_format($isolatedUsers) }} ta user 30+ kun yo'q</span>
              <span class="fw-semibold">Ko'rish →</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    {{-- Online users list --}}
    <div class="col-12 col-xl-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
          <h3 class="h6 fw-semibold mb-1 text-dark">Hozir online</h3>
          <div class="small text-success-emphasis d-flex align-items-center gap-2">
            <span class="rounded-circle bg-success d-inline-block" style="width:.5rem;height:.5rem;"></span>
            {{ $onlineUsers }} nafar
          </div>
        </div>
        <div class="card-body pt-0 px-3 pb-3">
          <div class="d-flex flex-column gap-1">
            @forelse($onlineUsersList as $u)
              <a href="{{ route('admin.users.show',$u->id) }}" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none hover-bg-light">
                @php $av = $resolveImg($u->avatar ?? null); @endphp
                @if($av)
                  <img src="{{ $av }}" alt="" class="rounded-circle border flex-shrink-0" style="width:36px;height:36px;object-fit:cover;">
                @else
                  <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary-emphasis fw-bold flex-shrink-0" style="width:36px;height:36px;">
                    {{ strtoupper(substr($u->name??'U',0,1)) }}
                  </span>
                @endif
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-semibold text-dark text-truncate">{{ $u->name }} {{ $u->lastname }}</div>
                  <div class="small text-secondary text-truncate">{{ $u->last_seen_at ? \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() : '—' }}</div>
                </div>
                <span class="rounded-circle bg-success d-inline-block" style="width:.5rem;height:.5rem;"></span>
              </a>
            @empty
              <div class="text-center text-secondary py-5">
                <i class="bi bi-wifi-off display-6 d-block mb-2 opacity-50"></i>
                Hozir hech kim online emas
              </div>
            @endforelse
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-pill w-100 mt-3">
            Barcha foydalanuvchilar <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

    {{-- Top buyers --}}
    <div class="col-12 col-xl-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between gap-2">
          <div>
            <h3 class="h6 fw-semibold mb-1 text-dark">Top mijozlar</h3>
            <div class="small text-secondary">Eng ko'p xarid qilganlar</div>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light border rounded-pill">Barchasi</a>
        </div>
        <div class="card-body pt-0 px-3 pb-3">
          @forelse($topBuyers as $i => $buyer)
            @php
              $topBuyerHref = $buyer->user ? route('admin.users.show', $buyer->user) : null;
              $rankTone = $i === 0 ? 'warning' : ($i === 1 ? 'secondary' : ($i === 2 ? 'danger' : 'light'));
              $rankBorder = $i < 3 ? '' : 'border';
            @endphp
            <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $rankTone }}-subtle text-{{ $rankTone }}-emphasis fw-bold flex-shrink-0 {{ $rankBorder }}" style="width:24px;height:24px;font-size:.75rem;">{{ $i+1 }}</span>
              @php $av = $resolveImg($buyer->user?->avatar); @endphp
              @if($av)
                <img src="{{ $av }}" alt="" class="rounded-circle border flex-shrink-0" style="width:32px;height:32px;object-fit:cover;">
              @else
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary-emphasis fw-bold flex-shrink-0" style="width:32px;height:32px;">
                  {{ strtoupper(substr($buyer->user?->name??'U',0,1)) }}
                </span>
              @endif
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-dark text-truncate small">
                  @if($topBuyerHref)
                    <a href="{{ $topBuyerHref }}" class="text-decoration-none text-dark">{{ $buyer->user ? $buyer->user->name.' '.$buyer->user->lastname : 'ID:'.$buyer->user_id }}</a>
                  @else
                    {{ $buyer->user ? $buyer->user->name.' '.$buyer->user->lastname : 'ID:'.$buyer->user_id }}
                  @endif
                </div>
                <div class="text-secondary" style="font-size:.7rem;">{{ $buyer->order_count }} ta buyurtma</div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-dark font-monospace small">{{ number_format($buyer->total_spent/1000) }}K</div>
                <div class="text-secondary" style="font-size:.65rem;">UZS</div>
              </div>
            </div>
          @empty
            <div class="text-center text-secondary py-5">
              <i class="bi bi-person-x display-6 d-block mb-2 opacity-50"></i>
              Ma'lumot yo'q
            </div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 5: BIZNES (pure Bootstrap)                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'catalog'" x-cloak>
  <div class="row g-3">

    {{-- Top products --}}
    <div class="col-12 col-xl-7">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-2">
          <h3 class="h6 fw-semibold mb-1 text-dark">Top mahsulotlar</h3>
          <div class="small text-secondary">Eng ko'p sotilganlar</div>
        </div>
        <div class="card-body pt-0 px-3 pb-3">
          @forelse($topMixedProducts as $i => $product)
            @php
              $imgs = is_array($product->images) ? $product->images : json_decode($product->images ?? '[]', true);
              $img  = $resolveImg($imgs[0] ?? null);
              $rankTone = $i === 0 ? 'warning' : ($i === 1 ? 'secondary' : ($i === 2 ? 'danger' : 'light'));
              $rankBorder = $i < 3 ? '' : 'border';
              $typeTone = $product->_type === 'stationery' ? 'warning' : 'info';
              $typeLabel = $product->_type === 'stationery' ? 'Kanstovar' : 'Kitob';
            @endphp
            <div class="d-flex align-items-center gap-3 px-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $rankTone }}-subtle text-{{ $rankTone }}-emphasis fw-bold flex-shrink-0 {{ $rankBorder }}" style="width:28px;height:28px;font-size:.8rem;">{{ $i+1 }}</span>
              <span class="rounded-3 bg-light d-inline-flex align-items-center justify-content-center overflow-hidden border flex-shrink-0" style="width:48px;height:48px;">
                @if($img)
                  <img src="{{ $img }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                @else
                  <i class="bi bi-{{ $product->_type === 'stationery' ? 'box' : 'book' }} text-secondary"></i>
                @endif
              </span>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-dark text-truncate">{{ $product->name }}</div>
                <div class="d-flex align-items-center gap-2 small">
                  <span class="badge rounded-pill text-bg-{{ $typeTone }}-subtle text-{{ $typeTone }}-emphasis fw-semibold">{{ $typeLabel }}</span>
                  <span class="text-secondary font-monospace">{{ number_format($product->total_revenue/1000) }}K rev.</span>
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-dark font-monospace">{{ number_format($product->sold_count) }}</div>
                <div class="text-secondary small">dona</div>
              </div>
            </div>
          @empty
            <div class="text-center text-secondary py-5">
              <i class="bi bi-box display-6 d-block mb-2 opacity-50"></i>
              Ma'lumot yo'q
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Sellers + Couriers stat cards --}}
    <div class="col-12 col-xl-5 d-flex flex-column gap-3">
      @php
        $bizCards = [
          [
            'title' => 'Sotuvchilar',
            'sub'   => "Do'konlar platformada",
            'icon'  => 'bi-shop-window',
            'tone'  => 'success',
            'href'  => route('admin.sellers.index'),
            'nums'  => [[$approvedSellers,'Faol','success'],[$totalSellers,'Jami','dark'],[$pendingSellers,'Ariza','warning']],
            'rate'  => $totalSellers > 0 ? round($approvedSellers/$totalSellers*100) : 0,
            'alert' => $pendingSellers > 0
                ? ['warning','bi-clock', "{$pendingSellers} ta yangi ariza", route('admin.sellers.index',['tab'=>'pending'])]
                : null,
          ],
          [
            'title' => 'Kuryerlar',
            'sub'   => 'Faol yetkazuvchilar',
            'icon'  => 'bi-bicycle',
            'tone'  => 'info',
            'href'  => route('admin.couriers.index'),
            'nums'  => [[$activeCouriers,'Faol','info'],[$totalCouriers,'Jami','dark'],[0,'Navbatda','secondary']],
            'rate'  => $totalCouriers > 0 ? round($activeCouriers/$totalCouriers*100) : 0,
            'alert' => null,
          ],
        ];
      @endphp
      @foreach($bizCards as $b)
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $b['tone'] }}-subtle text-{{ $b['tone'] }}-emphasis flex-shrink-0" style="width:2.75rem;height:2.75rem;font-size:1.25rem;">
                <i class="bi {{ $b['icon'] }}"></i>
              </span>
              <div class="flex-grow-1">
                <h3 class="h6 mb-1 fw-semibold text-dark">{{ $b['title'] }}</h3>
                <div class="small text-secondary">{{ $b['sub'] }}</div>
              </div>
              <a href="{{ $b['href'] }}" class="btn btn-sm btn-light border rounded-pill">
                Ko'rish <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
            <div class="row g-2 row-cols-3 text-center mb-3">
              @foreach($b['nums'] as [$v,$l,$c])
                <div class="col">
                  <div class="fw-bold text-{{ $c }}-emphasis font-monospace fs-5">{{ number_format($v) }}</div>
                  <div class="small text-secondary">{{ $l }}</div>
                </div>
              @endforeach
            </div>
            <div>
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-secondary">Faollik darajasi</span>
                <span class="fw-semibold text-dark">{{ $b['rate'] }}%</span>
              </div>
              <div class="progress" role="progressbar" style="height:.4rem;">
                <div class="progress-bar bg-{{ $b['tone'] }}" style="width:{{ $b['rate'] }}%"></div>
              </div>
            </div>
            @if($b['alert'])
              @php [$t, $ic, $msg, $h] = $b['alert']; @endphp
              <a href="{{ $h }}" class="alert alert-{{ $t }} d-flex align-items-center gap-2 mb-0 mt-3 small text-decoration-none">
                <i class="bi {{ $ic }}"></i>
                <span class="flex-grow-1">{{ $msg }}</span>
                <span class="fw-semibold">Ko'rish →</span>
              </a>
            @endif
          </div>
        </div>
      @endforeach
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

function _getApex() {
  if (window.ApexCharts) return window.ApexCharts;
  console.error('ApexCharts not available');
  return null;
}

function _setChartFallback(hostId, message = "Chart yuklanmadi") {
  const host = document.getElementById(hostId);
  if (!host) return null;
  host.innerHTML = `<div class="dash-empty"><i class="bi bi-bar-chart-line dash-empty__ico"></i>${message}</div>`;
  return host;
}

// ── Tab system ───────────────────────────────────────────────────────────────
const _tabInitialized = { main: true };
const _tabInitAttempts = {};
const _chartTabs = new Set(['orders', 'finance', 'users']);

function _canInitChartTab(tab) {
  return !_chartTabs.has(tab) || !!window.ApexCharts;
}

function _scheduleTabInit(tab, attempt = 0) {
  if (!_chartTabs.has(tab)) return;
  const nextAttempt = attempt + 1;
  if (nextAttempt > 20) {
    console.error(`dashboard tab init timeout: ${tab}`);
    return;
  }

  _tabInitAttempts[tab] = nextAttempt;
  setTimeout(() => {
    if (!_canInitChartTab(tab)) {
      _scheduleTabInit(tab, nextAttempt);
      return;
    }
    _initTab(tab);
  }, 120);
}

function _ensureTabInit(tab) {
  if (_canInitChartTab(tab)) {
    _initTab(tab);
    return;
  }
  _scheduleTabInit(tab, _tabInitAttempts[tab] || 0);
}

function _initTab(tab) {
  if (tab === 'orders')  { try { _initOrderCharts();  } catch(e) { console.error('orders chart:', e); } }
  if (tab === 'finance') { try { _initFinanceCharts(); } catch(e) { console.error('finance chart:', e); } }
  if (tab === 'users')   { try { _initUserCharts();   } catch(e) { console.error('users chart:', e); } }
}

// ── Orders tab ───────────────────────────────────────────────────────────────
let _ordersWeekChart = null;
let _revenueWeekChart = null;
let _orderDonutChart = null;
function _initOrderCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartOrdersWeek');
    _setChartFallback('chartRevenueWeek');
    _setChartFallback('chartDonut');
    return;
  }

  const ordersWeekHost = document.getElementById('chartOrdersWeek');
  const revenueWeekHost = document.getElementById('chartRevenueWeek');
  const donutHost = document.getElementById('chartDonut');
  if (!ordersWeekHost || !revenueWeekHost || !donutHost) return;

  const ordersWeekOptions = {
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
  };

  if (_ordersWeekChart) {
    _ordersWeekChart.updateOptions(ordersWeekOptions, true, true);
  } else {
    _ordersWeekChart = new Apex(ordersWeekHost, ordersWeekOptions);
    _ordersWeekChart.render();
  }

  const revenueWeekOptions = {
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
  };

  if (_revenueWeekChart) {
    _revenueWeekChart.updateOptions(revenueWeekOptions, true, true);
  } else {
    _revenueWeekChart = new Apex(revenueWeekHost, revenueWeekOptions);
    _revenueWeekChart.render();
  }

  const donutOptions = {
    series:[{{ $completedOrders }},{{ $onwayOrders }},{{ $packingOrders }},{{ $pendingOrders }},{{ $cancelledOrders }}],
    labels:['Mijoz qabul qildi',"Yo'lda",'Qadoqlanmoqda','Kutilmoqda','Bekor'],
    colors:[C.success,C.info,C.accent,C.warning,C.danger],
    chart:{type:'donut',height:212,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
    dataLabels:{enabled:false},
    plotOptions:{
      pie:{
        donut:{
          size:'74%',
          labels:{
            show:true,
            total:{
              show:true,
              label:'Jami',
              fontSize:'12px',
              color:C.muted,
              formatter:()=>'{{ number_format($totalOrders) }}'
            },
            value:{
              fontSize:'20px',
              fontWeight:700,
              color:C.text,
              fontFamily:'JetBrains Mono,monospace'
            }
          }
        }
      }
    },
    stroke:{width:2,colors:[C.surface]},
    tooltip:{theme:isDark?'dark':'light'},
  };

  if (_orderDonutChart) {
    _orderDonutChart.updateOptions(donutOptions, true, true);
  } else {
    _orderDonutChart = new Apex(donutHost, donutOptions);
    _orderDonutChart.render();
  }

  setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
}

// ── Finance tab ──────────────────────────────────────────────────────────────
let _revChart = null;
let _salesGeoChart = null;
let _aovChart = null;
let _typePieChart = null;
let _buyersChart = null;
const salesGeoState = {
  selected: @json($salesGeoDefaultCountry),
  countries: @json($salesGeoCountries),
  regions: @json($salesGeoRegionsByCountry),
};
function _initFinanceCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartRevenue');
    _setChartFallback('chartAov');
    _setChartFallback('chartTypePie');
    _setChartFallback('chartBuyers');
    _setChartFallback('chartSalesGeo');
    return;
  }

  const revenueHost = document.getElementById('chartRevenue');
  if (!revenueHost) return;

  const revenueOptions = {
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
  };

  if (_revChart) {
    _revChart.updateOptions(revenueOptions, true, true);
  } else {
    _revChart = new Apex(revenueHost, revenueOptions);
    _revChart.render();
  }

  @if($isSuperAdmin)
  @php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); @endphp
  const aovHost = document.getElementById('chartAov');
  const typePieHost = document.getElementById('chartTypePie');
  const buyersHost = document.getElementById('chartBuyers');

  const aovOptions = {
    series:[{name:'AOV',data:{!! $aovVals !!}}],
    chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    colors:[C.teal],stroke:{curve:'smooth',width:2.5},
    markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
    xaxis:{categories:{!! $aovLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
    grid:{borderColor:C.grid,strokeDashArray:4},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
  };
  if (aovHost) {
    if (_aovChart) {
      _aovChart.updateOptions(aovOptions, true, true);
    } else {
      _aovChart = new Apex(aovHost, aovOptions);
      _aovChart.render();
    }
  }

  const typePieOptions = {
    series:[{{ $revenueByType['book'] }},{{ $revenueByType['stationery'] }}],
    labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
  };
  if (typePieHost) {
    if (_typePieChart) {
      _typePieChart.updateOptions(typePieOptions, true, true);
    } else {
      _typePieChart = new Apex(typePieHost, typePieOptions);
      _typePieChart.render();
    }
  }

  const buyersOptions = {
    series:[{{ $newBuyersMonth }},{{ $repeatBuyersMonth }}],
    labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  };
  if (buyersHost) {
    if (_buyersChart) {
      _buyersChart.updateOptions(buyersOptions, true, true);
    } else {
      _buyersChart = new Apex(buyersHost, buyersOptions);
      _buyersChart.render();
    }
  }
  @endif

  _initSalesGeoChart();
  setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
}

function _initSalesGeoChart() {
  const Apex = _getApex();
  const host = document.getElementById('chartSalesGeo');
  if (!host || !salesGeoState.selected) return;
  if (!Apex) {
    _setChartFallback('chartSalesGeo');
    return;
  }

  const regions = salesGeoState.regions[salesGeoState.selected] || [];
  const categories = regions.map(r => r.label);
  const revenueData = regions.map(r => Number(((r.revenue || 0) / 1000000).toFixed(2)));

  if (_salesGeoChart) {
    _salesGeoChart.updateOptions({
      series:[{name:'Sotuv', data: revenueData}],
      xaxis:{categories},
    });
  } else {
    _salesGeoChart = new Apex(host, {
      series:[{name:'Sotuv', data: revenueData}],
      chart:{type:'bar',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
      colors:[C.accent],
      plotOptions:{bar:{horizontal:true,borderRadius:7,barHeight:'58%'}},
      dataLabels:{enabled:false},
      xaxis:{
        categories,
        labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'},
        axisBorder:{show:false},
        axisTicks:{show:false},
      },
      yaxis:{labels:{style:{colors:C.text,fontSize:'12px'}}},
      grid:{borderColor:C.grid,strokeDashArray:4},
      tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
    });
    _salesGeoChart.render();
  }

  _renderSalesGeoSide();
}

function _renderSalesGeoSide() {
  const summaryHost = document.getElementById('salesGeoCountrySummary');
  const listHost = document.getElementById('salesGeoRegionList');
  if (!summaryHost || !listHost || !salesGeoState.selected) return;

  const country = (salesGeoState.countries || []).find(c => c.key === salesGeoState.selected);
  const regions = salesGeoState.regions[salesGeoState.selected] || [];

  if (!country) {
    summaryHost.innerHTML = '';
    listHost.innerHTML = '';
    return;
  }

  summaryHost.innerHTML = `
    <div class="col">
      <div class="rounded-3 border bg-light px-3 py-3">
        <div class="small text-secondary text-uppercase" style="letter-spacing:.12em;font-size:.7rem;">Buyurtmalar</div>
        <div class="mt-1 h5 mb-0 fw-semibold text-dark font-monospace">${Number(country.orders || 0).toLocaleString()}</div>
      </div>
    </div>
    <div class="col">
      <div class="rounded-3 border bg-light px-3 py-3">
        <div class="small text-secondary text-uppercase" style="letter-spacing:.12em;font-size:.7rem;">Viloyatlar</div>
        <div class="mt-1 h5 mb-0 fw-semibold text-dark font-monospace">${Number(country.regions_count || 0).toLocaleString()}</div>
      </div>
    </div>
  `;

  listHost.innerHTML = regions.map((region, index) => {
    const amount = Number(region.revenue || 0);
    return `
      <div class="rounded-3 border bg-white px-3 py-2">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div class="min-w-0">
            <div class="small fw-semibold text-dark text-truncate">${index + 1}. ${region.label}</div>
            <div class="text-secondary" style="font-size:.7rem;">${Number(region.orders || 0).toLocaleString()} ta buyurtma</div>
          </div>
          <div class="text-end">
            <div class="small fw-semibold text-dark font-monospace">${Math.round(amount / 1000).toLocaleString()}K</div>
            <div class="text-secondary" style="font-size:.65rem;">UZS</div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function switchSalesGeoCountry(btn, countryKey) {
  document.querySelectorAll('#salesGeoCountryToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  salesGeoState.selected = countryKey;
  _initSalesGeoChart();
}

// ── Users tab ────────────────────────────────────────────────────────────────
let _userSparkChart = null;
function _initUserCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartUserSparkline');
    return;
  }
  const host = document.getElementById('chartUserSparkline');
  if (!host) return;
  @php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); @endphp
  const sparkOptions = {
    series:[{name:'Yangi user',data:{!! $sparkCounts !!}}],
    chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
    colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
    xaxis:{categories:{!! $sparkDays !!}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  };
  if (_userSparkChart) {
    _userSparkChart.updateOptions(sparkOptions, true, true);
  } else {
    _userSparkChart = new Apex(host, sparkOptions);
    _userSparkChart.render();
  }
}

// ── Period toggle ────────────────────────────────────────────────────────────
function switchRevPeriod(btn, period) {
  document.querySelectorAll('#revPeriodToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (!_revChart) return;
  const d = revData[period];
  if (!d) return;
  _revChart.updateOptions({
    series:[{name:'Daromad',data:d.data}],
    xaxis:{categories:d.labels},
    dataLabels:{formatter:d.formatter},
    yaxis:{labels:{formatter:d.formatter}},
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const activeTab = localStorage.getItem('a122-dash-tab') || 'main';
  if (_chartTabs.has(activeTab)) {
    _tabInitialized[activeTab] = true;
    _ensureTabInit(activeTab);
  }
});

window.addEventListener('a122:charts-ready', () => {
  const activeTab = localStorage.getItem('a122-dash-tab') || 'main';
  if (_chartTabs.has(activeTab)) {
    _tabInitialized[activeTab] = true;
    _ensureTabInit(activeTab);
  }
});
</script>
@endpush
