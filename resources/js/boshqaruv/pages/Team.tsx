import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { PageCrumbs } from '../Layout';
import Modal from '../components/AppModal';
import { MiniStat, EmptyState, type Tone } from '../components/Axelit';
import { usePalette } from '../utils/palette';
import { apiJson } from '../utils/realtime';

/*
 * Jamoa KPI — har bir xodim nima ish qilgani:
 * paneldagi amallar (modullar bo'yicha), faol kunlar, rolga xos natijalar
 * (katalog qarorlari, to'lovlar, support javoblari va baholar).
 */

type Row = {
  id: number;
  name: string;
  email: string;
  role: string;
  roleKey: string;
  isActive: boolean;
  isReadOnly: boolean;
  lastLogin: string | null;
  actions: number;
  activeDays: number;
  failed: number;
  modules: Array<{ module: string; label: string; count: number }>;
  days: number[];
  domain: Record<string, number>;
  support: { replies: number; closed: number; good: number; bad: number; satisfaction: number | null; avgFirstResponse: number | null } | null;
};

type TeamPayload = {
  range: { from: string; to: string };
  rows: Row[];
  totals: { admins: number; activeAdmins: number; actions: number; failed: number; catalogReviews: number; supportReplies: number };
  modules: Array<{ module: string; label: string; count: number }>;
  days: Array<{ date: string; count: number }>;
};

type Personal = {
  admin: { id: number; name: string; role: string };
  activity: { total: number; failed: number; activeDays: number; modules: Array<{ module: string; label: string; count: number }>; days: Array<{ date: string; count: number }> };
  highlights: Array<{ label: string; value: number | string; hint: string; tone: Tone; icon: string }>;
  recent: Array<{ id: number; text: string; moduleLabel: string; ok: boolean; at: string | null }>;
};

const DOMAIN_LABELS: Record<string, string> = {
  catalogReviews: 'Kitob arizalari',
  productModeration: 'Mahsulot moderatsiyasi',
  slotReviews: 'Katalog joylari',
  orderUpdates: 'Buyurtma amallari',
  payouts: 'Pul yechish qarorlari',
  refunds: 'Pul qaytarish',
  expenses: 'Chiqimlar',
  sellerApprovals: 'Do‘kon arizalari',
  courierApprovals: 'Kuryer arizalari',
  bookClubModeration: 'Book Club',
  complaints: 'Shikoyatlar',
  hrReplies: 'Nomzodlarga javob',
  contracts: 'Shartnomalar',
  pushSent: 'Push xabarlar',
};

const RANGES = [
  { key: 'today', label: 'Bugun' },
  { key: '7d', label: '7 kun' },
  { key: '30d', label: '30 kun' },
];

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));

function ago(iso: string | null) {
  if (!iso) return 'hech qachon';
  const min = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
  if (min < 60) return `${min} daq oldin`;
  const h = Math.floor(min / 60);
  if (h < 24) return `${h} soat oldin`;
  return `${Math.floor(h / 24)} kun oldin`;
}

function duration(s: number | null) {
  if (s === null || s === undefined) return '—';
  if (s < 60) return `${s} son`;
  const m = Math.round(s / 60);
  return m < 60 ? `${m} daq` : `${Math.floor(m / 60)} soat ${m % 60} daq`;
}

function Spark({ values }: { values: number[] }) {
  const max = Math.max(1, ...values);
  return (
    <span className="d-inline-flex align-items-end gap-1" style={{ height: 24 }} title={values.join(' · ')}>
      {values.slice(-14).map((v, i) => (
        <span key={i} className="d-inline-block b-r-4 bg-primary" style={{ width: 5, height: Math.max(2, Math.round((v / max) * 24)), opacity: v ? 0.85 : 0.2 }}></span>
      ))}
    </span>
  );
}

function initials(name: string) {
  const p = name.trim().split(/\s+/);
  return ((p[0]?.[0] || '?') + (p[1]?.[0] || '')).toUpperCase();
}

