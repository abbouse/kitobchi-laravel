<?php $__env->startSection('title', 'Live Command Center'); ?>

<?php $__env->startPush('styles'); ?>
<style>
  .fs-live {
    min-height: 100vh;
    background:
      radial-gradient(circle at 12% 0%, rgba(168, 85, 247, .16), transparent 28%),
      radial-gradient(circle at 86% 12%, rgba(59, 130, 246, .14), transparent 26%),
      #0f172a;
    color: #e2e8f0;
  }

  .fs-live__shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 20px;
  }

  .fs-live__topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }

  .fs-live__brand {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
  }

  .fs-live__mark {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f46e5, #ec4899);
    color: #fff;
    font-size: 24px;
    box-shadow: 0 0 20px rgba(236,72,153,.35);
  }

  .fs-live__title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .fs-live__title {
    margin: 0;
    color: #f8fafc;
    font-size: clamp(1.35rem, 2vw, 2rem);
    font-weight: 900;
    letter-spacing: 0;
  }

  .fs-live__subtitle {
    margin-top: 4px;
    color: #94a3b8;
    font-size: 13px;
  }

  .fs-live__controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
  }

  .fs-live .card-panel {
    border: 1px solid #334155 !important;
    border-radius: 14px !important;
    background: #1e293b !important;
    color: #e2e8f0;
    box-shadow: none !important;
  }

  .fs-live__metric {
    min-height: 150px;
    border-left: 4px solid var(--metric-color, #a855f7) !important;
    background: linear-gradient(180deg, rgba(30,41,59,.72), rgba(15,23,42,.72)) !important;
  }

  .fs-live__metric-label {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  .fs-live__metric-value {
    margin-top: 8px;
    font-size: clamp(1.55rem, 2.6vw, 2.35rem);
    font-weight: 900;
    line-height: 1;
    letter-spacing: 0;
    color: #f8fafc;
  }

  .fs-live__metric-sub {
    margin-top: 8px;
    color: #6ee7b7;
    font-size: 12px;
  }

  .fs-live__panel-title {
    color: #f8fafc;
    font-size: 16px;
    font-weight: 800;
  }

  .fs-live__panel-sub {
    color: #94a3b8;
    font-size: 12px;
  }

  .fs-live__map {
    position: relative;
    width: 100%;
    min-height: 245px;
    margin: 10px 0;
  }

  .fs-live__regions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,.06);
  }

  .fs-live__region {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #cbd5e1;
    font-size: 11px;
  }

  .fs-live__region-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--region-color);
  }

  .fs-live__sparkline {
    width: 100%;
    height: 235px;
    display: block;
  }

  .fs-live__feed {
    flex: 1;
    max-height: 320px;
    overflow: auto;
    padding-right: 4px;
  }

  .fs-live__feed-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
  }

  .fs-live__feed-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #475569;
    flex: 0 0 auto;
  }

  .fs-live__feed-row:first-child .fs-live__feed-dot {
    background: #10b981;
    box-shadow: 0 0 0 5px rgba(16,185,129,.12);
  }

  .fs-live__feed-main {
    flex: 1;
    min-width: 0;
  }

  .fs-live__feed-title {
    overflow: hidden;
    color: #e2e8f0;
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .fs-live__feed-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    color: #94a3b8;
    font-size: 10px;
  }

  .fs-live__feed-amount {
    color: #6ee7b7;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
  }

  .fs-live__mini-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 10px;
    background: rgba(255,255,255,.025);
  }

  .fs-live__rank {
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 7px;
    background: #334155;
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 900;
  }

  .fs-live__rank.is-top {
    background: #fbbf24;
    color: #fff;
  }

  @media (max-width: 900px) {
    .fs-live__shell { padding: 14px; }
    .fs-live__topbar { align-items: flex-start; flex-direction: column; }
    .fs-live__controls { justify-content: flex-start; }
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
  $snapshotPayload = [
    'generated_at' => $snapshot['generated_at'] ?? now()->format('H:i:s'),
    'online_users_count' => $snapshot['online_users_count'] ?? 0,
    'main_counts' => $snapshot['main_counts'] ?? [],
    'seller_counts' => $snapshot['seller_counts'] ?? [],
    'courier_counts' => $snapshot['courier_counts'] ?? [],
    'recent_orders' => $snapshot['recent_orders'] ?? [],
    'recent_seller_orders' => $snapshot['recent_seller_orders'] ?? [],
    'recent_courier_orders' => $snapshot['recent_courier_orders'] ?? [],
  ];
?>

<div class="fs-live" data-live-dashboard data-endpoint="<?php echo e(route('admin.dashboard.live.data')); ?>">
  <div class="fs-live__shell">
    <div class="fs-live__topbar">
      <div class="fs-live__brand">
        <div class="fs-live__mark"><i class="bi bi-broadcast"></i></div>
        <div>
          <div class="fs-live__title-row">
            <h1 class="fs-live__title">Live Command Center</h1>
            <span class="chip chip-success" data-live-status><span class="live-pulse"></span>System active</span>
          </div>
          <div class="fs-live__subtitle">Kitobchi real vaqt monitoringi · buyurtma, seller, kuryer va online mijozlar</div>
        </div>
      </div>

      <div class="fs-live__controls">
        <div class="btn-group btn-group-sm" role="group" aria-label="Polling speed">
          <button type="button" class="btn btn-primary" data-live-speed="8000">x1</button>
          <button type="button" class="btn btn-outline-light" data-live-speed="4000">x2</button>
          <button type="button" class="btn btn-outline-light" data-live-speed="1800">x5</button>
        </div>
        <button type="button" class="btn btn-outline-light btn-sm" data-live-pause>
          <i class="bi bi-pause-fill me-1"></i><span>Pauza</span>
        </button>
        <div class="px-3 py-1 rounded border border-secondary-subtle text-light small">
          <i class="bi bi-clock text-primary me-1"></i><span data-live-clock><?php echo e(now()->format('H:i:s')); ?></span>
        </div>
        <button type="button" class="btn btn-outline-light btn-sm" data-live-fullscreen>
          <i class="bi bi-arrows-fullscreen me-1"></i>Fullscreen
        </button>
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-primary-gradient btn-sm">
          <i class="bi bi-house-door me-1"></i>Asosiy panel
        </a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-xl-3 col-md-6">
        <div class="card-panel fs-live__metric" style="--metric-color:#a855f7">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fs-live__metric-label">Jami buyurtmalar</div>
            <i class="bi bi-bag-check" style="color:#a855f7;font-size:20px"></i>
          </div>
          <div class="fs-live__metric-value" data-live-main-total>0</div>
          <div class="fs-live__metric-sub"><span data-live-main-active>0</span> ta aktiv oqim</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card-panel fs-live__metric" style="--metric-color:#10b981">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fs-live__metric-label">Seller orderlar</div>
            <i class="bi bi-shop-window" style="color:#10b981;font-size:20px"></i>
          </div>
          <div class="fs-live__metric-value" data-live-seller-total>0</div>
          <div class="fs-live__metric-sub"><span data-live-seller-active>0</span> ta bajarilmoqda</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card-panel fs-live__metric" style="--metric-color:#3b82f6">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fs-live__metric-label">Kuryer oqimi</div>
            <i class="bi bi-truck" style="color:#3b82f6;font-size:20px"></i>
          </div>
          <div class="fs-live__metric-value" data-live-courier-total>0</div>
          <div class="fs-live__metric-sub"><span data-live-courier-active>0</span> ta yo‘lda</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card-panel fs-live__metric" style="--metric-color:#ec4899">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fs-live__metric-label">Online mijozlar</div>
            <i class="bi bi-people" style="color:#ec4899;font-size:20px"></i>
          </div>
          <div class="fs-live__metric-value" data-live-online>0</div>
          <div class="fs-live__metric-sub">So‘nggi 5 daqiqa</div>
        </div>
      </div>
    </div>

    <div class="row g-3 flex-fill">
      <div class="col-xl-4 d-flex">
        <div class="card-panel live-map-grid flex-fill d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fs-live__panel-title">O‘zbekiston savdo xaritasi</div>
              <div class="fs-live__panel-sub">Hududlar bo‘yicha real oqim signallari</div>
            </div>
            <span class="chip chip-purple">Dynamic</span>
          </div>
          <div class="fs-live__map">
            <svg viewBox="0 0 100 100" width="100%" height="100%" data-live-map>
              <path d="M 15 45 Q 30 25 60 30 T 95 35 Q 90 55 75 60 T 40 75 Q 20 65 15 45 Z" fill="rgba(79,70,229,.08)" stroke="rgba(255,255,255,.14)" stroke-width=".5" />
            </svg>
          </div>
          <div class="fs-live__regions" data-live-regions></div>
        </div>
      </div>

      <div class="col-xl-5 d-flex">
        <div class="card-panel flex-fill d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <div class="fs-live__panel-title">Savdo faolligi</div>
              <div class="fs-live__panel-sub">Oxirgi snapshotlar intensivligi</div>
            </div>
            <span class="chip chip-success"><span data-live-velocity>0</span> / min</span>
          </div>
          <svg class="fs-live__sparkline" viewBox="0 0 520 220" preserveAspectRatio="none" data-live-chart>
            <defs>
              <linearGradient id="liveArea" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#ec4899" stop-opacity=".52" />
                <stop offset="100%" stop-color="#4f46e5" stop-opacity="0" />
              </linearGradient>
            </defs>
            <path data-live-area fill="url(#liveArea)" d=""></path>
            <path data-live-line fill="none" stroke="#ec4899" stroke-width="3" d=""></path>
          </svg>
          <div class="d-flex justify-content-between pt-2 border-top border-secondary-subtle small text-secondary">
            <span>Oldingi</span>
            <span data-live-generated><?php echo e($snapshotPayload['generated_at']); ?></span>
            <span>Hozir</span>
          </div>
        </div>
      </div>

      <div class="col-xl-3 d-flex">
        <div class="card-panel flex-fill d-flex flex-column">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fs-live__panel-title">Jonli oqim</div>
            <span class="live-pulse"></span>
          </div>
          <div class="fs-live__feed" data-live-feed></div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-xl-6">
        <div class="card-panel h-100">
          <div class="fs-live__panel-title mb-3">Operatsion segmentlar</div>
          <div class="row g-2" data-live-segments></div>
        </div>
      </div>
      <div class="col-xl-6">
        <div class="card-panel h-100">
          <div class="fs-live__panel-title mb-3">Tizim statusi</div>
          <div class="row g-2" data-live-health></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
  const root = document.querySelector('[data-live-dashboard]');
  if (!root) return;

  let snapshot = <?php echo json_encode($snapshotPayload, 15, 512) ?>;
  let paused = false;
  let speed = 8000;
  let timer = null;
  let chart = Array.from({ length: 28 }, (_, i) => 25 + Math.sin(i / 2) * 8 + Math.random() * 10);

  const regions = [
    { name: 'Toshkent', value: 34, color: '#a855f7', x: 72, y: 35 },
    { name: 'Samarqand', value: 22, color: '#6366f1', x: 50, y: 55 },
    { name: 'Buxoro', value: 18, color: '#3b82f6', x: 35, y: 60 },
    { name: 'Farg‘ona', value: 15, color: '#10b981', x: 85, y: 45 },
    { name: 'Andijon', value: 12, color: '#f59e0b', x: 92, y: 40 },
    { name: 'Namangan', value: 14, color: '#ec4899', x: 82, y: 32 },
    { name: 'Xorazm', value: 10, color: '#06b6d4', x: 20, y: 45 },
    { name: 'Qashqadaryo', value: 16, color: '#f43f5e', x: 45, y: 70 },
  ];

  const fmt = (value) => new Intl.NumberFormat('uz-UZ').format(Number(value || 0));
  const el = (selector) => root.querySelector(selector);
  const count = (path, fallback = 0) => path.split('.').reduce((acc, key) => acc?.[key], snapshot) ?? fallback;

  function setText(selector, value) {
    const node = el(selector);
    if (node) node.textContent = value;
  }

  function activeMain() {
    return count('main_counts.new') + count('main_counts.packing') + count('main_counts.onway');
  }

  function activeSeller() {
    return count('seller_counts.payment_pending') + count('seller_counts.new') + count('seller_counts.accepted') + count('seller_counts.handover');
  }

  function activeCourier() {
    return count('courier_counts.pending') + count('courier_counts.in_delivery');
  }

  function feedRows() {
    const rows = [];
    (snapshot.recent_orders || []).forEach((order) => rows.push({
      title: `${order.customer} buyurtma #${order.id}`,
      meta: order.status,
      amount: order.amount,
      time: order.updated_at,
    }));
    (snapshot.recent_seller_orders || []).forEach((order) => rows.push({
      title: `${order.seller} seller order #${order.id}`,
      meta: order.status,
      amount: order.amount,
      time: order.updated_at,
    }));
    (snapshot.recent_courier_orders || []).forEach((order) => rows.push({
      title: `${order.courier} kuryer order #${order.id}`,
      meta: order.status,
      amount: order.amount,
      time: order.updated_at,
    }));
    return rows.slice(0, 12);
  }

  function renderMetrics() {
    setText('[data-live-main-total]', fmt(count('main_counts.all')));
    setText('[data-live-main-active]', fmt(activeMain()));
    setText('[data-live-seller-total]', fmt(count('seller_counts.all')));
    setText('[data-live-seller-active]', fmt(activeSeller()));
    setText('[data-live-courier-total]', fmt(count('courier_counts.all')));
    setText('[data-live-courier-active]', fmt(activeCourier()));
    setText('[data-live-online]', fmt(snapshot.online_users_count || 0));
    setText('[data-live-generated]', snapshot.generated_at || new Date().toLocaleTimeString('uz-UZ'));
    setText('[data-live-velocity]', fmt(Math.max(1, Math.round((activeMain() + activeSeller() + activeCourier()) / 3))));
  }

  function renderFeed() {
    const feed = el('[data-live-feed]');
    if (!feed) return;
    const rows = feedRows();
    if (!rows.length) {
      feed.innerHTML = '<div class="text-center py-4 text-secondary"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><br>Oqim kutilmoqda</div>';
      return;
    }
    feed.innerHTML = rows.map((row) => `
      <div class="fs-live__feed-row">
        <span class="fs-live__feed-dot"></span>
        <div class="fs-live__feed-main">
          <div class="fs-live__feed-title">${escapeHtml(row.title)}</div>
          <div class="fs-live__feed-meta"><span>${escapeHtml(row.meta || '—')}</span><span>${escapeHtml(row.time || '—')}</span></div>
        </div>
        <div class="fs-live__feed-amount">+${escapeHtml(row.amount || '0')}</div>
      </div>
    `).join('');
  }

  function renderMap() {
    const map = el('[data-live-map]');
    const list = el('[data-live-regions]');
    if (!map || !list) return;
    const pulseIndex = Math.floor(Math.random() * regions.length);
    map.querySelectorAll('[data-region-node]').forEach((node) => node.remove());
    regions.forEach((region, index) => {
      region.value += index === pulseIndex ? 1 : 0;
      const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      group.setAttribute('data-region-node', 'true');
      group.innerHTML = `
        <line x1="60" y1="45" x2="${region.x}" y2="${region.y}" stroke="rgba(255,255,255,.06)" stroke-width=".5" stroke-dasharray="1 1"></line>
        ${index === pulseIndex ? `<circle cx="${region.x}" cy="${region.y}" r="9" fill="none" stroke="${region.color}" stroke-width="1" opacity=".85"><animate attributeName="r" from="3" to="15" dur="1s" repeatCount="1"></animate><animate attributeName="opacity" from="1" to="0" dur="1s" repeatCount="1"></animate></circle>` : ''}
        <circle cx="${region.x}" cy="${region.y}" r="${index === pulseIndex ? 5 : 3.5}" fill="${region.color}"></circle>
        <text x="${region.x}" y="${region.y - 6}" fill="${index === pulseIndex ? '#fff' : '#94a3b8'}" font-size="6" text-anchor="middle" font-weight="${index === pulseIndex ? '700' : '400'}">${region.name}</text>
      `;
      map.appendChild(group);
    });
    list.innerHTML = regions.map((region) => `
      <span class="fs-live__region"><span class="fs-live__region-dot" style="--region-color:${region.color}"></span>${region.name}: <strong class="text-light">${region.value}</strong></span>
    `).join('');
  }

  function renderChart() {
    const line = el('[data-live-line]');
    const area = el('[data-live-area]');
    if (!line || !area) return;
    const value = activeMain() + activeSeller() + activeCourier() + (snapshot.online_users_count || 0);
    chart = [...chart.slice(1), Math.max(10, value + Math.random() * 18)];
    const max = Math.max(...chart);
    const min = Math.min(...chart);
    const points = chart.map((v, i) => {
      const x = (i / (chart.length - 1)) * 520;
      const y = 200 - ((v - min) / Math.max(max - min, 1)) * 170;
      return [x, y];
    });
    const d = points.map(([x, y], i) => `${i ? 'L' : 'M'} ${x.toFixed(1)} ${y.toFixed(1)}`).join(' ');
    line.setAttribute('d', d);
    area.setAttribute('d', `${d} L 520 220 L 0 220 Z`);
  }

  function renderSegments() {
    const node = el('[data-live-segments]');
    if (!node) return;
    const segments = [
      ['Yangi order', count('main_counts.new'), '#a855f7'],
      ['Qadoqlash', count('main_counts.packing'), '#3b82f6'],
      ['Yo‘lda', count('main_counts.onway'), '#10b981'],
      ['Seller yangi', count('seller_counts.new'), '#f59e0b'],
    ];
    node.innerHTML = segments.map(([name, value, color], index) => `
      <div class="col-md-6">
        <div class="fs-live__mini-row">
          <div class="fs-live__rank ${index === 0 ? 'is-top' : ''}">${index + 1}</div>
          <div class="flex-grow-1 min-w-0">
            <div class="text-light small fw-semibold text-truncate">${name}</div>
            <div class="text-secondary" style="font-size:11px">Operatsion navbat</div>
          </div>
          <div class="fw-bold" style="color:${color}">${fmt(value)}</div>
        </div>
      </div>
    `).join('');
  }

  function renderHealth() {
    const node = el('[data-live-health]');
    if (!node) return;
    const health = [
      ['Asosiy server', 'Optimal', '28%'],
      ['To‘lov shlyuzi', 'Tezkor', '14%'],
      ['SMS gateway', 'Barqaror', '45%'],
      ['Database', 'Optimal', '32%'],
    ];
    node.innerHTML = health.map(([name, status, load]) => `
      <div class="col-md-6">
        <div class="fs-live__mini-row justify-content-between">
          <div>
            <div class="text-light" style="font-size:12px">${name}</div>
            <div class="text-secondary" style="font-size:10px">Yuklanish: ${load}</div>
          </div>
          <span class="chip chip-success">${status}</span>
        </div>
      </div>
    `).join('');
  }

  function render() {
    renderMetrics();
    renderFeed();
    renderMap();
    renderChart();
    renderSegments();
    renderHealth();
  }

  async function refresh() {
    if (paused) return;
    try {
      const response = await fetch(root.dataset.endpoint, { headers: { Accept: 'application/json' } });
      if (response.ok) snapshot = await response.json();
      el('[data-live-status]').innerHTML = '<span class="live-pulse"></span>System active';
      render();
    } catch (error) {
      el('[data-live-status]').textContent = 'Connection waiting';
    }
  }

  function schedule() {
    clearInterval(timer);
    timer = setInterval(refresh, speed);
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  root.querySelectorAll('[data-live-speed]').forEach((button) => {
    button.addEventListener('click', () => {
      speed = Number(button.dataset.liveSpeed || 8000);
      root.querySelectorAll('[data-live-speed]').forEach((item) => {
        item.classList.toggle('btn-primary', item === button);
        item.classList.toggle('btn-outline-light', item !== button);
      });
      schedule();
    });
  });

  el('[data-live-pause]')?.addEventListener('click', (event) => {
    paused = !paused;
    const button = event.currentTarget;
    button.classList.toggle('btn-warning', paused);
    button.classList.toggle('btn-outline-light', !paused);
    button.querySelector('i').className = `bi ${paused ? 'bi-play-fill' : 'bi-pause-fill'} me-1`;
    button.querySelector('span').textContent = paused ? 'Davom etish' : 'Pauza';
    el('[data-live-status]').textContent = paused ? 'Paused' : 'System active';
  });

  el('[data-live-fullscreen]')?.addEventListener('click', () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen();
  });

  setInterval(() => setText('[data-live-clock]', new Date().toLocaleTimeString('uz-UZ')), 1000);
  render();
  schedule();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.monitor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/dashboard-live.blade.php ENDPATH**/ ?>