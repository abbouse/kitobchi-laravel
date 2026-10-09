import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { PageCrumbs } from '../Layout';
import { MiniStat, EmptyState } from '../components/Axelit';
import { usePalette } from '../utils/palette';

/*
 * Support KPI — operatorlar samaradorligi:
 * birinchi javob vaqti, yopilgan murojaatlar, yaxshi/yomon baholar, mamnunlik foizi.
 */

type Agent = {
  id: number;
  name: string;
  handled: number;
  closed: number;
  replies: number;
  good: number;
  bad: number;
  satisfaction: number | null;
  avg_first_response_s: number | null;
  open_now: number;
};

type Kpi = {
  range: { from: string; to: string };
  totals: {
    new: number; new_customer: number; new_shop: number; closed: number; open_now: number; unassigned_now: number;
    avg_first_response_s: number | null; good: number; bad: number; satisfaction: number | null;
  };
  agents: Agent[];
  bad_feedback: Array<{ key: string; segment: 'customer' | 'shop'; ticket_id: number; name: string; agent: string | null; at: string | null; title: string }>;
  days: Array<{ date: string; new: number; closed: number }>;
};

const RANGES = [
  { key: 'today', label: 'Bugun' },
  { key: '7d', label: '7 kun' },
  { key: '30d', label: '30 kun' },
];

function duration(seconds: number | null) {
  if (seconds === null || seconds === undefined) return '—';
  if (seconds < 60) return `${seconds} son`;
  const m = Math.round(seconds / 60);
  if (m < 60) return `${m} daq`;
  const h = Math.floor(m / 60);
  return `${h} soat ${m % 60} daq`;
}

function frtTone(seconds: number | null): 'success' | 'warning' | 'danger' | 'secondary' {
  if (seconds === null) return 'secondary';
  if (seconds <= 300) return 'success';
  if (seconds <= 1800) return 'warning';
  return 'danger';
}

function satTone(p: number | null): 'success' | 'warning' | 'danger' | 'secondary' {
  if (p === null) return 'secondary';
  if (p >= 85) return 'success';
  if (p >= 60) return 'warning';
  return 'danger';
}

