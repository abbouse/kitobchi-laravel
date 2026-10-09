import { Head, Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Dropdown } from 'react-bootstrap';
import Modal from '../components/AppModal';
import { apiJson, getEcho, RealtimeConfig, RealtimeState, watchState } from '../utils/realtime';

/*
 * Support inbox — Telegram Web uslubidagi operator ish joyi.
 *   chap: Mijozlar | Do'konlar ro'yxati (qidiruv, filtr, o'qilmaganlar)
 *   o'rta: suhbat (javob / ichki eslatma, shablonlar "/" bilan)
 *   o'ng: mijoz/do'kon ma'lumotlari, buyurtmalar, baholar, murojaatlar tarixi
 * Hammasi real-vaqtda (Reverb), ulanish bo'lmasa — avtomatik so'rov (polling).
 */

type Segment = 'customer' | 'shop';
type Filter = 'open' | 'unassigned' | 'mine' | 'all' | 'closed';

type Thread = {
  key: string;
  segment: Segment;
  source: 'telegram' | 'app' | 'shop';
  ticket_id: number;
  name: string;
  subtitle: string;
  avatar: string | null;
  last_message: string;
  last_from: 'customer' | 'agent' | 'system' | 'note';
  last_message_at: string | null;
  unread: number;
  status: 'open' | 'closed';
  raw_status: string;
  waiting: boolean;
  assignee: { id: number; name: string } | null;
  legacy_operator: string | null;
  feedback: 'good' | 'bad' | null;
  peer_read?: boolean;
};

type Attachment = { id: number; type: string; name: string | null; size: number | null; url: string };

type Message = {
  id: string;
  raw_id: number;
  ticket_id: number;
  from: 'customer' | 'agent' | 'system' | 'note';
  type: string;
  text: string;
  author: string | null;
  at: string | null;
  delivered: boolean;
  error: string | null;
  attachments: Attachment[];
  pending?: boolean;
};

type TicketRow = { id: number; status: 'open' | 'closed'; title: string; created_at: string | null; closed_at: string | null; feedback: 'good' | 'bad' | null; agent: string | null };

type Context = {
  kind: Segment;
  profile: Record<string, string | number | null | undefined>;
  stats: { orders_count: number; total_spent: number; cashback: number; registered: string | null } | null;
  orders: Array<{ id: number; amount: number; status: string; date: string | null; delivery_date: string | null; url: string }>;
  feedback: { good: number; bad: number; items: Array<{ ticket_id: number; value: 'good' | 'bad'; at: string | null; agent: string | null }> };
  tickets: TicketRow[];
};

type Template = { id: number; title: string; body: string; audience: 'all' | 'customer' | 'shop'; category: string | null; shortcut: string | null; usage: number };

type ListPayload = {
  items: Thread[];
  next: string | null;
  counts: Record<Filter, number>;
  segments: Record<Segment, { open: number; unread: number }>;
};

type InboxProps = {
  me: { id: number; name: string; readOnly: boolean };
  agents: Array<{ id: number; name: string }>;
  templates: Template[];
  realtime: RealtimeConfig | null;
  initial: { segment: Segment; filter: Filter; key: string; list: ListPayload };
  urls: Record<'threads' | 'thread' | 'older' | 'reply' | 'assign' | 'close' | 'read' | 'templates' | 'kpi' | 'broadcastAuth', string>;
};

type ThreadData = { thread: Thread; messages: Message[]; has_more: boolean; context: Context };

const FILTERS: Array<{ key: Filter; label: string }> = [
  { key: 'open', label: 'Ochiq' },
  { key: 'unassigned', label: 'Navbatda' },
  { key: 'mine', label: 'Meniki' },
  { key: 'all', label: 'Hammasi' },
  { key: 'closed', label: 'Yopilgan' },
];

const TONES = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

// ─── Yordamchilar ───────────────────────────────────────────────────────────

function toneOf(seed: string) {
  let h = 0;
  for (let i = 0; i < seed.length; i += 1) h = (h * 31 + seed.charCodeAt(i)) | 0;
  return TONES[Math.abs(h) % TONES.length];
}

