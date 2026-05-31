import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type StatusMeta = { label: string; badge?: string };
type Counts = Record<string, number>;

interface Seller {
  id: number;
  name: string;
  shopName?: string;
  firstName?: string;
  lastName?: string;
  ownerName?: string;
  legalName?: string;
  phone?: string;
  photo?: string;
  region?: string;
  district?: string;
  address?: string;
  status?: string;
  verified?: boolean;
  hidden?: boolean;
  premium?: boolean;
  premiumExpiresAt?: string;
  rating?: number;
  ratingReviewsCount?: number;
  reputationScore?: number;
  balance?: number;
  totalRevenue?: number;
  commissionRate?: number;
  products?: number;
  books?: number;
  stationeries?: number;
  orders?: number;
  warningCount?: number;
  legal?: Record<string, string | null | undefined>;
  bank?: Record<string, string | null | undefined>;
  contract?: Record<string, string | number | boolean | null | undefined>;
  locations?: Array<Record<string, string | number | boolean | null | undefined | Record<string, string>>>;
  recentOrders?: Array<Record<string, string | number | null | undefined>>;
  transactions?: Array<Record<string, string | number | null | undefined>>;
  banLogs?: Array<Record<string, string | number | boolean | null | undefined>>;
  actions?: Record<string, string>;
}

interface SellerOrder {
  id: number;
  orderId?: number;
  seller: string;
  sellerOwner?: string;
  sellerPhone?: string;
  customer: string;
  customerPhone?: string;
  courier?: string;
  courierPhone?: string;
  amount: number;
  mainOrderAmount?: number;
  deliveryPrice?: number;
  deliveryType?: string;
  status: string;
  statusLabel?: string;
  statusBadge?: string;
  acceptedAt?: string;
  date?: string;
  address?: Record<string, string | null | undefined | Record<string, string>>;
  summary?: { itemsCount?: number; itemsTotal?: number };
  items?: Array<{ name: string; type?: string; quantity: number; price: number; author?: string | null }>;
  statusUrl?: string;
}

const sellerTabs = [
  { key: 'pending', label: 'Kutilmoqda', icon: 'bi-hourglass-split' },
  { key: 'approved', label: 'Faol', icon: 'bi-shop' },
  { key: 'rejected', label: 'Bekor qilingan', icon: 'bi-x-octagon' },
  { key: 'blocked', label: 'Bloklangan', icon: 'bi-shield-lock' },
  { key: 'all', label: 'Barchasi', icon: 'bi-grid' },
];

const badgeClass = (badge?: string) => {
  if (badge === 'badge-success') return 'chip-success';
  if (badge === 'badge-danger') return 'chip-danger';
  if (badge === 'badge-warning') return 'chip-warning';
  if (badge === 'badge-info') return 'chip-info';
  return 'chip-gray';
};

const sellerChip = (status?: string) => {
  if (status === 'approved') return 'chip-success';
  if (status === 'pending') return 'chip-warning';
  if (status === 'rejected' || status === 'blocked') return 'chip-danger';
  return 'chip-gray';
};

const sellerLabel = (status?: string) => ({
  pending: 'Kutilmoqda',
  approved: 'Faol',
  rejected: 'Bekor qilingan',
  blocked: 'Bloklangan',
}[String(status || '')] || status || '—');

