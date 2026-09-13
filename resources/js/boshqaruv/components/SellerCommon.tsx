import { ReactNode, useState } from 'react';
import { Modal, Button } from 'react-bootstrap';

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

export type ReassignSellerOption = { id: number; name: string };

export const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

export const localDateTimeInput = (value?: string | Date | null) => {
  const date = value ? new Date(value) : new Date();
  if (Number.isNaN(date.getTime())) return '';
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
  return local.toISOString().slice(0, 16);
};

export const badgeClass = (badge?: string) => {
  if (badge === 'badge-success') return 'chip-success';
  if (badge === 'badge-danger') return 'chip-danger';
  if (badge === 'badge-warning') return 'chip-warning';
  if (badge === 'badge-info') return 'chip-info';
  return 'chip-gray';
};

export const sellerChip = (status?: string) => {
  if (status === 'approved') return 'chip-success';
  if (status === 'pending') return 'chip-warning';
  if (status === 'rejected' || status === 'blocked') return 'chip-danger';
  return 'chip-gray';
};

export const sellerLabel = (status?: string) => ({
  pending: 'Kutilmoqda',
  approved: 'Faol',
  rejected: 'Bekor qilingan',
  blocked: 'Bloklangan',
}[String(status || '')] || status || '—');

export const karmaChip = (code?: string) => {
  if (code === 'elite') return 'chip-success';
  if (code === 'strong') return 'chip-info';
  if (code === 'stable') return 'chip-warning';
  if (code === 'growing') return 'chip-gray';
  return 'chip-danger';
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
      <div className="detail-panel h-100">
        <h6 className="fw-bold mb-3">{title}</h6>
        <div className="address-list">
          {rows.map(([label, val]) => <div key={label}><span>{label}</span><strong>{val}</strong></div>)}
        </div>
      </div>
    </div>
  );
}

export function ListBlock<T>({ title, empty, items, render, action }: { title: string; empty: string; items: T[]; render: (item: T) => ReactNode; action?: ReactNode }) {
  return (
    <div className="col-xl-6">
      <div className="detail-panel h-100">
        <div className="d-flex align-items-center justify-content-between mb-3">
          <h6 className="fw-bold mb-0">{title}</h6>
          {action}
        </div>
        <div className="d-grid gap-2">
          {items.map((item, index) => <div className="mini-stat" key={index}>{render(item)}</div>)}
          {items.length === 0 ? <div className="text-muted">{empty}</div> : null}
        </div>
      </div>
    </div>
  );
}

export function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted small">Xarita linki yo'q</span>;

  return (
    <div className="d-flex gap-2 flex-wrap mt-2">
      {mapLinks.google ? <a className="btn btn-sm btn-light" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="bi bi-geo-alt me-1"></i>Google Map</a> : null}
      {mapLinks.yandex ? <a className="btn btn-sm btn-light" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="bi bi-map me-1"></i>Yandex Map</a> : null}
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
      <h6 className="fw-bold mb-0">{title}</h6>
      {hint ? <div className="text-muted small">{hint}</div> : null}
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
  const [sellerId, setSellerId] = useState<string>('');

  return (
    <Modal show={!!order} onHide={onHide} centered onExited={() => setSellerId('')}>
      <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Do'konni almashtirish</Modal.Title></Modal.Header>
      <Modal.Body>
        {!order ? null : (
          <div>
            <p className="text-muted mb-3">
              Seller order #{order.id} (asosiy buyurtma #{order.orderId || '—'}) hozir <strong>{order.seller}</strong> do'koniga tegishli.
              Bu amal — do'kon buyurtmani hali kuryerga topshirmagan bo'lsa (qabul qilgan bo'lsa ham mumkin) — egalikni butunlay
              boshqa do'konga o'tkazadi: narx, mahsulot, manzil o'zgarmaydi; eski do'konning ombor zaxirasi avtomatik qaytariladi;
              yangi do'kon buyurtmani "yangi" sifatida ko'rib, o'zi qabul qilishi kerak bo'ladi.
            </p>
            <label className="form-label fw-semibold">Yangi do'kon</label>
            <select className="form-select" value={sellerId} onChange={(e) => setSellerId(e.target.value)}>
              <option value="">— tanlang —</option>
              {sellers.filter((s) => s.id !== order.sellerId).map((s) => (
                <option key={s.id} value={s.id}>{s.name}</option>
              ))}
            </select>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer>
        <Button variant="light" onClick={onHide}>Bekor qilish</Button>
        <Button
          variant="primary"
          disabled={!sellerId}
          onClick={() => {
            if (!sellerId) return;
            if (!confirm("Buyurtma egaligini boshqa do'konga o'tkazishni tasdiqlaysizmi? Bu amalni qaytarib bo'lmaydi.")) return;
            onSubmit(Number(sellerId));
          }}
        >
          Tasdiqlash
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
