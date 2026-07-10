import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface OrderItem {
  id?: number;
  sellerOrderItemId?: number;
  sellerOrderId?: number;
  type: string;
  productUrl?: string;
  typeLabel: string;
  name: string;
  quantity: number;
  price: number;
  total: number;
  seller?: string | null;
  image?: string | null;
  variantId?: number | null;
  isCancelled?: boolean;
  cancelledAt?: string | null;
  cancelReasonCode?: string | null;
  cancelNotes?: { uz?: string | null; ru?: string | null; en?: string | null; ja?: string | null };
  refundStatus?: string | null;
  canRefund?: boolean;
  refundUrl?: string;
}

interface SellerOrder {
  id: number;
  sellerId?: number;
  seller?: string | null;
  sellerPhone?: string | null;
  sellerCommissionPercent?: number;
  courier?: string | null;
  courierPhone?: string | null;
  courierRegion?: string | null;
  amount: number;
  deliveryType?: string;
  status: string;
  acceptedAt?: string | null;
  createdAt?: string | null;
  address?: Record<string, unknown>;
  settlement?: SettlementOverview | null;
  url?: string;
  isCancelled?: boolean;
  cancelledAt?: string | null;
  cancelReasonCode?: string | null;
  cancelNotes?: { uz?: string | null; ru?: string | null; en?: string | null; ja?: string | null };
  refundStatus?: string | null;
  canRefund?: boolean;
  refundUrl?: string;
}

interface RefundReasonOption {
  code: string;
  notes: { uz?: string | null; ru?: string | null; en?: string | null; ja?: string | null };
  auto_zero_stock?: boolean;
}

interface RefundLedgerRow {
  id: number;
  type: string;
  status: string;
  cardRefundAmount: number;
  cashbackRestoreAmount: number;
  giftCertRestoreAmount: number;
  deliveryRefundAmount?: number;
  packagingRefundAmount?: number;
  reasonCode?: string | null;
  reasonNoteUz?: string | null;
  processedAt?: string | null;
}

interface SettlementOverview {
  gross?: number;
  commission?: number;
  net?: number;
  reversedNet?: number;
  currentNet?: number;
  saleCount?: number;
  reversalCount?: number;
  transactions?: number;
  status?: string;
  label?: string;
  latestSaleAt?: string | null;
  latestReversalAt?: string | null;
}

interface Ord {
  id: string;
  rawId?: number;
  splitStatus?: string | null;
  customer: string;
  user?: { name?: string; phone?: string; email?: string; url?: string } | null;
  userReputation?: {
    score: number;
    cashOnDeliveryAllowed: boolean;
    codReturnStrikes: number;
    cashOnDeliveryBlockReason?: string | null;
  } | null;
  items: number;
  itemsList?: OrderItem[];
  total: number;
  subtotal?: number;
  deliveryPrice?: number;
  discountAmount?: number;
  cashbackAmount?: number;
  withCashback?: boolean;
  awardedCashbackAmount?: number;
  cashbackReadyAt?: string | null;
  cashbackAwardedAt?: string | null;
  cashbackNotifiedAt?: string | null;
  giftCertAmount?: number;
  giftCertificateId?: number | null;
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
  returnFlow?: {
    isPostal: boolean;
    isReturned: boolean;
    penaltyAmount: number;
    canCreateReplacement: boolean;
    replacementOrderId?: number | null;
    availableAt?: string | null;
    note?: string | null;
  } | null;
  address?: Record<string, unknown>;
  addresses?: Array<Record<string, unknown>>;
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
    notes?: unknown;
    routingVersion?: string | null;
    routingSnapshot?: unknown;
    lastModeSwitch?: Record<string, unknown> | null;
    lastHubReroute?: Record<string, unknown> | null;
    timeline?: Array<{ code: string; title: string; at: string }>;
  } | null;
  paymentTransaction?: { id: number; provider?: string; providerCardId?: string; amount?: number; status?: string; date?: string } | null;
  paymentCard?: { provider?: string | null; providerCardId?: string | null; maskedNumber?: string | null; vendor?: string | null; cardName?: string | null; phone?: string | null };
  fiscalReceipt?: { status: 'disabled' | 'none' | 'pending' | 'registered' | 'refunded'; receiptUrl?: string | null; refundReceiptUrl?: string | null; receiptId?: number | string | null; fiscalSign?: string | null; date?: string | null };
  split?: {
    contractId: number;
    contractNumber: string;
    status: string;
    planName?: string | null;
    months: number;
    monthlyInterestPercent: number;
    principal: number;
    interest: number;
    total: number;
    paid: number;
    remaining: number;
    installmentsPaid: number;
    installmentsCount: number;
    debitDay?: number | null;
    nextDueAt?: string | null;
    nextAmount?: number | null;
    overdueSince?: string | null;
    startsAt?: string | null;
    closedAt?: string | null;
    installments: Array<{
      sequence: number;
      amount: number;
      dueAt?: string | null;
      paidAt?: string | null;
      status: string;
      attempts: number;
      isUpfront: boolean;
    }>;
    manageUrl: string;
  } | null;
  settlementOverview?: SettlementOverview;
  courierOrder?: {
    id: number;
    courier?: string;
    phone?: string;
    region?: string;
    status?: string;
    amount?: number;
    courierPrice?: number;
    courierBonus?: number;
    taskDistanceKm?: number;
    taskFeeAmount?: number;
    taskBaseFeeAmount?: number;
    taskDistanceFeeAmount?: number;
    taskBonusAmount?: number;
    taskLeg?: string;
    settledAmount?: number;
    settledAt?: string | null;
    pickedUpAt?: string | null;
  } | null;
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
  refundReasonCatalog?: {
    item?: RefundReasonOption[];
    order?: RefundReasonOption[];
  };
  refundLedger?: RefundLedgerRow[];
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
  { code: 'in_delivery', label: 'Yetkazilmoqda' },
  { code: 'delivered', label: 'Yetib bordi' },
  { code: 'customer_received', label: 'Mijoz qabul qildi' },
  { code: 'returned', label: 'Qaytgan' },
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

