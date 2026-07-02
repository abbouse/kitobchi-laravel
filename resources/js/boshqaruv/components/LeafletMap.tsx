import { useEffect, useMemo, useRef, useState } from 'react';

// Leaflet global (CDN orqali app.blade.php da yuklanadi)
declare global {
  interface Window {
    L?: any;
  }
}

const TASHKENT: [number, number] = [41.2995, 69.2401];

type LatLon = { lat: number | null; lon: number | null };

function useLeafletReady(): boolean {
  const [ready, setReady] = useState<boolean>(typeof window !== 'undefined' && !!window.L);

  useEffect(() => {
    if (window.L) {
      setReady(true);
      return;
    }
    const timer = window.setInterval(() => {
      if (window.L) {
        setReady(true);
        window.clearInterval(timer);
      }
    }, 150);
    return () => window.clearInterval(timer);
  }, []);

  return ready;
}

function toNum(value: number | string | null | undefined): number | null {
  if (value === null || value === undefined || value === '') return null;
  const n = typeof value === 'string' ? parseFloat(value) : value;
  return Number.isFinite(n) ? n : null;
}

/**
 * Interaktiv nuqta tanlagich: xaritaga bosib yoki markerni sudrab
 * koordinatani belgilash + manzil bo'yicha qidiruv (Nominatim, bepul).
 */
export function LeafletMapPicker({
  lat,
  lon,
  onChange,
  height = 320,
  search = true,
}: {
  lat: number | string | null;
  lon: number | string | null;
  onChange: (coords: LatLon, address?: string) => void;
  height?: number;
  search?: boolean;
}) {
  const ready = useLeafletReady();
  const mapEl = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<any>(null);
  const markerRef = useRef<any>(null);
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;

  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Array<{ label: string; lat: number; lon: number }>>([]);
  const [searching, setSearching] = useState(false);

  const initialLat = toNum(lat);
  const initialLon = toNum(lon);

  // Xaritani bir marta yaratamiz
  useEffect(() => {
    if (!ready || !mapEl.current || mapRef.current) return;
    const L = window.L;

    const center: [number, number] = initialLat !== null && initialLon !== null ? [initialLat, initialLon] : TASHKENT;
    const map = L.map(mapEl.current, { center, zoom: initialLat !== null ? 15 : 12, scrollWheelZoom: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap',
      maxZoom: 19,
    }).addTo(map);

    const marker = L.marker(center, { draggable: true }).addTo(map);
    markerRef.current = marker;
    mapRef.current = map;

    const emit = (latlng: any) => {
      onChangeRef.current({ lat: Number(latlng.lat.toFixed(6)), lon: Number(latlng.lng.toFixed(6)) });
    };

    map.on('click', (event: any) => {
      marker.setLatLng(event.latlng);
      emit(event.latlng);
    });
    marker.on('dragend', () => emit(marker.getLatLng()));

    // Konteyner o'lchamini to'g'rilash (modal ichida ochilganda)
    setTimeout(() => map.invalidateSize(), 200);

    return () => {
      map.remove();
      mapRef.current = null;
      markerRef.current = null;
    };
  }, [ready]);

  // Tashqaridan lat/lon o'zgarsa markerni ko'chiramiz
  useEffect(() => {
    if (!mapRef.current || !markerRef.current) return;
    const nLat = toNum(lat);
    const nLon = toNum(lon);
    if (nLat === null || nLon === null) return;
    const current = markerRef.current.getLatLng();
    if (Math.abs(current.lat - nLat) > 1e-6 || Math.abs(current.lng - nLon) > 1e-6) {
      markerRef.current.setLatLng([nLat, nLon]);
      mapRef.current.setView([nLat, nLon], Math.max(mapRef.current.getZoom(), 14));
    }
  }, [lat, lon]);

  // Manzil qidiruvi (Nominatim)
  useEffect(() => {
    if (!search) return;
    const clean = query.trim();
    if (clean.length < 3) {
      setResults([]);
      return;
    }
    const timer = window.setTimeout(async () => {
      setSearching(true);
      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=uz&limit=5&q=${encodeURIComponent(clean)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = res.ok ? await res.json() : [];
        setResults(
          Array.isArray(data)
            ? data.map((row: any) => ({ label: row.display_name, lat: parseFloat(row.lat), lon: parseFloat(row.lon) }))
            : [],
        );
      } catch {
        setResults([]);
      } finally {
        setSearching(false);
      }
    }, 500);
    return () => window.clearTimeout(timer);
  }, [query, search]);

  const pick = (row: { label: string; lat: number; lon: number }) => {
    setResults([]);
    setQuery(row.label.split(',').slice(0, 2).join(','));
    if (mapRef.current && markerRef.current) {
      markerRef.current.setLatLng([row.lat, row.lon]);
      mapRef.current.setView([row.lat, row.lon], 16);
    }
    onChangeRef.current({ lat: Number(row.lat.toFixed(6)), lon: Number(row.lon.toFixed(6)) }, row.label);
  };

  if (!ready) {
    return (
      <div className="d-flex align-items-center justify-content-center text-muted small border rounded-3" style={{ height }}>
        <span><span className="spinner-border spinner-border-sm me-2"></span>Xarita yuklanmoqda…</span>
      </div>
    );
  }

  return (
    <div>
      {search ? (
        <div className="position-relative mb-2">
          <i className="bi bi-geo-alt position-absolute" style={{ left: 12, top: 10, color: '#9CA3AF' }}></i>
          <input
            className="form-control"
            style={{ paddingLeft: 34 }}
            placeholder="Manzil qidiring (masalan: Chilonzor, Toshkent)"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          {searching ? <span className="spinner-border spinner-border-sm position-absolute" style={{ right: 12, top: 11 }}></span> : null}
          {results.length > 0 ? (
            <div className="position-absolute w-100 bg-white border rounded-3 shadow-sm mt-1" style={{ zIndex: 1200, maxHeight: 200, overflowY: 'auto' }}>
              {results.map((row, index) => (
                <button
                  key={index}
                  type="button"
                  className="btn btn-light w-100 text-start small border-0"
                  style={{ borderRadius: 0 }}
                  onClick={() => pick(row)}
                >
                  <i className="bi bi-geo-alt-fill text-danger me-1"></i>{row.label}
                </button>
              ))}
            </div>
          ) : null}
        </div>
      ) : null}
      <div ref={mapEl} style={{ height, borderRadius: 12, overflow: 'hidden', zIndex: 1 }} />
      <div className="small text-muted mt-1">
        <i className="bi bi-info-circle me-1"></i>Xaritaga bosing yoki markerni sudrab joyni belgilang.
      </div>
    </div>
  );
}

