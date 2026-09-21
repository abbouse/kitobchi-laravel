import { useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import { Avatar as PAvatar } from '../components/Profile';

// ===== BLOGERLAR =====
export function Blogerlar() {
  const { bloggers = [] } = usePage<{
    bloggers?: Array<{ id: number; name: string; phone?: string; address?: string; platforms?: string[]; followers?: number; shipments?: number; status: string; activeUntil?: string; createUrl?: string; showUrl?: string; editUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [selected, setSelected] = useState<(typeof bloggers)[0] | null>(null);
  const createUrl = '/boshqaruv/blogerlar';

  const destroy = (blogger: (typeof bloggers)[0]) => {
    if (!blogger.destroyUrl || !confirm(`${blogger.name} blogerini o'chirasizmi?`)) return;
    router.delete(blogger.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Hamkor blogerlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {bloggers.length} ta bloger · shipment va hamkorlik nazorati</p></div>
      </div>
      <div className="row">
        {bloggers.map(blogger => (
          <div className="col-xl-4 col-md-6" key={blogger.id}>
            <div className="card">
              <div className="card-body">
                <div className="d-flex align-items-center gap-2 mb-2">
                  <PAvatar name={blogger.name} size="lg" />
                  <div className="min-w-0">
                    <div className="f-w-600 text-truncate">{blogger.name}</div>
                    <p className="mb-0 text-secondary">{(blogger.platforms || []).join(', ') || blogger.phone || 'Bloger'} · {blogger.shipments || 0} shipment</p>
                  </div>
                  <div className="ms-auto"><span className={`badge ${blogger.status === 'Faol' ? 'text-light-success' : 'text-light-secondary'} f-s-9`}>{blogger.status}</span></div>
                </div>
                <div className="f-s-13 text-muted mb-3">{blogger.address || blogger.activeUntil || '—'}</div>
                <div className="d-flex gap-2">
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setSelected(blogger)}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(blogger)}><i className="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Telefon</p><div className="f-w-600">{selected?.phone || '—'}</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Status</p><div>{selected?.status || '—'}</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Shipment</p><div>{selected?.shipments || 0}</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Faol muddat</p><div>{selected?.activeUntil || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== SHIKOYATLAR =====
export function Shikoyatlar() {
  const { complaints = [], complaintPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 } } = usePage<{
    complaints?: Array<{
      id: number;
      user: string;
      phone?: string;
      avatar?: string;
      reason?: string;
      comment?: string;
      type: string;
      reportableId?: number;
      status: string;
      date?: string;
      content?: {
        kind: 'book_club' | 'conversation_message' | 'missing';
        title: string;
        summary: string;
        author?: string;
        phone?: string;
        avatar?: string;
        date?: string;
        images?: string[];
        stats?: { likes?: number; comments?: number };
        meta?: Record<string, string | number | boolean | null | undefined>;
        conversation?: { id?: number; user?: string; userPhone?: string; agent?: string; type?: string; orderId?: number | null };
        replyTo?: { text?: string; senderType?: string; date?: string };
        context?: Array<{ id: number; text: string; senderType: string; date?: string; isTarget?: boolean }>;
        manageUrl?: string | null;
        manageLabel?: string | null;
      };
      otherReports?: Array<{ id: number; reason?: string; status: string; date?: string }>;
      statusUrl?: string;
      destroyUrl?: string;
    }>;
    complaintPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
  }>().props;
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<(typeof complaints)[0] | null>(null);

  const complaintTypeLabel = (type?: string) => {
    switch (type) {
      case 'book_club':
      case 'bookclub':
        return 'Book Club posti';
      case 'conversation_message':
      case 'message':
      case 'messages':
        return 'Chat xabari';
      default:
        return type || 'Shikoyat';
    }
  };

  const complaintStatusLabel = (status?: string) => {
    switch (status) {
      case 'pending':
        return 'Ko‘rib chiqilmagan';
      case 'reviewed':
        return 'Ko‘rib chiqilgan';
      case 'dismissed':
        return 'Rad etilgan';
      default:
        return status || '—';
    }
  };

  const updateStatus = (status: string) => {
    if (!selected?.statusUrl) return;
    router.patch(selected.statusUrl, { status }, { preserveScroll: true });
  };
  const destroy = (complaint: (typeof complaints)[0]) => {
    if (!complaint.destroyUrl || !confirm(`#${complaint.id} shikoyat o'chirilsinmi?`)) return;
    router.delete(complaint.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3"><div><h4 className="main-title mb-0">Shikoyatlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {complaintPagination.total} ta shikoyat</p></div></div>
      <div className="card">
        <div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Sabab</th><th>Turi</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>{complaints.map(complaint => (
              <tr key={complaint.id}>
                <td className="f-w-600 text-primary">#{complaint.id}</td>
                <td><div className="d-flex align-items-center gap-2"><PAvatar name={String(complaint.user || "?")} size="sm" /><span className="f-w-600 text-nowrap">{complaint.user}</span></div></td>
                <td>{complaint.reason || complaint.comment || '—'}</td>
                <td><span className="badge text-light-secondary f-s-9">{complaintTypeLabel(complaint.type)}</span></td>
                <td className="text-muted">{complaint.date}</td>
                <td><span className={`badge ${complaint.status === 'pending' ? 'text-light-warning' : complaint.status === 'reviewed' ? 'text-light-success' : 'text-light-secondary'} f-s-9`}>{complaintStatusLabel(complaint.status)}</span></td>
                <td>
                  <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => { setSelected(complaint); setShowDetail(true); }}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(complaint)}><i className="ti ti-trash"></i></button>
                </td>
              </tr>
            ))}</tbody>
          </table></div><PaginationControls {...complaintPagination} onPageChange={(page) => router.get('/boshqaruv/shikoyatlar', { complaints_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
        </div>
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Shikoyat #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row">
            <div className="col-md-5"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Shikoyatchi</h5></div><div className="card-body"><div className="f-w-600">{selected?.user}</div><div className="text-muted f-s-13">{selected?.phone || '—'}</div><hr /><small className="text-muted d-block">Turi</small><strong className="d-block text-dark f-w-600 f-s-16">{complaintTypeLabel(selected?.type)}</strong><small className="text-muted d-block mt-2">Obyekt ID</small><strong className="d-block text-dark f-w-600 f-s-16">#{selected?.reportableId || '—'}</strong><small className="text-muted d-block mt-2">Holati</small><strong className="d-block text-dark f-w-600 f-s-16">{complaintStatusLabel(selected?.status)}</strong></div></div></div>
            <div className="col-md-7"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Shikoyat matni</h5></div><div className="card-body"><strong className="d-block text-dark f-w-600 f-s-16">{selected?.reason || 'Sabab ko‘rsatilmagan'}</strong><div className="text-muted mt-2">{selected?.comment || 'Izoh kiritilmagan'}</div><small className="text-muted d-block mt-3">{selected?.date || '—'}</small></div></div></div>
            <div className="col-12">
              <div className="card"><div className="card-body">
                  <div className="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                      <h6 className="f-w-600 mb-1">Shikoyat qilingan kontent</h6>
                      <div className="text-muted f-s-13">{selected?.content?.title || 'Kontent topilmadi'}</div>
                    </div>
                    {selected?.content?.manageUrl ? (
                      <Button as="a" href={selected.content.manageUrl} variant="outline-primary" size="sm">
                        {selected.content.manageLabel || 'Boshqaruvga o‘tish'}
                      </Button>
                    ) : null}
                  </div>

                  {selected?.content?.kind === 'book_club' ? (
                    <div>
                      <div className="d-flex align-items-center gap-3 mb-3">
                        <PAvatar src={selected.content.avatar} name={selected.content.author || 'B'} size="lg" />
                        <div>
                          <div className="f-w-600">{selected.content.author || 'Muallif topilmadi'}</div>
                          <div className="text-muted f-s-13">{selected.content.phone || 'Telefon yo‘q'} · {selected.content.date || '—'}</div>
                        </div>
                      </div>
                      <div className="bg-light-secondary b-r-15 p-3 text-dark" style={{ whiteSpace: 'pre-line' }}>{selected.content.summary || 'Post matni yo‘q'}</div>
                      {selected.content.images?.length ? (
                        <div className="d-flex flex-wrap gap-2 mt-3">
                          {selected.content.images.map((image) => (
                            <a key={image} href={image} target="_blank" rel="noreferrer" className="d-block h-90 w-90 b-r-15 overflow-hidden b-1-light"><img className="w-100 h-100 object-fit-cover" src={image} alt="" /></a>
                          ))}
                        </div>
                      ) : null}
                      <div className="d-flex flex-wrap gap-2 mt-3">
                        <span className="badge text-light-info">Like: {selected.content.stats?.likes || 0}</span>
                        <span className="badge text-light-secondary">Izoh: {selected.content.stats?.comments || 0}</span>
                        {selected.content.meta?.warning ? <span className="badge text-light-danger">Ogohlantirilgan</span> : null}
                        {selected.content.meta?.repost ? <span className="badge text-light-primary">Repost</span> : null}
                        {selected.content.meta?.isDeleted ? <span className="badge text-light-secondary">O‘chirilgan</span> : null}
                      </div>
                    </div>
                  ) : null}

                  {selected?.content?.kind === 'conversation_message' ? (
                    <div>
                      <div className="row g-3 mb-3">
                        <div className="col-md-6">
                          <small className="text-muted d-block">Mijoz</small>
                          <div className="f-w-600">{selected.content.conversation?.user || '—'}</div>
                          <div className="text-muted f-s-13">{selected.content.conversation?.userPhone || 'Telefon yo‘q'}</div>
                        </div>
                        <div className="col-md-6">
                          <small className="text-muted d-block">Qabul qiluvchi</small>
                          <div className="f-w-600">{selected.content.conversation?.agent || '—'}</div>
                          <div className="text-muted f-s-13">
                            {selected.content.conversation?.type || 'chat'}
                            {selected.content.conversation?.orderId ? ` · Buyurtma #${selected.content.conversation.orderId}` : ''}
                          </div>
                        </div>
                      </div>
                      <div className="b-1-light b-r-15 p-3">
                        <div className="text-muted f-s-13 mb-2">{selected.content.meta?.senderType || 'Yuboruvchi'} · {selected.content.date || '—'}</div>
                        <div style={{ whiteSpace: 'pre-line' }}>{selected.content.summary || 'Xabar matni yo‘q'}</div>
                      </div>
                      {selected.content.replyTo ? (
                        <div className="mt-3 b-1-light b-r-15 p-3">
                          <div className="text-muted f-s-13 mb-1">Javob berilgan xabar</div>
                          <div className="f-w-600 f-s-13">{selected.content.replyTo.senderType || '—'} · {selected.content.replyTo.date || '—'}</div>
                          <div className="text-muted mt-1">{selected.content.replyTo.text || 'Matn yo‘q'}</div>
                        </div>
                      ) : null}
                      <div className="mt-3">
                        <div className="f-w-600 mb-2">Suhbat konteksti</div>
                        <div className="d-grid gap-2">
                          {(selected.content.context || []).map((message) => (
                            <div key={message.id} className={`b-r-15 b-1-light p-3 ${message.isTarget ? 'border-danger bg-light-danger' : 'bg-white'}`}>
                              <div className="text-muted f-s-13 mb-1">{message.senderType} · {message.date || '—'}{message.isTarget ? ' · Shikoyat qilingan xabar' : ''}</div>
                              <div style={{ whiteSpace: 'pre-line' }}>{message.text}</div>
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>
                  ) : null}

                  {selected?.content?.kind === 'missing' ? (
                    <div className="alert alert-light-warning mb-0">{selected.content.summary}</div>
                  ) : null}
                </div></div>
            </div>
            <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Foydalanuvchining boshqa shikoyatlari</h5></div><div className="card-body">{(selected?.otherReports || []).map((report) => <div className="d-flex justify-content-between b-b-1-light py-2" key={report.id}><span>#{report.id} · {report.reason || '—'}</span><span className="text-muted f-s-13">{report.status} · {report.date || '—'}</span></div>)}{(selected?.otherReports || []).length === 0 ? <div className="text-muted">Boshqa shikoyat topilmadi</div> : null}</div></div></div>
          </div>
          <div className="nav kc-segment mt-3" role="tablist" aria-label="Shikoyat holati">
            {[['pending', 'Qayta ochish'], ['reviewed', "Ko'rildi"], ['dismissed', 'Rad etish']].map(([status, label]) => (
              <div className="nav-item" key={status}><button type="button" className={`nav-link ${selected?.status === status ? 'active' : ''}`} onClick={() => updateStatus(status)}>{label}</button></div>
            ))}
          </div>
        </Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={() => setShowDetail(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== GIFT SERTIFIKATLAR =====
export function GiftSertifikatlar() {
  const { giftCertificates = [] } = usePage<{
    giftCertificates?: Array<{ id: number; code: string; amount: number; buyer: string; recipient?: string; status: string; statusLabel?: string; expires?: string; paidAt?: string; showUrl?: string; statusUrl?: string; cancelUrl?: string }>;
  }>().props;

  const totalActive = giftCertificates.filter(s => s.status === 'active').reduce((a, s) => a + s.amount, 0);
  const cancel = (certificate: (typeof giftCertificates)[0]) => {
    if (!certificate.cancelUrl || !confirm(`${certificate.code} sertifikati bekor qilinsinmi?`)) return;
    router.post(certificate.cancelUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Gift sertifikatlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {giftCertificates.length} ta · {giftCertificates.filter(s => s.status === 'active').length} ta aktiv · {totalActive.toLocaleString()} so'm</p></div>
      </div>
      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Kod</th><th>Summa</th><th>Xaridor</th><th>Qabul qiluvchi</th><th>Muddati</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>{giftCertificates.map(certificate => (
              <tr key={certificate.id}>
                <td className="f-w-600 text-primary">#{certificate.id}</td>
                <td className="f-w-600 font-monospace">{certificate.code}</td>
                <td className="f-w-600 text-success">{certificate.amount.toLocaleString()} so'm</td>
                <td>{certificate.buyer}</td>
                <td>{certificate.recipient || '—'}</td>
                <td className="text-muted">{certificate.expires || '—'}</td>
                <td><span className={`badge ${certificate.status === 'active' ? 'text-light-success' : certificate.status === 'used' ? 'text-light-info' : certificate.status === 'cancelled' ? 'text-light-danger' : 'text-light-warning'} f-s-9`}>{certificate.statusLabel || certificate.status}</span></td>
                <td>
                  <span className="badge text-light-secondary">Ko'rildi</span>
                  {certificate.status === 'active' && <button className="btn btn-sm btn-danger" onClick={() => cancel(certificate)}><i className="ti ti-x"></i> Bekor qilish</button>}
                </td>
              </tr>
            ))}</tbody>
          </table></div>
        </div>
</div>
    </div>
  );
}
