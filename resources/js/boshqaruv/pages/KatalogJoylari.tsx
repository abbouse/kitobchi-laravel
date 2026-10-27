import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import ModerationRejectModal from '../components/ModerationRejectModal';
import { EmptyState, StatWidget } from '../components/Axelit';
import FormAction from '../components/FormAction';

interface SlotRow {
  id: number;
  status: string;
  seller?: string | null;
  sellerId: number;
  sellerVerified: boolean;
  sellerRating: number;
  bookId: number;
  bookName?: string | null;
  editionId: number;
  editionTitle?: string | null;
  editionAuthor?: string | null;
  isbn?: string | null;
  offersCount: number;
  days: number;
  price: number;
  refunded: boolean;
  rejectReason?: string | null;
  startsAt?: string | null;
  endsAt?: string | null;
  createdAt?: string | null;
  editionUrl: string;
  approveUrl: string;
  rejectUrl: string;
  stopUrl: string;
}

type Props = {
  items: SlotRow[];
  pagination: { page: number; totalPages: number; from: number; to: number; total: number };
  filters: { tab?: string };
  counts: Record<string, number>;
  settings: { price_per_month: number; min_days: number; max_days: number; is_active: boolean; saveUrl: string };
  revenue: { total: number; active: number };
};

const STATUS: Record<string, [string, string]> = {
  pending: ['Kutilmoqda', 'text-light-warning'],
  active: ['Faol', 'text-light-success'],
  expired: ['Muddati tugagan', 'text-light-secondary'],
  rejected: ['Rad etilgan', 'text-light-danger'],
  cancelled: ['Bekor qilingan', 'text-light-secondary'],
};

const money = (value: number) => new Intl.NumberFormat('ru-RU').format(value || 0);

