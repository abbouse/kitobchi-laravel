import { FormEvent, Fragment, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type SplitSettings = {
  enabled: boolean;
  publicEnabled: boolean;
  upfrontPercent: number;
  termDays: number;
  globalMinOrderSum: number;
  globalMaxOrderSum: number;
  globalMinLimit: number;
  globalMaxLimit: number;
  minCompletedOrders: number;
  minAccountAgeDays: number;
  minCardAgeDays: number;
  minReputationScore: number;
  maxActiveContracts: number;
  defaultFeePercent: number;
  cardDeleteLockEnabled: boolean;
  refundSenderCardId: string;
  refundServiceId: string;
};

type SplitSummary = {
  profiles: number;
  eligible: number;
  locked: number;
  avgConfidence: number;
  totalAvailableLimit: number;
};

type SplitRuleRow = {
  id: number;
  categoryType: 'book' | 'stationery';
  name: string;
  active: boolean;
  enabled: boolean;
  feePercent: number | null;
  minOrderSumOverride: number | null;
  maxOrderSumOverride: number | null;
  upfrontPercentOverride: number | null;
  destroyUrl?: string | null;
};

type SplitUserRow = {
  id: number;
  name: string;
  phone: string;
  verified: boolean;
  eligible: boolean;
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
  installments: SplitInstallmentRow[];
  activateUrl: string;
  cancelUrl: string;
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
    router.post(splitActions.refreshUrl, userId ? { user_id: userId } : {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Split nazorati</h1>
          <p className="page-subtitle">Tariflar (muddat, foiz, chastota), shartnomalar, kategoriya va foydalanuvchi ishonchlilik profilini boshqarish</p>
        </div>
        <div className="d-flex gap-2 flex-wrap">
          <button className="btn btn-light" onClick={() => refreshProfiles()}><i className="bi bi-arrow-clockwise me-1"></i>Barchasini qayta hisoblash</button>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          ['Profil yozuvlari', splitSummary.profiles, 'bi-database-check', '#4f46e5'],
          ['Mos userlar', splitSummary.eligible, 'bi-patch-check', '#10b981'],
          ['Kartasi lock bo‘ladiganlar', splitSummary.locked, 'bi-lock', '#ef4444'],
          ['Bo‘sh limitlar jami', `${fmt(splitSummary.totalAvailableLimit)} so'm`, 'bi-wallet2', '#0ea5e9'],
        ].map(([label, value, icon, color]) => (
          <div className="col-xl-3 col-md-6" key={String(label)}>
            <div className="stat-card">
              <div className="d-flex gap-3 align-items-center">
                <div className="stat-icon" style={{ background: String(color) }}><i className={`bi ${icon}`}></i></div>
                <div>
                  <div className="stat-value">{String(value)}</div>
                  <div className="stat-label">{label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3">
        <div className="col-12">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Global split policy</div>
                <small className="text-muted">Mobilga chiqmaydi. Hozircha faqat admin va backend tayyorgarligi.</small>
              </div>
              <div className="d-flex flex-wrap gap-2">
                <span className="chip chip-info">Upfront: {splitSettings.upfrontPercent}%</span>
                <span className="chip chip-purple">Muddat: {splitSettings.termDays} kun</span>
                <span className={`chip ${splitSettings.enabled ? 'chip-success' : 'chip-warning'}`}>{splitSettings.enabled ? 'Ichki modul yoqilgan' : "Ichki modul o'chirilgan"}</span>
                <span className={`chip ${splitSettings.publicEnabled ? 'chip-danger' : 'chip-gray'}`}>{splitSettings.publicEnabled ? 'Public ko‘rinadi' : 'Public yashirin'}</span>
              </div>
            </div>
            <form onSubmit={(event) => submitForm(event, splitActions.settingsUpdateUrl)}>
              <div className="row g-3">
                <div className="col-lg-3"><Toggle name="split_enabled" label="Ichki split moduli yoqilgan" defaultChecked={splitSettings.enabled} /></div>
                <div className="col-lg-3"><Toggle name="split_public_enabled" label="Public checkoutga chiqarish" defaultChecked={splitSettings.publicEnabled} /></div>
                <div className="col-lg-3"><Toggle name="split_card_delete_lock_enabled" label="Aktiv splitda karta o‘chirish blok" defaultChecked={splitSettings.cardDeleteLockEnabled} /></div>
                <div className="col-lg-3">
                  <div className="mini-stat h-100">
                    <strong>To&apos;lov modeli</strong>
                    <span>Muddat/foiz/chastota endi pastdagi tariflarda sozlanadi. 1-to&apos;lov checkoutda hold, topshirilganda yechiladi; qolganlari jadval bo&apos;yicha avto.</span>
                  </div>
                </div>
                <div className="col-md-3"><Field name="split_global_min_order_sum" label="Min order summasi" type="number" defaultValue={splitSettings.globalMinOrderSum} /></div>
                <div className="col-md-3"><Field name="split_global_max_order_sum" label="Max order summasi" type="number" defaultValue={splitSettings.globalMaxOrderSum} /></div>
                <div className="col-md-3"><Field name="split_global_min_limit" label="Min limit" type="number" defaultValue={splitSettings.globalMinLimit} /></div>
                <div className="col-md-3"><Field name="split_global_max_limit" label="Max limit" type="number" defaultValue={splitSettings.globalMaxLimit} /></div>
                <div className="col-md-3"><Field name="split_min_completed_orders" label="Min completed order" type="number" defaultValue={splitSettings.minCompletedOrders} /></div>
                <div className="col-md-3"><Field name="split_min_account_age_days" label="Min account yoshi (kun)" type="number" defaultValue={splitSettings.minAccountAgeDays} /></div>
                <div className="col-md-3"><Field name="split_min_card_age_days" label="Min karta yoshi (kun)" type="number" defaultValue={splitSettings.minCardAgeDays} /></div>
                <div className="col-md-3"><Field name="split_min_reputation_score" label="Min reputation score" type="number" step="0.01" defaultValue={splitSettings.minReputationScore} /></div>
                <div className="col-md-3"><Field name="split_max_active_contracts" label="Max aktiv split" type="number" defaultValue={splitSettings.maxActiveContracts} /></div>
                <div className="col-md-3"><Field name="split_default_fee_percent" label="Default ustama (%)" type="number" step="0.01" defaultValue={splitSettings.defaultFeePercent} /></div>
                <div className="col-md-3"><Field name="paylov_refund_sender_card_id" label="Refund sender cardId" defaultValue={splitSettings.refundSenderCardId} /></div>
                <div className="col-md-3"><Field name="paylov_refund_service_id" label="Refund serviceId" defaultValue={splitSettings.refundServiceId} /></div>
                <div className="col-md-6">
                  <div className="mini-stat h-100">
                    <strong>Skor nima bilan hisoblanadi</strong>
                    <span>Reputation, yakunlangan pullik orderlar, GMV, verified karta yoshi, saved-card payment tarixi, device barqarorligi, cancel/COD strike va warninglar.</span>
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="mini-stat h-100">
                    <strong>Partial refund texnik sozlamasi</strong>
                    <span>Item yoki seller-order bo‘yicha refund kerak bo‘lsa, tizim aynan original Paylov kartaga `Account2Card/P2P` bilan pul qaytaradi. Shu uchun sender `cardId` va kerak bo‘lsa `serviceId` shu yerda saqlanadi.</span>
                  </div>
                </div>
              </div>
              <div className="text-end mt-3">
                <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>Policy saqlash</button>
              </div>
            </form>
          </div>
        </div>

        <div className="col-12">
          <PlanSection plans={splitPlans} storeUrl={splitActions.planStoreUrl} previewUrl={splitActions.planPreviewUrl} />
        </div>

        <div className="col-12">
          <ContractSection
            contracts={splitContracts}
            stats={splitContractStats}
            plans={splitPlans}
            contractStoreUrl={splitActions.contractStoreUrl}
          />
        </div>

        <div className="col-12">
          <RuleSection title="Kitob kategoriyalari" rows={splitBookRules} actionUrl={splitActions.ruleStoreUrl} />
        </div>

        <div className="col-12">
          <RuleSection title="Kanselyariya kategoriyalari" rows={splitStationeryRules} actionUrl={splitActions.ruleStoreUrl} />
        </div>

        <div className="col-12">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Foydalanuvchi split profillari</div>
                <small className="text-muted">{splitPagination.total} ta foydalanuvchi ko‘rinmoqda · o‘rtacha ishonch {splitSummary.avgConfidence}</small>
              </div>
              <form className="d-flex gap-2 flex-wrap" onSubmit={(event) => { event.preventDefault(); reload(); }}>
                <select className="form-select form-select-sm" style={{ width: 180 }} value={status} onChange={(event) => { const value = event.target.value; setStatus(value); reload(1, value, search); }}>
                  <option value="all">Barchasi</option>
                  <option value="eligible">Moslar</option>
                  <option value="ineligible">Mos emaslar</option>
                  <option value="locked">Exposure borlar</option>
                </select>
                <input className="form-control form-control-sm" style={{ width: 260 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ID, ism, telefon, email" />
                <button className="btn btn-sm btn-outline-secondary"><i className="bi bi-search"></i></button>
              </form>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Moslik</th>
                    <th>Skor</th>
                    <th>Limit</th>
                    <th>Exposure</th>
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
                        <div className="fw-semibold">#{user.id} {user.name}</div>
                        <small className="text-muted">{user.phone || 'Telefon yo‘q'}</small>
                      </td>
                      <td>
                        <div className="d-flex flex-wrap gap-2">
                          <span className={`chip ${user.eligible ? 'chip-success' : 'chip-warning'}`}>{user.eligible ? 'Mos' : 'Mos emas'}</span>
                          {user.verified ? <span className="chip chip-info">Verified</span> : null}
                        </div>
                        {user.reasons.length > 0 ? <small className="text-muted d-block mt-1">{user.reasons[0]}</small> : null}
                      </td>
                      <td>
                        <strong>{user.confidenceScore}</strong>
                        <div className="text-muted small">Rep: {user.reputationScore}</div>
                      </td>
                      <td>
                        <strong>{fmt(user.computedLimit)} so'm</strong>
                        <div className="text-muted small">Bo‘sh: {fmt(user.availableLimit)} so'm</div>
                      </td>
                      <td>
                        <strong>{fmt(user.activeExposure)} so'm</strong>
                        <div className="text-muted small">COD strike: {user.codReturnStrikes}</div>
                      </td>
                      <td>
                        <strong>{user.verifiedCardsCount} ta</strong>
                        <div className="text-muted small">{fmt(user.verifiedCardAgeDays)} kun</div>
                      </td>
                      <td>
                        <strong>{user.completedOrders}</strong>
                        <div className="text-muted small">Saved-card: {user.successfulCardPayments180d}</div>
                      </td>
                      <td>
                        {user.reasons.length === 0 ? <span className="chip chip-success">Toza</span> : <span className="chip chip-warning">{user.reasons.length} ta signal</span>}
                      </td>
                      <td>{user.lastRefreshedAt || '—'}</td>
                      <td>
                        <div className="d-flex gap-2">
                          <a className="btn btn-sm btn-light" href={user.profileUrl}><i className="bi bi-person-lines-fill"></i></a>
                          <button className="btn btn-sm btn-light" onClick={() => refreshProfiles(user.id)}><i className="bi bi-arrow-clockwise"></i></button>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {splitUsers.length === 0 ? <tr><td colSpan={10} className="text-center text-muted py-5">Split profili topilmadi</td></tr> : null}
                </tbody>
              </table>
            </div>
            <PaginationControls {...splitPagination} onPageChange={(page) => reload(page)} />
          </div>
        </div>
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
  pending: ['chip-info', 'Kutilmoqda (hold)'],
  active: ['chip-success', 'Faol'],
  overdue: ['chip-danger', 'Muddati o‘tgan'],
  completed: ['chip-gray', 'Yopilgan'],
  cancelled: ['chip-gray', 'Bekor'],
  defaulted: ['chip-danger', 'Default'],
};

const installmentStatusChip: Record<string, [string, string]> = {
  pending: ['chip-info', 'Kutilmoqda'],
  paid: ['chip-success', 'To‘langan'],
  overdue: ['chip-danger', 'Kechikkan'],
  waived: ['chip-gray', 'Kechirilgan'],
  cancelled: ['chip-gray', 'Bekor'],
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
    <div className="card-panel">
      <div className="panel-head">
        <div>
          <div className="panel-title">Split tariflari</div>
          <small className="text-muted">
            Istagancha muddat qo&apos;shing: masalan «2 oy · foizsiz · har 2 haftada» yoki «6 oy · oyiga 3% · har oy».
            Ustama = mahsulot summasi × oylik % × oy. 1-to&apos;lov hozir, qolganlari sotib olish sanasidan anchor qilinadi.
            Yetkazish haqi splitga kirmaydi: to&apos;liq 1-to&apos;lovga qo&apos;shiladi va unga foiz hisoblanmaydi.
          </small>
        </div>
        <div className="d-flex align-items-center gap-2 flex-wrap">
          <span className="text-muted small">Preview — mahsulot:</span>
          <input
            className="form-control form-control-sm"
            style={{ width: 130 }}
            type="number"
            min={1000}
            value={previewAmount}
            onChange={(event) => setPreviewAmount(Number(event.target.value) || 0)}
          />
          <span className="text-muted small">yetkazish:</span>
          <input
            className="form-control form-control-sm"
            style={{ width: 110 }}
            type="number"
            min={0}
            value={previewDelivery}
            onChange={(event) => setPreviewDelivery(Number(event.target.value) || 0)}
          />
        </div>
      </div>
      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>Nomi</th>
              <th>Muddat (oy)</th>
              <th>Chastota</th>
              <th>Oylik %</th>
              <th>Umumiy ustama</th>
              <th>To&apos;lovlar</th>
              <th>Min/Max summa</th>
              <th>Min skor</th>
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
              <tr><td colSpan={10} className="text-center text-muted py-3">Hali tarif yo&apos;q — yuqoridagi qatordan birinchi tarifni qo&apos;shing (masalan: 2 oy, 0%, har 2 hafta).</td></tr>
            ) : null}
          </tbody>
        </table>
      </div>
      {preview ? (
        <div className="p-3 border-top">
          <div className="d-flex justify-content-between align-items-center mb-2">
            <strong>
              Jadval preview — mahsulot {fmt(preview.data.principal)} so&apos;m, ustama {fmt(preview.data.interest)} so&apos;m ({preview.data.total_interest_percent}%)
              {preview.data.upfront_extra > 0 ? <> + yetkazish {fmt(preview.data.upfront_extra)} so&apos;m (1-to&apos;lovda, foizsiz)</> : null},
              jami {fmt(preview.data.total)} so&apos;m
            </strong>
            <button className="btn btn-sm btn-light" onClick={() => setPreview(null)}><i className="bi bi-x"></i></button>
          </div>
          <div className="d-flex flex-wrap gap-2">
            {preview.data.installments.map((row) => (
              <span key={row.sequence} className={`chip ${row.is_upfront ? 'chip-success' : 'chip-info'}`}>
                #{row.sequence}: {fmt(row.amount)} so&apos;m · {row.is_upfront ? 'hozir' : row.due_at.slice(0, 10)}
              </span>
            ))}
          </div>
        </div>
      ) : null}
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
      <td><input form={formId} className="form-control form-control-sm" style={{ width: 70 }} name="months" type="number" min={1} max={36} defaultValue={plan?.months ?? 2} /></td>
      <td>
        <div className="d-flex gap-1 align-items-center">
          <span className="text-muted small">har</span>
          <input form={formId} className="form-control form-control-sm" style={{ width: 55 }} name="period_every" type="number" min={1} max={8} defaultValue={plan?.periodEvery ?? 1} />
          <select form={formId} className="form-select form-select-sm" style={{ width: 90 }} name="period_unit" defaultValue={plan?.periodUnit ?? 'month'}>
            <option value="month">oyda</option>
            <option value="week">haftada</option>
          </select>
        </div>
      </td>
      <td><input form={formId} className="form-control form-control-sm" style={{ width: 80 }} name="monthly_interest_percent" type="number" min={0} max={30} step="0.01" defaultValue={plan?.monthlyInterestPercent ?? 0} /></td>
      <td>{plan ? <span className="chip chip-purple">{plan.totalInterestPercent}%</span> : <span className="text-muted small">—</span>}</td>
      <td>{plan ? <span className="chip chip-info">{plan.installmentsCount} ta · {plan.frequencyLabel}</span> : <span className="text-muted small">—</span>}</td>
      <td>
        <div className="d-flex gap-1">
          <input form={formId} className="form-control form-control-sm" style={{ width: 100 }} name="min_order_sum" type="number" min={1000} placeholder="min" defaultValue={plan?.minOrderSum ?? ''} />
          <input form={formId} className="form-control form-control-sm" style={{ width: 100 }} name="max_order_sum" type="number" min={1000} placeholder="max" defaultValue={plan?.maxOrderSum ?? ''} />
        </div>
      </td>
      <td><input form={formId} className="form-control form-control-sm" style={{ width: 70 }} name="min_confidence_score" type="number" min={0} max={100} step="0.01" placeholder="—" defaultValue={plan?.minConfidenceScore ?? ''} /></td>
      <td>
        <label className="d-flex align-items-center gap-2 mb-0">
          <input form={formId} type="hidden" name="enabled" value="0" />
          <input form={formId} type="checkbox" className="form-check-input" name="enabled" value="1" defaultChecked={plan?.enabled ?? false} />
          {plan ? <span className={`chip ${plan.enabled ? 'chip-success' : 'chip-gray'}`}>{plan.enabled ? 'Faol' : 'O‘chiq'}</span> : <span className="text-muted small">yoqish</span>}
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
          <button className="btn btn-sm btn-light me-1" title={plan ? 'Saqlash' : "Qo'shish"}>
            <i className={`bi ${plan ? 'bi-check2' : 'bi-plus-lg'}`}></i>
          </button>
        </form>
        {plan && onPreview ? (
          <button className="btn btn-sm btn-light me-1" title="Jadval preview" onClick={onPreview} disabled={previewLoading}>
            <i className={`bi ${previewLoading ? 'bi-hourglass-split' : 'bi-calendar-week'}`}></i>
          </button>
        ) : null}
        {plan ? (
          <button
            className="btn btn-sm btn-light text-danger"
            title="O'chirish"
            onClick={() => {
              if (plan.openContracts > 0) {
                alert(`Bu tarifda ${plan.openContracts} ta ochiq shartnoma bor.`);
                return;
              }
              if (confirm('Tarif o‘chirilsinmi?')) router.delete(plan.destroyUrl, { preserveScroll: true });
            }}
          >
            <i className="bi bi-trash"></i>
          </button>
        ) : null}
      </td>
    </tr>
  );
}

function ContractSection({
  contracts,
  stats,
  plans,
  contractStoreUrl,
}: {
  contracts: SplitContractRow[];
  stats: SplitContractStats;
  plans: SplitPlanRow[];
  contractStoreUrl: string;
}) {
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const enabledPlans = plans.filter((plan) => plan.enabled);

  return (
    <div className="card-panel">
      <div className="panel-head">
        <div>
          <div className="panel-title">Split shartnomalari</div>
          <small className="text-muted">
            Ochiq: {stats.active} · Muddati o‘tgan: {stats.overdue} · Exposure: {fmt(stats.exposure)} so&apos;m · Oxirgi 30 kunda undirildi: {fmt(stats.collected30d)} so&apos;m
          </small>
        </div>
        <form
          className="d-flex gap-2 flex-wrap align-items-center"
          onSubmit={(event) => {
            event.preventDefault();
            router.post(contractStoreUrl, Object.fromEntries(new FormData(event.currentTarget).entries()), { preserveScroll: true });
          }}
        >
          <input className="form-control form-control-sm" style={{ width: 110 }} name="user_id" type="number" min={1} placeholder="User ID" required />
          <input className="form-control form-control-sm" style={{ width: 110 }} name="order_id" type="number" min={1} placeholder="Order ID" required />
          <select className="form-select form-select-sm" style={{ width: 200 }} name="plan_id" required defaultValue="">
            <option value="" disabled>Tarif tanlang</option>
            {enabledPlans.map((plan) => (
              <option key={plan.id} value={plan.id}>{plan.name} ({plan.months} oy, {plan.totalInterestPercent}%)</option>
            ))}
          </select>
          <button className="btn btn-sm btn-primary-gradient" disabled={enabledPlans.length === 0}>
            <i className="bi bi-plus-lg me-1"></i>Split ochish
          </button>
        </form>
      </div>
      <div className="table-responsive">
        <table className="data-table">
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
              const [chipClass, chipLabel] = contractStatusChip[contract.status] ?? ['chip-gray', contract.status];
              const expanded = expandedId === contract.id;

              return (
                <Fragment key={contract.id}>
                  <tr>
                    <td>
                      <button className="btn btn-sm btn-light" onClick={() => setExpandedId(expanded ? null : contract.id)}>
                        <i className={`bi ${expanded ? 'bi-chevron-up' : 'bi-chevron-down'}`}></i>
                      </button>{' '}
                      <span className="fw-semibold">#{contract.id}</span>
                    </td>
                    <td>
                      <div className="fw-semibold">{contract.userName}</div>
                      <small className="text-muted">User #{contract.userId} · Order #{contract.orderId ?? '—'}</small>
                    </td>
                    <td>
                      <div>{contract.planName}</div>
                      <small className="text-muted">{contract.startsAt}{contract.debitDay ? ` · har oyning ${contract.debitDay}-kuni` : ''}</small>
                    </td>
                    <td>
                      <span className={`chip ${chipClass}`}>{chipLabel}</span>
                      {contract.overdueSince ? <small className="text-danger d-block">{contract.overdueSince} dan beri</small> : null}
                    </td>
                    <td>
                      <strong>{fmt(contract.total)} so&apos;m</strong>
                      <div className="text-muted small">To‘langan: {fmt(contract.paid)} · Qoldiq: {fmt(contract.remaining)}</div>
                    </td>
                    <td>
                      <span className="chip chip-info">{contract.installmentsPaid}/{contract.installmentsCount}</span>
                    </td>
                    <td>
                      {contract.nextDueAt ? (
                        <>
                          <strong>{fmt(contract.nextAmount ?? 0)} so&apos;m</strong>
                          <div className="text-muted small">{contract.nextDueAt}</div>
                        </>
                      ) : <span className="text-muted">—</span>}
                    </td>
                    <td>
                      <div className="d-flex gap-1 flex-wrap">
                        {contract.status === 'pending' ? (
                          <>
                            <button className="btn btn-sm btn-light" title="Faollashtirish (holdni yechish)" onClick={() => { if (confirm('1-to‘lov holddan yechilsinmi?')) router.post(contract.activateUrl, {}, { preserveScroll: true }); }}>
                              <i className="bi bi-play-fill text-success"></i>
                            </button>
                            <button className="btn btn-sm btn-light" title="Bekor qilish (hold qaytadi)" onClick={() => { if (confirm('Shartnoma bekor qilinsinmi?')) router.post(contract.cancelUrl, {}, { preserveScroll: true }); }}>
                              <i className="bi bi-x-lg text-danger"></i>
                            </button>
                          </>
                        ) : null}
                        {contract.status === 'active' || contract.status === 'overdue' ? (
                          <>
                            <button className="btn btn-sm btn-light" title="Erta yopish (qolgan ustama kechiriladi)" onClick={() => { if (confirm('Shartnoma muddatidan oldin to‘liq yopilsinmi?')) router.post(contract.settleUrl, {}, { preserveScroll: true }); }}>
                              <i className="bi bi-flag-fill text-primary"></i>
                            </button>
                            <button
                              className="btn btn-sm btn-light"
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
                              <i className="bi bi-arrow-counterclockwise text-warning"></i>
                            </button>
                          </>
                        ) : null}
                        {contract.refundDue > 0 ? (
                          <span className="chip chip-danger" title="Ochiq to'lovlar kreditni qoplamadi — naqd refund kerak">Refund: {fmt(contract.refundDue)}</span>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                  {expanded ? (
                    <tr>
                      <td colSpan={8} className="bg-light">
                        <div className="d-flex flex-wrap gap-2 p-2">
                          {contract.installments.map((installment) => {
                            const [instChip, instLabel] = installmentStatusChip[installment.status] ?? ['chip-gray', installment.status];
                            const chargeable = (installment.status === 'pending' || installment.status === 'overdue') && !installment.isUpfront && (contract.status === 'active' || contract.status === 'overdue');

                            return (
                              <div key={installment.id} className="border rounded p-2 bg-white">
                                <div className="fw-semibold">#{installment.sequence} · {fmt(installment.amount)} so&apos;m</div>
                                <div className="small text-muted">{installment.isUpfront ? 'Upfront (hozir)' : installment.dueAt}</div>
                                <div className="d-flex align-items-center gap-2 mt-1">
                                  <span className={`chip ${instChip}`}>{instLabel}</span>
                                  {installment.attempts > 0 ? <small className="text-muted">{installment.attempts} urinish</small> : null}
                                  {chargeable ? (
                                    <button className="btn btn-sm btn-light" title="Hozir yechish" onClick={() => { if (confirm('Bu installment hozir yechilsinmi?')) router.post(installment.chargeUrl, {}, { preserveScroll: true }); }}>
                                      <i className="bi bi-lightning-charge text-warning"></i>
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
            {contracts.length === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Hali shartnoma yo&apos;q</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

function RuleSection({ title, rows, actionUrl }: { title: string; rows: SplitRuleRow[]; actionUrl: string }) {
  return (
    <div className="card-panel">
      <div className="panel-head">
        <div>
          <div className="panel-title">{title}</div>
          <small className="text-muted">Faqat admin ruxsat bergan kategoriyalarda split chiqishi mumkin bo‘ladi.</small>
        </div>
      </div>
      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>Kategoriya</th>
              <th>Holat</th>
              <th>Ustama %</th>
              <th>Min summa</th>
              <th>Max summa</th>
              <th>Upfront %</th>
              <th>Amal</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={`${row.categoryType}-${row.id}`}>
                <td>
                  <div className="fw-semibold">{row.name}</div>
                  <small className="text-muted">{row.active ? 'Katalogda faol' : 'Kategoriya nofaol'}</small>
                </td>
                <td>
                  <label className="d-flex align-items-center gap-2 mb-0">
                    <input form={`split-rule-${row.categoryType}-${row.id}`} type="hidden" name="enabled" value="0" />
                    <input form={`split-rule-${row.categoryType}-${row.id}`} type="checkbox" className="form-check-input" name="enabled" value="1" defaultChecked={row.enabled} />
                    <span className={`chip ${row.enabled ? 'chip-success' : 'chip-gray'}`}>{row.enabled ? 'Yoqilgan' : "O'chirilgan"}</span>
                  </label>
                </td>
                <td><input form={`split-rule-${row.categoryType}-${row.id}`} className="form-control form-control-sm" name="fee_percent" type="number" min={0} max={30} step="0.01" defaultValue={row.feePercent ?? ''} /></td>
                <td><input form={`split-rule-${row.categoryType}-${row.id}`} className="form-control form-control-sm" name="min_order_sum_override" type="number" min={1000} defaultValue={row.minOrderSumOverride ?? ''} /></td>
                <td><input form={`split-rule-${row.categoryType}-${row.id}`} className="form-control form-control-sm" name="max_order_sum_override" type="number" min={1000} defaultValue={row.maxOrderSumOverride ?? ''} /></td>
                <td><input form={`split-rule-${row.categoryType}-${row.id}`} className="form-control form-control-sm" name="upfront_percent_override" type="number" min={1} max={25} defaultValue={row.upfrontPercentOverride ?? ''} /></td>
                <td>
                  <form id={`split-rule-${row.categoryType}-${row.id}`} onSubmit={(event) => submitRule(event, actionUrl)} className="d-inline">
                    <input type="hidden" name="category_type" value={row.categoryType} />
                    <input type="hidden" name="category_id" value={row.id} />
                    <button className="btn btn-sm btn-light me-2"><i className="bi bi-check2"></i></button>
                  </form>
                  {row.destroyUrl ? <button className="btn btn-sm btn-light text-danger" onClick={() => resetRule(row.destroyUrl)}><i className="bi bi-trash"></i></button> : null}
                </td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={7} className="text-center text-muted py-5">Kategoriya topilmadi</td></tr> : null}
          </tbody>
        </table>
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

function Toggle({ name, label, defaultChecked }: { name: string; label: string; defaultChecked?: boolean }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 rounded border h-100">
      <span className="fw-semibold">{label}</span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

function Field({ name, label, defaultValue, type = 'text', step }: { name: string; label: string; defaultValue: string | number; type?: string; step?: string }) {
  return (
    <div>
      <label className="form-label small text-muted fw-semibold">{label}</label>
      <input name={name} type={type} step={step} className="form-control" defaultValue={defaultValue} />
    </div>
  );
}
