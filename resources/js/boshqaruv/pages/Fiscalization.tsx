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
type FiscalConfig = { standardReceiptType: number; splitReceiptType: number; splitAdvanceConfigured: boolean; amountMultiplier: number; vatPercent: number };
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

const defaultSummary: Summary = { paid: 0, registered: 0, pending: 0, failed: 0, refunded: 0, coveragePercent: 100 };
const defaultConfig: FiscalConfig = { standardReceiptType: 0, splitReceiptType: 1, splitAdvanceConfigured: false, amountMultiplier: 100, vatPercent: 0 };

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
    flash = {},
  } = usePage<{
    fiscalSummary?: Summary;
    fiscalHealth?: Health[];
    fiscalConfig?: FiscalConfig;
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
    <div className="fiscal-page">
      <header className="fiscal-header">
        <div className="fiscal-header__copy">
          <span className="fiscal-eyebrow"><i className="bi bi-shield-check"></i> Paylov OFD nazorati</span>
          <h1 className="page-title">Fiskalizatsiya</h1>
          <p className="page-subtitle">Cheklar, katalog kodlari va integratsiya xatolari yagona nazorat markazida.</p>
        </div>
        <button className="fiscal-retry-btn" disabled={Boolean(busy) || !retryPendingUrl} onClick={() => post(retryPendingUrl, 'batch', { limit: 100 })}>
          <i className={`bi ${busy === 'batch' ? 'bi-arrow-repeat fiscal-spin' : 'bi-arrow-clockwise'}`}></i>
          <span>Kutilayotganlarni yuborish</span>
          <small>eng ko‘pi 100 ta</small>
        </button>
      </header>

      {flash.success ? <div className="alert alert-success fiscal-alert">{flash.success}</div> : null}
      {flash.error ? <div className="alert alert-danger fiscal-alert">{flash.error}</div> : null}

      <section className="fiscal-overview" aria-label="Fiskalizatsiya umumiy holati">
        <div className="fiscal-overview__lead">
          <div className="fiscal-overview__ring" style={{ '--fiscal-progress': `${safeCoverage * 3.6}deg` } as React.CSSProperties}>
            <div><strong>{safeCoverage}%</strong><span>qamrov</span></div>
          </div>
          <div className="fiscal-overview__copy">
            <span>Umumiy holat</span>
            <strong>{fiscalSummary.failed > 0 ? `${fmt(fiscalSummary.failed)} ta chek e’tibor talab qiladi` : 'Fiskal oqim nazoratda'}</strong>
            <small>{fmt(fiscalSummary.registered)} ta chek muvaffaqiyatli ro‘yxatdan o‘tgan</small>
          </div>
        </div>
        <div className="fiscal-metrics">
          <Metric icon="bi-credit-card" label="To‘langan" value={fiscalSummary.paid} tone="ink" />
          <Metric icon="bi-patch-check" label="Chek tayyor" value={fiscalSummary.registered} tone="green" />
          <Metric icon="bi-hourglass-split" label="Kutilmoqda" value={fiscalSummary.pending} tone="amber" />
          <Metric icon="bi-exclamation-octagon" label="Xatolik" value={fiscalSummary.failed} tone="red" />
        </div>
      </section>

      <div className="fiscal-control-grid">
        <section className="fiscal-section">
          <div className="fiscal-section__head">
            <div>
              <span className="fiscal-section__eyebrow">Tizim</span>
              <h2>Integratsiya tayyorligi</h2>
            </div>
            <span className={`fiscal-count ${readyHealth === fiscalHealth.length ? 'is-ready' : 'is-warning'}`}>{readyHealth}/{fiscalHealth.length}</span>
          </div>
          <div className="fiscal-health-list">
            {fiscalHealth.map((item) => (
              <div className="fiscal-health-row" key={item.key}>
                <span className={`fiscal-health-row__icon ${item.ready ? 'is-ready' : 'is-error'}`}><i className={`bi ${item.ready ? 'bi-check-lg' : 'bi-exclamation-lg'}`}></i></span>
                <div className="fiscal-health-row__content">
                  <div><strong>{item.label}</strong><span className={item.ready ? 'is-ready' : 'is-error'}>{item.value}</span></div>
                  <small>{item.hint}</small>
                </div>
              </div>
            ))}
          </div>
        </section>

        <section className="fiscal-section">
          <div className="fiscal-section__head">
            <div>
              <span className="fiscal-section__eyebrow">Katalog</span>
              <h2>OFD kodlari qamrovi</h2>
            </div>
            <span className="fiscal-count is-neutral">2 tur</span>
          </div>
          <div className="fiscal-coverage-list">
            <CoverageRow label="Kitoblar" icon="bi-book" coverage={fiscalCatalogCoverage.books} href="/boshqaruv/books" />
            <CoverageRow label="Kanselyariya" icon="bi-pencil-square" coverage={fiscalCatalogCoverage.stationery} href="/boshqaruv/stationeries" />
          </div>
          <div className="fiscal-config-grid">
            <ConfigItem label="Oddiy chek" value={`Type ${fiscalConfig.standardReceiptType}`} state="ready" />
            <ConfigItem label="Split avans" value={`Type ${fiscalConfig.splitReceiptType}`} />
            <ConfigItem label="Split ID" value={fiscalConfig.splitAdvanceConfigured ? 'Tayyor' : 'Kiritilmagan'} state={fiscalConfig.splitAdvanceConfigured ? 'ready' : 'warning'} />
            <ConfigItem label="Hisob birligi" value={`×${fiscalConfig.amountMultiplier}`} />
            <ConfigItem label="QQS" value={`${fiscalConfig.vatPercent}%`} />
            <ConfigItem label="Refund chek" value={fmt(fiscalSummary.refunded)} />
          </div>
          <p className="fiscal-section__note">Kod mahsulotdan, keyin kategoriyadan, undan keyin global fallback’dan olinadi.</p>
        </section>
      </div>

      <section className="fiscal-section fiscal-transactions">
        <div className="fiscal-transactions__head">
          <div>
            <span className="fiscal-section__eyebrow">Operatsiyalar</span>
            <h2>OFD tranzaksiyalari</h2>
            <small>{fiscalPagination.from}-{fiscalPagination.to} / {fiscalPagination.total}</small>
          </div>
          <form className="fiscal-filters" onSubmit={submit}>
            <label>
              <span>Holat</span>
              <select className="form-select form-select-sm" value={status} onChange={(event) => changeStatus(event.target.value)}>
                <option value="all">Barchasi</option>
                <option value="registered">Chek tayyor</option>
                <option value="pending">Kutilmoqda</option>
                <option value="failed">Xatolik</option>
                <option value="refunded">Refund qilingan</option>
              </select>
            </label>
            <label className="fiscal-search">
              <span>Qidiruv</span>
              <div><i className="bi bi-search"></i><input className="form-control form-control-sm" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Order, transaction yoki mijoz" /><button type="submit" aria-label="Qidirish"><i className="bi bi-arrow-right"></i></button></div>
            </label>
          </form>
        </div>

        <div className="fiscal-table-wrap">
          <table className="data-table fiscal-table">
            <thead><tr><th>Order</th><th>Mijoz</th><th>Summa</th><th>Holat</th><th>Urinish</th><th>Paylov / xato</th><th>Sana</th><th>Amallar</th></tr></thead>
            <tbody>
              {fiscalTransactions.map((row) => (
                <tr key={row.id}>
                  <td><a className="fiscal-order-link" href={row.orderUrl}>#{row.orderId}</a><small>TX #{row.id}</small></td>
                  <td><strong>{row.user}</strong><small>{row.phone || '—'}</small></td>
                  <td className="fiscal-nowrap"><strong>{fmt(row.amount)}</strong><small>so‘m</small></td>
                  <td><FiscalStatus row={row} /></td>
                  <td><strong>{row.attempts}</strong><small>{row.lastAttemptAt || 'Hali yuborilmagan'}</small></td>
                  <td><FiscalMessage row={row} /></td>
                  <td className="fiscal-date">{row.createdAt || '—'}</td>
                  <td><FiscalActions row={row} busy={busy} post={post} /></td>
                </tr>
              ))}
              {fiscalTransactions.length === 0 ? <tr><td colSpan={8}><EmptyTransactions /></td></tr> : null}
            </tbody>
          </table>
        </div>

        <div className="fiscal-mobile-list">
          {fiscalTransactions.map((row) => (
            <article className="fiscal-mobile-card" key={row.id}>
              <div className="fiscal-mobile-card__head"><div><a href={row.orderUrl}>Buyurtma #{row.orderId}</a><small>TX #{row.id}</small></div><FiscalStatus row={row} /></div>
              <div className="fiscal-mobile-card__body">
                <div><span>Mijoz</span><strong>{row.user}</strong><small>{row.phone || '—'}</small></div>
                <div><span>Summa</span><strong>{fmt(row.amount)} so‘m</strong><small>{row.createdAt || '—'}</small></div>
              </div>
              <FiscalMessage row={row} />
              <div className="fiscal-mobile-card__footer"><small>{row.attempts} urinish · {row.lastAttemptAt || 'hali yuborilmagan'}</small><FiscalActions row={row} busy={busy} post={post} /></div>
            </article>
          ))}
          {fiscalTransactions.length === 0 ? <EmptyTransactions /> : null}
        </div>

        <PaginationControls {...fiscalPagination} onPageChange={(page) => load(page)} />
      </section>
    </div>
  );
}

