import { useState } from 'react';
import { Modal } from 'react-bootstrap';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));

interface AuditLog {
  id: number;
  admin: string;
  method: string;
  route?: string;
  path: string;
  action?: string;
  targetType?: string;
  targetId?: number;
  requestData?: Record<string, unknown>;
  ip?: string;
  statusCode?: number;
  date?: string;
}

const methodChip = (method: string) => {
  if (method === 'POST') return 'chip-info';
  if (method === 'PUT' || method === 'PATCH') return 'chip-warning';
  if (method === 'DELETE') return 'chip-danger';
  return 'chip-gray';
};

export default function AuditLogs() {
  const {
    auditLogs = [],
    auditLogPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    auditLogTotals = {},
  } = usePage<{
    auditLogs?: AuditLog[];
    auditLogPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    auditLogTotals?: Record<string, number>;
  }>().props;
  const [selected, setSelected] = useState<AuditLog | null>(null);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Audit log</h1>
          <p className="page-subtitle">Adminlar bajargan POST, PUT, PATCH va DELETE amallari.</p>
        </div>
      </div>

      <div className="row g-3 mb-3">
        {[
          ['Jami', auditLogTotals.all || 0, 'bi-clipboard-data', '#0B0342'],
          ['Bugun', auditLogTotals.today || 0, 'bi-calendar2-day', '#0F6A46'],
          ['Xatolik', auditLogTotals.failed || 0, 'bi-exclamation-triangle', '#A32A2E'],
        ].map(([label, value, icon, color]) => (
          <div className="col-md-4" key={String(label)}>
            <div className="stat-card"><div className="d-flex align-items-center gap-3"><div className="stat-icon" style={{ background: String(color) }}><i className={`bi ${icon}`}></i></div><div><div className="stat-value">{fmt(Number(value))}</div><div className="stat-label">{label}</div></div></div></div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div><div className="panel-title">Oxirgi admin amallari</div><small className="text-muted">Parol va fayl maydonlari saqlanmaydi.</small></div>
          <span className="chip chip-gray">{auditLogPagination.total} ta</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Admin</th><th>Method</th><th>Action</th><th>Target</th><th>Path</th><th>Status</th><th>Sana</th><th></th></tr></thead>
            <tbody>
              {auditLogs.map((log) => (
                <tr key={log.id}>
                  <td className="fw-semibold text-primary">#{log.id}</td>
                  <td>{log.admin}<small className="d-block text-muted">{log.ip || '—'}</small></td>
                  <td><span className={`chip ${methodChip(log.method)}`}>{log.method}</span></td>
                  <td><strong>{log.action || log.route || '—'}</strong><small className="d-block text-muted">{log.route || '—'}</small></td>
                  <td>{log.targetType ? <span className="chip chip-gray">{log.targetType} #{log.targetId || '—'}</span> : '—'}</td>
                  <td className="text-muted small">{log.path}</td>
                  <td><span className={`chip ${(log.statusCode || 0) >= 400 ? 'chip-danger' : 'chip-success'}`}>{log.statusCode || '—'}</span></td>
                  <td className="text-muted small">{log.date || '—'}</td>
                  <td><button className="btn btn-sm btn-light" onClick={() => setSelected(log)}><i className="bi bi-eye"></i></button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...auditLogPagination} onPageChange={(page) => router.get('/boshqaruv/audit-logs', { audit_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Audit #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-6"><small className="text-muted">Admin</small><div className="fw-semibold">{selected?.admin}</div></div>
            <div className="col-md-6"><small className="text-muted">IP</small><div>{selected?.ip || '—'}</div></div>
            <div className="col-md-6"><small className="text-muted">Route</small><div>{selected?.route || '—'}</div></div>
            <div className="col-md-6"><small className="text-muted">Target</small><div>{selected?.targetType || '—'} #{selected?.targetId || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Path</small><div>{selected?.path}</div></div>
            <div className="col-12">
              <small className="text-muted">Request data</small>
              <pre className="mt-2 p-3 rounded" style={{ background: 'var(--bs-body-bg)', border: '1px solid var(--bs-border-color)', fontSize: 12, whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected?.requestData || {}, null, 2)}</pre>
            </div>
          </div>
        </Modal.Body>
      </Modal>
    </div>
  );
}
