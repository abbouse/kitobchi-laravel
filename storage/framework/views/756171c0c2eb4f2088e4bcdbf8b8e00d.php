<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AdminPanel — Dashboard</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>

<!-- Bootstrap Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

<!-- ApexCharts -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.css" rel="stylesheet"/>

<style>
:root {
  --bg-base:       #0f1117;
  --bg-surface:    #181c27;
  --bg-elevated:   #1e2336;
  --bg-hover:      #252b3d;
  --border-color:  rgba(255,255,255,0.07);
  --border-hover:  rgba(255,255,255,0.13);
  --text-primary:  #eef0f7;
  --text-secondary:#8b91a8;
  --text-muted:    #555c75;
  --accent:        #4f7cff;
  --accent-dim:    rgba(79,124,255,0.12);
  --accent-hover:  #6690ff;
  --success:       #22c98e;
  --success-dim:   rgba(34,201,142,0.12);
  --warning:       #f5a623;
  --warning-dim:   rgba(245,166,35,0.12);
  --danger:        #ff5c6a;
  --danger-dim:    rgba(255,92,106,0.12);
  --info:          #38bdf8;
  --info-dim:      rgba(56,189,248,0.12);
  --sidebar-w:     260px;
  --radius:        12px;
  --radius-sm:     8px;
  --shadow:        0 4px 24px rgba(0,0,0,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg-base);
  color: var(--text-primary);
  min-height: 100vh;
  display: flex;
  overflow-x: hidden;
}

/* ─── Sidebar ─────────────────────────────────────── */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--bg-surface);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  transition: transform .3s ease;
}

.sidebar-brand {
  padding: 24px 20px 20px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-icon {
  width: 36px; height: 36px;
  background: var(--accent);
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 700; color: #fff;
  letter-spacing: -1px;
  box-shadow: 0 0 20px rgba(79,124,255,0.4);
}

.brand-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -.3px;
}

.brand-sub {
  font-size: 11px;
  color: var(--text-muted);
  font-family: 'DM Mono', monospace;
}

.sidebar-nav {
  flex: 1;
  padding: 16px 12px;
  overflow-y: auto;
}

.nav-section-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 8px 10px 6px;
  margin-top: 8px;
}

.nav-item-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  font-size: 13.5px;
  font-weight: 400;
  text-decoration: none;
  transition: all .15s ease;
  margin-bottom: 2px;
  position: relative;
}

.nav-item-link:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.nav-item-link.active {
  background: var(--accent-dim);
  color: var(--accent);
  font-weight: 500;
}

.nav-item-link.active::before {
  content: '';
  position: absolute;
  left: 0; top: 20%; bottom: 20%;
  width: 3px;
  background: var(--accent);
  border-radius: 0 4px 4px 0;
}

.nav-item-link i {
  font-size: 16px;
  width: 18px;
  text-align: center;
  flex-shrink: 0;
}

.nav-badge {
  margin-left: auto;
  background: var(--accent-dim);
  color: var(--accent);
  font-size: 10px;
  font-weight: 600;
  font-family: 'DM Mono', monospace;
  padding: 2px 7px;
  border-radius: 20px;
}

.nav-badge.success { background: var(--success-dim); color: var(--success); }

.sidebar-footer {
  padding: 16px 12px;
  border-top: 1px solid var(--border-color);
}

.user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background .15s;
}
.user-card:hover { background: var(--bg-hover); }

.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), #7c5cfc);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 600; color: #fff;
  flex-shrink: 0;
}

.user-name  { font-size: 13px; font-weight: 500; color: var(--text-primary); }
.user-role  { font-size: 11px; color: var(--text-muted); }
.user-arrow { margin-left: auto; color: var(--text-muted); font-size: 14px; }

/* ─── Main ─────────────────────────────────────────── */
.main-wrap {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ─── Topbar ─────────────────────────────────────────── */
.topbar {
  height: 60px;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  padding: 0 24px;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.topbar-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.topbar-breadcrumb {
  font-size: 12px;
  color: var(--text-muted);
  font-family: 'DM Mono', monospace;
}

.topbar-search {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 7px 14px;
  width: 220px;
  transition: border-color .2s;
}

.topbar-search:focus-within {
  border-color: var(--accent);
}

.topbar-search input {
  background: none;
  border: none;
  outline: none;
  color: var(--text-primary);
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  width: 100%;
}

.topbar-search input::placeholder { color: var(--text-muted); }

.topbar-search i { color: var(--text-muted); font-size: 14px; flex-shrink: 0; }

.topbar-action {
  width: 36px; height: 36px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-secondary);
  font-size: 16px;
  cursor: pointer;
  transition: all .15s;
  position: relative;
  text-decoration: none;
}

.topbar-action:hover {
  background: var(--bg-hover);
  border-color: var(--border-hover);
  color: var(--text-primary);
}

.notif-dot {
  position: absolute;
  top: 7px; right: 7px;
  width: 7px; height: 7px;
  background: var(--danger);
  border-radius: 50%;
  border: 1.5px solid var(--bg-surface);
}

/* ─── Page content ──────────────────────────────────── */
.page-content {
  padding: 24px;
  flex: 1;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -.4px;
}

.page-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 3px;
}

