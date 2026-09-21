import { ReactNode, useState } from 'react';
import { Button } from 'react-bootstrap';
import Modal from './AppModal';

// ── Sellerlar bo'limi uchun umumiy tip va yordamchilar ───────────────────
// SellerOrders (ro'yxat), SellerDetail (profil) va SellerEdit (tahrirlash)
// sahifalari orasida takrorlanmasligi uchun shu yerga chiqarilgan.

export type StatusMeta = { label: string; badge?: string };
export type Counts = Record<string, number>;
export type PaginationMeta = { page: number; totalPages: number; from: number; to: number; total: number };

export interface Seller {
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
  activityTypes?: string[];
  activityTypeLabels?: string[];
  status?: string;
  verified?: boolean;
  hidden?: boolean;
  premium?: boolean;
  premiumExpiresAt?: string;
  rating?: number;
  ratingReviewsCount?: number;
  reputationScore?: number;
  karma?: number;
  karmaCode?: string;
  karmaLabelUz?: string;
  karmaLabelRu?: string;
  karmaHintUz?: string;
  karmaHintRu?: string;
  productScore?: number;
  responseScore?: number;
  successScore?: number;
  catalogHealth?: number;
  balance?: number;
  totalRevenue?: number;
  commissionMode?: 'global' | 'individual';
  commissionRate?: number | null;
  commissionBenefitTotal?: number;
  commission?: {
    mode?: 'global' | 'individual';
    individualRate?: number | null;
    activePromotion?: {
      id: number;
      type: 'free' | 'fixed_rate';
      value: number;
      reason: string;
      notes?: string | null;
      startsAt?: string | null;
      endsAt?: string | null;
      endsAtLabel?: string | null;
    } | null;
    history?: Array<{
      id: number;
      type: 'free' | 'fixed_rate';
      value: number;
      reason: string;
      startsAtLabel?: string | null;
      endsAtLabel?: string | null;
      revokedAtLabel?: string | null;
      status: 'active' | 'scheduled' | 'ended' | 'revoked';
    }>;
  };
  products?: number;
  books?: number;
  stationeries?: number;
  orders?: number;
  warningCount?: number;
  legal?: Record<string, string | null | undefined>;
  bank?: Record<string, string | null | undefined>;
  contract?: Record<string, string | number | boolean | null | undefined>;
  qr?: Record<string, string | null | undefined>;
  locations?: Array<Record<string, string | number | boolean | null | undefined | Record<string, string>>>;
  documents?: Array<Record<string, string | number | null | undefined>>;
  contractHistory?: Array<Record<string, string | number | null | undefined>>;
  premiumPlans?: Array<{ type: string; label: string; price: number }>;
  recentOrders?: Array<Record<string, string | number | null | undefined>>;
  transactions?: Array<Record<string, string | number | null | undefined>>;
  banLogs?: Array<Record<string, string | number | boolean | null | undefined>>;
  actions?: Record<string, string>;
}

export interface SellerOrderRow {
  id: number;
  orderId?: number;
  sellerId?: number;
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
  // Do'kon-egalik almashtirish — faqat superadmin uchun (backend
  // canReassign'ni faqat superadmin bo'lsa hisoblaydi, boshqalarga har doim
  // false keladi).
  canReassign?: boolean;
  reassignUrl?: string;
}

export type ReassignSellerOption = { id: number; name: string; isActive?: boolean };

export const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

export const localDateTimeInput = (value?: string | Date | null) => {
  const date = value ? new Date(value) : new Date();
  if (Number.isNaN(date.getTime())) return '';
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
  return local.toISOString().slice(0, 16);
};

export const badgeClass = (badge?: string) => {
  if (badge === 'badge-success') return 'text-light-success';
  if (badge === 'badge-danger') return 'text-light-danger';
  if (badge === 'badge-warning') return 'text-light-warning';
  if (badge === 'badge-info') return 'text-light-info';
  return 'text-light-secondary';
};

