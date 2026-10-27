import { useMemo, useState, type DragEvent, type FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import Modal from '../components/AppModal';
import { Avatar } from '../components/Profile';
import { apiJson } from '../utils/realtime';

/*
 * Jamoa → Vazifalar. Telegram jamoa boti bilan bir xil yozuvlar: guruhda
 * "✅ Bajarildi" bosilsa shu yerda ham bajarilgan bo'ladi va aksincha.
 */

type Status = 'todo' | 'doing' | 'done' | 'cancelled';
type Priority = 'low' | 'normal' | 'high';
interface Task {
  id: number;
  title: string;
  description: string | null;
  status: Status;
  priority: Priority;
  dueAt: string | null;
  dueLabel: string | null;
  overdue: boolean;
  assignee: { id: number; name: string } | null;
  creator: string | null;
  orderId: number | null;
  orderUrl: string | null;
  source: 'panel' | 'telegram';
  updatedAt: string | null;
  updateUrl: string;
}
interface Props {
  tasks: Task[];
  admins: Array<{ id: number; name: string; telegram: boolean }>;
  scope: 'all' | 'mine';
  me: { id: number; readOnly: boolean; telegramLinked: boolean; telegramUsername: string | null };
  bot: { configured: boolean; groupConnected: boolean; username: string | null };
  urls: { store: string; link: string; unlink: string };
  [key: string]: unknown;
}

const COLUMNS: Array<{ key: Status; label: string; hint: string }> = [
  { key: 'todo', label: 'Bajarilishi kerak', hint: 'Yangi va hali boshlanmagan' },
  { key: 'doing', label: 'Jarayonda', hint: 'Kimdir shug‘ullanyapti' },
  { key: 'done', label: 'Bajarildi', hint: 'Oxirgi 14 kun' },
];
const PRIORITY: Record<Priority, { label: string; tone: string }> = {
  high: { label: 'Shoshilinch', tone: 'danger' },
  normal: { label: 'O‘rta', tone: 'warning' },
  low: { label: 'Past', tone: 'secondary' },
};

export default function Tasks() {
  const { tasks = [], admins = [], scope, me, bot, urls } = usePage<Props>().props;
  const [editing, setEditing] = useState<Partial<Task> | null>(null);
  const [dragId, setDragId] = useState<number | null>(null);
  const [overCol, setOverCol] = useState<Status | null>(null);
  const [linking, setLinking] = useState(false);
  const [linkError, setLinkError] = useState<string | null>(null);
  const [linkUrl, setLinkUrl] = useState<string | null>(null);
  const [q, setQ] = useState('');

  const filtered = useMemo(() => {
    const term = q.trim().toLowerCase();
    return term ? tasks.filter((t) => `${t.title} ${t.description || ''} ${t.assignee?.name || ''} ${t.orderId || ''}`.toLowerCase().includes(term)) : tasks;
  }, [tasks, q]);
  const byStatus = (s: Status) => filtered.filter((t) => t.status === s);
  const openCount = tasks.filter((t) => t.status === 'todo' || t.status === 'doing').length;
  const overdueCount = tasks.filter((t) => t.overdue).length;

  const move = (task: Task, status: Status) => {
    if (me.readOnly || task.status === status) return;
    router.patch(task.updateUrl, { status }, { preserveScroll: true, preserveState: true });
  };

  const onDrop = (event: DragEvent, status: Status) => {
    event.preventDefault();
    setOverCol(null);
    const task = tasks.find((t) => t.id === dragId);
    setDragId(null);
    if (task) move(task, status);
  };

  const linkTelegram = async () => {
    setLinking(true); setLinkError(null);
    try {
      const res = await apiJson<{ url: string }>(urls.link, { method: 'POST', body: {} });
      // Safari await'dan keyingi window.open'ni bloklaydi — shuning uchun havola tugma sifatida ham chiqadi
      setLinkUrl(res.url);
      window.open(res.url, '_blank', 'noopener');
    } catch (e) {
      setLinkError(e instanceof Error ? e.message : 'Havolani olib bo‘lmadi');
    } finally {
      setLinking(false);
    }
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Vazifalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Jamoa vazifalari — Telegram guruhidagi bot bilan bir xil: qayerda o‘zgarsa, ikkinchisida ham yangilanadi</p>
        </div>
        {!me.readOnly ? (
          <button type="button" className="btn btn-primary" onClick={() => setEditing({ priority: 'normal', assignee: null })}><i className="ti ti-plus"></i>Yangi vazifa</button>
        ) : null}
      </div>

      {/* Telegram holati */}
      <div className="card kb-tg-card">
        <div className="card-body d-flex align-items-center gap-3 flex-wrap">
          <span className="kb-tg-ico"><i className="ti ti-brand-telegram"></i></span>
          <div className="flex-grow-1 min-w-0">
            {!bot.configured ? (
              <>
                <div className="f-w-600">Jamoa boti hali sozlanmagan</div>
                <div className="f-s-13 text-secondary">Serverda <code>TEAM_BOT_TOKEN</code> va <code>TEAM_BOT_WEBHOOK_SECRET</code> qo‘shilgach, <code>php artisan team-bot:webhook</code> ishga tushiriladi. Keyin botni guruhga admin qilib qo‘shib, guruhda <code>/setup</code> yoziladi.</div>
              </>
            ) : (
              <>
                <div className="f-w-600 d-flex align-items-center gap-2 flex-wrap">
                  {bot.username ? <a href={`https://t.me/${bot.username}`} target="_blank" rel="noreferrer">@{bot.username}</a> : 'Jamoa boti'}
                  <span className={`badge ${bot.groupConnected ? 'text-light-success' : 'text-light-warning'}`}>{bot.groupConnected ? 'Guruh ulangan' : 'Guruh ulanmagan — guruhda /setup yozing'}</span>
                </div>
                <div className="f-s-13 text-secondary">
                  {me.telegramLinked
                    ? <>Sizning akkauntingiz ulangan{me.telegramUsername ? <> — <b>@{me.telegramUsername}</b></> : null}. Guruhdagi tugmalar siz nomingizdan ishlaydi.</>
                    : 'Guruhdagi tugmalarni (“Men olaman”, “Qadoqlashga”, “Bajarildi”) bosish uchun Telegram akkauntingizni ulang.'}
                </div>
                {linkError ? <div className="f-s-12 text-danger mt-1">{linkError}</div> : null}
                {linkUrl && !me.telegramLinked ? <div className="f-s-12 text-secondary mt-1">Botda «Start»ni bosing — havola 15 daqiqa amal qiladi. Ulangach sahifani yangilang.</div> : null}
              </>
            )}
          </div>
          {bot.configured ? (
            me.telegramLinked ? (
              <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => confirm('Telegram akkauntini uzasizmi?') && router.post(urls.unlink, {}, { preserveScroll: true })}>Uzish</button>
            ) : linkUrl ? (
              <a className="btn btn-sm btn-primary" href={linkUrl} target="_blank" rel="noreferrer"><i className="ti ti-brand-telegram"></i>Telegram’da ochish</a>
            ) : (
              <button type="button" className="btn btn-sm btn-primary" disabled={linking} onClick={linkTelegram}><i className="ti ti-link"></i>{linking ? 'Tayyorlanmoqda…' : 'Telegram’ni ulash'}</button>
            )
          ) : null}
        </div>
      </div>

      <div className="d-flex align-items-center gap-2 flex-wrap mb-3">
        <div className="nav kc-segment">
          {(['all', 'mine'] as const).map((s) => (
            <div className="nav-item" key={s}>
              <button type="button" className={`nav-link ${scope === s ? 'active' : ''}`} onClick={() => router.get('/boshqaruv/tasks', s === 'mine' ? { scope: 'mine' } : {}, { preserveScroll: true })}>
                {s === 'all' ? 'Hammasi' : 'Menga biriktirilgan'}
              </button>
            </div>
          ))}
        </div>
        <div className="app-form app-icon-form position-relative" style={{ width: 'min(300px, 100%)' }}>
          <i className="ti ti-search"></i>
          <input className="form-control form-control-sm" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Vazifa, xodim yoki buyurtma raqami" aria-label="Vazifa qidirish" />
        </div>
        <span className="ms-auto f-s-13 text-secondary">{openCount} ta ochiq{overdueCount ? <> · <span className="text-danger f-w-600">{overdueCount} ta muddati o‘tgan</span></> : null}</span>
      </div>

      <div className="kb-board">
        {COLUMNS.map((col) => {
          const items = byStatus(col.key);
          return (
            <section
              key={col.key}
              className={`kb-col ${overCol === col.key ? 'is-over' : ''}`}
              onDragOver={(e) => { if (dragId !== null) { e.preventDefault(); setOverCol(col.key); } }}
              onDragLeave={() => setOverCol((c) => (c === col.key ? null : c))}
              onDrop={(e) => onDrop(e, col.key)}
              aria-label={col.label}
            >
              <header className="kb-col-head">
                <span className={`kb-col-dot is-${col.key}`}></span>
                <b>{col.label}</b>
                <span className="kb-tab-count">{items.length}</span>
              </header>
              <div className="kb-col-body">
                {items.map((t) => (
                  <article
                    key={t.id}
                    className={`kb-task ${t.overdue ? 'is-overdue' : ''} ${dragId === t.id ? 'is-drag' : ''}`}
                    draggable={!me.readOnly}
                    onDragStart={() => setDragId(t.id)}
                    onDragEnd={() => { setDragId(null); setOverCol(null); }}
                    onClick={() => setEditing(t)}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => { if (e.key === 'Enter') setEditing(t); }}
                  >
                    <div className="d-flex align-items-start gap-2">
                      <span className={`kb-prio is-${t.priority}`} title={PRIORITY[t.priority].label}></span>
                      <div className={`kb-task-title ${t.status === 'done' ? 'is-done' : ''}`}>{t.title}</div>
                    </div>
                    {t.description ? <div className="kb-task-desc">{t.description}</div> : null}
                    <div className="kb-task-meta">
                      <span className="text-secondary">#{t.id}</span>
                      {t.orderId ? <a href={t.orderUrl || '#'} onClick={(e) => e.stopPropagation()} className="kb-chip-link"><i className="ti ti-receipt"></i>{t.orderId}</a> : null}
                      {t.dueLabel ? <span className={t.overdue ? 'text-danger f-w-600' : 'text-secondary'}><i className="ti ti-clock"></i> {t.dueLabel}</span> : null}
                      {t.source === 'telegram' ? <i className="ti ti-brand-telegram text-secondary" title="Telegram'da yaratilgan"></i> : null}
                      <span className="ms-auto d-flex align-items-center gap-1">
                        {t.assignee ? <><span className="kb-assignee-name">{t.assignee.name.split(' ')[0]}</span><Avatar name={t.assignee.name} size="xs" /></> : <span className="kb-unassigned" title="Mas'ul yo'q"><i className="ti ti-user-question"></i></span>}
                      </span>
                    </div>
                    {!me.readOnly ? (
                      <div className="kb-task-actions" onClick={(e) => e.stopPropagation()}>
                        {t.status === 'todo' ? <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => move(t, 'doing')}>Boshlash</button> : null}
                        {t.status === 'todo' || t.status === 'doing' ? <button type="button" className="btn btn-sm btn-light-success" onClick={() => move(t, 'done')}><i className="ti ti-check"></i>Bajarildi</button> : null}
                        {t.status === 'done' ? <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => move(t, 'todo')}>Qayta ochish</button> : null}
                      </div>
                    ) : null}
                  </article>
                ))}
                {items.length === 0 ? <div className="kb-col-empty">{col.key === 'done' ? 'Hali bajarilgan vazifa yo‘q' : 'Bo‘sh — zo‘r!'}</div> : null}
              </div>
            </section>
          );
        })}
      </div>

      <TaskModal task={editing} admins={admins} readOnly={me.readOnly} storeUrl={urls.store} onHide={() => setEditing(null)} />
    </div>
  );
}