function shortDate(iso: string) {
  const d = new Date(iso);
  return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}`;
}

export default function SupportKpi() {
  const { kpi, kpiFilters, inboxUrl } = usePage<{ kpi: Kpi; kpiFilters: { range: string; from: string; to: string }; inboxUrl: string }>().props;
  const palette = usePalette();
  const [from, setFrom] = useState(kpiFilters.from);
  const [to, setTo] = useState(kpiFilters.to);
  const t = kpi.totals;

  const go = (params: Record<string, string>) => router.get('/boshqaruv/support/kpi', params, { preserveScroll: true, preserveState: false });

  const maxHandled = Math.max(1, ...kpi.agents.map((a) => a.handled));

  return (
    <>
      <Head title="Support KPI" />
      <div className="row m-1">
        <div className="col-12 d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
          <div>
            <h4 className="main-title">Support KPI</h4>
            <PageCrumbs />
          </div>
          <div className="d-flex flex-wrap align-items-center gap-2">
            <div className="nav kc-segment">
              {RANGES.map((r) => (
                <div className="nav-item" key={r.key}>
                  <button type="button" className={`nav-link ${kpiFilters.range === r.key ? 'active' : ''}`} onClick={() => go({ range: r.key })}>{r.label}</button>
                </div>
              ))}
            </div>
            <div className="d-flex align-items-center gap-1">
              <input type="date" className="form-control form-control-sm" value={from} max={to} onChange={(e) => setFrom(e.target.value)} />
              <span className="text-secondary">—</span>
              <input type="date" className="form-control form-control-sm" value={to} min={from} onChange={(e) => setTo(e.target.value)} />
              <button type="button" className={`btn btn-sm ${kpiFilters.range === 'custom' ? 'btn-primary' : 'btn-light-primary'} b-r-10`} onClick={() => go({ range: 'custom', from, to })}>Ko‘rsatish</button>
            </div>
            <Link href={inboxUrl} className="btn btn-sm btn-primary b-r-10"><i className="ti ti-messages me-1"></i>Inbox</Link>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-6 col-xl-3">
          <MiniStat label="Yangi murojaatlar" value={t.new} icon="ti ti-message-plus" tone="primary" meta={`Mijoz ${t.new_customer} · Do‘kon ${t.new_shop}`} />
        </div>
        <div className="col-6 col-xl-3">
          <MiniStat label="Birinchi javob (o‘rtacha)" value={duration(t.avg_first_response_s)} icon="ti ti-clock-bolt" tone={frtTone(t.avg_first_response_s) === 'secondary' ? 'info' : frtTone(t.avg_first_response_s)} meta="Maqsad: 5 daqiqagacha" help="Murojaat ochilgandan operatorning birinchi javobigacha bo‘lgan o‘rtacha vaqt" />
        </div>
        <div className="col-6 col-xl-3">
          <MiniStat label="Mamnunlik" value={t.satisfaction === null ? '—' : `${t.satisfaction}%`} icon="ti ti-mood-happy" tone={satTone(t.satisfaction) === 'secondary' ? 'success' : satTone(t.satisfaction)} meta={<><i className="ti ti-thumb-up text-success"></i> {t.good} · <i className="ti ti-thumb-down text-danger"></i> {t.bad}</>} />
        </div>
        <div className="col-6 col-xl-3">
          <MiniStat label="Hozir ochiq" value={t.open_now} icon="ti ti-inbox" tone={t.unassigned_now > 0 ? 'warning' : 'success'} meta={t.unassigned_now > 0 ? `${t.unassigned_now} tasi navbatda (operatorsiz)` : 'Hammasi biriktirilgan'} />
        </div>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          <div className="card mb-3">
            <div className="card-header d-flex align-items-center justify-content-between">
              <h5 className="mb-0">Operatorlar</h5>
              <span className="f-s-12 text-secondary">{kpi.range.from} — {kpi.range.to} · yopilgan: {t.closed}</span>
            </div>
            <div className="card-body p-0">
              {kpi.agents.length === 0 ? <EmptyState text="Support huquqiga ega operator yo‘q" /> : (
                <div className="table-responsive">
                  <table className="table table-bottom-border align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Operator</th>
                        <th className="text-end">Murojaatlar</th>
                        <th className="text-end">Yopilgan</th>
                        <th className="text-end">Javoblar</th>
                        <th className="text-end">1-javob</th>
                        <th className="text-center">Baho</th>
                        <th className="text-end">Mamnunlik</th>
                        <th className="text-end">Hozir ochiq</th>
                      </tr>
                    </thead>
                    <tbody>
                      {kpi.agents.map((a) => (
                        <tr key={a.id}>
                          <td>
                            <div className="f-w-600">{a.name}</div>
                            <div className="progress mt-1" style={{ height: 4, maxWidth: 160 }}>
                              <div className="progress-bar bg-primary" style={{ width: `${Math.round((a.handled / maxHandled) * 100)}%` }}></div>
                            </div>
                          </td>
                          <td className="text-end f-w-600">{a.handled}</td>
                          <td className="text-end">{a.closed}</td>
                          <td className="text-end">{a.replies}</td>
                          <td className="text-end"><span className={`badge text-light-${frtTone(a.avg_first_response_s)}`}>{duration(a.avg_first_response_s)}</span></td>
                          <td className="text-center text-nowrap">
                            <span className="text-success me-2"><i className="ti ti-thumb-up"></i> {a.good}</span>
                            <span className={a.bad > 0 ? 'text-danger f-w-600' : 'text-secondary'}><i className="ti ti-thumb-down"></i> {a.bad}</span>
                          </td>
                          <td className="text-end"><span className={`badge text-light-${satTone(a.satisfaction)}`}>{a.satisfaction === null ? '—' : `${a.satisfaction}%`}</span></td>
                          <td className="text-end">{a.open_now}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>

          <div className="card mb-3">
            <div className="card-header"><h5 className="mb-0">Kunlar kesimida</h5></div>
            <div className="card-body">
              <ResponsiveContainer width="100%" height={260}>
                <BarChart data={kpi.days.map((d) => ({ ...d, label: shortDate(d.date) }))} barGap={2}>
                  <CartesianGrid strokeDasharray="3 3" stroke={palette.grid} vertical={false} />
                  <XAxis dataKey="label" stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} />
                  <YAxis allowDecimals={false} stroke={palette.line} tick={{ fill: palette.muted }} fontSize={11} width={32} />
                  <Tooltip cursor={{ fill: palette.grid }} contentStyle={{ background: palette.surface, border: `1px solid ${palette.line}`, borderRadius: 10 }} />
                  <Legend wrapperStyle={{ fontSize: 12 }} />
                  <Bar dataKey="new" name="Yangi" fill={palette.indigo} radius={[4, 4, 0, 0]} />
                  <Bar dataKey="closed" name="Yopilgan" fill={palette.green} radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="card mb-3">
            <div className="card-header d-flex align-items-center justify-content-between">
              <h5 className="mb-0">Yomon baholar</h5>
              <span className="badge text-light-danger">{t.bad}</span>
            </div>
            <div className="card-body p-0">
              {kpi.bad_feedback.length === 0 ? <EmptyState text="Bu davrda yomon baho yo‘q" icon="ti ti-mood-happy" /> : (
                <ul className="list-unstyled mb-0">
                  {kpi.bad_feedback.map((b) => (
                    <li key={`${b.segment}-${b.ticket_id}`} className="border-bottom kc-last-0">
                      <Link href={`${inboxUrl}?key=${encodeURIComponent(b.key)}&segment=${b.segment}`} className="d-flex align-items-start gap-2 p-3 text-reset">
                        <span className="h-35 w-35 d-flex-center b-r-10 text-light-danger flex-shrink-0"><i className={`ti ${b.segment === 'shop' ? 'ti-building-store' : 'ti-user'}`}></i></span>
                        <span className="flex-grow-1" style={{ minWidth: 0 }}>
                          <span className="d-flex align-items-center gap-2">
                            <span className="f-w-600 text-truncate">{b.name}</span>
                            <span className="ms-auto f-s-11 text-secondary flex-shrink-0">#{b.ticket_id}</span>
                          </span>
                          <span className="d-block f-s-12 text-secondary text-truncate">{b.title || '—'}</span>
                          <span className="d-block f-s-11 text-secondary">{b.agent ? `Operator: ${b.agent}` : 'Operator biriktirilmagan'}{b.at ? ` · ${shortDate(b.at)}` : ''}</span>
                        </span>
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>

          <div className="card mb-3">
            <div className="card-body f-s-13 text-secondary">
              <div className="f-w-600 text-dark mb-2">Ko‘rsatkichlar qanday hisoblanadi</div>
              <ul className="ps-3 mb-0">
                <li><b>Murojaatlar</b> — davrda ochilgan va operatorga biriktirilgan suhbatlar.</li>
                <li><b>1-javob</b> — murojaat ochilgandan birinchi javobgacha o‘rtacha vaqt (ichki eslatmalar hisobga olinmaydi).</li>
                <li><b>Mamnunlik</b> — yaxshi baholar ulushi: yaxshi ÷ (yaxshi + yomon).</li>
                <li>Baho mijoz/do‘kon suhbat yopilgandan keyin qo‘yadi (Telegram, ilova yoki do‘kon ilovasida).</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