export default function SellerOrders() {
  const {
    sellers = [],
    sellerCounts = {},
    sellerOrders = [],
    sellerOrderCounts = {},
    sellerOrderStatuses = {},
    sellerPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    sellerOrderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    sellerFilters = {}, sellerOrderFilters = {},
  } = usePage<{
    sellers?: Seller[];
    sellerCounts?: Counts;
    sellerOrders?: SellerOrder[];
    sellerOrderCounts?: Counts;
    sellerOrderStatuses?: Record<string, StatusMeta>;
    sellerPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerOrderPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerFilters?: { tab?: string; search?: string }; sellerOrderFilters?: { tab?: string; search?: string };
  }>().props;

  const [sellerTab, setSellerTab] = useState(sellerFilters.tab || 'pending');
  const [orderTab, setOrderTab] = useState(sellerOrderFilters.tab || 'all');
  const [sellerSearch, setSellerSearch] = useState(sellerFilters.search || '');
  const [orderSearch, setOrderSearch] = useState(sellerOrderFilters.search || '');
  const [selectedSeller, setSelectedSeller] = useState<Seller | null>(null);
  const [editingSeller, setEditingSeller] = useState<Seller | null>(null);
  const [selectedOrder, setSelectedOrder] = useState<SellerOrder | null>(null);

  const totalBalance = useMemo(() => sellers.reduce((sum, seller) => sum + (seller.balance || 0), 0), [sellers]);
  const orderStatusTabs = [{ key: 'all', label: 'Barchasi' }, ...Object.entries(sellerOrderStatuses).map(([key, meta]) => ({ key, label: meta.label }))];
  const load = (extra: Record<string, string | number> = {}) => router.get('/boshqaruv/sellers', { sellers_page: sellerPagination.page, sellers_tab: sellerTab, sellers_search: sellerSearch, seller_orders_page: sellerOrderPagination.page, seller_orders_tab: orderTab, seller_orders_search: orderSearch, ...extra }, { preserveState: true, preserveScroll: true, replace: true });

  const runPatch = (url?: string, message?: string, payload: Record<string, string> = {}) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, payload, { preserveScroll: true });
  };

  const warnSeller = (seller: Seller) => {
    const title = prompt('Ogohlantirish sarlavhasi', 'Admin ogohlantirishi');
    if (!title) return;
    const message = prompt('Ogohlantirish matni', 'Iltimos, marketplace qoidalariga amal qiling.');
    if (!message) return;
    router.post(seller.actions?.warnUrl || '', { title, message }, { preserveScroll: true });
  };

  const resetPassword = (seller: Seller) => {
    if (!seller.actions?.resetPasswordUrl || !confirm(`${seller.name} uchun yangi parol SMS orqali yuborilsinmi?`)) return;
    router.post(seller.actions.resetPasswordUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Sellerlar</h1>
          <p className="page-subtitle">Seller moderatsiyasi, shartnoma, ogohlantirishlar va seller buyurtmalari</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Kutilmoqda', value: sellerCounts.pending || 0, icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Faol', value: sellerCounts.approved || 0, icon: 'bi-shop', color: '#10b981' },
          { label: 'Bekor qilingan', value: sellerCounts.rejected || 0, icon: 'bi-x-octagon', color: '#ef4444' },
          { label: "To'lanadigan balans", value: `${fmt(totalBalance)} so'm`, icon: 'bi-wallet2', color: '#7c3aed' },
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

      <div className="card-panel mb-4">
        <div className="panel-head">
          <div>
            <div className="panel-title">Sotuvchilar jadvali</div>
            <small className="text-muted">{sellerPagination.total} ta seller topildi</small>
          </div>
          <div className="d-flex flex-wrap gap-2">
            <input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={sellerSearch} onChange={(e) => setSellerSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ sellers_page: 1 })} placeholder="Do'kon, telefon yoki hudud" />
          </div>
        </div>
        <div className="d-flex flex-wrap gap-2 mb-3">
          {sellerTabs.map((item) => (
            <button key={item.key} className={`btn btn-sm ${sellerTab === item.key ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { setSellerTab(item.key); load({ sellers_page: 1, sellers_tab: item.key }); }}>
              <i className={`bi ${item.icon} me-1`}></i>{item.label}
              <span className="badge rounded-pill bg-light text-dark ms-2">{fmt(sellerCounts[item.key] || 0)}</span>
            </button>
          ))}
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Do'kon</th><th>Tel</th><th>Viloyat</th><th>Mahsulot</th><th>Buyurtma</th><th>Ogohlantirish</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {sellers.map((seller) => (
                <tr key={seller.id}>
                  <td className="fw-semibold text-primary">#{seller.id}</td>
                  <td>
                    <div className="d-flex align-items-center gap-2">
                      <div className="resource-avatar">{seller.photo ? <img src={seller.photo} alt="" /> : seller.name.slice(0, 2).toUpperCase()}</div>
                      <div style={{ minWidth: 0 }}>
                        <div className="fw-semibold text-truncate">{seller.name}</div>
                        <small className="text-muted text-truncate d-block">{seller.ownerName || seller.legalName || '—'}</small>
                      </div>
                    </div>
                  </td>
                  <td>{seller.phone || '—'}</td>
                  <td>{seller.region || '—'}</td>
                  <td><span className="chip chip-gray">{seller.products || 0}</span></td>
                  <td><span className="chip chip-info">{seller.orders || 0}</span></td>
                  <td><span className={`chip ${(seller.warningCount || 0) >= 3 ? 'chip-danger' : (seller.warningCount || 0) > 0 ? 'chip-warning' : 'chip-gray'}`}>{seller.warningCount || 0}/3</span></td>
                  <td><span className={`chip ${sellerChip(seller.status)}`}>{sellerLabel(seller.status)}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelectedSeller(seller)}><i className="bi bi-eye"></i></button>
                    {seller.status !== 'approved' ? <button className="btn btn-sm btn-light me-1" onClick={() => runPatch(seller.actions?.approveUrl, 'Seller tasdiqlansinmi?')}><i className="bi bi-check2-circle"></i></button> : null}
                    <button className="btn btn-sm btn-light me-1" onClick={() => runPatch(seller.actions?.rejectUrl, 'Seller bekor qilinsinmi?')}><i className="bi bi-x-circle"></i></button>
                    {seller.status === 'blocked' ? <button className="btn btn-sm btn-light me-1" onClick={() => runPatch(seller.actions?.unblockUrl, 'Seller blokdan chiqarilsinmi?', { message: 'Admin tomonidan blokdan chiqarildi.' })}><i className="bi bi-unlock"></i></button> : null}
                    <button className="btn btn-sm btn-light text-warning" onClick={() => warnSeller(seller)}><i className="bi bi-exclamation-triangle"></i></button>
                  </td>
                </tr>
              ))}
              {sellerPagination.total === 0 ? <tr><td colSpan={9} className="text-center text-muted py-5">Hech qanday seller topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...sellerPagination} onPageChange={(page) => load({ sellers_page: page })} />
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Seller buyurtmalari</div>
            <small className="text-muted">{sellerOrderPagination.total} ta yozuv topildi</small>
          </div>
          <input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={orderSearch} onChange={(e) => setOrderSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ seller_orders_page: 1 })} placeholder="ID, seller yoki mijoz" />
        </div>
        <div className="d-flex flex-wrap gap-2 mb-3">
          {orderStatusTabs.map((item) => (
            <button key={item.key} className={`btn btn-sm ${orderTab === item.key ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { setOrderTab(item.key); load({ seller_orders_page: 1, seller_orders_tab: item.key }); }}>
              {item.label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(sellerOrderCounts[item.key] || 0)}</span>
            </button>
          ))}
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Seller</th><th>Mijoz</th><th>Summa</th><th>Mahsulot</th><th>Holat</th><th>Sana</th><th>Amallar</th></tr></thead>
            <tbody>
              {sellerOrders.map((order) => (
                <tr key={order.id}>
                  <td><div className="fw-bold">#{order.id}</div><small className="text-muted">ORD #{order.orderId || '—'}</small></td>
                  <td><div className="fw-semibold">{order.seller}</div><small className="text-muted">{order.sellerPhone || order.sellerOwner || '—'}</small></td>
                  <td><div>{order.customer}</div><small className="text-muted">{order.customerPhone || '—'}</small></td>
                  <td><div className="fw-semibold">{fmt(order.amount)} so'm</div><small className="text-muted">{order.deliveryType || '—'}</small></td>
                  <td>{order.summary?.itemsCount || 0} ta</td>
                  <td>
                    <select className={`form-select form-select-sm ${badgeClass(order.statusBadge)}`} value={order.status} onChange={(e) => runPatch(order.statusUrl, undefined, { status: e.target.value })}>
                      {Object.entries(sellerOrderStatuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}
                    </select>
                  </td>
                  <td className="text-muted">{order.date || order.acceptedAt || '—'}</td>
                  <td><button className="btn btn-sm btn-light" onClick={() => setSelectedOrder(order)}><i className="bi bi-eye"></i></button></td>
                </tr>
              ))}
              {sellerOrderPagination.total === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Hech qanday buyurtma topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...sellerOrderPagination} onPageChange={(page) => load({ seller_orders_page: page })} />
      </div>

      <SellerModal seller={selectedSeller} onHide={() => setSelectedSeller(null)} onWarn={warnSeller} onResetPassword={resetPassword} onPatch={runPatch} onEdit={(seller) => setEditingSeller(seller)} />
      <SellerEditModal seller={editingSeller} onHide={() => setEditingSeller(null)} />
      <OrderModal order={selectedOrder} statuses={sellerOrderStatuses} onHide={() => setSelectedOrder(null)} onPatch={runPatch} />
    </div>
  );
}