export const sellerChip = (status?: string) => {
  if (status === 'approved') return 'text-light-success';
  if (status === 'pending') return 'text-light-warning';
  if (status === 'rejected' || status === 'blocked') return 'text-light-danger';
  return 'text-light-secondary';
};

export const sellerLabel = (status?: string) => ({
  pending: 'Kutilmoqda',
  approved: 'Faol',
  rejected: 'Bekor qilingan',
  blocked: 'Bloklangan',
}[String(status || '')] || status || '—');

export const karmaChip = (code?: string) => {
  if (code === 'elite') return 'text-light-success';
  if (code === 'strong') return 'text-light-info';
  if (code === 'stable') return 'text-light-warning';
  if (code === 'growing') return 'text-light-secondary';
  return 'text-light-danger';
};

export const sellerActivityOptions = [
  { value: 'Kitob', label: 'Kitob' },
  { value: 'Kanstovar', label: 'Kanselyariya' },
];

export function initialsOf(name?: string): string {
  const value = (name || '').trim();
  return value ? value.slice(0, 2).toUpperCase() : 'SL';
}

export function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return (
    <div className="col-xl-6">
      <div className="card h-100"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body">
          <ul className="list-group list-group-flush">
            {rows.map(([label, val]) => <li className="list-group-item d-flex justify-content-between gap-3 px-0" key={label}><span className="text-secondary">{label}</span><span className="f-w-600 text-dark text-end">{val}</span></li>)}
          </ul>
        </div></div>
    </div>
  );
}

export function ListBlock<T>({ title, empty, items, render, action }: { title: string; empty: string; items: T[]; render: (item: T) => ReactNode; action?: ReactNode }) {
  return (
    <div className="col-xl-6">
      <div className="card h-100"><div className="card-header d-flex align-items-center justify-content-between">
          <h5 className="mb-0">{title}</h5>
          {action}
        </div><div className="card-body">
          <div className="d-grid gap-2">
            {items.map((item, index) => <div className="b-1-light b-r-15 p-3" key={index}>{render(item)}</div>)}
            {items.length === 0 ? <div className="text-muted">{empty}</div> : null}
          </div>
        </div></div>
    </div>
  );
}

export function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted f-s-13">Xarita linki yo'q</span>;

  return (
    <div className="d-flex gap-2 flex-wrap mt-2">
      {mapLinks.google ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="ti ti-map-pin me-1"></i>Google Map</a> : null}
      {mapLinks.yandex ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="ti ti-map me-1"></i>Yandex Map</a> : null}
    </div>
  );
}

export function FormInput({ name, label, defaultValue, required, type = 'text', min, max, hint, disabled }: {
  name: string; label: string; defaultValue?: string | number | null; required?: boolean; type?: string; min?: number; max?: number; hint?: string; disabled?: boolean;
}) {
  return (
    <div className="col-md-6">
      <label className="form-label">{label}</label>
      <input name={name} type={type} defaultValue={defaultValue ?? ''} required={required} min={min} max={max} disabled={disabled} className="form-control" />
      {hint ? <div className="form-text">{hint}</div> : null}
    </div>
  );
}

export function SectionTitle({ title, hint }: { title: string; hint?: string }) {
  return (
    <div className="col-12 mt-4">
      <h6 className="f-w-600 mb-0">{title}</h6>
      {hint ? <div className="text-muted f-s-13">{hint}</div> : null}
    </div>
  );
}


// ── Do'kon-egalik almashtirish modali ────────────────────────────────────
// SellerOrders.tsx VA Orders.tsx (asosiy Buyurtmalar sahifasi) ikkalasida
// ham ishlatiladi — shu sabab shu yerga (umumiy joyga) chiqarilgan, aks
// holda ikki nusxa vaqt o'tishi bilan bir-biridan farqlanib qolishi mumkin
// edi. Ikkala sahifaning o'z (bir-biridan biroz farqli) SellerOrder
// tiplari bor, shu sabab bu yerda faqat HAQIQATDA kerak bo'lgan
// maydonlarga ega minimal (strukturaviy mos keluvchi) tip ishlatiladi.
export type ReassignableSellerOrder = {
  id: number;
  orderId?: number;
  sellerId?: number;
  seller?: string | null;
};

