import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

interface Cat { id: number; name: string; active: boolean; itemsCount: number; }
const initial: Cat[] = [
  { id: 1, name: 'Badiiy', active: true, itemsCount: 142 },
  { id: 2, name: 'Ilmiy', active: true, itemsCount: 84 },
  { id: 3, name: 'Bolalar', active: true, itemsCount: 56 },
  { id: 4, name: 'Darslik', active: true, itemsCount: 34 },
  { id: 5, name: 'Tarixiy', active: false, itemsCount: 22 },
];

export default function BookCategories() {
  const [list, setList] = useState(initial);
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [selected, setSelected] = useState<Cat | null>(null);
  const [name, setName] = useState('');

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kitob kategoriyalari</h1>
          <p className="page-subtitle">Jami {list.length} ta kategoriya</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => { setName(''); setShowAdd(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi kategoriya</button>
      </div>
      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Nomi</th><th>Kitoblar</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {list.map(c => (
                <tr key={c.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{c.id}</td>
                  <td className="fw-semibold">{c.name}</td>
                  <td>{c.itemsCount} ta</td>
                  <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={c.active} onChange={() => setList(list.map(x => x.id === c.id ? { ...x, active: !x.active } : x))} /></div></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(c); setName(c.name); setShowEdit(true); }}><i className="bi bi-pencil"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== c.id))}><i className="bi bi-trash"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); setList([...list, { id: Date.now(), name, active: true, itemsCount: 0 }]); setShowAdd(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Yangi kategoriya</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Kategoriya nomi</Form.Label><Form.Control required placeholder="Masalan: She'riyat" value={name} onChange={e => setName(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
      <Modal show={showEdit} onHide={() => setShowEdit(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (!selected) return; setList(list.map(x => x.id === selected.id ? { ...x, name } : x)); setShowEdit(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Kategoriyani tahrirlash</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Kategoriya nomi</Form.Label><Form.Control required value={name} onChange={e => setName(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowEdit(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