function initials(name: string) {
  const parts = name.replace(/[@#]/g, '').trim().split(/\s+/).filter(Boolean);
  return ((parts[0]?.[0] || '?') + (parts[1]?.[0] || '')).toUpperCase();
}

function sameDay(a: Date, b: Date) {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

const MONTHS = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];

function hhmm(d: Date) {
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function listTime(iso: string | null) {
  if (!iso) return '';
  const d = new Date(iso);
  const now = new Date();
  if (sameDay(d, now)) return hhmm(d);
  const y = new Date(now); y.setDate(now.getDate() - 1);
  if (sameDay(d, y)) return 'kecha';
  if (now.getTime() - d.getTime() < 6 * 86400000) return ['Yak', 'Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan'][d.getDay()];
  return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}`;
}

function dayLabel(iso: string | null) {
  if (!iso) return '';
  const d = new Date(iso);
  const now = new Date();
  if (sameDay(d, now)) return 'Bugun';
  const y = new Date(now); y.setDate(now.getDate() - 1);
  if (sameDay(d, y)) return 'Kecha';
  return `${d.getDate()}-${MONTHS[d.getMonth()]}${d.getFullYear() !== now.getFullYear() ? ` ${d.getFullYear()}` : ''}`;
}

function waitLabel(iso: string | null) {
  if (!iso) return '';
  const min = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
  if (min < 1) return 'hozir';
  if (min < 60) return `${min} daq`;
  const h = Math.floor(min / 60);
  if (h < 24) return `${h} soat`;
  return `${Math.floor(h / 24)} kun`;
}

function money(n: number) {
  return `${Math.round(n || 0).toLocaleString('ru-RU').replace(/,/g, ' ')} so‘m`;
}

function isPlaceholder(text: string) {
  return /^\[[^\]]*\]\s*\S*$/.test(text.trim());
}

function fillTemplate(body: string, thread: Thread | null, me: InboxProps['me']) {
  const first = (thread?.name || '').replace(/^@/, '').split(/\s+/)[0] || '';
  return body.replace(/\{name\}/g, first).replace(/\{agent\}/g, me.name.split(/\s+/)[0] || me.name);
}

function playPing() {
  try {
    const Ctx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
    const ctx = new Ctx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12);
    gain.gain.setValueAtTime(0.0001, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
    osc.connect(gain).connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.4);
    setTimeout(() => ctx.close().catch(() => undefined), 600);
  } catch {
    // ovoz o'chirilgan bo'lishi mumkin
  }
}

function readPref(key: string, fallback: string) {
  try { return localStorage.getItem(key) ?? fallback; } catch { return fallback; }
}
function writePref(key: string, value: string) {
  try { localStorage.setItem(key, value); } catch { /* yo'q */ }
}

// ─── Kichik komponentlar ────────────────────────────────────────────────────

function Avatar({ name, src, size = 44, icon }: { name: string; src?: string | null; size?: number; icon?: string }) {
  const [broken, setBroken] = useState(false);
  const tone = toneOf(name);
  if (src && !broken) {
    return <img src={src} alt="" onError={() => setBroken(true)} className="b-r-50 flex-shrink-0" style={{ width: size, height: size, objectFit: 'cover' }} />;
  }
  return (
    <span className={`b-r-50 d-flex-center flex-shrink-0 f-w-600 text-light-${tone}`} style={{ width: size, height: size, fontSize: size * 0.36 }}>
      {icon ? <i className={icon}></i> : initials(name)}
    </span>
  );
}

function SourceIcon({ source }: { source: Thread['source'] }) {
  const map = { telegram: ['ti ti-brand-telegram', 'Telegram bot'], app: ['ti ti-device-mobile', 'Ilova chati'], shop: ['ti ti-building-store', 'Do‘kon'] } as const;
  const [icon, label] = map[source];
  return <i className={`${icon} f-s-14 text-secondary`} title={label}></i>;
}

function ConnectionDot({ state }: { state: RealtimeState }) {
  const map: Record<RealtimeState, [string, string]> = {
    connected: ['success', 'Jonli ulanish'],
    connecting: ['warning', 'Ulanmoqda…'],
    unavailable: ['danger', 'Ulanish yo‘q — har 8 soniyada yangilanadi'],
    offline: ['secondary', 'Avto-yangilanish (har 8 soniyada)'],
  };
  const [tone, label] = map[state];
  return (
    <span className={`badge text-light-${tone} d-inline-flex align-items-center gap-1`} title={label}>
      <span className={`d-inline-block b-r-50 bg-${tone}`} style={{ width: 7, height: 7 }}></span>
      {state === 'connected' ? 'Jonli' : state === 'connecting' ? 'Ulanmoqda' : 'Avto'}
    </span>
  );
}

function AttachmentView({ a }: { a: Attachment }) {
  if (a.type === 'photo' || a.type === 'sticker') {
    return (
      <a href={a.url} target="_blank" rel="noreferrer" className="d-block mb-1">
        <img src={a.url} alt={a.name || 'rasm'} loading="lazy" className="b-r-10 d-block" style={{ maxWidth: '100%', maxHeight: 280, objectFit: 'cover' }} />
      </a>
    );
  }
  if (a.type === 'voice' || a.type === 'audio') {
    return <audio controls preload="none" src={a.url} className="d-block mb-1" style={{ width: 260, maxWidth: '100%' }} />;
  }
  if (a.type === 'video' || a.type === 'video_note') {
    return <video controls preload="none" src={a.url} className={`d-block mb-1 ${a.type === 'video_note' ? 'b-r-50' : 'b-r-10'}`} style={{ maxWidth: a.type === 'video_note' ? 220 : '100%', maxHeight: 280 }} />;
  }
  return (
    <a href={a.url} target="_blank" rel="noreferrer" className="d-flex align-items-center gap-2 mb-1 text-reset">
      <span className="h-35 w-35 d-flex-center b-r-10 text-light-primary flex-shrink-0"><i className="ti ti-file-text"></i></span>
      <span className="text-truncate f-w-500">{a.name || 'Hujjat'}</span>
    </a>
  );
}

function Bubble({ m, showAuthor }: { m: Message; showAuthor: boolean }) {
  if (m.from === 'system') {
    return (
      <div className="text-center my-2">
        <span className="badge text-light-secondary f-w-500 f-s-12 text-wrap" style={{ maxWidth: '80%' }}>{m.text}</span>
      </div>
    );
  }

  const mine = m.from === 'agent' || m.from === 'note';
  const isNote = m.from === 'note';
  const hideText = m.attachments.length > 0 && isPlaceholder(m.text);

  return (
    <div className={`d-flex mb-1 ${mine ? 'justify-content-end' : 'justify-content-start'}`}>
      <div className={`kc-bubble ${mine ? 'kc-bubble-out' : 'kc-bubble-in'} ${isNote ? 'kc-bubble-note' : ''}`}>
        {isNote ? <div className="f-s-11 f-w-600 text-warning-dark mb-1"><i className="ti ti-lock me-1"></i>Ichki eslatma{m.author ? ` · ${m.author}` : ''}</div> : null}
        {!isNote && mine && showAuthor && m.author ? <div className="f-s-11 f-w-600 text-primary mb-1">{m.author}</div> : null}
        {m.attachments.map((a) => <AttachmentView key={a.id} a={a} />)}
        {!hideText && m.text ? <div className="kc-bubble-text">{m.text}</div> : null}
        <div className="kc-bubble-meta">
          {m.at ? hhmm(new Date(m.at)) : ''}
          {mine && !isNote ? (
            m.pending ? <i className="ti ti-clock ms-1"></i>
              : m.error ? <i className="ti ti-alert-circle ms-1 text-danger" title={m.error}></i>
                : <i className="ti ti-checks ms-1"></i>
          ) : null}
        </div>
      </div>
    </div>
  );
}

// ─── Asosiy sahifa ──────────────────────────────────────────────────────────

export default function SupportInbox() {
  const { inbox } = usePage<{ inbox: InboxProps }>().props;
  const { me, agents, urls, realtime } = inbox;

  const [segment, setSegment] = useState<Segment>(inbox.initial.segment);
  const [filter, setFilter] = useState<Filter>(inbox.initial.filter);
  const [search, setSearch] = useState('');
  const [list, setList] = useState<ListPayload>(inbox.initial.list);
  const [listLoading, setListLoading] = useState(false);
  const [activeKey, setActiveKey] = useState<string>(inbox.initial.key || '');
  const [data, setData] = useState<ThreadData | null>(null);
  const [threadLoading, setThreadLoading] = useState(false);
  const [olderLoading, setOlderLoading] = useState(false);
  const [templates, setTemplates] = useState<Template[]>(inbox.templates);
  const [composer, setComposer] = useState('');
  const [noteMode, setNoteMode] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');
  const [rt, setRt] = useState<RealtimeState>(realtime ? 'connecting' : 'offline');
  const [showInfo, setShowInfo] = useState(() => readPref('kc-inbox-info', '1') === '1');
  const [sound, setSound] = useState(() => readPref('kc-inbox-sound', '1') === '1');
  const [pickerOpen, setPickerOpen] = useState(false);
  const [pickerIndex, setPickerIndex] = useState(0);
  const [templateTpl, setTemplateTpl] = useState<Template | null>(null);
  const [templateModal, setTemplateModal] = useState(false);
  const [usedTemplate, setUsedTemplate] = useState<number | null>(null);

  const messagesRef = useRef<HTMLDivElement>(null);
  const composerRef = useRef<HTMLTextAreaElement>(null);
  const stateRef = useRef({ segment, filter, search, activeKey });
  stateRef.current = { segment, filter, search, activeKey };
  const refreshTimer = useRef<number | null>(null);
  const stickBottom = useRef(true);

  // ── Ro'yxatni yuklash ──
  const loadList = useCallback(async (opts: { silent?: boolean; append?: boolean } = {}) => {
    const { segment: seg, filter: fil, search: q } = stateRef.current;
    if (!opts.silent) setListLoading(true);
    try {
      const params = new URLSearchParams({ segment: seg, filter: fil, q });
      if (opts.append && list.next) params.set('before', list.next);
      const payload = await apiJson<ListPayload>(`${urls.threads}?${params}`);
      setList((prev) => (opts.append ? { ...payload, items: [...prev.items, ...payload.items.filter((t) => !prev.items.some((p) => p.key === t.key))] } : payload));
    } catch (e) {
      if (!opts.silent) setError((e as Error).message);
    } finally {
      if (!opts.silent) setListLoading(false);
    }
  }, [list.next, urls.threads]);

  const scheduleRefresh = useCallback(() => {
    if (refreshTimer.current) window.clearTimeout(refreshTimer.current);
    refreshTimer.current = window.setTimeout(() => loadList({ silent: true }), 1200);
  }, [loadList]);

  useEffect(() => {
    const t = window.setTimeout(() => loadList(), search ? 300 : 0);
    return () => window.clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [segment, filter, search]);

  // ── Suhbatni ochish ──
  const markRead = useCallback((key: string) => {
    apiJson(urls.read, { method: 'POST', body: { key } }).catch(() => undefined);
    setList((prev) => ({ ...prev, items: prev.items.map((t) => (t.key === key ? { ...t, unread: 0 } : t)) }));
  }, [urls.read]);

  const openThread = useCallback(async (key: string, opts: { silent?: boolean } = {}) => {
    if (!key) return;
    if (!opts.silent) {
      setThreadLoading(true);
      setActiveKey(key);
      setError('');
      stickBottom.current = true;
    }
    try {
      const payload = await apiJson<ThreadData>(`${urls.thread}?key=${encodeURIComponent(key)}`);
      if (stateRef.current.activeKey !== key && opts.silent) return;
      setData((prev) => {
        if (opts.silent && prev && prev.thread.key === key) {
          // Faqat yangi xabarlarni qo'shamiz (yuqoridagi eski sahifalar saqlanadi)
          const known = new Set(prev.messages.map((m) => m.id));
          const fresh = payload.messages.filter((m) => !known.has(m.id));
          return { ...payload, messages: [...prev.messages.filter((m) => !m.pending), ...fresh], has_more: prev.has_more };
        }
        return payload;
      });
      if (payload.thread.unread > 0) markRead(key);
      const url = new URL(window.location.href);
      url.searchParams.set('key', key);
      url.searchParams.set('segment', key.startsWith('s-') ? 'shop' : 'customer');
      window.history.replaceState(window.history.state, '', url.toString());
    } catch (e) {
      if (!opts.silent) setError((e as Error).message);
    } finally {
      if (!opts.silent) setThreadLoading(false);
    }
  }, [markRead, urls.thread]);

  useEffect(() => {
    if (inbox.initial.key) openThread(inbox.initial.key);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const loadOlder = useCallback(async () => {
    if (!data || !data.has_more || olderLoading) return;
    const first = data.messages.find((m) => !m.pending);
    if (!first) return;
    setOlderLoading(true);
    const box = messagesRef.current;
    const prevHeight = box?.scrollHeight || 0;
    try {
      const payload = await apiJson<{ messages: Message[]; has_more: boolean }>(`${urls.older}?key=${encodeURIComponent(data.thread.key)}&before_id=${first.raw_id}`);
      setData((prev) => (prev ? { ...prev, messages: [...payload.messages, ...prev.messages], has_more: payload.has_more } : prev));
      requestAnimationFrame(() => {
        if (box) box.scrollTop = box.scrollHeight - prevHeight;
      });
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setOlderLoading(false);
    }
  }, [data, olderLoading, urls.older]);

  // ── Avto-pastga aylantirish ──
  useEffect(() => {
    const box = messagesRef.current;
    if (box && stickBottom.current) box.scrollTop = box.scrollHeight;
  }, [data?.messages.length, data?.thread.key]);

  // ── Real-vaqt ──
  useEffect(() => {
    const echo = getEcho(realtime, urls.broadcastAuth);
    if (!echo) {
      setRt('offline');
      return undefined;
    }
    const stop = watchState(echo, setRt);
    const channel = echo.private('support.inbox');
    channel.listen('.SupportInboxUpdated', (event: { kind: string; segment: Segment; key: string; thread: Thread | null; message: Message | null }) => {
      const { activeKey: current, segment: seg } = stateRef.current;

      if (event.thread && event.segment === seg) {
        setList((prev) => {
          const exists = prev.items.some((t) => t.key === event.key);
          const merged = exists
            ? prev.items.map((t) => (t.key === event.key ? { ...event.thread!, unread: event.key === current && document.hasFocus() ? 0 : event.thread!.unread } : t))
            : [event.thread!, ...prev.items];
          return { ...prev, items: merged.sort((a, b) => (b.last_message_at || '').localeCompare(a.last_message_at || '')) };
        });
      }

      if (event.key === current) {
        setData((prev) => {
          if (!prev || prev.thread.key !== event.key) return prev;
          let messages = prev.messages;
          if (event.message && !messages.some((m) => m.id === event.message!.id)) {
            messages = [...messages.filter((m) => !(m.pending && m.text === event.message!.text && event.message!.from !== 'customer')), event.message];
          }
          return { ...prev, thread: event.thread || prev.thread, messages };
        });
        if (event.message?.from === 'customer' && document.hasFocus()) markRead(event.key);
      }

      if (event.message?.from === 'customer') {
        if (sound && (event.key !== current || !document.hasFocus())) playPing();
      }

      scheduleRefresh();
    });

    return () => {
      stop();
      echo.leave('support.inbox');
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sound]);

  // ── Ulanish bo'lmasa: polling ──
  useEffect(() => {
    if (rt === 'connected') return undefined;
    const id = window.setInterval(() => {
      loadList({ silent: true });
      if (stateRef.current.activeKey) openThread(stateRef.current.activeKey, { silent: true });
    }, 8000);
    return () => window.clearInterval(id);
  }, [rt, loadList, openThread]);

  // Ulangan bo'lsa ham vaqti-vaqti bilan tekshiruv (o'tkazib yuborilgan hodisalar uchun)
  useEffect(() => {
    const id = window.setInterval(() => {
      loadList({ silent: true });
      if (stateRef.current.activeKey) openThread(stateRef.current.activeKey, { silent: true });
    }, 60000);
    return () => window.clearInterval(id);
  }, [loadList, openThread]);

  // Oyna fokusga qaytganda o'qildi
  useEffect(() => {
    const onFocus = () => {
      const key = stateRef.current.activeKey;
      if (key && list.items.find((t) => t.key === key)?.unread) markRead(key);
    };
    window.addEventListener('focus', onFocus);
    return () => window.removeEventListener('focus', onFocus);
  }, [list.items, markRead]);

  // Sarlavhada o'qilmaganlar soni
  const totalUnread = list.segments.customer.unread + list.segments.shop.unread;
  useEffect(() => {
    const base = 'Support inbox - Kitobchi Boshqaruv';
    document.title = totalUnread > 0 ? `(${totalUnread}) ${base}` : base;
  }, [totalUnread]);

  // ── Amallar ──
  const thread = data?.thread || null;
  const audience: 'customer' | 'shop' = thread?.segment === 'shop' ? 'shop' : 'customer';
  const visibleTemplates = useMemo(() => templates.filter((t) => t.audience === 'all' || t.audience === audience), [templates, audience]);

  const slashQuery = composer.startsWith('/') && !composer.includes('\n') ? composer.slice(1).toLowerCase() : null;
  const pickerItems = useMemo(() => {
    const q = (slashQuery ?? '').trim();
    const items = q
      ? visibleTemplates.filter((t) => (t.shortcut || '').toLowerCase().startsWith(q) || t.title.toLowerCase().includes(q) || t.body.toLowerCase().includes(q))
      : visibleTemplates;
    return items.slice(0, 8);
  }, [slashQuery, visibleTemplates]);

  useEffect(() => {
    setPickerOpen(slashQuery !== null && pickerItems.length > 0);
    setPickerIndex(0);
  }, [slashQuery, pickerItems.length]);

  const applyTemplate = (t: Template) => {
    setComposer(fillTemplate(t.body, thread, me));
    setUsedTemplate(t.id);
    setPickerOpen(false);
    requestAnimationFrame(() => composerRef.current?.focus());
  };

  const send = async () => {
    if (!thread || !composer.trim() || sending || me.readOnly) return;
    const text = composer.trim();
    const note = noteMode;
    const tempId = `tmp-${Date.now()}`;
    const optimistic: Message = {
      id: tempId, raw_id: 0, ticket_id: thread.ticket_id, from: note ? 'note' : 'agent', type: note ? 'note' : 'text',
      text, author: me.name, at: new Date().toISOString(), delivered: false, error: null, attachments: [], pending: true,
    };
    stickBottom.current = true;
    setData((prev) => (prev ? { ...prev, messages: [...prev.messages, optimistic] } : prev));
    setComposer('');
    setSending(true);
    setError('');
    try {
      const res = await apiJson<{ message: Message }>(urls.reply, { method: 'POST', body: { key: thread.key, message: text, note, template_id: usedTemplate } });
      setData((prev) => {
        if (!prev) return prev;
        const without = prev.messages.filter((m) => m.id !== tempId);
        return { ...prev, messages: without.some((m) => m.id === res.message.id) ? without : [...without, res.message] };
      });
      setUsedTemplate(null);
      scheduleRefresh();
    } catch (e) {
      setError((e as Error).message);
      setData((prev) => (prev ? { ...prev, messages: prev.messages.map((m) => (m.id === tempId ? { ...m, pending: false, error: (e as Error).message } : m)) } : prev));
      setComposer(text);
    } finally {
      setSending(false);
      requestAnimationFrame(() => composerRef.current?.focus());
    }
  };

  const assign = async (adminId: number | null) => {
    if (!thread) return;
    try {
      const res = await apiJson<{ thread: Thread }>(urls.assign, { method: 'POST', body: { key: thread.key, admin_id: adminId } });
      setData((prev) => (prev ? { ...prev, thread: res.thread } : prev));
      openThread(thread.key, { silent: true });
      scheduleRefresh();
    } catch (e) {
      setError((e as Error).message);
    }
  };

  const closeThread = async () => {
    if (!thread) return;
    if (!window.confirm(thread.segment === 'shop' ? 'Do‘konning ochiq murojaatlari yopilsinmi? Do‘konga baholash so‘rovi boradi.' : 'Suhbat yopilsinmi? Mijozdan baho so‘raladi.')) return;
    try {
      const res = await apiJson<{ thread: Thread }>(urls.close, { method: 'POST', body: { key: thread.key } });
      setData((prev) => (prev ? { ...prev, thread: res.thread } : prev));
      openThread(thread.key, { silent: true });
      scheduleRefresh();
    } catch (e) {
      setError((e as Error).message);
    }
  };

  const onComposerKey = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (pickerOpen) {
      if (e.key === 'ArrowDown') { e.preventDefault(); setPickerIndex((i) => Math.min(i + 1, pickerItems.length - 1)); return; }
      if (e.key === 'ArrowUp') { e.preventDefault(); setPickerIndex((i) => Math.max(i - 1, 0)); return; }
      if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); const t = pickerItems[pickerIndex]; if (t) applyTemplate(t); return; }
      if (e.key === 'Escape') { setPickerOpen(false); return; }
    }
    if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent.isComposing) {
      e.preventDefault();
      send();
    }
  };

  // Textarea balandligi
  useEffect(() => {
    const el = composerRef.current;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, 180)}px`;
  }, [composer]);

  const switchSegment = (s: Segment) => {
    if (s === segment) return;
    setSegment(s);
    setList((prev) => ({ ...prev, items: [], next: null }));
  };

  const toggleInfo = () => {
    setShowInfo((v) => { writePref('kc-inbox-info', v ? '0' : '1'); return !v; });
  };

  // ── Xabarlarni guruhlash (kun / murojaat ajratgichlari) ──
  const rendered = useMemo(() => {
    if (!data) return [] as React.ReactNode[];
    const out: React.ReactNode[] = [];
    let lastDay = '';
    let lastTicket = 0;
    let lastFrom = '';
    const ticketMap = new Map(data.context.tickets.map((t) => [t.id, t]));
    data.messages.forEach((m) => {
      const day = m.at ? dayLabel(m.at) : '';
      if (day && day !== lastDay) {
        out.push(<div key={`d-${m.id}`} className="text-center my-3"><span className="badge kc-day-chip">{day}</span></div>);
        lastDay = day;
        lastFrom = '';
      }
      if (m.ticket_id !== lastTicket) {
        if (lastTicket !== 0) {
          const t = ticketMap.get(m.ticket_id);
          out.push(
            <div key={`t-${m.id}`} className="d-flex align-items-center gap-2 my-3 text-secondary f-s-12">
              <span className="flex-grow-1 border-top"></span>
              <span><i className="ti ti-ticket me-1"></i>Murojaat #{m.ticket_id}{t?.title ? ` · ${t.title}` : ''}</span>
              <span className="flex-grow-1 border-top"></span>
            </div>,
          );
        }
        lastTicket = m.ticket_id;
      }
      out.push(<Bubble key={m.id} m={m} showAuthor={lastFrom !== `${m.from}:${m.author}`} />);
      lastFrom = `${m.from}:${m.author}`;
    });
    return out;
  }, [data]);

  const counts = list.counts || ({} as Record<Filter, number>);
  const mobileView: 'list' | 'chat' = activeKey ? 'chat' : 'list';

  return (
    <>
      <Head title="Support inbox" />
      <div className={`kc-inbox card mb-0 ${showInfo && thread ? 'kc-inbox-with-info' : ''} kc-inbox-mobile-${mobileView}`}>
        {/* ═══ CHAP: RO'YXAT ═══ */}
        <aside className="kc-inbox-list">
          <div className="p-3 pb-2 border-bottom">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <div className="d-flex align-items-center gap-2">
                <h5 className="mb-0 f-w-600">Support</h5>
                <ConnectionDot state={rt} />
              </div>
              <div className="d-flex align-items-center gap-1">
                <button type="button" className="btn btn-sm btn-light-secondary icon-btn b-r-10" title={sound ? 'Ovozni o‘chirish' : 'Ovozni yoqish'}
                  onClick={() => setSound((v) => { writePref('kc-inbox-sound', v ? '0' : '1'); return !v; })}>
                  <i className={`ti ${sound ? 'ti-bell-ringing' : 'ti-bell-off'}`}></i>
                </button>
                <Link href={urls.kpi} className="btn btn-sm btn-light-primary icon-btn b-r-10" title="Support KPI"><i className="ti ti-chart-histogram"></i></Link>
              </div>
            </div>

            <div className="nav kc-segment w-100 mb-3">
              {(['customer', 'shop'] as Segment[]).map((s) => (
                <div className="nav-item" key={s}>
                  <button type="button" className={`nav-link ${segment === s ? 'active' : ''}`} onClick={() => switchSegment(s)}>
                    <i className={`ti ${s === 'customer' ? 'ti-user' : 'ti-building-store'}`}></i>
                    {s === 'customer' ? 'Mijoz' : 'Do‘kon'}
                    {list.segments[s].unread > 0 ? <span className="badge bg-danger text-white">{list.segments[s].unread}</span> : null}
                  </button>
                </div>
              ))}
            </div>

            <div className="position-relative mb-2">
              <i className="ti ti-search position-absolute text-secondary" style={{ left: 12, top: '50%', transform: 'translateY(-50%)' }}></i>
              <input type="search" className="form-control form-control-sm ps-5" placeholder={segment === 'shop' ? 'Do‘kon, telefon, mavzu…' : 'Ism, telefon, @username, ID…'}
                value={search} onChange={(e) => setSearch(e.target.value)} />
            </div>

            <div className="d-flex gap-1 overflow-auto kc-no-scrollbar pb-1">
              {FILTERS.map((f) => (
                <button key={f.key} type="button" onClick={() => setFilter(f.key)}
                  className={`btn btn-sm b-r-10 text-nowrap f-s-12 ${filter === f.key ? 'btn-primary' : 'btn-light-secondary'}`}>
                  {f.label}{counts[f.key] !== undefined ? <span className="ms-1 opacity-75">{counts[f.key]}</span> : null}
                </button>
              ))}
            </div>
          </div>

          <div className="kc-inbox-scroll">
            {listLoading && list.items.length === 0 ? (
              <div className="p-4 text-center text-secondary"><span className="spinner-border spinner-border-sm me-2"></span>Yuklanmoqda…</div>
            ) : null}
            {!listLoading && list.items.length === 0 ? (
              <div className="p-4 text-center text-secondary">
                <i className="ti ti-mood-empty f-s-30 d-block mb-2"></i>
                {filter === 'unassigned' ? 'Navbatda hech kim yo‘q' : filter === 'mine' ? 'Sizga biriktirilgan suhbat yo‘q' : 'Suhbat topilmadi'}
              </div>
            ) : null}
            {list.items.map((t) => {
              const active = t.key === activeKey;
              return (
                <button key={t.key} type="button" onClick={() => openThread(t.key)} className={`kc-thread ${active ? 'active' : ''}`}>
                  <Avatar name={t.name} src={t.avatar} icon={t.segment === 'shop' && !t.avatar ? 'ti ti-building-store' : undefined} />
                  <span className="flex-grow-1 min-w-0 text-start">
                    <span className="d-flex align-items-center gap-1">
                      <span className="f-w-600 text-truncate kc-thread-name">{t.name}</span>
                      <SourceIcon source={t.source} />
                      <span className="ms-auto f-s-11 text-secondary flex-shrink-0">{listTime(t.last_message_at)}</span>
                    </span>
                    <span className="d-flex align-items-center gap-1 mt-1">
                      <span className="text-truncate f-s-13 text-secondary flex-grow-1">
                        {t.last_from === 'agent' ? <span className="text-primary">Siz: </span> : null}
                        {t.last_message || '—'}
                      </span>
                      {t.unread > 0 ? <span className="badge bg-primary b-r-50 flex-shrink-0">{t.unread}</span> : null}
                    </span>
                    <span className="d-flex align-items-center gap-1 mt-1 f-s-11">
                      {t.status === 'closed'
                        ? <span className="text-secondary"><i className="ti ti-circle-check me-1"></i>Yopilgan</span>
                        : t.waiting
                          ? <span className={waitMinutes(t.last_message_at) >= 15 ? 'text-danger f-w-600' : 'text-warning f-w-600'}><i className="ti ti-clock me-1"></i>Javob kutmoqda · {waitLabel(t.last_message_at)}</span>
                          : <span className="text-success"><i className="ti ti-message-check me-1"></i>Javob berilgan</span>}
                      {t.feedback ? <i className={`ti ${t.feedback === 'good' ? 'ti-thumb-up text-success' : 'ti-thumb-down text-danger'} ms-1`}></i> : null}
                      <span className="ms-auto text-secondary text-truncate" style={{ maxWidth: 110 }}>
                        {t.assignee ? <><i className="ti ti-user-check me-1"></i>{t.assignee.id === me.id ? 'Siz' : t.assignee.name}</> : t.legacy_operator ? t.legacy_operator : t.status === 'open' ? <span className="text-warning">Navbatda</span> : null}
                      </span>
                    </span>
                  </span>
                </button>
              );
            })}
            {list.next ? (
              <div className="p-3 text-center">
                <button type="button" className="btn btn-sm btn-light-primary" onClick={() => loadList({ silent: true, append: true })}>Yana yuklash</button>
              </div>
            ) : null}
          </div>
        </aside>

        {/* ═══ O'RTA: SUHBAT ═══ */}
        <section className="kc-inbox-chat">
          {!thread && !threadLoading ? (
            <div className="h-100 d-flex flex-column align-items-center justify-content-center text-center p-4 text-secondary">
              <span className="h-80 w-80 d-flex-center b-r-50 text-light-primary mb-3" style={{ width: 80, height: 80 }}><i className="ti ti-messages f-s-36"></i></span>
              <h6 className="f-w-600 text-dark mb-1">Suhbatni tanlang</h6>
              <p className="mb-0 f-s-13">Chapdagi ro‘yxatdan mijoz yoki do‘konni tanlang. Yangi xabarlar shu yerda real vaqtda paydo bo‘ladi.</p>
            </div>
          ) : null}

          {threadLoading && !thread ? (
            <div className="h-100 d-flex-center text-secondary"><span className="spinner-border spinner-border-sm me-2"></span>Yuklanmoqda…</div>
          ) : null}

          {thread ? (
            <>
              <header className="kc-chat-head">
                <button type="button" className="btn btn-sm btn-light-secondary icon-btn b-r-10 d-md-none me-1" onClick={() => setActiveKey('')} aria-label="Orqaga">
                  <i className="ti ti-arrow-left"></i>
                </button>
                <Avatar name={thread.name} src={thread.avatar} size={40} icon={thread.segment === 'shop' && !thread.avatar ? 'ti ti-building-store' : undefined} />
                <div className="min-w-0 flex-grow-1">
                  <div className="d-flex align-items-center gap-2">
                    <span className="f-w-600 text-truncate">{thread.name}</span>
                    <span className={`badge ${thread.status === 'open' ? 'text-light-success' : 'text-light-secondary'}`}>{thread.status === 'open' ? 'Ochiq' : 'Yopilgan'}</span>
                  </div>
                  <div className="f-s-12 text-secondary text-truncate">
                    <SourceIcon source={thread.source} /> {thread.source === 'telegram' ? 'Telegram' : thread.source === 'app' ? 'Ilova' : 'Do‘kon'} · {thread.subtitle}
                  </div>
                </div>

                <div className="d-flex align-items-center gap-1 flex-shrink-0">
                  {!me.readOnly ? (
                    <Dropdown align="end">
                      <Dropdown.Toggle size="sm" variant="light-secondary" className="b-r-10 d-flex align-items-center gap-1">
                        <i className="ti ti-user-check"></i>
                        <span className="d-none d-lg-inline">{thread.assignee ? (thread.assignee.id === me.id ? 'Siz' : thread.assignee.name) : 'Biriktirish'}</span>
                      </Dropdown.Toggle>
                      <Dropdown.Menu className="b-r-10" style={{ maxHeight: 320, overflowY: 'auto' }}>
                        <Dropdown.Item onClick={() => assign(me.id)}><i className="ti ti-hand-grab me-2"></i>O‘zimga olish</Dropdown.Item>
                        <Dropdown.Divider />
                        {agents.filter((a) => a.id !== me.id).map((a) => (
                          <Dropdown.Item key={a.id} onClick={() => assign(a.id)} active={thread.assignee?.id === a.id}>{a.name}</Dropdown.Item>
                        ))}
                        {thread.assignee ? (<><Dropdown.Divider /><Dropdown.Item className="text-danger" onClick={() => assign(null)}>Bo‘shatish (navbatga)</Dropdown.Item></>) : null}
                      </Dropdown.Menu>
                    </Dropdown>
                  ) : null}
                  {!me.readOnly && thread.status === 'open' ? (
                    <button type="button" className="btn btn-sm btn-light-success b-r-10 d-flex align-items-center gap-1" onClick={closeThread}>
                      <i className="ti ti-circle-check"></i><span className="d-none d-lg-inline">Yopish</span>
                    </button>
                  ) : null}
                  <button type="button" className={`btn btn-sm icon-btn b-r-10 ${showInfo ? 'btn-light-primary' : 'btn-light-secondary'}`} onClick={toggleInfo} title="Ma’lumotlar">
                    <i className="ti ti-layout-sidebar-right"></i>
                  </button>
                </div>
              </header>

              <div className="kc-chat-body" ref={messagesRef}
                onScroll={(e) => {
                  const el = e.currentTarget;
                  stickBottom.current = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
                  if (el.scrollTop < 60) loadOlder();
                }}>
                {data?.has_more ? (
                  <div className="text-center my-2">
                    <button type="button" className="btn btn-sm btn-light-secondary b-r-10" onClick={loadOlder} disabled={olderLoading}>
                      {olderLoading ? <span className="spinner-border spinner-border-sm"></span> : 'Oldingi xabarlar'}
                    </button>
                  </div>
                ) : null}
                {rendered}
                {thread.status === 'closed' ? (
                  <div className="text-center my-3 f-s-12 text-secondary">
                    Suhbat yopilgan{thread.feedback ? <> · baho: {thread.feedback === 'good' ? <span className="text-success f-w-600">yaxshi</span> : <span className="text-danger f-w-600">yomon</span>}</> : ' · baho kutilmoqda'}. Javob yozsangiz, yangi murojaat ochiladi.
                  </div>
                ) : null}
              </div>

              {error ? (
                <div className="px-3 pt-2"><div className="alert alert-light-danger py-2 px-3 mb-0 f-s-13 d-flex align-items-center gap-2"><i className="ti ti-alert-circle"></i><span className="flex-grow-1">{error}</span><button type="button" className="btn-close" style={{ fontSize: 10 }} onClick={() => setError('')}></button></div></div>
              ) : null}

              {me.readOnly ? (
                <div className="kc-composer text-center text-secondary f-s-13 py-3">Faqat ko‘rish rejimi — javob yozib bo‘lmaydi.</div>
              ) : (
                <div className={`kc-composer ${noteMode ? 'kc-composer-note' : ''}`}>
                  {pickerOpen ? (
                    <div className="kc-template-picker card mb-0">
                      <div className="px-3 py-2 f-s-11 text-secondary border-bottom">Shablonlar · ↑↓ tanlash, Enter qo‘yish</div>
                      {pickerItems.map((t, i) => (
                        <button key={t.id} type="button" className={`kc-template-row ${i === pickerIndex ? 'active' : ''}`} onMouseEnter={() => setPickerIndex(i)} onClick={() => applyTemplate(t)}>
                          <span className="f-w-600 f-s-13">{t.title}{t.shortcut ? <span className="text-secondary f-w-400 ms-2">/{t.shortcut}</span> : null}</span>
                          <span className="d-block text-secondary f-s-12 text-truncate">{fillTemplate(t.body, thread, me)}</span>
                        </button>
                      ))}
                    </div>
                  ) : null}

                  <div className="d-flex align-items-center gap-2 mb-2">
                    <div className="nav kc-segment kc-segment-sm">
                      <div className="nav-item"><button type="button" className={`nav-link ${!noteMode ? 'active' : ''}`} onClick={() => setNoteMode(false)}><i className="ti ti-send"></i>Javob</button></div>
                      <div className="nav-item"><button type="button" className={`nav-link ${noteMode ? 'active' : ''}`} onClick={() => setNoteMode(true)}><i className="ti ti-lock"></i>Ichki eslatma</button></div>
                    </div>
                    <Dropdown drop="up" className="ms-auto">
                      <Dropdown.Toggle size="sm" variant="light-primary" className="b-r-10 d-flex align-items-center gap-1">
                        <i className="ti ti-template"></i><span className="d-none d-sm-inline">Shablonlar</span>
                      </Dropdown.Toggle>
                      <Dropdown.Menu className="b-r-10 p-0" style={{ width: 340, maxHeight: 360, overflowY: 'auto' }}>
                        {visibleTemplates.map((t) => (
                          <Dropdown.Item key={t.id} onClick={() => applyTemplate(t)} className="py-2 text-wrap">
                            <span className="f-w-600 f-s-13 d-block">{t.title}{t.shortcut ? <span className="text-secondary f-w-400 ms-2">/{t.shortcut}</span> : null}</span>
                            <span className="d-block text-secondary f-s-12 kc-clamp-2">{t.body}</span>
                          </Dropdown.Item>
                        ))}
                        <Dropdown.Divider className="my-0" />
                        <Dropdown.Item className="py-2 text-primary f-w-600" onClick={() => { setTemplateTpl(null); setTemplateModal(true); }}>
                          <i className="ti ti-plus me-1"></i>Yangi shablon
                        </Dropdown.Item>
                      </Dropdown.Menu>
                    </Dropdown>
                  </div>

                  <div className="d-flex align-items-end gap-2">
                    <textarea ref={composerRef} rows={1} className="form-control kc-composer-input" value={composer}
                      placeholder={noteMode ? 'Faqat jamoa ko‘radi…' : `Javob yozing… («/» — shablonlar)${thread.status === 'closed' ? ' · yangi murojaat ochiladi' : ''}`}
                      onChange={(e) => setComposer(e.target.value)} onKeyDown={onComposerKey} disabled={sending && !composer} />
                    <button type="button" className={`btn ${noteMode ? 'btn-warning' : 'btn-primary'} icon-btn b-r-10 flex-shrink-0`} style={{ width: 44, height: 44 }}
                      onClick={send} disabled={!composer.trim() || sending} aria-label="Yuborish">
                      {sending ? <span className="spinner-border spinner-border-sm"></span> : <i className={`ti ${noteMode ? 'ti-note' : 'ti-send'} f-s-18`}></i>}
                    </button>
                  </div>
                  <div className="f-s-11 text-secondary mt-1 d-none d-md-block">Enter — yuborish · Shift+Enter — yangi qator</div>
                </div>
              )}
            </>
          ) : null}
        </section>

        {/* ═══ O'NG: MA'LUMOTLAR ═══ */}
        {thread && showInfo && data ? (
          <aside className="kc-inbox-info">
            <div className="kc-inbox-scroll p-3">
              <div className="d-flex d-xxl-none justify-content-end mb-2">
                <button type="button" className="btn btn-sm btn-light-secondary icon-btn b-r-10" onClick={toggleInfo} aria-label="Yopish"><i className="ti ti-x"></i></button>
              </div>
              <InfoPanel data={data} />
              <TemplatesPanel templates={visibleTemplates} onUse={applyTemplate} onEdit={(t) => { setTemplateTpl(t); setTemplateModal(true); }} onAdd={() => { setTemplateTpl(null); setTemplateModal(true); }} readOnly={me.readOnly} />
            </div>
          </aside>
        ) : null}
      </div>

      <TemplateModal
        show={templateModal}
        template={templateTpl}
        defaultAudience={audience}
        onClose={() => setTemplateModal(false)}
        urls={urls}
        onSaved={(items) => { setTemplates(items); setTemplateModal(false); }}
      />
    </>
  );
}

