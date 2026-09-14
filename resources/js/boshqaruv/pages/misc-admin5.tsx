import { ChangeEvent, FormEvent, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import { YandexZoneEditor, YandexPreviewMap, resolveZonesForPoint, Ring, ZoneLike, LatLon } from '../components/YandexMap';

// ===== MYSTERY BOX =====
type MysteryBoxPlan = { id: number; name: string; months: number; price: number; booksPerMonth: number; active: boolean; subscribers: number; description?: string; plansUrl?: string; updateUrl?: string; destroyUrl?: string };

export function MysteryBox() {
  const { mysteryBox = { plans: [], subscriptions: [], indexUrl: '/boshqaruv/mystery-box', plansUrl: '/boshqaruv/mystery-box', createPlanUrl: '' } } = usePage<{
    mysteryBox?: {
      plans: MysteryBoxPlan[];
      subscriptions: Array<{ id: number; user: string; phone?: string; plan: string; status: string; statusLabel?: string; nextDelivery?: string; progress?: number; showUrl?: string; pauseUrl?: string; resumeUrl?: string; cancelUrl?: string }>;
      indexUrl: string;
      plansUrl: string;
      createPlanUrl?: string;
    };
  }>().props;
  const [selected, setSelected] = useState<(typeof mysteryBox.subscriptions)[0] | null>(null);
  const [planModal, setPlanModal] = useState<'create' | MysteryBoxPlan | null>(null);

  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true });
  const cancelSubscription = (url?: string) => {
    if (url && confirm("Obuna bekor qilinsinmi? Bu amalni qaytarib bo'lmaydi.")) router.patch(url, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Mystery Box</h1><p className="page-subtitle">{mysteryBox.plans.length} ta plan · {mysteryBox.subscriptions.filter(s => s.status === 'active').length} ta faol obuna</p></div>
        <div className="d-flex gap-2"><button className="btn btn-primary-gradient" onClick={() => setPlanModal('create')}><i className="bi bi-plus-lg me-1"></i>Yangi tarif</button></div>
      </div>

      <div className="row g-3 mb-3">
        {mysteryBox.plans.map(p => (
          <div className="col-xl-4 col-md-6" key={p.id}>
            <div className="card-panel">
              <div className="d-flex justify-content-between align-items-center mb-2">
                <div className="fw-bold fs-5">{p.name}</div>
                <span className={`chip ${p.active ? 'chip-success' : 'chip-gray'}`}>{p.active ? 'Faol' : 'Nofaol'}</span>
              </div>
              <div className="fw-bold text-primary fs-4">{p.price.toLocaleString()} so'm<small className="text-muted fs-6">/oy</small></div>
              <div className="text-muted mb-2">{p.months} oy · {p.booksPerMonth} kitob/oy · {p.subscribers} obunachi</div>
              <div className="d-flex gap-2">
                {p.updateUrl ? <button className="btn btn-sm btn-light" onClick={() => setPlanModal(p)}><i className="bi bi-pencil"></i></button> : null}
                {p.destroyUrl ? <button className="btn btn-sm btn-light text-danger" onClick={() => { if (confirm(`"${p.name}" tarifi o'chirilsinmi?`)) router.delete(p.destroyUrl!, { preserveScroll: true }); }}><i className="bi bi-trash"></i></button> : null}
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="panel-title mb-3">📦 Obunalar</div>
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Plan</th><th>Status</th><th>Keyingi yetkazish</th><th>Amallar</th></tr></thead>
          <tbody>{mysteryBox.subscriptions.map(s => (
            <tr key={s.id}>
              <td className="fw-semibold" style={{ color: '#0B0342' }}>#{s.id}</td>
              <td className="fw-semibold">{s.user}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{s.plan}</span></td>
              <td><span className={`chip ${s.status === 'active' ? 'chip-success' : s.status === 'paused' ? 'chip-warning' : 'chip-gray'}`} style={{ fontSize: 9 }}>{s.statusLabel || s.status}</span></td>
              <td className="text-muted">{s.nextDelivery}</td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(s)}><i className="bi bi-eye"></i></button>
                {s.status === 'active' ? <button className="btn btn-sm btn-warning me-1" onClick={() => patch(s.pauseUrl)}><i className="bi bi-pause-fill"></i></button> : null}
                {s.status === 'paused' ? <button className="btn btn-sm btn-success me-1" onClick={() => patch(s.resumeUrl)}><i className="bi bi-play-fill"></i></button> : null}
                {s.status === 'active' || s.status === 'paused' ? <button className="btn btn-sm btn-light text-danger" onClick={() => cancelSubscription(s.cancelUrl)}><i className="bi bi-x-lg"></i></button> : null}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.user}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Plan</small><div>{selected?.plan}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div>{selected?.statusLabel || selected?.status}</div></div>
            <div className="col-6"><small className="text-muted">Keyingi yetkazish</small><div>{selected?.nextDelivery || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Progress</small><div>{selected?.progress || 0}%</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.status === 'active' || selected?.status === 'paused' ? (
            <Button variant="outline-danger" onClick={() => { cancelSubscription(selected?.cancelUrl); setSelected(null); }}>Obunani bekor qilish</Button>
          ) : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>

      <Modal show={!!planModal} onHide={() => setPlanModal(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{planModal === 'create' ? "Yangi Mystery Box tarifi" : `"${(planModal as MysteryBoxPlan)?.name}" tarifini tahrirlash`}</Modal.Title></Modal.Header>
        {planModal ? (
          <MysteryBoxPlanForm
            plan={planModal === 'create' ? null : planModal}
            action={planModal === 'create' ? (mysteryBox.createPlanUrl || '') : (planModal.updateUrl || '')}
            onDone={() => setPlanModal(null)}
          />
        ) : null}
      </Modal>
    </div>
  );
}

function MysteryBoxPlanForm({ plan, action, onDone }: { plan: MysteryBoxPlan | null; action: string; onDone: () => void }) {
  return (
    <form onSubmit={(event) => submitLogistics(event, plan ? 'put' : 'post', action, onDone)}>
      <Modal.Body>
        <div className="row g-3">
          <div className="col-12">
            <LogisticsInput name="name_uz" label="Tarif nomi" defaultValue={plan?.name} required />
          </div>
          <div className="col-6">
            <label className="form-label small text-muted fw-semibold">Muddat (oy)</label>
            <select className="form-select" name="months" defaultValue={plan?.months ?? 1} disabled={!!plan}>
              <option value={1}>1 oy</option>
              <option value={3}>3 oy</option>
              <option value={6}>6 oy</option>
              <option value={12}>12 oy</option>
            </select>
            {plan ? <div className="form-text">Mavjud tarifda muddat o'zgartirilmaydi.</div> : null}
          </div>
          <div className="col-6">
            <LogisticsInput name="books_per_month" label="Oyiga kitob soni" type="number" min={1} max={10} defaultValue={plan?.booksPerMonth ?? 1} required />
          </div>
          <div className="col-6">
            <LogisticsInput name="price_uzs" label="Narx (so'm/oy)" type="number" min={1000} step="1000" defaultValue={plan?.price ?? ''} required />
          </div>
          <div className="col-6">
            <LogisticsInput name="sort_order" label="Tartib raqami" type="number" min={0} defaultValue={0} help="Kichik raqam avval ko'rsatiladi." />
          </div>
          <div className="col-12">
            <label className="form-label small text-muted fw-semibold">Tavsif</label>
            <textarea className="form-control" name="description_uz" rows={3} defaultValue={plan?.description ?? ''}></textarea>
          </div>
          {plan ? (
            <div className="col-12">
              <LogisticsToggle name="is_active" label="Tarif faol" defaultChecked={plan.active} />
            </div>
          ) : null}
        </div>
      </Modal.Body>
      <Modal.Footer>
        <Button variant="light" onClick={onDone}>Bekor qilish</Button>
        <button type="submit" className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{plan ? 'Saqlash' : "Tarif qo'shish"}</button>
      </Modal.Footer>
    </form>
  );
}

// ===== SOVG'ALAR =====
export function SovgAlar() {
  const { gifts = [] } = usePage<{
    gifts?: Array<{ id: number; name: string; seller?: string; stock: number; priceFrom: number; priceTo: number; status: string; approved: boolean; sold: number; revenue: number; image?: string | null; indexUrl?: string }>;
  }>().props;
  const indexUrl = '/boshqaruv/sovgalar';

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Sovg'alar</h1><p className="page-subtitle">Jami {gifts.length} ta sovg'a mahsuloti</p></div>
      </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th></th><th>Nomi</th><th>Seller</th><th>Narx</th><th>Ombor</th><th>Sotilgan</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{gifts.map(gift => (
            <tr key={gift.id}>
              <td><div className="thumb">{gift.image ? <img src={gift.image} alt={gift.name} /> : <i className="bi bi-gift"></i>}</div></td>
              <td className="fw-semibold">{gift.name}</td>
              <td>{gift.seller || '—'}</td>
              <td>{gift.priceFrom.toLocaleString()} - {gift.priceTo.toLocaleString()} so'm</td>
              <td>{gift.stock}</td>
              <td>{gift.sold}</td>
              <td><span className={`chip ${gift.approved ? 'chip-success' : 'chip-warning'}`}>{gift.approved ? gift.status : 'Moderatsiya'}</span></td>
              <td><span className="chip chip-gray">Ko'rildi</span></td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}

// ===== LOGISTIKA — YETKAZISH ZONALARI =====
type DeliveryService = {
  id: number;
  name: string;
  type?: string;
  price: number;
  days: number;
  country?: string;
  capital: boolean;
  freeFrom: number;
  active: boolean;
  updateUrl?: string;
  destroyUrl?: string;
};

type DeliveryRule = {
  id: number;
  zoneName: string;
  country?: string;
  scope?: string;
  region?: string;
  district?: string;
  city?: string;
  centerLat?: string | number | null;
  centerLon?: string | number | null;
  radiusKm?: string | number | null;
  polygon?: Array<[number, number]> | null;
  color?: string | null;
  deliveryServiceId?: number;
  service?: string;
  priority?: number;
  basePrice?: number;
  additionalSellerPercent?: number;
  freePriceFrom?: number;
  etaDays?: number;
  codAllowed?: boolean;
  active?: boolean;
  notes?: string | null;
  updateUrl?: string;
  destroyUrl?: string;
};

type LogisticsPayload = {
  deliveryServices?: DeliveryService[];
  deliveryRules?: DeliveryRule[];
  logisticsStats?: { services?: number; activeServices?: number; rules?: number; polygonRules?: number; radiusRules?: number; codRules?: number };
  logisticsFilters?: {
    codFilter?: string;
    previewLat?: string;
    previewLon?: string;
    previewAddress?: string;
    previewCountry?: string;
    previewSellerCount?: string | number;
    previewTotalSum?: string | number;
  };
  logisticsPreview?: null | {
    location?: { lat?: number; lon?: number; address?: string; country?: string };
    sellerCount?: number;
    totalSum?: number;
    offers?: Array<{ service?: string; price?: number; etaDays?: number; codAllowed?: boolean; rule?: string }>;
  };
  logisticsActions?: { ruleStoreUrl?: string; serviceStoreUrl?: string };
};

function submitLogistics(event: FormEvent<HTMLFormElement>, method: 'get' | 'post' | 'put', url: string, onDone?: () => void) {
  event.preventDefault();
  const data = Object.fromEntries(new FormData(event.currentTarget).entries());

  if (method === 'get') {
    router.get(url, data, { preserveScroll: true, preserveState: true });
  } else if (method === 'post') {
    router.post(url, data, { preserveScroll: true, onSuccess: onDone });
  } else {
    router.put(url, data, { preserveScroll: true, onSuccess: onDone });
  }
}

function removeLogistics(url?: string, label = "O'chirilsinmi?") {
  if (url && confirm(label)) router.delete(url, { preserveScroll: true });
}

function LogisticsInput({ name, label, defaultValue, type = 'text', required = false, min, max, step, help, inputMode, value, onChange }: {
  name: string;
  label: string;
  defaultValue?: string | number | null;
  type?: string;
  required?: boolean;
  min?: number;
  max?: number;
  step?: string;
  help?: string;
  inputMode?: 'text' | 'decimal' | 'numeric' | 'search' | 'tel' | 'url' | 'email';
  value?: string | number;
  onChange?: (value: string) => void;
}) {
  const controlProps = onChange
    ? { value: value ?? '', onChange: (event: ChangeEvent<HTMLInputElement>) => onChange(event.target.value) }
    : { defaultValue: defaultValue ?? '' };

  return (
    <div>
      <label className="form-label small text-muted fw-semibold">{label}</label>
      <input className="form-control" name={name} type={type} min={min} max={max} step={step} required={required} inputMode={inputMode} {...controlProps} />
      {help ? <div className="form-text">{help}</div> : null}
    </div>
  );
}

function LogisticsToggle({ name, label, defaultChecked = false }: { name: string; label: string; defaultChecked?: boolean }) {
  return (
    <label className="d-flex align-items-center justify-content-between p-3 rounded border h-100">
      <span className="fw-semibold">{label}</span>
      <span>
        <input type="hidden" name={name} value="0" />
        <input className="form-check-input" type="checkbox" name={name} value="1" defaultChecked={defaultChecked} />
      </span>
    </label>
  );
}

const tileSize = 256;
const earthRadiusMeters = 6378137;

function clampLatitude(value: number) {
  return Math.min(85.05112878, Math.max(-85.05112878, value));
}

function lonToWorldX(lon: number, zoom: number) {
  return ((lon + 180) / 360) * tileSize * 2 ** zoom;
}

function latToWorldY(lat: number, zoom: number) {
  const safeLat = clampLatitude(lat);
  const sinLat = Math.sin((safeLat * Math.PI) / 180);
  return (0.5 - Math.log((1 + sinLat) / (1 - sinLat)) / (4 * Math.PI)) * tileSize * 2 ** zoom;
}

function worldXToLon(x: number, zoom: number) {
  return (x / (tileSize * 2 ** zoom)) * 360 - 180;
}

function worldYToLat(y: number, zoom: number) {
  const n = Math.PI - (2 * Math.PI * y) / (tileSize * 2 ** zoom);
  return (180 / Math.PI) * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
}

function metersPerPixel(lat: number, zoom: number) {
  return (Math.cos((lat * Math.PI) / 180) * 2 * Math.PI * earthRadiusMeters) / (tileSize * 2 ** zoom);
}

function cartoTileUrl(x: number, y: number, zoom: number) {
  const server = ['a', 'b', 'c'][(Math.abs(x + y) % 3)];
  const retinaSuffix = typeof window !== 'undefined' && window.devicePixelRatio > 1 ? '@2x' : '';
  return `https://${server}.basemaps.cartocdn.com/light_all/${zoom}/${x}/${y}${retinaSuffix}.png`;
}

function buildMapTiles(centerLat: number, centerLon: number, zoom: number, width: number, height: number) {
  const centerX = lonToWorldX(centerLon, zoom);
  const centerY = latToWorldY(centerLat, zoom);
  const startTileX = Math.floor((centerX - width / 2) / tileSize);
  const endTileX = Math.floor((centerX + width / 2) / tileSize);
  const startTileY = Math.floor((centerY - height / 2) / tileSize);
  const endTileY = Math.floor((centerY + height / 2) / tileSize);
  const tileCount = 2 ** zoom;
  const tiles: Array<{ left: number; top: number; url: string; key: string }> = [];

  for (let x = startTileX; x <= endTileX; x += 1) {
    for (let y = startTileY; y <= endTileY; y += 1) {
      if (y < 0 || y >= tileCount) continue;
      const wrappedX = ((x % tileCount) + tileCount) % tileCount;
      tiles.push({
        left: x * tileSize - centerX + width / 2,
        top: y * tileSize - centerY + height / 2,
        url: cartoTileUrl(wrappedX, y, zoom),
        key: `${zoom}-${x}-${y}`,
      });
    }
  }

  return { centerX, centerY, tiles };
}

function toLogisticsDecimal(value: string | number | null | undefined, fallback = '') {
  if (value === null || value === undefined || value === '') return fallback;
  return String(value).replace(',', '.');
}

function RadiusMapPicker({
  lat,
  lon,
  radius,
  onLat,
  onLon,
  onRadius,
}: {
  lat: string;
  lon: string;
  radius: string;
  onLat: (value: string) => void;
  onLon: (value: string) => void;
  onRadius: (value: string) => void;
}) {
  const [zoom, setZoom] = useState(11);
  const numericLat = Number.parseFloat(lat || '41.3111');
  const numericLon = Number.parseFloat(lon || '69.2797');
  const numericRadius = Math.max(1, Number.parseFloat(radius || '28'));
  const width = 760;
  const height = 280;
  const { centerX, centerY, tiles } = buildMapTiles(numericLat, numericLon, zoom, width, height);

  const radiusPx = Math.max(12, (numericRadius * 1000) / metersPerPixel(numericLat, zoom));

  const setPreset = (presetLat: number, presetLon: number, presetRadius: number) => {
    onLat(presetLat.toFixed(6));
    onLon(presetLon.toFixed(6));
    onRadius(String(presetRadius));
  };

  const handlePick = (event: React.MouseEvent<HTMLDivElement>) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const clickY = event.clientY - rect.top;
    const pickedLon = worldXToLon(centerX + clickX - rect.width / 2, zoom);
    const pickedLat = worldYToLat(centerY + clickY - rect.height / 2, zoom);
    onLat(pickedLat.toFixed(6));
    onLon(pickedLon.toFixed(6));
  };

  return (
    <div className="rounded-4 border bg-light-subtle p-3">
      <div className="d-flex align-items-start justify-content-between gap-3 mb-3">
        <div>
          <div className="fw-bold">Radius xaritasi</div>
          <div className="small text-muted">Haqiqiy xaritadan markazni bosing, radiusni slider bilan belgilang. Toshkent uchun nom maydonlarini bo'sh qoldirish tavsiya qilinadi.</div>
        </div>
        <div className="d-flex align-items-center gap-2">
          <span className="chip chip-success">{numericRadius.toFixed(numericRadius % 1 === 0 ? 0 : 1)} km</span>
          <div className="btn-group btn-group-sm">
            <button type="button" className="btn btn-light" onClick={() => setZoom((value) => Math.max(5, value - 1))}><i className="bi bi-dash"></i></button>
            <button type="button" className="btn btn-light disabled">{zoom}</button>
            <button type="button" className="btn btn-light" onClick={() => setZoom((value) => Math.min(15, value + 1))}><i className="bi bi-plus"></i></button>
          </div>
        </div>
      </div>

      <div
        role="button"
        tabIndex={0}
        onClick={handlePick}
        className="position-relative overflow-hidden rounded-4 border"
        style={{
          height,
          cursor: 'crosshair',
          background: 'var(--kc-bg-subtle)',
        }}
      >
        {tiles.map((tile) => (
          <img
            alt=""
            draggable={false}
            key={tile.key}
            src={tile.url}
            className="position-absolute"
            style={{ left: tile.left, top: tile.top, width: tileSize, height: tileSize, userSelect: 'none' }}
          />
        ))}
        <div className="position-absolute rounded-pill bg-white border px-2 py-1 small text-muted shadow-sm" style={{ top: 14, left: 14 }}>OpenStreetMap / CARTO</div>
        <div className="position-absolute" style={{ left: '50%', top: '50%', transform: 'translate(-50%, -50%)' }}>
          <div className="position-absolute rounded-circle" style={{
            width: radiusPx * 2,
            height: radiusPx * 2,
            left: -radiusPx,
            top: -radiusPx,
            background: 'rgba(16,185,129,.16)',
            border: '1px solid rgba(16,185,129,.35)',
          }} />
          <div className="position-relative d-flex align-items-center justify-content-center rounded-circle shadow" style={{
            width: 24,
            height: 24,
            background: '#0F6A46',
            color: '#fff',
            border: '3px solid #fff',
          }}>
            <i className="bi bi-geo-alt-fill" style={{ fontSize: 11 }} />
          </div>
        </div>
        <div className="position-absolute small text-muted bg-white bg-opacity-75 rounded-pill px-2 py-1" style={{ right: 10, bottom: 10 }}>
          © OpenStreetMap contributors © CARTO
        </div>
      </div>

      <div className="d-flex flex-wrap gap-2 mt-3">
        <button type="button" className="btn btn-sm btn-light" onClick={() => setPreset(41.311081, 69.240562, 28)}>Toshkent markaz · 28 km</button>
        <button type="button" className="btn btn-sm btn-light" onClick={() => setPreset(41.311081, 69.240562, 40)}>Katta Toshkent · 40 km</button>
        <button type="button" className="btn btn-sm btn-light" onClick={() => setPreset(41.299496, 69.240073, 45)}>Uzoq zona · 45 km</button>
      </div>

      <div className="mt-3">
        <label className="form-label small text-muted fw-semibold">Radius: {numericRadius.toFixed(numericRadius % 1 === 0 ? 0 : 1)} km</label>
        <input className="form-range" type="range" min="1" max="80" step="1" value={Number.isFinite(numericRadius) ? numericRadius : 28} onChange={(event) => onRadius(event.target.value)} />
      </div>
    </div>
  );
}

function DeliveryServiceForm({ service, action, onDone }: { service?: DeliveryService | null; action?: string; onDone: () => void }) {
  if (!action) return null;

  return (
    <form onSubmit={(event) => submitLogistics(event, service ? 'put' : 'post', action, onDone)}>
      <div className="rounded-4 border bg-light-subtle p-3 mb-3">
        <div className="fw-bold mb-1">Xizmat - bu yetkazish kanali</div>
        <div className="small text-muted">Masalan: Kitobchi kuryer, UzPost yoki boshqa pochta. Xizmat bir marta ochiladi, keyin unga bir nechta zona qoidasi ulanadi.</div>
      </div>
      <div className="row g-3">
        <div className="col-md-6"><LogisticsInput name="name" label="Xizmat nomi" required defaultValue={service?.name} /></div>
        <div className="col-md-6">
          <label className="form-label small text-muted fw-semibold">Turi</label>
          <select className="form-select" name="type" required defaultValue={service?.type || 'courier_service'}>
            <option value="courier_service">Kuryer</option>
            <option value="mail_service">Pochta</option>
          </select>
        </div>
        <div className="col-md-4"><LogisticsInput name="priceKg" label="Narx / kg" type="number" min={0} required defaultValue={service?.price ?? 0} /></div>
        <div className="col-md-4"><LogisticsInput name="muddat" label="Muddat (kun)" type="number" min={1} required defaultValue={service?.days ?? 1} /></div>
        <div className="col-md-4"><LogisticsInput name="freePriceFrom" label="Bepuldan" type="number" min={0} defaultValue={service?.freeFrom ?? 0} /></div>
        <div className="col-md-6"><LogisticsInput name="forCountry" label="Mamlakat" required defaultValue={service?.country || 'uzbekistan'} /></div>
        <div className="col-md-3"><LogisticsToggle name="capital" label="Toshkent" defaultChecked={service?.capital ?? false} /></div>
        <div className="col-md-3"><LogisticsToggle name="status" label="Faol" defaultChecked={service?.active ?? true} /></div>
      </div>
      <div className="text-end mt-4">
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{service ? 'Saqlash' : "Xizmat qo'shish"}</button>
      </div>
    </form>
  );
}

function DeliveryRuleForm({ rule, services, action, onDone }: { rule?: DeliveryRule | null; services: DeliveryService[]; action?: string; onDone: () => void }) {
  const [scope, setScope] = useState(rule?.scope || 'polygon');
  const [lat, setLat] = useState(toLogisticsDecimal(rule?.centerLat, '41.311081'));
  const [lon, setLon] = useState(toLogisticsDecimal(rule?.centerLon, '69.240562'));
  const [radius, setRadius] = useState(toLogisticsDecimal(rule?.radiusKm, '28'));
  const [polygon, setPolygon] = useState<Ring | null>(
    Array.isArray(rule?.polygon) && (rule?.polygon?.length ?? 0) >= 3 ? (rule!.polygon as Ring) : null,
  );
  const [color, setColor] = useState(rule?.color || (rule?.scope === 'radius' ? '#0F6A46' : '#0B0342'));

  if (!action) return null;

  const radiusLabel = Math.max(1, Number.parseFloat(radius || '28')).toFixed(Number.parseFloat(radius || '28') % 1 === 0 ? 0 : 1);

  return (
    <form onSubmit={(event) => submitLogistics(event, rule?.updateUrl ? 'put' : 'post', action, onDone)}>
      <div className="rounded-4 border bg-light-subtle p-3 mb-3">
        <div className="fw-bold mb-1">Zona qoidasi — bu xizmat qayerda va qanday narxda ishlashini belgilaydi</div>
        <div className="small text-muted">Bitta xizmatga bir nechta zona qo'shish mumkin. Chegarani <b>polygon</b> qilib xaritada chizing (taksi uslubi) yoki oddiy <b>radius</b> bering.</div>
      </div>
      <div className="row g-3">
        <div className="col-md-5"><LogisticsInput name="zone_name" label="Zona nomi" required defaultValue={rule?.zoneName} /></div>
        <div className="col-md-2"><LogisticsInput name="country_code" label="Mamlakat" required max={2} defaultValue={rule?.country || 'UZ'} /></div>
        <div className="col-md-3">
          <label className="form-label small text-muted fw-semibold">Zona turi</label>
          <select className="form-select" name="scope" required value={scope} onChange={(event) => setScope(event.target.value)}>
            <option value="polygon">Polygon (xaritada chizish)</option>
            <option value="radius">Radius (doira)</option>
            <option value="country">Butun mamlakat</option>
          </select>
        </div>
        <div className="col-md-2">
          <label className="form-label small text-muted fw-semibold">Rang</label>
          <input className="form-control form-control-color w-100" type="color" name="color" value={color} onChange={(event) => setColor(event.target.value)} title="Xaritadagi rangi" />
        </div>
        <div className="col-12">
          <div className="alert alert-light border mb-0">
            <div className="fw-semibold mb-1">Nom maydonlari ixtiyoriy</div>
            <div className="small text-muted">Toshkent kuryer zonalarida viloyat/tuman/shaharni bo'sh qoldiring. Shunda Yandex manzil nomlari ruscha yoki inglizcha kelsa ham geometriya (polygon/radius) bo'yicha to'g'ri ishlaydi.</div>
          </div>
        </div>
        <div className="col-md-4"><LogisticsInput name="region_name" label="Viloyat" help="Faqat nom bo'yicha majburan cheklash kerak bo'lsa yozing." defaultValue={rule?.region} /></div>
        <div className="col-md-4"><LogisticsInput name="district_name" label="Tuman" help="Bo'sh bo'lsa tuman tekshirilmaydi." defaultValue={rule?.district} /></div>
        <div className="col-md-4"><LogisticsInput name="city_name" label="Shahar" help="Bo'sh bo'lsa shahar tekshirilmaydi." defaultValue={rule?.city} /></div>

        {scope === 'polygon' ? (
          <>
            <div className="col-12">
              <div className="rounded-4 border bg-light-subtle p-3">
                <div className="d-flex align-items-start justify-content-between gap-3 mb-2">
                  <div>
                    <div className="fw-bold">Zona chegarasi (polygon)</div>
                    <div className="small text-muted">Kuryer yetkaza oladigan hududni xaritada erkin shakl qilib chizing.</div>
                  </div>
                  <span className={`chip ${polygon && polygon.length >= 3 ? 'chip-purple' : 'chip-gray'}`}>{polygon ? polygon.length : 0} nuqta</span>
                </div>
                <YandexZoneEditor
                  scope="polygon"
                  lat={lat}
                  lon={lon}
                  radiusKm={radius}
                  polygon={polygon}
                  onRadiusChange={() => undefined}
                  onPolygonChange={(points) => setPolygon(points.length >= 3 ? points : null)}
                  height={400}
                />
              </div>
            </div>
            <input type="hidden" name="polygon" value={JSON.stringify(polygon ?? [])} />
            <input type="hidden" name="center_lat" value="" />
            <input type="hidden" name="center_lon" value="" />
            <input type="hidden" name="radius_km" value="" />
          </>
        ) : scope === 'radius' ? (
          <>
            <div className="col-12">
              <div className="rounded-4 border bg-light-subtle p-3">
                <div className="d-flex align-items-start justify-content-between gap-3 mb-2">
                  <div>
                    <div className="fw-bold">Radius xaritasi</div>
                    <div className="small text-muted">Xaritaga bosing yoki markerni sudrang. Manzil qidiruvi ham ishlaydi.</div>
                  </div>
                  <span className="chip chip-success">{radiusLabel} km</span>
                </div>
                <YandexZoneEditor
                  scope="radius"
                  lat={lat}
                  lon={lon}
                  radiusKm={radius}
                  polygon={null}
                  onRadiusChange={(coords) => { setLat(coords[0].toFixed(6)); setLon(coords[1].toFixed(6)); }}
                  onPolygonChange={() => undefined}
                  height={360}
                />
                <div className="d-flex flex-wrap gap-2 mt-3">
                  <button type="button" className="btn btn-sm btn-light" onClick={() => { setLat('41.311081'); setLon('69.240562'); setRadius('28'); }}>Toshkent markaz · 28 km</button>
                  <button type="button" className="btn btn-sm btn-light" onClick={() => { setLat('41.311081'); setLon('69.240562'); setRadius('40'); }}>Katta Toshkent · 40 km</button>
                  <button type="button" className="btn btn-sm btn-light" onClick={() => { setLat('41.299496'); setLon('69.240073'); setRadius('45'); }}>Uzoq zona · 45 km</button>
                </div>
                <div className="mt-3">
                  <label className="form-label small text-muted fw-semibold">Radius: {radiusLabel} km</label>
                  <input className="form-range" type="range" min="1" max="80" step="1" value={Number.isFinite(Number.parseFloat(radius)) ? Number.parseFloat(radius) : 28} onChange={(event) => setRadius(event.target.value)} />
                </div>
              </div>
            </div>
            <div className="col-md-4"><LogisticsInput name="center_lat" label="Markaz latitude" type="text" inputMode="decimal" required value={lat} onChange={(value) => setLat(value.replace(',', '.'))} help="Masalan: 41.311081" /></div>
            <div className="col-md-4"><LogisticsInput name="center_lon" label="Markaz longitude" type="text" inputMode="decimal" required value={lon} onChange={(value) => setLon(value.replace(',', '.'))} help="Masalan: 69.240562" /></div>
            <div className="col-md-4"><LogisticsInput name="radius_km" label="Radius km" type="text" inputMode="decimal" required value={radius} onChange={(value) => setRadius(value.replace(',', '.'))} help="Masalan: 28, 40 yoki 45" /></div>
          </>
        ) : (
          <>
            <input type="hidden" name="center_lat" value="" />
            <input type="hidden" name="center_lon" value="" />
            <input type="hidden" name="radius_km" value="" />
          </>
        )}
        <div className="col-md-6">
          <label className="form-label small text-muted fw-semibold">Yetkazish xizmati</label>
          <select className="form-select" name="delivery_service_id" required defaultValue={rule?.deliveryServiceId ?? services[0]?.id ?? ''}>
            {services.map((service) => <option value={service.id} key={service.id}>{service.name}</option>)}
          </select>
        </div>
        <div className="col-md-3"><LogisticsInput name="priority" label="Priority" type="number" min={0} defaultValue={rule?.priority ?? 100} /></div>
        <div className="col-md-3"><LogisticsInput name="eta_days" label="ETA kun" type="number" min={1} defaultValue={rule?.etaDays ?? 1} /></div>
        <div className="col-md-4"><LogisticsInput name="base_price" label="Base narx" type="number" min={0} defaultValue={rule?.basePrice ?? 0} /></div>
        <div className="col-md-4"><LogisticsInput name="additional_seller_percent" label="Qo'shimcha seller %" type="number" min={0} max={100} step="0.01" defaultValue={rule?.additionalSellerPercent ?? 0} /></div>
        <div className="col-md-4"><LogisticsInput name="free_price_from" label="Bepuldan" type="number" min={0} defaultValue={rule?.freePriceFrom ?? 0} /></div>
        <div className="col-md-6"><LogisticsToggle name="cod_allowed" label="COD ruxsat" defaultChecked={rule?.codAllowed ?? true} /></div>
        <div className="col-md-6"><LogisticsToggle name="is_active" label="Faol" defaultChecked={rule?.active ?? true} /></div>
        <div className="col-12">
          <label className="form-label small text-muted fw-semibold">Izoh</label>
          <textarea className="form-control" name="notes" rows={3} defaultValue={rule?.notes ?? ''} />
        </div>
      </div>
      <div className="text-end mt-4">
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{rule?.updateUrl ? 'Saqlash' : "Qoida qo'shish"}</button>
      </div>
    </form>
  );
}

export function Logistika() {
  const {
    deliveryServices = [],
    deliveryRules = [],
    logisticsStats = {},
    logisticsFilters = {},
    logisticsPreview = null,
    logisticsActions = {},
  } = usePage<LogisticsPayload>().props;
  const indexUrl = '/boshqaruv/logistika';
  const [editingService, setEditingService] = useState<DeliveryService | null | undefined>(undefined);
  const [editingRule, setEditingRule] = useState<DeliveryRule | null | undefined>(undefined);
  const [ruleQuery, setRuleQuery] = useState('');
  const [ruleScope, setRuleScope] = useState<'all' | 'radius' | 'polygon' | 'country'>('all');
  const [serviceQuery, setServiceQuery] = useState('');
  const [previewPoint, setPreviewPoint] = useState<LatLon | null>(null);
  const [sortKey, setSortKey] = useState<'priority' | 'basePrice' | null>(null);
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const courierServices = deliveryServices.filter((service) => service.type === 'courier_service');
  const postalServices = deliveryServices.filter((service) => service.type === 'mail_service');
  const radiusRules = deliveryRules.filter((rule) => rule.scope === 'radius');
  const polygonRules = deliveryRules.filter((rule) => rule.scope === 'polygon');
  const zoneColor = (rule: DeliveryRule) => rule.color || (rule.scope === 'polygon' ? '#0B0342' : '#0F6A46');

  // Client-side resolver uchun to'liq zona ma'lumoti (aqlli preview + overlap)
  const previewZones: ZoneLike[] = deliveryRules.map((rule) => ({
    id: rule.id,
    scope: rule.scope,
    active: rule.active,
    centerLat: rule.centerLat ?? null,
    centerLon: rule.centerLon ?? null,
    radiusKm: rule.radiusKm ?? null,
    polygon: (Array.isArray(rule.polygon) ? rule.polygon : null) as Ring | null,
    priority: rule.priority ?? 0,
    zoneName: rule.zoneName,
    service: rule.service,
    color: zoneColor(rule),
    basePrice: rule.basePrice ?? 0,
    etaDays: rule.etaDays ?? 0,
    codAllowed: rule.codAllowed,
  }));
  const previewMatches = previewPoint ? resolveZonesForPoint(previewZones, previewPoint[0], previewPoint[1]) : [];

  const filteredRules = useMemo(() => {
    const q = ruleQuery.trim().toLowerCase();
    return deliveryRules.filter((rule) => {
      if (ruleScope !== 'all' && (rule.scope || '') !== ruleScope) return false;
      if (!q) return true;
      return (
        (rule.zoneName || '').toLowerCase().includes(q) ||
        (rule.service || '').toLowerCase().includes(q) ||
        [rule.city, rule.district, rule.region, rule.country].filter(Boolean).join(' ').toLowerCase().includes(q)
      );
    });
  }, [deliveryRules, ruleQuery, ruleScope]);

  const filteredServices = useMemo(() => {
    const q = serviceQuery.trim().toLowerCase();
    if (!q) return deliveryServices;
    return deliveryServices.filter((service) =>
      (service.name || '').toLowerCase().includes(q) || (service.type || '').toLowerCase().includes(q) || (service.country || '').toLowerCase().includes(q),
    );
  }, [deliveryServices, serviceQuery]);
  const sortedRules = useMemo(() => {
    if (!sortKey) return filteredRules;
    const dir = sortDir === 'asc' ? 1 : -1;
    return [...filteredRules].sort((a, b) => {
      const av = sortKey === 'priority' ? (a.priority ?? 0) : (a.basePrice ?? 0);
      const bv = sortKey === 'priority' ? (b.priority ?? 0) : (b.basePrice ?? 0);
      return (av - bv) * dir;
    });
  }, [filteredRules, sortKey, sortDir]);

  const toggleSort = (key: 'priority' | 'basePrice') => {
    if (sortKey === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else {
      setSortKey(key);
      setSortDir('desc');
    }
  };
  const sortCaret = (key: 'priority' | 'basePrice') => (sortKey === key ? (sortDir === 'asc' ? ' ▲' : ' ▼') : '');

  const toggleZoneActive = (rule: DeliveryRule) => {
    if (rule.toggleUrl) router.patch(rule.toggleUrl, {}, { preserveScroll: true });
  };

  const duplicateZone = (rule: DeliveryRule) => {
    setEditingRule({ ...rule, id: 0, zoneName: `${rule.zoneName} (nusxa)`, updateUrl: undefined, destroyUrl: undefined, toggleUrl: undefined });
  };

  const exportGeoJson = () => {
    const features = deliveryRules.map((rule) => {
      let geometry: unknown = null;
      if (rule.scope === 'polygon' && Array.isArray(rule.polygon) && rule.polygon.length >= 3) {
        const ring = rule.polygon.map((p) => [p[1], p[0]]); // GeoJSON = [lon, lat]
        ring.push(ring[0]);
        geometry = { type: 'Polygon', coordinates: [ring] };
      } else if (rule.centerLat != null && rule.centerLon != null) {
        geometry = { type: 'Point', coordinates: [Number(rule.centerLon), Number(rule.centerLat)] };
      }
      return {
        type: 'Feature',
        geometry,
        properties: {
          id: rule.id, zone_name: rule.zoneName, scope: rule.scope, service: rule.service,
          radius_km: rule.radiusKm, priority: rule.priority, base_price: rule.basePrice,
          eta_days: rule.etaDays, cod_allowed: rule.codAllowed, active: rule.active, color: rule.color,
        },
      };
    });
    const blob = new Blob([JSON.stringify({ type: 'FeatureCollection', features }, null, 2)], { type: 'application/geo+json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'kitobchi-zonalar.geojson';
    link.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Logistika</h1><p className="page-subtitle">Kuryer yetkazish zonalarini xaritada belgilang — polygon (taksi uslubi) yoki radius.</p></div>
        <div className="d-flex gap-2">
          <button className="btn btn-light" onClick={() => setEditingService(null)}><i className="bi bi-truck me-1"></i>Xizmat</button>
          <button className="btn btn-primary-gradient" onClick={() => setEditingRule(null)}><i className="bi bi-plus-circle me-1"></i>Zona qoidasi</button>
        </div>
      </div>

      <div className="kpi-strip row g-3 mb-4">
        {[
          { label: 'Xizmatlar', value: logisticsStats.services ?? deliveryServices.length, icon: 'bi-truck', color: '#0B0342' },
          { label: 'Faol xizmat', value: logisticsStats.activeServices ?? deliveryServices.filter((item) => item.active).length, icon: 'bi-check-circle', color: '#0F6A46' },
          { label: 'Polygon zona', value: logisticsStats.polygonRules ?? polygonRules.length, icon: 'bi-pentagon', color: '#0B0342' },
          { label: 'Radius zona', value: logisticsStats.radiusRules ?? radiusRules.length, icon: 'bi-record-circle', color: '#0F6A46' },
          { label: 'COD qoidalari', value: logisticsStats.codRules ?? deliveryRules.filter((item) => item.codAllowed).length, icon: 'bi-cash-coin', color: '#4A3A7A' },
        ].map((item) => (
          <div className="col-xl col-md-4 col-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3 mb-4">
        {[
          { step: '1', title: 'Xizmat', icon: 'bi-truck', color: '#0B0342', text: 'Kuryer yoki pochta kanali (Kitobchi kuryer, UzPost). Bir marta ochiladi.' },
          { step: '2', title: 'Zona chegarasi', icon: 'bi-pentagon', color: '#4A3A7A', text: 'Polygon qilib xaritada chizasiz yoki radius berasiz — narx, COD, ETA shu yerda.' },
          { step: '3', title: 'Preview', icon: 'bi-calculator', color: '#0F6A46', text: 'Koordinata kiritib, checkoutda qaysi yetkazish chiqishini oldindan tekshirasiz.' },
        ].map((item) => (
          <div className="col-md-4" key={item.step}>
            <div className="card-panel h-100 d-flex align-items-start gap-3">
              <div className="stat-icon" style={{ flexShrink: 0 }}><i className={`bi ${item.icon}`}></i></div>
              <div>
                <div className="fw-bold">{item.step}. {item.title}</div>
                <div className="small text-muted">{item.text}</div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3 mb-4">
        <div className="col-xl-7">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Zona qoidalari</div>
                <small className="text-muted">A122 resolver ishlatadigan narx, radius, COD va priority sozlamalari</small>
              </div>
              <div className="d-flex gap-2 align-items-center">
                <button type="button" className="btn btn-sm btn-light" onClick={exportGeoJson} title="Zonalarni GeoJSON qilib yuklab olish"><i className="bi bi-download me-1"></i>GeoJSON</button>
                <form className="d-flex gap-2" onSubmit={(event) => submitLogistics(event, 'get', indexUrl)}>
                  <select className="form-select form-select-sm" name="cod_filter" defaultValue={logisticsFilters.codFilter || ''}>
                    <option value="">COD: barchasi</option>
                    <option value="on">COD bor</option>
                    <option value="off">COD yo'q</option>
                  </select>
                  <button className="btn btn-sm btn-light"><i className="bi bi-funnel"></i></button>
                </form>
              </div>
            </div>
            <div className="d-flex flex-wrap gap-2 mb-2">
              <div className="position-relative flex-fill" style={{ minWidth: 180 }}>
                <i className="bi bi-search position-absolute" style={{ left: 12, top: 9, color: 'var(--kc-text-muted)' }}></i>
                <input className="form-control form-control-sm" style={{ paddingLeft: 32 }} placeholder="Zona, xizmat yoki hudud bo'yicha" value={ruleQuery} onChange={(event) => setRuleQuery(event.target.value)} />
              </div>
              <div className="btn-group btn-group-sm">
                {(['all', 'polygon', 'radius', 'country'] as const).map((value) => (
                  <button key={value} type="button" className={`btn ${ruleScope === value ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => setRuleScope(value)}>
                    {value === 'all' ? 'Barchasi' : value === 'polygon' ? 'Polygon' : value === 'radius' ? 'Radius' : 'Mamlakat'}
                  </button>
                ))}
              </div>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead><tr>
                  <th>Zona</th>
                  <th>Scope</th>
                  <th>Xizmat</th>
                  <th style={{ cursor: 'pointer', userSelect: 'none' }} onClick={() => toggleSort('basePrice')}>Narx{sortCaret('basePrice')}</th>
                  <th style={{ cursor: 'pointer', userSelect: 'none' }} onClick={() => toggleSort('priority')}>Priority{sortCaret('priority')}</th>
                  <th>ETA</th>
                  <th>COD</th>
                  <th>Holat</th>
                  <th></th>
                </tr></thead>
                <tbody>{sortedRules.map((rule) => (
                  <tr key={rule.id}>
                    <td>
                      <div className="fw-semibold d-flex align-items-center gap-2">
                        <span style={{ width: 10, height: 10, borderRadius: 999, background: zoneColor(rule), display: 'inline-block', flexShrink: 0 }}></span>
                        {rule.zoneName}
                      </div>
                      <small className="text-muted">{[rule.city, rule.district, rule.region, rule.country].filter(Boolean).join(', ') || '—'}</small>
                    </td>
                    <td>
                      <span className={`chip ${rule.scope === 'polygon' ? 'chip-purple' : rule.scope === 'radius' ? 'chip-success' : 'chip-gray'}`}>{rule.scope || '—'}</span>
                      {rule.scope === 'radius' ? <div className="small text-muted">{rule.radiusKm || 0} km</div> : null}
                      {rule.scope === 'polygon' ? <div className="small text-muted">{Array.isArray(rule.polygon) ? rule.polygon.length : 0} nuqta</div> : null}
                    </td>
                    <td>{rule.service || '—'}</td>
                    <td><strong>{(rule.basePrice || 0).toLocaleString()} so'm</strong><div className="small text-muted">{rule.freePriceFrom ? `${rule.freePriceFrom.toLocaleString()} dan bepul` : 'chegara yoq'}</div></td>
                    <td className="fw-semibold">{rule.priority ?? 0}</td>
                    <td>{rule.etaDays || 0} kun</td>
                    <td><span className={`chip ${rule.codAllowed ? 'chip-success' : 'chip-gray'}`}>{rule.codAllowed ? 'Bor' : "Yo'q"}</span></td>
                    <td>
                      <button type="button" className="btn btn-sm p-0 border-0 bg-transparent" title={rule.active ? 'Nofaol qilish' : 'Faollashtirish'} onClick={() => toggleZoneActive(rule)}>
                        <i className={`bi ${rule.active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted'}`} style={{ fontSize: 20 }}></i>
                      </button>
                    </td>
                    <td className="text-end" style={{ whiteSpace: 'nowrap' }}>
                      <button className="btn btn-sm btn-light me-1" title="Nusxalash" onClick={() => duplicateZone(rule)}><i className="bi bi-files"></i></button>
                      <button className="btn btn-sm btn-light me-1" title="Tahrirlash" onClick={() => setEditingRule(rule)}><i className="bi bi-pencil"></i></button>
                      <button className="btn btn-sm btn-light text-danger" title="O'chirish" onClick={() => removeLogistics(rule.destroyUrl, `${rule.zoneName} qoidasi o'chirilsinmi?`)}><i className="bi bi-trash"></i></button>
                    </td>
                  </tr>
                ))}
                {sortedRules.length === 0 ? <tr><td colSpan={9} className="text-center text-muted py-4">Mos zona qoidasi topilmadi</td></tr> : null}</tbody>
              </table>
            </div>
          </div>
        </div>
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title"><i className="bi bi-cursor-fill me-2 text-primary"></i>Aqlli preview</div>
                <small className="text-muted">Xaritaga bosing — manzil qaysi zonaga tushishini darhol ko'rsatadi</small>
              </div>
            </div>
            <YandexPreviewMap
              height={280}
              zones={previewZones}
              point={previewPoint}
              onPick={(coords) => setPreviewPoint([Number(coords[0].toFixed(6)), Number(coords[1].toFixed(6))])}
            />
            {previewPoint ? (
              <div className="mt-3">
                <div className="small text-muted mb-2 d-flex align-items-center flex-wrap gap-2">
                  <span><i className="bi bi-geo-alt me-1"></i>{previewPoint[0].toFixed(5)}, {previewPoint[1].toFixed(5)}</span>
                  {previewMatches.length > 1 ? <span className="chip chip-warning">{previewMatches.length} zona mos · overlap</span> : null}
                  <button type="button" className="btn btn-sm btn-light ms-auto" onClick={() => setPreviewPoint(null)}><i className="bi bi-x"></i></button>
                </div>
                {previewMatches.length === 0 ? (
                  <div className="alert alert-warning py-2 px-3 small mb-0">Bu nuqta hech qaysi zonaga tushmaydi — checkoutda kuryer <b>ko'rsatilmaydi</b>.</div>
                ) : (
                  <div className="d-flex flex-column gap-2">
                    {previewMatches.map((zone, index) => (
                      <div key={zone.id} className="p-2 rounded border d-flex justify-content-between align-items-center gap-2" style={{ borderColor: index === 0 ? '#8E2226' : '#e5e7eb', background: index === 0 ? '#fef2f2' : '#fff' }}>
                        <div>
                          <div className="fw-semibold d-flex align-items-center gap-2">
                            <span style={{ width: 8, height: 8, borderRadius: 999, background: index === 0 ? '#8E2226' : (zone.color || '#8A92A2'), display: 'inline-block', flexShrink: 0 }}></span>
                            {zone.zoneName}
                            {index === 0 ? <span className="chip chip-danger">g'olib</span> : null}
                          </div>
                          <div className="small text-muted">{zone.service || '—'} · {zone.scope} · priority {zone.priority}</div>
                        </div>
                        <div className="text-end">
                          <div className="fw-bold">{(zone.basePrice || 0).toLocaleString()} so'm</div>
                          <div className="small text-muted">{zone.etaDays || 0} kun · COD {zone.codAllowed ? 'bor' : "yo'q"}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ) : null}

            <details className="mt-3">
              <summary className="small text-muted" style={{ cursor: 'pointer' }}>Server orqali aniq narx (seller soni, summa bilan hisoblash)</summary>
              <form className="mt-2" onSubmit={(event) => submitLogistics(event, 'get', indexUrl)}>
                <input type="hidden" name="cod_filter" value={logisticsFilters.codFilter || ''} />
                <div className="row g-2">
                  <div className="col-md-6"><LogisticsInput key={`plat-${previewPoint ? previewPoint[0] : 'x'}`} name="preview_lat" label="Latitude" type="number" step="0.000001" defaultValue={previewPoint ? previewPoint[0] : logisticsFilters.previewLat} required /></div>
                  <div className="col-md-6"><LogisticsInput key={`plon-${previewPoint ? previewPoint[1] : 'x'}`} name="preview_lon" label="Longitude" type="number" step="0.000001" defaultValue={previewPoint ? previewPoint[1] : logisticsFilters.previewLon} required /></div>
                  <div className="col-md-6"><LogisticsInput name="preview_country_code" label="Mamlakat" defaultValue={logisticsFilters.previewCountry || 'UZ'} /></div>
                  <div className="col-md-6"><LogisticsInput name="preview_seller_count" label="Seller soni" type="number" min={1} max={20} defaultValue={logisticsFilters.previewSellerCount || 1} /></div>
                  <div className="col-md-6"><LogisticsInput name="preview_total_sum" label="Buyurtma summasi" type="number" min={0} defaultValue={logisticsFilters.previewTotalSum || 0} /></div>
                  <div className="col-md-6"><LogisticsInput name="preview_address" label="Manzil" defaultValue={logisticsFilters.previewAddress} /></div>
                </div>
                <div className="text-end mt-2"><button className="btn btn-light btn-sm"><i className="bi bi-calculator me-1"></i>Server narxi</button></div>
              </form>
              {logisticsPreview ? (
                <div className="mt-2 d-flex flex-column gap-2">
                  {(logisticsPreview.offers || []).map((offer, index) => (
                    <div className="p-2 rounded border" key={`${offer.service}-${index}`}>
                      <div className="d-flex justify-content-between gap-3"><strong>{offer.service || 'Xizmat'}</strong><span className="fw-bold">{(offer.price || 0).toLocaleString()} so'm</span></div>
                      <div className="small text-muted">{offer.rule || 'qoida'} · {offer.etaDays || 0} kun · COD {offer.codAllowed ? 'bor' : "yo'q"}</div>
                    </div>
                  ))}
                  {(logisticsPreview.offers || []).length === 0 ? <div className="text-muted small">Mos yetkazish taklifi topilmadi.</div> : null}
                </div>
              ) : null}
            </details>
          </div>
        </div>
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Yetkazish xizmatlari</div>
            <small className="text-muted">Kuryer va pochta xizmatlari endi Settings emas, Logistika ichida boshqariladi</small>
          </div>
          <div className="d-flex gap-2 align-items-center">
            <div className="position-relative" style={{ width: 200 }}>
              <i className="bi bi-search position-absolute" style={{ left: 12, top: 9, color: 'var(--kc-text-muted)' }}></i>
              <input className="form-control form-control-sm" style={{ paddingLeft: 32 }} placeholder="Xizmat qidirish" value={serviceQuery} onChange={(event) => setServiceQuery(event.target.value)} />
            </div>
            <button className="btn btn-sm btn-light" onClick={() => setEditingService(null)}><i className="bi bi-plus-circle me-1"></i>Xizmat qo'shish</button>
          </div>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Xizmat</th><th>Turi</th><th>Narx/kg</th><th>Bepuldan</th><th>Muddat</th><th>Mamlakat</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>{filteredServices.map(service => (
              <tr key={service.id}>
                <td className="fw-semibold" style={{ color: '#0B0342' }}>#{service.id}</td>
                <td className="fw-semibold">{service.name}</td>
                <td><span className="chip chip-gray">{service.type || '—'}</span></td>
                <td>{service.price === 0 ? <span className="chip chip-success">Bepul</span> : <span className="fw-semibold">{service.price.toLocaleString()} so'm</span>}</td>
                <td>{service.freeFrom > 0 ? `${service.freeFrom.toLocaleString()} so'm` : '—'}</td>
                <td className="fw-semibold">{service.days} kun</td>
                <td>{service.country || '—'} {service.capital ? '· poytaxt' : ''}</td>
                <td><span className={`chip ${service.active ? 'chip-success' : 'chip-gray'}`}>{service.active ? 'Faol' : 'Nofaol'}</span></td>
                <td>
                  <button className="btn btn-sm btn-light me-1" onClick={() => setEditingService(service)}><i className="bi bi-pencil"></i></button>
                  <button className="btn btn-sm btn-light text-danger" onClick={() => removeLogistics(service.destroyUrl, `${service.name} xizmati o'chirilsinmi?`)}><i className="bi bi-trash"></i></button>
                </td>
              </tr>
            ))}
            {filteredServices.length === 0 ? <tr><td colSpan={9} className="text-center text-muted py-4">Mos xizmat topilmadi</td></tr> : null}</tbody>
          </table>
        </div>
      </div>

      <Modal show={editingRule !== undefined} onHide={() => setEditingRule(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editingRule?.updateUrl ? 'Zona qoidasini tahrirlash' : 'Yangi zona qoidasi'}</Modal.Title></Modal.Header>
        <Modal.Body>
          <DeliveryRuleForm rule={editingRule} services={deliveryServices} action={editingRule?.updateUrl || logisticsActions.ruleStoreUrl} onDone={() => setEditingRule(undefined)} />
        </Modal.Body>
      </Modal>

      <Modal show={editingService !== undefined} onHide={() => setEditingService(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editingService ? 'Xizmatni tahrirlash' : 'Yangi yetkazish xizmati'}</Modal.Title></Modal.Header>
        <Modal.Body>
          <DeliveryServiceForm service={editingService} action={editingService?.updateUrl || logisticsActions.serviceStoreUrl} onDone={() => setEditingService(undefined)} />
        </Modal.Body>
      </Modal>
    </div>
  );
}
