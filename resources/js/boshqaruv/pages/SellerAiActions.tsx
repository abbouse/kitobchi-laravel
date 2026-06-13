import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

interface AiAction {
  token: string;
  seller: string;
  sellerPhone?: string;
  requestedBy?: string;
  actionType: string;
  status: string;
  file?: string;
  summary?: string;
  itemsCount: number;
  appliedCount: number;
  payload?: unknown;
  result?: unknown;
  createdAt?: string;
  appliedAt?: string;
  rolledBackAt?: string;
}

const statusLabel = (status: string) => ({
  all: 'Barchasi',
  preview: 'Preview',
  applied: 'Bajarilgan',
  rolled_back: 'Rollback',
  failed: 'Xato',
  cancelled: 'Bekor',
}[status] || status);

const statusChip = (status: string) => ({
  preview: 'chip-warning',
  applied: 'chip-success',
  rolled_back: 'chip-info',
  failed: 'chip-danger',
  cancelled: 'chip-gray',
}[status] || 'chip-gray');

export default function SellerAiActions() {
  const {
    sellerAiActions = [],
    sellerAiActionPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    sellerAiActionCounts = {},
    sellerAiActionFilters = {},
  } = usePage<{
    sellerAiActions?: AiAction[];
    sellerAiActionPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerAiActionCounts?: Record<string, number>;
    sellerAiActionFilters?: { status?: string; search?: string };
  }>().props;

  const [status, setStatus] = useState(sellerAiActionFilters.status || 'all');
  const [search, setSearch] = useState(sellerAiActionFilters.search || '');
  const [selected, setSelected] = useState<AiAction | null>(null);

  const load = (page = 1, nextStatus = status, term = search) => {
    router.get('/boshqaruv/seller-ai-actions', { ai_page: page, ai_status: nextStatus, ai_search: term }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Seller AI Audit</h1>
          <p className="page-subtitle">AI preview, tasdiqlangan stock amallari va rollback tarixi</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { key: 'preview', label: 'Preview', icon: 'bi-eye', color: '#f59e0b' },
          { key: 'applied', label: 'Bajarilgan', icon: 'bi-check2-circle', color: '#10b981' },
          { key: 'rolled_back', label: 'Rollback', icon: 'bi-arrow-counterclockwise', color: '#3b82f6' },
          { key: 'all', label: 'Jami', icon: 'bi-robot', color: '#7c3aed' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div><div className="stat-value">{sellerAiActionCounts[item.key] || 0}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          {['all', 'preview', 'applied', 'rolled_back', 'failed', 'cancelled'].map((item) => (
            <button key={item} className={`btn btn-sm ${status === item ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => { setStatus(item); load(1, item); }}>
              {statusLabel(item)} <span className="ms-1 opacity-75">{sellerAiActionCounts[item] || 0}</span>
            </button>
          ))}
          <form className="ms-auto input-group" style={{ maxWidth: 300 }} onSubmit={(event) => { event.preventDefault(); load(); }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Seller, fayl yoki token..." value={search} onChange={(event) => setSearch(event.target.value)} />
          </form>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>Token</th><th>Seller</th><th>Fayl</th><th>Action</th><th>Items</th><th>Status</th><th>Sana</th><th>Amal</th></tr></thead>
            <tbody>
              {sellerAiActions.map((action) => (
                <tr key={action.token}>
                  <td className="text-monospace small">{action.token.slice(0, 8)}...</td>
                  <td><div className="fw-semibold">{action.seller}</div><small className="text-muted">{action.sellerPhone || action.requestedBy || '—'}</small></td>
                  <td>{action.file || '—'}</td>
                  <td><div className="fw-semibold">{action.actionType}</div><small className="text-muted">{action.summary || '—'}</small></td>
                  <td>{action.appliedCount || 0}/{action.itemsCount || 0}</td>
                  <td><span className={`chip ${statusChip(action.status)}`}>{statusLabel(action.status)}</span></td>
                  <td className="text-muted">{action.createdAt || '—'}</td>
                  <td><button className="btn btn-sm btn-light" onClick={() => setSelected(action)}><i className="bi bi-eye"></i></button></td>
                </tr>
              ))}
              {sellerAiActions.length === 0 ? <tr><td colSpan={8} className="text-center text-muted py-5">AI action topilmadi</td></tr> : null}
            </tbody>
          </table>
        </div>
        <PaginationControls {...sellerAiActionPagination} onPageChange={(page) => load(page)} />
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} size="xl" centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">AI action detail</Modal.Title></Modal.Header>
        <Modal.Body>
          {selected ? (
            <div className="row g-3">
              <div className="col-xl-4">
                <div className="detail-panel h-100">
                  <Info label="Token" value={selected.token} />
                  <Info label="Seller" value={selected.seller} />
                  <Info label="Status" value={statusLabel(selected.status)} />
                  <Info label="Fayl" value={selected.file} />
                  <Info label="Yaratilgan" value={selected.createdAt} />
                  <Info label="Bajarilgan" value={selected.appliedAt} />
                  <Info label="Rollback" value={selected.rolledBackAt} />
                </div>
              </div>
              <div className="col-xl-8">
                <div className="detail-panel mb-3">
                  <h6 className="fw-bold mb-2">Payload</h6>
                  <pre className="small mb-0" style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected.payload || {}, null, 2)}</pre>
                </div>
                <div className="detail-panel">
                  <h6 className="fw-bold mb-2">Result</h6>
                  <pre className="small mb-0" style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected.result || {}, null, 2)}</pre>
                </div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer><Button variant="light" onClick={() => setSelected(null)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

function Info({ label, value }: { label: string; value?: string | number | null }) {
  return <div className="border-bottom py-2"><small className="text-muted d-block">{label}</small><span className="fw-semibold">{String(value || '—')}</span></div>;
}
