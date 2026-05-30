import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import { orders } from '../data';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface OrderItem {
  id?: number;
  type: string;
  typeLabel: string;
  name: string;
  quantity: number;
  price: number;
  total: number;
  seller?: string | null;
  image?: string | null;
}

interface SellerOrder {
  id: number;
  seller?: string | null;
  courier?: string | null;
  courierPhone?: string | null;
  amount: number;
  deliveryType?: string;
  status: string;
  acceptedAt?: string | null;
  url?: string;
}

interface Ord {
  id: string;
  rawId?: number;
  customer: string;
  user?: { name?: string; phone?: string; email?: string; url?: string } | null;
  items: number;
  itemsList?: OrderItem[];
  total: number;
  subtotal?: number;
  deliveryPrice?: number;
  discountAmount?: number;
  cashbackAmount?: number;
  giftCertAmount?: number;
  packagingPrice?: number;
  status: string;
  legacyStatus?: string;
  date: string;
  completedAt?: string | null;
  payment: string;
  paymentStatus?: string;
  deliveryType?: string;
  orderKind?: string;
  postalReturnStatus?: string;
  postalReturnFee?: number;
  postalReturnNote?: string | null;
  address?: Record<string, unknown>;
  isInstore?: boolean;
  withPackaging?: boolean;
  isGiftToOther?: boolean;
  recipient?: { name?: string | null; phone?: string | null; region?: string | null; address?: string | null };
  buyerWish?: string | null;
  promocode?: string | null;
  courierName?: string | null;
  fulfillment?: {
    mode?: string | null;
    status?: string | null;
    hub?: string | null;
    firstMile?: string | null;
    lastMile?: string | null;
    isCod?: boolean;
    cashCollectAmount?: number;
    tracking?: string | null;
    labelCode?: string | null;
  } | null;
  sellerOrders?: SellerOrder[];
  showUrl?: string;
  labelUrl?: string;
  receiptUrl?: string;
  statusUrl?: string;
  cancelUrl?: string;
  switchModeUrl?: string;
  rerouteHubUrl?: string;
  postalReturnUrl?: string;
}

const statusChip = (status: string) => {
  const normalized = String(status || '').toLowerCase();
  if (['delivered', 'customer_received', 'c', 'completed'].includes(normalized)) return 'chip-success';
  if (['in_delivery', 'shipping', 'd'].includes(normalized)) return 'chip-info';
  if (['packing', 'processing', 'b', 'p'].includes(normalized)) return 'chip-warning';
  if (['cancelled', 'returned', 'f'].includes(normalized)) return 'chip-danger';
  return 'chip-gray';
};

const Detail = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-md-6">
    <div className="text-muted small">{label}</div>
    <div className="fw-semibold">{value || '—'}</div>
  </div>
);

const statusOptions = [
  { code: 'A', label: 'Yangi' },
  { code: 'P', label: "To'lov kutilmoqda" },
  { code: 'B', label: "Yig'ilmoqda" },
  { code: 'D', label: 'Yetkazishda' },
  { code: 'C', label: 'Yetkazildi' },
  { code: 'F', label: 'Bekor qilindi' },
];

