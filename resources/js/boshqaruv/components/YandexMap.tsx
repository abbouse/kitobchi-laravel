import { useEffect, useRef, useState } from 'react';

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
  radius: '#10b981',
  polygon: '#4f46e5',
  inactive: '#9ca3af',
};

type ReadyState = 'loading' | 'ready' | 'disabled';

/** ymaps global tayyorligini kuzatadi. Kalit bo'lmasa 'disabled'. */
export function useYmapsReady(): ReadyState {
  const [state, setState] = useState<ReadyState>(() => {
    if (typeof window === 'undefined') return 'loading';
    if (window.__YANDEX_MAPS_ENABLED__ === false) return 'disabled';
    return window.ymaps ? 'ready' : 'loading';
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
      window.ymaps.ready(() => !cancelled && setState('ready'));
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
      className="d-flex flex-column align-items-center justify-content-center text-center text-muted small border rounded-4 gap-2 p-3"
      style={{ height, background: '#f8fafc' }}
    >
      {state === 'loading' ? (
        <>
          <div className="spinner-border spinner-border-sm text-secondary" role="status" />
          <span>Yandex xaritasi yuklanmoqda…</span>
        </>
      ) : (
        <>
          <i className="bi bi-key fs-4 text-warning" />
          <div className="fw-semibold text-dark">Yandex Maps kaliti sozlanmagan</div>
          <div>
            <code>.env</code> faylida <code>YANDEX_MAPS_API_KEY</code> ni to'ldiring.
            Koordinatalarni pastdagi maydonlarga qo'lda ham kiritishingiz mumkin.
          </div>
        </>
      )}
    </div>
  );
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
    window.ymaps
      .geocode(text, { results: 5 })
      .then((res: any) => {
        const list: Array<{ label: string; coords: LatLon }> = [];
        res.geoObjects.each((obj: any) => {
          const coords = obj.geometry.getCoordinates();
          list.push({ label: obj.getAddressLine ? obj.getAddressLine() : obj.properties.get('text'), coords: [coords[0], coords[1]] });
        });
        setResults(list);
      })
      .catch(() => setResults([]))
      .finally(() => setBusy(false));
  };

  return (
    <div className="position-relative">
      <div className="input-group input-group-sm">
        <span className="input-group-text bg-white"><i className="bi bi-search" /></span>
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
        <button type="button" className="btn btn-primary-gradient" onClick={run} disabled={busy}>
          {busy ? <span className="spinner-border spinner-border-sm" /> : 'Qidirish'}
        </button>
      </div>
      {results.length > 0 ? (
        <div className="list-group position-absolute w-100 shadow-sm" style={{ zIndex: 5, maxHeight: 220, overflowY: 'auto' }}>
          {results.map((item, index) => (
            <button
              type="button"
              key={`${item.label}-${index}`}
              className="list-group-item list-group-item-action small text-start"
              onClick={() => {
                onPick(item.coords, item.label);
                setResults([]);
                setQuery(item.label);
              }}
            >
              <i className="bi bi-geo-alt me-1 text-primary" />
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
export function YandexZoneEditor({
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
            <button type="button" className={`btn ${drawing ? 'btn-primary-gradient' : 'btn-light'}`} onClick={() => { polygonRef.current?.editor.startDrawing(); setDrawing(true); }}>
              <i className="bi bi-pencil me-1" />Chizish
            </button>
            <button type="button" className="btn btn-light" onClick={() => { polygonRef.current?.editor.startEditing(); setDrawing(false); }}>
              <i className="bi bi-arrows-move me-1" />Tahrirlash
            </button>
            <button type="button" className="btn btn-light text-danger" onClick={redraw}>
              <i className="bi bi-arrow-counterclockwise me-1" />Qaytadan
            </button>
          </div>
        ) : null}
      </div>
      {scope === 'polygon' ? (
        <div className="alert alert-light border py-2 px-3 small mb-2">
          <i className="bi bi-info-circle me-1 text-primary" />
          Xaritaga bosib zona burchaklarini qo'ying. Tugatish uchun oxirgi nuqtaga ikki marta bosing. Keyin nuqtalarni sudrab tuzatishingiz mumkin.
        </div>
      ) : null}
      <div ref={mapEl} style={{ height, borderRadius: 14, overflow: 'hidden' }} />
    </div>
  );
}

/**
 * Faqat ko'rish uchun: barcha zonalarni (radius doiralari + polygonlar) xaritada ko'rsatadi.
 */
export function YandexZonesOverview({
  zones,
  height = 320,
  onSelect,
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

    const allBounds: LatLon[] = [];

    zones.forEach((zone) => {
      const color = zone.color || (zone.scope === 'polygon' ? COLORS.polygon : COLORS.radius);
      const hint = zone.label || '';

      if (zone.scope === 'polygon' && zone.polygon && zone.polygon.length >= 3) {
        const poly = new ymaps.Polygon([zone.polygon], { hintContent: hint }, {
          fillColor: color + '26',
          strokeColor: color,
          strokeWidth: 2,
        });
        if (onSelectRef.current) poly.events.add('click', () => onSelectRef.current!(zone.id));
        map.geoObjects.add(poly);
        zone.polygon.forEach((p) => allBounds.push(p));
      } else {
        const cLat = num(zone.lat);
        const cLon = num(zone.lon);
        const r = num(zone.radiusKm);
        if (cLat === null || cLon === null) return;
        if (r && r > 0) {
          const circle = new ymaps.Circle([[cLat, cLon], r * 1000], { hintContent: hint }, {
            fillColor: color + '20',
            strokeColor: color,
            strokeWidth: 1,
          });
          if (onSelectRef.current) circle.events.add('click', () => onSelectRef.current!(zone.id));
          map.geoObjects.add(circle);
        }
        const placemark = new ymaps.Placemark([cLat, cLon], { iconCaption: hint, hintContent: hint }, {
          preset: 'islands#circleIcon',
          iconColor: color,
        });
        if (onSelectRef.current) placemark.events.add('click', () => onSelectRef.current!(zone.id));
        map.geoObjects.add(placemark);
        allBounds.push([cLat, cLon]);
      }
    });

    if (map.geoObjects.getLength() > 0) {
      try {
        map.setBounds(map.geoObjects.getBounds(), { checkZoomRange: true, zoomMargin: 40 });
      } catch {
        /* bo'sh */
      }
    }
    setTimeout(() => map.container.fitToViewport(), 200);
  }, [state, zones]);

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
      <div className="d-flex align-items-center justify-content-center text-muted small border rounded-4" style={{ height }}>
        <span><i className="bi bi-geo me-1" />Xaritali zona hali qo'shilmagan</span>
      </div>
    );
  }

  return <div ref={mapEl} style={{ height, borderRadius: 14, overflow: 'hidden' }} />;
}
