import { FormEvent, useEffect, useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { tiIcon } from '../utils/icons';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type CourierBonusRule = { from_km?: number | string | null; to_km?: number | string | null; bonus_amount?: number | string | null };
type ProjectSettings = Record<string, string | number | boolean | CourierBonusRule[] | null | undefined>;
type ActionMap = Record<string, string>;
type Commission = { id: number; priceFrom: number; priceTo: number; percent: number; updateUrl: string; destroyUrl: string };
type Cashback = { id: number; fromUzs: number; toUzs: number; cashback: number; type: string; updateUrl: string; destroyUrl: string };

type ParserStats = {
  total_editions: number;
  with_isbn: number;
  without_isbn: number;
  without_description?: number;
  unlinked_books: number;
  parser_items_cached: number;
};

type EnrichProgress = {
  running: boolean;
  total: number;
  processed: number;
  updated: number;
  failed: number;
  last_title: string | null;
  updated_at: string;
};

type EnrichReport = {
  total: number;
  updated: number;
  failed: number;
};

type ParserItemResult = {
  title: string;
  isbn: string;
  source: string;
  action: string;
  message: string;
};

type ParserReport = {
  source: string;
  total_scanned: number;
  editions_created: number;
  isbn_enriched: number;
  already_matched: number;
  skipped_no_isbn: number;
  failed: number;
  items: ParserItemResult[];
};

type ParserProgress = {
  running: boolean;
  source: string;
  total_scanned: number;
  total_target: number;
  editions_created: number;
  isbn_enriched: number;
  already_matched: number;
  skipped_no_isbn: number;
  failed: number;
  last_title: string | null;
  updated_at: string;
};

type SettingsPayload = {
  project?: ProjectSettings;
  commission?: Commission[];
  cashback?: Cashback[];
  cashbackDelivery?: Cashback[];
  cashbackPickup?: Cashback[];
  parserStats?: ParserStats;
  actions?: ActionMap;
};

const tabs = [
  { key: 'versions', label: 'App versiyalar', icon: 'ti-device-mobile' },
  { key: 'contacts', label: 'Kontaktlar', icon: 'ti-headset' },
  { key: 'app-flags', label: 'App flaglar', icon: 'ti-toggle-right' },
  { key: 'courier-bonus', label: 'Kuryer bonus', icon: 'ti-bike' },
  { key: 'finance', label: 'Moliya', icon: 'ti-calculator' },
  { key: 'telegram', label: 'Telegram', icon: 'ti-brand-telegram' },
  { key: 'commission', label: 'Komissiya', icon: 'ti-percentage' },
  { key: 'cashback', label: 'Cashback', icon: 'ti-cash' },
  { key: 'catalog-parser', label: 'Katalog Parseri', icon: 'ti-world-download' },
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
      <label className="form-label f-s-13 text-muted f-w-600">{label}</label>
      <input name={name} type={type} min={min} max={max} required={required} className="form-control" defaultValue={defaultValue ?? ''} placeholder={placeholder} />
    </div>
  );
}

