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
  indigo: '--kc-cat-indigo',
  violet: '--kc-cat-violet',
  dviolet: '--kc-cat-dviolet',
  navy: '--kc-cat-navy',
  steel: '--kc-cat-steel',
  teal: '--kc-cat-teal',
  green: '--kc-cat-green',
  dgreen: '--kc-cat-dgreen',
  amber: '--kc-cat-amber',
  orange: '--kc-cat-orange',
  plum: '--kc-cat-plum',
  red: '--kc-cat-red',
  dred: '--kc-cat-dred',
} as const;

export type PaletteKey = keyof typeof TOKENS;
export type Palette = Record<PaletteKey, string>;

/** SSR va birinchi render uchun zaxira qiymatlar (yorug' mavzu). */
const FALLBACK: Palette = {
  ink: '#4338CA', text: '#16172A', soft: '#535873', muted: '#656A81',
  line: '#E0E3F0', grid: '#E0E3F0', surface: '#FFFFFF', page: '#F4F5FB',
  ok: '#15764D', warn: '#9A6207', danger: '#B03236', info: '#2456A8', neutral: '#5B6079',
  indigo: '#4338CA', violet: '#6532C4', dviolet: '#4C2E9E', navy: '#234F97',
  steel: '#1D5A8A', teal: '#12626F', green: '#15764D', dgreen: '#0F5D3D',
  amber: '#9A6207', orange: '#92500F', plum: '#962E68', red: '#B03236', dred: '#8A1F24',
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
