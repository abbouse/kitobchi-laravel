import { ChangeEvent, FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== MYSTERY BOX =====
export function MysteryBox() {
  const { mysteryBox = { plans: [], subscriptions: [], indexUrl: '/boshqaruv/mystery-box', plansUrl: '/boshqaruv/mystery-box' } } = usePage<{
    mysteryBox?: {
      plans: Array<{ id: number; name: string; months: number; price: number; booksPerMonth: number; active: boolean; subscribers: number; plansUrl?: string; destroyUrl?: string }>;
      subscriptions: Array<{ id: number; user: string; phone?: string; plan: string; status: string; statusLabel?: string; nextDelivery?: string; progress?: number; showUrl?: string; pauseUrl?: string; resumeUrl?: string; cancelUrl?: string }>;
      indexUrl: string;
      plansUrl: string;
    };
  }>().props;
  const [selected, setSelected] = useState<(typeof mysteryBox.subscriptions)[0] | null>(null);

  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true });

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Mystery Box</h1><p className="page-subtitle">{mysteryBox.plans.length} ta plan · {mysteryBox.subscriptions.filter(s => s.status === 'active').length} ta faol obuna</p></div>
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
                {p.destroyUrl ? <button className="btn btn-sm btn-light text-danger" onClick={() => router.delete(p.destroyUrl!, { preserveScroll: true })}><i className="bi bi-trash"></i></button> : null}
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
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{s.id}</td>
              <td className="fw-semibold">{s.user}</td>
              <td><span className="chip chip-purple" style={{ fontSize: 9 }}>{s.plan}</span></td>
              <td><span className={`chip ${s.status === 'active' ? 'chip-success' : s.status === 'paused' ? 'chip-warning' : 'chip-gray'}`} style={{ fontSize: 9 }}>{s.statusLabel || s.status}</span></td>
              <td className="text-muted">{s.nextDelivery}</td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(s)}><i className="bi bi-eye"></i></button>
                {s.status === 'active' ? <button className="btn btn-sm btn-warning" onClick={() => patch(s.pauseUrl)}><i className="bi bi-pause-fill"></i></button> : <button className="btn btn-sm btn-success" onClick={() => patch(s.resumeUrl)}><i className="bi bi-play-fill"></i></button>}
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
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
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
  logisticsStats?: { services?: number; activeServices?: number; rules?: number; codRules?: number };
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
          background: '#eef2f7',
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
            background: '#10b981',
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
  const [scope, setScope] = useState(rule?.scope || 'radius');
  const [lat, setLat] = useState(toLogisticsDecimal(rule?.centerLat, '41.311081'));
  const [lon, setLon] = useState(toLogisticsDecimal(rule?.centerLon, '69.240562'));
  const [radius, setRadius] = useState(toLogisticsDecimal(rule?.radiusKm, '28'));

  if (!action) return null;

  return (
    <form onSubmit={(event) => submitLogistics(event, rule ? 'put' : 'post', action, onDone)}>
      <div className="rounded-4 border bg-light-subtle p-3 mb-3">
        <div className="fw-bold mb-1">Zona qoidasi - bu xizmat qayerda va qanday narxda ishlashini belgilaydi</div>
        <div className="small text-muted">Bitta xizmatga bir nechta zona qo'shish mumkin. Masalan: Kitobchi kuryer 28 km standart, Kitobchi kuryer 45 km uzoq zona. Servisni ikki marta ochish shart emas.</div>
      </div>
      <div className="row g-3">
        <div className="col-md-6"><LogisticsInput name="zone_name" label="Zona nomi" required defaultValue={rule?.zoneName} /></div>
        <div className="col-md-3"><LogisticsInput name="country_code" label="Mamlakat kodi" required max={2} defaultValue={rule?.country || 'UZ'} /></div>
        <div className="col-md-3">
          <label className="form-label small text-muted fw-semibold">Zona turi</label>
          <select className="form-select" name="scope" required value={scope} onChange={(event) => setScope(event.target.value)}>
            <option value="radius">Radius bo'yicha</option>
            <option value="country">Butun mamlakat</option>
          </select>
        </div>
        <div className="col-12">
          <div className="alert alert-light border mb-0">
            <div className="fw-semibold mb-1">Nom maydonlari ixtiyoriy</div>
            <div className="small text-muted">Toshkent kuryer zonalarida viloyat/tuman/shaharni bo'sh qoldiring. Shunda Yandex manzil nomlari ruscha yoki inglizcha kelsa ham radius bo'yicha to'g'ri ishlaydi.</div>
          </div>
        </div>
        <div className="col-md-4"><LogisticsInput name="region_name" label="Viloyat" help="Faqat nom bo'yicha majburan cheklash kerak bo'lsa yozing." defaultValue={rule?.region} /></div>
        <div className="col-md-4"><LogisticsInput name="district_name" label="Tuman" help="Bo'sh bo'lsa tuman tekshirilmaydi." defaultValue={rule?.district} /></div>
        <div className="col-md-4"><LogisticsInput name="city_name" label="Shahar" help="Bo'sh bo'lsa shahar tekshirilmaydi." defaultValue={rule?.city} /></div>
        {scope === 'radius' ? (
          <>
            <div className="col-12">
              <RadiusMapPicker lat={lat} lon={lon} radius={radius} onLat={setLat} onLon={setLon} onRadius={setRadius} />
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
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>{rule ? 'Saqlash' : "Qoida qo'shish"}</button>
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
  const courierServices = deliveryServices.filter((service) => service.type === 'courier_service');
  const postalServices = deliveryServices.filter((service) => service.type === 'mail_service');
  const radiusRules = deliveryRules.filter((rule) => rule.scope === 'radius');
  const overviewWidth = 1040;
  const overviewHeight = 280;
  const overviewZoom = radiusRules.length > 1 ? 8 : 10;
  const overviewValidRules = radiusRules.filter((rule) => Number.isFinite(Number(rule.centerLat)) && Number.isFinite(Number(rule.centerLon)));
  const overviewLat = overviewValidRules.length
    ? overviewValidRules.reduce((sum, rule) => sum + Number(rule.centerLat), 0) / overviewValidRules.length
    : 41.311081;
  const overviewLon = overviewValidRules.length
    ? overviewValidRules.reduce((sum, rule) => sum + Number(rule.centerLon), 0) / overviewValidRules.length
    : 69.240562;
  const overviewMap = buildMapTiles(overviewLat, overviewLon, overviewZoom, overviewWidth, overviewHeight);

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Logistika</h1><p className="page-subtitle">Xizmatni bir marta oching, keyin u qaysi zonalarda ishlashini qoidalar orqali belgilang.</p></div>
        <div className="d-flex gap-2">
          <button className="btn btn-light" onClick={() => setEditingService(null)}><i className="bi bi-truck me-1"></i>Xizmat</button>
          <button className="btn btn-primary-gradient" onClick={() => setEditingRule(null)}><i className="bi bi-plus-circle me-1"></i>Zona qoidasi</button>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Xizmatlar', value: logisticsStats.services ?? deliveryServices.length, icon: 'bi-truck', color: '#4f46e5' },
          { label: 'Faol xizmat', value: logisticsStats.activeServices ?? deliveryServices.filter((item) => item.active).length, icon: 'bi-check-circle', color: '#10b981' },
          { label: 'Zona qoidalari', value: logisticsStats.rules ?? deliveryRules.length, icon: 'bi-geo-alt', color: '#f59e0b' },
          { label: 'COD qoidalari', value: logisticsStats.codRules ?? deliveryRules.filter((item) => item.codAllowed).length, icon: 'bi-cash-coin', color: '#7c3aed' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3 mb-4">
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="d-flex align-items-center gap-3 mb-3">
              <div className="stat-icon" style={{ background: '#111827' }}><i className="bi bi-diagram-3"></i></div>
              <div>
                <div className="panel-title">Qanday ishlaydi?</div>
                <small className="text-muted">Chalkashmaslik uchun ikki qismga bo'lingan</small>
              </div>
            </div>
            <div className="d-flex flex-column gap-3">
              <div className="rounded-4 border p-3">
                <div className="fw-bold">1. Xizmat</div>
                <div className="small text-muted">Kuryer yoki pochta kanali. Masalan: Kitobchi kuryer, UzPost. Buni ikki marta qo'shish shart emas.</div>
              </div>
              <div className="rounded-4 border p-3">
                <div className="fw-bold">2. Zona qoidasi</div>
                <div className="small text-muted">Shu xizmat qayerda ishlaydi, narxi qancha, COD bormi, radius nechchi km - hammasi shu yerda.</div>
              </div>
              <div className="rounded-4 border p-3">
                <div className="fw-bold">3. Preview</div>
                <div className="small text-muted">User koordinatasini kiritib, checkoutda qaysi yetkazish chiqishini oldindan tekshirasiz.</div>
              </div>
            </div>
          </div>
        </div>
        <div className="col-xl-8">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Faol logistika xaritasi</div>
                <small className="text-muted">Radiusli qoidalar haqiqiy xaritada ko'rinadi. Markerga bosib tahrirlaysiz.</small>
              </div>
              <span className="chip chip-gray">{radiusRules.length} radius</span>
            </div>
            <div className="position-relative overflow-hidden rounded-4 border bg-light-subtle" style={{ height: overviewHeight, background: '#eef2f7' }}>
              {overviewMap.tiles.map((tile) => (
                <img
                  alt=""
                  draggable={false}
                  key={tile.key}
                  src={tile.url}
                  className="position-absolute"
                  style={{ left: tile.left, top: tile.top, width: tileSize, height: tileSize, userSelect: 'none' }}
                />
              ))}
              {radiusRules.map((rule, index) => {
                const lat = Number(rule.centerLat);
                const lon = Number(rule.centerLon);
                const radius = Math.max(1, Number(rule.radiusKm || 1));
                const x = Number.isFinite(lon) ? lonToWorldX(lon, overviewZoom) - overviewMap.centerX + overviewWidth / 2 : 80 + index * 36;
                const y = Number.isFinite(lat) ? latToWorldY(lat, overviewZoom) - overviewMap.centerY + overviewHeight / 2 : 50 + index * 28;
                const size = Math.min(160, Math.max(24, (radius * 1000) / metersPerPixel(Number.isFinite(lat) ? lat : overviewLat, overviewZoom)));
                return (
                  <div key={rule.id} className="position-absolute" style={{ left: x, top: y, transform: 'translate(-50%, -50%)' }}>
                    <div className="position-absolute rounded-circle" style={{ width: size, height: size, left: -size / 2, top: -size / 2, border: '1px solid rgba(16,185,129,.28)', background: 'rgba(16,185,129,.12)' }} />
                    <button type="button" className="btn btn-sm btn-light shadow-sm rounded-pill position-relative" onClick={() => setEditingRule(rule)}>
                      <i className="bi bi-geo-alt-fill text-success me-1"></i>{rule.zoneName}
                    </button>
                  </div>
                );
              })}
              {radiusRules.length === 0 ? <div className="position-absolute top-50 start-50 translate-middle text-center text-muted">Radiusli zona hali qo'shilmagan</div> : null}
              <div className="position-absolute small text-muted bg-white bg-opacity-75 rounded-pill px-2 py-1" style={{ right: 10, bottom: 10 }}>
                © OpenStreetMap contributors © CARTO
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-xl-7">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Zona qoidalari</div>
                <small className="text-muted">A122 resolver ishlatadigan narx, radius, COD va priority sozlamalari</small>
              </div>
              <form className="d-flex gap-2" onSubmit={(event) => submitLogistics(event, 'get', indexUrl)}>
                <select className="form-select form-select-sm" name="cod_filter" defaultValue={logisticsFilters.codFilter || ''}>
                  <option value="">COD: barchasi</option>
                  <option value="on">COD bor</option>
                  <option value="off">COD yo'q</option>
                </select>
                <button className="btn btn-sm btn-light"><i className="bi bi-funnel"></i></button>
              </form>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead><tr><th>Zona</th><th>Scope</th><th>Xizmat</th><th>Narx</th><th>ETA</th><th>COD</th><th>Holat</th><th></th></tr></thead>
                <tbody>{deliveryRules.map((rule) => (
                  <tr key={rule.id}>
                    <td><div className="fw-semibold">{rule.zoneName}</div><small className="text-muted">{[rule.city, rule.district, rule.region, rule.country].filter(Boolean).join(', ') || '—'}</small></td>
                    <td><span className="chip chip-gray">{rule.scope || '—'}</span>{rule.scope === 'radius' ? <div className="small text-muted">{rule.radiusKm || 0} km</div> : null}</td>
                    <td>{rule.service || '—'}</td>
                    <td><strong>{(rule.basePrice || 0).toLocaleString()} so'm</strong><div className="small text-muted">{rule.freePriceFrom ? `${rule.freePriceFrom.toLocaleString()} dan bepul` : 'chegara yoq'}</div></td>
                    <td>{rule.etaDays || 0} kun</td>
                    <td><span className={`chip ${rule.codAllowed ? 'chip-success' : 'chip-gray'}`}>{rule.codAllowed ? 'Bor' : "Yo'q"}</span></td>
                    <td><span className={`chip ${rule.active ? 'chip-success' : 'chip-gray'}`}>{rule.active ? 'Faol' : 'Nofaol'}</span></td>
                    <td className="text-end">
                      <button className="btn btn-sm btn-light me-1" onClick={() => setEditingRule(rule)}><i className="bi bi-pencil"></i></button>
                      <button className="btn btn-sm btn-light text-danger" onClick={() => removeLogistics(rule.destroyUrl, `${rule.zoneName} qoidasi o'chirilsinmi?`)}><i className="bi bi-trash"></i></button>
                    </td>
                  </tr>
                ))}</tbody>
              </table>
            </div>
          </div>
        </div>
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Yetkazish preview</div>
                <small className="text-muted">Koordinata orqali resolver natijasini tekshirish</small>
              </div>
            </div>
            <form onSubmit={(event) => submitLogistics(event, 'get', indexUrl)}>
              <input type="hidden" name="cod_filter" value={logisticsFilters.codFilter || ''} />
              <div className="row g-2">
                <div className="col-md-6"><LogisticsInput name="preview_lat" label="Latitude" type="number" step="0.000001" defaultValue={logisticsFilters.previewLat} required /></div>
                <div className="col-md-6"><LogisticsInput name="preview_lon" label="Longitude" type="number" step="0.000001" defaultValue={logisticsFilters.previewLon} required /></div>
                <div className="col-md-6"><LogisticsInput name="preview_country_code" label="Mamlakat" defaultValue={logisticsFilters.previewCountry || 'UZ'} /></div>
                <div className="col-md-6"><LogisticsInput name="preview_seller_count" label="Seller soni" type="number" min={1} max={20} defaultValue={logisticsFilters.previewSellerCount || 1} /></div>
                <div className="col-md-6"><LogisticsInput name="preview_total_sum" label="Buyurtma summasi" type="number" min={0} defaultValue={logisticsFilters.previewTotalSum || 0} /></div>
                <div className="col-md-6"><LogisticsInput name="preview_address" label="Manzil" defaultValue={logisticsFilters.previewAddress} /></div>
              </div>
              <div className="text-end mt-3"><button className="btn btn-light"><i className="bi bi-calculator me-1"></i>Hisoblash</button></div>
            </form>
            {logisticsPreview ? (
              <div className="mt-3 d-flex flex-column gap-2">
                {(logisticsPreview.offers || []).map((offer, index) => (
                  <div className="p-3 rounded border" key={`${offer.service}-${index}`}>
                    <div className="d-flex justify-content-between gap-3"><strong>{offer.service || 'Xizmat'}</strong><span className="fw-bold">{(offer.price || 0).toLocaleString()} so'm</span></div>
                    <div className="small text-muted">{offer.rule || 'qoida'} · {offer.etaDays || 0} kun · COD {offer.codAllowed ? 'bor' : "yo'q"}</div>
                  </div>
                ))}
                {(logisticsPreview.offers || []).length === 0 ? <div className="text-muted small">Mos yetkazish taklifi topilmadi.</div> : null}
              </div>
            ) : null}
          </div>
        </div>
      </div>

      <div className="card-panel">
        <div className="panel-head">
          <div>
            <div className="panel-title">Yetkazish xizmatlari</div>
            <small className="text-muted">Kuryer va pochta xizmatlari endi Settings emas, Logistika ichida boshqariladi</small>
          </div>
          <button className="btn btn-sm btn-light" onClick={() => setEditingService(null)}><i className="bi bi-plus-circle me-1"></i>Xizmat qo'shish</button>
        </div>
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Xizmat</th><th>Turi</th><th>Narx/kg</th><th>Bepuldan</th><th>Muddat</th><th>Mamlakat</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>{deliveryServices.map(service => (
              <tr key={service.id}>
                <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{service.id}</td>
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
            ))}</tbody>
          </table>
        </div>
      </div>

      <Modal show={editingRule !== undefined} onHide={() => setEditingRule(undefined)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editingRule ? 'Zona qoidasini tahrirlash' : 'Yangi zona qoidasi'}</Modal.Title></Modal.Header>
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
