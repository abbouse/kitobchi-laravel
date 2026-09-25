import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import { PageCrumbs } from '../Layout';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import { StatWidget } from '../components/Axelit';
import { ProfileCard, AboutList } from '../components/Profile';
import FormAction, { ActionRow } from '../components/FormAction';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type Counts = Record<string, number>;
type StatusMeta = { label: string; badge?: string };
type PenaltyRule = { key: string; label: string; description: string; amount: number };

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

const statusChip = (status?: string) => {
  if (status === 'delivered' || status === 'customer_received') return 'text-light-success';
  if (status === 'cancelled' || status === 'returned') return 'text-light-danger';
  if (status === 'in_delivery') return 'text-light-warning';
  if (status === 'pending') return 'text-light-info';
  return 'text-light-secondary';
};

export default function CourierOrders() {
  const {
    courierOrders = [],
    courierOrderCounts = {},
    courierOrderStatuses = {},
    courierOrderPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    courierOrderFilters = {},
  } = usePage<{
    courierOrders?: CourierOrder[];
    courierOrderCounts?: Counts;
    courierOrderStatuses?: Record<string, StatusMeta>;
    courierOrderPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    courierOrderFilters?: { tab?: string; search?: string };
  }>().props;

  const [orderTab, setOrderTab] = useState(courierOrderFilters.tab || 'all');
  const [orderSearch, setOrderSearch] = useState(courierOrderFilters.search || '');
  const [selectedOrder, setSelectedOrder] = useState<CourierOrder | null>(null);

  const orderTabs = [
    { key: 'all', label: 'Barchasi' },
    ...Object.entries(courierOrderStatuses).map(([key, meta]) => ({ key, label: meta.label })),
  ];

  const load = (extra: Record<string, string | number> = {}) => {
    router.get(
      '/boshqaruv/courier-orders',
      {
        courier_orders_page: courierOrderPagination.page,
        courier_orders_tab: orderTab,
        courier_orders_search: orderSearch,
        ...extra,
      },
      { preserveState: true, preserveScroll: true, replace: true }
    );
  };

  const patch = (url?: string, data: Record<string, string> = {}, confirmation?: string) => {
    if (!url || (confirmation && !confirm(confirmation))) return;
    router.patch(url, data, { preserveScroll: true });
  };

  const inDeliveryCount = (courierOrderCounts['pending'] || 0) + (courierOrderCounts['in_delivery'] || 0);
  const deliveredCount = (courierOrderCounts['delivered'] || 0) + (courierOrderCounts['customer_received'] || 0);
  const problemCount = (courierOrderCounts['cancelled'] || 0) + (courierOrderCounts['returned'] || 0);

  return (
    <div className="container-fluid py-3">
      <PageCrumbs
        title="Kuryer buyurtmalari (Logistika va yetkazmalar)"
        crumbs={[
          { label: 'Boshqaruv', href: '/boshqaruv' },
          { label: 'Kuryerlar floti', href: '/boshqaruv/couriers' },
          { label: 'Kuryer buyurtmalari' },
        ]}
      />

      {/* Axelit Top KPI Stat Widgets */}
      <div className="row g-3 mb-4">
        <div className="col-12 col-sm-6 col-xl-3">
          <StatWidget
            title="Jami topshiriqlar"
            value={fmt(courierOrderCounts['all'] || courierOrderPagination.total)}
            badge="Hammasi"
            tone="primary"
            icon="ti ti-truck-delivery"
          />
        </div>
        <div className="col-12 col-sm-6 col-xl-3">
          <StatWidget
            title="Faol jarayonda"
            value={fmt(inDeliveryCount)}
            badge="Yo'lda / Kutilmoqda"
            tone="warning"
            icon="ti ti-route"
          />
        </div>
        <div className="col-12 col-sm-6 col-xl-3">
          <StatWidget
            title="Topshirildi"
            value={fmt(deliveredCount)}
            badge="Muvaffaqiyatli"
            tone="success"
            icon="ti ti-circle-check"
          />
        </div>
        <div className="col-12 col-sm-6 col-xl-3">
          <StatWidget
            title="Bekor / Qaytarilgan"
            value={fmt(problemCount)}
            badge="Muammoli"
            tone="danger"
            icon="ti ti-alert-triangle"
          />
        </div>
      </div>

      {/* Main Order List Card */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap pb-0">
          <div>
            <h5 className="f-w-600 mb-1">Kuryer yetkazib berish topshiriqlari</h5>
            <p className="text-muted f-s-13 mb-0">
              {courierOrderPagination.total} ta yetkazma yozuvi mavjud
            </p>
          </div>
          <div className="d-flex align-items-center gap-2">
            <input
              className="form-control form-control-sm"
              style={{ minWidth: 260, maxWidth: 360 }}
              value={orderSearch}
              onChange={(e) => setOrderSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && load({ courier_orders_page: 1 })}
              placeholder="Topshiriq ID, Order ID, mijoz yoki kuryer..."
            />
            {orderSearch && (
              <button
                className="btn btn-sm btn-light-secondary"
                onClick={() => {
                  setOrderSearch('');
                  load({ courier_orders_page: 1, courier_orders_search: '' });
                }}
              >
                Tozalash
              </button>
            )}
            <button
              className="btn btn-sm btn-primary"
              onClick={() => load({ courier_orders_page: 1 })}
            >
              <i className="ti ti-search me-1"></i>Qidirish
            </button>
          </div>
        </div>

        <div className="card-body">
          {/* Status Tabs */}
          <div className="nav kc-segment mb-3">
            {orderTabs.map((tab) => (
              <div key={tab.key} className="nav-item">
                <button
                  className={`nav-link ${orderTab === tab.key ? 'active' : ''}`}
                  onClick={() => {
                    setOrderTab(tab.key);
                    load({ courier_orders_page: 1, courier_orders_tab: tab.key });
                  }}
                >
                  {tab.label}
                  <span className="badge text-light-secondary ms-2">
                    {fmt(courierOrderCounts[tab.key] || 0)}
                  </span>
                </button>
              </div>
            ))}
          </div>

          {/* Table */}
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th>Topshiriq</th>
                  <th>Kuryer</th>
                  <th>Mijoz</th>
                  <th>Yetkazish manzili</th>
                  <th>Summa & Ulush</th>
                  <th>To'lov</th>
                  <th>Holat</th>
                  <th>Sana</th>
                  <th className="text-center">Amallar</th>
                </tr>
              </thead>
              <tbody>
                {courierOrders.map((order) => (
                  <tr key={order.id}>
                    <td>
                      <strong className="text-primary">#{order.id}</strong>
                      <small className="d-block text-muted">
                        Asosiy: #{order.orderId || '—'}
                      </small>
                    </td>
                    <td>
                      <div className="f-w-600">{order.courier}</div>
                      <small className="text-muted">{order.courierPhone || '—'}</small>
                      {order.courierRegion ? (
                        <small className="d-block text-muted">{order.courierRegion}</small>
                      ) : null}
                    </td>
                    <td>
                      <div className="f-w-500">{order.customer}</div>
                      <small className="text-muted">{order.customerPhone || '—'}</small>
                    </td>
                    <td>
                      <div className="f-s-13 text-truncate" style={{ maxWidth: 220 }}>
                        {[order.address?.region, order.address?.district, order.address?.street]
                          .filter(Boolean)
                          .join(', ') || 'Manzil ko‘rsatilmagan'}
                      </div>
                      {order.taskDistanceKm ? (
                        <small className="text-muted">
                          <i className="ti ti-map-pin me-1"></i>
                          {Number(order.taskDistanceKm).toFixed(1)} km
                        </small>
                      ) : null}
                    </td>
                    <td>
                      <div>
                        <strong>{fmt(order.amount)} so'm</strong>
                      </div>
                      <small className="text-muted">
                        Haqi: {fmt((order.courierPrice || 0) + (order.bonus || 0))} so'm
                      </small>
                    </td>
                    <td>
                      <span className="badge text-light-secondary">
                        {order.paymentStatus || '—'}
                      </span>
                    </td>
                    <td>
                      <select
                        className={`form-select form-select-sm ${statusChip(order.status)}`}
                        style={{ minWidth: 140 }}
                        value={order.status}
                        onChange={(e) => patch(order.statusUrl, { status: e.target.value })}
                      >
                        {Object.entries(courierOrderStatuses).map(([value, meta]) => (
                          <option key={value} value={value}>
                            {meta.label}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="text-muted f-s-13 text-nowrap">{order.date || '—'}</td>
                    <td className="text-center">
                      <button
                        className="btn btn-light-primary icon-btn w-30 h-30 b-r-22"
                        title="Batafsil ko'rish"
                        onClick={() => setSelectedOrder(order)}
                      >
                        <i className="ti ti-eye"></i>
                      </button>
                    </td>
                  </tr>
                ))}
                {courierOrderPagination.total === 0 && (
                  <tr>
                    <td colSpan={9} className="text-center py-5 text-secondary">
                      <i className="ti ti-package d-flex justify-content-center mb-2 f-s-30 text-primary"></i>
                      Hech qanday kuryer topshirig'i topilmadi
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          <PaginationControls
            {...courierOrderPagination}
            onPageChange={(page) => load({ courier_orders_page: page })}
          />
        </div>
      </div>

      {/* Axelit Order Detail Modal */}
      <OrderModal
        order={selectedOrder}
        statuses={courierOrderStatuses}
        onHide={() => setSelectedOrder(null)}
        onPatch={patch}
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
  order: CourierOrder | null;
  statuses: Record<string, StatusMeta>;
  onHide: () => void;
  onPatch: (url?: string, data?: Record<string, string>) => void;
}) {
  const [penaltyReason, setPenaltyReason] = useState('late_delivery');
  const activePenalty =
    (order?.penaltyRules || []).find((rule) => rule.key === penaltyReason) ||
    order?.penaltyRules?.[0];

  const submitPenalty = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!order?.penaltyUrl || !activePenalty) return;
    if (!confirm(`${order.courier} balansidan ${fmt(activePenalty.amount)} so'm jarima yechilsinmi?`))
      return;
    router.post(order.penaltyUrl, new FormData(event.currentTarget), { preserveScroll: true });
  };

  return (
    <Modal show={!!order} onHide={onHide} size="xl" centered>
      <Modal.Header closeButton>
        <Modal.Title className="f-s-20 f-w-600">
          Topshiriq #{order?.id} — Yetkazma tafsilotlari
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div className="row g-3">
            {/* Left sidebar profile summary */}
            <div className="col-lg-4 col-xxl-3">
              <ProfileCard
                icon="ti ti-truck-delivery"
                name={`Yetkazma #${order.id}`}
                subtitle={`Asosiy buyurtma: #${order.orderId || '—'}`}
                badges={
                  <>
                    <span className={`badge ${statusChip(order.status)}`}>
                      {order.statusLabel || order.status}
                    </span>
                    {order.paymentStatus ? (
                      <span className="badge text-light-secondary">{order.paymentStatus}</span>
                    ) : null}
                  </>
                }
                stats={[
                  { label: 'Buyurtma summasi', value: `${fmt(order.amount)} so'm` },
                  {
                    label: 'Kuryer daromadi',
                    value: `${fmt((order.courierPrice || 0) + (order.bonus || 0))} so'm`,
                  },
                ]}
              />

              <AboutList
                title="Ishtirokchilar aloqasi"
                rows={[
                  { icon: 'ti-bike', label: 'Kuryer', value: order.courier },
                  { icon: 'ti-phone', label: 'Kuryer tel.', value: order.courierPhone },
                  { icon: 'ti-user', label: 'Mijoz', value: order.customer },
                  { icon: 'ti-phone', label: 'Mijoz tel.', value: order.customerPhone },
                ]}
              />
            </div>

            {/* Right content details */}
            <div className="col-lg-8 col-xxl-9">
              <div className="row g-3">
                <Info
                  title="Yetkazma ma'lumotlari"
                  rows={[
                    ['Asosiy buyurtma', `#${order.orderId || '—'}`],
                    ['Kuryer', order.courier],
                    ['Kuryer telefoni', order.courierPhone || '—'],
                    ['Hudud', order.courierRegion || '—'],
                    ['Mijoz', order.customer],
                    ['Mijoz telefoni', order.customerPhone || '—'],
                  ]}
                />

                <Info
                  title="Moliyaviy taqsimot"
                  rows={[
                    ['Yetkazma summasi', `${fmt(order.amount)} so'm`],
                    ['Buyurtma summasi', `${fmt(order.mainOrderAmount || 0)} so'm`],
                    ['Kuryer ulushi', `${fmt(order.courierPrice || 0)} so'm`],
                    ['Bonus', `${fmt(order.bonus || 0)} so'm`],
                    [
                      'Jami payout',
                      `${fmt((order.courierPrice || 0) + (order.bonus || 0))} so'm`,
                    ],
                    ['Settled summa', `${fmt(order.settledAmount || 0)} so'm`],
                  ]}
                />

                <Info
                  title="Masofa va tarif hisobi"
                  rows={[
                    ['Masofa', `${Number(order.taskDistanceKm || 0).toFixed(2)} km`],
                    ['Marshrut qismi (Leg)', order.taskLeg || '—'],
                    ['Bazaviy tarif', `${fmt(order.taskBaseFeeAmount || 0)} so'm`],
                    ['Masofa tarifi', `${fmt(order.taskDistanceFeeAmount || 0)} so'm`],
                    ['Masofa bonusi', `${fmt(order.taskBonusAmount || 0)} so'm`],
                    ['Jami topshiriq tarifi', `${fmt(order.taskFeeAmount || 0)} so'm`],
                  ]}
                />

                <Info
                  title="Jarayon va vaqtlar"
                  rows={[
                    ['Holat', order.statusLabel || order.status],
                    ['To‘lov holati', order.paymentStatus || '—'],
                    ['Yetkazish turi', order.deliveryType || '—'],
                    ['Olingan vaqt', order.pickedUpAt || '—'],
                    ['Topshirilgan vaqt', order.settledAt || '—'],
                    ['Yaratilgan sana', order.date || '—'],
                  ]}
                />

                <div className="col-12">
                  <div className="card h-100">
                    <div className="card-header pb-2">
                      <h6 className="mb-0 f-w-600">Yetkazish manzili va xarita</h6>
                    </div>
                    <div className="card-body">
                      <div className="row g-2 mb-2">
                        <div className="col-sm-6">
                          <small className="text-muted d-block">Qabul qiluvchi</small>
                          <span className="f-w-600">{order.address?.fullName || '—'}</span>
                        </div>
                        <div className="col-sm-6">
                          <small className="text-muted d-block">Bog'lanish telefoni</small>
                          <span className="f-w-600">{order.address?.phone || '—'}</span>
                        </div>
                        <div className="col-12">
                          <small className="text-muted d-block">To'liq manzil</small>
                          <span className="f-w-600">
                            {[
                              order.address?.region,
                              order.address?.district,
                              order.address?.street,
                              order.address?.home,
                            ]
                              .filter(Boolean)
                              .join(', ') || 'Ko‘rsatilmagan'}
                          </span>
                        </div>
                      </div>
                      <MapButtons
                        mapLinks={(order.address?.mapLinks || {}) as Record<string, string>}
                      />
                    </div>
                  </div>
                </div>

                <div className="col-12">
                  <div className="card h-100">
                    <div className="card-header pb-2">
                      <h6 className="mb-0 f-w-600">Buyurtmadagi tovarlar</h6>
                    </div>
                    <div className="card-body">
                      {(order.items || []).map((item, index) => (
                        <div
                          className="d-flex justify-content-between align-items-center b-b-1-light py-2"
                          key={`${item.name}-${index}`}
                        >
                          <div>
                            <strong>{item.name}</strong>
                            <div className="f-s-13 text-muted">
                              {item.author || item.type || '—'}
                            </div>
                          </div>
                          <div className="text-end">
                            <span className="text-muted me-2">
                              {item.quantity} x {fmt(item.price)}
                            </span>
                            <span className="f-w-600">{fmt(item.quantity * item.price)} so'm</span>
                          </div>
                        </div>
                      ))}
                      {(order.items || []).length === 0 && (
                        <div className="text-muted f-s-13">Mahsulotlar topilmadi</div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Jarima qo'llash */}
                <div className="col-12">
                  <div className="card">
                    <div className="card-body">
                      <ActionRow
                        icon="ti ti-shield-x"
                        tone="danger"
                        title="Kuryerga jarima qo‘llash"
                        value={
                          activePenalty
                            ? `${fmt(activePenalty.amount)} so'm · ${activePenalty.label}`
                            : undefined
                        }
                        meta="Jarima mijoz shikoyati yoki kechikish holatiga qarab hisoblanadi, tasdiqlansa kuryer balansidan yechiladi"
                        action={
                          <FormAction
                            label="Jarima qo‘llash"
                            icon="ti ti-coins"
                            variant="light-danger"
                            title="Kuryerga jarima qo‘llash"
                            submitLabel="Jarimani qo‘llash"
                            submitVariant="danger"
                            disabled={!order.penaltyUrl || !(order.penaltyRules || []).length}
                            onSubmit={submitPenalty}
                          >
                            <div className="row g-3">
                              <div className="col-12">
                                <label className="form-label">Shikoyat sababi</label>
                                <select
                                  name="reason"
                                  className="form-select"
                                  value={activePenalty?.key || penaltyReason}
                                  onChange={(event) => setPenaltyReason(event.target.value)}
                                >
                                  {(order.penaltyRules || []).map((rule) => (
                                    <option key={rule.key} value={rule.key}>
                                      {rule.label} · {fmt(rule.amount)} so'm
                                    </option>
                                  ))}
                                </select>
                              </div>
                              <div className="col-12">
                                <label className="form-label">Admin izohi</label>
                                <input
                                  name="note"
                                  className="form-control"
                                  placeholder="Mijoz shikoyati, dalil yoki operator izohi"
                                />
                              </div>
                              {activePenalty ? (
                                <div className="col-12">
                                  <div className="f-s-13 text-muted">
                                    {activePenalty.description}
                                  </div>
                                </div>
                              ) : null}
                            </div>
                          </FormAction>
                        }
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer className="d-flex justify-content-between align-items-center">
        <div>
          {order && (
            <div className="d-flex align-items-center gap-2">
              <span className="text-muted f-s-13">Holatni yangilash:</span>
              <select
                className="form-select form-select-sm"
                style={{ maxWidth: 220 }}
                value={order.status}
                onChange={(e) => onPatch(order.statusUrl, { status: e.target.value })}
              >
                {Object.entries(statuses).map(([value, meta]) => (
                  <option key={value} value={value}>
                    {meta.label}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>
        <Button variant="light-secondary" onClick={onHide}>
          Yopish
        </Button>
      </Modal.Footer>
    </Modal>
  );
}

function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return (
    <div className="col-xl-6">
      <div className="card h-100">
        <div className="card-header pb-2">
          <h6 className="mb-0 f-w-600">{title}</h6>
        </div>
        <div className="card-body">
          <div className="row g-2">
            {rows.map(([label, value]) => (
              <div className="col-sm-6" key={label}>
                <small className="text-muted d-block">{label}</small>
                <span className="f-w-600 f-s-13">{value}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) {
    return <span className="text-muted f-s-13">Xarita linki mavjud emas</span>;
  }

  return (
    <div className="d-flex gap-2 flex-wrap mt-2">
      {mapLinks.google && (
        <a
          className="btn btn-sm btn-light-secondary"
          href={mapLinks.google}
          target="_blank"
          rel="noreferrer"
        >
          <i className="ti ti-map-pin me-1 text-primary"></i>Google Xarita
        </a>
      )}
      {mapLinks.yandex && (
        <a
          className="btn btn-sm btn-light-secondary"
          href={mapLinks.yandex}
          target="_blank"
          rel="noreferrer"
        >
          <i className="ti ti-map-pin me-1 text-warning"></i>Yandex Xarita
        </a>
      )}
    </div>
  );
}
