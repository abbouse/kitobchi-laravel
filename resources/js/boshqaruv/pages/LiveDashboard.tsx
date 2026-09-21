import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import InfoHint from '../components/InfoHint';
import { StatWidget, type StatVariant } from '../components/Axelit';
import { usePalette } from '../utils/palette';
import { applyTheme } from '../Layout';
import { tiIcon } from '../utils/icons';
import { Avatar as PAvatar } from '../components/Profile';

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

// Holat → Axelit rang nomi (badge text-light-*)
const statusTone = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (value.includes('qabul') || value.includes('yetib') || value.includes('delivered') || value === 'customer_received' || value === 'c' || value === 'd') return 'success';
  if (value.includes("yo'l") || value.includes('delivery') || value === 'b') return 'info';
  if (value.includes('bekor') || value.includes('qayt') || value.includes('cancel') || value === 'returned' || value === 'f' || value === 'r') return 'danger';
  return 'warning';
};

// Axelit ro'yxatlaridagi rang navbati
const TONES = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'] as const;
const toneAt = (index: number) => TONES[index % TONES.length];

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
  const [darkMode, setDarkMode] = useState(() => {
    try { return localStorage.getItem('boshqaruv-theme') === 'dark'; } catch { return false; }
  });
  const palette = usePalette();

  useEffect(() => { applyTheme(darkMode); }, [darkMode]);
  useEffect(() => { document.title = 'Live Dashboard — Kitobchi Boshqaruv'; }, []);

  const endpoint = liveEndpoint || snapshot.endpoint || '/boshqaruv/live/data';
  const mainActive = (snapshot.main_counts.new || 0) + (snapshot.main_counts.packing || 0) + (snapshot.main_counts.onway || 0);
  const sellerActive = (snapshot.seller_counts.payment_pending || 0) + (snapshot.seller_counts.new || 0) + (snapshot.seller_counts.accepted || 0) + (snapshot.seller_counts.handover || 0);
  const courierActive = (snapshot.courier_counts.pending || 0) + (snapshot.courier_counts.in_delivery || 0);
  const netSignal = snapshot.kpis.platform_profit;
  const regionTotalOrders = snapshot.regions.reduce((sum, region) => sum + region.value, 0);
  const regionTotalRevenue = snapshot.regions.reduce((sum, region) => sum + region.revenue, 0);
  const topRegion = snapshot.regions[0];

  const feed = useMemo(() => [
    ...snapshot.recent_orders.map((row) => ({ ...row, kind: 'Buyurtma', icon: 'ti-receipt' })),
    ...snapshot.recent_seller_orders.map((row) => ({ ...row, kind: 'Seller', icon: 'ti-building-store' })),
    ...snapshot.recent_courier_orders.map((row) => ({ ...row, kind: 'Kuryer', icon: 'ti-bike' })),
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

  const k = snapshot.kpis;
  // Axelit dashboard vidjetlari ritmi: katak-fon / primary-300 / danger-300 / yashil / info-300 / warning-300
  const kpis = [
    { l: 'Jami daromad', v: fmt(k.total_revenue), u: "so'm", icon: 'ti-cash', tone: 'provided' },
    { l: 'Bugungi daromad', v: fmt(k.today_revenue), u: "so'm", icon: 'ti-calendar-event', tone: 'primary' },
    { l: 'Oylik daromad', v: fmt(k.month_revenue), u: "so'm", icon: 'ti-calendar', tone: 'danger' },
    { l: 'Platform signal', v: fmt(netSignal), u: "so'm", icon: 'ti-trending-up', tone: 'store' },
    { l: 'Jami order', v: fmt(k.total_orders), u: 'ta', icon: 'ti-shopping-bag', tone: 'info' },
    { l: 'Bugungi order', v: fmt(k.today_orders), u: 'ta', icon: 'ti-bolt', tone: 'warning' },
    { l: 'Aktiv order', v: fmt(k.active_orders), u: 'ta', icon: 'ti-hourglass', tone: 'store' },
    { l: 'AOV', v: fmt(k.avg_order_value), u: "so'm", icon: 'ti-receipt', tone: 'provided' },
    { l: 'Seller oqimi', v: fmt(sellerActive), u: 'ta', icon: 'ti-building-store', tone: 'primary' },
    { l: 'Kuryer oqimi', v: fmt(courierActive), u: 'ta', icon: 'ti-bike', tone: 'danger' },
    { l: 'Online user', v: fmt(k.online_users), u: 'kishi', icon: 'ti-users', tone: 'warning' },
    { l: 'Completion', v: k.completion_rate.toFixed(1), u: '%', icon: 'ti-target', tone: 'info' },
  ];
  const finance = [
    { l: 'Yakuniy savdo', v: fmt(k.paid_orders), u: 'ta', s: `${k.paid_rate.toFixed(1)}% ulush`, tone: 'success' },
    { l: 'Seller komissiya', v: fmt(k.commission), u: "so'm", s: 'Tasdiqlangan tranzaksiya', tone: 'primary' },
    { l: 'Yetkazish daromadi', v: fmt(k.delivery_income), u: "so'm", s: 'Paid orderlar', tone: 'info' },
    { l: 'Kuryer payout', v: fmt(k.courier_payout), u: "so'm", s: 'Topshirilgan orderlar', tone: 'warning' },
    { l: 'Promo + cashback', v: fmt(k.promo_discount + k.cashback), u: "so'm", s: 'Chegirma xarajati', tone: 'danger' },
    { l: 'Chiqim + provider + soliq', v: fmt(k.manual_expenses + k.provider_fee + k.tax), u: "so'm", s: 'Marketplace xarajatlari', tone: 'secondary' },
    { l: 'Net marja', v: k.profit_margin.toFixed(1), u: '%', s: `Cancel ${k.cancellation_rate.toFixed(1)}%`, tone: k.profit_margin >= 0 ? 'success' : 'danger' },
  ];

  return (
    <div className="container-fluid py-4 px-3 px-xl-4">
      {/* ── Sarlavha: Axelit "main-title" + breadcrumb, o'ngda boshqaruv tugmalari ── */}
      <div className="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div className="d-flex align-items-center gap-3 min-w-0">
          <img className="h-45 w-45 b-r-10 flex-shrink-0" src="/favicon.svg" alt="" />
          <div className="min-w-0">
            <h4 className="main-title mb-0">Live Command Center</h4>
            <ul className="app-line-breadcrumbs mb-0">
              <li><a href="/boshqaruv" className="f-s-14 f-w-500" onClick={(e) => { e.preventDefault(); router.visit('/boshqaruv'); }}><span><i className="iconoir-home-alt f-s-16 align-text-top"></i> Boshqaruv</span></a></li>
              <li className="active"><a href="#" className="f-s-14 f-w-500" onClick={(e) => e.preventDefault()}>Live Dashboard</a></li>
            </ul>
          </div>
        </div>

        <div className="d-flex gap-2 align-items-center flex-wrap">
          <ul className="nav kc-segment mb-0" role="tablist" aria-label="Yangilanish tezligi">
            {[
              { label: 'x1', ms: 8000 },
              { label: 'x2', ms: 5000 },
              { label: 'x5', ms: 2000 },
            ].map((opt) => (
              <li className="nav-item" key={opt.ms}>
                <button type="button" className={`nav-link ${speed === opt.ms ? 'active' : ''}`} onClick={() => setSpeed(opt.ms)}>{opt.label}</button>
              </li>
            ))}
          </ul>
          <button type="button" className={`btn ${isPaused ? 'btn-light-success' : 'btn-light-warning'} icon-btn w-35 h-35 b-r-22`} onClick={() => setIsPaused(!isPaused)} title={isPaused ? 'Davom ettirish' : "To'xtatish"} aria-label={isPaused ? 'Davom ettirish' : "To'xtatish"}>
            <i className={`ti ${isPaused ? 'ti-player-play-filled' : 'ti-player-pause-filled'} f-s-18`}></i>
          </button>
          <span className="bg-white b-r-22 px-3 h-35 d-inline-flex align-items-center gap-2 f-w-600 text-dark">
            <i className="iconoir-clock f-s-18 text-primary"></i>{clock.toLocaleTimeString('uz-UZ')}
          </span>
          <button type="button" className="btn btn-light-secondary icon-btn w-35 h-35 b-r-22" onClick={() => setDarkMode((v) => !v)} title={darkMode ? "Yorug' rejim" : "Qorong'i rejim"} aria-label="Mavzuni almashtirish">
            <i className={`${darkMode ? 'iconoir-sun-light' : 'iconoir-half-moon'} f-s-18`}></i>
          </button>
          <button type="button" className="btn btn-light-secondary icon-btn w-35 h-35 b-r-22" onClick={goFull} title="To'liq ekran" aria-label="To'liq ekran">
            <i className="ti ti-maximize f-s-16"></i>
          </button>
          <button type="button" className="btn btn-primary icon-btn w-35 h-35 b-r-22" onClick={() => router.visit('/boshqaruv')} title="Boshqaruvga qaytish" aria-label="Boshqaruvga qaytish">
            <i className="iconoir-home-alt f-s-18"></i>
          </button>
        </div>
      </div>

      {/* Faqat xato bo'lsa — Axelit "alert-light-warning" */}
      {lastError ? (
        <div className="alert alert-light-warning d-flex align-items-center gap-2 mb-3" role="alert">
          <i className="iconoir-warning-triangle f-s-20"></i>
          <span className="f-w-600">Live ogohlantirish:</span>
          <span>{lastError}</span>
        </div>
      ) : null}

      {snapshot.financialRestricted ? (
        <div className="alert alert-light-secondary d-flex align-items-center gap-2 mb-3">
          <i className="iconoir-lock f-s-20"></i>
          <span>Sizning rolingizda moliyaviy ko'rsatkichlar (daromad, tushum, foyda) 0 qilib ko'rsatiladi — faqat operatsion sonlar (order, mijoz, hudud bo'yicha oqim) ochiq. Kerak bo'lsa, "Moliya" ruxsatiga ega admindan so'rang.</span>
        </div>
      ) : null}

      {/* ── KPI vidjetlari (Axelit e-commerce vidjetlari) ── */}
      <div className="row">
        {kpis.map((kpi) => (
          <div className="col-sm-6 col-md-4 col-xl-3" key={kpi.l}>
            <StatWidget
              variant={kpi.tone as StatVariant}
              label={kpi.l}
              help={liveKpiHelps[kpi.l]}
              value={<>{kpi.v} <small className="f-s-14 f-w-600">{kpi.u}</small></>}
            />
          </div>
        ))}
      </div>

      {/* ── Moliya — Axelit "Orders details" ro'yxati elementlari ── */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between">
          <h5 className="mb-0">Moliyaviy oqim</h5>
          <span className="badge text-light-primary">Tasdiqlangan savdolar</span>
        </div>
        <div className="card-body">
          <div className="row g-2">
            {finance.map((item) => (
              <div className="col-6 col-md-4 col-xl" key={item.l}>
                <div className={`bg-${item.tone}-300 b-r-15 p-3 h-100`}>
                  <h6 className={`text-${item.tone}-dark f-w-600 mb-1 d-flex align-items-center gap-1`}>
                    <span className="txt-ellipsis-1" title={item.l}>{item.l}</span>
                    <InfoHint text={liveFinanceHelps[item.l]} />
                  </h6>
                  <h5 className={`text-${item.tone}-dark mb-0 text-nowrap`}>{item.v}{item.u === '%' ? ' %' : ''}</h5>
                  <p className={`text-${item.tone}-dark mb-0 f-s-13 txt-ellipsis-1`} title={item.s}>{item.u === '%' ? item.s : `${item.u} · ${item.s}`}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* ── Oqim holatlari ── */}
      <div className="row">
        <StatusPanel title="Main orderlar" icon="ti-receipt" tone="primary" counts={snapshot.main_counts} labels={{ all: 'Jami', new: 'Yangi', packing: 'Qadoq', onway: "Yo'lda", arrived: 'Yetdi', done: 'Done', cancelled: 'Bekor' }} />
        <StatusPanel title="Seller fulfillment" icon="ti-building-store" tone="danger" counts={snapshot.seller_counts} labels={{ all: 'Jami', payment_pending: "To'lov", new: 'Yangi', accepted: 'Qabul', handover: 'Kuryerda', cancelled: 'Bekor' }} />
        <StatusPanel title="Kuryer fulfillment" icon="ti-bike" tone="success" counts={snapshot.courier_counts} labels={{ all: 'Jami', pending: 'Kutmoqda', in_delivery: "Yo'lda", delivered: 'Yetdi', customer_received: 'Qabul', rejected: 'Bekor' }} />
      </div>

      <div className="row">
        {/* Hududlar */}
        <div className="col-xl-4">
          <div className="card h-100">
            <div className="card-header d-flex align-items-center justify-content-between gap-2">
              <h5 className="mb-0 d-flex align-items-center gap-2">Hududlar bo'yicha oqim
                <InfoHint text="Buyurtma address snapshotidan viloyat/shahar nomi olinadi. Hozir O'zbekiston ichidagi real addresslar bo'yicha yig'iladi." />
              </h5>
              <span className="badge text-light-info">{fmt(regionTotalOrders)} ta</span>
            </div>
            <div className="card-body">
              <div className="row g-2 mb-3">
                <div className="col-7">
                  <div className="bg-primary-300 b-r-15 p-3 h-100">
                    <p className="text-primary-dark f-w-600 mb-1 f-s-13">Yetakchi hudud</p>
                    <h5 className="text-primary-dark mb-1 txt-ellipsis-1">{topRegion?.name || "Ma'lumot yo'q"}</h5>
                    <p className="text-primary-dark mb-0 f-s-12 txt-ellipsis-1">{topRegion ? `${fmt(topRegion.value)} order · ${fmt(topRegion.revenue)} so'm` : 'Address snapshot topilmadi'}</p>
                  </div>
                </div>
                <div className="col-5">
                  <div className="bg-danger-300 b-r-15 p-3 h-100">
                    <p className="text-danger-dark f-w-600 mb-1 f-s-13">Jami tushum</p>
                    <h5 className="text-danger-dark mb-1 txt-ellipsis-1">{fmt(regionTotalRevenue)}</h5>
                    <p className="text-danger-dark mb-0 f-s-12">so'm</p>
                  </div>
                </div>
              </div>

              <ul className="customer-list app-scroll overflow-auto pe-1" style={{ maxHeight: 330 }}>
                {snapshot.regions.map((region, index) => {
                  const orderShare = regionTotalOrders > 0 ? region.value / regionTotalOrders * 100 : 0;
                  const revenueShare = regionTotalRevenue > 0 ? region.revenue / regionTotalRevenue * 100 : 0;
                  const tone = toneAt(index);
                  return (
                    <li className="customer-list-item align-items-start" key={region.name}>
                      <span className={`text-light-${tone} f-w-600 h-35 w-35 d-flex-center b-r-50 customer-list-avtar`}>{index + 1}</span>
                      <div className="customer-list-content flex-grow-1 min-w-0">
                        <div className="d-flex justify-content-between gap-2">
                          <h6 className="mb-0 txt-ellipsis-1 f-s-15">{region.name}</h6>
                          <span className="f-w-600 text-dark f-s-14 text-nowrap">{fmt(region.value)} ta</span>
                        </div>
                        <div className="progress my-1" role="progressbar" aria-valuenow={Math.round(orderShare)} aria-valuemin={0} aria-valuemax={100}>
                          <div className={`progress-bar bg-${tone}`} style={{ width: `${Math.max(4, orderShare)}%` }}></div>
                        </div>
                        <p className="mb-0 f-s-12 text-secondary">{fmt(region.revenue)} so'm · {revenueShare.toFixed(1)}% tushum</p>
                      </div>
                    </li>
                  );
                })}
                {snapshot.regions.length === 0 ? <li className="text-secondary f-s-14">Hududlar bo'yicha ma'lumot yo'q.</li> : null}
              </ul>
            </div>
          </div>
        </div>

        {/* Savdo trendi */}
        <div className="col-xl-5">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="mb-0 d-flex align-items-center gap-2">Bugungi savdo trendi
                <InfoHint text="Bugun yakunlangan savdolar qabul qilingan soati bo'yicha guruhlanadi. Revenue - shu soatdagi yakuniy tushum, orders - yakuniy savdo soni." />
              </h5>
              <span className="badge text-light-success">{snapshot.generated_at}</span>
            </div>
            <div className="card-body">
              <div className="d-flex gap-3 mb-2 f-s-13 f-w-500">
                <span className="d-inline-flex align-items-center gap-1"><span className="d-inline-block h-10 w-10 b-r-50" style={{ background: palette.indigo }}></span>Tushum</span>
                <span className="d-inline-flex align-items-center gap-1"><span className="d-inline-block h-10 w-10 b-r-50" style={{ background: palette.green }}></span>Buyurtmalar</span>
              </div>
              <ResponsiveContainer width="100%" height={280}>
                <AreaChart data={snapshot.chart} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                  <defs>
                    <linearGradient id="liveRevenue" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor={palette.indigo} stopOpacity={0.35} />
                      <stop offset="100%" stopColor={palette.indigo} stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="liveOrders" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor={palette.green} stopOpacity={0.3} />
                      <stop offset="100%" stopColor={palette.green} stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="hour" interval={3} tickLine={false} axisLine={false} />
                  {/* Tushum va order soni har xil o'lchovda — alohida (yashirin) o'qlar */}
                  <YAxis yAxisId="revenue" hide />
                  <YAxis yAxisId="orders" hide orientation="right" allowDecimals={false} />
                  <Tooltip formatter={(value: number, name) => (name === 'revenue' ? [`${fmt(value)} so'm`, 'Tushum'] : [fmt(value), 'Buyurtmalar'])} />
                  <Area yAxisId="revenue" type="monotone" dataKey="revenue" stroke={palette.indigo} strokeWidth={2} fill="url(#liveRevenue)" />
                  <Area yAxisId="orders" type="monotone" dataKey="orders" stroke={palette.green} strokeWidth={2} fill="url(#liveOrders)" />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Alertlar — Axelit "Orders details" vidjeti */}
        <div className="col-xl-3">
          <div className="card order-detail-card h-100">
            <div className="pt-3">
              <h5 className="pa-s-20 mb-0">Alertlar</h5>
            </div>
            <div className="card-body">
              <ul className="order-content-list">
                {snapshot.alerts.length ? snapshot.alerts.map((alert) => {
                  const tone = alert.level === 'danger' ? 'danger' : alert.level === 'warning' ? 'warning' : alert.level === 'success' ? 'success' : 'info';
                  return (
                    <li className={`bg-${tone}-300`} key={alert.title}>
                      <a href={alert.url || '#'} className="d-block">
                        <h6 className={`text-${tone}-dark f-w-600 mb-0`}><i className={`${tiIcon(alert.icon)} me-1`}></i>{alert.title}</h6>
                        <p className={`text-${tone}-dark mb-0 txt-ellipsis-2 f-s-13`}>{alert.text}</p>
                      </a>
                    </li>
                  );
                }) : (
                  <li className="bg-success-300">
                    <h6 className="text-success-dark f-w-600 mb-0"><i className="ti ti-circle-check me-1"></i>Hammasi joyida</h6>
                    <p className="text-success-dark mb-0 f-s-13">Hozircha kritik ogohlantirish yo'q.</p>
                  </li>
                )}
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div className="row">
        {/* Jonli feed — Axelit "top-products-table" */}
        <div className="col-xl-5">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Jonli operatsion feed</h5>
              <span className="badge text-light-primary">{feed.length} ta</span>
            </div>
            <div className="card-body px-0 pb-2">
              <div className="table-responsive app-scroll" style={{ maxHeight: 440 }}>
                <table className="table align-middle top-products-table mb-0">
                  <thead>
                    <tr>
                      <th scope="col">Operatsiya</th>
                      <th scope="col" className="text-end">Summa</th>
                      <th scope="col" className="text-end">Holat</th>
                    </tr>
                  </thead>
                  <tbody>
                    {feed.map((row) => (
                      <tr key={`${row.kind}-${row.id}`}>
                        <td>
                          <a href={row.url || '#'} className="d-flex align-items-center gap-2">
                            <span className={`h-35 w-35 d-flex-center b-r-50 flex-shrink-0 text-light-${row.kind === 'Seller' ? 'danger' : row.kind === 'Kuryer' ? 'success' : 'primary'}`}><i className={`${tiIcon(row.icon)}`}></i></span>
                            <span className="min-w-0">
                              <h6 className="mb-0 f-s-14">{row.kind} #{row.id}</h6>
                              <span className="d-block f-s-12 text-secondary txt-ellipsis-1">{row.customer || row.seller || row.courier || row.title} · {row.updated_at || ''}</span>
                            </span>
                          </a>
                        </td>
                        <td className="text-end text-dark f-w-600 text-nowrap">{fmt(row.amount)} so'm</td>
                        <td className="text-end"><span className={`badge text-light-${statusTone(row.status_code || row.status)}`}>{row.status}</span></td>
                      </tr>
                    ))}
                    {feed.length === 0 ? (
                      <tr><td colSpan={3} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hozircha operatsiya yo'q</td></tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {/* Top mahsulotlar — Axelit "Customer" ro'yxati */}
        <div className="col-xl-3">
          <div className="card h-100">
            <div className="card-header d-flex align-items-center gap-2">
              <h5 className="mb-0">Top mahsulotlar</h5>
              <InfoHint text="To'langan order itemlaridan eng ko'p sotilgan mahsulotlar. Gift sovg'alar bu ro'yxatga qo'shilmaydi." />
            </div>
            <div className="card-body">
              <ul className="customer-list app-scroll overflow-auto" style={{ maxHeight: 440 }}>
                {snapshot.top_products.map((product, index) => (
                  <li className="customer-list-item gap-2" key={`${product.name}-${index}`}>
                    <span className={`text-light-${toneAt(index)} f-w-600 h-35 w-35 d-flex-center b-r-50 customer-list-avtar`}>{index + 1}</span>
                    <div className="customer-list-content min-w-0">
                      <h6 className="mb-0 txt-ellipsis-1 f-s-15">{product.name}</h6>
                      <p className="mb-0 f-s-12 text-secondary">{fmt(product.quantity)} ta sotildi</p>
                    </div>
                    <span className="f-w-600 text-dark f-s-14 text-nowrap ms-auto">{fmt(product.revenue)} so'm</span>
                  </li>
                ))}
                {snapshot.top_products.length === 0 ? <li className="text-secondary f-s-14">Ma'lumot yo'q.</li> : null}
              </ul>
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="row">
            <div className="col-md-6">
              <SplitPanel title="To'lov holati" help="Orderlar to'lov holati bo'yicha guruhlanadi. Foiz jami order ichidagi ulush." rows={snapshot.payment_split.map((row) => ({ name: row.name, value: row.share, meta: `${fmt(row.count)} ta` }))} percent />
            </div>
            <div className="col-md-6">
              <SplitPanel title="Yetkazish turi" help="Buyurtmalar delivery turi bo'yicha ajratiladi. Yonidagi summa shu turdagi orderlar tushumi." rows={snapshot.delivery_split.map((row) => ({ name: row.name, value: row.count, meta: `${fmt(row.revenue)} so'm` }))} />
            </div>
            <div className="col-12">
              <div className="card">
                <div className="card-header"><h5 className="mb-0">Online mijozlar</h5></div>
                <div className="card-body">
                  <ul className="customer-list">
                    {snapshot.online_users.length ? snapshot.online_users.map((user, index) => (
                      <li className="customer-list-item gap-2" key={user.id}>
                        <PAvatar src={user.avatar} name={user.name} size="sm" className="customer-list-avtar" />
                        <div className="customer-list-content min-w-0">
                          <h6 className="mb-0 txt-ellipsis-1 f-s-15">{user.name}</h6>
                          <p className="mb-0 f-s-12 text-secondary">{user.last_seen}</p>
                        </div>
                        <span className="bg-success h-10 w-10 b-r-50 d-inline-block flex-shrink-0 ms-auto"></span>
                      </li>
                    )) : <li className="text-secondary f-s-14">So'nggi 5 daqiqada online mijoz topilmadi.</li>}
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function StatusPanel({ title, icon, tone, counts, labels }: { title: string; icon: string; tone: string; counts: CountMap; labels: Record<string, string> }) {
  return (
    <div className="col-xl-4">
      <div className="card">
        <div className="card-header d-flex align-items-center gap-2">
          <span className={`h-35 w-35 d-flex-center b-r-50 text-light-${tone} flex-shrink-0`}><i className={`${tiIcon(icon)}`}></i></span>
          <h5 className="mb-0">{title}</h5>
        </div>
        <div className="card-body">
          <div className="row g-2">
            {Object.entries(labels).map(([key, label]) => (
              <div className="col-6 col-sm-3" key={key}>
                <div className={`b-r-15 p-2 px-3 h-100 ${key === 'all' ? `bg-${tone}-300` : 'b-1-light'}`}>
                  <span className={`d-block f-s-13 txt-ellipsis-1 ${key === 'all' ? `text-${tone}-dark` : 'text-secondary'}`}>{label}</span>
                  <h5 className={`mb-0 text-nowrap ${key === 'all' ? `text-${tone}-dark` : ''}`}>{fmt(counts[key] || 0)}</h5>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function SplitPanel({ title, rows, help, percent }: { title: string; rows: { name: string; value: number; meta: string }[]; help?: string; percent?: boolean }) {
  const total = rows.reduce((sum, row) => sum + row.value, 0) || 1;
  return (
    <div className="card">
      <div className="card-header d-flex align-items-center gap-2">
        <h5 className="mb-0">{title}</h5>
        {help ? <InfoHint text={help} /> : null}
      </div>
      <div className="card-body">
        {rows.length ? rows.map((row, index) => {
          const width = percent && row.value <= 100 ? row.value : row.value / total * 100;
          return (
            <div key={row.name} className="mb-3">
              <div className="d-flex justify-content-between gap-2 f-s-13 f-w-500 mb-1">
                <span className="text-dark txt-ellipsis-1">{row.name}</span>
                <span className="text-secondary text-nowrap">{row.meta}</span>
              </div>
              <div className="progress" role="progressbar" aria-valuenow={Math.round(width)} aria-valuemin={0} aria-valuemax={100}>
                <div className={`progress-bar bg-${toneAt(index)}`} style={{ width: `${Math.max(3, width)}%` }}></div>
              </div>
            </div>
          );
        }) : <div className="text-secondary f-s-14">Ma'lumot yo'q.</div>}
      </div>
    </div>
  );
}
