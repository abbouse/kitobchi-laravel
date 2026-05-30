import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Promo {
  id: number;
  code: string;
  discount: number;
  type: string;
  used: number;
  max: number;
  status: string;
  maxDiscount?: number;
  minOrder?: number;
  expiresAt?: string;
  createUrl?: string;
  generateUrl?: string;
  showUrl?: string;
  editUrl?: string;
  destroyUrl?: string;
}

export default function Promokodlar() {
  const { promocodes = [] } = usePage<{ promocodes?: Promo[] }>().props;
  const [selected, setSelected] = useState<Promo | null>(null);
  const createUrl = promocodes[0]?.createUrl || '/a122/promocodes/create';
  const generateUrl = promocodes[0]?.generateUrl || '/a122/promocodes/generate';

  const activeCount = promocodes.filter((promo) => promo.status === 'Active').length;
  const usedTotal = useMemo(() => promocodes.reduce((sum, promo) => sum + (promo.used || 0), 0), [promocodes]);

  const destroy = (promo: Promo) => {
    if (!promo.destroyUrl || !confirm(`${promo.code} promokodini o'chirasizmi?`)) return;
    router.delete(promo.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Promokodlar</h1>
          <p className="page-subtitle">Chegirmalar, limitlar va ishlatilish statistikasi</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href={generateUrl}><i className="bi bi-shuffle me-1"></i>Avto-generatsiya</a>
          <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi promokod</a>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami promokod', value: promocodes.length, icon: 'bi-ticket-perforated', color: '#4f46e5' },
          { label: 'Faol', value: activeCount, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Ishlatilgan', value: usedTotal, icon: 'bi-bag-check', color: '#f59e0b' },
          { label: 'Foizli kodlar', value: promocodes.filter((promo) => promo.type === 'percentage').length, icon: 'bi-percent', color: '#7c3aed' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Kod</th><th>Chegirma</th><th>Turi</th><th>Ishlatilgan</th><th>Limit</th><th>Muddati</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {promocodes.map((promo) => {
                const percent = promo.max > 0 ? Math.min(100, Math.round((promo.used / promo.max) * 100)) : 0;
                return (
                  <tr key={promo.id}>
                    <td className="fw-semibold text-primary">#{promo.id}</td>
                    <td className="fw-bold" style={{ fontFamily: 'monospace', letterSpacing: 1 }}>{promo.code}</td>
                    <td className="fw-bold text-success">{promo.type === 'percentage' ? `${promo.discount}%` : `${fmt(promo.discount)} so'm`}</td>
                    <td><span className="chip chip-purple">{promo.type}</span></td>
                    <td>{promo.used} / {promo.max || '∞'}</td>
                    <td>
                      <div className="progress" style={{ width: 90, height: 6 }}>
                        <div className="progress-bar" style={{ width: `${percent}%`, background: percent > 80 ? '#ef4444' : '#10b981' }}></div>
                      </div>
                    </td>
                    <td className="text-muted">{promo.expiresAt || '—'}</td>
                    <td><span className={`chip ${promo.status === 'Active' ? 'chip-success' : 'chip-gray'}`}>{promo.status}</span></td>
                    <td>
                      <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(promo)}><i className="bi bi-eye"></i></button>
                      {promo.editUrl ? <a className="btn btn-sm btn-light me-1" href={promo.editUrl}><i className="bi bi-pencil"></i></a> : null}
                      <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(promo)}><i className="bi bi-trash"></i></button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Promokod: {selected?.code}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Kod</small><div className="fw-bold" style={{ fontFamily: 'monospace' }}>{selected?.code}</div></div>
            <div className="col-6"><small className="text-muted">Chegirma</small><div className="fw-bold text-success">{selected?.type === 'percentage' ? `${selected?.discount}%` : `${fmt(selected?.discount || 0)} so'm`}</div></div>
            <div className="col-6"><small className="text-muted">Min. buyurtma</small><div>{fmt(selected?.minOrder || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Max. chegirma</small><div>{fmt(selected?.maxDiscount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Ishlatilgan</small><div>{selected?.used} / {selected?.max || '∞'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'Active' ? 'chip-success' : 'chip-gray'}`}>{selected?.status}</span></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.showUrl ? <a className="btn btn-primary-gradient" href={selected.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
