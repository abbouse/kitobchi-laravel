<!DOCTYPE html>
<html lang="uz" data-bs-theme="{{ session('theme', 'dark') }}">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', 'Admin') — kitobchi.</title>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.css" rel="stylesheet"/>

<style>
/* ══ CSS Variables ══════════════════════════════════════════ */
[data-bs-theme="dark"] {
  --p-bg:        #0c0e14;
  --p-surface:   #13161f;
  --p-elevated:  #1a1e2c;
  --p-hover:     #20253a;
  --p-border:    rgba(255,255,255,0.07);
  --p-border2:   rgba(255,255,255,0.13);
  --p-text:      #e8eaf4;
  --p-muted:     #8b91a8;
  --p-hint:      #4e5470;
  --p-accent:    #5b87ff;
  --p-accent-d:  rgba(91,135,255,0.13);
  --p-success:   #20c997;
  --p-success-d: rgba(32,201,151,0.12);
  --p-warning:   #f5a623;
  --p-warning-d: rgba(245,166,35,0.12);
  --p-danger:    #ff5370;
  --p-danger-d:  rgba(255,83,112,0.12);
  --p-info:      #38bdf8;
  --p-info-d:    rgba(56,189,248,0.12);
  --p-shadow:    0 8px 32px rgba(0,0,0,0.5);
  --p-shadow-sm: 0 2px 12px rgba(0,0,0,0.3);
  --topbar-blur: rgba(12,14,20,0.85);
}
[data-bs-theme="light"] {
  --p-bg:        #f0f2f8;
  --p-surface:   #ffffff;
  --p-elevated:  #f5f7fc;
  --p-hover:     #eaecf4;
  --p-border:    rgba(0,0,0,0.07);
  --p-border2:   rgba(0,0,0,0.14);
  --p-text:      #151929;
  --p-muted:     #6b7280;
  --p-hint:      #a0a8bf;
  --p-accent:    #4f7cff;
  --p-accent-d:  rgba(79,124,255,0.10);
  --p-success:   #059669;
  --p-success-d: rgba(5,150,105,0.10);
  --p-warning:   #d97706;
  --p-warning-d: rgba(217,119,6,0.10);
  --p-danger:    #e0284f;
  --p-danger-d:  rgba(224,40,79,0.10);
  --p-info:      #0284c7;
  --p-info-d:    rgba(2,132,199,0.10);
  --p-shadow:    0 4px 24px rgba(0,0,0,0.08);
  --p-shadow-sm: 0 1px 8px rgba(0,0,0,0.06);
  --topbar-blur: rgba(255,255,255,0.9);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--p-bg);
  color: var(--p-text);
  min-height: 100vh;
  display: flex;
  font-size: 14px;
  -webkit-font-smoothing: antialiased;
}

/* ══ SIDEBAR ════════════════════════════════════════════════ */
#sidebar {
  width: 260px;
  min-height: 100vh;
  background: var(--p-surface);
  border-right: 1px solid var(--p-border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 1000;
  transition: width .25s cubic-bezier(.4,0,.2,1), transform .25s cubic-bezier(.4,0,.2,1);
  overflow: hidden;
  overflow-y: hidden;
}
#sidebar.mini { width: 72px; }

.sidebar-brand {
  padding: 18px 16px;
  border-bottom: 1px solid var(--p-border);
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  min-height: 64px;
  transition: padding .25s;
}
#sidebar.mini .sidebar-brand { justify-content: center; padding: 18px 10px; }

.brand-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #5b87ff, #7c5cfc);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; color: #fff;
  box-shadow: 0 4px 14px rgba(91,135,255,.4);
  flex-shrink: 0;
  transition: transform .2s;
}
.brand-icon:hover { transform: scale(1.05); }
.brand-name  { font-size: 14px; font-weight: 700; color: var(--p-text); letter-spacing: -.2px; white-space: nowrap; }
.brand-badge { font-size: 10px; color: var(--p-hint); font-family: 'JetBrains Mono', monospace; white-space: nowrap; margin-top: 1px; }
.brand-text  { transition: opacity .2s; }
#sidebar.mini .brand-text { opacity: 0; width: 0; overflow: hidden; }

