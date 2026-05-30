import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== BLOGERLAR =====
export function Blogerlar() {
  const [list, setList] = useState([
    { id: 1, name: '@kitobsevarmi', followers: 12400, platform: 'Instagram', status: 'Active' },
    { id: 2, name: '@bookuz', followers: 8400, platform: 'Telegram', status: 'Active' },
    { id: 3, name: '@reading_uz', followers: 3200, platform: 'TikTok', status: 'Inactive' },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);
  const [name, setName] = useState('');
  const [platform, setPlatform] = useState('Instagram');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Hamkor blogerlar</h1><p className="page-subtitle">Jami {list.length} ta bloger</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setName(''); setPlatform('Instagram'); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi bloger</button>
      </div>
      <div className="row g-3">
        {list.map(b => (
          <div className="col-xl-4 col-md-6" key={b.id}>
            <div className="card-panel">
              <div className="d-flex align-items-center gap-2 mb-2">
                <div style={{ width: 44, height: 44, borderRadius: '50%', background: b.status === 'Active' ? '#10b981' : '#d1d5db', display: 'grid', placeItems: 'center', color: 'white', fontWeight: 700, fontSize: 16 }}>{b.name[1]}</div>
                <div><div className="fw-bold">{b.name}</div><small className="text-muted">{b.platform} · {b.followers.toLocaleString()} obunachi</small></div>
                <div className="ms-auto"><span className={`chip ${b.status === 'Active' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{b.status}</span></div>
              </div>
              <div className="d-flex gap-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelected(b); setName(b.name); setPlatform(b.platform); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== b.id))}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setList(list.map(x => x.id === selected.id ? { ...x, name, platform } : x)); else setList([...list, { id: Date.now(), name, followers: 0, platform, status: 'Active' }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi bloger'}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Bloger nomi</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group>
            <Form.Group><Form.Label className="small fw-semibold">Platforma</Form.Label><Form.Select value={platform} onChange={e => setPlatform(e.target.value)}><option>Instagram</option><option>Telegram</option><option>TikTok</option><option>YouTube</option></Form.Select></Form.Group>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== SHIKOYATLAR =====
export function Shikoyatlar() {
  const [list, setList] = useState([
    { id: 1, user: 'Bobur A.', reason: 'Buyurtma kelmadi', type: 'Delivery', status: 'New', date: '2 soat oldin' },
    { id: 2, user: 'Dilnoza R.', reason: 'Kitob muqovasi shikastlangan', type: 'Quality', status: 'In Progress', date: '5 soat oldin' },
    { id: 3, user: 'Shaxlo Y.', reason: "Xodim bilan muomala", type: 'Service', status: 'Resolved', date: '1 kun oldin' },
  ]);
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Shikoyatlar</h1><p className="page-subtitle">Jami {list.length} ta shikoyat</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Sabab</th><th>Turi</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(s => (
            <tr key={s.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{s.id}</td>
              <td className="fw-semibold">{s.user}</td>
              <td>{s.reason}</td>
              <td><span className="chip chip-gray" style={{ fontSize: 9 }}>{s.type}</span></td>
              <td className="text-muted">{s.date}</td>
              <td><span className={`chip ${s.status === 'New' ? 'chip-danger' : s.status === 'In Progress' ? 'chip-warning' : 'chip-success'}`} style={{ fontSize: 9 }}>{s.status}</span></td>
              <td>
                <button className="btn btn-sm btn-light" onClick={() => { setSelected(s); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Shikoyat #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <p><strong>{selected?.user}</strong> dan shikoyat: {selected?.reason}</p>
          <div className="d-flex gap-2 mt-3">
            {['New', 'In Progress', 'Resolved'].map(s => (
              <button key={s} className={`btn btn-sm ${selected?.status === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => { setList(list.map(x => x.id === selected?.id ? { ...x, status: s } : x)); setSelected(selected ? { ...selected, status: s } : null); }}>{s}</button>
            ))}
          </div>
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== GIFT SERTIFIKATLAR =====
export function GiftSertifikatlar() {
  const [list, setList] = useState([
    { id: 1, code: 'GIFT-1001', amount: 100000, buyer: 'Aziza K.', status: 'Active', expires: '2026-12-31' },
    { id: 2, code: 'GIFT-1002', amount: 250000, buyer: 'Bobur A.', status: 'Redeemed', expires: '2026-06-30' },
    { id: 3, code: 'GIFT-1003', amount: 50000, buyer: 'Dilnoza R.', status: 'Cancelled', expires: '2026-03-15' },
  ]);

  const totalActive = list.filter(s => s.status === 'Active').reduce((a, s) => a + s.amount, 0);

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Gift sertifikatlar</h1><p className="page-subtitle">Jami {list.length} ta · {list.filter(s => s.status === 'Active').length} ta aktiv · {totalActive.toLocaleString()} so'm</p></div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Kod</th><th>Summa</th><th>Xaridor</th><th>Muddati</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(s => (
            <tr key={s.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{s.id}</td>
              <td className="fw-bold" style={{ fontFamily: 'monospace' }}>{s.code}</td>
              <td className="fw-bold text-success">{s.amount.toLocaleString()} so'm</td>
              <td>{s.buyer}</td>
              <td className="text-muted">{s.expires}</td>
              <td><span className={`chip ${s.status === 'Active' ? 'chip-success' : s.status === 'Redeemed' ? 'chip-info' : 'chip-danger'}`} style={{ fontSize: 9 }}>{s.status}</span></td>
              <td>
                {s.status === 'Active' && <button className="btn btn-sm btn-danger" onClick={() => setList(list.map(x => x.id === s.id ? { ...x, status: 'Cancelled' } : x))}><i className="bi bi-x-lg"></i> Bekor qilish</button>}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}
