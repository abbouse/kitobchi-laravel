<!DOCTYPE html>
<html lang="uz" data-bs-theme="<?php echo e(session('theme', 'dark')); ?>">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>"/>
<title><?php echo $__env->yieldContent('title', 'Admin'); ?> — kitobchi.</title>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.css" rel="stylesheet"/>

<style>
/* ── CSS Variables ─────────────────────────────── */
[data-bs-theme="dark"] {
  --p-bg:        #0f1117;
  --p-surface:   #181c27;
  --p-elevated:  #1e2336;
  --p-hover:     #252b3d;
  --p-border:    rgba(255,255,255,0.07);
  --p-border2:   rgba(255,255,255,0.13);
  --p-text:      #eef0f7;
  --p-muted:     #8b91a8;
  --p-hint:      #555c75;
  --p-accent:    #4f7cff;
  --p-accent-d:  rgba(79,124,255,0.12);
  --p-success:   #22c98e;
  --p-success-d: rgba(34,201,142,0.12);
  --p-warning:   #f5a623;
  --p-warning-d: rgba(245,166,35,0.12);
  --p-danger:    #ff5c6a;
  --p-danger-d:  rgba(255,92,106,0.12);
  --p-info:      #38bdf8;
  --p-info-d:    rgba(56,189,248,0.12);
  --p-shadow:    0 4px 24px rgba(0,0,0,0.4);
}
[data-bs-theme="light"] {
  --p-bg:        #f4f6fb;
  --p-surface:   #ffffff;
  --p-elevated:  #f8f9fc;
  --p-hover:     #f0f2f8;
  --p-border:    rgba(0,0,0,0.08);
  --p-border2:   rgba(0,0,0,0.15);
  --p-text:      #1a1d2e;
  --p-muted:     #6b7280;
  --p-hint:      #9ca3af;
  --p-accent:    #4f7cff;
  --p-accent-d:  rgba(79,124,255,0.10);
  --p-success:   #16a34a;
  --p-success-d: rgba(22,163,74,0.10);
  --p-warning:   #d97706;
  --p-warning-d: rgba(217,119,6,0.10);
  --p-danger:    #dc2626;
  --p-danger-d:  rgba(220,38,38,0.10);
  --p-info:      #0284c7;
  --p-info-d:    rgba(2,132,199,0.10);
  --p-shadow:    0 2px 16px rgba(0,0,0,0.08);
}

*, *::before, *::after { box-sizing: border-box; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--p-bg);
  color: var(--p-text);
  min-height: 100vh;
  display: flex;
}

/* ── Sidebar ───────────────────────────────────── */
#sidebar {
  width: 260px;
  height: 100vh;
  background: var(--p-surface);
  border-right: 1px solid var(--p-border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 1000;
  transition: transform .3s ease;
  overflow-y: hidden;
}

.sidebar-brand {
  padding: 20px 16px;
  border-bottom: 1px solid var(--p-border);
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.brand-icon {
  width: 36px; height: 36px;
  background: var(--p-accent);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; color: #fff;
  box-shadow: 0 0 16px rgba(79,124,255,0.35);
  flex-shrink: 0;
}

.brand-name  { font-size: 14px; font-weight: 600; color: var(--p-text); }
.brand-badge { font-size: 10px; color: var(--p-hint); font-family: 'DM Mono',monospace; }

.sidebar-nav {
  flex: 1;
  padding: 12px 10px;
  overflow-y: auto;
  min-height: 0;
  scrollbar-width: thin;
  scrollbar-color: var(--p-hover) transparent;
}

.nav-section {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--p-hint);
  padding: 12px 10px 5px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 11px;
  border-radius: 8px;
  color: var(--p-muted);
  font-size: 13.5px;
  font-weight: 400;
  text-decoration: none;
  transition: all .15s;
  margin-bottom: 1px;
  position: relative;
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
  left: 0; top: 20%; bottom: 20%;
  width: 3px;
  background: var(--p-accent);
  border-radius: 0 3px 3px 0;
}

.nav-link i { font-size: 15px; width: 17px; text-align: center; flex-shrink: 0; }

.nav-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 600;
  font-family: 'DM Mono',monospace;
  padding: 1px 7px;
  border-radius: 20px;
  background: var(--p-accent-d);
  color: var(--p-accent);
}