.sidebar-nav {
  flex: 1;
  padding: 10px 8px;
  overflow-y: auto;
  min-height: 0;
  scrollbar-width: thin;
  scrollbar-color: var(--p-hover) transparent;
}

.nav-section {
  font-size: 9.5px;
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--p-hint);
  padding: 14px 10px 5px;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity .2s, height .2s;
}
#sidebar.mini .nav-section { opacity: 0; height: 4px; padding: 0; }

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 9px;
  color: var(--p-muted);
  font-size: 13.5px;
  font-weight: 400;
  text-decoration: none;
  transition: all .15s;
  margin-bottom: 1px;
  position: relative;
  white-space: nowrap;
}
.nav-link:hover { background: var(--p-hover); color: var(--p-text); }
.nav-link.active {
  background: var(--p-accent-d);
  color: var(--p-accent);
  font-weight: 500;
}
.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0; top: 22%; bottom: 22%;
  width: 3px;
  background: var(--p-accent);
  border-radius: 0 3px 3px 0;
}
.nav-link i { font-size: 15px; width: 18px; text-align: center; flex-shrink: 0; }
.nav-link-text { flex: 1; overflow: hidden; transition: opacity .2s, width .25s; }
#sidebar.mini .nav-link { justify-content: center; padding: 9px 0; }
#sidebar.mini .nav-link-text { opacity: 0; width: 0; }
#sidebar.mini .nav-link i { font-size: 18px; width: 20px; }
#sidebar.mini .nav-link.active::before { display: none; }

.nav-badge {
  font-size: 10px;
  font-weight: 700;
  font-family: 'JetBrains Mono', monospace;
  padding: 1px 6px;
  border-radius: 20px;
  background: var(--p-accent-d);
  color: var(--p-accent);
  flex-shrink: 0;
  transition: opacity .2s;
}
#sidebar.mini .nav-badge { opacity: 0; width: 0; overflow: hidden; padding: 0; }
.nav-badge.success { background: var(--p-success-d); color: var(--p-success); }
.nav-badge.warning { background: var(--p-warning-d); color: var(--p-warning); }
.nav-badge.danger  { background: var(--p-danger-d);  color: var(--p-danger); }
.nav-badge.info    { background: var(--p-info-d);     color: var(--p-info); }

/* Sidebar footer */
.sidebar-footer {
  padding: 10px 8px;
  border-top: 1px solid var(--p-border);
  flex-shrink: 0;
  position: relative;
}
.user-pill {
  display: flex; align-items: center; gap: 9px;
  padding: 8px 8px;
  border-radius: 9px;
  cursor: pointer;
  transition: background .15s;
  user-select: none;
}
.user-pill:hover { background: var(--p-hover); }
#sidebar.mini .user-pill { justify-content: center; }

.user-av {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, #5b87ff, #7c5cfc);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff;
  flex-shrink: 0; overflow: hidden; letter-spacing: 0;
}
.user-av img { width:100%; height:100%; object-fit:cover; }

