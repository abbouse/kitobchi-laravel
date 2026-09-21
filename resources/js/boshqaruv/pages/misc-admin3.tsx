import { FormEvent, useEffect, useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

type TranslateLocale = 'ru' | 'en' | 'ja';
type PushLocale = 'uz' | TranslateLocale;
const translateLocaleLabels: Record<TranslateLocale, string> = { ru: 'RU', en: 'EN', ja: 'JA' };
const pushLocaleLabels: Record<PushLocale, string> = { uz: "O'zbek", ru: 'Русский', en: 'English', ja: '日本語' };
const pushLocales: PushLocale[] = ['uz', 'ru', 'en', 'ja'];
const getCsrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';

function BannerImageHint() {
  const [open, setOpen] = useState(false);

  return (
    <span className="position-relative d-inline-block" style={{ verticalAlign: 'middle' }}>
      <button
    type="button"
    className="btn btn-link p-0 ms-1 text-muted lh-1"
    aria-label="Banner rasmi bo'yicha tavsiya"
    onClick={(event) => {
     event.preventDefault();
     event.stopPropagation();
     setOpen((value) => !value);
    }}
   >
        <i className="ti ti-help"></i>
      </button>
      {open ? (
        <span
          className="shadow position-absolute w-310 text-white b-r-15 f-s-12 f-w-400 cursor-pointer"
          onClick={(event) => {
            event.preventDefault();
            event.stopPropagation();
            setOpen(false);
          }}
          style={{ zIndex: 80, top: 24, left: -96, background: 'rgba(var(--primary), 1)', padding: '12px 14px', lineHeight: 1.55, textAlign: 'left', whiteSpace: 'normal' }}
        >
          <strong className="d-block mb-1">Banner rasmi uchun tavsiya</strong>
          O'lcham: <strong>1440 × 320 px</strong> (nisbat <strong>4.5:1</strong>). Aynan shu nisbatdagi rasm barcha qurilmada <strong>qirqilmasdan</strong> joylashadi.
          <span className="d-block mt-2">
            Boshqa nisbatdagi rasm <b>cover</b> qilib kesiladi. Shunda muhim narsalar (matn, logo, mahsulot, yuz) chetlardan uzoqroq tursin:
            <span className="d-block">• chap va o'ngdan <strong>≥ 120 px</strong></span>
            <span className="d-block">• yuqori va pastdan <strong>≥ 30 px</strong></span>
            (markazdagi ~1200 × 260 px maydonda).
          </span>
          <span className="d-block mt-2 text-white" style={{ opacity: .72 }}>
            Fonni to'liq chetgacha to'ldiring. Format WebP/JPG/PNG, 300–600 KB.
          </span>
        </span>
      ) : null}
    </span>
  );
}

// ===== REELS =====
export function Reels() {
  const { reels = [] } = usePage<{
    reels?: Array<{ id: number; title: string; description?: string; order?: number; status: string; items: number; createUrl?: string; updateUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [selected, setSelected] = useState<(typeof reels)[0] | null>(null);
  const [editing, setEditing] = useState<(typeof reels)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const createUrl = reels[0]?.createUrl || '/boshqaruv/reels';

  const destroy = (reel: (typeof reels)[0]) => {
    if (!reel.destroyUrl || !confirm(`${reel.title} reelini o'chirasizmi?`)) return;
    router.delete(reel.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // BUG TUZATILDI (2026-09): bu yerda ilgari boshqa (push-xabar)
    // komponentidan noto'g'ri ko'chirilgan tekshiruv turgan edi —
    // `pushText`/`setActiveLocale` bu komponentda UMUMAN mavjud emas
    // (aniqlanmagan), shu sabab reel qo'shish/tahrirlash formasini
    // yuborishning O'ZI `ReferenceError` bilan qulab tushar edi va
    // funksiya butunlay ishlamas edi. Reel formasi uchun tegishli
    // tekshiruv shart emas — sarlavha va tartib maydonlari allaqachon
    // HTML `required` orqali tekshiriladi.
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Reels / Shorts</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {reels.length} ta reel</p></div>
        <button className="btn btn-primary" onClick={() => { setEditing(null); setShowForm(true); }}><i className="ti ti-plus me-1"></i>Reel qo'shish</button>
      </div>
      <div className="row">
        {reels.map(reel => (
          <div className="col-xl-4 col-md-6" key={reel.id}>
            <div className="card">
              <div className="card-body">
                <div className="d-flex justify-content-between mb-2">
                  <div className="f-w-600">{reel.title}</div>
                  <span className={`badge ${reel.status === 'Active' ? 'text-light-success' : 'text-light-secondary'} f-s-9`}>{reel.status}</span>
                </div>
                <div className="d-flex gap-3 f-s-13 mb-2">
                  <span>{reel.order || 0} tartib</span>
                  <span>{reel.items} ta mahsulot</span>
                </div>
                <p className="text-muted f-s-13">{reel.description || '—'}</p>
                <div className="d-flex gap-2">
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setSelected(reel)}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => { setEditing(reel); setShowForm(true); }}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(reel)}><i className="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Tartib</small><div>{selected?.order || 0}</div></div>
            <div className="col-6"><small className="text-muted">Elementlar</small><div>{selected?.items || 0}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.description || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editing ? 'Reelni tahrirlash' : "Reel qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Sarlavha</Form.Label><Form.Control name="title" required defaultValue={editing?.title || ''} className="mb-3" />
            <Form.Label>Tartib</Form.Label><Form.Control name="order" type="number" min={0} required defaultValue={editing?.order ?? (reels.length + 1)} className="mb-3" />
            <Form.Label>Tavsif</Form.Label><Form.Control as="textarea" rows={4} name="description" defaultValue={editing?.description || ''} />
          </Modal.Body>
          <Modal.Footer><Button variant="light-secondary" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== MARKET YANGILIKLARI =====
export function MarketNews() {
  type NewsRow = {
    id: number;
    title: string;
    titleUz?: string | null;
    titleRu?: string | null;
    titleEn?: string | null;
    titleJa?: string | null;
    description?: string;
    descriptionUz?: string | null;
    descriptionRu?: string | null;
    descriptionEn?: string | null;
    descriptionJa?: string | null;
    align?: string;
    status: string;
    active?: boolean;
    action?: string;
    actionType?: string;
    actionId?: number | null;
    image?: string | null;
    date?: string;
    createUrl?: string;
    updateUrl?: string;
    toggleUrl?: string;
    destroyUrl?: string;
  };

  type NewsForm = {
    titleUz: string;
    titleRu: string;
    titleEn: string;
    titleJa: string;
    descriptionUz: string;
    descriptionRu: string;
    descriptionEn: string;
    descriptionJa: string;
    align: 'top' | 'center';
    action: 'to_bottomsheet' | 'to_shop' | 'to_product' | 'to_collection';
    actionId: string;
    status: boolean;
  };

  const defaultForm: NewsForm = {
    titleUz: '',
    titleRu: '',
    titleEn: '',
    titleJa: '',
    descriptionUz: '',
    descriptionRu: '',
    descriptionEn: '',
    descriptionJa: '',
    align: 'center',
    action: 'to_bottomsheet',
    actionId: '',
    status: true,
  };

  const { news = [], errors = {}, translateUrl = '/boshqaruv/content/translate' } = usePage<{
    news?: NewsRow[];
    translateUrl?: string;
    errors?: Record<string, string>;
  }>().props;
  const [selected, setSelected] = useState<NewsRow | null>(null);
  const [editing, setEditing] = useState<NewsRow | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<NewsForm>(defaultForm);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [translatingLocales, setTranslatingLocales] = useState<TranslateLocale[]>([]);
  const createUrl = news[0]?.createUrl || '/boshqaruv/market-news';

  const hydrateForm = (item?: NewsRow | null) => {
    if (!item) {
      setForm(defaultForm);
      setImageFile(null);
      return;
    }

    setForm({
      titleUz: item.titleUz || item.title || '',
      titleRu: item.titleRu || '',
      titleEn: item.titleEn || '',
      titleJa: item.titleJa || '',
      descriptionUz: item.descriptionUz || item.description || '',
      descriptionRu: item.descriptionRu || '',
      descriptionEn: item.descriptionEn || '',
      descriptionJa: item.descriptionJa || '',
      align: item.align === 'top' ? 'top' : 'center',
      action: (item.actionType as NewsForm['action']) || 'to_bottomsheet',
      actionId: item.actionId ? String(item.actionId) : '',
      status: item.status === 'Active',
    });
    setImageFile(null);
  };

  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      setShowForm(true);
    }
  }, [errors]);

  useEffect(() => {
    if (showForm) {
      hydrateForm(editing);
    }
  }, [showForm, editing]);

  const toggle = (item: NewsRow) => item.toggleUrl && router.patch(item.toggleUrl, {}, { preserveScroll: true });
  const destroy = (item: NewsRow) => {
    if (!item.destroyUrl || !confirm(`${item.title} yangiligi o'chirilsinmi?`)) return;
    router.delete(item.destroyUrl, { preserveScroll: true });
  };

  const translateFromUz = async (targetLocales: TranslateLocale[]) => {
    const texts: Record<string, string> = {};

    if (form.titleUz.trim()) texts.title = form.titleUz.trim();
    if (form.descriptionUz.trim()) texts.description = form.descriptionUz.trim();

    if (Object.keys(texts).length === 0) {
      window.alert("Avval UZ maydonlarini to'ldiring.");
      return;
    }

    setTranslatingLocales(targetLocales);

    try {
      const response = await fetch(translateUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          source_locale: 'uz',
          target_locales: targetLocales,
          texts,
        }),
      });

      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.status !== 'success') {
        throw new Error(payload?.message || payload?.errors?.texts?.[0] || 'AI tarjima xatosi');
      }

      setForm((prev) => {
        const next = { ...prev };

        targetLocales.forEach((locale) => {
          const translated = payload.data?.[locale] || {};

          if (translated.title) {
            (next as any)[`title${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = translated.title;
          }
          if (translated.description) {
            (next as any)[`description${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = translated.description;
          }
        });

        return next;
      });
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'AI tarjima vaqtincha ishlamadi.');
    } finally {
      setTranslatingLocales([]);
    }
  };

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData();
    data.append('title_uz', form.titleUz);
    data.append('title_ru', form.titleRu);
    data.append('title_en', form.titleEn);
    data.append('title_ja', form.titleJa);
    data.append('description_uz', form.descriptionUz);
    data.append('description_ru', form.descriptionRu);
    data.append('description_en', form.descriptionEn);
    data.append('description_ja', form.descriptionJa);
    data.append('align', form.align);
    data.append('action', form.action);
    data.append('status', form.status ? '1' : '0');

    if (form.actionId.trim()) {
      data.append('action_id', form.actionId.trim());
    }

    if (imageFile) {
      data.append('imgUrl', imageFile);
    }

    const options = {
      preserveScroll: true,
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        setEditing(null);
        setShowForm(false);
        setForm(defaultForm);
        setImageFile(null);
      },
      onError: () => { setShowForm(true); },
    };
    if (editing?.updateUrl) {
      data.append('_method', 'put');
      router.post(editing.updateUrl, data, options);
      return;
    }

    router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3"><div><h4 className="main-title mb-0">Market yangiliklari</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {news.length} ta yangilik</p></div>
        <button className="btn btn-primary" onClick={() => { setEditing(null); setShowForm(true); }}><i className="ti ti-plus me-1"></i>Qo'shish</button>
        </div>
      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Sarlavha</th><th>Action</th><th>Sana</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>{news.map(item => (
              <tr key={item.id}>
                <td className="f-w-600 text-primary">#{item.id}</td>
                <td className="f-w-600">{item.title}</td>
                <td><span className="badge text-light-secondary">{item.action || 'Yangilik'}</span></td>
                <td className="text-muted">{item.date || '—'}</td>
                <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={item.status === 'Active'} onChange={() => toggle(item)} /></div></td>
                <td>
                  <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => setSelected(item)}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1" onClick={() => { setEditing(item); setShowForm(true); }}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(item)}><i className="ti ti-trash"></i></button>
                </td>
              </tr>
            ))}</tbody>
          </table></div>
        </div>