function waitMinutes(iso: string | null) {
  if (!iso) return 0;
  return Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
}

// ─── O'ng panel ─────────────────────────────────────────────────────────────

function InfoPanel({ data }: { data: ThreadData }) {
  const { context: c, thread } = data;
  const p = c.profile;
  const totalRated = c.feedback.good + c.feedback.bad;
  const pct = totalRated ? Math.round((c.feedback.good * 100) / totalRated) : null;

  return (
    <>
      <div className="text-center mb-3">
        <div className="d-flex justify-content-center mb-2">
          <Avatar name={thread.name} src={(p.avatar as string) || thread.avatar} size={72} icon={c.kind === 'shop' && !p.avatar ? 'ti ti-building-store' : undefined} />
        </div>
        <h6 className="f-w-600 mb-0">{(p.name as string) || thread.name}</h6>
        <div className="f-s-12 text-secondary">{c.kind === 'shop' ? (p.owner as string) || 'Do‘kon' : (p.channel as string)}</div>
      </div>

      <div className="b-1-light b-r-15 p-3 mb-3">
        {c.kind === 'shop' ? (
          <>
            <InfoRow icon="ti-phone" label="Telefon" value={p.phone as string} copy />
            <InfoRow icon="ti-map-pin" label="Hudud" value={p.region as string} />
            <InfoRow icon="ti-shield-check" label="Holat" value={p.status as string} />
            <InfoRow icon="ti-star" label="Reyting" value={p.rating !== null && p.rating !== undefined ? String(p.rating) : null} />
            <InfoRow icon="ti-package" label="Muvaffaqiyatli buyurtmalar" value={p.successful_orders !== undefined ? String(p.successful_orders) : null} />
            <InfoRow icon="ti-calendar" label="Ro‘yxatdan o‘tgan" value={p.registered as string} />
            {p.seller_url ? <a href={p.seller_url as string} className="btn btn-sm btn-light-primary w-100 mt-2 b-r-10">Do‘kon sahifasi</a> : null}
          </>
        ) : (
          <>
            <InfoRow icon="ti-phone" label="Telefon" value={p.phone as string} copy />
            <InfoRow icon="ti-at" label="Username" value={p.username as string} copy />
            <InfoRow icon="ti-brand-telegram" label="Telegram ID" value={p.telegram_id ? String(p.telegram_id) : null} copy />
            <InfoRow icon="ti-id" label="Foydalanuvchi ID" value={p.user_id ? String(p.user_id) : null} copy />
            {c.stats ? (
              <>
                <InfoRow icon="ti-shopping-bag" label="Buyurtmalar" value={String(c.stats.orders_count)} />
                <InfoRow icon="ti-cash" label="Jami xarid" value={money(c.stats.total_spent)} />
                <InfoRow icon="ti-coin" label="Keshbek" value={money(c.stats.cashback)} />
              </>
            ) : <div className="f-s-12 text-secondary">Ilova akkaunti topilmadi (telefon raqami so‘ralsa, avtomatik bog‘lanadi).</div>}
            {p.user_url ? <a href={p.user_url as string} className="btn btn-sm btn-light-primary w-100 mt-2 b-r-10">Foydalanuvchi profili</a> : null}
          </>
        )}
      </div>

      <div className="b-1-light b-r-15 p-3 mb-3">
        <div className="d-flex align-items-center justify-content-between mb-2">
          <span className="f-w-600">Baholar</span>
          {pct !== null ? <span className={`badge ${pct >= 80 ? 'text-light-success' : pct >= 50 ? 'text-light-warning' : 'text-light-danger'}`}>{pct}% mamnun</span> : null}
        </div>
        <div className="d-flex gap-2 mb-2">
          <div className="flex-fill text-light-success b-r-10 p-2 text-center"><i className="ti ti-thumb-up me-1"></i><span className="f-w-600">{c.feedback.good}</span></div>
          <div className="flex-fill text-light-danger b-r-10 p-2 text-center"><i className="ti ti-thumb-down me-1"></i><span className="f-w-600">{c.feedback.bad}</span></div>
        </div>
        {c.feedback.items.length === 0 ? <div className="f-s-12 text-secondary">Hali baho qo‘yilmagan.</div> : (
          <ul className="list-unstyled mb-0 f-s-12">
            {c.feedback.items.map((f) => (
              <li key={f.ticket_id} className="d-flex align-items-center gap-2 py-1">
                <i className={`ti ${f.value === 'good' ? 'ti-thumb-up text-success' : 'ti-thumb-down text-danger'}`}></i>
                <span>#{f.ticket_id}</span>
                <span className="text-secondary text-truncate">{f.agent || '—'}</span>
                <span className="ms-auto text-secondary">{f.at ? listTime(f.at) : ''}</span>
              </li>
            ))}
          </ul>
        )}
      </div>

      {c.orders.length > 0 ? (
        <div className="b-1-light b-r-15 p-3 mb-3">
          <div className="f-w-600 mb-2">So‘nggi buyurtmalar</div>
          {c.orders.map((o) => (
            <a key={o.id} href={o.url} className="d-flex align-items-center gap-2 py-2 text-reset border-bottom kc-last-0">
              <span className="h-35 w-35 d-flex-center b-r-10 text-light-primary flex-shrink-0"><i className="ti ti-receipt"></i></span>
              <span className="min-w-0 flex-grow-1">
                <span className="d-block f-w-600 f-s-13">#{o.id} · {money(o.amount)}</span>
                <span className="d-block f-s-11 text-secondary text-truncate">{o.status}{o.delivery_date ? ` · yetkazish ${o.delivery_date}` : ''}</span>
              </span>
              <span className="f-s-11 text-secondary">{o.date ? listTime(o.date) : ''}</span>
            </a>
          ))}
        </div>
      ) : null}

      <div className="b-1-light b-r-15 p-3 mb-3">
        <div className="f-w-600 mb-2">Murojaatlar tarixi</div>
        {c.tickets.map((t) => (
          <div key={t.id} className="d-flex align-items-start gap-2 py-2 border-bottom kc-last-0">
            <span className={`badge ${t.status === 'open' ? 'text-light-success' : 'text-light-secondary'} mt-1`}>#{t.id}</span>
            <span className="min-w-0 flex-grow-1">
              <span className="d-block f-s-13 text-truncate">{t.title || '—'}</span>
              <span className="d-block f-s-11 text-secondary">{t.created_at ? listTime(t.created_at) : ''}{t.agent ? ` · ${t.agent}` : ''}</span>
            </span>
            {t.feedback ? <i className={`ti ${t.feedback === 'good' ? 'ti-thumb-up text-success' : 'ti-thumb-down text-danger'} mt-1`}></i> : null}
          </div>
        ))}
      </div>
    </>
  );
}

