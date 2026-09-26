/**
 * KITOB VIDEOLARI — 1080×1920 Reels videosini canvas'da chizish va yozib olish.
 *
 * Kitoblar ilovadagi kitob kartasi ko'rinishida chiziladi (oq karta, rasm,
 * nom, kulrang narx plashkasi + yurakcha). Uch xil shablon: carousel,
 * countdown, grid. Musiqa qo'shilmaydi — video ovozsiz yoziladi.
 */

export type VideoTemplate = 'carousel' | 'countdown' | 'grid';

export interface VideoBook {
  id: number;
  name: string;
  author?: string | null;
  price: number;
  oldPrice?: number | null;
  imageUrl: string;
  hasImage: boolean;
}

export interface VideoAssets {
  icon: string;
  appStore: string;
  googlePlay: string;
}

export interface VideoScene {
  template: VideoTemplate;
  title: string;
  periodLabel: string;
  books: VideoBook[];
  images: Map<number, HTMLImageElement>;
  assets: { icon?: HTMLImageElement; appStore?: HTMLImageElement; googlePlay?: HTMLImageElement };
}

export const W = 1080;
export const H = 1920;
const FONT = "Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif";
const INK = '#0B1220';
const BLUE = '#2178D7';
const MUTED = '#6B7280';
const RED = '#F0144B';

const INTRO = 2.2;
const OUTRO = 3.4;
const PER_BOOK = 2.4;
const GRID_MAX = 6;

// ── easing / helpers ─────────────────────────────────────────────
const clamp = (v: number, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const easeOut = (x: number) => 1 - Math.pow(1 - clamp(x), 3);
const easeInOut = (x: number) => { const t = clamp(x); return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; };
const backOut = (x: number) => { const t = clamp(x); const c1 = 1.70158; const c3 = c1 + 1; return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2); };
const prog = (t: number, start: number, dur: number) => clamp((t - start) / dur);

export const money = (value: number) => `${new Intl.NumberFormat('en-US').format(Math.round(value || 0))} so'm`;

export function durationOf(template: VideoTemplate, count: number): number {
  const n = Math.max(1, count);
  if (template === 'grid') return INTRO + 0.35 * Math.min(n, GRID_MAX) + 3.2 + OUTRO;
  return INTRO + PER_BOOK * n + OUTRO;
}

// ── assets ───────────────────────────────────────────────────────
export function loadImage(src: string): Promise<HTMLImageElement | undefined> {
  return new Promise((resolve) => {
    const img = new Image();
    img.decoding = 'async';
    img.onload = () => resolve(img);
    img.onerror = () => resolve(undefined);
    img.src = src;
  });
}

let fontPromise: Promise<void> | null = null;
/** Inter shriftini bir marta yuklaydi (bo'lmasa tizim shrifti ishlatiladi). */
export function ensureFont(): Promise<void> {
  if (fontPromise) return fontPromise;
  fontPromise = (async () => {
    if (!document.querySelector('link[data-book-video-font]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap';
      link.dataset.bookVideoFont = '1';
      document.head.appendChild(link);
    }
    try {
      await Promise.race([
        Promise.all(['500 40px Inter', '700 40px Inter', '800 40px Inter'].map((f) => document.fonts.load(f))),
        new Promise((r) => setTimeout(r, 2500)),
      ]);
    } catch {
      /* shrift yuklanmasa tizim shrifti bilan chizamiz */
    }
  })();
  return fontPromise;
}

// ── primitives ───────────────────────────────────────────────────
function roundRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
  const rr = Math.min(r, w / 2, h / 2);
  ctx.beginPath();
  ctx.moveTo(x + rr, y);
  ctx.arcTo(x + w, y, x + w, y + h, rr);
  ctx.arcTo(x + w, y + h, x, y + h, rr);
  ctx.arcTo(x, y + h, x, y, rr);
  ctx.arcTo(x, y, x + w, y, rr);
  ctx.closePath();
}