/* ─── Cards ─────────────────────────────────────────── */
.card-dark {
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 22px;
  transition: border-color .2s;
}

.card-dark:hover { border-color: var(--border-hover); }

/* ─── Metric cards ──────────────────────────────────── */
.metric-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px;
  margin-bottom: 16px;
}

.metric-label {
  font-size: 12px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 500;
  margin-bottom: 6px;
}

.metric-value {
  font-size: 26px;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -.5px;
  font-family: 'DM Mono', monospace;
  line-height: 1;
  margin-bottom: 12px;
}

.metric-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.metric-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  font-weight: 500;
  padding: 3px 9px;
  border-radius: 20px;
}

.metric-badge.up   { background: var(--success-dim); color: var(--success); }
.metric-badge.down { background: var(--danger-dim);  color: var(--danger);  }
.metric-badge.info { background: var(--info-dim);    color: var(--info);    }
.metric-badge.warn { background: var(--warning-dim); color: var(--warning); }

.metric-sub {
  font-size: 11px;
  color: var(--text-muted);
  font-family: 'DM Mono', monospace;
}

/* ─── Accent top border variant ─────────────────────── */
.card-accent-top {
  border-top: 2px solid var(--accent);
}
.card-accent-top.success { border-top-color: var(--success); }
.card-accent-top.warning { border-top-color: var(--warning); }
.card-accent-top.danger  { border-top-color: var(--danger);  }

/* ─── Section header ────────────────────────────────── */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -.2px;
}

.section-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 2px;
}

.btn-ghost {
  background: var(--bg-elevated);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all .15s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'DM Sans', sans-serif;
}

.btn-ghost:hover {
  background: var(--bg-hover);
  border-color: var(--border-hover);
  color: var(--text-primary);
}

/* ─── Progress bars ─────────────────────────────────── */
.prog-wrap { margin-bottom: 14px; }

.prog-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 5px;
}

.prog-label span:last-child {
  font-family: 'DM Mono', monospace;
  color: var(--text-primary);
  font-weight: 500;
}

.prog-track {
  height: 5px;
  background: var(--bg-elevated);
  border-radius: 10px;
  overflow: hidden;
}

.prog-fill {
  height: 100%;
  border-radius: 10px;
  background: var(--accent);
  transition: width .6s ease;
}

.prog-fill.success { background: var(--success); }
.prog-fill.warning { background: var(--warning); }
.prog-fill.danger  { background: var(--danger);  }
.prog-fill.info    { background: var(--info);    }

/* ─── Stat divider row ───────────────────────────────── */
.stat-row {
  display: flex;
  border-top: 1px solid var(--border-color);
  margin-top: 18px;
  padding-top: 18px;
  gap: 0;
}

.stat-cell {
  flex: 1;
  text-align: center;
  padding: 0 8px;
}

.stat-cell + .stat-cell {
  border-left: 1px solid var(--border-color);
}

.stat-cell-label {
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .07em;
  margin-bottom: 4px;
}

.stat-cell-val {
  font-size: 17px;
  font-weight: 600;
  color: var(--text-primary);
  font-family: 'DM Mono', monospace;
}

/* ─── Table ─────────────────────────────────────────── */
.table-dark-custom {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.table-dark-custom thead th {
  padding: 10px 14px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
}

.table-dark-custom tbody td {
  padding: 13px 14px;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-secondary);
  vertical-align: middle;
}

.table-dark-custom tbody tr:last-child td {
  border-bottom: none;
}

.table-dark-custom tbody tr {
  transition: background .12s;
}

.table-dark-custom tbody tr:hover td {
  background: var(--bg-hover);
}

/* ─── Status pills ───────────────────────────────────── */
.pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
}

.pill::before {
  content: '';
  width: 5px; height: 5px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}

.pill.success { background: var(--success-dim); color: var(--success); }
.pill.warning { background: var(--warning-dim); color: var(--warning); }
.pill.danger  { background: var(--danger-dim);  color: var(--danger);  }
.pill.info    { background: var(--info-dim);     color: var(--info);   }
.pill.muted   { background: rgba(255,255,255,.06); color: var(--text-muted); }

