import { useState } from 'react';
import type { ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

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
  resendSourceOrderId?: number | null;
  resendReplacementOrderId?: number | null;
  resendAvailableAt?: string | null;
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
    lastModeSwitch?: Record<string, unknown> | null;
    lastHubReroute?: Record<string, unknown> | null;
    timeline?: Array<{ code: string; title: string; at: string }>;
  } | null;
  paymentTransaction?: { id: number; provider?: string; providerCardId?: string; amount?: number; status?: string; date?: string } | null;
  settlementOverview?: { gross?: number; commission?: number; net?: number; reversedNet?: number; transactions?: number };
  courierOrder?: { id: number; courier?: string; phone?: string; region?: string; status?: string; amount?: number; courierPrice?: number } | null;
  activeHubs?: Array<{ id: number; label: string }>;
  fulfillmentModes?: Array<{ value: string; label: string }>;
  sellerOrders?: SellerOrder[];
  showUrl?: string;
  labelUrl?: string;
  receiptUrl?: string;
  statusUrl?: string;
  cancelUrl?: string;
  switchModeUrl?: string;
  rerouteHubUrl?: string;
  postalReturnUrl?: string;
  dataUrl?: string;
  canRefundPayment?: boolean;
  refundConfirmationPhrase?: string | null;
  refundCancelUrl?: string;
}

const statusChip = (status: string) => {
  const normalized = String(status || '').toLowerCase();
  if (['delivered', 'customer_received', 'c', 'completed'].includes(normalized)) return 'chip-success';
  if (['in_delivery', 'shipping', 'b'].includes(normalized)) return 'chip-info';
  if (['packing', 'processing', 'p'].includes(normalized)) return 'chip-warning';
  if (['cancelled', 'returned', 'f', 'r'].includes(normalized)) return 'chip-danger';
  return 'chip-gray';
};

const Detail = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-md-6">
    <div className="text-muted small">{label}</div>
    <div className="fw-semibold">{value || '—'}</div>
  </div>
);

const statusOptions = [
  { code: 'pending', label: 'Kutilmoqda' },
  { code: 'packing', label: 'Qadoqlanmoqda' },
  { code: 'in_delivery', label: "Yo'lda" },
  { code: 'delivered', label: 'Yetib bordi' },
  { code: 'customer_received', label: 'Mijoz qabul qildi' },
  { code: 'cancelled', label: 'Bekor qilindi' },
];

const normalizeStatus = (status?: string) => {
  const value = String(status || 'pending').toLowerCase();
  return ({
    a: 'pending',
    p: 'packing',
    b: 'in_delivery',
    c: 'delivered',
    d: 'customer_received',
    f: 'cancelled',
    r: 'returned',
  } as Record<string, string>)[value] || value;
};

const statusLabel = (status?: string) => {
  const normalized = normalizeStatus(status);
  if (normalized === 'returned') return 'Qaytgan';
  return statusOptions.find((option) => option.code === normalized)?.label || status || '—';
};

const canCancelOrder = (status?: string) => ![
  'delivered',
  'customer_received',
  'cancelled',
  'returned',
].includes(normalizeStatus(status));

const normalizeOrder = (order: Ord): Ord => ({ ...order, status: normalizeStatus(order.status) });

interface PaginationMeta {
  page: number;
  totalPages: number;
  from: number;
  to: number;
  total: number;
}

