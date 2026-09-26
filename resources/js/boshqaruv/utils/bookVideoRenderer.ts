/**
 * KITOB VIDEOLARI — 1080×1920 Reels videosini canvas'da chizish va yozib olish.
 *
 * Kitoblar ilovadagi kitob kartasi ko'rinishida chiziladi (oq karta, rasm,
 * nom, kulrang narx plashkasi + yurakcha). Motion: coverflow (3D perspektiva),
 * deck, assemble; expo easing, harfma-harf matn, shared-element o'tishlar,
 * muqova rangidan fon. Kesimlar 110 BPM zarbiga mos.
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

// ── motion toolkit ───────────────────────────────────────────────
// Professional product-promo tamoyillari: expo easing, 50–80 ms stagger,
// shared-element o'tishlar (kesim yo'q), 3D chuqurlik, harakat ierarxiyasi.
const PAPER = '#F4F6FA';
const NIGHT = '#0A0F1C';
const SKY = '#6FB6FF';

const expoOut = (x: number) => { const t = clamp(x); return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); };
const quartInOut = (x: number) => { const t = clamp(x); return t < 0.5 ? 8 * t ** 4 : 1 - Math.pow(-2 * t + 2, 4) / 2; };
const lerp = (a: number, b: number, p: number) => a + (b - a) * p;

function fill(ctx: CanvasRenderingContext2D, color: string) {
  ctx.fillStyle = color;
  ctx.fillRect(0, 0, W, H);
}

function glow(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, color: string) {
  const g = ctx.createRadialGradient(x, y, 0, x, y, r);
  g.addColorStop(0, color);
  g.addColorStop(1, 'rgba(0,0,0,0)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
}

function setFont(ctx: CanvasRenderingContext2D, size: number, weight: number, spacing = 0) {
  ctx.font = `${weight} ${size}px ${FONT}`;
  if ('letterSpacing' in ctx) (ctx as CanvasRenderingContext2D & { letterSpacing: string }).letterSpacing = `${spacing}px`;
}

/** Matn niqob ostidan ko'tarilib chiqadi (p: 0 → 1). */
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
  ctx.fillText(str, x, y + (1 - expoOut(p)) * size * 1.3);
  ctx.restore();
}

