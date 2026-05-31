import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
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

interface DashboardPayload {
  generatedAt: string;
  metrics: Record<string, number>;
  periods: Record<string, { revenue: number; orders: number; users: number; aov: number }>;
  financial: Record<string, number>;
  business: Record<string, number>;
  status: { main: Record<string, number>; seller: Record<string, number>; courier: Record<string, number> };
  salesByMonth: Array<{ month: string; revenue: number; profit: number; orders: number }>;
  hourlySales: Array<{ hour: string; revenue: number; orders: number }>;
  categoryShare: Array<{ name: string; value: number; revenue: number; color: string }>;
  topProducts: Array<{ name: string; quantity: number; revenue: number }>;
  recentOrders: Array<{ id: number; customer?: string; amount: number; status: string; updated_at?: string; url?: string }>;
  paymentSplit: Array<{ name: string; count: number; share: number; color: string }>;
  deliverySplit: Array<{ name: string; count: number; revenue: number }>;
  regions: Array<{ name: string; value: number; revenue: number; color: string }>;
  platformAnalysis: Array<{ name: string; icon: string; color: string; version: string; activeUsers: number; orders: number; revenue: number; conversion: number; crashRate: number; avgSessionSeconds: number }>;
  alerts: Array<{ level: string; icon: string; title: string; text: string; url?: string }>;
}

const emptyDashboard: DashboardPayload = {
  generatedAt: '--',
  metrics: {},
  periods: {},
  financial: {},
  business: {},
  status: { main: {}, seller: {}, courier: {} },
  salesByMonth: [],
  hourlySales: [],
  categoryShare: [],
  topProducts: [],
  recentOrders: [],
  paymentSplit: [],
  deliverySplit: [],
  regions: [],
  platformAnalysis: [],
  alerts: [],
};

const change = (current = 0, previous = 0) => previous > 0 ? ((current - previous) / previous * 100) : 0;

const metricHelps: Record<string, string> = {
  orders: "Bazadagi barcha asosiy buyurtmalar soni. Statusidan qat'i nazar jami orderlar sanaladi.",
  paidOrders: "To'lovi paid/success/completed bo'lgan yoki yakunlangan buyurtmalar. Moliyaviy hisoblar asosan shundan olinadi.",
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
  revenue: "Tanlangan davrda to'langan buyurtmalarning umumiy summasi. Pastdagi foiz oldingi shu davr bilan solishtiradi.",
  orders: "Tanlangan davrda tushgan buyurtmalar soni. Pastdagi foiz oldingi davrga nisbatan o'zgarish.",
  aov: "O'rtacha chek: tanlangan davrdagi tushum buyurtmalar soniga bo'linadi.",
  users: "Tanlangan davrda yangi ro'yxatdan o'tgan foydalanuvchilar soni.",
};

const businessHelps: Record<string, string> = {
  "To'lov ulushi": "Jami buyurtmalardan nechtasi haqiqatan to'langanini ko'rsatadi. Paid / jami order.",
  'Yakunlash ulushi': "Jami buyurtmalardan yakunlanganlari ulushi. Admin operatsiya sifati uchun signal.",
  'Bekor ulushi': "Bekor qilingan yoki qaytgan buyurtmalar ulushi. Ko'paysa logistika yoki mahsulot tomoni tekshiriladi.",
  'Qayta xaridor': "Bir martadan ko'p xarid qilgan foydalanuvchilar ulushi.",
  'Xaridorlar': "Kamida bitta to'langan buyurtma qilgan foydalanuvchilar.",
  'Karta ulangan': "Tizimda tasdiqlangan karta bog'lagan foydalanuvchilar.",
  "Order / xaridor": "To'langan buyurtmalar soni xaridorlar soniga bo'linadi.",
  "Daromad / xaridor": "To'langan buyurtmalar tushumi xaridorlar soniga bo'linadi.",
};

