import { Component, ReactNode, useEffect, useRef, useState } from 'react';

// Yandex Maps 2.1 global (boshqaruv/app.blade.php da apikey bilan yuklanadi)
declare global {
  interface Window {
    ymaps?: any;
    __YANDEX_MAPS_ENABLED__?: boolean;
  }
}

export type LatLon = [number, number];
export type Ring = LatLon[];

const TASHKENT: LatLon = [41.311081, 69.240562];

const COLORS = {
  radius: '#15764D',
  polygon: '#4338CA',
  inactive: '#656A81',
};

type ReadyState = 'loading' | 'ready' | 'disabled';

// ymaps.ready() bir marta ishga tushgach eslab qolamiz — keyingi mount'lar darhol tayyor.
let ymapsReadyOnce = false;

/**
 * ymaps API TO'LIQ tayyorligini kuzatadi (ymaps.ready orqali).
 * MUHIM: to'liq reload'da <head> skripti window.ymaps ni sinxron aniqlaydi,
 * lekin ymaps.Map/Polygon modullari ymaps.ready'dan keyin yuklanadi. Shuning uchun
 * window.ymaps mavjudligining o'zi yetarli emas — aks holda new ymaps.Map() throw beradi.
 */
export function useYmapsReady(): ReadyState {
  const [state, setState] = useState<ReadyState>(() => {
    if (typeof window === 'undefined') return 'loading';
    if (window.__YANDEX_MAPS_ENABLED__ === false) return 'disabled';
    return ymapsReadyOnce ? 'ready' : 'loading';
  });

  useEffect(() => {
    if (state !== 'loading') return;
    if (typeof window !== 'undefined' && window.__YANDEX_MAPS_ENABLED__ === false) {
      setState('disabled');
      return;
    }

    let cancelled = false;
    const markReady = () => {
      if (cancelled) return;
      window.ymaps.ready(() => {
        if (cancelled) return;
        ymapsReadyOnce = true;
        setState('ready');
      });
    };

    if (window.ymaps) {
      markReady();
      return () => {
        cancelled = true;
      };
    }

    const timer = window.setInterval(() => {
      if (window.ymaps) {
        window.clearInterval(timer);
        markReady();
      }
    }, 150);

    // 12 soniyada ham yuklanmasa — o'chirilgan deb hisoblaymiz
    const timeout = window.setTimeout(() => {
      if (!cancelled && !window.ymaps) {
        window.clearInterval(timer);
        setState('disabled');
      }
    }, 12000);

    return () => {
      cancelled = true;
      window.clearInterval(timer);
      window.clearTimeout(timeout);
    };
  }, [state]);

  return state;
}

function num(value: number | string | null | undefined): number | null {
  if (value === null || value === undefined || value === '') return null;
  const n = typeof value === 'string' ? Number.parseFloat(value) : value;
  return Number.isFinite(n) ? n : null;
}

// ───────────────────────── Client-side zona resolver ─────────────────────────
// MUHIM: bu backend DeliveryZoneResolverService bilan BIR XIL mantiqda bo'lishi kerak
// (haversine 6371 km, ray-casting point-in-polygon, priority > specificity saralash).
// Faqat preview/overlap vizuali uchun — haqiqiy narx baribir serverdan keladi.

export type ZoneLike = {
  id: number;
  scope?: string;
  active?: boolean;
  centerLat?: number | string | null;
  centerLon?: number | string | null;
  radiusKm?: number | string | null;
  polygon?: Ring | null;
  priority?: number;
  zoneName?: string;
  service?: string;
  color?: string | null;
  basePrice?: number;
  etaDays?: number;
  codAllowed?: boolean;
};

export function haversineKm(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const R = 6371;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a)));
}

/** Ray-casting (even-odd) — ring = [[lat, lon], ...]. */
export function pointInRing(lat: number, lon: number, ring: Ring): boolean {
  let inside = false;
  const n = ring.length;
  for (let i = 0, j = n - 1; i < n; j = i++) {
    const latI = ring[i][0], lonI = ring[i][1];
    const latJ = ring[j][0], lonJ = ring[j][1];
    let denom = latJ - latI;
    if (denom === 0) denom = 1e-12;
    const intersects = ((latI > lat) !== (latJ > lat)) && (lon < ((lonJ - lonI) * (lat - latI)) / denom + lonI);
    if (intersects) inside = !inside;
  }
  return inside;
}

