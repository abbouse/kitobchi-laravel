import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Seller {
  id: number;
  name: string;
  legalName?: string;
  phone?: string;
  region?: string;
  status?: string;
  verified?: boolean;
  hidden?: boolean;
  premium?: boolean;
  rating?: number;
  balance?: number;
  commissionRate?: number;
  products?: number;
  orders?: number;
  contract?: { number?: string; signed?: boolean; status?: string; expiresAt?: string; daysRemaining?: number };
  createUrl?: string;
  showUrl?: string;
  editUrl?: string;
  approveUrl?: string;
  rejectUrl?: string;
  unblockUrl?: string;
  warnUrl?: string;
}

interface SellerOrder {
  id: number;
  seller: string;
  sellerPhone?: string;
  customer: string;
  customerPhone?: string;
  courier?: string;
  amount: number;
  deliveryType?: string;
  status: string;
  acceptedAt?: string;
  date?: string;
  showUrl?: string;
  statusUrl?: string;
}

const statusChip = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (['active', 'approved', 'c', 'completed', 'delivered', 'customer_received'].includes(value)) return 'chip-success';
  if (['pending', 'p', 'a', 'new'].includes(value)) return 'chip-warning';
  if (['blocked', 'rejected', 'cancelled', 'f'].includes(value)) return 'chip-danger';
  return 'chip-gray';
};

const sellerStatuses = [
  { code: 'approved', label: 'Tasdiqlash' },
  { code: 'rejected', label: 'Rad etish' },
  { code: 'unblocked', label: 'Blokdan chiqarish' },
];

const orderStatuses = [
  { code: 'B', label: "Yig'ilmoqda" },
  { code: 'D', label: 'Yetkazishda' },
  { code: 'C', label: 'Yakunlandi' },
  { code: 'F', label: 'Bekor' },
];