function InfoRow({ icon, label, value, copy }: { icon: string; label: string; value: string | null | undefined; copy?: boolean }) {
  if (!value) return null;
  return (
    <div className="d-flex align-items-center gap-2 py-1 f-s-13">
      <i className={`ti ${icon} text-secondary`}></i>
      <span className="text-secondary">{label}</span>
      <span className="ms-auto f-w-500 text-truncate" style={{ maxWidth: '55%' }} title={value}>{value}</span>
      {copy ? (
        <button type="button" className="btn btn-link p-0 text-secondary" title="Nusxa olish" onClick={() => navigator.clipboard?.writeText(value)}>
          <i className="ti ti-copy f-s-14"></i>
        </button>
      ) : null}
    </div>
  );
}

function TemplatesPanel({ templates, onUse, onEdit, onAdd, readOnly }: { templates: Template[]; onUse: (t: Template) => void; onEdit: (t: Template) => void; onAdd: () => void; readOnly: boolean }) {
  const [q, setQ] = useState('');
  const items = templates.filter((t) => !q || t.title.toLowerCase().includes(q.toLowerCase()) || t.body.toLowerCase().includes(q.toLowerCase()));
  return (
    <div className="b-1-light b-r-15 p-3 mb-3">
      <div className="d-flex align-items-center justify-content-between mb-2">
        <span className="f-w-600">Shablon javoblar</span>
        {!readOnly ? <button type="button" className="btn btn-sm btn-light-primary icon-btn b-r-10" onClick={onAdd} title="Yangi shablon"><i className="ti ti-plus"></i></button> : null}
      </div>
      <input type="search" className="form-control form-control-sm mb-2" placeholder="Shablon qidirish…" value={q} onChange={(e) => setQ(e.target.value)} />
      {items.map((t) => (
        <div key={t.id} className="kc-template-card">
          <button type="button" className="kc-template-use" onClick={() => onUse(t)} disabled={readOnly}>
            <span className="d-block f-w-600 f-s-13">{t.title}{t.shortcut ? <span className="text-secondary f-w-400 ms-1">/{t.shortcut}</span> : null}</span>
            <span className="d-block f-s-12 text-secondary kc-clamp-2">{t.body}</span>
          </button>
          {!readOnly ? <button type="button" className="btn btn-link p-0 text-secondary" onClick={() => onEdit(t)} title="Tahrirlash"><i className="ti ti-pencil"></i></button> : null}
        </div>
      ))}
      {items.length === 0 ? <div className="f-s-12 text-secondary">Shablon topilmadi.</div> : null}
    </div>
  );
}

