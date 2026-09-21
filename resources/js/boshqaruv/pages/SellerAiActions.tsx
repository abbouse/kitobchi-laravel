import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

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
  preview: 'text-light-warning',
  applied: 'text-light-success',
  rolled_back: 'text-light-info',
  failed: 'text-light-danger',
  cancelled: 'text-light-secondary',
}[status] || 'text-light-secondary');

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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Seller AI Audit</h4><PageCrumbs />
          <p className="mb-0 text-secondary">AI preview, tasdiqlangan stock amallari va rollback tarixi</p>
        </div>
      </div>

      <div className="row">
        {[
          { key: 'preview', label: 'Preview', icon: 'ti-eye', color: 'rgba(var(--warning-dark), 1)' },
          { key: 'applied', label: 'Bajarilgan', icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { key: 'rolled_back', label: 'Rollback', icon: 'ti-rotate', color: 'rgba(var(--info), 1)' },
          { key: 'all', label: 'Jami', icon: 'ti-robot', color: 'rgba(var(--primary), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.key}>
          <StatWidget
            index={kpiIndex}
            label={item.label}
            value={sellerAiActionCounts[item.key] || 0} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-body">
          <div className="nav nav-tabs app-tabs-primary mb-3 flex-wrap">
            {['all', 'preview', 'applied', 'rolled_back', 'failed', 'cancelled'].map((item) => (
              <div key={item} className="nav-item"><button
                  className={`nav-link ${status === item ? 'active' : ''}`}
                  onClick={() => { setStatus(item); load(1, item); }}>
                  {statusLabel(item)} <span className="ms-1 opacity-75">{sellerAiActionCounts[item] || 0}</span>
                </button></div>
            ))}
            <form className="ms-auto input-group" style={{ maxWidth: 300 }} onSubmit={(event) => { event.preventDefault(); load(); }}>
              <span className="input-group-text bg-white"><i className="ti ti-search text-muted"></i></span>
              <input className="form-control" placeholder="Seller, fayl yoki token..." value={search} onChange={(event) => setSearch(event.target.value)} />
            </form>
          </div>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>Token</th><th>Seller</th><th>Fayl</th><th>Action</th><th>Items</th><th>Status</th><th>Sana</th><th>Amal</th></tr></thead>
              <tbody>
                {sellerAiActions.map((action) => (
                  <tr key={action.token}>
                    <td className="font-monospace f-s-13">{action.token.slice(0, 8)}...</td>
                    <td><div className="f-w-600">{action.seller}</div><small className="text-muted">{action.sellerPhone || action.requestedBy || '—'}</small></td>
                    <td>{action.file || '—'}</td>
                    <td><div className="f-w-600">{action.actionType}</div><small className="text-muted">{action.summary || '—'}</small></td>
                    <td>{action.appliedCount || 0}/{action.itemsCount || 0}</td>
                    <td><span className={`badge text-uppercase ${toneBadge(toneOf(statusChip(action.status)))}`}>{statusLabel(action.status)}</span></td>
                    <td className="text-muted">{action.createdAt || '—'}</td>
                    <td><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => setSelected(action)}><i className="ti ti-eye"></i></button></td>
                  </tr>
                ))}
                {sellerAiActions.length === 0 ? <tr><td colSpan={8} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>AI action topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...sellerAiActionPagination} onPageChange={(page) => load(page)} />
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} size="xl" centered>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">AI action detail</Modal.Title></Modal.Header>
        <Modal.Body>
          {selected ? (
            <div className="row">
              <div className="col-xl-4">
                <div className="card h-100"><div className="card-body">
                    <Info label="Token" value={selected.token} />
                    <Info label="Seller" value={selected.seller} />
                    <Info label="Status" value={statusLabel(selected.status)} />
                    <Info label="Fayl" value={selected.file} />
                    <Info label="Yaratilgan" value={selected.createdAt} />
                    <Info label="Bajarilgan" value={selected.appliedAt} />
                    <Info label="Rollback" value={selected.rolledBackAt} />
                  </div></div>
              </div>
              <div className="col-xl-8">
                <div className="card"><div className="card-header"><h5 className="mb-0">Payload</h5></div><div className="card-body">
                    <pre className="f-s-13 mb-0" style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected.payload || {}, null, 2)}</pre>
                  </div></div>
                <div className="card"><div className="card-header"><h5 className="mb-0">Result</h5></div><div className="card-body">
                    <pre className="f-s-13 mb-0" style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected.result || {}, null, 2)}</pre>
                  </div></div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}

function Info({ label, value }: { label: string; value?: string | number | null }) {
  return <div className="b-b-1-light py-2"><small className="text-muted d-block">{label}</small><span className="f-w-600">{String(value || '—')}</span></div>;
}
