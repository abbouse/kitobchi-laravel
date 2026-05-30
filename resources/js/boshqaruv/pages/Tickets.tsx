import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

interface Ticket {
  id: number;
  subject: string;
  user: string;
  operator?: string;
  messages: number;
  rating?: number;
  status: string;
  date?: string;
  showUrl?: string;
  assignUrl?: string;
  closeUrl?: string;
  replyUrl?: string;
}

const statusChip = (s: string) => ({
  queue: 'chip-warning',
  active: 'chip-info',
  closed: 'chip-gray',
  rated: 'chip-success',
}[s] || 'chip-gray');

export default function Tickets() {
  const { tickets = [] } = usePage<{ tickets?: Ticket[] }>().props;
  const [activeTab, setActiveTab] = useState('Barchasi');
  const [search, setSearch] = useState('');
  const [showReply, setShowReply] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
  const [replyText, setReplyText] = useState('');

  const handleOpenReply = (ticket: Ticket) => {
    setSelectedTicket(ticket);
    setReplyText('');
    setShowReply(true);
  };

  const closeTicket = (ticket: Ticket) => {
    if (!ticket.closeUrl || !confirm(`#${ticket.id} ticket yopilsinmi?`)) return;
    router.patch(ticket.closeUrl, {}, { preserveScroll: true });
  };

  const sendReply = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket?.replyUrl) return;
    router.post(selectedTicket.replyUrl, { message: replyText }, { preserveScroll: true, onSuccess: () => setShowReply(false) });
  };

  const filtered = tickets.filter((ticket) => {
    const matchTab = activeTab === 'Barchasi' || ticket.status === activeTab;
    const haystack = `${ticket.subject} ${ticket.user} ${ticket.id}`.toLowerCase();
    return matchTab && haystack.includes(search.toLowerCase());
  });
  const pagination = useClientPagination(filtered, 25);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Murojaatlar</h1>
          <p className="page-subtitle">Telegram/support ticketlar va operator javoblari</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Navbatda', val: tickets.filter(t => t.status === 'queue').length, icon: 'bi-envelope-exclamation', color: '#f59e0b' },
          { label: 'Aktiv', val: tickets.filter(t => t.status === 'active').length, icon: 'bi-chat-dots', color: '#3b82f6' },
          { label: 'Yopilgan', val: tickets.filter(t => t.status === 'closed').length, icon: 'bi-check2-circle', color: '#10b981' },
          { label: 'Baholangan', val: tickets.filter(t => t.status === 'rated').length, icon: 'bi-star', color: '#7c3aed' },
        ].map((s) => (
          <div className="col-xl-3 col-md-6" key={s.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: s.color }}><i className={`bi ${s.icon}`}></i></div>
                <div><div className="stat-value">{s.val}</div><div className="stat-label">{s.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          {['Barchasi', 'queue', 'active', 'closed', 'rated'].map((s) => (
            <button key={s} className={`btn btn-sm ${activeTab === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => setActiveTab(s)}>{s}</button>
          ))}
          <div className="ms-auto input-group" style={{ maxWidth: 260 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Ticket qidirish..." value={search} onChange={e => setSearch(e.target.value)} />
          </div>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Mavzu</th><th>Foydalanuvchi</th><th>Operator</th><th>Xabar</th><th>Reyting</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {pagination.paginated.map((ticket) => (
                <tr key={ticket.id}>
                  <td className="fw-semibold text-primary">#{ticket.id}</td>
                  <td className="fw-semibold">{ticket.subject}</td>
                  <td>{ticket.user}</td>
                  <td>{ticket.operator || '—'}</td>
                  <td>{ticket.messages}</td>
                  <td>{ticket.rating || '—'}</td>
                  <td className="text-muted">{ticket.date || '—'}</td>
                  <td><span className={`chip ${statusChip(ticket.status)}`}>{ticket.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-primary-gradient me-1" onClick={() => handleOpenReply(ticket)}><i className="bi bi-reply"></i></button>
                    {ticket.closeUrl ? <button className="btn btn-sm btn-light" onClick={() => closeTicket(ticket)}><i className="bi bi-check2"></i></button> : null}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...pagination} onPageChange={pagination.setPage} />
      </div>

      <Modal show={showReply} onHide={() => setShowReply(false)} centered>
        <Form onSubmit={sendReply}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Murojaatga javob: #{selectedTicket?.id}</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="mb-3">
              <small className="text-muted d-block">Mavzu:</small>
              <div className="fw-bold">{selectedTicket?.subject}</div>
              <small className="text-muted d-block mt-2">Mijoz:</small>
              <div className="fw-semibold">{selectedTicket?.user}</div>
            </div>
            <Form.Group>
              <Form.Label className="small fw-semibold">Javob matni</Form.Label>
              <Form.Control as="textarea" rows={4} required value={replyText} onChange={e => setReplyText(e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowReply(false)}>Bekor qilish</Button>
            <Button variant="success" type="submit" className="fw-semibold"><i className="bi bi-send me-1"></i>Yuborish</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
