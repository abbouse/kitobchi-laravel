import { useEffect, useMemo, useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import Modal from '../components/AppModal';
import { EmptyState, MiniStat } from '../components/Axelit';

/**
 * "Sovg'alar g'ildiragi" — ilovadagi o'yin: sovg'alar, tushish foizlari,
 * yutish shartlari, tangalar sozlamalari va statistika.
 */

type Difficulty = 'easy' | 'medium' | 'hard';
type PrizeType = 'fragments' | 'promocode' | 'coins';

interface Prize {
  id: number;
  type: PrizeType;
  title_uz: string;
  title_ru: string | null;
  image: string | null;
  cover: string | null;
  difficulty: Difficulty;
  chance: number;
  is_active: boolean;
  position: number;
  fragments_total: number | null;
  edition_id: number | null;
  edition_title: string | null;
  coins_amount: number | null;
  discount_type: 'percent' | 'fixed' | null;
  discount_value: number | null;
  max_discount: number | null;
  min_order_amount: number | null;
  scope: 'all' | 'edition' | 'seller';
  scope_id: number | null;
  scope_title: string | null;
  valid_days: number;
  min_orders: number;
  min_spins: number;
  max_wins_per_user: number | null;
  daily_limit: number | null;
  stock: number | null;
  won_count: number;
  starts_at: string | null;
  ends_at: string | null;
  actual_rate: number | null;
}

interface Task { id: number; key: string; title_uz: string; title_ru: string | null; coins: number; is_active: boolean }
interface Settings { enabled: number; spin_cost: number; daily_coins: number; order_coins: number; welcome_coins: number }
interface Stats {
  spins_total: number; spins_today: number; players: number; coins_in_wallets: number; coins_earned: number;
  rewards_issued: number; rewards_used: number; daily: { date: string; spins: number; wins: number }[];
}
interface Recent { id: number; user_id: number; user: string; prize: string | null; result: string; code: string | null; at: string }

interface PageProps {
  settings: Settings;
  prizes: Prize[];
  tasks: Task[];
  presets: Record<Difficulty, number>;
  stats: Stats;
  recent: Recent[];
  [key: string]: unknown;
}

const BASE = '/boshqaruv/prize-game';
const fmt = (n: number | null | undefined) => new Intl.NumberFormat('uz-UZ').format(Number(n || 0));
const pct = (n: number) => `${Number(n.toFixed(2))}%`;

const DIFF: Record<Difficulty, { label: string; tone: string; color: string }> = {
  easy: { label: 'Oson', tone: 'success', color: 'rgba(var(--success), 1)' },
  medium: { label: "O'rta", tone: 'warning', color: 'rgba(var(--warning), 1)' },
  hard: { label: 'Qiyin', tone: 'danger', color: 'rgba(var(--danger), 1)' },
};

const TYPES: Record<PrizeType, { label: string; icon: string; hint: string }> = {
  fragments: { label: "Kitob bo'laklari", icon: 'ti-book', hint: "Bo'laklar yig'ilsa — kitob bepul (shaxsiy kod)" },
  promocode: { label: 'Promokod', icon: 'ti-ticket', hint: "Har bir g'olibga alohida kod yaratiladi" },
  coins: { label: 'Tangalar', icon: 'ti-coins', hint: "Hamyonga qo'shimcha tanga" },
};

const RESULT_LABEL: Record<string, string> = {
  fragment: "Bo'lak", completed: 'Kitob yig\'ildi', promocode: 'Promokod', coins: 'Tanga',
};

function prizeValue(p: Prize): string {
  if (p.type === 'coins') return `${fmt(p.coins_amount)} tanga`;
  if (p.type === 'fragments') return `${p.fragments_total ?? 0} bo'lak`;
  const v = p.discount_type === 'percent' ? `${p.discount_value}%` : `${fmt(p.discount_value)} so'm`;
  return `${v} · ${p.valid_days} kun`;
}

function conditions(p: Prize): string[] {
  const out: string[] = [];
  if (p.min_orders > 0) out.push(`${p.min_orders}+ buyurtma`);
  if (p.min_spins > 0) out.push(`${p.min_spins}+ aylantirish`);
  if (p.max_wins_per_user) out.push(`1 kishiga ${p.max_wins_per_user} marta`);
  if (p.daily_limit) out.push(`kuniga ${p.daily_limit} ta`);
  if (p.stock) out.push(`jami ${p.stock} ta`);
  if (p.ends_at) out.push(`${p.ends_at.slice(0, 10)} gacha`);
  if (p.type === 'promocode' && p.scope !== 'all') out.push(p.scope === 'seller' ? `Do'kon: ${p.scope_title ?? p.scope_id}` : `Kitob: ${p.scope_title ?? p.scope_id}`);
  if (p.type === 'promocode' && p.min_order_amount) out.push(`min ${fmt(p.min_order_amount)} so'm`);
  return out;
}

export default function PrizeGame() {
  const { settings, prizes = [], tasks = [], presets, stats, recent = [] } = usePage<PageProps>().props;
  const [editing, setEditing] = useState<Partial<Prize> | null>(null);
  const [chances, setChances] = useState<Record<number, string>>({});

  useEffect(() => {
    setChances(Object.fromEntries(prizes.map((p) => [p.id, String(p.chance)])));
  }, [prizes]);

  const dirty = prizes.some((p) => Number(chances[p.id] ?? p.chance) !== Number(p.chance));
  const active = prizes.filter((p) => p.is_active);
  const total = active.reduce((s, p) => s + Number(chances[p.id] ?? p.chance), 0);
  const nothing = Math.max(0, 100 - total);

  const saveChances = () => {
    router.post(`${BASE}/chances`, { chances: Object.fromEntries(Object.entries(chances).map(([k, v]) => [k, Number(v) || 0])) }, { preserveScroll: true });
  };

  const applyPreset = (p: Prize) => setChances((c) => ({ ...c, [p.id]: String(presets[p.difficulty]) }));

  const destroy = (p: Prize) => {
    if (!confirm(`"${p.title_uz}" sovg'asini o'chirasizmi? Yig'ilgan bo'laklar ham o'chadi.`)) return;
    router.delete(`${BASE}/prizes/${p.id}`, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Sovg'alar g'ildiragi</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">Sovg'alar, tushish foizlari, yutish shartlari va tangalar</p>
        </div>
        <div className="d-flex gap-2 align-items-center">
          <span className={`badge ${settings.enabled ? 'text-light-success' : 'text-light-secondary'} f-s-13 px-3 py-2`}>
            <i className={`ti ${settings.enabled ? 'ti-circle-check' : 'ti-power'} me-1`}></i>{settings.enabled ? "O'yin yoqilgan" : "O'yin o'chirilgan"}
          </span>
          <button className="btn btn-primary" onClick={() => setEditing({ type: 'promocode', difficulty: 'medium', chance: presets.medium, scope: 'all', valid_days: 30, discount_type: 'percent', is_active: true })}>
            <i className="ti ti-plus me-1"></i>Sovg'a qo'shish
          </button>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-3 col-md-6"><MiniStat icon="ti ti-rotate-clockwise" label="Aylantirishlar" value={fmt(stats.spins_total)} meta={`Bugun: ${fmt(stats.spins_today)}`} /></div>
        <div className="col-xl-3 col-md-6"><MiniStat icon="ti ti-users" tone="info" label="O'yinchilar" value={fmt(stats.players)} /></div>
        <div className="col-xl-3 col-md-6"><MiniStat icon="ti ti-coins" tone="warning" label="Hamyonlardagi tangalar" value={fmt(stats.coins_in_wallets)} meta={`Jami berilgan: ${fmt(stats.coins_earned)}`} /></div>
        <div className="col-xl-3 col-md-6"><MiniStat icon="ti ti-ticket" tone="success" label="Berilgan kodlar" value={fmt(stats.rewards_issued)} meta={`Ishlatilgan: ${fmt(stats.rewards_used)}`} /></div>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          <div className="card mb-3">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
              <h5 className="mb-0">Sovg'alar va tushish foizlari</h5>
              <div className="d-flex gap-2">
                {dirty ? <button className="btn btn-light-secondary btn-sm" onClick={() => setChances(Object.fromEntries(prizes.map((p) => [p.id, String(p.chance)])))}>Bekor</button> : null}
                <button className="btn btn-primary btn-sm" disabled={!dirty} onClick={saveChances}><i className="ti ti-check me-1"></i>Foizlarni saqlash</button>
              </div>
            </div>
            <div className="card-body">
              <ChanceBar prizes={active} chances={chances} nothing={nothing} />
              <div className="d-flex flex-wrap gap-3 f-s-13 text-secondary mt-2 mb-3">
                <span>Faol sovg'alar: <b className={total > 100 ? 'text-danger' : 'text-dark'}>{pct(total)}</b></span>
                <span>"Omad keyingi safar": <b className="text-dark">{pct(nothing)}</b></span>
                {(Object.keys(DIFF) as Difficulty[]).map((d) => (
                  <span key={d}><span className={`badge text-light-${DIFF[d].tone} me-1`}>{DIFF[d].label}</span>tayyor foiz {presets[d]}%</span>
                ))}
              </div>
              {total > 100 ? <div className="alert alert-light-danger f-s-13 py-2">Yig'indi 100% dan oshdi — foizlar proporsional kamaytirib ishlatiladi va "hech narsa" tushmaydi.</div> : null}

              {prizes.length === 0 ? <EmptyState text="Hali sovg'a qo'shilmagan" /> : (
                <div className="table-responsive app-scroll">
                  <table className="table table-bottom-border align-middle mb-0">
                    <thead><tr><th>Sovg'a</th><th>Qiyinlik</th><th style={{ width: 150 }}>Foiz</th><th>Haqiqiy (30 kun)</th><th>Shartlar</th><th>Berildi</th><th></th></tr></thead>
                    <tbody>
                      {prizes.map((p) => {
                        const img = p.image || p.cover;
                        const conds = conditions(p);
                        return (
                          <tr key={p.id} className={p.is_active ? '' : 'opacity-50'}>
                            <td>
                              <div className="d-flex align-items-center gap-2">
                                <span className="h-40 w-40 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0">
                                  {img ? <img src={img} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} /> : <i className={`ti ${TYPES[p.type].icon} f-s-18`}></i>}
                                </span>
                                <div className="min-w-0">
                                  <div className="f-w-600 txt-ellipsis-1">{p.title_uz}</div>
                                  <div className="f-s-12 text-secondary">{TYPES[p.type].label} · {prizeValue(p)}</div>
                                </div>
                              </div>
                            </td>
                            <td><span className={`badge text-light-${DIFF[p.difficulty].tone}`}>{DIFF[p.difficulty].label}</span></td>
                            <td>
                              <div className="input-group input-group-sm">
                                <input type="number" step="0.001" min={0} max={100} className="form-control" value={chances[p.id] ?? ''} onChange={(e) => setChances((c) => ({ ...c, [p.id]: e.target.value }))} />
                                <span className="input-group-text">%</span>
                                <button type="button" className="btn btn-light-secondary" title={`Tayyor foiz: ${presets[p.difficulty]}%`} onClick={() => applyPreset(p)}><i className="ti ti-wand"></i></button>
                              </div>
                            </td>
                            <td className="text-nowrap">{p.actual_rate === null ? <span className="text-muted">—</span> : <span className={Math.abs(p.actual_rate - p.chance) > Math.max(1, p.chance * 0.5) ? 'text-warning-dark f-w-600' : ''}>{pct(p.actual_rate)}</span>}</td>
                            <td style={{ maxWidth: 220 }}>
                              {conds.length ? <div className="d-flex flex-wrap gap-1">{conds.map((c) => <span key={c} className="badge text-light-secondary">{c}</span>)}</div> : <span className="text-muted f-s-13">Hamma uchun</span>}
                            </td>
                            <td className="text-nowrap">{fmt(p.won_count)}{p.stock ? ` / ${fmt(p.stock)}` : ''}</td>
                            <td className="text-nowrap text-end">
                              <button className={`btn ${p.is_active ? 'btn-light-success' : 'btn-light-secondary'} icon-btn w-30 h-30 b-r-22 me-1`} title={p.is_active ? "O'chirish" : 'Yoqish'} onClick={() => router.patch(`${BASE}/prizes/${p.id}/toggle`, {}, { preserveScroll: true })}><i className={`ti ${p.is_active ? 'ti-toggle-right' : 'ti-toggle-left'}`}></i></button>
                              <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" title="Tahrirlash" onClick={() => setEditing(p)}><i className="ti ti-pencil"></i></button>
                              <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" title="O'chirish" onClick={() => destroy(p)}><i className="ti ti-trash"></i></button>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>

          <div className="card">
            <div className="card-header"><h5 className="mb-0">So'nggi yutuqlar</h5></div>
            <div className="card-body">
              <SpinChart daily={stats.daily} />
              {recent.length === 0 ? <EmptyState text="Hali yutuqlar yo'q" /> : (
                <div className="table-responsive app-scroll" style={{ maxHeight: 380 }}>
                  <table className="table table-bottom-border align-middle mb-0">
                    <thead><tr><th>Mijoz</th><th>Sovg'a</th><th>Natija</th><th>Kod</th><th>Vaqt</th></tr></thead>
                    <tbody>{recent.map((r) => (
                      <tr key={r.id}>
                        <td><a href={`/boshqaruv/users/${r.user_id}`} className="f-w-600 text-dark">{r.user}</a></td>
                        <td>{r.prize ?? '—'}</td>
                        <td><span className={`badge ${r.result === 'fragment' ? 'text-light-secondary' : 'text-light-success'}`}>{RESULT_LABEL[r.result] ?? r.result}</span></td>
                        <td className="font-monospace">{r.code ?? '—'}</td>
                        <td className="text-muted text-nowrap">{String(r.at).slice(0, 16).replace('T', ' ')}</td>
                      </tr>
                    ))}</tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <SettingsCard settings={settings} />
          <TasksCard tasks={tasks} />
        </div>
      </div>

      <PrizeForm prize={editing} presets={presets} onHide={() => setEditing(null)} />
    </div>
  );
}

const SEG_COLORS = ['#2980DD', '#6C5CE7', '#FF7AA2', '#20C997', '#F59F00', '#E8590C', '#15AABF', '#AE3EC9', '#5C940D', '#1C7ED6'];

function ChanceBar({ prizes, chances, nothing }: { prizes: Prize[]; chances: Record<number, string>; nothing: number }) {
  const items = prizes.map((p, i) => ({ id: p.id, label: p.title_uz, value: Number(chances[p.id] ?? p.chance) || 0, color: SEG_COLORS[i % SEG_COLORS.length] }));
  const sum = items.reduce((s, x) => s + x.value, 0) + nothing;
  const scale = sum > 0 ? 100 / sum : 0;
  return (
    <div>
      <div className="d-flex w-100 b-r-10 overflow-hidden" style={{ height: 14, background: 'rgba(var(--secondary), .12)' }}>
        {items.map((x) => <div key={x.id} title={`${x.label}: ${pct(x.value)}`} style={{ width: `${x.value * scale}%`, background: x.color }} />)}
        {nothing > 0 ? <div title={`Omad keyingi safar: ${pct(nothing)}`} style={{ width: `${nothing * scale}%`, background: 'rgba(var(--secondary), .25)' }} /> : null}
      </div>
      <div className="d-flex flex-wrap gap-3 mt-2 f-s-12">
        {items.map((x) => <span key={x.id} className="d-inline-flex align-items-center gap-1"><span className="d-inline-block b-r-50" style={{ width: 8, height: 8, background: x.color }}></span>{x.label} <b>{pct(x.value)}</b></span>)}
      </div>
    </div>
  );
}

function SpinChart({ daily }: { daily: Stats['daily'] }) {
  if (!daily.length) return null;
  const max = Math.max(...daily.map((d) => d.spins), 1);
  return (
    <div className="mb-3">
      <div className="d-flex align-items-end gap-1" style={{ height: 90 }}>
        {daily.map((d) => (
          <div key={d.date} className="flex-grow-1 d-flex flex-column justify-content-end" style={{ height: '100%' }} title={`${d.date}: ${d.spins} aylantirish, ${d.wins} yutuq`}>
            <div className="b-r-4" style={{ height: `${(d.wins / max) * 100}%`, background: 'rgba(var(--success), .85)' }} />
            <div style={{ height: `${((d.spins - d.wins) / max) * 100}%`, background: 'rgba(var(--primary), .35)', borderTopLeftRadius: d.wins ? 0 : 4, borderTopRightRadius: d.wins ? 0 : 4 }} />
          </div>
        ))}
      </div>
      <div className="d-flex justify-content-between f-s-11 text-muted mt-1"><span>{daily[0].date.slice(5)}</span><span>Aylantirish / <span className="text-success">yutuq</span> — so'nggi 14 kun</span><span>{daily[daily.length - 1].date.slice(5)}</span></div>
    </div>
  );
}

function SettingsCard({ settings }: { settings: Settings }) {
  const [enabled, setEnabled] = useState(!!settings.enabled);
  useEffect(() => setEnabled(!!settings.enabled), [settings.enabled]);
  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const f = new FormData(e.currentTarget);
    f.set('enabled', enabled ? '1' : '0');
    router.post(`${BASE}/settings`, f, { preserveScroll: true });
  };
  return (
    <div className="card mb-3">
      <div className="card-header"><h5 className="mb-0">Tangalar sozlamalari</h5></div>
      <div className="card-body">
        <form className="app-form" onSubmit={submit}>
          <div className="form-check form-switch d-flex align-items-center gap-2 mb-3 ps-0">
            <input className="form-check-input ms-0" type="checkbox" role="switch" id="pg-enabled" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} />
            <label className="form-check-label f-w-600" htmlFor="pg-enabled">O'yin ilovada ko'rinsin</label>
          </div>
          <div className="row g-3">
            <NumField name="spin_cost" label="1 aylantirish narxi" suffix="tanga" value={settings.spin_cost} col="col-6" />
            <NumField name="daily_coins" label="Kunlik bonus" suffix="tanga" value={settings.daily_coins} col="col-6" />
            <NumField name="order_coins" label="Yetkazilgan buyurtma uchun" suffix="tanga" value={settings.order_coins} col="col-6" />
            <NumField name="welcome_coins" label="Birinchi kirishda" suffix="tanga" value={settings.welcome_coins} col="col-6" />
          </div>
          <button className="btn btn-primary w-100 mt-3" type="submit">Saqlash</button>
        </form>
      </div>
    </div>
  );
}

function TasksCard({ tasks }: { tasks: Task[] }) {
  const [edit, setEdit] = useState<Task | null>(null);
  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!edit) return;
    const f = new FormData(e.currentTarget);
    router.put(`${BASE}/tasks/${edit.id}`, {
      title_uz: f.get('title_uz'), title_ru: f.get('title_ru'), coins: Number(f.get('coins') || 0), is_active: f.get('is_active') === '1',
    }, { preserveScroll: true, onSuccess: () => setEdit(null) });
  };
  return (
    <div className="card">
      <div className="card-header"><h5 className="mb-0">Vazifalar</h5><p className="mb-0 f-s-12 text-secondary">Har kuni bir marta tanga beriladi</p></div>
      <div className="card-body">
        {tasks.map((t) => (
          <div key={t.id} className={`d-flex align-items-center gap-3 py-2 border-bottom ${t.is_active ? '' : 'opacity-50'}`}>
            <span className="h-35 w-35 d-flex-center b-r-10 text-light-primary flex-shrink-0"><i className={`ti ${t.key === 'order' ? 'ti-shopping-bag' : t.key === 'review' ? 'ti-star' : 'ti-message'}`}></i></span>
            <div className="flex-grow-1 min-w-0"><div className="f-w-600 txt-ellipsis-1">{t.title_uz}</div><div className="f-s-12 text-secondary">+{t.coins} tanga</div></div>
            <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => setEdit(t)}><i className="ti ti-pencil"></i></button>
          </div>
        ))}
      </div>
      <Modal show={!!edit} onHide={() => setEdit(null)} centered>
        <form className="app-form" onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Vazifani tahrirlash</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="row g-3">
              <div className="col-12"><label className="form-label">Nomi (uz)</label><input name="title_uz" defaultValue={edit?.title_uz} required className="form-control" /></div>
              <div className="col-12"><label className="form-label">Nomi (ru)</label><input name="title_ru" defaultValue={edit?.title_ru ?? ''} className="form-control" /></div>
              <NumField name="coins" label="Tanga" suffix="tanga" value={edit?.coins ?? 0} col="col-6" />
              <div className="col-6"><label className="form-label">Holati</label><select name="is_active" defaultValue={edit?.is_active ? '1' : '0'} className="form-select"><option value="1">Faol</option><option value="0">O'chirilgan</option></select></div>
            </div>
          </Modal.Body>
          <Modal.Footer><button type="button" className="btn btn-light-secondary" onClick={() => setEdit(null)}>Bekor</button><button type="submit" className="btn btn-primary">Saqlash</button></Modal.Footer>
        </form>
      </Modal>
    </div>
  );
}

function NumField({ name, label, value, suffix, col = 'col-md-6', placeholder, min = 0, step }: { name: string; label: ReactNode; value?: number | string | null; suffix?: string; col?: string; placeholder?: string; min?: number; step?: string }) {
  return (
    <div className={col}>
      <label className="form-label f-s-13">{label}</label>
      <div className="input-group">
        <input name={name} type="number" min={min} step={step} defaultValue={value ?? ''} placeholder={placeholder} className="form-control" />
        {suffix ? <span className="input-group-text">{suffix}</span> : null}
      </div>
    </div>
  );
}

function PrizeForm({ prize, presets, onHide }: { prize: Partial<Prize> | null; presets: Record<Difficulty, number>; onHide: () => void }) {
  const isEdit = !!prize?.id;
  const [type, setType] = useState<PrizeType>('promocode');
  const [difficulty, setDifficulty] = useState<Difficulty>('medium');
  const [chance, setChance] = useState('7');
  const [scope, setScope] = useState<'all' | 'edition' | 'seller'>('all');
  const [discountType, setDiscountType] = useState<'percent' | 'fixed'>('percent');
  const [edition, setEdition] = useState<Picked | null>(null);
  const [scopeItem, setScopeItem] = useState<Picked | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [removeImage, setRemoveImage] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!prize) return;
    setType((prize.type as PrizeType) || 'promocode');
    setDifficulty((prize.difficulty as Difficulty) || 'medium');
    setChance(String(prize.chance ?? presets[(prize.difficulty as Difficulty) || 'medium']));
    setScope(prize.scope || 'all');
    setDiscountType(prize.discount_type || 'percent');
    setEdition(prize.edition_id ? { id: prize.edition_id, title: prize.edition_title || `#${prize.edition_id}` } : null);
    setScopeItem(prize.scope_id ? { id: prize.scope_id, title: prize.scope_title || `#${prize.scope_id}` } : null);
    setPreview(prize.image || null);
    setRemoveImage(false);
    setErrors({});
  }, [prize, presets]);

  const pickDifficulty = (d: Difficulty) => { setDifficulty(d); setChance(String(presets[d])); };

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const f = new FormData(e.currentTarget);
    f.set('type', type);
    f.set('difficulty', difficulty);
    f.set('chance', chance || '0');
    f.set('scope', type === 'promocode' ? scope : 'all');
    if (type === 'promocode') f.set('discount_type', discountType);
    if (type === 'fragments' && edition) f.set('edition_id', String(edition.id));
    if (type === 'promocode' && scope !== 'all' && scopeItem) f.set('scope_id', String(scopeItem.id));
    f.set('remove_image', removeImage ? '1' : '0');
    const file = f.get('image');
    if (file instanceof File && file.size === 0) f.delete('image');
    // Bo'sh maydonlar null bo'lib ketsin
    for (const [k, v] of Array.from(f.entries())) if (v === '') f.delete(k);
    setBusy(true);
    router.post(isEdit ? `${BASE}/prizes/${prize?.id}` : `${BASE}/prizes`, f, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: onHide,
      onError: (err) => setErrors(err as Record<string, string>),
      onFinish: () => setBusy(false),
    });
  };

  const err = (k: string) => (errors[k] ? <div className="text-danger f-s-12 mt-1">{errors[k]}</div> : null);

  return (
    <Modal show={!!prize} onHide={onHide} size="lg" page={false}>
      <form className="app-form" onSubmit={submit}>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{isEdit ? "Sovg'ani tahrirlash" : "Yangi sovg'a"}</Modal.Title></Modal.Header>
        <Modal.Body>
          <Section title="Sovg'a turi">
            <div className="row g-2">
              {(Object.keys(TYPES) as PrizeType[]).map((t) => (
                <div className="col-md-4" key={t}>
                  <button type="button" onClick={() => setType(t)} className={`w-100 text-start p-3 b-r-12 border ${type === t ? 'border-primary bg-light-primary' : 'bg-white'}`} style={{ borderWidth: type === t ? 1.5 : 1 }}>
                    <div className="d-flex align-items-center gap-2 f-w-600 text-dark"><i className={`ti ${TYPES[t].icon} f-s-18 ${type === t ? 'text-primary' : 'text-secondary'}`}></i>{TYPES[t].label}</div>
                    <div className="f-s-12 text-secondary mt-1">{TYPES[t].hint}</div>
                  </button>
                </div>
              ))}
            </div>
          </Section>

          <Section title="Asosiy">
            <div className="row g-3">
              <div className="col-md-6"><label className="form-label f-s-13">Nomi (uz)</label><input name="title_uz" defaultValue={prize?.title_uz ?? ''} required className="form-control" placeholder="Masalan: 15% chegirma" />{err('title_uz')}</div>
              <div className="col-md-6"><label className="form-label f-s-13">Nomi (ru)</label><input name="title_ru" defaultValue={prize?.title_ru ?? ''} className="form-control" /></div>
              <div className="col-md-8">
                <label className="form-label f-s-13">Rasm {type === 'fragments' ? <span className="text-muted">(bo'sh bo'lsa kitob muqovasi)</span> : null}</label>
                <div className="d-flex align-items-center gap-2">
                  <span className="h-45 w-45 b-r-10 overflow-hidden bg-light-secondary d-flex-center flex-shrink-0">
                    {preview && !removeImage ? <img src={preview} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} /> : <i className="ti ti-photo text-secondary"></i>}
                  </span>
                  <input name="image" type="file" accept="image/*" className="form-control" onChange={(e) => { const file = e.target.files?.[0]; if (file) { setPreview(URL.createObjectURL(file)); setRemoveImage(false); } }} />
                  {preview && !removeImage ? <button type="button" className="btn btn-light-danger icon-btn w-35 h-35 b-r-22 flex-shrink-0" onClick={() => setRemoveImage(true)}><i className="ti ti-trash"></i></button> : null}
                </div>
                {err('image')}
              </div>
              <NumField name="position" label="Tartib" value={prize?.position ?? 0} col="col-md-4" />
            </div>
          </Section>

          {type === 'fragments' ? (
            <Section title="Kitob bo'laklari">
              <div className="row g-3">
                <div className="col-md-8"><label className="form-label f-s-13">Kitob (global katalog)</label><Picker type="edition" value={edition} onChange={setEdition} />{err('edition_id')}</div>
                <NumField name="fragments_total" label="Bo'laklar soni" value={prize?.fragments_total ?? 4} col="col-md-4" min={2} />
                <div className="col-12"><Hint>Mijoz har aylantirishda bitta yangi bo'lak oladi. Hamma bo'lak yig'ilganda unga shu kitobga <b>1 dona bepul</b> shaxsiy promokod yaratiladi.</Hint></div>
              </div>
            </Section>
          ) : null}

          {type === 'coins' ? (
            <Section title="Tangalar">
              <div className="row g-3"><NumField name="coins_amount" label="Miqdori" suffix="tanga" value={prize?.coins_amount ?? 20} col="col-md-6" min={1} />{err('coins_amount')}</div>
            </Section>
          ) : null}

          {type === 'promocode' ? (
            <Section title="Promokod">
              <div className="row g-3">
                <div className="col-md-4">
                  <label className="form-label f-s-13">Turi</label>
                  <select className="form-select" value={discountType} onChange={(e) => setDiscountType(e.target.value as 'percent' | 'fixed')}><option value="percent">Foiz</option><option value="fixed">Summa</option></select>
                </div>
                <NumField name="discount_value" label="Chegirma" suffix={discountType === 'percent' ? '%' : "so'm"} value={prize?.discount_value ?? 10} col="col-md-4" min={1} />
                <NumField name="valid_days" label="Amal qilish muddati" suffix="kun" value={prize?.valid_days ?? 30} col="col-md-4" min={1} />
                {discountType === 'percent' ? <NumField name="max_discount" label="Maks. chegirma" suffix="so'm" value={prize?.max_discount} col="col-md-6" placeholder="Cheklovsiz" /> : null}
                <NumField name="min_order_amount" label="Min. buyurtma" suffix="so'm" value={prize?.min_order_amount} col="col-md-6" placeholder="Cheklovsiz" />
                <div className="col-12">
                  <label className="form-label f-s-13">Qamrovi</label>
                  <div className="btn-group w-100">
                    {([['all', 'Barcha mahsulotlar'], ['edition', 'Bitta kitob'], ['seller', "Bitta do'kon"]] as const).map(([k, l]) => (
                      <button type="button" key={k} className={`btn ${scope === k ? 'btn-primary' : 'btn-light-secondary'}`} onClick={() => { setScope(k); setScopeItem(null); }}>{l}</button>
                    ))}
                  </div>
                </div>
                {scope !== 'all' ? <div className="col-12"><Picker type={scope} value={scopeItem} onChange={setScopeItem} />{err('scope_id')}</div> : null}
                {err('discount_value')}
                <div className="col-12"><Hint>Har bir g'olibga alohida kod yaratiladi: faqat o'zi, 1 marta ishlata oladi.</Hint></div>
              </div>
            </Section>
          ) : null}

          <Section title="Tushish foizi">
            <div className="d-flex flex-wrap gap-2 mb-2">
              {(Object.keys(DIFF) as Difficulty[]).map((d) => (
                <button type="button" key={d} onClick={() => pickDifficulty(d)} className={`btn btn-sm ${difficulty === d ? `btn-${DIFF[d].tone}` : `btn-light-${DIFF[d].tone}`}`}>{DIFF[d].label} · {presets[d]}%</button>
              ))}
            </div>
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label f-s-13">Har bir aylantirishda</label>
                <div className="input-group"><input type="number" step="0.001" min={0} max={100} value={chance} onChange={(e) => setChance(e.target.value)} className="form-control" /><span className="input-group-text">%</span></div>
                {err('chance')}
              </div>
              <div className="col-md-6 d-flex align-items-end"><div className="f-s-13 text-secondary">≈ har {Number(chance) > 0 ? fmt(Math.round(100 / Number(chance))) : '∞'} aylantirishdan biri</div></div>
            </div>
          </Section>

          <Section title="Yutish shartlari" last>
            <div className="row g-3">
              <NumField name="min_orders" label="Kamida yetkazilgan buyurtma" value={prize?.min_orders || ''} placeholder="0" col="col-md-4" />
              <NumField name="min_spins" label="Kamida aylantirgan bo'lsin" value={prize?.min_spins || ''} placeholder="0" col="col-md-4" />
              <NumField name="max_wins_per_user" label="1 kishi necha marta yutadi" value={prize?.max_wins_per_user} placeholder="Cheklovsiz" col="col-md-4" min={1} />
              <NumField name="daily_limit" label="Kuniga jami" value={prize?.daily_limit} placeholder="Cheklovsiz" col="col-md-4" min={1} />
              <NumField name="stock" label="Jami zaxira" value={prize?.stock} placeholder="Cheklovsiz" col="col-md-4" min={1} />
              <div className="col-md-4" />
              <div className="col-md-6"><label className="form-label f-s-13">Boshlanishi</label><input name="starts_at" type="datetime-local" defaultValue={prize?.starts_at ?? ''} className="form-control" /></div>
              <div className="col-md-6"><label className="form-label f-s-13">Tugashi</label><input name="ends_at" type="datetime-local" defaultValue={prize?.ends_at ?? ''} className="form-control" />{err('ends_at')}</div>
              <div className="col-12"><Hint>Shart bajarilmagan mijozga bu sovg'a tushmaydi — o'rniga "omad keyingi safar" chiqadi.</Hint></div>
            </div>
          </Section>
        </Modal.Body>
        <Modal.Footer>
          <button type="button" className="btn btn-light-secondary" onClick={onHide}>Bekor</button>
          <button type="submit" className="btn btn-primary" disabled={busy}>{busy ? 'Saqlanmoqda…' : isEdit ? 'Saqlash' : "Qo'shish"}</button>
        </Modal.Footer>
      </form>
    </Modal>
  );
}

