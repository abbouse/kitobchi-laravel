import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { publishers } from '../data';

interface Pub {
  id: number;
  name: string;
  city: string;
  books: number;
  contact: string;
  rating: number;
}

export default function Publishers() {
  const [list, setList] = useState<Pub[]>(publishers);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [selectedPub, setSelectedPub] = useState<Pub | null>(null);

  // Form states
  const [name, setName] = useState('');
  const [city, setCity] = useState('');
  const [books, setBooks] = useState('');
  const [contact, setContact] = useState('');
  const [rating, setRating] = useState('4.8');

  const handleOpenAdd = () => {
    setName('');
    setCity('Toshkent');
    setBooks('');
    setContact('');
    setRating('4.8');
    setShowAdd(true);
  };

  const handleOpenEdit = (p: Pub) => {
    setSelectedPub(p);
    setName(p.name);
    setCity(p.city);
    setBooks(String(p.books));
    setContact(p.contact);
    setRating(String(p.rating));
    setShowEdit(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newP: Pub = {
      id: Date.now(),
      name: name || 'Yangi nashriyot',
      city: city || 'Toshkent',
      books: Number(books) || 0,
      contact: contact || '+998 71 200 00 00',
      rating: Number(rating) || 4.8
    };
    setList([newP, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPub) return;
    setList(list.map(p => p.id === selectedPub.id ? {
      ...p,
      name,
      city,
      books: Number(books) || 0,
      contact,
      rating: Number(rating) || 4.8
    } : p));
    setShowEdit(false);
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Nashriyotlar</h1>
          <p className="page-subtitle">Hamkor nashriyotlar va ular bilan aloqalar</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-plus-lg me-1"></i>Yangi nashriyot
        </button>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th>Nashriyot</th>
                <th>Shahar</th>
                <th>Kitoblar soni</th>
                <th>Kontakt</th>
                <th>Reyting</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {list.map((p, i) => (
                <tr key={p.id}>
                  <td>
                    <div className="d-flex align-items-center gap-2">
                      <div style={{ width: 40, height: 40, borderRadius: 10, background: `linear-gradient(135deg, hsl(${i*50+200},70%,55%), hsl(${i*50+240},70%,45%))`, color: 'white', display: 'grid', placeItems: 'center', fontSize: 18 }}>
                        <i className="bi bi-building"></i>
                      </div>
                      <div className="fw-semibold">{p.name}</div>
                    </div>
                  </td>
                  <td>{p.city}</td>
                  <td className="fw-semibold">{p.books}</td>
                  <td className="text-muted">{p.contact}</td>
                  <td><span className="chip chip-warning">⭐ {p.rating}</span></td>
                  <td><span className="chip chip-success">Faol</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenEdit(p)} title="Tahrirlash">
                      <i className="bi bi-pencil"></i>
                    </button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(item => item.id !== p.id))} title="O'chirish">
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
            <Modal.Title className="fs-5 fw-bold">Yangi nashriyot qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Nashriyot nomi</Form.Label>
              <Form.Control required placeholder="Hilol Nashr" value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Shahar</Form.Label>
                <Form.Control required value={city} onChange={e => setCity(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kitoblar soni</Form.Label>
                <Form.Control type="number" required placeholder="120" value={books} onChange={e => setBooks(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Telefon / Kontakt</Form.Label>
                <Form.Control required placeholder="+998 71 200 11 22" value={contact} onChange={e => setContact(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Reyting</Form.Label>
                <Form.Control type="number" step="0.1" max="5" min="1" required value={rating} onChange={e => setRating(e.target.value)} />
              </Form.Group>
            </div>
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
            <Modal.Title className="fs-5 fw-bold">Nashriyotni tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Nashriyot nomi</Form.Label>
              <Form.Control required value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Shahar</Form.Label>
                <Form.Control required value={city} onChange={e => setCity(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kitoblar soni</Form.Label>
                <Form.Control type="number" required value={books} onChange={e => setBooks(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Telefon / Kontakt</Form.Label>
                <Form.Control required value={contact} onChange={e => setContact(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Reyting</Form.Label>
                <Form.Control type="number" step="0.1" max="5" min="1" required value={rating} onChange={e => setRating(e.target.value)} />
              </Form.Group>
            </div>
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