.nav-badge.success { background: var(--p-success-d); color: var(--p-success); }
.nav-badge.warning { background: var(--p-warning-d); color: var(--p-warning); }
.nav-badge.danger  { background: var(--p-danger-d);  color: var(--p-danger);  }
.nav-badge.info    { background: var(--p-info-d);     color: var(--p-info);   }

/* ── Sidebar footer ──────────────────────────────── */
.sidebar-footer {
  padding: 10px;
  border-top: 1px solid var(--p-border);
  flex-shrink: 0;
  position: relative;
}

.user-pill {
  display: flex; align-items: center; gap: 9px;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background .15s;
  user-select: none;
  position: relative;
}
.user-pill:hover { background: var(--p-hover); }

.user-av {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--p-accent), #7c5cfc);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 600; color: #fff;
  flex-shrink: 0; overflow: hidden;
}
.user-av img { width:100%; height:100%; object-fit:cover; }

.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 13px; font-weight: 500; color: var(--p-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role { font-size: 10px; color: var(--p-hint); margin-top: 1px; }

/* Theme button — footer o'ng tomoni */
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

/* User popup — yuqoriga ochiladi */
.user-popup {
  display: none;
  position: absolute;
  bottom: calc(100% + 8px);
  left: 0; right: 0;
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 12px;
  padding: 6px;
  box-shadow: 0 -6px 24px rgba(0,0,0,.18);
  z-index: 2000;
  animation: fadeUp .15s ease;
}
.user-popup.open { display: block; }

.user-popup-head {
  padding: 10px 12px 12px;
  border-bottom: 1px solid var(--p-border);
  margin-bottom: 4px;
}
.user-popup-head .name { font-size: 13px; font-weight: 600; color: var(--p-text); }
.user-popup-head .role { font-size: 11px; color: var(--p-hint); margin-top: 1px; }

.user-popup a,
.user-popup button {
  display: flex; align-items: center; gap: 9px;
  width: 100%; padding: 9px 10px;
  border-radius: 8px;
  font-size: 13px; font-weight: 400;
  color: var(--p-text); text-decoration: none;
  background: none; border: none; cursor: pointer;
  transition: background .12s;
  font-family: 'DM Sans', sans-serif;
}
.user-popup a:hover,
.user-popup button:hover { background: var(--p-hover); }
.user-popup .pop-divider { height: 1px; background: var(--p-border); margin: 4px 0; }

/* ── Main wrap ───────────────────────────────────── */
#main-wrap {
  margin-left: 260px;
  flex: 1; display: flex; flex-direction: column;
  min-height: 100vh;
}

/* ── Search box (filter-bar ichida) ─────────────── */
.search-box {
  display: flex; align-items: center; gap: 8px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 8px;
  padding: 6px 12px;
  transition: border-color .2s;
}
.search-box:focus-within { border-color: var(--p-accent); }
.search-box input {
  background: none; border: none; outline: none;
  color: var(--p-text); font-size: 13px;
  font-family: 'DM Sans',sans-serif; width: 100%;
}
.search-box input::placeholder { color: var(--p-hint); }
.search-box i { color: var(--p-hint); font-size: 13px; flex-shrink: 0; }

/* ── Page ──────────────────────────────────────── */
.page-body { padding: 20px; flex: 1; }

.page-header { margin-bottom: 20px; }
.page-title  { font-size: 20px; font-weight: 600; color: var(--p-text); letter-spacing: -.3px; margin: 0; }
.page-sub    { font-size: 12px; color: var(--p-muted); margin-top: 2px; }

/* ── Card ──────────────────────────────────────── */
.p-card {
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 12px;
  padding: 18px;
  transition: border-color .2s;
}
.p-card:hover { border-color: var(--p-border2); }
.p-card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.p-card-title { font-size: 14px; font-weight: 600; color: var(--p-text); }
.p-card-sub   { font-size: 12px; color: var(--p-hint); margin-top: 1px; }

/* ── Metric card ───────────────────────────────── */
.metric-card {
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 12px;
  padding: 18px;
  border-top-width: 2px;
}
.metric-icon {
  width: 40px; height: 40px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  margin-bottom: 14px;
}
.metric-label { font-size: 11px; color: var(--p-hint); text-transform: uppercase; letter-spacing: .06em; font-weight: 500; }
.metric-value { font-size: 24px; font-weight: 600; color: var(--p-text); font-family: 'DM Mono',monospace; letter-spacing: -.5px; margin: 4px 0 10px; line-height: 1; }

