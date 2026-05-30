import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== REELS =====
export function Reels() {
  const [list, setList] = useState([
    { id: 1, title: "O'tkan kunlar — eng sara iqtiboslar", views: 12400, status: 'Active', items: 5 },
    { id: 2, title: 'Bolalar uchun ertaklar to\'plami', views: 8400, status: 'Active', items: 3 },
    { id: 3, title: 'Parker ruchka — yozish san\'ati', views: 3200, status: 'Inactive', items: 2 },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);
  const [title, setTitle] = useState('');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Reels / Shorts</h1><p className="page-subtitle">Jami {list.length} ta reel</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setTitle(''); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi reel</button>
      </div>
      <div className="row g-3">
        {list.map(r => (
          <div className="col-xl-4 col-md-6" key={r.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between mb-2">
                <div className="fw-bold">{r.title}</div>
                <span className={`chip ${r.status === 'Active' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{r.status}</span>
              </div>
              <div className="d-flex gap-3 small mb-2">
                <span>👁 {r.views.toLocaleString()}</span>
                <span>📦 {r.items} ta mahsulot</span>
              </div>
              <div className="d-flex gap-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelected(r); setTitle(r.title); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== r.id))}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setList(list.map(x => x.id === selected.id ? { ...x, title } : x)); else setList([...list, { id: Date.now(), title, views: 0, status: 'Active', items: 0 }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi reel'}</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Sarlavha</Form.Label><Form.Control required value={title} onChange={e => setTitle(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== MARKET YANGILIKLARI =====
export function MarketNews() {
  const [list, setList] = useState([
    { id: 1, title: "Yangi yil aksiyasi — 20% chegirma", status: 'Active', date: '2026-01-14' },
    { id: 2, title: "Parker ruchkalar yangi kolleksiyasi", status: 'Active', date: '2026-01-12' },
    { id: 3, title: "O'tkan kunlar — qayta nashr", status: 'Inactive', date: '2026-01-10' },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);
  const [title, setTitle] = useState('');

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Market yangiliklari</h1><p className="page-subtitle">Jami {list.length} ta yangilik</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setTitle(''); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi yangilik</button></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Sarlavha</th><th>Sana</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(n => (
            <tr key={n.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{n.id}</td>
              <td className="fw-semibold">{n.title}</td>
              <td className="text-muted">{n.date}</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={n.status === 'Active'} onChange={() => setList(list.map(x => x.id === n.id ? { ...x, status: x.status === 'Active' ? 'Inactive' : 'Active' } : x))} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(n); setTitle(n.title); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== n.id))}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setList(list.map(x => x.id === selected.id ? { ...x, title } : x)); else setList([...list, { id: Date.now(), title, status: 'Active', date: new Date().toISOString().split('T')[0] }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi'}</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Sarlavha</Form.Label><Form.Control required value={title} onChange={e => setTitle(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
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
  const [history, setHistory] = useState([
    { id: 1, title: "Chegirma — 20%", sent: 2840, opened: 1240, status: 'Sent', date: '2026-01-14' },
    { id: 2, title: "Yangi kitoblar keldi", sent: 2840, opened: 980, status: 'Sent', date: '2026-01-12' },
    { id: 3, title: "Buyurtma statusi yangilandi", sent: 128, opened: 84, status: 'Sent', date: '2026-01-10' },
  ]);
  const [show, setShow] = useState(false);
  const [pushTitle, setPushTitle] = useState('');
  const [pushBody, setPushBody] = useState('');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Push bildirishnomalar</h1><p className="page-subtitle">Jami {history.length} ta yuborilgan</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setPushTitle(''); setPushBody(''); setShow(true); }}><i className="bi bi-send me-1"></i>Yangi push yaratish</button>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Sarlavha</th><th>Yuborilgan</th><th>Ochilgan</th><th>CTR</th><th>Status</th><th>Sana</th><th>Amallar</th></tr></thead>
          <tbody>{history.map(p => (
            <tr key={p.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{p.id}</td>
              <td className="fw-semibold">{p.title}</td>
              <td>{p.sent.toLocaleString()}</td>
              <td>{p.opened.toLocaleString()}</td>
              <td><span className="chip chip-success" style={{ fontSize: 9 }}>{Math.round((p.opened / p.sent) * 100)}%</span></td>
              <td><span className="chip chip-success" style={{ fontSize: 9 }}>{p.status}</span></td>
              <td className="text-muted">{p.date}</td>
              <td><button className="btn btn-sm btn-light text-danger" onClick={() => setHistory(history.filter(x => x.id !== p.id))}><i className="bi bi-trash"></i></button></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); setHistory([{ id: Date.now(), title: pushTitle, sent: 2840, opened: 0, status: 'Sent', date: new Date().toISOString().split('T')[0] }, ...history]); setShow(false); alert(`Push yuborildi: ${pushTitle}`); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Yangi push bildirishnoma</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Sarlavha</Form.Label><Form.Control required placeholder="Yangi kitob" value={pushTitle} onChange={e => setPushTitle(e.target.value)} /></Form.Group>
            <Form.Group><Form.Label className="small fw-semibold">Matn</Form.Label><Form.Control as="textarea" rows={3} required placeholder="Yangi kitoblar keldi!" value={pushBody} onChange={e => setPushBody(e.target.value)} /></Form.Group>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient"><i className="bi bi-send me-1"></i>Yuborish</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
