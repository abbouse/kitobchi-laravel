import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Modal } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);
type Counts = Record<string, number>;
type StatusMeta = { label: string; badge?: string };
type AnyRow = Record<string, string | number | boolean | null | undefined>;

interface Courier {
  id: number;
  name: string;
  phone?: string;
  photo?: string;
  region?: string;
  status?: string;
  verificationStatus?: string;
  verificationLabel?: string;
  verificationNotes?: string;
  verifiedAt?: string;
  transport?: string;
  transportLabel?: string;
  vehicle?: string;
  vehicleColor?: string;
  plate?: string;
  balance?: number;
  reserved?: number;
  totalWithdrawal?: number;
  totalEarned?: number;
  orders?: number;
  warningCount?: number;
  joined?: string;
  location?: AnyRow;
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
  pickupBonus?: number;
  settledAmount?: number;
  settledAt?: string;
  pickedUpAt?: string;
  slaDeadline?: string;
  delaySeconds?: number;
  customerDelay?: boolean;
  deliveryPrice?: number;
  deliveryType?: string;
  paymentStatus?: string;
  status: string;
  statusLabel?: string;
  statusBadge?: string;
  date?: string;
  address?: Record<string, string | null | undefined>;
  summary?: { itemsCount?: number; itemsTotal?: number };
  items?: Array<{ name: string; type?: string; quantity: number; price: number; author?: string | null }>;
  statusUrl?: string;
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
  const [selectedOrder, setSelectedOrder] = useState<CourierOrder | null>(null);

  const totalBalance = useMemo(() => couriers.reduce((sum, courier) => sum + (courier.balance || 0), 0), [couriers]);
  const orderTabs = [{ key: 'all', label: 'Barchasi' }, ...Object.entries(courierOrderStatuses).map(([key, meta]) => ({ key, label: meta.label }))];
  const load = (extra: Record<string, string | number> = {}) => router.get('/boshqaruv/couriers', { couriers_page: courierPagination.page, couriers_tab: courierTab, couriers_search: courierSearch, courier_orders_page: courierOrderPagination.page, courier_orders_tab: orderTab, courier_orders_search: orderSearch, ...extra }, { preserveState: true, preserveScroll: true, replace: true });

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
      <div className="page-head"><div><h1 className="page-title">Kuryerlar</h1><p className="page-subtitle">Kuryer profillari, verifikatsiya, balans va yetkazmalar</p></div></div>
      <div className="row g-3 mb-4">
        {[
          ['Kutilmoqda', courierCounts.pending || 0, 'bi-hourglass-split', '#f59e0b'],
          ['Faol kuryer', courierCounts.approved || 0, 'bi-bicycle', '#10b981'],
          ["Yo'ldagi order", courierOrderCounts.in_delivery || 0, 'bi-truck', '#2563eb'],
          ['Kuryer balansi', `${fmt(totalBalance)} so'm`, 'bi-wallet2', '#7c3aed'],
        ].map(([label, value, icon, color]) => <div className="col-xl-3 col-md-6" key={String(label)}><div className="stat-card"><div className="d-flex align-items-center gap-3"><div className="stat-icon" style={{ background: String(color) }}><i className={`bi ${icon}`}></i></div><div><div className="stat-value">{value}</div><div className="stat-label">{label}</div></div></div></div></div>)}
      </div>

