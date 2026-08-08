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

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));
const money = (n: number) => `${fmt(n)} so'm`;
const compact = (n: number) => new Intl.NumberFormat('uz-UZ', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(n) || 0);

interface DashboardPayload {
  generatedAt: string;
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

export default function Dashboard() {
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
      <div className="page-head">
        <div>
          <h1 className="page-title">Boshqaruv dashboard</h1>
          <p className="page-subtitle">
            <span className="chip chip-success me-2"><span className="live-pulse"></span>Real DB</span>
            Oxirgi hisoblash: {dashboard.generatedAt}
          </p>
        </div>
        <div className="d-flex gap-2 align-items-center flex-wrap justify-content-end">
          <span className="chip chip-info"><i className="bi bi-calendar3 me-1"></i>{dashboard.range.label}</span>
          <div className="btn-group btn-group-sm" aria-label="Dashboard davri">
            {([
              ['today', 'Bugun'],
              ['week', 'Hafta'],
              ['month', 'Oy'],
              ['year', 'Yil'],
              ['all', 'Barchasi'],
            ] as const).map(([key, label]) => (
              <button key={key} disabled={isFiltering} className={`btn ${dashboard.range.key === key ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => selectPeriod(key)}>
                {label}
              </button>
            ))}
            <button disabled={isFiltering} className={`btn ${dashboard.range.key === 'custom' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setShowCustomRange((value) => !value)}>
              <i className="bi bi-calendar-range me-1"></i>Sana
            </button>
          </div>
          {dashboard.exportUrl ? (
            <div className="btn-group btn-group-sm">
              <a href={exportHref('investor')} className="btn btn-outline-success">
                <i className="bi bi-file-earmark-spreadsheet me-1"></i>Investor pack
              </a>
              <button type="button" className="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span className="visually-hidden">Export turlari</span>
              </button>
              <ul className="dropdown-menu dropdown-menu-end">
                <li><a className="dropdown-item" href={exportHref('investor')}><i className="bi bi-stars me-2"></i>Investor pack · multi-sheet</a></li>
                <li><a className="dropdown-item" href={exportHref('unit')}><i className="bi bi-calculator me-2"></i>Unit economics</a></li>
                <li><a className="dropdown-item" href={exportHref('dashboard')}><i className="bi bi-table me-2"></i>Dashboard snapshot</a></li>
              </ul>
            </div>
          ) : null}
          <Link href="/boshqaruv/live" className="btn btn-outline-secondary btn-sm"><i className="bi bi-broadcast me-1"></i>Live</Link>
        </div>
      </div>

      {showCustomRange ? (
        <div className="card-panel mb-3 py-2 px-3">
          <div className="d-flex align-items-end gap-2 flex-wrap">
            <label className="small text-muted">
              <span className="d-block mb-1">Boshlanish</span>
              <input className="form-control form-control-sm" type="date" max={customTo || today} value={customFrom} onChange={(event) => setCustomFrom(event.target.value)} />
            </label>
            <label className="small text-muted">
              <span className="d-block mb-1">Tugash</span>
              <input className="form-control form-control-sm" type="date" min={customFrom || undefined} max={today} value={customTo} onChange={(event) => setCustomTo(event.target.value)} />
            </label>
            <button className="btn btn-primary-gradient btn-sm" disabled={!customFrom || !customTo || isFiltering} onClick={applyCustomRange}>
              {isFiltering ? <span className="spinner-border spinner-border-sm me-1"></span> : <i className="bi bi-funnel me-1"></i>}
              Ko'rsatish
            </button>
            <small className="text-muted ms-auto">Savdo sanasi mijoz buyurtmani qabul qilgan vaqt bo'yicha olinadi.</small>
          </div>
        </div>
      ) : null}

      <div className="row g-2 mb-3">
        <Metric label="Buyurtmalar" value={dashboard.metrics.orders} icon="bi-receipt" color="#4f46e5" href="/boshqaruv/orders" help={metricHelps.orders} />
        <Metric label="Yakuniy savdo" value={dashboard.metrics.paidOrders} icon="bi-credit-card" color="#10b981" href="/boshqaruv/orders" help={metricHelps.paidOrders} />
        <Metric label="Foydalanuvchilar" value={dashboard.metrics.users} icon="bi-people" color="#06b6d4" href="/boshqaruv/users" help={metricHelps.users} />
        <Metric label="Kitoblar" value={dashboard.metrics.books} icon="bi-book" color="#f59e0b" href="/boshqaruv/books" help={metricHelps.books} />
        <Metric label="Sotuvchilar" value={dashboard.metrics.sellers} icon="bi-shop" color="#ec4899" href="/boshqaruv/sellers" help={metricHelps.sellers} />
        <Metric label="Kuryerlar" value={dashboard.metrics.couriers} icon="bi-bicycle" color="#7c3aed" href="/boshqaruv/couriers" help={metricHelps.couriers} />
      </div>

      <div className="row g-2 mb-3">
        <CompactMetric label="Premium user" value={dashboard.metrics.premiumUsers} icon="bi-stars" color="#7c3aed" href="/boshqaruv/users" help={metricHelps.premiumUsers} />
        <CompactMetric label="Online user" value={dashboard.metrics.onlineUsers} icon="bi-broadcast" color="#10b981" href="/boshqaruv/users" help={metricHelps.onlineUsers} />
        <CompactMetric label="Kanselyariya" value={dashboard.metrics.stationeries} icon="bi-pencil-square" color="#f59e0b" href="/boshqaruv/stationeries" help={metricHelps.stationeries} />
        <CompactMetric label="Pending seller" value={dashboard.metrics.pendingSellers} icon="bi-hourglass-split" color="#ec4899" href="/boshqaruv/sellers" help={metricHelps.pendingSellers} />
        <CompactMetric label="Support ticket" value={dashboard.metrics.tickets} icon="bi-headset" color="#06b6d4" href="/boshqaruv/tickets" help={metricHelps.tickets} />
        <CompactMetric label="Shikoyatlar" value={dashboard.metrics.complaints} icon="bi-exclamation-triangle" color="#ef4444" href="/boshqaruv/shikoyatlar" help={metricHelps.complaints} />
      </div>

      <div className="row g-2 mb-3">
        <PeriodCard label="Daromad" value={money(current.revenue)} delta={previous ? change(current.revenue, previous.revenue) : null} icon="bi-cash-coin" color="#4f46e5" help={periodHelps.revenue} />
        <PeriodCard label="Yakuniy savdolar" value={fmt(current.orders)} delta={previous ? change(current.orders, previous.orders) : null} icon="bi-bag-check" color="#10b981" help={periodHelps.orders} />
        <PeriodCard label="O'rtacha chek" value={money(current.aov)} delta={previous ? change(current.aov, previous.aov) : null} icon="bi-receipt" color="#f59e0b" help={periodHelps.aov} />
        <PeriodCard label="Yangi userlar" value={fmt(current.users)} delta={previous ? change(current.users, previous.users) : null} icon="bi-person-plus" color="#ec4899" help={periodHelps.users} />
      </div>

      <UnitEconomics data={dashboard.unitEconomics} />

      <div className="row g-3 mb-3">
        <div className="col-xl-8">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="d-flex align-items-center gap-2">
                  <div className="panel-title">{dashboard.salesTrend.label} savdo trendi</div>
                  <InfoHint text="Grafik tanlangan davr uzunligiga qarab soatlik, kunlik, oylik yoki yillik bo'linadi. Daromad va order faqat yakuniy savdolardan olinadi; Signal tanlangan davr P&L marjasining vaqt nuqtalariga mutanosib taqsimotidir." />
                </div>
                <small className="text-muted">{dashboard.salesTrend.granularity} · yakuniy savdolar va platform signal</small>
              </div>
              <div className="btn-group btn-group-sm">
                <button className={`btn ${chartMetric === 'revenue' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('revenue')}>Daromad</button>
                <button className={`btn ${chartMetric === 'profit' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('profit')}>Signal</button>
                <button className={`btn ${chartMetric === 'orders' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('orders')}>Order</button>
              </div>
            </div>
            <div style={{ overflowX: 'auto', overflowY: 'hidden' }}>
              <div style={{ minWidth: Math.max(720, dashboard.salesByMonth.length * 54) }}>
                <ResponsiveContainer width="100%" height={300}>
                  <AreaChart data={dashboard.salesByMonth} margin={{ left: 4, right: 18, top: 8, bottom: 0 }}>
                    <defs>
                      <linearGradient id="dashRevenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#4f46e5" stopOpacity={0.45} />
                        <stop offset="100%" stopColor="#4f46e5" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#eef0f4" vertical={false} />
                    <XAxis dataKey="month" stroke="#94a3b8" fontSize={11} interval={0} minTickGap={10} />
                    <YAxis stroke="#94a3b8" fontSize={11} width={54} tickFormatter={(value) => chartMetric === 'orders' ? fmt(Number(value)) : compact(Number(value))} />
                    <Tooltip formatter={(value: number) => chartMetric === 'orders' ? fmt(value) : money(value)} />
                    <Area type="monotone" dataKey={chartMetric} stroke={chartMetric === 'revenue' ? '#4f46e5' : chartMetric === 'profit' ? '#10b981' : '#f59e0b'} fill={chartMetric === 'revenue' ? 'url(#dashRevenue)' : 'transparent'} strokeWidth={2} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="d-flex align-items-center gap-2 mb-3">
                <div className="panel-title">Kategoriya bo'yicha savdo · {dashboard.range.label}</div>
              <InfoHint text="To'langan order itemlari kategoriya bo'yicha guruhlanadi. Kitoblar o'z categorylari bilan chiqadi, kanselyariya esa ichki bo'limlariga bo'linmay bitta Kanselyariya sifatida hisoblanadi. Gift sovg'alar kirmaydi." />
            </div>
            {dashboard.categoryShare.length ? (
              <>
                <ResponsiveContainer width="100%" height={190}>
                  <PieChart>
                    <Pie data={dashboard.categoryShare} dataKey="value" innerRadius={52} outerRadius={78} paddingAngle={3}>
                      {dashboard.categoryShare.map((row) => <Cell key={row.name} fill={row.color} />)}
                    </Pie>
                    <Tooltip formatter={(value: number) => `${value}%`} />
                  </PieChart>
                </ResponsiveContainer>
                {dashboard.categoryShare.map((row) => (
                  <div key={row.name} className="d-flex justify-content-between py-2 border-bottom small">
                    <span><i className="bi bi-circle-fill me-2" style={{ color: row.color }}></i>{row.name}</span>
                    <strong>{row.value}% · {money(row.revenue)}</strong>
                  </div>
                ))}
              </>
            ) : <div className="text-muted small">To'langan order itemlari hali topilmadi.</div>}
          </div>
        </div>
      </div>

      <div className="row g-3 mb-3">
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

      <div className="row g-3">
        <div className="col-xl-4">
          <RankPanel title={`Top mahsulotlar · ${dashboard.range.label}`} help="Tanlangan davrda yakunlangan savdolardagi mahsulotlar dona bo'yicha saralanadi. Gift turidagi sovg'alar tekin bo'lgani uchun bu ro'yxatga kirmaydi." rows={dashboard.topProducts.map((row) => ({ name: row.name, meta: `${fmt(row.quantity)} dona`, value: money(row.revenue) }))} />
        </div>
        <div className="col-xl-4">
          <RecentOrders rows={dashboard.recentOrders} />
        </div>
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">Operatsion ogohlantirishlar</div>
            {dashboard.alerts.length ? dashboard.alerts.map((alert) => (
              <Link href={alert.url || '/boshqaruv'} key={alert.title} className="text-decoration-none d-flex gap-2 align-items-start p-2 border-bottom">
                <i className={`bi ${alert.icon} text-warning`}></i>
                <span><strong className="d-block text-body">{alert.title}</strong><small className="text-muted">{alert.text}</small></span>
              </Link>
            )) : <div className="text-muted small">Kritik ogohlantirish yo'q.</div>}
          </div>
        </div>
      </div>

      <div className="row g-3 mt-1">
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
    { label: 'Yakuniy savdo ulushi', value: `${b.paidRate || 0}%`, meta: 'Yakuniy savdo / jami', icon: 'bi-credit-card', color: '#10b981', help: businessHelps['Yakuniy savdo ulushi'] },
    { label: 'Yakunlash ulushi', value: `${b.completionRate || 0}%`, meta: 'Yakunlangan / jami', icon: 'bi-check2-circle', color: '#4f46e5', help: businessHelps['Yakunlash ulushi'] },
    { label: 'Bekor ulushi', value: `${b.cancellationRate || 0}%`, meta: 'Bekor va qaytgan', icon: 'bi-x-circle', color: '#ef4444', help: businessHelps['Bekor ulushi'] },
    { label: 'Qayta xaridor', value: `${b.repeatBuyerRate || 0}%`, meta: `${fmt(b.repeatBuyers)} foydalanuvchi`, icon: 'bi-arrow-repeat', color: '#7c3aed', help: businessHelps['Qayta xaridor'] },
    { label: 'Xaridorlar', value: fmt(b.buyingUsers), meta: 'Paid order qilgan', icon: 'bi-people', color: '#06b6d4', help: businessHelps.Xaridorlar },
    { label: 'Karta ulangan', value: fmt(b.cardUsers), meta: 'Tasdiqlangan karta', icon: 'bi-credit-card-2-front', color: '#ec4899', help: businessHelps['Karta ulangan'] },
    { label: "Order / xaridor", value: String(b.avgOrdersPerBuyer || 0), meta: "O'rtacha chastota", icon: 'bi-bag-check', color: '#f59e0b', help: businessHelps["Order / xaridor"] },
    { label: "Daromad / xaridor", value: money(b.avgRevenuePerBuyer), meta: "O'rtacha paid revenue", icon: 'bi-cash-stack', color: '#059669', help: businessHelps["Daromad / xaridor"] },
  ];

  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="panel-title">Marketplace KPI</div>
          <small className="text-muted">Faqat real order va foydalanuvchi ma'lumotlaridan hisoblangan</small>
        </div>
      </div>
      <div className="row g-2">
        {rows.map((row) => (
          <div className="col-xl-3 col-md-6" key={row.label}>
            <div className="mini-stat h-100">
              <i className={`bi ${row.icon}`} style={{ color: row.color }}></i>
              <span>
                <span className="d-inline-flex align-items-center gap-1">{row.label}<InfoHint text={row.help} /></span>
                <small className="d-block text-muted">{row.meta}</small>
              </span>
              <strong style={{ color: row.color }}>{row.value}</strong>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function PlatformAnalysis({ rows }: { rows: DashboardPayload['platformAnalysis'] }) {
  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="d-flex align-items-center gap-2">
            <div className="panel-title">App platforma tahlili</div>
            <InfoHint text="Userning oxirgi connected device platformasi olinadi. Aktiv user - oxirgi 30 kunda device yangilangan user, Orders va Daromad - shu platformadagi userlarning paid orderlari, Conv - paid buyer / aktiv user." />
          </div>
          <small className="text-muted">Android va iOS bo'yicha real device, order va tushum signallari</small>
        </div>
      </div>
      <div className="row g-2">
        {rows.length ? rows.map((row) => (
          <div className="col-xl-6" key={row.name}>
            <div className="platform-card h-100">
              <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div className="d-flex align-items-center gap-2" style={{ minWidth: 0 }}>
                  <span className="platform-icon" style={{ color: row.color }}><i className={`bi ${row.icon}`}></i></span>
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{row.name}</div>
                    <small className="text-muted">{row.version} · Conv {row.conversion}%</small>
                  </div>
                </div>
                <span className="chip chip-info">App</span>
              </div>
              <div className="platform-grid">
                <PlatformStat label="Faol user" value={fmt(row.activeUsers)} />
                <PlatformStat label="Orders" value={fmt(row.orders)} />
                <PlatformStat label="Daromad" value={money(row.revenue)} />
                <PlatformStat label="Crash" value={`${row.crashRate}%`} />
                <PlatformStat label="Session" value={formatDuration(row.avgSessionSeconds)} />
              </div>
            </div>
          </div>
        )) : <div className="text-muted small">Platform device ma'lumotlari topilmadi.</div>}
      </div>
    </div>
  );
}

function PlatformStat({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <strong>{value}</strong>
      <span>{label}</span>
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

function UnitEconomics({ data }: { data: DashboardPayload['unitEconomics'] }) {
  const ratioTone = data.ltvCacRatio >= 3 ? '#10b981' : data.ltvCacRatio >= 1 ? '#f59e0b' : '#ef4444';
  const refundTone = data.refundRate > 5 ? '#ef4444' : data.refundRate > 2 ? '#f59e0b' : '#10b981';
  const cancelTone = data.cancelRate > 15 ? '#ef4444' : data.cancelRate > 8 ? '#f59e0b' : '#10b981';
  const marginTone = data.marginPct >= 0 ? '#059669' : '#ef4444';

  const cards = [
    { l: 'CAC', icon: 'bi-cash-coin', v: data.hasMarketingData ? money(data.cac) : '—', s: data.hasMarketingData ? `${fmt(data.newBuyers)} yangi xaridor` : 'Marketing xarajat kiritilmagan', c: '#6366f1', help: "Customer Acquisition Cost: davrdagi marketing xarajati / yangi xaridorlar. Marketing xarajati Chiqimlar bo'limidagi \"Marketing va reklama\" kategoriyasidan olinadi." },
    { l: 'LTV (margin)', icon: 'bi-gem', v: money(data.ltv), s: `ARPU ${money(data.arpu)}`, c: '#10b981', help: "Lifetime Value: har bir xaridorga to'g'ri keladigan umumiy platforma marjasi (contribution / jami xaridorlar). ARPU — o'rtacha yalpi tushum/xaridor." },
    { l: 'LTV : CAC', icon: 'bi-speedometer2', v: data.hasMarketingData ? `${data.ltvCacRatio}×` : '—', s: data.ltvCacRatio >= 3 ? "Sog'lom (≥3)" : data.ltvCacRatio >= 1 ? "O'rtacha" : 'Past', c: ratioTone, help: "Investor uchun asosiy nisbat. ≥3 sog'lom, 1–3 o'rtacha, <1 — mijoz jalb qilish zarar keltiryapti." },
    { l: 'Payback', icon: 'bi-arrow-repeat', v: data.hasMarketingData && data.paybackOrders > 0 ? `${data.paybackOrders} order` : '—', s: 'CAC ni qoplash', c: '#7c3aed', help: 'CAC ni qoplash uchun bitta xaridordan necha order kerak (CAC / contribution-per-order).' },
    { l: 'Margin / order', icon: 'bi-cash-stack', v: money(data.contributionPerOrder), s: `Gross ${money(data.grossPerOrder)}`, c: marginTone, help: "Har bir yakuniy orderdan qoladigan platforma marjasi (soliqdan oldingi contribution). Gross — o'rtacha order summasi." },
    { l: 'Gross margin', icon: 'bi-percent', v: `${data.marginPct}%`, s: 'Contribution / tushum', c: marginTone, help: 'Contribution margin yalpi tushumga nisbatan foizda. Manfiy bo\'lsa xarajat tushumdan oshgan.' },
    { l: 'Refund rate', icon: 'bi-arrow-return-left', v: `${data.refundRate}%`, s: `${fmt(data.refundOrders)} order · ${money(data.refundAmount)}`, c: refundTone, help: 'Qaytarilgan (refund) orderlar ulushi va summasi. Sold.refund_total_amount asosida.' },
    { l: 'Cancel rate', icon: 'bi-x-circle', v: `${data.cancelRate}%`, s: 'Bekor + qaytgan', c: cancelTone, help: 'Davrda yaratilgan orderlardan bekor qilingan yoki qaytganlari ulushi.' },
    { l: 'Repeat', icon: 'bi-arrow-repeat', v: `${data.repeatRate}%`, s: `${fmt(data.repeatBuyers)} qaytgan xaridor`, c: '#ec4899', help: "Bir martadan ko'p xarid qilgan xaridorlar ulushi (lifetime)." },
  ];

  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="d-flex align-items-center gap-2">
            <div className="panel-title">Unit economics</div>
            <InfoHint text="Investor va operator uchun birlik iqtisodiyoti. CAC/refund/cancel tanlangan davr bilan, LTV va repeat lifetime bo'yicha. Hammasi real order, xarajat va refund ma'lumotlaridan hisoblanadi." />
          </div>
          <small className="text-muted">CAC · LTV · margin/order · refund/cancel · repeat</small>
        </div>
        {data.hasMarketingData ? null : <span className="chip chip-warning">CAC uchun Chiqimlarga marketing xarajat kiriting</span>}
      </div>
      <div className="row g-2">
        {cards.map((card) => (
          <div className="col-xl-2 col-lg-3 col-md-4 col-6" key={card.l}>
            <div className="mini-stat h-100">
              <i className={`bi ${card.icon}`} style={{ color: card.c }}></i>
              <span>
                <span className="d-inline-flex align-items-center gap-1">{card.l}<InfoHint text={card.help} /></span>
                <small className="d-block text-muted">{card.s}</small>
              </span>
              <strong style={{ color: card.c }}>{card.v}</strong>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function FunnelPanel({ funnel }: { funnel: DashboardPayload['funnel'] }) {
  const stages = funnel.stages || [];
  const max = Math.max(1, ...stages.map((stage) => stage.value));

  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="d-flex align-items-center gap-2">
            <div className="panel-title">Xarid voronkasi</div>
            <InfoHint text="Ko'rish → buyurtma → to'lov → yakunlash. Ko'rishlar real mahsulot ko'rish eventlaridan (product_view_logs), qolgan bosqichlar bir order kohortasi (yaratilgan sana) bo'yicha. Foiz — oldingi bosqichdan konversiya." />
          </div>
          <small className="text-muted">
            {funnel.hasViewData
              ? `${fmt(funnel.uniqueViewers)} unikal ko'ruvchi · hozir savatda ${fmt(funnel.cartUsers)} mijoz`
              : `Ko'rish logi hali to'planmagan · hozir savatda ${fmt(funnel.cartUsers)} mijoz`}
          </small>
        </div>
        <span className="chip chip-success">Ko'rish→to'lov {funnel.viewToPaid}%</span>
      </div>
      {stages.length ? (
        <div className="d-flex flex-column gap-2">
          {stages.map((stage, index) => (
            <div key={stage.key}>
              <div className="d-flex justify-content-between align-items-center mb-1">
                <span className="fw-semibold small">{index + 1}. {stage.label}</span>
                <span className="small text-muted">
                  {fmt(stage.value)}
                  {index > 0 ? ` · ${stage.rate}% konversiya` : ''}
                  {stage.drop > 0 ? ` · −${stage.drop}%` : ''}
                </span>
              </div>
              <div className="progress" style={{ height: 22, background: '#f1f5f9' }}>
                <div className="progress-bar" style={{ width: `${Math.max(3, stage.value / max * 100)}%`, background: stage.color, fontWeight: 600, fontSize: 12 }}>
                  {fmt(stage.value)}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : <div className="text-muted small">Voronka uchun ma'lumot yo'q.</div>}
    </div>
  );
}

function CohortPanel({ retention }: { retention: DashboardPayload['retention'] }) {
  const cohorts = retention.cohorts || [];
  const offsets = Array.from({ length: (retention.maxOffset ?? 5) + 1 }, (_, index) => index);
  const cellStyle = (value: number | null) => {
    if (value === null || value === undefined) return { background: 'transparent', color: '#cbd5e1' };
    const alpha = Math.min(1, 0.12 + value / 100 * 0.88);
    return { background: `rgba(79,70,229,${alpha})`, color: value > 45 ? '#fff' : '#1e293b' };
  };

  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="d-flex align-items-center gap-2">
            <div className="panel-title">Retention kogortalari</div>
            <InfoHint text="Har oy birinchi marta xarid qilgan mijozlar keyingi oylarda yana xarid qildimi. M+0 doim 100% (birinchi oy). Faqat to'langan va mijoz qabul qilgan savdolar hisobga olinadi. Bo'sh katak — hali kelmagan oy." />
          </div>
          <small className="text-muted">Qayta xarid ulushi (%) — oylik kogortalar</small>
        </div>
      </div>
      {cohorts.length ? (
        <div style={{ overflowX: 'auto' }}>
          <table className="table table-sm mb-0 text-center align-middle" style={{ minWidth: 620 }}>
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
                  <td className="text-start fw-semibold">{cohort.month}</td>
                  <td>{fmt(cohort.size)}</td>
                  {offsets.map((offset) => {
                    const value = cohort.retention[offset] ?? null;
                    return (
                      <td key={offset} style={{ ...cellStyle(value), borderRadius: 6, fontWeight: 600, fontSize: 12 }}>
                        {value === null ? '·' : `${value}%`}
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : <div className="text-muted small">Kogorta uchun yetarli xarid tarixi yo'q.</div>}
    </div>
  );
}

function SellerScorecard({ rows }: { rows: DashboardPayload['sellerScorecard'] }) {
  return (
    <div className="card-panel mb-3">
      <div className="panel-head">
        <div>
          <div className="d-flex align-items-center gap-2">
            <div className="panel-title">Sotuvchilar reytingi</div>
            <InfoHint text="Top sotuvchilar tushum bo'yicha. Bekor % — bekor qilingan orderlar ulushi, Qabul — buyurtmani qabul qilishgacha o'rtacha daqiqa, Reyting va Reputatsiya seller profilidan olinadi." />
          </div>
          <small className="text-muted">Tushum bo'yicha top {rows.length || 8} sotuvchi</small>
        </div>
      </div>
      {rows.length ? (
        <div style={{ overflowX: 'auto' }}>
          <table className="table table-sm table-hover align-middle mb-0" style={{ minWidth: 720 }}>
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
                    <a href={seller.url || '#'} className="text-decoration-none text-body d-flex align-items-center gap-2">
                      {seller.avatar
                        ? <img src={seller.avatar} alt="" width={26} height={26} className="rounded-circle" style={{ objectFit: 'cover' }} />
                        : <span className="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style={{ width: 26, height: 26 }}><i className="bi bi-shop small"></i></span>}
                      <span className="text-truncate" style={{ maxWidth: 190 }}>{seller.name}</span>
                    </a>
                  </td>
                  <td className="text-end">{fmt(seller.orders)}</td>
                  <td className="text-end fw-semibold">{money(seller.revenue)}</td>
                  <td className="text-end"><span className={seller.cancelRate > 15 ? 'text-danger fw-semibold' : 'text-muted'}>{seller.cancelRate}%</span></td>
                  <td className="text-end">{seller.acceptMinutes === null ? '—' : `${fmt(seller.acceptMinutes)}m`}</td>
                  <td className="text-end">{seller.rating ? `${seller.rating.toFixed(1)}★` : '—'}{seller.ratingCount ? <small className="text-muted"> ({fmt(seller.ratingCount)})</small> : null}</td>
                  <td className="text-end">{fmt(seller.reputation)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : <div className="text-muted small">Sotuvchi order ma'lumotlari topilmadi.</div>}
    </div>
  );
}

function Metric({ label, value = 0, icon, color, href, help }: { label: string; value?: number; icon: string; color: string; href: string; help?: string }) {
  return (
    <div className="col-xl-2 col-md-4 col-6">
      <Link href={href} className="stat-card text-decoration-none d-block" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div className="d-flex align-items-center gap-1" style={{ minWidth: 0 }}>
            <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
            {help ? <InfoHint text={help} /> : null}
          </div>
          <i className={`bi ${icon}`} style={{ color, fontSize: 18, flexShrink: 0 }}></i>
        </div>
        <div className="text-body" style={{ fontSize: 22, fontWeight: 800 }}>{fmt(value)}</div>
      </Link>
    </div>
  );
}

function PeriodCard({ label, value, delta, icon, color, help }: { label: string; value: string; delta: number | null; icon: string; color: string; help?: string }) {
  return (
    <div className="col-xl-3 col-md-6">
      <div className="stat-card" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div className="d-flex align-items-center gap-1">
            <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
            {help ? <InfoHint text={help} /> : null}
          </div>
          <i className={`bi ${icon}`} style={{ color, fontSize: 18 }}></i>
        </div>
        <div className="text-body" style={{ fontSize: 22, fontWeight: 800 }}>{value}</div>
        {delta === null ? (
          <div className="text-muted" style={{ fontSize: 11 }}><i className="bi bi-infinity me-1"></i>Barcha davr</div>
        ) : (
          <div className={`stat-trend ${delta >= 0 ? 'up' : 'down'}`} style={{ fontSize: 11 }}>
            <i className={`bi ${delta >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'}`}></i> {delta >= 0 ? '+' : ''}{delta.toFixed(1)}%
          </div>
        )}
      </div>
    </div>
  );
}

function CompactMetric({ label, value = 0, icon, color, href, help }: { label: string; value?: number; icon: string; color: string; href: string; help?: string }) {
  return (
    <div className="col-xl-2 col-md-4 col-6">
      <Link href={href} className="stat-card text-decoration-none d-block h-100" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div className="d-flex align-items-center gap-1" style={{ minWidth: 0 }}>
            <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
            {help ? <InfoHint text={help} /> : null}
          </div>
          <i className={`bi ${icon}`} style={{ color, fontSize: 18, flexShrink: 0 }}></i>
        </div>
        <div className="text-body" style={{ fontSize: 22, fontWeight: 800 }}>{fmt(value)}</div>
      </Link>
    </div>
  );
}

function DistributionPanel({ title, rows, help }: { title: string; rows: Array<{ name: string; value: string }>; help?: string }) {
  return <div className="col-xl-4"><div className="card-panel h-100"><div className="d-flex align-items-center gap-2 mb-3"><div className="panel-title">{title}</div>{help ? <InfoHint text={help} /> : null}</div>{rows.length ? rows.slice(0, 8).map((row) => <div className="d-flex justify-content-between gap-3 py-2 border-bottom small" key={row.name}><span className="text-muted text-truncate">{row.name}</span><strong className="text-nowrap">{row.value}</strong></div>) : <div className="text-muted small">Ma'lumot topilmadi.</div>}</div></div>;
}

function FinancialPanel({ dashboard }: { dashboard: DashboardPayload }) {
  const f = dashboard.financial;
  const rows = [
    { label: 'Yakuniy savdo tushumi', raw: f.grossRevenue, color: '#10b981' },
    { label: 'Delivery income', raw: f.deliveryIncome, color: '#4f46e5' },
    { label: 'Seller commission', raw: f.commission, color: '#7c3aed' },
    { label: 'Promo discount', raw: -f.promoDiscount, color: '#ef4444' },
    { label: 'Cashback', raw: -f.cashback, color: '#ef4444' },
    { label: 'Courier payout', raw: -f.courierPayout, color: '#f59e0b' },
    { label: 'Kiritilgan chiqimlar', raw: -f.manualExpenses, color: '#ef4444' },
    { label: 'Provider komissiyasi', raw: -f.providerFee, color: '#f97316' },
    { label: 'Soliq', raw: -f.tax, color: '#dc2626' },
    { label: 'Marketplace marjasi', raw: f.platformProfit, color: '#059669' },
  ];

  return (
    <div className="col-xl-6">
      <div className="card-panel h-100">
        <div className="panel-head">
          <div>
            <div className="panel-title">Real P&L signali · {dashboard.range.label}</div>
            <small className="text-muted">Tanlangan davrdagi net komissiya, delivery, chegirma, kuryer, ledger, provider va soliq asosida</small>
          </div>
          <span className={`chip ${f.platformProfit >= 0 ? 'chip-success' : 'chip-danger'}`}>Net {f.netMargin || 0}%</span>
        </div>
        <div className="row g-2">
          {rows.map((row) => (
            <div className="col-md-6" key={row.label}>
              <div className="mini-stat">
                <i className="bi bi-dot" style={{ color: row.color }}></i>
                <span className="d-inline-flex align-items-center gap-1">{row.label}<InfoHint text={financialHelps[row.label]} /></span>
                <strong style={{ color: row.color }}>{money(Number(row.raw))}</strong>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function StatusPanel({ title, counts, labels }: { title: string; counts: Record<string, number>; labels: Record<string, string> }) {
  return (
    <div className="col-xl-2 col-md-4">
      <div className="card-panel h-100">
        <div className="panel-title mb-3">{title}</div>
        {Object.entries(labels).map(([key, label]) => (
          <div className="d-flex justify-content-between py-1 small border-bottom" key={key}>
            <span className="text-muted">{label}</span>
            <strong>{fmt(counts[key] || 0)}</strong>
          </div>
        ))}
      </div>
    </div>
  );
}

function RankPanel({ title, rows, help }: { title: string; rows: Array<{ name: string; meta: string; value: string }>; help?: string }) {
  return (
    <div className="card-panel h-100">
      <div className="d-flex align-items-center gap-2 mb-3"><div className="panel-title">{title}</div>{help ? <InfoHint text={help} /> : null}</div>
      {rows.length ? rows.map((row, index) => (
        <div className="d-flex align-items-center gap-2 py-2 border-bottom" key={`${row.name}-${index}`}>
          <span className="chip chip-purple">{index + 1}</span>
          <div style={{ minWidth: 0, flex: 1 }}>
            <div className="fw-semibold text-truncate">{row.name}</div>
            <small className="text-muted">{row.meta}</small>
          </div>
          <strong className="text-success small">{row.value}</strong>
        </div>
      )) : <div className="text-muted small">Ma'lumot topilmadi.</div>}
    </div>
  );
}

function RecentOrders({ rows }: { rows: DashboardPayload['recentOrders'] }) {
  return (
    <div className="card-panel h-100">
      <div className="panel-title mb-3">Oxirgi buyurtmalar</div>
      {rows.length ? rows.slice(0, 8).map((row) => (
        <div className="d-flex align-items-center gap-2 py-2 border-bottom" key={row.id}>
          <span className="chip chip-gray">#{row.id}</span>
          <div style={{ minWidth: 0, flex: 1 }}>
            <div className="fw-semibold text-truncate">{row.customer || 'Mijoz'}</div>
            <small className="text-muted">{row.status} · {row.updated_at || ''}</small>
          </div>
          <strong className="text-success small">{money(row.amount)}</strong>
          {row.url ? <a href={row.url} className="btn btn-sm btn-light" title="Buyurtmani ochish"><i className="bi bi-eye"></i></a> : null}
        </div>
      )) : <div className="text-muted small">Buyurtma topilmadi.</div>}
    </div>
  );
}
