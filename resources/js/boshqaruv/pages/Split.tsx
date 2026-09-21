import { FormEvent, Fragment, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type SplitSettings = {
  enabled: boolean;
  publicEnabled: boolean;
  globalMinLimit: number;
  globalMaxLimit: number;
  minCompletedOrders: number;
  minAccountAgeDays: number;
  minCardAgeDays: number;
  cardDeleteLockEnabled: boolean;
  refundSenderCardId: string;
  refundServiceId: string;
};

type SplitSummary = {
  profiles: number;
  eligible: number;
  locked: number;
  blocked: number;
  avgConfidence: number;
  totalAvailableLimit: number;
};

type SplitRuleRow = {
  id: number;
  categoryType: 'book' | 'stationery';
  name: string;
  active: boolean;
  enabled: boolean;
  destroyUrl?: string | null;
};

type SplitUserRow = {
  id: number;
  name: string;
  phone: string;
  verified: boolean;
  eligible: boolean;
  manuallyBlocked: boolean;
  manualBlockReason?: string | null;
  manualBlockedAt?: string | null;
  confidenceScore: number;
  computedLimit: number;
  availableLimit: number;
  activeExposure: number;
  reputationScore: number;
  completedOrders: number;
  verifiedCardAgeDays: number;
  verifiedCardsCount: number;
  successfulCardPayments180d: number;
  codReturnStrikes: number;
  reasons: string[];
  lastRefreshedAt?: string | null;
  profileUrl: string;
  refreshUrl: string;
  blockUrl: string;
  unblockUrl: string;
};

type PaginationMeta = { page: number; totalPages: number; from: number; to: number; total: number };

type SplitPlanRow = {
  id: number;
  name: string;
  months: number;
  periodUnit: 'month' | 'week';
  periodEvery: number;
  monthlyInterestPercent: number;
  totalInterestPercent: number;
  installmentsCount: number;
  frequencyLabel: string;
  minOrderSum: number | null;
  maxOrderSum: number | null;
  minConfidenceScore: number | null;
  enabled: boolean;
  sortOrder: number;
  openContracts: number;
  destroyUrl: string;
};

type SplitInstallmentRow = {
  id: number;
  sequence: number;
  amount: number;
  dueAt: string | null;
  status: string;
  attempts: number;
  isUpfront: boolean;
  chargeUrl: string;
};

type SplitContractRow = {
  id: number;
  orderId: number | null;
  userId: number;
  userName: string;
  planName: string;
  status: string;
  principal: number;
  interest: number;
  total: number;
  paid: number;
  remaining: number;
  installmentsPaid: number;
  installmentsCount: number;
  debitDay: number | null;
  nextDueAt: string | null;
  nextAmount: number | null;
  nextInstallmentId: number | null;
  startsAt: string | null;
  overdueSince: string | null;
  overdueDays: number;
  overdueAmount: number;
  contractPdfUrl: string;
  demandLetterUrl: string;
  installments: SplitInstallmentRow[];
  settleUrl: string;
  creditUrl: string;
  refundDue: number;
};

type SplitContractStats = { active: number; overdue: number; exposure: number; collected30d: number };

type SchedulePreview = {
  principal: number;
  interest: number;
  upfront_extra: number;
  total: number;
  total_interest_percent: number;
  installments: { sequence: number; amount: number; due_at: string; is_upfront: boolean }[];
};

export default function Split() {
  const {
    splitSettings,
    splitSummary,
    splitPlans = [],
    splitContracts = [],
    splitContractStats = { active: 0, overdue: 0, exposure: 0, collected30d: 0 },
    splitBookRules = [],
    splitStationeryRules = [],
    splitUsers = [],
    splitPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    splitFilters = {},
    splitContractFilters = { status: 'all' },
    splitActions,
  } = usePage<{
    splitSettings: SplitSettings;
    splitSummary: SplitSummary;
    splitPlans?: SplitPlanRow[];
    splitContracts?: SplitContractRow[];
    splitContractStats?: SplitContractStats;
    splitBookRules?: SplitRuleRow[];
    splitStationeryRules?: SplitRuleRow[];
    splitUsers?: SplitUserRow[];
    splitPagination?: PaginationMeta;
    splitFilters?: { status?: string; search?: string };
    splitContractFilters?: { status?: string };
    splitActions: {
      settingsUpdateUrl: string;
      ruleStoreUrl: string;
      refreshUrl: string;
      planStoreUrl: string;
      planPreviewUrl: string;
      contractStoreUrl: string;
    };
  }>().props;

  const [status, setStatus] = useState(splitFilters.status || 'all');
  const [search, setSearch] = useState(splitFilters.search || '');

  type SplitTab = 'plans' | 'contracts' | 'categories' | 'users' | 'settings';
  const [tab, setTab] = useState<SplitTab>((splitContractFilters.status || 'all') !== 'all' ? 'contracts' : 'plans');
  const tabs: [SplitTab, string, string][] = [
    ['plans', 'Tariflar', 'ti-calendar-time'],
    ['contracts', 'Shartnomalar', 'ti-file-text'],
    ['categories', 'Kategoriyalar', 'ti-tags'],
    ['users', 'Foydalanuvchilar', 'ti-users'],
    ['settings', 'Sozlamalar', 'ti-settings'],
  ];

  const reload = (page = 1, nextStatus = status, nextSearch = search) => {
    router.get('/boshqaruv/split', {
      split_page: page,
      split_status: nextStatus,
      split_search: nextSearch,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const refreshProfiles = (userId?: number) => {
    if (!userId && !confirm("Barcha foydalanuvchilarning split profillari qayta hisoblansinmi? Bu bir necha daqiqa vaqt olishi mumkin.")) return;
    router.post(splitActions.refreshUrl, userId ? { user_id: userId } : {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Split nazorati</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Nasiya tariflari, shartnomalar va foydalanuvchi limitlarini boshqarish</p>
        </div>
        {tab === 'users' ? (
          <div className="d-flex gap-2 flex-wrap">
            <button className="btn btn-light-secondary" onClick={() => refreshProfiles()}><i className="ti ti-rotate-clockwise me-1"></i>Barchasini qayta hisoblash</button>
          </div>
        ) : null}
      </div>

      <div className="nav kc-segment mb-3">
        {tabs.map(([key, label, icon]) => (
          <div key={key} className="nav-item"><button
              className={`nav-link ${tab === key ? 'active' : ''}`}
              onClick={() => setTab(key)}>
              <i className={`${tiIcon(icon)} me-1`}></i>
              {label}
              {key === 'contracts' && splitContractStats.overdue > 0 ? (
                <span className="badge bg-danger ms-1">{splitContractStats.overdue}</span>
              ) : null}
            </button></div>
        ))}
      </div>

      {tab === 'users' ? (
      <div className="row">
        {[
          ['Profil yozuvlari', splitSummary.profiles, 'ti-database', 'rgba(var(--primary), 1)'],
          ['Mos userlar', splitSummary.eligible, 'ti-discount-check', 'rgba(var(--success), 1)'],
          ['Kartasi lock bo‘ladiganlar', splitSummary.locked, 'ti-lock', 'rgba(var(--danger), 1)'],
          ['Split bloklanganlar', splitSummary.blocked, 'ti-ban', 'rgba(var(--danger-dark), 1)'],
          ['Bo‘sh limitlar jami', `${fmt(splitSummary.totalAvailableLimit)} so'm`, 'ti-wallet', 'rgba(var(--info-dark), 1)'],
        ].map(([label, value, icon, color], kpiIndex) => (<div className="col-xl-3 col-md-6" key={String(label)}>
          <StatWidget index={kpiIndex} label={label} value={String(value)} />
        </div>))}
      </div>
      ) : null}

      <div className="row">
        {tab === 'settings' ? (
        <div className="col-12">
          <div className="card">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <h5 className="f-w-600">Sozlamalar</h5>
                <p className="mb-0 text-secondary">Global xavfsizlik talablari shu yerda. Skor threshold, summa, foiz va muddat faqat «Tariflar» tabida.</p>
              </div>
              <div className="d-flex flex-wrap gap-2">
                <span className={`badge ${splitSettings.enabled ? 'text-light-success' : 'text-light-warning'}`}>{splitSettings.enabled ? 'Modul yoqilgan' : "Modul o'chirilgan"}</span>
                <span className={`badge ${splitSettings.publicEnabled ? 'text-light-danger' : 'text-light-secondary'}`}>{splitSettings.publicEnabled ? 'Ilovada ko‘rinadi' : 'Ilovada yashirin'}</span>
              </div>
            </div>
<div className="card-body">

              <form onSubmit={(event) => submitForm(event, splitActions.settingsUpdateUrl)}>
                <div className="row g-3">
                  <div className="col-lg-4"><Toggle name="split_enabled" label="Split moduli" hint="Umumiy vklyuchatel. O'chiq bo'lsa hech qanday yangi nasiya ochilmaydi — mavjud shartnomalar ishlashda davom etadi." defaultChecked={splitSettings.enabled} /></div>
                  <div className="col-lg-4"><Toggle name="split_public_enabled" label="Ilovada ko'rsatish" hint="Yoqilsa mos userlar checkoutda «Nasiya» tugmasini ko'radi. O'chiq bo'lsa modul faqat admin panelda ishlaydi (test rejimi)." defaultChecked={splitSettings.publicEnabled} /></div>
                  <div className="col-lg-4"><Toggle name="split_card_delete_lock_enabled" label="Qarzdorda karta o'chirish blok" hint="Ochiq nasiyasi bor user ilovadan bog'langan kartalarini o'chira olmaydi — qarzdan «qochib ketish» yo'li yopiladi. Nasiya to'liq yopilgach blok avtomatik ochiladi." defaultChecked={splitSettings.cardDeleteLockEnabled} /></div>

                  <div className="col-12"><div className="f-w-600 f-s-13 text-muted mt-2">LIMIT ORALIG&apos;I</div></div>
                  <div className="col-md-3"><Field name="split_global_min_limit" label="Min limit" type="number" defaultValue={splitSettings.globalMinLimit}
                    hint="Tizim hisoblagan shaxsiy limit bundan past chiqsa shu qiymatga ko'tariladi — mos user hech bo'lmaganda shuncha nasiya oladi."
                    example="min 300 000 bo'lsa, hisob 180 000 chiqqan userga baribir 300 000 limit beriladi." /></div>
                  <div className="col-md-3"><Field name="split_global_max_limit" label="Max limit" type="number" defaultValue={splitSettings.globalMaxLimit}
                    hint="Eng ishonchli user ham bundan ko'p limit ololmaydi. Kompaniyaning bitta userga maksimal riski."
                    example="2 000 000 qo'ysangiz, hech kimning limiti undan oshmaydi." /></div>

                  <div className="col-12"><div className="f-w-600 f-s-13 text-muted mt-2">KIM NASIYA OLADI (eligibility)</div></div>
                  <div className="col-md-3"><Field name="split_min_completed_orders" label="Min yakunlangan buyurtma" type="number" defaultValue={splitSettings.minCompletedOrders}
                    hint="User nasiya olishdan oldin kamida shuncha pullik buyurtmani muvaffaqiyatli yakunlagan bo'lishi kerak."
                    example="3 qo'ysangiz, 2 ta buyurtmasi bor user hali nasiya ko'rmaydi." /></div>
                  <div className="col-md-3"><Field name="split_min_account_age_days" label="Min akkaunt yoshi (kun)" type="number" defaultValue={splitSettings.minAccountAgeDays}
                    hint="Ro'yxatdan o'tganiga kamida shuncha kun bo'lgan userlargagina nasiya. Yangi akkaunt — firibgarlik riski."
                    example="90 bo'lsa, 2 oylik akkaunt hali mos emas." /></div>
                  <div className="col-md-3"><Field name="split_min_card_age_days" label="Min karta yoshi (kun)" type="number" defaultValue={splitSettings.minCardAgeDays}
                    min={0}
                    hint="Tasdiqlangan Paylov kartasi kamida shuncha kun oldin ulangan bo'lishi kerak. 0 qo'ysangiz kutish muddati bo'lmaydi, ammo tasdiqlangan karta baribir talab qilinadi."
                    example="0 — yangi ulangan karta darhol yaroqli; 45 — kamida 45 kunlik karta kerak." /></div>

                  <div className="col-12"><div className="f-w-600 f-s-13 text-muted mt-2">TEXNIK (Paylov refund)</div></div>
                  <div className="col-md-3"><Field name="paylov_refund_sender_card_id" label="Refund sender cardId" defaultValue={splitSettings.refundSenderCardId}
                    hint="Pul qaytarish kerak bo'lganda mablag' shu Paylov kartadan (Account2Card) jo'natiladi. Paylov kabinetidan olinadi." /></div>
                  <div className="col-md-3"><Field name="paylov_refund_service_id" label="Refund serviceId" defaultValue={splitSettings.refundServiceId}
                    hint="Paylov refund xizmatining ID'si. Bo'sh qoldirsangiz standart qiymat ishlatiladi." /></div>
                </div>
                <div className="text-end mt-3">
                  <button className="btn btn-primary"><i className="ti ti-check me-1"></i>Saqlash</button>
                </div>
              </form>
            </div>
</div>
        </div>
        ) : null}

        {tab === 'plans' ? (
        <div className="col-12">
          <PlanSection plans={splitPlans} storeUrl={splitActions.planStoreUrl} previewUrl={splitActions.planPreviewUrl} />
        </div>
        ) : null}

        {tab === 'contracts' ? (
        <div className="col-12">
          <ContractSection
            contracts={splitContracts}
            filter={splitContractFilters.status || 'all'}
            stats={splitContractStats}
            plans={splitPlans}
            contractStoreUrl={splitActions.contractStoreUrl}
          />
        </div>
        ) : null}

        {tab === 'categories' ? (
        <>
        <div className="col-12">
          <RuleSection title="Kitob kategoriyalari" rows={splitBookRules} actionUrl={splitActions.ruleStoreUrl} />
        </div>

        <div className="col-12">
          <RuleSection title="Kanselyariya kategoriyalari" rows={splitStationeryRules} actionUrl={splitActions.ruleStoreUrl} />
        </div>
        </>
        ) : null}

        {tab === 'users' ? (
        <div className="col-12">
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <h5 className="f-w-600">Foydalanuvchi split profillari</h5>
                <p className="mb-0 text-secondary">{splitPagination.total} ta foydalanuvchi ko‘rinmoqda · o‘rtacha ishonch {splitSummary.avgConfidence}</p>
              </div>
              <form className="d-flex gap-2 flex-wrap" onSubmit={(event) => { event.preventDefault(); reload(); }}>
                <select className="form-select form-select-sm w-180" value={status} onChange={(event) => { const value = event.target.value; setStatus(value); reload(1, value, search); }}>
                  <option value="all">Barchasi</option>
                  <option value="eligible">Moslar</option>
                  <option value="ineligible">Mos emaslar</option>
                  <option value="locked">Exposure borlar</option>
                  <option value="blocked">Admin bloklaganlar</option>
                </select>
                <input className="form-control form-control-sm w-260" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ID, ism, telefon, email" />
                <button className="btn btn-sm btn-outline-secondary"><i className="ti ti-search"></i></button>
              </form>
            </div>
            <div className="card-body">

              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle">
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>
                        Moslik
                        <InfoHint
                          text="Nasiya olish shartlarini bajargan-bajarmagani. «Mos emas» bo'lsa sababi qatorda ko'rinadi."
                          example="Karta yoshi yetmasa — global mos emas; skor yetmasa faqat shu skor talab qilingan tarif ochilmaydi."
                        />
                      </th>
                      <th>
                        Skor
                        <InfoHint
                          text="Ishonch balli (0–100) — buyurtma tarixi, GMV, karta yoshi, qurilma barqarorligi va nasiya to'lov intizomidan avtomatik hisoblanadi."
                          example="Har toza yopilgan nasiya ballni oshiradi, kechikish tushiradi."
                        />
                      </th>
                      <th>
                        Limit
                        <InfoHint
                          text="Tizim hisoblagan shaxsiy nasiya limiti. «Bo'sh» — hozir ishlatilishi mumkin bo'lgan qismi (limit minus ochiq qarz)."
                        />
                      </th>
                      <th>
                        Exposure
                        <InfoHint
                          text="Userning hozirgi ochiq nasiya qarzi (hali to'lanmagan bo'laklar yig'indisi)."
                        />
                      </th>
                      <th>Karta</th>
                      <th>Orderlar</th>
                      <th>Risk</th>
                      <th>Yangilangan</th>
                      <th>Amal</th>
                    </tr>
                  </thead>
                  <tbody>
                    {splitUsers.map((user) => (
                      <tr key={user.id}>
                        <td>
                          <div className="f-w-600">#{user.id} {user.name}</div>
                          <p className="mb-0 text-secondary">{user.phone || 'Telefon yo‘q'}</p>
                        </td>
                        <td>
                          <div className="d-flex flex-wrap gap-2">
                            <span className={`badge ${user.eligible ? 'text-light-success' : 'text-light-warning'}`}>{user.eligible ? 'Mos' : 'Mos emas'}</span>
                            {user.manuallyBlocked ? <span className="badge text-light-danger">Admin blok</span> : null}
                            {user.verified ? <span className="badge text-light-info">Verified</span> : null}
                          </div>
                          {user.reasons.length > 0 ? <small className="text-muted d-block mt-1">{user.reasons[0]}</small> : null}
                          {user.manuallyBlocked && user.manualBlockedAt ? <small className="text-danger d-block mt-1">Bloklangan: {user.manualBlockedAt}</small> : null}
                        </td>
                        <td>
                          <strong>{user.confidenceScore}</strong>
                          <div className="text-muted f-s-13">Rep: {user.reputationScore}</div>
                        </td>
                        <td>
                          <strong>{fmt(user.computedLimit)} so'm</strong>
                          <div className="text-muted f-s-13">Bo‘sh: {fmt(user.availableLimit)} so'm</div>
                        </td>
                        <td>
                          <strong>{fmt(user.activeExposure)} so'm</strong>
                          <div className="text-muted f-s-13">COD strike: {user.codReturnStrikes}</div>
                        </td>
                        <td>
                          <strong>{user.verifiedCardsCount} ta</strong>
                          <div className="text-muted f-s-13">{fmt(user.verifiedCardAgeDays)} kun</div>
                        </td>
                        <td>
                          <strong>{user.completedOrders}</strong>
                          <div className="text-muted f-s-13">Saved-card: {user.successfulCardPayments180d}</div>
                        </td>
                        <td>
                          {user.reasons.length === 0 ? <span className="badge text-light-success">Toza</span> : <span className="badge text-light-warning">{user.reasons.length} ta signal</span>}
                        </td>
                        <td>{user.lastRefreshedAt || '—'}</td>
                        <td>
                          <div className="d-flex gap-2">
                            <a className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" href={user.profileUrl}><i className="ti ti-address-book"></i></a>
                            <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => refreshProfiles(user.id)}><i className="ti ti-rotate-clockwise"></i></button>
                            {user.manuallyBlocked ? (
                              <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => router.post(user.unblockUrl, {}, { preserveScroll: true })}><i className="ti ti-lock-open"></i></button>
                            ) : (
                              <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => {
                                const reason = window.prompt('Splitni bloklash sababi');
                                if (!reason) return;
                                router.post(user.blockUrl, { reason }, { preserveScroll: true });
                              }}><i className="ti ti-ban"></i></button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                    {splitUsers.length === 0 ? <tr><td colSpan={10} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Split profili topilmadi</td></tr> : null}
                  </tbody>
                </table>
              </div>
              <PaginationControls {...splitPagination} onPageChange={(page) => reload(page)} />
            </div>
          </div>
        </div>
        ) : null}
      </div>
    </div>
  );
}

function submitForm(event: FormEvent<HTMLFormElement>, url: string) {
  event.preventDefault();
  const formData = Object.fromEntries(new FormData(event.currentTarget).entries());
  router.put(url, formData, { preserveScroll: true });
}

const contractStatusChip: Record<string, [string, string]> = {
  pending: ['text-light-info', 'Kutilmoqda (hold)'],
  active: ['text-light-success', 'Faol'],
  overdue: ['text-light-danger', 'Muddati o‘tgan'],
  completed: ['text-light-secondary', 'Yopilgan'],
  cancelled: ['text-light-secondary', 'Bekor'],
  defaulted: ['text-light-danger', 'Default'],
};

const installmentStatusChip: Record<string, [string, string]> = {
  pending: ['text-light-info', 'Kutilmoqda'],
  paid: ['text-light-success', 'To‘langan'],
  overdue: ['text-light-danger', 'Kechikkan'],
  waived: ['text-light-secondary', 'Kechirilgan'],
  cancelled: ['text-light-secondary', 'Bekor'],
};

function PlanSection({ plans, storeUrl, previewUrl }: { plans: SplitPlanRow[]; storeUrl: string; previewUrl: string }) {
  const [preview, setPreview] = useState<{ planId: number; data: SchedulePreview } | null>(null);
  const [previewAmount, setPreviewAmount] = useState(500000);
  const [previewDelivery, setPreviewDelivery] = useState(0);
  const [loadingPlanId, setLoadingPlanId] = useState<number | null>(null);

  const loadPreview = async (planId: number) => {
    setLoadingPlanId(planId);
    try {
      const response = await fetch(`${previewUrl}?plan_id=${planId}&amount=${previewAmount}&delivery_fee=${previewDelivery}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      if (!response.ok) throw new Error('preview failed');
      setPreview({ planId, data: await response.json() });
    } catch {
      alert('Jadvalni hisoblab bo‘lmadi.');
    } finally {
      setLoadingPlanId(null);
    }
  };

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600">
            Split tariflari
            <InfoHint
              text="Istagancha tarif qo'shing — har biri o'z muddati, foizi va to'lov chastotasi bilan. 1-to'lov xariddayoq olinadi, qolganlari sotib olish sanasidan hisoblangan aniq kunlarda avto yechiladi. Yetkazish/qadoqlash splitga kirmaydi — foizsiz 1-to'lovga qo'shiladi."
              example="«2 oy · 0% · har 2 haftada» va «6 oy · oyiga 3% · har oyda» — ikkalasi parallel ishlaydi."
            />
          </h5>
          <p className="mb-0 text-secondary">Pastdagi bo&apos;sh qatordan yangi tarif qo&apos;shasiz. Har bir qiymat nima ekani ustun sarlavhasidagi <i className="ti ti-info-circle"></i> da.</p>
        </div>
        <div className="d-flex align-items-center gap-2 flex-wrap">
          <span className="text-muted f-s-13">Preview — mahsulot:</span>
          <input
      className="form-control form-control-sm w-130"
      type="number"
      min={1000}
      value={previewAmount}
      onChange={(event) => setPreviewAmount(Number(event.target.value) || 0)}
     />
          <span className="text-muted f-s-13">yetkazish:</span>
          <input
      className="form-control form-control-sm w-110"
      type="number"
      min={0}
      value={previewDelivery}
      onChange={(event) => setPreviewDelivery(Number(event.target.value) || 0)}
     />
        </div>
      </div>
      <div className="card-body">

        <div className="table-responsive app-scroll">
          <table className="table table-bottom-border align-middle">
            <thead>
              <tr>
                <th>Nomi</th>
                <th>
                  Muddat (oy)
                  <InfoHint text="Nasiya jami necha oyga bo'linadi." example="2, 4 yoki 6 oy." />
                </th>
                <th>
                  Chastota
                  <InfoHint
                    text="To'lov qanchalik tez-tez yechiladi. Muddat o'zgarmaydi, faqat bo'laklar soni o'zgaradi."
                    example="2 oylik tarif «har 2 haftada» bo'lsa 4 ta to'lovga bo'linadi, «har oyda» bo'lsa 2 ta."
                  />
                </th>
                <th>
                  Oylik %
                  <InfoHint
                    text="Umumiy ustama = oylik % × oy soni. 0 kiritsangiz tarif foizsiz bo'ladi."
                    example="4 oy, oyiga 2.5% → jami 10%. 1 mln buyurtmada 100 000 so'm ustama."
                  />
                </th>
                <th>Umumiy ustama</th>
                <th>To&apos;lovlar</th>
                <th>
                  Min/Max summa
                  <InfoHint
                    text="Bu tarif qaysi buyurtma summalarida ko'rinadi. Min qiymat 0 yoki bo'sh bo'lsa — minimum yo'q (shaxsiy limit baribir yuqoridan chegaralaydi)."
                    example="6 oylik tarifga min 500 000 qo'ysangiz, arzon buyurtmalarda 6 oy varianti chiqmaydi."
                  />
                </th>
                <th>
                  Min skor
                  <InfoHint
                    text="Ishonch balli (0–100, tizim hisoblaydi). Faqat balli shundan yuqori userlar bu tarifni ko'radi. Bo'sh = barcha mos userlarga ochiq."
                    example="Uzoq 6 oylik tarifga 70 qo'yib, uni faqat eng ishonchli mijozlarga bering."
                  />
                </th>
                <th>Holat</th>
                <th>Amal</th>
              </tr>
            </thead>
            <tbody>
              {plans.map((plan) => (
                <PlanRow key={plan.id} plan={plan} storeUrl={storeUrl} onPreview={() => loadPreview(plan.id)} previewLoading={loadingPlanId === plan.id} />
              ))}
              <PlanRow plan={null} storeUrl={storeUrl} />
              {plans.length === 0 ? (
                <tr><td colSpan={10} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hali tarif yo'q — yuqoridagi qatordan birinchi tarifni qo'shing (masalan: 2 oy, 0%, har 2 hafta).</td></tr>
              ) : null}
            </tbody>
          </table>
        </div>
        {preview ? (
          <div className="p-3 b-t-1-light">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <strong>
                Jadval preview — mahsulot {fmt(preview.data.principal)} so&apos;m, ustama {fmt(preview.data.interest)} so&apos;m ({preview.data.total_interest_percent}%)
                {preview.data.upfront_extra > 0 ? <> + yetkazish {fmt(preview.data.upfront_extra)} so&apos;m (1-to&apos;lovda, foizsiz)</> : null},
                jami {fmt(preview.data.total)} so&apos;m
              </strong>
              <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => setPreview(null)}><i className="ti ti-x"></i></button>
            </div>
            <div className="d-flex flex-wrap gap-2">
              {preview.data.installments.map((row) => (
                <span key={row.sequence} className={`badge ${row.is_upfront ? 'text-light-success' : 'text-light-info'}`}>
                  #{row.sequence}: {fmt(row.amount)} so&apos;m · {row.is_upfront ? 'hozir' : row.due_at.slice(0, 10)}
                </span>
              ))}
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}

function PlanRow({ plan, storeUrl, onPreview, previewLoading }: { plan: SplitPlanRow | null; storeUrl: string; onPreview?: () => void; previewLoading?: boolean }) {
  const formId = plan ? `split-plan-${plan.id}` : 'split-plan-new';

  return (
    <tr className={plan ? '' : 'table-light'}>
      <td>
        {plan ? <input form={formId} type="hidden" name="id" value={plan.id} /> : null}
        <input form={formId} className="form-control form-control-sm" name="name" placeholder="Masalan: 2 oy foizsiz" defaultValue={plan?.name ?? ''} required />
      </td>
      <td><input form={formId} className="form-control form-control-sm w-70" name="months" type="number" min={1} max={36} defaultValue={plan?.months ?? 2} /></td>
      <td>
        <div className="d-flex gap-1 align-items-center">
          <span className="text-muted f-s-13">har</span>
          <input form={formId} className="form-control form-control-sm w-55" name="period_every" type="number" min={1} max={8} defaultValue={plan?.periodEvery ?? 1} />
          <select form={formId} className="form-select form-select-sm w-90" name="period_unit" defaultValue={plan?.periodUnit ?? 'month'}>
            <option value="month">oyda</option>
            <option value="week">haftada</option>
          </select>
        </div>
      </td>
      <td><input form={formId} className="form-control form-control-sm w-80" name="monthly_interest_percent" type="number" min={0} max={30} step="0.01" defaultValue={plan?.monthlyInterestPercent ?? 0} /></td>
      <td>{plan ? <span className="badge text-light-primary">{plan.totalInterestPercent}%</span> : <span className="text-muted f-s-13">—</span>}</td>
      <td>{plan ? <span className="badge text-light-info">{plan.installmentsCount} ta · {plan.frequencyLabel}</span> : <span className="text-muted f-s-13">—</span>}</td>
      <td>
        <div className="d-flex gap-1">
          <input form={formId} className="form-control form-control-sm w-100" name="min_order_sum" type="number" min={0} placeholder="min (0 = yo'q)" title="0 yoki bo'sh qiymat minimal buyurtma cheklovini o'chiradi" defaultValue={plan?.minOrderSum ?? ''} />
          <input form={formId} className="form-control form-control-sm w-100" name="max_order_sum" type="number" min={1000} placeholder="max" defaultValue={plan?.maxOrderSum ?? ''} />
        </div>
      </td>
      <td><input form={formId} className="form-control form-control-sm w-70" name="min_confidence_score" type="number" min={0} max={100} step="0.01" placeholder="—" defaultValue={plan?.minConfidenceScore ?? ''} /></td>
      <td>
        <label className="d-flex align-items-center gap-2 mb-0">
          <input form={formId} type="hidden" name="enabled" value="0" />
          <input form={formId} type="checkbox" className="form-check-input" name="enabled" value="1" defaultChecked={plan?.enabled ?? false} />
          {plan ? <span className={`badge ${plan.enabled ? 'text-light-success' : 'text-light-secondary'}`}>{plan.enabled ? 'Faol' : 'O‘chiq'}</span> : <span className="text-muted f-s-13">yoqish</span>}
        </label>
      </td>
      <td>
        <form
          id={formId}
          className="d-inline"
          onSubmit={(event) => {
            event.preventDefault();
            router.post(storeUrl, Object.fromEntries(new FormData(event.currentTarget).entries()), { preserveScroll: true });
          }}
        >
          <button className="btn btn-sm btn-light-secondary me-1" title={plan ? 'Saqlash' : "Qo'shish"}>
            <i className={`ti ${plan ? 'ti-check' : 'ti-plus'}`}></i>
          </button>
        </form>
        {plan && onPreview ? (
          <button className="btn btn-sm btn-light-secondary me-1" title="Jadval preview" onClick={onPreview} disabled={previewLoading}>
            <i className={`ti ${previewLoading ? 'ti-hourglass' : 'ti-calendar-time'}`}></i>
          </button>
        ) : null}
        {plan ? (
          <button
            className="btn btn-light-danger icon-btn w-30 h-30 b-r-22"
            title="O'chirish"
            onClick={() => {
              if (plan.openContracts > 0) {
                alert(`Bu tarifda ${plan.openContracts} ta ochiq shartnoma bor.`);
                return;
              }
              if (confirm('Tarif o‘chirilsinmi?')) router.delete(plan.destroyUrl, { preserveScroll: true });
            }}
          >
            <i className="ti ti-trash"></i>
          </button>
        ) : null}
      </td>
    </tr>
  );
}

function ContractSection({
  contracts,
  filter,
  stats,
  plans,
  contractStoreUrl,
}: {
  contracts: SplitContractRow[];
  filter: string;
  stats: SplitContractStats;
  plans: SplitPlanRow[];
  contractStoreUrl: string;
}) {
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const enabledPlans = plans.filter((plan) => plan.enabled);
  const filterOptions: [string, string][] = [
    ['all', 'Barchasi'],
    ['overdue', "To'lanmayotganlar"],
    ['active', 'Faol'],
    ['pending', 'Kutilmoqda'],
    ['completed', 'Yopilgan'],
  ];
  const applyFilter = (value: string) => {
    router.get('/boshqaruv/split', value === 'all' ? {} : { contract_status: value }, { preserveScroll: true });
  };

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600">
            Split shartnomalari
            <InfoHint
              text="Holatlar: «Kutilmoqda» — 1-to'lov hold qilingan, buyurtma hali topshirilmagan. «Faol» — jadval bo'yicha to'lanmoqda. «Muddati o'tgan» — avto yechish 4 urinishda ham o'tmagan. «Yopilgan» — to'liq to'langan."
              example="Kutilmoqda holatidagi shartnoma buyurtma orqali boshqariladi: buyurtma topshirilsa avtomatik faollashadi, buyurtma bekor qilinsa hold qaytadi. Bu yerdan alohida bekor qilinmaydi."
            />
          </h5>
          <p className="mb-0 text-secondary">
            Ochiq: {stats.active} · Muddati o‘tgan: {stats.overdue} · Exposure: {fmt(stats.exposure)} so&apos;m · Oxirgi 30 kunda undirildi: {fmt(stats.collected30d)} so&apos;m
          </p>
        </div>
        <form
          className="d-flex gap-2 flex-wrap align-items-center"
          onSubmit={(event) => {
            event.preventDefault();
            router.post(contractStoreUrl, Object.fromEntries(new FormData(event.currentTarget).entries()), { preserveScroll: true });
          }}
        >
          <input className="form-control form-control-sm w-110" name="user_id" type="number" min={1} placeholder="User ID" required />
          <input className="form-control form-control-sm w-110" name="order_id" type="number" min={1} placeholder="Order ID" required />
          <select className="form-select form-select-sm w-200" name="plan_id" required defaultValue="">
            <option value="" disabled>Tarif tanlang</option>
            {enabledPlans.map((plan) => (
              <option key={plan.id} value={plan.id}>{plan.name} ({plan.months} oy, {plan.totalInterestPercent}%)</option>
            ))}
          </select>
          <button className="btn btn-sm btn-primary" disabled={enabledPlans.length === 0}>
            <i className="ti ti-plus me-1"></i>Split ochish
          </button>
        </form>
      </div>
      <div className="card-body">

        <div className="nav kc-segment mb-3">
          {filterOptions.map(([value, label]) => (
            <div key={value} className="nav-item"><button
                className={`nav-link ${filter === value ? 'active' : ''}`}
                onClick={() => applyFilter(value)}>
                {value === 'overdue' ? <i className="ti ti-alert-triangle me-1"></i> : null}
                {label}
                {value === 'overdue' && stats.overdue > 0 ? <span className="badge bg-danger ms-1">{stats.overdue}</span> : null}
              </button></div>
          ))}
        </div>
        <div className="table-responsive app-scroll">
          <table className="table table-bottom-border align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>User / Order</th>
                <th>Tarif</th>
                <th>Holat</th>
                <th>Jami / To‘langan</th>
                <th>Progress</th>
                <th>Keyingi to‘lov</th>
                <th>Amal</th>
              </tr>
            </thead>
            <tbody>
              {contracts.map((contract) => {
                const [chipClass, chipLabel] = contractStatusChip[contract.status] ?? ['text-light-secondary', contract.status];
                const expanded = expandedId === contract.id;

                return (
                  <Fragment key={contract.id}>
                    <tr>
                      <td>
                        <button className="btn btn-sm btn-light-secondary" onClick={() => setExpandedId(expanded ? null : contract.id)}>
                          <i className={`ti ${expanded ? 'ti-chevron-up' : 'ti-chevron-down'}`}></i>
                        </button>{' '}
                        <span className="f-w-600">#{contract.id}</span>
                      </td>
                      <td>
                        <div className="f-w-600">{contract.userName}</div>
                        <p className="mb-0 text-secondary">User #{contract.userId} · Order #{contract.orderId ?? '—'}</p>
                      </td>
                      <td>
                        <div>{contract.planName}</div>
                        <p className="mb-0 text-secondary">{contract.startsAt}{contract.debitDay ? ` · har oyning ${contract.debitDay}-kuni` : ''}</p>
                      </td>
                      <td>
                        <span className={`badge ${chipClass}`}>{chipLabel}</span>
                        {contract.overdueDays > 0 ? (
                          <div className="mt-1">
                            <span className="badge text-light-danger">{contract.overdueDays} kun kechikkan</span>
                            <small className="text-danger d-block">
                              {fmt(contract.overdueAmount)} so&apos;m muddati o&apos;tgan{contract.overdueSince ? ` · ${contract.overdueSince} dan beri` : ''}
                            </small>
                          </div>
                        ) : contract.overdueSince ? <small className="text-danger d-block">{contract.overdueSince} dan beri</small> : null}
                      </td>
                      <td>
                        <strong>{fmt(contract.total)} so&apos;m</strong>
                        <div className="text-muted f-s-13">To‘langan: {fmt(contract.paid)} · Qoldiq: {fmt(contract.remaining)}</div>
                      </td>
                      <td>
                        <span className="badge text-light-info">{contract.installmentsPaid}/{contract.installmentsCount}</span>
                      </td>
                      <td>
                        {contract.nextDueAt ? (
                          <>
                            <strong>{fmt(contract.nextAmount ?? 0)} so&apos;m</strong>
                            <div className="text-muted f-s-13">{contract.nextDueAt}</div>
                          </>
                        ) : <span className="text-muted">—</span>}
                      </td>
                      <td>
                        <div className="d-flex gap-1 flex-wrap">
                          <a className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" href={contract.contractPdfUrl} target="_blank" rel="noreferrer" title="Shartnoma PDF (mijoz tilida)">
                            <i className="ti ti-file-download text-danger"></i>
                          </a>
                          {contract.overdueDays > 0 || contract.status === 'overdue' ? (
                            <a className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" href={contract.demandLetterUrl} target="_blank" rel="noreferrer" title="Undirish xati (talabnoma/pretenziya)">
                              <i className="ti ti-mail-off text-danger"></i>
                            </a>
                          ) : null}
                          {contract.status === 'pending' ? (
                            <span
                              className="text-muted f-s-13"
                              title="Buyurtma topshirilganda shartnoma avtomatik faollashadi; buyurtma bekor qilinsa hold avtomatik qaytadi. Boshqaruv Buyurtmalar sahifasidan."
                            >
                              <i className="ti ti-link me-1"></i>
                              Order #{contract.orderId} orqali boshqariladi
                            </span>
                          ) : null}
                          {contract.status === 'active' || contract.status === 'overdue' ? (
                            <>
                              <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" title="Erta yopish (qolgan ustama kechiriladi)" onClick={() => { if (confirm('Shartnoma muddatidan oldin to‘liq yopilsinmi?')) router.post(contract.settleUrl, {}, { preserveScroll: true }); }}>
                                <i className="ti ti-flag-filled text-primary"></i>
                              </button>
                              <button
                                className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22"
                                title="Bekor qilingan mahsulot krediti (keyingi to'lovlardan ayiriladi)"
                                onClick={() => {
                                  const product = prompt('Bekor qilingan mahsulot narxi (so‘m):');
                                  if (product === null) return;
                                  const delivery = prompt('Yetkazish krediti bo‘lsa (so‘m, bo‘lmasa 0):', '0');
                                  if (delivery === null) return;
                                  router.post(contract.creditUrl, {
                                    product_amount: Number(product) || 0,
                                    delivery_credit: Number(delivery) || 0,
                                  }, { preserveScroll: true });
                                }}
                              >
                                <i className="ti ti-rotate text-warning"></i>
                              </button>
                            </>
                          ) : null}
                          {contract.refundDue > 0 ? (
                            <span className="badge text-light-danger" title="Ochiq to'lovlar kreditni qoplamadi — naqd refund kerak">Refund: {fmt(contract.refundDue)}</span>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                    {expanded ? (
                      <tr>
                        <td colSpan={8} className="bg-light-secondary">
                          <div className="d-flex flex-wrap gap-2 p-2">
                            {contract.installments.map((installment) => {
                              const [instChip, instLabel] = installmentStatusChip[installment.status] ?? ['text-light-secondary', installment.status];
                              const chargeable = (installment.status === 'pending' || installment.status === 'overdue') && !installment.isUpfront && (contract.status === 'active' || contract.status === 'overdue');

                              return (
                                <div key={installment.id} className="b-1-light b-r-8 p-2 bg-white">
                                  <div className="f-w-600">#{installment.sequence} · {fmt(installment.amount)} so&apos;m</div>
                                  <div className="f-s-13 text-muted">{installment.isUpfront ? 'Upfront (hozir)' : installment.dueAt}</div>
                                  <div className="d-flex align-items-center gap-2 mt-1">
                                    <span className={`badge ${instChip}`}>{instLabel}</span>
                                    {installment.attempts > 0 ? <small className="text-muted">{installment.attempts} urinish</small> : null}
                                    {chargeable ? (
                                      <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" title="Hozir yechish" onClick={() => { if (confirm('Bu installment hozir yechilsinmi?')) router.post(installment.chargeUrl, {}, { preserveScroll: true }); }}>
                                        <i className="ti ti-bolt text-warning"></i>
                                      </button>
                                    ) : null}
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                        </td>
                      </tr>
                    ) : null}
                  </Fragment>
                );
              })}
              {contracts.length === 0 ? <tr><td colSpan={8} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>{filter === 'all' ? "Hali shartnoma yo'q" : "Bu filtrga mos shartnoma yo'q"}</td></tr> : null}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function RuleSection({ title, rows, actionUrl }: { title: string; rows: SplitRuleRow[]; actionUrl: string }) {
  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
          <h5 className="f-w-600">
            {title}
            <InfoHint
              text="Kategoriya darajasida faqat ruxsat/taqiq boshqariladi (summa va foiz tariflarda). Agar HECH BITTA kategoriya yoqilmagan bo'lsa — cheklov yo'q, hammasi nasiyada sotiladi. Kamida bittasi yoqilsa — faqat yoqilgan kategoriyalar nasiyaga chiqadi."
              example="Faqat «Badiiy adabiyot»ni yoqsangiz, boshqa kategoriyali mahsulot bor savatga nasiya ochilmaydi."
            />
          </h5>
          <p className="mb-0 text-secondary">Yoqilgan: {rows.filter((row) => row.enabled).length} / {rows.length}</p>
        </div>
      </div>
      <div className="card-body">

        <div className="table-responsive app-scroll">
          <table className="table table-bottom-border align-middle">
            <thead>
              <tr>
                <th>Kategoriya</th>
                <th>Nasiyaga ruxsat</th>
                <th>Amal</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={`${row.categoryType}-${row.id}`}>
                  <td>
                    <div className="f-w-600">{row.name}</div>
                    <p className="mb-0 text-secondary">{row.active ? 'Katalogda faol' : 'Kategoriya nofaol'}</p>
                  </td>
                  <td>
                    <label className="d-flex align-items-center gap-2 mb-0">
                      <input form={`split-rule-${row.categoryType}-${row.id}`} type="hidden" name="enabled" value="0" />
                      <input form={`split-rule-${row.categoryType}-${row.id}`} type="checkbox" className="form-check-input" name="enabled" value="1" defaultChecked={row.enabled} />
                      <span className={`badge ${row.enabled ? 'text-light-success' : 'text-light-secondary'}`}>{row.enabled ? 'Ruxsat' : 'Taqiq'}</span>
                    </label>
                  </td>
                  <td>
                    <form id={`split-rule-${row.categoryType}-${row.id}`} onSubmit={(event) => submitRule(event, actionUrl)} className="d-inline">
                      <input type="hidden" name="category_type" value={row.categoryType} />
                      <input type="hidden" name="category_id" value={row.id} />
                      <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-2" title="Saqlash"><i className="ti ti-check"></i></button>
                    </form>
                    {row.destroyUrl ? <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" title="Qoidani o'chirish" onClick={() => resetRule(row.destroyUrl)}><i className="ti ti-trash"></i></button> : null}
                  </td>
                </tr>
              ))}
              {rows.length === 0 ? <tr><td colSpan={3} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Kategoriya topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function submitRule(event: FormEvent<HTMLFormElement>, url: string) {
  event.preventDefault();
  const form = event.currentTarget;
  const data = Object.fromEntries(new FormData(form).entries());
  router.post(url, data, { preserveScroll: true });
}

function resetRule(url: string) {
  if (!confirm("Kategoriya override qoidasi o'chirilsinmi?")) return;
  router.delete(url, { preserveScroll: true });
}

function InfoHint({ text, example }: { text: string; example?: string }) {
  const [open, setOpen] = useState(false);

  return (
    <span className="position-relative d-inline-block" style={{ verticalAlign: 'middle' }}>
      <i
    className="ti ti-info-circle ms-1 text-secondary f-s-12 cursor-pointer"
    onClick={(event) => {
     event.preventDefault();
     event.stopPropagation();
     setOpen((value) => !value);
    }}
   ></i>
      {open ? (
        <span className="position-absolute w-270 text-white b-r-15 f-s-12 f-w-400 cursor-pointer"
          onClick={(event) => {
            event.preventDefault();
            event.stopPropagation();
            setOpen(false);
          }}
          style={{ zIndex: 60, top: 20, left: -120, background: 'rgba(var(--primary), 1)', padding: '10px 12px', lineHeight: 1.5, textAlign: 'left', whiteSpace: 'normal', textTransform: 'none', letterSpacing: 'normal', boxShadow: 'var(--box-shadow)' }}
        >
          {text}
          {example ? (
            <span className="d-block text-white" style={{ marginTop: 6, opacity: .72 }}>
              <i className="ti ti-bulb me-1"></i>Misol: {example}
            </span>
          ) : null}
        </span>
      ) : null}
    </span>
  );
}

function Toggle({ name, label, defaultChecked, hint, example }: { name: string; label: string; defaultChecked?: boolean; hint?: string; example?: string }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 b-r-8 b-1-light h-100">
      <span className="f-w-600">
        {label}
        {hint ? <InfoHint text={hint} example={example} /> : null}
      </span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

function Field({ name, label, defaultValue, type = 'text', step, min, max, hint, example }: { name: string; label: string; defaultValue: string | number; type?: string; step?: string; min?: number; max?: number; hint?: string; example?: string }) {
  return (
    <div>
      <label className="form-label f-s-13 text-muted f-w-600">
        {label}
        {hint ? <InfoHint text={hint} example={example} /> : null}
      </label>
      <input name={name} type={type} step={step} min={min} max={max} className="form-control" defaultValue={defaultValue} />
    </div>
  );
}
