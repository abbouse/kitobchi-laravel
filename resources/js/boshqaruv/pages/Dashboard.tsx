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
  recentOrders: Array<{ id: number; customer?: string; amount: number; status: string; updated_at?: string }>;
  paymentSplit: Array<{ name: string; count: number; share: number; color: string }>;
  deliverySplit: Array<{ name: string; count: number; revenue: number }>;
  regions: Array<{ name: string; value: number; revenue: number; color: string }>;
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
  alerts: [],
};

const change = (current = 0, previous = 0) => previous > 0 ? ((current - previous) / previous * 100) : 0;

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
        <Metric label="Buyurtmalar" value={dashboard.metrics.orders} icon="bi-receipt" color="#4f46e5" href="/boshqaruv/orders" />
        <Metric label="To'langan order" value={dashboard.metrics.paidOrders} icon="bi-credit-card" color="#10b981" href="/boshqaruv/orders" />
        <Metric label="Foydalanuvchilar" value={dashboard.metrics.users} icon="bi-people" color="#06b6d4" href="/boshqaruv/users" />
        <Metric label="Kitoblar" value={dashboard.metrics.books} icon="bi-book" color="#f59e0b" href="/boshqaruv/books" />
        <Metric label="Sotuvchilar" value={dashboard.metrics.sellers} icon="bi-shop" color="#ec4899" href="/boshqaruv/sellers" />
        <Metric label="Kuryerlar" value={dashboard.metrics.couriers} icon="bi-bicycle" color="#7c3aed" href="/boshqaruv/couriers" />
      </div>

      <div className="row g-2 mb-3">
        <CompactMetric label="Premium user" value={dashboard.metrics.premiumUsers} icon="bi-stars" color="#7c3aed" href="/boshqaruv/users" />
        <CompactMetric label="Online user" value={dashboard.metrics.onlineUsers} icon="bi-broadcast" color="#10b981" href="/boshqaruv/users" />
        <CompactMetric label="Kanselyariya" value={dashboard.metrics.stationeries} icon="bi-pencil-square" color="#f59e0b" href="/boshqaruv/stationeries" />
        <CompactMetric label="Pending seller" value={dashboard.metrics.pendingSellers} icon="bi-hourglass-split" color="#ec4899" href="/boshqaruv/sellers" />
        <CompactMetric label="Support ticket" value={dashboard.metrics.tickets} icon="bi-headset" color="#06b6d4" href="/boshqaruv/tickets" />
        <CompactMetric label="Shikoyatlar" value={dashboard.metrics.complaints} icon="bi-exclamation-triangle" color="#ef4444" href="/boshqaruv/shikoyatlar" />
      </div>

      <div className="row g-2 mb-3">
        <PeriodCard label="Daromad" value={money(current.revenue)} delta={change(current.revenue, previous.revenue)} icon="bi-cash-coin" color="#4f46e5" />
        <PeriodCard label="Buyurtmalar" value={fmt(current.orders)} delta={change(current.orders, previous.orders)} icon="bi-bag-check" color="#10b981" />
        <PeriodCard label="O'rtacha chek" value={money(current.aov)} delta={change(current.aov, previous.aov)} icon="bi-receipt" color="#f59e0b" />
        <PeriodCard label="Yangi userlar" value={fmt(current.users)} delta={change(current.users, previous.users)} icon="bi-person-plus" color="#ec4899" />
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-8">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">12 oylik real savdo</div>
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
            <div className="panel-title mb-3">Mahsulot turi bo'yicha ulush</div>
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

      <div className="row g-3">
        <div className="col-xl-4">
          <RankPanel title="Top mahsulotlar" rows={dashboard.topProducts.map((row) => ({ name: row.name, meta: `${fmt(row.quantity)} dona`, value: money(row.revenue) }))} />
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
        <DistributionPanel title="To'lov kesimi" rows={dashboard.paymentSplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${row.share}%` }))} />
        <DistributionPanel title="Yetkazish kesimi" rows={dashboard.deliverySplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${money(row.revenue)}` }))} />
        <DistributionPanel title="Hududlar" rows={dashboard.regions.map((row) => ({ name: row.name, value: `${fmt(row.value)} ta · ${money(row.revenue)}` }))} />
      </div>
    </div>
  );
}