/** Harfma-harf chiqish: har harf 28 ms kechikib, pastdan ko'tariladi. */
function charReveal(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, t: number, start: number, align: 'left' | 'center' = 'left') {
  if (!str || t < start) return;
  ctx.save();
  setFont(ctx, size, weight, -size * 0.035);
  ctx.fillStyle = color;
  ctx.textAlign = 'left';
  const total = ctx.measureText(str).width;
  const x0 = align === 'center' ? x - total / 2 : x;
  const done = t - start > 0.7 + str.length * 0.028;
  if (done) {
    ctx.fillText(str, x0, y);
  } else {
    for (let i = 0; i < str.length; i++) {
      const p = expoOut((t - start - i * 0.028) / 0.7);
      if (p <= 0) break;
      ctx.globalAlpha = p;
      ctx.fillText(str[i], x0 + ctx.measureText(str.slice(0, i)).width, y + (1 - p) * size * 0.55);
    }
  }
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

function fadeText(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, a: number, align: CanvasTextAlign = 'left', spacing = -size * 0.02) {
  if (a <= 0 || !str) return;
  ctx.save();
  ctx.globalAlpha *= clamp(a);
  setFont(ctx, size, weight, spacing);
  ctx.fillStyle = color;
  ctx.textAlign = align;
  ctx.fillText(str, x, y + (1 - expoOut(a)) * 24);
  ctx.restore();
}

const label = (ctx: CanvasRenderingContext2D, str: string, x: number, y: number, color: string, a: number, align: CanvasTextAlign = 'left') =>
  fadeText(ctx, str.toUpperCase(), x, y, 28, 700, color, a, align, 5);

/** Hisoblagich: raqamlar vertikal aylanib almashadi (value — kasr bo'lishi mumkin). */
function rollNumber(ctx: CanvasRenderingContext2D, value: number, fmt: (v: number) => string, x: number, y: number, size: number, weight: number, color: string, align: CanvasTextAlign = 'left') {
  const lo = Math.floor(value + 1e-6), f = value - lo;
  ctx.save();
  setFont(ctx, size, weight, -size * 0.04);
  ctx.fillStyle = color;
  ctx.textAlign = align;
  const w = Math.max(ctx.measureText(fmt(lo)).width, ctx.measureText(fmt(lo + 1)).width);
  const left = align === 'right' ? x - w : x;
  ctx.beginPath();
  ctx.rect(left - 20, y - size * 0.95, w + 40, size * 1.15);
  ctx.clip();
  const e = quartInOut(f);
  ctx.fillText(fmt(lo), x, y - e * size * 1.1);
  if (f > 0.001) ctx.fillText(fmt(lo + 1), x, y + (1 - e) * size * 1.1);
  ctx.restore();
}

// ── cards: cached bitmaps, soft shadow, real perspective ─────────
const CARD_W = 600;
const cardCache = new Map<string, HTMLCanvasElement>();
function cardBitmap(s: VideoScene, b: VideoBook): HTMLCanvasElement {
  const img = s.images.get(b.id);
  const key = `${b.id}|${img?.src ?? ''}|${b.name}|${b.price}|${b.oldPrice ?? ''}`;
  let c = cardCache.get(key);
  if (c) return c;
  c = document.createElement('canvas');
  c.width = CARD_W;
  c.height = Math.ceil(CARD_W * CARD_RATIO);
  drawBookCard(c.getContext('2d')!, b, img, 0, 0, CARD_W);
  if (cardCache.size > 80) cardCache.clear();
  cardCache.set(key, c);
  return c;
}

let shadowBmp: HTMLCanvasElement | null = null;
const SHADOW_M = 140;
function cardShadow(ctx: CanvasRenderingContext2D, cx: number, cy: number, w: number, a: number) {
  if (!shadowBmp) {
    shadowBmp = document.createElement('canvas');
    const ch = CARD_W * CARD_RATIO;
    shadowBmp.width = CARD_W + SHADOW_M * 2;
    shadowBmp.height = Math.ceil(ch) + SHADOW_M * 2;
    const x = shadowBmp.getContext('2d')!;
    x.shadowColor = 'rgba(0,0,0,0.6)';
    x.shadowBlur = 80;
    x.fillStyle = '#000';
    roundRect(x, SHADOW_M, SHADOW_M, CARD_W, ch, 52);
    x.fill();
    // faqat tashqi soya qoladi — karta aylanganda ostidan qora chiqmasin
    x.shadowColor = 'transparent';
    x.globalCompositeOperation = 'destination-out';
    roundRect(x, SHADOW_M, SHADOW_M, CARD_W, ch, 52);
    x.fill();
  }
  const k = w / CARD_W, h = w * CARD_RATIO;
  ctx.save();
  ctx.globalAlpha *= a;
  ctx.drawImage(shadowBmp, cx - w / 2 - SHADOW_M * k, cy - h / 2 - SHADOW_M * k + 26 * k, w + 2 * SHADOW_M * k, h + 2 * SHADOW_M * k);
  ctx.restore();
}

let scratch: HTMLCanvasElement | null = null;
/** Kartani Y o'qi atrofida perspektivada chizadi (rotY — radian). */
function card3D(ctx: CanvasRenderingContext2D, bmp: HTMLCanvasElement, cx: number, cy: number, w: number, rotY = 0, alpha = 1, shadow = 1, dim = 0) {
  if (alpha <= 0) return;
  const h = w * CARD_RATIO;
  ctx.save();
  ctx.globalAlpha *= alpha;
  if (shadow > 0) cardShadow(ctx, cx, cy, w * Math.max(0.5, Math.cos(rotY)), shadow * 0.85);
  if (Math.abs(rotY) < 0.003 && dim <= 0) {
    ctx.drawImage(bmp, cx - w / 2, cy - h / 2, w, h);
  } else {
    // tasmalar avval yordamchi canvas'ga to'liq shaffofsizlikda chiziladi —
    // aks holda ustma-ust tushgan joylari shaffoflikda chiziq bo'lib ko'rinadi
    const N = Math.abs(rotY) < 0.003 ? 1 : 30, D = 2000, sw = bmp.width / N;
    const cos = Math.cos(rotY), sin = Math.sin(rotY);
    const xs: number[] = [], fs: number[] = [];
    for (let i = 0; i <= N; i++) {
      const u = (i / N - 0.5) * w, f = D / (D + u * sin);
      xs.push(u * cos * f);
      fs.push(f);
    }
    const minX = Math.min(xs[0], xs[N]), maxX = Math.max(xs[0], xs[N]);
    const bw = Math.ceil(maxX - minX) + 6, bh = Math.ceil(h * Math.max(fs[0], fs[N])) + 6;
    if (!scratch) scratch = document.createElement('canvas');
    if (scratch.width < bw || scratch.height < bh) {
      scratch.width = Math.max(scratch.width, bw);
      scratch.height = Math.max(scratch.height, bh);
    }
    const sc = scratch.getContext('2d')!;
    sc.clearRect(0, 0, bw, bh);
    for (let i = 0; i < N; i++) {
      const hh = h * (fs[i] + fs[i + 1]) / 2;
      const x0 = Math.min(xs[i], xs[i + 1]) - minX + 3;
      sc.drawImage(bmp, i * sw, 0, sw, bmp.height, x0, (bh - hh) / 2, Math.abs(xs[i + 1] - xs[i]) + 0.8, hh);
    }
    if (dim > 0) {
      // orqadagi kartalar shaffof emas, qoraytiriladi — bir-birining ustidan ko'rinmasin
      sc.globalCompositeOperation = 'source-atop';
      sc.fillStyle = `rgba(10,15,28,${dim})`;
      sc.fillRect(0, 0, bw, bh);
      sc.globalCompositeOperation = 'source-over';
    }
    ctx.drawImage(scratch, 0, 0, bw, bh, cx + minX - 3, cy - bh / 2, bw, bh);
  }
  ctx.restore();
}

// ── mood colour: muqovaning asosiy rangidan chuqur fon ────────────
type RGB = [number, number, number];
const BRAND_MOOD: RGB = [14, 46, 94];
const moodCache = new WeakMap<HTMLImageElement, RGB>();
function hslToRgb(h: number, s: number, l: number): RGB {
  const k = (n: number) => (n + h / 30) % 12;
  const a = s * Math.min(l, 1 - l);
  const f = (n: number) => l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
  return [f(0) * 255, f(8) * 255, f(4) * 255];
}
function moodOf(img: HTMLImageElement | undefined): RGB {
  if (!img) return BRAND_MOOD;
  const hit = moodCache.get(img);
  if (hit) return hit;
  let out = BRAND_MOOD;
  try {
    const c = document.createElement('canvas');
    c.width = c.height = 12;
    const x = c.getContext('2d', { willReadFrequently: true })!;
    x.drawImage(img, 0, 0, 12, 12);
    const d = x.getImageData(0, 0, 12, 12).data;
    // eng to'yingan piksellar og'irroq hisoblanadi — kulrang fon rangni "bo'yamasin"
    let r = 0, g = 0, b = 0, wsum = 0;
    for (let i = 0; i < d.length; i += 4) {
      const mx = Math.max(d[i], d[i + 1], d[i + 2]), mn = Math.min(d[i], d[i + 1], d[i + 2]);
      const wgt = 0.05 + (mx - mn) / 255;
      r += d[i] * wgt; g += d[i + 1] * wgt; b += d[i + 2] * wgt; wsum += wgt;
    }
    r /= wsum; g /= wsum; b /= wsum;
    const mx = Math.max(r, g, b) / 255, mn = Math.min(r, g, b) / 255, l = (mx + mn) / 2;
    const s = mx === mn ? 0 : (mx - mn) / (1 - Math.abs(2 * l - 1));
    if (s < 0.14) out = BRAND_MOOD;
    else {
      let hue = 0;
      const R = r / 255, G = g / 255, B = b / 255, dlt = mx - mn;
      if (mx === R) hue = 60 * (((G - B) / dlt) % 6);
      else if (mx === G) hue = 60 * ((B - R) / dlt + 2);
      else hue = 60 * ((R - G) / dlt + 4);
      out = hslToRgb((hue + 360) % 360, Math.min(0.62, Math.max(0.4, s)), 0.2);
    }
  } catch {
    /* rasm boshqa domendan bo'lsa (tainted) — brend rangi */
  }
  moodCache.set(img, out);
  return out;
}
const mixRGB = (a: RGB, b: RGB, p: number): RGB => [lerp(a[0], b[0], p), lerp(a[1], b[1], p), lerp(a[2], b[2], p)];
const css = (c: RGB, a = 1) => `rgba(${c[0] | 0},${c[1] | 0},${c[2] | 0},${a})`;

function moodBg(ctx: CanvasRenderingContext2D, c: RGB) {
  const g = ctx.createLinearGradient(0, 0, 0, H);
  g.addColorStop(0, css(c));
  g.addColorStop(0.62, css(mixRGB(c, [10, 15, 28], 0.7)));
  g.addColorStop(1, NIGHT);
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
  glow(ctx, W / 2, 1000, 760, css(mixRGB(c, [255, 255, 255], 0.25), 0.35));
}

/** Kitoblar ro'yxatidagi kasr pozitsiya bo'yicha fon rangi (silliq o'tadi). */
function moodAt(s: VideoScene, order: VideoBook[], pos: number): RGB {
  const i = Math.max(0, Math.min(order.length - 1, Math.floor(pos)));
  const j = Math.min(order.length - 1, i + 1);
  const m = (b: VideoBook | undefined) => moodOf(b ? s.images.get(b.id) : undefined);
  return mixRGB(m(order[i]), m(order[j]), quartInOut(clamp(pos - i)));
}

// ── shared: sarlavha katta holatdan tepaga "joylashadi" ────────────
function titleBlock(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const [l1, l2] = splitTitle(s.title);
  const e = quartInOut(prog(t, INTRO - 1.0, 0.9));
  const x = 84;
  const s1 = lerp(64, 40, e);
  const s2 = fitSize(ctx, l2, lerp(112, 70, e), 800, W - 170);
  const yO = lerp(700, 236, e), y1 = lerp(810, 306, e), y2 = y1 + s2 * 1.06;
  label(ctx, s.periodLabel, x, yO, SKY, prog(t, 0.05, 0.5));
  charReveal(ctx, l1, x, y1, s1, 700, 'rgba(255,255,255,0.86)', t, 0.15);
  charReveal(ctx, l2, x, y2, s2, 800, '#FFFFFF', t, 0.15 + BEAT);
}

/** Kitoblar orasidagi "kamera" pozitsiyasi: har qadamda silliq siljiydi. */
function stepPos(t: number, n: number): number {
  if (t < INTRO) return 0;
  const k = Math.min(n - 1, Math.floor((t - INTRO) / PER_BOOK));
  const l = t - INTRO - k * PER_BOOK;
  return k > 0 && l < 0.8 ? k - 1 + quartInOut(l / 0.8) : k;
}

const CARD_CY = 1040;

// ── carousel → coverflow ─────────────────────────────────────────
function carouselScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const n = s.books.length;
  const enter = expoOut(prog(t, INTRO - 1.15, 1.5));
  const P = stepPos(t, n) - (1 - enter) * 2.2;
  moodBg(ctx, moodAt(s, s.books, Math.max(0, P)));
  titleBlock(ctx, s, t);

  const a = prog(t, INTRO - 0.4, 0.5);
  ctx.save();
  ctx.globalAlpha = a;
  rollNumber(ctx, Math.max(0, P) + 1, (v) => String(v).padStart(2, '0'), W - 84 - 90, 306, 40, 700, '#FFFFFF', 'right');
  ctx.restore();
  fadeText(ctx, `/ ${String(n).padStart(2, '0')}`, W - 84, 306, 40, 500, 'rgba(255,255,255,0.45)', a, 'right');
  // progress
  ctx.fillStyle = 'rgba(255,255,255,0.14)';
  ctx.fillRect(84, 440, (W - 168) * clamp(enter), 4);
  ctx.fillStyle = '#FFFFFF';
  ctx.fillRect(84, 440, (W - 168) * clamp((Math.max(0, P) + 1) / n) * clamp(enter), 4);

  const order = s.books.map((b, i) => ({ b, i, o: i - P })).filter((x) => Math.abs(x.o) < 2.4).sort((x, y) => Math.abs(y.o) - Math.abs(x.o));
  for (const { b, o } of order) {
    const ao = Math.min(Math.abs(o), 1.6);
    const w = 560 * (1 - 0.15 * ao);
    const cx = W / 2 + Math.sign(o) * (ao * 560 + Math.max(0, Math.abs(o) - 1.6) * 300);
    const rot = Math.max(-1.6, Math.min(1.6, o)) * 0.5;
    const bob = Math.sin(t * 1.5) * 6 * (1 - ao);
    card3D(ctx, cardBitmap(s, b), cx, CARD_CY + bob, w, rot, 1 - clamp(ao - 1.4) * 2, 1 - 0.6 * Math.min(ao, 1), 0.5 * Math.min(ao, 1) + 0.2 * clamp(ao - 1));
  }
  const cur = Math.round(Math.max(0, P));
  const near = 1 - Math.min(1, Math.abs(Math.max(0, P) - cur) * 3);
  const author = s.books[cur]?.author;
  if (author) fadeText(ctx, author, W / 2, 1580, 38, 500, 'rgba(255,255,255,0.7)', near * enter, 'center');
}