function heartPath(ctx: CanvasRenderingContext2D, cx: number, cy: number, s: number) {
  // 24×24 viewBox'dagi yurakcha, markazi (cx, cy), o'lchami s
  const k = s / 24;
  const X = (v: number) => cx + (v - 12) * k;
  const Y = (v: number) => cy + (v - 12.5) * k;
  ctx.beginPath();
  ctx.moveTo(X(12), Y(20.5));
  ctx.bezierCurveTo(X(12), Y(20.5), X(4.5), Y(15.9), X(2.7), Y(11.3));
  ctx.bezierCurveTo(X(1.4), Y(8), X(3.4), Y(4.5), X(7), Y(4.5));
  ctx.bezierCurveTo(X(9.1), Y(4.5), X(10.6), Y(5.7), X(12), Y(7.5));
  ctx.bezierCurveTo(X(13.4), Y(5.7), X(14.9), Y(4.5), X(17), Y(4.5));
  ctx.bezierCurveTo(X(20.6), Y(4.5), X(22.6), Y(8), X(21.3), Y(11.3));
  ctx.bezierCurveTo(X(19.5), Y(15.9), X(12), Y(20.5), X(12), Y(20.5));
  ctx.closePath();
}

function wrapLines(ctx: CanvasRenderingContext2D, text: string, maxWidth: number, maxLines: number): string[] {
  const words = text.split(/\s+/).filter(Boolean);
  const lines: string[] = [];
  let line = '';
  for (let i = 0; i < words.length; i++) {
    const test = line ? `${line} ${words[i]}` : words[i];
    if (ctx.measureText(test).width <= maxWidth || !line) {
      line = test;
      continue;
    }
    lines.push(line);
    line = words[i];
    if (lines.length === maxLines - 1) {
      // oxirgi qatorga qolgan hammasi, sig'masa "…"
      let rest = words.slice(i).join(' ');
      while (ctx.measureText(`${rest}…`).width > maxWidth && rest.length > 1) rest = rest.slice(0, -1);
      lines.push(rest.length < words.slice(i).join(' ').length ? `${rest.trimEnd()}…` : rest);
      return lines;
    }
  }
  if (line) lines.push(line);
  return lines.slice(0, maxLines);
}

function drawCover(ctx: CanvasRenderingContext2D, img: HTMLImageElement, x: number, y: number, w: number, h: number) {
  const ir = img.naturalWidth / img.naturalHeight;
  const r = w / h;
  let sw = img.naturalWidth, sh = img.naturalHeight, sx = 0, sy = 0;
  if (ir > r) { sw = sh * r; sx = (img.naturalWidth - sw) / 2; } else { sh = sw / r; sy = (img.naturalHeight - sh) / 2; }
  ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
}

