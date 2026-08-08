import { FormEvent, useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
  Seller, fmt, localDateTimeInput, sellerActivityOptions,
  FormInput, SectionTitle,
} from '../components/SellerCommon';

type EditProps = Seller & { backUrl: string };

export default function SellerEdit() {
  const seller = usePage<EditProps>().props;
  const selectedActivityTypes = seller.activityTypes || [];
  const [legalType, setLegalType] = useState(String(seller.legal?.type || ''));
  const [commissionMode, setCommissionMode] = useState<'global' | 'individual'>(seller.commissionMode || 'global');
  const [promotionAction, setPromotionAction] = useState<'keep' | 'grant' | 'revoke'>('keep');
  const [promotionType, setPromotionType] = useState<'free' | 'fixed_rate'>(seller.commission?.activePromotion?.type || 'free');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setLegalType(String(seller.legal?.type || ''));
    setCommissionMode(seller.commissionMode || 'global');
    setPromotionAction('keep');
    setPromotionType(seller.commission?.activePromotion?.type || 'free');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [seller.id]);

  const isIndividual = legalType === 'individual';
  const isOrganization = ['entrepreneur', 'llc', 'jsc'].includes(legalType);
  const detailUrl = seller.actions?.detailUrl || seller.backUrl;

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!seller.actions?.updateUrl) return;
    const form = new FormData(event.currentTarget);
    form.append('_method', 'put');
    setSaving(true);
    router.post(seller.actions.updateUrl, form, {
      preserveScroll: true,
      onFinish: () => setSaving(false),
    });
  };

  const activePromotion = seller.commission?.activePromotion;
  const defaultPromotionStart = localDateTimeInput();
  const defaultPromotionEnd = localDateTimeInput(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000));

  return (
    <form onSubmit={submit}>
      <Link href={detailUrl} className="back-link">
        <i className="bi bi-arrow-left me-1"></i>{seller.name}
      </Link>

      <div className="page-head">
        <div>
          <h1 className="page-title">Sellerni tahrirlash</h1>
          <p className="page-subtitle">{seller.name} · ID #{seller.id}</p>
        </div>
      </div>

      <div className="row g-3 seller-edit-form">
        <div className="col-12">
          <div className="card-panel">
            <div className="row g-3">
              <SectionTitle title="Asosiy ma'lumotlar" />
              <FormInput name="shop_name" label="Do'kon nomi" defaultValue={seller.shopName || seller.name} required />
              <FormInput name="phone_number" label="Telefon" defaultValue={seller.phone} required />
              <FormInput name="firstname" label="Ism" defaultValue={seller.firstName} />
              <FormInput name="lastname" label="Familiya" defaultValue={seller.lastName} />
              <FormInput name="region" label="Viloyat" defaultValue={seller.region} required />
              <FormInput name="district" label="Tuman" defaultValue={seller.district} />
              <div className="col-md-6">
                <label className="form-label">Faoliyat turlari</label>
                <div className="d-flex flex-wrap gap-2">
                  {sellerActivityOptions.map((option) => (
                    <label className="chip chip-gray" key={option.value} style={{ cursor: 'pointer' }}>
                      <input
                        className="form-check-input me-2"
                        type="checkbox"
                        name="activity_types[]"
                        value={option.value}
                        defaultChecked={selectedActivityTypes.includes(option.value)}
                      />
                      {option.label}
                    </label>
                  ))}
                </div>
                <div className="form-text">Business appdagi mahsulot qo'shish tanlovi shu maydonga qarab ishlaydi.</div>
              </div>
              <div className="col-md-6">
                <label className="form-label">Status</label>
                <select name="status" defaultValue={seller.status || 'pending'} className="form-select">
                  <option value="pending">Kutilmoqda</option>
                  <option value="approved">Faol</option>
                  <option value="rejected">Bekor qilingan</option>
                  <option value="blocked">Bloklangan</option>
                </select>
              </div>
              <FormInput name="balance" label="Balans" type="number" defaultValue={seller.balance} />
              <FormInput name="password" label="Yangi parol" type="password" hint="Bo'sh qoldirilsa o'zgarmaydi" />
              <FormInput name="photo" label="Profil rasmi" type="file" />
            </div>
          </div>
        </div>

        <div className="col-12">
          <div className="card-panel">
            <div className="row g-3">
              <SectionTitle title="Komissiya va vaqtinchalik imtiyoz" />
              <div className="col-md-6">
                <label className="form-label">Asosiy komissiya rejimi</label>
                <select name="commission_mode" value={commissionMode} onChange={(event) => setCommissionMode(event.target.value as 'global' | 'individual')} className="form-select">
                  <option value="global">Global tariflar</option>
                  <option value="individual">Individual foiz</option>
                </select>
                <div className="form-text">Imtiyoz tugagach seller avtomatik shu rejimga qaytadi.</div>
              </div>
              {commissionMode === 'individual'
                ? <FormInput name="commission_percent" label="Individual komissiya %" type="number" defaultValue={seller.commissionRate ?? 1} min={1} max={100} required />
                : <div className="col-md-6"><div className="detail-panel h-100 d-flex align-items-center text-muted small">Buyurtma summasiga mos global komissiya tarifi qo'llanadi.</div></div>}

              {activePromotion ? (
                <div className="col-12">
                  <div className="detail-panel border-success-subtle bg-success-subtle">
                    <div className="fw-semibold">Faol imtiyoz: {activePromotion.type === 'free' ? 'Komissiyasiz' : `${activePromotion.value}% komissiya`}</div>
                    <div className="small text-muted mt-1">{activePromotion.reason} · {activePromotion.endsAtLabel || '—'} gacha</div>
                  </div>
                </div>
              ) : null}

              {(seller.commission?.history || []).length ? (
                <div className="col-12">
                  <div className="detail-panel">
                    <div className="fw-semibold mb-2">Imtiyoz tarixi</div>
                    <div className="d-grid gap-2">
                      {(seller.commission?.history || []).map((promotion) => (
                        <div className="d-flex align-items-start justify-content-between gap-3 border-bottom pb-2" key={promotion.id}>
                          <div>
                            <div className="small fw-semibold">{promotion.type === 'free' ? '0% komissiya' : `${promotion.value}% komissiya`} · {promotion.reason}</div>
                            <div className="text-muted small">{promotion.startsAtLabel || '—'} — {promotion.endsAtLabel || '—'}</div>
                          </div>
                          <span className={`chip ${promotion.status === 'active' ? 'chip-success' : promotion.status === 'scheduled' ? 'chip-info' : 'chip-gray'}`}>
                            {promotion.status === 'active' ? 'Faol' : promotion.status === 'scheduled' ? 'Rejada' : promotion.status === 'revoked' ? 'Bekor qilingan' : 'Tugagan'}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ) : null}

              <div className="col-md-6">
                <label className="form-label">Imtiyoz amali</label>
                <select name="commission_promotion_action" value={promotionAction} onChange={(event) => setPromotionAction(event.target.value as 'keep' | 'grant' | 'revoke')} className="form-select">
                  <option value="keep">O'zgartirmaslik</option>
                  <option value="grant">Yangi imtiyoz berish</option>
                  <option value="revoke">Faol va rejalashtirilgan imtiyozni bekor qilish</option>
                </select>
              </div>
              {promotionAction === 'grant' ? (
                <>
                  <div className="col-md-6">
                    <label className="form-label">Imtiyoz turi</label>
                    <select name="commission_promotion_type" value={promotionType} onChange={(event) => setPromotionType(event.target.value as 'free' | 'fixed_rate')} className="form-select">
                      <option value="free">Komissiyasiz davr (0%)</option>
                      <option value="fixed_rate">Pasaytirilgan komissiya</option>
                    </select>
                  </div>
                  {promotionType === 'fixed_rate'
                    ? <FormInput name="commission_promotion_value" label="Imtiyozli komissiya %" type="number" defaultValue={activePromotion?.value ?? 0} min={0} max={100} required />
                    : <input type="hidden" name="commission_promotion_value" value="0" />}
                  <FormInput name="commission_promotion_starts_at" label="Boshlanish vaqti" type="datetime-local" defaultValue={defaultPromotionStart} required />
                  <FormInput name="commission_promotion_ends_at" label="Tugash vaqti" type="datetime-local" defaultValue={defaultPromotionEnd} required />
                  <FormInput name="commission_promotion_reason" label="Imtiyoz sababi" defaultValue="Yangi seller uchun onboarding imtiyozi" required />
                  <div className="col-md-6">
                    <label className="form-label">Ichki izoh</label>
                    <textarea name="commission_promotion_notes" className="form-control" rows={2}></textarea>
                  </div>
                </>
              ) : null}
            </div>
          </div>
        </div>

        <div className="col-12">
          <div className="card-panel">
            <div className="row g-3">
              <SectionTitle title="Huquqiy tur va rekvizitlar" />
              <div className="col-md-6">
                <label className="form-label">Seller turi</label>
                <select name="legal_type" value={legalType} onChange={(event) => setLegalType(event.target.value)} className="form-select">
                  <option value="">Tanlanmagan</option>
                  <option value="individual">Jismoniy shaxs</option>
                  <option value="entrepreneur">YTT</option>
                  <option value="llc">MChJ</option>
                  <option value="jsc">AJ / OAJ</option>
                </select>
                <div className="form-text">Tanlangan turga qarab faqat kerakli rekvizitlar ochiladi.</div>
              </div>
              {!legalType ? <div className="col-md-6"><div className="detail-panel h-100 d-flex align-items-center text-muted">Avval seller yuridik turini tanlang.</div></div> : null}

              {isIndividual ? (
                <>
                  <FormInput name="passport_series" label="Pasport seriyasi" defaultValue={seller.legal?.passportSeries as string | undefined} />
                  <FormInput name="passport_number" label="Pasport raqami" defaultValue={seller.legal?.passportNumber as string | undefined} />
                  <FormInput name="passport_issued_by" label="Pasport kim tomonidan berilgan" defaultValue={String(seller.legal?.passportIssuedBy || '')} />
                  <FormInput name="passport_issued_at" label="Pasport berilgan sana" type="date" defaultValue={String(seller.legal?.passportIssuedAt || '')} />
                  <FormInput name="payment_card" label="Karta" defaultValue={String(seller.bank?.rawCard || '')} />
                  <FormInput name="card_holder" label="Karta egasi" defaultValue={String(seller.bank?.cardHolder || '')} />
                </>
              ) : null}
              {isOrganization ? (
                <>
                  <FormInput name="inn" label="STIR" defaultValue={String(seller.legal?.inn || '')} />
                  <FormInput name="bank_name" label="Bank" defaultValue={String(seller.bank?.name || '')} />
                  <FormInput name="bank_account" label="Hisob raqam" defaultValue={String(seller.bank?.rawAccount || '')} />
                  <FormInput name="bank_mfo" label="MFO" defaultValue={String(seller.bank?.mfo || '')} />
                  <FormInput name="bank_swift" label="SWIFT" defaultValue={String(seller.bank?.swift || '')} />
                  <div className="col-12">
                    <label className="form-label">Yuridik manzil</label>
                    <textarea name="legal_address" defaultValue={String(seller.legal?.legalAddress || seller.address || '')} className="form-control" rows={2}></textarea>
                  </div>
                </>
              ) : null}
            </div>
          </div>
        </div>

        <div className="col-12">
          <div className="card-panel">
            <div className="row g-3">
              <SectionTitle title="Shartnoma" />
              <FormInput name="contract_number" label="Shartnoma raqami" defaultValue={String(seller.contract?.number || '')} />
              <div className="col-md-6">
                <label className="form-label">Shartnoma holati</label>
                <select name="contract_status" defaultValue={String(seller.contract?.rawStatus || 'none')} className="form-select">
                  <option value="none">Mavjud emas</option>
                  <option value="active">Faol</option>
                  <option value="expiring">Tugash arafasida</option>
                  <option value="expired">Tugagan</option>
                  <option value="terminated">To'xtatilgan</option>
                </select>
              </div>
              <FormInput name="contract_signed_at" label="Imzolangan sana" type="date" defaultValue={String(seller.contract?.signedAt || '')} />
              <FormInput name="contract_expires_at" label="Tugash sanasi" type="date" defaultValue={String(seller.contract?.expiresAt || '')} />
              <div className="col-md-6 form-check ms-2 d-flex align-items-center">
                <input className="form-check-input" name="contract_signed" value="1" type="checkbox" defaultChecked={Boolean(seller.contract?.signed)} id="seller-contract-signed" />
                <label className="form-check-label ms-2" htmlFor="seller-contract-signed">Shartnoma imzolangan</label>
              </div>
              <div className="col-12">
                <label className="form-label">Shartnoma izohi</label>
                <textarea name="contract_notes" defaultValue={String(seller.contract?.notes || '')} className="form-control" rows={2}></textarea>
              </div>
            </div>
          </div>
        </div>

        <div className="col-12">
          <div className="card-panel">
            <div className="row g-3">
              <SectionTitle title="Premium" />
              <div className="col-md-6">
                <label className="form-label">Premium amal</label>
                <select name="premium_action" defaultValue="keep" className="form-select">
                  <option value="keep">O'zgartirmaslik</option>
                  <option value="grant">Premium berish / uzaytirish</option>
                  <option value="revoke">Premiumni bekor qilish</option>
                </select>
                {seller.premium ? <div className="form-text">Joriy holat: Premium faol{seller.premiumExpiresAt ? ` · ${seller.premiumExpiresAt} gacha` : ''}.</div> : <div className="form-text">Joriy holat: Premium yo'q.</div>}
              </div>
              <div className="col-md-6">
                <label className="form-label">Premium tarif</label>
                <select name="premium_plan" defaultValue="" className="form-select">
                  <option value="">Tanlang</option>
                  {(seller.premiumPlans || []).map((plan) => <option value={plan.type} key={plan.type}>{plan.label} · {fmt(plan.price)} so'm</option>)}
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="edit-save-bar">
        <div className="text-muted small d-none d-md-block">{seller.name} ma'lumotlarini tahrirlamoqdasiz</div>
        <div className="d-flex gap-2 ms-auto">
          <Link href={detailUrl} className="btn btn-light">Bekor qilish</Link>
          <button type="submit" className="btn btn-primary-gradient border-0" disabled={saving}>
            {saving ? <><span className="spinner-border spinner-border-sm me-2"></span>Saqlanmoqda...</> : <><i className="bi bi-check2-circle me-1"></i>Saqlash</>}
          </button>
        </div>
      </div>
    </form>
  );
}