// ── countdown → deck ─────────────────────────────────────────────
function countdownScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const n = s.books.length;
  const seq = [...s.books].reverse(); // #n … #1
  const P = stepPos(t, n);
  const enter = expoOut(prog(t, INTRO - 1.15, 1.4));
  moodBg(ctx, mixRGB(moodAt(s, seq, P), [10, 15, 28], 0.35));
  const final = P > n - 1.001;
  const finalL = final ? t - INTRO - (n - 1) * PER_BOOK - 0.8 : -1;
  if (final) glow(ctx, W / 2, CARD_CY, 700, `rgba(46,139,239,${0.45 * clamp(finalL / 0.5)})`);
  titleBlock(ctx, s, t);

  // #N hisoblagich
  ctx.save();
  ctx.globalAlpha = prog(t, INTRO - 0.5, 0.5);
  const rank = n - P;
  const col = rank < 1.5 ? '#4EA3FF' : '#FFFFFF';
  setFont(ctx, 130, 800, -5);
  ctx.fillStyle = col;
  ctx.fillText('#', 80, 600);
  const hw = ctx.measureText('#').width;
  ctx.restore();
  if (t > INTRO - 0.5) {
    ctx.save();
    ctx.globalAlpha = prog(t, INTRO - 0.5, 0.5);
    rollNumber(ctx, n - P - 1, (v) => String(v + 1), 80 + hw + 4, 600, 130, 800, col, 'left');
    ctx.restore();
  }

  const dy = (1 - enter) * 1300;
  for (let j = Math.min(n - 1, Math.ceil(P) + 3); j >= 0; j--) {
    const d = j - P;
    const bmp = cardBitmap(s, seq[j]);
    if (d < 0) {
      const q = -d;
      if (q >= 1) continue;
      ctx.save();
      ctx.translate(W / 2 - q * 900, CARD_CY + q * 120);
      ctx.rotate(-q * 0.35);
      card3D(ctx, bmp, 0, 0, 520, q * 0.7, 1 - clamp((q - 0.35) / 0.65), 1 - q);
      ctx.restore();
      continue;
    }
    if (d > 3) continue;
    const w = 520 * (1 - 0.08 * d);
    const alpha = d <= 2 ? 1 : 3 - d;
    card3D(ctx, bmp, W / 2, CARD_CY + d * 64 + dy + Math.sin(t * 1.5) * 5 * (d < 0.5 ? 1 : 0), w, 0, alpha, d < 1 ? 1 : 0.4, Math.min(0.75, 0.32 * d));
  }

  // #1 — yaltirash
  if (final && finalL > 0.2 && finalL < 1.4) {
    const p = (finalL - 0.2) / 1.2;
    const w = 520, h = w * CARD_RATIO, x = W / 2 - w / 2, y = CARD_CY - h / 2;
    ctx.save();
    roundRect(ctx, x, y, w, h, 45);
    ctx.clip();
    const sx = x - 300 + p * (w + 600);
    const g = ctx.createLinearGradient(sx - 160, y, sx + 160, y + 200);
    g.addColorStop(0, 'rgba(255,255,255,0)');
    g.addColorStop(0.5, 'rgba(255,255,255,0.45)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = g;
    ctx.fillRect(x, y, w, h);
    ctx.restore();
  }
  const front = seq[Math.min(n - 1, Math.round(P))];
  const near = 1 - Math.min(1, Math.abs(P - Math.round(P)) * 3);
  if (final) label(ctx, "Eng ko'p sotilgan", W / 2, 1640, SKY, clamp(finalL / 0.4), 'center');
  else if (front?.author) fadeText(ctx, front.author, W / 2, 1640, 38, 500, 'rgba(255,255,255,0.7)', near * enter, 'center');
}