export default function SellerOrders() {
  const { sellers = [], sellerOrders = [] } = usePage<{ sellers?: Seller[]; sellerOrders?: SellerOrder[] }>().props;
  const [selectedSeller, setSelectedSeller] = useState<Seller | null>(null);
  const [selectedOrder, setSelectedOrder] = useState<SellerOrder | null>(null);

  const totalBalance = useMemo(() => sellers.reduce((sum, seller) => sum + (seller.balance || 0), 0), [sellers]);
  const activeSellers = sellers.filter((seller) => statusChip(seller.status) === 'chip-success').length;
  const premiumSellers = sellers.filter((seller) => seller.premium).length;
  const pendingOrders = sellerOrders.filter((order) => statusChip(order.status) === 'chip-warning').length;
  const createUrl = sellers[0]?.createUrl || '/a122/sellers';

  const runSellerAction = (url?: string, message?: string) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, {}, { preserveScroll: true });
  };

  const updateOrderStatus = (order: SellerOrder, status: string) => {
    if (!order.statusUrl) return;
    router.patch(order.statusUrl, { status }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Sellerlar</h1>
          <p className="page-subtitle">Marketplace sellerlari, shartnomalar va seller buyurtmalari</p>
        </div>
        <a className="btn btn-primary-gradient" href={createUrl}>
          <i className="bi bi-plus-lg me-1"></i>Seller boshqaruvi
        </a>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami seller', value: sellers.length, icon: 'bi-shop', color: '#4f46e5' },
          { label: 'Faol seller', value: activeSellers, icon: 'bi-patch-check', color: '#10b981' },
          { label: 'Premium', value: premiumSellers, icon: 'bi-gem', color: '#7c3aed' },
          { label: "To'lanadigan balans", value: `${fmt(totalBalance)} so'm`, icon: 'bi-wallet2', color: '#f59e0b' },
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
        {sellers.map((seller) => (
          <div className="col-xl-4 col-md-6" key={seller.id}>
            <div className="card-panel h-100 d-flex flex-column">
              <div className="d-flex justify-content-between align-items-start mb-3">
                <div className="d-flex align-items-center gap-3" style={{ minWidth: 0 }}>
                  <div className="resource-avatar">{seller.name.split(' ').map((part) => part[0]).join('').slice(0, 2)}</div>
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{seller.name}</div>
                    <div className="text-muted small text-truncate">{seller.phone || seller.region || 'Seller'}</div>
                  </div>
                </div>
                <span className={`chip ${statusChip(seller.status)}`}>{seller.status || '—'}</span>
              </div>

              <div className="row g-2 text-center mb-3">
                <div className="col-4"><div className="fw-bold text-primary">{seller.products || 0}</div><small className="text-muted">Mahsulot</small></div>
                <div className="col-4"><div className="fw-bold text-success">{seller.orders || 0}</div><small className="text-muted">Buyurtma</small></div>
                <div className="col-4"><div className="fw-bold text-warning">{seller.rating || 0}</div><small className="text-muted">Reyting</small></div>
              </div>

              <div className="p-2 rounded mb-3 small bg-light">
                <div className="d-flex justify-content-between"><span>Komissiya</span><strong>{seller.commissionRate || 0}%</strong></div>
                <div className="d-flex justify-content-between"><span>Balans</span><strong>{fmt(seller.balance || 0)} so'm</strong></div>
                <div className="d-flex justify-content-between"><span>Shartnoma</span><strong>{seller.contract?.status || '—'}</strong></div>
              </div>

              <div className="d-flex gap-2 mt-auto">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelectedSeller(seller)}><i className="bi bi-eye"></i></button>
                <a className="btn btn-sm btn-primary-gradient flex-fill" href={seller.editUrl || seller.showUrl || '#'}><i className="bi bi-pencil"></i></a>
                <button className="btn btn-sm btn-light" title="Ogohlantirish" onClick={() => runSellerAction(seller.warnUrl, `${seller.name} selleriga ogohlantirish yuborilsinmi?`)}>
                  <i className="bi bi-exclamation-triangle"></i>
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Seller buyurtmalari</div>
            <small className="text-muted">{sellerOrders.length} ta yozuv · {pendingOrders} ta kutilmoqda</small>
          </div>
          <a className="btn btn-sm btn-outline-secondary" href="/a122/seller-orders">To'liq ro'yxat</a>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Seller</th><th>Mijoz</th><th>Kuryer</th><th>Summa</th><th>Yetkazish</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {sellerOrders.map((order) => (
                <tr key={order.id}>
                  <td className="fw-semibold text-primary">#{order.id}</td>
                  <td><div className="fw-semibold">{order.seller}</div><small className="text-muted">{order.sellerPhone}</small></td>
                  <td><div>{order.customer}</div><small className="text-muted">{order.customerPhone}</small></td>
                  <td>{order.courier || '—'}</td>
                  <td className="fw-semibold">{fmt(order.amount)} so'm</td>
                  <td><span className="chip chip-gray">{order.deliveryType || '—'}</span></td>
                  <td className="text-muted">{order.date || order.acceptedAt || '—'}</td>
                  <td><span className={`chip ${statusChip(order.status)}`}>{order.status || '—'}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button>
                    {order.showUrl ? <a className="btn btn-sm btn-light" href={order.showUrl}><i className="bi bi-box-arrow-up-right"></i></a> : null}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selectedSeller} onHide={() => setSelectedSeller(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selectedSeller?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Telefon</small><div className="fw-semibold">{selectedSeller?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Region</small><div>{selectedSeller?.region || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${statusChip(selectedSeller?.status)}`}>{selectedSeller?.status || '—'}</span></div></div>
            <div className="col-6"><small className="text-muted">Shartnoma raqami</small><div>{selectedSeller?.contract?.number || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Shartnoma muddati</small><div>{selectedSeller?.contract?.expiresAt || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Balans</small><div className="fw-bold text-success">{fmt(selectedSeller?.balance || 0)} so'm</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {sellerStatuses.map((status) => {
            const url = status.code === 'approved' ? selectedSeller?.approveUrl : status.code === 'rejected' ? selectedSeller?.rejectUrl : selectedSeller?.unblockUrl;
            return url ? <Button key={status.code} variant="outline-secondary" onClick={() => runSellerAction(url, `${status.label} amalini tasdiqlaysizmi?`)}>{status.label}</Button> : null;
          })}
          {selectedSeller?.showUrl ? <a className="btn btn-primary-gradient" href={selectedSeller.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setSelectedSeller(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>

      <Modal show={!!selectedOrder} onHide={() => setSelectedOrder(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Seller order #{selectedOrder?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Seller</small><div className="fw-semibold">{selectedOrder?.seller}</div></div>
            <div className="col-6"><small className="text-muted">Mijoz</small><div>{selectedOrder?.customer}</div></div>
            <div className="col-6"><small className="text-muted">Kuryer</small><div>{selectedOrder?.courier || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div className="fw-bold">{fmt(selectedOrder?.amount || 0)} so'm</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {orderStatuses.map((status) => <Button key={status.code} variant="outline-secondary" onClick={() => selectedOrder && updateOrderStatus(selectedOrder, status.code)}>{status.label}</Button>)}
          {selectedOrder?.showUrl ? <a className="btn btn-primary-gradient" href={selectedOrder.showUrl}>Ko'rish</a> : null}
          <Button variant="light" onClick={() => setSelectedOrder(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