/** Ilovadagi kitob kartasi. (x, y) — chap-yuqori burchak, w — kenglik; balandlik = w × 1.56. */
export function drawBookCard(ctx: CanvasRenderingContext2D, book: VideoBook, img: HTMLImageElement | undefined, x: number, y: number, w: number) {
  const s = w / 800; // dizayn 800px kenglikda chizilgan
  const h = 1250 * s;
  ctx.save();
  ctx.shadowColor = 'rgba(15,42,79,0.22)';
  ctx.shadowBlur = 70 * s;
  ctx.shadowOffsetY = 30 * s;
  roundRect(ctx, x, y, w, h, 70 * s);
  ctx.fillStyle = '#FFFFFF';
  ctx.fill();
  ctx.restore();

  // rasm
  const pad = 34 * s;
  const iw = w - pad * 2;
  const ih = 760 * s;
  ctx.save();
  roundRect(ctx, x + pad, y + pad, iw, ih, 52 * s);
  ctx.clip();
  ctx.fillStyle = '#E9EEF5';
  ctx.fillRect(x + pad, y + pad, iw, ih);
  if (img) drawCover(ctx, img, x + pad, y + pad, iw, ih);
  ctx.restore();

  // chegirma belgisi ("-9%") — rasmning chap yuqori burchagida
  if (book.oldPrice && book.oldPrice > book.price) {
    const pct = Math.floor(((book.oldPrice - book.price) / book.oldPrice) * 100);
    if (pct > 0) {
      const label = `-${pct}%`;
      ctx.font = `700 ${56 * s}px ${FONT}`;
      const bw = ctx.measureText(label).width + 64 * s;
      roundRect(ctx, x + pad + 60 * s, y + pad + 40 * s, bw, 110 * s, 28 * s);
      ctx.fillStyle = '#2E9BF0';
      ctx.fill();
      ctx.fillStyle = '#FFFFFF';
      ctx.textBaseline = 'middle';
      ctx.fillText(label, x + pad + 60 * s + 32 * s, y + pad + 97 * s);
      ctx.textBaseline = 'alphabetic';
    }
  }

  // nom (2 qator)
  ctx.fillStyle = INK;
  ctx.font = `600 ${58 * s}px ${FONT}`;
  ctx.textBaseline = 'alphabetic';
  const lines = wrapLines(ctx, book.name, iw - 20 * s, 2);
  lines.forEach((ln, i) => ctx.fillText(ln, x + pad + 18 * s, y + pad + ih + 96 * s + i * 72 * s));

  // narx plashkasi
  const py = y + h - pad - 190 * s;
  roundRect(ctx, x + pad + 18 * s, py, iw - 36 * s, 190 * s, 56 * s);
  ctx.fillStyle = '#F1F2F4';
  ctx.fill();
  const discounted = !!book.oldPrice && book.oldPrice > book.price;
  if (discounted) {
    // ilovadagidek: eski narx chizilgan (qizil chiziq), yangi narx qizil
    const ox = x + pad + 70 * s;
    ctx.fillStyle = '#374151';
    ctx.font = `500 ${46 * s}px ${FONT}`;
    const oldText = money(book.oldPrice!);
    ctx.fillText(oldText, ox, py + 78 * s);
    ctx.strokeStyle = RED;
    ctx.lineWidth = 5 * s;
    ctx.beginPath();
    ctx.moveTo(ox - 4 * s, py + 62 * s);
    ctx.lineTo(ox + ctx.measureText(oldText).width + 4 * s, py + 62 * s);
    ctx.stroke();
    ctx.fillStyle = RED;
    ctx.font = `700 ${62 * s}px ${FONT}`;
    ctx.fillText(money(book.price), ox, py + 150 * s);
  } else {
    ctx.fillStyle = INK;
    ctx.font = `700 ${62 * s}px ${FONT}`;
    ctx.fillText(money(book.price), x + pad + 70 * s, py + 118 * s);
  }
  // yurakcha tugmasi
  const bx = x + w - pad - 18 * s - 30 * s - 140 * s;
  roundRect(ctx, bx, py + 25 * s, 140 * s, 140 * s, 36 * s);
  ctx.fillStyle = '#FFFFFF';
  ctx.fill();
  heartPath(ctx, bx + 70 * s, py + 95 * s, 76 * s);
  ctx.lineWidth = 5 * s;
  ctx.strokeStyle = '#4B5563';
  ctx.stroke();
}

export const CARD_RATIO = 1250 / 800;