// ── grid → assemble ──────────────────────────────────────────────
function gridLayout(count: number) {
  const cols = count > 4 ? 3 : 2;
  const rows = Math.ceil(count / cols);
  const gap = 28, top = 500, avail = 1650 - top;
  const cw = Math.min(390, (W - 168 - (cols - 1) * gap) / cols, (avail - (rows - 1) * gap) / rows / CARD_RATIO);
  const ch = cw * CARD_RATIO;
  const gridW = cols * cw + (cols - 1) * gap;
  const gridH = rows * ch + (rows - 1) * gap;
  const y0 = top + Math.max(0, (avail - gridH) / 2);
  const pos = Array.from({ length: count }, (_, i) => {
    const r = Math.floor(i / cols), c = i % cols;
    const inRow = r === rows - 1 ? count - r * cols : cols;
    const rowW = inRow * cw + (inRow - 1) * gap;
    return { x: W / 2 - (inRow === cols ? gridW : rowW) / 2 + c * (cw + gap) + cw / 2, y: y0 + r * (ch + gap) + ch / 2 };
  });
  return { cw, pos, cy: y0 + gridH / 2 };
}

function gridScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  fill(ctx, NIGHT);
  glow(ctx, W * 0.2, 300, 900, 'rgba(33,120,215,0.30)');
  glow(ctx, W * 0.85, 1500, 800, 'rgba(33,120,215,0.18)');
  titleBlock(ctx, s, t);
  const books = s.books.slice(0, GRID_MAX);
  const { cw, pos, cy } = gridLayout(books.length);
  const enter = expoOut(prog(t, INTRO - 1.15, 1.3));
  const outroAt = durationOf('grid', s.books.length) - OUTRO;
  const push = 1 + 0.04 * quartInOut(prog(t, INTRO + 1.2, outroAt - INTRO - 1.2));
  ctx.save();
  ctx.translate(W / 2, cy);
  ctx.scale(push, push);
  ctx.translate(-W / 2, -cy);
  for (let i = books.length - 1; i >= 0; i--) {
    const p = expoOut(prog(t, INTRO + 0.05 + i * 0.08, 0.95));
    const sx = W / 2, sy = 1080 - i * 8 + (1 - enter) * 1300;
    const r0 = (i % 2 ? 1 : -1) * (2 + i * 1.6) * Math.PI / 180;
    const x = lerp(sx, pos[i].x, p), y = lerp(sy, pos[i].y, p);
    const w = lerp(440, cw, p);
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(lerp(r0, 0, p));
    card3D(ctx, cardBitmap(s, books[i]), 0, 0, w, 0, 1, 1);
    ctx.restore();
  }
  ctx.restore();
}

