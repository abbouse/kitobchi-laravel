import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import InfoHint from '../components/InfoHint';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));

interface CountMap {
  [key: string]: number;
}

interface FeedRow {
  id: number;
  title?: string;
  customer?: string;
  seller?: string;
  courier?: string;
  amount: number;
  status: string;
  status_code?: string;
  updated_at?: string;
  url?: string;
}

interface Snapshot {
  generated_at: string;
  endpoint?: string;
  financialRestricted?: boolean;
  kpis: {
    total_revenue: number;
    today_revenue: number;
    month_revenue: number;
    platform_profit: number;
    total_orders: number;
    paid_orders: number;
    today_orders: number;
    week_orders: number;
    active_orders: number;
    completed_orders: number;
    cancelled_orders: number;
    avg_order_value: number;
    online_users: number;
    delivery_income: number;
    promo_discount: number;
    cashback: number;
    commission: number;
    courier_payout: number;
    manual_expenses: number;
    provider_fee: number;
    tax: number;
    completion_rate: number;
    cancellation_rate: number;
    paid_rate: number;
    profit_margin: number;
  };
  main_counts: CountMap;
  seller_counts: CountMap;
  courier_counts: CountMap;
  chart: { hour: string; orders: number; revenue: number }[];
  regions: { name: string; value: number; revenue: number; profit: number; color: string; coords: { x: number; y: number } }[];
  recent_orders: FeedRow[];
  recent_seller_orders: FeedRow[];
  recent_courier_orders: FeedRow[];
  online_users: { id: number; name: string; avatar?: string | null; last_seen?: string }[];
  top_products: { name: string; quantity: number; revenue: number }[];
  alerts: { level: string; icon: string; title: string; text: string; url?: string }[];
  payment_split: { name: string; count: number; share: number; color: string }[];
  delivery_split: { name: string; count: number; revenue: number }[];
}

const emptySnapshot: Snapshot = {
  generated_at: '--:--:--',
  kpis: {
    total_revenue: 0,
    today_revenue: 0,
    month_revenue: 0,
    platform_profit: 0,
    total_orders: 0,
    paid_orders: 0,
    today_orders: 0,
    week_orders: 0,
    active_orders: 0,
    completed_orders: 0,
    cancelled_orders: 0,
    avg_order_value: 0,
    online_users: 0,
    delivery_income: 0,
    promo_discount: 0,
    cashback: 0,
    commission: 0,
    courier_payout: 0,
    manual_expenses: 0,
    provider_fee: 0,
    tax: 0,
    completion_rate: 0,
    cancellation_rate: 0,
    paid_rate: 0,
    profit_margin: 0,
  },
  main_counts: {},
  seller_counts: {},
  courier_counts: {},
  chart: [],
  regions: [],
  recent_orders: [],
  recent_seller_orders: [],
  recent_courier_orders: [],
  online_users: [],
  top_products: [],
  alerts: [],
  payment_split: [],
  delivery_split: [],
};

const statusClass = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (value.includes('qabul') || value.includes('yetib') || value.includes('delivered') || value === 'customer_received' || value === 'c' || value === 'd') return 'chip-success';
  if (value.includes("yo'l") || value.includes('delivery') || value === 'b') return 'chip-info';
  if (value.includes('bekor') || value.includes('qayt') || value.includes('cancel') || value === 'returned' || value === 'f' || value === 'r') return 'chip-danger';
  return 'chip-warning';
};

const liveKpiHelps: Record<string, string> = {
  'Jami daromad': "Mijoz qabul qilgan va to'lovi tasdiqlangan barcha savdolar summasi. Bu yalpi tushum, sof foyda emas.",
  'Bugungi daromad': "Bugun mijoz qabul qilgan va to'lovi tasdiqlangan savdolar summasi.",
  'Oylik daromad': "Joriy oyda mijoz qabul qilgan va to'lovi tasdiqlangan savdolar summasi.",
  'Platform signal': "Taxminiy net signal: seller komissiya + delivery income - chegirma - cashback - kuryer - chiqim - provider - soliq.",
  'Jami order': "Barcha asosiy buyurtmalar soni.",
  'Bugungi order': "Bugun yaratilgan buyurtmalar soni.",
  'Aktiv order': "Hali jarayonda turgan buyurtmalar: yangi, qadoqlanmoqda yoki yo'lda.",
  AOV: "Average Order Value: yakuniy savdo tushumi mijoz qabul qilgan va to'lovi tasdiqlangan savdolar soniga bo'linadi.",
  'Seller oqimi': "Seller fulfillment jarayonida turgan orderlar soni.",
  'Kuryer oqimi': "Kuryerga tegishli aktiv yetkazish orderlari.",
  'Online user': "So'nggi bir necha daqiqada aktiv foydalanuvchilar.",
  Completion: "Yakunlangan orderlar ulushi: completed / jami order.",
};

