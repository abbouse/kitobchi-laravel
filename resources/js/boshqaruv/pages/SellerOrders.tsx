import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import {
  SellerOrderRow as SellerOrder, StatusMeta, Counts, ReassignSellerOption,
  fmt, badgeClass,
  Info, MapButtons, ReassignSellerModal,
} from '../components/SellerCommon';
import { StatWidget } from '../components/Axelit';
import { ProfileCard, AboutList } from '../components/Profile';

type SharedAdmin = { isSuperAdmin?: boolean };

export default function SellerOrders() {
  const {
    sellerOrders = [],
    sellerOrderCounts = {},
    sellerOrderStatuses = {},
    sellerOrderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    sellerOrderFilters = {},
    reassignSellers = [],
    auth,
  } = usePage<{
    sellerOrders?: SellerOrder[];
    sellerOrderCounts?: Counts;
    sellerOrderStatuses?: Record<string, StatusMeta>;
    sellerOrderPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerOrderFilters?: { tab?: string; search?: string };
    reassignSellers?: ReassignSellerOption[];
    auth?: { admin?: SharedAdmin };
  }>().props;

  const isSuperAdmin = !!auth?.admin?.isSuperAdmin;

  const [orderTab, setOrderTab] = useState(sellerOrderFilters.tab || 'all');
  const [orderSearch, setOrderSearch] = useState(sellerOrderFilters.search || '');
  const [selectedOrder, setSelectedOrder] = useState<SellerOrder | null>(null);
  const [reassignOrder, setReassignOrder] = useState<SellerOrder | null>(null);

  const orderStatusTabs = [
    { key: 'all', label: 'Barchasi' },
    ...Object.entries(sellerOrderStatuses).map(([key, meta]) => ({ key, label: meta.label })),
  ];

  const load = (extra: Record<string, string | number> = {}) =>
    router.get(
      '/boshqaruv/seller-orders',
      {
        seller_orders_page: sellerOrderPagination.page,
        seller_orders_tab: orderTab,
        seller_orders_search: orderSearch,
        ...extra,
      },
      { preserveState: true, preserveScroll: true, replace: true }
    );

  const runPatch = (url?: string, message?: string, payload: Record<string, string> = {}) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, payload, { preserveScroll: true });
  };

  const submitReassign = (sellerId: number) => {
    if (!reassignOrder?.reassignUrl) return;
    router.post(
      reassignOrder.reassignUrl,
      { seller_id: sellerId },
      {
        preserveScroll: true,
        onSuccess: () => setReassignOrder(null),
      }
    );
  };

  return (
    <div className="container-fluid py-3">
      {/* ── Sarlavha & Breadcrumbs ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <h4 className="main-title mb-0">Seller Buyurtmalari</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">
            Do'konlarga taqsimlangan sub-buyurtmalar, yig'ish jarayoni va seller hisob-kitoblari
          </p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <button
            className="btn btn-sm btn-outline-secondary"
            onClick={() => router.reload({ preserveScroll: true })}
          >
            <i className="ti ti-rotate me-1"></i>Yangilash
          </button>
        </div>
      </div>

      {/* ── KPI Widgets ── */}
      <div className="row g-3 mb-4">
        {[
          { label: 'Jami buyurtmalar', value: sellerOrderPagination.total || 0, icon: 'ti-package' },
          { label: 'Yangi / Kutilmoqda', value: sellerOrderCounts.pending || 0, icon: 'ti-clock' },
          { label: 'Tayyorlanmoqda', value: sellerOrderCounts.accepted || 0, icon: 'ti-rotate' },
          { label: 'Yetkazilmoqda', value: sellerOrderCounts.on_delivery || 0, icon: 'ti-truck' },
          { label: 'Yakunlangan', value: sellerOrderCounts.delivered || 0, icon: 'ti-circle-check' },
        ].map((item, kpiIndex) => (
          <div className="col-xl col-md-4 col-sm-6" key={item.label}>
            <StatWidget index={kpiIndex} label={item.label} value={item.value} />
          </div>
        ))}
      </div>

      {/* ── Asosiy Jadval Card ── */}
      <div className="card border-0 shadow-sm">
        <div className="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between gap-2 flex-wrap py-3">
          <div>
            <h5 className="f-w-600 mb-0">Buyurtmalar ro'yxati</h5>
            <p className="mb-0 text-secondary f-s-13">{sellerOrderPagination.total} ta seller buyurtmasi topildi</p>
          </div>
          <div className="d-flex gap-2">
            <input
              className="form-control form-control-sm"
              style={{ maxWidth: 280 }}
              value={orderSearch}
              onChange={(e) => setOrderSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && load({ seller_orders_page: 1 })}
              placeholder="ID, seller yoki mijoz qidirish..."
            />
          </div>
        </div>

        <div className="card-body">
          {/* Status Tabs */}
          <div className="nav kc-segment mb-3">
            {orderStatusTabs.map((item) => (
              <div key={item.key} className="nav-item">
                <button
                  className={`nav-link ${orderTab === item.key ? 'active' : ''}`}
                  onClick={() => {
                    setOrderTab(item.key);
                    load({ seller_orders_page: 1, seller_orders_tab: item.key });
                  }}
                >
                  {item.label}
                  <span className="badge text-light-secondary ms-2">
                    {fmt(sellerOrderCounts[item.key] || (item.key === 'all' ? sellerOrderPagination.total : 0))}
                  </span>
                </button>
              </div>
            ))}
          </div>

          {/* Table */}
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead className="bg-light">
                <tr>
                  <th className="ps-3">ID</th>
                  <th>Seller (Do'kon / Muallif)</th>
                  <th>Mijoz</th>
                  <th className="text-end">Summa</th>
                  <th className="text-end">Mahsulot</th>
                  <th>Holat</th>
                  <th className="text-end">Sana</th>
                  <th className="pe-3 text-end">Amallar</th>
                </tr>
              </thead>
              <tbody>
                {sellerOrders.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="text-center py-5 text-muted">
                      Hech qanday buyurtma topilmadi.
                    </td>
                  </tr>
                ) : (
                  sellerOrders.map((order) => (
                    <tr key={order.id}>
                      <td className="ps-3">
                        <div className="f-w-600">#{order.id}</div>
                        <small className="text-muted">ORD #{order.orderId || '—'}</small>
                      </td>
                      <td>
                        <div className="f-w-600 text-primary">{order.seller}</div>
                        <small className="text-muted">{order.sellerPhone || order.sellerOwner || '—'}</small>
                      </td>
                      <td>
                        <div className="f-w-600 text-dark">{order.customer}</div>
                        <small className="text-muted">{order.customerPhone || '—'}</small>
                      </td>
                      <td className="text-end">
                        <div className="f-w-600 text-dark">{fmt(order.amount)} so'm</div>
                        <small className="text-muted">{order.deliveryType || '—'}</small>
                      </td>
                      <td className="text-end">{order.summary?.itemsCount || 0} ta</td>
                      <td>
                        <select
                          className={`form-select form-select-sm ${badgeClass(order.statusBadge)}`}
                          value={order.status}
                          onChange={(e) => runPatch(order.statusUrl, undefined, { status: e.target.value })}
                        >
                          {Object.entries(sellerOrderStatuses).map(([value, meta]) => (
                            <option key={value} value={value}>
                              {meta.label}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="text-end text-muted f-s-13">
                        {order.date || order.acceptedAt || '—'}
                      </td>
                      <td className="pe-3 text-end">
                        <div className="d-inline-flex align-items-center gap-1">
                          <button
                            className="btn btn-light-primary icon-btn w-30 h-30 b-r-22"
                            onClick={() => setSelectedOrder(order)}
                            title="Ko'rish"
                          >
                            <i className="ti ti-eye"></i>
                          </button>
                          {isSuperAdmin && order.canReassign ? (
                            <button
                              className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22"
                              onClick={() => setReassignOrder(order)}
                              title="Do'konni almashtirish"
                            >
                              <i className="ti ti-arrows-left-right"></i>
                            </button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {sellerOrderPagination.totalPages > 1 && (
          <div className="card-footer bg-transparent border-top py-3">
            <PaginationControls
              {...sellerOrderPagination}
              onPageChange={(page) => load({ seller_orders_page: page })}
            />
          </div>
        )}
      </div>

      <OrderModal
        order={selectedOrder}
        statuses={sellerOrderStatuses}
        onHide={() => setSelectedOrder(null)}
        onPatch={runPatch}
      />
      <ReassignSellerModal
        order={reassignOrder}
        sellers={reassignSellers}
        onHide={() => setReassignOrder(null)}
        onSubmit={submitReassign}
      />
    </div>
  );
}

function OrderModal({
  order,
  statuses,
  onHide,
  onPatch,
}: {
  order: SellerOrder | null;
  statuses: Record<string, StatusMeta>;
  onHide: () => void;
  onPatch: (url?: string, message?: string, payload?: Record<string, string>) => void;
}) {
  return (
    <Modal show={!!order} onHide={onHide} centered size="lg">
      <Modal.Header closeButton>
        <Modal.Title className="f-s-20 f-w-600">Seller order #{order?.id}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div className="row g-3">
            <div className="col-lg-4 col-xxl-3">
              <ProfileCard
                icon="ti ti-building-store"
                name={`Seller order #${order.id}`}
                subtitle={order.seller}
                badges={
                  <span className="badge text-light-primary">
                    {statuses[order.status]?.label || order.status}
                  </span>
                }
                stats={[
                  { label: 'Summa', value: fmt(order.amount) },
                  { label: 'Mahsulot', value: order.summary?.itemsCount || 0 },
                ]}
              />
              <AboutList
                title="Ishtirokchilar"
                rows={[
                  { icon: 'ti-building-store', label: 'Seller', value: order.seller },
                  { icon: 'ti-user', label: 'Mijoz', value: order.customer },
                  { icon: 'ti-phone', label: 'Telefon', value: order.customerPhone },
                  { icon: 'ti-bike', label: 'Kuryer', value: order.courier },
                  { icon: 'ti-calendar', label: 'Sana', value: order.date },
                ]}
              />
            </div>
            <div className="col-lg-8 col-xxl-9">
              <div className="row g-3">
                <Info
                  title="Buyurtma"
                  rows={[
                    ['Asosiy order', `#${order.orderId || '—'}`],
                    ['Seller', order.seller],
                    ['Mijoz', order.customer],
                    ['Telefon', order.customerPhone || '—'],
                    ['Kuryer', order.courier || '—'],
                    ['Sana', order.date || '—'],
                  ]}
                />
                <Info
                  title="Hisob-kitob"
                  rows={[
                    ['Seller summa', `${fmt(order.amount)} so'm`],
                    ['Asosiy order summa', `${fmt(order.mainOrderAmount || 0)} so'm`],
                    ['Yetkazish', `${fmt(order.deliveryPrice || 0)} so'm`],
                    ['Yetkazish turi', order.deliveryType || '—'],
                    ['Mahsulot', `${order.summary?.itemsCount || 0} ta`],
                    ['Mahsulot jami', `${fmt(order.summary?.itemsTotal || 0)} so'm`],
                  ]}
                />
                <Info
                  title="Manzil"
                  rows={[
                    ['Qabul qiluvchi', order.address?.fullName || '—'],
                    ['Telefon', order.address?.phone || '—'],
                    ['Viloyat', order.address?.region || '—'],
                    ['Tuman', order.address?.district || '—'],
                    ['Ko‘cha', order.address?.street || '—'],
                    ['Uy', order.address?.home || '—'],
                  ]}
                />
                <div className="col-12">
                  <div className="card">
                    <div className="card-header">
                      <h5 className="mb-0">Xaritada ochish</h5>
                    </div>
                    <div className="card-body">
                      <MapButtons mapLinks={(order.address?.mapLinks || {}) as Record<string, string>} />
                    </div>
                  </div>
                </div>
                <div className="col-12">
                  <div className="card">
                    <div className="card-header">
                      <h5 className="mb-0">Mahsulotlar</h5>
                    </div>
                    <div className="card-body">
                      {(order.items || []).map((item, index) => (
                        <div
                          className="d-flex justify-content-between b-b-1-light py-2"
                          key={`${item.name}-${index}`}
                        >
                          <div>
                            <strong>{item.name}</strong>
                            <div className="text-muted f-s-13">{item.author || item.type || '—'}</div>
                          </div>
                          <div className="text-end">
                            <div>
                              {item.quantity} x {fmt(item.price)}
                            </div>
                            <strong>{fmt(item.quantity * item.price)} so'm</strong>
                          </div>
                        </div>
                      ))}
                      {(order.items || []).length === 0 ? (
                        <div className="text-muted">Mahsulotlar topilmadi</div>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        {order ? (
          <select
            className="form-select"
            style={{ maxWidth: 260 }}
            value={order.status}
            onChange={(e) => onPatch(order.statusUrl, undefined, { status: e.target.value })}
          >
            {Object.entries(statuses).map(([value, meta]) => (
              <option key={value} value={value}>
                {meta.label}
              </option>
            ))}
          </select>
        ) : null}
        <Button variant="light-secondary" onClick={onHide}>
          Yopish
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
