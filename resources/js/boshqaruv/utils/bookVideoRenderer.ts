/**
 * KITOB VIDEOLARI — 1080×1920 Reels videosini canvas'da chizish va yozib olish.
 *
 * Kitoblar ilovadagi kitob kartasi ko'rinishida chiziladi (oq karta, rasm,
 * nom, kulrang narx plashkasi + yurakcha). Uslub — toza "editorial": tekis
 * ranglar, kuchli tipografiya, bitta ko'k aksent, zarbga mos crossfade.
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

// ── editorial style: flat colours, strong type, one accent ───────
const PAPER = '#F4F6FA';
const NIGHT = '#0B1220';
const SKY = '#6FB6FF';
const XF = 0.35; // sahnalar orasidagi crossfade

function fill(ctx: CanvasRenderingContext2D, color: string) {
  ctx.fillStyle = color;
  ctx.fillRect(0, 0, W, H);
}

/** Juda yumshoq nur dog'i — tekis fonga chuqurlik beradi. */
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

function fadeText(ctx: CanvasRenderingContext2D, str: string, x: number, y: number, size: number, weight: number, color: string, a: number, align: CanvasTextAlign = 'left', spacing = -size * 0.02) {
  if (a <= 0 || !str) return;
  ctx.save();
  ctx.globalAlpha = clamp(a);
  setFont(ctx, size, weight, spacing);
  ctx.fillStyle = color;
  ctx.textAlign = align;
  ctx.fillText(str, x, y + (1 - easeOut(a)) * 24);
  ctx.restore();
}

/** Kichik, keng harf oralig'idagi yorliq (overline). */
const label = (ctx: CanvasRenderingContext2D, str: string, x: number, y: number, color: string, a: number, align: CanvasTextAlign = 'left') =>
  fadeText(ctx, str.toUpperCase(), x, y, 28, 700, color, a, align, 5);

function line(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, p: number, track: string, color: string) {
  ctx.fillStyle = track;
  ctx.fillRect(x, y, w, 4);
  ctx.fillStyle = color;
  ctx.fillRect(x, y, w * clamp(p), 4);
}

/** Karta sahnaga ko'tarilib kiradi, keyin juda sekin yaqinlashadi. */
function card(ctx: CanvasRenderingContext2D, s: VideoScene, b: VideoBook, cx: number, top: number, cw: number, l: number, dur: number, fromX = 0) {
  const ch = cw * CARD_RATIO;
  const e = easeOut(prog(l, 0.05, 0.6));
  const z = 1 + 0.025 * (l / dur);
  ctx.save();
  ctx.globalAlpha = clamp(prog(l, 0.05, 0.25));
  ctx.translate(cx + (1 - e) * fromX, top + ch / 2 + (1 - e) * (fromX ? 0 : 90));
  ctx.scale(z, z);
  drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
  ctx.restore();
}

// ── scenes ───────────────────────────────────────────────────────
/** Kirish: tungi fon, sarlavha zarbma-zarb, pastda muqovalar lentasi sekin suzadi. */
function intro(ctx: CanvasRenderingContext2D, s: VideoScene, t: number) {
  fill(ctx, NIGHT);
  glow(ctx, 200, 420, 900, 'rgba(33,120,215,0.28)');
  const x = 84;
  const [l1, l2] = splitTitle(s.title);
  const size2 = fitSize(ctx, l2, 104, 800, W - 170);
  label(ctx, s.periodLabel, x, 640, SKY, prog(t, 0.1, 0.45));
  reveal(ctx, l1, x, 760, 66, 700, 'rgba(255,255,255,0.92)', prog(t, BEAT, 0.55));
  reveal(ctx, l2, x, 760 + size2 * 1.08, size2, 800, '#FFFFFF', prog(t, BEAT * 2, 0.55));

  // muqovalar lentasi
  const covers = s.books.filter((b) => s.images.get(b.id));
  const cw = 176, ch = 250, gap = 22, y = 1040;
  const drift = -t * 36;
  covers.forEach((b, i) => {
    const a = easeOut(prog(t, BEAT * 3 + i * 0.07, 0.5));
    if (a <= 0) return;
    const cx = x + i * (cw + gap) + drift;
    if (cx > W) return;
    ctx.save();
    ctx.globalAlpha = a;
    roundRect(ctx, cx, y + (1 - a) * 40, cw, ch, 16);
    ctx.clip();
    drawCover(ctx, s.images.get(b.id)!, cx, y + (1 - a) * 40, cw, ch);
    ctx.restore();
  });
  fadeText(ctx, `${s.books.length} ta kitob`, x, 1390, 34, 600, 'rgba(255,255,255,0.55)', prog(t, BEAT * 3.5, 0.45));
}