function TemplateModal({ show, template, defaultAudience, onClose, onSaved, urls }: {
  show: boolean;
  template: Template | null;
  defaultAudience: 'customer' | 'shop';
  onClose: () => void;
  onSaved: (items: Template[]) => void;
  urls: InboxProps['urls'];
}) {
  const [form, setForm] = useState({ title: '', body: '', audience: 'all' as Template['audience'], category: '', shortcut: '' });
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState('');

  useEffect(() => {
    if (!show) return;
    setErr('');
    setForm(template
      ? { title: template.title, body: template.body, audience: template.audience, category: template.category || '', shortcut: template.shortcut || '' }
      : { title: '', body: '', audience: defaultAudience, category: '', shortcut: '' });
  }, [show, template, defaultAudience]);

  const save = async () => {
    setBusy(true);
    setErr('');
    try {
      const url = template ? `${urls.templates}/${template.id}` : urls.templates;
      const res = await apiJson<{ templates: Template[] }>(url, { method: template ? 'PUT' : 'POST', body: { ...form, shortcut: form.shortcut || null, category: form.category || null } });
      onSaved(res.templates);
    } catch (e) {
      setErr((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const remove = async () => {
    if (!template || !window.confirm('Shablon o‘chirilsinmi?')) return;
    setBusy(true);
    try {
      const res = await apiJson<{ templates: Template[] }>(`${urls.templates}/${template.id}`, { method: 'DELETE' });
      onSaved(res.templates);
    } catch (e) {
      setErr((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal show={show} onHide={onClose} centered>
      <Modal.Header closeButton><Modal.Title>{template ? 'Shablonni tahrirlash' : 'Yangi shablon'}</Modal.Title></Modal.Header>
      <Modal.Body>
        {err ? <div className="alert alert-light-danger py-2 f-s-13">{err}</div> : null}
        <div className="mb-3">
          <label className="form-label">Nomi</label>
          <input className="form-control" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} maxLength={120} />
        </div>
        <div className="mb-3">
          <label className="form-label">Matn</label>
          <textarea className="form-control" rows={5} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} maxLength={4000} />
          <div className="form-text">{'{name}'} — mijoz ismi, {'{agent}'} — sizning ismingiz.</div>
        </div>
        <div className="row g-2">
          <div className="col-sm-4">
            <label className="form-label">Kimlar uchun</label>
            <select className="form-select" value={form.audience} onChange={(e) => setForm({ ...form, audience: e.target.value as Template['audience'] })}>
              <option value="all">Hammasi</option>
              <option value="customer">Mijozlar</option>
              <option value="shop">Do‘konlar</option>
            </select>
          </div>
          <div className="col-sm-4">
            <label className="form-label">Qisqa buyruq</label>
            <input className="form-control" placeholder="salom" value={form.shortcut} onChange={(e) => setForm({ ...form, shortcut: e.target.value.replace(/[^\p{L}\p{N}_-]/gu, '') })} maxLength={32} />
          </div>
          <div className="col-sm-4">
            <label className="form-label">Toifa</label>
            <input className="form-control" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} maxLength={40} />
          </div>
        </div>
      </Modal.Body>
      <Modal.Footer className="d-flex">
        {template ? <button type="button" className="btn btn-light-danger me-auto" onClick={remove} disabled={busy}>O‘chirish</button> : null}
        <button type="button" className="btn btn-light-secondary" onClick={onClose}>Bekor qilish</button>
        <button type="button" className="btn btn-primary" onClick={save} disabled={busy || !form.title.trim() || !form.body.trim()}>
          {busy ? <span className="spinner-border spinner-border-sm"></span> : 'Saqlash'}
        </button>
      </Modal.Footer>
    </Modal>
  );
}

