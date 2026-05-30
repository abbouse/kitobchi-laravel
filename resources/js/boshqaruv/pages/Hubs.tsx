import { useState } from 'react';
import { Modal, Button } from 'react-bootstrap';
import { hubs, hubEmployees, hubFulfillmentQueue, fmt } from '../data';

export default function Hubs() {
  const [showHubDetail, setShowHubDetail] = useState(false);
  const [selectedHub, setSelectedHub] = useState<typeof hubs[0] | null>(null);

  const statusChip = (s: string) => ({
    'Active': 'chip-success', 'Maintenance': 'chip-warning', 'Overloaded': 'chip-danger',
  }[s] || 'chip-gray');

  const totalCapacity = hubs.reduce((a, h) => a + h.capacity, 0);
  const totalThroughput = hubs.reduce((a, h) => a + h.dailyThroughput, 0);
  const totalPending = hubs.reduce((a, h) => a + h.pendingOrders, 0);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Hub Fulfillment System</h1>
          <p className="page-subtitle">Fulfillment markazlari · qadoqlash · kuryer va pochta jo'natmalari</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => alert("Yangi hub qo'shish formasi ochiladi")}>
          <i className="bi bi-plus-lg me-1"></i>Yangi hub
        </button>
      </div>

      {/* Stats */}
      <div className="row g-2 mb-3">
        {[
          { l: 'Jami hub', v: hubs.length, icon: 'bi-building', c: '#4f46e5' },
          { l: 'Jami xodimlar', v: hubEmployees.length, icon: 'bi-people', c: '#10b981' },
          { l: 'Bugungi throughput', v: fmt(totalThroughput), icon: 'bi-box-seam', c: '#059669' },
          { l: 'Kutilayotgan', v: totalPending, icon: 'bi-clock-history', c: '#f59e0b' },
          { l: 'Kunlik qadoqlash', v: `${Math.round((totalThroughput / totalCapacity) * 100)}%`, icon: 'bi-speedometer2', c: '#7c3aed' },
          { l: "O'rtacha vaqt", v: '4m 52s', icon: 'bi-stopwatch', c: '#ec4899' },
        ].map(s => (
          <div className="col-xl-2 col-md-4 col-6" key={s.l}>
            <div className="stat-card" style={{ padding: 12 }}>
              <div className="d-flex align-items-center gap-2">
                <div className="stat-icon" style={{ width: 36, height: 36, background: s.c, fontSize: 14 }}><i className={`bi ${s.icon}`}></i></div>
                <div>
                  <div className="stat-value" style={{ fontSize: 18 }}>{s.v}</div>
                  <div className="stat-label">{s.l}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Hub Cards */}
      <div className="row g-3 mb-3">
        {hubs.map((hub) => (
          <div className="col-xl-4 col-md-6" key={hub.id}>
            <div className="card-panel h-100 d-flex flex-column">
              {/* Header */}
              <div className="d-flex justify-content-between align-items-start mb-2">
                <div className="d-flex align-items-center gap-2">
                  <div style={{ width: 44, height: 44, borderRadius: 10, background: hub.status === 'Active' ? 'linear-gradient(135deg,#4f46e5,#7c3aed)' : hub.status === 'Overloaded' ? 'linear-gradient(135deg,#ef4444,#dc2626)' : '#6b7280', display: 'grid', placeItems: 'center', fontSize: 18 }}>
                    <i className="bi bi-building" style={{ color: 'white' }}></i>
                  </div>
                  <div>
                    <div className="fw-bold" style={{ fontSize: 14 }}>{hub.name}</div>
                    <small className="text-muted">{hub.address}</small>
                  </div>
                </div>
                <span className={`chip ${statusChip(hub.status)}`} style={{ fontSize: 9 }}>{hub.status}</span>
              </div>

              {/* Manager */}
              <div className="small mb-2">
                <span className="text-muted">Manager: </span>
                <span className="fw-semibold">{hub.manager}</span>
                <span className="text-muted"> · {hub.phone}</span>
              </div>

              {/* Stats grid */}
              <div className="row g-1 mb-2 text-center small">
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ fontSize: 18, color: '#4f46e5' }}>{hub.employees}</div>
                  <small className="text-muted">Xodim</small>
                </div>
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ fontSize: 18, color: '#10b981' }}>{hub.dailyThroughput}</div>
                  <small className="text-muted">Bugun</small>
                </div>
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ fontSize: 18, color: `${hub.efficiency > 85 ? '#10b981' : '#ef4444'}` }}>{hub.efficiency}%</div>
                  <small className="text-muted">Effektivlik</small>
                </div>
              </div>

              {/* Progress bar */}
              <div className="mb-2">
                <div className="d-flex justify-content-between small">
                  <span className="text-muted">Qadoqlash: {hub.dailyThroughput} / {hub.capacity}</span>
                  <span className="fw-semibold">{Math.round((hub.dailyThroughput / hub.capacity) * 100)}%</span>
                </div>
                <div className="progress" style={{ height: 8 }}>
                  <div className="progress-bar" style={{ width: `${(hub.dailyThroughput / hub.capacity) * 100}%`, background: hub.dailyThroughput / hub.capacity > 0.85 ? '#10b981' : hub.dailyThroughput / hub.capacity > 0.7 ? '#f59e0b' : '#4f46e5' }}></div>
                </div>
              </div>

              {/* Inventory & shipping */}
              <div className="d-flex justify-content-between small text-muted mb-1">
                <span>📚 {fmt(hub.inventory.books)} kitob</span>
                <span>✏️ {fmt(hub.inventory.stationery)} kanselyariya</span>
              </div>
              <div className="d-flex justify-content-between small text-muted mb-2">
                <span>🚚 Kuryer kelishi: {hub.courierArrivals} marta</span>
                <span>📮 Pochta: {hub.mailOrders} ta</span>
              </div>

              {/* Packing time */}
              <div className="p-2 rounded small mb-2" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                <div className="d-flex justify-content-between">
                  <span>O'rtacha qadoqlash: <strong>{hub.avgPackingTime}</strong></span>
                  <span>Status: {hub.status}</span>
                </div>
              </div>

              <div className="d-flex gap-2 mt-auto pt-2 border-top">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelectedHub(hub); setShowHubDetail(true); }}>
                  <i className="bi bi-eye"></i> Batafsil
                </button>
                <button className="btn btn-sm btn-primary-gradient flex-fill">
                  <i className="bi bi-arrow-repeat"></i> Qayta yuklash
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Fulfillment Queue */}
      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">🚚 Fulfillment navbati (real vaqtda)</div>
            <small className="text-muted">Do'kondan hubga keladigan va hubdan chiqadigan buyurtmalar</small>
          </div>
          <span className="chip chip-success">
            <span className="live-pulse"></span>{hubFulfillmentQueue.filter(q => q.status !== 'Shipped').length} ta navbatda
          </span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr><th>ID</th><th>Buyurtma</th><th>Hub</th><th>Status</th><th>Qabul vaqti</th><th>ETA</th><th>Priority</th><th>Seller</th></tr>
            </thead>
            <tbody>
              {hubFulfillmentQueue.map(q => {
                const hubName = hubs.find(h => h.id === q.hubId)?.name.split('—')[0] || q.hubId;
                return (
                  <tr key={q.id}>
                    <td className="fw-semibold" style={{ color: '#4f46e5', fontSize: 11 }}>{q.id}</td>
                    <td className="fw-semibold">{q.orderId}</td>
                    <td>{hubName}</td>
                    <td>
                      <span className={`chip ${
                        q.status === 'Shipped' ? 'chip-success' :
                        q.status === 'Packed' || q.status === 'Labeled' ? 'chip-purple' :
                        q.status === 'Packing' ? 'chip-info' :
                        q.status === 'Waiting Courier' ? 'chip-warning' :
                        'chip-gray'
                      }`} style={{ fontSize: 9 }}>{q.status}</span>
                    </td>
                    <td className="text-muted">{q.time}</td>
                    <td className="fw-semibold">{q.eta}</td>
                    <td>
                      <span className={`chip ${q.priority === 'Express' ? 'chip-danger' : 'chip-gray'}`} style={{ fontSize: 9 }}>
                        {q.priority}
                      </span>
                    </td>
                    <td className="text-muted small">{q.sellerId}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Hub Detail Modal */}
      <Modal show={showHubDetail} onHide={() => setShowHubDetail(false)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">{selectedHub?.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body style={{ maxHeight: 500, overflowY: 'auto' }}>
          {selectedHub && (
            <>
              <div className="row g-2 mb-3">
                <div className="col-md-4"><small className="text-muted d-block">Manzil</small><div className="fw-semibold">{selectedHub.address}</div></div>
                <div className="col-md-4"><small className="text-muted d-block">Manager</small><div className="fw-semibold">{selectedHub.manager}</div></div>
                <div className="col-md-4"><small className="text-muted d-block">Telefon</small><div>{selectedHub.phone}</div></div>
                <div className="col-md-4"><small className="text-muted d-block">Kunlik sig'im</small><div className="fw-bold">{fmt(selectedHub.capacity)}</div></div>
                <div className="col-md-4"><small className="text-muted d-block">Bugungi qadoqlash</small><div className="fw-bold text-success">{fmt(selectedHub.dailyThroughput)}</div></div>
                <div className="col-md-4"><small className="text-muted d-block">Effektivlik</small><div className="fw-bold" style={{ color: selectedHub.efficiency > 85 ? '#10b981' : '#ef4444' }}>{selectedHub.efficiency}%</div></div>
              </div>

              <h6 className="fw-bold border-bottom pb-2">Hub xodimlari</h6>
              <div className="table-responsive mb-3">
                <table className="data-table">
                  <thead><tr><th>Ism</th><th>Lavozim</th><th>Smena</th><th>Faol buyurtma</th><th>Productivity</th><th>Oylik</th></tr></thead>
                  <tbody>
                    {hubEmployees.filter(e => e.hubId === selectedHub.id).map(e => (
                      <tr key={e.id}>
                        <td className="fw-semibold">{e.name}</td>
                        <td>{e.role}</td>
                        <td>{e.shift}</td>
                        <td>{e.activeOrders}</td>
                        <td><div className="d-flex align-items-center gap-1"><div className="progress" style={{ width: 60, height: 6 }}><div className="progress-bar bg-success" style={{ width: `${e.productivity}%` }}></div></div><small>{e.productivity}%</small></div></td>
                        <td className="fw-semibold">{fmt(e.salary)} so'm</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <h6 className="fw-bold border-bottom pb-2">Ombor holati</h6>
              <div className="row g-2 mb-3">
                <div className="col-6 p-3 rounded text-center" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                  <i className="bi bi-book" style={{ fontSize: 24, color: '#4f46e5' }}></i>
                  <div className="fw-bold fs-4">{fmt(selectedHub.inventory.books)}</div>
                  <small className="text-muted">Kitoblar</small>
                </div>
                <div className="col-6 p-3 rounded text-center" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                  <i className="bi bi-pencil" style={{ fontSize: 24, color: '#10b981' }}></i>
                  <div className="fw-bold fs-4">{fmt(selectedHub.inventory.stationery)}</div>
                  <small className="text-muted">Kanselyariya</small>
                </div>
              </div>

              <h6 className="fw-bold border-bottom pb-2">Logistika</h6>
              <div className="row g-2">
                <div className="col-6"><div className="p-3 rounded" style={{ background: '#eef2ff' }}><div className="text-muted small">Kuryer kelishlari (bugun)</div><div className="fw-bold fs-5" style={{ color: '#4f46e5' }}>{selectedHub.courierArrivals}</div></div></div>
                <div className="col-6"><div className="p-3 rounded" style={{ background: '#dcfce7' }}><div className="text-muted small">Pochta orqali jo'natilgan</div><div className="fw-bold fs-5 text-success">{selectedHub.mailOrders}</div></div></div>
                <div className="col-12"><div className="p-3 rounded" style={{ background: '#fef3c7' }}><div className="text-muted small">Kutilayotgan mahsulotlar</div><div className="fw-bold fs-5" style={{ color: '#92400e' }}>{selectedHub.pendingOrders} ta</div></div></div>
              </div>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowHubDetail(false)} className="w-100">Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