export default function KatalogJoylari() {
  const { items = [], pagination, filters = {}, counts = {}, settings, revenue } = usePage<Props>().props;
  const tab = filters.tab || 'pending';
  const [rejectTarget, setRejectTarget] = useState<SlotRow | null>(null);

  const load = (params: Record<string, string | number>) => {
    router.get('/boshqaruv/catalog-slots', { tab, ...params }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const perDay = settings.price_per_month ? Math.round(settings.price_per_month / 30) : 0;

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Katalog joylari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">
            Do'kon pul to'lab kitob kartasida birinchi va tanlangan taklif bo'lib turadi. Bitta kartada bitta joy.
          </p>
        </div>
        <div className="kb-page-actions">
          <FormAction
            label="Narx sozlamasi"
            icon="ti ti-settings"
            variant="light-secondary"
            size="md"
            title="Katalog joyi narxi"
            description="Oylik narx kiritiladi, do'kon tanlagan kunga proporsional hisoblanadi (narx / 30 × kun)."
            onSubmit={(event) => {
              const form = new FormData(event.currentTarget);
              event.preventDefault();
              router.post(settings.saveUrl, {
                price_per_month: Number(form.get('price_per_month') || 0),
                min_days: Number(form.get('min_days') || 1),
                max_days: Number(form.get('max_days') || 30),
                is_active: form.get('is_active') === 'on',
              }, { preserveScroll: true });
            }}
          >
            <div className="row g-3">
              <div className="col-12">
                <label className="form-label">Oylik narx (so'm)</label>
                <input name="price_per_month" type="number" min={0} className="form-control" defaultValue={settings.price_per_month} required />
                <small className="text-secondary">Bir kunlik: {money(perDay)} so'm</small>
              </div>
              <div className="col-6">
                <label className="form-label">Eng kam kun</label>
                <input name="min_days" type="number" min={1} max={365} className="form-control" defaultValue={settings.min_days} required />
              </div>
              <div className="col-6">
                <label className="form-label">Eng ko'p kun</label>
                <input name="max_days" type="number" min={1} max={365} className="form-control" defaultValue={settings.max_days} required />
              </div>
              <div className="col-12">
                <div className="form-check form-switch">
                  <input name="is_active" type="checkbox" className="form-check-input" id="slot-active" defaultChecked={settings.is_active} />
                  <label className="form-check-label" htmlFor="slot-active">Sotuvda (do'konlar sotib ola oladi)</label>
                </div>
              </div>
            </div>
          </FormAction>
        </div>
      </div>

      {!settings.is_active || !settings.price_per_month ? (
        <div className="alert alert-light-warning">
          <i className="ti ti-alert-triangle me-1"></i>
          Katalog joyi hozir sotuvda emas — do'konlar sotib ololmaydi. Narxni kiriting va "Sotuvda" ni yoqing.
        </div>
      ) : null}

      <div className="row g-3 mb-3">
        <div className="col-md-4"><StatWidget label="Jami tushum" value={`${money(revenue.total)} so'm`} variant="success" /></div>
        <div className="col-md-4"><StatWidget label="Faol joylardan" value={`${money(revenue.active)} so'm`} variant="primary" /></div>
        <div className="col-md-4"><StatWidget label="Narx" value={settings.price_per_month ? `${money(settings.price_per_month)} so'm / oy` : 'Belgilanmagan'} sub={settings.is_active ? `${settings.min_days}–${settings.max_days} kun` : 'Sotuvda emas'} variant={settings.is_active ? 'info' : 'warning'} /></div>
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Joylar</h5>
            <p className="mb-0 text-secondary f-s-13">{pagination.total} ta topildi</p>
          </div>
          <div className="nav kc-segment">
            {[
              { key: 'pending', label: 'Kutilmoqda' },
              { key: 'active', label: 'Faol' },
              { key: 'expired', label: 'Muddati tugagan' },
              { key: 'rejected', label: 'Rad etilgan' },
            ].map((item) => (
              <div className="nav-item" key={item.key}>
                <button type="button" className={`nav-link ${tab === item.key ? 'active' : ''}`} onClick={() => load({ tab: item.key, page: 1 })}>
                  {item.label}<span className="kb-tab-count">{counts[item.key] || 0}</span>
                </button>
              </div>
            ))}
          </div>
        </div>
        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead><tr>
                <th>Do'kon</th><th>Kitob</th><th>Kartada</th><th>Muddat</th><th>Narx</th><th>Holat</th><th></th>
              </tr></thead>
              <tbody>
                {items.map((item) => {
                  const [statusLabel, statusChip] = STATUS[item.status] || [item.status, 'text-light-secondary'];

                  return (
                    <tr key={item.id}>
                      <td>
                        <span className="f-w-600">{item.seller || '—'}</span>
                        {item.sellerVerified ? <i className="ti ti-discount-check-filled text-primary ms-1"></i> : null}
                        <small className="d-block text-secondary">#{item.id} · reyting {item.sellerRating.toFixed(1)}</small>
                      </td>
                      <td>
                        <Link href={item.editionUrl} className="f-w-600">{item.editionTitle || item.bookName || '—'}</Link>
                        <small className="d-block text-secondary">{item.editionAuthor || ''}{item.isbn ? ` · ${item.isbn}` : ''}</small>
                      </td>
                      <td><span className="badge text-light-info">{item.offersCount} ta do'kon</span></td>
                      <td>
                        <span className="f-w-600">{item.days} kun</span>
                        <small className="d-block text-secondary">
                          {item.startsAt ? `${item.startsAt} → ${item.endsAt}` : item.createdAt}
                        </small>
                      </td>
                      <td>
                        <span className="f-w-600">{money(item.price)}</span>
                        {item.refunded ? <small className="d-block text-success">qaytarilgan</small> : null}
                      </td>
                      <td>
                        <span className={`badge ${statusChip}`}>{statusLabel}</span>
                        {item.rejectReason ? <small className="d-block text-secondary">{item.rejectReason}</small> : null}
                      </td>
                      <td className="text-end">
                        <div className="d-flex gap-1 justify-content-end flex-wrap">
                          {item.status === 'pending' ? (
                            <>
                              <button type="button" className="btn btn-light-success btn-sm"
                                onClick={() => router.post(item.approveUrl, {}, { preserveScroll: true })}>
                                <i className="ti ti-check me-1"></i>Tasdiqlash
                              </button>
                              <button type="button" className="btn btn-light-danger btn-sm" onClick={() => setRejectTarget(item)}>
                                <i className="ti ti-x me-1"></i>Rad etish
                              </button>
                            </>
                          ) : null}
                          {item.status === 'active' ? (
                            <button type="button" className="btn btn-light-danger btn-sm"
                              onClick={() => {
                                if (window.confirm("Joyni to'xtatasizmi? Pul qaytarilmaydi — muddat sarflangan hisoblanadi.")) {
                                  router.post(item.stopUrl, {}, { preserveScroll: true });
                                }
                              }}>
                              <i className="ti ti-player-pause me-1"></i>To'xtatish
                            </button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          {!items.length ? <EmptyState text="Bu bo'limda joy yo'q" /> : null}
          <PaginationControls page={pagination.page} totalPages={pagination.totalPages} from={pagination.from} to={pagination.to} total={pagination.total} onPageChange={(page) => load({ page })} />
        </div>
      </div>

      <ModerationRejectModal
        show={!!rejectTarget}
        itemLabel={rejectTarget?.editionTitle || rejectTarget?.bookName || undefined}
        onCancel={() => setRejectTarget(null)}
        onConfirm={(reason) => {
          if (rejectTarget) {
            router.post(rejectTarget.rejectUrl, { reason }, { preserveScroll: true });
          }
          setRejectTarget(null);
        }}
      />
    </div>
  );
}
