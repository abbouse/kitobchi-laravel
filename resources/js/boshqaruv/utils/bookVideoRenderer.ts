/**
 * KITOB VIDEOLARI — 1080×1920 Reels videosini canvas'da chizish va yozib olish.
 *
 * Kitoblar ilovadagi kitob kartasi ko'rinishida chiziladi (oq karta, rasm,
 * nom, kulrang narx plashkasi + yurakcha). Uslub — kinematografik: zarbga
 * mos kesimlar, flash + punch-in, kinetik matn, muqovadan xira fon, grain.
 * Uch xil shablon: carousel, countdown, grid. Video ovozsiz yoziladi.
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

// Sahnalar 110 BPM zarbiga moslangan — keyin qo'yiladigan musiqa bilan kesimlar mos tushadi.
export const BEAT = 60 / 110;
const INTRO = BEAT * 5;
const OUTRO = BEAT * 7;
const PER_BOOK = BEAT * 4;
const GRID_STEP = BEAT / 2;
const GRID_HOLD = BEAT * 6;
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
  if (template === 'grid') return INTRO + BEAT + GRID_STEP * Math.min(n, GRID_MAX) + GRID_HOLD + OUTRO;
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
  setFont(ctx, 40, 600); // sarlavhalardagi harf oralig'i kartaga o'tmasin
  ctx.textAlign = 'left';
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
  ctx.restore();
}

export const CARD_RATIO = 1250 / 800;

// ── cinematic layer: backdrops, grain, flashes ───────────────────
const blurCache = new WeakMap<HTMLImageElement, HTMLCanvasElement>();
/** Muqovaning xira, past o'lchamli nusxasi — to'liq ekranga cho'zilganda yumshoq fon beradi. */
function blurredOf(img: HTMLImageElement): HTMLCanvasElement {
  let c = blurCache.get(img);
  if (c) return c;
  c = document.createElement('canvas');
  c.width = 180;
  c.height = 320;
  const x = c.getContext('2d')!;
  x.filter = 'blur(7px) saturate(1.3)';
  drawCover(x, img, -24, -24, 228, 368);
  blurCache.set(img, c);
  return c;
}

