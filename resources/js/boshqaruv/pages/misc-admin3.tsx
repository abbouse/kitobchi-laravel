import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== REELS =====
export function Reels() {
  const { reels = [] } = usePage<{
    reels?: Array<{ id: number; title: string; description?: string; order?: number; status: string; items: number; createUrl?: string; showUrl?: string; editUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [selected, setSelected] = useState<(typeof reels)[0] | null>(null);
  const createUrl = reels[0]?.createUrl || '/a122/reels/create';

  const destroy = (reel: (typeof reels)[0]) => {
    if (!reel.destroyUrl || !confirm(`${reel.title} reelini o'chirasizmi?`)) return;
    router.delete(reel.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Reels / Shorts</h1><p className="page-subtitle">Jami {reels.length} ta reel</p></div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi reel</a>
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
                <a className="btn btn-sm btn-primary-gradient flex-fill" href={reel.editUrl || reel.showUrl || '#'}><i className="bi bi-pencil"></i></a>
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
          {selected?.showUrl ? <a className="btn btn-primary-gradient" href={selected.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== MARKET YANGILIKLARI =====
export function MarketNews() {
  const { news = [] } = usePage<{
    news?: Array<{ id: number; title: string; description?: string; status: string; action?: string; image?: string | null; date?: string; createUrl?: string; showUrl?: string; editUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [selected, setSelected] = useState<(typeof news)[0] | null>(null);
  const createUrl = news[0]?.createUrl || '/a122/news/create';

  const toggle = (item: (typeof news)[0]) => item.toggleUrl && router.patch(item.toggleUrl, {}, { preserveScroll: true });
  const destroy = (item: (typeof news)[0]) => {
    if (!item.destroyUrl || !confirm(`${item.title} yangiligi o'chirilsinmi?`)) return;
    router.delete(item.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Market yangiliklari</h1><p className="page-subtitle">Jami {news.length} ta yangilik</p></div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi yangilik</a></div>
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
                {item.editUrl ? <a className="btn btn-sm btn-light me-1" href={item.editUrl}><i className="bi bi-pencil"></i></a> : null}
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(item)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          {selected?.image ? <img className="w-100 rounded mb-3" src={selected.image} alt={selected.title} /> : null}
          <p className="text-muted">{selected?.description || '—'}</p>
        </Modal.Body>
        <Modal.Footer>
          {selected?.showUrl ? <a className="btn btn-primary-gradient" href={selected.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== CHAT KUZATUV =====
export function ChatKuzatuv() {
  const [convs] = useState([
    { id: 1, user: 'Aziza K.', agent: 'AI Bot', messages: 12, lastMsg: 'Rahmat, tushindim', status: 'Active', time: '2 daqiqa oldin' },
    { id: 2, user: 'Bobur A.', agent: 'Admin Sobir', messages: 8, lastMsg: 'Buyurtma qachon keladi?', status: 'Active', time: '5 daqiqa oldin' },
    { id: 3, user: 'Dilnoza R.', agent: 'AI Bot', messages: 24, lastMsg: "Kitobni qaytarish mumkinmi?", status: 'Closed', time: '1 soat oldin' },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof convs[0] | null>(null);

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Chat kuzatuv</h1><p className="page-subtitle">Jami {convs.length} ta conversation</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Agent</th><th>Xabarlar</th><th>Oxirgi</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{convs.map(c => (
            <tr key={c.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{c.id}</td>
              <td className="fw-semibold">{c.user}</td>
              <td>{c.agent}</td>
              <td>{c.messages}</td>
              <td className="text-muted">{c.lastMsg}<br /><small>{c.time}</small></td>
              <td><span className={`chip ${c.status === 'Active' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{c.status}</span></td>
              <td><button className="btn btn-sm btn-light" onClick={() => { setSelected(c); setShow(true); }}><i className="bi bi-eye"></i></button></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Conversation #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="mb-3 p-3 rounded bg-light">
            <div className="fw-semibold mb-1">{selected?.user} — {selected?.agent}</div>
            <div className="text-muted small">{selected?.messages} ta xabar</div>
          </div>
          <div className="chat-bubble" style={{ textAlign: 'right' }}><div className="d-inline-block p-2 rounded" style={{ background: '#4f46e5', color: 'white' }}><small>{selected?.lastMsg}</small></div></div>
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
  const createUrl = notifications[0]?.createUrl || '/a122/push/create';
  const destroy = (notification: (typeof notifications)[0]) => {
    if (!notification.destroyUrl || !confirm(`#${notification.id} push o'chirilsinmi?`)) return;
    router.delete(notification.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Push bildirishnomalar</h1><p className="page-subtitle">Jami {notifications.length} ta yuborilgan</p></div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-send me-1"></i>Yangi push yaratish</a>
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
    </div>
  );
}
