import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import { ReassignSellerModal, ReassignSellerOption } from '../components/SellerCommon';

import { MiniStat, StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';
import { ProfileCard, Avatar as PAvatar } from '../components/Profile';
import { ActionRow } from '../components/FormAction';
import FormAction from '../components/FormAction';

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
  sellerId?: number | null;
  seller?: string | null;
  ownerLabel?: string | null;
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
  orderId?: number;
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
  // Do'kon-egalik almashtirish — FAQAT superadmin uchun (2026-09).
  canReassign?: boolean;
  reassignUrl?: string;
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
  source?: string;
  sourceLabel?: string;
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
  giftItems?: OrderItem[];
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
    postalProvider?: string | null;
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
  // Admin uchun: karta to'lovini kutayotgan (CARD_PENDING) buyurtmani
  // mijozning saqlangan kartasidan to'lashga urinish (2026-09).
  canPayPendingCard?: boolean;
  payPendingCardUrl?: string;
  customerCards?: Array<{ id: number; maskedNumber?: string | null; vendor?: string | null; isDefault?: boolean }>;
  postalInfo?: {
    provider: string;
    providers: Array<{ code: string; name: string }>;
    tracking: string;
    address: string;
    currentStatus?: {
      code?: string | null;
      providerName?: string | null;
      title?: string | null;
      location?: string | null;
      at?: string | null;
    } | null;
    saveUrl: string;
  };
  fiscalReceipt?: { status: 'disabled' | 'none' | 'pending' | 'failed' | 'registered' | 'refunded'; receiptUrl?: string | null; refundReceiptUrl?: string | null; receiptId?: number | string | null; fiscalSign?: string | null; date?: string | null; error?: string | null; errorCode?: string | null; errorField?: string | null; registerUrl?: string; syncUrl?: string };
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
  sendUnreachablePushUrl?: string;
  canSendUnreachablePush?: boolean;
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
  if (['delivered', 'customer_received', 'c', 'completed'].includes(normalized)) return 'text-light-success';
  if (['in_delivery', 'shipping', 'b'].includes(normalized)) return 'text-light-info';
  if (['packing', 'processing', 'p'].includes(normalized)) return 'text-light-warning';
  if (['cancelled', 'returned', 'f', 'r'].includes(normalized)) return 'text-light-danger';
  return 'text-light-secondary';
};

const Detail = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-md-6">
    <p className="mb-1 f-s-13 text-secondary">{label}</p>
    <h6 className="mb-0 f-w-600 f-s-14 text-dark text-break">{value || '—'}</h6>
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

// Jadval katagi uchun qisqa yorliqlar: uzun jumlalar ("Mijoz olganda naqd
// to'laydi") zich jadvalda qatorni yorib yuboradi, shuning uchun ro'yxatda
// qisqasi, tafsilot panelida esa to'lig'i ko'rsatiladi.
const paymentShort = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  return ({
    cash_pending: 'Naqd',
    card_pending: 'Karta · kutilmoqda',
    held: 'Karta · HOLD',
    paid: "To'landi",
    cancelled: 'Bekor qilingan',
  } as Record<string, string>)[normalized] || value || '—';
};

const paymentTone = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  if (normalized === 'paid') return 'ok';
  if (normalized === 'held' || normalized === 'card_pending') return 'warn';
  if (normalized === 'cancelled') return 'danger';
  return 'neutral';
};

const deliveryShort = (value?: string) => {
  const normalized = String(value || '').toLowerCase();
  return ({ pickup: 'Olib ketish', postal: 'Pochta', delivery: 'Kuryer' } as Record<string, string>)[normalized] || value || '—';
};

