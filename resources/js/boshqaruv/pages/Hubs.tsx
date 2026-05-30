import { useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

interface Hub {
  id: number;
  name: string;
  code?: string;
  country?: string;
  region?: string;
  city?: string;
  address?: string;
  active?: boolean;
  primary?: boolean;
  priority?: number;
  staff?: number;
  fulfillments?: number;
  courierTasks?: number;
  supportsFirstMile?: boolean;
  supportsLastMile?: boolean;
  supportsPostal?: boolean;
  indexUrl?: string;
}

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

export default function Hubs() {
  const { hubs = [] } = usePage<{ hubs?: Hub[] }>().props;
  const [selectedHub, setSelectedHub] = useState<Hub | null>(null);
  const indexUrl = '/boshqaruv/hubs';

  const totalStaff = useMemo(() => hubs.reduce((sum, hub) => sum + (hub.staff || 0), 0), [hubs]);
  const totalFulfillments = useMemo(() => hubs.reduce((sum, hub) => sum + (hub.fulfillments || 0), 0), [hubs]);
  const totalCourierTasks = useMemo(() => hubs.reduce((sum, hub) => sum + (hub.courierTasks || 0), 0), [hubs]);
  const activeHubs = hubs.filter((hub) => hub.active).length;

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Hub Fulfillment</h1>
          <p className="page-subtitle">Fulfillment markazlari, xodimlar va kuryer vazifalari</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami hub', value: hubs.length, icon: 'bi-building', color: '#4f46e5' },
          { label: 'Faol hub', value: activeHubs, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Xodimlar', value: totalStaff, icon: 'bi-people', color: '#7c3aed' },
          { label: 'Fulfillment', value: fmt(totalFulfillments), icon: 'bi-box-seam', color: '#f59e0b' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div>
                  <div className="stat-value">{item.value}</div>
                  <div className="stat-label">{item.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3 mb-4">
        {hubs.map((hub) => (
          <div className="col-xl-4 col-md-6" key={hub.id}>
            <div className="card-panel h-100 d-flex flex-column">
              <div className="d-flex justify-content-between align-items-start mb-3">
                <div className="d-flex align-items-center gap-3" style={{ minWidth: 0 }}>
                  <div className="resource-avatar"><i className="bi bi-building"></i></div>
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{hub.name}</div>
                    <div className="text-muted small text-truncate">{hub.code || hub.city || hub.region || 'Hub'}</div>
                  </div>
                </div>
                <span className={`chip ${hub.active ? 'chip-success' : 'chip-gray'}`}>{hub.active ? 'Faol' : 'Ochiq emas'}</span>
              </div>

              <div className="row g-2 text-center mb-3">
                <div className="col-4"><div className="fw-bold text-primary">{hub.staff || 0}</div><small className="text-muted">Xodim</small></div>
                <div className="col-4"><div className="fw-bold text-success">{hub.fulfillments || 0}</div><small className="text-muted">Order</small></div>
                <div className="col-4"><div className="fw-bold text-warning">{hub.courierTasks || 0}</div><small className="text-muted">Kuryer</small></div>
              </div>

              <div className="p-2 rounded mb-3 small bg-light">
                <div className="d-flex justify-content-between"><span>Manzil</span><strong className="text-end">{hub.city || hub.region || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Priority</span><strong>{hub.priority ?? '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Asosiy hub</span><strong>{hub.primary ? 'Ha' : "Yo'q"}</strong></div>
              </div>

              <div className="d-flex gap-2 flex-wrap mb-3">
                {hub.supportsFirstMile ? <span className="chip chip-info">First mile</span> : null}
                {hub.supportsLastMile ? <span className="chip chip-purple">Last mile</span> : null}
                {hub.supportsPostal ? <span className="chip chip-warning">Pochta</span> : null}
              </div>

              <div className="d-flex gap-2 mt-auto">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelectedHub(hub)}><i className="bi bi-eye"></i> Batafsil</button>
                <button className="btn btn-sm btn-primary-gradient flex-fill" onClick={() => setSelectedHub(hub)}><i className="bi bi-gear"></i> Sozlash</button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Fulfillment nazorati</div>
            <small className="text-muted">Hub orderlari va kuryer vazifalari eski controller orqali boshqariladi</small>
          </div>
          <span className="chip chip-info">{totalCourierTasks} ta kuryer vazifasi</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>Hub</th><th>Kod</th><th>Hudud</th><th>Xodim</th><th>Fulfillment</th><th>Kuryer task</th><th>Qo'llab-quvvatlaydi</th><th>Amallar</th></tr></thead>
            <tbody>
              {hubs.map((hub) => (
                <tr key={hub.id}>
                  <td className="fw-semibold">{hub.name}</td>
                  <td>{hub.code || '—'}</td>
                  <td>{[hub.city, hub.region].filter(Boolean).join(', ') || '—'}</td>
                  <td>{hub.staff || 0}</td>
                  <td>{hub.fulfillments || 0}</td>
                  <td>{hub.courierTasks || 0}</td>
                  <td>
                    <span className="chip chip-gray">
                      {[hub.supportsFirstMile ? 'First' : null, hub.supportsLastMile ? 'Last' : null, hub.supportsPostal ? 'Postal' : null].filter(Boolean).join(' / ') || '—'}
                    </span>
                  </td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelectedHub(hub)}><i className="bi bi-eye"></i></button>
                    <button className="btn btn-sm btn-light" onClick={() => setSelectedHub(hub)}><i className="bi bi-eye"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selectedHub} onHide={() => setSelectedHub(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selectedHub?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Kod</small><div className="fw-semibold">{selectedHub?.code || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selectedHub?.active ? 'chip-success' : 'chip-gray'}`}>{selectedHub?.active ? 'Faol' : 'Faol emas'}</span></div></div>
            <div className="col-12"><small className="text-muted">Manzil</small><div>{selectedHub?.address || '—'}</div></div>
            <div className="col-4"><small className="text-muted">Xodim</small><div className="fw-bold">{selectedHub?.staff || 0}</div></div>
            <div className="col-4"><small className="text-muted">Fulfillment</small><div className="fw-bold">{selectedHub?.fulfillments || 0}</div></div>
            <div className="col-4"><small className="text-muted">Kuryer</small><div className="fw-bold">{selectedHub?.courierTasks || 0}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelectedHub(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
