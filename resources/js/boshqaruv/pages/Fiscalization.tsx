import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

const fmt = (value: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(value || 0));

type Summary = {
  paid: number;
  registered: number;
  pending: number;
  failed: number;
  refunded: number;
  coveragePercent: number;
};

type Health = { key: string; label: string; ready: boolean; value: string; hint: string };
type Coverage = { total: number; ready: number; missing: number; direct: number; categoryFallback: number; globalFallback: number; percent: number };
type FiscalRow = {
  id: number;
  orderId: number;
  transactionId?: string | null;
  user: string;
  phone?: string | null;
  amount: number;
  status: 'registered' | 'pending' | 'failed' | 'refunded';
  attempts: number;
  lastAttemptAt?: string | null;
  error?: string | null;
  errorCode?: string | null;
  errorField?: string | null;
  receiptUrl?: string | null;
  refundReceiptUrl?: string | null;
  receiptId?: number | string | null;
  createdAt?: string | null;
  orderUrl: string;
  registerUrl: string;
  syncUrl: string;
};

type Pagination = { page: number; totalPages: number; from: number; to: number; total: number };

export default function Fiscalization() {
  const {
    fiscalSummary = { paid: 0, registered: 0, pending: 0, failed: 0, refunded: 0, coveragePercent: 100 },
    fiscalHealth = [],
    fiscalConfig = { receiptType: 1, amountMultiplier: 100, vatPercent: 0 },
    fiscalCatalogCoverage = {},
    fiscalTransactions = [],
    fiscalPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    fiscalFilters = {},
    retryPendingUrl = '',
    flash = {},
  } = usePage<{
    fiscalSummary?: Summary;
    fiscalHealth?: Health[];
    fiscalConfig?: { receiptType: number; amountMultiplier: number; vatPercent: number };
    fiscalCatalogCoverage?: { books?: Coverage; stationery?: Coverage };
    fiscalTransactions?: FiscalRow[];
    fiscalPagination?: Pagination;
    fiscalFilters?: { status?: string; search?: string };
    retryPendingUrl?: string;
    flash?: { success?: string; error?: string };
  }>().props;

  const [status, setStatus] = useState(fiscalFilters.status || 'all');
  const [search, setSearch] = useState(fiscalFilters.search || '');
  const [busy, setBusy] = useState<string | null>(null);
  const coverage = fiscalCatalogCoverage as { books?: Coverage; stationery?: Coverage };

  const load = (page = 1, nextStatus = status) => router.get('/boshqaruv/fiscalization', {
    fiscal_status: nextStatus,
    fiscal_search: search,
    fiscal_page: page,
  }, { preserveState: true, preserveScroll: true, replace: true });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    load(1);
  };

  const post = (url: string, key: string, data: Record<string, number> = {}) => {
    if (!url || busy) return;
    setBusy(key);
    router.post(url, data, { preserveScroll: true, onFinish: () => setBusy(null) });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Fiskalizatsiya</h1>
          <p className="page-subtitle">Paylov OFD cheklari, katalog kodlari va xatolarni bitta joydan nazorat qilish</p>
        </div>
        <button className="btn btn-primary-gradient" disabled={Boolean(busy) || !retryPendingUrl} onClick={() => post(retryPendingUrl, 'batch', { limit: 100 })}>
          <i className={`bi ${busy === 'batch' ? 'bi-arrow-repeat' : 'bi-arrow-clockwise'} me-2`}></i>
          100 ta kutilayotgan chekni yuborish
        </button>
      </div>

      {flash.success ? <div className="alert alert-success">{flash.success}</div> : null}
      {flash.error ? <div className="alert alert-danger">{flash.error}</div> : null}

      <div className="row g-3 mb-3">
        {[
          ['To‘langan orderlar', fiscalSummary.paid, 'bi-credit-card', '#4f46e5'],
          ['Fiskal chek tayyor', fiscalSummary.registered, 'bi-patch-check', '#10b981'],
          ['Chek kutilmoqda', fiscalSummary.pending, 'bi-hourglass-split', '#f59e0b'],
          ['Xato bilan qolgan', fiscalSummary.failed, 'bi-exclamation-octagon', '#ef4444'],
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

      <div className="row g-3 mb-3">
        <div className="col-xl-7">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div><div className="panel-title">Integratsiya tayyorligi</div><small className="text-muted">Secret qiymatlar panelda ochiq ko‘rsatilmaydi</small></div>
              <span className={`chip ${fiscalHealth.every((item) => item.ready) ? 'chip-success' : 'chip-warning'}`}>{fiscalHealth.filter((item) => item.ready).length}/{fiscalHealth.length} tayyor</span>
            </div>
            <div className="row g-2">
              {fiscalHealth.map((item) => (
                <div className="col-md-6" key={item.key}>
                  <div className="mini-stat h-100 d-flex align-items-start gap-3">
                    <div className={`rounded-circle d-grid flex-shrink-0 ${item.ready ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}`} style={{ width: 36, height: 36, placeItems: 'center' }}>
                      <i className={`bi ${item.ready ? 'bi-check-lg' : 'bi-exclamation-lg'}`}></i>
                    </div>
                    <div className="min-w-0"><strong className="d-block">{item.label}</strong><span className={item.ready ? 'text-success small' : 'text-danger small'}>{item.value}</span><small className="text-muted d-block mt-1">{item.hint}</small></div>
                  </div>
                </div>
              ))}
            </div>
            <div className="d-flex flex-wrap gap-2 mt-3">
              <span className="chip chip-gray">Receipt type: {fiscalConfig.receiptType}</span>
              <span className="chip chip-gray">Multiplier: ×{fiscalConfig.amountMultiplier}</span>
              <span className="chip chip-gray">QQS: {fiscalConfig.vatPercent}%</span>
              <span className="chip chip-info">Qamrov: {fiscalSummary.coveragePercent}%</span>
              <span className="chip chip-gray">Refund chek: {fmt(fiscalSummary.refunded)}</span>
            </div>
          </div>
        </div>
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">Katalog OFD qamrovi</div>
            <CoverageRow label="Kitoblar" coverage={coverage.books} href="/boshqaruv/books" />
            <CoverageRow label="Kanselyariya" coverage={coverage.stationery} href="/boshqaruv/stationeries" />
            <small className="text-muted d-block mt-3">Mahsulot kodi → kategoriya kodi → global fallback tartibida tekshiriladi. “Yetishmaydi” bo‘lgan mahsulot fiskal chekni to‘xtatadi.</small>
          </div>
        </div>
      </div>

      <div className="card-panel">
        <div className="panel-head gap-3 flex-wrap">
          <div><div className="panel-title">OFD tranzaksiyalari</div><small className="text-muted">{fiscalPagination.from}-{fiscalPagination.to} / {fiscalPagination.total}</small></div>
          <form className="d-flex gap-2 flex-wrap ms-auto" onSubmit={submit}>
            <select className="form-select form-select-sm" style={{ width: 180 }} value={status} onChange={(event) => { const value = event.target.value; setStatus(value); setTimeout(() => load(1, value), 0); }}>
              <option value="all">Barchasi</option>
              <option value="registered">Chek tayyor</option>
              <option value="pending">Kutilmoqda</option>
              <option value="failed">Xatolik</option>
              <option value="refunded">Refund qilingan</option>
            </select>
            <input className="form-control form-control-sm" style={{ width: 280 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Order ID, transaction, user" />
            <button className="btn btn-sm btn-light"><i className="bi bi-search"></i></button>
          </form>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>Order</th><th>Mijoz</th><th>Summa</th><th>Holat</th><th>Urinish</th><th>Paylov / xato</th><th>Sana</th><th>Amallar</th></tr></thead>
            <tbody>
              {fiscalTransactions.map((row) => (
                <tr key={row.id}>
                  <td><a className="fw-semibold text-primary" href={row.orderUrl}>#{row.orderId}</a><small className="text-muted d-block">TX #{row.id}</small></td>
                  <td><strong>{row.user}</strong><small className="text-muted d-block">{row.phone || '—'}</small></td>
                  <td className="fw-semibold">{fmt(row.amount)} so‘m</td>
                  <td><span className={`chip ${statusChip(row.status)}`}>{statusLabel(row.status)}</span>{row.receiptId ? <small className="text-muted d-block mt-1">Receipt #{row.receiptId}</small> : null}</td>
                  <td><strong>{row.attempts}</strong><small className="text-muted d-block">{row.lastAttemptAt || 'Hali yuborilmagan'}</small></td>
                  <td style={{ minWidth: 260 }}>
                    {row.error ? <><span className="text-danger small d-block">{row.error}</span><small className="text-muted">{[row.errorCode, row.errorField ? `field: ${row.errorField}` : null].filter(Boolean).join(' · ')}</small></> : <small className="text-muted text-break">{row.transactionId || 'Transaction ID yo‘q'}</small>}
                  </td>
                  <td className="text-muted">{row.createdAt || '—'}</td>
                  <td>
                    <div className="d-flex gap-1">
                      {row.receiptUrl ? <a className="btn btn-sm btn-light" href={row.receiptUrl} target="_blank" rel="noreferrer" title="Fiskal chek"><i className="bi bi-receipt"></i></a> : null}
                      {row.refundReceiptUrl ? <a className="btn btn-sm btn-light" href={row.refundReceiptUrl} target="_blank" rel="noreferrer" title="Refund chek"><i className="bi bi-arrow-counterclockwise"></i></a> : null}
                      <button className="btn btn-sm btn-light" disabled={Boolean(busy)} onClick={() => post(row.syncUrl, `sync-${row.id}`)} title="Paylovdan tekshirish"><i className={`bi ${busy === `sync-${row.id}` ? 'bi-arrow-repeat' : 'bi-cloud-download'}`}></i></button>
                      {row.status !== 'registered' && row.status !== 'refunded' ? <button className="btn btn-sm btn-outline-primary" disabled={Boolean(busy)} onClick={() => post(row.registerUrl, `register-${row.id}`)} title="Qayta fiskalizatsiya"><i className={`bi ${busy === `register-${row.id}` ? 'bi-arrow-repeat' : 'bi-send'}`}></i></button> : null}
                    </div>
                  </td>
                </tr>
              ))}
              {fiscalTransactions.length === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">Bu filter bo‘yicha fiskal tranzaksiya topilmadi.</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...fiscalPagination} onPageChange={(page) => load(page)} />
      </div>
    </div>
  );
}

function CoverageRow({ label, coverage, href }: { label: string; coverage?: Coverage; href: string }) {
  const data = coverage || { total: 0, ready: 0, missing: 0, direct: 0, categoryFallback: 0, globalFallback: 0, percent: 100 };
  return <div className="mini-stat mb-2">
    <div className="d-flex align-items-center justify-content-between gap-2"><strong>{label}</strong><a href={href} className={`chip text-decoration-none ${data.missing ? 'chip-danger' : 'chip-success'}`}>{data.ready}/{data.total} · {data.percent}%</a></div>
    <div className="progress mt-2" style={{ height: 6 }}><div className={`progress-bar ${data.missing ? 'bg-warning' : 'bg-success'}`} style={{ width: `${Math.max(0, Math.min(100, data.percent))}%` }}></div></div>
    <div className="d-flex flex-wrap gap-2 mt-2"><small className="text-muted">Product: {data.direct}</small><small className="text-muted">Kategoriya: {data.categoryFallback}</small><small className="text-muted">Global: {data.globalFallback}</small>{data.missing ? <small className="text-danger">Yetishmaydi: {data.missing}</small> : null}</div>
  </div>;
}

function statusChip(status: FiscalRow['status']) {
  if (status === 'registered') return 'chip-success';
  if (status === 'refunded') return 'chip-info';
  if (status === 'failed') return 'chip-danger';
  return 'chip-warning';
}

function statusLabel(status: FiscalRow['status']) {
  if (status === 'registered') return 'Chek tayyor';
  if (status === 'refunded') return 'Refund';
  if (status === 'failed') return 'Xatolik';
  return 'Kutilmoqda';
}
