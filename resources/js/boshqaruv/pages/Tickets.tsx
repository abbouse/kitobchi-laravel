import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

interface Ticket {
  id: number;
  source?: string;
  sourceLabel?: string;
  subject: string;
  user: string;
  operator?: string;
  messages: number;
  rating?: number;
  status: string;
  date?: string;
  dataUrl?: string | null;
  closeUrl?: string | null;
  replyUrl?: string | null;
}
interface TicketDetail {
  profile: Record<string, string | number | null | undefined>;
  messages: Array<Record<string, string | number | boolean | null | undefined>>;
  attachments: Array<Record<string, string | number | null | undefined>>;
  actions: Record<string, string>;
}

const statusChip = (s: string) => ({
  queue: 'chip-warning',
  active: 'chip-info',
  open: 'chip-warning',
  answered: 'chip-success',
  waiting: 'chip-info',
  closed: 'chip-gray',
  rated: 'chip-success',
}[s] || 'chip-gray');

const statusLabel = (s: string) => ({
  all: 'Barchasi',
  queue: 'Navbatda',
  active: 'Aktiv',
  open: 'Yangi',
  answered: 'Javob berildi',
  waiting: 'Seller javobini kutmoqda',
  closed: 'Yopilgan',
  rated: 'Baholangan',
}[s] || s || '—');

