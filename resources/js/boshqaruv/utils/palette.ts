import { useEffect, useState } from 'react';

/**
 * Diagrammalar (recharts, xarita) uchun rang manbai.
 *
 * SVG `fill` / `stroke` atributlari `var(--kc-…)` ni tushunmaydi — ular CSS emas,
 * atribut sifatida o'qiladi. Shuning uchun bu yerda token qiymatlari ish vaqtida
 * `getComputedStyle` orqali o'qiladi va mavzu almashganda qayta hisoblanadi.
 */

// Har bir kalit: [Axelit o'zgaruvchisi, shaffoflik]. RGB uchlik o'zgaruvchilar
// (--primary, --success ...) rgba() ga, qolganlari (--font-color ...) o'zicha.
const TOKENS = {
  ink: ['--primary', 1],
  text: ['--font-color', 1],
  soft: ['--dark', 0.75],
  muted: ['--secondary', 1],
  line: ['--border_color', 1],
  grid: ['--grid_color', 1],
  surface: ['--white', 1],
  page: ['--bodybg-color', 1],
  ok: ['--success', 1],
  warn: ['--warning-dark', 1],
  danger: ['--danger', 1],
  info: ['--info', 1],
  neutral: ['--secondary', 1],
  indigo: ['--primary', 1],
  violet: ['--kc-cat-violet', 1],
  dviolet: ['--kc-cat-dviolet', 1],
  navy: ['--kc-cat-navy', 1],
  steel: ['--kc-cat-steel', 1],
  teal: ['--kc-cat-teal', 1],
  green: ['--success', 1],
  dgreen: ['--kc-cat-dgreen', 1],
  amber: ['--warning', 1],
  orange: ['--kc-cat-orange', 1],
  plum: ['--kc-cat-plum', 1],
  red: ['--danger', 0.55],
  dred: ['--kc-cat-dred', 1],
} as const;

export type PaletteKey = keyof typeof TOKENS;
export type Palette = Record<PaletteKey, string>;

/** SSR va birinchi render uchun zaxira qiymatlar (Axelit yorug' mavzusi). */
const FALLBACK: Palette = {
  ink: 'rgb(41, 128, 221)', text: '#0F172A', soft: 'rgba(15, 23, 42, .75)', muted: 'rgb(100, 116, 139)',
  line: '#E6E8EC', grid: '#EEF0F3', surface: 'rgb(255, 255, 255)', page: '#F6F7F9',
  ok: 'rgb(47, 158, 68)', warn: 'rgb(154, 74, 0)', danger: 'rgb(217, 45, 32)', info: 'rgb(112, 72, 232)', neutral: 'rgb(100, 116, 139)',
  indigo: 'rgb(41, 128, 221)', violet: '#7048E8', dviolet: '#5B3BB0', navy: '#1D4F91',
  steel: '#64748B', teal: '#0CA678', green: 'rgb(47, 158, 68)', dgreen: '#146C43',
  amber: 'rgb(245, 159, 0)', orange: '#F76707', plum: '#AE3EC9', red: 'rgba(217, 45, 32, .55)', dred: '#B42318',
};

export function readPalette(scope?: Element | null): Palette {
  if (typeof window === 'undefined' || typeof getComputedStyle !== 'function') {
    return { ...FALLBACK };
  }
  // Axelit qorong'i mavzusi body.dark da — qiymatlar body'dan o'qiladi
  const styles = getComputedStyle(scope || document.body || document.documentElement);
  const out = {} as Palette;
  (Object.keys(TOKENS) as PaletteKey[]).forEach((key) => {
    const [name, alpha] = TOKENS[key];
    const raw = styles.getPropertyValue(name).trim();
    if (!raw) { out[key] = FALLBACK[key]; return; }
    out[key] = /^\d+\s*,\s*\d+\s*,\s*\d+$/.test(raw) ? `rgba(${raw}, ${alpha})` : raw;
  });
  return out;
}

/** Mavzu (light/dark) almashganda avtomatik yangilanadigan palitra. */
export function usePalette(scope?: Element | null): Palette {
  const [palette, setPalette] = useState<Palette>(FALLBACK);

  useEffect(() => {
    const update = () => setPalette(readPalette(scope));
    update();

    const observer = new MutationObserver(update);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme', 'class'] });
    if (document.body) {
      observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }
    return () => observer.disconnect();
  }, [scope]);

  return palette;
}

/** Bir nechta qatorga navbat bilan rang tarqatish uchun. */
export const CATEGORY_ORDER: PaletteKey[] = [
  'indigo', 'green', 'amber', 'plum', 'teal', 'violet', 'steel', 'orange', 'dgreen', 'red', 'navy', 'dviolet',
];

export function categoryColor(palette: Palette, index: number): string {
  return palette[CATEGORY_ORDER[index % CATEGORY_ORDER.length]];
}
