import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);
const reportUrl = (url?: string, lang: 'uz' | 'ru' = 'uz') => {
  if (!url) return '#';
  return `${url}${url.includes('?') ? '&' : '?'}lang=${lang}`;
};

interface Transaction {
  id: number;
  user: string;
  phone?: string;
  type: string;
  owner?: 'seller' | 'courier';
  amount: number;
  netAmount?: number;
  sellerId?: number;
  courierId?: number;
  orderId?: number;
  sellerOrderId?: number;
  courierOrderId?: number;
  courierTaskId?: number;
  status: string;
  statusLabel?: string;
  date?: string;
  updatedAt?: string;
  method?: string;
  note?: string;
  category?: string;
  ownerTotals?: { approvedCount?: number; approvedSum?: number; pendingSum?: number };
  sellerTotals?: { approvedCount?: number; approvedSum?: number; pendingSum?: number };
  breakdown?: {
    orders?: number;
    products?: number;
    gross?: number;
    commission?: number;
    net?: number;
    basePayout?: number;
    bonus?: number;
    periodFrom?: string;
    periodTo?: string;
    rows?: Array<{
      transaction_id?: number;
      order_id?: number;
      sub_order_id?: number;
      gross?: number;
      commission?: number;
      net?: number;
      base_payout?: number;
      bonus?: number;
      product_count?: number;
      date?: string;
    }>;
  };
  nearby?: Array<{ id: number; amount?: number; netAmount?: number; status?: string; date?: string }>;
  approveUrl?: string;
  rejectUrl?: string;
  reportUrl?: string;
}

const statusChip = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (['completed', 'approved', 'success', 'paid'].includes(value)) return 'chip-success';
  if (['pending', 'new', 'waiting'].includes(value)) return 'chip-warning';
  if (['rejected', 'failed', 'cancelled'].includes(value)) return 'chip-danger';
  return 'chip-gray';
};

