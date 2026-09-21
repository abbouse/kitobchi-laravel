import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Promo {
  id: number;
  code: string;
  discount: number;
  type: string;
  used: number;
  max: number;
  status: string;
  maxDiscount?: number;
  minOrder?: number;
  perUserLimit?: number;
  eligibleOrderCount?: number;
  expiresAt?: string;
  createUrl?: string;
  generateUrl?: string;
  updateUrl?: string;
  destroyUrl?: string;
}

const emptyPromo: Partial<Promo> = { code: '', type: 'percent', discount: 10, max: 0, minOrder: 0, maxDiscount: 0, perUserLimit: 1, eligibleOrderCount: 0, status: 'Active' };
const isPercentPromo = (type?: string | null) => type === 'percent';
const promoTypeLabel = (type?: string | null) => isPercentPromo(type) ? 'Foiz' : 'Summa';
const orderRuleLabel = (count?: number | null) => {
  const value = Number(count || 0);
  if (value <= 0) return 'Barchaga';
  return value === 1 ? 'Birinchi buyurtma' : `Birinchi ${value} ta buyurtma`;
};

export default function Promokodlar() {
  const { promocodes = [] } = usePage<{ promocodes?: Promo[] }>().props;
  const [selected, setSelected] = useState<Promo | null>(null);
  const [editing, setEditing] = useState<Partial<Promo> | null>(null);
  const activeCount = promocodes.filter((promo) => promo.status === 'Active').length;
  const usedTotal = useMemo(() => promocodes.reduce((sum, promo) => sum + (promo.used || 0), 0), [promocodes]);

  const destroy = (promo: Promo) => {
    if (!promo.destroyUrl || !confirm(`${promo.code} promokodini o'chirasizmi?`)) return;
    router.delete(promo.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Promokodlar</h1><p className="page-subtitle">Chegirmalar, limitlar va ishlatilish statistikasi</p></div>
        <button className="btn btn-primary-gradient" onClick={() => setEditing(emptyPromo)}><i className="bi bi-plus-lg me-1"></i>Promokod qo'shish</button>
      </div>

      <div className="kpi-strip row g-3 mb-4">
        {[
          { label: 'Jami promokod', value: promocodes.length, icon: 'bi-ticket-perforated', color: 'var(--kc-ink)' },
          { label: 'Faol', value: activeCount, icon: 'bi-check-circle', color: 'var(--kc-ok)' },
          { label: 'Ishlatilgan', value: usedTotal, icon: 'bi-bag-check', color: 'var(--kc-warn)' },
          { label: 'Foizli kodlar', value: promocodes.filter((promo) => promo.type === 'percent').length, icon: 'bi-percent', color: 'var(--kc-cat-violet)' },
        ].map((item) => <div className="col-xl-3 col-md-6" key={item.label}><div className="stat-card"><div className="d-flex align-items-center gap-3"><div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div></div></div></div>)}
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Kod</th><th>Chegirma</th><th>Turi</th><th>Order qoidasi</th><th>Ishlatilgan</th><th>Limit</th><th>Muddati</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>{promocodes.map((promo) => {
              const percent = promo.max > 0 ? Math.min(100, Math.round((promo.used / promo.max) * 100)) : 0;
              return <tr key={promo.id}><td className="cell-id">#{promo.id}</td><td className="fw-bold" style={{ fontFamily: 'monospace', letterSpacing: 1 }}>{promo.code}</td><td className="fw-bold text-success">{isPercentPromo(promo.type) ? `${promo.discount}%` : `${fmt(promo.discount)} so'm`}</td><td><span className="chip chip-purple">{promoTypeLabel(promo.type)}</span></td><td><span className="chip chip-gray">{orderRuleLabel(promo.eligibleOrderCount)}</span></td><td>{promo.used} / {promo.max || '∞'}</td><td><div className="progress" style={{ width: 90, height: 6 }}><div className="progress-bar" style={{ width: `${percent}%`, background: percent > 80 ? 'var(--kc-danger)' : 'var(--kc-ok)' }}></div></div></td><td className="text-muted">{promo.expiresAt || '—'}</td><td><span className={`chip ${promo.status === 'Active' ? 'chip-success' : 'chip-gray'}`}>{promo.status}</span></td><td><button className="btn btn-sm btn-light me-1" onClick={() => setSelected(promo)}><i className="bi bi-eye"></i></button><button className="btn btn-sm btn-light me-1" onClick={() => setEditing(promo)}><i className="bi bi-pencil"></i></button><button className="btn btn-sm btn-light text-danger" onClick={() => destroy(promo)}><i className="bi bi-trash"></i></button></td></tr>;
            })}</tbody>
          </table>
        </div>
      </div>

      <PromoView promo={selected} onHide={() => setSelected(null)} onEdit={() => { setEditing(selected); setSelected(null); }} />
      <PromoForm promo={editing} generateUrl={promocodes[0]?.generateUrl || '/boshqaruv/promokodlar/generate'} onHide={() => setEditing(null)} />
    </div>
  );
}

function PromoView({ promo, onHide, onEdit }: { promo: Promo | null; onHide: () => void; onEdit: () => void }) {
  return <Modal show={!!promo} onHide={onHide} centered><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Promokod: {promo?.code}</Modal.Title></Modal.Header><Modal.Body><div className="row g-3"><Info label="Kod" value={promo?.code} mono /><Info label="Chegirma" value={isPercentPromo(promo?.type) ? `${promo?.discount}%` : `${fmt(promo?.discount || 0)} so'm`} /><Info label="Min. buyurtma" value={`${fmt(promo?.minOrder || 0)} so'm`} /><Info label="Max. chegirma" value={`${fmt(promo?.maxDiscount || 0)} so'm`} /><Info label="Bir user limiti" value={promo?.perUserLimit || 1} /><Info label="Order qoidasi" value={orderRuleLabel(promo?.eligibleOrderCount)} /><Info label="Ishlatilgan" value={`${promo?.used} / ${promo?.max || '∞'}`} /></div></Modal.Body><Modal.Footer><Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button><Button variant="light" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function PromoForm({ promo, generateUrl, onHide }: { promo: Partial<Promo> | null; generateUrl: string; onHide: () => void }) {
  const isEdit = !!promo?.id;
  const [code, setCode] = useState(promo?.code || '');

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    if (isEdit) form.append('_method', 'put');
    router.post(isEdit ? String(promo?.updateUrl) : '/boshqaruv/promokodlar', form, { preserveScroll: true, onSuccess: onHide });
  };

  const generate = async () => {
    const response = await fetch(generateUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (response.ok) setCode((await response.json()).code || '');
  };

  return <Modal show={!!promo} onHide={onHide} centered><form onSubmit={submit}><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{isEdit ? 'Promokodni tahrirlash' : "Promokod qo'shish"}</Modal.Title></Modal.Header><Modal.Body><div className="row g-3"><div className="col-md-8"><label className="form-label">Kod</label><input name="code" value={code} onChange={(e) => setCode(e.target.value.toUpperCase())} disabled={isEdit} required className="form-control" /></div><div className="col-md-4 d-flex align-items-end"><button type="button" className="btn btn-light w-100" onClick={generate} disabled={isEdit}>Generate</button></div><Field name="amount" label="Chegirma" type="number" defaultValue={promo?.discount} required /><div className="col-md-6"><label className="form-label">Turi</label><select name="type" defaultValue={isPercentPromo(promo?.type) ? 'percent' : 'uzs'} className="form-select"><option value="percent">Foiz</option><option value="uzs">Summa</option></select></div><Field name="max_discount_amount" label="Max chegirma" type="number" defaultValue={promo?.maxDiscount} /><Field name="min_order_amount" label="Min buyurtma" type="number" defaultValue={promo?.minOrder} /><Field name="per_user_limit" label="Bir user limiti" type="number" defaultValue={promo?.perUserLimit || 1} /><Field name="eligible_order_count" label="Birinchi nechta muvaffaqiyatli order" type="number" defaultValue={promo?.eligibleOrderCount || 0} /><Field name="usesLimit" label="Umumiy limit" type="number" defaultValue={promo?.max || 0} /><Field name="expires_at" label="Muddati" type="date" defaultValue={promo?.expiresAt} required /><div className="col-md-6"><label className="form-label">Status</label><select name="status" defaultValue={promo?.status === 'Active' ? '1' : '0'} className="form-select"><option value="1">Faol</option><option value="0">Nofaol</option></select></div><div className="col-12"><div className="small text-muted rounded-3 p-3 bg-light">0 bo'lsa barcha mijozlarga ishlaydi. 1 bo'lsa faqat birinchi muvaffaqiyatli buyurtmaga, 3 bo'lsa birinchi 3 ta muvaffaqiyatli buyurtmaga amal qiladi.</div></div></div></Modal.Body><Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer></form></Modal>;
}

function Field({ name, label, type = 'text', defaultValue, required }: { name: string; label: string; type?: string; defaultValue?: string | number | null; required?: boolean }) {
  return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} type={type} defaultValue={defaultValue ?? ''} required={required} className="form-control" /></div>;
}

function Info({ label, value, mono }: { label: string; value?: string | number | null; mono?: boolean }) {
  return <div className="col-6"><small className="text-muted">{label}</small><div className="fw-bold" style={mono ? { fontFamily: 'monospace' } : undefined}>{value ?? '—'}</div></div>;
}
