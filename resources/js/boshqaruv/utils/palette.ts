import { useEffect, useState } from 'react';

/**
 * Diagrammalar (recharts, xarita) uchun rang manbai.
 *
 * SVG `fill` / `stroke` atributlari `var(--kc-…)` ni tushunmaydi — ular CSS emas,
 * atribut sifatida o'qiladi. Shuning uchun bu yerda token qiymatlari ish vaqtida
 * `getComputedStyle` orqali o'qiladi va mavzu almashganda qayta hisoblanadi.
 */

const TOKENS = {
  ink: '--kc-ink',
  text: '--kc-text',
  soft: '--kc-text-soft',
  muted: '--kc-text-muted',
  line: '--kc-border-subtle',
  grid: '--kc-border-subtle',
  surface: '--kc-bg-card',
  page: '--kc-bg-page',
  ok: '--kc-ok',
  warn: '--kc-warn',
  danger: '--kc-danger',
  info: '--kc-info',
  neutral: '--kc-neutral',
  indigo: '--kc-chart-indigo',
  violet: '--kc-chart-violet',
  dviolet: '--kc-chart-dviolet',
  navy: '--kc-chart-navy',
  steel: '--kc-chart-steel',
  teal: '--kc-chart-teal',
  green: '--kc-chart-green',
  dgreen: '--kc-chart-dgreen',
  amber: '--kc-chart-amber',
  orange: '--kc-chart-orange',
  plum: '--kc-chart-plum',
  red: '--kc-chart-red',
  dred: '--kc-chart-dred',
} as const;

export type PaletteKey = keyof typeof TOKENS;
export type Palette = Record<PaletteKey, string>;

/** SSR va birinchi render uchun zaxira qiymatlar (yorug' mavzu). */
const FALLBACK: Palette = {
  ink: '#6A4FE3', text: '#15264B', soft: '#5E5C65', muted: '#6B6A78',
  line: '#E0DFD6', grid: '#E0DFD6', surface: '#FFFFFF', page: '#F6F6F6',
  ok: '#147834', warn: '#6B6010', danger: '#B8089A', info: '#2E5EE7', neutral: '#5A4C55',
  indigo: '#8C76F0', violet: '#B3A5F7', dviolet: '#3C2A9E', navy: '#2E5EE7',
  steel: '#6C8FEF', teal: '#1C8C8C', green: '#1E8A43', dgreen: '#0E5A27',
  amber: '#CDD13A', orange: '#E39A3B', plum: '#F00AC8', red: '#D91BAE', dred: '#7A1480',
};

export function readPalette(scope?: Element | null): Palette {
  if (typeof window === 'undefined' || typeof getComputedStyle !== 'function') {
    return { ...FALLBACK };
  }
  const styles = getComputedStyle(scope || document.documentElement);
  const out = {} as Palette;
  (Object.keys(TOKENS) as PaletteKey[]).forEach((key) => {
    const value = styles.getPropertyValue(TOKENS[key]).trim();
    out[key] = value || FALLBACK[key];
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