/* ─── Avatar ─────────────────────────────────────────── */
.av {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.av-blue   { background: rgba(79,124,255,.18);  color: var(--accent);   }
.av-green  { background: rgba(34,201,142,.18);  color: var(--success);  }
.av-yellow { background: rgba(245,166,35,.18);  color: var(--warning);  }
.av-red    { background: rgba(255,92,106,.18);  color: var(--danger);   }
.av-cyan   { background: rgba(56,189,248,.18);  color: var(--info);     }
.av-purple { background: rgba(124,92,252,.18);  color: #7c5cfc;         }

/* ─── Online indicator ───────────────────────────────── */
.online-dot {
  display: inline-block;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 6px var(--success);
}

/* ─── Rank badge ─────────────────────────────────────── */
.rank {
  width: 26px; height: 26px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  font-family: 'DM Mono', monospace;
}

.rank-1 { background: rgba(245,166,35,.2);  color: var(--warning); }
.rank-2 { background: rgba(139,145,168,.15); color: var(--text-secondary); }
.rank-3 { background: rgba(205,127,50,.18); color: #cd7f32; }
.rank-n { background: transparent; color: var(--text-muted); }

/* ─── Sparkline mini bars ────────────────────────────── */
.spark-wrap {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 40px;
}

.spark-bar {
  flex: 1;
  background: var(--accent);
  opacity: .45;
  border-radius: 3px 3px 0 0;
  transition: opacity .2s;
}

.spark-bar:hover { opacity: 1; }

/* ─── Activity feed ──────────────────────────────────── */
.feed-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-color);
}

.feed-item:last-child { border-bottom: none; }

.feed-icon {
  width: 34px; height: 34px;
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
  margin-top: 1px;
}

.feed-title {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary);
  line-height: 1.4;
}

.feed-time {
  font-size: 11px;
  color: var(--text-muted);
  font-family: 'DM Mono', monospace;
  margin-top: 2px;
}

/* ─── Chart containers ───────────────────────────────── */
.chart-box {
  min-height: 240px;
}

