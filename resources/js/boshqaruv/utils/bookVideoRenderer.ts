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
/** Musiqa/SFX sahna kesimlariga mos tushishi uchun. */
export const TIMING = { INTRO, OUTRO, PER_BOOK } as const;

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
// Uslub: yorqin, quvnoq ranglar (sariq, to'q sariq, binafsha, yalpiz, pushti,
// brend ko'ki, laym), 3D ko'rinishdagi bezaklar va stiker-lentalar.
// Motion: expo easing, harfma-harf matn, shared-element o'tishlar.
const expoOut = (x: number) => { const t = clamp(x); return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); };
const quartInOut = (x: number) => { const t = clamp(x); return t < 0.5 ? 8 * t ** 4 : 1 - Math.pow(-2 * t + 2, 4) / 2; };
const lerp = (a: number, b: number, p: number) => a + (b - a) * p;

type RGB = [number, number, number];
const hex = (h: string): RGB => [parseInt(h.slice(1, 3), 16), parseInt(h.slice(3, 5), 16), parseInt(h.slice(5, 7), 16)];
const css = (c: RGB, a = 1) => `rgba(${c[0] | 0},${c[1] | 0},${c[2] | 0},${a})`;
const mix = (a: RGB, b: RGB, p: number): RGB => [lerp(a[0], b[0], p), lerp(a[1], b[1], p), lerp(a[2], b[2], p)];
const shade = (h: string, k: number) => css(mix(hex(h), k < 0 ? [0, 0, 0] : [255, 255, 255], Math.abs(k)));

interface Theme { bg: string; bg2: string; ink: string; sub: string; tape: string; tapeInk: string; decor: [string, string, string] }
const THEMES: Theme[] = [
  { bg: '#FFCB2F', bg2: '#FFAE00', ink: '#111111', sub: 'rgba(17,17,17,0.62)', tape: '#111111', tapeInk: '#FFCB2F', decor: ['#2E8BEF', '#FF6A2B', '#FFFFFF'] },
  { bg: '#FF7A21', bg2: '#FF5200', ink: '#FFFFFF', sub: 'rgba(255,255,255,0.8)', tape: '#FFE14D', tapeInk: '#111111', decor: ['#FFE14D', '#FFFFFF', '#2E8BEF'] },
  { bg: '#8E5CFF', bg2: '#6536EE', ink: '#FFFFFF', sub: 'rgba(255,255,255,0.8)', tape: '#FFE14D', tapeInk: '#111111', decor: ['#FFE14D', '#FF7AB0', '#FFFFFF'] },
  { bg: '#2ACFB6', bg2: '#0FAE97', ink: '#0B1B19', sub: 'rgba(11,27,25,0.62)', tape: '#111111', tapeInk: '#FFFFFF', decor: ['#FFCB2F', '#FFFFFF', '#8E5CFF'] },
  { bg: '#FF6FA5', bg2: '#FF4488', ink: '#FFFFFF', sub: 'rgba(255,255,255,0.82)', tape: '#FFE14D', tapeInk: '#111111', decor: ['#FFE14D', '#FFFFFF', '#8E5CFF'] },
  { bg: '#3B93F5', bg2: '#1C6FDA', ink: '#FFFFFF', sub: 'rgba(255,255,255,0.82)', tape: '#FFE14D', tapeInk: '#111111', decor: ['#FFE14D', '#FF7A21', '#FFFFFF'] },
  { bg: '#BDE64B', bg2: '#98C826', ink: '#111111', sub: 'rgba(17,17,17,0.62)', tape: '#111111', tapeInk: '#BDE64B', decor: ['#8E5CFF', '#FFFFFF', '#FF7A21'] },
];
const theme = (i: number) => THEMES[((Math.round(i) % THEMES.length) + THEMES.length) % THEMES.length];