// ── background & text ────────────────────────────────────────────
function background(ctx: CanvasRenderingContext2D, t: number) {
  ctx.fillStyle = '#F3F6FB';
  ctx.fillRect(0, 0, W, H);
  const blobs: [number, number, number, string][] = [
    [150 + 160 * Math.sin(t * 0.25), 300 + 120 * Math.cos(t * 0.2), 700, 'rgba(160,205,255,0.75)'],
    [950 - 140 * Math.sin(t * 0.22), 1000 + 150 * Math.sin(t * 0.18), 650, 'rgba(214,200,255,0.70)'],
    [300 + 180 * Math.cos(t * 0.2), 1750 - 120 * Math.sin(t * 0.25), 700, 'rgba(255,214,196,0.70)'],
  ];
  for (const [bx, by, r, c] of blobs) {
    const g = ctx.createRadialGradient(bx, by, 0, bx, by, r);
    g.addColorStop(0, c);
    g.addColorStop(1, 'rgba(243,246,251,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
  }
}

function text(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, alpha = 1, align: CanvasTextAlign = 'center') {
  ctx.save();
  ctx.globalAlpha = alpha;
  ctx.fillStyle = color;
  ctx.font = `${weight} ${size}px ${FONT}`;
  ctx.textAlign = align;
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(str, x, y);
  ctx.restore();
}

/** Sarlavhani 2 qatorga bo'ladi: oxirgi so'zlar ko'k urg'u bilan. */
function headline(ctx: CanvasRenderingContext2D, title: string, periodLabel: string, a: number, y = 250) {
  const words = title.trim().split(/\s+/);
  const cut = words.length > 2 ? Math.ceil(words.length / 2) : words.length - 1;
  const l1 = words.slice(0, Math.max(1, cut)).join(' ');
  const l2 = words.slice(Math.max(1, cut)).join(' ');
  const rise = (1 - easeOut(a)) * 40;
  text(ctx, l1, W / 2, y + rise, 66, 700, INK, easeOut(a));
  if (l2) text(ctx, l2, W / 2, y + 100 + rise, 92, 800, BLUE, easeOut(a));
  if (periodLabel) {
    ctx.save();
    ctx.globalAlpha = easeOut(prog(a, 0.3, 0.7));
    ctx.font = `600 34px ${FONT}`;
    const tw = ctx.measureText(periodLabel).width + 56;
    roundRect(ctx, W / 2 - tw / 2, y + 140, tw, 64, 32);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fill();
    ctx.fillStyle = MUTED;
    ctx.textAlign = 'center';
    ctx.fillText(periodLabel, W / 2, y + 184);
    ctx.restore();
  }
}

function outro(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const icon = scene.assets.icon;
  const p = backOut(prog(t, 0, 0.7));
  if (icon) {
    const s = 190 * p;
    ctx.save();
    ctx.globalAlpha = clamp(p);
    ctx.drawImage(icon, W / 2 - s / 2, 560 + (190 - s) / 2, s, s);
    ctx.restore();
  }
  text(ctx, 'Kitobchi', W / 2, 890, 118, 700, INK, easeOut(prog(t, 0.25, 0.5)));
  text(ctx, 'Ilovani yuklab oling', W / 2, 1010, 50, 600, '#4B5563', easeOut(prog(t, 0.55, 0.5)));
  const badges = [scene.assets.appStore, scene.assets.googlePlay];
  badges.forEach((b, i) => {
    if (!b) return;
    const a = easeOut(prog(t, 0.8 + i * 0.15, 0.5));
    ctx.save();
    ctx.globalAlpha = a;
    const bw = 370, bh = 111;
    const bx = W / 2 - bw - 12 + i * (bw + 24);
    const by = 1080 + (1 - a) * 40;
    roundRect(ctx, bx, by, bw, bh, 20);
    ctx.fillStyle = '#000';
    ctx.fill();
    ctx.drawImage(b, bx, by, bw, bh);
    ctx.restore();
  });
}

// ── templates ────────────────────────────────────────────────────
function carousel(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  headline(ctx, s.title, s.periodLabel, prog(t, 0.1, 0.8));
  const cw = 560, ch = cw * CARD_RATIO, cy = 560;
  s.books.forEach((b, i) => {
    const t0 = INTRO + i * PER_BOOK;
    const local = t - t0;
    if (local < -0.6 || local > PER_BOOK + 0.1) return;
    const inP = easeInOut(prog(local, -0.6, 0.7));
    const outP = easeInOut(prog(local, PER_BOOK - 0.6, 0.7));
    const x = W / 2 - cw / 2 + (1 - inP) * 900 - outP * 900;
    const rot = ((1 - inP) * 8 - outP * 8) * Math.PI / 180;
    const float = Math.sin(local * 2.2) * 8;
    ctx.save();
    ctx.globalAlpha = clamp(inP * 1.4) * clamp((1 - outP) * 1.4);
    ctx.translate(x + cw / 2, cy + ch / 2 + float);
    ctx.rotate(rot);
    drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
    ctx.restore();
    // hisoblagich
    text(ctx, `${String(i + 1).padStart(2, '0')} / ${String(s.books.length).padStart(2, '0')}`, W / 2, cy + ch + 90, 34, 600, MUTED, clamp(inP) * clamp(1 - outP));
  });
}

function countdown(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  headline(ctx, s.title, s.periodLabel, prog(t, 0.1, 0.8));
  const n = s.books.length;
  const cw = 520, ch = cw * CARD_RATIO, cy = 640;
  // oxirgidan birinchiga: #n … #1
  for (let k = 0; k < n; k++) {
    const idx = n - 1 - k;
    const b = s.books[idx];
    const t0 = INTRO + k * PER_BOOK;
    const local = t - t0;
    if (local < 0 || local > PER_BOOK) continue;
    const pop = backOut(prog(local, 0, 0.55));
    const out = easeInOut(prog(local, PER_BOOK - 0.35, 0.35));
    // katta raqam
    ctx.save();
    ctx.globalAlpha = clamp(prog(local, 0, 0.3)) * (1 - out);
    ctx.fillStyle = 'rgba(33,120,215,0.14)';
    ctx.font = `800 ${520}px ${FONT}`;
    ctx.textAlign = 'center';
    ctx.fillText(`${idx + 1}`, W / 2, cy + ch * 0.62);
    ctx.restore();
    // "#1" belgisi
    ctx.save();
    ctx.globalAlpha = (1 - out);
    const bs = 1 + (1 - clamp(pop)) * 0.4;
    ctx.translate(W / 2 + cw / 2 - 10, cy + 10);
    ctx.scale(bs, bs);
    ctx.beginPath();
    ctx.arc(0, 0, 70, 0, Math.PI * 2);
    ctx.fillStyle = idx === 0 ? '#FFB020' : BLUE;
    ctx.fill();
    ctx.fillStyle = '#fff';
    ctx.font = `800 64px ${FONT}`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(`#${idx + 1}`, 0, 4);
    ctx.restore();
    // karta
    ctx.save();
    ctx.globalAlpha = clamp(pop) * (1 - out);
    const sc = 0.7 + 0.3 * pop - out * 0.1;
    ctx.translate(W / 2, cy + ch / 2);
    ctx.scale(sc, sc);
    drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
    ctx.restore();
  }
}

function grid(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  headline(ctx, s.title, s.periodLabel, prog(t, 0.1, 0.8));
  const books = s.books.slice(0, GRID_MAX);
  const cols = books.length > 4 ? 3 : 2;
  const rows = Math.ceil(books.length / cols);
  const gap = 32;
  const top = 600;
  const avail = 1740 - top;
  // karta kengligi ham eniga, ham bo'yiga sig'adigan qilib tanlanadi
  const cw = Math.min(380, (W - 120 - (cols - 1) * gap) / cols, (avail - (rows - 1) * gap) / rows / CARD_RATIO);
  const ch = cw * CARD_RATIO;
  const gridW = cols * cw + (cols - 1) * gap;
  const gridH = rows * ch + (rows - 1) * gap;
  const y0 = top + Math.max(0, (avail - gridH) / 2);
  books.forEach((b, i) => {
    const r = Math.floor(i / cols), c = i % cols;
    // oxirgi qatordagi to'liq bo'lmagan kartalar markazlashtiriladi
    const inRow = r === rows - 1 ? books.length - r * cols : cols;
    const rowW = inRow * cw + (inRow - 1) * gap;
    const x = W / 2 - (inRow === cols ? gridW : rowW) / 2 + c * (cw + gap);
    const y = y0 + r * (ch + gap);
    const p = backOut(prog(t, INTRO + i * 0.35, 0.6));
    if (p <= 0) return;
    const float = Math.sin((t + i) * 1.8) * 6;
    ctx.save();
    ctx.globalAlpha = clamp(p);
    ctx.translate(x + cw / 2, y + ch / 2 + (1 - clamp(p)) * 120 + float);
    ctx.scale(0.8 + 0.2 * p, 0.8 + 0.2 * p);
    drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
    ctx.restore();
  });
}

/** Bitta kadrni chizadi: t — soniya. */
export function drawFrame(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const total = durationOf(scene.template, scene.books.length);
  const outroAt = total - OUTRO;
  background(ctx, t);
  if (t < outroAt) {
    const fade = 1 - easeInOut(prog(t, outroAt - 0.4, 0.4));
    ctx.save();
    ctx.globalAlpha = fade;
    if (scene.template === 'countdown') countdown(ctx, scene, t);
    else if (scene.template === 'grid') grid(ctx, scene, t);
    else carousel(ctx, scene, t);
    ctx.restore();
  } else {
    outro(ctx, scene, t - outroAt);
  }
}

// ── recording ────────────────────────────────────────────────────
// H.264 1080×1920 uchun kamida 4.0-daraja kerak (…1F = 3.1 — faqat 720p gacha).
// VP9 sekin kompyuterlarda 1080×1920 da bo'sh fayl berishi mumkin, shuning uchun VP8.
const MIME_CANDIDATES = [
  'video/mp4;codecs=avc1.640033',
  'video/mp4;codecs=avc1.4D0033',
  'video/mp4;codecs=avc1.42E033',
  'video/mp4;codecs=avc1',
  'video/mp4',
  'video/webm;codecs=vp8',
  'video/webm',
];

export function pickMimeType(format: 'any' | 'webm' = 'any'): string | null {
  return mimeCandidates(format)[0] ?? null;
}

function mimeCandidates(format: 'any' | 'webm'): string[] {
  if (typeof MediaRecorder === 'undefined') return [];
  const list = format === 'webm' ? MIME_CANDIDATES.filter((m) => m.startsWith('video/webm')) : MIME_CANDIDATES;
  return list.filter((m) => MediaRecorder.isTypeSupported(m));
}

/** Yozilgan fayl aniq H.264 (Instagram uchun tayyor) ekanmi. */
export const isInstagramReady = (mime: string) => mime.includes('avc1');

/**
 * Videoni real vaqtda yozadi (davomiyligi — shablon uzunligi). Brauzer
 * tabi ochiq turishi kerak: fonda requestAnimationFrame to'xtab qoladi.
 */
export function recordVideo(
  canvas: HTMLCanvasElement,
  scene: VideoScene,
  onProgress: (p: number) => void,
  format: 'any' | 'webm' = 'any',
): Promise<{ blob: Blob; ext: 'mp4' | 'webm'; mime: string }> {
  const ctx = canvas.getContext('2d');
  if (!ctx) return Promise.reject(new Error('Canvas topilmadi'));

  const total = durationOf(scene.template, scene.books.length);
  const stream = canvas.captureStream(30);
  // isTypeSupported "ha" desa ham konstruktor rad etishi mumkin — navbatdagisini sinaymiz
  let recorder: MediaRecorder | null = null;
  let mimeType = '';
  for (const m of mimeCandidates(format)) {
    try {
      recorder = new MediaRecorder(stream, { mimeType: m, videoBitsPerSecond: 10_000_000 });
      mimeType = m;
      break;
    } catch {
      /* keyingi format */
    }
  }
  if (!recorder) {
    stream.getTracks().forEach((tr) => tr.stop());
    return Promise.reject(new Error("Bu brauzer videoni yozishni qo'llab-quvvatlamaydi. Google Chrome'dan foydalaning."));
  }
  const chunks: BlobPart[] = [];
  recorder.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };

  return new Promise((resolve, reject) => {
    recorder!.onerror = () => reject(new Error('Video yozishda xatolik'));
    recorder!.onstop = () => {
      stream.getTracks().forEach((tr) => tr.stop());
      const ext = mimeType.startsWith('video/mp4') ? 'mp4' : 'webm';
      resolve({ blob: new Blob(chunks, { type: mimeType.split(';')[0] }), ext, mime: mimeType });
    };
    drawFrame(ctx, scene, 0);
    recorder!.start(250);
    const start = performance.now();
    const tick = () => {
      const t = (performance.now() - start) / 1000;
      drawFrame(ctx, scene, Math.min(t, total));
      onProgress(clamp(t / total));
      if (t < total) requestAnimationFrame(tick);
      else setTimeout(() => recorder!.stop(), 150);
    };
    requestAnimationFrame(tick);
  });
}