function BusinessKpis({ dashboard }: { dashboard: DashboardPayload }) {
  const b = dashboard.business;
  const rows = [
    { label: "To'lov ulushi", value: `${b.paidRate || 0}%`, meta: 'Paid / jami order', icon: 'bi-credit-card', color: '#10b981' },
    { label: 'Yakunlash ulushi', value: `${b.completionRate || 0}%`, meta: 'Yakunlangan / jami', icon: 'bi-check2-circle', color: '#4f46e5' },
    { label: 'Bekor ulushi', value: `${b.cancellationRate || 0}%`, meta: 'Bekor va qaytgan', icon: 'bi-x-circle', color: '#ef4444' },
    { label: 'Qayta xaridor', value: `${b.repeatBuyerRate || 0}%`, meta: `${fmt(b.repeatBuyers)} foydalanuvchi`, icon: 'bi-arrow-repeat', color: '#7c3aed' },
    { label: 'Xaridorlar', value: fmt(b.buyingUsers), meta: 'Paid order qilgan', icon: 'bi-people', color: '#06b6d4' },
    { label: 'Karta ulangan', value: fmt(b.cardUsers), meta: 'Tasdiqlangan karta', icon: 'bi-credit-card-2-front', color: '#ec4899' },
    { label: "Order / xaridor", value: String(b.avgOrdersPerBuyer || 0), meta: "O'rtacha chastota", icon: 'bi-bag-check', color: '#f59e0b' },
    { label: "Daromad / xaridor", value: money(b.avgRevenuePerBuyer), meta: "O'rtacha paid revenue", icon: 'bi-cash-stack', color: '#059669' },
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
              <span>{row.label}<small className="d-block text-muted">{row.meta}</small></span>
              <strong style={{ color: row.color }}>{row.value}</strong>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function Metric({ label, value = 0, icon, color, href }: { label: string; value?: number; icon: string; color: string; href: string }) {
  return (
    <div className="col-xl-2 col-md-4 col-6">
      <Link href={href} className="stat-card text-decoration-none d-block" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
          <i className={`bi ${icon}`} style={{ color, fontSize: 18 }}></i>
        </div>
        <div className="text-body" style={{ fontSize: 22, fontWeight: 800 }}>{fmt(value)}</div>
      </Link>
    </div>
  );
}

function PeriodCard({ label, value, delta, icon, color }: { label: string; value: string; delta: number; icon: string; color: string }) {
  return (
    <div className="col-xl-3 col-md-6">
      <div className="stat-card" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
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

function CompactMetric({ label, value = 0, icon, color, href }: { label: string; value?: number; icon: string; color: string; href: string }) {
  return (
    <div className="col-xl-2 col-md-4 col-6">
      <Link href={href} className="stat-card text-decoration-none d-block h-100" style={{ padding: 14 }}>
        <div className="d-flex justify-content-between align-items-start mb-1">
          <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{label}</div>
          <i className={`bi ${icon}`} style={{ color, fontSize: 18 }}></i>
        </div>
        <div className="text-body" style={{ fontSize: 22, fontWeight: 800 }}>{fmt(value)}</div>
      </Link>
    </div>
  );
}

function DistributionPanel({ title, rows }: { title: string; rows: Array<{ name: string; value: string }> }) {
  return <div className="col-xl-4"><div className="card-panel h-100"><div className="panel-title mb-3">{title}</div>{rows.length ? rows.slice(0, 8).map((row) => <div className="d-flex justify-content-between gap-3 py-2 border-bottom small" key={row.name}><span className="text-muted text-truncate">{row.name}</span><strong className="text-nowrap">{row.value}</strong></div>) : <div className="text-muted small">Ma'lumot topilmadi.</div>}</div></div>;
}

function FinancialPanel({ dashboard }: { dashboard: DashboardPayload }) {
  const f = dashboard.financial;
  const rows = [
    ["Paid order tushumi", f.grossRevenue, '#10b981'],
    ['Delivery income', f.deliveryIncome, '#4f46e5'],
    ['Seller commission', f.commission, '#7c3aed'],
    ['Promo discount', -f.promoDiscount, '#ef4444'],
    ['Cashback', -f.cashback, '#ef4444'],
    ['Courier payout', -f.courierPayout, '#f59e0b'],
    ['Kiritilgan chiqimlar', -f.manualExpenses, '#ef4444'],
    ['Provider komissiyasi', -f.providerFee, '#f97316'],
    ['Soliq', -f.tax, '#dc2626'],
    ['Marketplace marjasi', f.platformProfit, '#059669'],
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
          {rows.map(([label, raw, color]) => (
            <div className="col-md-6" key={String(label)}>
              <div className="mini-stat">
                <i className="bi bi-dot" style={{ color: String(color) }}></i>
                <span>{label}</span>
                <strong style={{ color: String(color) }}>{money(Number(raw))}</strong>
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

function RankPanel({ title, rows }: { title: string; rows: Array<{ name: string; meta: string; value: string }> }) {
  return (
    <div className="card-panel h-100">
      <div className="panel-title mb-3">{title}</div>
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
        </div>
      )) : <div className="text-muted small">Buyurtma topilmadi.</div>}
    </div>
  );
}