/* ─── Animations ─────────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0);    }
}

.fade-up { animation: fadeUp .4s ease both; }
.delay-1 { animation-delay: .05s; }
.delay-2 { animation-delay: .10s; }
.delay-3 { animation-delay: .15s; }
.delay-4 { animation-delay: .20s; }
.delay-5 { animation-delay: .25s; }
.delay-6 { animation-delay: .30s; }

/* ─── Scrollbar ──────────────────────────────────────── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--bg-hover); border-radius: 10px; }

/* ─── Responsive ─────────────────────────────────────── */
@media (max-width: 991px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-wrap { margin-left: 0; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon">A</div>
    <div>
      <div class="brand-name">AdminPanel</div>
      <div class="brand-sub">v2.0 · dashboard</div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">

    <div class="nav-section-label">Asosiy</div>

    <a href="#" class="nav-item-link active">
      <i class="bi bi-grid-1x2"></i>
      Dashboard
      <span class="nav-badge">yangi</span>
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-bar-chart-line"></i>
      Analitika
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-receipt"></i>
      Buyurtmalar
      <span class="nav-badge success">12</span>
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-box-seam"></i>
      Mahsulotlar
    </a>

    <div class="nav-section-label" style="margin-top:16px">Boshqaruv</div>

    <a href="#" class="nav-item-link">
      <i class="bi bi-people"></i>
      Foydalanuvchilar
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-shield-check"></i>
      Rollar
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-bell"></i>
      Bildirishnomalar
    </a>

    <div class="nav-section-label" style="margin-top:16px">Tizim</div>

    <a href="#" class="nav-item-link">
      <i class="bi bi-gear"></i>
      Sozlamalar
    </a>

    <a href="#" class="nav-item-link">
      <i class="bi bi-question-circle"></i>
      Yordam
    </a>
  </nav>

  <!-- Footer user -->
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">AU</div>
      <div>
        <div class="user-name">Admin User</div>
        <div class="user-role">Super Admin</div>
      </div>
      <i class="bi bi-three-dots user-arrow"></i>
    </div>
  </div>
</aside>

<!-- ══════════════════════════════════════════════════════
     MAIN
══════════════════════════════════════════════════════ -->
<div class="main-wrap">

  <!-- Topbar -->
  <header class="topbar">
    <button class="topbar-action d-lg-none border-0" id="sidebarToggle">
      <i class="bi bi-list"></i>
    </button>

    <div>
      <div class="topbar-title">Dashboard</div>
      <div class="topbar-breadcrumb">Bosh sahifa / Dashboard</div>
    </div>

    <div class="topbar-search">
      <i class="bi bi-search"></i>
      <input type="text" placeholder="Qidirish..."/>
    </div>

    <a href="#" class="topbar-action">
      <i class="bi bi-bell"></i>
      <span class="notif-dot"></span>
    </a>

    <a href="#" class="topbar-action">
      <i class="bi bi-moon"></i>
    </a>

    <div class="user-avatar" style="width:34px;height:34px;font-size:13px;cursor:pointer">AU</div>
  </header>

  <!-- Page content -->
  <main class="page-content">

    <!-- Page header -->
    <div class="page-header fade-up">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h1 class="page-title">Umumiy ko'rinish</h1>
          <p class="page-subtitle">Bugun, 21 Mart 2026 — Barcha ko'rsatkichlar real vaqtda</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-ghost">
            <i class="bi bi-download"></i> Eksport
          </button>
          <button class="btn-ghost" style="background:var(--accent-dim);border-color:var(--accent);color:var(--accent)">
            <i class="bi bi-plus-lg"></i> Yangi
          </button>
        </div>
      </div>
    </div>

    <!-- ── ROW 1: 4 metric cards ──────────────────────── -->
    <div class="row g-3 mb-4">

      <!-- Daromad -->
      <div class="col-sm-6 col-xl-3 fade-up delay-1">
        <div class="card-dark card-accent-top success">
          <div class="metric-icon" style="background:var(--success-dim);color:var(--success)">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="metric-label">Jami daromad</div>
          <div class="metric-value">128.5M</div>
          <div class="metric-footer">
            <span class="metric-badge up"><i class="bi bi-arrow-up-right"></i> +12.4%</span>
            <span class="metric-sub">UZS</span>
          </div>
        </div>
      </div>

      <!-- Buyurtmalar -->
      <div class="col-sm-6 col-xl-3 fade-up delay-2">
        <div class="card-dark card-accent-top">
          <div class="metric-icon" style="background:var(--accent-dim);color:var(--accent)">
            <i class="bi bi-bag-check"></i>
          </div>
          <div class="metric-label">Buyurtmalar</div>
          <div class="metric-value">3,842</div>
          <div class="metric-footer">
            <span class="metric-badge info"><i class="bi bi-arrow-up-right"></i> +48 bugun</span>
            <span class="metric-sub">ta jami</span>
          </div>
        </div>
      </div>

      <!-- Foydalanuvchilar -->
      <div class="col-sm-6 col-xl-3 fade-up delay-3">
        <div class="card-dark card-accent-top warning">
          <div class="metric-icon" style="background:var(--warning-dim);color:var(--warning)">
            <i class="bi bi-people"></i>
          </div>
          <div class="metric-label">Foydalanuvchilar</div>
          <div class="metric-value">12,074</div>
          <div class="metric-footer">
            <span class="metric-badge warn"><i class="bi bi-circle-fill" style="font-size:6px"></i> 142 online</span>
            <span class="metric-sub">+8 bugun</span>
          </div>
        </div>
      </div>

      <!-- Konversiya -->
      <div class="col-sm-6 col-xl-3 fade-up delay-4">
        <div class="card-dark card-accent-top danger">
          <div class="metric-icon" style="background:var(--danger-dim);color:var(--danger)">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div class="metric-label">Yakunlanish</div>
          <div class="metric-value">64%</div>
          <div class="metric-footer">
            <span class="metric-badge up"><i class="bi bi-arrow-up-right"></i> +2.1%</span>
            <span class="metric-sub">completion</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ROW 2: Bar chart + Donut + Activity ────────── -->
    <div class="row g-3 mb-4">

      <!-- Oylik daromad chart -->
      <div class="col-xl-7 fade-up delay-2">
        <div class="card-dark h-100">
          <div class="section-header">
            <div>
              <div class="section-title">Oylik daromad</div>
              <div class="section-sub">Oxirgi 6 oy · UZS</div>
            </div>
            <button class="btn-ghost">
              <i class="bi bi-three-dots"></i>
            </button>
          </div>
          <div class="chart-box" id="chartRevenue"></div>
        </div>
      </div>

      <!-- Buyurtma statusi donut -->
      <div class="col-xl-5 fade-up delay-3">
        <div class="card-dark h-100">
          <div class="section-header">
            <div>
              <div class="section-title">Buyurtmalar holati</div>
              <div class="section-sub">Jami: 3,842 ta</div>
            </div>
          </div>
          <div id="chartDonut" style="min-height:200px"></div>
          <div class="stat-row">
            <div class="stat-cell">
              <div class="stat-cell-label">Yetkazildi</div>
              <div class="stat-cell-val" style="color:var(--success)">2,460</div>
            </div>
            <div class="stat-cell">
              <div class="stat-cell-label">Yo'lda</div>
              <div class="stat-cell-val" style="color:var(--info)">614</div>
            </div>
            <div class="stat-cell">
              <div class="stat-cell-label">Kutilmoqda</div>
              <div class="stat-cell-val" style="color:var(--warning)">500</div>
            </div>
            <div class="stat-cell">
              <div class="stat-cell-label">Bekor</div>
              <div class="stat-cell-val" style="color:var(--danger)">268</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ROW 3: Foydalanuvchilar + Online + Feed ────── -->
    <div class="row g-3 mb-4">

      <!-- Foydalanuvchi holatlar -->
      <div class="col-xl-4 fade-up delay-1">
        <div class="card-dark h-100">
          <div class="section-header">
            <div>
              <div class="section-title">Foydalanuvchilar</div>
              <div class="section-sub">Holat taqsimoti</div>
            </div>
          </div>

          <div class="prog-wrap">
            <div class="prog-label">
              <span>Online (5 daqiqa)</span>
              <span>142</span>
            </div>
            <div class="prog-track">
              <div class="prog-fill success" style="width:1.2%"></div>
            </div>
          </div>

          <div class="prog-wrap">
            <div class="prog-label">
              <span>Faol (FCM token)</span>
              <span>6,640</span>
            </div>
            <div class="prog-track">
              <div class="prog-fill" style="width:55%"></div>
            </div>
          </div>

          <div class="prog-wrap">
            <div class="prog-label">
              <span>Nofaol</span>
              <span>5,434</span>
            </div>
            <div class="prog-track">
              <div class="prog-fill info" style="width:45%"></div>
            </div>
          </div>

          <div class="prog-wrap">
            <div class="prog-label">
              <span>Isolat (30+ kun)</span>
              <span>2,415</span>
            </div>
            <div class="prog-track">
              <div class="prog-fill danger" style="width:20%"></div>
            </div>
          </div>

          <div class="prog-wrap">
            <div class="prog-label">
              <span>Bu hafta yangi</span>
              <span>384</span>
            </div>
            <div class="prog-track">
              <div class="prog-fill warning" style="width:3%"></div>
            </div>
          </div>

          <div class="stat-row" style="margin-top:14px;padding-top:14px">
            <div class="stat-cell">
              <div class="stat-cell-label">Jami</div>
              <div class="stat-cell-val">12,074</div>
            </div>
            <div class="stat-cell">
              <div class="stat-cell-label">Premium</div>
              <div class="stat-cell-val" style="color:var(--warning)">1,230</div>
            </div>
            <div class="stat-cell">
              <div class="stat-cell-label">Bugun</div>
              <div class="stat-cell-val" style="color:var(--accent)">+8</div>
            </div>
          </div>

          <!-- Sparkline -->
          <div style="margin-top:18px;border-top:1px solid var(--border-color);padding-top:16px">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px">7 kunlik buyurtmalar</div>
            <div class="spark-wrap">
              <div class="spark-bar" style="height:40%"></div>
              <div class="spark-bar" style="height:65%"></div>
              <div class="spark-bar" style="height:50%"></div>
              <div class="spark-bar" style="height:80%"></div>
              <div class="spark-bar" style="height:45%"></div>
              <div class="spark-bar" style="height:95%"></div>
              <div class="spark-bar" style="height:60%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Online foydalanuvchilar -->
      <div class="col-xl-4 fade-up delay-2">
        <div class="card-dark h-100">
          <div class="section-header">
            <div>
              <div class="section-title">Hozir online</div>
              <div class="section-sub">Oxirgi 5 daqiqa</div>
            </div>
            <span class="metric-badge up" style="font-size:11px">
              <span class="online-dot"></span> 142
            </span>
          </div>

          <div>
            <!-- user row -->
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color)">
              <div class="av av-blue">AS</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Abdulloh Sobirov</div>
                <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">1 daqiqa oldin</div>
              </div>
              <span class="online-dot"></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color)">
              <div class="av av-yellow">MR</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Malika Rahimova</div>
                <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">2 daqiqa oldin</div>
              </div>
              <span class="online-dot"></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color)">
              <div class="av av-green">JT</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Jasur Toshmatov</div>
                <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">3 daqiqa oldin</div>
              </div>
              <span class="online-dot"></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color)">
              <div class="av av-red">DY</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Dilnoza Yusupova</div>
                <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">4 daqiqa oldin</div>
              </div>
              <span class="online-dot"></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0">
              <div class="av av-purple">BN</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Bekzod Normatov</div>
                <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">5 daqiqa oldin</div>
              </div>
              <span class="online-dot"></span>
            </div>
          </div>

          <!-- Isolat banner -->
          <div style="margin-top:16px;padding:12px 14px;background:var(--danger-dim);border:1px solid rgba(255,92,106,.2);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:space-between;gap:10px">
            <div style="display:flex;align-items:center;gap:8px">
              <i class="bi bi-exclamation-triangle" style="color:var(--danger);font-size:14px;flex-shrink:0"></i>
              <span style="font-size:12px;color:var(--danger)">2,415 ta foydalanuvchi 30+ kun ko'rinmagan</span>
            </div>
            <a href="#" style="font-size:12px;font-weight:600;color:var(--danger);white-space:nowrap;text-decoration:none">Ko'rish →</a>
          </div>
        </div>
      </div>

      <!-- Activity feed -->
      <div class="col-xl-4 fade-up delay-3">
        <div class="card-dark h-100">
          <div class="section-header">
            <div>
              <div class="section-title">So'nggi faollik</div>
              <div class="section-sub">Tizim hodisalari</div>
            </div>
            <button class="btn-ghost">Barchasi</button>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--success-dim);color:var(--success)">
              <i class="bi bi-bag-check"></i>
            </div>
            <div>
              <div class="feed-title">Yangi buyurtma #1042 qabul qilindi</div>
              <div class="feed-time">2 daqiqa oldin · 125,000 UZS</div>
            </div>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--accent-dim);color:var(--accent)">
              <i class="bi bi-person-plus"></i>
            </div>
            <div>
              <div class="feed-title">Yangi foydalanuvchi ro'yxatdan o'tdi</div>
              <div class="feed-time">8 daqiqa oldin · Abdulloh S.</div>
            </div>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--warning-dim);color:var(--warning)">
              <i class="bi bi-exclamation-circle"></i>
            </div>
            <div>
              <div class="feed-title">Buyurtma #1038 bekor qilindi</div>
              <div class="feed-time">15 daqiqa oldin · 45,000 UZS</div>
            </div>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--success-dim);color:var(--success)">
              <i class="bi bi-truck"></i>
            </div>
            <div>
              <div class="feed-title">#1036 yetkazildi — Yakunlandi</div>
              <div class="feed-time">32 daqiqa oldin · Toshkent</div>
            </div>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--info-dim);color:var(--info)">
              <i class="bi bi-star"></i>
            </div>
            <div>
              <div class="feed-title">Yangi izoh qoldirildi — 5 yulduz</div>
              <div class="feed-time">1 soat oldin · "Atom odatlar"</div>
            </div>
          </div>

          <div class="feed-item">
            <div class="feed-icon" style="background:var(--danger-dim);color:var(--danger)">
              <i class="bi bi-shield-x"></i>
            </div>
            <div>
              <div class="feed-title">Muvaffaqiyatsiz kirish urinishi</div>
              <div class="feed-time">2 soat oldin · IP: 192.168.1.x</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ROW 4: Top kitoblar + Top mijozlar ────────── -->
    <div class="row g-3 mb-4">

      <!-- Top kitoblar -->
      <div class="col-xl-6 fade-up delay-1">
        <div class="card-dark">
          <div class="section-header">
            <div>
              <div class="section-title">Top sotuvchi kitoblar</div>
              <div class="section-sub">Eng ko'p sotilganlar</div>
            </div>
            <button class="btn-ghost">Barchasi</button>
          </div>
          <div class="table-responsive">
            <table class="table-dark-custom">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kitob</th>
                  <th>Sotildi</th>
                  <th style="text-align:right">Daromad</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="rank rank-1">1</span></td>
                  <td><span style="color:var(--text-primary);font-weight:500">Atom odatlar</span></td>
                  <td><span class="pill success">842 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">8,420,000 <small style="color:var(--text-muted)">UZS</small></td>
                </tr>
                <tr>
                  <td><span class="rank rank-2">2</span></td>
                  <td><span style="color:var(--text-primary);font-weight:500">Boylar o'ylaydigan tarzda</span></td>
                  <td><span class="pill success">631 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">6,310,000 <small style="color:var(--text-muted)">UZS</small></td>
                </tr>
                <tr>
                  <td><span class="rank rank-3">3</span></td>
                  <td><span style="color:var(--text-primary);font-weight:500">1984</span></td>
                  <td><span class="pill info">510 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">5,100,000 <small style="color:var(--text-muted)">UZS</small></td>
                </tr>
                <tr>
                  <td><span class="rank rank-n">4</span></td>
                  <td><span style="color:var(--text-primary);font-weight:500">Don Kixot</span></td>
                  <td><span class="pill muted">389 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">3,890,000 <small style="color:var(--text-muted)">UZS</small></td>
                </tr>
                <tr>
                  <td><span class="rank rank-n">5</span></td>
                  <td><span style="color:var(--text-primary);font-weight:500">Kichkina shahzoda</span></td>
                  <td><span class="pill muted">284 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">2,840,000 <small style="color:var(--text-muted)">UZS</small></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top mijozlar -->
      <div class="col-xl-6 fade-up delay-2">
        <div class="card-dark">
          <div class="section-header">
            <div>
              <div class="section-title">Top mijozlar</div>
              <div class="section-sub">Eng ko'p xarid qilganlar</div>
            </div>
            <button class="btn-ghost">Barchasi</button>
          </div>
          <div class="table-responsive">
            <table class="table-dark-custom">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Mijoz</th>
                  <th>Buyurtma</th>
                  <th style="text-align:right">Jami xarid</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="rank rank-1">1</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-blue">AS</div>
                      <span style="color:var(--text-primary);font-weight:500">A. Sobirov</span>
                    </div>
                  </td>
                  <td><span style="color:var(--text-secondary)">47 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:500;color:var(--success)">4,700,000</td>
                </tr>
                <tr>
                  <td><span class="rank rank-2">2</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-yellow">MR</div>
                      <span style="color:var(--text-primary);font-weight:500">M. Rahimova</span>
                    </div>
                  </td>
                  <td><span style="color:var(--text-secondary)">38 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:500;color:var(--success)">3,800,000</td>
                </tr>
                <tr>
                  <td><span class="rank rank-3">3</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-green">JT</div>
                      <span style="color:var(--text-primary);font-weight:500">J. Toshmatov</span>
                    </div>
                  </td>
                  <td><span style="color:var(--text-secondary)">29 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:500;color:var(--success)">2,900,000</td>
                </tr>
                <tr>
                  <td><span class="rank rank-n">4</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-red">DY</div>
                      <span style="color:var(--text-primary);font-weight:500">D. Yusupova</span>
                    </div>
                  </td>
                  <td><span style="color:var(--text-secondary)">21 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:500;color:var(--success)">2,100,000</td>
                </tr>
                <tr>
                  <td><span class="rank rank-n">5</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-purple">BN</div>
                      <span style="color:var(--text-primary);font-weight:500">B. Normatov</span>
                    </div>
                  </td>
                  <td><span style="color:var(--text-secondary)">18 ta</span></td>
                  <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:500;color:var(--success)">1,800,000</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ROW 5: So'nggi buyurtmalar (full width) ────── -->
    <div class="row g-3 fade-up delay-1">
      <div class="col-12">
        <div class="card-dark">
          <div class="section-header">
            <div>
              <div class="section-title">So'nggi buyurtmalar</div>
              <div class="section-sub">Bugun: <strong style="color:var(--text-primary)">12</strong> ta yangi buyurtma</div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn-ghost"><i class="bi bi-funnel"></i> Filter</button>
              <button class="btn-ghost">Barchasi <i class="bi bi-arrow-right"></i></button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table-dark-custom" style="min-width:680px">
              <thead>
                <tr>
                  <th>#ID</th>
                  <th>Mijoz</th>
                  <th>Summa</th>
                  <th>Mahsulotlar</th>
                  <th>Status</th>
                  <th>Sana</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span style="font-family:'DM Mono',monospace;color:var(--accent);font-weight:500">#1042</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-blue">AS</div>
                      <span style="color:var(--text-primary);font-weight:500">Abdulloh Sobirov</span>
                    </div>
                  </td>
                  <td><span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--text-primary)">125,000 UZS</span></td>
                  <td><span style="color:var(--text-muted)">2 ta</span></td>
                  <td><span class="pill success">Yetkazildi</span></td>
                  <td><span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">21.03 14:30</span></td>
                  <td><button class="btn-ghost" style="padding:4px 10px;font-size:11px">Ko'rish</button></td>
                </tr>
                <tr>
                  <td><span style="font-family:'DM Mono',monospace;color:var(--accent);font-weight:500">#1041</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-yellow">MR</div>
                      <span style="color:var(--text-primary);font-weight:500">Malika Rahimova</span>
                    </div>
                  </td>
                  <td><span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--text-primary)">89,000 UZS</span></td>
                  <td><span style="color:var(--text-muted)">1 ta</span></td>
                  <td><span class="pill warning">Kutilmoqda</span></td>
                  <td><span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">21.03 13:15</span></td>
                  <td><button class="btn-ghost" style="padding:4px 10px;font-size:11px">Ko'rish</button></td>
                </tr>
                <tr>
                  <td><span style="font-family:'DM Mono',monospace;color:var(--accent);font-weight:500">#1040</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-green">JT</div>
                      <span style="color:var(--text-primary);font-weight:500">Jasur Toshmatov</span>
                    </div>
                  </td>
                  <td><span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--text-primary)">210,000 UZS</span></td>
                  <td><span style="color:var(--text-muted)">3 ta</span></td>
                  <td><span class="pill info">Yo'lda</span></td>
                  <td><span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">21.03 11:40</span></td>
                  <td><button class="btn-ghost" style="padding:4px 10px;font-size:11px">Ko'rish</button></td>
                </tr>
                <tr>
                  <td><span style="font-family:'DM Mono',monospace;color:var(--accent);font-weight:500">#1039</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-red">DY</div>
                      <span style="color:var(--text-primary);font-weight:500">Dilnoza Yusupova</span>
                    </div>
                  </td>
                  <td><span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--text-primary)">45,000 UZS</span></td>
                  <td><span style="color:var(--text-muted)">1 ta</span></td>
                  <td><span class="pill danger">Bekor qilindi</span></td>
                  <td><span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">21.03 10:05</span></td>
                  <td><button class="btn-ghost" style="padding:4px 10px;font-size:11px">Ko'rish</button></td>
                </tr>
                <tr>
                  <td><span style="font-family:'DM Mono',monospace;color:var(--accent);font-weight:500">#1038</span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="av av-purple">BN</div>
                      <span style="color:var(--text-primary);font-weight:500">Bekzod Normatov</span>
                    </div>
                  </td>
                  <td><span style="font-family:'DM Mono',monospace;font-weight:500;color:var(--text-primary)">178,000 UZS</span></td>
                  <td><span style="color:var(--text-muted)">2 ta</span></td>
                  <td><span class="pill success">Yetkazildi</span></td>
                  <td><span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">21.03 09:20</span></td>
                  <td><button class="btn-ghost" style="padding:4px 10px;font-size:11px">Ko'rish</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

