import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Transaction {
  id: number;
  user: string;
  phone?: string;
  type: string;
  amount: number;
  commission?: number;
  netAmount?: number;
  status: string;
  date?: string;
  method?: string;
  note?: string;
  showUrl?: string;
  approveUrl?: string;
  rejectUrl?: string;
}

const statusChip = (status?: string) => {
  const value = String(status || '').toLowerCase();
  if (['completed', 'approved', 'success', 'paid'].includes(value)) return 'chip-success';
  if (['pending', 'new', 'waiting'].includes(value)) return 'chip-warning';
  if (['rejected', 'failed', 'cancelled'].includes(value)) return 'chip-danger';
  return 'chip-gray';
};

export default function Transaksiyalar() {
  const { transactions = [] } = usePage<{ transactions?: Transaction[] }>().props;
  const [selected, setSelected] = useState<Transaction | null>(null);

  const totals = useMemo(() => {
    const income = transactions.reduce((sum, item) => sum + Math.max(item.amount || 0, 0), 0);
    const commission = transactions.reduce((sum, item) => sum + (item.commission || 0), 0);
    const pending = transactions.filter((item) => statusChip(item.status) === 'chip-warning').length;
    const approved = transactions.filter((item) => statusChip(item.status) === 'chip-success').length;
    return { income, commission, pending, approved };
  }, [transactions]);
  const pagination = useClientPagination(transactions, 30);

  const patch = (url?: string, message?: string) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Tranzaksiyalar</h1>
          <p className="page-subtitle">Seller to'lovlari, yechib olish so'rovlari va komissiyalar</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami tranzaksiya', value: transactions.length, icon: 'bi-receipt', color: '#4f46e5' },
          { label: 'Tasdiqlangan', value: totals.approved, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Kutilmoqda', value: totals.pending, icon: 'bi-hourglass-split', color: '#f59e0b' },
          { label: 'Komissiya', value: `${fmt(totals.commission)} so'm`, icon: 'bi-percent', color: '#7c3aed' },
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
            <thead><tr><th>ID</th><th>Seller</th><th>Turi</th><th>Summa</th><th>Komissiya</th><th>Net</th><th>Metod</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {pagination.paginated.map((item) => (
                <tr key={item.id}>
                  <td className="fw-semibold text-primary">#{item.id}</td>
                  <td><div className="fw-semibold">{item.user}</div><small className="text-muted">{item.phone}</small></td>
                  <td><span className="chip chip-gray">{item.type}</span></td>
                  <td className={`fw-bold ${item.amount >= 0 ? 'text-success' : 'text-danger'}`}>{item.amount >= 0 ? '+' : ''}{fmt(item.amount)} so'm</td>
                  <td>{fmt(item.commission || 0)} so'm</td>
                  <td className="fw-semibold">{fmt(item.netAmount ?? item.amount)} so'm</td>
                  <td><span className="chip chip-gray">{item.method || '—'}</span></td>
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
            </tbody>
          </table>
        </div>
        <PaginationControls {...pagination} onPageChange={pagination.setPage} />
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Tranzaksiya #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Seller</small><div className="fw-semibold">{selected?.user}</div></div>
            <div className="col-6"><small className="text-muted">Telefon</small><div>{selected?.phone || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Turi</small><div>{selected?.type}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${statusChip(selected?.status)}`}>{selected?.status || '—'}</span></div></div>
            <div className="col-6"><small className="text-muted">Summa</small><div className="fw-bold">{fmt(selected?.amount || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Net</small><div>{fmt(selected?.netAmount ?? selected?.amount ?? 0)} so'm</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selected?.note || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {statusChip(selected?.status) === 'chip-warning' && selected?.approveUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.approveUrl, 'Tranzaksiya tasdiqlansinmi?')}>Tasdiqlash</Button> : null}
          {statusChip(selected?.status) === 'chip-warning' && selected?.rejectUrl ? <Button variant="outline-secondary" onClick={() => patch(selected.rejectUrl, 'Tranzaksiya rad etilsinmi?')}>Rad etish</Button> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