function Metric({ icon, label, value, tone }: { icon: string; label: string; value: number; tone: string }) {
  return <div className={`fiscal-metric fiscal-metric--${tone}`}><span><i className={`bi ${icon}`}></i></span><div><strong>{fmt(value)}</strong><small>{label}</small></div></div>;
}

function ConfigItem({ label, value, state = 'neutral' }: { label: string; value: string; state?: 'neutral' | 'ready' | 'warning' }) {
  return <div className={`fiscal-config fiscal-config--${state}`}><span>{label}</span><strong>{value}</strong></div>;
}

function CoverageRow({ label, icon, coverage, href }: { label: string; icon: string; coverage?: Coverage; href: string }) {
  const data = coverage || { total: 0, ready: 0, missing: 0, direct: 0, categoryFallback: 0, globalFallback: 0, percent: 100 };
  const percent = Math.max(0, Math.min(100, Number(data.percent || 0)));
  return <a className="fiscal-coverage" href={href}>
    <span className="fiscal-coverage__icon"><i className={`bi ${icon}`}></i></span>
    <div className="fiscal-coverage__body">
      <div><strong>{label}</strong><span className={data.missing ? 'is-warning' : 'is-ready'}>{data.ready}/{data.total}</span></div>
      <div className="fiscal-progress"><span style={{ width: `${percent}%` }}></span></div>
      <small>Product {data.direct} · Kategoriya {data.categoryFallback} · Global {data.globalFallback}{data.missing ? ` · Yetishmaydi ${data.missing}` : ''}</small>
    </div>
    <i className="bi bi-chevron-right"></i>
  </a>;
}