function Toggle({ name, label, defaultChecked, icon }: { name: string; label: string; defaultChecked?: boolean; icon: string }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 b-r-8 b-1-light">
      <span className="d-flex align-items-center gap-2 f-w-600"><i className={`${tiIcon(icon)} text-primary`}></i>{label}</span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

function SaveButton({ label = 'Saqlash' }: { label?: string }) {
  return <button className="btn btn-primary"><i className="ti ti-check me-1"></i>{label}</button>;
}

function SectionCard({ title, icon, children }: { title: string; icon: string; children: React.ReactNode }) {
  return (
    <div className="card">
      <div className="card-body">
        <div className="d-flex align-items-center gap-2 mb-3">
          <i className={`${tiIcon(icon)} text-primary`}></i>
          <h5 className="f-w-600 mb-0">{title}</h5>
        </div>
        {children}
      </div>
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
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Sozlamalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">App versiyalari, operatsion flaglar, cashback va komissiya sozlamalari</p>
        </div>
      </div>

      <div className="card">
<div className="card-body">
          <div className="nav kc-segment">
            {tabs.map((item) => (
              <div key={item.key} className="nav-item"><button
                  type="button"
                  onClick={() => setActiveTab(item.key)}
                  className={`nav-link ${tab === item.key ? 'active' : ''}`}>
                  <i className={`${tiIcon(item.icon)} me-1`}></i>{item.label}
                </button></div>
            ))}
          </div>
        </div>
</div>

      {tab === 'versions' && (
        <SectionCard title="App versiyalari" icon="ti-device-mobile">
          <form onSubmit={(event) => submitForm(event, 'put', actions.versions)}>
            <div className="row g-3">
              {[
                ['Kitobchi Business', 'business'],
                ['Kuryer App', 'courier'],
                ['Market App', 'market'],
              ].map(([label, key]) => (
                <div className="col-lg-4" key={key}>
                  <div className="p-3 b-r-8 b-1-light h-100">
                    <div className="f-w-600 mb-3">{label}</div>
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
        <SectionCard title="Kontaktlar" icon="ti-headset">
          <form onSubmit={(event) => submitForm(event, 'put', actions.contacts)}>
            <div className="row g-3">
              {[
                ['Kitobchi Market', 'kitobchi'],
                ['Kitobchi Business', 'business'],
                ['Endi Courier', 'courier'],
              ].map(([label, key]) => (
                <div className="col-xl-4" key={key}>
                  <div className="p-3 b-r-8 b-1-light h-100">
                    <div className="f-w-600 mb-3">{label}</div>
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
        <SectionCard title="App flaglar va qadoqlash" icon="ti-toggle-right">
          <form onSubmit={(event) => submitForm(event, 'put', actions.appFlags)}>
            <div className="row g-3">
              <div className="col-lg-6"><Toggle name="on_premium" label="Premium rejim" icon="ti-star-filled" defaultChecked={checked(project, 'on_premium')} /></div>
              <div className="col-lg-6"><Toggle name="on_reels" label="Reels yoqilgan" icon="ti-player-play-filled" defaultChecked={checked(project, 'on_reels')} /></div>
              <div className="col-lg-6"><Toggle name="ramadan" label="Ramazon rejim" icon="ti-moon-stars" defaultChecked={checked(project, 'ramadan')} /></div>
              <div className="col-lg-6"><Toggle name="stop_sales" label="Savdo to'xtatilgan" icon="ti-ban" defaultChecked={checked(project, 'stop_sales')} /></div>
              <div className="col-lg-6"><Toggle name="show_home_special_sections" label="Mystery box va gift section ko'rsatilsin" icon="ti-layout-list" defaultChecked={project.show_home_special_sections === undefined ? true : checked(project, 'show_home_special_sections')} /></div>
              <div className="col-md-4"><TextInput name="packaging_price_small" label="Kichik qadoqlash (UZS)" type="number" min={0} required defaultValue={value(project, 'packaging_price_small', '25000')} /></div>
              <div className="col-md-4"><TextInput name="packaging_price_large" label="Katta qadoqlash (UZS)" type="number" min={0} required defaultValue={value(project, 'packaging_price_large', '40000')} /></div>
              <div className="col-md-4"><TextInput name="packaging_threshold" label="Chegara (ta kitob)" type="number" min={1} required defaultValue={value(project, 'packaging_threshold', '4')} /></div>
              <div className="col-12"><hr className="my-1" /></div>
              <div className="col-lg-6"><Toggle name="review_cashback_enabled" label="Izoh uchun keshbek yoqilgan" icon="ti-message-circle-2-filled" defaultChecked={project.review_cashback_enabled === undefined ? true : checked(project, 'review_cashback_enabled')} /></div>
              <div className="col-md-4"><TextInput name="review_cashback_amount" label="Izoh keshbek miqdori (UZS)" type="number" min={0} max={100000} defaultValue={value(project, 'review_cashback_amount', '100')} /></div>
              <div className="col-12 f-s-13 text-muted">Mijoz o'zi sotib olgan mahsulotga izoh qoldirsa shu miqdorda keshbek oladi. Har bir mahsulot uchun faqat 1 marta beriladi (nechta izoh yozishidan qat'i nazar).</div>
              <div className="col-12"><hr className="my-1" /></div>
              <div className="col-12">
                <label className="form-label f-s-13 text-muted f-w-600">AI bot qo'shimcha qo'llanmasi</label>
                <textarea name="ai_bot_extra_notes" className="form-control" rows={4} maxLength={2000} placeholder="Masalan: Ramazon aksiyasi davomida barcha buyurtmalarga sovg'a qo'shiladi. Ish vaqti: 9:00–21:00." defaultValue={value(project, 'ai_bot_extra_notes', '')} />
                <div className="f-s-13 text-muted mt-1">Bu matn AI chatbot bilimiga qo'shiladi — aksiyalar, ish vaqti, maxsus qoidalarni shu yerga yozing. Tariflar, yetkazish narxlari va to'lov qoidalari tizimdan avtomatik olinadi, ularni yozish shart emas.</div>
              </div>
            </div>
            <div className="f-s-13 text-muted mt-3">Agar bu flag o'chirilsa, mystery box va gift certificate sectionlari ilovada yashiriladi va top bannerlar homepage ichida ularning o'rniga tushadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'courier-bonus' && (
        <SectionCard title="Kuryer km va bonus tizimi" icon="ti-bike">
          <form onSubmit={(event) => submitForm(event, 'put', actions.courierBonus)}>
            <div className="row g-3">
              <div className="col-md-4"><TextInput name="courier_base_fee" label="Bazaviy haq" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_base_fee', '3000')} /></div>
              <div className="col-md-4"><TextInput name="courier_price_per_km" label="1 km narxi" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_price_per_km', '1500')} /></div>
              <div className="col-md-4"><TextInput name="courier_min_fee" label="Minimal payout" type="number" min={0} max={1000000} required defaultValue={value(project, 'courier_min_fee', '5000')} /></div>
              <div className="col-md-4"><TextInput name="seller_courier_min_delivery_price" label="Seller kuryeri minimal narxi" type="number" min={0} max={1000000} required defaultValue={value(project, 'seller_courier_min_delivery_price', '0')} /></div>
              <div className="col-md-8 d-flex align-items-end">
                <div className="f-s-13 text-muted b-r-8 b-1-light p-3 w-100">
                  Seller o'z kuryeri uchun filial narxini bundan arzon qo'ya olmaydi. Narx filialga biriktiriladi, shu filialdagi barcha do'kon kuryerlari bir xil narxda ishlaydi.
                </div>
              </div>
              <div className="col-12">
                <div className="b-r-8 b-1-light p-3">
                  <div className="f-w-600 mb-2">Masofa bonuslari</div>
                  <div className="f-s-13 text-muted mb-3">Masalan: 5 km dan 10 km gacha bo'lsa qo'shimcha bonus. Bo'sh qatorlar saqlanmaydi.</div>
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
        <SectionCard title="Telegram Login" icon="ti-brand-telegram">
          <form onSubmit={(event) => submitForm(event, 'put', actions.telegram)}>
            <div className="row g-3">
              <div className="col-12"><Toggle name="telegram_login_enabled" label="Telegram login yoqilgan" icon="ti-power" defaultChecked={checked(project, 'telegram_login_enabled')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_client_id" label="Client ID" defaultValue={value(project, 'telegram_client_id')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_scopes" label="Scopes" defaultValue={value(project, 'telegram_scopes', 'openid profile phone')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_redirect_uri_ios" label="iOS Redirect URI" defaultValue={value(project, 'telegram_redirect_uri_ios', 'https://app3206985527-login.tg.dev')} /></div>
              <div className="col-lg-6"><TextInput name="telegram_redirect_uri_android" label="Android Redirect URI" defaultValue={value(project, 'telegram_redirect_uri_android', 'https://app2854400165-login.tg.dev/tglogin')} /></div>
            </div>
            <div className="f-s-13 text-muted mt-3">Client secret server `.env` faylida saqlanadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'finance' && (
        <SectionCard title="Marketplace moliyaviy sozlamalari" icon="ti-calculator">
          <form onSubmit={(event) => submitForm(event, 'put', actions.finance)}>
            <div className="row g-3">
              <div className="col-lg-4">
                <label className="form-label f-s-13 text-muted f-w-600">Soliq hisoblash turi</label>
                <select name="tax_mode" className="form-select" defaultValue={value(project, 'tax_mode', 'fixed')}>
                  <option value="fixed">Belgilangan summa (UZS)</option>
                  <option value="profit_percent">Operatsion foydadan foiz</option>
                </select>
              </div>
              <div className="col-lg-4"><TextInput name="tax_fixed_uzs" label="Soliq summasi (UZS)" type="number" min={0} required defaultValue={value(project, 'tax_fixed_uzs', '0')} /></div>
              <div className="col-lg-4"><TextInput name="tax_profit_percent" label="Foydadan soliq (%)" type="number" min={0} max={100} required defaultValue={value(project, 'tax_profit_percent', '0')} /></div>
              <div className="col-lg-4"><TextInput name="payment_provider_percent" label="Payment provider komissiyasi (%)" type="number" min={0} max={100} required defaultValue={value(project, 'payment_provider_percent', '0')} /></div>
            </div>
            <div className="f-s-13 text-muted mt-3">Belgilangan soliq summasi har bir hisobot davriga qo‘llanadi. Payment provider komissiyasi paid orderlarga bog‘langan oxirgi muvaffaqiyatli karta tranzaksiyasidan olinadi.</div>
            <div className="text-end mt-3"><SaveButton /></div>
          </form>
        </SectionCard>
      )}

      {tab === 'commission' && (
        <div className="row g-3">
          <div className="col-xl-8">
            <SectionCard title="Komissiya qoidalari" icon="ti-percentage">
              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle mb-0">
                  <thead><tr><th>Narx dan</th><th>Narx gacha</th><th>Komissiya %</th><th></th></tr></thead>
                  <tbody>
                    {(settings.commission ?? []).map((item) => (
                      <tr key={item.id}>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="priceFrom" type="number" min={0} defaultValue={item.priceFrom} /></td>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="priceTo" type="number" min={1} defaultValue={item.priceTo} /></td>
                        <td><input form={`commission-${item.id}`} className="form-control form-control-sm" name="percent" type="number" min={0} max={100} defaultValue={item.percent} /></td>
                        <td className="text-end">
                          <form id={`commission-${item.id}`} className="d-inline" onSubmit={(event) => submitForm(event, 'put', item.updateUrl)}>
                            <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1"><i className="ti ti-check"></i></button>
                          </form>
                          <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(item.destroyUrl, 'Komissiya qoidasi o‘chirilsinmi?')}><i className="ti ti-trash"></i></button>
                        </td>
                      </tr>
                    ))}
                    {(settings.commission ?? []).length === 0 ? <tr><td colSpan={4} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Komissiya qoidalari yo'q</td></tr> : null}
                  </tbody>
                </table>
              </div>
            </SectionCard>
          </div>
          <div className="col-xl-4">
            <SectionCard title="Yangi qoida" icon="ti-circle-plus">
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
            <SectionCard title="Cashback qoidalari" icon="ti-cash">
              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle mb-0">
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
                            <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1"><i className="ti ti-check"></i></button>
                          </form>
                          <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(item.destroyUrl, 'Cashback qoidasi o‘chirilsinmi?')}><i className="ti ti-trash"></i></button>
                        </td>
                      </tr>
                    ))}
                    {(settings.cashback ?? []).length === 0 ? <tr><td colSpan={5} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Cashback qoidalari yo'q</td></tr> : null}
                  </tbody>
                </table>
              </div>
            </SectionCard>
          </div>
          <div className="col-xl-4">
            <SectionCard title="Yangi cashback" icon="ti-circle-plus">
              <form onSubmit={(event) => submitForm(event, 'post', actions.cashbackStore)}>
                <div className="row g-2">
                  <div className="col-12">
                    <label className="form-label f-s-13 text-muted f-w-600">Buyurtma turi</label>
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

      {tab === 'catalog-parser' && (
        <CatalogParserSection
          stats={settings.parserStats}
          runUrl={actions.parserRun}
          statsUrl={actions.parserStats}
          categorizeUrl={actions.parserCategorizeExisting}
          enrichUrl={actions.parserEnrichDescriptions}
        />
      )}

    </div>
  );
}

function CatalogParserSection({
  stats: initialStats,
  runUrl,
  statsUrl,
  categorizeUrl,
  enrichUrl,
}: {
  stats?: ParserStats;
  runUrl?: string;
  statsUrl?: string;
  categorizeUrl?: string;
  enrichUrl?: string;
}) {
  const [stats, setStats] = useState<ParserStats | undefined>(initialStats);
  const [source, setSource] = useState('all');
  const [limit, setLimit] = useState('50');
  const [withImages, setWithImages] = useState(true);
  const [loading, setLoading] = useState(false);
  const [report, setReport] = useState<ParserReport | null>(null);
  const [progress, setProgress] = useState<ParserProgress | null>(null);
  const [backgroundMsg, setBackgroundMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // AI Tavsif to'ldirish state
  const [enrichLimit, setEnrichLimit] = useState('50');
  const [enrichLoading, setEnrichLoading] = useState(false);
  const [enrichProgress, setEnrichProgress] = useState<EnrichProgress | null>(null);
  const [enrichBgMsg, setEnrichBgMsg] = useState<string | null>(null);
  const [enrichError, setEnrichError] = useState<string | null>(null);
  const [enrichReport, setEnrichReport] = useState<EnrichReport | null>(null);

  const getCsrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';

  const refreshStats = async () => {
    if (!statsUrl) return;
    try {
      const res = await fetch(statsUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) return;
      const data = await res.json();
      if (data.success) {
        if (data.stats) setStats(data.stats);
        if (data.progress) {
          setProgress(data.progress);
          if (!data.progress.running) {
            setBackgroundMsg(null);
          }
        }
        if (data.enrich_progress) {
          setEnrichProgress(data.enrich_progress);
          if (!data.enrich_progress.running) {
            setEnrichBgMsg(null);
          }
        }
      }
    } catch {
      // ignore
    }
  };

  useEffect(() => {
    let interval: any = null;
    if (progress?.running || backgroundMsg || enrichProgress?.running || enrichBgMsg) {
      interval = setInterval(() => {
        refreshStats();
      }, 3500);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [progress?.running, backgroundMsg, enrichProgress?.running, enrichBgMsg]);

  const handleRun = async (e: FormEvent) => {
    e.preventDefault();
    if (loading) return;

    setLoading(true);
    setError(null);
    setReport(null);

    try {
      const endpoint = runUrl || '/boshqaruv/settings/parser/run';
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          source,
          limit: Number(limit),
          with_images: withImages,
        }),
      });

      const contentType = res.headers.get('content-type') || '';
      let data: any = null;
      if (contentType.includes('application/json')) {
        data = await res.json();
      } else {
        const text = await res.text();
        if (res.status === 504 || res.status === 502) {
          throw new Error("Server vaqti tugadi (504 Gateway Timeout). Barcha kitoblarni yuklash juda ko'p vaqt (30-60 daqiqa) olgani uchun, terminalda 'php artisan catalog:sync-external --source=all --limit=0' buyrug'ini bering.");
        }
        throw new Error(`Server xatosi (${res.status}): ${text.slice(0, 100)}`);
      }

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Sinxronlashda xatolik yuz berdi');
      }

      if (data.is_background) {
        setBackgroundMsg(data.message);
        if (data.progress) setProgress(data.progress);
        setReport(null);
      } else {
        setBackgroundMsg(null);
        setReport(data.report);
      }

      if (data.stats) {
        setStats(data.stats);
      }
    } catch (err: any) {
      setError(err.message || 'Xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleEnrich = async (e: FormEvent) => {
    e.preventDefault();
    if (enrichLoading) return;

    setEnrichLoading(true);
    setEnrichError(null);
    setEnrichReport(null);

    try {
      const endpoint = enrichUrl || '/boshqaruv/settings/parser/enrich-descriptions';
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          limit: Number(enrichLimit),
        }),
      });

      const contentType = res.headers.get('content-type') || '';
      let data: any = null;
      if (contentType.includes('application/json')) {
        data = await res.json();
      } else {
        const text = await res.text();
        throw new Error(`Server xatosi (${res.status}): ${text.slice(0, 100)}`);
      }

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Tavsiflashda xatolik yuz berdi');
      }

      if (data.is_background) {
        setEnrichBgMsg(data.message);
        if (data.enrich_progress) setEnrichProgress(data.enrich_progress);
        setEnrichReport(null);
      } else {
        setEnrichBgMsg(null);
        setEnrichReport(data.report);
      }

      if (data.stats) {
        setStats(data.stats);
      }
    } catch (err: any) {
      setEnrichError(err.message || 'Xatolik yuz berdi');
    } finally {
      setEnrichLoading(false);
    }
  };

  return (
    <div className="row g-3">
      {/* 5 ta statistika kartochkasi */}
      <div className="col-12">
        <div className="row g-3">
          <div className="col-xl col-md-4 col-sm-6">
            <div className="card mb-0 bg-light-primary border-primary">
              <div className="card-body py-3">
                <div className="d-flex align-items-center justify-content-between">
                  <div>
                    <span className="text-muted f-s-13 f-w-600">Jami kitob kartalari</span>
                    <h4 className="mb-0 mt-1 f-w-700">{fmt(stats?.total_editions || 0)}</h4>
                  </div>
                  <i className="ti ti-books f-s-28 text-primary"></i>
                </div>
              </div>
            </div>
          </div>
          <div className="col-xl col-md-4 col-sm-6">
            <div className="card mb-0 bg-light-success border-success">
              <div className="card-body py-3">
                <div className="d-flex align-items-center justify-content-between">
                  <div>
                    <span className="text-muted f-s-13 f-w-600">ISBN mavjud kartalar</span>
                    <h4 className="mb-0 mt-1 f-w-700 text-success">{fmt(stats?.with_isbn || 0)}</h4>
                  </div>
                  <i className="ti ti-barcode f-s-28 text-success"></i>
                </div>
              </div>
            </div>
          </div>
          <div className="col-xl col-md-4 col-sm-6">
            <div className="card mb-0 bg-light-warning border-warning">
              <div className="card-body py-3">
                <div className="d-flex align-items-center justify-content-between">
                  <div>
                    <span className="text-muted f-s-13 f-w-600">ISBN yo'q kartalar</span>
                    <h4 className="mb-0 mt-1 f-w-700 text-warning">{fmt(stats?.without_isbn || 0)}</h4>
                  </div>
                  <i className="ti ti-alert-circle f-s-28 text-warning"></i>
                </div>
              </div>
            </div>
          </div>
          <div className="col-xl col-md-4 col-sm-6">
            <div className="card mb-0 bg-light-danger border-danger">
              <div className="card-body py-3">
                <div className="d-flex align-items-center justify-content-between">
                  <div>
                    <span className="text-muted f-s-13 f-w-600">Tavsifi yo'q kartalar</span>
                    <h4 className="mb-0 mt-1 f-w-700 text-danger">{fmt(stats?.without_description || 0)}</h4>
                  </div>
                  <i className="ti ti-file-description f-s-28 text-danger"></i>
                </div>
              </div>
            </div>
          </div>
          <div className="col-xl col-md-4 col-sm-6">
            <div className="card mb-0 bg-light-secondary border-secondary">
              <div className="card-body py-3">
                <div className="d-flex align-items-center justify-content-between">
                  <div>
                    <span className="text-muted f-s-13 f-w-600">Ulanmagan do'kon kitoblari</span>
                    <h4 className="mb-0 mt-1 f-w-700 text-secondary">{fmt(stats?.unlinked_books || 0)}</h4>
                  </div>
                  <i className="ti ti-link-off f-s-28 text-secondary"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Chap ustun: Boshqaruv vositalari */}
      <div className="col-xl-5">
        {/* Parser Boshqaruvi */}
        <SectionCard title="Tashqi Katalog Parseri" icon="ti-cloud-download">
          <p className="text-muted f-s-13 mb-3">
            <strong>Qamar.uz</strong> va <strong>Book.uz</strong> saytlaridan ISBN mavjud kitoblarni olib, global katalogga (<code>book_editions</code>) saqlaydi. 
            Mavjud kitob bilan nomi to'g'ri kelsa, uning ISBN va ma'lumotlarini to'ldirib qo'yadi.
          </p>

          <form onSubmit={handleRun}>
            <div className="mb-3">
              <label className="form-label f-s-13 text-muted f-w-600">Manba (Sayt)</label>
              <select className="form-select" value={source} onChange={(e) => setSource(e.target.value)} disabled={loading}>
                <option value="all">Qamar.uz va Book.uz (Ikkalasi)</option>
                <option value="qamar_uz">Faqat Qamar.uz (2 700+ kitob)</option>
                <option value="book_uz">Faqat Book.uz</option>
              </select>
            </div>

            <div className="mb-3">
              <label className="form-label f-s-13 text-muted f-w-600">Kitoblar soni (Limit)</label>
              <select className="form-select" value={limit} onChange={(e) => setLimit(e.target.value)} disabled={loading}>
                <option value="25">25 ta kitob (Tezkor tekshiruv)</option>
                <option value="50">50 ta kitob</option>
                <option value="100">100 ta kitob</option>
                <option value="0">Barcha kitoblar (Orqa fonda avtomatik ishlaydi)</option>
              </select>
            </div>

            <div className="mb-4">
              <label className="d-flex align-items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  className="form-check-input mt-0"
                  checked={withImages}
                  onChange={(e) => setWithImages(e.target.checked)}
                  disabled={loading}
                />
                <span className="f-s-13">Muqova rasmlarini serverga yuklab olish</span>
              </label>
            </div>

            <div className="d-flex justify-content-between align-items-center">
              <button type="button" className="btn btn-outline-secondary btn-sm" onClick={refreshStats} disabled={loading}>
                <i className="ti ti-refresh me-1"></i>Statistikani yangilash
              </button>
              <button type="submit" className="btn btn-primary" disabled={loading}>
                {loading ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Sinxronlanmoqda...
                  </>
                ) : (
                  <>
                    <i className="ti ti-player-play me-1"></i>Parserni ishga tushirish
                  </>
                )}
              </button>
            </div>

            <div className="mt-3 p-2 b-r-6 bg-light text-muted f-s-12">
              <div className="d-flex align-items-center gap-1 mb-1">
                <i className="ti ti-terminal text-primary"></i>
                <strong className="text-dark">Terminaldan to'liq sinxronlash (Tavsiya etiladi):</strong>
              </div>
              <code className="d-block user-select-all bg-white p-1 border b-r-4 text-dark f-s-11">
                php artisan catalog:sync-external --source=all --limit=0 --with-images
              </code>
            </div>
          </form>
        </SectionCard>

        {/* AI Tavsif Generator (Orqa fonda) */}
        <div className="mt-3">
          <SectionCard title="AI Tavsif Generator (Fonda)" icon="ti-sparkles">
            <p className="text-muted f-s-13 mb-3">
              Tavsifi bo'lmagan kitoblarga <strong>OpenAI</strong> orqali 3-5 gapli chiroyli adabiy o'zbekcha tavsif yozib chiqiladi.
              Tavsif saqlangach, bog'langan barcha do'kon takliflariga ham avtomatik uzatiladi.
              Server va API qotib qolmasligi uchun jarayon orqa fonda sekin tanaffuslar bilan bajariladi.
            </p>

            <form onSubmit={handleEnrich}>
              <div className="mb-3">
                <label className="form-label f-s-13 text-muted f-w-600">Ko'rib chiqish limiti</label>
                <select className="form-select" value={enrichLimit} onChange={(e) => setEnrichLimit(e.target.value)} disabled={enrichLoading}>
                  <option value="15">15 ta kitob (Tezkor tekshiruv)</option>
                  <option value="50">50 ta kitob (Fonda)</option>
                  <option value="100">100 ta kitob (Fonda)</option>
                  <option value="0">Barcha tavsifsiz kitoblar ({fmt(stats?.without_description || 0)} ta - Fonda)</option>
                </select>
              </div>

              <div className="d-flex justify-content-between align-items-center">
                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={refreshStats} disabled={enrichLoading}>
                  <i className="ti ti-refresh me-1"></i>Tekshirish
                </button>
                <button
                  type="submit"
                  className="btn btn-success"
                  disabled={enrichLoading || (stats?.without_description === 0) || enrichProgress?.running}
                >
                  {enrichLoading ? (
                    <>
                      <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Yuborilmoqda...
                    </>
                  ) : enrichProgress?.running ? (
                    <>
                      <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Fonda ishlamoqda...
                    </>
                  ) : (
                    <>
                      <i className="ti ti-sparkles me-1"></i>AI bilan tavsif yozish
                    </>
                  )}
                </button>
              </div>

              <div className="mt-3 p-2 b-r-6 bg-light text-muted f-s-12">
                <div className="d-flex align-items-center gap-1 mb-1">
                  <i className="ti ti-terminal text-success"></i>
                  <strong className="text-dark">Terminaldan sekin fonda yurgizish:</strong>
                </div>
                <code className="d-block user-select-all bg-white p-1 border b-r-4 text-dark f-s-11">
                  php artisan catalog:enrich-descriptions --limit=0
                </code>
              </div>
            </form>
          </SectionCard>
        </div>
      </div>

      {/* O'ng ustun: Natijalar paneli */}
      <div className="col-xl-7">
        {/* AI Tavsiflash xabarlari va progress */}
        {enrichError && (
          <div className="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <i className="ti ti-alert-triangle f-s-18"></i>
            <div>{enrichError}</div>
          </div>
        )}

        {enrichBgMsg && (
          <div className="alert alert-success d-flex align-items-center gap-2 mb-3">
            <i className="ti ti-circle-check f-s-18"></i>
            <div>{enrichBgMsg}</div>
          </div>
        )}

        {enrichProgress && enrichProgress.running && (
          <div className="card mb-3 border-warning bg-light-warning">
            <div className="card-body py-3">
              <div className="d-flex align-items-center justify-content-between mb-2">
                <div className="d-flex align-items-center gap-2">
                  <span className="spinner-border spinner-border-sm text-warning" role="status" aria-hidden="true"></span>
                  <strong className="text-dark">AI orqa fonda kitoblarga tavsif yozmoqda...</strong>
                </div>
                <button type="button" className="btn btn-outline-warning btn-xs py-0 px-2 f-s-11" onClick={refreshStats}>
                  Yangilash
                </button>
              </div>
              <div className="progress mb-2" style={{ height: '8px' }}>
                <div
                  className="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                  role="progressbar"
                  style={{
                    width: `${enrichProgress.total > 0 ? Math.min(100, Math.round((enrichProgress.processed / enrichProgress.total) * 100)) : 10}%`,
                  }}
                ></div>
              </div>
              <div className="f-s-13">
                Jarayon: <strong>{enrichProgress.processed}</strong> {enrichProgress.total > 0 ? `/ ${enrichProgress.total}` : 'ta kitob'} | 
                Tavsif saqlandi: <strong className="text-success">+{enrichProgress.updated}</strong> | 
                Xatolik: <strong className="text-danger">{enrichProgress.failed}</strong>
              </div>
              {enrichProgress.last_title && (
                <div className="f-s-12 text-muted mt-1 text-truncate">
                  Hozir yozilmoqda: <em>{enrichProgress.last_title}</em>
                </div>
              )}
            </div>
          </div>
        )}

        {enrichReport && (
          <div className="alert alert-success d-flex align-items-center justify-content-between mb-3">
            <div className="d-flex align-items-center gap-2">
              <i className="ti ti-sparkles f-s-20"></i>
              <span>AI tavsiflash yakunlandi: <strong>{enrichReport.updated}</strong> ta kitobga sifatli tavsif yozildi va saqlandi! ({enrichReport.failed} ta xatolik)</span>
            </div>
          </div>
        )}

        <SectionCard title="Sinxronlash natijalari" icon="ti-report-analytics">
          {error && (
            <div className="alert alert-danger d-flex align-items-center gap-2 mb-3">
              <i className="ti ti-alert-triangle f-s-18"></i>
              <div>{error}</div>
            </div>
          )}

          {backgroundMsg && (
            <div className="alert alert-success d-flex align-items-center gap-2 mb-3">
              <i className="ti ti-circle-check f-s-18"></i>
              <div>{backgroundMsg}</div>
            </div>
          )}

          {progress && progress.running && (
            <div className="alert alert-info mb-3">
              <div className="d-flex align-items-center justify-content-between mb-2">
                <div className="d-flex align-items-center gap-2">
                  <span className="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true"></span>
                  <strong>Orqa fonda sinxronlash bormoqda ({progress.source})...</strong>
                </div>
                <button type="button" className="btn btn-outline-info btn-xs py-0 px-2 f-s-11" onClick={refreshStats}>
                  Yangilash
                </button>
              </div>
              <div className="f-s-13">
                Skanerlandi: <strong>{progress.total_scanned}</strong> {progress.total_target > 0 ? `/ ${progress.total_target}` : 'ta kitob'} | 
                Yangi ochildi: <strong className="text-success">+{progress.editions_created}</strong> | 
                ISBN to'ldirildi: <strong className="text-primary">+{progress.isbn_enriched}</strong>
              </div>
              {progress.last_title && (
                <div className="f-s-12 text-muted mt-1 text-truncate">
                  Hozir ko'rilmoqda: <em>{progress.last_title}</em>
                </div>
              )}
            </div>
          )}

          {loading && (
            <div className="text-center py-5">
              <div className="spinner-border text-primary mb-3" style={{ width: '3rem', height: '3rem' }} role="status"></div>
              <h6 className="f-w-600">Kitoblar o'qilmoqda va bazaga kiritilmoqda...</h6>
              <p className="text-muted f-s-13 mb-0">Iltimos kuting, bu biroz vaqt olishi mumkin (sitemap, ISBN tekshiruvi va rasmlar yuklanishi).</p>
            </div>
          )}

          {!loading && !report && !error && (
            <div className="text-center py-5 text-muted">
              <i className="ti ti-cloud-search f-s-40 d-block mb-2 text-secondary"></i>
              <p className="mb-0">Parser hali ishga tushirilmadi. Chapdagi forma orqali manba va limitni tanlab, "Parserni ishga tushirish" tugmasini bosing.</p>
            </div>
          )}

          {report && (
            <div>
              <div className="alert alert-success d-flex align-items-center justify-content-between mb-3">
                <div className="d-flex align-items-center gap-2">
                  <i className="ti ti-circle-check f-s-20"></i>
                  <span>Sinxronlash muvaffaqiyatli yakunlandi! ({report.total_scanned} ta kitob ko'rib chiqildi)</span>
                </div>
              </div>

              <div className="row g-2 mb-3">
                <div className="col-sm-3 col-6">
                  <div className="p-2 b-r-8 bg-light-success text-center">
                    <span className="d-block f-s-11 text-muted">Yangi kartalar</span>
                    <strong className="f-s-16 text-success">+{report.editions_created}</strong>
                  </div>
                </div>
                <div className="col-sm-3 col-6">
                  <div className="p-2 b-r-8 bg-light-primary text-center">
                    <span className="d-block f-s-11 text-muted">ISBN to'ldirildi</span>
                    <strong className="f-s-16 text-primary">+{report.isbn_enriched}</strong>
                  </div>
                </div>
                <div className="col-sm-3 col-6">
                  <div className="p-2 b-r-8 bg-light-secondary text-center">
                    <span className="d-block f-s-11 text-muted">Mavjud edi</span>
                    <strong className="f-s-16 text-secondary">{report.already_matched}</strong>
                  </div>
                </div>
                <div className="col-sm-3 col-6">
                  <div className="p-2 b-r-8 bg-light-warning text-center">
                    <span className="d-block f-s-11 text-muted">ISBN'siz (o'tkazildi)</span>
                    <strong className="f-s-16 text-warning">{report.skipped_no_isbn}</strong>
                  </div>
                </div>
              </div>

              {report.items && report.items.length > 0 && (
                <div className="table-responsive app-scroll" style={{ maxHeight: '340px' }}>
                  <table className="table table-sm table-bottom-border align-middle mb-0 f-s-12">
                    <thead>
                      <tr>
                        <th>Kitob nomi</th>
                        <th>ISBN</th>
                        <th>Manba</th>
                        <th>Holati</th>
                      </tr>
                    </thead>
                    <tbody>
                      {report.items.map((item, idx) => (
                        <tr key={idx}>
                          <td className="f-w-600">{item.title}</td>
                          <td><code>{item.isbn || '—'}</code></td>
                          <td><span className="badge text-light-secondary">{item.source}</span></td>
                          <td>
                            {item.action === 'edition_created' && <span className="badge text-light-success">Yangi ochildi</span>}
                            {item.action === 'isbn_enriched' && <span className="badge text-light-primary">ISBN to'ldirildi</span>}
                            {item.action === 'already_matched' && <span className="badge text-light-secondary">Mavjud</span>}
                            {item.action === 'skipped_no_isbn' && <span className="badge text-light-warning">ISBN'siz</span>}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}
        </SectionCard>
      </div>
    </div>
  );
}
