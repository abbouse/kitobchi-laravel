import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';

import { StatWidget } from '../components/Axelit';

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
  if (['approved', 'active', 'paid', 'success'].includes(value)) return 'text-light-success';
  if (['pending', 'moderation', 'waiting'].includes(value)) return 'text-light-warning';
  if (['rejected', 'cancelled', 'failed'].includes(value)) return 'text-light-danger';
  return 'text-light-secondary';
};

export default function Reklamalar() {
  const { ads = [] } = usePage<{ ads?: Ad[] }>().props;
  const [selected, setSelected] = useState<Ad | null>(null);
  const totalBudget = useMemo(() => ads.reduce((sum, ad) => sum + (ad.budget || 0), 0), [ads]);
  const pending = ads.filter((ad) => chip(ad.status) === 'text-light-warning').length;

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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Reklamalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Seller reklamalari, moderatsiya va to'lov holati</p>
        </div>
      </div>

      <div className="row">
        {[
          { label: 'Jami reklama', value: ads.length, icon: 'ti-speakerphone', color: 'rgba(var(--primary), 1)' },
          { label: 'Moderatsiyada', value: pending, icon: 'ti-hourglass', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Tasdiqlangan', value: ads.filter((ad) => chip(ad.status) === 'text-light-success').length, icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { label: 'Budget', value: `${fmt(totalBudget)} so'm`, icon: 'ti-cash', color: 'rgba(var(--primary), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.value} />
        </div>))}
      </div>

      <div className="row">
        {ads.map((ad) => (
          <div className="col-xl-4 col-md-6" key={ad.id}>
            <div className="card h-100">
              <div className="card-body d-flex flex-column">
                <div className="d-flex justify-content-between align-items-start mb-3">
                  <div className="min-w-0">
                    <div className="f-w-600 text-truncate">{ad.name}</div>
                    <div className="text-muted f-s-13 text-truncate">{ad.sellerPhone || ad.type}</div>
                  </div>
                  <span className={`badge text-uppercase ${toneBadge(toneOf(chip(ad.status)))}`}>{ad.status || '—'}</span>
                </div>

                {ad.image ? <img className="w-100 b-r-22 mb-3 object-fit-cover" style={{ aspectRatio: '16/8', maxHeight: 220 }} src={ad.image} alt={ad.name} /> : null}

                <div className="row g-2 text-center mb-3">
                  <div className="col-4"><div className="f-w-600">{fmt(ad.clicks)}</div><small className="text-muted">Klik</small></div>
                  <div className="col-4"><div className="f-w-600 text-success">{fmt(ad.budget)}</div><small className="text-muted">Budget</small></div>
                  <div className="col-4"><div className="f-w-600 text-primary">{ad.days || 0}</div><small className="text-muted">Kun</small></div>
                </div>

                <div className="p-2 b-r-8 mb-3 f-s-13 bg-light-secondary">
                  <div className="d-flex justify-content-between"><span>Turi</span><strong>{ad.type || '—'}</strong></div>
                  <div className="d-flex justify-content-between"><span>To'lov</span><strong>{ad.paymentStatus || '—'}</strong></div>
                  <div className="d-flex justify-content-between"><span>Tugash</span><strong>{ad.expiresAt || '—'}</strong></div>
                </div>

                <div className="d-flex gap-2 mt-auto">
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setSelected(ad)}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(ad)}><i className="ti ti-trash"></i></button>
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
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Budget</small><div className="f-w-600">{fmt(selected?.budget || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">To'lov</small><div>{selected?.paymentStatus || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`badge text-uppercase ${toneBadge(toneOf(chip(selected?.status)))}`}>{selected?.status || '—'}</span></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.moderateUrl ? <Button variant="outline-secondary" onClick={() => moderate(selected, 'approved')}>Tasdiqlash</Button> : null}
          {selected?.moderateUrl ? <Button variant="outline-secondary" onClick={() => moderate(selected, 'rejected')}>Rad etish</Button> : null}
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
