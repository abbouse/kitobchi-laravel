import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useState } from 'react';
import Modal from '../components/AppModal';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

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
  if (method === 'POST') return 'text-light-info';
  if (method === 'PUT' || method === 'PATCH') return 'text-light-warning';
  if (method === 'DELETE') return 'text-light-danger';
  return 'text-light-secondary';
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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Audit log</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Adminlar bajargan POST, PUT, PATCH va DELETE amallari.</p>
        </div>
      </div>

      <div className="row">
        {[
          ['Jami', auditLogTotals.all || 0, 'ti-clipboard-data', 'rgba(var(--primary), 1)'],
          ['Bugun', auditLogTotals.today || 0, 'ti-calendar-event', 'rgba(var(--success), 1)'],
          ['Xatolik', auditLogTotals.failed || 0, 'ti-alert-triangle', 'rgba(var(--danger), 1)'],
        ].map(([label, value, icon, color], kpiIndex) => (<div className="col-md-4" key={String(label)}>
          <StatWidget index={kpiIndex} label={label} value={fmt(Number(value))} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div><h5 className="f-w-600">Oxirgi admin amallari</h5><small className="text-muted">Parol va fayl maydonlari saqlanmaydi.</small></div>
          <span className="badge text-light-secondary">{auditLogPagination.total} ta</span>
        </div>
        <div className="card-body">

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>Admin</th><th>Method</th><th>Action</th><th>Target</th><th>Path</th><th>Status</th><th>Sana</th><th></th></tr></thead>
              <tbody>
                {auditLogs.map((log) => (
                  <tr key={log.id}>
                    <td className="f-w-600 text-nowrap">#{log.id}</td>
                    <td>{log.admin}<small className="d-block text-muted">{log.ip || '—'}</small></td>
                    <td><span className={`badge text-uppercase ${toneBadge(toneOf(methodChip(log.method)))}`}>{log.method}</span></td>
                    <td><strong>{log.action || log.route || '—'}</strong><small className="d-block text-muted">{log.route || '—'}</small></td>
                    <td>{log.targetType ? <span className="badge text-light-secondary">{log.targetType} #{log.targetId || '—'}</span> : '—'}</td>
                    <td className="text-muted f-s-13">{log.path}</td>
                    <td><span className={`badge ${(log.statusCode || 0) >= 400 ? 'text-light-danger' : 'text-light-success'}`}>{log.statusCode || '—'}</span></td>
                    <td className="text-muted f-s-13">{log.date || '—'}</td>
                    <td><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => setSelected(log)}><i className="ti ti-eye"></i></button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationControls {...auditLogPagination} onPageChange={(page) => router.get('/boshqaruv/audit-logs', { audit_page: page }, { preserveState: true, preserveScroll: true, replace: true })} />
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Audit #{selected?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-6"><p className="mb-1 f-s-13 text-secondary">Admin</p><div className="f-w-600">{selected?.admin}</div></div>
            <div className="col-md-6"><p className="mb-1 f-s-13 text-secondary">IP</p><div>{selected?.ip || '—'}</div></div>
            <div className="col-md-6"><p className="mb-1 f-s-13 text-secondary">Route</p><div>{selected?.route || '—'}</div></div>
            <div className="col-md-6"><p className="mb-1 f-s-13 text-secondary">Target</p><div>{selected?.targetType || '—'} #{selected?.targetId || '—'}</div></div>
            <div className="col-12"><p className="mb-1 f-s-13 text-secondary">Path</p><div>{selected?.path}</div></div>
            <div className="col-12">
              <p className="mb-0 text-secondary">Request data</p>
              <pre className="mt-2 p-3 b-r-8 f-s-12" style={{ background: 'var(--bs-body-bg)', border: '1px solid var(--bs-border-color)', whiteSpace: 'pre-wrap' }}>{JSON.stringify(selected?.requestData || {}, null, 2)}</pre>
            </div>
          </div>
        </Modal.Body>
      </Modal>
    </div>
  );
}
