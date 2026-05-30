import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Courier {
  id: number;
  name: string;
  phone?: string;
  region?: string;
  status?: string;
  verificationStatus?: string;
  transport?: string;
  vehicle?: string;
  plate?: string;
  balance?: number;
  orders?: number;
  photo?: string | null;
  createUrl?: string;
  showUrl?: string;
  editUrl?: string;
  approveUrl?: string;
  rejectUrl?: string;
  unblockUrl?: string;
}

interface CourierOrder {
  id: number;
  orderId?: number | string;
  courier: string;
  courierPhone?: string;
  customer: string;
  customerPhone?: string;
  amount: number;
  courierPrice?: number;
  bonus?: number;
  status: string;
  date?: string;
  showUrl?: string;
  statusUrl?: string;
}

const statusChip = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (['active', 'approved', 'verified', 'c', 'completed', 'delivered'].includes(value)) return 'chip-success';
  if (['pending', 'new', 'a', 'p'].includes(value)) return 'chip-warning';
  if (['on_route', 'in_delivery', 'd'].includes(value)) return 'chip-info';
  if (['blocked', 'rejected', 'cancelled', 'f'].includes(value)) return 'chip-danger';
  return 'chip-gray';
};

const orderStatuses = [
  { code: 'B', label: "Qabul qilindi" },
  { code: 'D', label: "Yo'lda" },
  { code: 'C', label: 'Yetkazildi' },
  { code: 'F', label: 'Bekor' },
];