const financialHelps: Record<string, string> = {
  'Paid order tushumi': "To'lovi tasdiqlangan buyurtmalarning umumiy summasi. Bu hali sof foyda emas.",
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
  const [period, setPeriod] = useState<'today' | 'week' | 'month'>('month');
  const [chartMetric, setChartMetric] = useState<'revenue' | 'profit' | 'orders'>('revenue');
  const previousKey = period === 'today' ? 'yesterday' : period === 'week' ? 'lastWeek' : 'lastMonth';
  const current = dashboard.periods[period] || { revenue: 0, orders: 0, users: 0, aov: 0 };
  const previous = dashboard.periods[previousKey] || { revenue: 0, orders: 0, users: 0, aov: 0 };

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
        <div className="d-flex gap-2 align-items-center">
          <span className="text-muted small">Davr kartalari</span>
          <div className="btn-group btn-group-sm">
            {(['today', 'week', 'month'] as const).map((item) => (
              <button key={item} className={`btn ${period === item ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setPeriod(item)}>
                {item === 'today' ? 'Bugun' : item === 'week' ? 'Hafta' : 'Oy'}
              </button>
            ))}
          </div>
          <Link href="/boshqaruv/live" className="btn btn-outline-secondary btn-sm"><i className="bi bi-broadcast me-1"></i>Live</Link>
        </div>
      </div>

      <div className="row g-2 mb-3">
        <Metric label="Buyurtmalar" value={dashboard.metrics.orders} icon="bi-receipt" color="#4f46e5" href="/boshqaruv/orders" help={metricHelps.orders} />
        <Metric label="To'langan order" value={dashboard.metrics.paidOrders} icon="bi-credit-card" color="#10b981" href="/boshqaruv/orders" help={metricHelps.paidOrders} />
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
        <PeriodCard label="Daromad" value={money(current.revenue)} delta={change(current.revenue, previous.revenue)} icon="bi-cash-coin" color="#4f46e5" help={periodHelps.revenue} />
        <PeriodCard label="Buyurtmalar" value={fmt(current.orders)} delta={change(current.orders, previous.orders)} icon="bi-bag-check" color="#10b981" help={periodHelps.orders} />
        <PeriodCard label="O'rtacha chek" value={money(current.aov)} delta={change(current.aov, previous.aov)} icon="bi-receipt" color="#f59e0b" help={periodHelps.aov} />
        <PeriodCard label="Yangi userlar" value={fmt(current.users)} delta={change(current.users, previous.users)} icon="bi-person-plus" color="#ec4899" help={periodHelps.users} />
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-8">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="d-flex align-items-center gap-2">
                  <div className="panel-title">12 oylik real savdo</div>
                  <InfoHint text="Har oy bo'yicha to'langan buyurtmalar olinadi. Daromad - paid order summasi, Signal - shu oy uchun taxminiy marketplace foyda signali, Order - paid order soni." />
                </div>
                <small className="text-muted">To'langan buyurtmalar va platform signal</small>
              </div>
              <div className="btn-group btn-group-sm">
                <button className={`btn ${chartMetric === 'revenue' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('revenue')}>Daromad</button>
                <button className={`btn ${chartMetric === 'profit' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('profit')}>Signal</button>
                <button className={`btn ${chartMetric === 'orders' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('orders')}>Order</button>
              </div>
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <AreaChart data={dashboard.salesByMonth}>
                <defs>
                  <linearGradient id="dashRevenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#4f46e5" stopOpacity={0.45} />
                    <stop offset="100%" stopColor="#4f46e5" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#eef0f4" vertical={false} />
                <XAxis dataKey="month" stroke="#94a3b8" fontSize={11} />
                <YAxis stroke="#94a3b8" fontSize={11} tickFormatter={(v) => `${(Number(v) / 1000000).toFixed(1)}M`} />
                <Tooltip formatter={(value: number) => chartMetric === 'orders' ? fmt(value) : money(value)} />
                <Area type="monotone" dataKey={chartMetric} stroke={chartMetric === 'revenue' ? '#4f46e5' : chartMetric === 'profit' ? '#10b981' : '#f59e0b'} fill={chartMetric === 'revenue' ? 'url(#dashRevenue)' : 'transparent'} strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="d-flex align-items-center gap-2 mb-3">
                <div className="panel-title">Kategoriya bo'yicha savdo</div>
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

      <div className="row g-3">
        <div className="col-xl-4">
          <RankPanel title="Top mahsulotlar" help="To'langan buyurtmalardagi mahsulotlar sotilgan dona bo'yicha saralanadi. Gift turidagi sovg'alar tekin bo'lgani uchun bu ro'yxatga kirmaydi." rows={dashboard.topProducts.map((row) => ({ name: row.name, meta: `${fmt(row.quantity)} dona`, value: money(row.revenue) }))} />
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
        <DistributionPanel title="To'lov kesimi" help="Buyurtmalar to'lov statusi bo'yicha guruhlanadi: qancha order va jami ichidagi ulushi." rows={dashboard.paymentSplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${row.share}%` }))} />
        <DistributionPanel title="Yetkazish kesimi" help="Buyurtmalar yetkazish turi bo'yicha guruhlanadi. Yonidagi summa shu turdagi buyurtmalar tushumi." rows={dashboard.deliverySplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${money(row.revenue)}` }))} />
        <DistributionPanel title="Hududlar" help="Buyurtma address snapshotidan viloyat/shahar nomi olinadi. Noma'lum addresslar alohida guruhga tushadi." rows={dashboard.regions.map((row) => ({ name: row.name, value: `${fmt(row.value)} ta · ${money(row.revenue)}` }))} />
      </div>
    </div>
  );
}

function BusinessKpis({ dashboard }: { dashboard: DashboardPayload }) {
  const b = dashboard.business;
  const rows = [
    { label: "To'lov ulushi", value: `${b.paidRate || 0}%`, meta: 'Paid / jami order', icon: 'bi-credit-card', color: '#10b981', help: businessHelps["To'lov ulushi"] },
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

function PeriodCard({ label, value, delta, icon, color, help }: { label: string; value: string; delta: number; icon: string; color: string; help?: string }) {
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
        <div className={`stat-trend ${delta >= 0 ? 'up' : 'down'}`} style={{ fontSize: 11 }}>
          <i className={`bi ${delta >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'}`}></i> {delta >= 0 ? '+' : ''}{delta.toFixed(1)}%
        </div>
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
    { label: "Paid order tushumi", raw: f.grossRevenue, color: '#10b981' },
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
            <div className="panel-title">Real P&L signali</div>
            <small className="text-muted">Net komissiya, delivery, chegirma, kuryer, ledger, provider va soliq asosida</small>
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