.user-info { flex: 1; min-width: 0; transition: opacity .2s; }
#sidebar.mini .user-info { opacity: 0; width: 0; overflow: hidden; }
.user-name { font-size: 13px; font-weight: 600; color: var(--p-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role { font-size: 10px; color: var(--p-hint); margin-top: 1px; font-family: 'JetBrains Mono', monospace; }

.theme-btn {
  width: 26px; height: 26px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  color: var(--p-muted); font-size: 12px;
  cursor: pointer; transition: all .15s; flex-shrink: 0;
}
.theme-btn:hover { background: var(--p-hover); color: var(--p-text); border-color: var(--p-border2); }
#sidebar.mini .theme-btn { display: none; }

/* User popup */
.user-popup {
  display: none;
  position: absolute;
  bottom: calc(100% + 6px);
  left: 8px; right: 8px;
  background: var(--p-surface);
  border: 1px solid var(--p-border2);
  border-radius: 12px;
  padding: 6px;
  box-shadow: var(--p-shadow);
  z-index: 2000;
  animation: fadeUp .15s ease;
}
.user-popup.open { display: block; }
.user-popup-head {
  padding: 10px 10px 12px;
  border-bottom: 1px solid var(--p-border);
  margin-bottom: 4px;
}
.user-popup-head .name { font-size: 13px; font-weight: 600; color: var(--p-text); }
.user-popup-head .role { font-size: 11px; color: var(--p-hint); margin-top: 2px; }
.user-popup a, .user-popup button {
  display: flex; align-items: center; gap: 9px;
  width: 100%; padding: 9px 10px;
  border-radius: 8px;
  font-size: 13px; font-weight: 400;
  color: var(--p-text); text-decoration: none;
  background: none; border: none; cursor: pointer;
  transition: background .12s;
  font-family: 'Inter', sans-serif;
}
.user-popup a:hover, .user-popup button:hover { background: var(--p-hover); }
.user-popup .pop-divider { height: 1px; background: var(--p-border); margin: 4px 0; }

/* ══ MAIN WRAP ══════════════════════════════════════════════ */
#main-wrap {
  margin-left: 260px;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left .25s cubic-bezier(.4,0,.2,1);
}
#main-wrap.mini-shift { margin-left: 72px; }

/* ══ TOPBAR ═════════════════════════════════════════════════ */
#topbar {
  height: 64px;
  background: var(--topbar-blur);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--p-border);
  display: flex;
  align-items: center;
  padding: 0 20px;
  position: sticky;
  top: 0;
  z-index: 900;
  gap: 10px;
  flex-shrink: 0;
}

.topbar-toggle {
  width: 38px; height: 38px;
  display: flex; align-items: center; justify-content: center;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 9px;
  color: var(--p-muted);
  font-size: 18px;
  cursor: pointer;
  transition: all .15s;
  flex-shrink: 0;
  line-height: 1;
}
.topbar-toggle:hover {
  background: var(--p-hover);
  border-color: var(--p-border2);
  color: var(--p-text);
}

.topbar-page-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--p-text);
  letter-spacing: -.2px;
}
.topbar-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--p-hint);
  margin-top: 1px;
}
.topbar-breadcrumb span { color: var(--p-muted); }
.topbar-breadcrumb .current { color: var(--p-text); font-weight: 500; }

.topbar-sep { flex: 1; }

.topbar-end {
  display: flex;
  align-items: center;
  gap: 6px;
}

.topbar-icon-btn {
  width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 9px;
  color: var(--p-muted);
  font-size: 15px;
  cursor: pointer;
  text-decoration: none;
  transition: all .15s;
  flex-shrink: 0;
}
.topbar-icon-btn:hover {
  background: var(--p-hover);
  border-color: var(--p-border2);
  color: var(--p-text);
}

.topbar-divider {
  width: 1px;
  height: 24px;
  background: var(--p-border);
  margin: 0 2px;
}

.topbar-user-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 24px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  cursor: pointer;
  transition: all .15s;
  text-decoration: none;
}
.topbar-user-chip:hover {
  background: var(--p-hover);
  border-color: var(--p-border2);
}
.topbar-user-chip .av-sm {
  width: 28px; height: 28px; border-radius: 50%;
  background: linear-gradient(135deg, #5b87ff, #7c5cfc);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; color: #fff;
  overflow: hidden; flex-shrink: 0;
}
.topbar-user-chip .av-sm img { width:100%; height:100%; object-fit:cover; }
.topbar-user-chip .chip-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--p-text);
  white-space: nowrap;
}
@media (max-width: 575px) {
  .topbar-user-chip .chip-name { display: none; }
}

