import { useState } from 'react';
import { Modal, Button } from 'react-bootstrap';
import { fmt } from '../data';

interface TXN { id: string; user: string; type: string; amount: number; status: string; date: string; method: string; note: string; }

const initial: TXN[] = [
  { id: 'TXN-5001', user: 'Kanselyariya PLUS', type: 'payout', amount: 4_800_000, status: 'Completed', date: '2026-01-14', method: 'Bank', note: 'Dekabr to\'lovi' },
  { id: 'TXN-5002', user: 'Kitob olami MCHJ', type: 'payout', amount: 3_240_000, status: 'Completed', date: '2026-01-14', method: 'Bank', note: 'Dekabr to\'lovi' },
  { id: 'TXN-5003', user: 'Sharq kitoblari', type: 'payout', amount: 1_120_000, status: 'Completed', date: '2026-01-13', method: 'Card', note: 'To\'lov' },
  { id: 'TXN-5004', user: 'Iman books', type: 'payout', amount: 890_000, status: 'Completed', date: '2026-01-11', method: 'Card', note: 'To\'lov' },
  { id: 'TXN-5005', user: 'Sardor Raximov', type: 'salary', amount: 3_500_000, status: 'Completed', date: '2026-01-10', method: 'Wallet', note: 'Yanvar I yarmi' },
  { id: 'TXN-5006', user: 'Adabiyot do\'koni', type: 'payout', amount: 680_000, status: 'Pending', date: '2026-01-12', method: 'Bank', note: 'To\'lov kutilmoqda' },
  { id: 'TXN-5007', user: 'Akbar Tursunov', type: 'penalty', amount: -50_000, status: 'Completed', date: '2026-01-03', method: 'Wallet', note: '1 ta muvaffaqiyatsiz yetkazish' },
];

export default function Transaksiyalar() {
  const [list, setList] = useState(initial);
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<TXN | null>(null);

  const total = list.reduce((a, t) => a + (t.amount > 0 ? t.amount : 0), 0);
  const pending = list.filter(t => t.status === 'Pending').length;

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Tranzaksiyalar</h1>
          <p className="page-subtitle">Jami {list.length} ta tranzaksiya · {fmt(total)} so'm</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary"><i className="bi bi-download me-1"></i>Export</button>
          <span className="chip chip-warning">{pending} ta kutilmoqda</span>
        </div>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Turi</th><th>Summa</th><th>Metod</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {list.map(t => (
                <tr key={t.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5', fontSize: 11 }}>{t.id}</td>
                  <td className="fw-semibold">{t.user}</td>
                  <td><span className="chip" style={{ fontSize: 9, background: t.type === 'payout' || t.type === 'salary' ? '#dcfce7' : t.type === 'penalty' ? '#fee2e2' : '#eef2ff', color: t.type === 'payout' || t.type === 'salary' ? '#166534' : t.type === 'penalty' ? '#991b1b' : '#1e40af' }}>{t.type}</span></td>
                  <td className={`fw-bold ${t.amount >= 0 ? 'text-success' : 'text-danger'}`}>{t.amount >= 0 ? '+' : ''}{fmt(t.amount)} so'm</td>
                  <td><span className="chip chip-gray" style={{ fontSize: 9 }}>{t.method}</span></td>
                  <td className="text-muted">{t.date}</td>
                  <td><span className={`chip ${t.status === 'Completed' ? 'chip-success' : t.status === 'Pending' ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{t.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(t); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                    {t.status === 'Pending' && (
                      <>
                        <button className="btn btn-sm btn-success me-1" onClick={() => setList(list.map(x => x.id === t.id ? { ...x, status: 'Completed' } : x))}><i className="bi bi-check-lg"></i></button>
                        <button className="btn btn-sm btn-danger" onClick={() => setList(list.map(x => x.id === t.id ? { ...x, status: 'Failed' } : x))}><i className="bi bi-x-lg"></i></button>
                      </>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Tranzaksiya: {selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Foydalanuvchi</small><div className="fw-semibold">{selected?.user}</div></div>
            <div className="col-6"><small className="text-muted">Turi</small><div className="fw-semibold">{selected?.type}</div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div className="fw-bold" style={{ color: (selected?.amount || 0) >= 0 ? '#10b981' : '#ef4444' }}>{selected && (selected.amount >= 0 ? '+' : '')}{fmt(selected?.amount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Metod</small><div className="fw-semibold">{selected?.method}</div></div>
            <div className="col-6"><small className="text-muted">Sana</small><div>{selected?.date}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'Completed' ? 'chip-success' : selected?.status === 'Pending' ? 'chip-warning' : 'chip-danger'}`}>{selected?.status}</span></div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.note}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