<!-- ApexCharts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.js"></script>

<script>
// ── Sidebar toggle (mobile) ──────────────────────────
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
});

const C = {
  grid:    'rgba(255,255,255,0.05)',
  label:   '#555c75',
  accent:  '#4f7cff',
  success: '#22c98e',
  warning: '#f5a623',
  danger:  '#ff5c6a',
  info:    '#38bdf8',
};

// ── Oylik daromad bar chart ──────────────────────────
new ApexCharts(document.querySelector('#chartRevenue'), {
  series: [{ name: 'Daromad', data: [42, 78, 55, 91, 63, 128] }],
  chart: {
    type: 'bar',
    height: 240,
    toolbar: { show: false },
    fontFamily: 'DM Sans, sans-serif',
    background: 'transparent',
  },
  colors: [C.accent],
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '44%',
      distributed: false,
    }
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: ['Okt', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
    axisBorder: { show: false },
    axisTicks:  { show: false },
    labels: { style: { colors: C.label, fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      style: { colors: C.label, fontSize: '11px' },
      formatter: v => v + 'M',
    }
  },
  grid: {
    borderColor: C.grid,
    strokeDashArray: 5,
    xaxis: { lines: { show: false } },
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: v => v + ' mln UZS' },
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      type: 'vertical',
      shadeIntensity: 0.3,
      gradientToColors: ['#2650cc'],
      stops: [0, 100],
    }
  },
}).render();

