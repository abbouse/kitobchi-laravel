import { useMemo, useState } from 'react';
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
type AnyRow = Record<string, string | number | boolean | null | undefined | Record<string, string>>;

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

const courierTabs = [
  { key: 'pending', label: 'Kutilmoqda' },
  { key: 'approved', label: 'Faol' },
  { key: 'rejected', label: 'Rad etilgan' },
  { key: 'blocked', label: 'Bloklangan' },
  { key: 'all', label: 'Barchasi' },
];

const courierLabel = (status?: string) =>
  ({
    approved: 'Faol',
    pending: 'Kutilmoqda',
    rejected: 'Rad etilgan',
    blocked: 'Bloklangan',
  }[String(status || '')] || status || '—');

const courierChip = (status?: string) => {
  if (status === 'approved') return 'text-light-success';
  if (status === 'rejected' || status === 'blocked') return 'text-light-danger';
  if (status === 'pending') return 'text-light-info';
  return 'text-light-secondary';
};

function Avatar({ row }: { row: { photo?: string; name: string } }) {
  if (row.photo) {
    return <img src={row.photo} alt={row.name} className="h-40 w-40 b-r-50 object-fit-cover border" />;
  }
  return (
    <div className="h-40 w-40 b-r-50 bg-light-primary text-primary d-flex-center f-w-600 f-s-14">
      {row.name.slice(0, 2).toUpperCase()}
    </div>
  );
}

