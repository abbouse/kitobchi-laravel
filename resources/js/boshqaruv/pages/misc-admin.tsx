import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== VAKANSIYALAR =====
export function Vakansiyalar() {
  const [list, setList] = useState([
    { id: 1, title: 'Backend Laravel Developer', status: 'Active', applicants: 12 },
    { id: 2, title: 'Mobile Flutter Developer', status: 'Active', applicants: 8 },
    { id: 3, title: 'Marketing Manager', status: 'Inactive', applicants: 0 },
  ]);
  const [show, setShow] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);
  const [title, setTitle] = useState('');

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Vakansiyalar</h1><p className="page-subtitle">Jami {list.length} ta vakansiya</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setTitle(''); setShow(true); }}><i className="bi bi-plus-lg me-1"></i>Yangi vakansiya</button></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Vakansiya</th><th>Arizalar</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(v => (
            <tr key={v.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{v.id}</td>
              <td className="fw-semibold">{v.title}</td>
              <td>{v.applicants} ta</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={v.status === 'Active'} onChange={() => setList(list.map(x => x.id === v.id ? { ...x, status: x.status === 'Active' ? 'Inactive' : 'Active' } : x))} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(v); setTitle(v.title); setShow(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== v.id))}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={show} onHide={() => setShow(false)} centered>
        <Form onSubmit={(e) => { e.preventDefault(); if (selected) setList(list.map(x => x.id === selected.id ? { ...x, title } : x)); else setList([...list, { id: Date.now(), title, status: 'Active', applicants: 0 }]); setShow(false); }}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected ? 'Tahrirlash' : 'Yangi'}</Modal.Title></Modal.Header>
          <Modal.Body><Form.Group><Form.Label className="small fw-semibold">Vakansiya nomi</Form.Label><Form.Control required value={title} onChange={e => setTitle(e.target.value)} /></Form.Group></Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShow(false)}>Bekor qilish</Button><Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== KARYERA ARIZALARI =====
export function KaryeraArizalari() {
  const [list, setList] = useState([
    { id: 1, name: 'Diyorbek Karimov', vacancy: 'Backend Developer', status: 'New', email: 'diyor@mail.uz', phone: '+998 90 111 22 33' },
    { id: 2, name: 'Muxlisa Xasanova', vacancy: 'Marketing Manager', status: 'Reviewed', email: 'muxlisa@mail.uz', phone: '+998 91 222 33 44' },
    { id: 3, name: 'Sardor Aliyev', vacancy: 'Flutter Developer', status: 'Interview', email: 'sardor@mail.uz', phone: '+998 93 333 44 55' },
  ]);
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<typeof list[0] | null>(null);

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Karyera arizalari</h1><p className="page-subtitle">Jami {list.length} ta ariza</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Nomzod</th><th>Vakansiya</th><th>Kontakt</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{list.map(a => (
            <tr key={a.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{a.id}</td>
              <td className="fw-semibold">{a.name}</td>
              <td>{a.vacancy}</td>
              <td className="text-muted small">{a.email}<br />{a.phone}</td>
              <td><span className={`chip ${a.status === 'New' ? 'chip-info' : a.status === 'Reviewed' ? 'chip-warning' : 'chip-success'}`} style={{ fontSize: 9 }}>{a.status}</span></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(a); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light" onClick={() => alert("CV yuklab olinmoqda...")}><i className="bi bi-download"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-2">
            <div className="col-6"><small className="text-muted">Vakansiya</small><div className="fw-semibold">{selected?.vacancy}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'New' ? 'chip-info' : selected?.status === 'Reviewed' ? 'chip-warning' : 'chip-success'}`}>{selected?.status}</span></div></div>
            <div className="col-12"><small className="text-muted">Email</small><div>{selected?.email}</div></div>
            <div className="col-12"><small className="text-muted">Telefon</small><div>{selected?.phone}</div></div>
            <div className="col-12 mt-3">
              <div className="d-flex gap-2">
                {['New', 'Reviewed', 'Interview', 'Rejected'].map(s => (
                  <button key={s} className={`btn btn-sm ${selected?.status === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => { setList(list.map(x => x.id === selected?.id ? { ...x, status: s } : x)); setSelected(selected ? { ...selected, status: s } : null); }}>{s}</button>
                ))}
              </div>
            </div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="success" onClick={() => alert("Nomzodga reply yuborildi!")}><i className="bi bi-send me-1"></i>Reply yuborish</Button>
          <Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