// ── Buyurtmalar donut ────────────────────────────────
new ApexCharts(document.querySelector('#chartDonut'), {
  series: [2460, 614, 500, 268],
  labels: ['Yetkazildi', "Yo'lda", 'Kutilmoqda', 'Bekor'],
  colors: [C.success, C.info, C.warning, C.danger],
  chart: {
    type: 'donut',
    height: 200,
    toolbar: { show: false },
    fontFamily: 'DM Sans, sans-serif',
    background: 'transparent',
  },
  legend: {
    position: 'bottom',
    fontSize: '12px',
    labels: { colors: C.label },
    markers: { width: 8, height: 8, radius: 4 },
    itemMargin: { horizontal: 10 },
  },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '74%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Jami',
            fontSize: '12px',
            color: C.label,
            formatter: () => '3,842',
          },
          value: {
            fontSize: '20px',
            fontWeight: 600,
            color: '#eef0f7',
            fontFamily: 'DM Mono, monospace',
          }
        }
      }
    }
  },
  stroke: { width: 2, colors: ['#181c27'] },
  tooltip: {
    theme: 'dark',
    y: { formatter: v => v.toLocaleString() + ' ta' },
  },
}).render();
</script>
</body>
</html><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/pages/dashboard/ecommerce.blade.php ENDPATH**/ ?>