import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { authors } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface Author {
  id: number;
  name: string;
  country: string;
  books: number;
  followers: number;
  bio: string;
}

export default function Authors() {
  const [list, setList] = useState<Author[]>(authors);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showView, setShowView] = useState(false);
  const [showEdit, setShowEdit] = useState(false);

  const [selectedAuthor, setSelectedAuthor] = useState<Author | null>(null);

  // Form states
  const [name, setName] = useState('');
  const [country, setCountry] = useState('');
  const [books, setBooks] = useState('');
  const [followers, setFollowers] = useState('');
  const [bio, setBio] = useState('');

  const handleOpenAdd = () => {
    setName('');
    setCountry("O'zbekiston");
    setBooks('');
    setFollowers('');
    setBio('');
    setShowAdd(true);
  };

  const handleOpenView = (a: Author) => {
    setSelectedAuthor(a);
    setShowView(true);
  };

  const handleOpenEdit = (a: Author) => {
    setSelectedAuthor(a);
    setName(a.name);
    setCountry(a.country);
    setBooks(String(a.books));
    setFollowers(String(a.followers));
    setBio(a.bio);
    setShowEdit(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newA: Author = {
      id: Date.now(),
      name: name || 'Yangi muallif',
      country: country || "O'zbekiston",
      books: Number(books) || 0,
      followers: Number(followers) || 0,
      bio: bio || "Ma'lumot kiritilmagan"
    };
    setList([newA, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedAuthor) return;
    setList(list.map(a => a.id === selectedAuthor.id ? {
      ...a,
      name,
      country,
      books: Number(books) || 0,
      followers: Number(followers) || 0,
      bio
    } : a));
    setShowEdit(false);
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Mualliflar</h1>
          <p className="page-subtitle">Kitob mualliflari va ularning statistikasi</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-plus-lg me-1"></i>Yangi muallif
        </button>
      </div>

      <div className="row g-3">
        {list.map((a, i) => (
          <div className="col-xl-4 col-md-6" key={a.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div style={{ width: 64, height: 64, borderRadius: '50%', background: `linear-gradient(135deg, hsl(${i*60},70%,60%), hsl(${i*60+40},70%,50%))`, display: 'grid', placeItems: 'center', fontSize: 24, fontWeight: 700, color: 'white' }}>
                    {a.name.split(' ').map(n => n[0]).join('')}
                  </div>
                  <div style={{ flex: 1 }}>
                    <div className="fw-bold">{a.name}</div>
                    <div className="text-muted small"><i className="bi bi-geo-alt"></i> {a.country}</div>
                    <div className="text-muted small mt-1">{a.bio}</div>
                  </div>
                </div>
                <div className="d-flex justify-content-around pt-3 border-top text-center">
                  <div>
                    <div className="fw-bold" style={{ color: '#4f46e5', fontSize: 20 }}>{a.books}</div>
                    <small className="text-muted">Kitob</small>
                  </div>
                  <div>
                    <div className="fw-bold" style={{ color: '#ec4899', fontSize: 20 }}>{fmt(a.followers)}</div>
                    <small className="text-muted">Obunachi</small>
                  </div>
                  <div>
                    <div className="fw-bold" style={{ color: '#10b981', fontSize: 20 }}>⭐ 4.{7 + i%3}</div>
                    <small className="text-muted">Reyting</small>
                  </div>
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => handleOpenView(a)}>
                  <i className="bi bi-eye"></i> Ko'rish
                </button>
                <button className="btn btn-sm btn-primary-gradient flex-fill" onClick={() => handleOpenEdit(a)}>
                  <i className="bi bi-pencil"></i> Tahrirlash
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
            <Modal.Title className="fs-5 fw-bold">Yangi muallif qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Muallifning ism-sharifi</Form.Label>
              <Form.Control required placeholder="Erkin Vohidov" value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Davlat</Form.Label>
              <Form.Control required value={country} onChange={e => setCountry(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kitoblar soni</Form.Label>
                <Form.Control type="number" required placeholder="14" value={books} onChange={e => setBooks(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Obunachilar soni</Form.Label>
                <Form.Control type="number" required placeholder="16780" value={followers} onChange={e => setFollowers(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Qisqacha tarjimayi holi</Form.Label>
              <Form.Control as="textarea" rows={3} value={bio} onChange={e => setBio(e.target.value)} />
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
            <Modal.Title className="fs-5 fw-bold">Muallifni tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Muallifning ism-sharifi</Form.Label>
              <Form.Control required value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Davlat</Form.Label>
              <Form.Control required value={country} onChange={e => setCountry(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Kitoblar soni</Form.Label>
                <Form.Control type="number" required value={books} onChange={e => setBooks(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Obunachilar soni</Form.Label>
                <Form.Control type="number" required value={followers} onChange={e => setFollowers(e.target.value)} />
              </Form.Group>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Qisqacha tarjimayi holi</Form.Label>
              <Form.Control as="textarea" rows={3} value={bio} onChange={e => setBio(e.target.value)} />
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
          <Modal.Title className="fs-5 fw-bold">Muallif profili</Modal.Title>
        </Modal.Header>
        <Modal.Body className="text-center">
          <div style={{ width: 80, height: 80, borderRadius: '50%', background: 'linear-gradient(135deg,#4f46e5,#7c3aed)', color: 'white', display: 'grid', placeItems: 'center', fontSize: 32, fontWeight: 700, margin: '0 auto' }}>
            {selectedAuthor?.name.split(' ').map(n => n[0]).join('')}
          </div>
          <h4 className="fw-bold mt-3 mb-1">{selectedAuthor?.name}</h4>
          <span className="text-muted small d-block mb-3"><i className="bi bi-geo-alt"></i> {selectedAuthor?.country}</span>
          
          <p className="bg-light p-3 rounded text-start text-muted small mb-4">
            {selectedAuthor?.bio}
          </p>

          <div className="row g-2 border-top pt-3 text-start">
            <div className="col-6"><span className="text-muted small">Nashr qilingan kitoblari:</span></div>
            <div className="col-6 fw-semibold text-primary">{selectedAuthor?.books} ta</div>
            <div className="col-6"><span className="text-muted small">Kuzatuvchilar:</span></div>
            <div className="col-6 fw-semibold text-danger">{fmt(selectedAuthor?.followers || 0)} nafar</div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowView(false)} className="w-100">Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
