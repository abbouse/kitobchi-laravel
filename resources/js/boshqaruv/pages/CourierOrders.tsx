import { toneOf } from '../utils/tone';
import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Modal } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);
type Counts = Record<string, number>;
type StatusMeta = { label: string; badge?: string };
type AnyRow = Record<string, string | number | boolean | null | undefined | Record<string, string>>;
type PenaltyRule = { key: string; label: string; description: string; amount: number };

interface Courier {
  id: number;
  name: string;
  firstName?: string;
  lastName?: string;
  phone?: string;
  photo?: string;
  region?: string;
  status?: string;
  isOnline?: boolean;
  availabilityUpdatedAt?: string;
  verificationStatus?: string;
  verificationLabel?: string;
  verificationNotes?: string;
  verifiedAt?: string;
  transport?: string;
  transportLabel?: string;
  vehicle?: string;
  vehicleBrand?: string;
  vehicleModel?: string;
  vehicleColor?: string;
  plate?: string;
  balance?: number;
  reserved?: number;
  totalWithdrawal?: number;
  totalEarned?: number;
  orders?: number;
  warningCount?: number;
  joined?: string;
  location?: AnyRow & { mapLinks?: Record<string, string> };
  identity?: AnyRow;
  payment?: AnyRow;
  recentOrders?: AnyRow[];
  transactions?: AnyRow[];
  banLogs?: AnyRow[];
  documents?: AnyRow[];
  actions?: Record<string, string>;
}

interface CourierOrder {
  id: number;
  orderId?: number;
  courier: string;
  courierPhone?: string;
  courierRegion?: string;
  courierStatus?: string;
  customer: string;
  customerPhone?: string;
  amount: number;
  mainOrderAmount?: number;
  courierPrice?: number;
  bonus?: number;
  taskDistanceKm?: number;
  taskFeeAmount?: number;
  taskBaseFeeAmount?: number;
  taskDistanceFeeAmount?: number;
  taskBonusAmount?: number;
  taskLeg?: string;
  settledAmount?: number;
  settledAt?: string;
  pickedUpAt?: string;
  deliveryPrice?: number;
  deliveryType?: string;
  paymentStatus?: string;
  status: string;
  statusLabel?: string;
  statusBadge?: string;
  date?: string;
  address?: Record<string, string | null | undefined | Record<string, string>>;
  summary?: { itemsCount?: number; itemsTotal?: number };
  items?: Array<{ name: string; type?: string; quantity: number; price: number; author?: string | null }>;
  statusUrl?: string;
  penaltyUrl?: string;
  penaltyRules?: PenaltyRule[];
}

const courierTabs = [
  { key: 'pending', label: 'Kutilmoqda' },
  { key: 'approved', label: 'Faol' },
  { key: 'rejected', label: 'Rad etilgan' },
  { key: 'blocked', label: 'Bloklangan' },
  { key: 'all', label: 'Barchasi' },
];

const courierLabel = (status?: string) => ({
  approved: 'Faol', pending: 'Kutilmoqda', rejected: 'Rad etilgan', blocked: 'Bloklangan',
}[String(status || '')] || status || '—');

const chip = (status?: string, badge?: string) => {
  if (badge === 'badge-success' || status === 'approved' || status === 'delivered' || status === 'customer_received') return 'chip-success';
  if (badge === 'badge-danger' || status === 'rejected' || status === 'blocked' || status === 'cancelled' || status === 'returned') return 'chip-danger';
  if (badge === 'badge-info' || status === 'pending') return 'chip-info';
  if (badge === 'badge-warning' || status === 'in_delivery') return 'chip-warning';
  return 'chip-gray';
};

