import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { StatWidget, EmptyState } from '../components/Axelit';
import { ProfileCard, AboutList, Avatar as PAvatar } from '../components/Profile';

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

      <div className="row ticket-app">
        <div className="col-lg-6">
          <div className="row">
            {[
              { label: 'Yangi murojaatlar', val: (ticketCounts.open || 0) + (ticketCounts.queue || 0), icon: 'ti-mail-opened', tone: 'primary', statuses: ['open', 'queue'] },
              { label: 'Javob kutilmoqda', val: (ticketCounts.waiting || 0) + (ticketCounts.active || 0), icon: 'ti-clock-hour-4', tone: 'info', statuses: ['waiting', 'active'] },
              { label: 'Javob berilgan', val: ticketCounts.answered || 0, icon: 'ti-checks', tone: 'success', statuses: ['answered'] },
              { label: 'Yopilgan', val: ticketCounts.closed || 0, icon: 'ti-archive', tone: 'warning', statuses: ['closed', 'rated'] },
            ].map((card) => {
              const people = Array.from(new Set(tickets.filter((ticket) => card.statuses.includes(String(ticket.status))).map((ticket) => String(ticket.user || '')).filter(Boolean)));
              return (
                <div className="col-sm-6" key={card.label}>
                  <div className={`card ticket-card bg-light-${card.tone}`} role="button" onClick={() => { setActiveTab(card.statuses[0]); loadTickets(1, card.statuses[0]); }}>
                    <div className="card-body">
                      <i className="ph-bold ph-circle circle-bg-img"></i>
                      <div className="h-50 w-50 d-flex-center b-r-15 bg-white mb-3"><i className={`ti ${card.icon} f-s-25 text-${card.tone}`}></i></div>
                      <p className="f-s-16 mb-2">{card.label}</p>
                      <div className="d-flex justify-content-between align-items-center">
                        <h3 className={`text-${card.tone}-dark mb-0`}>{card.val}</h3>
                        {people.length ? (
                          <ul className="avatar-group list-unstyled mb-0">
                            {people.slice(0, 3).map((name) => <li key={name} className="b-r-50" title={name}><PAvatar name={name} size="xs" className="b-2-light" /></li>)}
                            {people.length > 3 ? <li className="bg-white text-dark h-30 w-30 d-flex-center b-r-50 f-s-12 f-w-600">{people.length - 3}+</li> : null}
                          </ul>
                        ) : null}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
        <div className="col-lg-6">
          <div className="card create-ticket-card">
            <div className="card-body">
              <div className="row align-items-center">
                <div className="col-sm-7">
                  <div className="ticket-create">
                    <h5 className="mb-2">Support markazi</h5>
                    <p className="mb-4 mt-3 text-secondary">Mijozlar va sellerlarning barcha murojaatlari bir joyda: yangi murojaatlarga tez javob bering, yopilganlarini baholang va manba bo'yicha filtrlang.</p>
                    <div className="d-flex flex-wrap gap-2">
                      <button type="button" className="btn btn-light-primary" onClick={() => { setActiveSource('user'); loadTickets(1, activeTab, search, 'user'); }}><i className="ti ti-user me-1"></i>Mijozlar</button>
                      <button type="button" className="btn btn-light-info" onClick={() => { setActiveSource('seller'); loadTickets(1, activeTab, search, 'seller'); }}><i className="ti ti-building-store me-1"></i>Sellerlar</button>
                    </div>
                  </div>
                </div>
                <div className="col-sm-5 d-none d-sm-block">
                  <span className="h-120 w-120 d-flex-center b-r-50 bg-light-primary mx-auto"><i className="ti ti-headset f-s-50 text-primary"></i></span>
                </div>
              </div>
            </div>
          </div>
          <h5 className="ms-2 mb-2">Holatlar bo'yicha</h5>
          <ul className="ticket-slider list-unstyled row g-0 mb-0">
            {(['open', 'answered', 'waiting', 'closed'] as const).map((key) => (
              <li className="col-6" key={key}>
                <div className="ticket-catagory p-3 gap-2" role="button" onClick={() => { setActiveTab(key); loadTickets(1, key); }}>
                  <h6 className="mb-0 f-s-14 txt-ellipsis-1">{statusLabel(key)}</h6>
                  <span className={`badge ${activeTab === key ? 'text-light-primary' : 'text-light-success'}`}>{ticketCounts[key] || 0}</span>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="nav kc-segment mb-3">
            {[
              ['all', 'Barcha murojaatlar', ticketCounts.all || 0],
              ['user', 'Mijoz supporti', ticketCounts.user || 0],
              ['seller', 'Seller tiketlari', ticketCounts.seller || 0],
            ].map(([source, label, count]) => (
              <div key={String(source)} className="nav-item"><button
                  className={`nav-link ${activeSource === source ? 'active' : ''}`}
                  onClick={() => { setActiveSource(String(source)); loadTickets(1, activeTab, search, String(source)); }}>
                  {label} <span className="badge">{count}</span>
                </button></div>
            ))}
          </div>
          <div className="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div className="nav kc-segment">
            {['all', 'open', 'answered', 'waiting', 'queue', 'active', 'closed', 'rated'].map((s) => (
              <div key={s} className="nav-item"><button
                  className={`nav-link ${activeTab === s ? 'active' : ''}`}
                  onClick={() => { setActiveTab(s); loadTickets(1, s); }}>{statusLabel(s)} <span className="badge">{ticketCounts[s] || 0}</span></button></div>
            ))}
          </div>
            <form className="app-form app-icon-form position-relative ms-auto" style={{ width: 'min(260px, 100%)' }} onSubmit={(event) => { event.preventDefault(); loadTickets(); }}>
              <input type="search" className="form-control form-control-sm" placeholder="Ticket qidirish..." value={search} onChange={(event) => setSearch(event.target.value)} />
              <i className="ti ti-search"></i>
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
                    <td><div className="d-flex align-items-center gap-2"><PAvatar name={String(ticket.user || "?")} size="sm" /><span className="text-nowrap">{ticket.user}</span></div></td>
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
          {loadingDetail ? <div className="text-center py-5"><span className="spinner-border text-primary"></span><p className="text-secondary mt-2 mb-0">Yuklanmoqda...</p></div> : !detail ? <div className="text-muted py-5 text-center">Ma'lumot yuklanmadi</div> : (
            <div className="row">
              <div className="col-lg-4 col-xxl-3">
                <ProfileCard
                  name={String(detail.profile.name || 'Foydalanuvchi')}
                  subtitle={detail.profile.username ? `@${String(detail.profile.username)}` : `User ID: ${String(detail.profile.userId || '—')}`}
                  badges={<span className="badge text-light-primary">{statusLabel(String(detail.profile.status || ''))}</span>}
                  stats={[{ label: 'Xabar', value: detail.messages.length }, { label: 'Ilova', value: detail.attachments.length }, { label: 'Reyting', value: detail.profile.rating ? String(detail.profile.rating) : '—' }]}
                />
                <AboutList title="Murojaat ma'lumotlari" rows={[
                  { icon: 'ti-id', label: 'User ID', value: detail.profile.userId ? String(detail.profile.userId) : null },
                  { icon: 'ti-brand-telegram', label: 'Telegram', value: detail.profile.username ? String(detail.profile.username) : null },
                  { icon: 'ti-inbox', label: 'Manba', value: detail.profile.sourceType ? String(detail.profile.sourceType) : null },
                  { icon: 'ti-headset', label: 'Operator', value: detail.profile.operator ? String(detail.profile.operator) : null },
                  { icon: 'ti-calendar-event', label: 'Yaratilgan', value: detail.profile.createdAt ? String(detail.profile.createdAt) : null },
                  { icon: 'ti-lock', label: 'Yopilgan', value: detail.profile.closedAt ? String(detail.profile.closedAt) : null },
                  { icon: 'ti-message-report', label: 'Yopish sababi', value: detail.profile.closeReason ? String(detail.profile.closeReason) : null },
                ]} />
              </div>
              <div className="col-lg-8 col-xxl-9">
                <div className="card"><div className="card-header d-flex align-items-center justify-content-between"><h5 className="mb-0">Suhbat tarixi</h5><span className="badge text-light-primary">{detail.messages.length} ta</span></div><div className="card-body">
                  <div className="d-flex flex-column gap-3">
                    {detail.messages.map((message) => {
                      const mine = message.sentBy !== 'user';
                      const who = String(message.actor || message.sentBy || 'Tizim');
                      return (
                        <div className={`d-flex gap-2 ${mine ? 'flex-row-reverse' : ''}`} key={String(message.id)}>
                          <PAvatar name={who} size="sm" icon={mine ? 'ti ti-headset' : undefined} />
                          <div className={`b-r-15 px-3 py-2 ${mine ? 'bg-light-primary' : 'bg-light-secondary'}`} style={{ maxWidth: '78%' }}>
                            <div className="d-flex justify-content-between gap-3 mb-1"><span className="f-w-600 f-s-13">{who}</span><span className="f-s-12 opacity-75">{String(message.date || '—')}</span></div>
                            <div className="text-break" style={{ whiteSpace: 'pre-wrap' }}>{String(message.message || '—')}</div>
                            {message.type && message.type !== 'text' || message.error ? <div className="f-s-12 opacity-75 mt-1">{String(message.type || 'text')}{message.error ? ` · ${message.error}` : ''}</div> : null}
                          </div>
                        </div>
                      );
                    })}
                    {detail.messages.length === 0 ? <EmptyState text="Xabar tarixi topilmadi" /> : null}
                  </div>
                </div></div>
                <div className="card"><div className="card-header"><h5 className="mb-0">Ilovalar</h5></div><div className="card-body">
                  <ul className="list-group list-group-flush">
                    {detail.attachments.map((file) => <li className="list-group-item d-flex align-items-center gap-2 px-0" key={String(file.id)}><span className="h-35 w-35 d-flex-center b-r-10 text-light-info flex-shrink-0"><i className="ti ti-paperclip"></i></span><div className="min-w-0"><div className="f-w-600 txt-ellipsis-1">{String(file.name || 'Fayl')}</div><div className="f-s-12 text-muted">{String(file.type || '—')} · {file.size ? `${file.size} KB` : 'hajm yo‘q'} · {String(file.sentBy || '—')}</div></div></li>)}
                  </ul>
                  {detail.attachments.length === 0 ? <EmptyState text="Ilova mavjud emas" /> : null}
                </div></div>
              </div>
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
