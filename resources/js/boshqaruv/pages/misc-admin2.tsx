import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

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
      <div className="page-head">
        <div><h1 className="page-title">Hamkor blogerlar</h1><p className="page-subtitle">Jami {bloggers.length} ta bloger · shipment va hamkorlik nazorati</p></div>
      </div>
      <div className="row g-3">
        {bloggers.map(blogger => (
          <div className="col-xl-4 col-md-6" key={blogger.id}>
            <div className="card-panel">
              <div className="d-flex align-items-center gap-2 mb-2">
                <div className="resource-avatar" style={{ width: 44, height: 44 }}>{blogger.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</div>
                <div style={{ minWidth: 0 }}>
                  <div className="fw-bold text-truncate">{blogger.name}</div>
                  <small className="text-muted">{(blogger.platforms || []).join(', ') || blogger.phone || 'Bloger'} · {blogger.shipments || 0} shipment</small>
                </div>
                <div className="ms-auto"><span className={`chip ${blogger.status === 'Faol' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{blogger.status}</span></div>
              </div>
              <div className="small text-muted mb-3">{blogger.address || blogger.activeUntil || '—'}</div>
              <div className="d-flex gap-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelected(blogger)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(blogger)}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Telefon</small><div className="fw-semibold">{selected?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div>{selected?.status || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Shipment</small><div>{selected?.shipments || 0}</div></div>
            <div className="col-6"><small className="text-muted">Faol muddat</small><div>{selected?.activeUntil || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
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
      <div className="page-head"><div><h1 className="page-title">Shikoyatlar</h1><p className="page-subtitle">Jami {complaintPagination.total} ta shikoyat</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Sabab</th><th>Turi</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{complaints.map(complaint => (
            <tr key={complaint.id}>
              <td className="fw-semibold" style={{ color: '#0B0342' }}>#{complaint.id}</td>
              <td className="fw-semibold">{complaint.user}</td>
              <td>{complaint.reason || complaint.comment || '—'}</td>
              <td><span className="chip chip-gray" style={{ fontSize: 9 }}>{complaintTypeLabel(complaint.type)}</span></td>
              <td className="text-muted">{complaint.date}</td>
              <td><span className={`chip ${complaint.status === 'pending' ? 'chip-warning' : complaint.status === 'reviewed' ? 'chip-success' : 'chip-gray'}`} style={{ fontSize: 9 }}>{complaintStatusLabel(complaint.status)}</span></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(complaint); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(complaint)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div><PaginationControls {...complaintPagination} onPageChange={(page) => router.get('/boshqaruv/shikoyatlar', { complaints_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Shikoyat #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-5"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Shikoyatchi</h6><div className="fw-semibold">{selected?.user}</div><div className="text-muted small">{selected?.phone || '—'}</div><hr /><small className="text-muted d-block">Turi</small><strong>{complaintTypeLabel(selected?.type)}</strong><small className="text-muted d-block mt-2">Obyekt ID</small><strong>#{selected?.reportableId || '—'}</strong><small className="text-muted d-block mt-2">Holati</small><strong>{complaintStatusLabel(selected?.status)}</strong></div></div>
            <div className="col-md-7"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Shikoyat matni</h6><strong>{selected?.reason || 'Sabab ko‘rsatilmagan'}</strong><div className="text-muted mt-2">{selected?.comment || 'Izoh kiritilmagan'}</div><small className="text-muted d-block mt-3">{selected?.date || '—'}</small></div></div>
            <div className="col-12">
              <div className="detail-panel">
                <div className="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                  <div>
                    <h6 className="fw-bold mb-1">Shikoyat qilingan kontent</h6>
                    <div className="text-muted small">{selected?.content?.title || 'Kontent topilmadi'}</div>
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
                      {selected.content.avatar ? (
                        <img src={selected.content.avatar} alt="" style={{ width: 48, height: 48, objectFit: 'cover', borderRadius: '50%' }} />
                      ) : (
                        <div className="resource-avatar" style={{ width: 48, height: 48 }}>{(selected.content.author || 'B').slice(0, 1)}</div>
                      )}
                      <div>
                        <div className="fw-semibold">{selected.content.author || 'Muallif topilmadi'}</div>
                        <div className="text-muted small">{selected.content.phone || 'Telefon yo‘q'} · {selected.content.date || '—'}</div>
                      </div>
                    </div>
                    <div className="border rounded-4 p-3 bg-light-subtle" style={{ whiteSpace: 'pre-line' }}>{selected.content.summary || 'Post matni yo‘q'}</div>
                    {selected.content.images?.length ? (
                      <div className="d-flex flex-wrap gap-2 mt-3">
                        {selected.content.images.map((image) => (
                          <img key={image} src={image} alt="" style={{ width: 92, height: 92, objectFit: 'cover', borderRadius: 'var(--kc-radius)', border: '1px solid var(--kc-border-subtle)' }} />
                        ))}
                      </div>
                    ) : null}
                    <div className="d-flex flex-wrap gap-2 mt-3">
                      <span className="chip chip-info">Like: {selected.content.stats?.likes || 0}</span>
                      <span className="chip chip-gray">Izoh: {selected.content.stats?.comments || 0}</span>
                      {selected.content.meta?.warning ? <span className="chip chip-danger">Ogohlantirilgan</span> : null}
                      {selected.content.meta?.repost ? <span className="chip chip-purple">Repost</span> : null}
                      {selected.content.meta?.isDeleted ? <span className="chip chip-gray">O‘chirilgan</span> : null}
                    </div>
                  </div>
                ) : null}

                {selected?.content?.kind === 'conversation_message' ? (
                  <div>
                    <div className="row g-3 mb-3">
                      <div className="col-md-6">
                        <small className="text-muted d-block">Mijoz</small>
                        <div className="fw-semibold">{selected.content.conversation?.user || '—'}</div>
                        <div className="text-muted small">{selected.content.conversation?.userPhone || 'Telefon yo‘q'}</div>
                      </div>
                      <div className="col-md-6">
                        <small className="text-muted d-block">Qabul qiluvchi</small>
                        <div className="fw-semibold">{selected.content.conversation?.agent || '—'}</div>
                        <div className="text-muted small">
                          {selected.content.conversation?.type || 'chat'}
                          {selected.content.conversation?.orderId ? ` · Buyurtma #${selected.content.conversation.orderId}` : ''}
                        </div>
                      </div>
                    </div>
                    <div className="border rounded-4 p-3 bg-light-subtle">
                      <div className="text-muted small mb-2">{selected.content.meta?.senderType || 'Yuboruvchi'} · {selected.content.date || '—'}</div>
                      <div style={{ whiteSpace: 'pre-line' }}>{selected.content.summary || 'Xabar matni yo‘q'}</div>
                    </div>
                    {selected.content.replyTo ? (
                      <div className="mt-3 border rounded-4 p-3">
                        <div className="text-muted small mb-1">Javob berilgan xabar</div>
                        <div className="fw-semibold small">{selected.content.replyTo.senderType || '—'} · {selected.content.replyTo.date || '—'}</div>
                        <div className="text-muted mt-1">{selected.content.replyTo.text || 'Matn yo‘q'}</div>
                      </div>
                    ) : null}
                    <div className="mt-3">
                      <div className="fw-semibold mb-2">Suhbat konteksti</div>
                      <div className="d-grid gap-2">
                        {(selected.content.context || []).map((message) => (
                          <div key={message.id} className={`rounded-4 border p-3 ${message.isTarget ? 'border-danger bg-danger-subtle' : 'bg-white'}`}>
                            <div className="text-muted small mb-1">{message.senderType} · {message.date || '—'}{message.isTarget ? ' · Shikoyat qilingan xabar' : ''}</div>
                            <div style={{ whiteSpace: 'pre-line' }}>{message.text}</div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                ) : null}

                {selected?.content?.kind === 'missing' ? (
                  <div className="alert alert-warning mb-0">{selected.content.summary}</div>
                ) : null}
              </div>
            </div>
            <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">Foydalanuvchining boshqa shikoyatlari</h6>{(selected?.otherReports || []).map((report) => <div className="d-flex justify-content-between border-bottom py-2" key={report.id}><span>#{report.id} · {report.reason || '—'}</span><span className="text-muted small">{report.status} · {report.date || '—'}</span></div>)}{(selected?.otherReports || []).length === 0 ? <div className="text-muted">Boshqa shikoyat topilmadi</div> : null}</div></div>
          </div>
          <div className="d-flex gap-2 mt-3">
            {[['pending', 'Qayta ochish'], ['reviewed', "Ko'rildi"], ['dismissed', 'Rad etish']].map(([status, label]) => (
              <button key={status} className={`btn btn-sm ${selected?.status === status ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => updateStatus(status)}>{label}</button>
            ))}
          </div>
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button></Modal.Footer>
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
      <div className="page-head">
        <div><h1 className="page-title">Gift sertifikatlar</h1><p className="page-subtitle">Jami {giftCertificates.length} ta · {giftCertificates.filter(s => s.status === 'active').length} ta aktiv · {totalActive.toLocaleString()} so'm</p></div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Kod</th><th>Summa</th><th>Xaridor</th><th>Qabul qiluvchi</th><th>Muddati</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{giftCertificates.map(certificate => (
            <tr key={certificate.id}>
              <td className="fw-semibold" style={{ color: '#0B0342' }}>#{certificate.id}</td>
              <td className="fw-bold" style={{ fontFamily: 'monospace' }}>{certificate.code}</td>
              <td className="fw-bold text-success">{certificate.amount.toLocaleString()} so'm</td>
              <td>{certificate.buyer}</td>
              <td>{certificate.recipient || '—'}</td>
              <td className="text-muted">{certificate.expires || '—'}</td>
              <td><span className={`chip ${certificate.status === 'active' ? 'chip-success' : certificate.status === 'used' ? 'chip-info' : certificate.status === 'cancelled' ? 'chip-danger' : 'chip-warning'}`} style={{ fontSize: 9 }}>{certificate.statusLabel || certificate.status}</span></td>
              <td>
                <span className="chip chip-gray">Ko'rildi</span>
                {certificate.status === 'active' && <button className="btn btn-sm btn-danger" onClick={() => cancel(certificate)}><i className="bi bi-x-lg"></i> Bekor qilish</button>}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}