export default function Orders() {
  const { orders: serverOrders = [] } = usePage<{ orders?: Ord[] }>().props;
  const list = useMemo<Ord[]>(() => (serverOrders.length ? serverOrders : orders as Ord[]), [serverOrders]);
  const [activeTab, setActiveTab] = useState('Barchasi');
  const [showView, setShowView] = useState(false);
  const [selectedOrd, setSelectedOrd] = useState<Ord | null>(null);

  const handleOpenView = (order: Ord) => {
    setSelectedOrd(order);
    setShowView(true);
  };

  const handleUpdateStatus = (status: string) => {
    if (!selectedOrd?.statusUrl) return;
    router.patch(selectedOrd.statusUrl, { status }, { preserveScroll: true });
  };

  const filtered = list.filter((order) => activeTab === 'Barchasi' || String(order.status).toLowerCase().includes(activeTab.toLowerCase()));
  const delivered = list.filter((order) => statusChip(order.status) === 'chip-success').length;
  const processing = list.filter((order) => statusChip(order.status) === 'chip-warning').length;
  const cancelled = list.filter((order) => statusChip(order.status) === 'chip-danger').length;

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Buyurtmalar</h1>
          <p className="page-subtitle">Mijoz, mahsulot, to'lov va fulfillment nazorati</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/a122/orders">
            <i className="bi bi-funnel me-1"></i>Filtr
          </a>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami buyurtmalar', val: list.length, icon: 'bi-receipt', color: '#4f46e5' },
          { label: 'Yetkazilgan', val: delivered, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Jarayonda', val: processing, icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Bekor qilingan', val: cancelled, icon: 'bi-x-circle', color: '#ef4444' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div>
                  <div className="stat-value">{item.val}</div>
                  <div className="stat-label">{item.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          {['Barchasi', 'pending', 'packing', 'in_delivery', 'delivered', 'cancelled'].map((status) => (
            <button
              key={status}
              className={`btn btn-sm ${activeTab === status ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
              onClick={() => setActiveTab(status)}
            >
              {status}
            </button>
          ))}
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th>Buyurtma ID</th>
                <th>Mijoz</th>
                <th>Mahsulotlar</th>
                <th>Summa</th>
                <th>To'lov</th>
                <th>Yetkazish</th>
                <th>Sana</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((order) => (
                <tr key={order.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>{order.id}</td>
                  <td>
                    <div className="fw-semibold">{order.customer}</div>
                    <div className="text-muted small">{order.user?.phone || ''}</div>
                  </td>
                  <td>{order.items} dona</td>
                  <td className="fw-semibold">{fmt(order.total)} so'm</td>
                  <td><span className="chip chip-gray">{order.paymentStatus || order.payment}</span></td>
                  <td><span className="chip chip-gray">{order.deliveryType || '—'}</span></td>
                  <td className="text-muted">{order.date}</td>
                  <td><span className={`chip ${statusChip(order.status)}`}>{order.status}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenView(order)} title="Ko'rish / Boshqarish">
                      <i className="bi bi-eye"></i>
                    </button>
                    <a className="btn btn-sm btn-light" href={order.receiptUrl || '#'} title="Chekni chop etish">
                      <i className="bi bi-printer"></i>
                    </a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Buyurtma: {selectedOrd?.id}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedOrd ? (
            <div className="row g-4">
              <div className="col-lg-4">
                <div className="detail-panel">
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <div className="text-muted small">Mijoz</div>
                      <div className="fw-bold fs-5">
                        {selectedOrd.user?.url ? <a href={selectedOrd.user.url}>{selectedOrd.customer}</a> : selectedOrd.customer}
                      </div>
                      <div className="text-muted small">{selectedOrd.user?.phone || selectedOrd.user?.email || 'Kontakt yoq'}</div>
                    </div>
                    <span className={`chip ${statusChip(selectedOrd.status)}`}>{selectedOrd.status}</span>
                  </div>
                  <div className="row g-3">
                    <Detail label="Sana" value={selectedOrd.date} />
                    <Detail label="Yakunlangan" value={selectedOrd.completedAt} />
                    <Detail label="To'lov statusi" value={selectedOrd.paymentStatus || selectedOrd.payment} />
                    <Detail label="Yetkazish turi" value={selectedOrd.deliveryType} />
                    <Detail label="Order turi" value={selectedOrd.orderKind} />
                    <Detail label="Pochta qaytimi" value={selectedOrd.postalReturnStatus} />
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Manzil va sovg'a</h6>
                  <AddressBlock address={selectedOrd.address || {}} />
                  <div className="row g-3 mt-1">
                    <Detail label="Instore" value={selectedOrd.isInstore ? 'Ha' : "Yo'q"} />
                    <Detail label="Qadoqlash" value={selectedOrd.withPackaging ? `${fmt(selectedOrd.packagingPrice || 0)} so'm` : "Yo'q"} />
                    <Detail label="Boshqasiga sovg'a" value={selectedOrd.isGiftToOther ? 'Ha' : "Yo'q"} />
                    <Detail label="Oluvchi" value={[selectedOrd.recipient?.name, selectedOrd.recipient?.phone].filter(Boolean).join(' / ')} />
                    <Detail label="Oluvchi manzili" value={[selectedOrd.recipient?.region, selectedOrd.recipient?.address].filter(Boolean).join(', ')} />
                    <Detail label="Mijoz izohi" value={selectedOrd.buyerWish} />
                  </div>
                </div>
              </div>

              <div className="col-lg-8">
                <div className="row g-3 mb-3">
                  {[
                    { label: 'Jami summa', value: `${fmt(selectedOrd.total)} so'm`, icon: 'bi-cash-stack' },
                    { label: 'Mahsulot', value: `${selectedOrd.items} dona`, icon: 'bi-box-seam' },
                    { label: 'Yetkazish', value: `${fmt(selectedOrd.deliveryPrice || 0)} so'm`, icon: 'bi-truck' },
                    { label: 'Chegirma', value: `${fmt(selectedOrd.discountAmount || 0)} so'm`, icon: 'bi-percent' },
                  ].map((item) => (
                    <div className="col-md-3 col-6" key={item.label}>
                      <div className="mini-stat">
                        <i className={`bi ${item.icon}`}></i>
                        <span>{item.label}</span>
                        <strong>{item.value}</strong>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="detail-panel">
                  <h6 className="fw-bold mb-3">Mahsulotlar</h6>
                  <div className="table-responsive">
                    <table className="data-table compact-table">
                      <thead>
                        <tr>
                          <th>Mahsulot</th>
                          <th>Seller</th>
                          <th>Turi</th>
                          <th>Soni</th>
                          <th>Narx</th>
                          <th>Jami</th>
                        </tr>
                      </thead>
                      <tbody>
                        {(selectedOrd.itemsList || []).map((item, index) => (
                          <tr key={`${item.type}-${item.id || index}`}>
                            <td>
                              <div className="d-flex align-items-center gap-2">
                                <div className="item-thumb">{item.image ? <img src={item.image} alt={item.name} /> : <i className="bi bi-box"></i>}</div>
                                <span className="fw-semibold">{item.name}</span>
                              </div>
                            </td>
                            <td>{item.seller || '—'}</td>
                            <td><span className="chip chip-gray">{item.typeLabel}</span></td>
                            <td>{item.quantity}</td>
                            <td>{fmt(item.price)} so'm</td>
                            <td className="fw-semibold">{fmt(item.total)} so'm</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="row g-3 mt-1">
                  <div className="col-lg-6">
                    <div className="detail-panel h-100">
                      <h6 className="fw-bold mb-3">Hisob-kitob</h6>
                      <div className="row g-3">
                        <Detail label="Mahsulotlar summasi" value={`${fmt(selectedOrd.subtotal || 0)} so'm`} />
                        <Detail label="Yetkazish" value={`${fmt(selectedOrd.deliveryPrice || 0)} so'm`} />
                        <Detail label="Qadoqlash" value={`${fmt(selectedOrd.packagingPrice || 0)} so'm`} />
                        <Detail label="Chegirma" value={`${fmt(selectedOrd.discountAmount || 0)} so'm`} />
                        <Detail label="Cashback" value={`${fmt(selectedOrd.cashbackAmount || 0)} so'm`} />
                        <Detail label="Sertifikat" value={`${fmt(selectedOrd.giftCertAmount || 0)} so'm`} />
                        <Detail label="Promokod" value={selectedOrd.promocode} />
                        <Detail label="Final summa" value={<span className="text-primary">{fmt(selectedOrd.total)} so'm</span>} />
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-6">
                    <div className="detail-panel h-100">
                      <h6 className="fw-bold mb-3">Fulfillment</h6>
                      {selectedOrd.fulfillment ? (
                        <div className="row g-3">
                          <Detail label="Mode" value={selectedOrd.fulfillment.mode} />
                          <Detail label="Status" value={selectedOrd.fulfillment.status} />
                          <Detail label="Hub" value={selectedOrd.fulfillment.hub} />
                          <Detail label="First / last mile" value={[selectedOrd.fulfillment.firstMile, selectedOrd.fulfillment.lastMile].filter(Boolean).join(' / ')} />
                          <Detail label="COD" value={selectedOrd.fulfillment.isCod ? `${fmt(selectedOrd.fulfillment.cashCollectAmount || 0)} so'm` : "Yo'q"} />
                          <Detail label="Tracking / Label" value={[selectedOrd.fulfillment.tracking, selectedOrd.fulfillment.labelCode].filter(Boolean).join(' / ')} />
                        </div>
                      ) : (
                        <div className="text-muted small">Fulfillment yozuvi hali yoq.</div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Seller orderlar</h6>
                  <SellerOrdersTable rows={selectedOrd.sellerOrders || []} />
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-2">Statusni o'zgartirish</h6>
                  <div className="d-flex flex-wrap gap-2">
                    {statusOptions.map((status) => (
                      <button
                        key={status.code}
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => handleUpdateStatus(status.code)}
                        disabled={!selectedOrd.statusUrl}
                      >
                        {status.label}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          {selectedOrd?.labelUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.labelUrl}>Label</a> : null}
          {selectedOrd?.receiptUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.receiptUrl}>Chek</a> : null}
          {selectedOrd?.showUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.showUrl}>Eski show</a> : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

function AddressBlock({ address }: { address: Record<string, unknown> }) {
  const rows = Object.entries(address).filter(([, value]) => value !== null && value !== undefined && value !== '');

  if (!rows.length) {
    return <div className="text-muted small">Manzil kiritilmagan.</div>;
  }

  return (
    <div className="address-list">
      {rows.map(([key, value]) => (
        <div key={key}>
          <span>{key}</span>
          <strong>{String(value)}</strong>
        </div>
      ))}
    </div>
  );
}

function SellerOrdersTable({ rows }: { rows: SellerOrder[] }) {
  if (!rows.length) {
    return <div className="text-muted small">Seller order topilmadi.</div>;
  }

  return (
    <div className="table-responsive">
      <table className="data-table compact-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Seller</th>
            <th>Kuryer</th>
            <th>Summa</th>
            <th>Status</th>
            <th>Qabul</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>{row.url ? <a href={row.url}>#{row.id}</a> : `#${row.id}`}</td>
              <td>{row.seller || '—'}</td>
              <td>
                <div>{row.courier || '—'}</div>
                <div className="text-muted small">{row.courierPhone || ''}</div>
              </td>
              <td className="fw-semibold">{fmt(row.amount)} so'm</td>
              <td><span className={`chip ${statusChip(row.status)}`}>{row.status || '—'}</span></td>
              <td className="text-muted">{row.acceptedAt || '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
