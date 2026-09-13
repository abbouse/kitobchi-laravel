import { FormEvent, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type CourierBonusRule = { from_km?: number | string | null; to_km?: number | string | null; bonus_amount?: number | string | null };
type ProjectSettings = Record<string, string | number | boolean | CourierBonusRule[] | null | undefined>;
type ActionMap = Record<string, string>;
type Commission = { id: number; priceFrom: number; priceTo: number; percent: number; updateUrl: string; destroyUrl: string };
type Cashback = { id: number; fromUzs: number; toUzs: number; cashback: number; type: string; updateUrl: string; destroyUrl: string };

type SettingsPayload = {
  project?: ProjectSettings;
  commission?: Commission[];
  cashback?: Cashback[];
  cashbackDelivery?: Cashback[];
  cashbackPickup?: Cashback[];
  actions?: ActionMap;
};

const tabs = [
  { key: 'versions', label: 'App versiyalar', icon: 'bi-phone' },
  { key: 'contacts', label: 'Kontaktlar', icon: 'bi-headset' },
  { key: 'app-flags', label: 'App flaglar', icon: 'bi-toggles' },
  { key: 'courier-bonus', label: 'Kuryer bonus', icon: 'bi-bicycle' },
  { key: 'finance', label: 'Moliya', icon: 'bi-calculator' },
  { key: 'telegram', label: 'Telegram', icon: 'bi-telegram' },
  { key: 'commission', label: 'Komissiya', icon: 'bi-percent' },
  { key: 'cashback', label: 'Cashback', icon: 'bi-cash-stack' },
];

function value(project: ProjectSettings, key: string, fallback = '') {
  const val = project[key];
  return val === null || val === undefined ? fallback : String(val);
}

function checked(project: ProjectSettings, key: string) {
  return Boolean(project[key]);
}

function submitForm(event: FormEvent<HTMLFormElement>, method: 'post' | 'put', url?: string) {
  event.preventDefault();
  if (!url) return;

  // BUG TUZATILDI (2026-09): ilgari `Object.fromEntries(new
  // FormData(form).entries())` ishlatilar edi. Bu "Kuryer km va bonus
  // tizimi" bo'limidagi `courier_bonus_rules[0][from_km]` kabi
  // kvadrat-qavsli (array) maydon nomlarini TO'G'RI ICHKARI massivga
  // aylantirmaydi — ular tekis (flat) obyektga aylanib,
  // "courier_bonus_rules[0][from_km]" degan LITERAL kalit bo'lib
  // qolar edi. Backend (`SettingsController::updateCourierBonus`)
  // esa `courier_bonus_rules` ni haqiqiy massiv deb kutadi — natijada
  // u har doim bo'sh/aniqlanmagan bo'lib qolib, admin masofa
  // bonuslarini kiritsa ham, HAR BIR saqlashda jim tarzda
  // TOZALANIB (0 ga tushirilib) ketardi. Xuddi shu forma-elementi
  // orqali yuborilgan boshqa (flat) maydonlar hech qanday
  // muammosiz ishlagani uchun bu bug fақат shu bo'limda sezilardi.
  //
  // Tuzatish: FormData obyektini o'zini to'g'ridan-to'g'ri yuborish —
  // bu holda kvadrat-qavsli nomlarni PHP/Laravel o'zi to'g'ri
  // ichki massivga aylantiradi (odatdagi HTML forma-yuborish
  // xulq-atvori). Bu yondashuv loyihaning boshqa ko'plab
  // sahifalarida (masalan, Blogerlar.tsx, CourierOrders.tsx,
  // SellerDetail.tsx) allaqachon ishlatilib, `router.put()` uchun
  // ham to'g'ri ishlaydi (Inertia o'zi `_method` spoofingni
  // FormData bilan avtomatik bajaradi).
  const data = new FormData(event.currentTarget);
  const options = { preserveScroll: true };

  if (method === 'post') {
    router.post(url, data, options);
  } else {
    router.put(url, data, options);
  }
}

function destroy(url?: string, label = "O'chirilsinmi?") {
  if (!url || !confirm(label)) return;
  router.delete(url, { preserveScroll: true });
}

function TextInput({ name, label, defaultValue, type = 'text', required = false, min, max, placeholder }: {
  name: string; label: string; defaultValue?: string | number; type?: string; required?: boolean; min?: number; max?: number; placeholder?: string;
}) {
  return (
    <div>
      <label className="form-label small text-muted fw-semibold">{label}</label>
      <input name={name} type={type} min={min} max={max} required={required} className="form-control" defaultValue={defaultValue ?? ''} placeholder={placeholder} />
    </div>
  );
}

function Toggle({ name, label, defaultChecked, icon }: { name: string; label: string; defaultChecked?: boolean; icon: string }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 rounded border">
      <span className="d-flex align-items-center gap-2 fw-semibold"><i className={`bi ${icon} text-primary`}></i>{label}</span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

function SaveButton({ label = 'Saqlash' }: { label?: string }) {
  return <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{label}</button>;
}

function SectionCard({ title, icon, children }: { title: string; icon: string; children: React.ReactNode }) {
  return (
    <div className="card-panel">
      <div className="d-flex align-items-center gap-2 mb-3">
        <i className={`bi ${icon} text-primary`}></i>
        <h5 className="fw-bold mb-0">{title}</h5>
      </div>
      {children}
    </div>
  );
}

export default function Settings() {
  const { settings = {} } = usePage<{ settings: SettingsPayload }>().props;
  const project = settings.project ?? {};
  const actions = settings.actions ?? {};
  const initialTab = useMemo(() => new URLSearchParams(window.location.search).get('tab') || 'versions', []);
  const [tab, setTab] = useState(initialTab);

  const setActiveTab = (key: string) => {
    setTab(key);
    window.history.replaceState(null, '', `${window.location.pathname}?tab=${key}`);
  };

  return (
    <div className="settings-page">
      <div className="page-head">
        <div>
          <h1 className="page-title">Sozlamalar</h1>
          <p className="page-subtitle">App versiyalari, operatsion flaglar, cashback va komissiya sozlamalari</p>
        </div>
      </div>

      <div className="card-panel mb-3">
        <div className="d-flex flex-wrap gap-2">
          {tabs.map((item) => (
            <button key={item.key} type="button" onClick={() => setActiveTab(item.key)} className={`btn ${tab === item.key ? 'btn-primary-gradient' : 'btn-light'}`}>
              <i className={`bi ${item.icon} me-1`}></i>{item.label}
            </button>
          ))}
        </div>
      </div>

      {tab === 'versions' && (
        <SectionCard title="App versiyalari" icon="bi-phone">
          <form onSubmit={(event) => submitForm(event, 'put', actions.versions)}>
            <div className="row g-3">
              {[
                ['Kitobchi Business', 'business'],
                ['Kuryer App', 'courier'],
                ['Market App', 'market'],
              ].map(([label, key]) => (
                <div className="col-lg-4" key={key}>
                  <div className="p-3 rounded border h-100">
                    <div className="fw-bold mb-3">{label}</div>
                    <div className="row g-2">
                      <div className="col-md-6 col-lg-12"><TextInput name={`${key}_version_ios`} label="iOS versiya" required defaultValue={value(project, `${key}_version_ios`)} placeholder="1.0.0" /></div>
                      <div className="col-md-6 col-lg-12"><TextInput name={`${key}_version_android`} label="Android versiya" required defaultValue={value(project, `${key}_version_android`)} placeholder="1.0.0" /></div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'contacts' && (
        <SectionCard title="Kontaktlar" icon="bi-headset">
          <form onSubmit={(event) => submitForm(event, 'put', actions.contacts)}>
            <div className="row g-3">
              {[
                ['Kitobchi Market', 'kitobchi'],
                ['Kitobchi Business', 'business'],
                ['Endi Courier', 'courier'],
              ].map(([label, key]) => (
                <div className="col-xl-4" key={key}>
                  <div className="p-3 rounded border h-100">
                    <div className="fw-bold mb-3">{label}</div>
                    <div className="row g-2">
                      <div className="col-md-6 col-xl-12"><TextInput name={`${key}_phone`} label="Call center raqami" defaultValue={value(project, `${key}_phone`)} placeholder="+998 XX XXX XX XX" /></div>
                      <div className="col-md-6 col-xl-12"><TextInput name={`${key}_email`} label="Email manzil" type="email" defaultValue={value(project, `${key}_email`)} placeholder="support@example.com" /></div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'app-flags' && (
        <SectionCard title="App flaglar va qadoqlash" icon="bi-toggles">
          <form onSubmit={(event) => submitForm(event, 'put', actions.appFlags)}>
            <div className="row g-3">
              <div className="col-lg-6"><Toggle name="on_premium" label="Premium rejim" icon="bi-star-fill" defaultChecked={checked(project, 'on_premium')} /></div>
              <div className="col-lg-6"><Toggle name="on_reels" label="Reels yoqilgan" icon="bi-play-circle-fill" defaultChecked={checked(project, 'on_reels')} /></div>
              <div className="col-lg-6"><Toggle name="ramadan" label="Ramazon rejim" icon="bi-moon-stars-fill" defaultChecked={checked(project, 'ramadan')} /></div>
              <div className="col-lg-6"><Toggle name="stop_sales" label="Savdo to'xtatilgan" icon="bi-slash-circle-fill" defaultChecked={checked(project, 'stop_sales')} /></div>
              <div className="col-lg-6"><Toggle name="show_home_special_sections" label="Mystery box va gift section ko'rsatilsin" icon="bi-layout-text-window-reverse" defaultChecked={project.show_home_special_sections === undefined ? true : checked(project, 'show_home_special_sections')} /></div>
              <div className="col-md-4"><TextInput name="packaging_price_small" label="Kichik qadoqlash (UZS)" type="number" min={0} required defaultValue={value(project, 'packaging_price_small', '25000')} /></div>
              <div className="col-md-4"><TextInput name="packaging_price_large" label="Katta qadoqlash (UZS)" type="number" min={0} required defaultValue={value(project, 'packaging_price_large', '40000')} /></div>
              <div className="col-md-4"><TextInput name="packaging_threshold" label="Chegara (ta kitob)" type="number" min={1} required defaultValue={value(project, 'packaging_threshold', '4')} /></div>
              <div className="col-12"><hr className="my-1" /></div>
              <div className="col-lg-6"><Toggle name="review_cashback_enabled" label="Izoh uchun keshbek yoqilgan" icon="bi-chat-heart-fill" defaultChecked={project.review_cashback_enabled === undefined ? true : checked(project, 'review_cashback_enabled')} /></div>
              <div className="col-md-4"><TextInput name="review_cashback_amount" label="Izoh keshbek miqdori (UZS)" type="number" min={0} max={100000} defaultValue={value(project, 'review_cashback_amount', '100')} /></div>
              <div className="col-12 small text-muted">Mijoz o'zi sotib olgan mahsulotga izoh qoldirsa shu miqdorda keshbek oladi. Har bir mahsulot uchun faqat 1 marta beriladi (nechta izoh yozishidan qat'i nazar).</div>
              <div className="col-12"><hr className="my-1" /></div>
              <div className="col-12">
                <label className="form-label small text-muted fw-semibold">AI bot qo'shimcha qo'llanmasi</label>
                <textarea name="ai_bot_extra_notes" className="form-control" rows={4} maxLength={2000} placeholder="Masalan: Ramazon aksiyasi davomida barcha buyurtmalarga sovg'a qo'shiladi. Ish vaqti: 9:00–21:00." defaultValue={value(project, 'ai_bot_extra_notes', '')} />
                <div className="small text-muted mt-1">Bu matn AI chatbot bilimiga qo'shiladi — aksiyalar, ish vaqti, maxsus qoidalarni shu yerga yozing. Tariflar, yetkazish narxlari va to'lov qoidalari tizimdan avtomatik olinadi, ularni yozish shart emas.</div>
              </div>
            </div>
            <div className="small text-muted mt-3">Agar bu flag o'chirilsa, mystery box va gift certificate sectionlari ilovada yashiriladi va top bannerlar homepage ichida ularning o'rniga tushadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'courier-bonus' && (
        <SectionCard title="Kuryer km va bonus tizimi" icon="bi-bicycle">
          <form onSubmit={(event) => submitForm(event, 'put', actions.courierBonus)}>
            <div className="row g-3">
              <div className="col-md-4"><TextInput name="courier_base_fee" label="Bazaviy haq" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_base_fee', '3000')} /></div>
              <div className="col-md-4"><TextInput name="courier_price_per_km" label="1 km narxi" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_price_per_km', '1500')} /></div>
              <div className="col-md-4"><TextInput name="courier_min_fee" label="Minimal payout" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_min_fee', '5000')} /></div>
              <div className="col-md-4"><TextInput name="seller_courier_min_delivery_price" label="Seller kuryeri minimal narxi" type="number" min={0} max={1000000} required defaultValue={value(project, 'seller_courier_min_delivery_price', '0')} /></div>
              <div className="col-md-8 d-flex align-items-end">
                <div className="small text-muted rounded border p-3 w-100">
                  Seller o'z kuryeri uchun filial narxini bundan arzon qo'ya olmaydi. Narx filialga biriktiriladi, shu filialdagi barcha do'kon kuryerlari bir xil narxda ishlaydi.
                </div>
              </div>
              <div className="col-12">
                <div className="rounded border p-3">
                  <div className="fw-bold mb-2">Masofa bonuslari</div>
                  <div className="small text-muted mb-3">Masalan: 5 km dan 10 km gacha bo'lsa qo'shimcha bonus. Bo'sh qatorlar saqlanmaydi.</div>
                  {Array.from({ length: 5 }).map((_, index) => {
                    const rules = Array.isArray(project.courier_bonus_rules) ? project.courier_bonus_rules as CourierBonusRule[] : [];
                    const rule = rules[index] || {};
                    return (
                      <div className="row g-2 align-items-end mb-2" key={index}>
                        <div className="col-md-4"><TextInput name={`courier_bonus_rules[${index}][from_km]`} label="Dan (km)" type="number" min={0} defaultValue={rule.from_km ?? ''} /></div>
                        <div className="col-md-4"><TextInput name={`courier_bonus_rules[${index}][to_km]`} label="Gacha (km)" type="number" min={0} defaultValue={rule.to_km ?? ''} /></div>
                        <div className="col-md-4"><TextInput name={`courier_bonus_rules[${index}][bonus_amount]`} label="Bonus (so'm)" type="number" min={0} defaultValue={rule.bonus_amount ?? ''} /></div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'telegram' && (
        <SectionCard title="Telegram Login" icon="bi-telegram">
          <form onSubmit={(event) => submitForm(event, 'put', actions.telegram)}>
            <div className="row g-3">
              <div className="col-12"><Toggle name="telegram_login_enabled" label="Telegram login yoqilgan" icon="bi-power" defaultChecked={checked(project, 'telegram_login_enabled')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_client_id" label="Client ID" defaultValue={value(project, 'telegram_client_id')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_scopes" label="Scopes" defaultValue={value(project, 'telegram_scopes', 'openid profile phone')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_redirect_uri_ios" label="iOS Redirect URI" defaultValue={value(project, 'telegram_redirect_uri_ios', 'https://app3206985527-login.tg.dev')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_redirect_uri_android" label="Android Redirect URI" defaultValue={value(project, 'telegram_redirect_uri_android', 'https://app2854400165-login.tg.dev/tglogin')} /></div>
            </div>
            <div className="small text-muted mt-3">Client secret server `.env` faylida saqlanadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'finance' && (
        <SectionCard title="Marketplace moliyaviy sozlamalari" icon="bi-calculator">
          <form onSubmit={(event) => submitForm(event, 'put', actions.finance)}>
            <div className="row g-3">
              <div className="col-lg-4">
                <label className="form-label small text-muted fw-semibold">Soliq hisoblash turi</label>
                <select name="tax_mode" className="form-select" defaultValue={value(project, 'tax_mode', 'fixed')}>
                  <option value="fixed">Belgilangan summa (UZS)</option>
                  <option value="profit_percent">Operatsion foydadan foiz</option>
                </select>
              </div>
              <div className="col-lg-4"><TextInput name="tax_fixed_uzs" label="Soliq summasi (UZS)" type="number" min={0} required defaultValue={value(project, 'tax_fixed_uzs', '0')} /></div>
              <div className="col-lg-4"><TextInput name="tax_profit_percent" label="Foydadan soliq (%)" type="number" min={0} max={100} required defaultValue={value(project, 'tax_profit_percent', '0')} /></div>
              <div className="col-lg-4"><TextInput name="payment_provider_percent" label="Payment provider komissiyasi (%)" type="number" min={0} max={100} required defaultValue={value(project, 'payment_provider_percent', '0')} /></div>
            </div>
            <div className="small text-muted mt-3">Belgilangan soliq summasi har bir hisobot davriga qo‘llanadi. Payment provider komissiyasi paid orderlarga bog‘langan oxirgi muvaffaqiyatli karta tranzaksiyasidan olinadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'commission' && (
        <div className="row g-3">
          <div className="col-xl-8">
            <SectionCard title="Komissiya qoidalari" icon="bi-percent">
              <div className="table-responsive">
                <table className="table data-table align-middle mb-0">
                  <thead><tr><th>Narx dan</th><th>Narx gacha</th><th>Komissiya %</th><th></th></tr></thead>
                  <tbody>
                    {(settings.commission ?? []).map((item) => (
                      <tr key={item.id}>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="priceFrom" type="number" min={0} defaultValue={item.priceFrom} /></td>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="priceTo" type="number" min={1} defaultValue={item.priceTo} /></td>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="percent" type="number" min={0} max={100} defaultValue={item.percent} /></td>
                        <td className="text-end">
                          <form id={`commission-${item.id}`} className="d-inline" onSubmit={(event) => submitForm(event, 'put', item.updateUrl)}>
                            <button className="btn btn-sm btn-light me-1"><i className="bi bi-check2"></i></button>
                          </form>
                          <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(item.destroyUrl, 'Komissiya qoidasi o‘chirilsinmi?')}><i className="bi bi-trash"></i></button>
                        </td>
                      </tr>
                    ))}
                    {(settings.commission ?? []).length === 0 ? <tr><td colSpan={4} className="text-center text-muted py-4">Komissiya qoidalari yo'q</td></tr> : null}
                  </tbody>
                </table>
              </div>
            </SectionCard>
          </div>
          <div className="col-xl-4">
            <SectionCard title="Yangi qoida" icon="bi-plus-circle">
              <form onSubmit={(event) => submitForm(event, 'post', actions.commissionStore)}>
                <div className="row g-2">
                  <div className="col-md-6 col-xl-12"><TextInput name="priceFrom" label="Narx dan" type="number" min={0} required /></div>
                  <div className="col-md-6 col-xl-12"><TextInput name="priceTo" label="Narx gacha" type="number" min={1} required /></div>
                  <div className="col-md-6 col-xl-12"><TextInput name="percent" label="Komissiya %" type="number" min={0} max={100} required /></div>
                </div>
                <div className="text-end mt-3"><SaveButton label="Qo'shish" /></div>
              </form>
            </SectionCard>
          </div>
        </div>
      )}

      {tab === 'cashback' && (
        <div className="row g-3">
          <div className="col-xl-8">
            <SectionCard title="Cashback qoidalari" icon="bi-cash-stack">
              <div className="table-responsive">
                <table className="table data-table align-middle mb-0">
                  <thead><tr><th>Tur</th><th>Xarid dan</th><th>Xarid gacha</th><th>Cashback %</th><th></th></tr></thead>
                  <tbody>
                    {(settings.cashback ?? []).map((item) => (
                      <tr key={item.id}>
                        <td>
                          <select form={`cashback-${item.id}`} className="form-select form-select-sm" name="type" defaultValue={item.type || 'delivery'}>
                            <option value="delivery">Yetkazib berish</option>
                            <option value="pickup">Pickup</option>
                          </select>
                        </td>
                        <td><input form={`cashback-${item.id}`} className="form-control form-control-sm" name="fromUzs" type="number" min={0} defaultValue={item.fromUzs} /></td>
                        <td><input form={`cashback-${item.id}`} className="form-control form-control-sm" name="toUzs" type="number" min={1} defaultValue={item.toUzs} /></td>
                        <td><input form={`cashback-${item.id}`} className="form-control form-control-sm" name="cashback" type="number" min={0} max={100} defaultValue={item.cashback} /></td>
                        <td className="text-end">
                          <form id={`cashback-${item.id}`} className="d-inline" onSubmit={(event) => submitForm(event, 'put', item.updateUrl)}>
                            <button className="btn btn-sm btn-light me-1"><i className="bi bi-check2"></i></button>
                          </form>
                          <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(item.destroyUrl, 'Cashback qoidasi o‘chirilsinmi?')}><i className="bi bi-trash"></i></button>
                        </td>
                      </tr>
                    ))}
                    {(settings.cashback ?? []).length === 0 ? <tr><td colSpan={5} className="text-center text-muted py-4">Cashback qoidalari yo'q</td></tr> : null}
                  </tbody>
                </table>
              </div>
            </SectionCard>
          </div>
          <div className="col-xl-4">
            <SectionCard title="Yangi cashback" icon="bi-plus-circle">
              <form onSubmit={(event) => submitForm(event, 'post', actions.cashbackStore)}>
                <div className="row g-2">
                  <div className="col-12">
                    <label className="form-label small text-muted fw-semibold">Buyurtma turi</label>
                    <select name="type" className="form-select" required>
                      <option value="delivery">Yetkazib berish</option>
                      <option value="pickup">Pickup</option>
                    </select>
                  </div>
                  <div className="col-md-6 col-xl-12"><TextInput name="fromUzs" label="Xarid dan" type="number" min={0} required /></div>
                  <div className="col-md-6 col-xl-12"><TextInput name="toUzs" label="Xarid gacha" type="number" min={1} required /></div>
                  <div className="col-md-6 col-xl-12"><TextInput name="cashback" label="Cashback %" type="number" min={0} max={100} required /></div>
                </div>
                <div className="text-end mt-3"><SaveButton label="Qo'shish" /></div>
              </form>
            </SectionCard>
          </div>
        </div>
      )}

    </div>
  );
}