export function ReassignSellerModal({ order, sellers, onHide, onSubmit }: {
  order: ReassignableSellerOrder | null;
  sellers: ReassignSellerOption[];
  onHide: () => void;
  onSubmit: (sellerId: number) => void;
}) {
  // Do'kon soni yuzlab bo'lishi mumkin (backend 500 tagacha yuboradi) —
  // oddiy <select> bilan qidirish noqulay, shu sabab yozib qidirish (nomi
  // bo'yicha filtrlaydigan) maydonga almashtirildi (2026-09).
  const [query, setQuery] = useState('');
  const [selected, setSelected] = useState<ReassignSellerOption | null>(null);

  const reset = () => {
    setQuery('');
    setSelected(null);
  };

  const matches = query.trim() === ''
    ? []
    : sellers
        .filter((s) => !order || s.id !== order.sellerId)
        .filter((s) => s.name.toLowerCase().includes(query.trim().toLowerCase()))
        .slice(0, 20);

  return (
    <Modal show={!!order} onHide={onHide} centered onExited={reset}>
      <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Do'konni almashtirish</Modal.Title></Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div>
            <p className="text-muted mb-3">
              Seller order #{order.id} (asosiy buyurtma #{order.orderId || '—'}) hozir <strong>{order.seller}</strong> do'koniga tegishli.
              Bu amal egalikni butunlay boshqa do'konga o'tkazadi: narx, mahsulot, manzil o'zgarmaydi; eski do'konning ombor
              zaxirasi avtomatik qaytariladi. Agar buyurtma hali kuryerga topshirilmagan bo'lsa — yangi do'kon buni "yangi"
              sifatida ko'rib, o'zi qabul qilishi kerak bo'ladi. Agar buyurtma ALLAQACHON kuryerga topshirilgan bo'lsa —
              kuryer topshirig'i va uning narxi O'ZGARTIRILMAYDI (kuryerni xabardor qilish operator zimmasida), faqat
              buyurtma egaligi (kim to'lov oladi) almashtiriladi.
            </p>
            <label className="form-label f-w-600">Yangi do'kon</label>
            {selected ? (
              <div className="d-flex align-items-center justify-content-between b-1-light b-r-10 px-3 py-2">
                <span className="f-w-600">
                  {selected.name}
                  {selected.isActive === false && (
                    <span className="badge bg-light-warning text-warning-dark ms-2">aktiv emas</span>
                  )}
                </span>
                <button type="button" className="btn btn-sm btn-link text-decoration-none p-0" onClick={() => setSelected(null)}>
                  O'zgartirish
                </button>
              </div>
            ) : (
              <div>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Do'kon nomini yozing..."
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  autoFocus
                />
                {query.trim() !== '' && (
                  <div className="b-1-light b-r-10 mt-1 overflow-y-auto" style={{ maxHeight: 220 }}>
                    {matches.length === 0 ? (
                      <div className="px-3 py-2 text-muted f-s-13">Shu nomdagi do'kon topilmadi</div>
                    ) : (
                      matches.map((s) => (
                        <button
                          type="button"
                          key={s.id}
                          className="d-block w-100 text-start btn btn-light-secondary border-0 b-r-0 px-3 py-2"
                          onClick={() => { setSelected(s); setQuery(''); }}
                        >
                          {s.name}
                          {s.isActive === false && (
                            <span className="badge bg-light-warning text-warning-dark ms-2">aktiv emas</span>
                          )}
                        </button>
                      ))
                    )}
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        <Button variant="light-secondary" onClick={onHide}>Bekor qilish</Button>
        <Button
          variant="primary"
          disabled={!selected}
          onClick={() => {
            if (!selected) return;
            if (!confirm("Buyurtma egaligini boshqa do'konga o'tkazishni tasdiqlaysizmi? Bu amalni qaytarib bo'lmaydi.")) return;
            onSubmit(selected.id);
          }}
        >
          Tasdiqlash
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