const paymentLabel = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  if (normalized.includes('naqd') || normalized.includes("to'lov")) return value || '—';
  return ({
    cash_pending: "Mijoz olganda naqd to'laydi",
    card_pending: "Karta to'lovi kutilmoqda",
    paid: "To'lov olingan",
    cancelled: "To'lov bekor qilingan",
  } as Record<string, string>)[normalized] || value || '—';
};

const deliveryTypeLabel = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  return ({
    pickup: "Do'kondan olib ketish",
    postal: 'Pochta orqali yuboriladi',
    delivery: 'Kuryer orqali yetkaziladi',
  } as Record<string, string>)[normalized] || value || '—';
};

const postalReturnLabel = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  return ({
    none: "Pochta qaytimi yo'q",
    returned_to_sender: "Pochta qaytarib yuborgan",
    resend_pending_payment: "Qayta yuborish to'lovi kutilmoqda",
    resent: "Qayta yuborilgan",
  } as Record<string, string>)[normalized] || value || '—';
};

const isCashPending = (value?: string) => String(value || '').toLowerCase() === 'cash_pending';

const returnFlowSummary = (order?: Ord | null) => {
  if (!order?.returnFlow?.isPostal) {
    return {
      title: "Oddiy qaytish oqimi",
      text: "Bu buyurtma pochta orqali qayta yuborish oqimiga kirmaydi. Zarur bo'lsa faqat 'Qaytgan' statusi bilan qayd etiladi.",
      tone: 'secondary',
    };
  }

  if (order.returnFlow.replacementOrderId) {
    return {
      title: 'Qayta yuborish buyurtmasi yaratilgan',
      text: `Mijoz jarimani to'lagan va yangi qayta yuborish buyurtmasi ochilgan: #${order.returnFlow.replacementOrderId}.`,
      tone: 'success',
    };
  }

  if (order.returnFlow.canCreateReplacement) {
    return {
      title: "Mijoz to'lov qilsa qayta yuboriladi",
      text: "Jarima summasi kiritilgan. Endi foydalanuvchi shu summani to'lab, buyurtmani qayta yuborish uchun yangi order ochishi mumkin.",
      tone: 'warning',
    };
  }

  if ((order.returnFlow.penaltyAmount || 0) > 0) {
    return {
      title: "Jarima kiritilgan, qaytish qayd etilgan",
      text: "Buyurtma qaytgan deb belgilangan. Qayta yuborish ochilishi uchun tizim source order holatini tekshiradi.",
      tone: 'warning',
    };
  }

  return {
    title: "Jarima hali kiritilmagan",
    text: "Pochta buyurtmasi qaytgan bo'lsa, pastdagi blok orqali jarima summasini kiriting va qayta yuborish oqimini yoqing.",
    tone: 'secondary',
  };
};

const orderKindLabel = (order?: Ord | null) => {
  if (!order) return '—';
  if (order.isInstore) return "Do'kon ichida rasmiylashtirilgan";
  if (order.isGiftToOther) return "Boshqa odam uchun sovg'a buyurtma";
  return 'Oddiy buyurtma';
};

const fulfillmentModeLabel = (value?: string | null) => {
  const normalized = String(value || '').toLowerCase();
  return ({
    hub_based: 'Hub orqali tayyorlanib, keyin kuryerga beriladi',
    direct_courier: "Do'kondan kuryer to'g'ridan-to'g'ri olib ketadi",
    postal_only_via_hub: "Hub orqali pochtaga topshiriladi",
    pickup_only: "Mijoz o'zi olib ketadi",
  } as Record<string, string>)[normalized] || value || 'Logistika yo‘li hali aniqlanmagan';
};

