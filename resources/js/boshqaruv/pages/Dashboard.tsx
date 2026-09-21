import { usePalette, categoryColor } from '../utils/palette';
import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import InfoHint from '../components/InfoHint';

import { StatWidget, MiniStat, EmptyState, type Tone } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));
const money = (n: number) => `${fmt(n)} so'm`;
const compact = (n: number) => new Intl.NumberFormat('uz-UZ', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(n) || 0);

interface DashboardPayload {
  generatedAt: string;
  exportUrl?: string;
  financialRestricted?: boolean;
  metrics: Record<string, number>;
  periods: {
    current?: { revenue: number; orders: number; users: number; aov: number };
    previous?: { revenue: number; orders: number; users: number; aov: number } | null;
  };
  range: { key: string; label: string; from?: string | null; to?: string | null; canCompare: boolean };
  financial: Record<string, number>;
  business: Record<string, number>;
  status: { main: Record<string, number>; seller: Record<string, number>; courier: Record<string, number> };
  salesByMonth: Array<{ month: string; revenue: number; profit: number; orders: number }>;
  salesTrend: { label: string; granularity: string };
  hourlySales: Array<{ hour: string; revenue: number; orders: number }>;
  categoryShare: Array<{ name: string; value: number; revenue: number; color: string }>;
  topProducts: Array<{ name: string; quantity: number; revenue: number }>;
  recentOrders: Array<{ id: number; customer?: string; amount: number; status: string; updated_at?: string; url?: string }>;
  paymentSplit: Array<{ name: string; count: number; share: number; color: string }>;
  deliverySplit: Array<{ name: string; count: number; revenue: number }>;
  regions: Array<{ name: string; value: number; revenue: number; color: string }>;
  platformAnalysis: Array<{ name: string; icon: string; color: string; version: string; activeUsers: number; orders: number; revenue: number; conversion: number; crashRate: number; avgSessionSeconds: number }>;
  funnel: { stages: Array<{ key: string; label: string; value: number; rate: number; drop: number; color: string }>; uniqueViewers: number; cartUsers: number; viewToPaid: number; hasViewData: boolean };
  retention: { cohorts: Array<{ month: string; size: number; retention: Array<number | null> }>; maxOffset: number };
  sellerScorecard: Array<{ id: number; name: string; avatar?: string | null; orders: number; revenue: number; cancelled: number; cancelRate: number; acceptMinutes: number | null; rating: number; ratingCount: number; reputation: number; url?: string }>;
  unitEconomics: {
    newBuyers: number; totalBuyers: number; marketingSpend: number; totalOpex: number;
    cac: number; blendedCac: number; arpu: number; ltv: number; ltvCacRatio: number;
    contributionPerOrder: number; grossPerOrder: number; marginPct: number;
    refundAmount: number; refundOrders: number; refundRate: number;
    cancelRate: number; repeatRate: number; repeatBuyers: number; paybackOrders: number; hasMarketingData: boolean;
  };
  unitEconomicsMonthly: {
    hasData: boolean;
    months: Array<{
      month: string; grossRevenue: number; contribution: number; platformProfit: number;
      commission: number; deliveryIncome: number; promoDiscount: number; collectionDiscount: number;
      cashback: number; courierPayout: number; manualExpenses: number; providerFee: number; tax: number;
      marketingSpend: number; paidOrders: number; totalBuyers: number; newBuyers: number; repeatBuyers: number;
      repeatRate: number; cac: number | null; cacPaybackOrders: number | null; arpu: number;
      grossPerOrder: number; contributionPerOrder: number; marginPct: number;
      refundAmount: number; refundOrders: number; refundRate: number; cancelRate: number;
    }>;
  };
  partnerEconomics: {
    hasData: boolean; hasMarketingData: boolean; currentPartners: number; currentMrr: number;
    summary: {
      arpc: number; cac: number | null; cacPaybackMonths: number | null;
      churnRateMrr: number; churnRateCount: number;
      grossRetention: number | null; netRetention: number | null;
      lifetimeMonths: number | null; ltv: number | null; ltvCacRatio: number | null;
    };
    months: Array<{
      month: string; mrr: number; newMrr: number; churnedMrr: number;
      currentPartners: number; newPartners: number; churnedPartners: number; marketingSpend: number;
      arpc: number; cac: number | null; cacPaybackMonths: number | null;
      churnRateMrr: number; churnRateCount: number;
      grossRetention: number | null; netRetention: number | null;
      lifetimeMonths: number | null; ltv: number | null; ltvCacRatio: number | null;
    }>;
  };
  exportUrl?: string;
  alerts: Array<{ level: string; icon: string; title: string; text: string; url?: string }>;
}

const emptyDashboard: DashboardPayload = {
  generatedAt: '--',
  metrics: {},
  periods: {},
  range: { key: 'month', label: 'Joriy oy', canCompare: true },
  financial: {},
  business: {},
  status: { main: {}, seller: {}, courier: {} },
  salesByMonth: [],
  salesTrend: { label: 'Joriy oy', granularity: 'Kunlik' },
  hourlySales: [],
  categoryShare: [],
  topProducts: [],
  recentOrders: [],
  paymentSplit: [],
  deliverySplit: [],
  regions: [],
  platformAnalysis: [],
  funnel: { stages: [], uniqueViewers: 0, cartUsers: 0, viewToPaid: 0, hasViewData: false },
  retention: { cohorts: [], maxOffset: 5 },
  sellerScorecard: [],
  unitEconomics: {
    newBuyers: 0, totalBuyers: 0, marketingSpend: 0, totalOpex: 0,
    cac: 0, blendedCac: 0, arpu: 0, ltv: 0, ltvCacRatio: 0,
    contributionPerOrder: 0, grossPerOrder: 0, marginPct: 0,
    refundAmount: 0, refundOrders: 0, refundRate: 0,
    cancelRate: 0, repeatRate: 0, repeatBuyers: 0, paybackOrders: 0, hasMarketingData: false,
  },
  unitEconomicsMonthly: { hasData: false, months: [] },
  partnerEconomics: {
    hasData: false, hasMarketingData: false, currentPartners: 0, currentMrr: 0,
    summary: {
      arpc: 0, cac: null, cacPaybackMonths: null,
      churnRateMrr: 0, churnRateCount: 0,
      grossRetention: null, netRetention: null,
      lifetimeMonths: null, ltv: null, ltvCacRatio: null,
    },
    months: [],
  },
  alerts: [],
};

const change = (current = 0, previous = 0) => previous > 0 ? ((current - previous) / previous * 100) : (current > 0 ? 100 : 0);

const metricHelps: Record<string, string> = {
  orders: "Bazadagi barcha asosiy buyurtmalar soni. Statusidan qat'i nazar jami orderlar sanaladi.",
  paidOrders: "Mijoz qabul qilgan va to'lovi tasdiqlangan yakuniy savdolar soni. Moliyaviy hisoblar shu orderlardan olinadi.",
  users: "Platformada ro'yxatdan o'tgan jami foydalanuvchilar soni.",
  books: "Kitob katalogidagi mahsulotlar soni.",
  sellers: "Marketplace sotuvchilari soni. Faol, kutilmoqda va bekor qilinganlar umumiy sanaladi.",
  couriers: "Tizimdagi kuryerlar soni.",
  premiumUsers: "Premium belgisi bor foydalanuvchilar soni.",
  onlineUsers: "So'nggi bir necha daqiqada aktiv bo'lgan foydalanuvchilar.",
  stationeries: "Kanselyariya katalogidagi mahsulotlar soni.",
  pendingSellers: "Hali tasdiq kutayotgan sellerlar soni.",
  tickets: "Support/ticket bo'limidagi murojaatlar soni.",
  complaints: "Shikoyatlar bo'limidagi yozuvlar soni.",
};

const periodHelps: Record<string, string> = {
  revenue: "Tanlangan davrda mijoz qabul qilgan va to'lovi tasdiqlangan savdolarning umumiy summasi. Foiz teng uzunlikdagi oldingi davr bilan solishtiriladi.",
  orders: "Tanlangan davrda mijoz qabul qilgan va to'lovi tasdiqlangan savdolar soni. Foiz teng uzunlikdagi oldingi davr bilan solishtiriladi.",
  aov: "O'rtacha chek: tanlangan davrdagi yakuniy savdo tushumi yakuniy savdolar soniga bo'linadi.",
  users: "Tanlangan davrda yangi ro'yxatdan o'tgan foydalanuvchilar soni.",
};