export function zoneMatchesPoint(zone: ZoneLike, lat: number, lon: number): boolean {
  if (zone.active === false) return false;
  const scope = zone.scope || 'radius';
  if (scope === 'country') return true;
  if (scope === 'polygon') {
    const ring = Array.isArray(zone.polygon) ? zone.polygon : null;
    if (!ring || ring.length < 3) return false;
    return pointInRing(lat, lon, ring);
  }
  const cLat = num(zone.centerLat);
  const cLon = num(zone.centerLon);
  const r = num(zone.radiusKm);
  if (cLat === null || cLon === null || r === null) return false;
  return haversineKm(lat, lon, cLat, cLon) <= r;
}

function zoneSpecificity(zone: ZoneLike): number {
  const scope = zone.scope || 'radius';
  if (scope === 'country') return 10;
  return scope === 'polygon' ? 120 : 100;
}

/** Nuqtaga mos zonalarni g'olib (priority > specificity > id) birinchi bo'lgan holda qaytaradi. */
export function resolveZonesForPoint(zones: ZoneLike[], lat: number, lon: number): ZoneLike[] {
  return zones
    .filter((z) => zoneMatchesPoint(z, lat, lon))
    .sort((a, b) => {
      const pa = a.priority ?? 0, pb = b.priority ?? 0;
      if (pb !== pa) return pb - pa;
      const sa = zoneSpecificity(a), sb = zoneSpecificity(b);
      if (sb !== sa) return sb - sa;
      return (b.id ?? 0) - (a.id ?? 0);
    });
}

/** Halqadan yopiluvchi takror nuqtani olib tashlaydi. */
function normalizeRing(ring: any): Ring {
  const points: Ring = (ring || [])
    .map((p: any) => [num(p?.[0]), num(p?.[1])])
    .filter((p: any) => p[0] !== null && p[1] !== null) as Ring;

  if (points.length > 1) {
    const first = points[0];
    const last = points[points.length - 1];
    if (Math.abs(first[0] - last[0]) < 1e-9 && Math.abs(first[1] - last[1]) < 1e-9) {
      points.pop();
    }
  }
  return points;
}

function MapFallback({ height, state }: { height: number; state: ReadyState }) {
  return (
    <div
      className="d-flex flex-column align-items-center justify-content-center text-center text-muted f-s-13 b-1-light b-r-15 gap-2 p-3 bg-light-secondary"
      style={{ height }}
    >
      {state === 'loading' ? (
        <>
          <div className="spinner-border spinner-border-sm text-secondary" role="status" />
          <span>Yandex xaritasi yuklanmoqda…</span>
        </>
      ) : (
        <>
          <i className="ti ti-key f-s-24 text-warning" />
          <div className="f-w-600 text-dark">Yandex Maps kaliti sozlanmagan</div>
          <div>
            <code>.env</code> faylida <code>YANDEX_MAPS_API_KEY</code> ni to'ldiring.
            Koordinatalarni pastdagi maydonlarga qo'lda ham kiritishingiz mumkin.
          </div>
        </>
      )}
    </div>
  );
}

/**
 * Xarita xatosi (masalan Yandex modul yuklanmasligi) butun boshqaruv sahifasini
 * oq qilib qo'ymasligi uchun himoya to'sig'i.
 */
class MapErrorBoundary extends Component<{ height: number; children: ReactNode }, { failed: boolean }> {
  state = { failed: false };

  static getDerivedStateFromError() {
    return { failed: true };
  }

  componentDidCatch(error: unknown) {
    // eslint-disable-next-line no-console
    console.error('Yandex xarita xatosi:', error);
  }

