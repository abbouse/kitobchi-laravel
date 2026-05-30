import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== MYSTERY BOX =====
export function MysteryBox() {
  const { mysteryBox = { plans: [], subscriptions: [], indexUrl: '/boshqaruv/mystery-box', plansUrl: '/boshqaruv/mystery-box' } } = usePage<{
    mysteryBox?: {
      plans: Array<{ id: number; name: string; months: number; price: number; booksPerMonth: number; active: boolean; subscribers: number; plansUrl?: string; destroyUrl?: string }>;
      subscriptions: Array<{ id: number; user: string; phone?: string; plan: string; status: string; statusLabel?: string; nextDelivery?: string; progress?: number; showUrl?: string; pauseUrl?: string; resumeUrl?: string; cancelUrl?: string }>;
      indexUrl: string;
      plansUrl: string;
    };
  }>().props;
  const [selected, setSelected] = useState<(typeof mysteryBox.subscriptions)[0] | null>(null);

  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true });

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Mystery Box</h1><p className="page-subtitle">{mysteryBox.plans.length} ta plan · {mysteryBox.subscriptions.filter(s => s.status === 'active').length} ta faol obuna</p></div>
      </div>

      <div className="row g-3 mb-3">
        {mysteryBox.plans.map(p => (
          <div className="col-xl-4 col-md-6" key={p.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between align-items-center mb-2">
                <div className="fw-bold fs-5">{p.name}</div>
                <span className={`chip ${p.active ? 'chip-success' : 'chip-gray'}`}>{p.active ? 'Faol' : 'Nofaol'}</span>
              </div>
              <div className="fw-bold text-primary fs-4">{p.price.toLocaleString()} so'm<small className="text-muted fs-6">/oy</small></div>
              <div className="text-muted mb-2">{p.months} oy · {p.booksPerMonth} kitob/oy · {p.subscribers} obunachi</div>
              <div className="d-flex gap-2">
                {p.destroyUrl ? <button className="btn btn-sm btn-light text-danger" onClick={() => router.delete(p.destroyUrl!, { preserveScroll: true })}><i className="bi bi-trash"></i></button> : null}
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-title mb-3">📦 Obunalar</div>
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Plan</th><th>Status</th><th>Keyingi yetkazish</th><th>Amallar</th></tr></thead>
          <tbody>{mysteryBox.subscriptions.map(s => (
            <tr key={s.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{s.id}</td>
              <td className="fw-semibold">{s.user}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{s.plan}</span></td>
              <td><span className={`chip ${s.status === 'active' ? 'chip-success' : s.status === 'paused' ? 'chip-warning' : 'chip-gray'}`} style={{ fontSize: 9 }}>{s.statusLabel || s.status}</span></td>
              <td className="text-muted">{s.nextDelivery}</td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(s)}><i className="bi bi-eye"></i></button>
                {s.status === 'active' ? <button className="btn btn-sm btn-warning" onClick={() => patch(s.pauseUrl)}><i className="bi bi-pause-fill"></i></button> : <button className="btn btn-sm btn-success" onClick={() => patch(s.resumeUrl)}><i className="bi bi-play-fill"></i></button>}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.user}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Plan</small><div>{selected?.plan}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div>{selected?.statusLabel || selected?.status}</div></div>
            <div className="col-6"><small className="text-muted">Keyingi yetkazish</small><div>{selected?.nextDelivery || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Progress</small><div>{selected?.progress || 0}%</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

// ===== SOVG'ALAR =====
export function SovgAlar() {
  const { gifts = [] } = usePage<{
    gifts?: Array<{ id: number; name: string; seller?: string; stock: number; priceFrom: number; priceTo: number; status: string; approved: boolean; sold: number; revenue: number; image?: string | null; indexUrl?: string }>;
  }>().props;
  const indexUrl = '/boshqaruv/sovgalar';

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Sovg'alar</h1><p className="page-subtitle">Jami {gifts.length} ta sovg'a mahsuloti</p></div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th></th><th>Nomi</th><th>Seller</th><th>Narx</th><th>Ombor</th><th>Sotilgan</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{gifts.map(gift => (
            <tr key={gift.id}>
              <td><div className="thumb">{gift.image ? <img src={gift.image} alt={gift.name} /> : <i className="bi bi-gift"></i>}</div></td>
              <td className="fw-semibold">{gift.name}</td>
              <td>{gift.seller || '—'}</td>
              <td>{gift.priceFrom.toLocaleString()} - {gift.priceTo.toLocaleString()} so'm</td>
              <td>{gift.stock}</td>
              <td>{gift.sold}</td>
              <td><span className={`chip ${gift.approved ? 'chip-success' : 'chip-warning'}`}>{gift.approved ? gift.status : 'Moderatsiya'}</span></td>
              <td><span className="chip chip-gray">Ko'rildi</span></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}

// ===== LOGISTIKA — YETKAZISH ZONALARI =====
export function Logistika() {
  const { deliveryServices = [] } = usePage<{
    deliveryServices?: Array<{ id: number; name: string; type?: string; price: number; days: number; country?: string; capital: boolean; freeFrom: number; active: boolean; indexUrl?: string }>;
  }>().props;
  const indexUrl = '/boshqaruv/logistika';

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Yetkazish zonalari va qoidalar</h1><p className="page-subtitle">Jami {deliveryServices.length} ta yetkazish xizmati</p></div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Xizmat</th><th>Turi</th><th>Narx/kg</th><th>Bepuldan</th><th>Muddat</th><th>Mamlakat</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{deliveryServices.map(service => (
            <tr key={service.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{service.id}</td>
              <td className="fw-semibold">{service.name}</td>
              <td><span className="chip chip-gray">{service.type || '—'}</span></td>
              <td>{service.price === 0 ? <span className="chip chip-success">Bepul</span> : <span className="fw-semibold">{service.price.toLocaleString()} so'm</span>}</td>
              <td>{service.freeFrom > 0 ? `${service.freeFrom.toLocaleString()} so'm` : '—'}</td>
              <td className="fw-semibold">{service.days} kun</td>
              <td>{service.country || '—'} {service.capital ? '· poytaxt' : ''}</td>
              <td><span className={`chip ${service.active ? 'chip-success' : 'chip-gray'}`}>{service.active ? 'Faol' : 'Nofaol'}</span></td>
              <td><span className="chip chip-gray">Ko'rildi</span></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}
