import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';
import { StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

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
type FiscalConfig = { standardReceiptType: number | null; splitReceiptType: number; splitAdvanceConfigured: boolean; amountMultiplier: number; vatPercent: number };
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
  errorData?: string | null;
  receiptUrl?: string | null;
  refundReceiptUrl?: string | null;
  receiptId?: number | string | null;
  createdAt?: string | null;
  orderUrl: string;
  registerUrl: string;
  syncUrl: string;
};

type Pagination = { page: number; totalPages: number; from: number; to: number; total: number };

const defaultSummary: Summary = { paid: 0, registered: 0, pending: 0, failed: 0, refunded: 0, coveragePercent: 100 };
const defaultConfig: FiscalConfig = { standardReceiptType: null, splitReceiptType: 2, splitAdvanceConfigured: true, amountMultiplier: 100, vatPercent: 0 };

export default function Fiscalization() {
  const {
    fiscalSummary = defaultSummary,
    fiscalHealth = [],
    fiscalConfig = defaultConfig,
    fiscalCatalogCoverage = {},
    fiscalTransactions = [],
    fiscalPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    fiscalFilters = {},
    retryPendingUrl = '',
  } = usePage<{
    fiscalSummary?: Summary;
    fiscalHealth?: Health[];
    fiscalConfig?: FiscalConfig;
    fiscalCatalogCoverage?: { books?: Coverage; stationery?: Coverage };
    fiscalTransactions?: FiscalRow[];
    fiscalPagination?: Pagination;
    fiscalFilters?: { status?: string; search?: string };
    retryPendingUrl?: string;
  }>().props;

  const [status, setStatus] = useState(fiscalFilters.status || 'all');
  const [search, setSearch] = useState(fiscalFilters.search || '');
  const [busy, setBusy] = useState<string | null>(null);
  const readyHealth = fiscalHealth.filter((item) => item.ready).length;
  const safeCoverage = Math.max(0, Math.min(100, Number(fiscalSummary.coveragePercent || 0)));

  const load = (page = 1, nextStatus = status) => router.get('/boshqaruv/fiscalization', {
    fiscal_status: nextStatus,
    fiscal_search: search,
    fiscal_page: page,
  }, { preserveState: true, preserveScroll: true, replace: true });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    load(1);
  };

  const changeStatus = (nextStatus: string) => {
    setStatus(nextStatus);
    load(1, nextStatus);
  };

  const post = (url: string, key: string, data: Record<string, number> = {}) => {
    if (!url || busy) return;
    setBusy(key);
    router.post(url, data, { preserveScroll: true, onFinish: () => setBusy(null) });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <span className="badge text-light-primary mb-2"><i className="ti ti-shield-check me-1"></i>Paylov OFD nazorati</span>
          <h4 className="main-title mb-0">Fiskalizatsiya</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Cheklar, katalog kodlari va integratsiya xatolari yagona nazorat markazida.</p>
        </div>
        <button type="button" className="btn btn-primary" disabled={Boolean(busy) || !retryPendingUrl} onClick={() => post(retryPendingUrl, 'batch', { limit: 100 })}>
          {busy === 'batch' ? <span className="spinner-border spinner-border-sm me-2"></span> : <i className="ti ti-rotate-clockwise me-2"></i>}
          Kutilayotganlarni yuborish <small className="ms-1 opacity-75">(eng ko‘pi 100 ta)</small>
        </button>
      </div>

      <div className="row">
        <div className="col-xl-4">
          <div className="card orders-provided-card h-100">
            <div className="card-body">
              <i className="ph-bold ph-circle circle-bg-img"></i>
              <p className="f-s-18 f-w-600 text-dark mb-1">Umumiy holat</p>
              <h2 className="text-secondary-dark mb-2">{safeCoverage}% <small className="f-s-14 f-w-500">qamrov</small></h2>
              <div className="custom-progress-container mb-2">
                <div className="progress-bar productive" style={{ width: `${safeCoverage}%` }}></div>
                <div className="progress-bar idle" style={{ width: `${100 - safeCoverage}%` }}></div>
              </div>
              <p className={`f-w-600 mb-0 ${fiscalSummary.failed > 0 ? 'text-danger' : 'text-success'}`}>{fiscalSummary.failed > 0 ? `${fmt(fiscalSummary.failed)} ta chek e’tibor talab qiladi` : 'Fiskal oqim nazoratda'}</p>
              <p className="text-secondary mb-0">{fmt(fiscalSummary.registered)} ta chek muvaffaqiyatli ro‘yxatdan o‘tgan</p>
            </div>
          </div>
        </div>
        <div className="col-xl-8">
          <div className="row">
            <div className="col-sm-6 col-xxl-3"><StatWidget index={1} label="To‘langan" value={fmt(fiscalSummary.paid)} /></div>
            <div className="col-sm-6 col-xxl-3"><StatWidget variant="success" label="Chek tayyor" value={fmt(fiscalSummary.registered)} /></div>
            <div className="col-sm-6 col-xxl-3"><StatWidget variant="warning" label="Kutilmoqda" value={fmt(fiscalSummary.pending)} /></div>
            <div className="col-sm-6 col-xxl-3"><StatWidget variant="danger" label="Xatolik" value={fmt(fiscalSummary.failed)} /></div>
          </div>
        </div>
      </div>

      <div className="row">
        <div className="col-xl-6">
          <div className="card h-100">
            <div className="card-header d-flex align-items-center justify-content-between gap-2">
              <div>
                <p className="text-secondary f-s-13 mb-0">Tizim</p>
                <h5 className="mb-0">Integratsiya tayyorligi</h5>
              </div>
              <span className={`badge ${readyHealth === fiscalHealth.length ? 'text-light-success' : 'text-light-warning'}`}>{readyHealth}/{fiscalHealth.length}</span>
            </div>
            <div className="card-body">
              <ul className="list-group list-group-flush">
                {fiscalHealth.map((item) => (
                  <li className="list-group-item d-flex align-items-start gap-3 px-0" key={item.key}>
                    <span className={`h-35 w-35 d-flex-center b-r-50 flex-shrink-0 ${item.ready ? 'text-light-success' : 'text-light-danger'}`}><i className={`ti ${item.ready ? 'ti-check' : 'ti-exclamation-mark'}`}></i></span>
                    <div className="flex-grow-1 min-w-0">
                      <div className="d-flex justify-content-between gap-2">
                        <h6 className="mb-0">{item.label}</h6>
                        <span className={`f-w-600 text-nowrap ${item.ready ? 'text-success' : 'text-danger'}`}>{item.value}</span>
                      </div>
                      <p className="mb-0 f-s-13 text-secondary">{item.hint}</p>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card h-100">
            <div className="card-header d-flex align-items-center justify-content-between gap-2">
              <div>
                <p className="text-secondary f-s-13 mb-0">Katalog</p>
                <h5 className="mb-0">OFD kodlari qamrovi</h5>
              </div>
              <span className="badge text-light-secondary">2 tur</span>
            </div>
            <div className="card-body">
              <div className="list-group list-group-flush mb-3">
                <CoverageRow label="Kitoblar" icon="ti-book" coverage={fiscalCatalogCoverage.books} href="/boshqaruv/books" />
                <CoverageRow label="Kanselyariya" icon="ti-edit" coverage={fiscalCatalogCoverage.stationery} href="/boshqaruv/stationeries" />
              </div>
              <div className="row g-2">
                <ConfigItem label="Oddiy chek" value="Avtomatik" state="ready" />
                <ConfigItem label="Nasiya cheki" value={`Kredit · Type ${fiscalConfig.splitReceiptType}`} />
                <ConfigItem label="Shartnoma ID" value="Har bir shartnomaga alohida" state="ready" />
                <ConfigItem label="Hisob birligi" value={`×${fiscalConfig.amountMultiplier}`} />
                <ConfigItem label="QQS" value={`${fiscalConfig.vatPercent}%`} />
                <ConfigItem label="Refund chek" value={fmt(fiscalSummary.refunded)} />
              </div>
              <p className="text-secondary f-s-13 mt-3 mb-0">Kod mahsulotdan, keyin kategoriyadan, undan keyin global fallback’dan olinadi.</p>
            </div>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-end justify-content-between flex-wrap gap-3">
          <div>
            <p className="text-secondary f-s-13 mb-0">Operatsiyalar</p>
            <h5 className="mb-0">OFD tranzaksiyalari</h5>
            <p className="mb-0">{fiscalPagination.from}-{fiscalPagination.to} / {fiscalPagination.total}</p>
          </div>
          <form className="app-form d-flex align-items-end flex-wrap gap-2" onSubmit={submit}>
            <div>
              <label className="form-label f-s-13 mb-1">Holat</label>
              <select className="form-select form-select-sm" value={status} onChange={(event) => changeStatus(event.target.value)}>
                <option value="all">Barchasi</option>
                <option value="registered">Chek tayyor</option>
                <option value="pending">Kutilmoqda</option>
                <option value="failed">Xatolik</option>
                <option value="refunded">Refund qilingan</option>
              </select>
            </div>
            <div>
              <label className="form-label f-s-13 mb-1">Qidiruv</label>
              <div className="d-flex gap-2">
                <div className="app-icon-form position-relative">
                  <input className="form-control form-control-sm" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Order, transaction yoki mijoz" />
                  <i className="ti ti-search text-dark"></i>
                </div>
                <button type="submit" className="btn btn-primary icon-btn w-35 h-35 b-r-22" aria-label="Qidirish"><i className="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </form>
        </div>

        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead><tr><th>Order</th><th>Mijoz</th><th className="text-end">Summa</th><th>Holat</th><th>Urinish</th><th>Paylov / xato</th><th>Sana</th><th>Amallar</th></tr></thead>
              <tbody>
                {fiscalTransactions.map((row) => (
                  <tr key={row.id}>
                    <td className="text-nowrap"><a className="f-w-600 text-primary" href={row.orderUrl}>#{row.orderId}</a><span className="d-block f-s-12 text-secondary">TX #{row.id}</span></td>
                    <td><span className="title-text">{row.user}</span><span className="d-block f-s-12 text-secondary">{row.phone || '—'}</span></td>
                    <td className="text-end text-nowrap"><span className="f-w-600">{fmt(row.amount)}</span><span className="d-block f-s-12 text-secondary">so‘m</span></td>
                    <td><FiscalStatus row={row} /></td>
                    <td><span className="f-w-600">{row.attempts}</span><span className="d-block f-s-12 text-secondary">{row.lastAttemptAt || 'Hali yuborilmagan'}</span></td>
                    <td style={{ maxWidth: 320 }}><FiscalMessage row={row} /></td>
                    <td className="text-nowrap text-secondary">{row.createdAt || '—'}</td>
                    <td className="text-nowrap"><FiscalActions row={row} busy={busy} post={post} /></td>
                  </tr>
                ))}
                {fiscalTransactions.length === 0 ? (
                  <tr><td colSpan={8} className="text-center py-5 text-secondary">
                    <i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>
                    <h6 className="mb-0">Tranzaksiya topilmadi</h6>
                    <p className="mb-0 f-s-13">Filter yoki qidiruv qiymatini o‘zgartirib ko‘ring.</p>
                  </td></tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <PaginationControls {...fiscalPagination} onPageChange={(page) => load(page)} />
        </div>
      </div>
    </div>
  );
}

function ConfigItem({ label, value, state = 'neutral' }: { label: string; value: string; state?: 'neutral' | 'ready' | 'warning' }) {
  return (
    <div className="col-sm-6 col-xxl-4">
      <div className={`b-r-15 p-2 px-3 h-100 ${state === 'ready' ? 'bg-light-success' : state === 'warning' ? 'bg-light-warning' : 'b-1-light'}`}>
        <span className="d-block f-s-12">{label}</span>
        <span className="d-block f-w-600">{value}</span>
      </div>
    </div>
  );
}

function CoverageRow({ label, icon, coverage, href }: { label: string; icon: string; coverage?: Coverage; href: string }) {
  const data = coverage || { total: 0, ready: 0, missing: 0, direct: 0, categoryFallback: 0, globalFallback: 0, percent: 100 };
  const percent = Math.max(0, Math.min(100, Number(data.percent || 0)));
  return (
    <a className="list-group-item list-group-item-action d-flex align-items-center gap-3 px-0" href={href}>
      <span className="h-35 w-35 d-flex-center b-r-50 text-light-primary flex-shrink-0"><i className={`${tiIcon(icon)}`}></i></span>
      <div className="flex-grow-1 min-w-0">
        <div className="d-flex justify-content-between gap-2">
          <h6 className="mb-0">{label}</h6>
          <span className={`f-w-600 ${data.missing ? 'text-warning-dark' : 'text-success'}`}>{data.ready}/{data.total}</span>
        </div>
        <div className="progress my-1" role="progressbar" aria-valuenow={percent} aria-valuemin={0} aria-valuemax={100}>
          <div className={`progress-bar ${data.missing ? 'bg-warning' : 'bg-success'}`} style={{ width: `${percent}%` }}></div>
        </div>
        <p className="mb-0 f-s-12 text-secondary">Product {data.direct} · Kategoriya {data.categoryFallback} · Global {data.globalFallback}{data.missing ? ` · Yetishmaydi ${data.missing}` : ''}</p>
      </div>
      <i className="ti ti-chevron-right text-secondary"></i>
    </a>
  );
}

function FiscalStatus({ row }: { row: FiscalRow }) {
  return (
    <div>
      <span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(row.status)))}`}>{statusLabel(row.status)}</span>
      {row.receiptId ? <span className="d-block f-s-12 text-secondary mt-1">Receipt #{row.receiptId}</span> : null}
    </div>
  );
}

function FiscalMessage({ row }: { row: FiscalRow }) {
  if (row.error) {
    return (
      <div>
        <span className="d-block text-danger f-w-500 text-break">{row.error}</span>
        <span className="d-block f-s-12 text-secondary text-break">{[row.errorCode, row.errorField ? `field: ${row.errorField}` : null, row.errorData].filter(Boolean).join(' · ')}</span>
      </div>
    );
  }
  return <span className="font-monospace f-s-12 text-secondary text-break">{row.transactionId || 'Transaction ID yo‘q'}</span>;
}

function FiscalActions({ row, busy, post }: { row: FiscalRow; busy: string | null; post: (url: string, key: string) => void }) {
  return (
    <div className="d-inline-flex gap-2">
      {row.receiptUrl ? <a className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" href={row.receiptUrl} target="_blank" rel="noreferrer" title="Fiskal chek"><i className="ti ti-receipt"></i></a> : null}
      {row.refundReceiptUrl ? <a className="btn btn-light-info icon-btn w-30 h-30 b-r-22" href={row.refundReceiptUrl} target="_blank" rel="noreferrer" title="Refund chek"><i className="ti ti-rotate"></i></a> : null}
      <button type="button" className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" disabled={Boolean(busy)} onClick={() => post(row.syncUrl, `sync-${row.id}`)} title="Paylovdan tekshirish">
        {busy === `sync-${row.id}` ? <span className="spinner-border spinner-border-sm"></span> : <i className="ti ti-cloud-download"></i>}
      </button>
      {row.status !== 'registered' && row.status !== 'refunded' ? (
        <button type="button" className="btn btn-light-success icon-btn w-30 h-30 b-r-22" disabled={Boolean(busy)} onClick={() => post(row.registerUrl, `register-${row.id}`)} title="Qayta fiskalizatsiya">
          {busy === `register-${row.id}` ? <span className="spinner-border spinner-border-sm"></span> : <i className="ti ti-send"></i>}
        </button>
      ) : null}
    </div>
  );
}

function statusChip(status: FiscalRow['status']) {
  if (status === 'registered') return 'text-light-success';
  if (status === 'refunded') return 'text-light-info';
  if (status === 'failed') return 'text-light-danger';
  return 'text-light-warning';
}

function statusLabel(status: FiscalRow['status']) {
  if (status === 'registered') return 'Chek tayyor';
  if (status === 'refunded') return 'Refund';
  if (status === 'failed') return 'Xatolik';
  return 'Kutilmoqda';
}