// ── outro ────────────────────────────────────────────────────────
function outroBg(ctx: CanvasRenderingContext2D) {
  const g = ctx.createLinearGradient(0, 0, 0, H);
  g.addColorStop(0, '#2B86E8');
  g.addColorStop(1, '#134F9E');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
  glow(ctx, W / 2, 660, 720, 'rgba(255,255,255,0.16)');
}

/** favicon.svg — burchaklari shaffof (rx 115/512); aniq shu shaklda qirqib chiziladi. */
function drawIcon(ctx: CanvasRenderingContext2D, icon: HTMLImageElement, cx: number, cy: number, size: number, shadow = true) {
  const r = size * (115 / 512);
  ctx.save();
  if (shadow) {
    ctx.save();
    ctx.shadowColor = 'rgba(4,20,50,0.35)';
    ctx.shadowBlur = size * 0.3;
    ctx.shadowOffsetY = size * 0.12;
    roundRect(ctx, cx - size / 2 + 2, cy - size / 2 + 2, size - 4, size - 4, r);
    ctx.fillStyle = '#2178D7';
    ctx.fill();
    ctx.restore();
  }
  roundRect(ctx, cx - size / 2, cy - size / 2, size, size, r);
  ctx.clip();
  ctx.drawImage(icon, cx - size / 2, cy - size / 2, size, size);
  ctx.restore();
}

