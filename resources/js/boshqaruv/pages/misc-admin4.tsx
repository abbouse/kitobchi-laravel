import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== ADMINLAR =====
export function Adminlar() {
  const { admins = [] } = usePage<{
    admins?: Array<{ id: number; name: string; email: string; role: string; active: boolean; lastLogin?: string; createUrl?: string; showUrl?: string; editUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const createUrl = admins[0]?.createUrl || '/a122/admins/create';

  const toggle = (admin: (typeof admins)[0]) => admin.toggleUrl && router.patch(admin.toggleUrl, {}, { preserveScroll: true });
  const destroy = (admin: (typeof admins)[0]) => {
    if (!admin.destroyUrl || !confirm(`${admin.name} admini o'chirilsinmi?`)) return;
    router.delete(admin.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Adminlar</h1><p className="page-subtitle">Jami {admins.length} ta admin</p></div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi admin</a>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Ism</th><th>Email</th><th>Rol</th><th>Oxirgi kirish</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{admins.map(admin => (
            <tr key={admin.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{admin.id}</td>
              <td className="fw-semibold">{admin.name}</td>
              <td className="text-muted">{admin.email}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{admin.role}</span></td>
              <td className="text-muted">{admin.lastLogin || '—'}</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={admin.active} onChange={() => toggle(admin)} /></div></td>
              <td>
                {admin.showUrl ? <a className="btn btn-sm btn-light me-1" href={admin.showUrl}><i className="bi bi-eye"></i></a> : null}
                {admin.editUrl ? <a className="btn btn-sm btn-light me-1" href={admin.editUrl}><i className="bi bi-pencil"></i></a> : null}
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(admin)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}

// ===== API MIJOZLAR =====
export function ApiClients() {
  const { apiClients = [], apiLogs = [] } = usePage<{
    apiClients?: Array<{ id: number; name: string; key: string; active: boolean; requests: number; rateLimitSecond?: number; rateLimitMinute?: number; createUrl?: string; docsUrl?: string; logsUrl?: string; editUrl?: string; toggleUrl?: string; regenerateUrl?: string; destroyUrl?: string }>;
    apiLogs?: Array<{ id: number; client?: string; method?: string; path?: string; status: number; date?: string }>;
  }>().props;
  const [showLogs, setShowLogs] = useState(false);

  const createUrl = apiClients[0]?.createUrl || '/a122/api-clients/create';
  const docsUrl = apiClients[0]?.docsUrl || '/a122/api-clients/docs';
  const logsUrl = apiClients[0]?.logsUrl || '/a122/api-clients/logs';
  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true });
  const destroy = (client: (typeof apiClients)[0]) => {
    if (!client.destroyUrl || !confirm(`${client.name} API clienti o'chirilsinmi?`)) return;
    router.delete(client.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">API mijozlar</h1><p className="page-subtitle">Jami {apiClients.length} ta client</p></div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => setShowLogs(true)}><i className="bi bi-journal-code me-1"></i>API Logs</button>
          <a className="btn btn-outline-secondary" href={docsUrl}><i className="bi bi-file-text me-1"></i>API Docs</a>
          <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi client</a>
        </div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Nomi</th><th>App ID</th><th>So'rovlar</th><th>Limit</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{apiClients.map(client => (
            <tr key={client.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{client.id}</td>
              <td className="fw-semibold">{client.name}</td>
              <td><code style={{ fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4 }}>{client.key}</code></td>
              <td>{client.requests.toLocaleString()}</td>
              <td>{client.rateLimitSecond || 0}/s · {client.rateLimitMinute || 0}/m</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={client.active} onChange={() => patch(client.toggleUrl)} /></div></td>
              <td>
                {client.editUrl ? <a className="btn btn-sm btn-light me-1" href={client.editUrl}><i className="bi bi-pencil"></i></a> : null}
                <button className="btn btn-sm btn-light me-1" onClick={() => patch(client.regenerateUrl)}><i className="bi bi-arrow-repeat"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(client)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showLogs} onHide={() => setShowLogs(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">API Logs (oxirgi 50 ta)</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="table-responsive"><table className="data-table">
            <thead><tr><th>Method</th><th>Path</th><th>Status</th><th>Vaqt</th><th>Client</th></tr></thead>
            <tbody>{apiLogs.map((log) => (
              <tr key={log.id}>
                <td><span className={`chip ${log.method === 'GET' ? 'chip-success' : log.method === 'POST' ? 'chip-info' : 'chip-danger'}`} style={{ fontSize: 9, fontFamily: 'monospace' }}>{log.method}</span></td>
                <td className="fw-semibold" style={{ fontSize: 11 }}>{log.path}</td>
                <td><span className={`chip ${log.status < 300 ? 'chip-success' : log.status < 400 ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{log.status}</span></td>
                <td className="text-muted small">{log.date}</td>
                <td className="text-muted small">{log.client}</td>
              </tr>
            ))}</tbody>
          </table></div>
        </Modal.Body>
        <Modal.Footer><a className="btn btn-outline-secondary" href={logsUrl}>To'liq loglar</a><Button variant="light" onClick={() => setShowLogs(false)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}