export default function CourierOrders() {
  const { couriers = [], courierOrders = [] } = usePage<{ couriers?: Courier[]; courierOrders?: CourierOrder[] }>().props;
  const [selectedCourier, setSelectedCourier] = useState<Courier | null>(null);
  const [selectedOrder, setSelectedOrder] = useState<CourierOrder | null>(null);

  const totalBalance = useMemo(() => couriers.reduce((sum, courier) => sum + (courier.balance || 0), 0), [couriers]);
  const activeCouriers = couriers.filter((courier) => statusChip(courier.status) === 'chip-success').length;
  const routeOrders = courierOrders.filter((order) => statusChip(order.status) === 'chip-info').length;
  const createUrl = '/boshqaruv/couriers';

  const patch = (url?: string, data: Record<string, string> = {}) => {
    if (!url) return;
    router.patch(url, data, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kuryerlar</h1>
          <p className="page-subtitle">Kuryer profillari, balans, tasdiqlash va yetkazmalar</p>
        </div>
        <a className="btn btn-primary-gradient" href={createUrl}>
          <i className="bi bi-plus-lg me-1"></i>Yangi kuryer
        </a>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami kuryer', value: couriers.length, icon: 'bi-bicycle', color: '#4f46e5' },
          { label: 'Faol', value: activeCouriers, icon: 'bi-check-circle', color: '#10b981' },
          { label: "Yo'ldagi order", value: routeOrders, icon: 'bi-truck', color: '#f59e0b' },
          { label: 'Kuryer balansi', value: `${fmt(totalBalance)} so'm`, icon: 'bi-wallet2', color: '#7c3aed' },
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
        {couriers.map((courier) => (
          <div className="col-xl-4 col-md-6" key={courier.id}>
            <div className="card-panel h-100 d-flex flex-column">
              <div className="d-flex justify-content-between align-items-start mb-3">
                <div className="d-flex align-items-center gap-3" style={{ minWidth: 0 }}>
                  <div className="resource-avatar">
                    {courier.photo ? <img src={courier.photo} alt={courier.name} /> : courier.name.split(' ').map((part) => part[0]).join('').slice(0, 2)}
                  </div>
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{courier.name}</div>
                    <div className="text-muted small text-truncate">{courier.phone || courier.region || 'Kuryer'}</div>
                  </div>
                </div>
                <span className={`chip ${statusChip(courier.status || courier.verificationStatus)}`}>{courier.status || courier.verificationStatus || '—'}</span>
              </div>

              <div className="row g-2 text-center mb-3">
                <div className="col-4"><div className="fw-bold text-primary">{courier.orders || 0}</div><small className="text-muted">Buyurtma</small></div>
                <div className="col-4"><div className="fw-bold text-success">{fmt(courier.balance || 0)}</div><small className="text-muted">Balans</small></div>
                <div className="col-4"><div className="fw-bold text-warning">{courier.transport || '—'}</div><small className="text-muted">Transport</small></div>
              </div>

              <div className="p-2 rounded mb-3 small bg-light">
                <div className="d-flex justify-content-between"><span>Hudud</span><strong>{courier.region || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Avtomobil</span><strong>{courier.vehicle || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Raqam</span><strong>{courier.plate || '—'}</strong></div>
              </div>

              <div className="d-flex gap-2 mt-auto">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelectedCourier(courier)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-primary-gradient flex-fill" onClick={() => setSelectedCourier(courier)}><i className="bi bi-pencil"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Kuryer buyurtmalari</div>
            <small className="text-muted">{courierOrders.length} ta yetkazma</small>
          </div>
          <a className="btn btn-sm btn-outline-secondary" href="/boshqaruv/courier-orders">To'liq ro'yxat</a>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Order</th><th>Kuryer</th><th>Mijoz</th><th>Summa</th><th>Kuryer ulushi</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {courierOrders.map((order) => (
                <tr key={order.id}>
                  <td className="fw-semibold text-primary">#{order.id}</td>
                  <td>{order.orderId || '—'}</td>
                  <td><div className="fw-semibold">{order.courier}</div><small className="text-muted">{order.courierPhone}</small></td>
                  <td><div>{order.customer}</div><small className="text-muted">{order.customerPhone}</small></td>
                  <td className="fw-semibold">{fmt(order.amount)} so'm</td>
                  <td>{fmt((order.courierPrice || 0) + (order.bonus || 0))} so'm</td>
                  <td className="text-muted">{order.date || '—'}</td>
                  <td><span className={`chip ${statusChip(order.status)}`}>{order.status || '—'}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button>
                    <button className="btn btn-sm btn-light" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selectedCourier} onHide={() => setSelectedCourier(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selectedCourier?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Telefon</small><div className="fw-semibold">{selectedCourier?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${statusChip(selectedCourier?.status)}`}>{selectedCourier?.status || '—'}</span></div></div>
            <div className="col-6"><small className="text-muted">Tasdiq</small><div>{selectedCourier?.verificationStatus || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Balans</small><div className="fw-bold text-success">{fmt(selectedCourier?.balance || 0)} so'm</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selectedCourier?.approveUrl ? <Button variant="outline-secondary" onClick={() => patch(selectedCourier.approveUrl)}>Tasdiqlash</Button> : null}
          {selectedCourier?.rejectUrl ? <Button variant="outline-secondary" onClick={() => patch(selectedCourier.rejectUrl)}>Rad etish</Button> : null}
          {selectedCourier?.unblockUrl ? <Button variant="outline-secondary" onClick={() => patch(selectedCourier.unblockUrl)}>Blokdan chiqarish</Button> : null}
          <Button variant="light" onClick={() => setSelectedCourier(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>

      <Modal show={!!selectedOrder} onHide={() => setSelectedOrder(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Kuryer order #{selectedOrder?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Kuryer</small><div className="fw-semibold">{selectedOrder?.courier}</div></div>
            <div className="col-6"><small className="text-muted">Mijoz</small><div>{selectedOrder?.customer}</div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div>{fmt(selectedOrder?.amount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Bonus</small><div>{fmt(selectedOrder?.bonus || 0)} so'm</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {orderStatuses.map((status) => <Button key={status.code} variant="outline-secondary" onClick={() => selectedOrder && patch(selectedOrder.statusUrl, { status: status.code })}>{status.label}</Button>)}
          <Button variant="light" onClick={() => setSelectedOrder(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
