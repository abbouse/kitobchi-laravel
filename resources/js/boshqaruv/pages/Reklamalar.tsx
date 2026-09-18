import { toneOf } from '../utils/tone';
import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Ad {
  id: number;
  name: string;
  sellerPhone?: string;
  type: string;
  budget: number;
  spent: number;
  clicks: number;
  days?: number;
  status: string;
  paymentStatus?: string;
  expiresAt?: string;
  image?: string | null;
  showUrl?: string;
  moderateUrl?: string;
  destroyUrl?: string;
}

const chip = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (['approved', 'active', 'paid', 'success'].includes(value)) return 'chip-success';
  if (['pending', 'moderation', 'waiting'].includes(value)) return 'chip-warning';
  if (['rejected', 'cancelled', 'failed'].includes(value)) return 'chip-danger';
  return 'chip-gray';
};

export default function Reklamalar() {
  const { ads = [] } = usePage<{ ads?: Ad[] }>().props;
  const [selected, setSelected] = useState<Ad | null>(null);
  const totalBudget = useMemo(() => ads.reduce((sum, ad) => sum + (ad.budget || 0), 0), [ads]);
  const pending = ads.filter((ad) => chip(ad.status) === 'chip-warning').length;

  const moderate = (ad: Ad, status: string) => {
    if (!ad.moderateUrl) return;
    router.patch(ad.moderateUrl, { action: status === 'approved' ? 'approve' : 'reject' }, { preserveScroll: true });
  };

  const destroy = (ad: Ad) => {
    if (!ad.destroyUrl || !confirm(`${ad.name} reklamasi o'chirilsinmi?`)) return;
    router.delete(ad.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Reklamalar</h1>
          <p className="page-subtitle">Seller reklamalari, moderatsiya va to'lov holati</p>
        </div>
      </div>

      <div className="kpi-strip row g-3 mb-4">
        {[
          { label: 'Jami reklama', value: ads.length, icon: 'bi-megaphone', color: 'var(--kc-cat-indigo)' },
          { label: 'Moderatsiyada', value: pending, icon: 'bi-hourglass-split', color: 'var(--kc-warn)' },
          { label: 'Tasdiqlangan', value: ads.filter((ad) => chip(ad.status) === 'chip-success').length, icon: 'bi-check-circle', color: 'var(--kc-ok)' },
          { label: 'Budget', value: `${fmt(totalBudget)} so'm`, icon: 'bi-cash-stack', color: 'var(--kc-cat-violet)' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3">
        {ads.map((ad) => (
          <div className="col-xl-4 col-md-6" key={ad.id}>
            <div className="card-panel h-100 d-flex flex-column">
              <div className="d-flex justify-content-between align-items-start mb-3">
                <div style={{ minWidth: 0 }}>
                  <div className="fw-bold text-truncate">{ad.name}</div>
                  <div className="text-muted small text-truncate">{ad.sellerPhone || ad.type}</div>
                </div>
                <span className={`st ${toneOf(chip(ad.status))}`}><i></i>{ad.status || '—'}</span>
              </div>

              {ad.image ? <img className="media-preview rounded mb-3" style={{ aspectRatio: '16/8' }} src={ad.image} alt={ad.name} /> : null}

              <div className="row g-2 text-center mb-3">
                <div className="col-4"><div className="fw-bold">{fmt(ad.clicks)}</div><small className="text-muted">Klik</small></div>
                <div className="col-4"><div className="fw-bold text-success">{fmt(ad.budget)}</div><small className="text-muted">Budget</small></div>
                <div className="col-4"><div className="fw-bold text-primary">{ad.days || 0}</div><small className="text-muted">Kun</small></div>
              </div>

              <div className="p-2 rounded mb-3 small bg-light">
                <div className="d-flex justify-content-between"><span>Turi</span><strong>{ad.type || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>To'lov</span><strong>{ad.paymentStatus || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Tugash</span><strong>{ad.expiresAt || '—'}</strong></div>
              </div>

              <div className="d-flex gap-2 mt-auto">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelected(ad)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(ad)}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Budget</small><div className="fw-bold">{fmt(selected?.budget || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">To'lov</small><div>{selected?.paymentStatus || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`st ${toneOf(chip(selected?.status))}`}><i></i>{selected?.status || '—'}</span></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.moderateUrl ? <Button variant="outline-secondary" onClick={() => moderate(selected, 'approved')}>Tasdiqlash</Button> : null}
          {selected?.moderateUrl ? <Button variant="outline-secondary" onClick={() => moderate(selected, 'rejected')}>Rad etish</Button> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
