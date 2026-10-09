import { usePalette, categoryColor } from '../utils/palette';
import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  ComposedChart,
  Legend,
  Line,
  LineChart,
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
import { Avatar as PAvatar } from '../components/Profile';

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

// ─── Xodim ish joyi (rolga mos) ─────────────────────────────────────────────

type QueueItem = { key: string; module: string; label: string; hint: string; count: number; tone: string; icon: string; url: string };
type Highlight = { label: string; value: number | string; hint: string; tone: Tone; icon: string };
type RecentAction = { id: number; text: string; module: string; moduleLabel: string; ok: boolean; at: string | null };
type PersonalKpi = {
  range: { from: string; to: string };
  admin: { id: number; name: string; role: string; roleKey: string };
  activity: { total: number; failed: number; activeDays: number; modules: Array<{ module: string; label: string; count: number }>; days: Array<{ date: string; count: number }> };
  support: { replies: number; closed: number; good: number; bad: number; satisfaction: number | null; avgFirstResponse: number | null; openNow: number } | null;
  highlights: Highlight[];
  recent: RecentAction[];
};
type Workspace = {
  greeting: string;
  today: string;
  access: { business: boolean; finance: boolean; partners: boolean; team: boolean; live: boolean };
  queues: QueueItem[];
  me: PersonalKpi;
  links: { team: string | null; live: string | null; supportKpi: string | null };
};

type TabKey = 'overview' | 'finance' | 'growth' | 'partners';