function outro(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  outroBg(ctx);
  const icon = scene.assets.icon;
  const p = prog(t, 0, 0.9);
  if (icon && p > 0) {
    const sz = 190 * lerp(0.2, 1, backOut(p) * 0.35 + expoOut(p) * 0.65);
    ctx.save();
    ctx.globalAlpha = clamp(p * 4);
    drawIcon(ctx, icon, W / 2, 655, sz);
    ctx.restore();
  }
  charReveal(ctx, 'Kitobchi', W / 2, 900, 118, 700, '#FFFFFF', t, 0.3, 'center');
  fadeText(ctx, 'Ilovani yuklab oling', W / 2, 1010, 50, 600, 'rgba(255,255,255,0.8)', prog(t, 0.75, 0.6), 'center');
  [scene.assets.appStore, scene.assets.googlePlay].forEach((b, i) => {
    if (!b) return;
    const a = expoOut(prog(t, 0.95 + i * 0.1, 0.8));
    if (a <= 0) return;
    const bw = 370, bh = 111;
    const bx = W / 2 - bw - 12 + i * (bw + 24);
    const by = 1085 + (1 - a) * 60;
    ctx.save();
    ctx.globalAlpha = a;
    roundRect(ctx, bx, by, bw, bh, 20);
    ctx.fillStyle = '#000';
    ctx.fill();
    ctx.clip();
    ctx.drawImage(b, bx, by, bw, bh);
    ctx.restore();
  });
}