</div>
      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          {selected?.image ? <img className="w-100 b-r-22 mb-3 object-fit-cover" style={{ maxHeight: 220 }} src={selected.image} alt={selected.title} /> : null}
          <p className="text-muted">{selected?.description || '—'}</p>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editing ? 'Yangilikni tahrirlash' : "Yangilik qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            {Object.keys(errors).length > 0 ? (
              <div className="alert alert-light-danger">
                <div className="f-w-600 mb-1">Saqlashda xatolik bor.</div>
                <ul className="mb-0 ps-3">
                  {Object.entries(errors).map(([key, value]) => (
                    <li key={key}>{value}</li>
                  ))}
                </ul>
              </div>
            ) : null}
            <div className="row g-3">
              <div className="col-12">
                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 b-r-15 b-1-light px-3 py-2">
                  <div>
                    <div className="f-w-600">UZ matndan AI tarjima</div>
                    <div className="f-s-13 text-muted">Sarlavha va tavsif RU, EN, JA maydonlariga to'ldiriladi.</div>
                  </div>
                  <div className="d-flex flex-wrap gap-2">
                    {(['ru', 'en', 'ja'] as TranslateLocale[]).map((locale) => (
                      <button
                        key={locale}
                        type="button"
                        className="btn btn-sm btn-light-secondary"
                        disabled={translatingLocales.length > 0}
                        onClick={() => translateFromUz([locale])}
                      >
                        {translatingLocales.includes(locale) ? '...' : translateLocaleLabels[locale]}
                      </button>
                    ))}
                    <button
                      type="button"
                      className="btn btn-sm btn-primary"
                      disabled={translatingLocales.length > 0}
                      onClick={() => translateFromUz(['ru', 'en', 'ja'])}
                    >
                      {translatingLocales.length > 0 ? 'Tarjima...' : 'Barchasi'}
                    </button>
                  </div>
                </div>
              </div>
              <div className="col-md-6"><Form.Label>Sarlavha (UZ)</Form.Label><Form.Control required value={form.titleUz} onChange={(event) => setForm((prev) => ({ ...prev, titleUz: event.target.value }))} /></div>
              <div className="col-md-6"><Form.Label>Joylashuv</Form.Label><Form.Select value={form.align} onChange={(event) => setForm((prev) => ({ ...prev, align: event.target.value as NewsForm['align'] }))}><option value="center">Center</option><option value="top">Top</option></Form.Select></div>
              <div className="col-md-4"><Form.Label>Sarlavha (RU)</Form.Label><Form.Control value={form.titleRu} onChange={(event) => setForm((prev) => ({ ...prev, titleRu: event.target.value }))} /></div>
              <div className="col-md-4"><Form.Label>Sarlavha (EN)</Form.Label><Form.Control value={form.titleEn} onChange={(event) => setForm((prev) => ({ ...prev, titleEn: event.target.value }))} /></div>
              <div className="col-md-4"><Form.Label>Sarlavha (JA)</Form.Label><Form.Control value={form.titleJa} onChange={(event) => setForm((prev) => ({ ...prev, titleJa: event.target.value }))} /></div>
              <div className="col-md-6"><Form.Label>Action</Form.Label><Form.Select value={form.action} onChange={(event) => setForm((prev) => ({ ...prev, action: event.target.value as NewsForm['action'] }))}><option value="to_bottomsheet">Bottomsheet</option><option value="to_shop">Do'konga o'tish</option><option value="to_product">Mahsulotga o'tish</option><option value="to_collection">Muayyan to'plam</option></Form.Select></div>
              <div className="col-md-6"><Form.Label>Action ID</Form.Label><Form.Control type="number" min={1} placeholder="Shop / mahsulot / to'plam ID" value={form.actionId} onChange={(event) => setForm((prev) => ({ ...prev, actionId: event.target.value }))} /><div className="form-text">Bottomsheet uchun bo'sh qoldiring.</div></div>
              <div className="col-12">
                <Form.Label>
                  Rasm <BannerImageHint />
                </Form.Label>
                <Form.Control type="file" accept="image/*" onChange={(event) => setImageFile(event.target.files?.[0] || null)} />
                <div className="form-text">
                  Tavsiya: <b>1440 × 320 px</b> (4.5:1) — aynan shu nisbat qirqilmasdan joylashadi. Boshqa nisbatda muhim matn/logoni chetlardan <b>chap-o'ng ≥120 px, yuqori-past ≥30 px</b> ichkarida qoldiring. Fonni to'liq to'ldiring.
                  {editing?.image ? ' Yangi rasm tanlanmasa, hozirgisi saqlanadi.' : ''}
                </div>
              </div>
              <div className="col-md-6"><Form.Label>Tavsif (UZ)</Form.Label><Form.Control as="textarea" rows={4} value={form.descriptionUz} onChange={(event) => setForm((prev) => ({ ...prev, descriptionUz: event.target.value }))} /></div>
              <div className="col-md-6"><Form.Label>Tavsif (RU)</Form.Label><Form.Control as="textarea" rows={4} value={form.descriptionRu} onChange={(event) => setForm((prev) => ({ ...prev, descriptionRu: event.target.value }))} /></div>
              <div className="col-md-6"><Form.Label>Tavsif (EN)</Form.Label><Form.Control as="textarea" rows={4} value={form.descriptionEn} onChange={(event) => setForm((prev) => ({ ...prev, descriptionEn: event.target.value }))} /></div>
              <div className="col-md-6"><Form.Label>Tavsif (JA)</Form.Label><Form.Control as="textarea" rows={4} value={form.descriptionJa} onChange={(event) => setForm((prev) => ({ ...prev, descriptionJa: event.target.value }))} /></div>
              <div className="col-12"><Form.Check type="switch" label="Faol" checked={form.status} onChange={(event) => setForm((prev) => ({ ...prev, status: event.target.checked }))} /></div>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light-secondary" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== CHAT KUZATUV =====
