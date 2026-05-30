import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { topBooks } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface Book {
  id: number;
  title: string;
  author: string;
  price: number;
  stock: number;
  sold: number;
  cover: string;
}

export default function Books() {
  const [list, setList] = useState<Book[]>(topBooks);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showView, setShowView] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [showDelete, setShowDelete] = useState(false);

  const [selectedBook, setSelectedBook] = useState<Book | null>(null);

  // Form states
  const [title, setTitle] = useState('');
  const [author, setAuthor] = useState('');
  const [price, setPrice] = useState('');
  const [stock, setStock] = useState('');
  const [cover, setCover] = useState('📕');

  const handleOpenAdd = () => {
    setTitle('');
    setAuthor('');
    setPrice('');
    setStock('');
    setCover('📕');
    setShowAdd(true);
  };

  const handleOpenView = (b: Book) => {
    setSelectedBook(b);
    setShowView(true);
  };

  const handleOpenEdit = (b: Book) => {
    setSelectedBook(b);
    setTitle(b.title);
    setAuthor(b.author);
    setPrice(String(b.price));
    setStock(String(b.stock));
    setCover(b.cover);
    setShowEdit(true);
  };

  const handleOpenDelete = (b: Book) => {
    setSelectedBook(b);
    setShowDelete(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newB: Book = {
      id: Date.now(),
      title: title || 'Yangi kitob',
      author: author || 'Noma\'lum',
      price: Number(price) || 0,
      stock: Number(stock) || 0,
      sold: 0,
      cover
    };
    setList([newB, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedBook) return;
    setList(list.map(b => b.id === selectedBook.id ? {
      ...b,
      title,
      author,
      price: Number(price) || 0,
      stock: Number(stock) || 0,
      cover
    } : b));
    setShowEdit(false);
  };

  const handleDelete = () => {
    if (!selectedBook) return;
    setList(list.filter(b => b.id !== selectedBook.id));
    setShowDelete(false);
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kitoblar katalogi</h1>
          <p className="page-subtitle">{list.length} ta kitob — badiiy, ilmiy, bolalar adabiyoti</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => alert("Import tizimi tez orada ishga tushadi!")}>
            <i className="bi bi-upload me-1"></i>Import
          </button>
          <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
            <i className="bi bi-plus-lg me-1"></i>Yangi kitob
          </button>
        </div>
      </div>

      <div className="row g-3">
        {list.map((b) => (
          <div className="col-xl-3 col-md-6" key={b.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex gap-3">
                  <div style={{ width: 80, height: 104, borderRadius: 8, background: 'linear-gradient(135deg,#c7d2fe,#f3e8ff)', display: 'grid', placeItems: 'center', fontSize: 40, flexShrink: 0 }}>
                    {b.cover}
                  </div>
                  <div style={{ minWidth: 0, flex: 1 }}>
                    <div className="fw-bold" style={{ fontSize: 15 }}>{b.title}</div>
                    <div className="text-muted small mb-2">{b.author}</div>
                    <div className="fw-bold" style={{ color: '#4f46e5' }}>{fmt(b.price)} so'm</div>
                    <div className="d-flex gap-1 mt-2">
                      <span className="chip chip-success">{b.stock} dona</span>
                      <span className="chip chip-gray">⭐ 4.8</span>
                    </div>
                  </div>
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-3 border-top">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => handleOpenView(b)} title="Ko'rish">
                  <i className="bi bi-eye"></i>
                </button>
                <button className="btn btn-sm btn-light flex-fill" onClick={() => handleOpenEdit(b)} title="Tahrirlash">
                  <i className="bi bi-pencil"></i>
                </button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => handleOpenDelete(b)} title="O'chirish">
                  <i className="bi bi-trash"></i>
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
            <Modal.Title className="fs-5 fw-bold">Yangi kitob qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Kitob nomi</Form.Label>
              <Form.Control required placeholder="Yulduzli tunlar" value={title} onChange={e => setTitle(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Muallif</Form.Label>
              <Form.Control required placeholder="Pirimqul Qodirov" value={author} onChange={e => setAuthor(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Narxi (so'm)</Form.Label>
                <Form.Control type="number" required placeholder="85000" value={price} onChange={e => setPrice(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Ombordagi soni</Form.Label>
                <Form.Control type="number" required placeholder="30" value={stock} onChange={e => setStock(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Muqova / Emoji</Form.Label>
              <Form.Select value={cover} onChange={e => setCover(e.target.value)}>
                <option value="📕">📕 Qizil muqova</option>
                <option value="📗">📗 Yashil muqova</option>
                <option value="📘">📘 Ko'k muqova</option>
                <option value="📙">📙 Apelsin muqova</option>
                <option value="📓">📓 Qora muqova</option>
              </Form.Select>
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
            <Modal.Title className="fs-5 fw-bold">Kitobni tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Kitob nomi</Form.Label>
              <Form.Control required value={title} onChange={e => setTitle(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Muallif</Form.Label>
              <Form.Control required value={author} onChange={e => setAuthor(e.target.value)} />
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
              <Form.Label className="small fw-semibold">Muqova / Emoji</Form.Label>
              <Form.Select value={cover} onChange={e => setCover(e.target.value)}>
                <option value="📕">📕 Qizil muqova</option>
                <option value="📗">📗 Yashil muqova</option>
                <option value="📘">📘 Ko'k muqova</option>
                <option value="📙">📙 Apelsin muqova</option>
                <option value="📓">📓 Qora muqova</option>
              </Form.Select>
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
          <Modal.Title className="fs-5 fw-bold">Kitob ma'lumotlari</Modal.Title>
        </Modal.Header>
        <Modal.Body className="text-center">
          <div style={{ fontSize: 72 }}>{selectedBook?.cover}</div>
          <h4 className="fw-bold mt-2">{selectedBook?.title}</h4>
          <div className="text-muted mb-3">{selectedBook?.author}</div>

          <div className="row g-2 text-start border-top pt-3">
            <div className="col-6"><span className="text-muted small">Narxi:</span></div>
            <div className="col-6 fw-semibold text-primary">{fmt(selectedBook?.price || 0)} so'm</div>
            <div className="col-6"><span className="text-muted small">Omborda:</span></div>
            <div className="col-6 fw-semibold">{selectedBook?.stock} dona</div>
            <div className="col-6"><span className="text-muted small">Sotilgan:</span></div>
            <div className="col-6 fw-semibold text-success">{selectedBook?.sold} marta</div>
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
          Haqiqatan ham <strong>{selectedBook?.title}</strong> kitobini o'chirmoqchimisiz?
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowDelete(false)}>Bekor qilish</Button>
          <Button variant="danger" onClick={handleDelete}>O'chirish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
