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
  violet: ['--primary', 0.45],
  dviolet: ['--primary-dark', 1],
  navy: ['--info', 1],
  steel: ['--info', 0.45],
  teal: ['--info-dark', 1],
  green: ['--success', 1],
  dgreen: ['--success', 0.5],
  amber: ['--warning', 1],
  orange: ['--warning-dark', 1],
  plum: ['--danger', 1],
  red: ['--danger', 0.45],
  dred: ['--danger-dark', 1],
} as const;

export type PaletteKey = keyof typeof TOKENS;
export type Palette = Record<PaletteKey, string>;

/** SSR va birinchi render uchun zaxira qiymatlar (Axelit yorug' mavzusi). */
const FALLBACK: Palette = {
  ink: 'rgb(140, 118, 240)', text: '#15264b', soft: 'rgba(40, 38, 50, .75)', muted: 'rgb(100, 100, 100)',
  line: '#e0dfd6', grid: 'rgba(144, 164, 246, .21)', surface: 'rgb(255, 255, 255)', page: '#f6f6f6',
  ok: 'rgb(20, 120, 52)', warn: 'rgb(99, 89, 29)', danger: 'rgb(240, 10, 200)', info: 'rgb(46, 94, 231)', neutral: 'rgb(100, 100, 100)',
  indigo: 'rgb(140, 118, 240)', violet: 'rgba(140, 118, 240, .45)', dviolet: 'rgb(36, 17, 135)', navy: 'rgb(46, 94, 231)',
  steel: 'rgba(46, 94, 231, .45)', teal: 'rgb(8, 60, 128)', green: 'rgb(20, 120, 52)', dgreen: 'rgba(20, 120, 52, .5)',
  amber: 'rgb(215, 220, 65)', orange: 'rgb(99, 89, 29)', plum: 'rgb(240, 10, 200)', red: 'rgba(240, 10, 200, .45)', dred: 'rgb(102, 15, 106)',
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
