import { useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';
import {
  Seller, SellerOrderRow as SellerOrder, StatusMeta, Counts, ReassignSellerOption,
  fmt, badgeClass, sellerChip, sellerLabel,
  Info, MapButtons, ReassignSellerModal,
} from '../components/SellerCommon';

// admin.isSuperAdmin — HandleInertiaRequests middleware orqali HAR BIR
// sahifaga uzatiladigan umumiy (shared) prop (Layout.tsx'da ham shu
// tipdan foydalaniladi).
type SharedAdmin = { isSuperAdmin?: boolean };

const sellerTabs = [
  { key: 'pending', label: 'Kutilmoqda', icon: 'bi-hourglass-split' },
  { key: 'approved', label: 'Faol', icon: 'bi-shop' },
  { key: 'rejected', label: 'Bekor qilingan', icon: 'bi-x-octagon' },
  { key: 'blocked', label: 'Bloklangan', icon: 'bi-shield-lock' },
  { key: 'all', label: 'Barchasi', icon: 'bi-grid' },
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
      <div className="page-head">
        <div>
          <h1 className="page-title">Sellerlar</h1>
          <p className="page-subtitle">Seller moderatsiyasi, shartnoma, ogohlantirishlar va seller buyurtmalari</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Kutilmoqda', value: sellerCounts.pending || 0, icon: 'bi-hourglass-split', color: '#8A5709' },
          { label: 'Faol', value: sellerCounts.approved || 0, icon: 'bi-shop', color: '#0F6A46' },
          { label: 'Bekor qilingan', value: sellerCounts.rejected || 0, icon: 'bi-x-octagon', color: '#A32A2E' },
          { label: "To'lanadigan balans", value: `${fmt(totalBalance)} so'm`, icon: 'bi-wallet2', color: '#4A3A7A' },
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
            <thead><tr><th>ID</th><th>Do'kon</th><th>Tel</th><th>Viloyat</th><th>Karma</th><th>Mahsulot</th><th>Buyurtma</th><th>Ogohlantirish</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {sellers.map((seller) => (
                <tr key={seller.id}>
                  <td className="fw-semibold text-primary">#{seller.id}</td>
                  <td>
                    <Link href={seller.actions?.detailUrl || '#'} className="d-flex align-items-center gap-2 text-reset text-decoration-none seller-row-link">
                      <div className="resource-avatar">{seller.photo ? <img src={seller.photo} alt="" /> : seller.name.slice(0, 2).toUpperCase()}</div>
                      <div style={{ minWidth: 0 }}>
                        <div className="fw-semibold text-truncate">{seller.name}</div>
                        <small className="text-muted text-truncate d-block">{seller.ownerName || seller.legalName || '—'}</small>
                      </div>
                    </Link>
                  </td>
                  <td>{seller.phone || '—'}</td>
                  <td>{seller.region || '—'}</td>
                  <td>
                    <div className="fw-semibold">{Math.round(seller.karma || 0)}%</div>
                    <small className="text-muted d-block">{seller.karmaLabelUz || '—'}</small>
                  </td>
                  <td><span className="chip chip-gray">{seller.products || 0}</span></td>
                  <td><span className="chip chip-info">{seller.orders || 0}</span></td>
                  <td><span className={`chip ${(seller.warningCount || 0) >= 3 ? 'chip-danger' : (seller.warningCount || 0) > 0 ? 'chip-warning' : 'chip-gray'}`}>{seller.warningCount || 0}/3</span></td>
                  <td><span className={`chip ${sellerChip(seller.status)}`}>{sellerLabel(seller.status)}</span></td>
                  <td>
                    <div className="d-flex align-items-center gap-1">
                      <Link href={seller.actions?.detailUrl || '#'} className="btn btn-sm btn-light" title="Ko'rish"><i className="bi bi-eye"></i></Link>
                      <Link href={seller.actions?.editUrl || '#'} className="btn btn-sm btn-light" title="Tahrirlash"><i className="bi bi-pencil-square"></i></Link>
                      <div className="dropdown">
                        <button
                          type="button"
                          className="btn btn-sm btn-light"
                          title="Boshqa amallar"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul className="dropdown-menu dropdown-menu-end">
                          {seller.status !== 'approved' ? (
                            <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.approveUrl, 'Seller tasdiqlansinmi?')}><i className="bi bi-check2-circle me-2"></i>Tasdiqlash</button></li>
                          ) : null}
                          {seller.status !== 'rejected' ? (
                            <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.rejectUrl, 'Seller bekor qilinsinmi?')}><i className="bi bi-x-circle me-2"></i>Bekor qilish</button></li>
                          ) : null}
                          {seller.status === 'blocked' ? (
                            <li><button type="button" className="dropdown-item" onClick={() => runPatch(seller.actions?.unblockUrl, 'Seller blokdan chiqarilsinmi?', { message: 'Admin tomonidan blokdan chiqarildi.' })}><i className="bi bi-unlock me-2"></i>Blokdan chiqarish</button></li>
                          ) : null}
                          <li><button type="button" className="dropdown-item" onClick={() => warnSeller(seller)}><i className="bi bi-exclamation-triangle me-2"></i>Ogohlantirish</button></li>
                          <li><button type="button" className="dropdown-item" onClick={() => resetPassword(seller)}><i className="bi bi-key me-2"></i>Parol reset</button></li>
                          <li><hr className="dropdown-divider" /></li>
                          <li><Link href={`${seller.actions?.detailUrl || '#'}?tab=staff`} className="dropdown-item"><i className="bi bi-people me-2"></i>Xodimlar</Link></li>
                        </ul>
                      </div>
                    </div>
                  </td>
                </tr>
              ))}
              {sellerPagination.total === 0 ? <tr><td colSpan={10} className="text-center text-muted py-5">Hech qanday seller topilmadi</td></tr> : null}
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
                  <td>
                    <div className="d-flex align-items-center gap-1">
                      <button className="btn btn-sm btn-light" onClick={() => setSelectedOrder(order)} title="Ko'rish"><i className="bi bi-eye"></i></button>
                      {isSuperAdmin && order.canReassign ? (
                        <button className="btn btn-sm btn-light" onClick={() => setReassignOrder(order)} title="Do'konni almashtirish"><i className="bi bi-arrow-left-right"></i></button>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
              {sellerOrderPagination.total === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Hech qanday buyurtma topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...sellerOrderPagination} onPageChange={(page) => load({ seller_orders_page: page })} />
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
