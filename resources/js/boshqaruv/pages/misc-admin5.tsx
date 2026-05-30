import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== MYSTERY BOX =====
export function MysteryBox() {
  const [plans, setPlans] = useState([
    { id: 1, name: 'Bronze', price: 99000, active: true, subscribers: 84 },
    { id: 2, name: 'Silver', price: 199000, active: true, subscribers: 42 },
    { id: 3, name: 'Gold', price: 349000, active: false, subscribers: 18 },
  ]);
  const [subs] = useState([
    { id: 1, user: 'Aziza K.', plan: 'Bronze', status: 'Active', nextDelivery: '2026-01-20' },
    { id: 2, user: 'Bobur A.', plan: 'Silver', status: 'Paused', nextDelivery: '—' },
    { id: 3, user: 'Dilnoza R.', plan: 'Gold', status: 'Active', nextDelivery: '2026-01-22' },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof plans[0] | null>(null);
  const [name, setName] = useState('');
  const [price, setPrice] = useState('');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Mystery Box</h1><p className="page-subtitle">{plans.length} ta plan · {subs.filter(s => s.status === 'Active').length} ta faol obuna</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setName(''); setPrice(''); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi plan</button>
      </div>

      <div className="row g-3 mb-3">
        {plans.map(p => (
          <div className="col-xl-4 col-md-6" key={p.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between align-items-center mb-2">
                <div className="fw-bold fs-5">{p.name}</div>
                <div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={p.active} onChange={() => setPlans(plans.map(x => x.id === p.id ? { ...x, active: !x.active } : x))} /></div>
              </div>
              <div className="fw-bold text-primary fs-4">{p.price.toLocaleString()} so'm<small className="text-muted fs-6">/oy</small></div>
              <div className="text-muted mb-2">{p.subscribers} obunachi</div>
              <div className="d-flex gap-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelected(p); setName(p.name); setPrice(String(p.price)); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setPlans(plans.filter(x => x.id !== p.id))}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-title mb-3">📦 Obunalar</div>
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Plan</th><th>Status</th><th>Keyingi yetkazish</th><th>Amallar</th></tr></thead>
          <tbody>{subs.map(s => (
            <tr key={s.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{s.id}</td>
              <td className="fw-semibold">{s.user}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{s.plan}</span></td>
              <td><span className={`chip ${s.status === 'Active' ? 'chip-success' : 'chip-warning'}`} style={{ fontSize: 9 }}>{s.status}</span></td>
              <td className="text-muted">{s.nextDelivery}</td>
              <td>
                <button className="btn btn-sm btn-light me-1"><i className="bi bi-eye"></i></button>
                {s.status === 'Active' ? <button className="btn btn-sm btn-warning" onClick={() => alert("Paused")}><i className="bi bi-pause-fill"></i></button> : <button className="btn btn-sm btn-success" onClick={() => alert("Resumed")}><i className="bi bi-play-fill"></i></button>}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>

      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setPlans(plans.map(x => x.id === selected.id ? { ...x, name, price: Number(price) } : x)); else setPlans([...plans, { id: Date.now(), name, price: Number(price), active: true, subscribers: 0 }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi plan'}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Plan nomi</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group>
            <Form.Group><Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label><Form.Control type="number" required value={price} onChange={e => setPrice(e.target.value)} /></Form.Group>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== SOVG'ALAR =====
export function SovgAlar() {
  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Sovg'alar</h1><p className="page-subtitle">Sovg'a mahsulotlari — placeholder</p></div>
      </div>
      <div className="card-panel text-center py-5">
        <i className="bi bi-gift" style={{ fontSize: 64, color: '#ec4899' }}></i>
        <h4 className="mt-3">Sovg'alar bo'limi</h4>
        <p className="text-muted">Hozircha bu yerda placeholder. To'liq CRUD tez orada qo'shiladi.</p>
      </div>
    </div>
  );
}

// ===== LOGISTIKA — YETKAZISH ZONALARI =====
export function Logistika() {
  const [zones, setZones] = useState([
    { id: 1, name: 'Toshkent shahri', price: 0, minOrder: 0, deliveryDays: '1 kun', active: true },
    { id: 2, name: 'Toshkent viloyati', price: 25000, minOrder: 100000, deliveryDays: '1-2 kun', active: true },
    { id: 3, name: 'Viloyat markazlari', price: 45000, minOrder: 200000, deliveryDays: '2-3 kun', active: true },
    { id: 4, name: 'Tumanlar', price: 65000, minOrder: 300000, deliveryDays: '3-5 kun', active: true },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof zones[0] | null>(null);
  const [name, setName] = useState('');
  const [price, setPrice] = useState('');
  const [minOrder, setMinOrder] = useState('');
  const [deliveryDays, setDeliveryDays] = useState('');

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Yetkazish zonalari va qoidalar</h1><p className="page-subtitle">Jami {zones.length} ta zona</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setName(''); setPrice(''); setMinOrder(''); setDeliveryDays(''); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi qoida</button>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Zona</th><th>Yetkazish narxi</th><th>Min. buyurtma</th><th>Muddat</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{zones.map(z => (
            <tr key={z.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{z.id}</td>
              <td className="fw-semibold">{z.name}</td>
              <td>{z.price === 0 ? <span className="chip chip-success" style={{ fontSize: 9 }}>Bepul</span> : <span className="fw-semibold">{z.price.toLocaleString()} so'm</span>}</td>
              <td>{z.minOrder > 0 ? `${z.minOrder.toLocaleString()} so'm` : '—'}</td>
              <td className="fw-semibold">{z.deliveryDays}</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={z.active} onChange={() => setZones(zones.map(x => x.id === z.id ? { ...x, active: !x.active } : x))} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(z); setName(z.name); setPrice(String(z.price)); setMinOrder(String(z.minOrder)); setDeliveryDays(z.deliveryDays); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setZones(zones.filter(x => x.id !== z.id))}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); const obj = { id: Date.now(), name, price: Number(price), minOrder: Number(minOrder) || 0, deliveryDays, active: true }; if (selected) setZones(zones.map(x => x.id === selected.id ? { ...x, name, price: Number(price), minOrder: Number(minOrder) || 0, deliveryDays } : x)); else setZones([...zones, obj]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi qoida'}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Zona nomi</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group>
            <div className="row g-3">
              <Form.Group className="col-4"><Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label><Form.Control type="number" required value={price} onChange={e => setPrice(e.target.value)} /></Form.Group>
              <Form.Group className="col-4"><Form.Label className="small fw-semibold">Min. summa</Form.Label><Form.Control type="number" value={minOrder} onChange={e => setMinOrder(e.target.value)} /></Form.Group>
              <Form.Group className="col-4"><Form.Label className="small fw-semibold">Yetkazish muddati</Form.Label><Form.Control required placeholder="2-3 kun" value={deliveryDays} onChange={e => setDeliveryDays(e.target.value)} /></Form.Group>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