const businessHelps: Record<string, string> = {
  'Yakuniy savdo ulushi': "Jami buyurtmalardan nechtasi mijoz tomonidan qabul qilingan va to'lovi tasdiqlangan yakuniy savdoga aylanganini ko'rsatadi.",
  'Yakunlash ulushi': "Jami buyurtmalardan yakunlanganlari ulushi. Admin operatsiya sifati uchun signal.",
  'Bekor ulushi': "Bekor qilingan yoki qaytgan buyurtmalar ulushi. Ko'paysa logistika yoki mahsulot tomoni tekshiriladi.",
  'Qayta xaridor': "Bir martadan ko'p xarid qilgan foydalanuvchilar ulushi.",
  'Xaridorlar': "Kamida bitta to'langan buyurtma qilgan foydalanuvchilar.",
  'Karta ulangan': "Tizimda tasdiqlangan karta bog'lagan foydalanuvchilar.",
  "Order / xaridor": "To'langan buyurtmalar soni xaridorlar soniga bo'linadi.",
  "Daromad / xaridor": "To'langan buyurtmalar tushumi xaridorlar soniga bo'linadi.",
};

const financialHelps: Record<string, string> = {
  'Yakuniy savdo tushumi': "Mijoz qabul qilgan va to'lovi tasdiqlangan savdolarning umumiy summasi. Bu hali sof foyda emas.",
  'Delivery income': "Mijoz to'lagan yetkazish pullari yig'indisi.",
  'Seller commission': "Sellerdan ushlanadigan marketplace komissiyasi. Sellerda alohida komissiya bo'lsa o'sha, bo'lmasa settingsdagi global foiz ishlaydi.",
  'Promo discount': "Promokod yoki aksiyalar sabab platforma tomondan berilgan chegirmalar.",
  'Cashback': "Foydalanuvchiga qaytarilgan cashback summasi.",
  'Courier payout': "Kuryerga to'lanadigan yetkazish xarajatlari.",
  'Kiritilgan chiqimlar': "Chiqimlar bo'limiga admin kiritgan real xarajatlar: reklama, servis, qadoq, operatsiya va hokazo.",
  'Provider komissiyasi': "Payment provider ushlaydigan komissiya. Settingsdagi foiz asosida hisoblanadi.",
  'Soliq': "Settingsdagi soliq sozlamasi bo'yicha hisoblanadi: so'm yoki foydadan foiz.",
  'Marketplace marjasi': "Taxminiy net signal: seller komissiya + delivery income - promo - cashback - kuryer - chiqim - provider - soliq.",
};


// Axelit ro'yxatlaridagi rang navbati
const DASH_TONES = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

