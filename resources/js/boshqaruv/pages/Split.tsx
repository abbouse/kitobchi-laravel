import { FormEvent, useState } from 'react';
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

export default function Split() {
  const {
    splitSettings,
    splitSummary,
    splitBookRules = [],
    splitStationeryRules = [],
    splitUsers = [],
    splitPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    splitFilters = {},
    splitActions,
  } = usePage<{
    splitSettings: SplitSettings;
    splitSummary: SplitSummary;
    splitBookRules?: SplitRuleRow[];
    splitStationeryRules?: SplitRuleRow[];
    splitUsers?: SplitUserRow[];
    splitPagination?: PaginationMeta;
    splitFilters?: { status?: string; search?: string };
    splitActions: { settingsUpdateUrl: string; ruleStoreUrl: string; refreshUrl: string };
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
          <p className="page-subtitle">2 oylik split uchun policy, kategoriya va foydalanuvchi ishonchlilik profilini boshqarish</p>
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
                    <strong>Qat’iy mahsulot qoidasi</strong>
                    <span>V1 faqat 2 oylik: {splitSettings.upfrontPercent}% hozir, qolgani {splitSettings.termDays} kundan keyin.</span>
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
