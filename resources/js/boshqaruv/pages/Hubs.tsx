import { FormEvent, InputHTMLAttributes, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import { LeafletMapPicker, LeafletMapView } from '../components/LeafletMap';

interface HubPipeline {
  inbound: number;
  qc: number;
  packing: number;
  dispatch: number;
  delivery: number;
  exceptions: number;
  open: number;
}

interface Hub {
  id: number;
  name: string;
  code?: string;
  country?: string;
  region?: string;
  city?: string;
  address?: string;
  lat?: number | string | null;
  lon?: number | string | null;
  active?: boolean;
  primary?: boolean;
  priority?: number;
  staff?: number;
  fulfillments?: number;
  courierTasks?: number;
  supportsFirstMile?: boolean;
  supportsLastMile?: boolean;
  supportsPostal?: boolean;
  notes?: string | null;
  pipeline?: HubPipeline;
  updateUrl?: string;
  destroyUrl?: string;
}

interface FulfillmentStage {
  key: string;
  label: string;
  icon: string;
  color: string;
}

interface FulfillmentRecent {
  id: number;
  orderId?: number | null;
  hub: string;
  hubCode?: string;
  status: string;
  stage?: string | null;
  stageLabel: string;
  hasException: boolean;
  updatedAt?: string;
}

interface HubFulfillment {
  stages?: FulfillmentStage[];
  totals?: Record<string, number>;
  recent?: FulfillmentRecent[];
}

interface HubStaff {
  id: number;
  hubId?: number;
  hub?: string;
  hubCode?: string;
  name: string;
  username: string;
  phone?: string;
  role: string;
  active?: boolean;
  permissions?: string[];
  effectivePermissions?: string[];
  lastSeenAt?: string;
  updateUrl?: string;
  toggleUrl?: string;
  resetPasswordUrl?: string;
}

interface HubRole {
  value: string;
  label: string;
  description?: string;
  permissions?: string[];
}

interface HubPermission {
  key: string;
  label: string;
  description?: string;
}

interface HubsPayload {
  hubs?: Hub[];
  hubStaff?: HubStaff[];
  hubRoles?: HubRole[];
  hubPermissions?: HubPermission[];
  hubStats?: { total?: number; active?: number; postal?: number; firstMile?: number; staff?: number };
  hubActions?: { storeUrl?: string; staffStoreUrl?: string };
  hubFulfillment?: HubFulfillment;
}

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

const STAGE_ORDER = ['inbound', 'qc', 'packing', 'dispatch', 'delivery'] as const;

// Hub kartasidagi kichik pipeline chizig'i
function PipelineBar({ pipeline, stages }: { pipeline?: HubPipeline; stages: FulfillmentStage[] }) {
  const stageList = stages.length ? stages : STAGE_ORDER.map((k) => ({ key: k, label: k, icon: 'bi-dot', color: '#0B0342' }));
  const total = STAGE_ORDER.reduce((sum, key) => sum + (pipeline?.[key] || 0), 0);

  if (!pipeline || total === 0) {
    return <div className="small text-muted"><i className="bi bi-check2-circle me-1 text-success"></i>Ochiq fulfillment yo'q</div>;
  }

  return (
    <div>
      <div className="d-flex rounded-pill overflow-hidden mb-2" style={{ height: 8, background: '#F1F3F5' }}>
        {stageList.map((stage) => {
          const value = pipeline[stage.key as keyof HubPipeline] || 0;
          if (!value) return null;
          return <div key={stage.key} title={`${stage.label}: ${value}`} style={{ width: `${(value / total) * 100}%`, background: stage.color }} />;
        })}
      </div>
      <div className="d-flex flex-wrap gap-1">
        {stageList.map((stage) => {
          const value = pipeline[stage.key as keyof HubPipeline] || 0;
          if (!value) return null;
          return (
            <span key={stage.key} className="badge rounded-pill" style={{ background: `${stage.color}18`, color: stage.color, fontWeight: 600 }}>
              <i className={`bi ${stage.icon} me-1`}></i>{value}
            </span>
          );
        })}
        {pipeline.exceptions > 0 ? (
          <span className="badge rounded-pill" style={{ background: '#FEE2E2', color: '#DC2626', fontWeight: 600 }}>
            <i className="bi bi-exclamation-triangle me-1"></i>{pipeline.exceptions}
          </span>
        ) : null}
      </div>
    </div>
  );
}

function submitForm(event: FormEvent<HTMLFormElement>, method: 'post' | 'put', url?: string, onDone?: () => void) {
  event.preventDefault();
  if (!url) return;

  const data = Object.fromEntries(new FormData(event.currentTarget).entries());
  const options = { preserveScroll: true, onSuccess: onDone };

  if (method === 'post') {
    router.post(url, data, options);
  } else {
    router.put(url, data, options);
  }
}

function toggle(url?: string) {
  if (url) router.patch(url, {}, { preserveScroll: true });
}

function destroy(url?: string, message = "O'chirilsinmi?") {
  if (url && confirm(message)) router.delete(url, { preserveScroll: true });
}

function TextInput({ name, label, defaultValue, type = 'text', required = false, min, max, step, placeholder, inputMode }: {
  name: string; label: string; defaultValue?: string | number | null; type?: string; required?: boolean; min?: number; max?: number; step?: string; placeholder?: string; inputMode?: InputHTMLAttributes<HTMLInputElement>['inputMode'];
}) {
  return (
    <div>
      <label className="form-label small text-muted fw-semibold">{label}</label>
      <input className="form-control" name={name} type={type} min={min} max={max} step={step} required={required} defaultValue={defaultValue ?? ''} placeholder={placeholder} inputMode={inputMode} />
    </div>
  );
}

function Toggle({ name, label, defaultChecked = false }: { name: string; label: string; defaultChecked?: boolean }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 rounded border h-100">
      <span className="fw-semibold">{label}</span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

function HubForm({ hub, action, onDone }: { hub?: Hub | null; action?: string; onDone: () => void }) {
  const [coords, setCoords] = useState<{ lat: number | string | null; lon: number | string | null }>({
    lat: hub?.lat ?? '',
    lon: hub?.lon ?? '',
  });
  const [autoAddress, setAutoAddress] = useState<string | null>(null);

  return (
    <form onSubmit={(event) => submitForm(event, hub ? 'put' : 'post', action, onDone)}>
      <div className="row g-3">
        <div className="col-md-6"><TextInput name="name" label="Hub nomi" required defaultValue={hub?.name} /></div>
        <div className="col-md-6"><TextInput name="code" label="Kod" required defaultValue={hub?.code} placeholder="TAS-01" /></div>
        <div className="col-md-4"><TextInput name="country_code" label="Mamlakat kodi" required defaultValue={hub?.country || 'UZ'} max={2} /></div>
        <div className="col-md-4"><TextInput name="region_name" label="Viloyat" defaultValue={hub?.region} /></div>
        <div className="col-md-4"><TextInput name="city_name" label="Shahar" defaultValue={hub?.city} /></div>
        <div className="col-12"><TextInput name="address" label="Manzil" defaultValue={hub?.address} /></div>

        <div className="col-12">
          <label className="form-label small text-muted fw-semibold">Joylashuv (xaritadan tanlang)</label>
          <LeafletMapPicker
            lat={coords.lat}
            lon={coords.lon}
            onChange={(next, address) => {
              setCoords({ lat: next.lat ?? '', lon: next.lon ?? '' });
              if (address) setAutoAddress(address);
            }}
            height={300}
          />
          {autoAddress ? <div className="small text-muted mt-1"><i className="bi bi-pin-map me-1"></i>{autoAddress}</div> : null}
        </div>
        <div className="col-md-4">
          <label className="form-label small text-muted fw-semibold">Latitude</label>
          <input
            className="form-control"
            name="lat"
            inputMode="decimal"
            value={coords.lat ?? ''}
            onChange={(e) => setCoords((prev) => ({ ...prev, lat: e.target.value }))}
            placeholder="41.2995"
          />
        </div>
        <div className="col-md-4">
          <label className="form-label small text-muted fw-semibold">Longitude</label>
          <input
            className="form-control"
            name="lon"
            inputMode="decimal"
            value={coords.lon ?? ''}
            onChange={(e) => setCoords((prev) => ({ ...prev, lon: e.target.value }))}
            placeholder="69.2401"
          />
        </div>
        <div className="col-md-4"><TextInput name="priority" label="Priority" type="number" min={0} defaultValue={hub?.priority ?? 100} /></div>
        <div className="col-md-6"><Toggle name="is_active" label="Faol" defaultChecked={hub?.active ?? true} /></div>
        <div className="col-md-6"><Toggle name="is_primary" label="Asosiy hub" defaultChecked={hub?.primary ?? false} /></div>
        <div className="col-md-4"><Toggle name="supports_first_mile" label="First mile" defaultChecked={hub?.supportsFirstMile ?? true} /></div>
        <div className="col-md-4"><Toggle name="supports_last_mile" label="Last mile" defaultChecked={hub?.supportsLastMile ?? true} /></div>
        <div className="col-md-4"><Toggle name="supports_postal_dispatch" label="Pochta dispatch" defaultChecked={hub?.supportsPostal ?? false} /></div>
        <div className="col-12">
          <label className="form-label small text-muted fw-semibold">Izoh</label>
          <textarea className="form-control" name="meta" rows={3} defaultValue={hub?.notes ?? ''} />
        </div>
      </div>
      <div className="text-end mt-4">
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{hub ? 'Saqlash' : "Qo'shish"}</button>
      </div>
    </form>
  );
}

function StaffForm({ staff, hubs, roles, permissions, action, onDone }: {
  staff?: HubStaff | null; hubs: Hub[]; roles: HubRole[]; permissions: HubPermission[]; action?: string; onDone: () => void;
}) {
  const selectedPermissions = new Set(staff?.permissions ?? []);

  return (
    <form onSubmit={(event) => submitForm(event, staff ? 'put' : 'post', action, onDone)}>
      <div className="row g-3">
        <div className="col-md-6">
          <label className="form-label small text-muted fw-semibold">Hub</label>
          <select className="form-select" name="hub_id" required defaultValue={staff?.hubId ?? hubs[0]?.id ?? ''}>
            {hubs.map((hub) => <option value={hub.id} key={hub.id}>{hub.name} {hub.code ? `(${hub.code})` : ''}</option>)}
          </select>
        </div>
        <div className="col-md-6">
          <label className="form-label small text-muted fw-semibold">Rol</label>
          <select className="form-select" name="role" required defaultValue={staff?.role ?? roles[0]?.value ?? 'operator'}>
            {roles.map((role) => <option value={role.value} key={role.value}>{role.label}</option>)}
          </select>
        </div>
        <div className="col-md-6"><TextInput name="full_name" label="F.I.Sh." required defaultValue={staff?.name} /></div>
        <div className="col-md-6"><TextInput name="username" label="Username" required defaultValue={staff?.username} /></div>
        <div className="col-md-6"><TextInput name="phone_number" label="Telefon" defaultValue={staff?.phone} /></div>
        {!staff ? <div className="col-md-6"><TextInput name="password" label="Parol" type="password" required /></div> : null}
        {staff ? (
          <>
            <div className="col-md-6"><Toggle name="is_active" label="Faol" defaultChecked={staff.active ?? true} /></div>
            <div className="col-12">
              <div className="fw-bold mb-2">Qo'shimcha ruxsatlar</div>
              <div className="row g-2">
                {permissions.map((permission) => (
                  <div className="col-md-6 col-xl-4" key={permission.key}>
                    <label className="d-flex gap-2 p-2 rounded border h-100 small">
                      <input className="form-check-input mt-1" type="checkbox" name="permissions[]" value={permission.key} defaultChecked={selectedPermissions.has(permission.key)} />
                      <span><strong>{permission.label}</strong><br /><span className="text-muted">{permission.description || permission.key}</span></span>
                    </label>
                  </div>
                ))}
              </div>
            </div>
          </>
        ) : null}
      </div>
      <div className="text-end mt-4">
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{staff ? 'Saqlash' : "Xodim qo'shish"}</button>
      </div>
    </form>
  );
}

export default function Hubs() {
  const { hubs = [], hubStaff = [], hubRoles = [], hubPermissions = [], hubStats = {}, hubActions = {}, hubFulfillment = {} } = usePage<HubsPayload>().props;
  const [selectedHub, setSelectedHub] = useState<Hub | null>(null);
  const [editingHub, setEditingHub] = useState<Hub | null | undefined>(undefined);
  const [editingStaff, setEditingStaff] = useState<HubStaff | null | undefined>(undefined);

  const totalFulfillments = useMemo(() => hubs.reduce((sum, hub) => sum + (hub.fulfillments || 0), 0), [hubs]);
  const totalCourierTasks = useMemo(() => hubs.reduce((sum, hub) => sum + (hub.courierTasks || 0), 0), [hubs]);

  const stages = hubFulfillment.stages ?? [];
  const totals = hubFulfillment.totals ?? {};
  const recent = hubFulfillment.recent ?? [];
  const openTotal = STAGE_ORDER.reduce((sum, key) => sum + (totals[key] || 0), 0);
  const hubMarkers = useMemo(
    () =>
      hubs
        .filter((hub) => hub.lat && hub.lon)
        .map((hub) => ({
          lat: hub.lat ?? null,
          lon: hub.lon ?? null,
          label: `<strong>${hub.name}</strong><br>${hub.code || ''}`,
          color: hub.active ? '#0F6A46' : '#8A92A2',
        })),
    [hubs],
  );

  const resetPassword = (staff: HubStaff) => {
    const password = prompt(`${staff.name} uchun yangi parol`);
    if (password && staff.resetPasswordUrl) {
      router.post(staff.resetPasswordUrl, { password }, { preserveScroll: true });
    }
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Hub Fulfillment</h1>
          <p className="page-subtitle">Fulfillment markazlari, xodimlar, rollar va kuryer vazifalari</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => setEditingHub(null)}><i className="bi bi-plus-circle me-1"></i>Hub qo'shish</button>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami hub', value: hubStats.total ?? hubs.length, icon: 'bi-building', color: '#0B0342' },
          { label: 'Faol hub', value: hubStats.active ?? hubs.filter((hub) => hub.active).length, icon: 'bi-check-circle', color: '#0F6A46' },
          { label: 'Xodimlar', value: hubStats.staff ?? hubStaff.length, icon: 'bi-people', color: '#4A3A7A' },
          { label: 'Fulfillment', value: fmt(totalFulfillments), icon: 'bi-box-seam', color: '#8A5709' },
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

      {/* Fulfillment quvuri — global kesim */}
      <div className="card-panel mb-4">
        <div className="panel-head">
          <div>
            <div className="panel-title">Fulfillment quvuri</div>
            <small className="text-muted">Barcha hublardagi ochiq orderlarning bosqichlari</small>
          </div>
          <span className="chip chip-info">{fmt(openTotal)} ta ochiq</span>
        </div>
        <div className="row g-2">
          {(stages.length ? stages : STAGE_ORDER.map((k) => ({ key: k, label: k, icon: 'bi-dot', color: '#0B0342' }))).map((stage) => (
            <div className="col-6 col-xl" key={stage.key}>
              <div className="p-3 rounded-3 h-100" style={{ background: `${stage.color}12` }}>
                <div className="d-flex align-items-center gap-2 mb-1" style={{ color: stage.color }}>
                  <i className={`bi ${stage.icon}`}></i>
                  <span className="fw-bold fs-4">{fmt(totals[stage.key] || 0)}</span>
                </div>
                <div className="small text-muted">{stage.label}</div>
              </div>
            </div>
          ))}
          <div className="col-6 col-xl">
            <div className="p-3 rounded-3 h-100" style={{ background: (totals.exceptions || 0) > 0 ? '#FEE2E2' : '#F1F5F9' }}>
              <div className="d-flex align-items-center gap-2 mb-1" style={{ color: (totals.exceptions || 0) > 0 ? '#DC2626' : '#64748B' }}>
                <i className="bi bi-exclamation-triangle"></i>
                <span className="fw-bold fs-4">{fmt(totals.exceptions || 0)}</span>
              </div>
              <div className="small text-muted">Exception</div>
            </div>
          </div>
        </div>
        {(totals.delivered || totals.returned) ? (
          <div className="d-flex gap-3 mt-3 small text-muted">
            <span><i className="bi bi-check-circle text-success me-1"></i>Yetkazilgan: <strong>{fmt(totals.delivered || 0)}</strong></span>
            <span><i className="bi bi-arrow-counterclockwise text-danger me-1"></i>Qaytgan/bekor: <strong>{fmt(totals.returned || 0)}</strong></span>
          </div>
        ) : null}
      </div>

      <div className="row g-3 mb-4">
        <div className="col-xl-7">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div><div className="panel-title">Hublar xaritasi</div><small className="text-muted">Fulfillment markazlarining joylashuvi</small></div>
            </div>
            <LeafletMapView markers={hubMarkers} height={280} />
          </div>
        </div>
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div><div className="panel-title">So'nggi harakatlar</div><small className="text-muted">Oxirgi fulfillment yangilanishlari</small></div>
            </div>
            <div className="d-flex flex-column gap-2" style={{ maxHeight: 280, overflowY: 'auto' }}>
              {recent.length === 0 ? <div className="text-muted small text-center py-4">Harakatlar yo'q</div> : null}
              {recent.map((item) => (
                <div key={item.id} className="d-flex align-items-center justify-content-between gap-2 p-2 rounded border">
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-semibold small text-truncate">
                      {item.orderId ? `#${item.orderId}` : `Fulfillment #${item.id}`} · {item.hub}
                    </div>
                    <div className="text-muted" style={{ fontSize: 12 }}>{item.updatedAt || ''}</div>
                  </div>
                  <div className="text-end">
                    {item.hasException ? <span className="chip" style={{ background: '#FEE2E2', color: '#DC2626' }}>Exception</span> : <span className="chip chip-gray">{item.stageLabel}</span>}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {hubs.map((hub) => (
          <div className="col-xl-4 col-md-6" key={hub.id}>
            <div className="card-panel h-100 d-flex flex-column">
              <div className="d-flex justify-content-between align-items-start mb-3 gap-2">
                <div className="d-flex align-items-center gap-3" style={{ minWidth: 0 }}>
                  <div className="resource-avatar"><i className="bi bi-building"></i></div>
                  <div style={{ minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{hub.name}</div>
                    <div className="text-muted small text-truncate">{hub.code || hub.city || hub.region || 'Hub'}</div>
                  </div>
                </div>
                <span className={`chip ${hub.active ? 'chip-success' : 'chip-gray'}`}>{hub.active ? 'Faol' : 'Nofaol'}</span>
              </div>

              <div className="row g-2 text-center mb-3">
                <div className="col-4"><div className="fw-bold text-primary">{hub.staff || 0}</div><small className="text-muted">Xodim</small></div>
                <div className="col-4"><div className="fw-bold text-success">{hub.fulfillments || 0}</div><small className="text-muted">Order</small></div>
                <div className="col-4"><div className="fw-bold text-warning">{hub.courierTasks || 0}</div><small className="text-muted">Kuryer</small></div>
              </div>

              <div className="mb-3">
                <div className="small text-muted fw-semibold mb-2">Fulfillment quvuri</div>
                <PipelineBar pipeline={hub.pipeline} stages={stages} />
              </div>

              <div className="p-2 rounded mb-3 small bg-light">
                <div className="d-flex justify-content-between gap-3"><span>Manzil</span><strong className="text-end">{hub.city || hub.region || '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Priority</span><strong>{hub.priority ?? '—'}</strong></div>
                <div className="d-flex justify-content-between"><span>Asosiy hub</span><strong>{hub.primary ? 'Ha' : "Yo'q"}</strong></div>
              </div>

              <div className="d-flex gap-2 flex-wrap mb-3">
                {hub.supportsFirstMile ? <span className="chip chip-info">First mile</span> : null}
                {hub.supportsLastMile ? <span className="chip chip-purple">Last mile</span> : null}
                {hub.supportsPostal ? <span className="chip chip-warning">Pochta</span> : null}
              </div>

              <div className="d-flex gap-2 mt-auto">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setSelectedHub(hub)}><i className="bi bi-eye"></i> Batafsil</button>
                <button className="btn btn-sm btn-light" onClick={() => setEditingHub(hub)}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(hub.destroyUrl, `${hub.name} hub o'chirilsinmi?`)}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3">
        <div className="col-xl-7">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Hub xodimlari</div>
                <small className="text-muted">A122 dagi rollar va permission katalogi asosida</small>
              </div>
              <button className="btn btn-sm btn-primary-gradient" onClick={() => setEditingStaff(null)}><i className="bi bi-person-plus me-1"></i>Xodim</button>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead><tr><th>Xodim</th><th>Hub</th><th>Rol</th><th>Ruxsat</th><th>Oxirgi aktivlik</th><th>Holat</th><th></th></tr></thead>
                <tbody>
                  {hubStaff.map((staff) => (
                    <tr key={staff.id}>
                      <td><div className="fw-semibold">{staff.name}</div><small className="text-muted">{staff.username} · {staff.phone || 'telefon yoq'}</small></td>
                      <td>{staff.hub || '—'}<div className="small text-muted">{staff.hubCode}</div></td>
                      <td><span className="chip chip-purple">{hubRoles.find((role) => role.value === staff.role)?.label || staff.role}</span></td>
                      <td>{(staff.effectivePermissions?.length || staff.permissions?.length || 0)} ta</td>
                      <td className="text-muted">{staff.lastSeenAt || '—'}</td>
                      <td><span className={`chip ${staff.active ? 'chip-success' : 'chip-gray'}`}>{staff.active ? 'Faol' : 'Nofaol'}</span></td>
                      <td className="text-end">
                        <button className="btn btn-sm btn-light me-1" onClick={() => setEditingStaff(staff)}><i className="bi bi-pencil"></i></button>
                        <button className="btn btn-sm btn-light me-1" onClick={() => toggle(staff.toggleUrl)}><i className="bi bi-power"></i></button>
                        <button className="btn btn-sm btn-light" onClick={() => resetPassword(staff)}><i className="bi bi-key"></i></button>
                      </td>
                    </tr>
                  ))}
                  {hubStaff.length === 0 ? <tr><td colSpan={7} className="text-center text-muted py-4">Hub xodimlari yo'q</td></tr> : null}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div className="col-xl-5">
          <div className="card-panel">
            <div className="panel-head">
              <div>
                <div className="panel-title">Rollar va vakolatlar</div>
                <small className="text-muted">Hub operatsion rollari uchun default ruxsatlar</small>
              </div>
              <span className="chip chip-info">{hubPermissions.length} permission</span>
            </div>
            <div className="d-flex flex-column gap-2">
              {hubRoles.map((role) => (
                <div className="p-3 rounded border" key={role.value}>
                  <div className="d-flex justify-content-between gap-3 mb-1">
                    <strong>{role.label}</strong>
                    <span className="chip chip-gray">{role.permissions?.length || 0}</span>
                  </div>
                  <div className="small text-muted mb-2">{role.description || role.value}</div>
                  <div className="d-flex flex-wrap gap-1">
                    {(role.permissions || []).slice(0, 6).map((permission) => <span className="chip chip-info" key={permission}>{permission}</span>)}
                    {(role.permissions?.length || 0) > 6 ? <span className="chip chip-gray">+{(role.permissions?.length || 0) - 6}</span> : null}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="card-panel mt-3">
        <div className="panel-head">
          <div>
            <div className="panel-title">Fulfillment nazorati</div>
            <small className="text-muted">Hub orderlari va kuryer vazifalarining operatsion kesimi</small>
          </div>
          <span className="chip chip-info">{totalCourierTasks} ta kuryer vazifasi</span>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>Hub</th><th>Kod</th><th>Hudud</th><th>Xodim</th><th>Fulfillment</th><th>Kuryer task</th><th>Qo'llab-quvvatlaydi</th><th>Amallar</th></tr></thead>
            <tbody>
              {hubs.map((hub) => (
                <tr key={hub.id}>
                  <td className="fw-semibold">{hub.name}</td>
                  <td>{hub.code || '—'}</td>
                  <td>{[hub.city, hub.region].filter(Boolean).join(', ') || '—'}</td>
                  <td>{hub.staff || 0}</td>
                  <td>{hub.fulfillments || 0}</td>
                  <td>{hub.courierTasks || 0}</td>
                  <td>
                    <span className="chip chip-gray">
                      {[hub.supportsFirstMile ? 'First' : null, hub.supportsLastMile ? 'Last' : null, hub.supportsPostal ? 'Postal' : null].filter(Boolean).join(' / ') || '—'}
                    </span>
                  </td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelectedHub(hub)}><i className="bi bi-eye"></i></button>
                    <button className="btn btn-sm btn-light" onClick={() => setEditingHub(hub)}><i className="bi bi-pencil"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selectedHub} onHide={() => setSelectedHub(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selectedHub?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-3"><small className="text-muted">Kod</small><div className="fw-semibold">{selectedHub?.code || '—'}</div></div>
            <div className="col-md-3"><small className="text-muted">Status</small><div><span className={`chip ${selectedHub?.active ? 'chip-success' : 'chip-gray'}`}>{selectedHub?.active ? 'Faol' : 'Faol emas'}</span></div></div>
            <div className="col-md-3"><small className="text-muted">Priority</small><div className="fw-semibold">{selectedHub?.priority ?? '—'}</div></div>
            <div className="col-md-3"><small className="text-muted">Koordinata</small><div>{[selectedHub?.lat, selectedHub?.lon].filter(Boolean).join(', ') || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Manzil</small><div>{selectedHub?.address || '—'}</div></div>
            {selectedHub?.lat && selectedHub?.lon ? (
              <div className="col-12">
                <LeafletMapView markers={[{ lat: selectedHub.lat, lon: selectedHub.lon, label: selectedHub.name, color: selectedHub.active ? '#0F6A46' : '#8A92A2' }]} height={220} />
              </div>
            ) : null}
            {selectedHub?.pipeline ? (
              <div className="col-12">
                <small className="text-muted">Fulfillment quvuri</small>
                <div className="mt-1"><PipelineBar pipeline={selectedHub.pipeline} stages={hubFulfillment.stages ?? []} /></div>
              </div>
            ) : null}
            <div className="col-md-4"><small className="text-muted">Xodim</small><div className="fw-bold">{selectedHub?.staff || 0}</div></div>
            <div className="col-md-4"><small className="text-muted">Fulfillment</small><div className="fw-bold">{selectedHub?.fulfillments || 0}</div></div>
            <div className="col-md-4"><small className="text-muted">Kuryer</small><div className="fw-bold">{selectedHub?.courierTasks || 0}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selectedHub?.notes || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setSelectedHub(null)}>Yopish</Button>
          {selectedHub ? <Button variant="primary" onClick={() => { setEditingHub(selectedHub); setSelectedHub(null); }}>Tahrirlash</Button> : null}
        </Modal.Footer>
      </Modal>

      <Modal show={editingHub !== undefined} onHide={() => setEditingHub(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editingHub ? 'Hub tahrirlash' : "Yangi hub"}</Modal.Title></Modal.Header>
        <Modal.Body>
          <HubForm hub={editingHub} action={editingHub?.updateUrl || hubActions.storeUrl} onDone={() => setEditingHub(undefined)} />
        </Modal.Body>
      </Modal>

      <Modal show={editingStaff !== undefined} onHide={() => setEditingStaff(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editingStaff ? 'Xodim tahrirlash' : "Yangi hub xodimi"}</Modal.Title></Modal.Header>
        <Modal.Body>
          <StaffForm staff={editingStaff} hubs={hubs} roles={hubRoles} permissions={hubPermissions} action={editingStaff?.updateUrl || hubActions.staffStoreUrl} onDone={() => setEditingStaff(undefined)} />
        </Modal.Body>
      </Modal>
    </div>
  );
}
