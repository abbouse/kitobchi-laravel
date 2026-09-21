import { FormEvent, InputHTMLAttributes, useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import { LeafletMapPicker, LeafletMapView } from '../components/LeafletMap';

import { StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

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
  const stageList = stages.length ? stages : STAGE_ORDER.map((k) => ({ key: k, label: k, icon: 'ti-point-filled', color: 'rgba(var(--primary), 1)' }));
  const total = STAGE_ORDER.reduce((sum, key) => sum + (pipeline?.[key] || 0), 0);

  if (!pipeline || total === 0) {
    return <div className="f-s-13 text-muted"><i className="ti ti-circle-check me-1 text-success"></i>Ochiq fulfillment yo'q</div>;
  }

  return (
    <div>
      <div className="d-flex b-r-50 overflow-hidden mb-2 h-5" style={{ background: 'rgba(var(--light), .3)' }}>
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
            <span key={stage.key} className="badge" style={{ background: `color-mix(in srgb, ${stage.color} 14%, transparent)`, color: stage.color, fontWeight: 600 }}>
              <i className={`${tiIcon(stage.icon)} me-1`}></i>{value}
            </span>
          );
        })}
        {pipeline.exceptions > 0 ? (
          <span className="badge text-danger f-w-500" style={{ background: 'rgba(var(--danger), .3)' }}>
            <i className="ti ti-alert-triangle me-1"></i>{pipeline.exceptions}
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
      <label className="form-label f-s-13 text-muted f-w-600">{label}</label>
      <input className="form-control" name={name} type={type} min={min} max={max} step={step} required={required} defaultValue={defaultValue ?? ''} placeholder={placeholder} inputMode={inputMode} />
    </div>
  );
}