function Section({ title, children, last }: { title: string; children: ReactNode; last?: boolean }) {
  return (
    <div className={last ? '' : 'mb-4'}>
      <p className="f-w-600 text-dark mb-2">{title}</p>
      {children}
    </div>
  );
}

function Hint({ children }: { children: ReactNode }) {
  return <div className="f-s-13 text-secondary b-r-10 p-2 px-3 bg-light-secondary">{children}</div>;
}

interface Picked { id: number; title: string; sub?: string | null; image?: string | null }

function Picker({ type, value, onChange }: { type: 'edition' | 'seller'; value: Picked | null; onChange: (v: Picked | null) => void }) {
  const [q, setQ] = useState('');
  const [rows, setRows] = useState<Picked[]>([]);
  const [loading, setLoading] = useState(false);
  const timer = useRef<number | undefined>(undefined);

  useEffect(() => {
    window.clearTimeout(timer.current);
    if (q.trim().length < 2) { setRows([]); return; }
    timer.current = window.setTimeout(async () => {
      setLoading(true);
      try {
        const r = await fetch(`${BASE}/lookup?type=${type}&q=${encodeURIComponent(q.trim())}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (r.ok) setRows((await r.json()).data || []);
      } finally {
        setLoading(false);
      }
    }, 250);
    return () => window.clearTimeout(timer.current);
  }, [q, type]);

  const placeholder = useMemo(() => (type === 'seller' ? "Do'kon nomi yoki ID" : 'Kitob nomi, muallif, ISBN yoki ID'), [type]);

  if (value) {
    return (
      <div className="d-flex align-items-center gap-2 p-2 b-r-10 border bg-white">
        {value.image ? <img src={value.image} alt="" className="b-r-6" style={{ width: 28, height: 40, objectFit: 'cover' }} /> : <i className={`ti ${type === 'seller' ? 'ti-building-store' : 'ti-book'} text-primary f-s-18 ms-1`}></i>}
        <div className="flex-grow-1 min-w-0"><div className="f-w-600 txt-ellipsis-1">{value.title}</div>{value.sub ? <div className="f-s-12 text-secondary">{value.sub}</div> : null}</div>
        <button type="button" className="btn btn-light-secondary btn-sm" onClick={() => onChange(null)}>O'zgartirish</button>
      </div>
    );
  }

  return (
    <div className="position-relative">
      <div className="input-group"><span className="input-group-text"><i className="ti ti-search"></i></span><input value={q} onChange={(e) => setQ(e.target.value)} placeholder={placeholder} className="form-control" /></div>
      {q.trim().length >= 2 ? (
        <div className="border b-r-10 bg-white mt-1 app-scroll" style={{ maxHeight: 240, overflowY: 'auto' }}>
          {loading && !rows.length ? <div className="p-2 f-s-13 text-secondary">Qidirilmoqda…</div> : null}
          {!loading && !rows.length ? <div className="p-2 f-s-13 text-secondary">Topilmadi</div> : null}
          {rows.map((r) => (
            <button type="button" key={r.id} className="d-flex align-items-center gap-2 w-100 text-start p-2 border-0 bg-transparent border-bottom" onClick={() => { onChange(r); setQ(''); }}>
              {r.image ? <img src={r.image} alt="" className="b-r-6" style={{ width: 24, height: 34, objectFit: 'cover' }} /> : <i className={`ti ${type === 'seller' ? 'ti-building-store' : 'ti-book'} text-secondary`}></i>}
              <div className="min-w-0"><div className="f-w-500 txt-ellipsis-1">{r.title}</div>{r.sub ? <div className="f-s-12 text-secondary">{r.sub}</div> : null}</div>
              <span className="ms-auto f-s-12 text-muted">#{r.id}</span>
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