function SellerModal({ seller, onHide, onWarn, onResetPassword, onPatch, onEdit }: {
  seller: Seller | null;
  onHide: () => void;
  onWarn: (seller: Seller) => void;
  onResetPassword: (seller: Seller) => void;
  onPatch: (url?: string, message?: string, payload?: Record<string, string>) => void;
  onEdit: (seller: Seller) => void;
}) {
  return (
    <Modal show={!!seller} onHide={onHide} centered size="xl">
      <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{seller?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {!seller ? null : (
          <div className="row g-3">
            <Info title="Asosiy ma'lumotlar" rows={[
              ['Egasi', seller.ownerName || '—'], ['Telefon', seller.phone || '—'], ['Hudud', [seller.region, seller.district].filter(Boolean).join(', ') || '—'],
              ['Status', sellerLabel(seller.status)], ['Reyting', `${seller.rating || 0} (${seller.ratingReviewsCount || 0})`], ['Reputatsiya', String(seller.reputationScore || 0)],
            ]} />
            <Info title="Moliya va mahsulot" rows={[
              ['Balans', `${fmt(seller.balance || 0)} so'm`], ['Tushum', `${fmt(seller.totalRevenue || 0)} so'm`], ['Komissiya', `${seller.commissionRate || 0}%`],
              ['Kitoblar', String(seller.books || 0)], ['Kanstovar', String(seller.stationeries || 0)], ['Buyurtmalar', String(seller.orders || 0)],
            ]} />
            <Info title="Shartnoma" rows={[
              ['Raqam', String(seller.contract?.number || '—')], ['Imzolangan', seller.contract?.signed ? 'Ha' : "Yo'q"], ['Holat', String(seller.contract?.status || '—')],
              ['Tugash sanasi', String(seller.contract?.expiresAt || '—')], ['Qolgan kun', String(seller.contract?.daysRemaining ?? '—')], ['Izoh', String(seller.contract?.notes || '—')],
            ]} />
            <Info title="Huquqiy va bank" rows={[
              ['Yuridik turi', String(seller.legal?.type || '—')], ['INN', String(seller.legal?.inn || '—')], ['Pasport', String(seller.legal?.passport || '—')],
              ['Bank', String(seller.bank?.name || '—')], ['Hisob', String(seller.bank?.account || '—')], ['Karta', String(seller.bank?.card || '—')],
            ]} />
            <ListBlock title="Filiallar" empty="Filial yo'q" items={seller.locations || []} render={(item) => <><strong>{String(item.address || '—')}</strong><span>{item.main ? 'Asosiy filial' : 'Filial'} · {String(item.description || '')}</span><MapButtons mapLinks={(item.mapLinks || {}) as Record<string, string>} /></>} />
            <ListBlock title="Oxirgi seller orderlar" empty="Order yo'q" items={seller.recentOrders || []} render={(item) => <><strong>#{item.id} · {fmt(Number(item.amount || 0))} so'm</strong><span>{String(item.customer || 'Mijoz')} · {String(item.date || '—')}</span></>} />
            <ListBlock title="Tranzaksiyalar" empty="Tranzaksiya yo'q" items={seller.transactions || []} render={(item) => <><strong>{fmt(Number(item.net || item.amount || 0))} so'm · {String(item.status || '—')}</strong><span>{String(item.category || item.type || '—')} · {String(item.date || '—')}</span></>} />
            <ListBlock title={`Ogohlantirishlar (${seller.warningCount || 0}/3)`} empty="Ogohlantirish yo'q" items={seller.banLogs || []} render={(item) => <><strong>{String(item.title || '—')}</strong><span>{String(item.message || '')} · {String(item.date || '—')}</span></>} />
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        {seller ? <Button variant="outline-warning" onClick={() => onWarn(seller)}>Ogohlantirish</Button> : null}
        {seller ? <Button variant="outline-primary" onClick={() => onEdit(seller)}>Tahrirlash</Button> : null}
        {seller ? <Button variant="outline-secondary" onClick={() => onResetPassword(seller)}>Parol reset</Button> : null}
        {seller?.status !== 'approved' ? <Button variant="outline-success" onClick={() => onPatch(seller?.actions?.approveUrl, 'Seller tasdiqlansinmi?')}>Tasdiqlash</Button> : null}
        <Button variant="outline-danger" onClick={() => onPatch(seller?.actions?.rejectUrl, 'Seller bekor qilinsinmi?')}>Bekor qilish</Button>
        {seller?.status === 'blocked' ? <Button variant="outline-primary" onClick={() => onPatch(seller?.actions?.unblockUrl, 'Seller blokdan chiqarilsinmi?', { message: 'Admin tomonidan blokdan chiqarildi.' })}>Blokdan chiqarish</Button> : null}
        <Button variant="light" onClick={onHide}>Yopish</Button>
      </Modal.Footer>
    </Modal>
  );
}

function OrderModal({ order, statuses, onHide, onPatch }: {
  order: SellerOrder | null;
  statuses: Record<string, StatusMeta>;
  onHide: () => void;
  onPatch: (url?: string, message?: string, payload?: Record<string, string>) => void;
}) {
  return (
    <Modal show={!!order} onHide={onHide} centered size="lg">
      <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Seller order #{order?.id}</Modal.Title></Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div className="row g-3">
            <Info title="Buyurtma" rows={[
              ['Asosiy order', `#${order.orderId || '—'}`], ['Seller', order.seller], ['Mijoz', order.customer],
              ['Telefon', order.customerPhone || '—'], ['Kuryer', order.courier || '—'], ['Sana', order.date || '—'],
            ]} />
            <Info title="Hisob-kitob" rows={[
              ['Seller summa', `${fmt(order.amount)} so'm`], ['Asosiy order summa', `${fmt(order.mainOrderAmount || 0)} so'm`],
              ['Yetkazish', `${fmt(order.deliveryPrice || 0)} so'm`], ['Yetkazish turi', order.deliveryType || '—'],
              ['Mahsulot', `${order.summary?.itemsCount || 0} ta`], ['Mahsulot jami', `${fmt(order.summary?.itemsTotal || 0)} so'm`],
            ]} />
            <Info title="Manzil" rows={[
              ['Qabul qiluvchi', order.address?.fullName || '—'], ['Telefon', order.address?.phone || '—'], ['Viloyat', order.address?.region || '—'],
              ['Tuman', order.address?.district || '—'], ['Ko‘cha', order.address?.street || '—'], ['Uy', order.address?.home || '—'],
            ]} />
            <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-2">Xaritada ochish</h6><MapButtons mapLinks={(order.address?.mapLinks || {}) as Record<string, string>} /></div></div>
            <div className="col-12">
              <div className="detail-panel">
                <h6 className="fw-bold mb-3">Mahsulotlar</h6>
                {(order.items || []).map((item, index) => (
                  <div className="d-flex justify-content-between border-bottom py-2" key={`${item.name}-${index}`}>
                    <div><strong>{item.name}</strong><div className="text-muted small">{item.author || item.type || '—'}</div></div>
                    <div className="text-end"><div>{item.quantity} x {fmt(item.price)}</div><strong>{fmt(item.quantity * item.price)} so'm</strong></div>
                  </div>
                ))}
                {(order.items || []).length === 0 ? <div className="text-muted">Mahsulotlar topilmadi</div> : null}
              </div>
            </div>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        {order ? (
          <select className="form-select" style={{ maxWidth: 260 }} value={order.status} onChange={(e) => onPatch(order.statusUrl, undefined, { status: e.target.value })}>
            {Object.entries(statuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}
          </select>
        ) : null}
        <Button variant="light" onClick={onHide}>Yopish</Button>
      </Modal.Footer>
    </Modal>
  );
}

function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return (
    <div className="col-xl-6">
      <div className="detail-panel h-100">
        <h6 className="fw-bold mb-3">{title}</h6>
        <div className="address-list">
          {rows.map(([label, val]) => <div key={label}><span>{label}</span><strong>{val}</strong></div>)}
        </div>
      </div>
    </div>
  );
}

function ListBlock<T>({ title, empty, items, render }: { title: string; empty: string; items: T[]; render: (item: T) => React.ReactNode }) {
  return (
    <div className="col-xl-6">
      <div className="detail-panel h-100">
        <h6 className="fw-bold mb-3">{title}</h6>
        <div className="d-grid gap-2">
          {items.map((item, index) => <div className="mini-stat" key={index}>{render(item)}</div>)}
          {items.length === 0 ? <div className="text-muted">{empty}</div> : null}
        </div>
      </div>
    </div>
  );
}

function SellerEditModal({ seller, onHide }: { seller: Seller | null; onHide: () => void }) {
  const submit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!seller?.actions?.updateUrl) return;
    const form = new FormData(event.currentTarget);
    form.append('_method', 'put');
    router.post(seller.actions.updateUrl, form, { preserveScroll: true, onSuccess: onHide });
  };

  return <Modal show={!!seller} onHide={onHide} centered size="lg"><form onSubmit={submit}><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Seller tahrirlash</Modal.Title></Modal.Header><Modal.Body><div className="row g-3">
    <FormInput name="shop_name" label="Do'kon nomi" defaultValue={seller?.shopName || seller?.name} required />
    <FormInput name="phone_number" label="Telefon" defaultValue={seller?.phone} required />
    <FormInput name="firstname" label="Ism" defaultValue={seller?.firstName} />
    <FormInput name="lastname" label="Familiya" defaultValue={seller?.lastName} />
    <FormInput name="region" label="Viloyat" defaultValue={seller?.region} required />
    <FormInput name="district" label="Tuman" defaultValue={seller?.district} />
    <div className="col-md-6"><label className="form-label">Status</label><select name="status" defaultValue={seller?.status || 'pending'} className="form-select"><option value="pending">Kutilmoqda</option><option value="approved">Faol</option><option value="rejected">Bekor qilingan</option><option value="blocked">Bloklangan</option></select></div>
    <FormInput name="balance" label="Balans" type="number" defaultValue={seller?.balance} />
    <FormInput name="commission_percent" label="Komissiya %" type="number" defaultValue={seller?.commissionRate} />
    <FormInput name="legal_type" label="Yuridik turi" defaultValue={String(seller?.legal?.type || '')} />
    <FormInput name="inn" label="INN" defaultValue={String(seller?.legal?.inn || '')} />
    <FormInput name="bank_name" label="Bank" defaultValue={String(seller?.bank?.name || '')} />
    <FormInput name="bank_account" label="Hisob raqam" defaultValue={String(seller?.bank?.rawAccount || '')} />
    <FormInput name="payment_card" label="Karta" defaultValue={String(seller?.bank?.rawCard || '')} />
    <FormInput name="card_holder" label="Karta egasi" defaultValue={String(seller?.bank?.cardHolder || '')} />
    <div className="col-12"><label className="form-label">Yuridik manzil</label><textarea name="legal_address" defaultValue={String(seller?.legal?.legalAddress || seller?.address || '')} className="form-control" rows={2}></textarea></div>
  </div></Modal.Body><Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">Saqlash</Button></Modal.Footer></form></Modal>;
}

function FormInput({ name, label, defaultValue, required, type = 'text' }: { name: string; label: string; defaultValue?: string | number | null; required?: boolean; type?: string }) {
  return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} type={type} defaultValue={defaultValue ?? ''} required={required} className="form-control" /></div>;
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
