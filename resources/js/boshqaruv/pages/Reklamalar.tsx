import { useState } from 'react';
import { Modal, Button } from 'react-bootstrap';
import { fmt } from '../data';

interface Ad { id: number; name: string; type: string; budget: number; spent: number; clicks: number; status: string; }
const initial: Ad[] = [
  { id: 1, name: 'Banner — Kitob olami', type: 'Banner', budget: 2_000_000, spent: 1_240_000, clicks: 8420, status: 'Active' },
  { id: 2, name: 'Kategoriya reklama — Kanselyariya', type: 'Category', budget: 1_500_000, spent: 520_000, clicks: 3120, status: 'Active' },
  { id: 3, name: 'Post — Yangi yil aksiyasi', type: 'Promo', budget: 3_000_000, spent: 2_800_000, clicks: 12400, status: 'Pending' },
];

export default function Reklamalar() {
  const [list, setList] = useState(initial);
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<Ad | null>(null);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Reklamalar</h1>
          <p className="page-subtitle">Jami {list.length} ta reklama</p>
        </div>
      </div>

      <div className="row g-3">
        {list.map(a => (
          <div className="col-xl-4 col-md-6" key={a.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <div className="fw-bold">{a.name}</div>
                  <span className="chip chip-purple" style={{ fontSize: 9 }}>{a.type}</span>
                </div>
                <span className={`chip ${a.status === 'Active' ? 'chip-success' : a.status === 'Pending' ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{a.status}</span>
              </div>
              <div className="d-flex gap-3 small text-center mb-2 p-2 rounded" style={{ background: '#f9fafb' }}>
                <div className="flex-fill"><div className="fw-bold">{fmt(a.clicks)}</div><small className="text-muted">Klik</small></div>
                <div className="flex-fill"><div className="fw-bold text-success">{fmt(a.spent)}</div><small className="text-muted">Sarflangan</small></div>
                <div className="flex-fill"><div className="fw-bold text-primary">{fmt(a.budget)}</div><small className="text-muted">Budget</small></div>
              </div>
              <div className="progress mb-2" style={{ height: 6 }}>
                <div className="progress-bar" style={{ width: `${(a.spent / a.budget) * 100}%` }}></div>
              </div>
              <div className="d-flex gap-2 mt-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelected(a); setShowDetail(true); }}><i className="bi bi-eye"></i> Ko'rish</button>
                {a.status === 'Pending' ? (
                  <>
                    <button className="btn btn-sm btn-success flex-fill" onClick={() => setList(list.map(x => x.id === a.id ? { ...x, status: 'Active' } : x))}><i className="bi bi-check-lg"></i> Tasdiqlash</button>
                    <button className="btn btn-sm btn-danger" onClick={() => setList(list.filter(x => x.id !== a.id))}><i className="bi bi-x-lg"></i></button>
                  </>
                ) : (
                  <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(x => x.id !== a.id))}><i className="bi bi-trash"></i></button>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type}</div></div>
            <div className="col-6"><small className="text-muted">Budget</small><div className="fw-bold">{fmt(selected?.budget || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Sarflangan</small><div className="fw-bold text-success">{fmt(selected?.spent || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Kliklar</small><div className="fw-bold">{fmt(selected?.clicks || 0)}</div></div>
            <div className="col-12"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'Active' ? 'chip-success' : 'chip-warning'}`}>{selected?.status}</span></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}