/* ══ PAGE BODY ══════════════════════════════════════════════ */
.page-body { padding: 24px; flex: 1; }

.page-header { margin-bottom: 22px; }
.page-title  { font-size: 20px; font-weight: 700; color: var(--p-text); letter-spacing: -.4px; }
.page-sub    { font-size: 12.5px; color: var(--p-muted); margin-top: 3px; }

/* ══ CARD ═══════════════════════════════════════════════════ */
.p-card {
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 14px;
  padding: 20px;
  transition: border-color .2s, box-shadow .2s;
}
.p-card:hover { border-color: var(--p-border2); box-shadow: var(--p-shadow-sm); }
.p-card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px;
}
.p-card-title { font-size: 14px; font-weight: 600; color: var(--p-text); }
.p-card-sub   { font-size: 12px; color: var(--p-hint); margin-top: 2px; }

/* ══ METRIC CARD ════════════════════════════════════════════ */
.metric-card {
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 14px;
  padding: 20px;
  border-top-width: 2px;
  transition: border-color .2s, transform .2s, box-shadow .2s;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: var(--p-shadow-sm); }
.metric-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; margin-bottom: 14px;
}
.metric-label { font-size: 11px; color: var(--p-hint); text-transform: uppercase; letter-spacing: .08em; font-weight: 600; }
.metric-value { font-size: 26px; font-weight: 700; color: var(--p-text); font-family: 'JetBrains Mono', monospace; letter-spacing: -.5px; margin: 5px 0 10px; line-height: 1; }

/* ══ STATUS PILL ════════════════════════════════════════════ */
.s-pill {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11.5px; font-weight: 500;
  padding: 3px 10px; border-radius: 20px;
}
.s-pill::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
.s-pill.success { background: var(--p-success-d); color: var(--p-success); }
.s-pill.warning { background: var(--p-warning-d); color: var(--p-warning); }
.s-pill.danger  { background: var(--p-danger-d);  color: var(--p-danger);  }
.s-pill.info    { background: var(--p-info-d);     color: var(--p-info);   }
.s-pill.muted   { background: var(--p-elevated);   color: var(--p-muted);  }
.s-pill.accent  { background: var(--p-accent-d);   color: var(--p-accent); }

/* ══ TABLE ══════════════════════════════════════════════════ */
.p-table { width:100%; border-collapse:collapse; font-size:13px; }
.p-table thead th {
  padding: 10px 14px;
  font-size: 10.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .08em;
  color: var(--p-hint);
  border-bottom: 1px solid var(--p-border);
  white-space: nowrap;
  background: var(--p-surface);
}
.p-table tbody td {
  padding: 12px 14px;
  border-bottom: 1px solid var(--p-border);
  color: var(--p-muted);
  vertical-align: middle;
}
.p-table tbody tr:last-child td { border-bottom: none; }
.p-table tbody tr { transition: background .12s; }
.p-table tbody tr:hover td { background: var(--p-hover); }

