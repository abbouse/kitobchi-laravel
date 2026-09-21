import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import {
  Seller, SellerOrderRow as SellerOrder, StatusMeta, Counts, ReassignSellerOption,
  fmt, badgeClass, sellerChip, sellerLabel,
  Info, MapButtons, ReassignSellerModal,
} from '../components/SellerCommon';

import { StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

// admin.isSuperAdmin — HandleInertiaRequests middleware orqali HAR BIR
// sahifaga uzatiladigan umumiy (shared) prop (Layout.tsx'da ham shu
// tipdan foydalaniladi).
type SharedAdmin = { isSuperAdmin?: boolean };

const sellerTabs = [
  { key: 'pending', label: 'Kutilmoqda', icon: 'ti-hourglass' },
  { key: 'approved', label: 'Faol', icon: 'ti-building-store' },
  { key: 'rejected', label: 'Bekor qilingan', icon: 'ti-octagon-off' },
  { key: 'blocked', label: 'Bloklangan', icon: 'ti-shield-lock' },
  { key: 'all', label: 'Barchasi', icon: 'ti-layout-grid' },
];

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
    reassignSellers = [],
    auth,
  } = usePage<{
    sellers?: Seller[];
    sellerCounts?: Counts;
    sellerOrders?: SellerOrder[];
    sellerOrderCounts?: Counts;
    sellerOrderStatuses?: Record<string, StatusMeta>;
    sellerPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerOrderPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerFilters?: { tab?: string; search?: string }; sellerOrderFilters?: { tab?: string; search?: string };
    reassignSellers?: ReassignSellerOption[];
    // MUHIM (2026-09 bugfix): superadmin bayrog'i HandleInertiaRequests
    // middleware orqali `auth.admin.isSuperAdmin` sifatida keladi
    // (top-level 'admin' emas — Layout.tsx dagi bilan bir xil pattern).
    // Ilgari noto'g'ri top-level 'admin' o'qilgani uchun bu yerda
    // isSuperAdmin doim false bo'lib, "Do'konni almashtirish" tugmasi
    // hech kimga chiqmasdi.
    auth?: { admin?: SharedAdmin };
  }>().props;

  const isSuperAdmin = !!auth?.admin?.isSuperAdmin;

  const [sellerTab, setSellerTab] = useState(sellerFilters.tab || 'pending');
  const [orderTab, setOrderTab] = useState(sellerOrderFilters.tab || 'all');
  const [sellerSearch, setSellerSearch] = useState(sellerFilters.search || '');
  const [orderSearch, setOrderSearch] = useState(sellerOrderFilters.search || '');
  const [selectedOrder, setSelectedOrder] = useState<SellerOrder | null>(null);
  const [reassignOrder, setReassignOrder] = useState<SellerOrder | null>(null);

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

  const submitReassign = (sellerId: number) => {
    if (!reassignOrder?.reassignUrl) return;
    router.post(reassignOrder.reassignUrl, { seller_id: sellerId }, {
      preserveScroll: true,
      onSuccess: () => setReassignOrder(null),
    });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Sellerlar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Seller moderatsiyasi, shartnoma, ogohlantirishlar va seller buyurtmalari</p>
        </div>
      </div>

      <div className="row">
        {[
          { label: 'Kutilmoqda', value: sellerCounts.pending || 0, icon: 'ti-hourglass', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Faol', value: sellerCounts.approved || 0, icon: 'ti-building-store', color: 'rgba(var(--success), 1)' },
          { label: 'Bekor qilingan', value: sellerCounts.rejected || 0, icon: 'ti-octagon-off', color: 'rgba(var(--danger), 1)' },
          { label: "To'lanadigan balans", value: `${fmt(totalBalance)} so'm`, icon: 'ti-wallet', color: 'rgba(var(--primary), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.value} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Sotuvchilar jadvali</h5>
            <p className="mb-0 text-secondary">{sellerPagination.total} ta seller topildi</p>
          </div>
          <div className="d-flex flex-wrap gap-2">
            <input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={sellerSearch} onChange={(e) => setSellerSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ sellers_page: 1 })} placeholder="Do'kon, telefon yoki hudud" />
          </div>
        </div>
        <div className="card-body">

          <div className="nav nav-tabs app-tabs-primary flex-wrap mb-3">
            {sellerTabs.map((item) => (
              <div key={item.key} className="nav-item"><button
                  className={`nav-link ${sellerTab === item.key ? 'active' : ''}`}
                  onClick={() => { setSellerTab(item.key); load({ sellers_page: 1, sellers_tab: item.key }); }}>
                  <i className={`${tiIcon(item.icon)} me-1`}></i>{item.label}
                  <span className="badge text-light-secondary ms-2">{fmt(sellerCounts[item.key] || 0)}</span>
                </button></div>
            ))}
          </div>
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>Do'kon</th><th>Tel</th><th>Viloyat</th><th className="text-end">Karma</th><th className="text-end">Mahsulot</th><th className="text-end">Buyurtma</th><th className="text-end">Ogohlantirish</th><th>Holat</th><th>Amallar</th></tr></thead>
              <tbody>
                {sellers.map((seller) => (
                  <tr key={seller.id}>
                    <td className="f-w-600 text-nowrap">#{seller.id}</td>
                    <td>
                      <Link href={seller.actions?.detailUrl || '#'} className="d-flex align-items-center gap-2 text-reset text-decoration-none">
                        <div className="h-55 w-55 d-flex-center b-r-50 bg-light-primary f-w-600 f-s-18 overflow-hidden flex-shrink-0">{seller.photo ? <img className="w-100 h-100 object-fit-cover" src={seller.photo} alt="" /> : seller.name.slice(0, 2).toUpperCase()}</div>
                        <div className="min-w-0">
                          <div className="f-w-600 text-truncate">{seller.name}</div>
                          <small className="text-muted text-truncate d-block">{seller.ownerName || seller.legalName || '—'}</small>
                        </div>
                      </Link>
                    </td>
                    <td>{seller.phone || '—'}</td>
                    <td>{seller.region || '—'}</td>
                    <td>
                      <div className="f-w-600">{Math.round(seller.karma || 0)}%</div>
                      <small className="text-muted d-block">{seller.karmaLabelUz || '—'}</small>
                    </td>
                    <td className="text-end f-w-600 text-nowrap">{seller.products || 0}</td>
                    <td className="text-end f-w-600 text-nowrap">{seller.orders || 0}</td>
                    <td><span className={`badge ${(seller.warningCount || 0) >= 3 ? 'text-light-danger' : (seller.warningCount || 0) > 0 ? 'text-light-warning' : 'text-light-secondary'}`}>{seller.warningCount || 0}/3</span></td>
                    <td><span className={`badge text-uppercase ${toneBadge(toneOf(sellerChip(seller.status)))}`}>{sellerLabel(seller.status)}</span></td>
                    <td>
                      <div className="d-flex align-items-center gap-1">
                        <Link href={seller.actions?.detailUrl || '#'} className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" title="Ko'rish"><i className="ti ti-eye"></i></Link>
                        <Link href={seller.actions?.editUrl || '#'} className="btn btn-light-success icon-btn w-30 h-30 b-r-22" title="Tahrirlash"><i className="ti ti-edit"></i></Link>
                        <div className="dropdown">
                          <button
                            type="button"
                            className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22"
                            title="Boshqa amallar"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                          >
                            <i className="ti ti-dots-vertical"></i>
                          </button>
                          <ul className="dropdown-menu dropdown-menu-end">
                            {seller.status !== 'approved' ? (
                              <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.approveUrl, 'Seller tasdiqlansinmi?')}><i className="ti ti-circle-check me-2"></i>Tasdiqlash</button></li>
                            ) : null}
                            {seller.status !== 'rejected' ? (
                              <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.rejectUrl, 'Seller bekor qilinsinmi?')}><i className="ti ti-circle-x me-2"></i>Bekor qilish</button></li>
                            ) : null}
                            {seller.status === 'blocked' ? (
                              <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.unblockUrl, 'Seller blokdan chiqarilsinmi?', { message: 'Admin tomonidan blokdan chiqarildi.' })}><i className="ti ti-lock-open me-2"></i>Blokdan chiqarish</button></li>
                            ) : null}
                            <li><button type="button" className="dropdown-item" onClick={() => warnSeller(seller)}><i className="ti ti-alert-triangle me-2"></i>Ogohlantirish</button></li>
                            <li><button type="button" className="dropdown-item" onClick={() => resetPassword(seller)}><i className="ti ti-key me-2"></i>Parol reset</button></li>
                            <li><hr className="dropdown-divider" /></li>
                            <li><Link href={`${seller.actions?.detailUrl || '#'}?tab=staff`} className="dropdown-item"><i className="ti ti-users me-2"></i>Xodimlar</Link></li>
                          </ul>
                        </div>
                      </div>
                    </td>
                  </tr>
                ))}
                {sellerPagination.total === 0 ? <tr><td colSpan={10} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hech qanday seller topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...sellerPagination} onPageChange={(page) => load({ sellers_page: page })} />
        </div>
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Seller buyurtmalari</h5>
            <p className="mb-0 text-secondary">{sellerOrderPagination.total} ta yozuv topildi</p>
          </div>
          <input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={orderSearch} onChange={(e) => setOrderSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load({ seller_orders_page: 1 })} placeholder="ID, seller yoki mijoz" />
        </div>
        <div className="card-body">

          <div className="nav nav-tabs app-tabs-primary flex-wrap mb-3">
            {orderStatusTabs.map((item) => (
              <div key={item.key} className="nav-item"><button
                  className={`nav-link ${orderTab === item.key ? 'active' : ''}`}
                  onClick={() => { setOrderTab(item.key); load({ seller_orders_page: 1, seller_orders_tab: item.key }); }}>
                  {item.label}<span className="badge text-light-secondary ms-2">{fmt(sellerOrderCounts[item.key] || 0)}</span>
                </button></div>
            ))}
          </div>
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>Seller</th><th>Mijoz</th><th className="text-end">Summa</th><th className="text-end">Mahsulot</th><th>Holat</th><th className="text-end">Sana</th><th></th></tr></thead>
              <tbody>
                {sellerOrders.map((order) => (
                  <tr key={order.id}>
                    <td><div className="f-w-600">#{order.id}</div><small className="text-muted">ORD #{order.orderId || '—'}</small></td>
                    <td><div className="f-w-600">{order.seller}</div><small className="text-muted">{order.sellerPhone || order.sellerOwner || '—'}</small></td>
                    <td><div>{order.customer}</div><small className="text-muted">{order.customerPhone || '—'}</small></td>
                    <td><div className="f-w-600">{fmt(order.amount)} so'm</div><small className="text-muted">{order.deliveryType || '—'}</small></td>
                    <td>{order.summary?.itemsCount || 0} ta</td>
                    <td>
                      <select className={`form-select form-select-sm ${badgeClass(order.statusBadge)}`} value={order.status} onChange={(e) => runPatch(order.statusUrl, undefined, { status: e.target.value })}>
                        {Object.entries(sellerOrderStatuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}
                      </select>
                    </td>
                    <td className="text-muted">{order.date || order.acceptedAt || '—'}</td>
                    <td>
                      <div className="d-flex align-items-center gap-1">
                        <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => setSelectedOrder(order)} title="Ko'rish"><i className="ti ti-eye"></i></button>
                        {isSuperAdmin && order.canReassign ? (
                          <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => setReassignOrder(order)} title="Do'konni almashtirish"><i className="ti ti-arrows-left-right"></i></button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
                {sellerOrderPagination.total === 0 ? <tr><td colSpan={8} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hech qanday buyurtma topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...sellerOrderPagination} onPageChange={(page) => load({ seller_orders_page: page })} />
        </div>
      </div>

      <OrderModal order={selectedOrder} statuses={sellerOrderStatuses} onHide={() => setSelectedOrder(null)} onPatch={runPatch} />
      <ReassignSellerModal order={reassignOrder} sellers={reassignSellers} onHide={() => setReassignOrder(null)} onSubmit={submitReassign} />
    </div>
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
      <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Seller order #{order?.id}</Modal.Title></Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div className="row">
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
            <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Xaritada ochish</h5></div><div className="card-body"><MapButtons mapLinks={(order.address?.mapLinks || {}) as Record<string, string>} /></div></div></div>
            <div className="col-12">
              <div className="card"><div className="card-header"><h5 className="mb-0">Mahsulotlar</h5></div><div className="card-body">
                  {(order.items || []).map((item, index) => (
                    <div className="d-flex justify-content-between b-b-1-light py-2" key={`${item.name}-${index}`}>
                      <div><strong>{item.name}</strong><div className="text-muted f-s-13">{item.author || item.type || '—'}</div></div>
                      <div className="text-end"><div>{item.quantity} x {fmt(item.price)}</div><strong>{fmt(item.quantity * item.price)} so'm</strong></div>
                    </div>
                  ))}
                  {(order.items || []).length === 0 ? <div className="text-muted">Mahsulotlar topilmadi</div> : null}
                </div></div>
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
        <Button variant="light-secondary" onClick={onHide}>Yopish</Button>
      </Modal.Footer>
    </Modal>
  );
}
