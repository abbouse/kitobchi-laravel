import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import {
  Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart,
  ResponsiveContainer, Tooltip, XAxis, YAxis, Area, AreaChart
} from 'recharts';
import {
  stats, salesByMonth, categoryShare, topBooks, recentActivity,
  hourlySales, weeklySales, regionalData, paymentData,
  customerSegments, topPerformers, inventoryAlerts,
  authors, publishers, courierOrders, fmt,
  appPlatformData, appVersionData, appEventFunnel, paymentFailureReasons,
  operationalQuality, cohortRetention, adminWorkload, profitLeakage
} from '../data';

export default function Dashboard() {
  const [period, setPeriod] = useState<'today' | 'week' | 'month'>('month');
  const [chartMetric, setChartMetric] = useState<'revenue' | 'profit' | 'orders'>('revenue');
  const { metrics = {} } = usePage<{
    metrics?: {
      orders?: number;
      users?: number;
      books?: number;
      sellers?: number;
      couriers?: number;
      tickets?: number;
    };
  }>().props;

  const periodComparison = {
    today: { revenue: 1_840_500, orders: 48, aov: 38_344, visitors: 2480 },
    yesterday: { revenue: 1_680_200, orders: 44, aov: 38_186, visitors: 2340 },
    week: { revenue: 11_610_000, orders: 305, aov: 38_066, visitors: 15420 },
    lastWeek: { revenue: 10_420_500, orders: 274, aov: 38_030, visitors: 14180 },
    month: { revenue: 48_720_500, orders: 1284, aov: 37_940, visitors: 56840 },
    lastMonth: { revenue: 42_180_000, orders: 1112, aov: 37_920, visitors: 49280 },
  };
  const cur = periodComparison[period];
  const prev = periodComparison[period === 'today' ? 'yesterday' : period === 'week' ? 'lastWeek' : 'lastMonth'];
  const chg = (a: number, b: number) => b === 0 ? 0 : +((a - b) / b * 100).toFixed(1);

  return (
    <div>
      {/* HEADER */}
      <div className="page-head">
        <div>
          <h1 className="page-title">Marketplace Analytics</h1>
          <p className="page-subtitle">
            <span className="chip chip-success me-2"><span className="live-pulse"></span>Live</span>
            Barcha ko'rsatkichlar real vaqtda yangilanmoqda · Oxirgi yangilanish: 2 daqiqa oldin
          </p>
        </div>
        <div className="d-flex gap-2 align-items-center">
          <div className="btn-group btn-group-sm">
            <button className={`btn ${period === 'today' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setPeriod('today')}>Bugun</button>
            <button className={`btn ${period === 'week' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setPeriod('week')}>Hafta</button>
            <button className={`btn ${period === 'month' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setPeriod('month')}>Oy</button>
          </div>
          <button className="btn btn-outline-secondary btn-sm"><i className="bi bi-download me-1"></i>Export</button>
          <button className="btn btn-primary-gradient btn-sm"><i className="bi bi-calendar3 me-1"></i>Custom</button>
        </div>
      </div>

      <div className="row g-2 mb-3">
        {[
          { l: 'Real buyurtmalar', v: metrics.orders ?? 0, icon: 'bi-receipt', c: '#4f46e5' },
          { l: 'Foydalanuvchilar', v: metrics.users ?? 0, icon: 'bi-people', c: '#10b981' },
          { l: 'Kitoblar', v: metrics.books ?? 0, icon: 'bi-book', c: '#f59e0b' },
          { l: 'Sotuvchilar', v: metrics.sellers ?? 0, icon: 'bi-shop', c: '#ec4899' },
          { l: 'Kuryerlar', v: metrics.couriers ?? 0, icon: 'bi-bicycle', c: '#06b6d4' },
          { l: 'Support ticketlar', v: metrics.tickets ?? 0, icon: 'bi-headset', c: '#7c3aed' },
        ].map((s) => (
          <div className="col-xl-2 col-md-4 col-6" key={s.l}>
            <div className="stat-card" style={{ padding: 14 }}>
              <div className="d-flex justify-content-between align-items-start mb-1">
                <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{s.l}</div>
                <i className={`bi ${s.icon}`} style={{ color: s.c, fontSize: 18 }}></i>
              </div>
              <div style={{ fontSize: 22, fontWeight: 800, color: '#111827' }}>{fmt(Number(s.v))}</div>
              <div className="stat-trend up" style={{ fontSize: 11 }}>Laravel DB snapshot</div>
            </div>
          </div>
        ))}
      </div>

      {/* =================== TIME PERIODS COMPARISON =================== */}
      <div className="row g-2 mb-3">
        {[
          { l: 'Daromad', v: fmt(cur.revenue) + ' so\'m', p: prev.revenue, icon: 'bi-cash-coin', c: '#4f46e5' },
          { l: 'Buyurtmalar', v: fmt(cur.orders), p: prev.orders, icon: 'bi-bag-check', c: '#10b981' },
          { l: 'O\'rtacha chek', v: fmt(cur.aov) + ' so\'m', p: prev.aov, icon: 'bi-receipt', c: '#f59e0b' },
          { l: 'Faol app userlar', v: fmt(cur.visitors), p: prev.visitors, icon: 'bi-phone', c: '#ec4899' },
        ].map((s) => {
          const delta = chg(Number(s.v.replace(/[^0-9]/g, '')), s.p);
          return (
            <div className="col-xl-3 col-md-6" key={s.l}>
              <div className="stat-card" style={{ padding: 14 }}>
                <div className="d-flex justify-content-between align-items-start mb-1">
                  <div style={{ fontSize: 11, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{s.l}</div>
                  <i className={`bi ${s.icon}`} style={{ color: s.c, fontSize: 18 }}></i>
                </div>
                <div style={{ fontSize: 22, fontWeight: 800, color: '#111827' }}>{s.v}</div>
                <div className={`stat-trend ${delta >= 0 ? 'up' : 'down'}`} style={{ fontSize: 11 }}>
                  <i className={`bi ${delta >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'}`}></i> {delta >= 0 ? '+' : ''}{delta}% vs oldingi
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* =================== P&L (PROFIT & LOSS) PANEL =================== */}
      <div className="card-panel mb-3" style={{ background: 'linear-gradient(135deg,#eef2ff 0%,#fdf4ff 100%)', border: '1px solid #c7d2fe' }}>
        <div className="panel-head">
          <div>
            <div className="panel-title"><i className="bi bi-graph-up-arrow text-success me-2"></i>P&L — Foyda va Zarar (Oylik)</div>
            <small className="text-muted">Moliyaviy ko'rsatkichlar — marketpleys standartlari</small>
          </div>
          <span className="chip chip-success">Sof foyda: {fmt(stats.netProfit)} so'm</span>
        </div>
        <div className="row g-2 text-center">
          {[
            { l: 'Umumiy daromad', v: fmt(stats.grossRevenue), sub: 'Gross Revenue', c: '#10b981', bg: '#dcfce7' },
            { l: 'Tovar tannarxi', v: fmt(stats.cogs), sub: '-COGS', c: '#ef4444', bg: '#fee2e2' },
            { l: 'Yalpi foyda', v: fmt(stats.grossProfit), sub: `Marja ${stats.grossMargin}%`, c: '#4f46e5', bg: '#e0e7ff' },
            { l: 'Marketing', v: fmt(stats.marketingSpend), sub: 'Xarajat', c: '#f59e0b', bg: '#fef3c7' },
            { l: 'Yetkazib berish', v: fmt(stats.shippingCost), sub: 'Xarajat', c: '#f59e0b', bg: '#fef3c7' },
            { l: 'Boshqa xarajat', v: fmt(stats.operatingExpenses), sub: 'Operational', c: '#f59e0b', bg: '#fef3c7' },
            { l: 'Qaytarishlar', v: fmt(stats.refunds + stats.cancelled), sub: 'Zarar', c: '#ef4444', bg: '#fee2e2' },
            { l: 'SOF FOYDA', v: fmt(stats.netProfit), sub: `Net ${stats.netMargin}%`, c: '#059669', bg: '#a7f3d0', bold: true },
          ].map((m) => (
            <div className="col-xl-3 col-md-6" key={m.l}>
              <div className="p-3 rounded" style={{ background: m.bg, border: `1px solid ${m.c}22` }}>
                <div style={{ fontSize: 10, color: '#6b7280', textTransform: 'uppercase', letterSpacing: 1 }}>{m.sub}</div>
                <div style={{ fontSize: m.bold ? 18 : 15, fontWeight: m.bold ? 800 : 700, color: m.c }}>{m.v}</div>
                <div style={{ fontSize: 11, fontWeight: 600, color: '#374151' }}>{m.l}</div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* =================== MAIN CHARTS ROW =================== */}
      <div className="row g-3 mb-3">
        {/* SALES CHART */}
        <div className="col-xl-8">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Savdo, Foyda va Buyurtmalar dinamikasi</div>
                <small className="text-muted">Oylik taqqoslash — 12 oy</small>
              </div>
              <div className="btn-group btn-group-sm">
                <button className={`btn ${chartMetric === 'revenue' ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('revenue')}>Daromad</button>
                <button className={`btn ${chartMetric === 'profit' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('profit')}>Foyda</button>
                <button className={`btn ${chartMetric === 'orders' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setChartMetric('orders')}>Buyurtma</button>
              </div>
            </div>
            <ResponsiveContainer width="100%" height={280}>
              <LineChart data={salesByMonth}>
                <defs>
                  <linearGradient id="gRev" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stopColor="#4f46e5" /><stop offset="100%" stopColor="#a855f7" />
                  </linearGradient>
                  <linearGradient id="gProf" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stopColor="#10b981" /><stop offset="100%" stopColor="#059669" />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#eef0f4" vertical={false} />
                <XAxis dataKey="month" stroke="#9ca3af" fontSize={11} />
                <YAxis stroke="#9ca3af" fontSize={11} tickFormatter={(v) => chartMetric === 'orders' ? v : `${(v/1000000).toFixed(1)}M`} />
                <Tooltip formatter={(v: any) => chartMetric === 'orders' ? fmt(Number(v)) : `${fmt(Number(v))} so'm`} />
                <Line type="monotone" dataKey="revenue" stroke="url(#gRev)" strokeWidth={2.5} dot={{ r: 3 }} hide={chartMetric !== 'revenue'} />
                <Line type="monotone" dataKey="profit" stroke="url(#gProf)" strokeWidth={2.5} dot={{ r: 3 }} hide={chartMetric !== 'profit'} />
                <Line type="monotone" dataKey="orders" stroke="#f59e0b" strokeWidth={2.5} dot={{ r: 3 }} hide={chartMetric !== 'orders'} />
                <Line type="monotone" dataKey="loss" stroke="#ef4444" strokeWidth={2} dot={{ r: 2 }} strokeDasharray="4 4" hide={chartMetric !== 'profit'} />
              </LineChart>
            </ResponsiveContainer>
            <div className="d-flex gap-3 mt-2 justify-content-center flex-wrap" style={{ fontSize: 11 }}>
              <span><span style={{ width: 10, height: 3, background: '#4f46e5', display: 'inline-block', marginRight: 4 }}></span>Daromad</span>
              <span><span style={{ width: 10, height: 3, background: '#10b981', display: 'inline-block', marginRight: 4 }}></span>Foyda</span>
              <span><span style={{ width: 10, height: 3, background: '#ef4444', display: 'inline-block', marginRight: 4, borderTop: '1px dashed #ef4444' }}></span>Zarar</span>
              <span><span style={{ width: 10, height: 3, background: '#f59e0b', display: 'inline-block', marginRight: 4 }}></span>Buyurtma</span>
            </div>
          </div>
        </div>

        {/* CATEGORY PIE + DETAILS */}
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div className="panel-title">Kategoriya bo'yicha daromad</div>
            </div>
            <ResponsiveContainer width="100%" height={180}>
              <PieChart>
                <Pie data={categoryShare} dataKey="value" innerRadius={50} outerRadius={75} paddingAngle={3}>
                  {categoryShare.map((c, i) => <Cell key={i} fill={c.color} />)}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
            <div className="d-flex flex-column gap-1 mt-2">
              {categoryShare.map((c) => (
                <div key={c.name} className="d-flex justify-content-between align-items-center small py-1 border-bottom">
                  <div className="d-flex align-items-center gap-2">
                    <span style={{ width: 8, height: 8, borderRadius: '50%', background: c.color, display: 'inline-block' }}></span>
                    <span className="fw-semibold">{c.name}</span>
                  </div>
                  <div className="text-end">
                    <div style={{ fontSize: 12, fontWeight: 700 }}>{c.value}%</div>
                    <small className="text-success">{c.margin}% marja</small>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* =================== MARKETPLACE METRICS (KPI Grid) =================== */}
      <div className="card-panel mb-3">
        <div className="panel-head">
          <div>
            <div className="panel-title"><i className="bi bi-speedometer2 text-primary me-2"></i>Marketplace KPI lar</div>
            <small className="text-muted">Konversiya, marja, LTV/CAC va boshqa muhim ko'rsatkichlar</small>
          </div>
          <span className="chip chip-purple">Marketpleys standartlari</span>
        </div>
        <div className="row g-2">
          {[
            { l: 'Konversiya', v: `${stats.conversionRate}%`, sub: 'Visit → Purchase', c: '#4f46e5', icon: 'bi-bullseye' },
            { l: 'Savat tashlash', v: `${stats.cartAbandonment}%`, sub: 'Cart Abandon', c: '#ef4444', icon: 'bi-cart-x' },
            { l: 'Qaytarish %', v: `${stats.returnRate}%`, sub: 'Refund Rate', c: '#f59e0b', icon: 'bi-arrow-counterclockwise' },
            { l: 'Saqlash %', v: `${stats.customerRetention}%`, sub: 'Retention', c: '#10b981', icon: 'bi-people' },
            { l: 'CAC', v: fmt(stats.cac), sub: 'Acquisition Cost', c: '#7c3aed', icon: 'bi-person-plus' },
            { l: 'LTV', v: fmt(stats.ltv), sub: 'Lifetime Value', c: '#ec4899', icon: 'bi-heart' },
            { l: 'LTV/CAC', v: `${stats.ltvCacRatio}x`, sub: 'Sog\'lom: 3x+', c: '#10b981', icon: 'bi-graph-up' },
            { l: 'ROAS', v: `${stats.roas}x`, sub: 'Return on Ad', c: '#4f46e5', icon: 'bi-megaphone' },
            { l: 'ROI', v: `${stats.roi}%`, sub: 'Investitsiya qaytimi', c: '#10b981', icon: 'bi-cash-stack' },
            { l: 'Gross Marja', v: `${stats.grossMargin}%`, sub: 'Target: 40%+', c: '#059669', icon: 'bi-percent' },
            { l: 'Net Marja', v: `${stats.netMargin}%`, sub: 'Target: 10%+', c: '#059669', icon: 'bi-graph-up-arrow' },
            { l: 'O\'rt. chek', v: fmt(stats.avgOrderValue), sub: 'AOV', c: '#f59e0b', icon: 'bi-receipt-cutoff' },
          ].map((m) => (
            <div className="col-xl-2 col-lg-3 col-md-4 col-6" key={m.l}>
              <div className="p-3 rounded text-center h-100" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                <i className={`bi ${m.icon}`} style={{ fontSize: 20, color: m.c }}></i>
                <div style={{ fontSize: 20, fontWeight: 800, color: m.c, margin: '4px 0 2px' }}>{m.v}</div>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#374151' }}>{m.l}</div>
                <div style={{ fontSize: 10, color: '#9ca3af' }}>{m.sub}</div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* =================== APP PLATFORM + CUSTOMER SEGMENTS =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-7">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Android / iOS platforma tahlili</div>
                <small className="text-muted">Veb sayt yo'q: asosiy manba app eventlari, platforma, versiya va order konversiya</small>
              </div>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Platforma</th><th>Faol user</th><th>Orders</th><th>Daromad</th><th>Foyda</th><th>Conv.</th><th>Crash</th><th>Avg session</th>
                  </tr>
                </thead>
                <tbody>
                  {appPlatformData.map((p) => (
                    <tr key={p.platform}>
                      <td><i className={`bi ${p.platform === 'Android' ? 'bi-android2' : 'bi-apple'} me-1`}></i><span className="fw-semibold">{p.platform}</span></td>
                      <td>{fmt(p.activeUsers)}</td>
                      <td className="fw-semibold">{fmt(p.orders)}</td>
                      <td className="text-success fw-semibold">{fmt(p.revenue)}</td>
                      <td className="text-primary fw-semibold">{fmt(p.profit)}</td>
                      <td>
                        <div className="d-flex align-items-center gap-1">
                          <div className="progress" style={{ width: 40, height: 5 }}>
                            <div className="progress-bar" style={{ width: `${p.conversion * 10}%`, background: p.color }}></div>
                          </div>
                          <small className="fw-bold">{p.conversion}%</small>
                        </div>
                      </td>
                      <td><span className={`chip ${p.crashRate < 0.5 ? 'chip-success' : 'chip-warning'}`}>{p.crashRate}%</span></td>
                      <td>{p.avgSession}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Mijozlar segmentlari</div>
                <small className="text-muted">RFM (Recency, Frequency, Monetary)</small>
              </div>
            </div>
            {customerSegments.map((s) => (
              <div key={s.segment} className="d-flex align-items-center gap-3 py-2 border-bottom">
                <div style={{ width: 40, height: 40, borderRadius: 8, background: s.color, color: 'white', display: 'grid', placeItems: 'center', fontWeight: 700, fontSize: 14 }}>
                  {s.share}%
                </div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div className="fw-bold small">{s.segment}</div>
                  <div className="text-muted" style={{ fontSize: 11 }}>{fmt(s.count)} mijoz · {fmt(s.orders)} buyurtma · Ret. {s.retention}%</div>
                </div>
                <div className="text-end">
                  <div style={{ fontSize: 13, fontWeight: 700, color: '#059669' }}>{fmt(s.revenue)}</div>
                  <small className="text-muted">O'rt. {fmt(s.avgSpent)}</small>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* =================== HOURLY & WEEKLY =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-6">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Soatlik savdo aktivligi (Bugun)</div>
                <small className="text-muted">Qaysi soatlarda eng yuqori savdo</small>
              </div>
            </div>
            <ResponsiveContainer width="100%" height={200}>
              <AreaChart data={hourlySales}>
                <defs>
                  <linearGradient id="hourGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#ec4899" stopOpacity={0.7} />
                    <stop offset="100%" stopColor="#4f46e5" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <XAxis dataKey="hour" stroke="#9ca3af" fontSize={10} interval={3} />
                <YAxis stroke="#9ca3af" fontSize={10} />
                <Tooltip />
                <Area type="monotone" dataKey="orders" stroke="#ec4899" fill="url(#hourGrad)" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
            <div className="d-flex gap-3 justify-content-center small text-muted mt-2">
              <span>🌅 Ertalab: 08-11</span><span>☀️ Tushlik: 12-14</span><span>🌆 Kechqurun: 19-22 (peak)</span>
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Haftalik taqsimot</div>
                <small className="text-muted">Hafta kunlari bo'yicha savdo</small>
              </div>
            </div>
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={weeklySales}>
                <CartesianGrid strokeDasharray="3 3" stroke="#eef0f4" vertical={false} />
                <XAxis dataKey="day" stroke="#9ca3af" fontSize={11} />
                <YAxis stroke="#9ca3af" fontSize={11} tickFormatter={(v) => `${(v/1000000).toFixed(1)}M`} />
                <Tooltip formatter={(v: any) => `${fmt(Number(v))} so'm`} />
                <Bar dataKey="revenue" fill="#4f46e5" radius={[6, 6, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
            <div className="d-flex justify-content-between small mt-2">
              <span className="text-muted">Eng past: Dushanba</span>
              <span className="fw-bold text-success">Eng yuqori: Shanba (+75%)</span>
            </div>
          </div>
        </div>
      </div>

      {/* =================== CONVERSION FUNNEL + DEVICE =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">App Event Funnel — kuchli tahlil</div>
                <small className="text-muted">Android/iOS ichidagi eventlar: qayerda user yo'qolayotgani aniq ko'rinadi</small>
              </div>
              <span className="chip chip-warning">3.42% final</span>
            </div>
            {appEventFunnel.map((f, i) => (
              <div key={f.stage} className="mb-2">
                <div className="d-flex justify-content-between small mb-1">
                  <span className="fw-semibold">{i + 1}. {f.stage}</span>
                  <span>{fmt(f.users)} · <strong style={{ color: f.color }}>{f.conversion}%</strong></span>
                </div>
                <div className="progress" style={{ height: 22 }}>
                  <div className="progress-bar" style={{ width: `${f.conversion}%`, background: f.color, fontWeight: 700, fontSize: 11 }}>
                    {f.conversion > 8 ? `${f.conversion}%` : ''}
                  </div>
                </div>
                {i > 0 && (
                  <div className="d-flex justify-content-between mt-1" style={{ fontSize: 10 }}>
                    <span className="text-danger">Drop: {f.drop}%</span>
                    <span className="text-muted text-end" style={{ maxWidth: 260 }}>{f.problem}</span>
                  </div>
                )}
              </div>
            ))}
            <div className="alert alert-warning mt-3 mb-0 small">
              <i className="bi bi-exclamation-triangle me-1"></i>
              Eng katta yo'qotish: <strong>Product detail → Add to cart</strong> bosqichida. Narx, zaxira, tavsiya va qadoqlash ma'lumotlarini kuchaytirish kerak.
            </div>
          </div>
        </div>

        <div className="col-xl-7">
          <div className="row g-3 h-100">
            <div className="col-md-6">
              <div className="card-panel h-100">
              <div className="panel-title mb-3">📱 App versiyalar bo'yicha risk</div>
                {appVersionData.map((d) => (
                  <div key={`${d.platform}-${d.version}`} className="mb-2">
                    <div className="d-flex justify-content-between small mb-1">
                      <span className="fw-semibold">{d.platform} {d.version}</span>
                      <span>{fmt(d.users)} users · {fmt(d.orders)} orders</span>
                    </div>
                    <div className="progress" style={{ height: 10 }}>
                      <div className={`progress-bar ${d.status === 'Risk' ? 'bg-danger' : d.status === 'Update kerak' ? 'bg-warning' : 'bg-success'}`} style={{ width: `${Math.min(d.crashRate * 45, 100)}%` }}></div>
                    </div>
                    <div className="d-flex justify-content-between small mt-1">
                      <span className="text-muted">Crash {d.crashRate}% · Pay fail {d.paymentFailRate}%</span>
                      <span className={d.status === 'Risk' ? 'text-danger fw-bold' : d.status === 'Update kerak' ? 'text-warning fw-bold' : 'text-success fw-bold'}>{d.status}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="col-md-6">
              <div className="card-panel h-100">
                <div className="panel-title mb-3">💳 To'lov: karta / naqd</div>
                {paymentData.map((p) => (
                  <div key={p.method} className="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                      <div className="fw-semibold small">{p.method}</div>
                      <small className="text-muted">{fmt(p.orders)} buyurtma · {p.share}% · Success {p.successRate}%</small>
                    </div>
                    <div className="text-end">
                      <div className="fw-bold text-success small">{fmt(p.revenue)}</div>
                      <small className="text-muted">Failed: {p.failed}</small>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* =================== APP OPERATIONS: PAYMENT, BACKEND, ADMIN, LEAKAGE =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">💳 Karta to'lov xatolari</div>
            {paymentFailureReasons.map((r) => (
              <div key={r.reason} className="py-2 border-bottom">
                <div className="d-flex justify-content-between small">
                  <span className="fw-semibold">{r.reason}</span>
                  <span className="text-danger fw-bold">{r.share}%</span>
                </div>
                <div className="d-flex justify-content-between" style={{ fontSize: 11 }}>
                  <span className="text-muted">{r.count} ta holat</span>
                  <span className="text-danger">-{fmt(r.amount)} so'm</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">⚙️ Laravel/API operational quality</div>
            {operationalQuality.map((q) => (
              <div key={q.metric} className="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                  <div className="fw-semibold" style={{ fontSize: 12 }}>{q.metric}</div>
                  <small className="text-muted">Target: {q.target}</small>
                </div>
                <div className="text-end">
                  <div className="fw-bold" style={{ color: q.color }}>{q.value}</div>
                  <small style={{ color: q.color }}>{q.status}</small>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">🧑‍💻 Admin panel workload</div>
            {adminWorkload.map((w) => (
              <div key={w.queue} className="py-2 border-bottom">
                <div className="d-flex justify-content-between small">
                  <span className="fw-semibold">{w.queue}</span>
                  <span className={`chip ${w.priority === 'High' ? 'chip-danger' : w.priority === 'Medium' ? 'chip-warning' : 'chip-gray'}`}>{w.count}</span>
                </div>
                <div className="d-flex justify-content-between" style={{ fontSize: 11 }}>
                  <span className="text-muted">Avg: {w.avgHandle}</span>
                  <span className={w.slaMiss > 1 ? 'text-danger fw-semibold' : 'text-success'}>SLA miss: {w.slaMiss}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">🩸 Profit leakage</div>
            {profitLeakage.map((l) => (
              <div key={l.source} className="py-2 border-bottom">
                <div className="d-flex justify-content-between small">
                  <span className="fw-semibold">{l.source}</span>
                  <span className="text-danger fw-bold">-{fmt(l.amount)}</span>
                </div>
                <small className="text-muted">Impact {l.impact} · {l.fix}</small>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* =================== COHORT RETENTION =================== */}
      <div className="card-panel mb-3">
        <div className="panel-head">
          <div>
            <div className="panel-title">🔁 App cohort retention</div>
            <small className="text-muted">App o'rnatgan yoki ilk buyurtma qilgan mijozlar qaytish sifati</small>
          </div>
          <span className="chip chip-info">D1 / D7 / D14 / D30</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr><th>Cohort</th><th>Day 1</th><th>Day 7</th><th>Day 14</th><th>Day 30</th><th>Revenue</th><th>Trend</th></tr>
            </thead>
            <tbody>
              {cohortRetention.map((c) => (
                <tr key={c.cohort}>
                  <td className="fw-semibold">{c.cohort}</td>
                  <td><span className="chip chip-success">{c.day1}%</span></td>
                  <td><span className="chip chip-info">{c.day7}%</span></td>
                  <td><span className="chip chip-purple">{c.day14}%</span></td>
                  <td><span className={c.day30 >= 28 ? 'chip chip-success' : 'chip chip-warning'}>{c.day30}%</span></td>
                  <td className="fw-bold text-success">{fmt(c.revenue)} so'm</td>
                  <td>
                    <div className="progress" style={{ height: 8, minWidth: 120 }}>
                      <div className="progress-bar bg-success" style={{ width: `${c.day30 * 2}%` }}></div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* =================== TOP PERFORMERS (BEST/WORST) =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-6">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div className="panel-title">🏆 Best-sellers (Eng yaxshi)</div>
              <span className="chip chip-success">Top 5</span>
            </div>
            {topPerformers.best.map((p, i) => (
              <div key={p.product} className="d-flex align-items-center gap-2 py-2 border-bottom">
                <div style={{ width: 28, height: 28, borderRadius: 6, background: i === 0 ? 'linear-gradient(135deg,#fbbf24,#f59e0b)' : i === 1 ? '#d1d5db' : i === 2 ? '#d97706' : '#e5e7eb', color: i > 2 ? '#374151' : 'white', display: 'grid', placeItems: 'center', fontSize: 12, fontWeight: 800 }}>{i + 1}</div>
                <div style={{ flex: 1 }}>
                  <div className="fw-semibold small">{p.product}</div>
                  <div className="progress mt-1" style={{ height: 5 }}>
                    <div className="progress-bar bg-success" style={{ width: `${(p.revenue / topPerformers.best[0].revenue) * 100}%` }}></div>
                  </div>
                </div>
                <div className="text-end">
                  <div style={{ fontSize: 13, fontWeight: 700, color: '#059669' }}>{fmt(p.revenue)}</div>
                  <small className="text-success">↑ {p.growth}%</small>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div className="panel-title">⚠️ Low-performers (Tahlil kerak)</div>
              <span className="chip chip-danger">Ogohlantirish</span>
            </div>
            {topPerformers.worst.map((p, i) => (
              <div key={p.product} className="d-flex align-items-center gap-2 py-2 border-bottom">
                <div style={{ width: 28, height: 28, borderRadius: 6, background: '#fee2e2', color: '#991b1b', display: 'grid', placeItems: 'center', fontSize: 12, fontWeight: 800 }}>{i + 1}</div>
                <div style={{ flex: 1 }}>
                  <div className="fw-semibold small">{p.product}</div>
                  <div className="progress mt-1" style={{ height: 5 }}>
                    <div className="progress-bar bg-danger" style={{ width: `${(p.revenue / topPerformers.worst[3].revenue) * 100}%` }}></div>
                  </div>
                </div>
                <div className="text-end">
                  <div style={{ fontSize: 13, fontWeight: 700, color: '#374151' }}>{fmt(p.revenue)}</div>
                  <small className="text-danger">↓ {p.decline}%</small>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* =================== REGIONAL + INVENTORY + ACTIVITY =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-5">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">🗺️ Geografik segmentlar</div>
                <small className="text-muted">Viloyatlar bo'yicha foyda/zarar</small>
              </div>
            </div>
            <div style={{ maxHeight: 320, overflowY: 'auto' }}>
              {regionalData.map((r, i) => (
                <div key={r.region} className="d-flex align-items-center gap-2 py-2 border-bottom">
                  <div style={{ width: 28, height: 28, borderRadius: 6, background: i === 0 ? '#4f46e5' : '#eef2ff', color: i === 0 ? 'white' : '#4f46e5', display: 'grid', placeItems: 'center', fontSize: 11, fontWeight: 700 }}>#{i + 1}</div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div className="fw-semibold small">{r.region}</div>
                    <small className="text-muted">{fmt(r.orders)} orders · {fmt(r.customers)} customers · Return {r.returns}%</small>
                  </div>
                  <div className="text-end">
                    <div className="fw-bold small text-primary">{fmt(r.revenue)}</div>
                    <small className="text-success">+{fmt(r.profit)}</small>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div className="panel-title">📦 Inventory Alerts</div>
              <span className="chip chip-danger">{inventoryAlerts.length} ogohlantirish</span>
            </div>
            {inventoryAlerts.map((a) => (
              <div key={a.product} className="py-2 border-bottom">
                <div className="d-flex justify-content-between small">
                  <span className="fw-semibold">{a.product}</span>
                  <span className={`chip ${a.status === 'Kam' ? 'chip-danger' : 'chip-warning'}`}>{a.stock} qolgan</span>
                </div>
                <small className="text-muted">
                  <i className="bi bi-clock"></i> {a.daysLeft} kunga yetadi · Min. {a.threshold} kerak
                </small>
              </div>
            ))}
          </div>
        </div>

        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div className="panel-title">⚡ Jonli faoliyat</div>
              <span className="chip chip-success"><span className="live-pulse"></span>Real-time</span>
            </div>
            <div style={{ maxHeight: 320, overflowY: 'auto' }}>
              {recentActivity.map((a, i) => (
                <div key={i} className="d-flex align-items-start gap-3 py-2 border-bottom">
                  <div style={{ width: 34, height: 34, borderRadius: 10, background: `${a.color}1a`, color: a.color, display: 'grid', placeItems: 'center', fontSize: 14, flexShrink: 0 }}>
                    <i className={`bi ${a.icon}`}></i>
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: 12 }}>{a.text}</div>
                    <div style={{ fontSize: 10, color: '#9ca3af' }}>{a.time}{a.amount > 0 && ` · ${fmt(a.amount)} so'm`}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* =================== AUTHORS + PUBLISHERS PERFORMANCE =================== */}
      <div className="row g-3 mb-3">
        <div className="col-xl-6">
          <div className="card-panel">
            <div className="panel-head">
              <div className="panel-title">📚 Mualliflar performansi</div>
              <a href="/authors" className="small text-decoration-none fw-semibold" style={{ color: '#4f46e5' }}>Barchasi →</a>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead>
                  <tr><th>Muallif</th><th>Kitoblar</th><th>Daromad</th><th>Foyda</th><th>Obunachi</th></tr>
                </thead>
                <tbody>
                  {authors.slice(0, 6).map((a) => (
                    <tr key={a.id}>
                      <td className="fw-semibold">{a.name}</td>
                      <td>{a.books}</td>
                      <td className="text-success fw-semibold">{fmt(a.revenue)}</td>
                      <td className="fw-bold text-primary">{fmt(a.profit)}</td>
                      <td>{fmt(a.followers)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card-panel">
            <div className="panel-head">
              <div className="panel-title">🏢 Nashriyotlar performansi</div>
              <a href="/publishers" className="small text-decoration-none fw-semibold" style={{ color: '#4f46e5' }}>Barchasi →</a>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead>
                  <tr><th>Nashriyot</th><th>Shahar</th><th>Daromad</th><th>Foyda</th><th>Reyting</th></tr>
                </thead>
                <tbody>
                  {publishers.slice(0, 6).map((p) => (
                    <tr key={p.id}>
                      <td className="fw-semibold">{p.name}</td>
                      <td className="text-muted">{p.city}</td>
                      <td className="text-success fw-semibold">{fmt(p.revenue)}</td>
                      <td className="fw-bold text-primary">{fmt(p.profit)}</td>
                      <td><span className="chip chip-warning">⭐ {p.rating}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {/* =================== KURYER EFFICIENCY =================== */}
      <div className="card-panel mb-3">
        <div className="panel-head">
          <div>
            <div className="panel-title">🚚 Kuryerlar samaradorligi</div>
            <small className="text-muted">Yetkazish tezligi, muvaffaqiyat % va on-time rate</small>
          </div>
          <a href="/courier-orders" className="small text-decoration-none fw-semibold" style={{ color: '#4f46e5' }}>Batafsil →</a>
        </div>
        <div className="row g-2">
          {courierOrders.map((c) => {
            const successRate = Math.round((c.delivered / Math.max(c.orders, 1)) * 100);
            return (
              <div className="col-xl-4 col-md-6" key={c.id}>
                <div className="p-3 rounded" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <div className="fw-bold small">{c.courier}</div>
                      <small className="text-muted">{c.region}</small>
                    </div>
                    <span className={`chip ${c.status === 'Completed' ? 'chip-success' : c.status === 'On Route' ? 'chip-info' : 'chip-warning'}`}>{c.status}</span>
                  </div>
                  <div className="row g-1 text-center small">
                    <div className="col-3"><div className="fw-bold">{c.orders}</div><small className="text-muted">Buyurtma</small></div>
                    <div className="col-3"><div className="fw-bold text-success">{c.delivered}</div><small className="text-muted">Yetkazildi</small></div>
                    <div className="col-3"><div className="fw-bold text-danger">{c.failed}</div><small className="text-muted">Muvaffaqiyatsiz</small></div>
                    <div className="col-3"><div className="fw-bold text-primary">{c.onTimeRate}%</div><small className="text-muted">On-time</small></div>
                  </div>
                  <div className="progress mt-2" style={{ height: 6 }}>
                    <div className="progress-bar bg-success" style={{ width: `${successRate}%` }}></div>
                  </div>
                  <div className="text-end small mt-1"><span className="text-success fw-semibold">{successRate}% muvaffaqiyat</span></div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* =================== TOP BOOKS (DETAILED) =================== */}
      <div className="card-panel mb-3">
        <div className="panel-head">
          <div>
            <div className="panel-title">📖 Top kitoblar — batafsil tahlil</div>
            <small className="text-muted">Foyda, marja, 30 kunlik trend va reyting bilan</small>
          </div>
          <a href="/books" className="small text-decoration-none fw-semibold" style={{ color: '#4f46e5' }}>Barcha kitoblar →</a>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th></th><th>Kitob</th><th>Muallif</th><th>Narx</th><th>Tannarx</th><th>Marja</th><th>30 kun</th><th>Daromad</th><th>Foyda</th><th>Reyting</th>
              </tr>
            </thead>
            <tbody>
              {topBooks.map((b) => {
                const margin = Math.round(((b.price - b.cost) / b.price) * 100);
                return (
                  <tr key={b.id}>
                    <td><div className="thumb d-grid place-items-center" style={{ fontSize: 22 }}>{b.cover}</div></td>
                    <td><div className="fw-semibold">{b.title}</div></td>
                    <td className="text-muted small">{b.author}</td>
                    <td className="fw-semibold">{fmt(b.price)}</td>
                    <td className="text-muted">{fmt(b.cost)}</td>
                    <td><span className="chip chip-success">{margin}%</span></td>
                    <td><span className="chip chip-purple">+{b.sold30}</span></td>
                    <td className="fw-bold text-success">{fmt(b.revenue)}</td>
                    <td className="fw-bold text-primary">{fmt(b.profit)}</td>
                    <td><span className="chip chip-warning">⭐ {b.rating}</span></td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* =================== FOOTER INSIGHTS =================== */}
      <div className="row g-3">
        <div className="col-xl-4">
          <div className="card-panel" style={{ background: 'linear-gradient(135deg,#dcfce7,#d1fae5)', border: '1px solid #86efac' }}>
            <div className="d-flex align-items-center gap-2 mb-2">
              <i className="bi bi-lightbulb-fill" style={{ fontSize: 20, color: '#059669' }}></i>
              <span className="fw-bold" style={{ color: '#059669' }}>AI Insight — O'sish imkoniyati</span>
            </div>
            <p className="small mb-0" style={{ color: '#14532d' }}>
              <strong>Telegram kanali</strong>ning ROAS <strong>7.4x</strong> — eng yuqori. Byudjetni +25% ga oshirish orqali oylik foydani <strong>+1.2M so'm</strong> ga ko'tarish mumkin.
            </p>
          </div>
        </div>
        <div className="col-xl-4">
          <div className="card-panel" style={{ background: 'linear-gradient(135deg,#fef3c7,#fde68a)', border: '1px solid #fcd34d' }}>
            <div className="d-flex align-items-center gap-2 mb-2">
              <i className="bi bi-exclamation-triangle-fill" style={{ fontSize: 20, color: '#92400e' }}></i>
              <span className="fw-bold" style={{ color: '#92400e' }}>Diqqat — Ogohlantirish</span>
            </div>
            <p className="small mb-0" style={{ color: '#713f12' }}>
              <strong>3 ta kitob</strong>da 12 kunga yetarli zaxira qolgan. Qayta buyurtma berish tavsiya etiladi — aks holda <strong>~2.4M so'm</strong> savdo yo'qotiladi.
            </p>
          </div>
        </div>
        <div className="col-xl-4">
          <div className="card-panel" style={{ background: 'linear-gradient(135deg,#fee2e2,#fecaca)', border: '1px solid #fca5a5' }}>
            <div className="d-flex align-items-center gap-2 mb-2">
              <i className="bi bi-graph-down" style={{ fontSize: 20, color: '#991b1b' }}></i>
              <span className="fw-bold" style={{ color: '#991b1b' }}>Harakat kerak — Zarar</span>
            </div>
            <p className="small mb-0" style={{ color: '#7f1d1d' }}>
              <strong>5 ta mahsulot</strong> 30 kundan beri pasayish trendida. Cheks yoki marketingni qayta ko'rib chiqing — potentsial zarar <strong>~1.8M so'm/oy</strong>.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
