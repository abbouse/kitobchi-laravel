import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { fmt } from '../data';

interface Promo { id: number; code: string; discount: number; type: string; used: number; max: number; status: string; }
const generateCode = () => Math.random().toString(36).substring(2, 10).toUpperCase();

export default function Promokodlar() {
  const [list, setList] = useState<Promo[]>([
    { id: 1, code: 'KITOB20', discount: 20, type: 'percentage', used: 84, max: 200, status: 'Active' },
    { id: 2, code: 'YANGIYIL', discount: 15000, type: 'fixed', used: 32, max: 100, status: 'Active' },
    { id: 3, code: 'BONUS50', discount: 50, type: 'percentage', used: 142, max: 150, status: 'Expired' },
  ]);
  const [showAdd, setShowAdd] = useState(false);
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<Promo | null>(null);
  const [code, setCode] = useState('');
  const [discount, setDiscount] = useState('');
  const [type, setType] = useState('percentage');
  const [max, setMax] = useState('');

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Promokodlar</h1>
          <p className="page-subtitle">Jami {list.length} ta promokod</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => setCode(generateCode())}><i className="bi bi-shuffle me-1"></i>Avto-generatsiya</button>
          <button className="btn btn-primary-gradient" onClick={() => { setCode(''); setDiscount(''); setType('percentage'); setMax(''); setShowAdd(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi promokod</button>
        </div>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Kod</th><th>Chegirma</th><th>Turi</th><th>Ishlatilgan</th><th>Limit</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {list.map(p => (
                <tr key={p.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{p.id}</td>
                  <td className="fw-bold" style={{ fontFamily: 'monospace', letterSpacing: 1 }}>{p.code}</td>
                  <td className="fw-bold text-success">{p.type === 'percentage' ? `${p.discount}%` : `${fmt(p.discount)} so'm`}</td>
                  <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{p.type}</span></td>
                  <td>{p.used} / {p.max}</td>
                  <td>
                    <div className="progress" style={{ width: 80, height: 6 }}>
                      <div className="progress-bar" style={{ width: `${(p.used / p.max) * 100}%`, background: p.used / p.max > 0.8 ? '#ef4444' : '#10b981' }}></div>
                    </div>
                  </td>
                  <td><span className={`chip ${p.status === 'Active' ? 'chip-success' : 'chip-danger'}`} style={{ fontSize: 9 }}>{p.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(p); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== p.id))}><i className="bi bi-trash"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); setList([...list, { id: Date.now(), code, discount: Number(discount), type, used: 0, max: Number(max) || 100, status: 'Active' }]); setShowAdd(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Yangi promokod</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3"><Form.Label className="small fw-semibold">Kod</Form.Label><Form.Control required value={code} onChange={e => setCode(e.target.value)} /></Form.Group>
            <div className="row g-3">
              <Form.Group className="col-6"><Form.Label className="small fw-semibold">Turi</Form.Label><Form.Select value={type} onChange={e => setType(e.target.value)}><option value="percentage">Foiz (%)</option><option value="fixed">Qat'iy (so'm)</option></Form.Select></Form.Group>
              <Form.Group className="col-6"><Form.Label className="small fw-semibold">Chegirma</Form.Label><Form.Control type="number" required value={discount} onChange={e => setDiscount(e.target.value)} /></Form.Group>
              <Form.Group className="col-6"><Form.Label className="small fw-semibold">Maksimal foydalanish</Form.Label><Form.Control type="number" value={max} onChange={e => setMax(e.target.value)} /></Form.Group>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>

      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Promokod: {selected?.code}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Kod</small><div className="fw-bold" style={{ fontFamily: 'monospace' }}>{selected?.code}</div></div>
            <div className="col-6"><small className="text-muted">Chegirma</small><div className="fw-bold text-success">{selected?.type === 'percentage' ? `${selected?.discount}%` : `${fmt(selected?.discount || 0)} so'm`}</div></div>
            <div className="col-6"><small className="text-muted">Ishlatilgan</small><div>{selected?.used} / {selected?.max}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'Active' ? 'chip-success' : 'chip-danger'}`}>{selected?.status}</span></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}