export default function Orders() {
  const { orders = [], orderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, orderCounts = {}, orderFilters = {} } = usePage<{
    orders?: Ord[];
    orderPagination?: PaginationMeta;
    orderCounts?: Record<string, number>;
    orderFilters?: { tab?: string; search?: string };
  }>().props;
  const [activeTab, setActiveTab] = useState(orderFilters.tab || 'pending');
  const [search, setSearch] = useState(orderFilters.search || '');
  const [showView, setShowView] = useState(false);
  const [selectedOrd, setSelectedOrd] = useState<Ord | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const loadOrders = (page = 1, tab = activeTab, term = search) => {
    router.get('/boshqaruv/orders', { orders_page: page, orders_tab: tab, orders_search: term }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const loadOrderDetail = async (order: Ord) => {
    if (!order.dataUrl) return;
    setDetailLoading(true);
    try {
      const response = await fetch(order.dataUrl, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Buyurtma tafsilotlarini olib bo‘lmadi.');
      setSelectedOrd(normalizeOrder(await response.json()));
    } finally {
      setDetailLoading(false);
    }
  };

  const handleOpenView = async (order: Ord) => {
    setSelectedOrd(normalizeOrder(order));
    setShowView(true);
    await loadOrderDetail(order);
  };

  const handleUpdateStatus = (status: string) => {
    if (!selectedOrd?.statusUrl) return;
    const nextStatus = normalizeStatus(status);
    if (nextStatus === 'cancelled' && !confirm('Buyurtma bekor qilinsinmi?')) return;
    const currentOrder = selectedOrd;
    setSelectedOrd({ ...currentOrder, status: nextStatus });
    router.patch(currentOrder.statusUrl, { status: nextStatus }, {
      preserveScroll: true,
      onSuccess: () => loadOrderDetail(currentOrder),
      onError: () => setSelectedOrd(currentOrder),
    });
  };

  const postPrompt = (url: string | undefined, data: Record<string, string | number>, method: 'post' | 'patch' = 'post') => {
    if (!url) return;
    const currentOrder = selectedOrd;
    router[method](url, data, {
      preserveScroll: true,
      onSuccess: () => currentOrder && loadOrderDetail(currentOrder),
    });
  };

  const switchMode = () => {
    if (!selectedOrd?.switchModeUrl) return;
    const target_mode = prompt('Yangi fulfillment mode', selectedOrd.fulfillment?.mode || selectedOrd.fulfillmentModes?.[0]?.value || '');
    if (!target_mode) return;
    const hub_id = prompt("Target hub ID (auto tanlash uchun bo'sh qoldiring)", '') || '';
    const override_note = prompt('Mode almashtirish izohi', '') || '';
    postPrompt(selectedOrd.switchModeUrl, { target_mode, hub_id, override_note });
  };

  const rerouteHub = () => {
    if (!selectedOrd?.rerouteHubUrl) return;
    const hub_id = prompt('Yangi hub ID', String(selectedOrd.activeHubs?.[0]?.id || ''));
    if (!hub_id) return;
    const reroute_note = prompt('Reroute izohi', '') || '';
    postPrompt(selectedOrd.rerouteHubUrl, { hub_id, reroute_note });
  };

  const markPostalReturned = () => {
    if (!selectedOrd?.postalReturnUrl) return;
    const postal_return_fee = prompt('Pochta qaytimi jarimasi', String(selectedOrd.postalReturnFee || 0));
    if (postal_return_fee === null) return;
    const postal_return_note = prompt('Qaytim izohi', selectedOrd.postalReturnNote || '') || '';
    postPrompt(selectedOrd.postalReturnUrl, { postal_return_fee, postal_return_note }, 'patch');
  };

  const refundAndCancel = () => {
    if (!selectedOrd?.refundCancelUrl || !selectedOrd.refundConfirmationPhrase) return;
    const confirmation_phrase = prompt(`Pulni qaytarish uchun tasdiqlash matnini kiriting: ${selectedOrd.refundConfirmationPhrase}`, '');
    if (!confirmation_phrase) return;
    const reason = prompt('Refund sababi', '') || '';
    postPrompt(selectedOrd.refundCancelUrl, { confirmation_phrase, reason });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Buyurtmalar</h1>
          <p className="page-subtitle">Mijoz, mahsulot, to'lov va fulfillment nazorati</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami buyurtmalar', val: orderCounts.all || 0, icon: 'bi-receipt', color: '#4f46e5' },
          { label: 'Yetkazilgan', val: orderCounts.paid || 0, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Jarayonda', val: (orderCounts.pending || 0) + (orderCounts.shipped || 0), icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Bekor qilingan', val: orderCounts.cancelled || 0, icon: 'bi-x-circle', color: '#ef4444' },
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
        <div className="d-flex gap-2 mb-3 flex-wrap align-items-center">
          {[
            ['all', 'Barchasi'],
            ['pending', 'Kutilmoqda'],
            ['shipped', "Yo'lda"],
            ['paid', 'Yetkazilgan'],
            ['cancelled', 'Bekor qilingan'],
          ].map(([status, label]) => (
            <button
              key={status}
              className={`btn btn-sm ${activeTab === status ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
              onClick={() => { setActiveTab(status); loadOrders(1, status); }}
            >
              {label} <span className="ms-1 opacity-75">{orderCounts[status] || 0}</span>
            </button>
          ))}
          <form className="ms-auto d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadOrders(1); }}>
            <input className="form-control form-control-sm" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ID, mijoz yoki telefon" />
            <button className="btn btn-sm btn-outline-secondary" title="Qidirish"><i className="bi bi-search"></i></button>
          </form>
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
              {orders.map((order) => (
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
                  <td><span className={`chip ${statusChip(order.status)}`}>{statusLabel(order.status)}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => handleOpenView(order)} title="Ko'rish / Boshqarish">
                      <i className="bi bi-eye"></i>
                    </button>
                    <a className="btn btn-sm btn-light" href={order.receiptUrl || '#'} title="Chekni chop etish" target="_blank">
                      <i className="bi bi-printer"></i>
                    </a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...orderPagination} onPageChange={(page) => loadOrders(page)} />
      </div>

      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Buyurtma: {selectedOrd?.id}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {detailLoading ? <div className="py-5 text-center text-muted">Buyurtma tafsilotlari yuklanmoqda...</div> : selectedOrd ? (
            <div className="row g-4">
              <div className="col-lg-4">
                <div className="detail-panel">
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <div className="text-muted small">Mijoz</div>
                      <div className="fw-bold fs-5">
                        {selectedOrd.customer}
                      </div>
                      <div className="text-muted small">{selectedOrd.user?.phone || selectedOrd.user?.email || 'Kontakt yoq'}</div>
                    </div>
                    <span className={`chip ${statusChip(selectedOrd.status)}`}>{statusLabel(selectedOrd.status)}</span>
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
                        <Detail label="Qaytim holati" value={selectedOrd.postalReturnStatus} />
                        <Detail label="Qayta jo'natish to'lovi" value={`${fmt(selectedOrd.postalReturnFee || 0)} so'm`} />
                        <Detail label="Manba order" value={selectedOrd.resendSourceOrderId ? `#${selectedOrd.resendSourceOrderId}` : '—'} />
                        <Detail label="Replacement order" value={selectedOrd.resendReplacementOrderId ? `#${selectedOrd.resendReplacementOrderId}` : '—'} />
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
                          <Detail label="Oxirgi mode almashuvi" value={formatAudit(selectedOrd.fulfillment.lastModeSwitch)} />
                          <Detail label="Oxirgi hub reroute" value={formatAudit(selectedOrd.fulfillment.lastHubReroute)} />
                        </div>
                      ) : (
                        <div className="text-muted small">Fulfillment yozuvi hali yoq.</div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Operatsion timeline</h6>
                  <OrderTimeline rows={selectedOrd.fulfillment?.timeline || []} />
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Seller orderlar</h6>
                  <SellerOrdersTable rows={selectedOrd.sellerOrders || []} />
                </div>

                <div className="row g-3 mt-1">
                  <div className="col-lg-6">
                    <div className="detail-panel h-100">
                      <h6 className="fw-bold mb-3">Seller hisob-kitobi</h6>
                      <div className="row g-3">
                        <Detail label="Gross" value={`${fmt(selectedOrd.settlementOverview?.gross || 0)} so'm`} />
                        <Detail label="Komissiya" value={`${fmt(selectedOrd.settlementOverview?.commission || 0)} so'm`} />
                        <Detail label="Seller net" value={`${fmt(selectedOrd.settlementOverview?.net || 0)} so'm`} />
                        <Detail label="Qaytarilgan net" value={`${fmt(selectedOrd.settlementOverview?.reversedNet || 0)} so'm`} />
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-6">
                    <div className="detail-panel h-100">
                      <h6 className="fw-bold mb-3">To'lov va kuryer</h6>
                      <div className="row g-3">
                        <Detail label="Payment provider" value={selectedOrd.paymentTransaction?.provider} />
                        <Detail label="Payment status" value={selectedOrd.paymentTransaction?.status} />
                        <Detail label="Kuryer" value={selectedOrd.courierOrder?.courier || selectedOrd.courierName} />
                        <Detail label="Kuryer telefoni" value={selectedOrd.courierOrder?.phone} />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-2">Fulfillment boshqaruvi</h6>
                  <div className="d-flex flex-wrap gap-2">
                    <button className="btn btn-sm btn-outline-secondary" onClick={switchMode}>Mode almashtirish</button>
                    <button className="btn btn-sm btn-outline-secondary" onClick={rerouteHub}>Hub reroute</button>
                    <button className="btn btn-sm btn-outline-secondary" onClick={markPostalReturned}>Pochta qaytimi</button>
                    {selectedOrd.cancelUrl && canCancelOrder(selectedOrd.status) ? <button className="btn btn-sm btn-outline-danger" onClick={() => confirm('Buyurtma bekor qilinsinmi?') && postPrompt(selectedOrd.cancelUrl, {})}>Bekor qilish</button> : null}
                    {selectedOrd.canRefundPayment ? <button className="btn btn-sm btn-danger" onClick={refundAndCancel}>Refund + bekor qilish</button> : null}
                  </div>
                  {selectedOrd.activeHubs?.length ? <div className="text-muted small mt-3">Faol hub ID: {selectedOrd.activeHubs.map((hub) => `${hub.id}: ${hub.label}`).join(' · ')}</div> : null}
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-2">Statusni o'zgartirish</h6>
                  <div className="d-flex flex-wrap gap-2">
                    {statusOptions.map((status) => (
                      <button
                        key={status.code}
                        className={`btn btn-sm ${normalizeStatus(selectedOrd.status) === status.code ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
                        onClick={() => handleUpdateStatus(status.code)}
                        disabled={!selectedOrd.statusUrl || normalizeStatus(selectedOrd.status) === status.code}
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
          {selectedOrd?.labelUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.labelUrl} target="_blank">Label</a> : null}
          {selectedOrd?.receiptUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.receiptUrl} target="_blank">Chek</a> : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

function formatAudit(value?: Record<string, unknown> | null) {
  if (!value) return '—';
  return Object.entries(value)
    .filter(([, item]) => item !== null && item !== undefined && item !== '')
    .map(([key, item]) => `${key}: ${String(item)}`)
    .join(' · ');
}

function OrderTimeline({ rows }: { rows: Array<{ code: string; title: string; at: string }> }) {
  if (!rows.length) {
    return <div className="text-muted small">Fulfillment bosqichlari hali qayd etilmagan.</div>;
  }

  return (
    <div className="d-flex flex-column gap-2">
      {rows.map((row) => (
        <div className="d-flex gap-3 align-items-start" key={`${row.code}-${row.at}`}>
          <i className="bi bi-check-circle-fill text-success mt-1"></i>
          <div>
            <div className="fw-semibold">{row.title}</div>
            <div className="text-muted small">{row.at}</div>
          </div>
        </div>
      ))}
    </div>
  );
}

function AddressBlock({ address }: { address: Record<string, unknown> }) {
  const rows = Object.entries(address).filter(([key, value]) => key !== 'mapLinks' && value !== null && value !== undefined && value !== '');
  const mapLinks = (address.mapLinks || {}) as Record<string, string>;

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
      <div>
        <span>Xarita</span>
        <MapButtons mapLinks={mapLinks} />
      </div>
    </div>
  );
}

function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <strong>—</strong>;

  return (
    <strong className="d-flex gap-2 flex-wrap justify-content-end">
      {mapLinks.google ? <a className="btn btn-sm btn-light" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="bi bi-geo-alt me-1"></i>Google Map</a> : null}
      {mapLinks.yandex ? <a className="btn btn-sm btn-light" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="bi bi-map me-1"></i>Yandex Map</a> : null}
    </strong>
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
              <td>#{row.id}</td>
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
