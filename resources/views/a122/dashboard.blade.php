@extends('a122.layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
  .dash-template {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .dash-stat-card {
    position: relative;
    min-height: 168px;
    overflow: hidden;
    border: 1px solid var(--template-border);
    border-radius: 14px;
    background: var(--template-card);
    padding: 20px;
    transition: transform .2s ease, box-shadow .2s ease;
  }

  .dash-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(15,23,42,.08);
  }

  .dark .dash-stat-card:hover {
    box-shadow: none;
  }

  .dash-stat-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: #fff;
    font-size: 22px;
  }

  .dash-stat-label {
    margin-top: 12px;
    color: var(--template-muted);
    font-size: 13px;
  }

  .dash-stat-value {
    margin-top: 2px;
    color: var(--template-text);
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 0;
  }

  .dash-stat-trend {
    color: #10b981;
    font-size: 12px;
    font-weight: 800;
  }

  .dash-stat-trend.is-down {
    color: #ef4444;
  }

  .panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 16px;
  }

  .panel-title {
    color: var(--template-text);
    font-size: 16px;
    font-weight: 800;
  }

  .dash-chart {
    width: 100%;
    height: 300px;
    display: block;
  }

  .dash-mini-chart {
    width: 100%;
    height: 240px;
    display: block;
  }

  .dash-activity-row {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    padding: .72rem 0;
    border-bottom: 1px solid color-mix(in srgb, var(--template-border) 74%, transparent);
  }

  .dash-activity-row:last-child {
    border-bottom: 0;
  }

  .dash-activity-icon {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 10px;
    background: rgba(79,70,229,.12);
    color: #4f46e5;
  }

  .dash-product-rank {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: color-mix(in srgb, var(--template-card) 72%, var(--template-bg));
    color: var(--template-muted);
    font-size: 12px;
    font-weight: 900;
  }
</style>
@endpush

@section('content')
@php
  $dashAdmin = $admin ?? auth('panel')->user();
  $resolveImg = function ($path) {
    $value = trim((string) $path);
    if ($value === '') return null;
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) return $value;
    return asset('storage/' . ltrim($value, '/'));
  };
  $extractImage = function ($images) use ($resolveImg) {
    if (is_string($images)) {
      $decoded = json_decode($images, true);
      if (is_array($decoded)) {
        $images = $decoded;
      }
    }
    if (is_array($images)) {
      $first = $images[0] ?? data_get($images, '0.url') ?? null;
      return $resolveImg(is_array($first) ? ($first['url'] ?? null) : $first);
    }
    return $resolveImg($images);
  };
  $fmtMoney = fn ($amount) => number_format((float) $amount, 0, '.', ' ');
  $opsLoad = $pendingOrders + $packingOrders + $onwayOrders;
  $payoutQueue = $pendingSellerTxCount + $pendingCourierTxCount;
  $trendRevenue = $weekRevenue > 0 ? round(($todayRevenue / max($weekRevenue / 7, 1) - 1) * 100, 1) : 0;
  $trendOrders = $weekOrders > 0 ? round(($todayOrders / max($weekOrders / 7, 1) - 1) * 100, 1) : 0;
  $dashCards = [
    [
      'label' => 'Umumiy daromad',
      'value' => number_format($totalRevenue / 1_000_000, 1) . 'M UZS',
      'trend' => ($trendRevenue >= 0 ? '+' : '') . $trendRevenue . '%',
      'down' => $trendRevenue < 0,
      'icon' => 'bi-cash-coin',
      'color' => 'linear-gradient(135deg,#4f46e5,#7c3aed)',
    ],
    [
      'label' => 'Buyurtmalar',
      'value' => number_format($totalOrders),
      'trend' => ($trendOrders >= 0 ? '+' : '') . $trendOrders . '%',
      'down' => $trendOrders < 0,
      'icon' => 'bi-bag-check',
      'color' => 'linear-gradient(135deg,#10b981,#059669)',
    ],
    [
      'label' => 'Mijozlar',
      'value' => number_format($totalUsers),
      'trend' => '+' . number_format($newUsersToday) . ' bugun',
      'down' => false,
      'icon' => 'bi-people',
      'color' => 'linear-gradient(135deg,#f59e0b,#d97706)',
    ],
    [
      'label' => 'Navbat',
      'value' => number_format($opsLoad + $payoutQueue),
      'trend' => number_format($pendingOrders) . ' order',
      'down' => ($opsLoad + $payoutQueue) > 0,
      'icon' => 'bi-clock-history',
      'color' => 'linear-gradient(135deg,#ef4444,#dc2626)',
    ],
  ];
  $categoryShare = [
    ['name' => 'Kitob', 'value' => (int) ($revenueByType['book'] ?? 0), 'color' => '#4f46e5'],
    ['name' => 'Kanstovar', 'value' => (int) ($revenueByType['stationery'] ?? 0), 'color' => '#10b981'],
    ['name' => 'Mystery Box', 'value' => (int) ($mysteryRevTotal ?? 0), 'color' => '#f59e0b'],
    ['name' => 'Gift', 'value' => (int) ($giftRevenue ?? 0), 'color' => '#ec4899'],
  ];
  $chartPayload = [
    'revenue' => collect($monthlyRevenue)->map(fn ($row) => ['label' => $row['month'], 'value' => round(((float) $row['total']) / 1_000_000, 2)])->values(),
    'orders' => collect($dailyOrders)->map(fn ($row) => ['label' => $row['day'], 'value' => (int) $row['count']])->values(),
    'categories' => $categoryShare,
  ];
  $quickLinks = [
    ['label' => 'Buyurtmalar', 'icon' => 'bi-receipt', 'href' => route('admin.orders.index'), 'meta' => number_format($pendingOrders) . ' kutilmoqda'],
    ['label' => 'Sellerlar', 'icon' => 'bi-shop-window', 'href' => route('admin.sellers.index'), 'meta' => number_format($pendingSellers) . ' ariza'],
    ['label' => 'Tranzaksiyalar', 'icon' => 'bi-arrow-left-right', 'href' => route('admin.transactions.index'), 'meta' => number_format($payoutQueue) . ' navbat'],
    ['label' => 'Support', 'icon' => 'bi-headset', 'href' => route('admin.support.index'), 'meta' => 'Inbox'],
  ];