      <div className="card-panel mb-4">
        <div className="panel-head"><div><div className="panel-title">Kuryerlar jadvali</div><small className="text-muted">{courierPagination.total} ta kuryer topildi</small></div><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={courierSearch} onChange={(e) => setCourierSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ couriers_page: 1 })} placeholder="Ism, telefon, hudud yoki raqam" /></div>
        <div className="d-flex flex-wrap gap-2 mb-3">{courierTabs.map((tab) => <button key={tab.key} className={`btn btn-sm ${courierTab === tab.key ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { setCourierTab(tab.key); load({ couriers_page: 1, couriers_tab: tab.key }); }}>{tab.label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(courierCounts[tab.key] || 0)}</span></button>)}</div>
        <div className="table-responsive"><table className="data-table"><thead><tr><th>ID</th><th>Kuryer</th><th>Hudud</th><th>Transport</th><th>Buyurtma</th><th>Balans</th><th>Ogohlantirish</th><th>Holat</th><th>Amallar</th></tr></thead><tbody>
          {couriers.map((courier) => <tr key={courier.id}>
            <td className="fw-semibold text-primary">#{courier.id}</td>
            <td><div className="d-flex align-items-center gap-2"><Avatar row={courier} /><div><div className="fw-semibold">{courier.name}</div><small className="text-muted">{courier.phone || '—'}</small></div></div></td>
            <td>{courier.region || '—'}</td><td><div>{courier.transportLabel || courier.transport || '—'}</div><small className="text-muted">{courier.plate || courier.vehicle}</small></td>
            <td><span className="chip chip-info">{courier.orders || 0}</span></td><td>{fmt(courier.balance || 0)} so'm</td>
            <td><span className={`chip ${(courier.warningCount || 0) > 0 ? 'chip-warning' : 'chip-gray'}`}>{courier.warningCount || 0}/3</span></td>
            <td><span className={`chip ${chip(courier.status)}`}>{courierLabel(courier.status)}</span></td>
            <td><button className="btn btn-sm btn-light me-1" onClick={() => setSelectedCourier(courier)}><i className="bi bi-eye"></i></button>{courier.status !== 'approved' ? <button className="btn btn-sm btn-light me-1" onClick={() => patch(courier.actions?.approveUrl, {}, 'Kuryer tasdiqlansinmi?')}><i className="bi bi-check2-circle"></i></button> : null}<button className="btn btn-sm btn-light text-warning" onClick={() => warn(courier)}><i className="bi bi-exclamation-triangle"></i></button></td>
          </tr>)}{courierPagination.total === 0 ? <tr><td colSpan={9} className="text-center text-muted py-5">Kuryer topilmadi</td></tr> : null}
        </tbody></table></div><PaginationControls {...courierPagination} onPageChange={(page) => load({ couriers_page: page })} />
      </div>

      <div className="card-panel">
        <div className="panel-head"><div><div className="panel-title">Kuryer buyurtmalari</div><small className="text-muted">{courierOrderPagination.total} ta yozuv topildi</small></div><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={orderSearch} onChange={(e) => setOrderSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ courier_orders_page: 1 })} placeholder="Order, kuryer yoki mijoz" /></div>
        <div className="d-flex flex-wrap gap-2 mb-3">{orderTabs.map((tab) => <button key={tab.key} className={`btn btn-sm ${orderTab === tab.key ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { setOrderTab(tab.key); load({ courier_orders_page: 1, courier_orders_tab: tab.key }); }}>{tab.label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(courierOrderCounts[tab.key] || 0)}</span></button>)}</div>
        <div className="table-responsive"><table className="data-table"><thead><tr><th>ID</th><th>Kuryer</th><th>Mijoz</th><th>Summa</th><th>To'lov</th><th>Holat</th><th>Sana</th><th>Amallar</th></tr></thead><tbody>
          {courierOrders.map((order) => <tr key={order.id}><td><strong>#{order.id}</strong><small className="d-block text-muted">ORD #{order.orderId || '—'}</small></td><td><div className="fw-semibold">{order.courier}</div><small className="text-muted">{order.courierPhone || '—'}</small></td><td><div>{order.customer}</div><small className="text-muted">{order.customerPhone || '—'}</small></td><td><strong>{fmt(order.amount)} so'm</strong><small className="d-block text-muted">Ulush: {fmt((order.courierPrice || 0) + (order.bonus || 0))}</small></td><td>{order.paymentStatus || '—'}</td><td><select className={`form-select form-select-sm ${chip(order.status, order.statusBadge)}`} value={order.status} onChange={(e) => patch(order.statusUrl, { status: e.target.value })}>{Object.entries(courierOrderStatuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}</select></td><td className="text-muted">{order.date || '—'}</td><td><button className="btn btn-sm btn-light" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button></td></tr>)}
          {courierOrderPagination.total === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Buyurtma topilmadi</td></tr> : null}
        </tbody></table></div><PaginationControls {...courierOrderPagination} onPageChange={(page) => load({ courier_orders_page: page })} />
      </div>
      <CourierModal courier={selectedCourier} onHide={() => setSelectedCourier(null)} onPatch={patch} onWarn={warn} onResetPassword={resetPassword} />
      <OrderModal order={selectedOrder} statuses={courierOrderStatuses} onHide={() => setSelectedOrder(null)} onPatch={patch} />
    </div>
  );
}

function CourierModal({ courier, onHide, onPatch, onWarn, onResetPassword }: { courier: Courier | null; onHide: () => void; onPatch: (url?: string, data?: Record<string, string>, confirmation?: string) => void; onWarn: (courier: Courier) => void; onResetPassword: (courier: Courier) => void }) {
  return <Modal show={!!courier} onHide={onHide} size="xl" centered><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{courier?.name}</Modal.Title></Modal.Header><Modal.Body>{!courier ? null : <div className="row g-3">
    <Info title="Asosiy ma'lumotlar" rows={[['Telefon', courier.phone || '—'], ['Hudud', courier.region || '—'], ['Holat', courierLabel(courier.status)], ['Verifikatsiya', courier.verificationLabel || courier.verificationStatus || '—'], ['Ro‘yxatdan o‘tgan', courier.joined || '—'], ['Ogohlantirish', `${courier.warningCount || 0}/3`]]} />
    <Info title="Moliya" rows={[['Balans', `${fmt(courier.balance || 0)} so'm`], ['Rezerv', `${fmt(courier.reserved || 0)} so'm`], ['Daromad', `${fmt(courier.totalEarned || 0)} so'm`], ['Yechilgan', `${fmt(courier.totalWithdrawal || 0)} so'm`], ['Buyurtmalar', String(courier.orders || 0)], ['Karta', String(courier.payment?.card || '—')]]} />
    <Info title="Transport va shaxs" rows={[['Transport', courier.transportLabel || courier.transport || '—'], ['Avtomobil', [courier.vehicle, courier.vehicleColor].filter(Boolean).join(', ') || '—'], ['Raqam', courier.plate || '—'], ['INN', String(courier.identity?.inn || '—')], ['Pasport', String(courier.identity?.passport || '—')], ['Guvohnoma', String(courier.identity?.license || '—')]]} />
    <Info title="Lokatsiya va manzil" rows={[['Uy manzili', String(courier.payment?.homeAddress || '—')], ['Karta egasi', String(courier.payment?.cardHolder || '—')], ['Latitude', String(courier.location?.lat || '—')], ['Longitude', String(courier.location?.lon || '—')], ['Lokatsiya yangilangan', String(courier.location?.updatedAt || '—')], ['Verifikatsiya izohi', courier.verificationNotes || '—']]} />
    <ListBlock title="Oxirgi buyurtmalar" items={courier.recentOrders || []} render={(item) => <><strong>#{item.id} / ORD #{item.orderId} · {fmt(Number(item.amount || 0))} so'm</strong><span>{String(item.customer || 'Mijoz')} · {String(item.status || '—')} · {String(item.date || '—')}</span></>} />
    <ListBlock title="Tranzaksiyalar" items={courier.transactions || []} render={(item) => <><strong>{fmt(Number(item.net || item.amount || 0))} so'm · {String(item.status || '—')}</strong><span>Komissiya: {fmt(Number(item.commission || 0))} · {String(item.date || '—')}</span></>} />
    <ListBlock title="Hujjatlar" items={courier.documents || []} render={(item) => <><strong>{String(item.type || 'Hujjat')} · {String(item.name || '—')}</strong><span>{String(item.description || '')} {item.size ? `· ${item.size} KB` : ''}</span></>} />
    <ListBlock title={`Ogohlantirishlar (${courier.warningCount || 0}/3)`} items={courier.banLogs || []} render={(item) => <><strong>{String(item.title || '—')}</strong><span>{String(item.message || '')} · {String(item.date || '—')}</span></>} />
  </div>}</Modal.Body><Modal.Footer>{courier ? <Button variant="outline-warning" onClick={() => onWarn(courier)}>Ogohlantirish</Button> : null}{courier ? <Button variant="outline-secondary" onClick={() => onResetPassword(courier)}>Parol reset</Button> : null}{courier?.status !== 'approved' ? <Button variant="outline-success" onClick={() => onPatch(courier?.actions?.approveUrl, {}, 'Kuryer tasdiqlansinmi?')}>Tasdiqlash</Button> : null}<Button variant="outline-danger" onClick={() => onPatch(courier?.actions?.rejectUrl, {}, 'Kuryer rad etilsinmi?')}>Rad etish</Button>{courier?.status === 'blocked' ? <Button variant="outline-primary" onClick={() => onPatch(courier?.actions?.unblockUrl, { message: 'Admin tomonidan blokdan chiqarildi.' }, 'Kuryer blokdan chiqarilsinmi?')}>Blokdan chiqarish</Button> : null}<Button variant="light" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function OrderModal({ order, statuses, onHide, onPatch }: { order: CourierOrder | null; statuses: Record<string, StatusMeta>; onHide: () => void; onPatch: (url?: string, data?: Record<string, string>) => void }) {
  return <Modal show={!!order} onHide={onHide} size="xl" centered><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Kuryer order #{order?.id}</Modal.Title></Modal.Header><Modal.Body>{!order ? null : <div className="row g-3">
    <Info title="Yetkazma" rows={[['Asosiy order', `#${order.orderId || '—'}`], ['Kuryer', order.courier], ['Kuryer telefoni', order.courierPhone || '—'], ['Hudud', order.courierRegion || '—'], ['Mijoz', order.customer], ['Mijoz telefoni', order.customerPhone || '—']]} />
    <Info title="Hisob-kitob" rows={[['Yetkazma summasi', `${fmt(order.amount)} so'm`], ['Order summasi', `${fmt(order.mainOrderAmount || 0)} so'm`], ['Kuryer ulushi', `${fmt(order.courierPrice || 0)} so'm`], ['Bonus', `${fmt(order.bonus || 0)} so'm`], ['Pickup bonus', `${fmt(order.pickupBonus || 0)} so'm`], ['Settled', `${fmt(order.settledAmount || 0)} so'm`]]} />
    <Info title="Jarayon" rows={[['Holat', order.statusLabel || order.status], ['To‘lov', order.paymentStatus || '—'], ['Yetkazish turi', order.deliveryType || '—'], ['Olingan vaqt', order.pickedUpAt || '—'], ['SLA deadline', order.slaDeadline || '—'], ['Kechikish', `${order.delaySeconds || 0} soniya`]]} />
    <Info title="Manzil" rows={[['Qabul qiluvchi', order.address?.fullName || '—'], ['Telefon', order.address?.phone || '—'], ['Viloyat', order.address?.region || '—'], ['Tuman', order.address?.district || '—'], ['Ko‘cha', order.address?.street || '—'], ['Uy', order.address?.home || '—']]} />
    <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">Mahsulotlar</h6>{(order.items || []).map((item, index) => <div className="d-flex justify-content-between border-bottom py-2" key={`${item.name}-${index}`}><div><strong>{item.name}</strong><div className="small text-muted">{item.author || item.type || '—'}</div></div><div className="text-end">{item.quantity} x {fmt(item.price)}<div className="fw-semibold">{fmt(item.quantity * item.price)} so'm</div></div></div>)}{(order.items || []).length === 0 ? <div className="text-muted">Mahsulot topilmadi</div> : null}</div></div>
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
