import { FormEvent, useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

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
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Reels / Shorts</h1><p className="page-subtitle">Jami {reels.length} ta reel</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Reel qo'shish</button>
      </div>
      <div className="row g-3">
        {reels.map(reel => (
          <div className="col-xl-4 col-md-6" key={reel.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between mb-2">
                <div className="fw-bold">{reel.title}</div>
                <span className={`chip ${reel.status === 'Active' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{reel.status}</span>
              </div>
              <div className="d-flex gap-3 small mb-2">
                <span>{reel.order || 0} tartib</span>
                <span>{reel.items} ta mahsulot</span>
              </div>
              <p className="text-muted small">{reel.description || '—'}</p>
              <div className="d-flex gap-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelected(reel)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light" onClick={() => { setEditing(reel); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(reel)}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Tartib</small><div>{selected?.order || 0}</div></div>
            <div className="col-6"><small className="text-muted">Elementlar</small><div>{selected?.items || 0}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.description || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'Reelni tahrirlash' : "Reel qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Sarlavha</Form.Label><Form.Control name="title" required defaultValue={editing?.title || ''} className="mb-3" />
            <Form.Label>Tartib</Form.Label><Form.Control name="order" type="number" min={0} required defaultValue={editing?.order ?? (reels.length + 1)} className="mb-3" />
            <Form.Label>Tavsif</Form.Label><Form.Control as="textarea" rows={4} name="description" defaultValue={editing?.description || ''} />
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== MARKET YANGILIKLARI =====
export function MarketNews() {
  const { news = [] } = usePage<{
    news?: Array<{ id: number; title: string; description?: string; align?: string; status: string; active?: boolean; action?: string; actionType?: string; actionId?: number | null; image?: string | null; date?: string; createUrl?: string; updateUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [selected, setSelected] = useState<(typeof news)[0] | null>(null);
  const [editing, setEditing] = useState<(typeof news)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const createUrl = news[0]?.createUrl || '/boshqaruv/market-news';

  const toggle = (item: (typeof news)[0]) => item.toggleUrl && router.patch(item.toggleUrl, {}, { preserveScroll: true });
  const destroy = (item: (typeof news)[0]) => {
    if (!item.destroyUrl || !confirm(`${item.title} yangiligi o'chirilsinmi?`)) return;
    router.delete(item.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const options = { preserveScroll: true, forceFormData: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.post(editing.updateUrl, { ...Object.fromEntries(data.entries()), _method: 'put' }, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Market yangiliklari</h1><p className="page-subtitle">Jami {news.length} ta yangilik</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Qo'shish</button>
        </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Sarlavha</th><th>Action</th><th>Sana</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{news.map(item => (
            <tr key={item.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{item.id}</td>
              <td className="fw-semibold">{item.title}</td>
              <td><span className="chip chip-gray">{item.action || 'Yangilik'}</span></td>
              <td className="text-muted">{item.date || '—'}</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={item.status === 'Active'} onChange={() => toggle(item)} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(item)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setEditing(item); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(item)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          {selected?.image ? <img className="media-preview rounded mb-3" src={selected.image} alt={selected.title} /> : null}
          <p className="text-muted">{selected?.description || '—'}</p>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'Yangilikni tahrirlash' : "Yangilik qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="row g-3">
              <div className="col-md-8"><Form.Label>Sarlavha</Form.Label><Form.Control name="title" required defaultValue={editing?.title || ''} /></div>
              <div className="col-md-4"><Form.Label>Joylashuv</Form.Label><Form.Select name="align" defaultValue={editing?.align || 'center'}><option value="center">Center</option><option value="top">Top</option></Form.Select></div>
              <div className="col-md-6"><Form.Label>Action</Form.Label><Form.Select name="action" defaultValue={editing?.actionType || 'news'}><option value="news">Yangilik</option><option value="to_shop">Do'konga o'tish</option><option value="to_product">Mahsulotga o'tish</option></Form.Select></div>
              <div className="col-md-6"><Form.Label>Action ID</Form.Label><Form.Control name="action_id" type="number" min={1} defaultValue={editing?.actionId || ''} /></div>
              <div className="col-12"><Form.Label>Rasm</Form.Label><Form.Control name="imgUrl" type="file" accept="image/*" /></div>
              <div className="col-12"><Form.Label>Tavsif</Form.Label><Form.Control as="textarea" rows={4} name="description" defaultValue={editing?.description || ''} /></div>
              <div className="col-12"><Form.Check type="switch" name="status" value="1" label="Faol" defaultChecked={editing ? editing.status === 'Active' : true} /></div>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== CHAT KUZATUV =====
export function ChatKuzatuv() {
  type Conversation = { id: number; kind: string; type?: string; user: string; phone?: string; agent: string; messages: number; lastMsg: string; date?: string; dataUrl?: string };
  type Detail = { profile: Record<string, string | number | null | undefined>; messages: Array<Record<string, string | number | boolean | null | undefined>> };
  const { conversations = [], conversationCounts = {}, conversationPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, conversationFilters = {} } = usePage<{ conversations?: Conversation[]; conversationCounts?: Record<string, number>; conversationPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; conversationFilters?: { tab?: string; search?: string } }>().props;
  const [tab, setTab] = useState(conversationFilters.tab || 'all');
  const [search, setSearch] = useState(conversationFilters.search || '');
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<Conversation | null>(null);
  const [detail, setDetail] = useState<Detail | null>(null);
  const [loading, setLoading] = useState(false);
  const loadConversations = (page = 1, activeTab = tab, term = search) => router.get('/boshqaruv/chat', { chat_page: page, chat_tab: activeTab, chat_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  const open = async (conversation: Conversation) => {
    if (!conversation.dataUrl) return;
    setSelected(conversation); setShow(true); setLoading(true);
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

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Chat kuzatuv</h1><p className="page-subtitle">Foydalanuvchi va seller suhbatlarini real vaqt kontekstida tekshirish</p></div></div>
      <div className="card-panel">
        <div className="panel-head"><div className="d-flex flex-wrap gap-2">{[['all', 'Barchasi'], ['user', 'User chat'], ['seller', 'Seller chat']].map(([key, label]) => <button className={`btn btn-sm ${tab === key ? 'btn-primary-gradient' : 'btn-light'}`} key={key} onClick={() => { setTab(key); loadConversations(1, key); }}>{label}<span className="badge rounded-pill bg-light text-dark ms-2">{conversationCounts[key] || 0}</span></button>)}</div><form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadConversations(); }}><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="User, telefon yoki seller" /><button className="btn btn-sm btn-outline-secondary"><i className="bi bi-search"></i></button></form></div>
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Qabul qiluvchi</th><th>Turi</th><th>Xabarlar</th><th>Oxirgi</th><th>Amallar</th></tr></thead>
          <tbody>{conversations.map(c => (
            <tr key={c.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{c.id}</td>
              <td><div className="fw-semibold">{c.user}</div><small className="text-muted">{c.phone || '—'}</small></td>
              <td>{c.agent}</td>
              <td><span className={`chip ${c.kind === 'seller' ? 'chip-purple' : 'chip-info'}`}>{c.kind}</span></td>
              <td>{c.messages}</td>
              <td className="text-muted">{c.lastMsg}<br /><small>{c.date || '—'}</small></td>
              <td><button className="btn btn-sm btn-light" onClick={() => open(c)}><i className="bi bi-eye"></i></button></td>
            </tr>
          ))}{conversationPagination.total === 0 ? <tr><td colSpan={7} className="text-center text-muted py-5">Suhbat topilmadi</td></tr> : null}</tbody>
        </table></div><PaginationControls {...conversationPagination} onPageChange={(page) => loadConversations(page)} />
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Conversation #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="mb-3 p-3 rounded bg-light">
            <div className="fw-semibold mb-1">{selected?.user} — {selected?.agent}</div>
            <div className="text-muted small">{selected?.messages} ta xabar</div>
          </div>
          {loading ? <div className="text-muted text-center py-5">Yuklanmoqda...</div> : !detail ? <div className="text-muted text-center py-5">Xabarlar yuklanmadi</div> : <div>{detail.messages.map((message) => <div className={`d-flex mb-2 ${message.senderType === 'user' ? '' : 'justify-content-end'}`} key={String(message.id)}><div className="p-3 rounded border" style={{ maxWidth: '82%' }}><div>{String(message.message || '—')}</div><small className="text-muted">{String(message.senderType || 'user')} · {String(message.date || '—')}{message.reported ? ' · report bor' : ''}</small></div></div>)}{detail.messages.length === 0 ? <div className="text-muted">Xabar topilmadi</div> : null}</div>}
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== PUSH BILDIRISHNOMALAR =====
export function PushNotifications() {
  const { notifications = [] } = usePage<{
    notifications?: Array<{ id: number; title: string; body?: string; who?: string; status: string; date?: string; createUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [showForm, setShowForm] = useState(false);
  const createUrl = notifications[0]?.createUrl || '/boshqaruv/push';
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post(createUrl, Object.fromEntries(new FormData(event.currentTarget).entries()), { preserveScroll: true, onSuccess: () => setShowForm(false) });
  };
  const destroy = (notification: (typeof notifications)[0]) => {
    if (!notification.destroyUrl || !confirm(`#${notification.id} push o'chirilsinmi?`)) return;
    router.delete(notification.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Push bildirishnomalar</h1><p className="page-subtitle">Jami {notifications.length} ta yuborilgan</p></div>
        <button className="btn btn-primary-gradient" onClick={() => setShowForm(true)}><i className="bi bi-send me-1"></i>Push yaratish</button>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Sarlavha</th><th>Matn</th><th>Target</th><th>Status</th><th>Sana</th><th>Amallar</th></tr></thead>
          <tbody>{notifications.map(notification => (
            <tr key={notification.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{notification.id}</td>
              <td className="fw-semibold">{notification.title}</td>
              <td className="text-muted">{notification.body || '—'}</td>
              <td><span className="chip chip-gray">{notification.who || 'all'}</span></td>
              <td><span className="chip chip-success" style={{ fontSize: 9 }}>{notification.status}</span></td>
              <td className="text-muted">{notification.date}</td>
              <td><button className="btn btn-sm btn-light text-danger" onClick={() => destroy(notification)}><i className="bi bi-trash"></i></button></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Push bildirishnoma</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Sarlavha</Form.Label><Form.Control name="name" required className="mb-3" />
            <Form.Label>Matn</Form.Label><Form.Control as="textarea" rows={4} name="description" required className="mb-3" />
            <Form.Label>Auditoriya</Form.Label><Form.Select name="who" required defaultValue="users"><option value="users">Foydalanuvchilar</option><option value="business">Sellerlar</option><option value="courier">Kuryerlar</option></Form.Select>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