/* ══ AVATAR ═════════════════════════════════════════════════ */
.av { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; overflow:hidden; }
.av img { width:100%; height:100%; object-fit:cover; }
.av-blue   { background:rgba(91,135,255,.15); color:var(--p-accent); }
.av-green  { background:rgba(32,201,151,.15); color:var(--p-success); }
.av-yellow { background:rgba(245,166,35,.15); color:var(--p-warning); }
.av-red    { background:rgba(255,83,112,.15); color:var(--p-danger); }
.av-purple { background:rgba(124,92,252,.15); color:#7c5cfc; }

/* ══ BUTTONS ════════════════════════════════════════════════ */
.btn-p {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 9px;
  font-size: 13px; font-weight: 500;
  font-family: 'Inter', sans-serif;
  cursor: pointer; border: none; transition: all .15s;
  text-decoration: none; white-space: nowrap;
}
.btn-p.primary { background: var(--p-accent); color: #fff; box-shadow: 0 2px 10px rgba(91,135,255,.3); }
.btn-p.primary:hover { background: #6b94ff; color: #fff; box-shadow: 0 4px 16px rgba(91,135,255,.4); }
.btn-p.ghost   { background: var(--p-elevated); border: 1px solid var(--p-border); color: var(--p-muted); }
.btn-p.ghost:hover { background: var(--p-hover); color: var(--p-text); border-color: var(--p-border2); }
.btn-p.danger  { background: var(--p-danger-d); border: 1px solid transparent; color: var(--p-danger); }
.btn-p.danger:hover { background: var(--p-danger); color: #fff; }
.btn-p.success { background: var(--p-success-d); border: 1px solid transparent; color: var(--p-success); }
.btn-p.success:hover { background: var(--p-success); color: #fff; }
.btn-p.warning { background: var(--p-warning-d); border: 1px solid transparent; color: var(--p-warning); }
.btn-p.warning:hover { background: var(--p-warning); color: #fff; }
.btn-p.sm { padding: 5px 10px; font-size: 12px; border-radius: 7px; }
.btn-p.icon { padding: 7px; }

/* ══ FORM ═══════════════════════════════════════════════════ */
.p-form-label { font-size: 12px; font-weight: 500; color: var(--p-muted); margin-bottom: 5px; display: block; }
.p-form-control {
  width: 100%;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 9px;
  padding: 9px 13px;
  font-size: 13px;
  color: var(--p-text);
  font-family: 'Inter', sans-serif;
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
.p-form-control:focus { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-accent-d); }
.p-form-control::placeholder { color: var(--p-hint); }
.p-form-control option { background: var(--p-surface); }
textarea.p-form-control { resize: vertical; }
.border-danger { border-color: var(--p-danger) !important; }

/* ══ SEARCH BOX ═════════════════════════════════════════════ */
.search-box {
  display: flex; align-items: center; gap: 8px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 9px;
  padding: 7px 12px;
  transition: border-color .2s, box-shadow .2s;
}
.search-box:focus-within { border-color: var(--p-accent); box-shadow: 0 0 0 3px var(--p-accent-d); }
.search-box input {
  background: none; border: none; outline: none;
  color: var(--p-text); font-size: 13px;
  font-family: 'Inter', sans-serif; width: 100%;
}
.search-box input::placeholder { color: var(--p-hint); }
.search-box i { color: var(--p-hint); font-size: 13px; flex-shrink: 0; }

/* ══ PAGINATION ═════════════════════════════════════════════ */
.p-pagination { display: flex; align-items: center; gap: 4px; margin-top: 16px; flex-wrap: wrap; }
.p-page-btn {
  min-width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px;
  border: 1px solid var(--p-border);
  background: var(--p-elevated);
  color: var(--p-muted);
  font-size: 12.5px;
  cursor: pointer;
  text-decoration: none;
  transition: all .15s;
  padding: 0 8px;
}
.p-page-btn:hover { background: var(--p-hover); color: var(--p-text); border-color: var(--p-border2); }
.p-page-btn.active { background: var(--p-accent); border-color: var(--p-accent); color: #fff; box-shadow: 0 2px 8px rgba(91,135,255,.3); }
.p-page-btn.disabled { opacity: .35; cursor: default; pointer-events: none; }

/* ══ ALERT ══════════════════════════════════════════════════ */
.p-alert {
  padding: 12px 16px; border-radius: 11px;
  font-size: 13px; display: flex; align-items: center; gap: 10px;
  margin-bottom: 18px;
}
.p-alert.success { background: var(--p-success-d); color: var(--p-success); border: 1px solid rgba(32,201,151,.2); }
.p-alert.danger  { background: var(--p-danger-d);  color: var(--p-danger);  border: 1px solid rgba(255,83,112,.2); }
.p-alert.warning { background: var(--p-warning-d); color: var(--p-warning); border: 1px solid rgba(245,166,35,.2); }

/* ══ FILTER BAR ═════════════════════════════════════════════ */
.filter-bar {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  padding: 12px 14px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 11px;
  margin-bottom: 14px;
}

/* ══ TAB PILLS ══════════════════════════════════════════════ */
.tab-pills { display: flex; gap: 2px; border-bottom: 1px solid var(--p-border); margin-bottom: 18px; }
.tab-pill {
  padding: 9px 15px;
  font-size: 13px; font-weight: 500;
  color: var(--p-muted);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all .15s;
  text-decoration: none;
  border-radius: 6px 6px 0 0;
}
.tab-pill:hover { color: var(--p-text); background: var(--p-hover); }
.tab-pill.active { color: var(--p-accent); border-bottom-color: var(--p-accent); }
.tab-count, .tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px;
  background: var(--p-elevated);
  border-radius: 10px;
  font-size: 10px; font-weight: 700;
  font-family: 'JetBrains Mono', monospace;
  padding: 0 5px;
  margin-left: 5px;
}

/* ══ RANK ═══════════════════════════════════════════════════ */
.rank { width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; font-family:'JetBrains Mono',monospace; }
.rank-1 { background:rgba(245,166,35,.2); color:var(--p-warning); }
.rank-2 { background:rgba(139,145,168,.12); color:var(--p-muted); }
.rank-3 { background:rgba(205,127,50,.18); color:#cd7f32; }

/* ══ MOBILE BACKDROP ════════════════════════════════════════ */
.sidebar-backdrop {
  position: fixed; top:0; left:0; width:100%; height:100%;
  background: rgba(0,0,0,.55);
  z-index: 999;
  backdrop-filter: blur(2px);
}

/* ══ SCROLLBAR ══════════════════════════════════════════════ */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--p-hover); border-radius:10px; }

/* ══ RESPONSIVE ═════════════════════════════════════════════ */
@media (max-width: 991px) {
  #sidebar { transform: translateX(-100%); width: 260px !important; }
  #sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,.4); }
  #main-wrap { margin-left: 0 !important; }
  .page-body { padding: 16px; }
}
@media (max-width: 575px) {
  .page-body { padding: 12px; }
  #topbar { padding: 0 14px; }
}

/* ══ ANIMATIONS ═════════════════════════════════════════════ */
@keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
.fade-up { animation: fadeUp .3s ease both; }
.fade-in { animation: fadeIn .25s ease both; }
.d1 { animation-delay:.05s; } .d2 { animation-delay:.1s; }
.d3 { animation-delay:.15s; } .d4 { animation-delay:.2s; }
</style>

@stack('styles')
</head>
<body>

{{-- Sidebar backdrop (mobile) --}}
<div class="sidebar-backdrop d-none" id="sidebarBackdrop"></div>

{{-- ══ SIDEBAR ════════════════════════════════════════════ --}}
<aside id="sidebar">
  @include('panel.partials.sidebar')
</aside>

{{-- ══ MAIN WRAP ══════════════════════════════════════════ --}}
<div id="main-wrap">

  {{-- ── TOPBAR ─────────────────────────────────────────── --}}
  <header id="topbar">
    <button class="topbar-toggle" id="sidebarToggle" aria-label="Sidebar toggle">
      <i class="bi bi-list"></i>
    </button>

    <div class="topbar-divider d-none d-lg-block"></div>

    <div class="d-none d-sm-block">
      <div class="topbar-page-title">@yield('title', 'Dashboard')</div>
    </div>

    <div class="topbar-sep"></div>

    <div class="topbar-end">

      @yield('topbar-actions')

      {{-- Refresh --}}
      @hasSection('topbar-refresh')
        @yield('topbar-refresh')
      @else
        <a href="{{ request()->fullUrl() }}" class="topbar-icon-btn" title="Yangilash">
          <i class="bi bi-arrow-clockwise"></i>
        </a>
      @endif

      {{-- Theme toggle --}}
      <form method="POST" action="{{ route('panel.theme') }}" style="margin:0">
        @csrf
        <input type="hidden" name="theme" value="{{ session('theme','dark') === 'dark' ? 'light' : 'dark' }}">
        <button type="submit" class="topbar-icon-btn"
                title="{{ session('theme','dark') === 'dark' ? 'Light rejim' : 'Dark rejim' }}">
          <i class="bi bi-{{ session('theme','dark') === 'dark' ? 'sun' : 'moon-stars' }}"></i>
        </button>
      </form>

      <div class="topbar-divider"></div>

      {{-- User chip --}}
      @php $me = auth('panel')->user(); @endphp
      <div class="topbar-user-chip" id="topbarUserBtn">
        <div class="av-sm">
          @if($me?->avatar)
            <img src="{{ asset('storage/'.$me->avatar) }}" alt="{{ $me->name }}">
          @else
            {{ strtoupper(substr($me?->name ?? 'A', 0, 1)) }}
          @endif
        </div>
        <span class="chip-name">{{ $me?->name ?? 'Admin' }}</span>
        <i class="bi bi-chevron-down" style="font-size:10px;color:var(--p-hint);margin-right:2px"></i>
      </div>
    </div>
  </header>

  {{-- ── PAGE BODY ───────────────────────────────────────── --}}
  <main class="page-body">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-alert success fade-up">
      <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="p-alert danger fade-up">
      <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
    </div>
    @endif
    @if(session('warning'))
    <div class="p-alert warning fade-up">
      <i class="bi bi-exclamation-triangle-fill"></i> {{ session('warning') }}
    </div>
    @endif

    @yield('content')
  </main>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.js"></script>

<script>
// ── Sidebar toggle ─────────────────────────────────────────
const sidebar   = document.getElementById('sidebar');
const mainWrap  = document.getElementById('main-wrap');
const backdrop  = document.getElementById('sidebarBackdrop');
const toggleBtn = document.getElementById('sidebarToggle');

const isMobile = () => window.innerWidth < 992;

// Restore desktop mini state
if (!isMobile() && localStorage.getItem('sidebarMini') === '1') {
  sidebar.classList.add('mini');
  mainWrap.classList.add('mini-shift');
}

toggleBtn?.addEventListener('click', () => {
  if (isMobile()) {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('d-none');
  } else {
    const isMini = sidebar.classList.toggle('mini');
    mainWrap.classList.toggle('mini-shift', isMini);
    localStorage.setItem('sidebarMini', isMini ? '1' : '0');
  }
});

backdrop?.addEventListener('click', () => {
  sidebar.classList.remove('open');
  backdrop.classList.add('d-none');
});

window.addEventListener('resize', () => {
  if (!isMobile()) {
    sidebar.classList.remove('open');
    backdrop.classList.add('d-none');
    const mini = localStorage.getItem('sidebarMini') === '1';
    sidebar.classList.toggle('mini', mini);
    mainWrap.classList.toggle('mini-shift', mini);
  }
});

// ── User popup (sidebar footer) ────────────────────────────
const userPill  = document.getElementById('userPill');
const userPopup = document.getElementById('userPopup');

userPill?.addEventListener('click', (e) => {
  e.stopPropagation();
  userPopup?.classList.toggle('open');
});
document.addEventListener('click', () => userPopup?.classList.remove('open'));
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') userPopup?.classList.remove('open');
});

// ── Topbar user chip → sidebar footer popup ────────────────
document.getElementById('topbarUserBtn')?.addEventListener('click', (e) => {
  e.stopPropagation();
  if (isMobile()) {
    sidebar.classList.add('open');
    backdrop.classList.remove('d-none');
  } else {
    userPill?.click();
  }
});

// ── Auto-hide flash alerts ──────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.p-alert').forEach(el => {
    el.style.transition = 'opacity .4s, transform .4s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-4px)';
    setTimeout(() => el.remove(), 400);
  });
}, 4000);
</script>

@stack('scripts')
</body>
</html>
