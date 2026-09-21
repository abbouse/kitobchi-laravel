import { FormEvent, useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';

type PanelModule = { key: string; label: string; superOnly: boolean };
type PanelRolePreset = { key: string; label: string; permissions: string[]; readOnly: boolean };
type AdminRow = {
  id: number; name: string; email: string; role: string; roleKey?: string; active: boolean;
  lastLogin?: string; permissions?: string[]; isReadOnly?: boolean; isSuperAdmin?: boolean;
  createUrl?: string; updateUrl?: string; toggleUrl?: string; destroyUrl?: string;
};

// ===== ADMINLAR =====
// Rol/ruxsat (RBAC) boshqaruvi: har bir admin uchun rol (tayyor shablon) va
// alohida modul ruxsatlari (checkbox) tanlanadi. Rol tanlanganda checkbox'lar
// avtomatik to'ldiriladi, lekin keyin qo'lda ham o'zgartirish mumkin — yakuniy
// natija har doim `permissions[]` massivi sifatida saqlanadi. Bu sahifaning
// o'ziga faqat Super Admin kira oladi (backend: panel.permission:admins,
// 'admins' moduli superOnly), shuning uchun barcha modul checkbox'lari
// (jumladan 'admins'ning o'zi) hech qanday cheklovsiz ko'rsatiladi.
export function Adminlar() {
  const { admins = [], roleMeta } = usePage<{
    admins?: AdminRow[];
    roleMeta?: { modules: PanelModule[]; roles: PanelRolePreset[] };
  }>().props;
  const modules = roleMeta?.modules || [];
  const roles = roleMeta?.roles || [];
  const roleByKey = useMemo(() => Object.fromEntries(roles.map((r) => [r.key, r])), [roles]);

  const [editing, setEditing] = useState<AdminRow | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [selectedRole, setSelectedRole] = useState('admin');
  const [selectedPerms, setSelectedPerms] = useState<string[]>([]);
  const [isReadOnly, setIsReadOnly] = useState(false);
  const createUrl = admins[0]?.createUrl || '/boshqaruv/adminlar';

  const openForm = (admin: AdminRow | null) => {
    setEditing(admin);
    setSelectedRole(admin?.roleKey || 'admin');
    setSelectedPerms(admin?.permissions || roleByKey['admin']?.permissions || []);
    setIsReadOnly(admin?.isReadOnly || false);
    setShowForm(true);
  };

  const applyRolePreset = (roleKey: string) => {
    setSelectedRole(roleKey);
    const preset = roleByKey[roleKey];
    if (preset) {
      setSelectedPerms(preset.permissions);
      setIsReadOnly(preset.readOnly);
    }
  };

  const togglePerm = (key: string) =>
    setSelectedPerms((current) => (current.includes(key) ? current.filter((p) => p !== key) : [...current, key]));

  const toggle = (admin: AdminRow) => admin.toggleUrl && router.patch(admin.toggleUrl, {}, { preserveScroll: true });
  const destroy = (admin: AdminRow) => {
    if (!admin.destroyUrl || !confirm(`${admin.name} admini o'chirilsinmi?`)) return;
    router.delete(admin.destroyUrl, { preserveScroll: true });
  };

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const data = {
      name: String(form.get('name') || ''),
      email: String(form.get('email') || ''),
      password: String(form.get('password') || ''),
      role: selectedRole,
      permissions: selectedPerms,
      is_active: form.get('is_active') ? '1' : '0',
      is_read_only: isReadOnly ? '1' : '0',
    };
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Adminlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {admins.length} ta admin — rol va ruxsatlar shu yerda boshqariladi</p></div>
        <button className="btn btn-primary" onClick={() => openForm(null)}><i className="ti ti-plus me-1"></i>Admin qo'shish</button>
      </div>
      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Ism</th><th>Email</th><th>Rol</th><th>Ruxsatlar</th><th>Oxirgi kirish</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>{admins.map(admin => (
              <tr key={admin.id}>
                <td className="f-w-600 text-primary">#{admin.id}</td>
                <td className="f-w-600">{admin.name}</td>
                <td className="text-muted">{admin.email}</td>
                <td>
                  <span className="badge text-light-primary f-s-9">{admin.role}</span>
                  {admin.isReadOnly && <span className="badge text-light-secondary ms-1 f-s-9">faqat ko'rish</span>}
                </td>
                <td>
                  {admin.isSuperAdmin ? (
                    <span className="text-muted f-s-13">hammasi</span>
                  ) : (
                    <span className="text-muted f-s-13">{(admin.permissions || []).length} ta modul</span>
                  )}
                </td>
                <td className="text-muted">{admin.lastLogin || '—'}</td>
                <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={admin.active} onChange={() => toggle(admin)} /></div></td>
                <td>
                  <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1" onClick={() => openForm(admin)}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(admin)}><i className="ti ti-trash"></i></button>
                </td>
              </tr>
            ))}</tbody>
          </table></div>
        </div>
</div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editing ? 'Adminni tahrirlash' : "Admin qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Ism</Form.Label><Form.Control name="name" required defaultValue={editing?.name || ''} className="mb-3" />
            <Form.Label>Email</Form.Label><Form.Control name="email" type="email" required defaultValue={editing?.email || ''} className="mb-3" />
            <Form.Label>Rol (tayyor shablon — tanlanganda ruxsatlar avtomatik to'ldiriladi)</Form.Label>
            <Form.Select value={selectedRole} onChange={(e) => applyRolePreset(e.target.value)} className="mb-3">
              {roles.map((r) => <option key={r.key} value={r.key}>{r.label}</option>)}
            </Form.Select>

            {selectedRole !== 'superadmin' && (
              <>
                <Form.Label>Ruxsat berilgan bo'limlar</Form.Label>
                <div className="row g-1 mb-2 p-2 bg-light-secondary b-r-8 overflow-y-auto" style={{ maxHeight: 220 }}>
                  {modules.map((m) => (
                    <div className="col-6" key={m.key}>
                      <Form.Check
                        type="checkbox"
                        id={`perm-${m.key}`}
                        label={m.label}
                        checked={selectedPerms.includes(m.key)}
                        onChange={() => togglePerm(m.key)}
                      />
                    </div>
                  ))}
                </div>
                <Form.Check
                  type="switch"
                  id="is_read_only_switch"
                  label="Faqat ko'rish (Auditor) — hech narsani o'zgartira olmaydi"
                  checked={isReadOnly}
                  onChange={(e) => setIsReadOnly(e.target.checked)}
                  className="mb-3"
                />
              </>
            )}
            {selectedRole === 'superadmin' && (
              <div className="text-muted f-s-13 mb-3"><i className="ti ti-info-circle me-1"></i>Super Admin barcha bo'limga to'liq kirish huquqiga ega — ruxsatlarni alohida belgilash shart emas.</div>
            )}

            <Form.Label>Parol {editing ? <span className="text-muted">(bo'sh qoldirilsa o'zgarmaydi)</span> : null}</Form.Label>
            <Form.Control name="password" type="password" minLength={8} required={!editing} className="mb-3" />
            <Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.active : true} />
          </Modal.Body>
          <Modal.Footer><Button variant="light-secondary" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

// ===== API MIJOZLAR =====
export function ApiClients() {
  const { apiClients = [], apiLogs = [], apiClientsMeta } = usePage<{
    apiClients?: Array<{ id: number; name: string; key: string; secret?: string; abilities?: string; sellerId?: number | null; sellerName?: string | null; allowedIps?: string; active: boolean; requests: number; rateLimitSecond?: number; rateLimitMinute?: number; createUrl?: string; updateUrl?: string; toggleUrl?: string; regenerateUrl?: string; destroyUrl?: string; webhooks?: Array<{ id: number; url: string; events: string[]; active: boolean; failures: number; toggleUrl: string; destroyUrl: string }>; availableEvents?: string[]; webhookStoreUrl?: string }>;
    apiLogs?: Array<{ id: number; client?: string; method?: string; path?: string; status: number; date?: string }>;
    apiClientsMeta?: { warnings?: string[] };
  }>().props;
  const [showLogs, setShowLogs] = useState(false);
  const [editing, setEditing] = useState<(typeof apiClients)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [showSecret, setShowSecret] = useState<Record<number, boolean>>({});
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    setCopiedKey(label);
    setTimeout(() => setCopiedKey(null), 2000);
  };

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

  // ── Webhooklar ──
  const [webhookClientId, setWebhookClientId] = useState<number | null>(null);
  const [selectedEvents, setSelectedEvents] = useState<string[]>([]);
  const webhookClient = apiClients.find((c) => c.id === webhookClientId) || null;
  const toggleEvent = (ev: string) =>
    setSelectedEvents((s) => (s.includes(ev) ? s.filter((x) => x !== ev) : [...s, ev]));
  const addWebhook = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!webhookClient?.webhookStoreUrl) return;
    const form = event.currentTarget;
    const url = (new FormData(form).get('url') as string) || '';
    router.post(
      webhookClient.webhookStoreUrl,
      { url, events: selectedEvents },
      { preserveScroll: true, onSuccess: () => { setSelectedEvents([]); form.reset(); } },
    );
  };
  const removeWebhook = (url?: string) => {
    if (!url || !confirm("Webhook o'chirilsinmi?")) return;
    router.delete(url, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">API mijozlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {apiClients.length} ta client</p></div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/developers/api"><i className="ti ti-file-code me-1"></i>Docs</a>
          <button className="btn btn-outline-secondary" onClick={() => setShowLogs(true)}><i className="ti ti-file-code me-1"></i>API Logs</button>
          <button className="btn btn-primary" onClick={() => { setEditing(null); setShowForm(true); }}><i className="ti ti-plus me-1"></i>Client qo'shish</button>
        </div>
      </div>
      {(apiClientsMeta?.warnings || []).length ? (
        <div className="alert alert-light-warning">
          <div className="f-w-600 mb-1">Sahifa himoyalangan rejimda ishlayapti</div>
          <ul className="mb-0 ps-3">
            {(apiClientsMeta?.warnings || []).map((warning) => <li key={warning}>{warning}</li>)}
          </ul>
        </div>
      ) : null}
      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>ID</th><th>Nomi</th><th>Credentials (App ID & Secret)</th><th>So'rovlar</th><th>Limit</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>{apiClients.map(client => (
              <tr key={client.id}>
                <td className="f-w-600 text-primary">#{client.id}</td>
                <td className="f-w-600">{client.name}{client.sellerName ? <div className="text-muted f-s-13"><i className="ti ti-building-store me-1"></i>{client.sellerName}</div> : (client.sellerId ? <div className="text-muted f-s-13"><i className="ti ti-building-store me-1"></i>#{client.sellerId}</div> : null)}</td>
                <td>
                  <div className="d-flex flex-column gap-1" style={{ minWidth: 260 }}>
                    <div className="d-flex align-items-center gap-1">
                      <span className="badge bg-light-secondary text-dark b-1-light px-1 f-s-9">ID</span>
                      <code className="f-s-11 bg-light-secondary b-r-4" style={{ padding: '2px 6px' }}>{client.key}</code>
                      <button
                        type="button"
                        className="btn btn-sm btn-link p-0 text-muted"
                        title="App ID nusxalash"
                        onClick={() => copyToClipboard(client.key, `id-${client.id}`)}
                      >
                        <i className={`ti ${copiedKey === `id-${client.id}` ? 'ti-check text-success' : 'ti-clipboard'}`}></i>
                      </button>
                    </div>
                    {client.secret ? (
                      <div className="d-flex align-items-center gap-1">
                        <span className="badge bg-light-secondary text-dark b-1-light px-1 f-s-9">Secret</span>
                        <code className="f-s-11 bg-light-secondary b-r-4" style={{ padding: '2px 6px', letterSpacing: showSecret[client.id] ? 'normal' : '2px' }}>
                          {showSecret[client.id] ? client.secret : '••••••••••••••••'}
                        </code>
                        <button
                          type="button"
                          className="btn btn-sm btn-link p-0 text-muted ms-1"
                          title={showSecret[client.id] ? "Yashirish" : "Ko'rish"}
                          onClick={() => setShowSecret(prev => ({ ...prev, [client.id]: !prev[client.id] }))}
                        >
                          <i className={`ti ${showSecret[client.id] ? 'ti-eye-off' : 'ti-eye'}`}></i>
                        </button>
                        <button
                          type="button"
                          className="btn btn-sm btn-link p-0 text-muted"
                          title="App Secret nusxalash"
                          onClick={() => copyToClipboard(client.secret || '', `sec-${client.id}`)}
                        >
                          <i className={`ti ${copiedKey === `sec-${client.id}` ? 'ti-check text-success' : 'ti-clipboard'}`}></i>
                        </button>
                      </div>
                    ) : null}
                  </div>
                </td>
                <td>{client.requests.toLocaleString()}</td>
                <td>{client.rateLimitSecond != null || client.rateLimitMinute != null ? `${client.rateLimitSecond ?? 0}/s · ${client.rateLimitMinute ?? 0}/m` : 'Limitlar sozlanmagan'}</td>
                <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={client.active} onChange={() => patch(client.toggleUrl)} /></div></td>
                <td>
                  <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1" onClick={() => { setEditing(client); setShowForm(true); }}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-sm btn-light-secondary me-1" title="Webhooklar" onClick={() => { setSelectedEvents([]); setWebhookClientId(client.id); }}>
                    <i className="ti ti-broadcast"></i>
                    {(client.webhooks?.length || 0) > 0 ? <span className="badge bg-secondary ms-1 f-s-9">{client.webhooks!.length}</span> : null}
                  </button>
                  <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22 me-1" title="Secret kalitni qayta yaratish" onClick={() => { if (confirm(`${client.name} uchun API kalit qayta yaratilsinmi? Eski kalit darhol ishlamay qoladi.`)) patch(client.regenerateUrl); }}><i className="ti ti-repeat"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(client)}><i className="ti ti-trash"></i></button>
                </td>
              </tr>
            ))}</tbody>
          </table></div>
        </div>
</div>
      <Modal show={showLogs} onHide={() => setShowLogs(false)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">API Logs (oxirgi 50 ta)</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle">
            <thead><tr><th>Method</th><th>Path</th><th>Status</th><th>Vaqt</th><th>Client</th></tr></thead>
            <tbody>{apiLogs.map((log) => (
              <tr key={log.id}>
                <td><span className={`badge ${log.method === 'GET' ? 'text-light-success' : log.method === 'POST' ? 'text-light-info' : 'text-light-danger'} f-s-9 font-monospace`}>{log.method}</span></td>
                <td className="f-w-600 f-s-11">{log.path}</td>
                <td><span className={`badge ${log.status < 300 ? 'text-light-success' : log.status < 400 ? 'text-light-warning' : 'text-light-danger'} f-s-9`}>{log.status}</span></td>
                <td className="text-muted f-s-13">{log.date}</td>
                <td className="text-muted f-s-13">{log.client}</td>
              </tr>
            ))}</tbody>
          </table></div>
        </Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={() => setShowLogs(false)}>Yopish</Button></Modal.Footer>
      </Modal>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editing ? 'API mijozni tahrirlash' : "API mijoz qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Label>Nomi</Form.Label><Form.Control name="name" required defaultValue={editing?.name || ''} className="mb-3" />
            <Form.Label>Abilities</Form.Label><Form.Control name="abilities" placeholder="read, stock:write" defaultValue={editing?.abilities || 'read'} className="mb-1" />
            <div className="text-muted f-s-13 mb-3">Seller integratsiyasi uchun: <code>read, stock:write</code></div>
            <Form.Label>Seller ID <span className="text-muted">(ixtiyoriy — kalitni do'konga bog'lash)</span></Form.Label>
            <Form.Control name="seller_id" type="number" min={1} placeholder="Masalan: 12" defaultValue={editing?.sellerId ?? ''} className="mb-3" />
            <Form.Label>Ruxsat etilgan IP'lar <span className="text-muted">(ixtiyoriy, vergul/qator bilan; bo'sh = hamma)</span></Form.Label>
            <Form.Control name="allowed_ips" as="textarea" rows={2} placeholder="203.0.113.10, 10.0.0.0/24" defaultValue={editing?.allowedIps || ''} className="mb-3" />
            <div className="row g-3 mb-3">
              <div className="col-6"><Form.Label>Limit / sekund</Form.Label><Form.Control name="rate_limit_per_second" type="number" min={1} defaultValue={editing?.rateLimitSecond || 8} /></div>
              <div className="col-6"><Form.Label>Limit / minut</Form.Label><Form.Control name="rate_limit_per_minute" type="number" min={1} defaultValue={editing?.rateLimitMinute || 240} /></div>
            </div>
            <Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.active : true} />
          </Modal.Body>
          <Modal.Footer><Button variant="light-secondary" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
      <Modal show={webhookClient !== null} onHide={() => setWebhookClientId(null)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title className="f-s-20 f-w-600">Webhooklar — {webhookClient?.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {(webhookClient?.webhooks || []).length ? (
            <div className="table-responsive app-scroll mb-4"><table className="table table-bottom-border align-middle">
              <thead><tr><th>URL</th><th>Hodisalar</th><th>Xato</th><th>Holat</th><th></th></tr></thead>
              <tbody>{(webhookClient?.webhooks || []).map((w) => (
                <tr key={w.id}>
                  <td className="f-s-11"><code>{w.url}</code></td>
                  <td>{w.events.map((e) => <span key={e} className="badge text-light-info me-1 mb-1 f-s-9">{e}</span>)}</td>
                  <td>{w.failures > 0 ? <span className="badge text-light-danger f-s-9">{w.failures}</span> : <span className="text-muted">—</span>}</td>
                  <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={w.active} onChange={() => patch(w.toggleUrl)} /></div></td>
                  <td><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => removeWebhook(w.destroyUrl)}><i className="ti ti-trash"></i></button></td>
                </tr>
              ))}</tbody>
            </table></div>
          ) : <p className="text-muted">Hali webhook obunasi yo'q.</p>}

          <Form onSubmit={addWebhook}>
            <Form.Label>Yangi webhook URL</Form.Label>
            <Form.Control name="url" type="url" placeholder="https://your-server.com/webhooks/kitobchi" required className="mb-3" />
            <Form.Label>Hodisalar</Form.Label>
            <div className="mb-3">
              {(webhookClient?.availableEvents || []).map((ev) => (
                <Form.Check inline key={ev} type="checkbox" id={`ev-${ev}`} label={ev} checked={selectedEvents.includes(ev)} onChange={() => toggleEvent(ev)} />
              ))}
            </div>
            <div className="text-muted f-s-13 mb-3">Imzo: har yetkazishda <code>X-Kitobchi-Signature: sha256=HMAC(secret, body)</code>. Secret webhook yaratilganda avtomatik beriladi.</div>
            <Button type="submit" className="btn-primary border-0" disabled={selectedEvents.length === 0}>Webhook qo'shish</Button>
          </Form>
        </Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={() => setWebhookClientId(null)}>Yopish</Button></Modal.Footer>
      </Modal>
    </div>
  );
}
