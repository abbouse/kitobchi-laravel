import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { orders } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface Ord {
  id: string;
  customer: string;
  items: number;
  total: number;
  status: string;
  date: string;
  payment: string;
}

const statusChip = (s: string) => {
  const map: Record<string, string> = {
    'Delivered': 'chip-success', 'Shipping': 'chip-info', 'Processing': 'chip-warning',
    'Pending': 'chip-gray', 'Cancelled': 'chip-danger',
  };
  return map[s] || 'chip-gray';
};

export default function Orders() {
  const [list, setList] = useState<Ord[]>(orders);
  const [activeTab, setActiveTab] = useState('Barchasi');

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showView, setShowView] = useState(false);
  const [selectedOrd, setSelectedOrd] = useState<Ord | null>(null);

  // Form states
  const [customer, setCustomer] = useState('');
  const [items, setItems] = useState('');
  const [total, setTotal] = useState('');
  const [payment, setPayment] = useState('Click');

  const handleOpenAdd = () => {
    setCustomer('');
    setItems('');
    setTotal('');
    setPayment('Click');
    setShowAdd(true);
  };

  const handleOpenView = (o: Ord) => {
    setSelectedOrd(o);
    setShowView(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newO: Ord = {
      id: `#ORD-${Math.floor(1000 + Math.random() * 9000)}`,
      customer: customer || 'Yangi Mijoz',
      items: Number(items) || 1,
      total: Number(total) || 50000,
      status: 'Pending',
      date: new Date().toISOString().split('T')[0],
      payment
    };
    setList([newO, ...list]);
    setShowAdd(false);
  };

  const handleUpdateStatus = (status: string) => {
    if (!selectedOrd) return;
    setList(list.map(o => o.id === selectedOrd.id ? { ...o, status } : o));
    setSelectedOrd({ ...selectedOrd, status });
  };

  const filtered = list.filter(o => activeTab === 'Barchasi' || o.status === activeTab);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Buyurtmalar</h1>
          <p className="page-subtitle">Barcha mijoz buyurtmalarini boshqarish</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => alert("Filtr menyusi ochildi!")}>
            <i className="bi bi-funnel me-1"></i>Filtr
          </button>
          <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
            <i className="bi bi-plus-lg me-1"></i>Yangi buyurtma
          </button>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami buyurtmalar', val: list.length, icon: 'bi-receipt', color: '#4f46e5' },
          { label: 'Yetkazilgan', val: list.filter(o => o.status === 'Delivered').length, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Jarayonda', val: list.filter(o => o.status === 'Processing').length, icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Bekor qilingan', val: list.filter(o => o.status === 'Cancelled').length, icon: 'bi-x-circle', color: '#ef4444' },
        ].map((s) => (
          <div className="col-xl-3 col-md-6" key={s.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: s.color }}><i className={`bi ${s.icon}`}></i></div>
                <div>
                  <div className="stat-value">{s.val}</div>
                  <div className="stat-label">{s.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          {['Barchasi', 'Pending', 'Processing', 'Shipping', 'Delivered', 'Cancelled'].map((s) => (
            <button 
              key={s} 
              className={`btn btn-sm ${activeTab === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
              onClick={() => setActiveTab(s)}
            >
              {s}
            </button>
          ))}
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th><input type="checkbox" className="form-check-input" /></th>
                <th>Buyurtma ID</th>
                <th>Mijoz</th>
                <th>Mahsulotlar</th>
                <th>Summa</th>
                <th>To'lov</th>
                <th>Sana</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((o) => (
                <tr key={o.id}>
                  <td><input type="checkbox" className="form-check-input" /></td>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>{o.id}</td>
                  <td>{o.customer}</td>
                  <td>{o.items} dona</td>
                  <td className="fw-semibold">{fmt(o.total)} so'm</td>
                  <td><span className="chip chip-gray">{o.payment}</span></td>
                  <td className="text-muted">{o.date}</td>
                  <td><span className={`chip ${statusChip(o.status)}`}>{o.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenView(o)} title="Ko'rish / Boshqarish">
                      <i className="bi bi-eye"></i>
                    </button>
                    <button className="btn btn-sm btn-light" onClick={() => window.print()} title="Chekni chop etish">
                      <i className="bi bi-printer"></i>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* ADD MODAL */}
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={handleAdd}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Yangi buyurtma yaratish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mijoz ism-sharifi</Form.Label>
              <Form.Control required placeholder="Aziza Karimova" value={customer} onChange={e => setCustomer(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Mahsulotlar soni</Form.Label>
                <Form.Control type="number" required placeholder="3" value={items} onChange={e => setItems(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Umumiy summa (so'm)</Form.Label>
                <Form.Control type="number" required placeholder="385000" value={total} onChange={e => setTotal(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">To'lov tizimi</Form.Label>
              <Form.Select value={payment} onChange={e => setPayment(e.target.value)}>
                <option value="Click">Click</option>
                <option value="Payme">Payme</option>
                <option value="Uzum">Uzum</option>
                <option value="Cash">Naqd pul</option>
              </Form.Select>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Yaratish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* VIEW MODAL */}
      <Modal show={showView} onHide={() => setShowView(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Buyurtma: {selectedOrd?.id}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <div>
              <small className="text-muted d-block">Mijoz:</small>
              <span className="fw-bold fs-5">{selectedOrd?.customer}</span>
            </div>
            <div className="text-end">
              <small className="text-muted d-block">To'lov:</small>
              <span className="chip chip-gray">{selectedOrd?.payment}</span>
            </div>
          </div>

          <div className="row g-2 mb-4">
            <div className="col-6"><span className="text-muted small">Mahsulotlar:</span></div>
            <div className="col-6 fw-semibold">{selectedOrd?.items} dona</div>
            <div className="col-6"><span className="text-muted small">Umumiy summa:</span></div>
            <div className="col-6 fw-semibold text-primary">{fmt(selectedOrd?.total || 0)} so'm</div>
            <div className="col-6"><span className="text-muted small">Sana:</span></div>
            <div className="col-6 fw-semibold">{selectedOrd?.date}</div>
            <div className="col-6"><span className="text-muted small">Joriy status:</span></div>
            <div className="col-6">
              <span className={`chip ${statusChip(selectedOrd?.status || '')}`}>{selectedOrd?.status}</span>
            </div>
          </div>

          <h6 className="fw-semibold mb-2">Statusni o'zgartirish</h6>
          <div className="d-flex flex-wrap gap-1">
            {['Pending', 'Processing', 'Shipping', 'Delivered', 'Cancelled'].map((s) => (
              <button 
                key={s} 
                className={`btn btn-sm ${selectedOrd?.status === s ? 'btn-dark' : 'btn-outline-secondary'}`}
                onClick={() => handleUpdateStatus(s)}
              >
                {s}
              </button>
            ))}
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowView(false)} className="w-100">Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
