import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import { tickets } from '../data';

interface Ticket {
  id: string;
  subject: string;
  user: string;
  priority: string;
  status: string;
  date: string;
}

const priorityChip = (p: string) => ({
  'Yuqori': 'chip-danger', "O'rta": 'chip-warning', 'Past': 'chip-gray',
}[p] || 'chip-gray');

const statusChip = (s: string) => ({
  'Open': 'chip-danger', 'In Progress': 'chip-info', 'Resolved': 'chip-success', 'Closed': 'chip-gray',
}[s] || 'chip-gray');

export default function Tickets() {
  const [list, setList] = useState<Ticket[]>(tickets);
  const [activeTab, setActiveTab] = useState('Barchasi');
  const [search, setSearch] = useState('');

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showReply, setShowReply] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);

  // Form states
  const [subject, setSubject] = useState('');
  const [user, setUser] = useState('');
  const [priority, setPriority] = useState('Yuqori');
  const [replyText, setReplyText] = useState('');

  const handleOpenAdd = () => {
    setSubject('');
    setUser('');
    setPriority('Yuqori');
    setShowAdd(true);
  };

  const handleOpenReply = (t: Ticket) => {
    setSelectedTicket(t);
    setReplyText('');
    setShowReply(true);
  };

  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    const newT: Ticket = {
      id: `#TK-${Math.floor(1000 + Math.random() * 9000)}`,
      subject: subject || 'Yangi murojaat',
      user: user || 'Mijoz',
      priority,
      status: 'Open',
      date: 'Hozir'
    };
    setList([newT, ...list]);
    setShowAdd(false);
  };

  const handleSendReply = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket) return;
    setList(list.map(t => t.id === selectedTicket.id ? { ...t, status: 'Resolved' } : t));
    setShowReply(false);
  };

  const filtered = list.filter(t => {
    const matchTab = activeTab === 'Barchasi' || t.status === activeTab;
    const matchSearch = t.subject.toLowerCase().includes(search.toLowerCase()) ||
                        t.user.toLowerCase().includes(search.toLowerCase()) ||
                        t.id.toLowerCase().includes(search.toLowerCase());
    return matchTab && matchSearch;
  });

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Murojaatlar (Tickets)</h1>
          <p className="page-subtitle">Mijozlardan kelgan shikoyat va savollarni boshqarish</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={handleOpenAdd}>
          <i className="bi bi-plus-lg me-1"></i>Yangi ticket
        </button>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: "Ochiq", val: list.filter(t => t.status === 'Open').length, icon: 'bi-envelope-exclamation', color: '#ef4444' },
          { label: "Jarayonda", val: list.filter(t => t.status === 'In Progress').length, icon: 'bi-hourglass-split', color: '#3b82f6' },
          { label: "Hal qilingan", val: list.filter(t => t.status === 'Resolved').length, icon: 'bi-check2-circle', color: '#10b981' },
          { label: "O'rt. javob vaqti", val: '2.4 soat', icon: 'bi-clock', color: '#f59e0b' },
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
          {['Barchasi', 'Open', 'In Progress', 'Resolved', 'Closed'].map((s) => (
            <button 
              key={s} 
              className={`btn btn-sm ${activeTab === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
              onClick={() => setActiveTab(s)}
            >
              {s}
            </button>
          ))}
          <div className="ms-auto input-group" style={{ maxWidth: 260 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input 
              className="form-control" 
              placeholder="Ticket qidirish..." 
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
          </div>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Mavzu</th>
                <th>Foydalanuvchi</th>
                <th>Muhimlik</th>
                <th>Sana</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((t) => (
                <tr key={t.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>{t.id}</td>
                  <td className="fw-semibold">{t.subject}</td>
                  <td>{t.user}</td>
                  <td><span className={`chip ${priorityChip(t.priority)}`}>{t.priority}</span></td>
                  <td className="text-muted">{t.date}</td>
                  <td><span className={`chip ${statusChip(t.status)}`}>{t.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-primary-gradient me-1" onClick={() => handleOpenReply(t)}>
                      <i className="bi bi-reply"></i> Javob
                    </button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => setList(list.filter(item => item.id !== t.id))} title="O'chirish">
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
            <Modal.Title className="fs-5 fw-bold">Yangi Murojaat (Ticket)</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mijoz ism-sharifi</Form.Label>
              <Form.Control required placeholder="Bobur Aliyev" value={user} onChange={e => setUser(e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Mavzu / Muammo</Form.Label>
              <Form.Control required placeholder="Buyurtma yetib kelmadi" value={subject} onChange={e => setSubject(e.target.value)} />
            </Form.Group>
            <Form.Group>
              <Form.Label className="small fw-semibold">Muhimlik darajasi</Form.Label>
              <Form.Select value={priority} onChange={e => setPriority(e.target.value)}>
                <option value="Yuqori">Yuqori</option>
                <option value="O'rta">O'rta</option>
                <option value="Past">Past</option>
              </Form.Select>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Yaratish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* REPLY MODAL */}
      <Modal show={showReply} onHide={() => setShowReply(false)} centered>
        <Form onSubmit={handleSendReply}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Murojaatga javob: {selectedTicket?.id}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <div className="mb-3">
              <small className="text-muted d-block">Mavzu:</small>
              <div className="fw-bold">{selectedTicket?.subject}</div>
              <small className="text-muted d-block mt-2">Mijoz:</small>
              <div className="fw-semibold">{selectedTicket?.user}</div>
            </div>

            <Form.Group>
              <Form.Label className="small fw-semibold">Javob matni</Form.Label>
              <Form.Control 
                as="textarea" 
                rows={4} 
                required 
                placeholder="Mijozga yuboriladigan rasmiy javob..." 
                value={replyText} 
                onChange={e => setReplyText(e.target.value)}
              />
              <Form.Text className="text-muted">
                Javob yuborilgach, ticket avtomatik ravishda <strong>Resolved (Hal qilingan)</strong> holatiga o'tadi.
              </Form.Text>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowReply(false)}>Bekor qilish</Button>
            <Button variant="success" type="submit" className="fw-semibold">
              <i className="bi bi-send me-1"></i>Yuborish va Yopish
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