export default function Tickets() {
  const { tickets = [], ticketPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, ticketCounts = {}, ticketFilters = {} } = usePage<{ tickets?: Ticket[]; ticketPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; ticketCounts?: Record<string, number>; ticketFilters?: { tab?: string; source?: string; search?: string } }>().props;
  const [activeTab, setActiveTab] = useState(ticketFilters.tab || 'all');
  const [activeSource, setActiveSource] = useState(ticketFilters.source || 'all');
  const [search, setSearch] = useState(ticketFilters.search || '');
  const [showReply, setShowReply] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
  const [replyText, setReplyText] = useState('');
  const [detail, setDetail] = useState<TicketDetail | null>(null);
  const [showDetail, setShowDetail] = useState(false);
  const [loadingDetail, setLoadingDetail] = useState(false);

  const handleOpenReply = (ticket: Ticket) => {
    setSelectedTicket(ticket);
    setReplyText('');
    setShowReply(true);
  };

  const closeTicket = (ticket: Ticket) => {
    if (!ticket.closeUrl || !confirm(`#${ticket.id} ticket yopilsinmi?`)) return;
    router.patch(ticket.closeUrl, { close_reason: prompt('Yopish sababi', 'Muammo hal qilindi.') || '' }, { preserveScroll: true });
  };
  const openDetail = async (ticket: Ticket) => {
    if (!ticket.dataUrl) return;
    setSelectedTicket(ticket);
    setShowDetail(true);
    setLoadingDetail(true);
    try {
      const response = await fetch(ticket.dataUrl, { headers: { Accept: 'application/json' } });
      setDetail(response.ok ? await response.json() : null);
    } finally {
      setLoadingDetail(false);
    }
  };

  const sendReply = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket?.replyUrl) return;
    router.post(selectedTicket.replyUrl, { message: replyText }, { preserveScroll: true, onSuccess: () => setShowReply(false) });
  };

  const loadTickets = (page = 1, tab = activeTab, term = search, source = activeSource) => router.get('/boshqaruv/tickets', { tickets_page: page, tickets_tab: tab, tickets_source: source, tickets_search: term }, { preserveState: true, preserveScroll: true, replace: true });

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Murojaatlar</h1>
          <p className="page-subtitle">Mijoz supporti va sellerlarning Kitobchi bilan suhbatlari</p>
        </div>
      </div>

      <div className="kpi-strip row g-3 mb-4">
          {[
          { label: 'Yangi', val: (ticketCounts.open || 0) + (ticketCounts.queue || 0), icon: 'bi-envelope-exclamation', color: '#8A5709' },
          { label: 'Javob berildi', val: ticketCounts.answered || 0, icon: 'bi-reply', color: '#24509B' },
          { label: 'Yopilgan', val: ticketCounts.closed || 0, icon: 'bi-check2-circle', color: '#0F6A46' },
          { label: 'Jami', val: ticketCounts.all || 0, icon: 'bi-headset', color: '#4A3A7A' },
        ].map((s) => (
          <div className="col-xl-3 col-md-6" key={s.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div><div className="stat-value">{s.val}</div><div className="stat-label">{s.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="kc-tabs d-flex gap-2 mb-3 flex-wrap">
          {[
            ['all', 'Barcha murojaatlar', ticketCounts.all || 0],
            ['user', 'Mijoz supporti', ticketCounts.user || 0],
            ['seller', 'Seller tiketlari', ticketCounts.seller || 0],
          ].map(([source, label, count]) => (
            <button
              key={String(source)}
              className={`kc-tab ${activeSource === source ? 'active' : ''}`}
              onClick={() => { setActiveSource(String(source)); loadTickets(1, activeTab, search, String(source)); }}
            >
              {label} <span className="ms-1 opacity-75">{count}</span>
            </button>
          ))}
        </div>
        <div className="kc-tabs d-flex gap-2 mb-3 flex-wrap">
          {['all', 'open', 'answered', 'waiting', 'queue', 'active', 'closed', 'rated'].map((s) => (
            <button key={s} className={`kc-tab ${activeTab === s ? 'active' : ''}`} onClick={() => { setActiveTab(s); loadTickets(1, s); }}>{statusLabel(s)} <span className="ms-1 opacity-75">{ticketCounts[s] || 0}</span></button>
          ))}
          <form className="ms-auto input-group" style={{ maxWidth: 260 }} onSubmit={(event) => { event.preventDefault(); loadTickets(); }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Ticket qidirish..." value={search} onChange={e => setSearch(e.target.value)} />
          </form>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Manba</th><th>Mavzu</th><th>Foydalanuvchi</th><th>Operator</th><th>Xabar</th><th>Reyting</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {tickets.map((ticket) => (
                <tr key={`${ticket.source || 'bot'}-${ticket.id}`}>
                  <td className="fw-semibold text-primary">#{ticket.id}</td>
                  <td><span className={`chip ${ticket.source === 'seller' ? 'chip-purple' : 'chip-info'}`}>{ticket.sourceLabel || 'Support'}</span></td>
                  <td className="fw-semibold">{ticket.subject}</td>
                  <td>{ticket.user}</td>
                  <td>{ticket.operator || '—'}</td>
                  <td>{ticket.messages}</td>
                  <td>{ticket.rating || '—'}</td>
                  <td className="text-muted">{ticket.date || '—'}</td>
                  <td><span className={`chip ${statusChip(ticket.status)}`}>{statusLabel(ticket.status)}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => openDetail(ticket)}><i className="bi bi-eye"></i></button>
                    {/* BUG TUZATILDI (2026-09): ilgari bu tugma
                        `ticket.replyUrl` tekshirilmasdan HAR DOIM
                        ko'rsatilar edi. Ammo backend yopilgan seller
                        tiketlari uchun `replyUrl`ni ATAYLAB `null`
                        qiladi (AdminController:9210). Natijada admin
                        yopilgan tiketga "javob" bosib, matn yozib
                        yuborsa — `sendReply()` ichidagi `replyUrl`
                        tekshiruvi so'rovni jim tarzda bekor qilar,
                        hech qanday xabar chiqmasdan modal ochiq
                        qolaverardi. Endi tugma `closeUrl` bilan bir
                        xil andozada shartli ko'rsatiladi. */}
                    {ticket.replyUrl ? <button className="btn btn-sm btn-primary-gradient me-1" onClick={() => handleOpenReply(ticket)}><i className="bi bi-reply"></i></button> : null}
                    {ticket.closeUrl ? <button className="btn btn-sm btn-light" onClick={() => closeTicket(ticket)}><i className="bi bi-check2"></i></button> : null}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...ticketPagination} onPageChange={(page) => loadTickets(page)} />
      </div>

      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered size="xl" dialogClassName="kc-sheet">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Murojaat #{selectedTicket?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loadingDetail ? <div className="text-muted py-5 text-center">Yuklanmoqda...</div> : !detail ? <div className="text-muted py-5 text-center">Ma'lumot yuklanmadi</div> : (
            <div className="row g-3">
              <div className="col-xl-4"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Murojaat egasi</h6><Info label="Ism" value={detail.profile.name} /><Info label="Telegram" value={detail.profile.username} /><Info label="User ID" value={detail.profile.userId} /><Info label="Manba" value={detail.profile.sourceType} /><Info label="Operator" value={detail.profile.operator} /><Info label="Sana" value={detail.profile.createdAt} /></div></div>
              <div className="col-xl-8"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Suhbat tarixi</h6>{detail.messages.map((message) => <div className={`border rounded p-3 mb-2 ${message.sentBy === 'user' ? 'bg-light' : ''}`} key={String(message.id)}><div className="d-flex justify-content-between gap-3 mb-1"><strong className="small">{String(message.actor || message.sentBy || 'Tizim')}</strong><span className="text-muted small">{String(message.date || '—')}</span></div><div>{String(message.message || '—')}</div><small className="text-muted">{String(message.type || 'text')}{message.error ? ` · ${message.error}` : ''}</small></div>)}{detail.messages.length === 0 ? <div className="text-muted">Xabar tarixi topilmadi</div> : null}</div></div>
              <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Ilovalar</h6>{detail.attachments.map((file) => <div className="border-bottom py-2" key={String(file.id)}><strong>{String(file.name || 'Fayl')}</strong><div className="small text-muted">{String(file.type || '—')} · {file.size ? `${file.size} KB` : 'hajm yo‘q'} · {String(file.sentBy || '—')}</div></div>)}{detail.attachments.length === 0 ? <div className="text-muted">Ilova mavjud emas</div> : null}</div></div>
              <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Holat</h6><Info label="Status" value={statusLabel(String(detail.profile.status || ''))} /><Info label="Reyting" value={detail.profile.rating} /><Info label="Yopish sababi" value={detail.profile.closeReason} /><Info label="Yopilgan vaqt" value={detail.profile.closedAt} /></div></div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>{selectedTicket?.replyUrl ? <Button variant="outline-primary" onClick={() => { setShowDetail(false); handleOpenReply(selectedTicket); }}>Javob yozish</Button> : null}{selectedTicket?.closeUrl ? <Button variant="outline-danger" onClick={() => closeTicket(selectedTicket)}>Yopish</Button> : null}<Button variant="light" onClick={() => setShowDetail(false)}>Bekor qilish</Button></Modal.Footer>
      </Modal>

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

function Info({ label, value }: { label: string; value: string | number | null | undefined }) {
  return <div className="border-bottom py-2"><small className="text-muted d-block">{label}</small><span className="fw-semibold">{String(value || '—')}</span></div>;
}
