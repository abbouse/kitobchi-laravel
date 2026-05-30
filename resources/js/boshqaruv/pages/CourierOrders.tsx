import { useState } from 'react';
import { Modal, Button } from 'react-bootstrap';
import { couriers, fmt } from '../data';

export default function CourierOrders() {
  const [list] = useState(couriers);
  const [showView, setShowView] = useState(false);
  const [showContract, setShowContract] = useState(false);
  const [selectedItem, setSelectedItem] = useState<typeof couriers[0] | null>(null);

  const totalOrders = list.reduce((a, c) => a + c.ordersToday, 0);
  const totalDelivered = list.reduce((a, c) => a + c.deliveredToday, 0);
  const totalFailed = list.reduce((a, c) => a + c.failedToday, 0);

  const statusChip = (s: string) => ({
    'Available': 'chip-success',
    'On Route': 'chip-info',
    'Offline': 'chip-gray',
    'Suspended': 'chip-danger',
  }[s] || 'chip-gray');

  const vehicleIcon = (v: string) => ({
    'Bicycle': '🚲',
    'Scooter': '🛵',
    'Motorbike': '🏍️',
    'Car': '🚗',
  }[v] || '🚲');

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kuryer tizimi</h1>
          <p className="page-subtitle">Kuryerlar · shartnomalar · tranzaksiyalar · hub biriktirish</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => alert("Yangi kuryer qo'shish formasi ochiladi")}>
          <i className="bi bi-plus-lg me-1"></i>Yangi kuryer
        </button>
      </div>

      {/* Stats */}
      <div className="row g-2 mb-3">
        {[
          { l: 'Jami kuryerlar', v: list.length, icon: 'bi-bicycle', c: '#4f46e5' },
          { l: 'Bugungi topshiriq', v: totalOrders, icon: 'bi-box-seam', c: '#10b981' },
          { l: 'Yetkazilgan', v: totalDelivered, icon: 'bi-check-circle', c: '#059669' },
          { l: 'Muvaffaqiyatsiz', v: totalFailed, icon: 'bi-x-circle', c: '#ef4444' },
          { l: "Yo'lda", v: list.filter(c => c.status === 'On Route').length, icon: 'bi-truck', c: '#f59e0b' },
          { l: "O'rt. on-time", v: Math.round(list.reduce((a, c) => a + c.onTimeRate, 0) / list.length) + '%', icon: 'bi-clock', c: '#7c3aed' },
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

      {/* Cards */}
      <div className="row g-3">
        {list.map((courier) => (
          <div className="col-xl-4 col-md-6" key={courier.id}>
            <div className="card-panel h-100 d-flex flex-column">
              {/* Header */}
              <div className="d-flex justify-content-between align-items-start mb-2">
                <div className="d-flex align-items-center gap-2">
                  <div style={{ width: 44, height: 44, borderRadius: '50%', background: courier.status === 'On Route' ? 'linear-gradient(135deg,#10b981,#059669)' : courier.status === 'Offline' ? '#d1d5db' : '#3b82f6', display: 'grid', placeItems: 'center', fontSize: 20 }}>
                    {vehicleIcon(courier.vehicle)}
                  </div>
                  <div>
                    <div className="fw-bold" style={{ fontSize: 14 }}>{courier.name}</div>
                    <small className="text-muted">{courier.vehicle} · {courier.region}</small>
                  </div>
                </div>
                <span className={`chip ${statusChip(courier.status)}`} style={{ fontSize: 9 }}>{courier.status}</span>
              </div>

              {/* Stats */}
              <div className="d-flex text-center gap-1 mb-2 p-2 rounded" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                <div className="flex-fill">
                  <div className="fw-bold" style={{ fontSize: 18, color: '#4f46e5' }}>{courier.ordersToday}</div>
                  <small className="text-muted" style={{ fontSize: 10 }}>Bugun</small>
                </div>
                <div className="flex-fill">
                  <div className="fw-bold" style={{ fontSize: 18, color: '#10b981' }}>{courier.deliveredToday}</div>
                  <small className="text-muted" style={{ fontSize: 10 }}>Yetkazildi</small>
                </div>
                <div className="flex-fill">
                  <div className="fw-bold" style={{ fontSize: 18, color: courier.failedToday > 0 ? '#ef4444' : '#10b981' }}>{courier.failedToday}</div>
                  <small className="text-muted" style={{ fontSize: 10 }}>Failed</small>
                </div>
                <div className="flex-fill">
                  <div className="fw-bold" style={{ fontSize: 18, color: '#059669' }}>{courier.onTimeRate}%</div>
                  <small className="text-muted" style={{ fontSize: 10 }}>On-time</small>
                </div>
              </div>

              {/* Hub */}
              <div className="d-flex justify-content-between small mb-1">
                <span className="text-muted">Hub:</span>
                <span className="fw-semibold">{courier.assignedHub === 'hub-toshkent' ? 'Hub Toshkent' : courier.assignedHub === 'hub-samarqand' ? 'Hub Samarqand' : courier.assignedHub}</span>
              </div>

              {/* Rating & Revenue */}
              <div className="d-flex justify-content-between small mb-2">
                <span><span className="chip chip-warning" style={{ fontSize: 9 }}>⭐ {courier.rating}</span></span>
                <span className="fw-semibold text-primary">Jami: {fmt(courier.totalRevenue)} so'm</span>
              </div>

              {/* Contract type */}
              <div className="p-2 rounded mb-2 small" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                <div className="d-flex justify-content-between">
                  <span><strong>{courier.contract.type}</strong> · {courier.contract.baseSalary > 0 ? `${fmt(courier.contract.baseSalary)}/oy` : 'Bayt bo\'yi'}</span>
                  <span className="fw-semibold">{courier.contract.perDelivery} so'm/delivery</span>
                </div>
                <div className="text-muted" style={{ fontSize: 10 }}>
                  {courier.contract.startDate} — {courier.contract.endDate}
                  {courier.contract.insurance && ' · 🛡️ Sug\'urta'}
                </div>
              </div>

              {/* Balance */}
              <div className="d-flex justify-content-between align-items-center mb-2 small">
                <span>Balans: <strong className="text-success">{fmt(courier.balance)} so'm</strong></span>
                <span>Jami yetkazish: <strong>{courier.totalDeliveries} ta</strong></span>
              </div>

              <div className="d-flex gap-2 mt-auto pt-2 border-top">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelectedItem(courier); setShowView(true); }}>
                  <i className="bi bi-eye"></i> Tranzaksiya
                </button>
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelectedItem(courier); setShowContract(true); }}>
                  <i className="bi bi-file-earmark-text"></i> Shartnoma
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* View Transactions Modal */}
      <Modal show={showView} onHide={() => setShowView(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">{selectedItem?.name} — Tranzaksiyalar</Modal.Title>
        </Modal.Header>
        <Modal.Body style={{ maxHeight: 400, overflowY: 'auto' }}>
          {selectedItem && (
            <div className="table-responsive">
              <table className="data-table">
                <thead><tr><th>ID</th><th>Turi</th><th>Summa</th><th>Sana</th><th>Status</th><th>Izoh</th></tr></thead>
                <tbody>
                  {selectedItem.transactions.map(t => (
                    <tr key={t.id}>
                      <td style={{ fontSize: 11, color: '#4f46e5' }}>{t.id}</td>
                      <td><span className="chip" style={{ fontSize: 9, background: t.type === 'bonus' || t.type === 'salary' ? '#dcfce7' : t.type === 'penalty' ? '#fee2e2' : '#eef2ff', color: t.type === 'bonus' || t.type === 'salary' ? '#166534' : t.type === 'penalty' ? '#991b1b' : '#1e40af' }}>{t.type}</span></td>
                      <td className={`fw-bold ${t.amount >= 0 ? 'text-success' : 'text-danger'}`}>{t.amount >= 0 ? '+' : ''}{fmt(t.amount)} so'm</td>
                      <td className="text-muted small">{t.date}</td>
                      <td><span className={`chip ${t.status === 'Completed' ? 'chip-success' : t.status === 'Pending' ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{t.status}</span></td>
                      <td style={{ fontSize: 10 }}>{t.note}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowView(false)} className="w-100">Yopish</Button>
        </Modal.Footer>
      </Modal>

      {/* View Contract Modal */}
      <Modal show={showContract} onHide={() => setShowContract(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">{selectedItem?.name} — Shartnoma</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedItem && (
            <>
              <div className="row g-2 mb-3">
                <div className="col-6"><small className="text-muted d-block">Shartnoma turi</small><div className="fw-semibold">{selectedItem.contract.type}</div></div>
                <div className="col-6"><small className="text-muted d-block">Status</small><span className="chip chip-success" style={{ fontSize: 9 }}>{selectedItem.contract.status}</span></div>
                <div className="col-6"><small className="text-muted d-block">Boshlanish sanasi</small><div>{selectedItem.contract.startDate}</div></div>
                <div className="col-6"><small className="text-muted d-block">Tugash sanasi</small><div>{selectedItem.contract.endDate}</div></div>
                <div className="col-6"><small className="text-muted d-block">Asosiy oylik</small><div className="fw-bold text-success">{fmt(selectedItem.contract.baseSalary)} so'm</div></div>
                <div className="col-6"><small className="text-muted d-block">Har bir yetkazish</small><div className="fw-bold">{fmt(selectedItem.contract.perDelivery)} so'm</div></div>
                <div className="col-12"><small className="text-muted d-block">Sug'urta</small><div>{selectedItem.contract.insurance ? '✅ Ha' : '❌ Yo\'q'}</div></div>
                <div className="col-12"><small className="text-muted d-block">Hujjat</small><div className="fw-semibold" style={{ color: '#4f46e5' }}>{selectedItem.contract.document}</div></div>
              </div>
              <div className="alert alert-info small">
                <i className="bi bi-info-circle me-1"></i> 
                Shartnoma {selectedItem.contract.autoRenew ? 'avtomatik uzayadi (auto-renew)' : 'avtomatik uzaymaydi'}.
              </div>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowContract(false)}>Yopish</Button>
          <Button variant="primary-gradient" className="btn-primary-gradient" onClick={() => alert("Shartnoma PDF yuklanmoqda...")}>
            <i className="bi bi-download me-1"></i>PDF yuklab olish
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