export default function CourierOrders() {
  const {
    couriers = [], courierCounts = {}, courierOrders = [], courierOrderCounts = {}, courierOrderStatuses = {},
    courierPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, courierOrderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    courierFilters = {}, courierOrderFilters = {},
  } = usePage<{
    couriers?: Courier[]; courierCounts?: Counts; courierOrders?: CourierOrder[];
    courierOrderCounts?: Counts; courierOrderStatuses?: Record<string, StatusMeta>;
    courierPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    courierOrderPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    courierFilters?: { tab?: string; search?: string }; courierOrderFilters?: { tab?: string; search?: string };
  }>().props;

  const [courierTab, setCourierTab] = useState(courierFilters.tab || 'pending');
  const [orderTab, setOrderTab] = useState(courierOrderFilters.tab || 'all');
  const [courierSearch, setCourierSearch] = useState(courierFilters.search || '');
  const [orderSearch, setOrderSearch] = useState(courierOrderFilters.search || '');
  const [selectedCourier, setSelectedCourier] = useState<Courier | null>(null);
  const [editingCourier, setEditingCourier] = useState<Courier | null>(null);
  const [selectedOrder, setSelectedOrder] = useState<CourierOrder | null>(null);

  const totalBalance = useMemo(() => couriers.reduce((sum, courier) => sum + (courier.balance || 0), 0), [couriers]);
  const onlineCount = useMemo(() => couriers.filter((courier) => courier.isOnline).length, [couriers]);
  const orderTabs = [{ key: 'all', label: 'Barchasi' }, ...Object.entries(courierOrderStatuses).map(([key, meta]) => ({ key, label: meta.label }))];
  const isOrderPage = typeof window !== 'undefined' && window.location.pathname.includes('/courier-orders');
  const pageUrl = isOrderPage ? '/boshqaruv/courier-orders' : '/boshqaruv/couriers';
  const load = (extra: Record<string, string | number> = {}) => router.get(pageUrl, { couriers_page: courierPagination.page, couriers_tab: courierTab, couriers_search: courierSearch, courier_orders_page: courierOrderPagination.page, courier_orders_tab: orderTab, courier_orders_search: orderSearch, ...extra }, { preserveState: true, preserveScroll: true, replace: true });

  const patch = (url?: string, data: Record<string, string> = {}, confirmation?: string) => {
    if (!url || (confirmation && !confirm(confirmation))) return;
    router.patch(url, data, { preserveScroll: true });
  };
  const warn = (courier: Courier) => {
    const title = prompt('Ogohlantirish sarlavhasi', 'Admin ogohlantirishi');
    if (!title) return;
    const message = prompt('Ogohlantirish matni', 'Iltimos, yetkazib berish qoidalariga amal qiling.');
    if (!message || !courier.actions?.warnUrl) return;
    router.post(courier.actions.warnUrl, { title, message }, { preserveScroll: true });
  };
  const resetPassword = (courier: Courier) => {
    if (!courier.actions?.resetPasswordUrl || !confirm(`${courier.name} uchun yangi parol SMS orqali yuborilsinmi?`)) return;
    router.post(courier.actions.resetPasswordUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">{isOrderPage ? 'Kuryer buyurtmalari' : 'Kuryerlar'}</h1><p className="page-subtitle">{isOrderPage ? 'Kuryer orderlari, statuslar, mijoz qidiruvi va jarimalar' : 'Kuryer profillari, online holat, lokatsiya, verifikatsiya va balans'}</p></div></div>
      <div className="kpi-strip row g-3 mb-4">
        {(isOrderPage ? [
          ['Jami order', courierOrderCounts.all || 0, 'bi-truck', 'var(--kc-ink)'],
          ["Yo'lda", courierOrderCounts.in_delivery || 0, 'bi-signpost-split', 'var(--kc-cat-navy)'],
          ['Yetkazildi', courierOrderCounts.delivered || 0, 'bi-check-circle', 'var(--kc-ok)'],
          ['Mijoz qabul qildi', courierOrderCounts.customer_received || 0, 'bi-bag-check', 'var(--kc-cat-violet)'],
        ] : [
          ['Kutilmoqda', courierCounts.pending || 0, 'bi-hourglass-split', 'var(--kc-warn)'],
          ['Faol kuryer', courierCounts.approved || 0, 'bi-bicycle', 'var(--kc-ok)'],
          ['Online', onlineCount, 'bi-broadcast-pin', 'var(--kc-cat-navy)'],
          ['Kuryer balansi', `${fmt(totalBalance)} so'm`, 'bi-wallet2', 'var(--kc-cat-violet)'],
        ]).map(([label, value, icon, color]) => <div className="col-xl-3 col-md-6" key={String(label)}><div className="stat-card"><div className="d-flex align-items-center gap-3"><div><div className="stat-value">{value}</div><div className="stat-label">{label}</div></div></div></div></div>)}
      </div>

      {!isOrderPage ? <div className="card-panel">
        <div className="panel-head"><div><div className="panel-title">Kuryerlar jadvali</div><small className="text-muted">{courierPagination.total} ta kuryer topildi</small></div><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={courierSearch} onChange={(e) => setCourierSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ couriers_page: 1 })} placeholder="Ism, telefon, hudud yoki raqam" /></div>
        <div className="kc-tabs d-flex flex-wrap gap-2 mb-3">{courierTabs.map((tab) => <button key={tab.key} className={`kc-tab ${courierTab === tab.key ? 'active' : ''}`} onClick={() => { setCourierTab(tab.key); load({ couriers_page: 1, couriers_tab: tab.key }); }}>{tab.label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(courierCounts[tab.key] || 0)}</span></button>)}</div>
        <div className="table-responsive"><table className="data-table"><thead><tr><th>ID</th><th>Kuryer</th><th>Ish holati</th><th>Hudud</th><th>Transport</th><th className="right">Buyurtma</th><th className="right">Balans</th><th>Ogohlantirish</th><th>Holat</th><th>Amallar</th></tr></thead><tbody>
          {couriers.map((courier) => <tr key={courier.id}>
            <td className="cell-id">#{courier.id}</td>
            <td><div className="d-flex align-items-center gap-2"><Avatar row={courier} /><div><div className="fw-semibold">{courier.name}</div><small className="text-muted">{courier.phone || '—'}</small></div></div></td>
            <td><span className={`chip ${courier.isOnline ? 'chip-success' : 'chip-gray'}`}>{courier.isOnline ? 'Online' : 'Offline'}</span><small className="d-block text-muted">{courier.availabilityUpdatedAt || courier.location?.updatedAt || '—'}</small></td>
            <td>{courier.region || '—'}</td><td><div>{courier.transportLabel || courier.transport || '—'}</div><small className="text-muted">{courier.plate || courier.vehicle}</small></td>
            <td className="right money">{courier.orders || 0}</td><td>{fmt(courier.balance || 0)} so'm</td>
            <td><span className={`chip ${(courier.warningCount || 0) > 0 ? 'chip-warning' : 'chip-gray'}`}>{courier.warningCount || 0}/3</span></td>
            <td><span className={`st ${toneOf(chip(courier.status))}`}><i></i>{courierLabel(courier.status)}</span></td>
            <td><button className="btn btn-sm btn-light me-1" onClick={() => setSelectedCourier(courier)}><i className="bi bi-eye"></i></button>{courier.status !== 'approved' ? <button className="btn btn-sm btn-light me-1" onClick={() => patch(courier.actions?.approveUrl, {}, 'Kuryer tasdiqlansinmi?')}><i className="bi bi-check2-circle"></i></button> : null}<button className="btn btn-sm btn-light text-warning" onClick={() => warn(courier)}><i className="bi bi-exclamation-triangle"></i></button></td>
          </tr>)}{courierPagination.total === 0 ? <tr><td colSpan={10} className="text-center text-muted py-5">Kuryer topilmadi</td></tr> : null}
        </tbody></table></div><PaginationControls {...courierPagination} onPageChange={(page) => load({ couriers_page: page })} />
      </div> : null}

      {isOrderPage ? <div className="card-panel">
        <div className="panel-head"><div><div className="panel-title">Kuryer buyurtmalari</div><small className="text-muted">{courierOrderPagination.total} ta yozuv topildi</small></div><input className="form-control form-control-sm" style={{ maxWidth: 360 }} value={orderSearch} onChange={(e) => setOrderSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ courier_orders_page: 1 })} placeholder="Order ID, mijoz raqami/ismi yoki kuryer" /></div>
        <div className="kc-tabs d-flex flex-wrap gap-2 mb-3">{orderTabs.map((tab) => <button key={tab.key} className={`kc-tab ${orderTab === tab.key ? 'active' : ''}`} onClick={() => { setOrderTab(tab.key); load({ courier_orders_page: 1, courier_orders_tab: tab.key }); }}>{tab.label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(courierOrderCounts[tab.key] || 0)}</span></button>)}</div>
        <div className="table-responsive"><table className="data-table"><thead><tr><th>ID</th><th>Kuryer</th><th>Mijoz</th><th>Summa</th><th>To'lov</th><th>Holat</th><th>Sana</th><th>Amallar</th></tr></thead><tbody>
          {courierOrders.map((order) => <tr key={order.id}><td><strong>#{order.id}</strong><small className="d-block text-muted">ORD #{order.orderId || '—'}</small></td><td><div className="fw-semibold">{order.courier}</div><small className="text-muted">{order.courierPhone || '—'}</small></td><td><div>{order.customer}</div><small className="text-muted">{order.customerPhone || '—'}</small></td><td><strong>{fmt(order.amount)} so'm</strong><small className="d-block text-muted">Ulush: {fmt((order.courierPrice || 0) + (order.bonus || 0))}</small></td><td>{order.paymentStatus || '—'}</td><td><select className={`form-select form-select-sm ${chip(order.status, order.statusBadge)}`} value={order.status} onChange={(e) => patch(order.statusUrl, { status: e.target.value })}>{Object.entries(courierOrderStatuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}</select></td><td className="text-muted">{order.date || '—'}</td><td><button className="btn btn-sm btn-light" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button></td></tr>)}
          {courierOrderPagination.total === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Buyurtma topilmadi</td></tr> : null}
        </tbody></table></div><PaginationControls {...courierOrderPagination} onPageChange={(page) => load({ courier_orders_page: page })} />
      </div> : null}
      <CourierModal courier={selectedCourier} onHide={() => setSelectedCourier(null)} onPatch={patch} onWarn={warn} onResetPassword={resetPassword} onEdit={(courier) => setEditingCourier(courier)} />
      <CourierEditModal courier={editingCourier} onHide={() => setEditingCourier(null)} />
      <OrderModal order={selectedOrder} statuses={courierOrderStatuses} onHide={() => setSelectedOrder(null)} onPatch={patch} />
    </div>
  );
}

