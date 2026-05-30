import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

interface Policy { id: number; title: string; status: string; updated: string; }
const initial: Policy[] = [
  { id: 1, title: "Maxfiylik siyosati", status: 'Active', updated: '2026-01-10' },
  { id: 2, title: "Foydalanish qoidalari", status: 'Active', updated: '2026-01-08' },
  { id: 3, title: "Yetkazib berish qoidalari", status: 'Active', updated: '2026-01-05' },
  { id: 4, title: "Qaytarish siyosati", status: 'Inactive', updated: '2025-12-20' },
];

export default function Siyosatlar() {
  const [list, setList] = useState(initial);
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [selected, setSelected] = useState<Policy | null>(null);
  const [title, setTitle] = useState('');

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Siyosatlar va qoidalar</h1>
          <p className="page-subtitle">Jami {list.length} ta siyosat</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => { setTitle(''); setShowAdd(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi siyosat</button>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Sarlavha</th><th>Oxirgi yangilangan</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {list.map(p => (
                <tr key={p.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{p.id}</td>
                  <td className="fw-semibold">{p.title}</td>
                  <td className="text-muted">{p.updated}</td>
                  <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={p.status === 'Active'} onChange={() => setList(list.map(x => x.id === p.id ? { ...x, status: x.status === 'Active' ? 'Inactive' : 'Active' } : x))} /></div></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(p); setTitle(p.title); setShowEdit(true); }}><i className="bi bi-pencil"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== p.id))}><i className="bi bi-trash"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); setList([...list, { id: Date.now(), title, status: 'Active', updated: new Date().toISOString().split('T')[0] }]); setShowAdd(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Yangi siyosat</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Sarlavha</Form.Label><Form.Control required placeholder="Masalan: Chegirma siyosati" value={title} onChange={e => setTitle(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>

      <Modal show={showEdit} onHide={() => setShowEdit(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (!selected) return; setList(list.map(x => x.id === selected.id ? { ...x, title } : x)); setShowEdit(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Siyosatni tahrirlash</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Sarlavha</Form.Label><Form.Control required value={title} onChange={e => setTitle(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowEdit(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
