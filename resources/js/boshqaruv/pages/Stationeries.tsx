import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { stationeries } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface StatItem {
  id: number;
  name: string;
  category: string;
  price: number;
  stock: number;
  sold: number;
  icon: string;
}

export default function Stationeries() {
  const [list, setList] = useState<StatItem[]>(stationeries);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [selectedItem, setSelectedItem] = useState<StatItem | null>(null);

  // Form states
  const [name, setName] = useState('');
  const [category, setCategory] = useState('');
  const [price, setPrice] = useState('');
  const [stock, setStock] = useState('');
  const [icon, setIcon] = useState('✏️');

  const handleOpenAdd = () => {
    setName('');
    setCategory('');
    setPrice('');
    setStock('');
    setIcon('✏️');
    setShowAdd(true);
  };

  const handleOpenEdit = (s: StatItem) => {
    setSelectedItem(s);
    setName(s.name);
    setCategory(s.category);
    setPrice(String(s.price));
    setStock(String(s.stock));
    setIcon(s.icon);
    setShowEdit(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newS: StatItem = {
      id: Date.now(),
      name: name || 'Yangi kanselyariya',
      category: category || 'Umumiy',
      price: Number(price) || 0,
      stock: Number(stock) || 0,
      sold: 0,
      icon
    };
    setList([newS, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedItem) return;
    setList(list.map(s => s.id === selectedItem.id ? {
      ...s,
      name,
      category,
      price: Number(price) || 0,
      stock: Number(stock) || 0,
      icon
    } : s));
    setShowEdit(false);
  };

  const handleRestock = (id: number) => {
    setList(list.map(s => s.id === id ? { ...s, stock: s.stock + 50 } : s));
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kanselyariya mahsulotlari</h1>
          <p className="page-subtitle">Ruchkalar, daftarchalar, qog'ozlar va ofis buyumlari</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-plus-lg me-1"></i>Yangi mahsulot
        </button>
      </div>

      <div className="row g-3">
        {list.map((s) => (
          <div className="col-xl-4 col-md-6" key={s.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div style={{ width: 64, height: 64, borderRadius: 12, background: 'linear-gradient(135deg,#fef3c7,#fecaca)', display: 'grid', placeItems: 'center', fontSize: 32 }}>
                    {s.icon}
                  </div>
                  <div style={{ flex: 1 }}>
                    <div className="fw-bold">{s.name}</div>
                    <span className="chip chip-info mt-1">{s.category}</span>
                  </div>
                  <div className="text-end">
                    <div className="fw-bold" style={{ color: '#10b981' }}>{fmt(s.price)} so'm</div>
                    <small className="text-muted">Narx</small>
                  </div>
                </div>
                <div className="row text-center g-2 pt-3 border-top">
                  <div className="col">
                    <div className="fw-bold">{s.stock}</div>
                    <small className="text-muted">Omborda</small>
                  </div>
                  <div className="col">
                    <div className="fw-bold text-success">{s.sold}</div>
                    <small className="text-muted">Sotilgan</small>
                  </div>
                  <div className="col">
                    <div className="fw-bold">{fmt(s.price * s.sold)} so'm</div>
                    <small className="text-muted">Daromad</small>
                  </div>
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => handleOpenEdit(s)}>
                  <i className="bi bi-pencil"></i> Tahrirlash
                </button>
                <button className="btn btn-sm btn-primary-gradient flex-fill" onClick={() => handleRestock(s.id)}>
                  <i className="bi bi-box-arrow-up"></i> +50 Zaxira
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* ADD MODAL */}
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={handleAdd}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Yangi kanselyariya qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mahsulot nomi</Form.Label>
              <Form.Control required placeholder="A4 qog'oz 500 varaq" value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Kategoriya</Form.Label>
              <Form.Control required placeholder="Qog'ozlar" value={category} onChange={e => setCategory(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label>
                <Form.Control type="number" required placeholder="78000" value={price} onChange={e => setPrice(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Ombordagi soni</Form.Label>
                <Form.Control type="number" required placeholder="45" value={stock} onChange={e => setStock(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Ikonka / Emoji</Form.Label>
              <Form.Control value={icon} onChange={e => setIcon(e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Qo'shish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* EDIT MODAL */}
      <Modal show={showEdit} onHide={() => setShowEdit(false)} centered>
        <Form onSubmit={handleEdit}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Kanselyariyani tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mahsulot nomi</Form.Label>
              <Form.Control required value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Kategoriya</Form.Label>
              <Form.Control required value={category} onChange={e => setCategory(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label>
                <Form.Control type="number" required value={price} onChange={e => setPrice(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Ombordagi soni</Form.Label>
                <Form.Control type="number" required value={stock} onChange={e => setStock(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Ikonka / Emoji</Form.Label>
              <Form.Control value={icon} onChange={e => setIcon(e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowEdit(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
