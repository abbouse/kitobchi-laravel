import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== ADMINLAR =====
export function Adminlar() {
  const [list, setList] = useState([
    { id: 1, name: 'Admin Sobir', email: 'sobir@bookhub.uz', role: 'Super Admin', active: true },
    { id: 2, name: 'Zarnigor Xalilova', email: 'zarnigor@bookhub.uz', role: 'Moderator', active: true },
    { id: 3, name: 'Javohir Raximjonov', email: 'java@bookhub.uz', role: 'Support', active: false },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState('Moderator');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Adminlar</h1><p className="page-subtitle">Jami {list.length} ta admin</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setName(''); setEmail(''); setRole('Moderator'); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi admin</button>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Ism</th><th>Email</th><th>Rol</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(a => (
            <tr key={a.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{a.id}</td>
              <td className="fw-semibold">{a.name}</td>
              <td className="text-muted">{a.email}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{a.role}</span></td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={a.active} onChange={() => setList(list.map(x => x.id === a.id ? { ...x, active: !x.active } : x))} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(a); setName(a.name); setEmail(a.email); setRole(a.role); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== a.id))}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setList(list.map(x => x.id === selected.id ? { ...x, name, email, role } : x)); else setList([...list, { id: Date.now(), name, email, role, active: true }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi admin'}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Ism</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Email</Form.Label><Form.Control type="email" required value={email} onChange={e => setEmail(e.target.value)} /></Form.Group>
            <Form.Group><Form.Label className="small fw-semibold">Rol</Form.Label><Form.Select value={role} onChange={e => setRole(e.target.value)}><option value="Super Admin">Super Admin</option><option value="Moderator">Moderator</option><option value="Support">Support</option></Form.Select></Form.Group>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== API MIJOZLAR =====
export function ApiClients() {
  const [list, setList] = useState([
    { id: 1, name: 'Mobile App Android', key: 'android_live_sk_8f4a...', active: true, requests: 124800 },
    { id: 2, name: 'Mobile App iOS', key: 'ios_live_sk_3b7c...', active: true, requests: 84200 },
    { id: 3, name: 'Web Widget', key: 'web_widget_9d2e...', active: false, requests: 4200 },
  ]);
  const [showAdd, setShowAdd] = useState(false);
  const [name, setName] = useState('');
  const [showLogs, setShowLogs] = useState(false);

  const logs = [
    { method: 'POST', path: '/api/v2/orders', status: 200, time: '2026-01-14 10:23:12', client: 'Mobile App Android' },
    { method: 'GET', path: '/api/v2/products', status: 200, time: '2026-01-14 10:23:11', client: 'Mobile App iOS' },
    { method: 'POST', path: '/api/v2/auth/login', status: 401, time: '2026-01-14 10:23:10', client: 'Mobile App Android' },
    { method: 'DELETE', path: '/api/v2/cart/items/42', status: 204, time: '2026-01-14 10:23:08', client: 'Mobile App Android' },
  ];

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">API mijozlar</h1><p className="page-subtitle">Jami {list.length} ta client</p></div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => setShowLogs(true)}><i className="bi bi-journal-code me-1"></i>API Logs</button>
          <button className="btn btn-outline-secondary"><i className="bi bi-file-text me-1"></i>API Docs</button>
          <button className="btn btn-primary-gradient" onClick={() => { setName(''); setShowAdd(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi client</button>
        </div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Nomi</th><th>API Key</th><th>So'rovlar</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(c => (
            <tr key={c.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{c.id}</td>
              <td className="fw-semibold">{c.name}</td>
              <td><code style={{ fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4 }}>{c.key}</code></td>
              <td>{c.requests.toLocaleString()}</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={c.active} onChange={() => setList(list.map(x => x.id === c.id ? { ...x, active: !x.active } : x))} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setName(c.name); setShowAdd(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light me-1" onClick={() => alert("Secret regenerate qilindi!")}><i className="bi bi-arrow-repeat"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== c.id))}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); setList([...list, { id: Date.now(), name, key: `${name.toLowerCase().replace(/\s/g, '_')}_sk_${Math.random().toString(36).substring(2, 10)}`, active: true, requests: 0 }]); setShowAdd(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Yangi API client</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Client nomi</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Yaratish</Button></Modal.Footer>
        </Form>
      </Modal>
      <Modal show={showLogs} onHide={() => setShowLogs(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">API Logs (oxirgi 50 ta)</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="table-responsive"><table className="data-table">
            <thead><tr><th>Method</th><th>Path</th><th>Status</th><th>Vaqt</th><th>Client</th></tr></thead>
            <tbody>{logs.map((l, i) => (
              <tr key={i}>
                <td><span className={`chip ${l.method === 'GET' ? 'chip-success' : l.method === 'POST' ? 'chip-info' : 'chip-danger'}`} style={{ fontSize: 9, fontFamily: 'monospace' }}>{l.method}</span></td>
                <td className="fw-semibold" style={{ fontSize: 11 }}>{l.path}</td>
                <td><span className={`chip ${l.status < 300 ? 'chip-success' : l.status < 400 ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{l.status}</span></td>
                <td className="text-muted small">{l.time}</td>
                <td className="text-muted small">{l.client}</td>
              </tr>
            ))}</tbody>
          </table></div>
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShowLogs(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}