function FiscalStatus({ row }: { row: FiscalRow }) {
  return <div className="fiscal-status-wrap"><span className={`chip ${statusChip(row.status)}`}>{statusLabel(row.status)}</span>{row.receiptId ? <small>Receipt #{row.receiptId}</small> : null}</div>;
}

function FiscalMessage({ row }: { row: FiscalRow }) {
  if (row.error) {
    return <div className="fiscal-error"><span>{row.error}</span><small>{[row.errorCode, row.errorField ? `field: ${row.errorField}` : null].filter(Boolean).join(' · ')}</small></div>;
  }
  return <span className="fiscal-transaction-id">{row.transactionId || 'Transaction ID yo‘q'}</span>;
}

function FiscalActions({ row, busy, post }: { row: FiscalRow; busy: string | null; post: (url: string, key: string) => void }) {
  return <div className="fiscal-actions">
    {row.receiptUrl ? <a href={row.receiptUrl} target="_blank" rel="noreferrer" title="Fiskal chek"><i className="bi bi-receipt"></i></a> : null}
    {row.refundReceiptUrl ? <a href={row.refundReceiptUrl} target="_blank" rel="noreferrer" title="Refund chek"><i className="bi bi-arrow-counterclockwise"></i></a> : null}
    <button disabled={Boolean(busy)} onClick={() => post(row.syncUrl, `sync-${row.id}`)} title="Paylovdan tekshirish"><i className={`bi ${busy === `sync-${row.id}` ? 'bi-arrow-repeat fiscal-spin' : 'bi-cloud-download'}`}></i></button>
    {row.status !== 'registered' && row.status !== 'refunded' ? <button className="is-primary" disabled={Boolean(busy)} onClick={() => post(row.registerUrl, `register-${row.id}`)} title="Qayta fiskalizatsiya"><i className={`bi ${busy === `register-${row.id}` ? 'bi-arrow-repeat fiscal-spin' : 'bi-send'}`}></i></button> : null}
  </div>;
}

function EmptyTransactions() {
  return <div className="fiscal-empty"><span><i className="bi bi-receipt"></i></span><strong>Tranzaksiya topilmadi</strong><small>Filter yoki qidiruv qiymatini o‘zgartirib ko‘ring.</small></div>;
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