function carouselScene(ctx: CanvasRenderingContext2D, s: VideoScene, i: number, l: number) {
  const n = s.books.length;
  fill(ctx, PAPER);
  glow(ctx, W / 2, 900, 800, 'rgba(33,120,215,0.07)');
  label(ctx, s.title, 84, 300, MUTED, 1);
  fadeText(ctx, `${String(i + 1).padStart(2, '0')} / ${String(n).padStart(2, '0')}`, W - 84, 300, 28, 700, INK, 1, 'right', 2);
  line(ctx, 84, 330, W - 168, (i + clamp(l / PER_BOOK)) / n, '#E3E8EF', BLUE);
  card(ctx, s, s.books[i], W / 2, 410, 600, l, PER_BOOK, 160);
  const b = s.books[i];
  if (b.author) fadeText(ctx, b.author, W / 2, 1450, 38, 500, MUTED, prog(l, 0.3, 0.4), 'center');
}

function countdownScene(ctx: CanvasRenderingContext2D, s: VideoScene, k: number, l: number) {
  const n = s.books.length;
  const idx = n - 1 - k;
  const first = idx === 0;
  fill(ctx, NIGHT);
  glow(ctx, W / 2, 1050, first ? 800 : 650, first ? 'rgba(33,120,215,0.42)' : 'rgba(33,120,215,0.2)');
  label(ctx, first ? "Eng ko'p sotilgan" : `Top ${n}`, 84, 300, SKY, 1);
  reveal(ctx, `#${idx + 1}`, 78, 500, 180, 800, first ? '#4EA3FF' : '#FFFFFF', prog(l, 0.02, 0.5), 'left', -8);
  fadeText(ctx, s.title, W - 84, 300, 30, 600, 'rgba(255,255,255,0.5)', 1, 'right');
  card(ctx, s, s.books[idx], W / 2, 580, 520, l, PER_BOOK);
  const b = s.books[idx];
  if (b.author) fadeText(ctx, b.author, W / 2, 1520, 38, 500, 'rgba(255,255,255,0.6)', prog(l, 0.3, 0.4), 'center');
}

function gridScene(ctx: CanvasRenderingContext2D, s: VideoScene, l: number) {
  fill(ctx, PAPER);
  glow(ctx, W / 2, 1100, 900, 'rgba(33,120,215,0.08)');
  const [l1, l2] = splitTitle(s.title);
  const size2 = fitSize(ctx, l2, 88, 800, W - 170);
  label(ctx, s.periodLabel, 84, 250, MUTED, prog(l, 0, 0.4));
  reveal(ctx, l1, 84, 345, 60, 700, INK, prog(l, 0.05, 0.5));
  reveal(ctx, l2, 84, 345 + size2 * 1.05, size2, 800, BLUE, prog(l, 0.15, 0.5));

  const books = s.books.slice(0, GRID_MAX);
  const cols = books.length > 4 ? 3 : 2;
  const rows = Math.ceil(books.length / cols);
  const gap = 28;
  const top = 540;
  const avail = 1660 - top;
  const cw = Math.min(380, (W - 168 - (cols - 1) * gap) / cols, (avail - (rows - 1) * gap) / rows / CARD_RATIO);
  const ch = cw * CARD_RATIO;
  const gridW = cols * cw + (cols - 1) * gap;
  const gridH = rows * ch + (rows - 1) * gap;
  const y0 = top + Math.max(0, (avail - gridH) / 2);
  books.forEach((b, i) => {
    const r = Math.floor(i / cols), c = i % cols;
    const inRow = r === rows - 1 ? books.length - r * cols : cols;
    const rowW = inRow * cw + (inRow - 1) * gap;
    const x = W / 2 - (inRow === cols ? gridW : rowW) / 2 + c * (cw + gap);
    const y = y0 + r * (ch + gap);
    const lp = l - BEAT - i * GRID_STEP;
    if (lp <= 0) return;
    const e = easeOut(prog(lp, 0, 0.55));
    ctx.save();
    ctx.globalAlpha = clamp(prog(lp, 0, 0.25));
    ctx.translate(x + cw / 2, y + ch / 2 + (1 - e) * 70);
    drawBookCard(ctx, b, s.images.get(b.id), -cw / 2, -ch / 2, cw);
    ctx.restore();
  });
}