/** Fon: ikki mavzu orasida silliq o'tadi (pos — kasr indeks). */
function freshBg(ctx: CanvasRenderingContext2D, pos: number) {
  const i = Math.floor(pos), p = quartInOut(pos - i);
  const a = theme(i), b = theme(i + 1);
  const g = ctx.createLinearGradient(0, 0, W * 0.6, H);
  g.addColorStop(0, css(mix(hex(a.bg), hex(b.bg), p)));
  g.addColorStop(1, css(mix(hex(a.bg2), hex(b.bg2), p)));
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
  glow(ctx, 160, 260, 900, 'rgba(255,255,255,0.30)');
  glow(ctx, W - 100, H - 200, 700, 'rgba(0,0,0,0.07)');
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

/** Harfma-harf chiqish: har harf 28 ms kechikib, pastdan ko'tariladi. */
function charReveal(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, t: number, start: number, align: 'left' | 'center' = 'left') {
  if (!str || t < start) return;
  ctx.save();
  setFont(ctx, size, weight, -size * 0.035);
  ctx.fillStyle = color;
  ctx.textAlign = 'left';
  const total = ctx.measureText(str).width;
  const x0 = align === 'center' ? x - total / 2 : x;
  if (t - start > 0.7 + str.length * 0.028) {
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

/** Sarlavhani 2 qatorga bo'ladi. */
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

/** Yumaloq "chip" (davr, hisoblagich). */
function chip(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, bg: string, fg: string, a: number, align: 'left' | 'right' = 'left') {
  if (a <= 0 || !str) return;
  ctx.save();
  ctx.globalAlpha *= clamp(a);
  setFont(ctx, 30, 700, 1);
  const w = ctx.measureText(str).width + 48, h = 62;
  const x0 = align === 'right' ? x - w : x;
  const yy = y + (1 - expoOut(a)) * 20;
  roundRect(ctx, x0, yy, w, h, h / 2);
  ctx.fillStyle = bg;
  ctx.fill();
  ctx.fillStyle = fg;
  ctx.textBaseline = 'middle';
  ctx.fillText(str, x0 + 24, yy + h / 2 + 1);
  ctx.restore();
}

/** Qiya stiker-lenta ("PROMOKOD" uslubida) — sakrab chiqadi. */
function tape(ctx: CanvasRenderingContext2D, str: string, cx: number, cy: number, rot: number, bg: string, fg: string, p: number, size = 46) {
  if (p <= 0 || !str) return;
  ctx.save();
  setFont(ctx, size, 800, 0);
  const w = ctx.measureText(str).width + size * 1.4, h = size * 1.75;
  ctx.translate(cx, cy);
  ctx.rotate(rot);
  const sc = backOut(clamp(p));
  ctx.scale(sc, sc);
  ctx.globalAlpha *= clamp(p * 3);
  roundRect(ctx, -w / 2 + 8, -h / 2 + 12, w, h, 18);
  ctx.fillStyle = 'rgba(0,0,0,0.18)';
  ctx.fill();
  roundRect(ctx, -w / 2, -h / 2, w, h, 18);
  ctx.fillStyle = bg;
  ctx.fill();
  ctx.fillStyle = fg;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(str, 0, 3);
  ctx.restore();
}

/** Qalin "3D" matn: pastki qatlamlar chuqurlik beradi. */
function text3D(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, color: string, depth: string, p: number) {
  if (p <= 0) return;
  ctx.save();
  setFont(ctx, size, 800, -size * 0.04);
  const e = expoOut(p);
  ctx.globalAlpha = clamp(p * 3);
  const yy = y + (1 - e) * size * 0.5;
  const d = Math.round(size * 0.07);
  ctx.fillStyle = depth;
  for (let i = d; i > 0; i--) ctx.fillText(str, x + i * 0.45, yy + i);
  ctx.fillStyle = color;
  ctx.fillText(str, x, yy);
  ctx.restore();
}

/** Hisoblagich: raqamlar vertikal aylanib almashadi. */
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

// ── 3D bezaklar: yaltiroq sharlar, halqalar, yulduzlar, kapsulalar ──
function sphere(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, c: string) {
  const g = ctx.createRadialGradient(x - r * 0.38, y - r * 0.42, r * 0.08, x, y, r);
  g.addColorStop(0, 'rgba(255,255,255,0.95)');
  g.addColorStop(0.28, c);
  g.addColorStop(1, shade(c, -0.32));
  ctx.fillStyle = g;
  ctx.beginPath();
  ctx.arc(x, y, r, 0, Math.PI * 2);
  ctx.fill();
}

function ring(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, c: string, rot: number) {
  ctx.save();
  ctx.translate(x, y);
  ctx.rotate(rot);
  ctx.scale(1, 0.62);
  const g = ctx.createLinearGradient(-r, -r, r, r);
  g.addColorStop(0, shade(c, 0.45));
  g.addColorStop(0.5, c);
  g.addColorStop(1, shade(c, -0.35));
  ctx.strokeStyle = g;
  ctx.lineWidth = r * 0.42;
  ctx.beginPath();
  ctx.arc(0, 0, r, 0, Math.PI * 2);
  ctx.stroke();
  ctx.restore();
}

function starPath(ctx: CanvasRenderingContext2D, r: number) {
  ctx.beginPath();
  for (let i = 0; i < 10; i++) {
    const a = -Math.PI / 2 + (i * Math.PI) / 5;
    const rr = i % 2 ? r * 0.48 : r;
    ctx.lineTo(Math.cos(a) * rr, Math.sin(a) * rr);
  }
  ctx.closePath();
}

function star(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, c: string, rot: number) {
  ctx.save();
  ctx.translate(x, y + r * 0.12);
  ctx.rotate(rot);
  ctx.lineJoin = 'round';
  starPath(ctx, r);
  ctx.fillStyle = shade(c, -0.3);
  ctx.strokeStyle = shade(c, -0.3);
  ctx.lineWidth = r * 0.22;
  ctx.fill();
  ctx.stroke();
  ctx.translate(0, -r * 0.12);
  starPath(ctx, r);
  const g = ctx.createLinearGradient(-r, -r, r, r);
  g.addColorStop(0, shade(c, 0.4));
  g.addColorStop(1, c);
  ctx.fillStyle = g;
  ctx.strokeStyle = g;
  ctx.fill();
  ctx.stroke();
  ctx.restore();
}

function capsule(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, c: string, rot: number) {
  ctx.save();
  ctx.translate(x, y);
  ctx.rotate(rot);
  const g = ctx.createLinearGradient(0, -r * 0.5, 0, r * 0.5);
  g.addColorStop(0, shade(c, 0.45));
  g.addColorStop(0.45, c);
  g.addColorStop(1, shade(c, -0.3));
  roundRect(ctx, -r, -r * 0.45, r * 2, r * 0.9, r * 0.45);
  ctx.fillStyle = g;
  ctx.fill();
  ctx.restore();
}

const DECOR_SLOTS = [
  { x: 120, y: 610, r: 54 }, { x: 975, y: 690, r: 46 }, { x: 985, y: 1540, r: 70 },
  { x: 105, y: 1560, r: 44 }, { x: 70, y: 1000, r: 30 }, { x: 150, y: 1260, r: 26 },
];
/**
 * Bezak qatlami: seed — sahna raqami (turi/rangi o'zgarib turadi), p — chiqish,
 * out — ketish, front — karta oldida turadiganlari.
 */
function decor(ctx: CanvasRenderingContext2D, th: Theme, seed: number, t: number, p: number, out: number, drift: number, front: boolean, offY = 0) {
  DECOR_SLOTS.forEach((s, j) => {
    if ((j % 3 === 1) !== front) return;
    const a = backOut(clamp((p - j * 0.07) / 0.6)) * (1 - expoOut(out));
    if (a <= 0.01) return;
    const kind = (seed + j) % 4;
    const c = th.decor[(seed + j) % 3];
    const x = s.x + drift * (j % 2 ? 1 : 0.6);
    const y = s.y + offY + Math.sin(t * 1.3 + j * 1.7) * 12;
    const r = s.r * a;
    const rot = t * 0.4 * (j % 2 ? 1 : -1) + j;
    if (kind === 0) sphere(ctx, x, y, r, c);
    else if (kind === 1) ring(ctx, x, y, r, c, rot * 0.3);
    else if (kind === 2) star(ctx, x, y, r, c, rot * 0.2);
    else capsule(ctx, x, y, r, c, rot * 0.3);
  });
}

// ── cards: cached bitmaps, soft shadow, real perspective ─────────
const CARD_W = 640;
const cardCache = new Map<string, HTMLCanvasElement>();
function cardBitmap(s: VideoScene, b: VideoBook): HTMLCanvasElement {
  const img = s.images.get(b.id);
  const key = `${b.id}|${img?.src ?? ''}|${b.name}|${b.price}|${b.oldPrice ?? ''}`;
  let c = cardCache.get(key);
  if (c) return c;
  c = document.createElement('canvas');
  c.width = CARD_W;
  c.height = Math.ceil(CARD_W * CARD_RATIO);
  const x = c.getContext('2d')!;
  x.imageSmoothingQuality = 'high';
  drawBookCard(x, b, img, 0, 0, CARD_W);
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
    x.shadowColor = 'rgba(40,20,0,0.45)';
    x.shadowBlur = 70;
    x.fillStyle = '#000';
    roundRect(x, SHADOW_M, SHADOW_M, CARD_W, ch, 56);
    x.fill();
    x.shadowColor = 'transparent';
    x.globalCompositeOperation = 'destination-out';
    roundRect(x, SHADOW_M, SHADOW_M, CARD_W, ch, 56);
    x.fill();
  }
  const k = w / CARD_W, h = w * CARD_RATIO;
  ctx.save();
  ctx.globalAlpha *= a;
  ctx.drawImage(shadowBmp, cx - w / 2 - SHADOW_M * k, cy - h / 2 - SHADOW_M * k + 30 * k, w + 2 * SHADOW_M * k, h + 2 * SHADOW_M * k);
  ctx.restore();
}

let scratch: HTMLCanvasElement | null = null;
/**
 * Kartani Y o'qi atrofida perspektivada chizadi (rotY — radian). Tasmalar
 * avval yordamchi canvas'ga chiziladi — ustma-ust joylari chiziq bo'lib ko'rinmasin.
 */
function card3D(ctx: CanvasRenderingContext2D, bmp: HTMLCanvasElement, cx: number, cy: number, w: number, rotY = 0, alpha = 1, shadow = 1) {
  if (alpha <= 0) return;
  const h = w * CARD_RATIO;
  ctx.save();
  ctx.globalAlpha *= alpha;
  ctx.imageSmoothingQuality = 'high';
  if (shadow > 0) cardShadow(ctx, cx, cy, w * Math.max(0.6, Math.cos(rotY)), shadow);
  if (Math.abs(rotY) < 0.003) {
    ctx.drawImage(bmp, cx - w / 2, cy - h / 2, w, h);
  } else {
    const N = 36, D = 2400, sw = bmp.width / N;
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
    sc.imageSmoothingQuality = 'high';
    sc.clearRect(0, 0, bw, bh);
    for (let i = 0; i < N; i++) {
      const hh = h * (fs[i] + fs[i + 1]) / 2;
      const x0 = Math.min(xs[i], xs[i + 1]) - minX + 3;
      sc.drawImage(bmp, i * sw, 0, sw, bmp.height, x0, (bh - hh) / 2, Math.abs(xs[i + 1] - xs[i]) + 0.8, hh);
    }
    ctx.drawImage(scratch, 0, 0, bw, bh, cx + minX - 3, cy - bh / 2, bw, bh);
  }
  ctx.restore();
}

// ── scene timing helpers ─────────────────────────────────────────
/** Kitoblar orasidagi "kamera" pozitsiyasi: har qadamda silliq siljiydi. */
function stepPos(t: number, n: number): number {
  if (t < INTRO) return 0;
  const k = Math.min(n - 1, Math.floor((t - INTRO) / PER_BOOK));
  const l = t - INTRO - k * PER_BOOK;
  return k > 0 && l < 0.8 ? k - 1 + quartInOut(l / 0.8) : k;
}
const stepStart = (k: number) => INTRO + k * PER_BOOK;

/** Kirish sarlavhasi: katta, harfma-harf; oxirida yuqoriga chiqib ketadi yoki "joylashadi". */
function introTitle(ctx: CanvasRenderingContext2D, s: VideoScene, t: number, th: Theme, dock: boolean) {
  const [l1, l2] = splitTitle(s.title);
  const x = 80;
  if (dock) {
    const e = quartInOut(prog(t, INTRO - 1.0, 0.9));
    const s1 = lerp(72, 46, e);
    const s2 = fitSize(ctx, l2, lerp(124, 80, e), 800, W - 160);
    const y1 = lerp(800, 350, e);
    charReveal(ctx, l1, x, y1, s1, 800, th.ink, t, 0.2);
    charReveal(ctx, l2, x, y1 + s2 * 1.02, s2, 800, th.ink, t, 0.2 + BEAT);
    return;
  }
  const out = expoOut(prog(t, INTRO - 0.75, 0.6));
  if (out >= 1) return;
  ctx.save();
  ctx.globalAlpha = 1 - out;
  ctx.translate(0, -out * 260);
  const s2 = fitSize(ctx, l2, 124, 800, W - 160);
  charReveal(ctx, l1, x, 800, 72, 800, th.ink, t, 0.2);
  charReveal(ctx, l2, x, 800 + s2 * 1.02, s2, 800, th.ink, t, 0.2 + BEAT);
  ctx.restore();
}

const CARD_CY = 1080;
const TAGLINES = ['Hafta hiti', 'Tavsiya qilamiz', "O'qib ko'ring", 'Sevimli tanlov', 'Yangi kashfiyot', 'Albatta o\'qing', 'Kitobxonlar tanlovi', 'Ajoyib tanlov', 'Top tanlov', 'Qiziqarli asar'];

function discountOf(b: VideoBook) {
  return b.oldPrice && b.oldPrice > b.price ? Math.floor(((b.oldPrice - b.price) / b.oldPrice) * 100) : 0;
}

// ── carousel → coverflow ─────────────────────────────────────────
function carouselScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const n = s.books.length;
  const enter = expoOut(prog(t, INTRO - 1.15, 1.5));
  const P = stepPos(t, n) - (1 - enter) * 2.2;
  const bgPos = t < INTRO - 0.4 ? 0 : 1 + Math.max(0, stepPos(t, n)) - (1 - quartInOut(prog(t, INTRO - 0.4, 0.8)));
  freshBg(ctx, bgPos);
  const cur = Math.max(0, Math.round(Math.max(0, P)));
  const th = theme(cur + 1);
  const thIntro = theme(0);

  // bezaklar (orqa)
  if (t < INTRO) decor(ctx, thIntro, 0, t, prog(t, 0.3, 1.2), prog(t, INTRO - 0.7, 0.5), 0, false);
  const lt = t - stepStart(cur);
  const moveOut = cur < n - 1 ? prog(t, stepStart(cur + 1), 0.45) : 0;
  if (t >= INTRO - 0.2) decor(ctx, th, cur + 1, t, prog(lt, 0.1, 1.0), moveOut, -(Math.max(0, P) - cur) * 260, false);

  chip(ctx, s.periodLabel, 80, 200, 'rgba(17,17,17,0.9)', '#FFFFFF', prog(t, 0.05, 0.5));
  introTitle(ctx, s, t, thIntro, false);

  // kartalar: markaziy — to'liq, yondagilar kichik va burilgan (qoraytirilmaydi)
  const order = s.books.map((b, i) => ({ b, o: i - P })).filter((x) => Math.abs(x.o) < 2.4).sort((x, y) => Math.abs(y.o) - Math.abs(x.o));
  for (const { b, o } of order) {
    const ao = Math.min(Math.abs(o), 1.6);
    const w = 580 * (1 - 0.16 * ao);
    const cx = W / 2 + Math.sign(o) * (ao * 580 + Math.max(0, Math.abs(o) - 1.6) * 300);
    const rot = Math.max(-1, Math.min(1, o)) * 0.38;
    const bob = Math.sin(t * 1.5) * 7 * (1 - ao);
    card3D(ctx, cardBitmap(s, b), cx, CARD_CY + bob, w, rot, 1 - clamp((ao - 1.3) * 2), 1 - 0.5 * Math.min(ao, 1));
  }
  if (t >= INTRO - 0.2) decor(ctx, th, cur + 1, t, prog(lt, 0.1, 1.0), moveOut, -(Math.max(0, P) - cur) * 420, true);

  if (t >= INTRO - 0.3) {
    chip(ctx, `${String(cur + 1).padStart(2, '0')} / ${String(n).padStart(2, '0')}`, W - 80, 200, 'rgba(255,255,255,0.92)', '#111111', prog(t, INTRO - 0.3, 0.5), 'right');
    const hs = prog(lt + (cur === 0 ? 0.2 : 0), 0.35, 1);
    const tag = TAGLINES[cur % TAGLINES.length];
    charReveal(ctx, tag, 80, 400, fitSize(ctx, tag, 104, 800, W - 160), 800, th.ink, lt, cur === 0 ? -0.1 : 0.35);
    const b = s.books[cur];
    if (b?.author) fadeText(ctx, b.author, 80, 470, 40, 600, th.sub, hs);
    const pct = discountOf(b);
    tape(ctx, pct ? `-${pct}% CHEGIRMA` : `TOP ${cur + 1}`, 800, 1540, -0.12, th.tape, th.tapeInk, prog(lt, cur === 0 ? 0.3 : 0.75, 0.55));
  }
}

// ── countdown → deck ─────────────────────────────────────────────
const CONFETTI = Array.from({ length: 36 }, (_, i) => ({ x: (i * 197) % W, d: 0.6 + ((i * 37) % 10) / 12, w: 14 + (i % 3) * 6, r: i * 1.3, c: i % 4 }));
function countdownScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const n = s.books.length;
  const seq = [...s.books].reverse(); // #n … #1
  const P = stepPos(t, n);
  const enter = expoOut(prog(t, INTRO - 1.15, 1.4));
  const bgPos = t < INTRO - 0.4 ? 0 : 1 + P - (1 - quartInOut(prog(t, INTRO - 0.4, 0.8)));
  freshBg(ctx, bgPos);
  const cur = Math.round(P);
  const th = theme(cur + 1);
  const lt = t - stepStart(cur);
  const moveOut = cur < n - 1 ? prog(t, stepStart(cur + 1), 0.45) : 0;
  if (t < INTRO) decor(ctx, theme(0), 0, t, prog(t, 0.3, 1.2), prog(t, INTRO - 0.7, 0.5), 0, false);
  else decor(ctx, th, cur + 2, t, prog(lt, 0.1, 1.0), moveOut, 0, false);

  chip(ctx, `TOP ${n} · ${s.periodLabel}`, 80, 200, 'rgba(17,17,17,0.9)', '#FFFFFF', prog(t, 0.05, 0.5));
  introTitle(ctx, s, t, theme(0), false);

  // #N — qalin 3D raqam, har qadamda aylanib kamayadi
  if (t > INTRO - 0.5) {
    ctx.save();
    ctx.globalAlpha = prog(t, INTRO - 0.5, 0.5);
    setFont(ctx, 170, 800, -7);
    const hw = ctx.measureText('#').width;
    ctx.restore();
    text3D(ctx, '#', 80, 520, 170, th.ink === '#FFFFFF' ? '#FFFFFF' : '#111111', shade(th.bg2, -0.25), prog(t, INTRO - 0.5, 0.6));
    ctx.save();
    ctx.globalAlpha = prog(t, INTRO - 0.5, 0.5);
    const d = Math.round(170 * 0.07);
    for (let i = d; i > 0; i -= 2) rollNumber(ctx, n - P - 1, (v) => String(v + 1), 80 + hw + 6 + i * 0.45, 520 + i, 170, 800, shade(th.bg2, -0.25));
    rollNumber(ctx, n - P - 1, (v) => String(v + 1), 80 + hw + 6, 520, 170, 800, th.ink === '#FFFFFF' ? '#FFFFFF' : '#111111');
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
      ctx.translate(W / 2 - q * 950, CARD_CY + q * 140);
      ctx.rotate(-q * 0.4);
      card3D(ctx, bmp, 0, 0, 540, q * 0.6, 1 - clamp((q - 0.4) / 0.6), 1 - q);
      ctx.restore();
      continue;
    }
    if (d > 3) continue;
    const w = 540 * (1 - 0.07 * d);
    card3D(ctx, bmp, W / 2 + d * 26, CARD_CY + d * 50 + dy + Math.sin(t * 1.5) * 6 * (d < 0.5 ? 1 : 0), w, 0, d <= 2 ? 1 : 3 - d, d < 1 ? 1 : 0.5);
  }
  if (t >= INTRO) decor(ctx, th, cur + 2, t, prog(lt, 0.1, 1.0), moveOut, 0, true);

  const final = P > n - 1.001;
  const fl = final ? t - stepStart(n - 1) - 0.8 : -1;
  if (final && fl > 0.2 && fl < 1.4) {
    const p = (fl - 0.2) / 1.2, w = 540, h = w * CARD_RATIO, x = W / 2 - w / 2, y = CARD_CY - h / 2;
    ctx.save();
    roundRect(ctx, x, y, w, h, 47);
    ctx.clip();
    const sx = x - 300 + p * (w + 600);
    const g = ctx.createLinearGradient(sx - 160, y, sx + 160, y + 200);
    g.addColorStop(0, 'rgba(255,255,255,0)');
    g.addColorStop(0.5, 'rgba(255,255,255,0.5)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = g;
    ctx.fillRect(x, y, w, h);
    ctx.restore();
  }
  if (final && fl > 0) {
    // konfetti
    const cols = ['#FFE14D', '#FFFFFF', '#FF6FA5', '#2E8BEF'];
    for (const c of CONFETTI) {
      const y = -40 + (fl * 900 * c.d) % 2100;
      ctx.save();
      ctx.translate(c.x + Math.sin(fl * 3 + c.r) * 30, y);
      ctx.rotate(c.r + fl * 4 * c.d);
      ctx.globalAlpha = clamp(fl * 3);
      ctx.fillStyle = cols[c.c];
      ctx.fillRect(-c.w / 2, -c.w * 0.3, c.w, c.w * 0.6);
      ctx.restore();
    }
    tape(ctx, "ENG KO'P SOTILGAN", W / 2, 1600, -0.08, th.tape, th.tapeInk, prog(fl, 0, 0.6), 48);
  } else if (t >= INTRO) {
    const b = seq[cur];
    const pct = b ? discountOf(b) : 0;
    if (pct) tape(ctx, `-${pct}% CHEGIRMA`, 810, 1580, -0.12, th.tape, th.tapeInk, prog(lt, cur === 0 ? 0.3 : 0.75, 0.55));
    else if (b?.author) fadeText(ctx, b.author, W / 2, 1640, 40, 600, th.sub, prog(lt, cur === 0 ? 0.2 : 0.7, 0.5), 'center');
  }
}