const fulfillmentStatusLabel = (value?: string | null) => {
  const normalized = String(value || '').toLowerCase();
  return ({
    awaiting_seller_prep: 'Seller tayyorlamoqda',
    ready_for_pickup: "Olib ketishga tayyor",
    picked_from_seller: "Do'kondan olib ketilgan",
    arrived_at_hub: 'Hubga yetib kelgan',
    qc_checked: 'Sifat nazoratidan o‘tgan',
    packed: 'Qadoqlangan',
    labeled: 'Etiketka yopishtirilgan',
    dispatched_to_post: 'Pochtaga topshirilgan',
    assigned_last_mile: 'Yakuniy yetkazuvchi biriktirilgan',
    out_for_delivery: 'Mijozga olib ketilmoqda',
    delivered: 'Yetib borgan',
    returned: 'Qaytgan',
    cancelled: 'Bekor qilingan',
  } as Record<string, string>)[normalized] || value || 'Hali ishga tushmagan';
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
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');

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

  useEffect(() => {
    if (orderFilters.search && orderFilters.search !== autoOpenedSearch && orders.length === 1 && !showView) {
      setAutoOpenedSearch(orderFilters.search);
      void handleOpenView(orders[0]);
    }
  }, [orderFilters.search, autoOpenedSearch, orders, showView]);

  const handleUpdateStatus = (status: string) => {
    if (!selectedOrd?.statusUrl) return;
    const nextStatus = normalizeStatus(status);
    if (nextStatus === 'cancelled' && !confirm('Buyurtma bekor qilinsinmi?')) return;
    if (nextStatus === 'returned' && selectedOrd.deliveryType === 'postal' && selectedOrd.postalReturnUrl) {
      alert("Pochta buyurtmasiga jarima belgilash va qayta yuborish oqimini ochish uchun pastdagi 'Pochta qaytimi / qayta yuborish' blokidan foydalaning.");
      return;
    }
    if (
      nextStatus === 'returned'
      && !confirm("Buyurtma qaytgan deb belgilansinmi?\n\nAgar bu naqd buyurtma bo'lsa, mijoz uchun naqd to'lov vaqtincha yopilishi mumkin.")
    ) return;
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

  const submitForm = (event: FormEvent<HTMLFormElement>, url: string | undefined, method: 'post' | 'patch' = 'post') => {
    event.preventDefault();
    if (!url) return;
    const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
    const currentOrder = selectedOrd;
    router[method](url, data, {
      preserveScroll: true,
      onSuccess: () => currentOrder && loadOrderDetail(currentOrder),
    });
  };

  const showItemRefundColumn = (selectedOrd?.itemsList || []).some((item) => item.canRefund && item.refundUrl);
  const showSellerOrderRefundColumn = (selectedOrd?.sellerOrders || []).some((row) => row.canRefund && row.refundUrl);

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
          { label: 'Qaytgan', val: orderCounts.returned || 0, icon: 'bi-arrow-counterclockwise', color: '#f97316' },
          { label: 'Bekor qilingan', val: orderCounts.cancelled || 0, icon: 'bi-x-circle', color: '#ef4444' },
        ].map((item) => (
          <div className="col-xl col-md-6" key={item.label}>
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
            ['returned', 'Qaytgan'],
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
                  <td>
                    <span className="chip chip-gray">{paymentLabel(order.paymentStatus || order.payment)}</span>
                    {order.splitStatus ? (
                      <span className={`chip ms-1 ${order.splitStatus === 'overdue' ? 'chip-danger' : 'chip-purple'}`}>
                        Nasiya{order.splitStatus === 'overdue' ? ' !' : ''}
                      </span>
                    ) : null}
                  </td>
                  <td><span className="chip chip-gray">{deliveryTypeLabel(order.deliveryType)}</span></td>
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
                  {normalizeStatus(selectedOrd.status) === 'returned' ? (
                    <div className="alert alert-warning py-2 small mb-3">
                      Bu buyurtma <strong>qaytgan</strong> holatda. {isCashPending(selectedOrd.paymentStatus || selectedOrd.payment)
                        ? "Naqd buyurtma bo'lgani uchun bu holat mijozning naqd buyurtma olish imkoniyatiga ta'sir qiladi."
                        : "Bu qaytish qayd etilgan, lekin u naqd jarima hisobiga kirmaydi."}
                    </div>
                  ) : null}
                  <div className="row g-3">
                    <Detail label="Sana" value={selectedOrd.date} />
                    <Detail label="Yakunlangan" value={selectedOrd.completedAt} />
                    <Detail label="To'lov holati" value={paymentLabel(selectedOrd.paymentStatus || selectedOrd.payment)} />
                    <Detail label="Yetkazish turi" value={deliveryTypeLabel(selectedOrd.deliveryType)} />
                    <Detail label="Buyurtma turi" value={orderKindLabel(selectedOrd)} />
                    <Detail label="Pochta qaytimi" value={postalReturnLabel(selectedOrd.postalReturnStatus)} />
                  </div>
                </div>

                {selectedOrd.split ? (
                  <div className="detail-panel mt-3">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                      <h6 className="fw-bold mb-0">
                        <i className="bi bi-calendar-week me-1"></i>Nasiya shartnomasi
                      </h6>
                      <span className={`chip ${
                        selectedOrd.split.status === 'overdue' ? 'chip-danger'
                          : selectedOrd.split.status === 'active' ? 'chip-success'
                          : selectedOrd.split.status === 'pending' ? 'chip-info'
                          : selectedOrd.split.status === 'completed' ? 'chip-gray'
                          : 'chip-warning'
                      }`}>
                        {selectedOrd.split.status === 'pending' ? 'Kutilmoqda (hold)'
                          : selectedOrd.split.status === 'active' ? 'Faol'
                          : selectedOrd.split.status === 'overdue' ? "Muddati o'tgan"
                          : selectedOrd.split.status === 'completed' ? 'Yopilgan'
                          : selectedOrd.split.status === 'cancelled' ? 'Bekor qilingan'
                          : selectedOrd.split.status}
                      </span>
                    </div>
                    <div className="row g-3">
                      <Detail label="Shartnoma" value={selectedOrd.split.contractNumber} />
                      <Detail label="Tarif" value={selectedOrd.split.planName || `${selectedOrd.split.months} oy`} />
                      <Detail label="Jami / To'langan" value={`${fmt(selectedOrd.split.total)} / ${fmt(selectedOrd.split.paid)} so'm`} />
                      <Detail label="Qoldiq" value={`${fmt(selectedOrd.split.remaining)} so'm`} />
                      <Detail label="Progress" value={`${selectedOrd.split.installmentsPaid}/${selectedOrd.split.installmentsCount} to'lov`} />
                      {selectedOrd.split.nextDueAt ? (
                        <Detail label="Keyingi to'lov" value={`${selectedOrd.split.nextDueAt} · ${fmt(selectedOrd.split.nextAmount || 0)} so'm`} />
                      ) : null}
                      {selectedOrd.split.overdueSince ? (
                        <Detail label="Kechikish boshlanishi" value={selectedOrd.split.overdueSince} />
                      ) : null}
                    </div>
                    {selectedOrd.split.installments.length > 0 ? (
                      <div className="mt-3">
                        <div className="text-muted small mb-2">To'lov grafigi</div>
                        {selectedOrd.split.installments.map((inst) => (
                          <div key={inst.sequence} className="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <span className="text-muted">
                              #{inst.sequence} · {inst.isUpfront ? 'Upfront' : inst.dueAt}
                              {inst.attempts > 0 ? ` · ${inst.attempts} urinish` : ''}
                            </span>
                            <span>
                              <strong>{fmt(inst.amount)}</strong>{' '}
                              <span className={`chip ${
                                inst.status === 'paid' ? 'chip-success'
                                  : inst.status === 'overdue' ? 'chip-danger'
                                  : inst.status === 'waived' ? 'chip-gray'
                                  : inst.status === 'cancelled' ? 'chip-gray'
                                  : 'chip-info'
                              }`} style={{ fontSize: 11 }}>
                                {inst.status === 'paid' ? "To'landi"
                                  : inst.status === 'overdue' ? 'Kechikkan'
                                  : inst.status === 'waived' ? 'Kechirilgan'
                                  : inst.status === 'cancelled' ? 'Bekor'
                                  : 'Kutilmoqda'}
                              </span>
                            </span>
                          </div>
                        ))}
                        <div className="text-end mt-2">
                          <a className="btn btn-sm btn-light" href={selectedOrd.split.manageUrl}>
                            <i className="bi bi-box-arrow-up-right me-1"></i>Split boshqaruvida ochish
                          </a>
                        </div>
                      </div>
                    ) : null}
                  </div>
                ) : null}

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Manzil va sovg'a</h6>
                  <AddressBlock address={selectedOrd.address || {}} />
                  {selectedOrd.addresses && selectedOrd.addresses.length > 1 ? (
                    <div className="mt-3">
                      <div className="text-muted small mb-2">Buyurtmadagi barcha manzillar</div>
                      <div className="d-flex flex-column gap-2">
                        {selectedOrd.addresses.map((address, index) => (
                          <div className="rounded-3 border p-3" key={index}>
                            <div className="fw-semibold mb-2">Manzil #{index + 1}</div>
                            <AddressBlock address={address} />
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : null}
                  <div className="row g-3 mt-1">
                    <Detail label="Instore" value={selectedOrd.isInstore ? 'Ha' : "Yo'q"} />
                    <Detail label="Qadoqlash" value={selectedOrd.withPackaging ? `${fmt(selectedOrd.packagingPrice || 0)} so'm` : "Yo'q"} />
                    <Detail label="Boshqasiga sovg'a" value={selectedOrd.isGiftToOther ? 'Ha' : "Yo'q"} />
                    <Detail label="Oluvchi" value={[selectedOrd.recipient?.name, selectedOrd.recipient?.phone].filter(Boolean).join(' / ')} />
                    <Detail label="Oluvchi manzili" value={[selectedOrd.recipient?.region, selectedOrd.recipient?.address].filter(Boolean).join(', ')} />
                    <Detail label="Mijoz izohi" value={selectedOrd.buyerWish} />
                  </div>
                </div>

                {selectedOrd.userReputation ? (
                  <div className="detail-panel mt-3">
                    <h6 className="fw-bold mb-3">Mijoz ishonch holati</h6>
                    <div className="row g-3">
                      <Detail label="Ishonch balli" value={`${selectedOrd.userReputation.score} / 100`} />
                      <Detail label="Qaytgan naqd buyurtmalar" value={`${selectedOrd.userReputation.codReturnStrikes} ta`} />
                      <Detail
                        label="Naqd buyurtma huquqi"
                        value={selectedOrd.userReputation.cashOnDeliveryAllowed ? 'Ochiq' : 'Vaqtincha yopilgan'}
                      />
                      <Detail
                        label="Izoh"
                        value={selectedOrd.userReputation.cashOnDeliveryAllowed
                          ? "Mijoz hozircha naqd buyurtma bera oladi."
                          : selectedOrd.userReputation.cashOnDeliveryBlockReason}
                      />
                    </div>
                  </div>
                ) : null}

                <ReturnPenaltyCard order={selectedOrd} />
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
                          <th>Holat</th>
                          {showItemRefundColumn ? <th>Refund</th> : null}
                        </tr>
                      </thead>
                      <tbody>
                        {(selectedOrd.itemsList || []).map((item, index) => (
                          <tr key={`${item.type}-${item.id || index}`}>
                            <td>
                              <div className="d-flex align-items-center gap-2">
                                <div className="item-thumb">{item.image ? <img src={item.image} alt={item.name} /> : <i className="bi bi-box"></i>}</div>
                                {item.productUrl ? (
                                  <a className="fw-semibold text-decoration-none" href={item.productUrl} title="Mahsulotni ochish">{item.name}</a>
                                ) : (
                                  <span className="fw-semibold">{item.name}</span>
                                )}
                              </div>
                            </td>
                            <td>{item.seller || '—'}</td>
                            <td><span className="chip chip-gray">{item.typeLabel}</span></td>
                            <td>{item.quantity}</td>
                            <td>{fmt(item.price)} so'm</td>
                            <td className="fw-semibold">{fmt(item.total)} so'm</td>
                            <td>
                              {item.isCancelled ? (
                                <div>
                                  <span className="chip chip-danger">Bekor qilingan</span>
                                  <div className="text-muted small mt-1">{item.cancelNotes?.uz || item.cancelReasonCode || '—'}</div>
                                </div>
                              ) : (
                                <span className="chip chip-success">Faol</span>
                              )}
                            </td>
                            {showItemRefundColumn ? (
                              <td style={{ minWidth: 260 }}>
                                {item.canRefund && item.refundUrl ? (
                                  <form onSubmit={(event) => submitForm(event, item.refundUrl)} className="d-flex flex-column gap-2">
                                    <select name="reason_code" className="form-select form-select-sm" defaultValue={(selectedOrd.refundReasonCatalog?.item || [])[0]?.code || 'product_out_of_stock'} required>
                                      {(selectedOrd.refundReasonCatalog?.item || []).map((reason) => (
                                        <option value={reason.code} key={reason.code}>
                                          {reason.notes?.uz}{reason.auto_zero_stock ? ' · stock 0' : ''}
                                        </option>
                                      ))}
                                    </select>
                                    <input name="custom_note" className="form-control form-control-sm" placeholder="Custom izoh kerak bo‘lsa" />
                                    <button className="btn btn-sm btn-outline-danger">Faqat shu mahsulotni refund qilish</button>
                                  </form>
                                ) : (
                                  <div className="text-muted small">
                                    {item.isCancelled ? "Bu mahsulot allaqachon bekor qilingan." : 'Refund mumkin emas.'}
                                  </div>
                                )}
                              </td>
                            ) : null}
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
                        <Detail label="Cashback berilgan" value={`${fmt(selectedOrd.awardedCashbackAmount || 0)} so'm`} />
                        <Detail label="Cashback tayyor vaqti" value={selectedOrd.cashbackReadyAt} />
                        <Detail label="Cashback tushgan vaqt" value={selectedOrd.cashbackAwardedAt} />
                        <Detail label="Cashback xabari" value={selectedOrd.cashbackNotifiedAt} />
                        <Detail label="Sertifikat" value={`${fmt(selectedOrd.giftCertAmount || 0)} so'm`} />
                        <Detail label="Sertifikat ID" value={selectedOrd.giftCertificateId ? `#${selectedOrd.giftCertificateId}` : '—'} />
                        <Detail label="Promokod" value={selectedOrd.promocode} />
                        <Detail label="Qaytim holati" value={postalReturnLabel(selectedOrd.postalReturnStatus)} />
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
                        <Detail label="Qanday yo'l bilan bajariladi" value={fulfillmentModeLabel(selectedOrd.fulfillment.mode)} />
                        <Detail label="Logistika bosqichi" value={fulfillmentStatusLabel(selectedOrd.fulfillment.status)} />
                        <Detail label="Mas'ul hub" value={selectedOrd.fulfillment.hub || "Hub biriktirilmagan yoki to'g'ridan-to'g'ri oqim"} />
                        <Detail label="First / last mile" value={[selectedOrd.fulfillment.firstMile, selectedOrd.fulfillment.lastMile].filter(Boolean).join(' / ')} />
                        <Detail label="Naqd yig'ish" value={selectedOrd.fulfillment.isCod ? `${fmt(selectedOrd.fulfillment.cashCollectAmount || 0)} so'm olinadi` : "Yo'q, oldindan to'langan"} />
                        <Detail label="Kuzatuv / label" value={[selectedOrd.fulfillment.tracking, selectedOrd.fulfillment.labelCode].filter(Boolean).join(' / ')} />
                        <Detail label="Routing versiyasi" value={selectedOrd.fulfillment.routingVersion} />
                        <Detail label="Oxirgi oqim almashtirish" value={formatAudit(selectedOrd.fulfillment.lastModeSwitch)} />
                        <Detail label="Oxirgi hub almashtirish" value={formatAudit(selectedOrd.fulfillment.lastHubReroute)} />
                        <Detail label="Tizim routing yozuvi" value={<JsonPreview value={selectedOrd.fulfillment.routingSnapshot} />} />
                        <Detail label="Ichki izohlar" value={<JsonPreview value={selectedOrd.fulfillment.notes} />} />
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
                  <SellerOrdersTable rows={selectedOrd.sellerOrders || []} reasonOptions={selectedOrd.refundReasonCatalog?.order || []} onSubmit={submitForm} showRefundColumn={showSellerOrderRefundColumn} />
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Refund jurnali</h6>
                  <RefundLedgerTable rows={selectedOrd.refundLedger || []} />
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
                        <Detail label="Hozirgi net" value={`${fmt(selectedOrd.settlementOverview?.currentNet || 0)} so'm`} />
                        <Detail label="Holat" value={selectedOrd.settlementOverview?.label} />
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-6">
                    <div className="detail-panel h-100">
                      <h6 className="fw-bold mb-3">To'lov va kuryer</h6>
                      <div className="row g-3">
                        <Detail label="Payment provider" value={selectedOrd.paymentTransaction?.provider} />
                        <Detail label="Payment status" value={selectedOrd.paymentTransaction?.status} />
                        <Detail label="Provider card ID" value={selectedOrd.paymentCard?.providerCardId || selectedOrd.paymentTransaction?.providerCardId} />
                        <Detail label="Karta" value={[selectedOrd.paymentCard?.maskedNumber, selectedOrd.paymentCard?.vendor, selectedOrd.paymentCard?.cardName].filter(Boolean).join(' / ')} />
                        <Detail label="Karta telefoni" value={selectedOrd.paymentCard?.phone} />
                        <div className="col-md-6">
                          <div className="text-muted small">Fiskal chek (OFD)</div>
                          <div className="fw-semibold">
                            {selectedOrd.fiscalReceipt?.status === 'registered' && (
                              <>
                                <span className="chip chip-success border-0 me-2">Berilgan</span>
                                {selectedOrd.fiscalReceipt?.receiptUrl && (
                                  <a href={selectedOrd.fiscalReceipt.receiptUrl} target="_blank" rel="noreferrer">Chekni ochish</a>
                                )}
                              </>
                            )}
                            {selectedOrd.fiscalReceipt?.status === 'refunded' && (
                              <>
                                <span className="chip chip-gray border-0 me-2">Qaytarilgan</span>
                                {selectedOrd.fiscalReceipt?.refundReceiptUrl && (
                                  <a href={selectedOrd.fiscalReceipt.refundReceiptUrl} target="_blank" rel="noreferrer">Refund chek</a>
                                )}
                              </>
                            )}
                            {selectedOrd.fiscalReceipt?.status === 'pending' && (
                              <span className="chip chip-warning border-0">Kutilmoqda</span>
                            )}
                            {selectedOrd.fiscalReceipt?.status === 'none' && <span className="text-muted">—</span>}
                            {(!selectedOrd.fiscalReceipt || selectedOrd.fiscalReceipt.status === 'disabled') && (
                              <span className="text-muted">O'chirilgan</span>
                            )}
                          </div>
                          {selectedOrd.fiscalReceipt?.fiscalSign && (
                            <div className="text-muted small">Fiskal belgi: {selectedOrd.fiscalReceipt.fiscalSign}</div>
                          )}
                        </div>
                        <Detail label="Kuryer" value={selectedOrd.courierOrder?.courier || selectedOrd.courierName} />
                        <Detail label="Kuryer telefoni" value={selectedOrd.courierOrder?.phone} />
                        <Detail label="Kuryer narxi" value={`${fmt(selectedOrd.courierOrder?.courierPrice || 0)} so'm`} />
                        <Detail label="Masofa" value={`${Number(selectedOrd.courierOrder?.taskDistanceKm || 0).toFixed(2)} km`} />
                        <Detail label="Km haqi" value={`${fmt(selectedOrd.courierOrder?.taskDistanceFeeAmount || 0)} so'm`} />
                        <Detail label="Kuryer bonuslari" value={`${fmt(selectedOrd.courierOrder?.courierBonus ?? 0)} so'm`} />
                        <Detail label="Task jami" value={`${fmt(selectedOrd.courierOrder?.taskFeeAmount || 0)} so'm`} />
                        <Detail label="Settled" value={selectedOrd.courierOrder?.settledAt ? `${fmt(selectedOrd.courierOrder?.settledAmount || 0)} so'm · ${selectedOrd.courierOrder.settledAt}` : '—'} />
                        <Detail label="Kutish rejimi" value="O'chirilgan" />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-2">Logistika boshqaruvi</h6>
                  <div className="row g-3">
                    <div className="col-xl-6">
                      <form className="rounded-3 border p-3 h-100" onSubmit={(event) => submitForm(event, selectedOrd.switchModeUrl)}>
                        <div className="fw-semibold mb-2">Yetkazish oqimini almashtirish</div>
                        <label className="form-label small text-muted">Yangi oqim</label>
                        <select name="target_mode" className="form-select form-select-sm mb-2" defaultValue={selectedOrd.fulfillment?.mode || selectedOrd.fulfillmentModes?.[0]?.value || ''} required>
                          {(selectedOrd.fulfillmentModes || []).map((mode) => <option value={mode.value} key={mode.value}>{mode.label}</option>)}
                        </select>
                        <label className="form-label small text-muted">Qaysi hubga biriktirilsin</label>
                        <select name="hub_id" className="form-select form-select-sm mb-2" defaultValue="">
                          <option value="">Auto tanlash</option>
                          {(selectedOrd.activeHubs || []).map((hub) => <option value={hub.id} key={hub.id}>{hub.label}</option>)}
                        </select>
                        <label className="form-label small text-muted">Sabab</label>
                        <textarea name="override_note" className="form-control form-control-sm mb-3" rows={2} placeholder="Nega logistika yo'li o'zgaryapti?" />
                        <button className="btn btn-sm btn-primary-gradient" disabled={!selectedOrd.switchModeUrl}>Oqimni yangilash</button>
                      </form>
                    </div>
                    <div className="col-xl-6">
                      <form className="rounded-3 border p-3 h-100" onSubmit={(event) => submitForm(event, selectedOrd.rerouteHubUrl)}>
                        <div className="fw-semibold mb-2">Mas'ul hubni almashtirish</div>
                        <label className="form-label small text-muted">Yangi hub</label>
                        <select name="hub_id" className="form-select form-select-sm mb-2" defaultValue="" required>
                          <option value="" disabled>Hub tanlang</option>
                          {(selectedOrd.activeHubs || []).map((hub) => <option value={hub.id} key={hub.id}>{hub.label}</option>)}
                        </select>
                        <label className="form-label small text-muted">Almashtirish sababi</label>
                        <textarea name="reroute_note" className="form-control form-control-sm mb-3" rows={2} placeholder="Masalan: mijozga yaqinroq hub tanlandi" />
                        <button className="btn btn-sm btn-outline-secondary" disabled={!selectedOrd.rerouteHubUrl}>Hubni yangilash</button>
                      </form>
                    </div>
                    <div className="col-xl-6">
                      <form className="rounded-3 border p-3 h-100" onSubmit={(event) => submitForm(event, selectedOrd.postalReturnUrl, 'patch')}>
                        <div className="fw-semibold mb-2">Pochta qaytimi / jarima / qayta yuborish</div>
                        <div className="text-muted small mb-2">
                          Bu blok buyurtmani qaytgan deb belgilaydi, jarima summasini yozadi va mijoz to'lagach qayta yuborish oqimini ochadi.
                        </div>
                        <label className="form-label small text-muted">Qaytim xarajati</label>
                        <input name="postal_return_fee" type="number" min={0} max={1000000} className="form-control form-control-sm mb-2" defaultValue={selectedOrd.postalReturnFee || 0} required />
                        <label className="form-label small text-muted">Izoh</label>
                        <textarea name="postal_return_note" className="form-control form-control-sm mb-3" rows={2} defaultValue={selectedOrd.postalReturnNote || ''} />
                        <button className="btn btn-sm btn-outline-secondary" disabled={!selectedOrd.postalReturnUrl}>Jarima qo'yib qaytgan deb belgilash</button>
                      </form>
                    </div>
                    <div className="col-xl-6">
                      <div className="rounded-3 border p-3 h-100">
                        <div className="fw-semibold mb-2">Riskli amallar</div>
                        <div className="d-flex flex-wrap gap-2 mb-3">
                          {selectedOrd.cancelUrl && canCancelOrder(selectedOrd.status) ? <button className="btn btn-sm btn-outline-danger" onClick={() => confirm('Buyurtma bekor qilinsinmi?') && postPrompt(selectedOrd.cancelUrl, {})}>Bekor qilish</button> : null}
                        </div>
                        {selectedOrd.canRefundPayment && selectedOrd.refundConfirmationPhrase ? (
                          <form onSubmit={(event) => submitForm(event, selectedOrd.refundCancelUrl)}>
                            <div className="alert alert-danger py-2 small mb-2">
                              Tasdiqlash matni: <strong>{selectedOrd.refundConfirmationPhrase}</strong>
                            </div>
                            <input name="confirmation_phrase" className="form-control form-control-sm mb-2" placeholder="Tasdiqlash matni" required />
                            <input name="reason" className="form-control form-control-sm mb-2" placeholder="Refund sababi" />
                            <button className="btn btn-sm btn-danger">Refund + bekor qilish</button>
                          </form>
                        ) : <div className="text-muted small">Refund faqat ruxsat bo'lsa va shartlar mos kelsa chiqadi.</div>}
                      </div>
                    </div>
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

function JsonPreview({ value }: { value?: unknown }) {
  if (!value || (Array.isArray(value) && value.length === 0)) return <>—</>;
  if (typeof value === 'string') return <>{value || '—'}</>;
  return (
    <pre className="small text-muted mb-0 bg-light rounded-3 p-2" style={{ maxHeight: 140, overflow: 'auto', whiteSpace: 'pre-wrap' }}>
      {JSON.stringify(value, null, 2)}
    </pre>
  );
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

function ReturnPenaltyCard({ order }: { order: Ord }) {
  const summary = returnFlowSummary(order);
  const alertClass = summary.tone === 'success'
    ? 'alert-success'
    : summary.tone === 'warning'
      ? 'alert-warning'
      : 'alert-secondary';

  return (
    <div className="detail-panel mt-3">
      <h6 className="fw-bold mb-3">Jarima va qayta yuborish</h6>
      <div className={`alert ${alertClass} py-2 small mb-3`}>
        <strong>{summary.title}.</strong> {summary.text}
      </div>
      <div className="row g-3">
        <Detail label="Oqim turi" value={order.returnFlow?.isPostal ? 'Pochta orqali qaytish' : 'Oddiy qaytish'} />
        <Detail label="Buyurtma holati" value={order.returnFlow?.isReturned ? 'Qaytgan deb qayd etilgan' : 'Hali qaytgan emas'} />
        <Detail label="Jarima summasi" value={`${fmt(order.returnFlow?.penaltyAmount || 0)} so'm`} />
        <Detail
          label="Qayta yuborish holati"
          value={order.returnFlow?.replacementOrderId
            ? `Yangi order ochilgan (#${order.returnFlow.replacementOrderId})`
            : order.returnFlow?.canCreateReplacement
              ? "Mijoz to'lov qilsa ochiladi"
              : "Hali ochilmagan"}
        />
        <Detail label="Qayta yuborish ochilgan vaqt" value={order.returnFlow?.availableAt} />
        <Detail label="Admin izohi" value={order.returnFlow?.note || order.postalReturnNote} />
      </div>
      {order.returnFlow?.isPostal ? (
        <div className="text-muted small mt-3">
          Eslatma: `returned` holatda buyurtma yaratilganda ishlatilgan sertifikat, promokod yoki cashback ortga qaytmaydi. Faqat jarima to'lansa, qayta yuborish uchun yangi order ochiladi.
        </div>
      ) : null}
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

function SellerOrdersTable({
  rows,
  reasonOptions,
  onSubmit,
  showRefundColumn,
}: {
  rows: SellerOrder[];
  reasonOptions: RefundReasonOption[];
  onSubmit: (event: FormEvent<HTMLFormElement>, url: string | undefined, method?: 'post' | 'patch') => void;
  showRefundColumn: boolean;
}) {
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
            <th>Hisob-kitob</th>
            <th>Status</th>
            <th>Qabul</th>
            {showRefundColumn ? <th>Refund</th> : null}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>#{row.id}</td>
              <td>
                <div className="fw-semibold">{row.seller || '—'}</div>
                <div className="text-muted small">{[row.sellerPhone, row.sellerCommissionPercent ? `${row.sellerCommissionPercent}% komissiya` : null].filter(Boolean).join(' · ')}</div>
              </td>
              <td>
                <div>{row.courier || '—'}</div>
                <div className="text-muted small">{[row.courierPhone, row.courierRegion].filter(Boolean).join(' · ')}</div>
              </td>
              <td className="fw-semibold">{fmt(row.amount)} so'm</td>
              <td>
                <div className="fw-semibold">{fmt(row.settlement?.currentNet || 0)} so'm</div>
                <div className="text-muted small">{row.settlement?.label || 'Hisob-kitob kutilmoqda'}</div>
              </td>
              <td><span className={`chip ${statusChip(row.status)}`}>{row.status || '—'}</span></td>
              <td>
                <div className="text-muted">{row.acceptedAt || '—'}</div>
                <div className="text-muted small">{row.createdAt || ''}</div>
              </td>
              {showRefundColumn ? (
                <td style={{ minWidth: 280 }}>
                  {row.canRefund && row.refundUrl ? (
                    <form onSubmit={(event) => onSubmit(event, row.refundUrl)} className="d-flex flex-column gap-2">
                      <select name="reason_code" className="form-select form-select-sm" defaultValue={reasonOptions[0]?.code || 'all_products_out_of_stock'} required>
                        {reasonOptions.map((reason) => (
                          <option value={reason.code} key={reason.code}>
                            {reason.notes?.uz}{reason.auto_zero_stock ? ' · stock 0' : ''}
                          </option>
                        ))}
                      </select>
                      <input name="custom_note" className="form-control form-control-sm" placeholder="Custom izoh kerak bo‘lsa" />
                      <button className="btn btn-sm btn-outline-danger">Shu seller orderni refund qilish</button>
                    </form>
                  ) : (
                    <div className="text-muted small">
                      {row.isCancelled ? (row.cancelNotes?.uz || 'Seller order bekor qilingan.') : 'Refund mumkin emas.'}
                    </div>
                  )}
                </td>
              ) : null}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RefundLedgerTable({ rows }: { rows: RefundLedgerRow[] }) {
  if (!rows.length) {
    return <div className="text-muted small">Bu buyurtma bo‘yicha refund yozuvlari hali yo‘q.</div>;
  }

  return (
    <div className="table-responsive">
      <table className="data-table compact-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Turi</th>
            <th>Karta</th>
            <th>Cashback</th>
            <th>Gift cert</th>
            <th>Sabab</th>
            <th>Vaqti</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>#{row.id}</td>
              <td>
                <div className="fw-semibold">{row.type}</div>
                <div className="text-muted small">{row.status}</div>
              </td>
              <td>{fmt(row.cardRefundAmount || 0)} so'm</td>
              <td>{fmt(row.cashbackRestoreAmount || 0)} so'm</td>
              <td>{fmt(row.giftCertRestoreAmount || 0)} so'm</td>
              <td>{row.reasonNoteUz || row.reasonCode || '—'}</td>
              <td>{row.processedAt || '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
