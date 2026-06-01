import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));
const money = (n: number) => `${fmt(n)} so'm`;

interface Row {
  id: number;
  seller: string;
  phone?: string;
  orderId?: number;
  sellerOrderId?: number;
  amount: number;
  actualPercent: number;
  actualCommission: number;
  expectedPercent: number;
  expectedCommission: number;
  ruleSource: string;
  sellerRate: number;
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
  } = usePage<{
    commissionAuditRows?: Row[];
    commissionAuditPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    commissionAuditTotals?: Record<string, number>;
    commissionRules?: Array<{ id: number; from: number; to: number; percent: number }>;
  }>().props;

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Komissiya audit</h1>
          <p className="page-subtitle">Seller-specific va global komissiya qoidalari real tranzaksiyalar bilan solishtiriladi.</p>
        </div>
      </div>

      <div className="row g-3 mb-3">
        {[
          ['Yozuvlar', commissionAuditTotals.rows || 0, 'bi-list-check', '#4f46e5'],
          ['Farq bor', commissionAuditTotals.mismatches || 0, 'bi-exclamation-triangle', '#ef4444'],
          ['Seller qoidasi', commissionAuditTotals.sellerSpecific || 0, 'bi-shop', '#10b981'],
          ['Global qoida', commissionAuditTotals.global || 0, 'bi-globe', '#7c3aed'],
        ].map(([label, value, icon, color]) => (
          <div className="col-xl-3 col-md-6" key={String(label)}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: String(color) }}><i className={`bi ${icon}`}></i></div>
                <div><div className="stat-value">{fmt(Number(value))}</div><div className="stat-label">{label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel mb-3">
        <div className="panel-title mb-3">Global komissiya qoidalari</div>
        <div className="d-flex flex-wrap gap-2">
          {commissionRules.length ? commissionRules.map((rule) => (
            <span className="chip chip-info" key={rule.id}>{money(rule.from)} - {rule.to ? money(rule.to) : 'cheksiz'} · {rule.percent}%</span>
          )) : <span className="text-muted small">Global qoidalar topilmadi.</span>}
        </div>
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div><div className="panel-title">Tranzaksiya bo'yicha tekshiruv</div><small className="text-muted">0 yoki bo'sh seller komissiyasi global qoida bilan hisoblanadi.</small></div>
          <span className="chip chip-gray">{commissionAuditPagination.total} ta</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Seller</th><th>Order</th><th>Summa</th><th>Qoida</th><th>Amalda</th><th>Kutilgan</th><th>Farq</th><th>Status</th></tr></thead>
            <tbody>
              {commissionAuditRows.map((row) => (
                <tr key={row.id}>
                  <td className="fw-semibold text-primary">#{row.id}</td>
                  <td><strong>{row.seller}</strong><small className="d-block text-muted">{row.phone || '—'}</small></td>
                  <td><span className="chip chip-gray">#{row.orderId || '—'}</span><small className="d-block text-muted">SELL #{row.sellerOrderId || '—'}</small></td>
                  <td>{money(row.amount)}</td>
                  <td><span className={`chip ${row.ruleSource === 'seller' ? 'chip-success' : 'chip-info'}`}>{row.ruleSource === 'seller' ? 'Seller' : 'Global'} · {row.expectedPercent}%</span></td>
                  <td>{row.actualPercent}% · {money(row.actualCommission)}</td>
                  <td>{row.expectedPercent}% · {money(row.expectedCommission)}</td>
                  <td><span className={`chip ${row.ok ? 'chip-success' : 'chip-danger'}`}>{row.ok ? 'OK' : `${row.diffPercent}% · ${money(row.diffAmount)}`}</span></td>
                  <td><small className="text-muted">{row.status} · {row.date || '—'}</small></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...commissionAuditPagination} onPageChange={(page) => router.get('/boshqaruv/commission-audit', { commission_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
      </div>
    </div>
  );
}
