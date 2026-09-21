import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

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
  queue: 'text-light-warning',
  active: 'text-light-info',
  open: 'text-light-warning',
  answered: 'text-light-success',
  waiting: 'text-light-info',
  closed: 'text-light-secondary',
  rated: 'text-light-success',
}[s] || 'text-light-secondary');

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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Murojaatlar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Mijoz supporti va sellerlarning Kitobchi bilan suhbatlari</p>
        </div>
      </div>

      <div className="row">
          {[
          { label: 'Yangi', val: (ticketCounts.open || 0) + (ticketCounts.queue || 0), icon: 'ti-mail-off', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Javob berildi', val: ticketCounts.answered || 0, icon: 'ti-arrow-back-up', color: 'rgba(var(--info), 1)' },
          { label: 'Yopilgan', val: ticketCounts.closed || 0, icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { label: 'Jami', val: ticketCounts.all || 0, icon: 'ti-headset', color: 'rgba(var(--primary), 1)' },
        ].map((s, kpiIndex) => (<div className="col-xl-3 col-md-6" key={s.label}>
            <StatWidget index={kpiIndex} label={s.label} value={s.val} />
          </div>))}
      </div>

      <div className="card">
        <div className="card-body">
          <div className="nav nav-tabs app-tabs-primary mb-3 flex-wrap">
            {[
              ['all', 'Barcha murojaatlar', ticketCounts.all || 0],
              ['user', 'Mijoz supporti', ticketCounts.user || 0],
              ['seller', 'Seller tiketlari', ticketCounts.seller || 0],
            ].map(([source, label, count]) => (
              <div key={String(source)} className="nav-item"><button
                  className={`nav-link ${activeSource === source ? 'active' : ''}`}
                  onClick={() => { setActiveSource(String(source)); loadTickets(1, activeTab, search, String(source)); }}>
                  {label} <span className="ms-1 opacity-75">{count}</span>
                </button></div>
            ))}
          </div>
          <div className="nav nav-tabs app-tabs-primary mb-3 flex-wrap">
            {['all', 'open', 'answered', 'waiting', 'queue', 'active', 'closed', 'rated'].map((s) => (
              <div key={s} className="nav-item"><button
                  className={`nav-link ${activeTab === s ? 'active' : ''}`}
                  onClick={() => { setActiveTab(s); loadTickets(1, s); }}>{statusLabel(s)} <span className="ms-1 opacity-75">{ticketCounts[s] || 0}</span></button></div>
            ))}
            <form className="ms-auto input-group" style={{ maxWidth: 260 }} onSubmit={(event) => { event.preventDefault(); loadTickets(); }}>
              <span className="input-group-text bg-white"><i className="ti ti-search text-muted"></i></span>
              <input className="form-control" placeholder="Ticket qidirish..." value={search} onChange={e => setSearch(e.target.value)} />
            </form>
          </div>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>Manba</th><th>Mavzu</th><th>Foydalanuvchi</th><th>Operator</th><th>Xabar</th><th>Reyting</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
              <tbody>
                {tickets.map((ticket) => (
                  <tr key={`${ticket.source || 'bot'}-${ticket.id}`}>
                    <td className="f-w-600 text-nowrap">#{ticket.id}</td>
                    <td><span className={`badge ${ticket.source === 'seller' ? 'text-light-primary' : 'text-light-info'}`}>{ticket.sourceLabel || 'Support'}</span></td>
                    <td className="f-w-600">{ticket.subject}</td>
                    <td>{ticket.user}</td>
                    <td>{ticket.operator || '—'}</td>
                    <td>{ticket.messages}</td>
                    <td>{ticket.rating || '—'}</td>
                    <td className="text-muted">{ticket.date || '—'}</td>
                    <td><span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(ticket.status)))}`}>{statusLabel(ticket.status)}</span></td>
                    <td>
                      <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => openDetail(ticket)}><i className="ti ti-eye"></i></button>
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
                      {ticket.replyUrl ? <button className="btn btn-sm btn-primary me-1" onClick={() => handleOpenReply(ticket)}><i className="ti ti-arrow-back-up"></i></button> : null}
                      {ticket.closeUrl ? <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => closeTicket(ticket)}><i className="ti ti-check"></i></button> : null}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationControls {...ticketPagination} onPageChange={(page) => loadTickets(page)} />
        </div>
      </div>

      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered size="xl">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Murojaat #{selectedTicket?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loadingDetail ? <div className="text-muted py-5 text-center">Yuklanmoqda...</div> : !detail ? <div className="text-muted py-5 text-center">Ma'lumot yuklanmadi</div> : (
            <div className="row">
              <div className="col-xl-4"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Murojaat egasi</h5></div><div className="card-body"><Info label="Ism" value={detail.profile.name} /><Info label="Telegram" value={detail.profile.username} /><Info label="User ID" value={detail.profile.userId} /><Info label="Manba" value={detail.profile.sourceType} /><Info label="Operator" value={detail.profile.operator} /><Info label="Sana" value={detail.profile.createdAt} /></div></div></div>
              <div className="col-xl-8"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Suhbat tarixi</h5></div><div className="card-body">{detail.messages.map((message) => <div className={`b-1-light b-r-8 p-3 mb-2 ${message.sentBy === 'user' ? 'bg-light-secondary' : ''}`} key={String(message.id)}><div className="d-flex justify-content-between gap-3 mb-1"><strong className="f-s-13">{String(message.actor || message.sentBy || 'Tizim')}</strong><span className="text-muted f-s-13">{String(message.date || '—')}</span></div><div>{String(message.message || '—')}</div><small className="text-muted">{String(message.type || 'text')}{message.error ? ` · ${message.error}` : ''}</small></div>)}{detail.messages.length === 0 ? <div className="text-muted">Xabar tarixi topilmadi</div> : null}</div></div></div>
              <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Ilovalar</h5></div><div className="card-body">{detail.attachments.map((file) => <div className="b-b-1-light py-2" key={String(file.id)}><strong>{String(file.name || 'Fayl')}</strong><div className="f-s-13 text-muted">{String(file.type || '—')} · {file.size ? `${file.size} KB` : 'hajm yo‘q'} · {String(file.sentBy || '—')}</div></div>)}{detail.attachments.length === 0 ? <div className="text-muted">Ilova mavjud emas</div> : null}</div></div></div>
              <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Holat</h5></div><div className="card-body"><Info label="Status" value={statusLabel(String(detail.profile.status || ''))} /><Info label="Reyting" value={detail.profile.rating} /><Info label="Yopish sababi" value={detail.profile.closeReason} /><Info label="Yopilgan vaqt" value={detail.profile.closedAt} /></div></div></div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>{selectedTicket?.replyUrl ? <Button variant="outline-primary" onClick={() => { setShowDetail(false); handleOpenReply(selectedTicket); }}>Javob yozish</Button> : null}{selectedTicket?.closeUrl ? <Button variant="outline-danger" onClick={() => closeTicket(selectedTicket)}>Yopish</Button> : null}<Button variant="light-secondary" onClick={() => setShowDetail(false)}>Bekor qilish</Button></Modal.Footer>
      </Modal>

      <Modal show={showReply} onHide={() => setShowReply(false)} centered>
        <Form onSubmit={sendReply}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Murojaatga javob: #{selectedTicket?.id}</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="mb-3">
              <small className="text-muted d-block">Mavzu:</small>
              <div className="f-w-600">{selectedTicket?.subject}</div>
              <small className="text-muted d-block mt-2">Mijoz:</small>
              <div className="f-w-600">{selectedTicket?.user}</div>
            </div>
            <Form.Group>
              <Form.Label className="f-s-13 f-w-600">Javob matni</Form.Label>
              <Form.Control as="textarea" rows={4} required value={replyText} onChange={e => setReplyText(e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={() => setShowReply(false)}>Bekor qilish</Button>
            <Button variant="success" type="submit" className="f-w-600"><i className="ti ti-send me-1"></i>Yuborish</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

function Info({ label, value }: { label: string; value: string | number | null | undefined }) {
  return <div className="b-b-1-light py-2"><small className="text-muted d-block">{label}</small><span className="f-w-600">{String(value || '—')}</span></div>;
}