/**
 * Faqat ko'rish uchun: bir yoki bir nechta markerni xaritada ko'rsatadi.
 */
export function LeafletMapView({
  markers,
  height = 260,
  zoom = 12,
}: {
  markers: Array<{ lat: number | string | null; lon: number | string | null; label?: string; color?: string }>;
  height?: number;
  zoom?: number;
}) {
  const ready = useLeafletReady();
  const mapEl = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<any>(null);

  const points = useMemo(
    () =>
      markers
        .map((m) => ({ ...m, lat: toNum(m.lat), lon: toNum(m.lon) }))
        .filter((m) => m.lat !== null && m.lon !== null) as Array<{ lat: number; lon: number; label?: string; color?: string }>,
    [markers],
  );

  useEffect(() => {
    if (!ready || !mapEl.current) return;
    const L = window.L;

    if (!mapRef.current) {
      mapRef.current = L.map(mapEl.current, { center: points[0] ? [points[0].lat, points[0].lon] : TASHKENT, zoom });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapRef.current);
    }

    const map = mapRef.current;
    const layer = L.layerGroup().addTo(map);

    points.forEach((p) => {
      const icon = L.divIcon({
        className: '',
        html: `<div style="width:16px;height:16px;border-radius:50%;background:${p.color || '#4f46e5'};border:2px solid #fff;box-shadow:0 0 0 2px ${p.color || '#4f46e5'}55"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8],
      });
      const marker = L.marker([p.lat, p.lon], { icon }).addTo(layer);
      if (p.label) marker.bindPopup(p.label);
    });

    if (points.length > 1) {
      map.fitBounds(points.map((p) => [p.lat, p.lon]), { padding: [30, 30] });
    } else if (points.length === 1) {
      map.setView([points[0].lat, points[0].lon], Math.max(zoom, 14));
    }

    setTimeout(() => map.invalidateSize(), 200);

    return () => {
      layer.remove();
    };
  }, [ready, points, zoom]);

  useEffect(() => () => {
    if (mapRef.current) {
      mapRef.current.remove();
      mapRef.current = null;
    }
  }, []);

  if (!ready) {
    return (
      <div className="d-flex align-items-center justify-content-center text-muted small border rounded-3" style={{ height }}>
        Xarita yuklanmoqda…
      </div>
    );
  }

  if (points.length === 0) {
    return (
      <div className="d-flex align-items-center justify-content-center text-muted small border rounded-3" style={{ height }}>
        <span><i className="bi bi-geo me-1"></i>Koordinata belgilanmagan</span>
      </div>
    );
  }

  return <div ref={mapEl} style={{ height, borderRadius: 12, overflow: 'hidden', zIndex: 1 }} />;
}