/* ── Status pill ───────────────────────────────── */
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

/* ── Table ─────────────────────────────────────── */
.p-table { width:100%; border-collapse:collapse; font-size:13px; }
.p-table thead th {
  padding: 9px 12px;
  font-size: 11px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .07em;
  color: var(--p-hint);
  border-bottom: 1px solid var(--p-border);
  white-space: nowrap;
  background: var(--p-surface);
}
.p-table tbody td {
  padding: 12px 12px;
  border-bottom: 1px solid var(--p-border);
  color: var(--p-muted);
  vertical-align: middle;
}
.p-table tbody tr:last-child td { border-bottom: none; }
.p-table tbody tr { transition: background .12s; }
.p-table tbody tr:hover td { background: var(--p-hover); }

/* ── Avatar ────────────────────────────────────── */
.av { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; flex-shrink:0; overflow:hidden; }
.av img { width:100%; height:100%; object-fit:cover; }
.av-blue   { background:rgba(79,124,255,.15); color:var(--p-accent); }
.av-green  { background:rgba(34,201,142,.15); color:var(--p-success); }
.av-yellow { background:rgba(245,166,35,.15); color:var(--p-warning); }
.av-red    { background:rgba(255,92,106,.15); color:var(--p-danger); }
.av-purple { background:rgba(124,92,252,.15); color:#7c5cfc; }

/* ── Buttons ───────────────────────────────────── */
.btn-p {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 8px;
  font-size: 13px; font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer; border: none; transition: all .15s;
  text-decoration: none;
}
.btn-p.primary { background: var(--p-accent); color: #fff; }
.btn-p.primary:hover { background: #6690ff; color: #fff; }
.btn-p.ghost   { background: var(--p-elevated); border: 1px solid var(--p-border); color: var(--p-muted); }
.btn-p.ghost:hover { background: var(--p-hover); color: var(--p-text); border-color: var(--p-border2); }
.btn-p.danger  { background: var(--p-danger-d); border: 1px solid var(--p-danger-d); color: var(--p-danger); }
.btn-p.danger:hover { background: var(--p-danger); color: #fff; }
.btn-p.success { background: var(--p-success-d); border: 1px solid var(--p-success-d); color: var(--p-success); }
.btn-p.success:hover { background: var(--p-success); color: #fff; }
.btn-p.warning { background: var(--p-warning-d); border: 1px solid var(--p-warning-d); color: var(--p-warning); }
.btn-p.warning:hover { background: var(--p-warning); color: #fff; }
.btn-p.sm { padding: 4px 10px; font-size: 12px; }
.btn-p.icon { padding: 7px; }

/* ── Form ──────────────────────────────────────── */
.p-form-label { font-size: 12px; font-weight: 500; color: var(--p-muted); margin-bottom: 5px; display: block; }
.p-form-control {
  width: 100%;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: var(--p-text);
  font-family: 'DM Sans', sans-serif;
  transition: border-color .2s;
  outline: none;
}
.p-form-control:focus { border-color: var(--p-accent); }
.p-form-control::placeholder { color: var(--p-hint); }
.p-form-control option { background: var(--p-surface); }
textarea.p-form-control { resize: vertical; }
.border-danger { border-color: var(--p-danger) !important; }

/* ── Pagination ────────────────────────────────── */
.p-pagination { display: flex; align-items: center; gap: 4px; margin-top: 16px; }
.p-page-btn {
  min-width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 7px;
  border: 1px solid var(--p-border);
  background: var(--p-elevated);
  color: var(--p-muted);
  font-size: 12px;
  cursor: pointer;
  text-decoration: none;
  transition: all .15s;
  padding: 0 8px;
}
.p-page-btn:hover { background: var(--p-hover); color: var(--p-text); }
.p-page-btn.active { background: var(--p-accent); border-color: var(--p-accent); color: #fff; }
.p-page-btn.disabled { opacity: .4; cursor: default; pointer-events: none; }

/* ── Alert ─────────────────────────────────────── */
.p-alert {
  padding: 12px 16px; border-radius: 10px;
  font-size: 13px; display: flex; align-items: center; gap: 10px;
  margin-bottom: 16px;
}
.p-alert.success { background: var(--p-success-d); color: var(--p-success); border: 1px solid rgba(34,201,142,.2); }
.p-alert.danger  { background: var(--p-danger-d);  color: var(--p-danger);  border: 1px solid rgba(255,92,106,.2); }
.p-alert.warning { background: var(--p-warning-d); color: var(--p-warning); border: 1px solid rgba(245,166,35,.2); }

/* ── Filter bar ────────────────────────────────── */
.filter-bar {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  padding: 12px 14px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-radius: 10px;
  margin-bottom: 14px;
}

/* filter-bar ichidagi search-box o'ng tomonga ketmasin */
.filter-bar .search-box { margin-left: 0; }

/* ── Tab pills ─────────────────────────────────── */
.tab-pills { display: flex; gap: 4px; border-bottom: 1px solid var(--p-border); margin-bottom: 16px; }
.tab-pill {
  padding: 8px 14px;
  font-size: 13px; font-weight: 500;
  color: var(--p-muted);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all .15s;
  text-decoration: none;
}
.tab-pill:hover { color: var(--p-text); }
.tab-pill.active { color: var(--p-accent); border-bottom-color: var(--p-accent); }
.tab-count, .tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px;
  background: var(--p-elevated);
  border-radius: 10px;
  font-size: 10px; font-weight: 700;
  font-family: 'DM Mono',monospace;
  padding: 0 5px;
  margin-left: 5px;
}

/* ── Rank ──────────────────────────────────────── */
.rank { width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; font-family:'DM Mono',monospace; }
.rank-1 { background:rgba(245,166,35,.2); color:var(--p-warning); }
.rank-2 { background:rgba(139,145,168,.12); color:var(--p-muted); }
.rank-3 { background:rgba(205,127,50,.18); color:#cd7f32; }

/* ── Scrollbar ─────────────────────────────────── */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--p-hover); border-radius:10px; }

/* ── Responsive ────────────────────────────────── */
@media (max-width: 991px) {
  #sidebar { transform: translateX(-100%); }
  #sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.3); }
  #main-wrap { margin-left: 0; }
  .sidebar-backdrop { display: block !important; }
}

/* ── Animations ────────────────────────────────── */
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.fade-up { animation: fadeUp .3s ease both; }
.d1 { animation-delay:.04s; } .d2 { animation-delay:.08s; }
.d3 { animation-delay:.12s; } .d4 { animation-delay:.16s; }
</style>

<?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>


<div class="sidebar-backdrop d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:999" id="sidebarBackdrop"></div>


<aside id="sidebar">
  <?php echo $__env->make('panel.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</aside>


<div id="main-wrap">

  
  <main class="page-body">

    
    <?php if(session('success')): ?>
    <div class="p-alert success fade-up">
      <i class="bi bi-check-circle-fill"></i> <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
    <div class="p-alert danger fade-up">
      <i class="bi bi-x-circle-fill"></i> <?php echo e(session('error')); ?>

    </div>
    <?php endif; ?>
    <?php if(session('warning')): ?>
    <div class="p-alert warning fade-up">
      <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e(session('warning')); ?>

    </div>
    <?php endif; ?>

    <?php echo $__env->yieldContent('content'); ?>
  </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.49.0/apexcharts.min.js"></script>

<script>
// Mobil: sidebar toggle (hamburger tugmasi sidebar.blade.php da qoladi)
const sidebar  = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');

document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  backdrop.classList.toggle('d-none');
});
backdrop?.addEventListener('click', () => {
  sidebar.classList.remove('open');
  backdrop.classList.add('d-none');
});

// User popup (sidebar footer)
const userPill  = document.getElementById('userPill');
const userPopup = document.getElementById('userPopup');

userPill?.addEventListener('click', (e) => {
  e.stopPropagation();
  userPopup.classList.toggle('open');
});

document.addEventListener('click', (e) => {
  if (userPopup && !userPopup.contains(e.target) && !userPill.contains(e.target)) {
    userPopup.classList.remove('open');
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') userPopup?.classList.remove('open');
});

// Auto-hide alerts
setTimeout(() => {
  document.querySelectorAll('.p-alert').forEach(el => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  });
}, 4000);
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/layouts/panel.blade.php ENDPATH**/ ?>