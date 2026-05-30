import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { topBooks, stationeries } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface Prod {
  id: number;
  title: string;
  type: string;
  price: number;
  stock: number;
  sold: number;
  img: string;
  category?: string;
  author?: string;
}

const initialAll: Prod[] = [
  ...topBooks.map(b => ({ ...b, type: 'Kitob', img: b.cover })),
  ...stationeries.map(s => ({
    id: s.id + 100,
    title: s.name,
    type: 'Kanselyariya',
    price: s.price,
    stock: s.stock,
    sold: s.sold,
    img: s.icon,
    category: s.category
  })),
];

export default function Products() {
  const [list, setList] = useState<Prod[]>(initialAll);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('Barchasi');
  const [stockFilter, setStockFilter] = useState('Barchasi');

  // Modals state
  const [showAdd, setShowAdd] = useState(false);
  const [showView, setShowView] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [showDelete, setShowDelete] = useState(false);

  const [selectedProd, setSelectedProd] = useState<Prod | null>(null);

  // Form states
  const [formTitle, setFormTitle] = useState('');
  const [formType, setFormType] = useState('Kitob');
  const [formPrice, setFormPrice] = useState('');
  const [formStock, setFormStock] = useState('');
  const [formCategory, setFormCategory] = useState('');
  const [formImg, setFormImg] = useState('📦');

  // Open Modals
  const handleOpenAdd = () => {
    setFormTitle('');
    setFormType('Kitob');
    setFormPrice('');
    setFormStock('');
    setFormCategory('');
    setFormImg('📦');
    setShowAdd(true);
  };

  const handleOpenView = (p: Prod) => {
    setSelectedProd(p);
    setShowView(true);
  };

  const handleOpenEdit = (p: Prod) => {
    setSelectedProd(p);
    setFormTitle(p.title);
    setFormType(p.type);
    setFormPrice(String(p.price));
    setFormStock(String(p.stock));
    setFormCategory(p.category || '');
    setFormImg(p.img);
    setShowEdit(true);
  };

  const handleOpenDelete = (p: Prod) => {
    setSelectedProd(p);
    setShowDelete(true);
  };

  // CRUD Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newP: Prod = {
      id: Date.now(),
      title: formTitle || 'Yangi mahsulot',
      type: formType,
      price: Number(formPrice) || 0,
      stock: Number(formStock) || 0,
      sold: 0,
      img: formImg,
      category: formCategory || 'Umumiy'
    };
    setList([newP, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProd) return;
    setList(list.map(p => p.id === selectedProd.id ? {
      ...p,
      title: formTitle,
      type: formType,
      price: Number(formPrice) || 0,
      stock: Number(formStock) || 0,
      category: formCategory,
      img: formImg
    } : p));
    setShowEdit(false);
  };

  const handleDelete = () => {
    if (!selectedProd) return;
    setList(list.filter(p => p.id !== selectedProd.id));
    setShowDelete(false);
  };

  // Filters
  const filtered = list.filter(p => {
    const matchSearch = p.title.toLowerCase().includes(search.toLowerCase()) || 
                        (p.category && p.category.toLowerCase().includes(search.toLowerCase()));
    const matchType = typeFilter === 'Barchasi' || p.type === typeFilter;
    let matchStock = true;
    if (stockFilter === 'Omborda') matchStock = p.stock > 0;
    if (stockFilter === 'Kam qolgan') matchStock = p.stock > 0 && p.stock < 15;
    if (stockFilter === 'Tugagan') matchStock = p.stock === 0;

    return matchSearch && matchType && matchStock;
  });

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Barcha mahsulotlar</h1>
          <p className="page-subtitle">Jami {list.length} ta mahsulot ro'yxati</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-plus-lg me-1"></i>Yangi mahsulot
        </button>
      </div>

      <div className="card-panel">
        <div className="d-flex flex-wrap gap-2 mb-3">
          <div className="input-group" style={{ maxWidth: 320 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input 
              className="form-control" 
              placeholder="Mahsulot nomi, kategoriya..." 
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
          </div>
          <select className="form-select" style={{ width: 'auto' }} value={typeFilter} onChange={e => setTypeFilter(e.target.value)}>
            <option value="Barchasi">Barcha turlar</option>
            <option value="Kitob">Kitoblar</option>
            <option value="Kanselyariya">Kanselyariya</option>
          </select>
          <select className="form-select" style={{ width: 'auto' }} value={stockFilter} onChange={e => setStockFilter(e.target.value)}>
            <option value="Barchasi">Barcha holatlar</option>
            <option value="Omborda">Omborda</option>
            <option value="Kam qolgan">Kam qolgan</option>
            <option value="Tugagan">Tugagan</option>
          </select>
          <div className="ms-auto btn-group">
            <button className="btn btn-outline-secondary btn-sm active"><i className="bi bi-grid"></i></button>
            <button className="btn btn-outline-secondary btn-sm"><i className="bi bi-list"></i></button>
          </div>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th><input type="checkbox" className="form-check-input" /></th>
                <th></th>
                <th>Mahsulot</th>
                <th>Kategoriya</th>
                <th>Narx</th>
                <th>Ombor</th>
                <th>Sotilgan</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 ? (
                <tr><td colSpan={9} className="text-center py-4 text-muted">Mahsulot topilmadi</td></tr>
              ) : filtered.map((p) => (
                <tr key={p.id}>
                  <td><input type="checkbox" className="form-check-input" /></td>
                  <td><div className="thumb d-grid place-items-center" style={{ fontSize: 20 }}>{p.img}</div></td>
                  <td>
                    <div className="fw-semibold">{p.title}</div>
                    <small className="text-muted">ID: #PRD-{String(p.id).slice(-4)}</small>
                  </td>
                  <td><span className={`chip ${p.type === 'Kitob' ? 'chip-purple' : 'chip-info'}`}>{p.category || p.type}</span></td>
                  <td className="fw-semibold">{fmt(p.price)} so'm</td>
                  <td>
                    <span className={`chip ${p.stock < 15 ? 'chip-danger' : p.stock < 30 ? 'chip-warning' : 'chip-success'}`}>
                      {p.stock} dona
                    </span>
                  </td>
                  <td>{p.sold}</td>
                  <td><span className="chip chip-success">Faol</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenView(p)} title="Ko'rish">
                      <i className="bi bi-eye"></i>
                    </button>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenEdit(p)} title="Tahrirlash">
                      <i className="bi bi-pencil"></i>
                    </button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => handleOpenDelete(p)} title="O'chirish">
                      <i className="bi bi-trash"></i>
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
            <Modal.Title className="fs-5 fw-bold">Yangi mahsulot qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mahsulot nomi</Form.Label>
              <Form.Control required placeholder="Masalan: Qalamlar to'plami" value={formTitle} onChange={e => setFormTitle(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Turi</Form.Label>
                <Form.Select value={formType} onChange={e => setFormType(e.target.value)}>
                  <option value="Kitob">Kitob</option>
                  <option value="Kanselyariya">Kanselyariya</option>
                </Form.Select>
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kategoriya</Form.Label>
                <Form.Control placeholder="Ruchka, Badiiy..." value={formCategory} onChange={e => setFormCategory(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label>
                <Form.Control type="number" required placeholder="15000" value={formPrice} onChange={e => setFormPrice(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Ombordagi soni</Form.Label>
                <Form.Control type="number" required placeholder="50" value={formStock} onChange={e => setFormStock(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Ikonka / Emoji</Form.Label>
              <Form.Control value={formImg} onChange={e => setFormImg(e.target.value)} />
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
            <Modal.Title className="fs-5 fw-bold">Mahsulotni tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mahsulot nomi</Form.Label>
              <Form.Control required value={formTitle} onChange={e => setFormTitle(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Turi</Form.Label>
                <Form.Select value={formType} onChange={e => setFormType(e.target.value)}>
                  <option value="Kitob">Kitob</option>
                  <option value="Kanselyariya">Kanselyariya</option>
                </Form.Select>
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kategoriya</Form.Label>
                <Form.Control value={formCategory} onChange={e => setFormCategory(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label>
                <Form.Control type="number" required value={formPrice} onChange={e => setFormPrice(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Ombordagi soni</Form.Label>
                <Form.Control type="number" required value={formStock} onChange={e => setFormStock(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Ikonka / Emoji</Form.Label>
              <Form.Control value={formImg} onChange={e => setFormImg(e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowEdit(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* VIEW MODAL */}
      <Modal show={showView} onHide={() => setShowView(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Mahsulot ma'lumotlari</Modal.Title>
        </Modal.Header>
        <Modal.Body className="text-center">
          <div style={{ fontSize: 64 }}>{selectedProd?.img}</div>
          <h4 className="fw-bold mt-2">{selectedProd?.title}</h4>
          <span className="chip chip-purple mb-3">{selectedProd?.type}</span>

          <div className="row g-2 text-start mt-3 border-top pt-3">
            <div className="col-6"><span className="text-muted small">Kategoriya:</span></div>
            <div className="col-6 fw-semibold">{selectedProd?.category || selectedProd?.type}</div>
            <div className="col-6"><span className="text-muted small">Narxi:</span></div>
            <div className="col-6 fw-semibold text-primary">{fmt(selectedProd?.price || 0)} so'm</div>
            <div className="col-6"><span className="text- mounts small">Omborda:</span></div>
            <div className="col-6 fw-semibold">{selectedProd?.stock} dona</div>
            <div className="col-6"><span className="text-muted small">Sotilgan:</span></div>
            <div className="col-6 fw-semibold text-success">{selectedProd?.sold} marta</div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowView(false)} className="w-100">Yopish</Button>
        </Modal.Footer>
      </Modal>

      {/* DELETE MODAL */}
      <Modal show={showDelete} onHide={() => setShowDelete(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold text-danger">O'chirishni tasdiqlang</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          Haqiqatan ham <strong>{selectedProd?.title}</strong> mahsulotini o'chirmoqchimisiz? Bu amalni bekor qilib bo'lmaydi.
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowDelete(false)}>Bekor qilish</Button>
          <Button variant="danger" onClick={handleDelete}>O'chirish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