function outro(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const g = ctx.createLinearGradient(0, 0, 0, H);
  g.addColorStop(0, '#2B86E8');
  g.addColorStop(1, '#1559AE');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, W, H);
  glow(ctx, W / 2, 700, 700, 'rgba(255,255,255,0.14)');
  const icon = scene.assets.icon;
  const p = easeOut(prog(t, 0.1, 0.7));
  if (icon && p > 0) {
    ctx.save();
    ctx.globalAlpha = p;
    ctx.translate(W / 2, 655 + (1 - p) * 40);
    ctx.shadowColor = 'rgba(4,20,50,0.35)';
    ctx.shadowBlur = 60;
    ctx.shadowOffsetY = 26;
    ctx.drawImage(icon, -95, -95, 190, 190);
    ctx.restore();
  }
  reveal(ctx, 'Kitobchi', W / 2, 900, 118, 700, '#FFFFFF', prog(t, 0.4, 0.55), 'center', -4);
  fadeText(ctx, 'Ilovani yuklab oling', W / 2, 1010, 50, 600, 'rgba(255,255,255,0.78)', prog(t, 0.75, 0.5), 'center');
  const badges = [scene.assets.appStore, scene.assets.googlePlay];
  badges.forEach((b, i) => {
    if (!b) return;
    const a = easeOut(prog(t, 1.0 + i * 0.12, 0.5));
    if (a <= 0) return;
    const bw = 370, bh = 111;
    const bx = W / 2 - bw - 12 + i * (bw + 24);
    const by = 1085 + (1 - a) * 30;
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

/** Sahna kesimlari (soniya) — shu nuqtalarda crossfade bo'ladi. */
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

function drawScene(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const outroAt = durationOf(scene.template, scene.books.length) - OUTRO;
  if (t < INTRO) intro(ctx, scene, t);
  else if (t >= outroAt) outro(ctx, scene, t - outroAt);
  else if (scene.template === 'grid') gridScene(ctx, scene, t - INTRO);
  else {
    const k = Math.min(scene.books.length - 1, Math.floor((t - INTRO) / PER_BOOK));
    const l = t - INTRO - k * PER_BOOK;
    if (scene.template === 'countdown') countdownScene(ctx, scene, k, l);
    else carouselScene(ctx, scene, k, l);
  }
}

/** Bitta kadrni chizadi: t — soniya. */
export function drawFrame(ctx: CanvasRenderingContext2D, scene: VideoScene, t: number) {
  const last = [...cutsOf(scene)].reverse().find((c) => t >= c);
  const since = last === undefined ? Infinity : t - last;
  if (since >= XF) {
    drawScene(ctx, scene, t);
    return;
  }
  // oldingi sahnaning oxirgi kadri ustiga yangisi yumshoq chiqadi
  const e = easeInOut(since / XF);
  drawScene(ctx, scene, last! - 0.001);
  ctx.save();
  ctx.globalAlpha = e;
  ctx.translate(0, (1 - e) * 60);
  drawScene(ctx, scene, t);
  ctx.restore();
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