  render() {
    if (this.state.failed) {
      return (
        <div
          className="d-flex flex-column align-items-center justify-content-center text-center text-muted f-s-13 b-1-light b-r-15 gap-2 p-3 bg-light-secondary"
          style={{ height: this.props.height }}
        >
          <i className="ti ti-alert-triangle f-s-24 text-warning" />
          <div className="f-w-600 text-dark">Xaritani ochishda xatolik</div>
          <div>Sahifani yangilang. Muammo qaytarilsa, Yandex kaliti yoki internet aloqasini tekshiring.</div>
        </div>
      );
    }
    return this.props.children;
  }
}

/** Manzil bo'yicha qidiruv — Yandex geocoder. */
function GeocodeSearch({ onPick }: { onPick: (coords: LatLon, label: string) => void }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Array<{ label: string; coords: LatLon }>>([]);
  const [busy, setBusy] = useState(false);

  const run = () => {
    const text = query.trim();
    if (!text || !window.ymaps) return;
    setBusy(true);
    // MUHIM: Yandex geocode "vow" promise qaytaradi — unda .catch/.finally YO'Q.
    // Faqat .then(onOk, onErr) ishlaydi; busy'ni ikkala tarmoqda ham o'chiramiz.
    try {
      window.ymaps.geocode(text, { results: 5 }).then(
        (res: any) => {
          const list: Array<{ label: string; coords: LatLon }> = [];
          res.geoObjects.each((obj: any) => {
            const coords = obj.geometry.getCoordinates();
            list.push({ label: obj.getAddressLine ? obj.getAddressLine() : obj.properties.get('text'), coords: [coords[0], coords[1]] });
          });
          setResults(list);
          setBusy(false);
        },
        () => {
          setResults([]);
          setBusy(false);
        },
      );
    } catch {
      setResults([]);
      setBusy(false);
    }
  };

  return (
    <div className="position-relative">
      <div className="input-group input-group-sm">
        <span className="input-group-text bg-white"><i className="ti ti-search" /></span>
        <input
          className="form-control"
          placeholder="Manzil qidirish (masalan: Chilonzor, Toshkent)"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault();
              run();
            }
          }}
        />
        <button type="button" className="btn btn-primary" onClick={run} disabled={busy}>
          {busy ? <span className="spinner-border spinner-border-sm" /> : 'Qidirish'}
        </button>
      </div>
      {results.length > 0 ? (
        <div className="list-group position-absolute w-100 overflow-y-auto" style={{ zIndex: 5, maxHeight: 220 }}>
          {results.map((item, index) => (
            <button
              type="button"
              key={`${item.label}-${index}`}
              className="list-group-item list-group-item-action f-s-13 text-start"
              onClick={() => {
                onPick(item.coords, item.label);
                setResults([]);
                setQuery(item.label);
              }}
            >
              <i className="ti ti-map-pin me-1 text-primary" />
              {item.label}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}

/**
 * Zona muharriri — radius (marker + doira) yoki polygon (xaritada chizish) rejimida.
 * Taksi xizmatlaridagi kabi: xaritaga bosib zona chegarasini belgilaysiz.
 */
function YandexZoneEditorInner({
  scope,
  lat,
  lon,
  radiusKm,
  polygon,
  onRadiusChange,
  onPolygonChange,
  height = 380,
}: {
  scope: 'radius' | 'polygon';
  lat: number | string | null;
  lon: number | string | null;
  radiusKm: number | string | null;
  polygon: Ring | null;
  onRadiusChange: (coords: LatLon) => void;
  onPolygonChange: (points: Ring) => void;
  height?: number;
}) {
  const state = useYmapsReady();
  const mapEl = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<any>(null);
  const placemarkRef = useRef<any>(null);
  const circleRef = useRef<any>(null);
  const polygonRef = useRef<any>(null);
  const [drawing, setDrawing] = useState(false);

  // Eng oxirgi callbacklarni ref orqali ushlaymiz (stale closure bo'lmasligi uchun)
  const onRadiusRef = useRef(onRadiusChange);
  const onPolygonRef = useRef(onPolygonChange);
  onRadiusRef.current = onRadiusChange;
  onPolygonRef.current = onPolygonChange;

  // Xaritani bir marta yaratamiz
  useEffect(() => {
    if (state !== 'ready' || !mapEl.current || mapRef.current) return;
    const ymaps = window.ymaps;
    const center: LatLon = num(lat) !== null && num(lon) !== null ? [num(lat)!, num(lon)!] : TASHKENT;

    const map = new ymaps.Map(
      mapEl.current,
      { center, zoom: num(lat) !== null ? 12 : 11, controls: ['zoomControl', 'typeSelector', 'fullscreenControl'] },
      { suppressMapOpenBlock: true },
    );
    mapRef.current = map;
    setTimeout(() => map.container.fitToViewport(), 200);

    return () => {
      map.destroy();
      mapRef.current = null;
      placemarkRef.current = null;
      circleRef.current = null;
      polygonRef.current = null;
    };
  }, [state]);

  // Scope o'zgarsa mos geometriyani o'rnatamiz
  useEffect(() => {
    const map = mapRef.current;
    if (state !== 'ready' || !map) return;
    const ymaps = window.ymaps;

    // Avvalgi obyektlarni tozalash
    map.geoObjects.removeAll();
    placemarkRef.current = null;
    circleRef.current = null;
    polygonRef.current = null;
    setDrawing(false);

    if (scope === 'radius') {
      const center: LatLon = num(lat) !== null && num(lon) !== null ? [num(lat)!, num(lon)!] : TASHKENT;
      const rMeters = Math.max(0.1, num(radiusKm) ?? 5) * 1000;

      const circle = new ymaps.Circle([center, rMeters], {}, {
        fillColor: COLORS.radius + '26',
        strokeColor: COLORS.radius,
        strokeWidth: 2,
      });
      const placemark = new ymaps.Placemark(center, {}, { draggable: true, preset: 'islands#greenCircleDotIcon' });

      circleRef.current = circle;
      placemarkRef.current = placemark;
      map.geoObjects.add(circle);
      map.geoObjects.add(placemark);

      const sync = (coords: LatLon) => {
        circle.geometry.setCoordinates(coords);
        onRadiusRef.current([Number(coords[0].toFixed(6)), Number(coords[1].toFixed(6))]);
      };
      placemark.events.add('dragend', () => sync(placemark.geometry.getCoordinates()));
      map.events.add('click', (e: any) => {
        const coords = e.get('coords');
        placemark.geometry.setCoordinates(coords);
        sync(coords);
      });
      map.setBounds(circle.geometry.getBounds(), { checkZoomRange: true, zoomMargin: 30 });
    } else {
      const initial = polygon && polygon.length >= 3 ? [polygon] : [[]];
      const poly = new ymaps.Polygon(initial, {}, {
        editorDrawingCursor: 'crosshair',
        fillColor: COLORS.polygon + '26',
        strokeColor: COLORS.polygon,
        strokeWidth: 2,
        editorMaxPoints: 1000,
      });
      polygonRef.current = poly;
      map.geoObjects.add(poly);

      const emit = () => {
        const ring = normalizeRing(poly.geometry.getCoordinates()?.[0] || []);
        onPolygonRef.current(ring);
      };
      poly.geometry.events.add('change', emit);

      if (polygon && polygon.length >= 3) {
        poly.editor.startEditing();
        map.setBounds(poly.geometry.getBounds(), { checkZoomRange: true, zoomMargin: 30 });
      } else {
        poly.editor.startDrawing();
        setDrawing(true);
      }
    }
    // radiusKm bu yerda ataylab dep emas — u alohida effektda yangilanadi
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state, scope]);

  // Radius (km) o'zgarsa doirani yangilaymiz
  useEffect(() => {
    if (scope !== 'radius' || !circleRef.current) return;
    const rMeters = Math.max(0.1, num(radiusKm) ?? 5) * 1000;
    circleRef.current.geometry.setRadius(rMeters);
  }, [radiusKm, scope]);

  // Tashqaridan lat/lon o'zgarsa (preset tugmalari) markerni ko'chiramiz
  useEffect(() => {
    if (scope !== 'radius' || !mapRef.current || !placemarkRef.current) return;
    const nLat = num(lat);
    const nLon = num(lon);
    if (nLat === null || nLon === null) return;
    const current = placemarkRef.current.geometry.getCoordinates();
    if (Math.abs(current[0] - nLat) > 1e-6 || Math.abs(current[1] - nLon) > 1e-6) {
      placemarkRef.current.geometry.setCoordinates([nLat, nLon]);
      if (circleRef.current) circleRef.current.geometry.setCoordinates([nLat, nLon]);
      mapRef.current.panTo([nLat, nLon], { flying: false });
    }
  }, [lat, lon, scope]);

  const redraw = () => {
    const poly = polygonRef.current;
    if (!poly) return;
    poly.editor.stopEditing();
    poly.geometry.setCoordinates([[]]);
    poly.editor.startDrawing();
    setDrawing(true);
    onPolygonRef.current([]);
  };

  const handleSearchPick = (coords: LatLon) => {
    if (!mapRef.current) return;
    mapRef.current.setCenter(coords, 14, { duration: 300 });
    if (scope === 'radius' && placemarkRef.current) {
      placemarkRef.current.geometry.setCoordinates(coords);
      if (circleRef.current) circleRef.current.geometry.setCoordinates(coords);
      onRadiusRef.current([Number(coords[0].toFixed(6)), Number(coords[1].toFixed(6))]);
    }
  };

  if (state !== 'ready') {
    return <MapFallback height={height} state={state} />;
  }

  return (
    <div>
      <div className="d-flex flex-wrap gap-2 align-items-center mb-2">
        <div className="flex-fill" style={{ minWidth: 220 }}>
          <GeocodeSearch onPick={handleSearchPick} />
        </div>
        {scope === 'polygon' ? (
          <div className="btn-group btn-group-sm">
            <button type="button" className={`btn ${drawing ? 'btn-primary' : 'btn-light-secondary'}`} onClick={() => { polygonRef.current?.editor.startDrawing(); setDrawing(true); }}>
              <i className="ti ti-pencil me-1" />Chizish
            </button>
            <button type="button" className="btn btn-light-secondary" onClick={() => { polygonRef.current?.editor.startEditing(); setDrawing(false); }}>
              <i className="ti ti-arrows-move me-1" />Tahrirlash
            </button>
            <button type="button" className="btn btn-light-secondary text-danger" onClick={redraw}>
              <i className="ti ti-rotate me-1" />Qaytadan
            </button>
          </div>
        ) : null}
      </div>
      {scope === 'polygon' ? (
        <div className="alert alert-border-secondary py-2 px-3 f-s-13 mb-2">
          <i className="ti ti-info-circle me-1 text-primary" />
          Xaritaga bosib zona burchaklarini qo'ying. Tugatish uchun oxirgi nuqtaga ikki marta bosing. Keyin nuqtalarni sudrab tuzatishingiz mumkin.
        </div>
      ) : null}
      <div className="b-r-18 overflow-hidden" ref={mapEl} style={{ height }} />
    </div>
  );
}

/**
 * Faqat ko'rish uchun: barcha zonalarni (radius doiralari + polygonlar) xaritada ko'rsatadi.
 */
function YandexZonesOverviewInner({
  zones,
  height = 320,
  onSelect,
  focusId = null,
}: {
  zones: Array<{
    id: number;
    scope?: string;
    lat?: number | string | null;
    lon?: number | string | null;
    radiusKm?: number | string | null;
    polygon?: Ring | null;
    label?: string;
    color?: string;
  }>;
  height?: number;
  onSelect?: (id: number) => void;
  focusId?: number | null;
}) {
  const state = useYmapsReady();
  const mapEl = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<any>(null);
  const onSelectRef = useRef(onSelect);
  onSelectRef.current = onSelect;

  useEffect(() => {
    if (state !== 'ready' || !mapEl.current) return;
    const ymaps = window.ymaps;

    if (!mapRef.current) {
      mapRef.current = new ymaps.Map(
        mapEl.current,
        { center: TASHKENT, zoom: 10, controls: ['zoomControl', 'typeSelector'] },
        { suppressMapOpenBlock: true },
      );
    }
    const map = mapRef.current;
    map.geoObjects.removeAll();

    let focusBounds: any = null;

    zones.forEach((zone) => {
      const color = zone.color || (zone.scope === 'polygon' ? COLORS.polygon : COLORS.radius);
      const hint = zone.label || '';
      const focused = focusId != null && zone.id === focusId;

      if (zone.scope === 'polygon' && zone.polygon && zone.polygon.length >= 3) {
        const poly = new ymaps.Polygon([zone.polygon], { hintContent: hint }, {
          fillColor: color + (focused ? '40' : '26'),
          strokeColor: color,
          strokeWidth: focused ? 4 : 2,
        });
        if (onSelectRef.current) poly.events.add('click', () => onSelectRef.current!(zone.id));
        map.geoObjects.add(poly);
        if (focused) focusBounds = poly.geometry.getBounds();
      } else {
        const cLat = num(zone.lat);
        const cLon = num(zone.lon);
        const r = num(zone.radiusKm);
        if (cLat === null || cLon === null) return;
        let circle: any = null;
        if (r && r > 0) {
          circle = new ymaps.Circle([[cLat, cLon], r * 1000], { hintContent: hint }, {
            fillColor: color + (focused ? '33' : '20'),
            strokeColor: color,
            strokeWidth: focused ? 3 : 1,
          });
          if (onSelectRef.current) circle.events.add('click', () => onSelectRef.current!(zone.id));
          map.geoObjects.add(circle);
        }
        const placemark = new ymaps.Placemark([cLat, cLon], { iconCaption: hint, hintContent: hint }, {
          preset: focused ? 'islands#redCircleDotIcon' : 'islands#circleIcon',
          iconColor: color,
        });
        if (onSelectRef.current) placemark.events.add('click', () => onSelectRef.current!(zone.id));
        map.geoObjects.add(placemark);
        if (focused) focusBounds = circle ? circle.geometry.getBounds() : [[cLat, cLon], [cLat, cLon]];
      }
    });

    try {
      if (focusBounds) {
        map.setBounds(focusBounds, { checkZoomRange: true, zoomMargin: 60 });
      } else if (map.geoObjects.getLength() > 0) {
        map.setBounds(map.geoObjects.getBounds(), { checkZoomRange: true, zoomMargin: 40 });
      }
    } catch {
      /* bo'sh */
    }
    setTimeout(() => map.container.fitToViewport(), 200);
  }, [state, zones, focusId]);

  useEffect(() => () => {
    if (mapRef.current) {
      mapRef.current.destroy();
      mapRef.current = null;
    }
  }, []);

  if (state !== 'ready') {
    return <MapFallback height={height} state={state} />;
  }

  if (zones.length === 0) {
    return (
      <div className="d-flex align-items-center justify-content-center text-muted f-s-13 b-1-light b-r-15" style={{ height }}>
        <span><i className="ti ti-map-pin me-1" />Xaritali zona hali qo'shilmagan</span>
      </div>
    );
  }

  return <div className="b-r-18 overflow-hidden" ref={mapEl} style={{ height }} />;
}

// Tashqi eksportlar — har biri MapErrorBoundary bilan o'ralgan (xarita xatosi sahifani buzmasin).
export function YandexZoneEditor(props: Parameters<typeof YandexZoneEditorInner>[0]) {
  return (
    <MapErrorBoundary height={props.height ?? 380}>
      <YandexZoneEditorInner {...props} />
    </MapErrorBoundary>
  );
}

export function YandexZonesOverview(props: Parameters<typeof YandexZonesOverviewInner>[0]) {
  return (
    <MapErrorBoundary height={props.height ?? 320}>
      <YandexZonesOverviewInner {...props} />
    </MapErrorBoundary>
  );
}

/**
 * Aqlli preview xaritasi: xaritaga bosib (yoki manzil qidirib) nuqta tanlaysiz,
 * mos keladigan zonalar ajratiladi, g'olib (priority > specificity) qizil bo'ladi.
 * Bir nechta zona mos kelsa — bu overlap; g'olib qizil rang orqali ko'rinadi.
 */
function YandexPreviewMapInner({
  zones,
  point,
  onPick,
  height = 360,
}: {
  zones: ZoneLike[];
  point: LatLon | null;
  onPick: (coords: LatLon) => void;
  height?: number;
}) {
  const state = useYmapsReady();
  const mapEl = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<any>(null);
  const onPickRef = useRef(onPick);
  onPickRef.current = onPick;

  const pLat = point ? point[0] : null;
  const pLon = point ? point[1] : null;

  useEffect(() => {
    if (state !== 'ready' || !mapEl.current || mapRef.current) return;
    const ymaps = window.ymaps;
    const map = new ymaps.Map(
      mapEl.current,
      { center: point ?? TASHKENT, zoom: 11, controls: ['zoomControl', 'typeSelector'] },
      { suppressMapOpenBlock: true },
    );
    mapRef.current = map;
    map.events.add('click', (e: any) => onPickRef.current(e.get('coords')));
    setTimeout(() => map.container.fitToViewport(), 200);
    return () => {
      map.destroy();
      mapRef.current = null;
    };
  }, [state]);

  useEffect(() => {
    const map = mapRef.current;
    if (state !== 'ready' || !map) return;
    const ymaps = window.ymaps;
    map.geoObjects.removeAll();

    const matched = point ? resolveZonesForPoint(zones, point[0], point[1]) : [];
    const matchedIds = new Set(matched.map((z) => z.id));
    const winnerId = matched.length ? matched[0].id : null;

    zones.forEach((zone) => {
      const base = zone.color || (zone.scope === 'polygon' ? COLORS.polygon : COLORS.radius);
      const isMatch = matchedIds.has(zone.id);
      const isWinner = zone.id === winnerId;
      const color = isWinner ? '#8A1F24' : base;
      const dim = point != null && !isMatch;
      const stroke = isWinner ? 5 : isMatch ? 3 : 1;
      const fillA = dim ? '0d' : isMatch ? '33' : '1f';
      const opts = { fillColor: color + fillA, strokeColor: color, strokeWidth: stroke, strokeStyle: dim ? 'dash' : 'solid' };

      if (zone.scope === 'polygon' && Array.isArray(zone.polygon) && zone.polygon.length >= 3) {
        map.geoObjects.add(new ymaps.Polygon([zone.polygon], { hintContent: zone.zoneName || '' }, opts));
      } else if (zone.scope !== 'country') {
        const cLat = num(zone.centerLat);
        const cLon = num(zone.centerLon);
        const r = num(zone.radiusKm);
        if (cLat !== null && cLon !== null && r) {
          map.geoObjects.add(new ymaps.Circle([[cLat, cLon], r * 1000], { hintContent: zone.zoneName || '' }, opts));
        }
      }
    });

    if (point) {
      map.geoObjects.add(new ymaps.Placemark(point, { iconCaption: 'Tekshiruv nuqtasi' }, { preset: 'islands#blackStretchyIcon' }));
    }
    setTimeout(() => map.container.fitToViewport(), 120);
  }, [state, zones, pLat, pLon]);

  useEffect(() => () => {
    if (mapRef.current) {
      mapRef.current.destroy();
      mapRef.current = null;
    }
  }, []);

  if (state !== 'ready') {
    return <MapFallback height={height} state={state} />;
  }

  return (
    <div>
      <div className="mb-2">
        <GeocodeSearch onPick={(coords) => onPickRef.current(coords)} />
      </div>
      <div className="alert alert-border-secondary py-2 px-3 f-s-13 mb-2">
        <i className="ti ti-pointer me-1 text-primary" />
        Xaritaga bosing yoki manzil qidiring — mos zonalar ajraladi, <b className="text-danger">g'olib qizil</b> bo'ladi.
      </div>
      <div className="b-r-18 overflow-hidden" ref={mapEl} style={{ height }} />
    </div>
  );
}

export function YandexPreviewMap(props: Parameters<typeof YandexPreviewMapInner>[0]) {
  return (
    <MapErrorBoundary height={props.height ?? 360}>
      <YandexPreviewMapInner {...props} />
    </MapErrorBoundary>
  );
}
