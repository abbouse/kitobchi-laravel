import { useEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import {
  VideoBook, VideoScene, VideoTemplate, W, H,
  drawFrame, durationOf, ensureFont, isInstagramReady, loadImage, money, pickMimeType, recordVideo,
} from '../utils/bookVideoRenderer';

type Props = {
  period: 'weekly' | 'monthly';
  set: {
    id: number;
    title: string;
    template: VideoTemplate;
    periodLabel: string;
    isCustomized: boolean;
    books: (VideoBook & { visible: boolean })[];
    updateUrl: string;
    autoPickUrl: string;
  };
  history: { id: number; label: string; title: string }[];
  templates: VideoTemplate[];
  maxBooks: number;
  searchUrl: string;
  convertUrl: string;
  assets: { icon: string; appStore: string; googlePlay: string };
};

const TEMPLATE_INFO: Record<VideoTemplate, { name: string; desc: string; icon: string }> = {
  carousel: { name: 'Karusel', desc: "Kitoblar birin-ketin o'ngdan kirib, chapga chiqib ketadi", icon: 'ti-arrows-horizontal' },
  countdown: { name: 'Top reyting', desc: '#N dan #1 gacha — eng ko\'p sotilgani oxirida', icon: 'ti-trophy' },
  grid: { name: "To'r (grid)", desc: "Kitoblar 2 ustunli to'rga ketma-ket tushadi (6 tagacha)", icon: 'ti-layout-grid' },
};

export default function BookVideos() {
  const { period, set, history, templates, maxBooks, searchUrl, convertUrl, assets } = usePage<Props>().props;

  const [title, setTitle] = useState(set.title);
  const [template, setTemplate] = useState<VideoTemplate>(set.template);
  const [books, setBooks] = useState<VideoBook[]>(set.books);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<VideoBook[]>([]);
  const [playing, setPlaying] = useState(true);
  const [recording, setRecording] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [images, setImages] = useState<Map<number, HTMLImageElement>>(new Map());
  const [loadedAssets, setLoadedAssets] = useState<VideoScene['assets']>({});
  const canvasRef = useRef<HTMLCanvasElement>(null);

  // Boshqa davr/to'plam ochilganda lokal holatni yangilaymiz
  useEffect(() => { setTitle(set.title); setTemplate(set.template); setBooks(set.books); }, [set.id, set.title, set.template, set.books]);

  const dirty = title !== set.title || template !== set.template
    || books.map((b) => b.id).join(',') !== set.books.map((b) => b.id).join(',');

  // Shrift, ikonka va store tugmalari
  useEffect(() => {
    void ensureFont();
    void Promise.all([loadImage(assets.icon), loadImage(assets.appStore), loadImage(assets.googlePlay)])
      .then(([icon, appStore, googlePlay]) => setLoadedAssets({ icon, appStore, googlePlay }));
  }, [assets.icon, assets.appStore, assets.googlePlay]);

  // Kitob rasmlari (boshqaruv domenidagi proksi orqali)
  useEffect(() => {
    const missing = books.filter((b) => b.hasImage && !images.has(b.id));
    if (!missing.length) return;
    void Promise.all(missing.map(async (b) => [b.id, await loadImage(b.imageUrl)] as const)).then((pairs) => {
      setImages((prev) => {
        const next = new Map(prev);
        pairs.forEach(([id, img]) => { if (img) next.set(id, img); });
        return next;
      });
    });
  }, [books, images]);

  const scene: VideoScene = useMemo(() => ({
    template, title, periodLabel: set.periodLabel, books, images, assets: loadedAssets,
  }), [template, title, set.periodLabel, books, images, loadedAssets]);
  const total = durationOf(template, books.length);

  // Jonli ko'rish (yozish paytida to'xtaydi — kadrni recorder chizadi)
  useEffect(() => {
    const canvas = canvasRef.current;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx || recording !== null || !books.length) return;
    let raf = 0;
    const start = performance.now();
    const loop = () => {
      const t = playing ? ((performance.now() - start) / 1000) % total : 3;
      drawFrame(ctx, scene, t);
      if (playing) raf = requestAnimationFrame(loop);
    };
    loop();
    return () => cancelAnimationFrame(raf);
  }, [scene, playing, recording, total, books.length]);

  // Qidiruv (debounce)
  useEffect(() => {
    if (query.trim().length < 2) { setResults([]); return; }
    const id = setTimeout(async () => {
      const res = await fetch(`${searchUrl}?q=${encodeURIComponent(query.trim())}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (res.ok) setResults((await res.json()).items ?? []);
    }, 300);
    return () => clearTimeout(id);
  }, [query, searchUrl]);

  const move = (i: number, d: -1 | 1) => setBooks((prev) => {
    const next = [...prev];
    const j = i + d;
    if (j < 0 || j >= next.length) return prev;
    [next[i], next[j]] = [next[j], next[i]];
    return next;
  });
  const remove = (id: number) => setBooks((prev) => prev.filter((b) => b.id !== id));
  const add = (b: VideoBook) => {
    setBooks((prev) => (prev.some((x) => x.id === b.id) || prev.length >= maxBooks ? prev : [...prev, b]));
    setQuery('');
    setResults([]);
  };

  const save = () => router.put(set.updateUrl, { title, template, book_ids: books.map((b) => b.id) }, { preserveScroll: true });
  const autoPick = () => {
    if (confirm("Kitoblar qaytadan avtomatik tanlanadi, qo'lda qilingan o'zgarishlar bekor bo'ladi. Davom etamizmi?")) {
      router.post(set.autoPickUrl, {}, { preserveScroll: true });
    }
  };

  const download = async () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    setError(null);
    setRecording(0);
    try {
      let { blob, ext, mime } = await recordVideo(canvas, scene, (p) => setRecording(p));
      // Ba'zi brauzerlar formatni "qo'llaydi" deb aytadi-yu, bo'sh fayl beradi — WebM bilan qayta yozamiz
      if (!blob.size) ({ blob, ext, mime } = await recordVideo(canvas, scene, (p) => setRecording(p), 'webm'));
      // Aniq H.264 bo'lmasa (WebM yoki kodeki noma'lum MP4) — serverda Instagram formatiga o'giramiz
      if (blob.size && !isInstagramReady(mime)) {
        setRecording(1);
        const form = new FormData();
        form.append('video', blob, 'video.webm');
        const res = await fetch(convertUrl, {
          method: 'POST', body: form, credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '' },
        });
        if (res.ok) { blob = await res.blob(); ext = 'mp4'; mime = 'video/mp4;codecs=avc1'; }
      }
      if (!blob.size) throw new Error('Video bo\'sh chiqdi. Google Chrome\'da qayta urinib ko\'ring.');
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `kitobchi-${period === 'monthly' ? 'oylik' : 'haftalik'}-${set.periodLabel.replace(/[^0-9]+/g, '-')}.${ext}`;
      a.click();
      setTimeout(() => URL.revokeObjectURL(url), 5000);
      if (!isInstagramReady(mime)) setError("Serverda MP4 (H.264) ga o'girib bo'lmadi — fayl Instagram'ga yuklanmasligi mumkin. Google Chrome'ning yangi versiyasidan foydalaning.");
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Video yozishda xatolik');
    } finally {
      setRecording(null);
    }
  };

  const mime = typeof window !== 'undefined' ? pickMimeType() : null;

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Kitob videolari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">
            Haftalik va oylik e'lon videolari. Tizim eng ko'p sotilgan kitoblarni o'zi tanlaydi — tarkib, tartib, sarlavha va shablonni o'zgartirishingiz mumkin. Video musiqasiz yuklab olinadi.
          </p>
        </div>
        <div className="btn-group">
          {(['weekly', 'monthly'] as const).map((p) => (
            <button key={p} type="button" className={`btn btn-sm ${period === p ? 'btn-primary' : 'btn-light-secondary'}`}
              onClick={() => router.get('/boshqaruv/book-videos', { period: p }, { preserveScroll: true })}>
              {p === 'weekly' ? 'Haftalik' : 'Oylik'}
            </button>
          ))}
        </div>
      </div>

      <div className="row g-3">
        <div className="col-lg-7">
          <div className="card">
            <div className="card-body">
              <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div className="d-flex align-items-center gap-2">
                  <span className="fw-semibold">Davr:</span>
                  <select className="form-select form-select-sm" style={{ width: 'auto' }} value={set.id}
                    onChange={(e) => router.get('/boshqaruv/book-videos', { period, set: e.target.value }, { preserveScroll: true })}>
                    {history.map((h) => <option key={h.id} value={h.id}>{h.label}</option>)}
                  </select>
                  <span className={`badge ${set.isCustomized ? 'text-light-warning' : 'text-light-success'}`}>
                    {set.isCustomized ? "Qo'lda tahrirlangan" : 'Avto tanlov'}
                  </span>
                </div>
                <button type="button" className="btn btn-light-secondary btn-sm" onClick={autoPick}>
                  <i className="ti ti-wand me-1"></i>Qaytadan avto tanlash
                </button>
              </div>

              <label className="form-label">Sarlavha</label>
              <input className="form-control mb-3" maxLength={120} value={title} onChange={(e) => setTitle(e.target.value)} />

              <label className="form-label">Shablon</label>
              <div className="row g-2 mb-3">
                {templates.map((tpl) => (
                  <div key={tpl} className="col-md-4">
                    <button type="button" onClick={() => setTemplate(tpl)}
                      className={`w-100 text-start p-3 rounded-3 border ${template === tpl ? 'border-primary bg-light-primary' : 'bg-white'}`}>
                      <div className="fw-semibold"><i className={`ti ${TEMPLATE_INFO[tpl].icon} me-1`}></i>{TEMPLATE_INFO[tpl].name}</div>
                      <small className="text-secondary">{TEMPLATE_INFO[tpl].desc}</small>
                    </button>
                  </div>
                ))}
              </div>

              <div className="d-flex justify-content-between align-items-center mb-2">
                <label className="form-label mb-0">Kitoblar ({books.length}/{maxBooks}) — videoda shu tartibda chiqadi</label>
              </div>
              <div className="position-relative mb-2">
                <input className="form-control" placeholder="Kitob qo'shish: nomi, muallif, ISBN yoki artikul…" value={query}
                  disabled={books.length >= maxBooks} onChange={(e) => setQuery(e.target.value)} />
                {results.length > 0 && (
                  <div className="list-group position-absolute w-100 shadow" style={{ zIndex: 20, maxHeight: 320, overflowY: 'auto' }}>
                    {results.map((r) => (
                      <button key={r.id} type="button" className="list-group-item list-group-item-action d-flex align-items-center gap-2"
                        disabled={books.some((b) => b.id === r.id)} onClick={() => add(r)}>
                        <img src={r.imageUrl} alt="" width={36} height={48} style={{ objectFit: 'cover', borderRadius: 6 }} />
                        <span className="flex-grow-1">{r.name}{r.author ? <small className="text-secondary"> — {r.author}</small> : null}</span>
                        <small>{money(r.price)}</small>
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <ul className="list-group mb-3">
                {books.map((b, i) => (
                  <li key={b.id} className="list-group-item d-flex align-items-center gap-2">
                    <span className="text-secondary" style={{ width: 22 }}>{i + 1}.</span>
                    <img src={b.imageUrl} alt="" width={40} height={54} style={{ objectFit: 'cover', borderRadius: 6 }} />
                    <div className="flex-grow-1">
                      <div className="fw-semibold">{b.name}</div>
                      <small className="text-secondary">{money(b.price)}</small>
                      {'visible' in b && (b as VideoBook & { visible: boolean }).visible === false ? (
                        <small className="text-danger ms-2">saytda ko'rinmaydi</small>
                      ) : null}
                    </div>
                    <button type="button" className="btn btn-light-secondary btn-sm" disabled={i === 0} onClick={() => move(i, -1)} title="Yuqoriga"><i className="ti ti-arrow-up"></i></button>
                    <button type="button" className="btn btn-light-secondary btn-sm" disabled={i === books.length - 1} onClick={() => move(i, 1)} title="Pastga"><i className="ti ti-arrow-down"></i></button>
                    <button type="button" className="btn btn-light-danger btn-sm" onClick={() => remove(b.id)} title="Olib tashlash"><i className="ti ti-trash"></i></button>
                  </li>
                ))}
                {!books.length && <li className="list-group-item text-secondary">Kitob qo'shing</li>}
              </ul>
              {template === 'grid' && books.length > 6 && (
                <div className="alert alert-light-warning py-2">To'r shablonida faqat birinchi 6 ta kitob chiqadi.</div>
              )}

              <button type="button" className="btn btn-primary" disabled={!dirty || !books.length || !title.trim()} onClick={save}>
                <i className="ti ti-device-floppy me-1"></i>Saqlash
              </button>
              {dirty && <small className="text-warning ms-2">Saqlanmagan o'zgarishlar bor</small>}
            </div>
          </div>
        </div>

        <div className="col-lg-5">
          <div className="card">
            <div className="card-body text-center">
              <canvas ref={canvasRef} width={W} height={H}
                style={{ width: 300, maxWidth: '100%', aspectRatio: '9 / 16', borderRadius: 18, boxShadow: '0 10px 30px rgba(0,0,0,.15)', background: '#F3F6FB' }} />
              <div className="small text-secondary mt-2">{Math.round(total)} soniya · 1080×1920 · musiqasiz</div>
              <div className="d-flex justify-content-center gap-2 mt-3">
                <button type="button" className="btn btn-light-secondary btn-sm" disabled={recording !== null} onClick={() => setPlaying((p) => !p)}>
                  <i className={`ti ${playing ? 'ti-player-pause' : 'ti-player-play'} me-1`}></i>{playing ? "To'xtatish" : "Ko'rish"}
                </button>
                <button type="button" className="btn btn-primary btn-sm" disabled={recording !== null || !books.length || !mime} onClick={download}>
                  <i className="ti ti-download me-1"></i>
                  {recording === null ? 'Videoni yuklab olish' : recording >= 1 ? "MP4 ga o'girilmoqda…" : `Yozilmoqda… ${Math.round(recording * 100)}%`}
                </button>
              </div>
              {recording !== null && (
                <div className="progress mt-2" style={{ height: 6 }}><div className="progress-bar" style={{ width: `${recording * 100}%` }}></div></div>
              )}
              <p className="small text-secondary mt-2 mb-0">
                Video real vaqtda yoziladi ({Math.round(total)} s) — shu vaqtda ushbu tabni yopmang va boshqa tabga o'tmang.
                {!mime && " Brauzeringiz video yozishni qo'llab-quvvatlamaydi — Google Chrome'dan foydalaning."}
              </p>
              {error && <div className="alert alert-light-warning mt-2 mb-0 small">{error}</div>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
