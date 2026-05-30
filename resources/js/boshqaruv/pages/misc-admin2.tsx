import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

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
  const { complaints = [] } = usePage<{
    complaints?: Array<{ id: number; user: string; phone?: string; reason?: string; comment?: string; type: string; status: string; date?: string; showUrl?: string; statusUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<(typeof complaints)[0] | null>(null);

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
      <div className="page-head"><div><h1 className="page-title">Shikoyatlar</h1><p className="page-subtitle">Jami {complaints.length} ta shikoyat</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Sabab</th><th>Turi</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{complaints.map(complaint => (
            <tr key={complaint.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{complaint.id}</td>
              <td className="fw-semibold">{complaint.user}</td>
              <td>{complaint.reason || complaint.comment || '—'}</td>
              <td><span className="chip chip-gray" style={{ fontSize: 9 }}>{complaint.type}</span></td>
              <td className="text-muted">{complaint.date}</td>
              <td><span className={`chip ${complaint.status === 'new' ? 'chip-danger' : complaint.status === 'reviewing' ? 'chip-warning' : 'chip-success'}`} style={{ fontSize: 9 }}>{complaint.status}</span></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(complaint); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(complaint)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Shikoyat #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <p><strong>{selected?.user}</strong> dan shikoyat: {selected?.reason || selected?.comment || '—'}</p>
          <div className="d-flex gap-2 mt-3">
            {['new', 'reviewing', 'resolved'].map(s => (
              <button key={s} className={`btn btn-sm ${selected?.status === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => updateStatus(s)}>{s}</button>
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
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{certificate.id}</td>
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
