import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== ADMINLAR =====
export function Adminlar() {
  const { admins = [] } = usePage<{
    admins?: Array<{ id: number; name: string; email: string; role: string; roleKey?: string; active: boolean; lastLogin?: string; createUrl?: string; updateUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [editing, setEditing] = useState<(typeof admins)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const createUrl = admins[0]?.createUrl || '/boshqaruv/adminlar';

  const toggle = (admin: (typeof admins)[0]) => admin.toggleUrl && router.patch(admin.toggleUrl, {}, { preserveScroll: true });
  const destroy = (admin: (typeof admins)[0]) => {
    if (!admin.destroyUrl || !confirm(`${admin.name} admini o'chirilsinmi?`)) return;
    router.delete(admin.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Adminlar</h1><p className="page-subtitle">Jami {admins.length} ta admin</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Admin qo'shish</button>
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
                <button className="btn btn-sm btn-light me-1" onClick={() => { setEditing(admin); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(admin)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'Adminni tahrirlash' : "Admin qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Ism</Form.Label><Form.Control name="name" required defaultValue={editing?.name || ''} className="mb-3" />
            <Form.Label>Email</Form.Label><Form.Control name="email" type="email" required defaultValue={editing?.email || ''} className="mb-3" />
            <Form.Label>Rol</Form.Label><Form.Select name="role" defaultValue={editing?.roleKey || 'admin'} className="mb-3"><option value="superadmin">Super Admin</option><option value="admin">Admin</option><option value="moderator">Moderator</option></Form.Select>
            <Form.Label>Parol {editing ? <span className="text-muted">(bo'sh qoldirilsa o'zgarmaydi)</span> : null}</Form.Label><Form.Control name="password" type="password" minLength={8} required={!editing} className="mb-3" />
            <Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.active : true} />
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== API MIJOZLAR =====
export function ApiClients() {
  const { apiClients = [], apiLogs = [] } = usePage<{
    apiClients?: Array<{ id: number; name: string; key: string; abilities?: string; active: boolean; requests: number; rateLimitSecond?: number; rateLimitMinute?: number; createUrl?: string; updateUrl?: string; toggleUrl?: string; regenerateUrl?: string; destroyUrl?: string }>;
    apiLogs?: Array<{ id: number; client?: string; method?: string; path?: string; status: number; date?: string }>;
  }>().props;
  const [showLogs, setShowLogs] = useState(false);
  const [editing, setEditing] = useState<(typeof apiClients)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);

  const createUrl = apiClients[0]?.createUrl || '/boshqaruv/api-clients';
  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true });
  const destroy = (client: (typeof apiClients)[0]) => {
    if (!client.destroyUrl || !confirm(`${client.name} API clienti o'chirilsinmi?`)) return;
    router.delete(client.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">API mijozlar</h1><p className="page-subtitle">Jami {apiClients.length} ta client</p></div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={() => setShowLogs(true)}><i className="bi bi-journal-code me-1"></i>API Logs</button>
          <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Client qo'shish</button>
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
                <button className="btn btn-sm btn-light me-1" onClick={() => { setEditing(client); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
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
        <Modal.Footer><Button variant="light" onClick={() => setShowLogs(false)}>Yopish</Button></Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'API mijozni tahrirlash' : "API mijoz qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Nomi</Form.Label><Form.Control name="name" required defaultValue={editing?.name || ''} className="mb-3" />
            <Form.Label>Abilities</Form.Label><Form.Control name="abilities" placeholder="read, write" defaultValue={editing?.abilities || 'read'} className="mb-3" />
            <div className="row g-3 mb-3">
              <div className="col-6"><Form.Label>Limit / sekund</Form.Label><Form.Control name="rate_limit_per_second" type="number" min={1} defaultValue={editing?.rateLimitSecond || 8} /></div>
              <div className="col-6"><Form.Label>Limit / minut</Form.Label><Form.Control name="rate_limit_per_minute" type="number" min={1} defaultValue={editing?.rateLimitMinute || 240} /></div>
            </div>
            <Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.active : true} />
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