export function ChatKuzatuv() {
  type Conversation = { id: number; kind: string; type?: string; user: string; phone?: string; agent: string; messages: number; lastMsg: string; date?: string; dataUrl?: string };
  type Detail = {
    profile: Record<string, string | number | null | undefined>;
    messages: Array<Record<string, string | number | boolean | null | undefined>>;
    otherConversations?: Array<{ id: number; kind: string; type?: string; agent: string; messages: number; lastMsg: string; date?: string; dataUrl?: string }>;
  };
  const { conversations = [], conversationCounts = {}, conversationPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, conversationFilters = {} } = usePage<{ conversations?: Conversation[]; conversationCounts?: Record<string, number>; conversationPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; conversationFilters?: { tab?: string; search?: string } }>().props;
  const [tab, setTab] = useState(conversationFilters.tab || 'all');
  const [search, setSearch] = useState(conversationFilters.search || '');
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<Conversation | null>(null);
  const [detail, setDetail] = useState<Detail | null>(null);
  const [loading, setLoading] = useState(false);
  const [otherLimit, setOtherLimit] = useState(4);
  const loadConversations = (page = 1, activeTab = tab, term = search) => router.get('/boshqaruv/chat', { chat_page: page, chat_tab: activeTab, chat_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  const open = async (conversation: Conversation) => {
    if (!conversation.dataUrl) return;
    setSelected(conversation); setShow(true); setLoading(true); setOtherLimit(4);
    try {
      const response = await fetch(conversation.dataUrl, { headers: { Accept: 'application/json' } });
      setDetail(response.ok ? await response.json() : null);
    } finally { setLoading(false); }
  };

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const focusChatId = Number(new URLSearchParams(window.location.search).get('focus_chat') || 0);
    if (!focusChatId) return;

    const existing = conversations.find((conversation) => conversation.id === focusChatId);

    if (existing) {
      open(existing);
      return;
    }

    open({
      id: focusChatId,
      kind: 'unknown',
      user: 'Foydalanuvchi',
      agent: 'Chat',
      messages: 0,
      lastMsg: 'Xabarlar yuklanmoqda...',
      dataUrl: `/boshqaruv/chat/${focusChatId}/data`,
    });
  }, []);

  const chatKindLabel = (kind?: string) => {
    switch (kind) {
      case 'seller':
        return 'Do‘kon bilan';
      case 'user':
        return 'Foydalanuvchi bilan';
      default:
        return 'Suhbat';
    }
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3"><div><h4 className="main-title mb-0">Chat kuzatuv</h4><PageCrumbs /><p className="mb-0 text-secondary">Foydalanuvchi va seller suhbatlarini real vaqt kontekstida tekshirish</p></div></div>
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap"><div className="nav nav-tabs app-tabs-primary flex-wrap">{[['all', 'Barchasi'], ['user', 'User chat'], ['seller', 'Seller chat']].map(([key, label]) => <div key={key} className="nav-item"><button
            className={`nav-link ${tab === key ? 'active' : ''}`}
            onClick={() => { setTab(key); loadConversations(1, key); }}>{label}<span className="badge text-light-secondary ms-2">{conversationCounts[key] || 0}</span></button></div>)}</div><form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadConversations(); }}><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="User, telefon yoki seller" /><button className="btn btn-sm btn-outline-secondary"><i className="ti ti-search"></i></button></form></div>
        <div className="card-body">

          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Qabul qiluvchi</th><th>Turi</th><th>Xabarlar</th><th>Oxirgi</th><th>Amallar</th></tr></thead>
            <tbody>{conversations.map(c => (
              <tr key={c.id}>
                <td className="f-w-600 text-primary">#{c.id}</td>
                <td><div className="f-w-600">{c.user}</div><small className="text-muted">{c.phone || '—'}</small></td>
                <td>{c.agent}</td>
                <td><span className={`badge ${c.kind === 'seller' ? 'text-light-primary' : 'text-light-info'}`}>{chatKindLabel(c.kind)}</span></td>
                <td>{c.messages}</td>
                <td className="text-muted">{c.lastMsg}<br /><small>{c.date || '—'}</small></td>
                <td><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => open(c)}><i className="ti ti-eye"></i></button></td>
              </tr>
            ))}{conversationPagination.total === 0 ? <tr><td colSpan={7} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Suhbat topilmadi</td></tr> : null}</tbody>
          </table></div><PaginationControls {...conversationPagination} onPageChange={(page) => loadConversations(page)} />
        </div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered size="xl">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Suhbat #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loading ? <div className="text-muted text-center py-5">Yuklanmoqda...</div> : !detail ? <div className="text-muted text-center py-5">Xabarlar yuklanmadi</div> : (
            <div className="row">
              <div className="col-xl-8">
                <div className="card"><div className="card-body">
                    <div className="d-flex flex-wrap align-items-start justify-content-between gap-3">
                      <div>
                        <div className="f-w-600 f-s-20">{String(detail.profile.user || 'Foydalanuvchi')} — {String(detail.profile.agent || 'Suhbatdosh')}</div>
                        <div className="text-muted f-s-13 mt-1">{String(detail.profile.kindLabel || chatKindLabel(String(detail.profile.kind || '')))} · {String(detail.profile.type || 'chat')}</div>
                      </div>
                      <div className="d-flex flex-wrap gap-2">
                        <span className="badge text-light-info">{String(detail.profile.messagesCount || detail.messages.length)} ta xabar</span>
                        {detail.profile.orderId ? <span className="badge text-light-secondary">Buyurtma #{String(detail.profile.orderId)}</span> : null}
                        <span className="badge text-light-secondary">Oxirgi: {String(detail.profile.lastMessageAt || '—')}</span>
                      </div>
                    </div>
                    <div className="row g-3 mt-1">
                      <div className="col-md-6">
                        <small className="text-muted d-block">Mijoz</small>
                        <div className="f-w-600">{String(detail.profile.user || '—')}</div>
                        <div className="text-muted f-s-13">{String(detail.profile.phone || 'Telefon yo‘q')}</div>
                      </div>
                      <div className="col-md-6">
                        <small className="text-muted d-block">Suhbatdosh</small>
                        <div className="f-w-600">{String(detail.profile.agent || '—')}</div>
                        <div className="text-muted f-s-13">Ochilgan: {String(detail.profile.createdAt || '—')}</div>
                      </div>
                    </div>
                  </div></div>

                <div className="card"><div className="card-header d-flex align-items-center justify-content-between">
                    <h5 className="mb-0">Xabarlar oqimi</h5>
                    <span className="text-muted f-s-13">Eng ko‘pi bilan 200 ta so‘nggi xabar</span>
                  </div><div className="card-body">
                    <div className="d-grid gap-2">
                      {detail.messages.map((message) => (
                        <div className={`d-flex ${message.senderType === 'user' ? '' : 'justify-content-end'}`} key={String(message.id)}>
                          <div className={`b-r-15 b-1-light p-3 ${message.senderType === 'user' ? 'bg-white' : 'bg-light-subtle'}`} style={{ maxWidth: '86%' }}>
                            <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                              <span className="f-w-600">{String(message.senderLabel || message.senderType || 'Xabar')}</span>
                              <span className="text-muted f-s-13">{String(message.date || '—')}</span>
                              {message.edited ? <span className="badge text-light-secondary">Tahrirlangan</span> : null}
                              {message.read ? <span className="badge text-light-success">O‘qilgan</span> : null}
                              {message.reported ? <span className="badge text-light-danger">Shikoyat bor</span> : null}
                            </div>
                            <div style={{ whiteSpace: 'pre-line' }}>{String(message.message || '—')}</div>
                          </div>
                        </div>
                      ))}
                      {detail.messages.length === 0 ? <div className="text-muted">Xabar topilmadi</div> : null}
                    </div>
                  </div></div>
              </div>

              <div className="col-xl-4">
                <div className="card"><div className="card-header d-flex align-items-center justify-content-between">
                    <h5 className="mb-0">Userning boshqa yozishmalari</h5>
                    <span className="badge text-light-secondary">{detail.otherConversations?.length || 0} ta</span>
                  </div><div className="card-body">
                    {(detail.otherConversations || []).slice(0, otherLimit).map((conversation) => (
                      <button
             key={conversation.id}
             type="button"
             className="w-100 text-start b-1-light b-r-15 p-3 bg-white mb-2 cursor-pointer"
             onClick={() => open({
              id: conversation.id,
              kind: conversation.kind,
              type: conversation.type,
              user: String(detail.profile.user || 'Foydalanuvchi'),
              phone: String(detail.profile.phone || ''),
              agent: conversation.agent,
              messages: conversation.messages,
              lastMsg: conversation.lastMsg,
              date: conversation.date,
              dataUrl: conversation.dataUrl,
             })}
            >
                        <div className="d-flex justify-content-between gap-2">
                          <div className="f-w-600">{conversation.agent}</div>
                          <span className={`badge ${conversation.kind === 'seller' ? 'text-light-primary' : 'text-light-info'}`}>{chatKindLabel(conversation.kind)}</span>
                        </div>
                        <div className="text-muted f-s-13 mt-1">{conversation.lastMsg}</div>
                        <div className="text-muted f-s-13 mt-2">{conversation.messages} ta xabar · {conversation.date || '—'}</div>
                      </button>
                    ))}
                    {(detail.otherConversations || []).length === 0 ? <div className="text-muted">Bu foydalanuvchining boshqa yozishmasi topilmadi.</div> : null}
                    {(detail.otherConversations || []).length > otherLimit ? (
                      <button className="btn btn-sm btn-light-secondary w-100 mt-2" onClick={() => setOtherLimit((limit) => limit + 4)}>
                        Yana ko‘rsatish
                      </button>
                    ) : null}
                  </div></div>
              </div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={() => setShow(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== PUSH BILDIRISHNOMALAR =====
export function PushNotifications() {
  const { notifications = [], translateUrl = '/boshqaruv/content/translate' } = usePage<{
    notifications?: Array<{ id: number; title: string; body?: string; localized?: Record<PushLocale, { title?: string | null; body?: string | null }>; who?: string; targetMode?: 'audience' | 'individual'; targetLabel?: string; source?: string; status: string; sentCount?: number; failedCount?: number; date?: string; createUrl?: string; resendUrl?: string; destroyUrl?: string }>;
    translateUrl?: string;
  }>().props;
  const [showForm, setShowForm] = useState(false);
  const [targetMode, setTargetMode] = useState<'audience' | 'individual'>('individual');
  const [audience, setAudience] = useState('users');
  const [activeLocale, setActiveLocale] = useState<PushLocale>('uz');
  const [pushText, setPushText] = useState<Record<PushLocale, { title: string; body: string }>>({
    uz: { title: '', body: '' },
    ru: { title: '', body: '' },
    en: { title: '', body: '' },
    ja: { title: '', body: '' },
  });
  const [translating, setTranslating] = useState(false);
  const createUrl = notifications[0]?.createUrl || '/boshqaruv/push';
  const updatePushText = (locale: PushLocale, field: 'title' | 'body', value: string) => {
    setPushText((prev) => ({ ...prev, [locale]: { ...prev[locale], [field]: value } }));
  };
  const resetForm = () => {
    setTargetMode('individual');
    setAudience('users');
    setActiveLocale('uz');
    setPushText({
      uz: { title: '', body: '' },
      ru: { title: '', body: '' },
      en: { title: '', body: '' },
      ja: { title: '', body: '' },
    });
  };
  const translatePush = async () => {
    const texts: Record<string, string> = {};
    if (pushText.uz.title.trim()) texts.title = pushText.uz.title.trim();
    if (pushText.uz.body.trim()) texts.body = pushText.uz.body.trim();

    if (!texts.title || !texts.body) {
      window.alert("AI tarjima uchun avval o'zbekcha sarlavha va matnni yozing.");
      return;
    }

    setTranslating(true);
    try {
      const response = await fetch(translateUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          source_locale: 'uz',
          target_locales: ['ru', 'en', 'ja'],
          texts,
        }),
      });
      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.status !== 'success') {
        throw new Error(payload?.message || payload?.errors?.texts?.[0] || 'AI tarjima xatosi');
      }

      setPushText((prev) => {
        const next = { ...prev };
        (['ru', 'en', 'ja'] as TranslateLocale[]).forEach((locale) => {
          next[locale] = {
            title: payload.data?.[locale]?.title || prev[locale].title,
            body: payload.data?.[locale]?.body || prev[locale].body,
          };
        });
        return next;
      });
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'AI tarjima xatosi');
    } finally {
      setTranslating(false);
    }
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!pushText.uz.title.trim() || !pushText.uz.body.trim()) {
      setActiveLocale('uz');
      window.alert("Push yuborish uchun o'zbekcha sarlavha va matn majburiy.");
      return;
    }

    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    pushLocales.forEach((locale) => {
      data[`name_${locale}`] = pushText[locale].title;
      data[`description_${locale}`] = pushText[locale].body;
    });
    data.name = pushText.uz.title;
    data.description = pushText.uz.body;

    router.post(createUrl, data, {
      preserveScroll: true,
      onSuccess: () => {
        setShowForm(false);
        resetForm();
      },
    });
  };
  const destroy = (notification: (typeof notifications)[0]) => {
    if (!notification.destroyUrl || !confirm(`#${notification.id} push o'chirilsinmi?`)) return;
    router.delete(notification.destroyUrl, { preserveScroll: true });
  };
  const resend = (notification: (typeof notifications)[0]) => {
    if (!notification.resendUrl || !confirm(`#${notification.id} push dublikat qilinib qayta yuborilsinmi?`)) return;
    router.post(notification.resendUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Push bildirishnomalar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {notifications.length} ta yuborilgan</p></div>
        <button className="btn btn-primary" onClick={() => { resetForm(); setShowForm(true); }}><i className="ti ti-send me-1"></i>Push yaratish</button>
      </div>
      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Sarlavha</th><th>Matn</th><th>Target</th><th>Status</th><th>Sana</th><th>Amallar</th></tr></thead>
            <tbody>{notifications.map(notification => (
              <tr key={notification.id}>
                <td className="f-w-600 text-primary">#{notification.id}</td>
                <td>
                  <div className="f-w-600">{notification.title}</div>
                  <div className="d-flex flex-wrap gap-1 mt-1">
                    {pushLocales.map((locale) => {
                      const filled = locale === 'uz' || Boolean(notification.localized?.[locale]?.title || notification.localized?.[locale]?.body);
                      return (
                        <span key={locale} className={`badge ${filled ? 'text-light-success' : 'text-light-secondary'} f-s-9`}>
                          {locale.toUpperCase()}
                        </span>
                      );
                    })}
                  </div>
                </td>
                <td className="text-muted">{notification.body || '—'}</td>
                <td>
                  <span className={`badge ${notification.targetMode === 'individual' ? 'text-light-success' : 'text-light-secondary'}`}>
                    {notification.targetLabel || notification.who || 'all'}
                  </span>
                </td>
                <td>
                  <span className={`badge ${notification.status === 'Xatolik' ? 'text-light-danger' : notification.status === 'Yuborildi' ? 'text-light-success' : 'text-light-secondary'} f-s-9`}>
                    {notification.status}
                  </span>
                  {notification.status === 'Yuborildi' ? <div className="text-muted mt-1 f-s-10">{notification.sentCount || 0} qurilma</div> : null}
                </td>
                <td className="text-muted">{notification.date}</td>
                <td>
                  <div className="d-flex gap-1">
                    <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => resend(notification)} title="Dublikat qilib qayta yuborish">
                      <i className="ti ti-repeat"></i>
                    </button>
                    <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(notification)} title="O'chirish">
                      <i className="ti ti-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            ))}</tbody>
          </table></div>
        </div>
</div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Push bildirishnoma</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
              <div className="nav nav-tabs app-tabs-primary flex-wrap p-1">
                {pushLocales.map((locale) => (
                  <div key={locale} className="nav-item"><button
                      type="button"
                      className={`nav-link ${activeLocale === locale ? 'active' : ''}`}
                      onClick={() => setActiveLocale(locale)}>
                      {pushLocaleLabels[locale]}
                    </button></div>
                ))}
              </div>
              <Button
                type="button"
                variant="light-secondary"
                className="b-1-light"
                disabled={translating}
                onClick={translatePush}
              >
                <i className="ti ti-sparkles me-1"></i>{translating ? 'Tarjima qilinyapti...' : 'AI tarjima'}
              </Button>
            </div>
            <div className="alert alert-border-secondary f-s-13 mb-3">
              Avval o'zbekcha matnni yozing, AI tarjima ru/en/ja maydonlarini to'ldiradi. Xohlasangiz har bir tilni alohida qo'lda tahrirlashingiz mumkin.
            </div>
            <Form.Label>Sarlavha ({pushLocaleLabels[activeLocale]})</Form.Label>
            <Form.Control
              required={activeLocale === 'uz'}
              value={pushText[activeLocale].title}
              onChange={(event) => updatePushText(activeLocale, 'title', event.target.value)}
              className="mb-3"
              maxLength={255}
              placeholder={activeLocale === 'uz' ? 'Masalan: Yangi chegirmalar boshlandi' : `${pushLocaleLabels[activeLocale]} sarlavha`}
            />
            <Form.Label>Matn ({pushLocaleLabels[activeLocale]})</Form.Label>
            <Form.Control
              as="textarea"
              rows={4}
              required={activeLocale === 'uz'}
              value={pushText[activeLocale].body}
              onChange={(event) => updatePushText(activeLocale, 'body', event.target.value)}
              className="mb-3"
              maxLength={1000}
              placeholder={activeLocale === 'uz' ? 'Push matnini yozing...' : `${pushLocaleLabels[activeLocale]} matn`}
            />
            <Form.Label>Ilova auditoriyasi</Form.Label>
            <Form.Select name="who" required value={audience} onChange={(event) => setAudience(event.target.value)} className="mb-3">
              <option value="users">Foydalanuvchilar</option>
              <option value="business">Sellerlar</option>
              <option value="courier">Kuryerlar</option>
            </Form.Select>

            <Form.Label>Qabul qiluvchilar</Form.Label>
            <div className="d-flex gap-2 mb-3">
              <Button
                type="button"
                variant={targetMode === 'individual' ? 'dark' : 'light'}
                className="flex-fill"
                onClick={() => setTargetMode('individual')}
              >
                <i className="ti ti-user me-1"></i>Bitta qabul qiluvchi
              </Button>
              <Button
                type="button"
                variant={targetMode === 'audience' ? 'dark' : 'light'}
                className="flex-fill"
                onClick={() => setTargetMode('audience')}
              >
                <i className="ti ti-users me-1"></i>Butun auditoriya
              </Button>
            </div>
            <input type="hidden" name="target_mode" value={targetMode} />

            {targetMode === 'individual' ? (
              <>
                <Form.Label>Qabul qiluvchi</Form.Label>
                <Form.Control
                  name="recipient"
                  required
                  placeholder={audience === 'users' ? 'ID, telefon, email yoki username' : 'ID yoki telefon raqami'}
                />
                <Form.Text className="text-muted">Push faqat topilgan akkauntning faol qurilmalariga yuboriladi.</Form.Text>
              </>
            ) : (
              <div className="alert alert-light-warning mb-0 py-2 f-s-13">
                Bu xabar tanlangan ilovaning barcha faol qurilmalariga yuboriladi.
              </div>
            )}
          </Modal.Body>
          <Modal.Footer><Button variant="light-secondary" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary border-0">Yuborish</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