// ── grid → assemble ──────────────────────────────────────────────
function gridLayout(count: number) {
  const cols = count > 4 ? 3 : 2;
  const rows = Math.ceil(count / cols);
  const gap = 28, top = 560, avail = 1640 - top;
  const cw = Math.min(390, (W - 160 - (cols - 1) * gap) / cols, (avail - (rows - 1) * gap) / rows / CARD_RATIO);
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
  return { cw, pos, cy: y0 + gridH / 2, bottom: y0 + gridH };
}

function gridScene(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  const th = theme(3);
  freshBg(ctx, 3);
  decor(ctx, th, 3, t, prog(t, 0.3, 1.2), 0, 0, false);
  chip(ctx, s.periodLabel, 80, 200, 'rgba(17,17,17,0.9)', '#FFFFFF', prog(t, 0.05, 0.5));
  introTitle(ctx, s, t, th, true);
  const books = s.books.slice(0, GRID_MAX);
  const { cw, pos, cy, bottom } = gridLayout(books.length);
  const enter = expoOut(prog(t, INTRO - 1.15, 1.3));
  const outroAt = durationOf('grid', s.books.length) - OUTRO;
  const push = 1 + 0.035 * quartInOut(prog(t, INTRO + 1.2, outroAt - INTRO - 1.2));
  ctx.save();
  ctx.translate(W / 2, cy);
  ctx.scale(push, push);
  ctx.translate(-W / 2, -cy);
  for (let i = books.length - 1; i >= 0; i--) {
    const p = expoOut(prog(t, INTRO + 0.05 + i * 0.08, 0.95));
    const sy = 1100 - i * 8 + (1 - enter) * 1300;
    const r0 = (i % 2 ? 1 : -1) * (2 + i * 1.6) * Math.PI / 180;
    ctx.save();
    ctx.translate(lerp(W / 2, pos[i].x, p), lerp(sy, pos[i].y, p));
    ctx.rotate(lerp(r0, 0, p));
    card3D(ctx, cardBitmap(s, books[i]), 0, 0, lerp(460, cw, p), 0, 1, 1);
    ctx.restore();
  }
  ctx.restore();
  decor(ctx, th, 3, t, prog(t, 0.3, 1.2), 0, 0, true);
  tape(ctx, `${s.books.length} TA KITOB`, 830, Math.min(1620, bottom + 10), -0.12, th.tape, th.tapeInk, prog(t, INTRO + 0.9, 0.6));
}

