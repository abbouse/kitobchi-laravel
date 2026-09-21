import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

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
  recipient?: {
    legalType?: string;
    legalTypeLabel?: string;
    inn?: string;
    legalAddress?: string;
    bankName?: string;
    bankAccount?: string;
    bankMfo?: string;
    bankSwift?: string;
    card?: string;
    cardHolder?: string;
  };
  contract?: {
    number?: string;
    signed?: boolean;
    signedAt?: string;
    expiresAt?: string;
    status?: string;
    rawStatus?: string;
    notes?: string;
    invoiceNumber?: string;
    invoiceDate?: string;
    paymentPurpose?: string;
  };
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
  if (['completed', 'approved', 'success', 'paid'].includes(value)) return 'text-light-success';
  if (['pending', 'new', 'waiting'].includes(value)) return 'text-light-warning';
  if (['rejected', 'failed', 'cancelled'].includes(value)) return 'text-light-danger';
  return 'text-light-secondary';
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
  const copy = (value?: string) => {
    if (!value) return;
    navigator.clipboard?.writeText(value);
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Tranzaksiyalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Sotuvchi va kuryer to'lovlari, jarimalar, buyurtma daromadlari va yechib olish so'rovlari</p>
        </div>
      </div>

      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div className="nav nav-tabs app-tabs-primary flex-wrap">
          {[
            ['seller', 'Sotuvchilar'],
            ['courier', 'Kuryerlar'],
          ].map(([key, label]) => <div key={key} className="nav-item"><button
              className={`nav-link ${owner === key ? 'active' : ''}`}
              onClick={() => { setOwner(key); router.get('/boshqaruv/transactions', { transaction_owner: key, transactions_page: 1 }, { preserveState: true, preserveScroll: true, replace: true }); }}>{label}</button></div>)}
        </div>
        <input className="form-control form-control-sm" style={{ maxWidth: 360 }} value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && go({ transactions_page: 1 })} placeholder={`${ownerLabel}, telefon, order ID yoki summa`} />
      </div>

      <div className="row">
        {[
          { label: `${ownerLabel} tranzaksiya`, value: transactionTotals.all || 0, icon: 'ti-receipt', color: 'rgba(var(--primary), 1)' },
          { label: 'Tasdiqlangan', value: totals.approved, icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { label: 'Kutilmoqda', value: totals.pending, icon: 'ti-hourglass', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Jami summa', value: `${fmt(totals.income)} so'm`, icon: 'ti-wallet', color: 'rgba(var(--info-dark), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.value} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">To'lovlar ro'yxati</h5>
            <p className="mb-0 text-secondary">Jami summa: {fmt(totals.income)} so'm</p>
          </div>
          <span className="badge text-light-warning">{totals.pending} ta kutilmoqda</span>
        </div>
        <div className="card-body">

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>{ownerLabel}</th><th>Turi</th><th>Summa</th><th>Yakuniy</th><th>Usul</th><th>Buyurtma</th><th>Sana</th><th>Holat</th><th>Amallar</th></tr></thead>
              <tbody>
                {transactions.map((item) => (
                  <tr key={item.id}>
                    <td className="f-w-600 text-nowrap">#{item.id}</td>
                    <td><div className="f-w-600">{item.user}</div><small className="text-muted">{item.phone}</small></td>
                    <td><span className="badge text-light-secondary">{item.type}</span></td>
                    <td className={`f-w-600 ${item.amount >= 0 ? 'text-success' : 'text-danger'}`}>{item.amount >= 0 ? '+' : ''}{fmt(item.amount)} so'm</td>
                    <td className="f-w-600">{fmt(item.netAmount ?? item.amount)} so'm</td>
                    <td><span className="badge text-light-secondary">{item.method || '—'}</span></td>
                    <td><small className="text-muted">{owner === 'courier' ? `Buyurtma #${item.orderId || '—'} · Kuryer #${item.courierOrderId || '—'}` : `Buyurtma #${item.orderId || '—'} · Sotuvchi #${item.sellerOrderId || '—'}`}</small></td>
                    <td className="text-muted">{item.date || '—'}</td>
                    <td><span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(item.status)))}`}>{item.status || '—'}</span></td>
                    <td>
                      <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => setSelected(item)}><i className="ti ti-eye"></i></button>
                      {statusChip(item.status) === 'text-light-warning' && item.approveUrl ? (
                        <button className="btn btn-sm btn-success me-1" onClick={() => patch(item.approveUrl, 'Tranzaksiya tasdiqlansinmi?')}><i className="ti ti-check"></i></button>
                      ) : null}
                      {statusChip(item.status) === 'text-light-warning' && item.rejectUrl ? (
                        <button className="btn btn-sm btn-danger" onClick={() => patch(item.rejectUrl, 'Tranzaksiya rad etilsinmi?')}><i className="ti ti-x"></i></button>
                      ) : null}
                    </td>
                  </tr>
                ))}
                {transactions.length === 0 ? <tr><td colSpan={10} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Tranzaksiya topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...transactionPagination} onPageChange={(page) => go({ transactions_page: page })} />
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Tranzaksiya #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row">
            <div className="col-6"><small className="text-muted">{selected?.owner === 'courier' ? 'Kuryer' : 'Sotuvchi'}</small><div className="f-w-600">{selected?.user}</div></div>
            <div className="col-6"><small className="text-muted">Telefon</small><div>{selected?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type} {selected?.category ? `· ${selected.category}` : ''}</div></div>
            <div className="col-6"><small className="text-muted">Holat</small><div><span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(selected?.status)))}`}>{selected?.status || '—'}</span></div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div className="f-w-600">{fmt(selected?.amount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Yakuniy summa</small><div className="f-w-600 text-success">{fmt(selected?.netAmount ?? selected?.amount ?? 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Karta</small><div>{selected?.method || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Buyurtma</small><div>{selected?.owner === 'courier' ? `#${selected?.orderId || '—'} · Kuryer #${selected?.courierOrderId || '—'} · Vazifa #${selected?.courierTaskId || '—'}` : `#${selected?.orderId || '—'} · Sotuvchi #${selected?.sellerOrderId || '—'}`}</div></div>
            <div className="col-6"><small className="text-muted">Yangilangan</small><div>{selected?.updatedAt || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.note || '—'}</div></div>
            {selected?.owner === 'seller' ? (
              <div className="col-12">
                <div className="card"><div className="card-body">
                    <div className="d-flex justify-content-between align-items-start gap-2 mb-3">
                      <div>
                        <h6 className="f-w-600 mb-1">Pul o‘tkazish rekvizitlari</h6>
                        <p className="mb-0 text-secondary">Shartnoma va bank ma’lumotlari seller kartochkasidan olinadi.</p>
                      </div>
                      <span className="badge text-light-secondary">{selected?.recipient?.legalTypeLabel || 'Tanlanmagan'}</span>
                    </div>
                    <div className="row g-3">
                      <div className="col-md-4"><small className="text-muted d-block">Hisob raqam</small><strong className="text-break">{selected?.recipient?.bankAccount || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">STIR</small><strong>{selected?.recipient?.inn || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">MFO</small><strong>{selected?.recipient?.bankMfo || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">Bank</small><strong>{selected?.recipient?.bankName || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">SWIFT</small><strong>{selected?.recipient?.bankSwift || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">Karta / karta egasi</small><strong>{[selected?.recipient?.card, selected?.recipient?.cardHolder].filter(Boolean).join(' · ') || '—'}</strong></div>
                      <div className="col-12"><small className="text-muted d-block">Yuridik manzil</small><strong>{selected?.recipient?.legalAddress || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">Shartnoma raqami</small><strong>{selected?.contract?.number || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">Imzolangan sana</small><strong>{selected?.contract?.signedAt || '—'}</strong></div>
                      <div className="col-md-4"><small className="text-muted d-block">Amal qilish muddati</small><strong>{selected?.contract?.expiresAt || '—'}</strong></div>
                      <div className="col-md-6"><small className="text-muted d-block">Hisobvaraq-faktura raqami</small><strong>{selected?.contract?.invoiceNumber || '—'}</strong></div>
                      <div className="col-md-6"><small className="text-muted d-block">Hisobvaraq-faktura sanasi</small><strong>{selected?.contract?.invoiceDate || '—'}</strong></div>
                      <div className="col-12">
                        <div className="p-3 b-r-15 b-1-light bg-light-secondary">
                          <div className="d-flex justify-content-between align-items-start gap-2">
                            <div>
                              <small className="text-muted d-block mb-1">To‘lov izohi</small>
                              <strong>{selected?.contract?.paymentPurpose || selected?.note || 'Shartnoma raqami kiritilmagan'}</strong>
                            </div>
                            <button type="button" className="btn btn-sm btn-light-secondary border" onClick={() => copy(selected?.contract?.paymentPurpose || selected?.note)}>
                              <i className="ti ti-copy"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                      {selected?.contract?.notes ? <div className="col-12"><small className="text-muted d-block">Shartnoma izohi</small><div>{selected.contract.notes}</div></div> : null}
                    </div>
                  </div></div>
              </div>
            ) : null}
            <div className="col-12">
              <div className="card"><div className="card-body">
                  <div className="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                      <h6 className="f-w-600 mb-1">Hisob-kitob tarkibi</h6>
                      <p className="mb-0 text-secondary">{selected?.breakdown?.periodFrom || 'Boshlanishidan'} — {selected?.breakdown?.periodTo || selected?.date || '—'}</p>
                    </div>
                    {selected?.reportUrl ? (
                      <div className="d-flex flex-wrap align-items-center gap-2">
                        <span className="text-muted f-s-13">PDF tili:</span>
                        <a className="btn btn-sm btn-dark" href={reportUrl(selected.reportUrl, 'uz')} target="_blank" rel="noreferrer"><i className="ti ti-file-download me-1"></i> O'zbekcha</a>
                        <a className="btn btn-sm btn-outline-dark" href={reportUrl(selected.reportUrl, 'ru')} target="_blank" rel="noreferrer"><i className="ti ti-file-download me-1"></i> Русский</a>
                      </div>
                    ) : null}
                  </div>
                  <div className="row g-2 mb-3">
                    <div className="col-md-3 col-6"><small className="text-muted d-block">Buyurtmalar</small><strong>{selected?.breakdown?.orders || 0} ta</strong></div>
                    <div className="col-md-3 col-6"><small className="text-muted d-block">Mahsulotlar</small><strong>{selected?.breakdown?.products || 0} ta</strong></div>
                    <div className="col-md-3 col-6"><small className="text-muted d-block">{selected?.owner === 'courier' ? 'Bonus' : 'Komissiya'}</small><strong>{fmt(selected?.owner === 'courier' ? (selected?.breakdown?.bonus || 0) : (selected?.breakdown?.commission || 0))} so'm</strong></div>
                    <div className="col-md-3 col-6"><small className="text-muted d-block">Yakuniy</small><strong>{fmt(selected?.breakdown?.net || selected?.netAmount || selected?.amount || 0)} so'm</strong></div>
                  </div>
                  <div className="table-responsive app-scroll">
                    <table className="table table-bottom-border align-middle f-s-13">
                      <thead><tr><th>Buyurtma</th><th>Ichki buyurtma</th><th>Mahsulot</th><th>Umumiy/Asosiy</th><th>Komissiya/Bonus</th><th>Yakuniy</th></tr></thead>
                      <tbody>
                        {(selected?.breakdown?.rows || []).slice(0, 8).map((row, index) => (
                          <tr key={`${row.transaction_id || index}-${row.order_id || 'x'}`}>
                            <td>#{row.order_id || '—'}<br /><small className="text-muted">{row.date || '—'}</small></td>
                            <td>#{row.sub_order_id || '—'}<br /><small className="text-muted">Tranzaksiya #{row.transaction_id || '—'}</small></td>
                            <td>{row.product_count || 0} ta</td>
                            <td>{fmt(selected?.owner === 'courier' ? (row.base_payout || row.gross || 0) : (row.gross || 0))} so'm</td>
                            <td>{fmt(selected?.owner === 'courier' ? (row.bonus || 0) : (row.commission || 0))} so'm</td>
                            <td className="f-w-600">{fmt(row.net || 0)} so'm</td>
                          </tr>
                        ))}
                        {(selected?.breakdown?.rows || []).length === 0 ? <tr><td colSpan={6} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hisob-kitob qatorlari topilmadi</td></tr> : null}
                      </tbody>
                    </table>
                  </div>
                  {(selected?.breakdown?.rows || []).length > 8 ? <small className="text-muted d-block mt-2">PDF hisobotda barcha qatorlar to'liq chiqadi.</small> : null}
                </div></div>
            </div>
            <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">{selected?.owner === 'courier' ? 'Kuryer bo‘yicha qisqa ma’lumot' : 'Sotuvchi bo‘yicha qisqa ma’lumot'}</h5></div><div className="card-body"><div className="row g-2"><div className="col-md-4"><small className="text-muted d-block">Tasdiqlangan</small><strong>{selected?.ownerTotals?.approvedCount || selected?.sellerTotals?.approvedCount || 0} ta</strong></div><div className="col-md-4"><small className="text-muted d-block">Tasdiqlangan summa</small><strong>{fmt(selected?.ownerTotals?.approvedSum || selected?.sellerTotals?.approvedSum || 0)} so'm</strong></div><div className="col-md-4"><small className="text-muted d-block">Kutilayotgan summa</small><strong>{fmt(selected?.ownerTotals?.pendingSum || selected?.sellerTotals?.pendingSum || 0)} so'm</strong></div></div></div></div></div>
            <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Yaqin tranzaksiyalar</h5></div><div className="card-body">{(selected?.nearby || []).map((row) => <div className="d-flex justify-content-between b-b-1-light py-2" key={row.id}><span>Tranzaksiya #{row.id} · {row.status || '—'}</span><strong>{fmt(row.netAmount || row.amount || 0)} so'm</strong></div>)}{(selected?.nearby || []).length === 0 ? <div className="text-muted">Boshqa tranzaksiya topilmadi</div> : null}</div></div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.reportUrl ? (
            <div className="me-auto d-flex flex-wrap align-items-center gap-2">
              <span className="text-muted f-s-13">PDF hisobot:</span>
              <a className="btn btn-outline-dark" href={reportUrl(selected.reportUrl, 'uz')} target="_blank" rel="noreferrer"><i className="ti ti-file-download me-1"></i> O'zbekcha</a>
              <a className="btn btn-outline-dark" href={reportUrl(selected.reportUrl, 'ru')} target="_blank" rel="noreferrer"><i className="ti ti-file-download me-1"></i> Русский</a>
            </div>
          ) : null}
          {statusChip(selected?.status) === 'text-light-warning' && selected?.approveUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.approveUrl, 'Tranzaksiya tasdiqlansinmi?')}>Tasdiqlash</Button> : null}
          {statusChip(selected?.status) === 'text-light-warning' && selected?.rejectUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.rejectUrl, 'Tranzaksiya rad etilsinmi?')}>Rad etish</Button> : null}
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