export default function Transaksiyalar() {
  const { transactions = [], transactionPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, transactionTotals = {}, transactionFilters = {} } = usePage<{ transactions?: Transaction[]; transactionPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; transactionTotals?: Record<string, number>; transactionFilters?: { owner?: string; search?: string } }>().props;
  const [selected, setSelected] = useState<Transaction | null>(null);
  const [owner, setOwner] = useState(transactionFilters.owner || 'seller');
  const [search, setSearch] = useState(transactionFilters.search || '');

  const totals = { income: transactionTotals.income || 0, pending: transactionTotals.pending || 0, approved: transactionTotals.approved || 0 };
  const ownerLabel = owner === 'courier' ? 'Kuryer' : 'Sotuvchi';
  const go = (extra: Record<string, string | number> = {}) => router.get('/boshqaruv/transactions', { transaction_owner: owner, transactions_search: search, transactions_page: transactionPagination.page, ...extra }, { preserveState: true, preserveScroll: true, replace: true });

  const patch = (url?: string, message?: string) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Tranzaksiyalar</h1>
          <p className="page-subtitle">Sotuvchi va kuryer to'lovlari, jarimalar, buyurtma daromadlari va yechib olish so'rovlari</p>
        </div>
      </div>

      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div className="btn-group">
          {[
            ['seller', 'Sotuvchilar'],
            ['courier', 'Kuryerlar'],
          ].map(([key, label]) => <button key={key} className={`btn btn-sm ${owner === key ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { setOwner(key); router.get('/boshqaruv/transactions', { transaction_owner: key, transactions_page: 1 }, { preserveState: true, preserveScroll: true, replace: true }); }}>{label}</button>)}
        </div>
        <input className="form-control form-control-sm" style={{ maxWidth: 360 }} value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && go({ transactions_page: 1 })} placeholder={`${ownerLabel}, telefon, order ID yoki summa`} />
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: `${ownerLabel} tranzaksiya`, value: transactionTotals.all || 0, icon: 'bi-receipt', color: '#4f46e5' },
          { label: 'Tasdiqlangan', value: totals.approved, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Kutilmoqda', value: totals.pending, icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Jami summa', value: `${fmt(totals.income)} so'm`, icon: 'bi-wallet2', color: '#0ea5e9' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div>
                  <div className="stat-value">{item.value}</div>
                  <div className="stat-label">{item.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">To'lovlar ro'yxati</div>
            <small className="text-muted">Jami summa: {fmt(totals.income)} so'm</small>
          </div>
          <span className="chip chip-warning">{totals.pending} ta kutilmoqda</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>{ownerLabel}</th><th>Turi</th><th>Summa</th><th>Yakuniy</th><th>Usul</th><th>Buyurtma</th><th>Sana</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {transactions.map((item) => (
                <tr key={item.id}>
                  <td className="fw-semibold text-primary">#{item.id}</td>
                  <td><div className="fw-semibold">{item.user}</div><small className="text-muted">{item.phone}</small></td>
                  <td><span className="chip chip-gray">{item.type}</span></td>
                  <td className={`fw-bold ${item.amount >= 0 ? 'text-success' : 'text-danger'}`}>{item.amount >= 0 ? '+' : ''}{fmt(item.amount)} so'm</td>
                  <td className="fw-semibold">{fmt(item.netAmount ?? item.amount)} so'm</td>
                  <td><span className="chip chip-gray">{item.method || '—'}</span></td>
                  <td><small className="text-muted">{owner === 'courier' ? `Buyurtma #${item.orderId || '—'} · Kuryer #${item.courierOrderId || '—'}` : `Buyurtma #${item.orderId || '—'} · Sotuvchi #${item.sellerOrderId || '—'}`}</small></td>
                  <td className="text-muted">{item.date || '—'}</td>
                  <td><span className={`chip ${statusChip(item.status)}`}>{item.status || '—'}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(item)}><i className="bi bi-eye"></i></button>
                    {statusChip(item.status) === 'chip-warning' && item.approveUrl ? (
                      <button className="btn btn-sm btn-success me-1" onClick={() => patch(item.approveUrl, 'Tranzaksiya tasdiqlansinmi?')}><i className="bi bi-check-lg"></i></button>
                    ) : null}
                    {statusChip(item.status) === 'chip-warning' && item.rejectUrl ? (
                      <button className="btn btn-sm btn-danger" onClick={() => patch(item.rejectUrl, 'Tranzaksiya rad etilsinmi?')}><i className="bi bi-x-lg"></i></button>
                    ) : null}
                  </td>
                </tr>
              ))}
              {transactions.length === 0 ? <tr><td colSpan={10} className="text-center text-muted py-5">Tranzaksiya topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...transactionPagination} onPageChange={(page) => go({ transactions_page: page })} />
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Tranzaksiya #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">{selected?.owner === 'courier' ? 'Kuryer' : 'Sotuvchi'}</small><div className="fw-semibold">{selected?.user}</div></div>
            <div className="col-6"><small className="text-muted">Telefon</small><div>{selected?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type} {selected?.category ? `· ${selected.category}` : ''}</div></div>
            <div className="col-6"><small className="text-muted">Holat</small><div><span className={`chip ${statusChip(selected?.status)}`}>{selected?.status || '—'}</span></div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div className="fw-bold">{fmt(selected?.amount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Yakuniy summa</small><div className="fw-bold text-success">{fmt(selected?.netAmount ?? selected?.amount ?? 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Karta</small><div>{selected?.method || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Buyurtma</small><div>{selected?.owner === 'courier' ? `#${selected?.orderId || '—'} · Kuryer #${selected?.courierOrderId || '—'} · Vazifa #${selected?.courierTaskId || '—'}` : `#${selected?.orderId || '—'} · Sotuvchi #${selected?.sellerOrderId || '—'}`}</div></div>
            <div className="col-6"><small className="text-muted">Yangilangan</small><div>{selected?.updatedAt || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.note || '—'}</div></div>
            <div className="col-12">
              <div className="detail-panel">
                <div className="d-flex justify-content-between align-items-start gap-2 mb-3">
                  <div>
                    <h6 className="fw-bold mb-1">Hisob-kitob tarkibi</h6>
                    <small className="text-muted">{selected?.breakdown?.periodFrom || 'Boshlanishidan'} — {selected?.breakdown?.periodTo || selected?.date || '—'}</small>
                  </div>
                  {selected?.reportUrl ? (
                    <div className="d-flex flex-wrap align-items-center gap-2">
                      <span className="text-muted small">PDF tili:</span>
                      <a className="btn btn-sm btn-dark" href={reportUrl(selected.reportUrl, 'uz')} target="_blank" rel="noreferrer"><i className="bi bi-filetype-pdf me-1"></i> O'zbekcha</a>
                      <a className="btn btn-sm btn-outline-dark" href={reportUrl(selected.reportUrl, 'ru')} target="_blank" rel="noreferrer"><i className="bi bi-filetype-pdf me-1"></i> Русский</a>
                    </div>
                  ) : null}
                </div>
                <div className="row g-2 mb-3">
                  <div className="col-md-3 col-6"><small className="text-muted d-block">Buyurtmalar</small><strong>{selected?.breakdown?.orders || 0} ta</strong></div>
                  <div className="col-md-3 col-6"><small className="text-muted d-block">Mahsulotlar</small><strong>{selected?.breakdown?.products || 0} ta</strong></div>
                  <div className="col-md-3 col-6"><small className="text-muted d-block">{selected?.owner === 'courier' ? 'Bonus' : 'Komissiya'}</small><strong>{fmt(selected?.owner === 'courier' ? (selected?.breakdown?.bonus || 0) : (selected?.breakdown?.commission || 0))} so'm</strong></div>
                  <div className="col-md-3 col-6"><small className="text-muted d-block">Yakuniy</small><strong>{fmt(selected?.breakdown?.net || selected?.netAmount || selected?.amount || 0)} so'm</strong></div>
                </div>
                <div className="table-responsive">
                  <table className="data-table small">
                    <thead><tr><th>Buyurtma</th><th>Ichki buyurtma</th><th>Mahsulot</th><th>Umumiy/Asosiy</th><th>Komissiya/Bonus</th><th>Yakuniy</th></tr></thead>
                    <tbody>
                      {(selected?.breakdown?.rows || []).slice(0, 8).map((row, index) => (
                        <tr key={`${row.transaction_id || index}-${row.order_id || 'x'}`}>
                          <td>#{row.order_id || '—'}<br /><small className="text-muted">{row.date || '—'}</small></td>
                          <td>#{row.sub_order_id || '—'}<br /><small className="text-muted">Tranzaksiya #{row.transaction_id || '—'}</small></td>
                          <td>{row.product_count || 0} ta</td>
                          <td>{fmt(selected?.owner === 'courier' ? (row.base_payout || row.gross || 0) : (row.gross || 0))} so'm</td>
                          <td>{fmt(selected?.owner === 'courier' ? (row.bonus || 0) : (row.commission || 0))} so'm</td>
                          <td className="fw-bold">{fmt(row.net || 0)} so'm</td>
                        </tr>
                      ))}
                      {(selected?.breakdown?.rows || []).length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-3">Hisob-kitob qatorlari topilmadi</td></tr> : null}
                    </tbody>
                  </table>
                </div>
                {(selected?.breakdown?.rows || []).length > 8 ? <small className="text-muted d-block mt-2">PDF hisobotda barcha qatorlar to'liq chiqadi.</small> : null}
              </div>
            </div>
            <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">{selected?.owner === 'courier' ? 'Kuryer bo‘yicha qisqa ma’lumot' : 'Sotuvchi bo‘yicha qisqa ma’lumot'}</h6><div className="row g-2"><div className="col-md-4"><small className="text-muted d-block">Tasdiqlangan</small><strong>{selected?.ownerTotals?.approvedCount || selected?.sellerTotals?.approvedCount || 0} ta</strong></div><div className="col-md-4"><small className="text-muted d-block">Tasdiqlangan summa</small><strong>{fmt(selected?.ownerTotals?.approvedSum || selected?.sellerTotals?.approvedSum || 0)} so'm</strong></div><div className="col-md-4"><small className="text-muted d-block">Kutilayotgan summa</small><strong>{fmt(selected?.ownerTotals?.pendingSum || selected?.sellerTotals?.pendingSum || 0)} so'm</strong></div></div></div></div>
            <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">Yaqin tranzaksiyalar</h6>{(selected?.nearby || []).map((row) => <div className="d-flex justify-content-between border-bottom py-2" key={row.id}><span>Tranzaksiya #{row.id} · {row.status || '—'}</span><strong>{fmt(row.netAmount || row.amount || 0)} so'm</strong></div>)}{(selected?.nearby || []).length === 0 ? <div className="text-muted">Boshqa tranzaksiya topilmadi</div> : null}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.reportUrl ? (
            <div className="me-auto d-flex flex-wrap align-items-center gap-2">
              <span className="text-muted small">PDF hisobot:</span>
              <a className="btn btn-outline-dark" href={reportUrl(selected.reportUrl, 'uz')} target="_blank" rel="noreferrer"><i className="bi bi-filetype-pdf me-1"></i> O'zbekcha</a>
              <a className="btn btn-outline-dark" href={reportUrl(selected.reportUrl, 'ru')} target="_blank" rel="noreferrer"><i className="bi bi-filetype-pdf me-1"></i> Русский</a>
            </div>
          ) : null}
          {statusChip(selected?.status) === 'chip-warning' && selected?.approveUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.approveUrl, 'Tranzaksiya tasdiqlansinmi?')}>Tasdiqlash</Button> : null}
          {statusChip(selected?.status) === 'chip-warning' && selected?.rejectUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.rejectUrl, 'Tranzaksiya rad etilsinmi?')}>Rad etish</Button> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