function CourierModal({ courier, onHide, onPatch, onWarn, onResetPassword, onEdit }: { courier: Courier | null; onHide: () => void; onPatch: (url?: string, data?: Record<string, string>, confirmation?: string) => void; onWarn: (courier: Courier) => void; onResetPassword: (courier: Courier) => void; onEdit: (courier: Courier) => void }) {
  const uploadDocument = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!courier?.actions?.uploadDocumentUrl) return;
    router.post(courier.actions.uploadDocumentUrl, new FormData(event.currentTarget), { preserveScroll: true });
  };
  return <Modal show={!!courier} onHide={onHide} size="xl" centered dialogClassName="kc-sheet"><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{courier?.name}</Modal.Title></Modal.Header><Modal.Body>{!courier ? null : <div className="row g-3">
    <Info title="Asosiy ma'lumotlar" rows={[['Telefon', courier.phone || '—'], ['Hudud', courier.region || '—'], ['Ish faoliyati', courier.isOnline ? 'Online' : 'Offline'], ['Holat yangilangan', courier.availabilityUpdatedAt || '—'], ['Holat', courierLabel(courier.status)], ['Verifikatsiya', courier.verificationLabel || courier.verificationStatus || '—'], ['Ro‘yxatdan o‘tgan', courier.joined || '—'], ['Ogohlantirish', `${courier.warningCount || 0}/3`]]} />
    <Info title="Moliya" rows={[['Balans', `${fmt(courier.balance || 0)} so'm`], ['Rezerv', `${fmt(courier.reserved || 0)} so'm`], ['Daromad', `${fmt(courier.totalEarned || 0)} so'm`], ['Yechilgan', `${fmt(courier.totalWithdrawal || 0)} so'm`], ['Buyurtmalar', String(courier.orders || 0)], ['Karta', String(courier.payment?.card || '—')]]} />
    <Info title="Transport va shaxs" rows={[['Transport', courier.transportLabel || courier.transport || '—'], ['Avtomobil', [courier.vehicle, courier.vehicleColor].filter(Boolean).join(', ') || '—'], ['Raqam', courier.plate || '—'], ['INN', String(courier.identity?.inn || '—')], ['Tug‘ilgan sana', String(courier.identity?.birthdate || '—')], ['Pasport', String(courier.identity?.passport || '—')], ['Pasport berilgan', String(courier.identity?.passportIssuedAt || '—')], ['Guvohnoma', String(courier.identity?.license || '—')], ['Guvohnoma tugaydi', String(courier.identity?.licenseExpiresAt || '—')], ['Qolgan kun', String(courier.identity?.licenseDaysRemaining ?? '—')]]} />
    <Info title="Lokatsiya va manzil" rows={[['Uy manzili', String(courier.payment?.homeAddress || '—')], ['Karta egasi', String(courier.payment?.cardHolder || '—')], ['Latitude', String(courier.location?.lat || '—')], ['Longitude', String(courier.location?.lon || '—')], ['Lokatsiya yangilangan', String(courier.location?.updatedAt || '—')], ['Tasdiqlangan sana', courier.verifiedAt || '—'], ['Verifikatsiya izohi', courier.verificationNotes || '—']]} />
    <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Joriy lokatsiya xaritasi</h6><div className="small text-muted mb-2">Kuryerning oxirgi yuborgan koordinatasi asosida ochiladi.</div><MapButtons mapLinks={(courier.location?.mapLinks || {}) as Record<string, string>} /></div></div>
    <ListBlock title="Oxirgi buyurtmalar" items={courier.recentOrders || []} render={(item) => <><strong>#{item.id} / ORD #{item.orderId} · {fmt(Number(item.amount || 0))} so'm</strong><span>{String(item.customer || 'Mijoz')} · {String(item.status || '—')} · {String(item.date || '—')}</span></>} />
    <ListBlock title="Tranzaksiyalar" items={courier.transactions || []} render={(item) => {
      const isExpense = item.type === 'expense' || item.category === 'penalty' || item.category === 'withdrawal' || item.category === 'reversal';
      return <><strong className={isExpense ? 'text-danger' : 'text-success'}>{isExpense ? '-' : '+'}{fmt(Number(item.net || item.amount || 0))} so'm · {String(item.status || '—')}</strong><span>{String(item.description || item.category || 'Tranzaksiya')} · {String(item.date || '—')}</span></>;
    }} />
    <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">Hujjatlar</h6><form onSubmit={uploadDocument} className="row g-2 mb-3"><div className="col-md-3"><select name="type" className="form-select form-select-sm" required><option value="passport">Pasport</option><option value="driver_license">Haydovchi guvohnomasi</option><option value="vehicle_reg">Transport guvohnomasi</option><option value="vehicle_insurance">Sug'urta polisi</option><option value="inn_certificate">STIR guvohnomasi</option><option value="medical_cert">Tibbiy ma'lumotnoma</option><option value="photo_with_passport">Pasport bilan selfi</option><option value="other">Boshqa</option></select></div><div className="col-md-4"><input type="file" name="file" className="form-control form-control-sm" accept=".pdf,image/*" required /></div><div className="col-md-3"><input name="description" className="form-control form-control-sm" placeholder="Izoh" /></div><div className="col-md-2"><Button size="sm" type="submit">Yuklash</Button></div></form>{(courier.documents || []).map((item) => <div className="d-flex justify-content-between align-items-center border-top py-2 gap-2" key={String(item.id)}><div><strong>{String(item.typeLabel || item.type || 'Hujjat')}</strong><div className="small text-muted">{String(item.name || '—')} · {String(item.date || '—')}</div><div className="small">{String(item.description || '')}</div></div><div className="d-flex gap-2">{item.url ? <a href={String(item.url)} target="_blank" rel="noreferrer" className="btn btn-sm btn-light">Ko'rish</a> : null}{item.deleteUrl ? <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => confirm("Hujjat o'chirilsinmi?") && router.delete(String(item.deleteUrl), { preserveScroll: true })}><i className="bi bi-trash"></i></button> : null}</div></div>)}{(courier.documents || []).length === 0 ? <div className="text-muted small">Hujjat topilmadi</div> : null}</div></div>
    <ListBlock title={`Ogohlantirishlar (${courier.warningCount || 0}/3)`} items={courier.banLogs || []} render={(item) => <><strong>{String(item.title || '—')}</strong><span>{String(item.message || '')} · {String(item.date || '—')}</span></>} />
  </div>}</Modal.Body><Modal.Footer>{courier ? <Button variant="outline-warning" onClick={() => onWarn(courier)}>Ogohlantirish</Button> : null}{courier ? <Button variant="outline-primary" onClick={() => onEdit(courier)}>Tahrirlash</Button> : null}{courier ? <Button variant="outline-secondary" onClick={() => onResetPassword(courier)}>Parol reset</Button> : null}{courier?.status !== 'approved' ? <Button variant="outline-success" onClick={() => onPatch(courier?.actions?.approveUrl, {}, 'Kuryer tasdiqlansinmi?')}>Tasdiqlash</Button> : null}<Button variant="outline-danger" onClick={() => onPatch(courier?.actions?.rejectUrl, {}, 'Kuryer rad etilsinmi?')}>Rad etish</Button>{courier?.status === 'blocked' ? <Button variant="outline-primary" onClick={() => onPatch(courier?.actions?.unblockUrl, { message: 'Admin tomonidan blokdan chiqarildi.' }, 'Kuryer blokdan chiqarilsinmi?')}>Blokdan chiqarish</Button> : null}<Button variant="light" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function OrderModal({ order, statuses, onHide, onPatch }: { order: CourierOrder | null; statuses: Record<string, StatusMeta>; onHide: () => void; onPatch: (url?: string, data?: Record<string, string>) => void }) {
  const [penaltyReason, setPenaltyReason] = useState('late_delivery');
  const activePenalty = (order?.penaltyRules || []).find((rule) => rule.key === penaltyReason) || order?.penaltyRules?.[0];
  const submitPenalty = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!order?.penaltyUrl || !activePenalty) return;
    if (!confirm(`${order.courier} balansidan ${fmt(activePenalty.amount)} so'm jarima yechilsinmi?`)) return;
    router.post(order.penaltyUrl, new FormData(event.currentTarget), { preserveScroll: true });
  };

  return <Modal show={!!order} onHide={onHide} size="xl" centered dialogClassName="kc-sheet"><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Kuryer order #{order?.id}</Modal.Title></Modal.Header><Modal.Body>{!order ? null : <div className="row g-3">
    <Info title="Yetkazma" rows={[['Asosiy order', `#${order.orderId || '—'}`], ['Kuryer', order.courier], ['Kuryer telefoni', order.courierPhone || '—'], ['Hudud', order.courierRegion || '—'], ['Mijoz', order.customer], ['Mijoz telefoni', order.customerPhone || '—']]} />
    <Info title="Hisob-kitob" rows={[['Yetkazma summasi', `${fmt(order.amount)} so'm`], ['Order summasi', `${fmt(order.mainOrderAmount || 0)} so'm`], ['Kuryer ulushi', `${fmt(order.courierPrice || 0)} so'm`], ['Bonus', `${fmt(order.bonus || 0)} so'm`], ['Jami payout', `${fmt((order.courierPrice || 0) + (order.bonus || 0))} so'm`], ['Settled', `${fmt(order.settledAmount || 0)} so'm`]]} />
    <Info title="Km payout breakdown" rows={[['Masofa', `${Number(order.taskDistanceKm || 0).toFixed(2)} km`], ['Leg', order.taskLeg || '—'], ['Bazaviy haq', `${fmt(order.taskBaseFeeAmount || 0)} so'm`], ['Km haqi', `${fmt(order.taskDistanceFeeAmount || 0)} so'm`], ['Masofa bonusi', `${fmt(order.taskBonusAmount || 0)} so'm`], ['Task jami', `${fmt(order.taskFeeAmount || 0)} so'm`]]} />
    <Info title="Jarayon" rows={[['Holat', order.statusLabel || order.status], ['To‘lov', order.paymentStatus || '—'], ['Yetkazish turi', order.deliveryType || '—'], ['Olingan vaqt', order.pickedUpAt || '—'], ['Kutish rejimi', 'O‘chirilgan'], ['Kechikish', 'Hisoblanmaydi']]} />
    <Info title="Manzil" rows={[['Qabul qiluvchi', order.address?.fullName || '—'], ['Telefon', order.address?.phone || '—'], ['Viloyat', order.address?.region || '—'], ['Tuman', order.address?.district || '—'], ['Ko‘cha', order.address?.street || '—'], ['Uy', order.address?.home || '—']]} />
    <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-2">Xaritada ochish</h6><MapButtons mapLinks={(order.address?.mapLinks || {}) as Record<string, string>} /></div></div>
    <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">Mahsulotlar</h6>{(order.items || []).map((item, index) => <div className="d-flex justify-content-between border-bottom py-2" key={`${item.name}-${index}`}><div><strong>{item.name}</strong><div className="small text-muted">{item.author || item.type || '—'}</div></div><div className="text-end">{item.quantity} x {fmt(item.price)}<div className="fw-semibold">{fmt(item.quantity * item.price)} so'm</div></div></div>)}{(order.items || []).length === 0 ? <div className="text-muted">Mahsulot topilmadi</div> : null}</div></div>
    <div className="col-12"><div className="detail-panel border border-danger-subtle bg-danger-subtle bg-opacity-10"><div className="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><h6 className="fw-bold mb-1 text-danger"><i className="bi bi-shield-exclamation me-2"></i>Kuryerga jarima qo‘llash</h6><div className="small text-muted">Jarima mijoz shikoyati va order holatiga qarab avtomatik hisoblanadi. Tasdiqlansa kuryer balansidan yechiladi.</div></div>{activePenalty ? <span className="chip chip-danger">{fmt(activePenalty.amount)} so'm</span> : null}</div><form onSubmit={submitPenalty} className="row g-2"><div className="col-md-5"><label className="form-label small fw-semibold">Shikoyat sababi</label><select name="reason" className="form-select" value={activePenalty?.key || penaltyReason} onChange={(event) => setPenaltyReason(event.target.value)}>{(order.penaltyRules || []).map((rule) => <option key={rule.key} value={rule.key}>{rule.label} · {fmt(rule.amount)} so'm</option>)}</select></div><div className="col-md-7"><label className="form-label small fw-semibold">Admin izohi</label><input name="note" className="form-control" placeholder="Mijoz shikoyati, dalil yoki operator izohi" /></div>{activePenalty ? <div className="col-12"><div className="small text-muted">{activePenalty.description}</div></div> : null}<div className="col-12"><Button type="submit" variant="outline-danger" disabled={!order.penaltyUrl || !activePenalty}><i className="bi bi-cash-coin me-2"></i>Jarimani qo‘llash</Button></div></form></div></div>
  </div>}</Modal.Body><Modal.Footer>{order ? <select className="form-select" style={{ maxWidth: 280 }} value={order.status} onChange={(e) => onPatch(order.statusUrl, { status: e.target.value })}>{Object.entries(statuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}</select> : null}<Button variant="light" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function Avatar({ row }: { row: Courier }) {
  return <div className="resource-avatar">{row.photo ? <img src={row.photo} alt="" /> : row.name.slice(0, 2).toUpperCase()}</div>;
}
function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">{title}</h6><div className="row g-2">{rows.map(([label, value]) => <div className="col-sm-6" key={label}><small className="text-muted d-block">{label}</small><span className="fw-semibold">{value}</span></div>)}</div></div></div>;
}
function ListBlock({ title, items, render }: { title: string; items: AnyRow[]; render: (item: AnyRow) => JSX.Element }) {
  return <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">{title}</h6>{items.map((item, index) => <div className="border-bottom py-2 d-flex flex-column" key={String(item.id || index)}>{render(item)}</div>)}{items.length === 0 ? <div className="text-muted small">Ma'lumot topilmadi</div> : null}</div></div>;
}

function CourierEditModal({ courier, onHide }: { courier: Courier | null; onHide: () => void }) {
  const submit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!courier?.actions?.updateUrl) return;
    const form = new FormData(event.currentTarget);
    form.append('_method', 'put');
    router.post(courier.actions.updateUrl, form, { preserveScroll: true, onSuccess: onHide });
  };

  return <Modal show={!!courier} onHide={onHide} centered size="xl"><form onSubmit={submit}><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Kuryer tahrirlash</Modal.Title></Modal.Header><Modal.Body><div className="row g-3">
    <SectionTitle title="Asosiy ma'lumotlar" />
    <FormInput name="first_name" label="Ism" defaultValue={courier?.firstName || courier?.name?.split(' ')[0]} required />
    <FormInput name="last_name" label="Familiya" defaultValue={courier?.lastName || courier?.name?.split(' ').slice(1).join(' ')} required />
    <FormInput name="phone_number" label="Telefon" defaultValue={courier?.phone} required />
    <FormInput name="region" label="Hudud" defaultValue={courier?.region} required />
    <div className="col-md-6"><label className="form-label">Status</label><select name="status" defaultValue={courier?.status || 'pending'} className="form-select"><option value="pending">Kutilmoqda</option><option value="approved">Faol</option><option value="rejected">Rad etilgan</option><option value="blocked">Bloklangan</option></select></div>
    <FormInput name="balance" label="Balans" type="number" defaultValue={courier?.balance} />
    <FormInput name="password" label="Yangi parol" type="password" />
    <FormInput name="photo" label="Profil rasmi" type="file" />
    <FormInput name="birthdate" label="Tug'ilgan sana" type="date" defaultValue={String(courier?.identity?.birthdate || '')} />
    <FormInput name="home_address" label="Uy manzili" defaultValue={String(courier?.payment?.homeAddress || '')} />
    <SectionTitle title="Transport va to'lov" />
    <div className="col-md-6"><label className="form-label">Transport</label><select name="transport_type" defaultValue={courier?.transport || ''} className="form-select"><option value="">Tanlanmagan</option><option value="foot">Piyoda</option><option value="bicycle">Velosiped</option><option value="motorcycle">Mototsikl</option><option value="car">Avto</option></select></div>
    <FormInput name="vehicle_brand" label="Brend" defaultValue={courier?.vehicleBrand} />
    <FormInput name="vehicle_model" label="Model" defaultValue={courier?.vehicleModel} />
    <FormInput name="vehicle_color" label="Rang" defaultValue={courier?.vehicleColor} />
    <FormInput name="vehicle_plate_number" label="Raqam" defaultValue={courier?.plate} />
    <FormInput name="inn" label="INN" defaultValue={String(courier?.identity?.inn || '')} />
    <FormInput name="payment_card" label="Karta" defaultValue={String(courier?.payment?.rawCard || '')} />
    <FormInput name="card_holder" label="Karta egasi" defaultValue={String(courier?.payment?.cardHolder || '')} />
    <SectionTitle title="Pasport va haydovchilik guvohnomasi" />
    <FormInput name="passport_series" label="Pasport seriyasi" defaultValue={String(courier?.identity?.passport || '').split(' ')[0]} />
    <FormInput name="passport_number" label="Pasport raqami" defaultValue={String(courier?.identity?.passport || '').split(' ').slice(1).join(' ')} />
    <FormInput name="passport_issued_by" label="Pasport kim tomonidan berilgan" defaultValue={String(courier?.identity?.passportIssuedBy || '')} />
    <FormInput name="passport_issued_at" label="Pasport berilgan sana" type="date" defaultValue={String(courier?.identity?.passportIssuedAt || '')} />
    <FormInput name="driver_license_number" label="Guvohnoma raqami" defaultValue={String(courier?.identity?.license || '')} />
    <FormInput name="driver_license_issued_at" label="Guvohnoma berilgan sana" type="date" defaultValue={String(courier?.identity?.licenseIssuedAt || '')} />
    <FormInput name="driver_license_expires_at" label="Guvohnoma tugash sanasi" type="date" defaultValue={String(courier?.identity?.licenseExpiresAt || '')} />
    <SectionTitle title="Verifikatsiya" />
    <div className="col-md-6"><label className="form-label">Verifikatsiya</label><select name="verification_status" defaultValue={courier?.verificationStatus || 'unverified'} className="form-select"><option value="unverified">Unverified</option><option value="pending">Pending</option><option value="verified">Verified</option><option value="rejected">Rejected</option></select></div>
    <div className="col-12"><label className="form-label">Verifikatsiya izohi</label><textarea name="verification_notes" defaultValue={courier?.verificationNotes || ''} className="form-control" rows={2}></textarea></div>
  </div></Modal.Body><Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">Saqlash</Button></Modal.Footer></form></Modal>;
}

function FormInput({ name, label, defaultValue, required, type = 'text' }: { name: string; label: string; defaultValue?: string | number | null; required?: boolean; type?: string }) {
  return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} type={type} defaultValue={defaultValue ?? ''} required={required} className="form-control" /></div>;
}

function SectionTitle({ title }: { title: string }) {
  return <div className="col-12 mt-4"><h6 className="fw-bold mb-0">{title}</h6></div>;
}

function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted small">Xarita linki yo'q</span>;

  return (
    <div className="d-flex gap-2 flex-wrap mt-2">
      {mapLinks.google ? <a className="btn btn-sm btn-light" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="bi bi-geo-alt me-1"></i>Google Map</a> : null}
      {mapLinks.yandex ? <a className="btn btn-sm btn-light" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="bi bi-map me-1"></i>Yandex Map</a> : null}
    </div>
  );
}
