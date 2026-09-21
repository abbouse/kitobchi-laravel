import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));
const money = (n: number) => `${fmt(n)} so'm`;

interface Row {
  id: number;
  owner?: 'seller' | 'courier';
  seller: string;
  phone?: string;
  courierId?: number;
  orderId?: number;
  sellerOrderId?: number;
  courierOrderId?: number;
  courierTaskId?: number;
  amount: number;
  netAmount: number;
  balanceEffect: number;
  actualPercent: number;
  actualCommission: number;
  expectedPercent: number;
  expectedCommission: number;
  basePercent?: number;
  baseCommission?: number;
  benefitAmount?: number;
  ruleSource: string;
  sellerRate: number;
  type?: string;
  category?: string;
  auditKind?: string;
  distanceKm?: number;
  baseFee?: number;
  distanceFee?: number;
  bonus?: number;
  diffPercent: number;
  diffAmount: number;
  status: string;
  date?: string;
  ok: boolean;
}

export default function CommissionAudit() {
  const {
    commissionAuditRows = [],
    commissionAuditPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    commissionAuditTotals = {},
    commissionRules = [],
    commissionAuditFilters = {},
  } = usePage<{
    commissionAuditRows?: Row[];
    commissionAuditPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    commissionAuditTotals?: Record<string, number>;
    commissionRules?: Array<{ id: number | string; from: number; to: number; percent: number; label?: string; bonus?: number; base?: number; perKm?: number; min?: number }>;
    commissionAuditFilters?: { owner?: string };
  }>().props;
  const owner = commissionAuditFilters.owner === 'courier' ? 'courier' : 'seller';
  const isCourier = owner === 'courier';
  const goOwner = (nextOwner: string) => router.get('/boshqaruv/commission-audit', { commission_audit_owner: nextOwner, commission_page: 1 }, { preserveState: true, preserveScroll: true, replace: true });

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Komissiya audit</h4><PageCrumbs />
          <p className="mb-0 text-secondary">{isCourier ? 'Kuryer payout, km formula, bonus va withdrawal komissiyasi real tranzaksiyalar bilan solishtiriladi.' : 'Seller-specific va global komissiya qoidalari real tranzaksiyalar bilan solishtiriladi.'}</p>
        </div>
      </div>

      <div className="nav nav-tabs app-tabs-primary flex-wrap mb-3">
        <div className="nav-item"><button className={`nav-link ${owner === 'seller' ? 'active' : ''}`} onClick={() => goOwner('seller')}>Seller audit</button></div>
        <div className="nav-item"><button className={`nav-link ${owner === 'courier' ? 'active' : ''}`} onClick={() => goOwner('courier')}>Kuryer audit</button></div>
      </div>

      <div className="row">
        {[
          ['Yozuvlar', commissionAuditTotals.rows || 0, 'ti-list-check', 'rgba(var(--primary), 1)'],
          ['Farq bor', commissionAuditTotals.mismatches || 0, 'ti-alert-triangle', 'rgba(var(--danger), 1)'],
          [isCourier ? 'Km formula' : 'Seller qoidasi', commissionAuditTotals.sellerSpecific || 0, isCourier ? 'ti-directions' : 'ti-building-store', 'rgba(var(--success), 1)'],
          ['Balansga qo‘shilgan', commissionAuditTotals.balanceAdded || 0, 'ti-wallet', 'rgba(var(--info-dark), 1)'],
        ].map(([label, value, icon, color], kpiIndex) => (<div className="col-xl-3 col-md-6" key={String(label)}>
          <StatWidget index={kpiIndex} label={label} value={fmt(Number(value))} />
        </div>))}
      </div>

      <div className="card">
<div className="card-header">
          <h5 className="mb-0">{isCourier ? 'Kuryer payout qoidalari' : 'Global komissiya qoidalari'}</h5>
        </div>
<div className="card-body">

          <div className="d-flex flex-wrap gap-2">
            {commissionRules.length ? commissionRules.map((rule) => (
              <span className="badge text-light-info" key={rule.id}>{rule.label === 'Kuryer km payout' ? `Base ${money(rule.base || 0)} · 1 km ${money(rule.perKm || 0)} · min ${money(rule.min || 0)}` : rule.label === 'Kuryer bonus' ? `${rule.from} - ${rule.to || '∞'} km · bonus ${money(rule.bonus || 0)}` : `${money(rule.from)} - ${rule.to ? money(rule.to) : 'cheksiz'} · ${rule.percent}%`}</span>
            )) : <span className="text-muted f-s-13">Global qoidalar topilmadi.</span>}
          </div>
        </div>
</div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div><h5 className="f-w-600">Tranzaksiya bo'yicha tekshiruv</h5><small className="text-muted">{isCourier ? 'Delivery/hub uchun km formula, withdrawal uchun komissiya qoidasi tekshiriladi.' : "0 yoki bo'sh seller komissiyasi global qoida bilan hisoblanadi."}</small></div>
          <span className="badge text-light-secondary">{commissionAuditPagination.total} ta</span>
        </div>
        <div className="card-body">

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>{isCourier ? 'Kuryer' : 'Seller'}</th><th>Order</th><th>Summa</th><th>Balansga</th><th>Qoida</th><th>Amalda</th><th>Kutilgan</th><th>Farq</th><th>Status</th></tr></thead>
              <tbody>
                {commissionAuditRows.map((row) => (
                  <tr key={row.id}>
                    <td className="f-w-600 text-nowrap">#{row.id}</td>
                    <td><strong>{row.seller}</strong><small className="d-block text-muted">{row.phone || '—'}</small></td>
                    <td><span className="badge text-light-secondary">#{row.orderId || '—'}</span><small className="d-block text-muted">{isCourier ? `CO #${row.courierOrderId || '—'} · TASK #${row.courierTaskId || '—'}` : `SELL #${row.sellerOrderId || '—'}`}</small></td>
                    <td>{money(row.amount)}</td>
                    <td>
                      <strong className={row.balanceEffect < 0 ? 'text-danger' : row.balanceEffect > 0 ? 'text-success' : 'text-muted'}>
                        {row.balanceEffect > 0 ? '+' : ''}{money(row.balanceEffect || row.netAmount || 0)}
                      </strong>
                      <small className="d-block text-muted">{row.balanceEffect > 0 ? 'seller balansiga qo‘shildi' : row.balanceEffect < 0 ? 'balansdan qaytarildi' : 'balansga ta’sir yo‘q'}</small>
                    </td>
                    <td><span className={`badge ${['seller', 'individual', 'promotion', 'km_formula'].includes(row.ruleSource) ? 'text-light-success' : 'text-light-info'}`}>{isCourier ? courierRuleLabel(row) : sellerRuleLabel(row)}</span></td>
                    <td>{isCourier && row.auditKind !== 'withdrawal_commission' ? money(row.actualCommission) : `${row.actualPercent}% · ${money(row.actualCommission)}`}</td>
                    <td>{isCourier && row.auditKind !== 'withdrawal_commission' ? money(row.expectedCommission) : row.ruleSource === 'promotion' ? `${money(row.baseCommission || 0)} − ${money(row.benefitAmount || 0)} = ${money(row.expectedCommission)}` : `${row.expectedPercent}% · ${money(row.expectedCommission)}`}</td>
                    <td><span className={`badge ${row.ok ? 'text-light-success' : 'text-light-danger'}`}>{row.ok ? 'OK' : `${isCourier && row.auditKind !== 'withdrawal_commission' ? '' : `${row.diffPercent}% · `}${money(row.diffAmount)}`}</span></td>
                    <td><small className="text-muted">{row.status} · {row.date || '—'}</small></td>
                  </tr>
                ))}
                {commissionAuditRows.length === 0 ? <tr><td colSpan={10} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Audit yozuvi topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...commissionAuditPagination} onPageChange={(page) => router.get('/boshqaruv/commission-audit', { commission_audit_owner: owner, commission_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
        </div>
      </div>
    </div>
  );
}

function courierRuleLabel(row: Row) {
  if (row.auditKind === 'withdrawal_commission') return `Withdrawal · ${row.expectedPercent}%`;
  if (row.ruleSource === 'km_formula') return `${Number(row.distanceKm || 0).toFixed(2)} km · base ${money(row.baseFee || 0)} + km ${money(row.distanceFee || 0)} + bonus ${money(row.bonus || 0)}`;
  return row.ruleSource || 'Tranzaksiya';
}

function sellerRuleLabel(row: Row) {
  if (row.ruleSource === 'promotion') return `Imtiyoz · ${row.actualPercent}% (bazaviy ${row.basePercent || 0}%)`;
  if (row.ruleSource === 'individual' || row.ruleSource === 'seller') return `Individual · ${row.expectedPercent}%`;
  if (row.ruleSource === 'legacy_snapshot') return `Tarixiy snapshot · ${row.expectedPercent}%`;
  if (row.ruleSource === 'missing') return 'Tarif topilmadi · 0%';
  return `Global · ${row.expectedPercent}%`;
}