@endphp

<div class="dash-template" data-template-dashboard data-chart='@json($chartPayload)'>
  <div class="page-head">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Bugungi buyurtma, savdo va operatsion navbatlar.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('admin.dashboard.live') }}" target="_blank" class="btn btn-primary-gradient btn-sm">
        <i class="bi bi-broadcast me-1"></i>Live
      </a>
      <a href="{{ route('admin.dashboard', ['clear_cache' => 1]) }}" class="btn btn-light border btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i>Yangilash
      </a>
    </div>
  </div>

  <div class="row g-3">
    @foreach($dashCards as $card)
      <div class="col-xl-3 col-md-6">
        <div class="dash-stat-card h-100">
          <div class="d-flex align-items-start justify-content-between">
            <div class="dash-stat-icon" style="background: {{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></div>
            <span class="dash-stat-trend {{ $card['down'] ? 'is-down' : '' }}">
              <i class="bi bi-{{ $card['down'] ? 'arrow-down' : 'arrow-up' }}"></i> {{ $card['trend'] }}
            </span>
          </div>
          <div class="dash-stat-value">{{ $card['value'] }}</div>
          <div class="dash-stat-label">{{ $card['label'] }}</div>
        </div>
      </div>
    @endforeach
  </div>

  @if(!empty($alerts))
    <div class="row g-3">
      @foreach(array_slice($alerts, 0, 4) as [$color, $icon, $title, $desc, $url])
        @php
          $tone = match($color) {
            'danger' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            'info' => 'info',
            default => 'secondary',
          };
        @endphp
        <div class="col-xl-3 col-md-6">
          <a href="{{ $url }}" class="card-panel d-flex align-items-center gap-3 h-100 text-decoration-none">
            <span class="dash-activity-icon bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis"><i class="bi {{ $icon }}"></i></span>
            <span class="min-w-0">
              <span class="d-block fw-bold text-truncate">{{ $title }}</span>
              <span class="d-block small text-secondary text-truncate">{{ $desc }}</span>
            </span>
          </a>
        </div>
      @endforeach
    </div>
  @endif

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-panel h-100">
        <div class="panel-head">
          <div>
            <div class="panel-title">Savdo dinamikasi</div>
            <small class="text-secondary">Oylik paid daromad, mln UZS</small>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary active" data-chart-mode="revenue">Daromad</button>
            <button type="button" class="btn btn-outline-secondary" data-chart-mode="orders">Buyurtmalar</button>
          </div>
        </div>
        <svg class="dash-chart" viewBox="0 0 760 300" preserveAspectRatio="none" data-bar-chart></svg>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card-panel h-100">
        <div class="panel-head">
          <div class="panel-title">Kategoriya ulushi</div>
        </div>
        <svg class="dash-mini-chart" viewBox="0 0 320 220" data-donut-chart></svg>
        <div class="d-flex flex-wrap gap-2 mt-2">
          @foreach($categoryShare as $item)
            <span class="chip chip-gray">
              <span style="width:8px;height:8px;border-radius:50%;background:{{ $item['color'] }};display:inline-block"></span>
              {{ $item['name'] }}
            </span>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-panel">
        <div class="panel-head">
          <div class="panel-title">Eng ko‘p sotilgan mahsulotlar</div>
          <a href="{{ route('admin.books.index') }}" class="small text-decoration-none fw-semibold" style="color:#4f46e5">Katalog</a>
        </div>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th></th>
                <th>Mahsulot</th>
                <th>Tur</th>
                <th>Sotilgan</th>
                <th>Daromad</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topMixedProducts->take(6) as $product)
                @php $cover = $extractImage($product->images ?? null); @endphp
                <tr>
                  <td>
                    @if($cover)
                      <img src="{{ $cover }}" alt="" class="thumb">
                    @else
                      <div class="thumb d-grid place-items-center"><i class="bi bi-book"></i></div>
                    @endif
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $product->name }}</div>
                    <div class="small text-secondary">{{ $product->author ?? '—' }}</div>
                  </td>
                  <td><span class="chip {{ $product->_type === 'stationery' ? 'chip-warning' : 'chip-purple' }}">{{ $product->_type === 'stationery' ? 'Kanstovar' : 'Kitob' }}</span></td>
                  <td class="fw-semibold">{{ number_format($product->sold_count) }}</td>
                  <td class="fw-semibold text-success">{{ $fmtMoney($product->total_revenue) }} UZS</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center py-5 text-secondary">Mahsulot statistikasi yo‘q.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card-panel h-100">
        <div class="panel-head">
          <div class="panel-title">So‘nggi faoliyat</div>
          <span class="chip chip-success"><span class="live-pulse"></span>Jonli</span>
        </div>
        <div>
          @forelse($recentOrders->take(8) as $order)
            @php
              $statusTone = match($order['status']) {
                'Mijoz qabul qildi', 'Yetib bordi' => 'success',
                "Yo'lda", 'Qadoqlanmoqda' => 'info',
                'Kutilmoqda' => 'warning',
                'Bekor qilindi', 'Qaytgan' => 'danger',
                default => 'gray',
              };
            @endphp
            <div class="dash-activity-row">
              <div class="dash-activity-icon"><i class="bi bi-receipt"></i></div>
              <div class="flex-grow-1 min-w-0">
                <div class="small fw-semibold text-truncate">#{{ $order['id'] }} · {{ $order['customer'] }}</div>
                <div class="small text-secondary">{{ $order['date'] }}</div>
              </div>
              <div class="text-end">
                <div class="small fw-bold text-nowrap">{{ $order['amount'] }}</div>
                <span class="chip chip-{{ $statusTone }}">{{ $order['status'] }}</span>
              </div>
            </div>
          @empty
            <div class="text-center py-5 text-secondary">Faoliyat yo‘q.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-panel">
        <div class="panel-head">
          <div class="panel-title">Tezkor bo‘limlar</div>
        </div>
        <div class="row g-2">
          @foreach($quickLinks as $link)
            <div class="col-md-3 col-6">
              <a href="{{ $link['href'] }}" class="text-decoration-none d-flex align-items-center gap-2 p-3 rounded border h-100">
                <span class="dash-product-rank"><i class="bi {{ $link['icon'] }}"></i></span>
                <span class="min-w-0">
                  <span class="d-block fw-semibold text-truncate">{{ $link['label'] }}</span>
                  <span class="d-block small text-secondary text-truncate">{{ $link['meta'] }}</span>
                </span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-panel h-100">
        <div class="panel-head">
          <div class="panel-title">Top mijozlar</div>
        </div>
        @forelse($topBuyers as $index => $buyer)
          <div class="dash-activity-row">
            <div class="dash-product-rank {{ $index === 0 ? 'text-warning' : '' }}">{{ $index + 1 }}</div>
            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold text-truncate">{{ trim(($buyer->user?->name ?? 'User') . ' ' . ($buyer->user?->lastname ?? '')) }}</div>
              <div class="small text-secondary">{{ number_format($buyer->order_count) }} buyurtma</div>
            </div>
            <div class="fw-bold text-nowrap">{{ $fmtMoney($buyer->total_spent) }}</div>
          </div>
        @empty
          <div class="text-center py-5 text-secondary">Mijoz statistikasi yo‘q.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const root = document.querySelector('[data-template-dashboard]');
  if (!root) return;
  const payload = JSON.parse(root.dataset.chart || '{}');
  const fmt = (value) => new Intl.NumberFormat('uz-UZ').format(Number(value || 0));

  function drawBars(mode = 'revenue') {
    const svg = root.querySelector('[data-bar-chart]');
    if (!svg) return;
    const data = payload[mode] || [];
    const max = Math.max(...data.map((row) => Number(row.value || 0)), 1);
    const width = 760;
    const height = 300;
    const pad = 36;
    const gap = 16;
    const barW = Math.max(22, (width - pad * 2 - gap * Math.max(data.length - 1, 0)) / Math.max(data.length, 1));
    svg.innerHTML = `
      <defs>
        <linearGradient id="dashBarGradient" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#7c3aed" stop-opacity="1"></stop>
          <stop offset="100%" stop-color="#4f46e5" stop-opacity=".72"></stop>
        </linearGradient>
      </defs>
      <line x1="${pad}" y1="${height - pad}" x2="${width - pad}" y2="${height - pad}" stroke="rgba(148,163,184,.28)" />
      ${data.map((row, index) => {
        const value = Number(row.value || 0);
        const h = Math.max(3, (value / max) * (height - pad * 2));
        const x = pad + index * (barW + gap);
        const y = height - pad - h;
        return `
          <rect x="${x}" y="${y}" width="${barW}" height="${h}" rx="8" fill="url(#dashBarGradient)"></rect>
          <text x="${x + barW / 2}" y="${height - 12}" text-anchor="middle" fill="#9ca3af" font-size="12">${row.label}</text>
          <text x="${x + barW / 2}" y="${Math.max(16, y - 8)}" text-anchor="middle" fill="currentColor" font-size="12" font-weight="700">${fmt(value)}</text>
        `;
      }).join('')}
    `;
  }

  function polar(cx, cy, r, angle) {
    const rad = (angle - 90) * Math.PI / 180;
    return { x: cx + r * Math.cos(rad), y: cy + r * Math.sin(rad) };
  }

  function arcPath(cx, cy, r, start, end) {
    const s = polar(cx, cy, r, end);
    const e = polar(cx, cy, r, start);
    const large = end - start <= 180 ? 0 : 1;
    return `M ${s.x} ${s.y} A ${r} ${r} 0 ${large} 0 ${e.x} ${e.y}`;
  }

  function drawDonut() {
    const svg = root.querySelector('[data-donut-chart]');
    if (!svg) return;
    const data = (payload.categories || []).filter((row) => Number(row.value || 0) > 0);
    const total = data.reduce((sum, row) => sum + Number(row.value || 0), 0);
    if (!total) {
      svg.innerHTML = '<text x="160" y="112" text-anchor="middle" fill="#9ca3af" font-size="13">Ma’lumot yo‘q</text>';
      return;
    }
    let start = 0;
    const paths = data.map((row) => {
      const angle = Number(row.value || 0) / total * 360;
      const path = `<path d="${arcPath(160, 108, 78, start, start + angle)}" fill="none" stroke="${row.color}" stroke-width="28" stroke-linecap="round"></path>`;
      start += angle;
      return path;
    }).join('');
    svg.innerHTML = `${paths}<text x="160" y="103" text-anchor="middle" fill="currentColor" font-size="18" font-weight="800">${fmt(Math.round(total / 1_000_000))}M</text><text x="160" y="124" text-anchor="middle" fill="#9ca3af" font-size="12">UZS</text>`;
  }

  root.querySelectorAll('[data-chart-mode]').forEach((button) => {
    button.addEventListener('click', () => {
      root.querySelectorAll('[data-chart-mode]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      drawBars(button.dataset.chartMode || 'revenue');
    });
  });

  drawBars('revenue');
  drawDonut();
})();
</script>
@endpush