const liveFinanceHelps: Record<string, string> = {
  'Yakuniy savdo': "Moliyaviy hisobga faqat mijoz qabul qilgan va to'lovi tasdiqlangan orderlar kiradi.",
  'Seller komissiya': "Sellerlardan ushlanadigan komissiya. Sellerning o'z foizi bo'lsa o'sha, bo'lmasa global settings foizi ishlaydi.",
  'Yetkazish daromadi': "Mijozlar to'lagan delivery summasi.",
  'Kuryer payout': "Kuryerlarga to'lanadigan yetkazish xarajatlari.",
  'Promo + cashback': "Platforma bergan chegirma va cashbacklar yig'indisi.",
  'Chiqim + provider + soliq': "Admin kiritgan chiqimlar, payment provider komissiyasi va soliq yig'indisi.",
  'Net marja': "Platform signal yalpi tushumga nisbatan foizda. Manfiy chiqsa xarajat tushumdan ko'p.",
};

export default function LiveDashboard() {
  const { snapshot: initialSnapshot = emptySnapshot, liveEndpoint } = usePage<{ snapshot?: Snapshot; liveEndpoint?: string }>().props;
  const [snapshot, setSnapshot] = useState<Snapshot>(initialSnapshot);
  const [isPaused, setIsPaused] = useState(false);
  const [speed, setSpeed] = useState(5000);
  const [clock, setClock] = useState(new Date());
  const [lastError, setLastError] = useState<string | null>(null);

  const endpoint = liveEndpoint || snapshot.endpoint || '/boshqaruv/live/data';
  const mainActive = (snapshot.main_counts.new || 0) + (snapshot.main_counts.packing || 0) + (snapshot.main_counts.onway || 0);
  const sellerActive = (snapshot.seller_counts.payment_pending || 0) + (snapshot.seller_counts.new || 0) + (snapshot.seller_counts.accepted || 0) + (snapshot.seller_counts.handover || 0);
  const courierActive = (snapshot.courier_counts.pending || 0) + (snapshot.courier_counts.in_delivery || 0);
  const netSignal = snapshot.kpis.platform_profit;
  const regionTotalOrders = snapshot.regions.reduce((sum, region) => sum + region.value, 0);
  const regionTotalRevenue = snapshot.regions.reduce((sum, region) => sum + region.revenue, 0);
  const topRegion = snapshot.regions[0];

  const feed = useMemo(() => [
    ...snapshot.recent_orders.map((row) => ({ ...row, kind: 'Buyurtma', icon: 'bi-receipt' })),
    ...snapshot.recent_seller_orders.map((row) => ({ ...row, kind: 'Seller', icon: 'bi-shop-window' })),
    ...snapshot.recent_courier_orders.map((row) => ({ ...row, kind: 'Kuryer', icon: 'bi-bicycle' })),
  ].sort((a, b) => b.id - a.id).slice(0, 14), [snapshot]);

  useEffect(() => {
    const timer = setInterval(() => setClock(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (isPaused) return;

    const controller = new AbortController();
    const refresh = async () => {
      try {
        const response = await fetch(endpoint, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal,
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        setSnapshot(await response.json());
        setLastError(null);
      } catch (error) {
        if (!controller.signal.aborted) setLastError(error instanceof Error ? error.message : 'Live data olinmadi');
      }
    };

    const interval = setInterval(refresh, speed);
    refresh();
    return () => {
      controller.abort();
      clearInterval(interval);
    };
  }, [endpoint, isPaused, speed]);

  const goFull = () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen();
  };

  return (
    <div className="fs-live p-3 p-xl-4" style={{ minHeight: '100vh' }}>
      <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.08)' }}>
        <div className="d-flex align-items-center gap-2">
          <div className="live-brand-mark"><i className="bi bi-broadcast"></i></div>
          <div>
            <div className="d-flex align-items-center gap-2">
              <h3 style={{ color: '#f8fafc', fontWeight: 800, margin: 0 }}>Kitobchi Live Command Center</h3>
              <span className="chip" style={{ background: isPaused ? 'rgba(245,158,11,0.2)' : 'rgba(16,185,129,0.2)', color: isPaused ? '#fcd34d' : '#8FE3C1', border: `1px solid ${isPaused ? '#DCAE63' : '#63CE9F'}`, fontSize: 10 }}>
                {!isPaused && <span className="live-pulse"></span>}
                {isPaused ? 'PAUSED' : 'LIVE'}
              </span>
            </div>
            <div style={{ color: '#94a3b8', fontSize: 11 }}>Buyurtma, seller, kuryer, to'lov va online mijozlar real monitoringi</div>
          </div>
        </div>

        <div className="d-flex gap-1 align-items-center flex-wrap">
          <div className="btn-group btn-group-sm">
            <button className={`btn ${speed === 8000 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(8000)}>x1</button>
            <button className={`btn ${speed === 5000 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(5000)}>x2</button>
            <button className={`btn ${speed === 2000 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(2000)}>x5</button>
          </div>
          <button className={`btn btn-sm ${isPaused ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setIsPaused(!isPaused)}>
            <i className={`bi ${isPaused ? 'bi-play-fill' : 'bi-pause-fill'}`}></i>
          </button>
          <div className="px-2 py-1 rounded" style={{ background: '#1e293b', color: '#e2e8f0', fontSize: 12, border: '1px solid #334155' }}>
            <i className="bi bi-clock text-primary me-1"></i>{clock.toLocaleTimeString('uz-UZ')}
          </div>
          <button className="btn btn-outline-light btn-sm" onClick={goFull}><i className="bi bi-arrows-fullscreen"></i></button>
          <button className="btn btn-primary-gradient btn-sm" onClick={() => router.visit('/boshqaruv')}><i className="bi bi-house-door"></i></button>
        </div>
      </div>

      <div className="mb-3 p-2 rounded d-flex align-items-center gap-2" style={{ background: 'var(--kc-bg-subtle)', border: '1px solid var(--kc-border-subtle)' }}>
        <i className={`bi ${lastError ? 'bi-exclamation-triangle' : 'bi-activity'}`} style={{ fontSize: 18, color: lastError ? '#DCAE63' : '#8FE3C1' }}></i>
        <span className="fw-bold small" style={{ color: lastError ? '#fcd34d' : '#8FE3C1' }}>{lastError ? 'Live ogohlantirish:' : 'Snapshot:'}</span>
        <span style={{ color: '#e2e8f0', fontSize: 13 }}>{lastError || `So'nggi yangilanish ${snapshot.generated_at}. Aktiv oqim: ${mainActive + sellerActive + courierActive} ta.`}</span>
      </div>

      {snapshot.financialRestricted ? (
        <div className="mb-3 p-2 rounded d-flex align-items-center gap-2" style={{ background: 'rgba(100,116,139,0.18)', border: '1px solid rgba(148,163,184,0.35)' }}>
          <i className="bi bi-lock-fill" style={{ fontSize: 16, color: '#cbd5e1' }}></i>
          <span style={{ color: '#e2e8f0', fontSize: 13 }}>Sizning rolingizda moliyaviy ko'rsatkichlar (daromad, tushum, foyda) 0 qilib ko'rsatiladi — faqat operatsion sonlar (order, mijoz, hudud bo'yicha oqim) ochiq. Kerak bo'lsa, "Moliya" ruxsatiga ega admindan so'rang.</span>
        </div>
      ) : null}

      <div className="row g-2 mb-3">
        {[
          { l: 'Jami daromad', v: fmt(snapshot.kpis.total_revenue) + " so'm", icon: 'bi-cash-stack', c: '#C3A6EE' },
          { l: 'Bugungi daromad', v: fmt(snapshot.kpis.today_revenue) + " so'm", icon: 'bi-calendar2-day', c: '#63CE9F' },
          { l: 'Oylik daromad', v: fmt(snapshot.kpis.month_revenue) + " so'm", icon: 'bi-calendar3', c: '#A9B8FF' },
          { l: 'Platform signal', v: fmt(netSignal) + " so'm", icon: 'bi-graph-up-arrow', c: '#4FBF8E' },
          { l: 'Jami order', v: fmt(snapshot.kpis.total_orders), icon: 'bi-bag-check', c: '#63CE9F' },
          { l: 'Bugungi order', v: fmt(snapshot.kpis.today_orders), icon: 'bi-lightning-charge', c: '#06b6d4' },
          { l: 'Aktiv order', v: fmt(snapshot.kpis.active_orders), icon: 'bi-hourglass-split', c: '#DCAE63' },
          { l: 'AOV', v: fmt(snapshot.kpis.avg_order_value) + " so'm", icon: 'bi-receipt', c: '#E39BC0' },
          { l: 'Seller oqimi', v: fmt(sellerActive), icon: 'bi-shop-window', c: '#B9A9F0' },
          { l: 'Kuryer oqimi', v: fmt(courierActive), icon: 'bi-bicycle', c: '#92B3F2' },
          { l: 'Online user', v: fmt(snapshot.kpis.online_users), icon: 'bi-people', c: '#63CE9F' },
          { l: 'Completion', v: snapshot.kpis.completion_rate.toFixed(1) + '%', icon: 'bi-bullseye', c: '#DCAE63' },
        ].map((kpi) => (
          <div className="col-xl-2 col-lg-3 col-md-4 col-6" key={kpi.l}>
            <div className="live-kpi" style={{ borderLeftColor: kpi.c }}>
              <div className="d-flex justify-content-between align-items-start">
                <span className="d-inline-flex align-items-center gap-1">{kpi.l}<InfoHint tone="dark" text={liveKpiHelps[kpi.l]} /></span>
                <i className={`bi ${kpi.icon}`} style={{ color: kpi.c }}></i>
              </div>
              <strong style={{ color: kpi.c }}>{kpi.v}</strong>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-2 mb-3">
        {[
          { l: 'Yakuniy savdo', v: fmt(snapshot.kpis.paid_orders), s: `${snapshot.kpis.paid_rate.toFixed(1)}% ulush`, c: '#63CE9F' },
          { l: 'Seller komissiya', v: fmt(snapshot.kpis.commission) + " so'm", s: 'Tasdiqlangan tranzaksiya', c: '#B9A9F0' },
          { l: 'Yetkazish daromadi', v: fmt(snapshot.kpis.delivery_income) + " so'm", s: 'Paid orderlar', c: '#06b6d4' },
          { l: 'Kuryer payout', v: fmt(snapshot.kpis.courier_payout) + " so'm", s: 'Topshirilgan orderlar', c: '#DCAE63' },
          { l: 'Promo + cashback', v: fmt(snapshot.kpis.promo_discount + snapshot.kpis.cashback) + " so'm", s: 'Chegirma xarajati', c: '#EC8A8D' },
          { l: 'Chiqim + provider + soliq', v: fmt(snapshot.kpis.manual_expenses + snapshot.kpis.provider_fee + snapshot.kpis.tax) + " so'm", s: 'Marketplace xarajatlari', c: '#E07A7D' },
          { l: 'Net marja', v: snapshot.kpis.profit_margin.toFixed(1) + '%', s: `Cancel ${snapshot.kpis.cancellation_rate.toFixed(1)}%`, c: snapshot.kpis.profit_margin >= 0 ? '#63CE9F' : '#EC8A8D' },
        ].map((item) => (
          <div className="col-xl-2 col-lg-4 col-md-6" key={item.l}>
            <div className="live-kpi" style={{ borderLeftColor: item.c }}>
              <span className="d-inline-flex align-items-center gap-1">{item.l}<InfoHint tone="dark" text={liveFinanceHelps[item.l]} /></span>
              <strong style={{ color: item.c }}>{item.v}</strong>
              <small style={{ color: '#94a3b8' }}>{item.s}</small>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-2 mb-3">
        <StatusPanel title="Main orderlar" icon="bi-receipt" counts={snapshot.main_counts} labels={{ all: 'Jami', new: 'Yangi', packing: 'Qadoq', onway: "Yo'lda", arrived: 'Yetdi', done: 'Done', cancelled: 'Bekor' }} />
        <StatusPanel title="Seller fulfillment" icon="bi-shop-window" counts={snapshot.seller_counts} labels={{ all: 'Jami', payment_pending: "To'lov", new: 'Yangi', accepted: 'Qabul', handover: 'Kuryerda', cancelled: 'Bekor' }} />
        <StatusPanel title="Kuryer fulfillment" icon="bi-bicycle" counts={snapshot.courier_counts} labels={{ all: 'Jami', pending: 'Kutmoqda', in_delivery: "Yo'lda", delivered: 'Yetdi', customer_received: 'Qabul', rejected: 'Bekor' }} />
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-4">
          <div className="card-panel live-region-panel h-100">
            <div className="d-flex align-items-center justify-content-between gap-2 mb-3">
              <div className="d-flex align-items-center gap-2">
                <div className="live-panel-title mb-0">Hududlar bo'yicha oqim</div>
                <InfoHint tone="dark" text="Buyurtma address snapshotidan viloyat/shahar nomi olinadi. Hozir O'zbekiston ichidagi real addresslar bo'yicha yig'iladi." />
              </div>
              <span className="chip chip-info">{fmt(regionTotalOrders)} ta</span>
            </div>

            <div className="live-region-hero">
              <div>
                <small>Yetakchi hudud</small>
                <strong>{topRegion?.name || "Ma'lumot yo'q"}</strong>
                <span>{topRegion ? `${fmt(topRegion.value)} order · ${fmt(topRegion.revenue)} so'm` : 'Address snapshot topilmadi'}</span>
              </div>
              <div>
                <small>Jami tushum</small>
                <strong>{fmt(regionTotalRevenue)}</strong>
                <span>so'm</span>
              </div>
            </div>

            <div className="live-region-bars">
              {snapshot.regions.map((region, index) => {
                const orderShare = regionTotalOrders > 0 ? region.value / regionTotalOrders * 100 : 0;
                const revenueShare = regionTotalRevenue > 0 ? region.revenue / regionTotalRevenue * 100 : 0;

                return (
                  <div className="live-region-row" key={region.name}>
                    <div className="live-region-rank" style={{ background: region.color }}>{index + 1}</div>
                    <div className="live-region-main">
                      <div className="d-flex justify-content-between gap-2">
                        <b>{region.name}</b>
                        <span>{fmt(region.value)} ta</span>
                      </div>
                      <div className="live-region-track">
                        <i style={{ width: `${Math.max(4, orderShare)}%`, background: region.color }}></i>
                      </div>
                      <small>{fmt(region.revenue)} so'm · {revenueShare.toFixed(1)}% tushum</small>
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="live-region-heat">
              {snapshot.regions.slice(0, 6).map((region) => {
                const intensity = regionTotalOrders > 0 ? Math.max(0.16, region.value / regionTotalOrders) : 0.16;
                return <span key={region.name} style={{ background: `color-mix(in srgb, ${region.color} ${Math.min(85, intensity * 160)}%, rgba(15,23,42,.82))` }}>{region.name.slice(0, 10)}</span>;
              })}
            </div>
          </div>
        </div>

        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <div className="d-flex align-items-center gap-2">
                <div className="live-panel-title mb-0">Bugungi savdo trendi</div>
                <InfoHint tone="dark" text="Bugun yakunlangan savdolar qabul qilingan soati bo'yicha guruhlanadi. Revenue - shu soatdagi yakuniy tushum, orders - yakuniy savdo soni." />
              </div>
              <span className="chip chip-success">{snapshot.generated_at}</span>
            </div>
            <ResponsiveContainer width="100%" height={235}>
              <AreaChart data={snapshot.chart}>
                <defs>
                  <linearGradient id="liveRevenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#E39BC0" stopOpacity={0.55} />
                    <stop offset="100%" stopColor="#A9B8FF" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="liveOrders" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#63CE9F" stopOpacity={0.45} />
                    <stop offset="100%" stopColor="#4FBF8E" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <XAxis dataKey="hour" tick={{ fill: '#94a3b8', fontSize: 10 }} interval={3} />
                <YAxis hide />
                <Tooltip contentStyle={{ background: '#0f172a', border: '1px solid #334155', borderRadius: 8, fontSize: 12 }} formatter={(value: number, name) => name === 'revenue' ? `${fmt(value)} so'm` : fmt(value)} />
                <Area type="monotone" dataKey="revenue" stroke="#E39BC0" strokeWidth={2} fill="url(#liveRevenue)" />
                <Area type="monotone" dataKey="orders" stroke="#63CE9F" strokeWidth={2} fill="url(#liveOrders)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="live-panel-title">Alertlar</div>
            <div className="live-alert-list">
              {snapshot.alerts.length ? snapshot.alerts.map((alert) => (
                <a href={alert.url || '#'} key={alert.title} className={`live-alert is-${alert.level}`}>
                  <i className={`bi ${alert.icon}`}></i>
                  <span><b>{alert.title}</b><small>{alert.text}</small></span>
                </a>
              )) : <div className="text-muted small">Hozircha kritik ogohlantirish yo'q.</div>}
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3">
        <div className="col-xl-5">
          <div className="card-panel live-feed-panel">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <div className="live-panel-title">Jonli operatsion feed</div>
              <span className="live-pulse"></span>
            </div>
            <div className="live-feed-list">
              {feed.map((row) => (
                <a href={row.url || '#'} className="live-feed-row" key={`${row.kind}-${row.id}`}>
                  <i className={`bi ${row.icon}`}></i>
                  <span>
                    <b>{row.kind} #{row.id}</b>
                    <small>{row.customer || row.seller || row.courier || row.title} · {row.updated_at || ''}</small>
                  </span>
                  <em>{fmt(row.amount)} so'm</em>
                  <strong className={`chip ${statusClass(row.status_code || row.status)}`}>{row.status}</strong>
                </a>
              ))}
            </div>
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel live-feed-panel">
            <div className="d-flex align-items-center gap-2 mb-2">
              <div className="live-panel-title mb-0">Top mahsulotlar</div>
              <InfoHint tone="dark" text="To'langan order itemlaridan eng ko'p sotilgan mahsulotlar. Gift sovg'alar bu ro'yxatga qo'shilmaydi." />
            </div>
            <div className="live-rank-list">
              {snapshot.top_products.map((product, index) => (
                <div className="live-rank-row" key={`${product.name}-${index}`}>
                  <span>{index + 1}</span>
                  <div><b>{product.name}</b><small>{fmt(product.quantity)} ta sotildi</small></div>
                  <strong>{fmt(product.revenue)} so'm</strong>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="row g-3">
            <div className="col-md-6">
              <SplitPanel title="To'lov holati" help="Orderlar to'lov holati bo'yicha guruhlanadi. Foiz jami order ichidagi ulush." rows={snapshot.payment_split.map((row) => ({ name: row.name, value: row.share, meta: `${fmt(row.count)} ta`, color: row.color }))} />
            </div>
            <div className="col-md-6">
              <SplitPanel title="Yetkazish turi" help="Buyurtmalar delivery turi bo'yicha ajratiladi. Yonidagi summa shu turdagi orderlar tushumi." rows={snapshot.delivery_split.map((row, index) => ({ name: row.name, value: row.count, meta: `${fmt(row.revenue)} so'm`, color: ['#A9B8FF', '#63CE9F', '#DCAE63', '#E39BC0', '#06b6d4'][index % 5] }))} />
            </div>
            <div className="col-12">
              <div className="card-panel">
                <div className="live-panel-title">Online mijozlar</div>
                <div className="live-online-list">
                  {snapshot.online_users.length ? snapshot.online_users.map((user) => (
                    <div key={user.id}>
                      {user.avatar ? <img src={user.avatar} alt={user.name} /> : <i className="bi bi-person"></i>}
                      <span><b>{user.name}</b><small>{user.last_seen}</small></span>
                    </div>
                  )) : <div className="text-muted small">So'nggi 5 daqiqada online mijoz topilmadi.</div>}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function StatusPanel({ title, icon, counts, labels }: { title: string; icon: string; counts: CountMap; labels: Record<string, string> }) {
  return (
    <div className="col-xl-4">
      <div className="live-status-panel">
        <div className="d-flex align-items-center gap-2 mb-2">
          <i className={`bi ${icon}`}></i>
          <b>{title}</b>
        </div>
        <div className="live-status-grid">
          {Object.entries(labels).map(([key, label]) => (
            <div key={key}>
              <span>{label}</span>
              <strong>{fmt(counts[key] || 0)}</strong>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function SplitPanel({ title, rows, help }: { title: string; rows: { name: string; value: number; meta: string; color: string }[]; help?: string }) {
  const total = rows.reduce((sum, row) => sum + row.value, 0) || 1;
  return (
    <div className="card-panel h-100">
      <div className="d-flex align-items-center gap-2 mb-2">
        <div className="live-panel-title mb-0">{title}</div>
        {help ? <InfoHint tone="dark" text={help} /> : null}
      </div>
      {rows.length ? rows.map((row) => {
        const width = row.value <= 100 && title.includes("To'lov") ? row.value : row.value / total * 100;
        return (
          <div key={row.name} className="mb-2">
            <div className="d-flex justify-content-between" style={{ fontSize: 11, color: '#cbd5e1' }}>
              <span>{row.name}</span>
              <span>{row.meta}</span>
            </div>
            <div className="progress" style={{ height: 7, background: 'rgba(255,255,255,0.06)' }}>
              <div className="progress-bar" style={{ width: `${Math.max(3, width)}%`, background: row.color }}></div>
            </div>
          </div>
        );
      }) : <div className="text-muted small">Ma'lumot yo'q.</div>}
    </div>
  );
}