// Holat rangi butun panelda bitta ma'noda: yashil — yakunlangan,
// ko'k — jarayonda, sariq — kutilmoqda, qizil — muammo.
const statusTone = (status?: string) => {
  const normalized = String(status || '').toLowerCase();
  if (['delivered', 'customer_received', 'c', 'completed'].includes(normalized)) return 'ok';
  if (['in_delivery', 'shipping', 'b'].includes(normalized)) return 'info';
  if (['packing', 'processing', 'p', 'pending', 'a'].includes(normalized)) return 'warn';
  if (['cancelled', 'returned', 'f', 'r'].includes(normalized)) return 'danger';
  return 'neutral';
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

// BUG TUZATILDI (2026-09): 'in_delivery' ro'yxatda YO'Q edi — demak
// buyurtma kuryerga berilib, allaqachon yo'lda bo'lsa ham, admin panelda
// "Bekor qilish" tugmasi hali ham ko'rsatilar edi. Backend
// (OrderService::cancelOrder) faqat to'lov holatini tekshiradi — kuryer
// allaqachon jo'natilgan-jo'natilmaganini tekshirmaydi — shu sababli bu
// SOF frontend/UI tekshiruvi haqiqiy himoya vazifasini bajaradi. Kuryer
// yo'lda bo'lgan buyurtma uchun oddiy bekor qilish emas, balki mavjud
// qaytarish/refund oqimlaridan foydalanish kerak.
const canCancelOrder = (status?: string) => ![
  'in_delivery',
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
  const {
    orders = [], orderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, orderCounts = {}, orderFilters = {},
    reassignSellers = [], auth,
  } = usePage<{
    orders?: Ord[];
    orderPagination?: PaginationMeta;
    orderCounts?: Record<string, number>;
    orderFilters?: { tab?: string; search?: string };
    // Do'kon-egalik almashtirish (2026-09): tanlov ro'yxati va
    // superadminlikni bilish uchun. MUHIM: superadmin bayrog'i
    // HandleInertiaRequests middleware orqali `auth.admin.isSuperAdmin`
    // sifatida keladi (TOP-LEVEL 'admin' emas — bu 2026-09'dagi bug
    // tuzatildi: avval noto'g'ri top-level 'admin' o'qilardi, u hech
    // qachon kelmagani uchun tugma hech kimga chiqmasdi).
    reassignSellers?: ReassignSellerOption[];
    auth?: { admin?: { isSuperAdmin?: boolean } };
  }>().props;
  const isSuperAdmin = !!auth?.admin?.isSuperAdmin;
  const [activeTab, setActiveTab] = useState(orderFilters.tab || 'pending');
  const [search, setSearch] = useState(orderFilters.search || '');
  const [showView, setShowView] = useState(false);
  const [selectedOrd, setSelectedOrd] = useState<Ord | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');
  const [reassignOrder, setReassignOrder] = useState<SellerOrder | null>(null);

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

  // Do'kon-egalik almashtirish (2026-09) — SellerOrders.tsx dagi bilan bir
  // xil pattern: tanlangan do'kon o'zgargandan keyin buyurtma tafsilotini
  // qayta yuklaymiz, shunda "Seller orderlar" jadvali yangilangan egalikni
  // darhol ko'rsatadi.
  const submitReassign = (sellerId: number) => {
    if (!reassignOrder?.reassignUrl) return;
    const currentOrder = selectedOrd;
    router.post(reassignOrder.reassignUrl, { seller_id: sellerId }, {
      preserveScroll: true,
      onSuccess: () => {
        setReassignOrder(null);
        if (currentOrder) loadOrderDetail(currentOrder);
      },
    });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Buyurtmalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Mijoz, mahsulot, to'lov va fulfillment nazorati</p>
        </div>
      </div>

      <div className="row">
        {[
          { label: 'Jami buyurtmalar', val: orderCounts.all || 0, icon: 'ti-receipt', color: 'rgba(var(--primary), 1)' },
          { label: 'Yetkazilgan', val: orderCounts.paid || 0, icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { label: 'Jarayonda', val: (orderCounts.pending || 0) + (orderCounts.shipped || 0), icon: 'ti-hourglass', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Qaytgan', val: orderCounts.returned || 0, icon: 'ti-rotate', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Bekor qilingan', val: orderCounts.cancelled || 0, icon: 'ti-circle-x', color: 'rgba(var(--danger), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.val} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-body">
          <div className="nav kc-segment mb-3">
            {[
              ['all', 'Barchasi'],
              ['pending', 'Kutilmoqda'],
              ['shipped', "Yo'lda"],
              ['paid', 'Yetkazilgan'],
              ['returned', 'Qaytgan'],
              ['cancelled', 'Bekor qilingan'],
            ].map(([status, label]) => (
              <div key={status} className="nav-item"><button
                  className={`nav-link ${activeTab === status ? 'active' : ''}`}
                  onClick={() => { setActiveTab(status); loadOrders(1, status); }}>
                  {label} <span className="badge">{orderCounts[status] || 0}</span>
                </button></div>
            ))}
          </div>

          <form className="d-flex align-items-center gap-2 flex-wrap mb-3" onSubmit={(event) => { event.preventDefault(); loadOrders(1); }}>
            <div className="app-form app-icon-form position-relative" style={{ width: 'min(280px, 100%)' }}>
              <i className="ti ti-search"></i>
              <input
                className="form-control form-control-sm"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="ID, mijoz yoki telefon"
              />
            </div>
            <button className="btn btn-sm btn-light-secondary" type="submit">Qidirish</button>
            {search ? (
              <button className="btn btn-sm btn-light-secondary" type="button" onClick={() => { setSearch(''); loadOrders(1); }}>Tozalash</button>
            ) : null}
            <span className="ms-auto f-s-13 text-secondary">{orderPagination.total ? `${fmt(orderPagination.total)} ta buyurtma` : null}</span>
          </form>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th>Buyurtma</th>
                  <th>Mijoz</th>
                  <th>Manba</th>
                  <th className="text-end">Dona</th>
                  <th className="text-end">Summa</th>
                  <th>To'lov</th>
                  <th>Yetkazish</th>
                  <th>Holat</th>
                  <th className="text-end">Sana</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => {
                  const payment = order.paymentStatus || order.payment;
                  // Bekor qilingan buyurtma diqqat talab qilmaydi — faqat hali
                  // hal qilinmagan to'lov muammolari belgilanadi.
                  const needsAttention = statusTone(order.status) !== 'danger'
                    && (paymentTone(payment) === 'danger'
                      || (paymentTone(payment) === 'warn' && statusTone(order.status) === 'warn'));
                  return (
                    <tr
                      key={order.id}
                      role="button"
                      data-flag={needsAttention ? 'warn' : undefined}
                      onClick={() => handleOpenView(order)}
                    >
                      <td className="text-nowrap"><span className={`d-inline-block w-5 h-20 b-r-4 me-2 align-middle ${needsAttention ? 'bg-warning' : ''}`}></span><span className="f-w-600">{order.id}</span></td>
                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <PAvatar name={order.customer} size="md" />
                          <div className="min-w-0">
                            <div className="title-text text-nowrap">{order.customer}</div>
                            {order.user?.phone ? <div className="f-s-13 text-secondary">{order.user.phone}</div> : null}
                          </div>
                        </div>
                      </td>
                      <td className="f-s-13 text-secondary">{order.sourceLabel || (order.source === 'web' ? 'Veb-sayt' : 'Ilova')}</td>
                      <td className="text-end f-w-600 text-nowrap">{order.items}</td>
                      <td className="text-end f-w-600 text-nowrap">{fmt(order.total)}</td>
                      <td>
                        <span className={`badge text-uppercase ${toneBadge(paymentTone(payment))}`}>{paymentShort(payment)}</span>
                        {order.splitStatus ? (
                          <div className={`f-s-13 text-secondary ${order.splitStatus === 'overdue' ? ' text-danger' : ''}`}>
                            Nasiya{order.splitStatus === 'overdue' ? ' · muddati o\u2018tgan' : ''}
                          </div>
                        ) : null}
                      </td>
                      <td className="f-s-13 text-secondary">{deliveryShort(order.deliveryType)}</td>
                      <td><span className={`badge text-uppercase ${toneBadge(statusTone(order.status))}`}>{statusLabel(order.status)}</span></td>
                      <td className="text-end f-s-13 text-secondary"><span className="f-w-600 text-nowrap">{order.date}</span></td>
                      <td className="text-end" onClick={(event) => event.stopPropagation()}>
                        <span className="d-inline-flex gap-2 justify-content-end">
                          <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-2" onClick={() => handleOpenView(order)} title="Ko'rish / boshqarish">
                            <i className="ti ti-eye"></i>
                          </button>
                          <a className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" href={order.receiptUrl || '#'} title="Chekni chop etish" target="_blank">
                            <i className="ti ti-printer"></i>
                          </a>
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <PaginationControls {...orderPagination} onPageChange={(page) => loadOrders(page)} />
        </div>
      </div>

      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="f-s-20 f-w-600">Buyurtma: {selectedOrd?.id}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {detailLoading ? <div className="py-5 text-center text-muted">Buyurtma tafsilotlari yuklanmoqda...</div> : selectedOrd ? (
            <div className="row">
              <div className="col-lg-4">
                <ProfileCard
                  name={selectedOrd.customer || 'Mijoz'}
                  subtitle={selectedOrd.user?.phone || selectedOrd.user?.email || "Kontakt yo'q"}
                  badges={<>
                    <span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(selectedOrd.status)))}`}>{statusLabel(selectedOrd.status)}</span>
                    <span className="badge text-light-secondary">{selectedOrd.sourceLabel || (selectedOrd.source === 'web' ? 'Veb-sayt' : 'Ilova')}</span>
                  </>}
                  stats={[
                    { label: 'Summa', value: fmt(selectedOrd.total) },
                    { label: 'Mahsulot', value: selectedOrd.items },
                  ]}
                />
                <div className="card"><div className="card-header"><h5 className="mb-0">Buyurtma ma'lumotlari</h5></div><div className="card-body">
                    {normalizeStatus(selectedOrd.status) === 'returned' ? (
                      <div className="alert alert-light-warning py-2 f-s-13 mb-3">
                        Bu buyurtma <strong>qaytgan</strong> holatda. {isCashPending(selectedOrd.paymentStatus || selectedOrd.payment)
                          ? "Naqd buyurtma bo'lgani uchun bu holat mijozning naqd buyurtma olish imkoniyatiga ta'sir qiladi."
                          : "Bu qaytish qayd etilgan, lekin u naqd jarima hisobiga kirmaydi."}
                      </div>
                    ) : null}
                    {selectedOrd.canSendUnreachablePush ? (
                      <div className="alert alert-light-info py-2 px-3 mb-3">
                        <div className="d-flex justify-content-between align-items-center">
                          <div>
                            <div className="f-w-600 f-s-13 text-primary">
                              <i className="ti ti-phone-off me-1"></i>Bog'lanish kutilmoqda
                            </div>
                            <div className="text-muted f-s-11">
                              Operator qo'ng'iroqqa tusha olmagan bo'lsa, eslatma push yuboring.
                            </div>
                          </div>
                          <button
                            type="button"
                            className="btn btn-sm btn-primary flex-shrink-0 ms-2"
                            onClick={() => confirm("Mijozga «Operator siz bilan bog'lana olmadi» push xabarnomasi yuborilsinmi?") && postPrompt(selectedOrd.sendUnreachablePushUrl, {})}
                            disabled={!selectedOrd.sendUnreachablePushUrl}
                          >
                            <i className="ti ti-bell-filled me-1"></i>Push yuborish
                          </button>
                        </div>
                      </div>
                    ) : null}
                    <div className="row g-3">
                      <Detail label="Manba" value={selectedOrd.sourceLabel || (selectedOrd.source === 'web' ? 'Veb-sayt' : 'Ilova')} />
                      <Detail label="Sana" value={selectedOrd.date} />
                      <Detail label="Yakunlangan" value={selectedOrd.completedAt} />
                      <Detail label="To'lov holati" value={paymentLabel(selectedOrd.paymentStatus || selectedOrd.payment)} />
                      <Detail label="Yetkazish turi" value={deliveryTypeLabel(selectedOrd.deliveryType)} />
                      <Detail label="Buyurtma turi" value={orderKindLabel(selectedOrd)} />
                      <Detail label="Pochta qaytimi" value={postalReturnLabel(selectedOrd.postalReturnStatus)} />
                    </div>
                  </div></div>

                {selectedOrd.split ? (
                  <div className="card"><div className="card-header d-flex justify-content-between align-items-center">
                      <h5 className="mb-0">
                        <i className="ti ti-calendar-time me-1"></i>Nasiya shartnomasi
                      </h5>
                      <span className={`badge ${
                        selectedOrd.split.status === 'overdue' ? 'text-light-danger'
                          : selectedOrd.split.status === 'active' ? 'text-light-success'
                          : selectedOrd.split.status === 'pending' ? 'text-light-info'
                          : selectedOrd.split.status === 'completed' ? 'text-light-secondary'
                          : 'text-light-warning'
                      }`}>
                        {selectedOrd.split.status === 'pending' ? 'Kutilmoqda (hold)'
                          : selectedOrd.split.status === 'active' ? 'Faol'
                          : selectedOrd.split.status === 'overdue' ? "Muddati o'tgan"
                          : selectedOrd.split.status === 'completed' ? 'Yopilgan'
                          : selectedOrd.split.status === 'cancelled' ? 'Bekor qilingan'
                          : selectedOrd.split.status}
                      </span>
                    </div><div className="card-body">
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
                          <div className="text-muted f-s-13 mb-2">To'lov grafigi</div>
                          {selectedOrd.split.installments.map((inst) => (
                            <div key={inst.sequence} className="d-flex justify-content-between align-items-center py-1 b-b-1-light f-s-13">
                              <span className="text-muted">
                                #{inst.sequence} · {inst.isUpfront ? 'Upfront' : inst.dueAt}
                                {inst.attempts > 0 ? ` · ${inst.attempts} urinish` : ''}
                              </span>
                              <span>
                                <strong>{fmt(inst.amount)}</strong>{' '}
                                <span className={`badge ${
                  inst.status === 'paid' ? 'text-light-success'
                   : inst.status === 'overdue' ? 'text-light-danger'
                   : inst.status === 'waived' ? 'text-light-secondary'
                   : inst.status === 'cancelled' ? 'text-light-secondary'
                   : 'text-light-info'
                 } f-s-11`}>
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
                            <a className="btn btn-sm btn-light-secondary" href={selectedOrd.split.manageUrl}>
                              <i className="ti ti-external-link me-1"></i>Split boshqaruvida ochish
                            </a>
                          </div>
                        </div>
                      ) : null}
                    </div></div>
                ) : null}

                <div className="card"><div className="card-header"><h5 className="mb-0">Manzil va sovg'a</h5></div><div className="card-body">
                    <AddressBlock address={selectedOrd.address || {}} />
                    {selectedOrd.addresses && selectedOrd.addresses.length > 1 ? (
                      <div className="mt-3">
                        <div className="text-muted f-s-13 mb-2">Buyurtmadagi barcha manzillar</div>
                        <div className="d-flex flex-column gap-2">
                          {selectedOrd.addresses.map((address, index) => (
                            <div className="b-r-10 b-1-light p-3" key={index}>
                              <div className="f-w-600 mb-2">Manzil #{index + 1}</div>
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
                  </div></div>

                {selectedOrd.userReputation ? (
                  <div className="card"><div className="card-header"><h5 className="mb-0">Mijoz ishonch holati</h5></div><div className="card-body">
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
                    </div></div>
                ) : null}

                <ReturnPenaltyCard order={selectedOrd} />
              </div>

              <div className="col-lg-8">
                <div className="row g-3 mb-3">
                  {[
                    { label: 'Jami summa', value: `${fmt(selectedOrd.total)} so'm`, icon: 'ti-cash' },
                    { label: 'Mahsulot', value: `${selectedOrd.items} dona`, icon: 'ti-package' },
                    { label: 'Yetkazish', value: `${fmt(selectedOrd.deliveryPrice || 0)} so'm`, icon: 'ti-truck' },
                    { label: 'Chegirma', value: `${fmt(selectedOrd.discountAmount || 0)} so'm`, icon: 'ti-percentage' },
                  ].map((item) => (
                    <div className="col-sm-6" key={item.label}>
                      <MiniStat icon={tiIcon(item.icon)} label={item.label} value={item.value} />
                    </div>
                  ))}
                </div>

                <div className="card">
                  <div className="card-header d-flex align-items-center justify-content-between gap-2">
                    <h5 className="mb-0">Boshqaruv amallari</h5>
                    <span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(selectedOrd.status)))}`}>{statusLabel(selectedOrd.status)}</span>
                  </div>
                  <div className="card-body pt-0">
                    <ActionRow
                      icon="ti ti-status-change"
                      title="Buyurtma statusi"
                      value={statusLabel(selectedOrd.status)}
                      meta="Mijoz va seller ilovasida shu holat ko'rinadi"
                      action={
                        <FormAction label="O'zgartirish" icon="ti ti-edit" title="Statusni o'zgartirish" disabled={!selectedOrd.statusUrl} onSubmit={(event) => { event.preventDefault(); const value = String(new FormData(event.currentTarget).get('status') || ''); if (value && normalizeStatus(value) !== normalizeStatus(selectedOrd.status)) handleUpdateStatus(value); }}>
                          <div className="d-grid gap-2">
                            {statusOptions.map((status) => (
                              <label key={status.code} className="d-flex align-items-center gap-2 b-1-light b-r-10 px-3 py-2 m-0" role="button">
                                <input className="form-check-input m-0" type="radio" name="status" value={status.code} defaultChecked={normalizeStatus(selectedOrd.status) === status.code} />
                                <span className="f-w-500">{status.label}</span>
                              </label>
                            ))}
                          </div>
                        </FormAction>
                      }
                    />
                    {selectedOrd.deliveryType === 'postal' && selectedOrd.postalInfo ? (
                      <ActionRow
                        icon="ti ti-mail-fast"
                        tone="info"
                        title="Pochta ma'lumotlari"
                        value={[selectedOrd.postalInfo.providers.find((provider) => provider.code === selectedOrd.postalInfo?.provider)?.name || selectedOrd.postalInfo.provider, selectedOrd.postalInfo.tracking].filter(Boolean).join(' · ') || 'Kiritilmagan'}
                        meta={selectedOrd.postalInfo.currentStatus?.title ? `${selectedOrd.postalInfo.currentStatus.providerName || 'Pochta'}: ${selectedOrd.postalInfo.currentStatus.title}${selectedOrd.postalInfo.currentStatus.location ? ` • ${selectedOrd.postalInfo.currentStatus.location}` : ''}` : (selectedOrd.postalInfo.address || "Trek va manzil «yetib keldi» SMS'ida mijozga boradi")}
                        action={
                          <FormAction label="Tahrirlash" icon="ti ti-edit" title="Pochta ma'lumotlari" description="Trek va manzil «yetib keldi» SMS'ida mijozga boradi." onSubmit={(event) => submitForm(event, selectedOrd.postalInfo!.saveUrl, 'patch')}>
                            <label className="form-label">Pochta xizmati</label>
                            <select name="postal_provider" className="form-select mb-3" defaultValue={selectedOrd.postalInfo.provider || 'uzpost'}>
                              {selectedOrd.postalInfo.providers.map((provider) => <option key={provider.code} value={provider.code}>{provider.name}</option>)}
                            </select>
                            <label className="form-label">Trek raqami</label>
                            <input name="tracking" className="form-control mb-3" maxLength={64} defaultValue={selectedOrd.postalInfo.tracking} placeholder="MM196558286UZ" />
                            <label className="form-label">Pochta bo'limi manzili</label>
                            <input name="postal_office_address" className="form-control" maxLength={255} defaultValue={selectedOrd.postalInfo.address} placeholder="Toshkent sh., Chilonzor t., 5-pochta bo'limi" />
                          </FormAction>
                        }
                      />
                    ) : null}
                    <ActionRow
                      icon="ti ti-route"
                      tone="success"
                      title="Yetkazish oqimi"
                      value={fulfillmentModeLabel(selectedOrd.fulfillment?.mode)}
                      meta={formatAudit(selectedOrd.fulfillment?.lastModeSwitch) !== '—' ? `Oxirgi: ${formatAudit(selectedOrd.fulfillment?.lastModeSwitch)}` : 'Buyurtma qaysi yo‘l bilan bajarilishi'}
                      action={
                        <FormAction label="Almashtirish" icon="ti ti-arrows-exchange" title="Yetkazish oqimini almashtirish" disabled={!selectedOrd.switchModeUrl} onSubmit={(event) => submitForm(event, selectedOrd.switchModeUrl)}>
                          <label className="form-label">Yangi oqim</label>
                          <select name="target_mode" className="form-select mb-3" defaultValue={selectedOrd.fulfillment?.mode || selectedOrd.fulfillmentModes?.[0]?.value || ''} required>
                            {(selectedOrd.fulfillmentModes || []).map((mode) => <option value={mode.value} key={mode.value}>{mode.label}</option>)}
                          </select>
                          <label className="form-label">Qaysi hubga biriktirilsin</label>
                          <select name="hub_id" className="form-select mb-3" defaultValue="">
                            <option value="">Auto tanlash</option>
                            {(selectedOrd.activeHubs || []).map((hub) => <option value={hub.id} key={hub.id}>{hub.label}</option>)}
                          </select>
                          <label className="form-label">Sabab</label>
                          <textarea name="override_note" className="form-control" rows={2} placeholder="Nega logistika yo'li o'zgaryapti?" />
                        </FormAction>
                      }
                    />
                    <ActionRow
                      icon="ti ti-building-warehouse"
                      tone="warning"
                      title="Mas'ul hub"
                      value={selectedOrd.fulfillment?.hub || 'Biriktirilmagan'}
                      meta={formatAudit(selectedOrd.fulfillment?.lastHubReroute) !== '—' ? `Oxirgi: ${formatAudit(selectedOrd.fulfillment?.lastHubReroute)}` : undefined}
                      action={
                        <FormAction label="Almashtirish" icon="ti ti-arrows-exchange" title="Mas'ul hubni almashtirish" disabled={!selectedOrd.rerouteHubUrl} onSubmit={(event) => submitForm(event, selectedOrd.rerouteHubUrl)}>
                          <label className="form-label">Yangi hub</label>
                          <select name="hub_id" className="form-select mb-3" defaultValue="" required>
                            <option value="" disabled>Hub tanlang</option>
                            {(selectedOrd.activeHubs || []).map((hub) => <option value={hub.id} key={hub.id}>{hub.label}</option>)}
                          </select>
                          <label className="form-label">Almashtirish sababi</label>
                          <textarea name="reroute_note" className="form-control" rows={2} placeholder="Masalan: mijozga yaqinroq hub tanlandi" />
                        </FormAction>
                      }
                    />
                    <ActionRow
                      icon="ti ti-truck-return"
                      tone="secondary"
                      title="Pochta qaytimi va jarima"
                      value={`${fmt(selectedOrd.postalReturnFee || 0)} so'm · ${postalReturnLabel(selectedOrd.postalReturnStatus)}`}
                      meta="Qaytgan deb belgilaydi, jarima yozadi va to'langach qayta yuborishni ochadi"
                      action={
                        <FormAction label="Belgilash" icon="ti ti-edit" title="Pochta qaytimi / jarima / qayta yuborish" description="Bu amal buyurtmani qaytgan deb belgilaydi, jarima summasini yozadi va mijoz to'lagach qayta yuborish oqimini ochadi." disabled={!selectedOrd.postalReturnUrl} submitLabel="Qaytgan deb belgilash" onSubmit={(event) => submitForm(event, selectedOrd.postalReturnUrl, 'patch')}>
                          <label className="form-label">Qaytim xarajati (so'm)</label>
                          <input name="postal_return_fee" type="number" min={0} max={1000000} className="form-control mb-3" defaultValue={selectedOrd.postalReturnFee || 0} required />
                          <label className="form-label">Izoh</label>
                          <textarea name="postal_return_note" className="form-control" rows={2} defaultValue={selectedOrd.postalReturnNote || ''} />
                        </FormAction>
                      }
                    />
                    {selectedOrd.canPayPendingCard ? (
                      <ActionRow
                        icon="ti ti-credit-card"
                        tone="info"
                        title="Kartadan to'lovga urinish"
                        value={`${(selectedOrd.customerCards || []).length} ta saqlangan karta`}
                        meta="Kartada summa bron qilinadi (hold), yakuniy yechish odatdagidek"
                        action={
                          <FormAction label="Urinish" icon="ti ti-credit-card" title="Kartadan to'lovga urinish" description="Mijozning saqlangan kartasidan to'lovga urinish — summa bron qilinadi (hold), yakuniy yechib olish buyurtma seller/kuryerga topshirilganda avtomatik amalga oshadi." submitLabel="To'lovga urinish" disabled={(selectedOrd.customerCards || []).length === 0} onSubmit={(event) => submitForm(event, selectedOrd.payPendingCardUrl)}>
                            <label className="form-label">Karta</label>
                            <select name="card_id" className="form-select" required>
                              {(selectedOrd.customerCards || []).map((card) => (
                                <option value={card.id} key={card.id}>{[card.maskedNumber, card.vendor].filter(Boolean).join(' / ')}{card.isDefault ? ' (asosiy)' : ''}</option>
                              ))}
                            </select>
                          </FormAction>
                        }
                      />
                    ) : null}
                    {(selectedOrd.cancelUrl && canCancelOrder(selectedOrd.status)) || (selectedOrd.canRefundPayment && selectedOrd.refundConfirmationPhrase) ? (
                      <ActionRow
                        icon="ti ti-alert-octagon"
                        tone="danger"
                        title="Riskli amallar"
                        meta="Bekor qilish va to'lovni qaytarish"
                        action={<>
                          {selectedOrd.cancelUrl && canCancelOrder(selectedOrd.status) ? <button type="button" className="btn btn-sm btn-light-danger" onClick={() => confirm('Buyurtma bekor qilinsinmi?') && postPrompt(selectedOrd.cancelUrl, {})}><i className="ti ti-x me-1"></i>Bekor qilish</button> : null}
                          {selectedOrd.canRefundPayment && selectedOrd.refundConfirmationPhrase ? (
                            <FormAction label="Refund + bekor" icon="ti ti-receipt-refund" variant="danger" title="Refund + bekor qilish" submitLabel="Refund + bekor qilish" submitVariant="danger" onSubmit={(event) => submitForm(event, selectedOrd.refundCancelUrl)}>
                              <div className="alert alert-light-danger f-s-13">Tasdiqlash uchun quyidagi matnni kiriting: <strong>{selectedOrd.refundConfirmationPhrase}</strong></div>
                              <label className="form-label">Tasdiqlash matni</label>
                              <input name="confirmation_phrase" className="form-control mb-3" placeholder={selectedOrd.refundConfirmationPhrase} required />
                              <label className="form-label">Refund sababi</label>
                              <input name="reason" className="form-control" placeholder="Refund sababi" />
                            </FormAction>
                          ) : null}
                        </>}
                      />
                    ) : null}
                    {selectedOrd.activeHubs?.length ? <div className="text-muted f-s-12 pt-3">Faol hub ID: {selectedOrd.activeHubs.map((hub) => `${hub.id}: ${hub.label}`).join(' · ')}</div> : null}
                  </div>
                </div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Mahsulotlar</h5></div><div className="card-body">
                    <div className="table-responsive app-scroll">
                      <table className="table table-bottom-border align-middle">
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
                          {(selectedOrd.itemsList || []).filter((item) => item.type !== 'gift').map((item, index) => (
                            <tr key={`${item.type}-${item.id || index}`}>
                              <td>
                                <div className="d-flex align-items-center gap-2">
                                  <div className="h-45 w-45 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 f-s-18">{item.image ? <img className="w-100 h-100 object-fit-cover" src={item.image} alt={item.name} /> : <i className="ti ti-box"></i>}</div>
                                  {item.productUrl ? (
                                    <a className="f-w-600 text-decoration-none" href={item.productUrl} title="Mahsulotni ochish">{item.name}</a>
                                  ) : (
                                    <span className="f-w-600">{item.name}</span>
                                  )}
                                </div>
                              </td>
                              <td>{item.seller || '—'}</td>
                              <td><span className="badge text-light-secondary">{item.typeLabel}</span></td>
                              <td>{item.quantity}</td>
                              <td>{fmt(item.price)} so'm</td>
                              <td className="f-w-600">{fmt(item.total)} so'm</td>
                              <td>
                                {item.isCancelled ? (
                                  <div>
                                    <span className="badge text-light-danger">Bekor qilingan</span>
                                    <div className="text-muted f-s-13 mt-1">{item.cancelNotes?.uz || item.cancelReasonCode || '—'}</div>
                                  </div>
                                ) : (
                                  <span className="badge text-light-success">Faol</span>
                                )}
                              </td>
                              {showItemRefundColumn ? (
                                <td>
                                  {item.canRefund && item.refundUrl ? (
                                    <FormAction
                                      label="Refund"
                                      icon="ti ti-receipt-refund"
                                      variant="light-danger"
                                      title={`Refund: ${item.name}`}
                                      description="Faqat shu mahsulot refund qilinadi."
                                      submitLabel="Refund qilish"
                                      submitVariant="danger"
                                      onSubmit={(event) => submitForm(event, item.refundUrl)}
                                    >
                                      <label className="form-label">Sabab</label>
                                      <select name="reason_code" className="form-select mb-3" defaultValue={(selectedOrd.refundReasonCatalog?.item || [])[0]?.code || 'product_out_of_stock'} required>
                                        {(selectedOrd.refundReasonCatalog?.item || []).map((reason) => (
                                          <option value={reason.code} key={reason.code}>
                                            {reason.notes?.uz}{reason.auto_zero_stock ? ' · stock 0' : ''}
                                          </option>
                                        ))}
                                      </select>
                                      <label className="form-label">Izoh (ixtiyoriy)</label>
                                      <textarea name="custom_note" className="form-control" rows={2} placeholder="Custom izoh kerak bo‘lsa" />
                                    </FormAction>
                                  ) : (
                                    <div className="text-muted f-s-13">
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
                  </div></div>

                {(selectedOrd.giftItems || []).length > 0 ? (
                  <div className="card"><div className="card-header d-flex align-items-center justify-content-between">
                      <h5 className="mb-0">
                        <i className="ti ti-gift me-2 text-primary"></i>
                        Buyurtma sovg‘asi
                      </h5>
                      <span className="badge text-light-secondary">{(selectedOrd.giftItems || []).length} tur</span>
                    </div><div className="card-body">
                      <div className="d-flex flex-column gap-2">
                        {(selectedOrd.giftItems || []).map((gift, index) => {
                          const recipient = selectedOrd.isGiftToOther
                            ? [selectedOrd.recipient?.name, selectedOrd.recipient?.phone].filter(Boolean).join(' · ')
                            : selectedOrd.customer;

                          return (
                            <div className="b-1-light b-r-10 p-3" key={`gift-${gift.id || index}`}>
                              <div className="d-flex align-items-start gap-3">
                                <div className="h-45 w-45 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 f-s-18">
                                  {gift.image ? <img className="w-100 h-100 object-fit-cover" src={gift.image} alt={gift.name} /> : <i className="ti ti-gift"></i>}
                                </div>
                                <div className="flex-grow-1 min-w-0">
                                  <div className="d-flex flex-wrap align-items-center gap-2">
                                    {gift.productUrl ? (
                                      <a className="f-w-600 text-decoration-none" href={gift.productUrl}>{gift.name}</a>
                                    ) : (
                                      <span className="f-w-600">{gift.name}</span>
                                    )}
                                    <span className="badge text-light-success">Bepul sovg‘a</span>
                                    {gift.quantity > 1 ? <span className="badge text-light-secondary">{gift.quantity} dona</span> : null}
                                  </div>
                                  <div className="row g-2 mt-1">
                                    <div className="col-md-6">
                                      <div className="text-muted f-s-13">Sovg‘a egasi</div>
                                      <div className="f-w-600">{gift.ownerLabel || gift.seller || 'Ega aniqlanmagan'}</div>
                                    </div>
                                    <div className="col-md-6">
                                      <div className="text-muted f-s-13">Kim uchun</div>
                                      <div className="f-w-600">{recipient || 'Mijoz'}</div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div></div>
                ) : null}

                <div className="row">
                  <div className="col-lg-6">
                    <div className="card h-100"><div className="card-header"><h5 className="mb-0">Hisob-kitob</h5></div><div className="card-body">
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
                      </div></div>
                  </div>
                  <div className="col-lg-6">
                    <div className="card h-100"><div className="card-header"><h5 className="mb-0">Fulfillment</h5></div><div className="card-body">
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
                          <div className="text-muted f-s-13">Fulfillment yozuvi hali yoq.</div>
                        )}
                      </div></div>
                  </div>
                </div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Operatsion timeline</h5></div><div className="card-body">
                    <OrderTimeline rows={selectedOrd.fulfillment?.timeline || []} />
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Seller orderlar</h5></div><div className="card-body">
                    <SellerOrdersTable rows={selectedOrd.sellerOrders || []} reasonOptions={selectedOrd.refundReasonCatalog?.order || []} onSubmit={submitForm} showRefundColumn={showSellerOrderRefundColumn} isSuperAdmin={isSuperAdmin} onReassign={setReassignOrder} />
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Refund jurnali</h5></div><div className="card-body">
                    <RefundLedgerTable rows={selectedOrd.refundLedger || []} />
                  </div></div>

                <div className="row">
                  <div className="col-lg-6">
                    <div className="card h-100"><div className="card-header"><h5 className="mb-0">Seller hisob-kitobi</h5></div><div className="card-body">
                        <div className="row g-3">
                          <Detail label="Gross" value={`${fmt(selectedOrd.settlementOverview?.gross || 0)} so'm`} />
                          <Detail label="Komissiya" value={`${fmt(selectedOrd.settlementOverview?.commission || 0)} so'm`} />
                          <Detail label="Seller net" value={`${fmt(selectedOrd.settlementOverview?.net || 0)} so'm`} />
                          <Detail label="Qaytarilgan net" value={`${fmt(selectedOrd.settlementOverview?.reversedNet || 0)} so'm`} />
                          <Detail label="Hozirgi net" value={`${fmt(selectedOrd.settlementOverview?.currentNet || 0)} so'm`} />
                          <Detail label="Holat" value={selectedOrd.settlementOverview?.label} />
                        </div>
                      </div></div>
                  </div>
                  <div className="col-lg-6">
                    <div className="card h-100"><div className="card-header"><h5 className="mb-0">To'lov va kuryer</h5></div><div className="card-body">
                        <div className="row g-3">
                          <Detail label="Payment provider" value={selectedOrd.paymentTransaction?.provider} />
                          <Detail label="Payment status" value={selectedOrd.paymentTransaction?.status} />
                          <Detail label="Provider card ID" value={selectedOrd.paymentCard?.providerCardId || selectedOrd.paymentTransaction?.providerCardId} />
                          <Detail label="Karta" value={[selectedOrd.paymentCard?.maskedNumber, selectedOrd.paymentCard?.vendor, selectedOrd.paymentCard?.cardName].filter(Boolean).join(' / ')} />
                          <Detail label="Karta telefoni" value={selectedOrd.paymentCard?.phone} />
                          <div className="col-md-6">
                            <div className="text-muted f-s-13">Fiskal chek (OFD)</div>
                            <div className="f-w-600">
                              {selectedOrd.fiscalReceipt?.status === 'registered' && (
                                <>
                                  <span className="badge text-light-success border-0 me-2">Berilgan</span>
                                  {selectedOrd.fiscalReceipt?.receiptUrl && (
                                    <a href={selectedOrd.fiscalReceipt.receiptUrl} target="_blank" rel="noreferrer">Chekni ochish</a>
                                  )}
                                </>
                              )}
                              {selectedOrd.fiscalReceipt?.status === 'refunded' && (
                                <>
                                  <span className="badge text-light-secondary border-0 me-2">Qaytarilgan</span>
                                  {selectedOrd.fiscalReceipt?.refundReceiptUrl && (
                                    <a href={selectedOrd.fiscalReceipt.refundReceiptUrl} target="_blank" rel="noreferrer">Refund chek</a>
                                  )}
                                </>
                              )}
                              {selectedOrd.fiscalReceipt?.status === 'pending' && (
                                <span className="badge text-light-warning border-0">Kutilmoqda</span>
                              )}
                              {selectedOrd.fiscalReceipt?.status === 'failed' && (
                                <span className="badge text-light-danger border-0">Xatolik</span>
                              )}
                              {selectedOrd.fiscalReceipt?.status === 'none' && <span className="text-muted">—</span>}
                              {(!selectedOrd.fiscalReceipt || selectedOrd.fiscalReceipt.status === 'disabled') && (
                                <span className="text-muted">O'chirilgan</span>
                              )}
                            </div>
                            {selectedOrd.fiscalReceipt?.fiscalSign && (
                              <div className="text-muted f-s-13">Fiskal belgi: {selectedOrd.fiscalReceipt.fiscalSign}</div>
                            )}
                            {selectedOrd.fiscalReceipt?.error && (
                              <div className="text-danger f-s-13 mt-1">{selectedOrd.fiscalReceipt.error}</div>
                            )}
                            {selectedOrd.fiscalReceipt && ['pending', 'failed'].includes(selectedOrd.fiscalReceipt.status) && (
                              <div className="d-flex gap-1 mt-2">
                                {selectedOrd.fiscalReceipt.syncUrl ? <button className="btn btn-sm btn-light-secondary" onClick={() => router.post(selectedOrd.fiscalReceipt!.syncUrl!, {}, { preserveScroll: true })}><i className="ti ti-cloud-download me-1"></i>Tekshirish</button> : null}
                                {selectedOrd.fiscalReceipt.registerUrl ? <button className="btn btn-sm btn-outline-primary" onClick={() => router.post(selectedOrd.fiscalReceipt!.registerUrl!, {}, { preserveScroll: true })}><i className="ti ti-send me-1"></i>Qayta yuborish</button> : null}
                              </div>
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

                      </div></div>
                  </div>
                </div>

              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          {selectedOrd?.canSendUnreachablePush ? (
            <Button
              variant="outline-primary"
              className="me-auto"
              onClick={() => confirm("Mijozga «Operator siz bilan bog'lana olmadi» push xabarnomasi yuborilsinmi?") && postPrompt(selectedOrd.sendUnreachablePushUrl, {})}
            >
              <i className="ti ti-phone-off me-1"></i>Bog'lana olmadik (Push)
            </Button>
          ) : null}
          {selectedOrd?.labelUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.labelUrl} target="_blank">Label</a> : null}
          {selectedOrd?.receiptUrl ? <a className="btn btn-outline-secondary" href={selectedOrd.receiptUrl} target="_blank">Chek</a> : null}
          <Button variant="light-secondary" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>

      <ReassignSellerModal order={reassignOrder} sellers={reassignSellers} onHide={() => setReassignOrder(null)} onSubmit={submitReassign} />
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
    <pre className="f-s-13 text-muted mb-0 bg-light-secondary b-r-10 p-2 overflow-auto" style={{ maxHeight: 140, whiteSpace: 'pre-wrap' }}>
      {JSON.stringify(value, null, 2)}
    </pre>
  );
}

function OrderTimeline({ rows }: { rows: Array<{ code: string; title: string; at: string }> }) {
  if (!rows.length) {
    return <div className="text-muted f-s-13">Fulfillment bosqichlari hali qayd etilmagan.</div>;
  }

  return (
    <div className="d-flex flex-column gap-2">
      {rows.map((row) => (
        <div className="d-flex gap-3 align-items-start" key={`${row.code}-${row.at}`}>
          <i className="ti ti-circle-check-filled text-success mt-1"></i>
          <div>
            <div className="f-w-600">{row.title}</div>
            <div className="text-muted f-s-13">{row.at}</div>
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
    return <div className="text-muted f-s-13">Manzil kiritilmagan.</div>;
  }

  return (
    <ul className="list-group list-group-flush">
      {rows.map(([key, value]) => (
        <li className="list-group-item d-flex justify-content-between gap-3 px-0" key={key}>
          <span className="text-secondary">{key}</span>
          <span className="f-w-600 text-dark text-end">{String(value)}</span>
        </li>
      ))}
      <li className="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
        <span className="text-secondary">Xarita</span>
        <MapButtons mapLinks={mapLinks} />
      </li>
    </ul>
  );
}

function ReturnPenaltyCard({ order }: { order: Ord }) {
  const summary = returnFlowSummary(order);
  const alertClass = summary.tone === 'success'
    ? 'alert-light-success'
    : summary.tone === 'warning'
      ? 'alert-light-warning'
      : 'alert-light-secondary';

  return (
    <div className="card"><div className="card-header"><h5 className="mb-0">Jarima va qayta yuborish</h5></div><div className="card-body">
        <div className={`alert ${alertClass} py-2 f-s-13 mb-3`}>
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
          <div className="text-muted f-s-13 mt-3">
            Eslatma: `returned` holatda buyurtma yaratilganda ishlatilgan sertifikat, promokod yoki cashback ortga qaytmaydi. Faqat jarima to'lansa, qayta yuborish uchun yangi order ochiladi.
          </div>
        ) : null}
      </div></div>
  );
}

function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <strong>—</strong>;

  return (
    <strong className="d-flex gap-2 flex-wrap justify-content-end">
      {mapLinks.google ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="ti ti-map-pin me-1"></i>Google Map</a> : null}
      {mapLinks.yandex ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="ti ti-map me-1"></i>Yandex Map</a> : null}
    </strong>
  );
}

function SellerOrdersTable({
  rows,
  reasonOptions,
  onSubmit,
  showRefundColumn,
  isSuperAdmin,
  onReassign,
}: {
  rows: SellerOrder[];
  reasonOptions: RefundReasonOption[];
  onSubmit: (event: FormEvent<HTMLFormElement>, url: string | undefined, method?: 'post' | 'patch') => void;
  showRefundColumn: boolean;
  isSuperAdmin: boolean;
  onReassign: (row: SellerOrder) => void;
}) {
  if (!rows.length) {
    return <div className="text-muted f-s-13">Seller order topilmadi.</div>;
  }

  return (
    <div className="table-responsive app-scroll">
      <table className="table table-bottom-border align-middle">
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
            <th>Amallar</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>#{row.id}</td>
              <td>
                <div className="f-w-600">{row.seller || '—'}</div>
                <div className="text-muted f-s-13">{[row.sellerPhone, row.sellerCommissionPercent ? `${row.sellerCommissionPercent}% komissiya` : null].filter(Boolean).join(' · ')}</div>
              </td>
              <td>
                <div>{row.courier || '—'}</div>
                <div className="text-muted f-s-13">{[row.courierPhone, row.courierRegion].filter(Boolean).join(' · ')}</div>
              </td>
              <td className="f-w-600">{fmt(row.amount)} so'm</td>
              <td>
                <div className="f-w-600">{fmt(row.settlement?.currentNet || 0)} so'm</div>
                <div className="text-muted f-s-13">{row.settlement?.label || 'Hisob-kitob kutilmoqda'}</div>
              </td>
              <td><span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(row.status)))}`}>{row.status || '—'}</span></td>
              <td>
                <div className="text-muted">{row.acceptedAt || '—'}</div>
                <div className="text-muted f-s-13">{row.createdAt || ''}</div>
              </td>
              {showRefundColumn ? (
                <td>
                  {row.canRefund && row.refundUrl ? (
                    <FormAction
                      label="Refund"
                      icon="ti ti-receipt-refund"
                      variant="light-danger"
                      title={`Seller order #${row.id} refund`}
                      description="Butun seller order refund qilinadi."
                      submitLabel="Refund qilish"
                      submitVariant="danger"
                      onSubmit={(event) => onSubmit(event, row.refundUrl)}
                    >
                      <label className="form-label">Sabab</label>
                      <select name="reason_code" className="form-select mb-3" defaultValue={reasonOptions[0]?.code || 'all_products_out_of_stock'} required>
                        {reasonOptions.map((reason) => (
                          <option value={reason.code} key={reason.code}>
                            {reason.notes?.uz}{reason.auto_zero_stock ? ' · stock 0' : ''}
                          </option>
                        ))}
                      </select>
                      <label className="form-label">Izoh (ixtiyoriy)</label>
                      <textarea name="custom_note" className="form-control" rows={2} placeholder="Custom izoh kerak bo‘lsa" />
                    </FormAction>
                  ) : (
                    <div className="text-muted f-s-13">
                      {row.isCancelled ? (row.cancelNotes?.uz || 'Seller order bekor qilingan.') : 'Refund mumkin emas.'}
                    </div>
                  )}
                </td>
              ) : null}
              <td>
                {/* Do'kon-egalik almashtirish (2026-09) — faqat superadmin,
                    va faqat backend canReassign=true deganda (buyurtma hali
                    kuryerga topshirilmagan bosqichda). */}
                {isSuperAdmin && row.canReassign && row.reassignUrl ? (
                  <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => onReassign(row)} title="Do'konni almashtirish">
                    <i className="ti ti-arrows-left-right"></i>
                  </button>
                ) : <span className="text-muted f-s-13">—</span>}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RefundLedgerTable({ rows }: { rows: RefundLedgerRow[] }) {
  if (!rows.length) {
    return <div className="text-muted f-s-13">Bu buyurtma bo‘yicha refund yozuvlari hali yo‘q.</div>;
  }

  return (
    <div className="table-responsive app-scroll">
      <table className="table table-bottom-border align-middle">
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
                <div className="f-w-600">{row.type}</div>
                <div className="text-muted f-s-13">{row.status}</div>
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