const MONTHS_UZ = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
const WEEKDAYS_UZ = ['yakshanba', 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba'];

function longDate(iso: string) {
  const d = new Date(`${iso}T00:00:00`);
  return `${d.getDate()}-${MONTHS_UZ[d.getMonth()]}, ${WEEKDAYS_UZ[d.getDay()]}`;
}

function agoLabel(iso: string | null) {
  if (!iso) return '';
  const min = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
  if (min < 1) return 'hozir';
  if (min < 60) return `${min} daq oldin`;
  const h = Math.floor(min / 60);
  if (h < 24) return `${h} soat oldin`;
  return `${Math.floor(h / 24)} kun oldin`;
}

function QueueBoard({ queues }: { queues: QueueItem[] }) {
  const [showAll, setShowAll] = useState(false);
  const active = queues.filter((q) => q.count > 0);
  const calm = queues.filter((q) => q.count === 0);

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600 mb-0">Diqqat talab qiladi</h5>
          <p className="mb-0 text-secondary f-s-13">Sizning bo‘limlaringizdagi navbatdagi ishlar — har 30 soniyada yangilanadi</p>
        </div>
        {calm.length ? (
          <button type="button" className="btn btn-sm btn-light-secondary b-r-10" onClick={() => setShowAll((v) => !v)}>
            {showAll ? 'Faqat ishlar' : `Hammasini ko‘rsatish (${queues.length})`}
          </button>
        ) : null}
      </div>
      <div className="card-body">
        {active.length === 0 && !showAll ? (
          <div className="d-flex align-items-center gap-3 p-3 b-r-15 text-light-success">
            <span className="h-45 w-45 d-flex-center b-r-50 bg-success text-white flex-shrink-0"><i className="ti ti-check f-s-22"></i></span>
            <div>
              <div className="f-w-600">Navbatda ish yo‘q</div>
              <div className="f-s-13">Barcha arizalar, buyurtmalar va murojaatlar ko‘rib chiqilgan. Ajoyib!</div>
            </div>
          </div>
        ) : (
          <div className="row g-3">
            {(showAll ? queues : active).map((q) => (
              <div className="col-sm-6 col-lg-4 col-xxl-3" key={q.key}>
                <Link href={q.url} className={`kc-queue-tile d-flex align-items-center gap-3 p-3 b-r-15 h-100 text-reset ${q.count > 0 ? `kc-queue-${q.tone}` : 'kc-queue-calm'}`}>
                  <span className={`h-45 w-45 d-flex-center b-r-12 flex-shrink-0 f-s-20 text-light-${q.count > 0 ? q.tone : 'secondary'}`}><i className={`ti ${q.icon}`}></i></span>
                  <span className="min-w-0 flex-grow-1">
                    <span className="d-block f-w-600 text-dark text-truncate">{q.label}</span>
                    <span className="d-block f-s-12 text-secondary text-truncate">{q.hint}</span>
                  </span>
                  <span className={`f-w-700 f-s-22 ${q.count > 0 ? `text-${q.tone}` : 'text-secondary'}`}>{fmt(q.count)}</span>
                </Link>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function MyWork({ me, links }: { me: PersonalKpi; links: Workspace['links'] }) {
  const palette = usePalette();
  const days = me.activity.days.map((d) => ({ ...d, label: new Date(`${d.date}T00:00:00`).getDate() }));
  const maxModule = Math.max(1, ...me.activity.modules.map((m) => m.count));

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600 mb-0">Mening ishim · so‘nggi 7 kun</h5>
          <p className="mb-0 text-secondary f-s-13">Paneldagi amallaringiz va rolingizga mos ko‘rsatkichlar</p>
        </div>
        <div className="d-flex gap-2">
          {links.supportKpi ? <Link href={links.supportKpi} className="btn btn-sm btn-light-primary b-r-10"><i className="ti ti-chart-histogram me-1"></i>Support KPI</Link> : null}
          {links.team ? <Link href={links.team} className="btn btn-sm btn-light-primary b-r-10"><i className="ti ti-users-group me-1"></i>Jamoa KPI</Link> : null}
        </div>
      </div>
      <div className="card-body">
        <div className="row g-3 mb-3">
          {me.highlights.map((h) => (
            <div className="col-6 col-xl-3" key={h.label}>
              <MiniStat label={h.label} value={typeof h.value === 'number' ? fmt(h.value) : h.value} icon={`ti ${h.icon}`} tone={h.tone} meta={h.hint} />
            </div>
          ))}
        </div>
        <div className="row g-3">
          <div className="col-lg-5">
            <div className="f-w-600 mb-2 f-s-14">Kunlik faollik</div>
            <ResponsiveContainer width="100%" height={150}>
              <BarChart data={days} margin={{ left: 0, right: 0, top: 4, bottom: 0 }}>
                <XAxis dataKey="label" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                <YAxis allowDecimals={false} hide />
                <Tooltip cursor={{ fill: palette.grid }} formatter={(v: number) => [`${fmt(v)} ta amal`, 'Faollik']} labelFormatter={() => ''} />
                <Bar dataKey="count" fill={palette.indigo} radius={[6, 6, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
            {me.activity.modules.length ? (
              <div className="mt-2">
                {me.activity.modules.slice(0, 5).map((m) => (
                  <div className="mb-2" key={m.module}>
                    <div className="d-flex justify-content-between f-s-12 mb-1"><span className="text-secondary">{m.label}</span><span className="f-w-600">{fmt(m.count)}</span></div>
                    <div className="progress h-5"><div className="progress-bar bg-primary" style={{ width: `${Math.max(4, (m.count / maxModule) * 100)}%` }}></div></div>
                  </div>
                ))}
              </div>
            ) : null}
          </div>
          <div className="col-lg-7">
            <div className="f-w-600 mb-2 f-s-14">So‘nggi amallar</div>
            {me.recent.length ? (
              <ul className="list-unstyled mb-0">
                {me.recent.slice(0, 8).map((r) => (
                  <li key={r.id} className="d-flex align-items-start gap-2 py-2 border-bottom kc-last-0">
                    <span className={`h-30 w-30 d-flex-center b-r-50 flex-shrink-0 f-s-14 ${r.ok ? 'text-light-primary' : 'text-light-danger'}`}><i className={`ti ${r.ok ? 'ti-check' : 'ti-alert-triangle'}`}></i></span>
                    <span className="min-w-0 flex-grow-1">
                      <span className="d-block f-s-13 text-dark text-truncate">{r.text}</span>
                      <span className="d-block f-s-11 text-secondary">{r.moduleLabel} · {agoLabel(r.at)}</span>
                    </span>
                  </li>
                ))}
              </ul>
            ) : <EmptyState text="Bu hafta hali amal bajarilmagan." icon="ti ti-mood-smile" />}
          </div>
        </div>
      </div>
    </div>
  );
}

function PipelinePanel({ counts }: { counts: Record<string, number> }) {
  const steps = [
    { key: 'new', label: 'Yangi', tone: 'primary', icon: 'ti-sparkles' },
    { key: 'packing', label: 'Qadoqlanmoqda', tone: 'info', icon: 'ti-package' },
    { key: 'onway', label: 'Yo‘lda', tone: 'warning', icon: 'ti-truck-delivery' },
    { key: 'done', label: 'Yetkazildi', tone: 'success', icon: 'ti-circle-check' },
    { key: 'cancelled', label: 'Bekor / qaytgan', tone: 'danger', icon: 'ti-circle-x' },
  ];
  const total = Math.max(1, steps.reduce((a, s) => a + (counts[s.key] || 0), 0));

  return (
    <div className="card h-100">
      <div className="card-header d-flex align-items-center justify-content-between">
        <h5 className="f-w-600 mb-0">Buyurtmalar oqimi</h5>
        <Link href="/boshqaruv/orders" className="f-s-13">Barchasi</Link>
      </div>
      <div className="card-body">
        <div className="progress mb-3" style={{ height: 10 }}>
          {steps.map((s) => (
            <div key={s.key} className={`progress-bar bg-${s.tone}`} style={{ width: `${((counts[s.key] || 0) / total) * 100}%` }} title={s.label}></div>
          ))}
        </div>
        <ul className="list-unstyled mb-0">
          {steps.map((s) => (
            <li key={s.key} className="d-flex align-items-center gap-2 py-2 border-bottom kc-last-0">
              <span className={`h-35 w-35 d-flex-center b-r-10 flex-shrink-0 text-light-${s.tone}`}><i className={`ti ${s.icon}`}></i></span>
              <span className="flex-grow-1 text-secondary f-s-14">{s.label}</span>
              <span className="f-w-600 text-dark">{fmt(counts[s.key] || 0)}</span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

function TabLoading() {
  return (
    <div className="card"><div className="card-body text-center text-secondary py-5">
      <span className="spinner-border spinner-border-sm me-2"></span>Tahlil hisoblanmoqda…
    </div></div>
  );
}

export default function Dashboard() {
  const palette = usePalette();
  const page = usePage<{
    workspace: Workspace;
    dashboard: DashboardPayload | null;
    economics?: Partial<DashboardPayload> | null;
    growth?: Partial<DashboardPayload> | null;
    partners?: Partial<DashboardPayload> | null;
    auth?: { admin?: { name?: string; role?: string } };
  }>();
  const { workspace, economics, growth, partners, auth } = page.props;
  const base = page.props.dashboard;
  const dashboard: DashboardPayload = { ...emptyDashboard, ...(base || {}), ...(economics || {}), ...(growth || {}), ...(partners || {}) } as DashboardPayload;
  const access = workspace.access;
  const finance = access.finance;

  const tabs = ([
    { key: 'overview', label: 'Umumiy', icon: 'ti-layout-dashboard', show: true },
    { key: 'finance', label: 'Moliya', icon: 'ti-coins', show: access.finance },
    { key: 'growth', label: 'O‘sish', icon: 'ti-trending-up', show: true },
    { key: 'partners', label: 'Hamkorlar', icon: 'ti-building-store', show: access.partners },
  ] as Array<{ key: TabKey; label: string; icon: string; show: boolean }>).filter((t) => t.show);

  const [tab, setTab] = useState<TabKey>(() => {
    try {
      const saved = localStorage.getItem('kc-dashboard-tab') as TabKey | null;
      return saved && tabs.some((t) => t.key === saved) ? saved : 'overview';
    } catch { return 'overview'; }
  });
  const [stale, setStale] = useState<Record<string, boolean>>({});
  const [loadingTab, setLoadingTab] = useState(false);
  const [chartMetric, setChartMetric] = useState<'revenue' | 'profit' | 'orders'>(finance ? 'revenue' : 'orders');
  const [showCustomRange, setShowCustomRange] = useState(dashboard.range.key === 'custom');
  const [customFrom, setCustomFrom] = useState(dashboard.range.from || '');
  const [customTo, setCustomTo] = useState(dashboard.range.to || '');
  const [isFiltering, setIsFiltering] = useState(false);
  const current = dashboard.periods.current || { revenue: 0, orders: 0, users: 0, aov: 0 };
  const previous = dashboard.periods.previous || null;
  const localNow = new Date();
  const today = `${localNow.getFullYear()}-${String(localNow.getMonth() + 1).padStart(2, '0')}-${String(localNow.getDate()).padStart(2, '0')}`;

  const propOf: Record<TabKey, 'economics' | 'growth' | 'partners' | null> = { overview: null, finance: 'economics', growth: 'growth', partners: 'partners' };
  const loadedOf: Record<TabKey, unknown> = { overview: true, finance: economics, growth, partners };

  const periodParams = () => {
    const params: Record<string, string> = { dashboard_period: dashboard.range.key };
    if (dashboard.range.key === 'custom' && dashboard.range.from && dashboard.range.to) {
      params.dashboard_from = dashboard.range.from;
      params.dashboard_to = dashboard.range.to;
    }
    return params;
  };

  const loadTab = (key: TabKey, force = false) => {
    const prop = propOf[key];
    if (!prop) return;
    if (!force && loadedOf[key] !== undefined && !stale[key]) return;
    setLoadingTab(true);
    router.reload({
      only: [prop],
      data: periodParams(),
      onFinish: () => { setLoadingTab(false); setStale((s) => ({ ...s, [key]: false })); },
    });
  };

  const openTab = (key: TabKey) => {
    setTab(key);
    try { localStorage.setItem('kc-dashboard-tab', key); } catch { /* yo'q */ }
    loadTab(key);
  };

  useEffect(() => {
    if (tab !== 'overview') loadTab(tab);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const selectPeriod = (dashboardPeriod: string, from?: string, to?: string) => {
    setIsFiltering(true);
    const prop = propOf[tab];
    setStale({ finance: tab !== 'finance', growth: tab !== 'growth', partners: tab !== 'partners' });
    router.get('/boshqaruv', {
      dashboard_period: dashboardPeriod,
      ...(dashboardPeriod === 'custom' ? { dashboard_from: from, dashboard_to: to } : {}),
    }, {
      only: prop ? ['dashboard', prop] : ['dashboard'],
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
    const params = new URLSearchParams({ ...periodParams(), export_type: exportType });
    return `${dashboard.exportUrl}?${params.toString()}`;
  };

  const firstName = (auth?.admin?.name || workspace.me.admin.name || '').split(/\s+/)[0];
  const tabLoaded = tab === 'overview' || (loadedOf[tab] !== undefined && loadedOf[tab] !== null);

  return (
    <div>
      {/* ── Sarlavha ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-1">{workspace.greeting}, {firstName}!</h4>
          <p className="mb-0 text-secondary">
            <span className="badge text-light-primary me-2">{workspace.me.admin.role}</span>
            {longDate(workspace.today)}
          </p>
        </div>
        {base ? (
          <div className="d-flex gap-2 align-items-center flex-wrap justify-content-end">
            <div className="nav kc-segment" role="tablist" aria-label="Dashboard davri">
              {([
                ['today', 'Bugun'],
                ['week', 'Hafta'],
                ['month', 'Oy'],
                ['year', 'Yil'],
                ['all', 'Barchasi'],
              ] as const).map(([key, label]) => (
                <div className="nav-item" key={key}>
                  <button type="button" role="tab" aria-selected={dashboard.range.key === key} disabled={isFiltering} className={`nav-link ${dashboard.range.key === key ? 'active' : ''}`} onClick={() => selectPeriod(key)}>
                    {label}
                  </button>
                </div>
              ))}
              <div className="nav-item">
                <button type="button" role="tab" aria-selected={dashboard.range.key === 'custom'} disabled={isFiltering} className={`nav-link ${dashboard.range.key === 'custom' ? 'active' : ''}`} onClick={() => setShowCustomRange((value) => !value)}>
                  <i className="ti ti-calendar-stats"></i>Sana
                </button>
              </div>
            </div>
            {dashboard.exportUrl ? (
              <div className="btn-group btn-group-sm">
                <a href={exportHref('investor')} className="btn btn-light-success" title="Investor paketi: unit economics, P&L, trend, sellers">
                  <i className="ti ti-file-spreadsheet me-1"></i>Excel
                </a>
                <button type="button" className="btn btn-light-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                  <span className="visually-hidden">Export turlari</span>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                  <li><a className="dropdown-item" href={exportHref('investor')}><i className="ti ti-sparkles me-2"></i>Investor pack</a></li>
                  <li><a className="dropdown-item" href={exportHref('unit')}><i className="ti ti-calculator me-2"></i>Unit economics + MRR</a></li>
                  <li><a className="dropdown-item" href={exportHref('dashboard')}><i className="ti ti-table me-2"></i>Dashboard snapshot</a></li>
                </ul>
              </div>
            ) : null}
            {workspace.links.live ? <Link href={workspace.links.live} className="btn btn-light-danger btn-sm"><i className="ti ti-broadcast me-1"></i>Live</Link> : null}
          </div>
        ) : null}
      </div>

      {base && showCustomRange ? (
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
                Ko‘rsatish
              </button>
            </div>
          </div>
        </div>
      ) : null}

      {/* ── 1. Navbatdagi ishlar (rolga mos) ── */}
      <QueueBoard queues={workspace.queues} />

      {/* ── 2. Mening ishim ── */}
      <MyWork me={workspace.me} links={workspace.links} />

      {/* ── 3. Biznes ko'rinishi ── */}
      {base ? (
        <>
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mx-1 mb-3 mt-2">
            <h5 className="f-w-600 mb-0">Biznes ko‘rinishi · <span className="text-secondary f-w-500">{dashboard.range.label}</span></h5>
            <div className="nav kc-segment" role="tablist" aria-label="Bo‘limlar">
              {tabs.map((t) => (
                <div className="nav-item" key={t.key}>
                  <button type="button" role="tab" aria-selected={tab === t.key} className={`nav-link ${tab === t.key ? 'active' : ''}`} onClick={() => openTab(t.key)}>
                    <i className={`ti ${t.icon}`}></i>{t.label}
                  </button>
                </div>
              ))}
            </div>
          </div>

          {tab === 'overview' ? (
            <>
              <div className="row mb-1">
                {finance ? (
                  <>
                    <PeriodCard index={0} label="Daromad" value={money(current.revenue)} delta={previous ? change(current.revenue, previous.revenue) : null} icon="ti-coins" help={periodHelps.revenue} />
                    <PeriodCard index={1} label="Yakuniy savdolar" value={fmt(current.orders)} delta={previous ? change(current.orders, previous.orders) : null} icon="ti-shopping-bag" help={periodHelps.orders} />
                    <PeriodCard index={2} label="O‘rtacha chek" value={money(current.aov)} delta={previous ? change(current.aov, previous.aov) : null} icon="ti-receipt" help={periodHelps.aov} />
                    <PeriodCard index={3} label="Yangi foydalanuvchilar" value={fmt(current.users)} delta={previous ? change(current.users, previous.users) : null} icon="ti-user-plus" help={periodHelps.users} />
                  </>
                ) : (
                  <>
                    <PeriodCard index={0} label="Yakuniy savdolar" value={fmt(current.orders)} delta={previous ? change(current.orders, previous.orders) : null} icon="ti-shopping-bag" help={periodHelps.orders} />
                    <PeriodCard index={1} label="Yangi foydalanuvchilar" value={fmt(current.users)} delta={previous ? change(current.users, previous.users) : null} icon="ti-user-plus" help={periodHelps.users} />
                    <PeriodCard index={2} label="Jarayondagi buyurtmalar" value={fmt((dashboard.status.main.new || 0) + (dashboard.status.main.packing || 0) + (dashboard.status.main.onway || 0))} delta={null} icon="ti-hourglass" />
                    <PeriodCard index={3} label="Hozir onlayn" value={fmt(dashboard.metrics.onlineUsers || 0)} delta={null} icon="ti-broadcast" help={metricHelps.onlineUsers} />
                  </>
                )}
              </div>

              <div className="row">
                <div className="col-xl-8">
                  <div className="card h-100">
                    <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                      <div>
                        <h5 className="f-w-600 mb-0">Savdo trendi</h5>
                        <p className="mb-0 text-secondary f-s-13">{dashboard.salesTrend.granularity} · yakunlangan savdolar</p>
                      </div>
                      <div className="nav kc-segment kc-segment-sm" role="tablist" aria-label="Grafik ko‘rsatkichi">
                        {(finance ? [['revenue', 'Daromad'], ['profit', 'Marja'], ['orders', 'Buyurtma']] : [['orders', 'Buyurtma']]).map(([key, label]) => (
                          <div className="nav-item" key={key}>
                            <button type="button" role="tab" aria-selected={chartMetric === key} className={`nav-link ${chartMetric === key ? 'active' : ''}`} onClick={() => setChartMetric(key as typeof chartMetric)}>{label}</button>
                          </div>
                        ))}
                      </div>
                    </div>
                    <div className="card-body">
                      <ResponsiveContainer width="100%" height={300}>
                        <AreaChart data={dashboard.salesByMonth} margin={{ left: 0, right: 12, top: 8, bottom: 0 }}>
                          <defs>
                            <linearGradient id="dashTrend" x1="0" y1="0" x2="0" y2="1">
                              <stop offset="0%" stopColor={palette.indigo} stopOpacity={0.35} />
                              <stop offset="100%" stopColor={palette.indigo} stopOpacity={0} />
                            </linearGradient>
                          </defs>
                          <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                          <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} minTickGap={16} />
                          <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={48} tickFormatter={(value) => chartMetric === 'orders' ? fmt(Number(value)) : compact(Number(value))} />
                          <Tooltip formatter={(value: number) => chartMetric === 'orders' ? `${fmt(value)} ta` : money(value)} />
                          <Area type="monotone" dataKey={chartMetric} stroke={chartMetric === 'profit' ? palette.green : palette.indigo} fill="url(#dashTrend)" strokeWidth={2.5} />
                        </AreaChart>
                      </ResponsiveContainer>
                    </div>
                  </div>
                </div>
                <div className="col-xl-4">
                  <PipelinePanel counts={dashboard.status.main} />
                </div>
              </div>

              <div className="row">
                <div className="col-xl-4 col-md-6">
                  <RankPanel title="Top mahsulotlar" help="Tanlangan davrda yakunlangan savdolardagi mahsulotlar dona bo‘yicha. Sovg‘alar kirmaydi." rows={dashboard.topProducts.map((row) => ({ name: row.name, meta: `${fmt(row.quantity)} dona`, value: finance ? money(row.revenue) : `${fmt(row.quantity)} ta` }))} />
                </div>
                <div className="col-xl-4 col-md-6">
                  <div className="card h-100">
                    <div className="card-header d-flex align-items-center gap-2">
                      <h5 className="f-w-600 mb-0">Kategoriyalar ulushi</h5>
                      <InfoHint text="Yakunlangan savdolardagi mahsulotlar kategoriya bo‘yicha. Kanselyariya bitta guruh sifatida hisoblanadi." />
                    </div>
                    <div className="card-body">
                      {dashboard.categoryShare.length ? (
                        <>
                          <ResponsiveContainer width="100%" height={170}>
                            <PieChart>
                              <Pie data={dashboard.categoryShare} dataKey="value" innerRadius={48} outerRadius={74} paddingAngle={3}>
                                {dashboard.categoryShare.map((row, index) => <Cell key={row.name} fill={categoryColor(palette, index)} />)}
                              </Pie>
                              <Tooltip formatter={(value: number) => `${value}%`} />
                            </PieChart>
                          </ResponsiveContainer>
                          <ul className="list-unstyled mb-0">
                            {dashboard.categoryShare.slice(0, 5).map((row, index) => (
                              <li className="d-flex align-items-center justify-content-between gap-3 py-1 f-s-13" key={row.name}>
                                <span className="d-flex align-items-center gap-2 min-w-0 text-secondary"><span className="d-inline-block h-10 w-10 b-r-50 flex-shrink-0" style={{ background: categoryColor(palette, index) }}></span><span className="text-truncate">{row.name}</span></span>
                                <strong className="text-nowrap text-dark">{row.value}%</strong>
                              </li>
                            ))}
                          </ul>
                        </>
                      ) : <EmptyState text="Yakunlangan savdo hali yo‘q." />}
                    </div>
                  </div>
                </div>
                <div className="col-xl-4">
                  <div className="card h-100">
                    <div className="card-header"><h5 className="mb-0 f-w-600">Operatsion ogohlantirishlar</h5></div>
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
                            <p className="text-success-dark mb-0 f-s-13">Kritik ogohlantirish yo‘q.</p>
                          </li>
                        )}
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div className="row">
                <div className="col-xl-8">
                  <RecentOrders rows={dashboard.recentOrders} showAmount={finance} />
                </div>
                <div className="col-xl-4">
                  <div className="card h-100">
                    <div className="card-header"><h5 className="mb-0 f-w-600">Platforma</h5></div>
                    <div className="card-body">
                      <div className="row g-2">
                        {[
                          ['Foydalanuvchilar', dashboard.metrics.users, 'ti-users', '/boshqaruv/users'],
                          ['Premium', dashboard.metrics.premiumUsers, 'ti-sparkles', '/boshqaruv/users'],
                          ['Kitoblar', dashboard.metrics.books, 'ti-book', '/boshqaruv/books'],
                          ['Kanselyariya', dashboard.metrics.stationeries, 'ti-edit', '/boshqaruv/stationeries'],
                          ['Do‘konlar', dashboard.metrics.sellers, 'ti-building-store', '/boshqaruv/sellers'],
                          ['Kuryerlar', dashboard.metrics.couriers, 'ti-bike', '/boshqaruv/couriers'],
                        ].map(([label, value, icon, href]) => (
                          <div className="col-6" key={label as string}>
                            <Link href={href as string} className="d-flex align-items-center gap-2 p-2 b-r-10 b-1-light text-reset h-100">
                              <span className="h-35 w-35 d-flex-center b-r-10 text-light-primary flex-shrink-0"><i className={`ti ${icon}`}></i></span>
                              <span className="min-w-0">
                                <span className="d-block f-w-600 text-dark">{fmt(Number(value) || 0)}</span>
                                <span className="d-block f-s-11 text-secondary text-truncate">{label}</span>
                              </span>
                            </Link>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </>
          ) : null}

          {tab !== 'overview' && (loadingTab || !tabLoaded) ? <TabLoading /> : null}

          {tab === 'finance' && tabLoaded && !loadingTab ? (
            <>
              <div className="row"><FinancialPanel dashboard={dashboard} />
                <div className="col-xl-6">
                  <div className="row">
                    <DistributionPanel colClass="col-12" title="To‘lov holati" help="Tanlangan davrda yaratilgan buyurtmalar to‘lov statusi bo‘yicha." rows={dashboard.paymentSplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${row.share}%` }))} />
                    <DistributionPanel colClass="col-12" title="Yetkazish turlari" help="Yakunlangan savdolar yetkazish turi bo‘yicha." rows={dashboard.deliverySplit.map((row) => ({ name: row.name, value: `${fmt(row.count)} ta · ${money(row.revenue)}` }))} />
                  </div>
                </div>
              </div>
              <UnitEconomics data={dashboard.unitEconomics} monthly={dashboard.unitEconomicsMonthly} />
            </>
          ) : null}

          {tab === 'growth' && tabLoaded && !loadingTab ? (
            <>
              <BusinessKpis dashboard={dashboard} />
              <FunnelPanel funnel={dashboard.funnel} />
              <CohortPanel retention={dashboard.retention} />
              <PlatformAnalysis rows={dashboard.platformAnalysis} />
              <div className="row">
                <DistributionPanel colClass="col-xl-6" title="Hududlar" help="Yakunlangan savdolarning manzilidan viloyat/shahar olinadi." rows={dashboard.regions.map((row) => ({ name: row.name, value: finance ? `${fmt(row.value)} ta · ${money(row.revenue)}` : `${fmt(row.value)} ta` }))} />
              </div>
            </>
          ) : null}

          {tab === 'partners' && tabLoaded && !loadingTab ? (
            <>
              <div className="row">
                <StatusPanel colClass="col-md-6" title="Do‘kon buyurtmalari" counts={dashboard.status.seller} labels={{ all: 'Jami', payment_pending: 'To‘lov kutilmoqda', new: 'Yangi', accepted: 'Qabul qilingan', handover: 'Kuryerga berilgan', cancelled: 'Bekor' }} />
                <StatusPanel colClass="col-md-6" title="Kuryer buyurtmalari" counts={dashboard.status.courier} labels={{ all: 'Jami', pending: 'Kutmoqda', in_delivery: 'Yo‘lda', delivered: 'Yetkazildi', customer_received: 'Qabul qilindi', rejected: 'Bekor' }} />
              </div>
              <SellerScorecard rows={dashboard.sellerScorecard} />
              {finance ? <PartnerEconomics data={dashboard.partnerEconomics} /> : null}
            </>
          ) : null}
        </>
      ) : (
        <QuickLinks />
      )}
    </div>
  );
}

/** Biznes ko'rinishi bo'lmagan rollar uchun: o'z bo'limlariga tezkor havolalar. */
function QuickLinks() {
  const { props } = usePage<{ auth?: { admin?: { permissions?: string[]; isSuperAdmin?: boolean } } }>();
  const perms = new Set(props.auth?.admin?.permissions || []);
  const links = [
    ['support', '/boshqaruv/support/inbox', 'Support inbox', 'ti-messages'],
    ['support', '/boshqaruv/support/kpi', 'Support KPI', 'ti-chart-histogram'],
    ['support', '/boshqaruv/shikoyatlar', 'Shikoyatlar', 'ti-alert-triangle'],
    ['catalog', '/boshqaruv/catalog/submissions', 'Kitob arizalari', 'ti-inbox'],
    ['catalog', '/boshqaruv/books', 'Kitoblar', 'ti-book'],
    ['catalog', '/boshqaruv/catalog', 'Global katalog', 'ti-stack-2'],
    ['book-club', '/boshqaruv/book-club', 'Book Club', 'ti-bookmark'],
    ['hr', '/boshqaruv/karyera-arizalari', 'Karyera arizalari', 'ti-file-certificate'],
    ['hr', '/boshqaruv/vakansiyalar', 'Vakansiyalar', 'ti-id-badge'],
    ['push', '/boshqaruv/push', 'Push bildirishnomalar', 'ti-bell'],
    ['premium', '/boshqaruv/mystery-box', 'Mystery Box', 'ti-package'],
    ['settings', '/boshqaruv/settings', 'Sozlamalar', 'ti-settings'],
  ].filter(([perm]) => props.auth?.admin?.isSuperAdmin || perms.has(perm));

  if (!links.length) return null;

  return (
    <div className="card">
      <div className="card-header"><h5 className="f-w-600 mb-0">Mening bo‘limlarim</h5></div>
      <div className="card-body">
        <div className="row g-3">
          {links.map(([, href, label, icon]) => (
            <div className="col-6 col-md-4 col-xl-3" key={href}>
              <Link href={href} className="d-flex align-items-center gap-3 p-3 b-r-15 b-1-light text-reset h-100 kc-queue-tile">
                <span className="h-45 w-45 d-flex-center b-r-12 text-light-primary flex-shrink-0 f-s-20"><i className={`ti ${icon}`}></i></span>
                <span className="f-w-600 text-dark">{label}</span>
              </Link>
            </div>
          ))}
        </div>
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
  const [view, setView] = useState<'overview' | 'trend' | 'table'>('overview');
  const palette = usePalette();
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

  const tone: Tone = data.hasMarketingData ? ratioTone : 'secondary';
  const ratioPct = data.hasMarketingData ? Math.max(2, Math.min(100, (data.ltvCacRatio / 5) * 100)) : 0;
  const byLabel = (label: string) => cards.find((card) => card.l === label)!;
  const groups: Array<{ title: string; icon: string; items: string[] }> = [
    { title: 'Xaridor iqtisodiyoti', icon: 'ti ti-users', items: ['CAC', 'LTV (margin)', 'Payback', 'Repeat'] },
    { title: 'Order iqtisodiyoti', icon: 'ti ti-receipt', items: ['Margin / order', 'Gross margin'] },
    { title: 'Sifat signallari', icon: 'ti ti-shield-check', items: ['Refund rate', 'Cancel rate'] },
  ];
  const trend = months.map((m) => ({ ...m, marginPct: Number(m.marginPct || 0) }));

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="mb-0">Unit economics</h5>
            <InfoHint text="Investor va operator uchun birlik iqtisodiyoti. Ko'rsatkichlar tanlangan davr/lifetime bo'yicha, dinamika va jadval — so'nggi 12 oy (Input Data + Key Indicators)." />
          </div>
          <p className="mb-0 text-secondary f-s-13">Xaridor jalb qilish narxi, qiymati va order marjasi</p>
        </div>
        <div className="nav kc-segment kc-segment-sm" role="tablist" aria-label="Unit economics ko'rinishi">
          {([['overview', "Ko'rsatkichlar", 'ti ti-layout-grid'], ['trend', 'Dinamika', 'ti ti-chart-bar'], ['table', 'Jadval', 'ti ti-table']] as const).map(([key, label, icon]) => (
            <div className="nav-item" key={key}>
              <button type="button" role="tab" aria-selected={view === key} disabled={key !== 'overview' && !months.length} className={`nav-link ${view === key ? 'active' : ''}`} onClick={() => setView(key)}><i className={icon}></i>{label}</button>
            </div>
          ))}
        </div>
      </div>
      <div className="card-body">
        {data.hasMarketingData ? null : (
          <div className="alert alert-light-warning d-flex align-items-center gap-2 f-s-13">
            <i className="ti ti-info-circle f-s-18"></i>
            CAC, LTV : CAC va payback hisoblanishi uchun Chiqimlar bo'limida "Marketing va reklama" xarajatlarini kiriting.
          </div>
        )}

        {view === 'overview' ? (
          <div className="row g-3">
            <div className="col-xl-4">
              <div className={`h-100 b-r-15 p-4 bg-light-${tone}`}>
                <div className="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <p className="f-s-14 f-w-500 mb-1 d-flex align-items-center gap-1">LTV : CAC <InfoHint text={byLabel('LTV : CAC').help} /></p>
                    <h2 className={`mb-0 text-${tone}-dark`}>{byLabel('LTV : CAC').v}</h2>
                  </div>
                  <span className="h-45 w-45 d-flex-center b-r-15 bg-white flex-shrink-0"><i className={`ti ti-gauge f-s-24 text-${tone}`}></i></span>
                </div>
                <span className={`badge bg-white text-${tone}-dark mt-2`}>{data.hasMarketingData ? byLabel('LTV : CAC').s : "Ma'lumot yetarli emas"}</span>
                <div className="mt-4">
                  <div className="d-flex justify-content-between f-s-12 mb-1"><span>0×</span><span>Norma ≥ 3×</span><span>5×+</span></div>
                  <div className="progress w-100 h-10" role="progressbar" aria-valuenow={data.ltvCacRatio} aria-valuemin={0} aria-valuemax={5}>
                    <div className={`progress-bar bg-${tone}`} style={{ width: `${ratioPct}%` }}></div>
                  </div>
                </div>
                <div className="row g-2 mt-3">
                  <div className="col-4"><p className="f-s-12 mb-0">LTV</p><h6 className="mb-0 f-w-600 f-s-14 text-break">{byLabel('LTV (margin)').v}</h6></div>
                  <div className="col-4"><p className="f-s-12 mb-0">CAC</p><h6 className="mb-0 f-w-600 f-s-14 text-break">{byLabel('CAC').v}</h6></div>
                  <div className="col-4"><p className="f-s-12 mb-0">Payback</p><h6 className="mb-0 f-w-600 f-s-14 text-break">{byLabel('Payback').v}</h6></div>
                </div>
              </div>
            </div>
            <div className="col-xl-8">
              {groups.map((group) => (
                <div key={group.title} className="mb-3">
                  <h6 className="f-w-600 text-secondary f-s-14 mb-2"><i className={`${group.icon} me-1`}></i>{group.title}</h6>
                  <div className="row g-3">
                    {group.items.map((label) => {
                      const card = byLabel(label);
                      return (
                        <div className="col-sm-6" key={label}>
                          <MiniStat icon={tiIcon(card.icon)} tone={card.c || 'primary'} valueTone={card.c} label={card.l} help={card.help} value={card.v} meta={card.s} />
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : null}

        {view === 'trend' && months.length ? (
          <div className="row g-3">
            <div className="col-xl-7">
              <h6 className="f-w-600 f-s-14 mb-2">Tushum, contribution va marja</h6>
              <ResponsiveContainer width="100%" height={300}>
                <ComposedChart data={trend} margin={{ left: 4, right: 4, top: 8, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis yAxisId="money" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={54} tickFormatter={(value) => compact(Number(value))} />
                  <YAxis yAxisId="pct" orientation="right" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={40} tickFormatter={(value) => `${value}%`} />
                  <Tooltip formatter={(value: number, name: string) => (name === 'Marja %' ? `${value}%` : money(value))} />
                  <Legend />
                  <Bar yAxisId="money" dataKey="grossRevenue" name="Tushum" fill={palette.violet} radius={[6, 6, 0, 0]} />
                  <Bar yAxisId="money" dataKey="contribution" name="Contribution" fill={palette.indigo} radius={[6, 6, 0, 0]} />
                  <Line yAxisId="pct" type="monotone" dataKey="marginPct" name="Marja %" stroke={palette.green} strokeWidth={2} dot={{ r: 3 }} />
                </ComposedChart>
              </ResponsiveContainer>
            </div>
            <div className="col-xl-5">
              <h6 className="f-w-600 f-s-14 mb-2">Sifat foizlari</h6>
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={trend} margin={{ left: 4, right: 12, top: 8, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={40} tickFormatter={(value) => `${value}%`} />
                  <Tooltip formatter={(value: number) => `${value}%`} />
                  <Legend />
                  <Line type="monotone" dataKey="repeatRate" name="Repeat" stroke={palette.green} strokeWidth={2} dot={false} />
                  <Line type="monotone" dataKey="refundRate" name="Refund" stroke={palette.danger} strokeWidth={2} dot={false} />
                  <Line type="monotone" dataKey="cancelRate" name="Cancel" stroke={palette.amber} strokeWidth={2} dot={false} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </div>
        ) : null}

        {view === 'table' && months.length ? (
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 text-nowrap">
              <thead>
                <tr>
                  <th className="position-sticky start-0 bg-body">Ko'rsatkich</th>
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
  const [view, setView] = useState<'overview' | 'trend' | 'table'>('overview');
  const palette = usePalette();
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

          <div className="text-center py-4">
            <span className="h-60 w-60 d-flex-center b-r-50 text-light-warning mx-auto mb-3"><i className="ti ti-crown f-s-30"></i></span>
            <h6 className="f-w-600 mb-1">Hali faol premium hamkor yo'q</h6>
            <p className="text-secondary f-s-13 mb-0">Birinchi sotuvchi premium obuna sotib olgach, shu yerda MRR, churn, retention va LTV avtomatik hisoblanib boradi.</p>
          </div>
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

  const byLabel = (label: string) => cards.find((card) => card.l === label)!;
  const retention = [
    { label: 'Gross retention', value: s.grossRetention, tone: 'success', max: 100, help: byLabel('Gross Retention').help },
    { label: 'Net retention', value: s.netRetention, tone: 'info', max: 150, help: byLabel('Net Retention').help },
    { label: 'Churn · MRR', value: s.churnRateMrr, tone: 'danger', max: 20, help: byLabel('Churn · MRR').help },
    { label: 'Churn · hamkor', value: s.churnRateCount, tone: 'warning', max: 20, help: byLabel('Churn · hamkor').help },
  ];
  const movement = months.map((m) => ({ month: m.month, mrr: m.mrr, newMrr: m.newMrr, churnedMrr: -Math.abs(m.churnedMrr || 0), partners: m.currentPartners }));
  const secondary = ['ARPC', 'CAC', 'CAC Payback', 'Lifetime', 'LTV : CAC'];

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h5 className="mb-0">Hamkorlar MRR</h5>
            <span className="badge text-light-warning"><i className="ti ti-crown me-1"></i>Premium obuna</span>
            <InfoHint text="Premium obuna to'lagan sotuvchilar (hamkorlar) uchun SaaS uslubidagi MRR, churn, retention va LTV hisoboti. Xaridor unit economics blokidan alohida hisoblanadi, ikkalasi qo'shilmaydi." />
          </div>
          <p className="mb-0 text-secondary f-s-13">MRR · Churn · Retention · LTV — oylik dinamika</p>
        </div>
        <div className="nav kc-segment kc-segment-sm" role="tablist" aria-label="MRR ko'rinishi">
          {([['overview', "Ko'rsatkichlar", 'ti ti-layout-grid'], ['trend', 'Dinamika', 'ti ti-chart-bar'], ['table', 'Jadval', 'ti ti-table']] as const).map(([key, label, icon]) => (
            <div className="nav-item" key={key}>
              <button type="button" role="tab" aria-selected={view === key} className={`nav-link ${view === key ? 'active' : ''}`} onClick={() => setView(key)}><i className={icon}></i>{label}</button>
            </div>
          ))}
        </div>
      </div>
      <div className="card-body">
        {data.hasMarketingData ? null : (
          <div className="alert alert-light-warning d-flex align-items-center gap-2 f-s-13">
            <i className="ti ti-info-circle f-s-18"></i>
            CAC va LTV : CAC uchun Chiqimlar bo'limida marketing xarajatlarini kiriting.
          </div>
        )}

        {view === 'overview' ? (
          <div className="row g-3">
            <div className="col-xl-4">
              <div className="h-100 b-r-15 p-4 bg-light-primary">
                <div className="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <p className="f-s-14 f-w-500 mb-1">Joriy MRR</p>
                    <h3 className="mb-0 text-primary-dark text-break">{money(data.currentMrr)}</h3>
                  </div>
                  <span className="h-45 w-45 d-flex-center b-r-15 bg-white flex-shrink-0"><i className="ti ti-repeat f-s-24 text-primary"></i></span>
                </div>
                <div className="d-flex flex-wrap gap-2 mt-2">
                  <span className="badge bg-white text-primary-dark"><i className="ti ti-users me-1"></i>{fmt(data.currentPartners)} faol hamkor</span>
                  <span className="badge bg-white text-primary-dark">ARPC {money(s.arpc)}</span>
                </div>
                <div className="d-flex flex-column gap-3 mt-4">
                  {retention.map((row) => (
                    <div key={row.label}>
                      <div className="d-flex justify-content-between align-items-center f-s-13 mb-1">
                        <span className="f-w-500 d-flex align-items-center gap-1">{row.label}<InfoHint text={row.help} /></span>
                        <span className="f-w-600">{row.value === null ? '—' : `${row.value}%`}</span>
                      </div>
                      <div className="progress w-100 h-5" role="progressbar" aria-valuenow={row.value || 0} aria-valuemin={0} aria-valuemax={row.max}>
                        <div className={`progress-bar bg-${row.tone}`} style={{ width: `${row.value ? Math.max(2, Math.min(100, (row.value / row.max) * 100)) : 0}%` }}></div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
            <div className="col-xl-8">
              <div className="row g-3">
                {secondary.map((label) => {
                  const card = byLabel(label);
                  return (
                    <div className="col-sm-6 col-xxl-4" key={label}>
                      <MiniStat icon={tiIcon(card.icon)} tone={card.c || 'primary'} valueTone={card.c} label={card.l} help={card.help} value={card.v} meta={card.s} />
                    </div>
                  );
                })}
                <div className="col-sm-6 col-xxl-4">
                  <MiniStat icon="ti ti-diamond" tone="info" label="LTV" value={s.ltv !== null ? money(s.ltv) : '—'} meta="ARPC × lifetime" />
                </div>
              </div>
              <h6 className="f-w-600 f-s-14 mt-4 mb-2">MRR harakati</h6>
              <ResponsiveContainer width="100%" height={200}>
                <AreaChart data={movement} margin={{ left: 4, right: 8, top: 8, bottom: 0 }}>
                  <defs>
                    <linearGradient id="mrrFill" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor={palette.indigo} stopOpacity={0.35} />
                      <stop offset="100%" stopColor={palette.indigo} stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={54} tickFormatter={(value) => compact(Number(value))} />
                  <Tooltip formatter={(value: number) => money(value)} />
                  <Area type="monotone" dataKey="mrr" name="MRR" stroke={palette.indigo} fill="url(#mrrFill)" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>
        ) : null}

        {view === 'trend' ? (
          <div className="row g-3">
            <div className="col-xl-8">
              <h6 className="f-w-600 f-s-14 mb-2">Yangi va yo'qotilgan MRR</h6>
              <ResponsiveContainer width="100%" height={300}>
                <ComposedChart data={movement} margin={{ left: 4, right: 4, top: 8, bottom: 0 }} stackOffset="sign">
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={54} tickFormatter={(value) => compact(Number(value))} />
                  <Tooltip formatter={(value: number) => money(Math.abs(value))} />
                  <Legend />
                  <Bar dataKey="newMrr" name="Yangi MRR" stackId="move" fill={palette.green} radius={[6, 6, 0, 0]} />
                  <Bar dataKey="churnedMrr" name="Bekor bo'lgan MRR" stackId="move" fill={palette.danger} radius={[0, 0, 6, 6]} />
                  <Line type="monotone" dataKey="mrr" name="MRR" stroke={palette.indigo} strokeWidth={2} dot={{ r: 3 }} />
                </ComposedChart>
              </ResponsiveContainer>
            </div>
            <div className="col-xl-4">
              <h6 className="f-w-600 f-s-14 mb-2">Faol hamkorlar</h6>
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={movement} margin={{ left: 4, right: 4, top: 8, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="month" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={36} allowDecimals={false} />
                  <Tooltip formatter={(value: number) => fmt(value)} />
                  <Bar dataKey="partners" name="Hamkorlar" fill={palette.info} radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        ) : null}

        {view === 'table' ? (
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 text-nowrap">
              <thead>
                <tr>
                  <th className="position-sticky start-0 bg-body">Ko'rsatkich</th>
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
                        <PAvatar src={seller.avatar} name={seller.name} size="sm" />
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

function DistributionPanel({ title, rows, help, colClass = 'col-xl-4' }: { title: string; rows: Array<{ name: string; value: string }>; help?: string; colClass?: string }) {
  return (
    <div className={colClass}><div className="card h-100">
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

function StatusPanel({ title, counts, labels, colClass = 'col-xl-2 col-md-4' }: { title: string; counts: Record<string, number>; labels: Record<string, string>; colClass?: string }) {
  const entries = Object.entries(labels).filter(([key]) => key !== 'all');
  const sum = entries.reduce((acc, [key]) => acc + (counts[key] || 0), 0);
  const total = Math.max(counts.all || 0, sum, 1);
  return (
    <div className={colClass}>
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

function RecentOrders({ rows, showAmount = true }: { rows: DashboardPayload['recentOrders']; showAmount?: boolean }) {
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
                <PAvatar name={row.customer || 'Mijoz'} size="sm" className="customer-list-avtar" />
                <div className="customer-list-content min-w-0">
                  <h6 className="mb-0 f-s-15 txt-ellipsis-1">{row.customer || 'Mijoz'} <span className="text-secondary f-w-500 f-s-12">#{row.id}</span></h6>
                  <p className="mb-0 f-s-12 text-secondary txt-ellipsis-1">{row.status} · {row.updated_at || ''}</p>
                </div>
                {showAmount ? <span className="f-w-600 text-dark f-s-14 text-nowrap ms-auto">{money(row.amount)}</span> : <span className="ms-auto"></span>}
                {row.url ? <a href={row.url} className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 flex-shrink-0" title="Buyurtmani ochish"><i className="ti ti-eye"></i></a> : null}
              </li>
            ))}
          </ul>
        ) : <EmptyState text="Buyurtma topilmadi." />}
      </div>
    </div>
  );
}