export default function Couriers() {
  const {
    couriers = [],
    courierCounts = {},
    courierPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    courierFilters = {},
  } = usePage<{
    couriers?: Courier[];
    courierCounts?: Counts;
    courierPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    courierFilters?: { tab?: string; search?: string };
  }>().props;

  const [courierTab, setCourierTab] = useState(courierFilters.tab || 'pending');
  const [courierSearch, setCourierSearch] = useState(courierFilters.search || '');
  const [selectedCourier, setSelectedCourier] = useState<Courier | null>(null);
  const [editingCourier, setEditingCourier] = useState<Courier | null>(null);

  const totalBalance = useMemo(
    () => couriers.reduce((sum, courier) => sum + (courier.balance || 0), 0),
    [couriers]
  );
  const onlineCount = useMemo(
    () => couriers.filter((courier) => courier.isOnline).length,
    [couriers]
  );

  const load = (extra: Record<string, string | number> = {}) =>
    router.get(
      '/boshqaruv/couriers',
      {
        couriers_page: courierPagination.page,
        couriers_tab: courierTab,
        couriers_search: courierSearch,
        ...extra,
      },
      { preserveState: true, preserveScroll: true, replace: true }
    );

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
    <div className="container-fluid py-3">
      {/* ── Sarlavha & Breadcrumbs ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <h4 className="main-title mb-0">Kuryerlar</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">
            Yetkazib berish xizmati floti, online holat, transport vositalari, reyting va hisob-kitoblar
          </p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <button
            className="btn btn-sm btn-outline-secondary"
            onClick={() => router.reload({ preserveScroll: true })}
          >
            <i className="ti ti-refresh me-1"></i>Yangilash
          </button>
        </div>
      </div>

      {/* ── KPI Widgets ── */}
      <div className="row g-3 mb-4">
        {[
          { label: 'Kutilayotgan arizalar', value: courierCounts.pending || 0, icon: 'ti-hourglass' },
          { label: 'Faol kuryerlar', value: courierCounts.approved || 0, icon: 'ti-bike' },
          { label: 'Hozir online', value: onlineCount, icon: 'ti-broadcast' },
          { label: 'Kuryerlar balansi', value: `${fmt(totalBalance)} so'm`, icon: 'ti-wallet' },
        ].map((item, kpiIndex) => (
          <div className="col-xl-3 col-sm-6" key={item.label}>
            <StatWidget index={kpiIndex} label={item.label} value={item.value} />
          </div>
        ))}
      </div>

      {/* ── Jadval Card ── */}
      <div className="card border-0 shadow-sm">
        <div className="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between gap-2 flex-wrap py-3">
          <div>
            <h5 className="f-w-600 mb-0">Kuryerlar floti</h5>
            <small className="text-muted">{courierPagination.total} ta kuryer ro'yxatda mavjud</small>
          </div>
          <input
            className="form-control form-control-sm"
            style={{ maxWidth: 300 }}
            value={courierSearch}
            onChange={(e) => setCourierSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && load({ couriers_page: 1 })}
            placeholder="Ism, telefon, viloyat yoki davlat raqami..."
          />
        </div>

        <div className="card-body">
          {/* Segment tabs */}
          <div className="nav kc-segment mb-3">
            {courierTabs.map((tab) => (
              <div key={tab.key} className="nav-item">
                <button
                  className={`nav-link ${courierTab === tab.key ? 'active' : ''}`}
                  onClick={() => {
                    setCourierTab(tab.key);
                    load({ couriers_page: 1, couriers_tab: tab.key });
                  }}
                >
                  {tab.label}
                  <span className="badge text-light-secondary ms-2">{fmt(courierCounts[tab.key] || 0)}</span>
                </button>
              </div>
            ))}
          </div>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead className="bg-light">
                <tr>
                  <th className="ps-3">ID</th>
                  <th>Kuryer</th>
                  <th>Ish holati</th>
                  <th>Hudud</th>
                  <th>Transport</th>
                  <th className="text-end">Buyurtmalar</th>
                  <th className="text-end">Balans</th>
                  <th>Ogohlantirish</th>
                  <th>Holat</th>
                  <th className="pe-3 text-end">Amallar</th>
                </tr>
              </thead>
              <tbody>
                {couriers.length === 0 ? (
                  <tr>
                    <td colSpan={10} className="text-center py-5 text-muted">
                      Hech qanday kuryer topilmadi.
                    </td>
                  </tr>
                ) : (
                  couriers.map((courier) => (
                    <tr key={courier.id}>
                      <td className="ps-3 f-w-600 text-nowrap">#{courier.id}</td>
                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <Avatar row={courier} />
                          <div>
                            <div className="f-w-600 text-primary">{courier.name}</div>
                            <small className="text-muted">{courier.phone || '—'}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span className={`badge border-0 px-2 py-1 ${courier.isOnline ? 'text-light-success' : 'text-light-secondary'}`}>
                          <i className={`ti ${courier.isOnline ? 'ti-point-filled' : 'ti-point'} me-1`}></i>
                          {courier.isOnline ? 'Online' : 'Offline'}
                        </span>
                        <small className="d-block text-muted f-s-11 mt-1">
                          {courier.availabilityUpdatedAt || courier.location?.updatedAt || '—'}
                        </small>
                      </td>
                      <td>{courier.region || '—'}</td>
                      <td>
                        <div className="f-w-500">{courier.transportLabel || courier.transport || '—'}</div>
                        <small className="text-muted font-monospace">{courier.plate || courier.vehicle || 'Raqam kiritilmagan'}</small>
                      </td>
                      <td className="text-end f-w-600 text-nowrap">{courier.orders || 0} ta</td>
                      <td className="text-end f-w-600 text-dark">{fmt(courier.balance || 0)} so'm</td>
                      <td>
                        <span className={`badge border-0 ${(courier.warningCount || 0) > 0 ? 'text-light-warning' : 'text-light-secondary'}`}>
                          {courier.warningCount || 0} / 3
                        </span>
                      </td>
                      <td>
                        <span className={`badge border-0 text-uppercase ${courierChip(courier.status)}`}>
                          {courierLabel(courier.status)}
                        </span>
                      </td>
                      <td className="pe-3 text-end">
                        <div className="d-inline-flex gap-1">
                          <button
                            className="btn btn-light-primary icon-btn w-30 h-30 b-r-22"
                            onClick={() => setSelectedCourier(courier)}
                            title="Ko'rish"
                          >
                            <i className="ti ti-eye"></i>
                          </button>

                          {courier.status !== 'approved' && (
                            <button
                              className="btn btn-light-success icon-btn w-30 h-30 b-r-22"
                              onClick={() => patch(courier.actions?.approveUrl, {}, 'Kuryer tasdiqlansinmi?')}
                              title="Tasdiqlash"
                            >
                              <i className="ti ti-circle-check"></i>
                            </button>
                          )}

                          <button
                            className="btn btn-light-warning icon-btn w-30 h-30 b-r-22"
                            onClick={() => warn(courier)}
                            title="Ogohlantirish"
                          >
                            <i className="ti ti-alert-triangle"></i>
                          </button>

                          <button
                            className="btn btn-light-dark icon-btn w-30 h-30 b-r-22"
                            onClick={() => resetPassword(courier)}
                            title="Parolni tiklash"
                          >
                            <i className="ti ti-key"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {courierPagination.totalPages > 1 && (
          <div className="card-footer bg-transparent border-top py-3">
            <PaginationControls
              {...courierPagination}
              onPageChange={(page) => load({ couriers_page: page })}
            />
          </div>
        )}
      </div>

      <CourierModal
        courier={selectedCourier}
        onHide={() => setSelectedCourier(null)}
        onPatch={patch}
        onWarn={warn}
        onResetPassword={resetPassword}
        onEdit={(courier) => setEditingCourier(courier)}
      />
      <CourierEditModal
        courier={editingCourier}
        onHide={() => setEditingCourier(null)}
      />
    </div>
  );
}

function CourierModal({
  courier,
  onHide,
  onPatch,
  onWarn,
  onResetPassword,
  onEdit,
}: {
  courier: Courier | null;
  onHide: () => void;
  onPatch: (url?: string, data?: Record<string, string>, confirmation?: string) => void;
  onWarn: (courier: Courier) => void;
  onResetPassword: (courier: Courier) => void;
  onEdit: (courier: Courier) => void;
}) {
  const uploadDocument = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!courier?.actions?.documentUploadUrl) return;
    const form = event.currentTarget;
    router.post(courier.actions.documentUploadUrl, new FormData(form), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  return (
    <Modal show={!!courier} onHide={onHide} centered size="xl">
      <Modal.Header closeButton>
        <Modal.Title className="f-s-20 f-w-600">
          Kuryer: {courier?.name} (#{courier?.id})
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {!courier ? null : (
          <div className="row g-3">
            <div className="col-lg-4 col-xxl-3">
              <ProfileCard
                avatar={courier.photo}
                name={courier.name}
                subtitle={courier.phone || 'Telefon yo‘q'}
                badges={
                  <span className={`badge border-0 ${courierChip(courier.status)}`}>
                    {courierLabel(courier.status)}
                  </span>
                }
                stats={[
                  { label: 'Buyurtmalar', value: courier.orders || 0 },
                  { label: 'Balans', value: `${fmt(courier.balance || 0)} so'm` },
                ]}
              />
              <AboutList
                title="Profil"
                rows={[
                  { icon: 'ti-phone', label: 'Telefon', value: courier.phone },
                  { icon: 'ti-map-pin', label: 'Viloyat', value: courier.region },
                  { icon: 'ti-car', label: 'Transport', value: courier.transportLabel || courier.transport },
                  { icon: 'ti-calendar', label: "Qo'shilgan", value: courier.joined },
                  { icon: 'ti-wallet', label: 'Jami ishlagan', value: `${fmt(courier.totalEarned || 0)} so'm` },
                ]}
              />
            </div>
            <div className="col-lg-8 col-xxl-9">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="card h-100">
                    <div className="card-header"><h6 className="mb-0">Transport va avto</h6></div>
                    <div className="card-body">
                      <div>Brand: <strong>{courier.vehicleBrand || '—'}</strong></div>
                      <div>Model: <strong>{courier.vehicleModel || '—'}</strong></div>
                      <div>Rang: <strong>{courier.vehicleColor || '—'}</strong></div>
                      <div>Davlat raqami: <strong>{courier.plate || '—'}</strong></div>
                    </div>
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="card h-100">
                    <div className="card-header"><h6 className="mb-0">Verifikatsiya</h6></div>
                    <div className="card-body">
                      <div>Holati: <span className="badge text-light-primary border-0">{courier.verificationLabel || courier.verificationStatus || '—'}</span></div>
                      <div>Tasdiqlangan sana: <strong>{courier.verifiedAt || '—'}</strong></div>
                      <div className="text-muted f-s-13 mt-2">{courier.verificationNotes || 'Izoh mavjud emas.'}</div>
                    </div>
                  </div>
                </div>

                <div className="col-12">
                  <div className="card">
                    <div className="card-header"><h6 className="mb-0">Hujjat yuklash</h6></div>
                    <div className="card-body">
                      <form onSubmit={uploadDocument} className="row g-2">
                        <div className="col-md-4">
                          <input type="text" name="title" className="form-control form-control-sm" placeholder="Hujjat nomi (Haydovchilik guvohnomasi va h.k.)" required />
                        </div>
                        <div className="col-md-3">
                          <select name="type" className="form-select form-select-sm" required>
                            <option value="license">Haydovchilik guvohnomasi</option>
                            <option value="passport">Pasport / ID karta</option>
                            <option value="tech_passport">Tex passport</option>
                            <option value="contract">Shartnoma</option>
                            <option value="other">Boshqa</option>
                          </select>
                        </div>
                        <div className="col-md-3">
                          <input type="file" name="file" className="form-control form-control-sm" required />
                        </div>
                        <div className="col-md-2">
                          <button type="submit" className="btn btn-sm btn-primary w-100">Yuklash</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        {courier && (
          <>
            <Button variant="outline-warning" onClick={() => onWarn(courier)}>
              <i className="ti ti-alert-triangle me-1"></i>Ogohlantirish
            </Button>
            <Button variant="outline-secondary" onClick={() => onResetPassword(courier)}>
              <i className="ti ti-key me-1"></i>Parol reset
            </Button>
            {courier.actions?.editUrl && (
              <Button variant="outline-primary" onClick={() => onEdit(courier)}>
                <i className="ti ti-edit me-1"></i>Tahrirlash
              </Button>
            )}
          </>
        )}
        <Button variant="light-secondary" onClick={onHide}>
          Yopish
        </Button>
      </Modal.Footer>
    </Modal>
  );
}

function CourierEditModal({
  courier,
  onHide,
}: {
  courier: Courier | null;
  onHide: () => void;
}) {
  const submit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!courier?.actions?.updateUrl) return;
    router.put(courier.actions.updateUrl, Object.fromEntries(new FormData(event.currentTarget)), {
      preserveScroll: true,
      onSuccess: () => onHide(),
    });
  };

  return (
    <Modal show={!!courier} onHide={onHide} centered size="lg">
      <Modal.Header closeButton>
        <Modal.Title className="f-s-20 f-w-600">Kuryerni tahrirlash #{courier?.id}</Modal.Title>
      </Modal.Header>
      <form onSubmit={submit}>
        <Modal.Body>
          {!courier ? null : (
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label f-s-13">Ism</label>
                <input name="first_name" defaultValue={courier.firstName} className="form-control" required />
              </div>
              <div className="col-md-6">
                <label className="form-label f-s-13">Familiya</label>
                <input name="last_name" defaultValue={courier.lastName} className="form-control" />
              </div>
              <div className="col-md-6">
                <label className="form-label f-s-13">Telefon</label>
                <input name="phone_number" defaultValue={courier.phone} className="form-control" required />
              </div>
              <div className="col-md-6">
                <label className="form-label f-s-13">Viloyat</label>
                <input name="region" defaultValue={courier.region} className="form-control" required />
              </div>
              <div className="col-md-6">
                <label className="form-label f-s-13">Holati</label>
                <select name="status" defaultValue={courier.status} className="form-select">
                  <option value="pending">Kutilmoqda</option>
                  <option value="approved">Faol</option>
                  <option value="rejected">Rad etilgan</option>
                  <option value="blocked">Bloklangan</option>
                </select>
              </div>
              <div className="col-md-6">
                <label className="form-label f-s-13">Transport turi</label>
                <input name="transport" defaultValue={courier.transport} className="form-control" />
              </div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={onHide}>Bekor qilish</Button>
          <Button type="submit" variant="primary">Saqlash</Button>
        </Modal.Footer>
      </form>
    </Modal>
  );
}