export default function Dashboard() {
  const palette = usePalette();
  const { dashboard = emptyDashboard } = usePage<{ dashboard?: DashboardPayload }>().props;
  const [chartMetric, setChartMetric] = useState<'revenue' | 'profit' | 'orders'>('revenue');
  const [showCustomRange, setShowCustomRange] = useState(dashboard.range.key === 'custom');
  const [customFrom, setCustomFrom] = useState(dashboard.range.from || '');
  const [customTo, setCustomTo] = useState(dashboard.range.to || '');
  const [isFiltering, setIsFiltering] = useState(false);
  const current = dashboard.periods.current || { revenue: 0, orders: 0, users: 0, aov: 0 };
  const previous = dashboard.periods.previous || null;
  const localNow = new Date();
  const today = `${localNow.getFullYear()}-${String(localNow.getMonth() + 1).padStart(2, '0')}-${String(localNow.getDate()).padStart(2, '0')}`;

  const selectPeriod = (dashboardPeriod: string, from?: string, to?: string) => {
    setIsFiltering(true);
    router.get('/boshqaruv', {
      dashboard_period: dashboardPeriod,
      ...(dashboardPeriod === 'custom' ? { dashboard_from: from, dashboard_to: to } : {}),
    }, {
      only: ['dashboard'],
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onFinish: () => setIsFiltering(false),
    });
  };

  const applyCustomRange = () => {
    if (!customFrom || !customTo) return;
    selectPeriod('custom', customFrom, customTo);
  };

  // Excel eksport joriy davr bilan bir xil bo'lishi uchun period paramlarini qo'shamiz.
  const exportHref = (exportType: 'investor' | 'dashboard' | 'unit' = 'investor') => {
    if (!dashboard.exportUrl) return '#';
    const params = new URLSearchParams({ dashboard_period: dashboard.range.key, export_type: exportType });
    if (dashboard.range.key === 'custom' && dashboard.range.from && dashboard.range.to) {
      params.set('dashboard_from', dashboard.range.from);
      params.set('dashboard_to', dashboard.range.to);
    }
    return `${dashboard.exportUrl}?${params.toString()}`;
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Boshqaruv dashboard</h4>
        </div>
        <div className="d-flex gap-2 align-items-center flex-wrap justify-content-end">
          <span className="badge text-light-info"><i className="ti ti-calendar me-1"></i>{dashboard.range.label}</span>
          <div className="btn-group btn-group-sm" aria-label="Dashboard davri">
            {([
              ['today', 'Bugun'],
              ['week', 'Hafta'],
              ['month', 'Oy'],
              ['year', 'Yil'],
              ['all', 'Barchasi'],
            ] as const).map(([key, label]) => (
              <button key={key} disabled={isFiltering} className={`btn ${dashboard.range.key === key ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => selectPeriod(key)}>
                {label}
              </button>
            ))}
            <button disabled={isFiltering} className={`btn ${dashboard.range.key === 'custom' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setShowCustomRange((value) => !value)}>
              <i className="ti ti-calendar-stats me-1"></i>Sana
            </button>
          </div>
          {dashboard.exportUrl ? (
            <div className="d-flex align-items-center gap-1">
              <div className="btn-group btn-group-sm">
                <a href={exportHref('investor')} className="btn btn-outline-success" title="To'liq investor paketi: assumptions, unit economics, P&L, sales trend, sellers va h.k. — bir nechta varaqda.">
                  <i className="ti ti-file-spreadsheet me-1"></i>Excel export
                </a>
                <button type="button" className="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                  <span className="visually-hidden">Export turlari</span>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                  <li><a className="dropdown-item" href={exportHref('investor')}>
                    <i className="ti ti-sparkles me-2"></i>Investor pack · multi-sheet
                    <small className="d-block text-muted ms-4">Barcha varaqlar: summary, unit econ, P&L, trend, sellers</small>
                  </a></li>
                  <li><hr className="dropdown-divider" /></li>
                  <li><a className="dropdown-item" href={exportHref('unit')}>
                    <i className="ti ti-calculator me-2"></i>Unit economics + Partners MRR
                    <small className="d-block text-muted ms-4">Xaridor CAC/LTV va hamkorlar (premium) MRR/churn, oylik</small>
                  </a></li>
                  <li><hr className="dropdown-divider" /></li>
                  <li><a className="dropdown-item" href={exportHref('dashboard')}>
                    <i className="ti ti-table me-2"></i>Dashboard snapshot
                    <small className="d-block text-muted ms-4">Ekrandagi asosiy KPI'lar — tezkor umumiy jadval</small>
                  </a></li>
                </ul>
              </div>
              <InfoHint text="Excel fayl joriy tanlangan davr (yuqoridagi filtr) bilan bir xil ma'lumotdan generatsiya qilinadi — ekrandagi raqamlar bilan aynan mos keladi. Katta marketpleyslar kabi: bir tugma, tayyor .xlsx." />
            </div>
          ) : null}
          <Link href="/boshqaruv/live" className="btn btn-outline-secondary btn-sm"><i className="ti ti-broadcast me-1"></i>Live</Link>
        </div>
      </div>

      {dashboard.financialRestricted ? (
        <div className="alert alert-light-secondary d-flex align-items-center gap-2 mb-3">
          <i className="ti ti-lock"></i>
          <div>Sizning rolingizda moliyaviy ko'rsatkichlar (daromad, marja, komissiya, CAC/LTV va h.k.) ko'rsatilmaydi — faqat operatsion sonlar (buyurtma, foydalanuvchi soni va h.k.) ochiq. Kerak bo'lsa, "Moliya" ruxsatiga ega admindan so'rang.</div>
        </div>
      ) : null}

      {showCustomRange ? (
        <div className="card">
<div className="card-body py-2 px-3">
            <div className="d-flex align-items-end gap-2 flex-wrap">
              <label className="f-s-13 text-muted">
                <span className="d-block mb-1">Boshlanish</span>
                <input className="form-control form-control-sm" type="date" max={customTo || today} value={customFrom} onChange={(event) => setCustomFrom(event.target.value)} />
              </label>
              <label className="f-s-13 text-muted">
                <span className="d-block mb-1">Tugash</span>
                <input className="form-control form-control-sm" type="date" min={customFrom || undefined} max={today} value={customTo} onChange={(event) => setCustomTo(event.target.value)} />
              </label>
              <button className="btn btn-primary btn-sm" disabled={!customFrom || !customTo || isFiltering} onClick={applyCustomRange}>
                {isFiltering ? <span className="spinner-border spinner-border-sm me-1"></span> : <i className="ti ti-filter me-1"></i>}
                Ko'rsatish
              </button>
              <small className="text-muted ms-auto">Savdo sanasi mijoz buyurtmani qabul qilgan vaqt bo'yicha olinadi.</small>
            </div>
          </div>
</div>
      ) : null}

      <div className="row">
        <Metric index={0} label="Buyurtmalar" value={dashboard.metrics.orders} icon="ti-receipt" href="/boshqaruv/orders" help={metricHelps.orders} />
        <Metric index={1} label="Yakuniy savdo" value={dashboard.metrics.paidOrders} icon="ti-credit-card" href="/boshqaruv/orders" help={metricHelps.paidOrders} />
        <Metric index={2} label="Foydalanuvchilar" value={dashboard.metrics.users} icon="ti-users" href="/boshqaruv/users" help={metricHelps.users} />
        <Metric index={3} label="Kitoblar" value={dashboard.metrics.books} icon="ti-book" href="/boshqaruv/books" help={metricHelps.books} />
        <Metric index={4} label="Sotuvchilar" value={dashboard.metrics.sellers} icon="ti-building-store" href="/boshqaruv/sellers" help={metricHelps.sellers} />
        <Metric index={5} label="Kuryerlar" value={dashboard.metrics.couriers} icon="ti-bike" href="/boshqaruv/couriers" help={metricHelps.couriers} />
        <Metric index={6} label="Premium user" value={dashboard.metrics.premiumUsers} icon="ti-sparkles" href="/boshqaruv/users" help={metricHelps.premiumUsers} />
        <Metric index={7} label="Online user" value={dashboard.metrics.onlineUsers} icon="ti-broadcast" href="/boshqaruv/users" help={metricHelps.onlineUsers} />
        <Metric index={8} label="Kanselyariya" value={dashboard.metrics.stationeries} icon="ti-edit" href="/boshqaruv/stationeries" help={metricHelps.stationeries} />
        <Metric index={9} label="Pending seller" value={dashboard.metrics.pendingSellers} icon="ti-hourglass" href="/boshqaruv/sellers" help={metricHelps.pendingSellers} />
        <Metric index={10} label="Support ticket" value={dashboard.metrics.tickets} icon="ti-headset" href="/boshqaruv/tickets" help={metricHelps.tickets} />
        <Metric index={11} label="Shikoyatlar" value={dashboard.metrics.complaints} icon="ti-alert-triangle" href="/boshqaruv/shikoyatlar" help={metricHelps.complaints} />
      </div>

      <div className="row">
        <PeriodCard index={0} label="Daromad" value={money(current.revenue)} delta={previous ? change(current.revenue, previous.revenue) : null} icon="ti-coins" help={periodHelps.revenue} />
        <PeriodCard index={1} label="Yakuniy savdolar" value={fmt(current.orders)} delta={previous ? change(current.orders, previous.orders) : null} icon="ti-shopping-bag" help={periodHelps.orders} />
        <PeriodCard index={2} label="O'rtacha chek" value={money(current.aov)} delta={previous ? change(current.aov, previous.aov) : null} icon="ti-receipt" help={periodHelps.aov} />
        <PeriodCard index={3} label="Yangi userlar" value={fmt(current.users)} delta={previous ? change(current.users, previous.users) : null} icon="ti-user-plus" help={periodHelps.users} />
      </div>

      <UnitEconomics data={dashboard.unitEconomics} monthly={dashboard.unitEconomicsMonthly} />

      <PartnerEconomics data={dashboard.partnerEconomics} />

      <div className="row">
        <div className="col-xl-8">
          <div className="card h-100">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <div className="d-flex align-items-center gap-2">
                  <h5 className="f-w-600">{dashboard.salesTrend.label} savdo trendi</h5>
                  <InfoHint text="Grafik tanlangan davr uzunligiga qarab soatlik, kunlik, oylik yoki yillik bo'linadi. Daromad va order faqat yakuniy savdolardan olinadi; Signal tanlangan davr P&L marjasining vaqt nuqtalariga mutanosib taqsimotidir." />
                </div>
                <p className="mb-0 text-secondary">{dashboard.salesTrend.granularity} · yakuniy savdolar va platform signal</p>
              </div>
              <div className="btn-group btn-group-sm">
                <button className={`btn ${chartMetric === 'revenue' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('revenue')}>Daromad</button>
                <button className={`btn ${chartMetric === 'profit' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('profit')}>Signal</button>
                <button className={`btn ${chartMetric === 'orders' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('orders')}>Order</button>
              </div>
            </div>
<div className="card-body">

              <div className="app-scroll overflow-x-auto overflow-y-hidden">
                <div style={{ minWidth: Math.max(720, dashboard.salesByMonth.length * 54) }}>
                  <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={dashboard.salesByMonth} margin={{ left: 4, right: 18, top: 8, bottom: 0 }}>
                      <defs>
                        <linearGradient id="dashRevenue" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stopColor={palette.indigo} stopOpacity={0.45} />
                          <stop offset="100%" stopColor={palette.indigo} stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                      <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} interval={0} minTickGap={10} />
                      <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={54} tickFormatter={(value) => chartMetric === 'orders' ? fmt(Number(value)) : compact(Number(value))} />
                      <Tooltip formatter={(value: number) => chartMetric === 'orders' ? fmt(value) : money(value)} />
                      <Area type="monotone" dataKey={chartMetric} stroke={chartMetric === 'revenue' ? palette.indigo : chartMetric === 'profit' ? palette.green : palette.amber} fill={chartMetric === 'revenue' ? 'url(#dashRevenue)' : 'transparent'} strokeWidth={2} />
                    </AreaChart>
                  </ResponsiveContainer>
                </div>
              </div>
            </div>
</div>
        </div>

        <div className="col-xl-4">
          <div className="card h-100">
<div className="card-header d-flex align-items-center gap-2">
                <h5 className="f-w-600">Kategoriya bo'yicha savdo · {dashboard.range.label}</h5>
              <InfoHint text="To'langan order itemlari kategoriya bo'yicha guruhlanadi. Kitoblar o'z categorylari bilan chiqadi, kanselyariya esa ichki bo'limlariga bo'linmay bitta Kanselyariya sifatida hisoblanadi. Gift sovg'alar kirmaydi." />
            </div>
<div className="card-body">

              {dashboard.categoryShare.length ? (
                <>
                  <ResponsiveContainer width="100%" height={190}>
                    <PieChart>
                      <Pie data={dashboard.categoryShare} dataKey="value" innerRadius={52} outerRadius={78} paddingAngle={3}>
                        {dashboard.categoryShare.map((row, index) => <Cell key={row.name} fill={categoryColor(palette, index)} />)}
                      </Pie>
                      <Tooltip formatter={(value: number) => `${value}%`} />
                    </PieChart>
                  </ResponsiveContainer>
                  <ul className="list-group list-group-flush">
                    {dashboard.categoryShare.map((row, index) => (
                      <li className="list-group-item d-flex align-items-center justify-content-between gap-3 px-0" key={row.name}>
                        <span className="d-flex align-items-center gap-2 min-w-0 text-secondary"><span className="d-inline-block h-10 w-10 b-r-50 flex-shrink-0" style={{ background: categoryColor(palette, index) }}></span><span className="text-truncate">{row.name}</span></span>
                        <strong className="text-nowrap text-dark">{row.value}% · {money(row.revenue)}</strong>
                      </li>
                    ))}
                  </ul>
                </>
              ) : <EmptyState text="To'langan order itemlari hali topilmadi." />}
            </div>
</div>
        </div>
      </div>

      <div className="row">
        <FinancialPanel dashboard={dashboard} />
        <StatusPanel title="Main orderlar" counts={dashboard.status.main} labels={{ all: 'Jami', new: 'Yangi', packing: 'Qadoq', onway: "Yo'lda", done: 'Done', cancelled: 'Bekor' }} />
        <StatusPanel title="Seller orderlar" counts={dashboard.status.seller} labels={{ all: 'Jami', payment_pending: "To'lov", new: 'Yangi', accepted: 'Qabul', handover: 'Kuryerda', cancelled: 'Bekor' }} />
        <StatusPanel title="Kuryer orderlar" counts={dashboard.status.courier} labels={{ all: 'Jami', pending: 'Kutmoqda', in_delivery: "Yo'lda", delivered: 'Yetdi', customer_received: 'Qabul', rejected: 'Bekor' }} />
      </div>

      <BusinessKpis dashboard={dashboard} />

      <PlatformAnalysis rows={dashboard.platformAnalysis} />

      <FunnelPanel funnel={dashboard.funnel} />

      <CohortPanel retention={dashboard.retention} />

      <SellerScorecard rows={dashboard.sellerScorecard} />

      <div className="row">
        <div className="col-xl-4">
          <RankPanel title={`Top mahsulotlar · ${dashboard.range.label}`} help="Tanlangan davrda yakunlangan savdolardagi mahsulotlar dona bo'yicha saralanadi. Gift turidagi sovg'alar tekin bo'lgani uchun bu ro'yxatga kirmaydi." rows={dashboard.topProducts.map((row) => ({ name: row.name, meta: `${fmt(row.quantity)} dona`, value: money(row.revenue) }))} />
        </div>
        <div className="col-xl-4">
          <RecentOrders rows={dashboard.recentOrders} />
        </div>
        <div className="col-xl-4">
          <div className="card h-100">
<div className="card-header">
              <h5 className="mb-0">Operatsion ogohlantirishlar</h5>
            </div>
<div className="card-body">

              <ul className="order-content-list">
                {dashboard.alerts.length ? dashboard.alerts.map((alert, index) => {
                  const tone = ['warning', 'danger', 'info', 'primary'][index % 4];
                  return (
                    <li className={`bg-${tone}-300`} key={alert.title}>
                      <Link href={alert.url || '/boshqaruv'} className="d-block text-decoration-none">
                        <h6 className={`text-${tone}-dark f-w-600 mb-0`}><i className={`${tiIcon(alert.icon)} me-1`}></i>{alert.title}</h6>
                        <p className={`text-${tone}-dark mb-0 f-s-13 txt-ellipsis-2`}>{alert.text}</p>
                      </Link>
                    </li>
                  );
                }) : (
                  <li className="bg-success-300">
                    <h6 className="text-success-dark f-w-600 mb-0"><i className="ti ti-circle-check me-1"></i>Hammasi joyida</h6>
                    <p className="text-success-dark mb-0 f-s-13">Kritik ogohlantirish yo'q.</p>
                  </li>
                )}
              </ul>
            </div>
</div>
        </div>
      </div>

      <div className="row">
        <DistributionPanel title={`To'lov kesimi · ${dashboard.range.label}`} help="Tanlangan davrda yaratilgan buyurtmalar to'lov statusi bo'yicha guruhlanadi: qancha order va jami ichidagi ulushi." rows={dashboard.paymentSplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${row.share}%` }))} />
        <DistributionPanel title={`Yetkazish kesimi · ${dashboard.range.label}`} help="Tanlangan davrda yakunlangan savdolar yetkazish turi bo'yicha guruhlanadi. Yonidagi summa shu turdagi savdolar tushumi." rows={dashboard.deliverySplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${money(row.revenue)}` }))} />
        <DistributionPanel title={`Hududlar · ${dashboard.range.label}`} help="Tanlangan davrda yakunlangan savdolarning address snapshotidan viloyat/shahar olinadi. Noma'lum addresslar alohida guruhga tushadi." rows={dashboard.regions.map((row) => ({ name: row.name, value: `${fmt(row.value)} ta · ${money(row.revenue)}` }))} />
      </div>
    </div>
  );
}

function BusinessKpis({ dashboard }: { dashboard: DashboardPayload }) {
  const b = dashboard.business;
  const rows = [
    { label: 'Yakuniy savdo ulushi', value: `${b.paidRate || 0}%`, meta: 'Yakuniy savdo / jami', icon: 'ti-credit-card', help: businessHelps['Yakuniy savdo ulushi'] },
    { label: 'Yakunlash ulushi', value: `${b.completionRate || 0}%`, meta: 'Yakunlangan / jami', icon: 'ti-circle-check', help: businessHelps['Yakunlash ulushi'] },
    { label: 'Bekor ulushi', value: `${b.cancellationRate || 0}%`, meta: 'Bekor va qaytgan', icon: 'ti-circle-x', help: businessHelps['Bekor ulushi'] },
    { label: 'Qayta xaridor', value: `${b.repeatBuyerRate || 0}%`, meta: `${fmt(b.repeatBuyers)} foydalanuvchi`, icon: 'ti-repeat', help: businessHelps['Qayta xaridor'] },
    { label: 'Xaridorlar', value: fmt(b.buyingUsers), meta: 'Paid order qilgan', icon: 'ti-users', help: businessHelps.Xaridorlar },
    { label: 'Karta ulangan', value: fmt(b.cardUsers), meta: 'Tasdiqlangan karta', icon: 'ti-credit-card', help: businessHelps['Karta ulangan'] },
    { label: "Order / xaridor", value: String(b.avgOrdersPerBuyer || 0), meta: "O'rtacha chastota", icon: 'ti-shopping-bag', help: businessHelps["Order / xaridor"] },
    { label: "Daromad / xaridor", value: money(b.avgRevenuePerBuyer), meta: "O'rtacha paid revenue", icon: 'ti-cash', help: businessHelps["Daromad / xaridor"] },
  ];

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600">Marketplace KPI</h5>
          <p className="mb-0 text-secondary">Faqat real order va foydalanuvchi ma'lumotlaridan hisoblangan</p>
        </div>
      </div>
      <div className="card-body">

        <div className="row g-3">
          {rows.map((row, index) => (
            <div className="col-xl-3 col-md-6" key={row.label}>
              <MiniStat icon={tiIcon(row.icon)} tone={DASH_TONES[index % DASH_TONES.length] as Tone} label={row.label} help={row.help} value={row.value} meta={row.meta} />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function PlatformAnalysis({ rows }: { rows: DashboardPayload['platformAnalysis'] }) {
  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">App platforma tahlili</h5>
            <InfoHint text="Userning oxirgi connected device platformasi olinadi. Aktiv user - oxirgi 30 kunda device yangilangan user, Orders va Daromad - shu platformadagi userlarning paid orderlari, Conv - paid buyer / aktiv user." />
          </div>
          <p className="mb-0 text-secondary">Android va iOS bo'yicha real device, order va tushum signallari</p>
        </div>
      </div>
      <div className="card-body">

        <div className="row g-2">
          {rows.length ? rows.map((row, index) => (
            <div className="col-xl-6" key={row.name}>
              <div className="b-1-light b-r-15 p-3 h-100">
                <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div className="d-flex align-items-center gap-2 min-w-0">
                    <span className={`h-45 w-45 d-flex-center b-r-10 f-s-22 flex-shrink-0 text-light-${index % 2 ? 'info' : 'success'}`}><i className={tiIcon(row.icon)}></i></span>
                    <div className="min-w-0">
                      <h6 className="mb-0 f-w-600 txt-ellipsis-1">{row.name}</h6>
                      <p className="mb-0 text-secondary f-s-13">{row.version} · Conv {row.conversion}%</p>
                    </div>
                  </div>
                  <span className="badge text-light-info">App</span>
                </div>
                <div className="row g-2 row-cols-2 row-cols-md-3">
                  <PlatformStat label="Faol user" value={fmt(row.activeUsers)} />
                  <PlatformStat label="Orders" value={fmt(row.orders)} />
                  <PlatformStat label="Daromad" value={money(row.revenue)} />
                  <PlatformStat label="Crash" value={`${row.crashRate}%`} />
                  <PlatformStat label="Session" value={formatDuration(row.avgSessionSeconds)} />
                </div>
              </div>
            </div>
          )) : <div className="col-12"><EmptyState text="Platform device ma'lumotlari topilmadi." /></div>}
        </div>
      </div>
    </div>
  );
}

function PlatformStat({ label, value }: { label: string; value: string }) {
  return (
    <div className="col">
      <div className="b-1-light b-r-10 p-2 h-100">
        <span className="d-block f-w-600 text-dark f-s-15 text-break">{value}</span>
        <span className="d-block f-s-12 text-secondary">{label}</span>
      </div>
    </div>
  );
}

function formatDuration(seconds = 0) {
  if (!seconds) return '0m';
  const minutes = Math.floor(seconds / 60);
  const rest = seconds % 60;
  if (minutes >= 60) {
    const hours = Math.floor(minutes / 60);
    return `${hours}h ${minutes % 60}m`;
  }

  return `${minutes}m ${rest}s`;
}

type UnitEconomicsMonth = DashboardPayload['unitEconomicsMonthly']['months'][number];

function UnitEconomics({ data, monthly }: { data: DashboardPayload['unitEconomics']; monthly: DashboardPayload['unitEconomicsMonthly'] }) {
  const ratioTone: Tone = data.ltvCacRatio >= 3 ? 'success' : data.ltvCacRatio >= 1 ? 'warning' : 'danger';
  const refundTone: Tone = data.refundRate > 5 ? 'danger' : data.refundRate > 2 ? 'warning' : 'success';
  const cancelTone: Tone = data.cancelRate > 15 ? 'danger' : data.cancelRate > 8 ? 'warning' : 'success';
  const marginTone: Tone = data.marginPct >= 0 ? 'success' : 'danger';
  const months = monthly?.months || [];

  const inputRows: Array<{ label: string; get: (m: UnitEconomicsMonth) => string }> = [
    { label: 'Yakuniy savdo tushumi', get: (m) => money(m.grossRevenue) },
    { label: 'Contribution (soliqdan oldin)', get: (m) => money(m.contribution) },
    { label: 'Platforma sof marja', get: (m) => money(m.platformProfit) },
    { label: 'Marketing xarajat', get: (m) => money(m.marketingSpend) },
    { label: "To'langan orderlar", get: (m) => fmt(m.paidOrders) },
    { label: 'Xaridorlar (oy)', get: (m) => fmt(m.totalBuyers) },
    { label: 'Yangi xaridorlar', get: (m) => fmt(m.newBuyers) },
    { label: 'Qaytgan xaridorlar', get: (m) => fmt(m.repeatBuyers) },
    { label: 'Refund summa', get: (m) => money(m.refundAmount) },
  ];

  const indicatorRows: Array<{ label: string; get: (m: UnitEconomicsMonth) => string }> = [
    { label: 'CAC', get: (m) => (m.cac !== null ? money(m.cac) : '—') },
    { label: 'CAC Payback (order)', get: (m) => (m.cacPaybackOrders !== null ? String(m.cacPaybackOrders) : '—') },
    { label: 'AOV / Gross per order', get: (m) => money(m.grossPerOrder) },
    { label: 'Contribution / order', get: (m) => money(m.contributionPerOrder) },
    { label: 'Gross margin', get: (m) => `${m.marginPct}%` },
    { label: 'ARPU — oylik', get: (m) => money(m.arpu) },
    { label: 'Repeat purchase rate', get: (m) => `${m.repeatRate}%` },
    { label: 'Refund rate', get: (m) => `${m.refundRate}%` },
    { label: 'Cancel rate', get: (m) => `${m.cancelRate}%` },
  ];

  const cards: Array<{ l: string; icon: string; v: string; s: string; c: Tone | ''; help: string }> = [
    { l: 'CAC', icon: 'ti-coins', v: data.hasMarketingData ? money(data.cac) : '—', s: data.hasMarketingData ? `${fmt(data.newBuyers)} yangi xaridor` : 'Marketing xarajat kiritilmagan', c: '', help: "Customer Acquisition Cost: davrdagi marketing xarajati / yangi xaridorlar. Marketing xarajati Chiqimlar bo'limidagi \"Marketing va reklama\" kategoriyasidan olinadi." },
    { l: 'LTV (margin)', icon: 'ti-diamond', v: money(data.ltv), s: `ARPU ${money(data.arpu)}`, c: '', help: "Lifetime Value: har bir xaridorga to'g'ri keladigan umumiy platforma marjasi (contribution / jami xaridorlar). ARPU — o'rtacha yalpi tushum/xaridor." },
    { l: 'LTV : CAC', icon: 'ti-gauge', v: data.hasMarketingData ? `${data.ltvCacRatio}×` : '—', s: data.ltvCacRatio >= 3 ? "Sog'lom (≥3)" : data.ltvCacRatio >= 1 ? "O'rtacha" : 'Past', c: ratioTone, help: "Investor uchun asosiy nisbat. ≥3 sog'lom, 1–3 o'rtacha, <1 — mijoz jalb qilish zarar keltiryapti." },
    { l: 'Payback', icon: 'ti-repeat', v: data.hasMarketingData && data.paybackOrders > 0 ? `${data.paybackOrders} order` : '—', s: 'CAC ni qoplash', c: '', help: 'CAC ni qoplash uchun bitta xaridordan necha order kerak (CAC / contribution-per-order).' },
    { l: 'Margin / order', icon: 'ti-cash', v: money(data.contributionPerOrder), s: `Gross ${money(data.grossPerOrder)}`, c: marginTone, help: "Har bir yakuniy orderdan qoladigan platforma marjasi (soliqdan oldingi contribution). Gross — o'rtacha order summasi." },
    { l: 'Gross margin', icon: 'ti-percentage', v: `${data.marginPct}%`, s: 'Contribution / tushum', c: marginTone, help: 'Contribution margin yalpi tushumga nisbatan foizda. Manfiy bo\'lsa xarajat tushumdan oshgan.' },
    { l: 'Refund rate', icon: 'ti-arrow-back', v: `${data.refundRate}%`, s: `${fmt(data.refundOrders)} order · ${money(data.refundAmount)}`, c: refundTone, help: 'Qaytarilgan (refund) orderlar ulushi va summasi. Sold.refund_total_amount asosida.' },
    { l: 'Cancel rate', icon: 'ti-circle-x', v: `${data.cancelRate}%`, s: 'Bekor + qaytgan', c: cancelTone, help: 'Davrda yaratilgan orderlardan bekor qilingan yoki qaytganlari ulushi.' },
    { l: 'Repeat', icon: 'ti-repeat', v: `${data.repeatRate}%`, s: `${fmt(data.repeatBuyers)} qaytgan xaridor`, c: '', help: "Bir martadan ko'p xarid qilgan xaridorlar ulushi (lifetime)." },
  ];

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">Unit economics</h5>
            <InfoHint text="Investor va operator uchun birlik iqtisodiyoti — Google Sheets namunasidagi tuzilishda (Input Data + Key Indicators, oylar ustunlarda). Yuqoridagi kartalar tanlangan davr/lifetime bo'yicha, pastdagi jadval so'nggi 12 oy dinamikasi." />
          </div>
          <p className="mb-0 text-secondary">CAC · LTV · margin/order · refund/cancel · repeat</p>
        </div>
        {data.hasMarketingData ? null : <span className="badge text-light-warning">CAC uchun Chiqimlarga marketing xarajat kiriting</span>}
      </div>
      <div className="card-body">

        <div className="row g-3 mb-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5">
          {cards.map((card) => (
            <div className="col" key={card.l}>
              <MiniStat icon={tiIcon(card.icon)} tone={card.c || 'primary'} valueTone={card.c} label={card.l} help={card.help} value={card.v} meta={card.s} />
            </div>
          ))}
        </div>

        {months.length ? (
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 text-nowrap">
              <thead>
                <tr>
                  <th className="position-sticky start-0 bg-body">Metric</th>
                  {months.map((m) => <th key={m.month} className="text-end">{m.month}</th>)}
                </tr>
              </thead>
              <tbody>
                <tr><td colSpan={months.length + 1} className="bg-light-primary f-w-600">Input Data</td></tr>
                {inputRows.map((row) => (
                  <tr key={row.label}>
                    <td className="position-sticky start-0 bg-body f-w-500 text-secondary">{row.label}</td>
                    {months.map((m) => <td key={m.month} className="text-end">{row.get(m)}</td>)}
                  </tr>
                ))}
                <tr><td colSpan={months.length + 1} className="bg-light-primary f-w-600">Key Indicators</td></tr>
                {indicatorRows.map((row) => (
                  <tr key={row.label}>
                    <td className="position-sticky start-0 bg-body f-w-500 text-secondary">{row.label}</td>
                    {months.map((m) => <td key={m.month} className="text-end">{row.get(m)}</td>)}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : null}
      </div>
    </div>
  );
}

type PartnerMonth = DashboardPayload['partnerEconomics']['months'][number];

function PartnerEconomics({ data }: { data: DashboardPayload['partnerEconomics'] }) {
  const pct = (v: number | null) => (v === null ? '—' : `${v}%`);
  const months = data.months || [];

  if (!data.hasData || !months.length) {
    return (
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <div className="d-flex align-items-center gap-2">
              <h5 className="f-w-600">Hamkorlar MRR (Premium obuna)</h5>
              <InfoHint text="Premium obuna to'lagan sotuvchilar (hamkorlar) uchun SaaS uslubidagi MRR, churn, retention va LTV hisoboti. Xaridor unit economics blokidan alohida — ikkalasi qo'shilmaydi." />
            </div>
            <p className="mb-0 text-secondary">seller_premium_subscriptions asosida, oylik dinamika</p>
          </div>
        </div>
        <div className="card-body">

          <div className="text-muted f-s-13">Hali faol premium (hamkor) obuna topilmadi. Birinchi sotuvchi premium sotib olgach, shu yerda MRR, churn va LTV avtomatik hisoblanib boradi.</div>
        </div>
      </div>
    );
  }

  const s = data.summary;
  const ratioTone: Tone | '' = s.ltvCacRatio === null ? '' : s.ltvCacRatio >= 3 ? 'success' : s.ltvCacRatio >= 1 ? 'warning' : 'danger';
  const churnTone: Tone = s.churnRateMrr > 5 ? 'danger' : s.churnRateMrr > 2 ? 'warning' : 'success';
  const netTone: Tone | '' = s.netRetention === null ? '' : s.netRetention >= 100 ? 'success' : 'warning';

  const cards: Array<{ l: string; icon: string; v: string; s: string; c: Tone | ''; help: string }> = [
    { l: 'Faol hamkor', icon: 'ti-users', v: fmt(data.currentPartners), s: `MRR ${money(data.currentMrr)}`, c: '', help: "Joriy oyda faol (to'lovi o'tgan) premium sotuvchilar soni va ularning umumiy oylik takrorlanuvchi daromadi (MRR)." },
    { l: 'ARPC', icon: 'ti-coins', v: money(s.arpc), s: 'Hamkor boshiga MRR', c: '', help: "Average Revenue Per Customer — faol hamkor boshiga o'rtacha oylik daromad, oylik jadval o'rtachasi." },
    { l: 'CAC', icon: 'ti-magnet', v: data.hasMarketingData && s.cac !== null ? money(s.cac) : '—', s: 'Marketing / yangi hamkor', c: '', help: "Marketing xarajati / davrdagi yangi premium hamkorlar. Chiqimlar bo'limida marketing kategoriyasi kiritilishi kerak." },
    { l: 'CAC Payback', icon: 'ti-hourglass', v: s.cacPaybackMonths !== null ? `${s.cacPaybackMonths} oy` : '—', s: 'CAC ni qoplash muddati', c: '', help: 'CAC ni ARPC bilan qoplash uchun kerak bo\'ladigan oylar soni (CAC / ARPC).' },
    { l: 'Churn · MRR', icon: 'ti-trending-down', v: pct(s.churnRateMrr), s: "Oylik, MRR bo'yicha", c: churnTone, help: "Oy davomida bekor bo'lgan MRR / oy boshidagi MRR, oylar bo'yicha o'rtacha." },
    { l: 'Churn · hamkor', icon: 'ti-user-minus', v: pct(s.churnRateCount), s: "Oylik, soni bo'yicha", c: churnTone, help: "Oy davomida ketgan hamkorlar soni / oy boshidagi hamkorlar soni, oylar bo'yicha o'rtacha." },
    { l: 'Gross Retention', icon: 'ti-shield-check', v: pct(s.grossRetention), s: 'Churn hisobga olib', c: '', help: "(Oy boshi MRR − bekor bo'lgan MRR) / oy boshi MRR. 100% dan yuqori bo'lmaydi." },
    { l: 'Net Retention', icon: 'ti-circle-arrow-up-right', v: pct(s.netRetention), s: 'Upgrade/downgrade bilan', c: netTone, help: "Mavjud hamkorlarning joriy MRR'si / ularning oy boshidagi MRR'si. 100% dan yuqori bo'lsa — mavjud hamkorlar ko'proq to'lamoqda." },
    { l: 'Lifetime', icon: 'ti-infinity', v: s.lifetimeMonths !== null ? `${s.lifetimeMonths} oy` : '—', s: '1 / churn rate', c: '', help: "Hamkorning o'rtacha faollik davomiyligi (oyda): 1 / churn rate (soni bo'yicha)." },
    { l: 'LTV : CAC', icon: 'ti-gauge', v: s.ltvCacRatio !== null ? `${s.ltvCacRatio}×` : '—', s: s.ltv !== null ? `LTV ${money(s.ltv)}` : 'LTV —', c: ratioTone, help: "Lifetime Value (ARPC × Lifetime) / CAC. ≥3 sog'lom signal, <1 — hamkor jalb qilish zarar keltiryapti." },
  ];

  const inputRows: Array<{ label: string; get: (m: PartnerMonth) => string }> = [
    { label: "Partners' MRR", get: (m) => money(m.mrr) },
    { label: 'Yangi hamkor MRR', get: (m) => money(m.newMrr) },
    { label: "Bekor bo'lgan MRR", get: (m) => money(m.churnedMrr) },
    { label: 'Faol hamkorlar', get: (m) => fmt(m.currentPartners) },
    { label: 'Yangi hamkorlar', get: (m) => fmt(m.newPartners) },
    { label: 'Bekor qilgan hamkorlar', get: (m) => fmt(m.churnedPartners) },
    { label: 'Marketing xarajat', get: (m) => money(m.marketingSpend) },
  ];

  const indicatorRows: Array<{ label: string; get: (m: PartnerMonth) => string }> = [
    { label: 'ARPC', get: (m) => money(m.arpc) },
    { label: 'CAC', get: (m) => (m.cac !== null ? money(m.cac) : '—') },
    { label: 'CAC Payback (oy)', get: (m) => (m.cacPaybackMonths !== null ? String(m.cacPaybackMonths) : '—') },
    { label: 'Churn — MRR', get: (m) => `${m.churnRateMrr}%` },
    { label: 'Churn — soni', get: (m) => `${m.churnRateCount}%` },
    { label: 'Gross Retention', get: (m) => (m.grossRetention !== null ? `${m.grossRetention}%` : '—') },
    { label: 'Net Retention', get: (m) => (m.netRetention !== null ? `${m.netRetention}%` : '—') },
    { label: 'Lifetime (oy)', get: (m) => (m.lifetimeMonths !== null ? String(m.lifetimeMonths) : '—') },
    { label: 'LTV', get: (m) => (m.ltv !== null ? money(m.ltv) : '—') },
    { label: 'LTV : CAC', get: (m) => (m.ltvCacRatio !== null ? `${m.ltvCacRatio}×` : '—') },
  ];

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">Hamkorlar MRR (Premium obuna)</h5>
            <InfoHint text="Premium obuna to'lagan sotuvchilar (hamkorlar) uchun SaaS uslubidagi MRR, churn, retention va LTV hisoboti — Google Sheets namunasidagi tuzilishda (Input Data + Key Indicators, oylar ustunlarda). Xaridor unit economics blokidan alohida hisoblanadi, ikkalasi qo'shilmaydi." />
          </div>
          <p className="mb-0 text-secondary">MRR · Churn · Retention · LTV — oylik dinamika</p>
        </div>
        {data.hasMarketingData ? null : <span className="badge text-light-warning">CAC uchun Chiqimlarga marketing xarajat kiriting</span>}
      </div>
      <div className="card-body">


        <div className="row g-3 mb-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5">
          {cards.map((card) => (
            <div className="col" key={card.l}>
              <MiniStat icon={tiIcon(card.icon)} tone={card.c || 'primary'} valueTone={card.c} label={card.l} help={card.help} value={card.v} meta={card.s} />
            </div>
          ))}
        </div>

        <div className="table-responsive app-scroll">
          <table className="table table-bottom-border align-middle mb-0 text-nowrap">
            <thead>
              <tr>
                <th className="position-sticky start-0 bg-body">Metric</th>
                {months.map((m) => <th key={m.month} className="text-end">{m.month}</th>)}
              </tr>
            </thead>
            <tbody>
              <tr><td colSpan={months.length + 1} className="bg-light-primary f-w-600">Input Data</td></tr>
              {inputRows.map((row) => (
                <tr key={row.label}>
                  <td className="position-sticky start-0 bg-body f-w-500 text-secondary">{row.label}</td>
                  {months.map((m) => <td key={m.month} className="text-end">{row.get(m)}</td>)}
                </tr>
              ))}
              <tr><td colSpan={months.length + 1} className="bg-light-primary f-w-600">Key Indicators</td></tr>
              {indicatorRows.map((row) => (
                <tr key={row.label}>
                  <td className="position-sticky start-0 bg-body f-w-500 text-secondary">{row.label}</td>
                  {months.map((m) => <td key={m.month} className="text-end">{row.get(m)}</td>)}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function FunnelPanel({ funnel }: { funnel: DashboardPayload['funnel'] }) {
  const stages = funnel.stages || [];
  const max = Math.max(1, ...stages.map((stage) => stage.value));

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">Xarid voronkasi</h5>
            <InfoHint text="Ko'rish → buyurtma → to'lov → yakunlash. Ko'rishlar real mahsulot ko'rish eventlaridan (product_view_logs), qolgan bosqichlar bir order kohortasi (yaratilgan sana) bo'yicha. Foiz — oldingi bosqichdan konversiya." />
          </div>
          <p className="mb-0 text-secondary">
            {funnel.hasViewData
              ? `${fmt(funnel.uniqueViewers)} unikal ko'ruvchi · hozir savatda ${fmt(funnel.cartUsers)} mijoz`
              : `Ko'rish logi hali to'planmagan · hozir savatda ${fmt(funnel.cartUsers)} mijoz`}
          </p>
        </div>
        <span className="badge text-light-success">Ko'rish→to'lov {funnel.viewToPaid}%</span>
      </div>
      <div className="card-body">

        {stages.length ? (
          <div className="d-flex flex-column gap-2">
            {stages.map((stage, index) => (
              <div key={stage.key}>
                <div className="d-flex justify-content-between align-items-center mb-1">
                  <span className="f-w-600 f-s-13">{index + 1}. {stage.label}</span>
                  <span className="f-s-13 text-muted">
                    {fmt(stage.value)}
                    {index > 0 ? ` · ${stage.rate}% konversiya` : ''}
                    {stage.drop > 0 ? ` · −${stage.drop}%` : ''}
                  </span>
                </div>
                {/* Raqam yuqoridagi qatorda allaqachon bor — chiziq ichida
                    takrorlanmaydi (rangli fonda o'qilmay qolardi). */}
                <div className="progress w-100" role="progressbar" aria-valuenow={stage.value} aria-valuemin={0} aria-valuemax={max}>
                  <div className={`progress-bar bg-${DASH_TONES[index % DASH_TONES.length]}`} style={{ width: `${Math.max(2, stage.value / max * 100)}%` }} />
                </div>
              </div>
            ))}
          </div>
        ) : <EmptyState text="Voronka uchun ma'lumot yo'q." />}
      </div>
    </div>
  );
}

function CohortPanel({ retention }: { retention: DashboardPayload['retention'] }) {
  const cohorts = retention.cohorts || [];
  const offsets = Array.from({ length: (retention.maxOffset ?? 5) + 1 }, (_, index) => index);
  const cellStyle = (value: number | null) => {
    if (value === null || value === undefined) return { background: 'transparent', color: 'rgba(var(--secondary), 1)' };
    const alpha = Math.min(1, 0.12 + value / 100 * 0.88);
    return { background: `rgba(var(--primary), ${alpha})`, color: alpha > 0.55 ? 'rgba(var(--white), 1)' : 'var(--font-color)' };
  };

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">Retention kogortalari</h5>
            <InfoHint text="Har oy birinchi marta xarid qilgan mijozlar keyingi oylarda yana xarid qildimi. M+0 doim 100% (birinchi oy). Faqat to'langan va mijoz qabul qilgan savdolar hisobga olinadi. Bo'sh katak — hali kelmagan oy." />
          </div>
          <p className="mb-0 text-secondary">Qayta xarid ulushi (%) — oylik kogortalar</p>
        </div>
      </div>
      <div className="card-body">

        {cohorts.length ? (
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border mb-0 text-center align-middle" style={{ minWidth: 620 }}>
              <thead>
                <tr>
                  <th className="text-start">Kogorta</th>
                  <th>Mijoz</th>
                  {offsets.map((offset) => <th key={offset}>M+{offset}</th>)}
                </tr>
              </thead>
              <tbody>
                {cohorts.map((cohort) => (
                  <tr key={cohort.month}>
                    <td className="text-start f-w-600">{cohort.month}</td>
                    <td>{fmt(cohort.size)}</td>
                    {offsets.map((offset) => {
                      const value = cohort.retention[offset] ?? null;
                      return (
                        <td key={offset} className="f-w-600 f-s-12" style={cellStyle(value)}>
                          {value === null ? '·' : `${value}%`}
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : <EmptyState text="Kogorta uchun yetarli xarid tarixi yo'q." />}
      </div>
    </div>
  );
}

function SellerScorecard({ rows }: { rows: DashboardPayload['sellerScorecard'] }) {
  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="f-w-600">Sotuvchilar reytingi</h5>
            <InfoHint text="Top sotuvchilar tushum bo'yicha. Bekor % — bekor qilingan orderlar ulushi, Qabul — buyurtmani qabul qilishgacha o'rtacha daqiqa, Reyting va Reputatsiya seller profilidan olinadi." />
          </div>
          <p className="mb-0 text-secondary">Tushum bo'yicha top {rows.length || 8} sotuvchi</p>
        </div>
      </div>
      <div className="card-body">

        {rows.length ? (
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 text-nowrap">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Sotuvchi</th>
                  <th className="text-end">Order</th>
                  <th className="text-end">Tushum</th>
                  <th className="text-end">Bekor %</th>
                  <th className="text-end">Qabul</th>
                  <th className="text-end">Reyting</th>
                  <th className="text-end">Reputatsiya</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((seller, index) => (
                  <tr key={seller.id}>
                    <td>{index + 1}</td>
                    <td>
                      <a href={seller.url || '#'} className="text-decoration-none text-dark d-flex align-items-center gap-2">
                        <span className={`h-30 w-30 d-flex-center b-r-50 overflow-hidden flex-shrink-0 text-light-${DASH_TONES[index % DASH_TONES.length]}`}>
                          {seller.avatar ? <img src={seller.avatar} alt="" className="w-100 h-100 object-fit-cover" /> : <i className="ti ti-building-store"></i>}
                        </span>
                        <span className="txt-ellipsis-1 f-w-500 w-200">{seller.name}</span>
                      </a>
                    </td>
                    <td className="text-end">{fmt(seller.orders)}</td>
                    <td className="text-end f-w-600">{money(seller.revenue)}</td>
                    <td className="text-end"><span className={seller.cancelRate > 15 ? 'text-danger f-w-600' : 'text-muted'}>{seller.cancelRate}%</span></td>
                    <td className="text-end">{seller.acceptMinutes === null ? '—' : `${fmt(seller.acceptMinutes)}m`}</td>
                    <td className="text-end">{seller.rating ? <><i className="ti ti-star-filled text-warning me-1"></i>{seller.rating.toFixed(1)}</> : '—'}{seller.ratingCount ? <small className="text-muted"> ({fmt(seller.ratingCount)})</small> : null}</td>
                    <td className="text-end">{fmt(seller.reputation)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : <EmptyState text="Sotuvchi order ma'lumotlari topilmadi." />}
      </div>
    </div>
  );
}

function Metric({ label, value = 0, href, help, index }: { label: string; value?: number; icon: string; href: string; help?: string; index: number }) {
  return (
    <div className="col-sm-6 col-md-4 col-xl-3">
      <StatWidget index={index} label={label} value={fmt(value)} href={href} help={help} />
    </div>
  );
}

function PeriodCard({ label, value, delta, help, index }: { label: string; value: string; delta: number | null; icon: string; help?: string; index: number }) {
  return (
    <div className="col-xl-3 col-md-6">
      <StatWidget
        index={index}
        label={label}
        value={value}
        help={help}
        sub={delta === null ? 'Barcha davr' : 'oldingi davrga'}
        trend={delta === null ? undefined : `${delta >= 0 ? '+' : ''}${delta.toFixed(1)}%`}
        trendTone={delta !== null && delta < 0 ? 'danger' : 'success'}
      />
    </div>
  );
}

function DistributionPanel({ title, rows, help }: { title: string; rows: Array<{ name: string; value: string }>; help?: string }) {
  return (
    <div className="col-xl-4"><div className="card h-100">
      <div className="card-header d-flex align-items-center gap-2"><h5 className="f-w-600">{title}</h5>{help ? <InfoHint text={help} /> : null}</div>
      <div className="card-body">{rows.length ? <ul className="list-group list-group-flush">{rows.slice(0, 8).map((row, index) => <li className="list-group-item d-flex align-items-center justify-content-between gap-3 px-0" key={row.name}><span className="d-flex align-items-center gap-2 min-w-0 text-secondary"><span className={`d-inline-block h-10 w-10 b-r-50 flex-shrink-0 bg-${DASH_TONES[index % DASH_TONES.length]}`}></span><span className="text-truncate">{row.name}</span></span><strong className="text-nowrap text-dark">{row.value}</strong></li>)}</ul> : <EmptyState text="Ma'lumot topilmadi." />}</div>
    </div></div>
  );
}

// Real P&L qatorlari: rang faqat ishorani bildiradi — kirim, chiqim yoki
// yakuniy natija. Har bir qatorga alohida rang berilmaydi.
function toneKeyOfRow(row: { raw: number; isResult?: boolean }): Tone {
  if (row.isResult) return row.raw >= 0 ? 'primary' : 'danger';
  return Number(row.raw) >= 0 ? 'success' : 'danger';
}

function FinancialPanel({ dashboard }: { dashboard: DashboardPayload }) {
  const f = dashboard.financial;
  const rows = [
    { label: 'Yakuniy savdo tushumi', raw: f.grossRevenue },
    { label: 'Delivery income', raw: f.deliveryIncome },
    { label: 'Seller commission', raw: f.commission },
    { label: 'Promo discount', raw: -f.promoDiscount },
    { label: 'Cashback', raw: -f.cashback },
    { label: 'Courier payout', raw: -f.courierPayout },
    { label: 'Kiritilgan chiqimlar', raw: -f.manualExpenses },
    { label: 'Provider komissiyasi', raw: -f.providerFee },
    { label: 'Soliq', raw: -f.tax },
    { label: 'Marketplace marjasi', raw: f.platformProfit, isResult: true },
  ];

  return (
    <div className="col-xl-6">
      <div className="card h-100">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Real P&L signali · {dashboard.range.label}</h5>
            <p className="mb-0 text-secondary">Tanlangan davrdagi net komissiya, delivery, chegirma, kuryer, ledger, provider va soliq asosida</p>
          </div>
          <span className={`badge ${f.platformProfit >= 0 ? 'text-light-success' : 'text-light-danger'}`}>Net {f.netMargin || 0}%</span>
        </div>
<div className="card-body">

          <div className="row g-3">
            {rows.map((row) => (
              <div className="col-md-6" key={row.label}>
                <MiniStat icon={`ti ${row.isResult ? 'ti-chart-line' : Number(row.raw) >= 0 ? 'ti-arrow-up-right' : 'ti-arrow-down-right'}`} tone={toneKeyOfRow(row)} valueTone={toneKeyOfRow(row)} label={row.label} help={financialHelps[row.label]} value={money(Number(row.raw))} />
              </div>
            ))}
          </div>
        </div>
</div>
    </div>
  );
}

const STATUS_TONES: Record<string, string> = {
  new: 'primary', payment_pending: 'warning', pending: 'warning', packing: 'info', accepted: 'info', onway: 'info', in_delivery: 'info',
  handover: 'secondary', done: 'success', delivered: 'success', customer_received: 'success', cancelled: 'danger', rejected: 'danger',
};

function StatusPanel({ title, counts, labels }: { title: string; counts: Record<string, number>; labels: Record<string, string> }) {
  const entries = Object.entries(labels).filter(([key]) => key !== 'all');
  const sum = entries.reduce((acc, [key]) => acc + (counts[key] || 0), 0);
  const total = Math.max(counts.all || 0, sum, 1);
  return (
    <div className="col-xl-2 col-md-4">
      <div className="card h-100">
        <div className="card-header">
          <h5 className="mb-0 f-s-18">{title}</h5>
          <p className="mb-0 text-secondary f-s-13">{labels.all || 'Jami'}: <span className="f-w-600 text-primary">{fmt(counts.all || sum)}</span></p>
        </div>
        <div className="card-body">
          <ul className="list-unstyled mb-0 d-flex flex-column gap-3">
            {entries.map(([key, label]) => {
              const value = counts[key] || 0;
              const tone = STATUS_TONES[key] || 'secondary';
              return (
                <li key={key}>
                  <div className="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <span className="f-s-13 text-secondary f-w-500 txt-ellipsis-1">{label}</span>
                    <span className="f-s-14 f-w-600 text-dark">{fmt(value)}</span>
                  </div>
                  <div className="progress w-100 h-5" role="progressbar" aria-valuenow={value} aria-valuemin={0} aria-valuemax={total}>
                    <div className={`progress-bar bg-${tone}`} style={{ width: `${value ? Math.max(3, value / total * 100) : 0}%` }}></div>
                  </div>
                </li>
              );
            })}
          </ul>
        </div>
      </div>
    </div>
  );
}

function RankPanel({ title, rows, help }: { title: string; rows: Array<{ name: string; meta: string; value: string }>; help?: string }) {
  return (
    <div className="card h-100">
      <div className="card-header d-flex align-items-center gap-2"><h5 className="f-w-600">{title}</h5>{help ? <InfoHint text={help} /> : null}</div>
      <div className="card-body">

        {rows.length ? (
          <ul className="customer-list">
            {rows.map((row, index) => (
              <li className="customer-list-item gap-2" key={`${row.name}-${index}`}>
                <span className={`text-light-${DASH_TONES[index % DASH_TONES.length]} f-w-600 h-35 w-35 d-flex-center b-r-50 customer-list-avtar`}>{index + 1}</span>
                <div className="customer-list-content min-w-0">
                  <h6 className="mb-0 f-s-15 txt-ellipsis-1">{row.name}</h6>
                  <p className="mb-0 f-s-12 text-secondary">{row.meta}</p>
                </div>
                <span className="f-w-600 text-dark f-s-14 text-nowrap ms-auto">{row.value}</span>
              </li>
            ))}
          </ul>
        ) : <EmptyState text="Ma'lumot topilmadi." />}
      </div>
    </div>
  );
}

function RecentOrders({ rows }: { rows: DashboardPayload['recentOrders'] }) {
  return (
    <div className="card h-100">
      <div className="card-header">
        <h5 className="mb-0">Oxirgi buyurtmalar</h5>
      </div>
      <div className="card-body">

        {rows.length ? (
          <ul className="customer-list">
            {rows.slice(0, 8).map((row, index) => (
              <li className="customer-list-item gap-2" key={row.id}>
                <span className={`text-light-${DASH_TONES[index % DASH_TONES.length]} f-w-600 h-35 w-35 d-flex-center b-r-50 customer-list-avtar f-s-12`}>{(row.customer || 'M').trim().slice(0, 1).toUpperCase()}</span>
                <div className="customer-list-content min-w-0">
                  <h6 className="mb-0 f-s-15 txt-ellipsis-1">{row.customer || 'Mijoz'} <span className="text-secondary f-w-500 f-s-12">#{row.id}</span></h6>
                  <p className="mb-0 f-s-12 text-secondary txt-ellipsis-1">{row.status} · {row.updated_at || ''}</p>
                </div>
                <span className="f-w-600 text-dark f-s-14 text-nowrap ms-auto">{money(row.amount)}</span>
                {row.url ? <a href={row.url} className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 flex-shrink-0" title="Buyurtmani ochish"><i className="ti ti-eye"></i></a> : null}
              </li>
            ))}
          </ul>
        ) : <EmptyState text="Buyurtma topilmadi." />}
      </div>
    </div>
  );
}