function Toggle({ name, label, defaultChecked = false }: { name: string; label: string; defaultChecked?: boolean }) {
  return (
    <label className="d-flex align-items-center justify-content-between gap-3 p-3 b-r-8 b-1-light h-100">
      <span className="f-w-600">{label}</span>
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
          <label className="form-label f-s-13 text-muted f-w-600">Joylashuv (xaritadan tanlang)</label>
          <LeafletMapPicker
            lat={coords.lat}
            lon={coords.lon}
            onChange={(next, address) => {
              setCoords({ lat: next.lat ?? '', lon: next.lon ?? '' });
              if (address) setAutoAddress(address);
            }}
            height={300}
          />
          {autoAddress ? <div className="f-s-13 text-muted mt-1"><i className="ti ti-map-pin me-1"></i>{autoAddress}</div> : null}
        </div>
        <div className="col-md-4">
          <label className="form-label f-s-13 text-muted f-w-600">Latitude</label>
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
          <label className="form-label f-s-13 text-muted f-w-600">Longitude</label>
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
          <label className="form-label f-s-13 text-muted f-w-600">Izoh</label>
          <textarea className="form-control" name="meta" rows={3} defaultValue={hub?.notes ?? ''} />
        </div>
      </div>
      <div className="text-end mt-4">
        <button className="btn btn-primary"><i className="ti ti-check me-1"></i>{hub ? 'Saqlash' : "Qo'shish"}</button>
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
          <label className="form-label f-s-13 text-muted f-w-600">Hub</label>
          <select className="form-select" name="hub_id" required defaultValue={staff?.hubId ?? hubs[0]?.id ?? ''}>
            {hubs.map((hub) => <option value={hub.id} key={hub.id}>{hub.name} {hub.code ? `(${hub.code})` : ''}</option>)}
          </select>
        </div>
        <div className="col-md-6">
          <label className="form-label f-s-13 text-muted f-w-600">Rol</label>
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
              <div className="f-w-600 mb-2">Qo'shimcha ruxsatlar</div>
              <div className="row g-2">
                {permissions.map((permission) => (
                  <div className="col-md-6 col-xl-4" key={permission.key}>
                    <label className="d-flex gap-2 p-2 b-r-8 b-1-light h-100 f-s-13">
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
        <button className="btn btn-primary"><i className="ti ti-check me-1"></i>{staff ? 'Saqlash' : "Xodim qo'shish"}</button>
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
          color: hub.active ? 'rgba(var(--success), 1)' : 'rgba(var(--secondary), 1)',
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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Hub Fulfillment</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Fulfillment markazlari, xodimlar, rollar va kuryer vazifalari</p>
        </div>
        <button className="btn btn-primary" onClick={() => setEditingHub(null)}><i className="ti ti-circle-plus me-1"></i>Hub qo'shish</button>
      </div>

      <div className="row">
        {[
          { label: 'Jami hub', value: hubStats.total ?? hubs.length, icon: 'ti-building', color: 'rgba(var(--primary), 1)' },
          { label: 'Faol hub', value: hubStats.active ?? hubs.filter((hub) => hub.active).length, icon: 'ti-circle-check', color: 'rgba(var(--success), 1)' },
          { label: 'Xodimlar', value: hubStats.staff ?? hubStaff.length, icon: 'ti-users', color: 'rgba(var(--primary), 1)' },
          { label: 'Fulfillment', value: fmt(totalFulfillments), icon: 'ti-package', color: 'rgba(var(--warning-dark), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.value} />
        </div>))}
      </div>

      {/* Fulfillment quvuri — global kesim */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Fulfillment quvuri</h5>
            <p className="mb-0 text-secondary">Barcha hublardagi ochiq orderlarning bosqichlari</p>
          </div>
          <span className="badge text-light-info">{fmt(openTotal)} ta ochiq</span>
        </div>
        <div className="card-body">

          <div className="row g-2">
            {/* Quvur bosqichlari: raqam har doim asosiy matn rangida (qorong'i rejimda
                ham o'qiladi), bosqich ulushi esa ostidagi ingichka chiziqda ko'rinadi */}
            {(stages.length ? stages : STAGE_ORDER.map((k) => ({ key: k, label: k, icon: 'ti-point-filled', color: '' }))).map((stage, index) => {
              const value = totals[stage.key] || 0;
              const share = openTotal > 0 ? Math.round((value / openTotal) * 100) : 0;
              return (
                <div className="col-6 col-xl" key={stage.key}>
                  <div className="bg-light-primary b-r-15 p-3 h-100">
                    <p className="text-primary-dark f-w-600 mb-1 f-s-13">{index + 1} · {stage.label}</p>
                    <h4 className="text-primary-dark mb-0">{fmt(value)}</h4>
                    <div className="custom-progress-container mt-2 mb-0"><div className="progress-bar productive" style={{ width: `${value ? Math.max(share, 5) : 0}%` }}></div></div>
                  </div>
                </div>
              );
            })}
            <div className="col-6 col-xl">
              <div className={`b-r-15 p-3 h-100 ${(totals.exceptions || 0) > 0 ? 'bg-light-danger' : 'bg-light-secondary'}`}>
                <p className="f-w-600 mb-1 f-s-13">Exception</p>
                <h4 className="mb-0">{fmt(totals.exceptions || 0)}</h4>
                <div className="custom-progress-container mt-2 mb-0"><div className={`progress-bar ${(totals.exceptions || 0) > 0 ? 'bg-danger' : 'idle'}`} style={{ width: (totals.exceptions || 0) > 0 ? '100%' : '0%' }}></div></div>
              </div>
            </div>
          </div>
          {(totals.delivered || totals.returned) ? (
            <div className="d-flex gap-3 mt-3 f-s-13 text-muted">
              <span><i className="ti ti-circle-check text-success me-1"></i>Yetkazilgan: <strong>{fmt(totals.delivered || 0)}</strong></span>
              <span><i className="ti ti-rotate text-danger me-1"></i>Qaytgan/bekor: <strong>{fmt(totals.returned || 0)}</strong></span>
            </div>
          ) : null}
        </div>
      </div>

      <div className="row">
        <div className="col-xl-7">
          <div className="card h-100">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div><h5 className="f-w-600">Hublar xaritasi</h5><small className="text-muted">Fulfillment markazlarining joylashuvi</small></div>
            </div>
<div className="card-body">

              <LeafletMapView markers={hubMarkers} height={280} />
            </div>
</div>
        </div>
        <div className="col-xl-5">
          <div className="card h-100">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div><h5 className="f-w-600">So'nggi harakatlar</h5><small className="text-muted">Oxirgi fulfillment yangilanishlari</small></div>
            </div>
<div className="card-body">

              <div className="d-flex flex-column gap-2 overflow-y-auto" style={{ maxHeight: 280 }}>
                {recent.length === 0 ? <div className="text-muted f-s-13 text-center py-4">Harakatlar yo'q</div> : null}
                {recent.map((item) => (
                  <div key={item.id} className="d-flex align-items-center justify-content-between gap-2 p-2 b-r-8 b-1-light">
                    <div className="min-w-0">
                      <div className="f-w-600 f-s-13 text-truncate">
                        {item.orderId ? `#${item.orderId}` : `Fulfillment #${item.id}`} · {item.hub}
                      </div>
                      <div className="text-muted f-s-12">{item.updatedAt || ''}</div>
                    </div>
                    <div className="text-end">
                      {item.hasException ? <span className="badge text-danger" style={{ background: 'rgba(var(--danger), .3)' }}>Exception</span> : <span className="badge text-light-secondary">{item.stageLabel}</span>}
                    </div>
                  </div>
                ))}
              </div>
            </div>
</div>
        </div>
      </div>

      <div className="row">
        {hubs.map((hub) => (
          <div className="col-xl-4 col-md-6" key={hub.id}>
            <div className="card h-100">
              <div className="card-body d-flex flex-column">
                <div className="d-flex justify-content-between align-items-start mb-3 gap-2">
                  <div className="d-flex align-items-center gap-3 min-w-0">
                    <div className="h-55 w-55 d-flex-center b-r-50 bg-light-primary f-w-600 f-s-18 overflow-hidden flex-shrink-0"><i className="ti ti-building"></i></div>
                    <div className="min-w-0">
                      <div className="f-w-600 text-truncate">{hub.name}</div>
                      <div className="text-muted f-s-13 text-truncate">{hub.code || hub.city || hub.region || 'Hub'}</div>
                    </div>
                  </div>
                  <span className={`badge ${hub.active ? 'text-light-success' : 'text-light-secondary'}`}>{hub.active ? 'Faol' : 'Nofaol'}</span>
                </div>

                <div className="row g-2 text-center mb-3">
                  <div className="col-4"><div className="f-w-600">{hub.staff || 0}</div><small className="text-muted">Xodim</small></div>
                  <div className="col-4"><div className="f-w-600">{hub.fulfillments || 0}</div><small className="text-muted">Order</small></div>
                  <div className="col-4"><div className="f-w-600">{hub.courierTasks || 0}</div><small className="text-muted">Kuryer</small></div>
                </div>

                <div className="mb-3">
                  <div className="f-s-13 text-muted f-w-600 mb-2">Fulfillment quvuri</div>
                  <PipelineBar pipeline={hub.pipeline} stages={stages} />
                </div>

                <div className="p-2 b-r-8 mb-3 f-s-13 bg-light-secondary">
                  <div className="d-flex justify-content-between gap-3"><span>Manzil</span><strong className="text-end">{hub.city || hub.region || '—'}</strong></div>
                  <div className="d-flex justify-content-between"><span>Priority</span><strong>{hub.priority ?? '—'}</strong></div>
                  <div className="d-flex justify-content-between"><span>Asosiy hub</span><strong>{hub.primary ? 'Ha' : "Yo'q"}</strong></div>
                </div>

                <div className="d-flex gap-2 flex-wrap mb-3">
                  {hub.supportsFirstMile ? <span className="badge text-light-info">First mile</span> : null}
                  {hub.supportsLastMile ? <span className="badge text-light-primary">Last mile</span> : null}
                  {hub.supportsPostal ? <span className="badge text-light-warning">Pochta</span> : null}
                </div>

                <div className="d-flex gap-2 mt-auto">
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setSelectedHub(hub)}><i className="ti ti-eye"></i> Batafsil</button>
                  <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => setEditingHub(hub)}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(hub.destroyUrl, `${hub.name} hub o'chirilsinmi?`)}><i className="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row">
        <div className="col-xl-7">
          <div className="card">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <h5 className="f-w-600">Hub xodimlari</h5>
                <p className="mb-0 text-secondary">A122 dagi rollar va permission katalogi asosida</p>
              </div>
              <button className="btn btn-sm btn-primary" onClick={() => setEditingStaff(null)}><i className="ti ti-user-plus me-1"></i>Xodim</button>
            </div>
<div className="card-body">

              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle">
                  <thead><tr><th>Xodim</th><th>Hub</th><th>Rol</th><th>Ruxsat</th><th>Oxirgi aktivlik</th><th>Holat</th><th></th></tr></thead>
                  <tbody>
                    {hubStaff.map((staff) => (
                      <tr key={staff.id}>
                        <td><div className="f-w-600">{staff.name}</div><small className="text-muted">{staff.username} · {staff.phone || 'telefon yoq'}</small></td>
                        <td>{staff.hub || '—'}<div className="f-s-13 text-muted">{staff.hubCode}</div></td>
                        <td><span className="badge text-light-primary">{hubRoles.find((role) => role.value === staff.role)?.label || staff.role}</span></td>
                        <td>{(staff.effectivePermissions?.length || staff.permissions?.length || 0)} ta</td>
                        <td className="text-muted">{staff.lastSeenAt || '—'}</td>
                        <td><span className={`badge ${staff.active ? 'text-light-success' : 'text-light-secondary'}`}>{staff.active ? 'Faol' : 'Nofaol'}</span></td>
                        <td className="text-end">
                          <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1" onClick={() => setEditingStaff(staff)}><i className="ti ti-pencil"></i></button>
                          <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => toggle(staff.toggleUrl)}><i className="ti ti-power"></i></button>
                          <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => resetPassword(staff)}><i className="ti ti-key"></i></button>
                        </td>
                      </tr>
                    ))}
                    {hubStaff.length === 0 ? <tr><td colSpan={7} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hub xodimlari yo'q</td></tr> : null}
                  </tbody>
                </table>
              </div>
            </div>
</div>
        </div>
        <div className="col-xl-5">
          <div className="card">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div>
                <h5 className="f-w-600">Rollar va vakolatlar</h5>
                <p className="mb-0 text-secondary">Hub operatsion rollari uchun default ruxsatlar</p>
              </div>
              <span className="badge text-light-info">{hubPermissions.length} permission</span>
            </div>
<div className="card-body">

              <div className="d-flex flex-column gap-2">
                {hubRoles.map((role) => (
                  <div className="p-3 b-r-8 b-1-light" key={role.value}>
                    <div className="d-flex justify-content-between gap-3 mb-1">
                      <strong>{role.label}</strong>
                      <span className="f-w-600 text-nowrap">{role.permissions?.length || 0}</span>
                    </div>
                    <div className="f-s-13 text-muted mb-2">{role.description || role.value}</div>
                    <div className="d-flex flex-wrap gap-1">
                      {(role.permissions || []).slice(0, 6).map((permission) => <span className="badge text-light-info" key={permission}>{permission}</span>)}
                      {(role.permissions?.length || 0) > 6 ? <span className="badge text-light-secondary">+{(role.permissions?.length || 0) - 6}</span> : null}
                    </div>
                  </div>
                ))}
              </div>
            </div>
</div>
        </div>
      </div>

      <div className="card mt-3">
<div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Fulfillment nazorati</h5>
            <p className="mb-0 text-secondary">Hub orderlari va kuryer vazifalarining operatsion kesimi</p>
          </div>
          <span className="badge text-light-info">{totalCourierTasks} ta kuryer vazifasi</span>
        </div>
<div className="card-body">

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>Hub</th><th>Kod</th><th>Hudud</th><th className="text-end text-nowrap">Xodim</th><th className="text-end text-nowrap">Fulfillment</th><th className="text-end text-nowrap">Kuryer task</th><th>Qo'llab-quvvatlaydi</th><th>Amallar</th></tr></thead>
              <tbody>
                {hubs.map((hub) => (
                  <tr key={hub.id}>
                    <td className="f-w-600">{hub.name}</td>
                    <td>{hub.code || '—'}</td>
                    <td>{[hub.city, hub.region].filter(Boolean).join(', ') || '—'}</td>
                    <td className="text-end text-nowrap">{hub.staff || 0}</td>
                    <td className="text-end text-nowrap">{hub.fulfillments || 0}</td>
                    <td className="text-end text-nowrap">{hub.courierTasks || 0}</td>
                    <td>
                      <span className="badge text-light-secondary">
                        {[hub.supportsFirstMile ? 'First' : null, hub.supportsLastMile ? 'Last' : null, hub.supportsPostal ? 'Postal' : null].filter(Boolean).join(' / ') || '—'}
                      </span>
                    </td>
                    <td>
                      <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => setSelectedHub(hub)}><i className="ti ti-eye"></i></button>
                      <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => setEditingHub(hub)}><i className="ti ti-pencil"></i></button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
</div>

      <Modal show={!!selectedHub} onHide={() => setSelectedHub(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{selectedHub?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-3"><small className="text-muted">Kod</small><div className="f-w-600">{selectedHub?.code || '—'}</div></div>
            <div className="col-md-3"><small className="text-muted">Status</small><div><span className={`badge ${selectedHub?.active ? 'text-light-success' : 'text-light-secondary'}`}>{selectedHub?.active ? 'Faol' : 'Faol emas'}</span></div></div>
            <div className="col-md-3"><small className="text-muted">Priority</small><div className="f-w-600">{selectedHub?.priority ?? '—'}</div></div>
            <div className="col-md-3"><small className="text-muted">Koordinata</small><div>{[selectedHub?.lat, selectedHub?.lon].filter(Boolean).join(', ') || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Manzil</small><div>{selectedHub?.address || '—'}</div></div>
            {selectedHub?.lat && selectedHub?.lon ? (
              <div className="col-12">
                <LeafletMapView markers={[{ lat: selectedHub.lat, lon: selectedHub.lon, label: selectedHub.name, color: selectedHub.active ? 'rgba(var(--success), 1)' : 'rgba(var(--secondary), 1)' }]} height={220} />
              </div>
            ) : null}
            {selectedHub?.pipeline ? (
              <div className="col-12">
                <p className="mb-0 text-secondary">Fulfillment quvuri</p>
                <div className="mt-1"><PipelineBar pipeline={selectedHub.pipeline} stages={hubFulfillment.stages ?? []} /></div>
              </div>
            ) : null}
            <div className="col-md-4"><small className="text-muted">Xodim</small><div className="f-w-600">{selectedHub?.staff || 0}</div></div>
            <div className="col-md-4"><small className="text-muted">Fulfillment</small><div className="f-w-600">{selectedHub?.fulfillments || 0}</div></div>
            <div className="col-md-4"><small className="text-muted">Kuryer</small><div className="f-w-600">{selectedHub?.courierTasks || 0}</div></div>
            <div className="col-12"><small className="text-muted">Izoh</small><div>{selectedHub?.notes || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={() => setSelectedHub(null)}>Yopish</Button>
          {selectedHub ? <Button variant="primary" onClick={() => { setEditingHub(selectedHub); setSelectedHub(null); }}>Tahrirlash</Button> : null}
        </Modal.Footer>
      </Modal>

      <Modal show={editingHub !== undefined} onHide={() => setEditingHub(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editingHub ? 'Hub tahrirlash' : "Yangi hub"}</Modal.Title></Modal.Header>
        <Modal.Body>
          <HubForm hub={editingHub} action={editingHub?.updateUrl || hubActions.storeUrl} onDone={() => setEditingHub(undefined)} />
        </Modal.Body>
      </Modal>

      <Modal show={editingStaff !== undefined} onHide={() => setEditingStaff(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{editingStaff ? 'Xodim tahrirlash' : "Yangi hub xodimi"}</Modal.Title></Modal.Header>
        <Modal.Body>
          <StaffForm staff={editingStaff} hubs={hubs} roles={hubRoles} permissions={hubPermissions} action={editingStaff?.updateUrl || hubActions.staffStoreUrl} onDone={() => setEditingStaff(undefined)} />
        </Modal.Body>
      </Modal>
    </div>
  );
}