/** Kitob muqovasidan kinematografik fon: xira, sekin kattalashadi, qoraytirilgan. */
function coverBackdrop(ctx: CanvasRenderingContext2D, img: HTMLImageElement | undefined, zoom: number, dark: number) {
  ctx.fillStyle = '#07090F';
  ctx.fillRect(0, 0, W, H);
  if (img) {
    ctx.save();
    ctx.translate(W / 2, H / 2);
    ctx.scale(zoom, zoom);
    ctx.imageSmoothingQuality = 'low'; // manba allaqachon xira — bilinear yetarli va tez
    ctx.drawImage(blurredOf(img), -W / 2, -H / 2, W, H);
    ctx.restore();
  }
  ctx.fillStyle = `rgba(7,9,16,${dark})`;
  ctx.fillRect(0, 0, W, H);
  const g = ctx.createLinearGradient(0, H * 0.45, 0, H);
  g.addColorStop(0, 'rgba(7,9,16,0)');
  g.addColorStop(1, 'rgba(7,9,16,0.9)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
}

function radialBg(ctx: CanvasRenderingContext2D, stops: [number, string][], cy = 0.45) {
  const g = ctx.createRadialGradient(W / 2, H * cy, 0, W / 2, H * cy, H * 0.75);
  stops.forEach(([o, c]) => g.addColorStop(o, c));
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
}

function rays(ctx: CanvasRenderingContext2D, cx: number, cy: number, rot: number, color: string, alpha: number) {
  ctx.save();
  ctx.globalAlpha = alpha;
  ctx.translate(cx, cy);
  ctx.rotate(rot);
  const g = ctx.createRadialGradient(0, 0, 60, 0, 0, 1000);
  g.addColorStop(0, color);
  g.addColorStop(1, 'rgba(255,255,255,0)');
  ctx.fillStyle = g;
  for (let i = 0; i < 20; i++) {
    const a = (i / 20) * Math.PI * 2;
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.arc(0, 0, 1400, a, a + Math.PI / 40);
    ctx.closePath();
    ctx.fill();
  }
  ctx.restore();
}

let grainTile: HTMLCanvasElement | null = null;
const GRAIN_OFFSETS = [[0, 0], [-61, 37], [43, -79], [-87, -21], [29, 88], [-38, -57], [79, 31], [-19, 68]];
function grain(ctx: CanvasRenderingContext2D, t: number) {
  if (!grainTile) {
    grainTile = document.createElement('canvas');
    grainTile.width = grainTile.height = 256;
    const x = grainTile.getContext('2d')!;
    const d = x.createImageData(256, 256);
    let seed = 7;
    for (let i = 0; i < d.data.length; i += 4) {
      seed = (seed * 16807) % 2147483647;
      const v = (seed / 2147483647) * 255;
      d.data[i] = d.data[i + 1] = d.data[i + 2] = v;
      d.data[i + 3] = 255;
    }
    x.putImageData(d, 0, 0);
  }
  const pat = ctx.createPattern(grainTile, 'repeat');
  if (!pat) return;
  const [ox, oy] = GRAIN_OFFSETS[Math.floor(t * 30) % GRAIN_OFFSETS.length];
  ctx.save();
  // oddiy (source-over) aralashtirish — overlay dasturiy renderda juda sekin
  ctx.globalAlpha = 0.035;
  ctx.translate(ox, oy);
  ctx.fillStyle = pat;
  ctx.fillRect(-ox, -oy, W, H);
  ctx.restore();
}

function vignette(ctx: CanvasRenderingContext2D) {
  const g = ctx.createRadialGradient(W / 2, H / 2, H * 0.32, W / 2, H / 2, H * 0.62);
  g.addColorStop(0, 'rgba(0,0,0,0)');
  g.addColorStop(1, 'rgba(0,0,0,0.24)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
}

// ── kinetic type ─────────────────────────────────────────────────
function setFont(ctx: CanvasRenderingContext2D, size: number, weight: number, spacing = 0) {
  ctx.font = `${weight} ${size}px ${FONT}`;
  if ('letterSpacing' in ctx) (ctx as CanvasRenderingContext2D & { letterSpacing: string }).letterSpacing = `${spacing}px`;
}

/** Matn niqob ostidan pastdan ko'tarilib chiqadi (p: 0 → 1). */
function reveal(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, p: number, align: CanvasTextAlign = 'left', spacing = -size * 0.035) {
  if (p <= 0 || !str) return;
  ctx.save();
  setFont(ctx, size, weight, spacing);
  const w = ctx.measureText(str).width;
  const left = align === 'center' ? x - w / 2 : align === 'right' ? x - w : x;
  ctx.beginPath();
  ctx.rect(left - 30, y - size * 1.05, w + 60, size * 1.4);
  ctx.clip();
  ctx.fillStyle = color;
  ctx.textAlign = align;
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(str, x, y + (1 - easeOut(p)) * size * 1.3);
  ctx.restore();
}

function fitSize(ctx: CanvasRenderingContext2D, str: string, size: number, weight: number, maxW: number) {
  ctx.save();
  setFont(ctx, size, weight, -size * 0.035);
  const w = ctx.measureText(str).width;
  ctx.restore();
  return w > maxW ? Math.floor(size * (maxW / w)) : size;
}

/** Sarlavhani 2 qatorga bo'ladi: [birinchi qator, urg'uli ikkinchi qator]. */
function splitTitle(title: string): [string, string] {
  const words = title.trim().split(/\s+/).filter(Boolean);
  if (words.length < 2) return ['', words[0] ?? ''];
  const cut = words.length > 2 ? Math.ceil(words.length / 2) : 1;
  return [words.slice(0, cut).join(' '), words.slice(cut).join(' ')];
}

function pill(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, a: number, align: 'left' | 'center' = 'left', bg = 'rgba(255,255,255,0.14)', fg = 'rgba(255,255,255,0.9)') {
  if (a <= 0 || !str) return;
  ctx.save();
  ctx.globalAlpha = a;
  setFont(ctx, 30, 700, 3);
  const tw = ctx.measureText(str).width + 52;
  const px = align === 'center' ? x - tw / 2 : x;
  roundRect(ctx, px, y + (1 - a) * 20, tw, 62, 31);
  ctx.fillStyle = bg;
  ctx.fill();
  ctx.fillStyle = fg;
  ctx.textAlign = 'left';
  ctx.textBaseline = 'middle';
  ctx.fillText(str, px + 26, y + 32 + (1 - a) * 20);
  ctx.restore();
}

function fadeText(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, a: number, align: CanvasTextAlign = 'center') {
  if (a <= 0 || !str) return;
  ctx.save();
  ctx.globalAlpha = clamp(a);
  setFont(ctx, size, weight, -size * 0.02);
  ctx.fillStyle = color;
  ctx.textAlign = align;
  ctx.fillText(str, x, y + (1 - easeOut(a)) * 30);
  ctx.restore();
}

function sparkles(ctx: CanvasRenderingContext2D, t: number, color: string) {
  for (let i = 0; i < 14; i++) {
    const x = 120 + ((i * 373) % 840);
    const y = 380 + ((i * 541) % 1100);
    const tw = Math.max(0, Math.sin(t * 3.2 + i * 1.7));
    const s = 10 + (i % 3) * 8;
    ctx.save();
    ctx.globalAlpha = tw * 0.9;
    ctx.translate(x, y);
    ctx.rotate(t * 0.6 + i);
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.moveTo(0, -s);
    ctx.quadraticCurveTo(0, 0, s, 0);
    ctx.quadraticCurveTo(0, 0, 0, s);
    ctx.quadraticCurveTo(0, 0, -s, 0);
    ctx.quadraticCurveTo(0, 0, 0, -s);
    ctx.fill();
    ctx.restore();
  }
}

// ── scenes ───────────────────────────────────────────────────────
const heroBook = (s: VideoScene) => s.books.find((b) => s.images.get(b.id)) ?? s.books[0];

/** Kirish: muqova foni, kitob "posteri" va sarlavha zarbga mos chiqadi. */
function intro(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const hero = heroBook(s);
  const img = hero ? s.images.get(hero.id) : undefined;
  coverBackdrop(ctx, img, 1.12 + 0.1 * (t / INTRO), s.template === 'countdown' ? 0.62 : 0.45);

  // poster: katta muqova, sekin yaqinlashadi (countdown'da #1 sir saqlanadi — faqat fon)
  if (img && s.template !== 'countdown') {
    const pw = 560, ph = 760;
    const z = 1 + 0.08 * easeInOut(t / INTRO);
    const a = easeOut(prog(t, 0, 0.5));
    ctx.save();
    ctx.globalAlpha = a;
    ctx.translate(W / 2, 250 + ph / 2 + (1 - a) * 60);
    ctx.scale(z, z);
    ctx.rotate(-0.025 + 0.02 * (t / INTRO));
    ctx.shadowColor = 'rgba(0,0,0,0.55)';
    ctx.shadowBlur = 90;
    ctx.shadowOffsetY = 40;
    roundRect(ctx, -pw / 2, -ph / 2, pw, ph, 26);
    ctx.fillStyle = '#111';
    ctx.fill();
    ctx.shadowColor = 'transparent';
    ctx.clip();
    drawCover(ctx, img, -pw / 2, -ph / 2, pw, ph);
    ctx.restore();
  }
  if (s.template === 'countdown') {
    const z = 1 + 0.06 * (t / INTRO);
    ctx.save();
    ctx.translate(W / 2, 700);
    ctx.scale(z, z);
    ctx.globalAlpha = 0.9 * easeOut(prog(t, 0.1, 0.5));
    setFont(ctx, 420, 800, -20);
    ctx.textAlign = 'center';
    ctx.strokeStyle = 'rgba(255,196,64,0.8)';
    ctx.lineWidth = 5;
    ctx.strokeText(`TOP ${s.books.length}`, 0, 150);
    ctx.restore();
  }

  const [l1, l2] = splitTitle(s.title);
  const x = 84;
  const size2 = fitSize(ctx, l2, 96, 800, W - 150);
  pill(ctx, s.periodLabel.toUpperCase(), x, 1180, easeOut(prog(t, 0.15, 0.4)));
  reveal(ctx, l1, x, 1345, 64, 700, 'rgba(255,255,255,0.88)', prog(t, BEAT, 0.5));
  reveal(ctx, l2, x, 1345 + size2 * 1.1, size2, 800, s.template === 'countdown' ? '#FFC440' : '#4EA3FF', prog(t, BEAT * 2, 0.5));
  fadeText(ctx, `${s.books.length} ta kitob · Kitobchi`, x, 1345 + size2 * 1.1 + 90, 34, 600, 'rgba(255,255,255,0.6)', prog(t, BEAT * 3, 0.4), 'left');
}

function storiesBars(ctx: CanvasRenderingContext2D, n: number, i: number, p: number) {
  const gap = 10, x0 = 70, w = (W - 140 - gap * (n - 1)) / n;
  for (let k = 0; k < n; k++) {
    roundRect(ctx, x0 + k * (w + gap), 210, w, 8, 4);
    ctx.fillStyle = 'rgba(255,255,255,0.25)';
    ctx.fill();
    const f = k < i ? 1 : k === i ? p : 0;
    if (f > 0) {
      roundRect(ctx, x0 + k * (w + gap), 210, w * f, 8, 4);
      ctx.fillStyle = '#FFFFFF';
      ctx.fill();
    }
  }
}

/** Karta: kirishda kattalikdan tushadi, so'ng sekin yaqinlashadi. */
function heroCard(ctx: CanvasRenderingContext2D, s: VideoScene, b: VideoBook, cx: number, top: number, cw: number, l: number, tilt: number) {
  const ch = cw * CARD_RATIO;
  const inP = backOut(prog(l, 0, 0.5));
  const z = (0.86 + 0.14 * inP) * (1 + 0.035 * (l / PER_BOOK));
  ctx.save();
  ctx.globalAlpha = clamp(prog(l, 0, 0.18));
  ctx.translate(cx, top + ch / 2 + (1 - clamp(inP)) * 90);
  ctx.rotate(tilt * (1 - clamp(inP)) * Math.PI / 180);
  ctx.scale(z, z);
  drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
  ctx.restore();
}

function carouselScene(ctx: CanvasRenderingContext2D, s: VideoScene, i: number, l: number) {
  const b = s.books[i];
  coverBackdrop(ctx, s.images.get(b.id), 1.15 + 0.08 * (l / PER_BOOK), 0.5);
  storiesBars(ctx, s.books.length, i, clamp(l / PER_BOOK));
  fadeText(ctx, `${String(i + 1).padStart(2, '0')} / ${String(s.books.length).padStart(2, '0')}`, W - 70, 290, 32, 700, 'rgba(255,255,255,0.75)', 1, 'right');
  heroCard(ctx, s, b, W / 2, 380, 560, l, i % 2 ? 4 : -4);
  if (b.author) fadeText(ctx, b.author, W / 2, 1400, 40, 600, 'rgba(255,255,255,0.72)', prog(l, 0.3, 0.4));
}

function countdownScene(ctx: CanvasRenderingContext2D, s: VideoScene, k: number, l: number) {
  const n = s.books.length;
  const idx = n - 1 - k;
  const b = s.books[idx];
  const first = idx === 0;
  if (first) radialBg(ctx, [[0, '#4A3208'], [0.45, '#1A1206'], [1, '#07090F']]);
  else radialBg(ctx, [[0, '#10284A'], [0.55, '#0A1426'], [1, '#060B16']]);
  rays(ctx, W / 2, 900, l * 0.12, first ? 'rgba(255,196,64,0.5)' : 'rgba(78,163,255,0.28)', first ? 0.55 : 0.35);
  if (first) sparkles(ctx, l, '#FFD980');

  // katta konturli raqam — zarb bilan "uriladi"
  const slam = prog(l, 0, 0.28);
  const sc = 1.6 - 0.6 * easeOut(slam);
  const shake = slam < 1 && slam > 0.6 ? Math.sin(l * 90) * 10 * (1 - slam) : 0;
  ctx.save();
  ctx.globalAlpha = clamp(slam * 2);
  ctx.translate(W / 2 + shake, 1010);
  ctx.scale(sc, sc);
  setFont(ctx, 640, 800, -30);
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.lineWidth = 6;
  ctx.strokeStyle = first ? 'rgba(255,196,64,0.75)' : 'rgba(78,163,255,0.5)';
  ctx.strokeText(`${idx + 1}`, 0, 0);
  ctx.restore();

  reveal(ctx, `#${idx + 1}`, W / 2, 330, 96, 800, first ? '#FFC440' : '#FFFFFF', prog(l, 0.05, 0.4), 'center');
  pill(ctx, first ? 'ENG KO\'P SOTILGAN' : s.title.toUpperCase(), W / 2, 370, easeOut(prog(l, 0.2, 0.4)), 'center',
    first ? 'rgba(255,196,64,0.18)' : 'rgba(255,255,255,0.1)', first ? '#FFD980' : 'rgba(255,255,255,0.75)');
  heroCard(ctx, s, b, W / 2, 500, 500, l, idx % 2 ? 5 : -5);
  if (b.author) fadeText(ctx, b.author, W / 2, 1400, 40, 600, 'rgba(255,255,255,0.72)', prog(l, 0.3, 0.4));
}

function gridScene(ctx: CanvasRenderingContext2D, s: VideoScene, l: number) {
  radialBg(ctx, [[0, '#3C95F2'], [0.5, '#2178D7'], [1, '#0F4A94']], 0.5);
  rays(ctx, W / 2, 1000, l * 0.08, 'rgba(255,255,255,0.35)', 0.35);
  const [l1, l2] = splitTitle(s.title);
  const size2 = fitSize(ctx, l2, 88, 800, W - 140);
  reveal(ctx, l1, W / 2, 300, 58, 700, 'rgba(255,255,255,0.85)', prog(l, 0, 0.45), 'center');
  reveal(ctx, l2, W / 2, 300 + size2 * 1.08, size2, 800, '#FFFFFF', prog(l, 0.12, 0.45), 'center');
  pill(ctx, s.periodLabel.toUpperCase(), W / 2, 300 + size2 * 1.08 + 44, easeOut(prog(l, 0.3, 0.4)), 'center');

  const books = s.books.slice(0, GRID_MAX);
  const cols = books.length > 4 ? 3 : 2;
  const rows = Math.ceil(books.length / cols);
  const gap = 30;
  const top = 560;
  const avail = 1650 - top;
  const cw = Math.min(360, (W - 140 - (cols - 1) * gap) / cols, (avail - (rows - 1) * gap) / rows / CARD_RATIO);
  const ch = cw * CARD_RATIO;
  const gridW = cols * cw + (cols - 1) * gap;
  const gridH = rows * ch + (rows - 1) * gap;
  const y0 = top + Math.max(0, (avail - gridH) / 2);
  const hold = GRID_STEP * books.length + GRID_HOLD;
  const cam = 1 + 0.04 * easeInOut(l / hold);
  ctx.save();
  ctx.translate(W / 2, y0 + gridH / 2);
  ctx.scale(cam, cam);
  ctx.translate(-W / 2, -(y0 + gridH / 2));
  books.forEach((b, i) => {
    const r = Math.floor(i / cols), c = i % cols;
    const inRow = r === rows - 1 ? books.length - r * cols : cols;
    const rowW = inRow * cw + (inRow - 1) * gap;
    const x = W / 2 - (inRow === cols ? gridW : rowW) / 2 + c * (cw + gap);
    const y = y0 + r * (ch + gap);
    const lp = l - BEAT - i * GRID_STEP;
    if (lp <= 0) return;
    const p = backOut(prog(lp, 0, 0.45));
    const sc = 1.3 - 0.3 * p;
    ctx.save();
    ctx.globalAlpha = clamp(prog(lp, 0, 0.15));
    ctx.translate(x + cw / 2, y + ch / 2 + Math.sin((l + i) * 1.6) * 5);
    ctx.rotate((1 - clamp(p)) * (i % 2 ? 6 : -6) * Math.PI / 180);
    ctx.scale(sc, sc);
    drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
    ctx.restore();
  });
  ctx.restore();
}

function outro(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  radialBg(ctx, [[0, '#2E88EA'], [0.45, '#1A62B8'], [1, '#0C3470']], 0.42);
  rays(ctx, W / 2, 700, t * 0.13, 'rgba(255,255,255,0.4)', 0.3);
  const icon = scene.assets.icon;
  const p = backOut(prog(t, 0.1, 0.8));
  if (icon && p > 0) {
    ctx.save();
    ctx.globalAlpha = clamp(p);
    ctx.translate(W / 2, 655);
    ctx.rotate((1 - clamp(p)) * -0.2);
    ctx.scale(0.4 + 0.6 * p, 0.4 + 0.6 * p);
    ctx.shadowColor = 'rgba(4,20,50,0.5)';
    ctx.shadowBlur = 70;
    ctx.shadowOffsetY = 30;
    roundRect(ctx, -101, -101, 202, 202, 52);
    ctx.fillStyle = 'rgba(255,255,255,0.18)';
    ctx.fill();
    ctx.shadowColor = 'transparent';
    ctx.drawImage(icon, -95, -95, 190, 190);
    ctx.restore();
  }
  reveal(ctx, 'Kitobchi', W / 2, 900, 118, 700, '#FFFFFF', prog(t, 0.45, 0.5), 'center', -4);
  fadeText(ctx, 'Ilovani yuklab oling', W / 2, 1010, 50, 600, '#CFE3FA', prog(t, 0.8, 0.5));
  const badges = [scene.assets.appStore, scene.assets.googlePlay];
  badges.forEach((b, i) => {
    if (!b) return;
    const a = easeOut(prog(t, 1.05 + i * 0.15, 0.5));
    if (a <= 0) return;
    const bw = 370, bh = 111;
    const bx = W / 2 - bw - 12 + i * (bw + 24);
    const by = 1085 + (1 - a) * 40;
    ctx.save();
    ctx.globalAlpha = a;
    ctx.shadowColor = 'rgba(0,0,0,0.3)';
    ctx.shadowBlur = 40;
    ctx.shadowOffsetY = 18;
    roundRect(ctx, bx, by, bw, bh, 20);
    ctx.fillStyle = '#000';
    ctx.fill();
    ctx.shadowColor = 'transparent';
    ctx.clip();
    ctx.drawImage(b, bx, by, bw, bh);
    // yaltirash
    const sh = prog(t, 1.9, 1.1);
    if (sh > 0 && sh < 1) {
      const sx = bx - bw + sh * bw * 3;
      const g = ctx.createLinearGradient(sx - 120, 0, sx + 120, 0);
      g.addColorStop(0, 'rgba(255,255,255,0)');
      g.addColorStop(0.5, 'rgba(255,255,255,0.4)');
      g.addColorStop(1, 'rgba(255,255,255,0)');
      ctx.fillStyle = g;
      ctx.fillRect(bx, by, bw, bh);
    }
    ctx.restore();
  });
}

/** Sahna kesimlari (soniya) — flash va "punch-in" shu nuqtalarda. */
function cutsOf(scene: VideoScene): number[] {
  const total = durationOf(scene.template, scene.books.length);
  const outroAt = total - OUTRO;
  if (scene.template === 'grid') return [INTRO, outroAt];
  return [...scene.books.map((_, i) => INTRO + i * PER_BOOK), outroAt];
}

/** Admin panelda to'xtatilgan ko'rinish uchun chiroyli kadr vaqti. */
export function previewTime(template: VideoTemplate, count: number): number {
  if (template === 'grid') return INTRO + BEAT + GRID_STEP * Math.min(count, GRID_MAX) + 0.6;
  return INTRO + PER_BOOK * 0.6;
}

/** Bitta kadrni chizadi: t — soniya. */
export function drawFrame(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const total = durationOf(scene.template, scene.books.length);
  const outroAt = total - OUTRO;
  const cuts = cutsOf(scene);
  const last = [...cuts].reverse().find((c) => t >= c);
  const since = last === undefined ? Infinity : t - last;

  ctx.save();
  if (since < 0.45) {
    const z = 1 + 0.07 * (1 - easeOut(since / 0.45));
    ctx.translate(W / 2, H / 2);
    ctx.scale(z, z);
    ctx.translate(-W / 2, -H / 2);
  }
  if (t < INTRO) intro(ctx, scene, t);
  else if (t >= outroAt) outro(ctx, scene, t - outroAt);
  else if (scene.template === 'grid') gridScene(ctx, scene, t - INTRO);
  else {
    const k = Math.min(scene.books.length - 1, Math.floor((t - INTRO) / PER_BOOK));
    const l = t - INTRO - k * PER_BOOK;
    if (scene.template === 'countdown') countdownScene(ctx, scene, k, l);
    else carouselScene(ctx, scene, k, l);
  }
  ctx.restore();

  vignette(ctx);
  grain(ctx, t);
  if (since < 0.3) {
    ctx.fillStyle = `rgba(255,255,255,${0.7 * Math.pow(1 - since / 0.3, 2)})`;
    ctx.fillRect(0, 0, W, H);
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