/** Admin panelda to'xtatilgan ko'rinish uchun chiroyli kadr vaqti. */
export function previewTime(template: VideoTemplate, count: number): number {
  if (template === 'grid') return INTRO + 0.05 + 0.08 * Math.min(count, GRID_MAX) + 1.4;
  return INTRO + PER_BOOK * 0.6;
}

function drawScene(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  if (scene.template === 'grid') gridScene(ctx, scene, t);
  else if (scene.template === 'countdown') countdownScene(ctx, scene, t);
  else carouselScene(ctx, scene, t);
}

/** Bitta kadrni chizadi: t — soniya. Oxirida sahna markazga yig'ilib, ikonkaga aylanadi. */
export function drawFrame(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const outroAt = durationOf(scene.template, scene.books.length) - OUTRO;
  if (t >= outroAt) {
    outro(ctx, scene, t - outroAt);
    return;
  }
  const endP = prog(t, outroAt - 0.55, 0.55);
  if (endP <= 0) {
    drawScene(ctx, scene, t);
    return;
  }
  outroBg(ctx);
  const z = 1 - 0.8 * Math.pow(endP, 2.2);
  ctx.save();
  ctx.globalAlpha = 1 - Math.pow(endP, 1.6);
  ctx.translate(W / 2, 655 + (H / 2 - 655) * (1 - endP));
  ctx.scale(z, z);
  ctx.translate(-W / 2, -H / 2);
  roundRect(ctx, 0, 0, W, H, 80 * endP / z);
  ctx.clip();
  drawScene(ctx, scene, t);
  ctx.restore();
}

// ── cover (Instagram muqova rasmi) ───────────────────────────────
// Asosiy mazmun profil to'ridagi 3:4 kesimga (y ≈ 240…1680) sig'adigan qilib joylanadi.
function posterBrand(ctx: CanvasRenderingContext2D, scene: VideoScene, y: number, color: string) {
  ctx.save();
  setFont(ctx, 40, 700, -1);
  const tw = ctx.measureText('Kitobchi').width;
  const iw = 64, gap = 18;
  const x0 = W / 2 - (iw + gap + tw) / 2;
  if (scene.assets.icon) drawIcon(ctx, scene.assets.icon, x0 + iw / 2, y - 16, iw, false);
  ctx.fillStyle = color;
  ctx.textAlign = 'left';
  ctx.fillText('Kitobchi', x0 + iw + gap, y);
  ctx.restore();
}

function posterTitle(ctx: CanvasRenderingContext2D, s: VideoScene, y: number, light: boolean, over: string) {
  const [l1, l2] = splitTitle(s.title);
  const size2 = fitSize(ctx, l2, 116, 800, W - 170);
  label(ctx, over, 84, y, light ? SKY : BLUE, 1);
  reveal(ctx, l1, 84, y + 100, 66, 700, light ? 'rgba(255,255,255,0.9)' : INK, 1);
  reveal(ctx, l2, 80, y + 100 + size2 * 1.05, size2, 800, light ? '#FFFFFF' : BLUE, 1);
  return y + 100 + size2 * 1.05;
}