function TaskModal({ task, admins, readOnly, storeUrl, onHide }: { task: Partial<Task> | null; admins: Props['admins']; readOnly: boolean; storeUrl: string; onHide: () => void }) {
  const isEdit = !!task?.id;
  const [busy, setBusy] = useState(false);
  const errors = (usePage().props as { errors?: Record<string, string> }).errors || {};
  const err = (k: string) => (errors[k] ? <div className="invalid-feedback d-block">{errors[k]}</div> : null);
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (readOnly) return;
    const fd = new FormData(event.currentTarget);
    const data: Record<string, string | null> = {};
    fd.forEach((v, k) => { data[k] = String(v) === '' ? null : String(v); });
    setBusy(true);
    const opts = { preserveScroll: true, preserveState: true, onSuccess: onHide, onFinish: () => setBusy(false) };
    if (isEdit && task?.updateUrl) router.patch(task.updateUrl, data, opts);
    else router.post(storeUrl, data, opts);
  };

  return (
    <Modal show={!!task} onHide={onHide} centered>
      <form onSubmit={submit} key={task?.id ?? 'new'}>
        <Modal.Header closeButton><Modal.Title>{isEdit ? `Vazifa #${task?.id}` : 'Yangi vazifa'}</Modal.Title></Modal.Header>
        <Modal.Body>
          <fieldset disabled={readOnly} className="row g-3">
            <div className="col-12">
              <label className="form-label">Nima qilish kerak</label>
              <input name="title" className="form-control" defaultValue={task?.title || ''} required maxLength={250} placeholder="Masalan: #48210 mijozga qo‘ng‘iroq qilish" autoFocus />
              {err('title')}
            </div>
            <div className="col-12">
              <label className="form-label">Izoh <span className="text-secondary f-w-400">(ixtiyoriy)</span></label>
              <textarea name="description" className="form-control" rows={3} defaultValue={task?.description || ''} maxLength={4000} />
            </div>
            <div className="col-md-6">
              <label className="form-label">Mas’ul</label>
              <select name="assignee_admin_id" className="form-select" defaultValue={task?.assignee?.id ?? ''}>
                <option value="">— Hali hech kim —</option>
                {admins.map((a) => <option value={a.id} key={a.id}>{a.name}{a.telegram ? '' : ' (Telegram ulanmagan)'}</option>)}
              </select>
            </div>
            <div className="col-md-6">
              <label className="form-label">Muddat</label>
              <input name="due_at" type="datetime-local" className="form-control" defaultValue={task?.dueAt || ''} />
              {err('due_at')}
            </div>
            <div className="col-md-6">
              <label className="form-label">Muhimligi</label>
              <select name="priority" className="form-select" defaultValue={task?.priority || 'normal'}>
                {(Object.keys(PRIORITY) as Priority[]).map((p) => <option key={p} value={p}>{PRIORITY[p].label}</option>)}
              </select>
            </div>
            <div className="col-md-6">
              <label className="form-label">Buyurtma raqami <span className="text-secondary f-w-400">(ixtiyoriy)</span></label>
              <input name="order_id" type="number" min={1} className="form-control" defaultValue={task?.orderId ?? ''} placeholder="48210" />
              {err('order_id')}
            </div>
            {isEdit ? (
              <div className="col-12">
                <label className="form-label">Holat</label>
                <select name="status" className="form-select" defaultValue={task?.status}>
                  <option value="todo">Bajarilishi kerak</option>
                  <option value="doing">Jarayonda</option>
                  <option value="done">Bajarildi</option>
                  <option value="cancelled">Bekor qilindi</option>
                </select>
                <div className="form-text">{task?.creator ? `Yaratgan: ${task.creator}` : ''}{task?.updatedAt ? ` · o‘zgargan: ${task.updatedAt}` : ''}</div>
              </div>
            ) : (
              <div className="col-12"><div className="form-text"><i className="ti ti-brand-telegram"></i> Vazifa jamoa guruhining «Vazifalar» bo‘limiga ham yuboriladi, mas’ul belgilansa — u eslatiladi.</div></div>
            )}
          </fieldset>
        </Modal.Body>
        <Modal.Footer>
          <button type="button" className="btn btn-light-secondary" onClick={onHide}>Bekor</button>
          {!readOnly ? <button type="submit" className="btn btn-primary" disabled={busy}>{isEdit ? 'Saqlash' : 'Yaratish'}</button> : null}
        </Modal.Footer>
      </form>
    </Modal>
  );
}