// ── outro: oq-pastel fon, ikonka soyasiz ──────────────────────────
function outroBg(ctx: CanvasRenderingContext2D, t = 0) {
  fill(ctx, '#F7F9FC');
  const blobs: [number, number, number, string][] = [
    [150 + 120 * Math.sin(t * 0.4), 380, 700, 'rgba(160,205,255,0.75)'],
    [960 - 100 * Math.sin(t * 0.35), 1050, 650, 'rgba(214,200,255,0.65)'],
    [300 + 140 * Math.cos(t * 0.3), 1720, 700, 'rgba(255,214,190,0.7)'],
  ];
  for (const [x, y, r, c] of blobs) glow(ctx, x, y, r, c);
}

function fill(ctx: CanvasRenderingContext2D, color: string) {
  ctx.fillStyle = color;
  ctx.fillRect(0, 0, W, H);
}

/** favicon.svg — burchaklari shaffof (rx 115/512); aniq shu shaklda qirqib chiziladi. */
function drawIcon(ctx: CanvasRenderingContext2D, icon: HTMLImageElement, cx: number, cy: number, size: number) {
  ctx.save();
  roundRect(ctx, cx - size / 2, cy - size / 2, size, size, size * (115 / 512));
  ctx.clip();
  ctx.drawImage(icon, cx - size / 2, cy - size / 2, size, size);
  ctx.restore();
}