function tiltedCard(ctx: CanvasRenderingContext2D, s: VideoScene, b: VideoBook, cx: number, top: number, cw: number, deg: number) {
  const ch = cw * CARD_RATIO;
  ctx.save();
  ctx.translate(cx, top + ch / 2);
  ctx.rotate(deg * Math.PI / 180);
  drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
  ctx.restore();
}

/** Video uchun cover: shablonga mos, 1080×1920. */
export function drawPoster(ctx: CanvasRenderingContext2D, s: VideoScene) {
  const books = s.books;
  if (s.template === 'grid') {
    fill(ctx, PAPER);
    glow(ctx, W / 2, 1150, 900, 'rgba(33,120,215,0.10)');
    const bottom = posterTitle(ctx, s, 330, false, s.periodLabel);
    const covers = books.filter((b) => s.images.get(b.id)).slice(0, 6);
    const cols = covers.length > 4 ? 3 : 2;
    const gap = 26;
    const cw = cols === 3 ? 285 : 360;
    const ch = cw * 1.42;
    const rows = Math.ceil(covers.length / cols);
    const gridH = rows * ch + (rows - 1) * gap;
    const y0 = bottom + 70 + Math.max(0, (1560 - bottom - 70 - gridH) / 2);
    covers.forEach((b, i) => {
      const r = Math.floor(i / cols), c = i % cols;
      const inRow = r === rows - 1 ? covers.length - r * cols : cols;
      const x = W / 2 - (inRow * cw + (inRow - 1) * gap) / 2 + c * (cw + gap);
      const y = y0 + r * (ch + gap);
      ctx.save();
      ctx.shadowColor = 'rgba(15,42,79,0.22)';
      ctx.shadowBlur = 40;
      ctx.shadowOffsetY = 18;
      roundRect(ctx, x, y, cw, ch, 22);
      ctx.fillStyle = '#fff';
      ctx.fill();
      ctx.shadowColor = 'transparent';
      ctx.clip();
      drawCover(ctx, s.images.get(b.id)!, x, y, cw, ch);
      ctx.restore();
    });
    posterBrand(ctx, s, 1640, INK);
    return;
  }

  fill(ctx, NIGHT);
  glow(ctx, W / 2, 1150, 900, 'rgba(33,120,215,0.32)');
  if (s.template === 'countdown') {
    posterTitle(ctx, s, 330, true, `Top ${books.length} · ${s.periodLabel}`);
    const b = books[0];
    if (b) {
      if (books[1]) { ctx.globalAlpha = 0.55; tiltedCard(ctx, s, books[1], W / 2 - 250, 820, 380, -9); ctx.globalAlpha = 1; }
      if (books[2]) { ctx.globalAlpha = 0.55; tiltedCard(ctx, s, books[2], W / 2 + 250, 820, 380, 9); ctx.globalAlpha = 1; }
      tiltedCard(ctx, s, b, W / 2, 720, 480, 0);
      // "#1" belgisi
      ctx.save();
      ctx.translate(W / 2 + 220, 730);
      ctx.beginPath();
      ctx.arc(0, 0, 78, 0, Math.PI * 2);
      ctx.fillStyle = BLUE;
      ctx.shadowColor = 'rgba(0,0,0,0.35)';
      ctx.shadowBlur = 30;
      ctx.fill();
      ctx.shadowColor = 'transparent';
      setFont(ctx, 62, 800, -2);
      ctx.fillStyle = '#fff';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('#1', 0, 4);
      ctx.restore();
    }
  } else {
    posterTitle(ctx, s, 330, true, s.periodLabel);
    const [a, b, c] = books;
    if (b) tiltedCard(ctx, s, b, W / 2 - 260, 840, 390, -10);
    if (c) tiltedCard(ctx, s, c, W / 2 + 260, 840, 390, 10);
    if (a) tiltedCard(ctx, s, a, W / 2, 740, 470, 0);
  }
  posterBrand(ctx, s, 1600, '#FFFFFF');
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
