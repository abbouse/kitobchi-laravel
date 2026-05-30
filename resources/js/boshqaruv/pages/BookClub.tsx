import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { bookClubPosts } from '../data';

interface Post {
  id: number;
  author: string;
  title: string;
  likes: number;
  comments: number;
  date: string;
  tag: string;
}

const tagChip = (t: string) => ({
  'Muhokama': 'chip-info', 'Tavsiya': 'chip-success', 'Tahlil': 'chip-purple', 'Sharh': 'chip-warning',
}[t] || 'chip-gray');

export default function BookClub() {
  const [list, setList] = useState<Post[]>(bookClubPosts);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showView, setShowView] = useState(false);
  const [selectedPost, setSelectedPost] = useState<Post | null>(null);

  // Form states
  const [author, setAuthor] = useState('');
  const [title, setTitle] = useState('');
  const [tag, setTag] = useState('Muhokama');

  const handleOpenAdd = () => {
    setAuthor('');
    setTitle('');
    setTag('Muhokama');
    setShowAdd(true);
  };

  const handleOpenView = (p: Post) => {
    setSelectedPost(p);
    setShowView(true);
  };

  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newP: Post = {
      id: Date.now(),
      author: author || 'Kitobxon',
      title: title || 'Yangi muhokama',
      likes: 1,
      comments: 0,
      date: 'Hozirgi vaqt',
      tag
    };
    setList([newP, ...list]);
    setShowAdd(false);
  };

  const handleLike = (id: number) => {
    setList(list.map(p => p.id === id ? { ...p, likes: p.likes + 1 } : p));
    if (selectedPost && selectedPost.id === id) {
      setSelectedPost({ ...selectedPost, likes: selectedPost.likes + 1 });
    }
  };

  const handleAddComment = () => {
    if (!selectedPost) return;
    setList(list.map(p => p.id === selectedPost.id ? { ...p, comments: p.comments + 1 } : p));
    setSelectedPost({ ...selectedPost, comments: selectedPost.comments + 1 });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Book Club postlari</h1>
          <p className="page-subtitle">O'quvchilar hamjamiyati va muhokamalar</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-megaphone me-1"></i>Yangi post
        </button>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          {list.map((p, i) => (
            <div className="card-panel mb-3" key={p.id}>
              <div className="d-flex align-items-start gap-3">
                <div style={{ width: 48, height: 48, borderRadius: '50%', background: `linear-gradient(135deg, hsl(${i*70},70%,60%), hsl(${i*70+40},70%,50%))`, color: 'white', display: 'grid', placeItems: 'center', fontWeight: 700, flexShrink: 0 }}>
                  {p.author.split(' ').map(n => n[0]).join('')}
                </div>
                <div style={{ flex: 1 }}>
                  <div className="d-flex align-items-center gap-2 flex-wrap">
                    <span className="fw-semibold">{p.author}</span>
                    <span className="text-muted small">· {p.date}</span>
                    <span className={`chip ${tagChip(p.tag)}`}>{p.tag}</span>
                  </div>
                  <h5 className="mt-2 mb-2 fw-bold" style={{ cursor: 'pointer' }} onClick={() => handleOpenView(p)}>
                    {p.title}
                  </h5>
                  <p className="text-muted mb-3" style={{ cursor: 'pointer' }} onClick={() => handleOpenView(p)}>
                    Bu kitob meni hayratda qoldirdi. Muallifning yozish uslubi, qahramonlarning xarakteri va syujet rivoji juda ta'sirli. O'qishni boshlagan kuningizdanoq to'xtata olmaysiz...
                  </p>
                  <div className="d-flex align-items-center gap-3 flex-wrap">
                    <button className="btn btn-sm btn-light" onClick={() => handleLike(p.id)}>
                      <i className="bi bi-heart-fill text-danger"></i> {p.likes}
                    </button>
                    <button className="btn btn-sm btn-light" onClick={() => handleOpenView(p)}>
                      <i className="bi bi-chat"></i> {p.comments}
                    </button>
                    <button className="btn btn-sm btn-light" onClick={() => alert("Havola nusxalandi!")}>
                      <i className="bi bi-share"></i> Ulashish
                    </button>
                    <button className="btn btn-sm btn-light text-danger ms-auto" onClick={() => alert("Shikoyat qabul qilindi.")}>
                      <i className="bi bi-flag"></i> Shikoyat
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="col-xl-4">
          <div className="card-panel mb-3">
            <div className="panel-title mb-3">🔥 Eng faol a'zolar</div>
            {[
              { n: 'Dilnoza R.', posts: 42, pts: 1240 },
              { n: 'Shaxlo Y.', posts: 38, pts: 1120 },
              { n: 'Aziza K.', posts: 31, pts: 980 },
              { n: 'Bobur A.', posts: 24, pts: 760 },
            ].map((m, i) => (
              <div key={m.n} className="d-flex align-items-center gap-2 py-2 border-bottom">
                <div style={{ width: 24, height: 24, borderRadius: '50%', background: i===0?'#fbbf24':i===1?'#d1d5db':i===2?'#d97706':'#f3f4f6', display: 'grid', placeItems: 'center', fontSize: 12, fontWeight: 700, color: i===3?'#374151':'white' }}>{i+1}</div>
                <div className="fw-semibold small" style={{ flex: 1 }}>{m.n}</div>
                <span className="chip chip-purple">{m.pts} ball</span>
              </div>
            ))}
          </div>
          <div className="card-panel">
            <div className="panel-title mb-3">📊 Hamjamiyat statistikasi</div>
            <div className="mb-3"><div className="text-muted small">Jami postlar</div><div className="fw-bold fs-4">{list.length + 2179}</div></div>
            <div className="mb-3"><div className="text-muted small">Izohlar</div><div className="fw-bold fs-4">{list.reduce((acc, p) => acc + p.comments, 0) + 8390}</div></div>
            <div><div className="text-muted small">Faol a'zolar (30 kun)</div><div className="fw-bold fs-4 text-success">842</div></div>
          </div>
        </div>
      </div>

      {/* ADD MODAL */}
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={handleAdd}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Yangi post yaratish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Muallif (Sizning ismingiz)</Form.Label>
              <Form.Control required placeholder="Aziza K." value={author} onChange={e => setAuthor(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Post sarlavhasi</Form.Label>
              <Form.Control required placeholder="O'tkan kunlar haqida taassurot" value={title} onChange={e => setTitle(e.target.value)} />
            </Form.Group>
            <Form.Group>
              <Form.Label className="small fw-semibold">Tegi</Form.Label>
              <Form.Select value={tag} onChange={e => setTag(e.target.value)}>
                <option value="Muhokama">Muhokama</option>
                <option value="Tavsiya">Tavsiya</option>
                <option value="Tahlil">Tahlil</option>
                <option value="Sharh">Sharh</option>
              </Form.Select>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Chop etish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* VIEW MODAL */}
      <Modal show={showView} onHide={() => setShowView(false)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Post tafsilotlari</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="d-flex align-items-center gap-2 mb-3">
            <span className="fw-bold">{selectedPost?.author}</span>
            <span className="text-muted small">· {selectedPost?.date}</span>
            <span className={`chip ${tagChip(selectedPost?.tag || '')}`}>{selectedPost?.tag}</span>
          </div>

          <h4 className="fw-bold mb-3">{selectedPost?.title}</h4>
          <p className="text-muted mb-4" style={{ lineHeight: 1.6 }}>
            Bu kitob meni hayratda qoldirdi. Muallifning yozish uslubi, qahramonlarning xarakteri va syujet rivoji juda ta'sirli. O'qishni boshlagan kuningizdanoq to'xtata olmaysiz. Ayniqsa qahramonlarning ichki kechinmalari va o'sha davr muhiti o'quvchini o'ziga to'liq jalb qiladi. Barchaga o'qishni tavsiya qilaman!
          </p>

          <div className="d-flex gap-3 border-top pt-3 mb-4">
            <button className="btn btn-light" onClick={() => handleLike(selectedPost?.id || 0)}>
              <i className="bi bi-heart-fill text-danger me-1"></i> {selectedPost?.likes}
            </button>
            <button className="btn btn-light">
              <i className="bi bi-chat me-1"></i> {selectedPost?.comments} Izohlar
            </button>
          </div>

          <h6 className="fw-bold mb-3">Izohlar</h6>
          <div className="bg-light p-3 rounded mb-3">
            <div className="d-flex justify-content-between mb-1">
              <span className="fw-semibold small">Sardor A.</span>
              <span className="text-muted small">1 soat oldin</span>
            </div>
            <p className="small mb-0 text-muted">Juda to'g'ri fikrlar! Men ham bu asarni qayta-qayta o'qiyman.</p>
          </div>

          <div className="input-group mt-3">
            <input className="form-control" placeholder="O'z fikringizni yozing..." />
            <button className="btn btn-primary-gradient" onClick={handleAddComment}>Yuborish</button>
          </div>
        </Modal.Body>
      </Modal>
    </div>
  );
}