function outro(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  outroBg(ctx, t);
  const icon = scene.assets.icon;
  const p = prog(t, 0, 0.9);
  if (icon && p > 0) {
    ctx.save();
    ctx.globalAlpha = clamp(p * 4);
    drawIcon(ctx, icon, W / 2, 655, 190 * lerp(0.2, 1, backOut(p) * 0.35 + expoOut(p) * 0.65));
    ctx.restore();
  }
  charReveal(ctx, 'Kitobchi', W / 2, 900, 118, 700, INK, t, 0.3, 'center');
  fadeText(ctx, 'Ilovani yuklab oling', W / 2, 1010, 50, 600, '#4B5563', prog(t, 0.75, 0.6), 'center');
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
  if (template === 'grid') return INTRO + 0.05 + 0.08 * Math.min(count, GRID_MAX) + 1.6;
  return INTRO + PER_BOOK * 0.7;
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
  outroBg(ctx, 0);
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
// Asosiy mazmun profil to'ridagi 3:4 kesimga (y ≈ 240…1680) sig'adi.
function posterBrand(ctx: CanvasRenderingContext2D, scene: VideoScene, y: number, color: string) {
  ctx.save();
  setFont(ctx, 40, 700, -1);
  const tw = ctx.measureText('Kitobchi').width;
  const iw = 64, gap = 18;
  const x0 = W / 2 - (iw + gap + tw) / 2;
  if (scene.assets.icon) drawIcon(ctx, scene.assets.icon, x0 + iw / 2, y - 16, iw);
  ctx.fillStyle = color;
  ctx.textAlign = 'left';
  ctx.fillText('Kitobchi', x0 + iw + gap, y);
  ctx.restore();
}

function tiltedCard(ctx: CanvasRenderingContext2D, s: VideoScene, b: VideoBook, cx: number, cy: number, w: number, deg: number) {
  ctx.save();
  ctx.translate(cx, cy);
  ctx.rotate(deg * Math.PI / 180);
  card3D(ctx, cardBitmap(s, b), 0, 0, w, 0, 1, 1);
  ctx.restore();
}

/** Video uchun cover: shablonga mos, 1080×1920. */
export function drawPoster(ctx: CanvasRenderingContext2D, s: VideoScene) {
  const books = s.books;
  const idx = s.template === 'grid' ? 3 : s.template === 'countdown' ? 2 : 0;
  const th = theme(idx);
  freshBg(ctx, idx);
  decor(ctx, th, idx, 5, 1, 0, 0, false, 90);
  chip(ctx, s.template === 'countdown' ? `TOP ${books.length} · ${s.periodLabel}` : s.periodLabel, 80, 290, 'rgba(17,17,17,0.9)', '#FFFFFF', 1);
  const [l1, l2] = splitTitle(s.title);
  const s2 = fitSize(ctx, l2, 124, 800, W - 160);
  charReveal(ctx, l1, 80, 460, 72, 800, th.ink, 9, 0);
  charReveal(ctx, l2, 80, 460 + s2 * 1.02, s2, 800, th.ink, 9, 0);

  if (s.template === 'grid') {
    const covers = books.filter((b) => s.images.get(b.id)).slice(0, 6);
    const cols = covers.length > 4 ? 3 : 2;
    const gap = 26, cw = cols === 3 ? 270 : 340, ch = cw * 1.42;
    const rows = Math.ceil(covers.length / cols);
    const y0 = 700 + Math.max(0, (1500 - 700 - (rows * ch + (rows - 1) * gap)) / 2);
    covers.forEach((b, i) => {
      const r = Math.floor(i / cols), c = i % cols;
      const inRow = r === rows - 1 ? covers.length - r * cols : cols;
      const x = W / 2 - (inRow * cw + (inRow - 1) * gap) / 2 + c * (cw + gap);
      const y = y0 + r * (ch + gap);
      ctx.save();
      ctx.shadowColor = 'rgba(40,20,0,0.3)';
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
    tape(ctx, `${books.length} TA KITOB`, 820, 1520, -0.12, th.tape, th.tapeInk, 1);
  } else {
    const [a, b, c] = s.template === 'countdown' ? [books[0], books[1], books[2]] : books;
    if (b) tiltedCard(ctx, s, b, W / 2 - 250, 1180, 380, -10);
    if (c) tiltedCard(ctx, s, c, W / 2 + 250, 1180, 380, 10);
    if (a) tiltedCard(ctx, s, a, W / 2, 1110, 470, 0);
    tape(ctx, s.template === 'countdown' ? "#1 — ENG KO'P SOTILGAN" : `TOP ${books.length}`, W / 2 + 120, 1490, -0.1, th.tape, th.tapeInk, 1, 44);
  }
  decor(ctx, th, idx, 5, 1, 0, 0, true, 90);
  posterBrand(ctx, s, 1640, th.ink);
}

// ── recording ────────────────────────────────────────────────────
// H.264 1080×1920 uchun kamida 4.0-daraja kerak (…1F = 3.1 — faqat 720p gacha).
// VP9 sekin kompyuterlarda 1080×1920 da bo'sh fayl berishi mumkin, shuning uchun VP8.
// Ovozli yozuv uchun avval AAC (mp4a) bilan H.264 — Instagram to'g'ridan-to'g'ri qabul qiladi.
const AUDIO_MIME_CANDIDATES = [
  'video/mp4;codecs=avc1.640033,mp4a.40.2',
  'video/mp4;codecs=avc1.4D0033,mp4a.40.2',
  'video/mp4;codecs=avc1.42E033,mp4a.40.2',
  'video/mp4;codecs=avc1,mp4a.40.2',
  'video/webm;codecs=vp8,opus',
];
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

function mimeCandidates(format: 'any' | 'webm', withAudio = false): string[] {
  if (typeof MediaRecorder === 'undefined') return [];
  const all = withAudio ? [...AUDIO_MIME_CANDIDATES, ...MIME_CANDIDATES] : MIME_CANDIDATES;
  const list = format === 'webm' ? all.filter((m) => m.startsWith('video/webm')) : all;
  return list.filter((m) => MediaRecorder.isTypeSupported(m));
}

/** Yozilgan fayl aniq H.264 (+ ovoz bo'lsa AAC) — Instagram uchun tayyor ekanmi. */
export const isInstagramReady = (mime: string, withAudio = false) => mime.includes('avc1') && (!withAudio || mime.includes('mp4a'));

/**
 * Videoni real vaqtda yozadi (davomiyligi — shablon uzunligi). Brauzer
 * tabi ochiq turishi kerak: fonda requestAnimationFrame to'xtab qoladi.
 */
export function recordVideo(
  canvas: HTMLCanvasElement,
  scene: VideoScene,
  onProgress: (p: number) => void,
  format: 'any' | 'webm' = 'any',
  audio: AudioBuffer | null = null,
): Promise<{ blob: Blob; ext: 'mp4' | 'webm'; mime: string }> {
  const ctx = canvas.getContext('2d');
  if (!ctx) return Promise.reject(new Error('Canvas topilmadi'));

  const total = durationOf(scene.template, scene.books.length);
  const stream = canvas.captureStream(30);
  // Ovoz: tayyor AudioBuffer video bilan bir vaqtda ijro etilib, oqimga qo'shiladi
  let ac: AudioContext | null = null;
  let src: AudioBufferSourceNode | null = null;
  if (audio) {
    ac = new AudioContext();
    const dest = ac.createMediaStreamDestination();
    src = ac.createBufferSource();
    src.buffer = audio;
    src.connect(dest);
    dest.stream.getAudioTracks().forEach((tr) => stream.addTrack(tr));
  }
  // isTypeSupported "ha" desa ham konstruktor rad etishi mumkin — navbatdagisini sinaymiz
  let recorder: MediaRecorder | null = null;
  let mimeType = '';
  for (const m of mimeCandidates(format, !!audio)) {
    try {
      recorder = new MediaRecorder(stream, { mimeType: m, videoBitsPerSecond: 10_000_000, audioBitsPerSecond: 192_000 });
      mimeType = m;
      break;
    } catch {
      /* keyingi format */
    }
  }
  if (!recorder) {
    stream.getTracks().forEach((tr) => tr.stop());
    void ac?.close();
    return Promise.reject(new Error("Bu brauzer videoni yozishni qo'llab-quvvatlamaydi. Google Chrome'dan foydalaning."));
  }
  const chunks: BlobPart[] = [];
  recorder.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };

  return new Promise((resolve, reject) => {
    recorder!.onerror = () => reject(new Error('Video yozishda xatolik'));
    recorder!.onstop = () => {
      stream.getTracks().forEach((tr) => tr.stop());
      void ac?.close();
      const ext = mimeType.startsWith('video/mp4') ? 'mp4' : 'webm';
      resolve({ blob: new Blob(chunks, { type: mimeType.split(';')[0] }), ext, mime: mimeType });
    };
    drawFrame(ctx, scene, 0);
    void ac?.resume();
    recorder!.start(250);
    src?.start();
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