export default function Team() {
  const { team, teamFilters } = usePage<{ team: TeamPayload; teamFilters: { range: string; from: string; to: string } }>().props;
  const palette = usePalette();
  const [from, setFrom] = useState(teamFilters.from);
  const [to, setTo] = useState(teamFilters.to);
  const [q, setQ] = useState('');
  const [sort, setSort] = useState<'actions' | 'name' | 'lastLogin'>('actions');
  const [detail, setDetail] = useState<Personal | null>(null);
  const [detailRow, setDetailRow] = useState<Row | null>(null);
  const [loading, setLoading] = useState(false);

  const go = (params: Record<string, string>) => router.get('/boshqaruv/team', params, { preserveScroll: true });

  const rows = useMemo(() => {
    const term = q.trim().toLowerCase();
    const list = team.rows.filter((r) => !term || r.name.toLowerCase().includes(term) || r.role.toLowerCase().includes(term) || r.email.toLowerCase().includes(term));
    return [...list].sort((a, b) => {
      if (sort === 'name') return a.name.localeCompare(b.name);
      if (sort === 'lastLogin') return (b.lastLogin || '').localeCompare(a.lastLogin || '');
      return b.actions - a.actions;
    });
  }, [team.rows, q, sort]);

  const openDetail = async (row: Row) => {
    setDetailRow(row);
    setDetail(null);
    setLoading(true);
    try {
      const params = new URLSearchParams({ range: teamFilters.range, from: teamFilters.from, to: teamFilters.to });
      setDetail(await apiJson<Personal>(`/boshqaruv/team/${row.id}/data?${params}`));
    } finally {
      setLoading(false);
    }
  };

  const maxActions = Math.max(1, ...team.rows.map((r) => r.actions));
  const t = team.totals;

  return (
    <>
      <Head title="Jamoa KPI" />
      <div className="d-flex flex-wrap align-items-end justify-content-between gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Jamoa KPI</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">Xodimlar faolligi va natijalari · {team.range.from} — {team.range.to}</p>
        </div>
        <div className="d-flex flex-wrap align-items-center gap-2">
          <div className="nav kc-segment">
            {RANGES.map((r) => (
              <div className="nav-item" key={r.key}>
                <button type="button" className={`nav-link ${teamFilters.range === r.key ? 'active' : ''}`} onClick={() => go({ range: r.key })}>{r.label}</button>
              </div>
            ))}
          </div>
          <div className="d-flex align-items-center gap-1">
            <input type="date" className="form-control form-control-sm" value={from} max={to} onChange={(e) => setFrom(e.target.value)} />
            <span className="text-secondary">—</span>
            <input type="date" className="form-control form-control-sm" value={to} min={from} onChange={(e) => setTo(e.target.value)} />
            <button type="button" className={`btn btn-sm ${teamFilters.range === 'custom' ? 'btn-primary' : 'btn-light-primary'} b-r-10`} onClick={() => go({ range: 'custom', from, to })}>Ko‘rsatish</button>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-6 col-xl-3"><MiniStat label="Faol xodimlar" value={`${t.activeAdmins} / ${t.admins}`} icon="ti ti-users-group" tone="primary" meta="Davr ichida kamida bitta amal" /></div>
        <div className="col-6 col-xl-3"><MiniStat label="Jami amallar" value={fmt(t.actions)} icon="ti ti-activity" tone="info" meta={t.failed ? `${t.failed} tasi xato bilan` : 'Xatosiz'} /></div>
        <div className="col-6 col-xl-3"><MiniStat label="Support javoblari" value={fmt(t.supportReplies)} icon="ti ti-message-check" tone="success" meta="Mijoz va do‘konlarga" /></div>
        <div className="col-6 col-xl-3"><MiniStat label="Katalog qarorlari" value={fmt(t.catalogReviews)} icon="ti ti-books" tone="warning" meta="Kitob arizalari bo‘yicha" /></div>
      </div>

      <div className="row">
        <div className="col-xl-8">
          <div className="card">
            <div className="card-header"><h5 className="mb-0 f-w-600">Kunlik faollik</h5></div>
            <div className="card-body">
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={team.days.map((d) => ({ ...d, label: d.date.slice(5).split('-').reverse().join('.') }))}>
                  <XAxis dataKey="label" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} minTickGap={8} />
                  <YAxis allowDecimals={false} stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={34} />
                  <Tooltip cursor={{ fill: palette.grid }} formatter={(v: number) => [`${fmt(v)} ta`, 'Amallar']} />
                  <Bar dataKey="count" fill={palette.indigo} radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
        <div className="col-xl-4">
          <div className="card">
            <div className="card-header"><h5 className="mb-0 f-w-600">Bo‘limlar bo‘yicha</h5></div>
            <div className="card-body">
              {team.modules.length === 0 ? <EmptyState text="Bu davrda amal yo‘q." /> : team.modules.slice(0, 8).map((m) => (
                <div className="mb-2" key={m.module}>
                  <div className="d-flex justify-content-between f-s-13 mb-1"><span className="text-secondary">{m.label}</span><span className="f-w-600">{fmt(m.count)}</span></div>
                  <div className="progress h-5"><div className="progress-bar bg-primary" style={{ width: `${Math.max(3, (m.count / Math.max(1, team.modules[0].count)) * 100)}%` }}></div></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 className="mb-0 f-w-600">Xodimlar</h5>
          <div className="d-flex gap-2 align-items-center">
            <input type="search" className="form-control form-control-sm" style={{ width: 220 }} placeholder="Ism, rol yoki email" value={q} onChange={(e) => setQ(e.target.value)} />
            <select className="form-select form-select-sm" style={{ width: 170 }} value={sort} onChange={(e) => setSort(e.target.value as typeof sort)}>
              <option value="actions">Faollik bo‘yicha</option>
              <option value="lastLogin">Oxirgi kirish</option>
              <option value="name">Ism bo‘yicha</option>
            </select>
          </div>
        </div>
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-bottom-border align-middle mb-0">
              <thead>
                <tr>
                  <th>Xodim</th>
                  <th>Faollik</th>
                  <th className="text-end">Amallar</th>
                  <th className="text-end">Faol kun</th>
                  <th>Asosiy ishlari</th>
                  <th>Natijalar</th>
                  <th className="text-end">Oxirgi kirish</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => {
                  const domain = Object.entries(r.domain || {}).filter(([, v]) => v > 0).sort((a, b) => b[1] - a[1]).slice(0, 3);
                  return (
                    <tr key={r.id} role="button" onClick={() => openDetail(r)} className={r.isActive ? '' : 'opacity-50'}>
                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <span className="h-40 w-40 d-flex-center b-r-50 text-light-primary f-w-600 flex-shrink-0">{initials(r.name)}</span>
                          <div className="min-w-0">
                            <div className="f-w-600 text-truncate">{r.name}{!r.isActive ? <span className="badge text-light-secondary ms-1">nofaol</span> : null}</div>
                            <div className="f-s-12 text-secondary">{r.role}{r.isReadOnly ? ' · faqat ko‘rish' : ''}</div>
                          </div>
                        </div>
                      </td>
                      <td style={{ minWidth: 150 }}>
                        <Spark values={r.days} />
                        <div className="progress h-5 mt-1" style={{ maxWidth: 140 }}><div className="progress-bar bg-primary" style={{ width: `${(r.actions / maxActions) * 100}%` }}></div></div>
                      </td>
                      <td className="text-end f-w-600">{fmt(r.actions)}{r.failed ? <div className="f-s-11 text-danger">{r.failed} xato</div> : null}</td>
                      <td className="text-end">{r.activeDays}</td>
                      <td>
                        <div className="d-flex flex-wrap gap-1">
                          {r.modules.length ? r.modules.slice(0, 3).map((m) => <span key={m.module} className="badge text-light-primary">{m.label} {m.count}</span>) : <span className="text-secondary f-s-12">—</span>}
                        </div>
                      </td>
                      <td>
                        <div className="d-flex flex-wrap gap-1">
                          {r.support ? (
                            <span className={`badge ${r.support.satisfaction === null ? 'text-light-secondary' : r.support.satisfaction >= 85 ? 'text-light-success' : r.support.satisfaction >= 60 ? 'text-light-warning' : 'text-light-danger'}`}>
                              Support: {r.support.replies} javob · {r.support.satisfaction === null ? 'baho yo‘q' : `${r.support.satisfaction}% mamnun`}
                            </span>
                          ) : null}
                          {domain.map(([k, v]) => <span key={k} className="badge text-light-info">{DOMAIN_LABELS[k] || k}: {v}</span>)}
                          {!r.support && domain.length === 0 ? <span className="text-secondary f-s-12">—</span> : null}
                        </div>
                      </td>
                      <td className="text-end f-s-13 text-secondary text-nowrap">{ago(r.lastLogin)}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          {rows.length === 0 ? <EmptyState text="Xodim topilmadi." /> : null}
        </div>
      </div>

      <Modal show={!!detailRow} onHide={() => setDetailRow(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title>{detailRow?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loading || !detail ? (
            <div className="text-center text-secondary py-5"><span className="spinner-border spinner-border-sm me-2"></span>Yuklanmoqda…</div>
          ) : (
            <>
              <div className="row g-3 mb-3">
                {detail.highlights.map((h) => (
                  <div className="col-6 col-lg-3" key={h.label}>
                    <MiniStat label={h.label} value={typeof h.value === 'number' ? fmt(h.value) : h.value} icon={`ti ${h.icon}`} tone={h.tone} meta={h.hint} />
                  </div>
                ))}
              </div>
              {detailRow?.support ? (
                <div className="b-1-light b-r-15 p-3 mb-3 d-flex flex-wrap gap-4 f-s-13">
                  <span><span className="text-secondary">Yopgan:</span> <b>{detailRow.support.closed}</b></span>
                  <span><span className="text-secondary">1-javob:</span> <b>{duration(detailRow.support.avgFirstResponse)}</b></span>
                  <span className="text-success"><i className="ti ti-thumb-up"></i> {detailRow.support.good}</span>
                  <span className="text-danger"><i className="ti ti-thumb-down"></i> {detailRow.support.bad}</span>
                </div>
              ) : null}
              <div className="row g-3">
                <div className="col-lg-5">
                  <div className="f-w-600 mb-2">Bo‘limlar</div>
                  {detail.activity.modules.length ? detail.activity.modules.map((m) => (
                    <div className="mb-2" key={m.module}>
                      <div className="d-flex justify-content-between f-s-13 mb-1"><span className="text-secondary">{m.label}</span><span className="f-w-600">{m.count}</span></div>
                      <div className="progress h-5"><div className="progress-bar bg-primary" style={{ width: `${Math.max(4, (m.count / Math.max(1, detail.activity.modules[0].count)) * 100)}%` }}></div></div>
                    </div>
                  )) : <div className="text-secondary f-s-13">Bu davrda amal yo‘q.</div>}
                </div>
                <div className="col-lg-7">
                  <div className="f-w-600 mb-2">So‘nggi amallar</div>
                  {detail.recent.length ? (
                    <ul className="list-unstyled mb-0">
                      {detail.recent.map((a) => (
                        <li key={a.id} className="d-flex align-items-start gap-2 py-2 border-bottom kc-last-0">
                          <i className={`ti ${a.ok ? 'ti-check text-primary' : 'ti-alert-triangle text-danger'} mt-1`}></i>
                          <span className="min-w-0">
                            <span className="d-block f-s-13 text-truncate">{a.text}</span>
                            <span className="d-block f-s-11 text-secondary">{a.moduleLabel} · {ago(a.at)}</span>
                          </span>
                        </li>
                      ))}
                    </ul>
                  ) : <div className="text-secondary f-s-13">Amal yo‘q.</div>}
                </div>
              </div>
            </>
          )}
        </Modal.Body>
      </Modal>
    </>
  );
}
